<?php
session_start();
include('db_connection.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if the user is the organization owner (creator) or a moderator
$check_owner_sql = "SELECT u.organization_id, o.name, o.auto_accept, o.created_by,
                   (o.created_by = ?) AS is_owner
                   FROM users u 
                   JOIN organizations o ON u.organization_id = o.organization_id 
                   WHERE u.user_id = ? AND o.created_by = ?";
$check_owner_stmt = $conn->prepare($check_owner_sql);
$check_owner_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$check_owner_stmt->execute();
$owner_result = $check_owner_stmt->get_result();

// If not the owner, check if the user is a regular member
if ($owner_result->num_rows === 0) {
    $check_member_sql = "SELECT u.organization_id, o.name, o.auto_accept, o.created_by, 0 AS is_owner
                       FROM users u 
                       JOIN organizations o ON u.organization_id = o.organization_id 
                       WHERE u.user_id = ?";
    $check_member_stmt = $conn->prepare($check_member_sql);
    $check_member_stmt->bind_param("i", $user_id);
    $check_member_stmt->execute();
    $member_result = $check_member_stmt->get_result();
    
    if ($member_result->num_rows === 0) {
        // Not authorized to manage requests
        $_SESSION['error_message'] = "You do not have permission to manage organization membership requests. Only the organization owner or members can manage membership requests.";
        header("Location: user_settings.php");
        exit();
    }
    
    $admin_data = $member_result->fetch_assoc();
    $organization_id = $admin_data['organization_id'];
    $organization_name = $admin_data['name'];
    $auto_accept = $admin_data['auto_accept'];
    $is_owner = false;
    $is_moderator = false; // Regular member
} else {
    $admin_data = $owner_result->fetch_assoc();
    $organization_id = $admin_data['organization_id'];
    $organization_name = $admin_data['name'];
    $auto_accept = $admin_data['auto_accept'];
    $is_owner = $admin_data['is_owner'];
    $is_moderator = false; // Owner is separate from moderator
}

// Toggle auto-accept setting (only owner can do this)
if (isset($_POST['submit_auto_accept']) && $is_owner) {
    $new_auto_accept = isset($_POST['toggle_auto_accept']) ? 1 : 0;
    $update_auto_accept_sql = "UPDATE organizations SET auto_accept = ? WHERE organization_id = ? AND created_by = ?";
    $update_auto_accept_stmt = $conn->prepare($update_auto_accept_sql);
    $update_auto_accept_stmt->bind_param("iii", $new_auto_accept, $organization_id, $user_id);
    
    if ($update_auto_accept_stmt->execute()) {
        $auto_accept = $new_auto_accept;
        $_SESSION['message'] = "Auto-accept setting has been " . ($new_auto_accept ? "enabled" : "disabled") . ".";
    } else {
        $_SESSION['message'] = "Failed to update auto-accept setting.";
    }
}

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $admin_response = isset($_POST['admin_response']) ? trim($_POST['admin_response']) : '';
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'Approved' : 'Rejected';
        
        // Get request data before updating
        $requestDataSql = "
            SELECT 
                omr.user_id as requester_id,
                u.first_name,
                u.last_name,
                u.email
            FROM 
                organization_membership_requests omr
            JOIN 
                users u ON omr.user_id = u.user_id
            WHERE 
                omr.request_id = ? 
                AND omr.organization_id = ?";
        
        $requestDataStmt = $conn->prepare($requestDataSql);
        $requestDataStmt->bind_param("ii", $request_id, $organization_id);
        $requestDataStmt->execute();
        $requestDataResult = $requestDataStmt->get_result();
        $requestData = $requestDataResult->fetch_assoc();
        
        if ($requestData) {
            // Update the request status with admin response
            $updateSql = "UPDATE organization_membership_requests 
                         SET status = ?, admin_response = ? 
                         WHERE request_id = ? 
                         AND organization_id = ?";
            
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssis", $status, $admin_response, $request_id, $organization_id);
            $updateStmt->execute();
            
            // If approved, update the user's organization
            if ($action === 'approve') {
                $requester_id = $requestData['requester_id'];
                
                $updateUserSql = "UPDATE users 
                                 SET organization_id = ?, user_type = 'with_organization' 
                                 WHERE user_id = ?";
                $updateUserStmt = $conn->prepare($updateUserSql);
                $updateUserStmt->bind_param("ii", $organization_id, $requester_id);
                $updateUserStmt->execute();
                
                // If the current user is the one being approved, update their session too
                if ($requester_id === $user_id) {
                    $_SESSION['organization_id'] = $organization_id;
                    $_SESSION['user_type'] = 'with_organization';
                }
            }
            
            // Add notification for the requester
            $requester_id = $requestData['requester_id'];
            $requester_name = $requestData['first_name'] . ' ' . $requestData['last_name'];
            
            if ($action === 'approve') {
                $message = "Your request to join organization '$organization_name' has been approved. You are now a member.";
                if (!empty($admin_response)) {
                    $message .= " Admin message: " . $admin_response;
                }
                $notification_type = "org_request_approved";
            } else {
                $message = "Your request to join organization '$organization_name' has been rejected.";
                if (!empty($admin_response)) {
                    $message .= " Reason: " . $admin_response;
                }
                $notification_type = "org_request_rejected";
            }
            
            // Insert notification
            $insertNotificationSql = "
                INSERT INTO user_notifications (
                    user_id,
                    message,
                    related_id,
                    notification_type,
                    is_read
                ) VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    FALSE
                )";
            
            $notificationStmt = $conn->prepare($insertNotificationSql);
            $notificationStmt->bind_param("isis", $requester_id, $message, $organization_id, $notification_type);
            $notificationStmt->execute();
            
            $_SESSION['message'] = "Request has been " . strtolower($status) . " and the requester has been notified.";
        } else {
            $_SESSION['message'] = "Error: Request not found or you don't have permission to modify it.";
        }
    }
}

