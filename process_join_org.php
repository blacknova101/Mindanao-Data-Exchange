<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['organization_id'])) {
    error_log("Join organization failed: Missing user_id or organization_id");
    header("Location: join_organization.php");
    exit();
}

$userId = $_SESSION['user_id'];
$orgId = $_POST['organization_id'];
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// First verify the organization exists and check if it has auto-accept enabled
$check_org_sql = "SELECT organization_id, auto_accept, created_by FROM organizations WHERE organization_id = ?";
$check_stmt = $conn->prepare($check_org_sql);
$check_stmt->bind_param("i", $orgId);
$check_stmt->execute();
$org_result = $check_stmt->get_result();

if ($org_result->num_rows === 0) {
    error_log("Join organization failed: Organization ID $orgId not found");
    $_SESSION['error_message'] = "Organization not found.";
    header("Location: join_organization.php");
    exit();
}

$org_data = $org_result->fetch_assoc();
$auto_accept = $org_data['auto_accept'];

// Check if the organization has an owner
if ($org_data['created_by'] === NULL) {
    error_log("Join organization failed: Organization ID $orgId has no owner");
    $_SESSION['error_message'] = "Cannot join an organization without an owner. Please contact the administrator.";
    header("Location: join_organization.php");
    exit();
}

// Check if the user already has a pending request
$check_pending_sql = "SELECT request_id, status FROM organization_membership_requests 
                      WHERE user_id = ? AND status = 'Pending'";
$check_pending_stmt = $conn->prepare($check_pending_sql);
$check_pending_stmt->bind_param("i", $userId);
$check_pending_stmt->execute();
$pending_result = $check_pending_stmt->get_result();

if ($pending_result->num_rows > 0) {
    $_SESSION['error_message'] = "You already have a pending organization membership request. Please wait for a response before submitting another request.";
    header("Location: join_organization.php");
    exit();
}

// Check if the user is already a member of an organization
$check_membership_sql = "SELECT organization_id FROM users WHERE user_id = ? AND organization_id IS NOT NULL";
$check_membership_stmt = $conn->prepare($check_membership_sql);
$check_membership_stmt->bind_param("i", $userId);
$check_membership_stmt->execute();
$membership_result = $check_membership_stmt->get_result();

if ($membership_result->num_rows > 0) {
    $_SESSION['error_message'] = "You are already a member of an organization. Please leave your current organization before joining another.";
    header("Location: join_organization.php");
    exit();
}

// Calculate expiration date (14 days from now)
$expiration_date = date('Y-m-d H:i:s', strtotime('+14 days'));

// Check if user previously had ANY request for this organization (not just rejected ones)
$check_existing_sql = "SELECT request_id, status FROM organization_membership_requests 
                      WHERE user_id = ? AND organization_id = ?";
$check_existing_stmt = $conn->prepare($check_existing_sql);
$check_existing_stmt->bind_param("ii", $userId, $orgId);
$check_existing_stmt->execute();
$existing_result = $check_existing_stmt->get_result();

