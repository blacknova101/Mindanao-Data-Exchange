<?php
/**
 * Mindanao Data Exchange - Request Expiration Script
 * 
 * This script checks for expired organization membership requests and marks them as 'Expired'.
 * It should be run periodically via a scheduled task (cron job) to maintain the platform's
 * membership request system.
 * 
 * Recommended cron setup: Run daily at midnight
 * 0 0 * * * php /path/to/expire_requests.php
 */

// Disable direct access to this script via browser
if (php_sapi_name() !== 'cli' && isset($_SERVER['REMOTE_ADDR'])) {
    header("HTTP/1.0 403 Forbidden");
    exit("This script can only be run from the command line or a scheduled task.");
}

// Include database connection
require_once('db_connection.php');

// Get current timestamp for logging
$timestamp = date('Y-m-d H:i:s');
echo "[$timestamp] Starting organization membership request expiration check...\n";

// Find all pending requests that have expired
$find_expired_sql = "SELECT request_id, organization_id, user_id, request_date, expiration_date 
                    FROM organization_membership_requests 
                    WHERE status = 'Pending' 
                    AND expiration_date IS NOT NULL 
                    AND expiration_date < NOW()";

$expired_result = $conn->query($find_expired_sql);
$expired_count = $expired_result->num_rows;

echo "[$timestamp] Found $expired_count expired requests to process.\n";

if ($expired_count > 0) {
    // Prepare the update statement
    $update_sql = "UPDATE organization_membership_requests SET status = 'Expired' WHERE request_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    
    // Prepare the notification statement
    $notification_sql = "INSERT INTO user_notifications (user_id, message, related_id, notification_type) 
                        VALUES (?, ?, ?, ?)";
    $notification_stmt = $conn->prepare($notification_sql);
    
    while ($request = $expired_result->fetch_assoc()) {
        $request_id = $request['request_id'];
        $user_id = $request['user_id'];
        $organization_id = $request['organization_id'];
        
        // Update request status to 'Expired'
        $update_stmt->bind_param("i", $request_id);
        $update_result = $update_stmt->execute();
        
        if ($update_result) {
            echo "[$timestamp] Request ID $request_id marked as expired.\n";
            
            // Get organization name for the notification
            $org_name_sql = "SELECT name FROM organizations WHERE organization_id = ?";
            $org_name_stmt = $conn->prepare($org_name_sql);
            $org_name_stmt->bind_param("i", $organization_id);
            $org_name_stmt->execute();
            $org_name_result = $org_name_stmt->get_result();
            
            if ($org_name_result->num_rows > 0) {
                $org_data = $org_name_result->fetch_assoc();
                $org_name = $org_data['name'];
                
                // Send notification to user
                $message = "Your request to join organization '$org_name' has expired. You may submit a new request if you are still interested.";
                $notification_type = "org_request_expired";
                
                $notification_stmt->bind_param("isis", $user_id, $message, $organization_id, $notification_type);
                $notification_result = $notification_stmt->execute();
                
                if ($notification_result) {
                    echo "[$timestamp] Notification sent to user ID $user_id about expired request.\n";
                    
                    // Also notify the organization owner
                    $get_owner_sql = "SELECT created_by FROM organizations WHERE organization_id = ?";
                    $get_owner_stmt = $conn->prepare($get_owner_sql);
                    $get_owner_stmt->bind_param("i", $organization_id);
                    $get_owner_stmt->execute();
                    $owner_result = $get_owner_stmt->get_result();
                    
                    if ($owner_result->num_rows > 0) {
                        $owner_id = $owner_result->fetch_assoc()['created_by'];
                        
                        // Get user name
                        $get_user_sql = "SELECT CONCAT(first_name, ' ', last_name) as user_name FROM users WHERE user_id = ?";
                        $get_user_stmt = $conn->prepare($get_user_sql);
                        $get_user_stmt->bind_param("i", $user_id);
                        $get_user_stmt->execute();
                        $user_result = $get_user_stmt->get_result();
                        
                        if ($user_result->num_rows > 0) {
                            $user_name = $user_result->fetch_assoc()['user_name'];
                            
                            $owner_message = "A membership request from $user_name has expired.";
                            $notification_stmt->bind_param("isis", $owner_id, $owner_message, $organization_id, $notification_type);
                            $owner_notification_result = $notification_stmt->execute();
                            
                            if ($owner_notification_result) {
                                echo "[$timestamp] Notification sent to organization owner (ID: $owner_id) about expired request.\n";
                            }
                        }
                    }
                } else {
                    echo "[$timestamp] ERROR: Failed to send notification to user ID $user_id: " . $notification_stmt->error . "\n";
                }
            }
        } else {
            echo "[$timestamp] ERROR: Failed to update request ID $request_id: " . $update_stmt->error . "\n";
        }
    }
    
    // Close prepared statements
    $update_stmt->close();
    $notification_stmt->close();
} else {
    echo "[$timestamp] No expired requests found. Nothing to do.\n";
}

echo "[$timestamp] Request expiration check completed.\n";

// Close database connection
$conn->close(); 