// Fetch all membership requests for the organization
$requestsSql = "
    SELECT 
        omr.request_id,
        omr.user_id,
        omr.request_date,
        omr.status,
        omr.message,
        omr.admin_response,
        omr.expiration_date,
        u.first_name,
        u.last_name,
        u.email
    FROM 
        organization_membership_requests omr
    JOIN 
        users u ON omr.user_id = u.user_id
    WHERE 
        omr.organization_id = ?
    ORDER BY 
        omr.status = 'Pending' DESC,
        omr.request_date DESC
";

$requestsStmt = $conn->prepare($requestsSql);
$requestsStmt->bind_param("i", $organization_id);
$requestsStmt->execute();
$requestsResult = $requestsStmt->get_result();

// Debug information
echo "<!-- Debug Info: 
    Organization ID: " . $organization_id . "
    Number of requests found: " . $requestsResult->num_rows . "
    Current user (owner) ID: " . $user_id . " 
-->";

// Also check if there are any requests in the system at all
$checkAllRequestsSql = "SELECT COUNT(*) as total FROM organization_membership_requests";
$allRequestsResult = $conn->query($checkAllRequestsSql);
$allRequestsCount = $allRequestsResult->fetch_assoc()['total'];
echo "<!-- Total requests in system: " . $allRequestsCount . " -->";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Manage Organization Membership Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            color: #333;
            overflow-x: hidden;
            min-width: 320px;
        }
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5%;
            padding-left: 30px;
            background-color: #0099ff;
            color: #cfd9ff;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            margin: 10px 0;
            backdrop-filter: blur(10px);
            max-width: 1200px;
            width: 100%;
            margin-top: 30px;
            margin-left: auto;
            margin-right: auto;
            font-weight: bold;
            box-sizing: border-box;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo img {
            height: auto;
            width: 80px;
            max-width: 100%;
        }
        .logo h2 {
            color: white;
            margin: 0 0 0 15px;
            font-size: 20px;
        }
        .nav-links {
            display: flex;
            align-items: center;
        }
        .nav-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-size: 18px;
            transition: transform 0.3s ease;
        }
        .nav-links a:hover {
            transform: scale(1.2);
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        h1, h2 {
            color: #007BFF;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .settings-panel {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        .toggle-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #2196F3;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        .setting-description {
            flex: 1;
            margin-right: 20px;
        }
        .requests-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }
        .requests-table th, .requests-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .requests-table th:nth-child(1) { width: 25%; }  /* Requester - increased width */
        .requests-table th:nth-child(2) { width: 15%; }  /* Date */
        .requests-table th:nth-child(3) { width: 20%; }  /* Message */
        .requests-table th:nth-child(4) { width: 15%; }  /* Status */
        .requests-table th:nth-child(5) { width: 25%; }  /* Actions */
        .requests-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #007BFF;
        }
        .requests-table tr:hover {
            background-color: #f1f1f1;
        }
        .btn {
            padding: 8px 12px;
            margin-right: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            color: white;
        }
        .btn-approve {
            background-color: #28a745;
        }
        .btn-approve:hover {
            background-color: #218838;
        }
        .btn-reject {
            background-color: #dc3545;
        }
        .btn-reject:hover {
            background-color: #c82333;
        }
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        .status-approved {
            color: #28a745;
            font-weight: bold;
        }
        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }
        .status-expired {
            color: #6c757d;
            font-weight: bold;
        }
        .no-requests {
            padding: 20px;
            text-align: center;
            color: #6c757d;
        }
        #background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .message-text {
            max-height: 120px;
            overflow-y: auto;
            font-size: 14px;
            padding: 5px;
            background-color: #f9f9f9;
            border-radius: 4px;
            line-height: 1.4;
            word-wrap: break-word;
            white-space: pre-line;
            max-width: 250px;
        }
        
        /* Webkit scrollbar styling */
        .message-text::-webkit-scrollbar {
            width: 6px;
        }
        
        .message-text::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .message-text::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .message-text::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        .user-info {
            display: flex;
            align-items: flex-start;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            background-color: #e9ecef;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .user-details {
            width: 100%;
            overflow: hidden;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .user-details strong {
            display: block;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .user-details small, .user-email {
            display: block;
            word-wrap: break-word;
            overflow-wrap: break-word;
            width: 100%;
            white-space: normal;
            line-height: 1.3;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 8px;
            width: 50%;
            max-width: 600px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        #modal-message-content {
            max-height: 300px;
            overflow-y: auto;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
            margin-top: 10px;
            line-height: 1.5;
            white-space: pre-wrap !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            max-width: 100% !important;
        }
        
        #modal-message-content * {
            white-space: pre-wrap !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            line-height: 1.8 !important;
        }
        
        .response-modal-content {
            width: 500px;
        }
        
        .response-textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
            font-family: inherit;
            box-sizing: border-box;
            resize: vertical;
        }
        
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }
        
        .modal-buttons button {
            margin-left: 10px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
        }
        
        .expiration-warning {
            color: #e67e22;
            font-size: 12px;
            margin-top: 5px;
        }
        
        /* Mobile menu toggle button */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
        }
        
        /* Responsive styles */
        @media screen and (max-width: 992px) {
            .container {
                width: 90%;
                padding: 15px;
            }
            
            .navbar {
                padding: 10px 20px;
                width: 90%;
            }
            
            .logo h2 {
                font-size: 18px;
            }
            
            .modal-content {
                width: 70%;
            }
            
            .message-text {
                max-width: 200px;
            }
            
            .response-modal-content {
                width: 90%;
                max-width: 500px;
            }
        }
        
        @media screen and (max-width: 768px) {
            .navbar {
                padding: 10px 15px;
                width: calc(100% - 20px);
                margin-left: 10px;
                margin-right: 10px;
            }
            
            .mobile-menu-toggle {
                display: block;
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .logo img {
                width: 50px;
            }
            
            .logo h2 {
                font-size: 16px;
                margin-left: 10px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 200px;
            }
            
            .nav-links {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                flex-direction: column;
                background-color: #0099ff;
                padding: 10px 0;
                border-radius: 0 0 15px 15px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                display: none;
                z-index: 1000;
            }
            
            .nav-links.active {
                display: flex;
            }
            
            .nav-links a {
                width: 100%;
                text-align: center;
                padding: 10px 0;
                margin: 0;
            }
            
            .container {
                padding: 10px;
                margin: 20px auto;
            }
            
            .toggle-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .setting-description {
                margin-right: 0;
                margin-bottom: 15px;
                width: 100%;
            }
            
            /* Table responsive */
            .requests-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
            }
            
            .requests-table thead, 
            .requests-table tbody,
            .requests-table tr {
                display: block;
                width: 100%;
            }
            
            .requests-table tr {
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 10px;
            }
            
            .requests-table td, 
            .requests-table th {
                display: flex;
                flex-direction: column;
                border: none;
                padding: 8px 5px;
                text-align: left;
                white-space: normal;
                width: 100% !important;
                word-wrap: break-word;
                overflow-wrap: break-word;
                box-sizing: border-box;
            }
            
            .requests-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            .requests-table td {
                position: relative;
                padding-left: 50%;
                border-bottom: 1px solid #eee;
                min-height: 40px;
            }
            
            .requests-table td::before {
                position: absolute;
                left: 10px;
                width: 45%;
                white-space: nowrap;
                font-weight: bold;
                color: #007BFF;
            }
            
            .requests-table td:nth-of-type(1)::before { content: "Requester"; }
            .requests-table td:nth-of-type(2)::before { content: "Date Requested"; }
            .requests-table td:nth-of-type(3)::before { content: "Message"; }
            .requests-table td:nth-of-type(4)::before { content: "Status"; }
            .requests-table td:nth-of-type(5)::before { content: "Actions"; }
            
            .btn {
                margin-bottom: 5px;
                width: 100%;
                box-sizing: border-box;
                text-align: center;
            }
            
            .message-text {
                max-width: 100%;
                width: 100%;
            }
            
            .modal-content {
                width: 90%;
                margin: 30% auto;
                padding: 15px;
            }
            
            .user-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-avatar {
                margin-bottom: 10px;
            }
            
            .modal-buttons {
                flex-direction: column;
            }
            
            .modal-buttons button {
                margin: 5px 0;
                width: 100%;
            }
        }
        
        @media screen and (max-width: 480px) {
            .navbar {
                padding: 8px 10px;
            }
            
            .logo img {
                width: 40px;
            }
            
            .logo h2 {
                font-size: 14px;
                margin-left: 8px;
                max-width: 150px;
            }
            
            h1 {
                font-size: 20px;
            }
            
            .container {
                padding: 8px;
                margin: 15px auto;
                width: 95%;
            }
            
            .response-textarea {
                min-height: 80px;
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>
    
    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mindanao Data Exchange Logo">
            <h2>Manage Organization Membership</h2>
        </div>
        <button class="mobile-menu-toggle" id="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <nav class="nav-links" id="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="datasets.php">ALL DATASETS</a>
            <a href="mydatasets.php">MY DATASETS</a>
            <a href="user_settings.php"><i class="fas fa-user-circle"></i></a>
        </nav>
    </header>
    
    <div class="container">
        <h1>Membership Requests for <?php echo htmlspecialchars($organization_name); ?></h1>
        
        <?php if ($is_moderator && !$is_owner): ?>
        <div class="alert" style="background-color: #d4edda; color: #155724; border-color: #c3e6cb;">
            You are viewing this page as a member of the organization. You can approve or reject membership requests, but only the organization owner can change organization settings.
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']); 
                ?>
            </div>
        <?php endif; ?>
        
        <div class="settings-panel">
            <h3>Organization Settings</h3>
            <form method="POST" class="toggle-container">
                <div class="setting-description">
                    <strong>Auto-Accept New Members</strong>
                    <p>When enabled, all new membership requests will be automatically approved without requiring admin review.</p>
                </div>
                <div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="toggle_auto_accept" <?php echo $auto_accept ? 'checked' : ''; ?> <?php echo (!$is_owner) ? 'disabled' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <?php if ($is_owner): ?>
                    <button type="submit" name="submit_auto_accept" class="btn" style="background-color: #0099ff; margin-top: 10px;">Save Settings</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <?php if ($requestsResult->num_rows > 0): ?>
            <table class="requests-table">
                <thead>
                    <tr>
                        <th>Requester</th>
                        <th>Date Requested</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($request = $requestsResult->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <img src="images/avatarIconunknown.jpg" alt="Profile" class="user-avatar">
                                    <div class="user-details">
                                        <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong>
                                        <small class="user-email"><?php echo htmlspecialchars($request['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php echo date('M j, Y, g:i a', strtotime($request['request_date'])); ?>
                                <?php if ($request['status'] === 'Pending' && !empty($request['expiration_date'])): ?>
                                    <div class="expiration-warning">
                                        Expires: <?php echo date('M j, Y', strtotime($request['expiration_date'])); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="message-text">
                                    <?php if (!empty($request['message'])): ?>
                                        <div class="message-preview">
                                            <?php echo nl2br(htmlspecialchars($request['message'])); ?>
                                        </div>
                                        <button type="button" class="see-all-btn" onclick="showMessageModal(<?php echo $request['request_id']; ?>)">Show More</button>
                                        <input type="hidden" id="message-<?php echo $request['request_id']; ?>" value="<?php echo htmlspecialchars($request['message']); ?>">
                                    <?php else: ?>
                                        <em>No message provided</em>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-<?php echo strtolower($request['status']); ?>">
                                    <?php echo $request['status']; ?>
                                </span>
                                <?php if (!empty($request['admin_response']) && ($request['status'] === 'Approved' || $request['status'] === 'Rejected')): ?>
                                    <button type="button" class="see-all-btn" onclick="showResponseModal(<?php echo $request['request_id']; ?>)">View Response</button>
                                    <input type="hidden" id="response-<?php echo $request['request_id']; ?>" value="<?php echo htmlspecialchars($request['admin_response']); ?>">
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($request['status'] === 'Pending'): ?>
                                    <button type="button" class="btn btn-approve" onclick="showResponseForm(<?php echo $request['request_id']; ?>, 'approve')">Approve</button>
                                    <button type="button" class="btn btn-reject" onclick="showResponseForm(<?php echo $request['request_id']; ?>, 'reject')">Reject</button>
                                <?php else: ?>
                                    <span class="completed-action">
                                        <?php echo ($request['status'] === 'Approved') ? 'Approved' : (($request['status'] === 'Rejected') ? 'Rejected' : 'Expired'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-requests">
                <p>You don't have any organization membership requests to manage.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Message Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideMessageModal()">&times;</span>
            <h3>Request Message</h3>
            <div id="modal-message-content"></div>
        </div>
    </div>
    
    <!-- Admin Response Modal -->
    <div id="responseModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideResponseModal()">&times;</span>
            <h3>Admin Response</h3>
            <div id="modal-response-content"></div>
        </div>
    </div>
    
    <!-- Response Form Modal -->
    <div id="responseFormModal" class="modal">
        <div class="modal-content response-modal-content">
            <span class="close" onclick="hideResponseFormModal()">&times;</span>
            <h3 id="responseFormTitle">Approve Request</h3>
            <form id="responseForm" method="POST">
                <input type="hidden" name="request_id" id="response_request_id">
                <input type="hidden" name="action" id="response_action">
                
                <p>Add an optional message to include with your decision:</p>
                <textarea name="admin_response" class="response-textarea" placeholder="Your response message (optional)"></textarea>
                
                <div class="modal-buttons">
                    <button type="button" class="btn" style="background-color: #6c757d;" onclick="hideResponseFormModal()">Cancel</button>
                    <button type="submit" class="btn" id="confirmActionBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show request message modal
        function showMessageModal(requestId) {
            const fullMessage = document.getElementById('message-' + requestId).value;
            
            const messageParagraph = document.createElement('p');
            messageParagraph.style.margin = '0';
            messageParagraph.style.padding = '0';
            messageParagraph.style.whiteSpace = 'pre-wrap';
            messageParagraph.style.wordBreak = 'break-word';
            messageParagraph.textContent = fullMessage;
            
            const modalContent = document.getElementById('modal-message-content');
            modalContent.innerHTML = '';
            modalContent.appendChild(messageParagraph);
            
            document.getElementById('messageModal').style.display = 'block';
        }
        
        // Hide request message modal
        function hideMessageModal() {
            document.getElementById('messageModal').style.display = 'none';
        }
        
        // Show admin response modal
        function showResponseModal(requestId) {
            const response = document.getElementById('response-' + requestId).value;
            
            const responseParagraph = document.createElement('p');
            responseParagraph.style.margin = '0';
            responseParagraph.style.padding = '0';
            responseParagraph.style.whiteSpace = 'pre-wrap';
            responseParagraph.style.wordBreak = 'break-word';
            responseParagraph.textContent = response;
            
            const responseContent = document.getElementById('modal-response-content');
            responseContent.innerHTML = '';
            responseContent.appendChild(responseParagraph);
            
            document.getElementById('responseModal').style.display = 'block';
        }
        
        // Hide admin response modal
        function hideResponseModal() {
            document.getElementById('responseModal').style.display = 'none';
        }
        
        // Show response form modal for approving/rejecting
        function showResponseForm(requestId, action) {
            document.getElementById('response_request_id').value = requestId;
            document.getElementById('response_action').value = action;
            
            const title = document.getElementById('responseFormTitle');
            const confirmBtn = document.getElementById('confirmActionBtn');
            
            if (action === 'approve') {
                title.textContent = 'Approve Membership Request';
                confirmBtn.textContent = 'Approve';
                confirmBtn.className = 'btn btn-approve';
            } else {
                title.textContent = 'Reject Membership Request';
                confirmBtn.textContent = 'Reject';
                confirmBtn.className = 'btn btn-reject';
            }
            
            document.getElementById('responseFormModal').style.display = 'block';
        }
        
        // Hide response form modal
        function hideResponseFormModal() {
            document.getElementById('responseFormModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const messageModal = document.getElementById('messageModal');
            if (event.target == messageModal) {
                hideMessageModal();
            }
            
            const responseModal = document.getElementById('responseModal');
            if (event.target == responseModal) {
                hideResponseModal();
            }
            
            const responseFormModal = document.getElementById('responseFormModal');
            if (event.target == responseFormModal) {
                hideResponseFormModal();
            }
        }
        
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const navLinks = document.getElementById('nav-links');
            
            mobileMenuToggle.addEventListener('click', function() {
                navLinks.classList.toggle('active');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInsideNavbar = event.target.closest('.navbar');
                if (!isClickInsideNavbar && navLinks.classList.contains('active')) {
                    navLinks.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html> 