// If request exists, update it instead of creating a new one
if ($existing_result->num_rows > 0) {
    $existing_row = $existing_result->fetch_assoc();
    $request_id = $existing_row['request_id'];
    
    $update_sql = "UPDATE organization_membership_requests SET status = 'Pending', request_date = NOW(), 
                  message = ?, expiration_date = ? WHERE request_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $message, $expiration_date, $request_id);
    
    if ($update_stmt->execute()) {
        // If auto_accept is enabled, automatically approve the request
        if ($auto_accept) {
            // Update the request to approved
            $approve_sql = "UPDATE organization_membership_requests SET status = 'Approved' WHERE request_id = ?";
            $approve_stmt = $conn->prepare($approve_sql);
            $approve_stmt->bind_param("i", $request_id);
            $approve_stmt->execute();
            
            // Update the user's organization
            $update_user_sql = "UPDATE users SET organization_id = ?, user_type = 'with_organization' WHERE user_id = ?";
            $update_user_stmt = $conn->prepare($update_user_sql);
            $update_user_stmt->bind_param("ii", $orgId, $userId);
            $update_user_stmt->execute();
            
            // Get organization name for the notification
            $org_name_sql = "SELECT name FROM organizations WHERE organization_id = ?";
            $org_name_stmt = $conn->prepare($org_name_sql);
            $org_name_stmt->bind_param("i", $orgId);
            $org_name_stmt->execute();
            $org_name_result = $org_name_stmt->get_result();
            $org_name_data = $org_name_result->fetch_assoc();
            $org_name = $org_name_data['name'];
            
            // Send auto-approval notification to user
            $auto_approval_message = "Your request to join organization '$org_name' has been automatically approved. You are now a member.";
            $auto_notification_type = "org_request_approved";
            
            $auto_notification_sql = "INSERT INTO user_notifications (user_id, message, related_id, notification_type) 
                                    VALUES (?, ?, ?, ?)";
            $auto_notification_stmt = $conn->prepare($auto_notification_sql);
            $auto_notification_stmt->bind_param("isis", $userId, $auto_approval_message, $orgId, $auto_notification_type);
            $auto_notification_stmt->execute();
            
            $_SESSION['success_message'] = "Your membership request has been automatically approved. You are now a member of the organization.";
            header("Location: user_settings.php");
            exit();
        }
        
        // Get organization owner ID to send notification (the creator of the organization)
        $get_owner_sql = "SELECT u.user_id, o.created_by FROM users u 
                         JOIN organizations o ON u.user_id = o.created_by 
                         WHERE o.organization_id = ? 
                         LIMIT 1";
        $get_owner_stmt = $conn->prepare($get_owner_sql);
        $get_owner_stmt->bind_param("i", $orgId);
        $get_owner_stmt->execute();
        $owner_result = $get_owner_stmt->get_result();
        
        // Debug info about the request
        error_log("Join request created - Request ID: $request_id, Organization ID: $orgId, User ID: $userId");
        
        if ($owner_result->num_rows > 0) {
            $owner = $owner_result->fetch_assoc();
            $owner_id = $owner['user_id'];
            $created_by = $owner['created_by'];
            
            error_log("Owner found - Owner ID: $owner_id, Created By: $created_by");
            
            // Send notification to organization owner
            $notification_message = "A user has requested to join your organization.";
            $notification_type = "org_join_request";
            
            $notification_sql = "INSERT INTO user_notifications (user_id, message, related_id, notification_type) 
                               VALUES (?, ?, ?, ?)";
            $notification_stmt = $conn->prepare($notification_sql);
            $notification_stmt->bind_param("isis", $owner_id, $notification_message, $request_id, $notification_type);
            
            if ($notification_stmt->execute()) {
                error_log("Notification sent to owner ID: $owner_id");
            } else {
                error_log("Failed to send notification: " . $notification_stmt->error);
            }
        } else {
            error_log("No owner found for organization ID: $orgId");
        }
        
        $_SESSION['success_message'] = "Your membership request has been resubmitted. You will be notified when the organization admin reviews your request.";
        header("Location: user_settings.php");
    } else {
        error_log("Join organization request update failed: " . $update_stmt->error);
        $_SESSION['error_message'] = "Failed to submit membership request. Please try again.";
        header("Location: join_organization.php");
    }
} else {
    // Create a new membership request
    $insert_sql = "INSERT INTO organization_membership_requests (organization_id, user_id, message, expiration_date) 
                  VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iiss", $orgId, $userId, $message, $expiration_date);
    
    if ($insert_stmt->execute()) {
        $request_id = $insert_stmt->insert_id;
        
        // If auto_accept is enabled, automatically approve the request
        if ($auto_accept) {
            // Update the request to approved
            $approve_sql = "UPDATE organization_membership_requests SET status = 'Approved' WHERE request_id = ?";
            $approve_stmt = $conn->prepare($approve_sql);
            $approve_stmt->bind_param("i", $request_id);
            $approve_stmt->execute();
            
            // Update the user's organization
            $update_user_sql = "UPDATE users SET organization_id = ?, user_type = 'with_organization' WHERE user_id = ?";
            $update_user_stmt = $conn->prepare($update_user_sql);
            $update_user_stmt->bind_param("ii", $orgId, $userId);
            $update_user_stmt->execute();
            
            // Get organization name for the notification
            $org_name_sql = "SELECT name FROM organizations WHERE organization_id = ?";
            $org_name_stmt = $conn->prepare($org_name_sql);
            $org_name_stmt->bind_param("i", $orgId);
            $org_name_stmt->execute();
            $org_name_result = $org_name_stmt->get_result();
            $org_name_data = $org_name_result->fetch_assoc();
            $org_name = $org_name_data['name'];
            
            // Send auto-approval notification to user
            $auto_approval_message = "Your request to join organization '$org_name' has been automatically approved. You are now a member.";
            $auto_notification_type = "org_request_approved";
            
            $auto_notification_sql = "INSERT INTO user_notifications (user_id, message, related_id, notification_type) 
                                    VALUES (?, ?, ?, ?)";
            $auto_notification_stmt = $conn->prepare($auto_notification_sql);
            $auto_notification_stmt->bind_param("isis", $userId, $auto_approval_message, $orgId, $auto_notification_type);
            $auto_notification_stmt->execute();
            
            $_SESSION['success_message'] = "Your membership request has been automatically approved. You are now a member of the organization.";
            header("Location: user_settings.php");
            exit();
        }
        
        // Get organization owner ID to send notification (the creator of the organization)
        $get_owner_sql = "SELECT u.user_id, o.created_by FROM users u 
                         JOIN organizations o ON u.user_id = o.created_by 
                         WHERE o.organization_id = ? 
                         LIMIT 1";
        $get_owner_stmt = $conn->prepare($get_owner_sql);
        $get_owner_stmt->bind_param("i", $orgId);
        $get_owner_stmt->execute();
        $owner_result = $get_owner_stmt->get_result();
        
        // Debug info about the request
        error_log("Join request created - Request ID: $request_id, Organization ID: $orgId, User ID: $userId");
        
        if ($owner_result->num_rows > 0) {
            $owner = $owner_result->fetch_assoc();
            $owner_id = $owner['user_id'];
            $created_by = $owner['created_by'];
            
            error_log("Owner found - Owner ID: $owner_id, Created By: $created_by");
            
            // Send notification to organization owner
            $notification_message = "A user has requested to join your organization.";
            $notification_type = "org_join_request";
            
            $notification_sql = "INSERT INTO user_notifications (user_id, message, related_id, notification_type) 
                               VALUES (?, ?, ?, ?)";
            $notification_stmt = $conn->prepare($notification_sql);
            $notification_stmt->bind_param("isis", $owner_id, $notification_message, $request_id, $notification_type);
            
            if ($notification_stmt->execute()) {
                error_log("Notification sent to owner ID: $owner_id");
            } else {
                error_log("Failed to send notification: " . $notification_stmt->error);
            }
        } else {
            error_log("No owner found for organization ID: $orgId");
        }
        
        $_SESSION['success_message'] = "Your membership request has been submitted. You will be notified when the organization admin reviews your request.";
        header("Location: user_settings.php");
    } else {
        error_log("Join organization request failed: " . $insert_stmt->error);
        $_SESSION['error_message'] = "Failed to submit membership request. Please try again.";
        header("Location: join_organization.php");
    }
}

exit();