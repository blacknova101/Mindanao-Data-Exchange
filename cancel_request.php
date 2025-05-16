<?php
session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if request_id is provided
if (!isset($_POST['request_id']) || !is_numeric($_POST['request_id'])) {
    $_SESSION['error_message'] = "Invalid request. Please try again.";
    header("Location: user_settings.php");
    exit();
}

$request_id = (int)$_POST['request_id'];

// Verify that the request belongs to the current user and is still pending
$check_sql = "SELECT omr.request_id, omr.organization_id, o.name as organization_name
             FROM organization_membership_requests omr
             JOIN organizations o ON omr.organization_id = o.organization_id
             WHERE omr.request_id = ? AND omr.user_id = ? AND omr.status = 'Pending'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $request_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $_SESSION['error_message'] = "Request not found or you don't have permission to cancel it.";
    header("Location: user_settings.php");
    exit();
}

$request_data = $check_result->fetch_assoc();
$organization_name = $request_data['organization_name'];

// Delete the request
$delete_sql = "DELETE FROM organization_membership_requests WHERE request_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $request_id);

if ($delete_stmt->execute()) {
    $_SESSION['success_message'] = "Your request to join '$organization_name' has been cancelled.";
    
    // Notify organization owner that request was cancelled
    $get_owner_sql = "SELECT u.user_id FROM users u 
                     JOIN organizations o ON u.user_id = o.created_by 
                     WHERE o.organization_id = ? 
                     LIMIT 1";
    $get_owner_stmt = $conn->prepare($get_owner_sql);
    $get_owner_stmt->bind_param("i", $request_data['organization_id']);
    $get_owner_stmt->execute();
    $owner_result = $get_owner_stmt->get_result();
    
    if ($owner_result->num_rows > 0) {
        // Get user name for notification
        $get_user_sql = "SELECT CONCAT(first_name, ' ', last_name) as user_name FROM users WHERE user_id = ?";
        $get_user_stmt = $conn->prepare($get_user_sql);
        $get_user_stmt->bind_param("i", $user_id);
        $get_user_stmt->execute();
        $user_result = $get_user_stmt->get_result();
        $user_data = $user_result->fetch_assoc();
        $user_name = $user_data['user_name'];
        
        // Create notification for organization owner
        $notification_sql = "INSERT INTO user_notifications (user_id, message, related_id, notification_type, is_read) 
                           VALUES (?, ?, ?, ?, FALSE)";
        $notification_stmt = $conn->prepare($notification_sql);
        $notification_type = "org_request_cancelled";
        $message = "$user_name has cancelled their request to join your organization.";
        
        $owner_id = $owner_result->fetch_assoc()['user_id'];
        $notification_stmt->bind_param("isis", $owner_id, $message, $request_data['organization_id'], $notification_type);
        $notification_stmt->execute();
    }
} else {
    $_SESSION['error_message'] = "Failed to cancel request. Please try again.";
}

header("Location: user_settings.php");
exit(); 