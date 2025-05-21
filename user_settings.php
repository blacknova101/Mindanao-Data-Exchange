<?php
session_start();
include 'db_connection.php'; // Make sure this file sets up $conn

// Check if the user is logged in (ensure 'user_id' is set in the session)
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not authenticated
    header("Location: login.php");
    exit();
}

// Initialize the total_count variable
$total_count = 0;

// Get count of pending access requests for this user
$request_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $requestCountSql = "SELECT COUNT(*) as count FROM dataset_access_requests 
                        WHERE owner_id = $user_id AND status = 'Pending'";
    $requestCountResult = mysqli_query($conn, $requestCountSql);
    if ($requestCountResult) {
        $row = mysqli_fetch_assoc($requestCountResult);
        $request_count = $row['count'];
    }
}

// Get count of unread notifications for this user
$notif_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $notifCountSql = "SELECT COUNT(*) as count FROM user_notifications 
                      WHERE user_id = $user_id AND is_read = FALSE";
    $notifCountResult = mysqli_query($conn, $notifCountSql);
    if ($notifCountResult) {
        $row = mysqli_fetch_assoc($notifCountResult);
        $notif_count = $row['count'];
    }
}

// Total count for badge display (requests + notifications)
$total_count = $request_count + $notif_count;

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT u.*, o.name as organization_name, o.organization_id 
        FROM users u 
        LEFT JOIN organizations o ON u.organization_id = o.organization_id 
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check for pending organization membership requests
$pending_request_sql = "SELECT omr.*, o.name as organization_name, omr.expiration_date
                      FROM organization_membership_requests omr
                      JOIN organizations o ON omr.organization_id = o.organization_id
                      WHERE omr.user_id = ? AND omr.status = 'Pending'";
$pending_request_stmt = $conn->prepare($pending_request_sql);
$pending_request_stmt->bind_param("i", $user_id);
$pending_request_stmt->execute();
$pending_request_result = $pending_request_stmt->get_result();
$has_pending_request = ($pending_request_result->num_rows > 0);
$pending_request = $has_pending_request ? $pending_request_result->fetch_assoc() : null;

// Debug information
error_log("User Settings - User ID: " . $user_id);
error_log("User Settings - Organization ID: " . ($user['organization_id'] ?? 'NULL'));
error_log("User Settings - Organization Name: " . ($user['organization_name'] ?? 'NULL'));

// If user not found, redirect to login
if (!$user) {
    header("Location: login.php");
    exit();
}

$organizationName = $user['organization_name'] ?? '';
$organizationId = $user['organization_id'] ?? null;
$hasOrganization = !empty($organizationId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('images/Mindanao.png');
            background-size: cover;
            background-attachment: fixed;
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
            z-index: 999;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo img {
            height: auto;
            width: 80px;
            max-width: 100%;
            margin-right: 15px;
        }
        .search-bar {
            flex-grow: 1;
            display: flex;
            align-items: center;
            position: relative;
            margin-left: -190px;
        }
        .search-bar input {
            padding: 8px;
            width: 400px;
            border-radius: 5px;
            border: none;
        }
        .search-bar button {
            background: none;
            border: none;
            cursor: pointer;
        }
        .search-bar img {
            width: 20px;
            height: 20px;
            margin-left: -50px;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
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
        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white; 
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 20px;
        }
        .profile-icon img {
            width: 150%;
            height: auto;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
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
        
        .mobile-menu-toggle i {
            display: block;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .navbar {
                padding: 10px;
                border-radius: 15px;
                width: 90%;
                max-width: 90%;
            }
            
            .logo {
                flex-direction: row;
                text-align: center;
                max-width: 80%;
            }
            
            .logo img {
                width: 50px;
                margin-right: 10px;
            }
            
            .search-bar {
                display: none;
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
                z-index: 9999;
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
            
            .profile-icon {
                margin-left: 0;
            }
            
            .stats-box {
                flex-direction: column;
                width: 80%;
                font-size: 24px;
            }
            
            .divider {
                width: 100%;
                height: 2px;
                margin: 20px 0;
            }
            
            h1 {
                font-size: 40px;
            }
        }

        .container {
            width: 70%;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
        }
        
        .settings-container {
            width: 100%;
            max-width: 1200px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        
        .settings-container h2 {
            margin: 0 auto;
            font-size: 25px;
            font-weight: bold;
            text-align: left;
            color: black;
        }
        
        @media (max-width: 1200px) {
            .settings-container {
                padding: 20px;
                width: 90%;
            }
        }

        @media (max-width: 600px) {
            .settings-container {
                width: 90%;
                padding: 15px;
            }
        }

        #uploadForm {
            padding: 20px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .drop-area {
            border: 2px dashed black;
            padding: 50px 200px 50px 200px;
            margin: 0 auto;
        }
        .drop-area.dragover {
            background-color: #e6ebff;
            border-color: #0c1a36;
        }
        .drop-area img {
            width: 50px;
            margin-bottom: 10px;
        }
        .drop-area p {
            font-size: 16px;
            color: #333;
            margin: 5px 0;
        }
        input[type="file"] {
            display: none;
        }
        .browse-btn {
            background-color: #0c1a36;
            color: white;
            padding: 10px;
            margin: 0 auto;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            display: block;
            margin-top: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 50%;
            max-width: 600px;
            text-align: center;
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .category-grid div {
            padding: 10px;
            background: #e3f2fd;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .category-grid div:hover {
            background: #bbdefb;
        }
        .close-btn {
            background: red;
            color: white;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        @media (max-width: 600px) {
            .container {
                width: 90%;
            }
            
            .category-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 90%;
            }
        }
        
        #errormessage{
            color: red;
        }

        .profile-container {
            width: 100%;
            max-width: 500px;
            display: flex;
            justify-content: space-between;
            gap: 30px;
        }

        .form-group {
            padding-top: 10px;
            padding-left: 15px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input {
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            width: 100%;
        }

        .form-group input[type="file"] {
            padding: 5px;
        }
        
        #email, #current_password, #new_password, #confirm_password {
            width: 100%;
            max-width: 468px;
        }
        
        #first_name, #last_name {
            width: 100%;
            max-width: 200px;
        }
        
        #confirmpass{
            padding-bottom: 20px;
        }

        .submit-btn {
            padding: 10px 20px;
            background-color: #0099ff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            height: 42px;
            min-width: 120px;
            max-width: 200px;
        }

        .submit-btn:hover {
            background-color: #007acc;
        }
        
        #profpic-firstname {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 10px;
        }

        #avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
        }

        #header #first_name, #header #last_name {
            font-size: 25px;
            font-weight: bold;
        }

        .section-divider {
            border: none;
            border-top: 2px solid black;
            margin-bottom: 10px;
        }
        
        #first_name[readonly],
        #last_name[readonly] {
            background-color: #d3d3d3;
            cursor: not-allowed;
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
        
        #profile-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 50px;
            justify-content: center;
            margin-bottom: 50px;
        }
        
        #profile {
            width: 100%;
            max-width: 525px;
            padding: 25px;
            background-color: #f2f2f2;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        #change-password {
            width: 100%;
            max-width: 525px;
            padding: 25px;
            background-color: #f2f2f2;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            clear: both;
            box-sizing: border-box;
        }
        
        #change-password h2 {
            margin-bottom: 20px;
            text-align: left;
            padding-left: 0;
        }
        
        #change-password .form-group {
            width: 100%;
            padding-right: 15px;
            box-sizing: border-box;
        }
        
        #change-password input[type="password"] {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-bottom: 15px;
            box-sizing: border-box;
        }
        
        #change-password .submit-btn {
            width: auto;
            min-width: 200px;
            margin: 10px auto;
            display: block;
        }
        
        #organization-container {
            width: 100%;
            max-width: 525px;
            padding: 25px;
            background-color: #f2f2f2;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }
        
        .create-org-btn {
            background-color: #0099ff;
            color: white;
            padding: 10px;
            margin-top: 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
            width: 100%;
        }
        
        #org{
            margin-bottom: 23px;
        }
        
        .create-org-btn:disabled {
            background-color: #cccccc;
            color: #666666;
            cursor: not-allowed;
        }
        
        .leave-org-modal-content {
            background: #fff;
            border: 2px solid #0099ff;
            color: #ff4d4d;
            box-shadow: 0 0 20px #0099ff22;
            padding: 30px 20px;
            border-radius: 16px;
            max-width: 400px;
            margin: auto;
            text-align: center;
            position: relative;
        }
        
        .leave-org-modal-content h3 {
            color: #ff4d4d;
            margin-bottom: 10px;
        }
        
        .leave-org-modal-content p {
            color: #0099ff;
            margin-bottom: 20px;
        }
        
        .leave-org-modal-content input[type="text"] {
            border: 1.5px solid #0099ff;
            border-radius: 5px;
            padding: 8px;
            width: 80%;
            margin-bottom: 15px;
            color: #222;
            background: #fff;
        }
        
        .leave-org-modal-content button[type="submit"] {
            background: #0099ff;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px 18px;
            font-weight: bold;
            margin-right: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .leave-org-modal-content button[type="submit"]:hover {
            background: #007acc;
        }
        
        .leave-org-modal-content button[type="button"] {
            background: #fff;
            color: #0099ff;
            border: 1.5px solid #0099ff;
            border-radius: 5px;
            padding: 10px 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        
        .leave-org-modal-content button[type="button"]:hover {
            background: #0099ff;
            color: #fff;
        }
        
        .leave-org-modal-content .warning-icon {
            font-size: 40px;
            color: #ff4d4d;
            margin-bottom: 10px;
            display: block;
        }

        .floating-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #00cc00;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            transform: translateX(150%);
            transition: transform 0.3s ease-in-out;
            font-weight: bold;
        }
        
        .floating-message.show {
            transform: translateX(0);
        }
        
        .floating-message.hide {
            transform: translateX(150%);
        }
        
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 14px;
        }

        .email-update-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            padding-right: 20px;
        }

        .email-update-btn {
            width: auto;
            padding: 10px 20px;
            height: 42px;
            white-space: nowrap;
        }

        .pending-request-info {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
            color: #856404;
        }
        
        .pending-request-info p {
            margin: 5px 0;
        }
        
        .request-dates {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 15px !important;
        }
        
        .cancel-request-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .cancel-request-btn:hover {
            background-color: #c82333;
        }
        
        .admin-controls {
            margin-top: 20px;
            padding: 15px;
            background-color: #e6f7ff;
            border: 1px solid #b3e0ff;
            border-radius: 4px;
        }
        
        .admin-controls h4 {
            color: #0066cc;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .admin-btn {
            display: inline-block;
            background-color: #0099ff;
            color: white !important;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 5px;
            transition: background-color 0.2s;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            font-size: 14px;
            min-height: 38px;
            line-height: 1.5;
        }
        
        .admin-btn:hover {
            background-color: #007acc;
            color: white !important;
            text-decoration: none;
        }
        
        .nav-links .profile-icon {
            position: relative;
        }
        
        .nav-links .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff3b30;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            padding: 0;
            line-height: 18px;
            text-align: center;
        }
        
        /* Responsive styles for smaller screens */
        @media screen and (max-width: 768px) {
            .profile-container {
                flex-direction: column;
                gap: 15px;
            }
            
            #first_name, #last_name {
                width: 90%;
                max-width: none;
            }
            
            #email, #current_password, #new_password, #confirm_password {
                width: 90%;
                max-width: none;
            }
            
            .email-update-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .email-update-btn {
                width: 100%;
                margin-top: 10px;
            }
            
            .org-actions {
                flex-direction: column;
                width: 100%;
                gap: 15px;
            }
            
            .create-org-btn {
                width: 100%;
                margin-left: 0;
                margin-top: 0;
            }
            
            #profpic-firstname {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            #header #first_name, #header #last_name {
                font-size: 20px;
            }
            
            .settings-container {
                padding-bottom: 50px;
            }
            
            #profile-wrapper {
                gap: 30px;
            }
            
            #organization-container {
                padding: 20px;
                width: 100%;
            }
            
            .admin-controls {
                padding: 10px;
            }
            
            .admin-btn {
                width: 100%;
                box-sizing: border-box;
                padding: 8px 10px;
                font-size: 14px;
                margin-top: 10px;
            }
            
            #change-password {
                margin-top: 20px;
                padding: 15px;
                width: 100%;
                max-width: none;
                box-sizing: border-box;
            }
            
            #change-password input[type="password"] {
                width: 100%;
                box-sizing: border-box;
            }
            
            #change-password .form-group {
                padding: 0;
                width: 100%;
                box-sizing: border-box;
            }
            
            #change-password .submit-btn {
                width: 100%;
                margin: 10px auto;
            }
        }
        
        @media screen and (max-width: 480px) {
            .settings-container h2 {
                font-size: 20px;
            }
            
            #profile-wrapper {
                margin-bottom: 30px;
            }
            
            #profile, #organization-container, #change-password {
                margin-bottom: 30px;
            }
            
            .leave-org-modal-content {
                max-width: 90%;
                padding: 20px 10px;
            }
            
            .leave-org-modal-content button[type="submit"],
            .leave-org-modal-content button[type="button"] {
                padding: 8px 12px;
                width: 100%;
                margin: 5px 0;
            }
            
            #change-password {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            #change-password input[type="password"] {
                width: 100%;
                margin-bottom: 10px;
            }
        }

        /* Ensure consistent spacing for organization actions */
        .org-actions {
            display: flex;
            gap: 10px;
            width: 100%;
            margin-top: 10px;
        }

        /* Style the view organization requests link */
        #organization-container a {
            display: inline-block;
            margin-top: 15px;
            color: #0099ff;
            text-decoration: none;
        }

        /* Fix organization input field styling */
        #organization {
            background-color: #e9e9e9;
            cursor: not-allowed;
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            font-size: 14px;
            color: #555;
            box-sizing: border-box;
        }
        
        #organization-container .form-group {
            padding-left: 0;
            margin-bottom: 15px;
        }
        
        #org-label {
            padding-left: 0;
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="floating-message" id="successMessage">
        <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        ?>
    </div>
    <?php endif; ?>

    <div id="wrapper">
    <header class="navbar">
            <div class="logo">
                <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
            </div>
            <form id="searchForm" action="search_results.php" method="GET">
                <div class="search-bar">
                    <input type="text" name="search" placeholder="Search datasets" onfocus="showDropdown()" onblur="hideDropdown()">
                    <button>
                        <img src="images/search_icon.png" alt="Search">
                    </button>
                </div>
            </form>
            <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="nav-links" id="nav-links">
                <a href="HomeLogin.php">HOME</a>
                <a href="datasets.php">DATASETS</a>
                <a onclick="showModal()" style="cursor: pointer;">CATEGORY</a>
                <div class="profile-icon" id="navbar-profile-icon">
                    <img src="images/avatarIconunknown.jpg" alt="Profile">
                    <?php if ($total_count > 0): ?>
                        <span class="notification-badge"><?php echo $total_count; ?></span>
                    <?php endif; ?>
                </div>
            </nav>
        </header> 
        <div class="settings-container">
            <div id="header">
            <h2>User Settings</h2>
                <form action="verify_changes.php" method="POST" enctype="multipart/form-data">
                    <div id="profpic-firstname">
                        <div>
                            <img id="avatar" src="images/user_icon.png" alt="User Icon">
                        </div>
                        <div>
                            <span id="first_name"><?php echo $user['first_name']; ?></span>
                            <span id="last_name"><?php echo $user['last_name']; ?></span>
                        </div>
                    </div>
                    <hr class="section-divider">
                </div>
                <div id="profile-wrapper">
                    <div id="profile">
                        <h2>Profile</h2>
                        
                        <div class="profile-container">
                            <div id="display1" class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo $user['first_name']; ?>" required readonly>
                            </div>
                            <div id="display2" class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo $user['last_name']; ?>" required readonly>
                            </div>
                        </div>
                        <form action="verify_changes.php" method="POST" class="email-update-form">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <div class="email-update-container">
                                    <input type="email" id="email" name="new_value" value="<?php echo $user['email']; ?>" required>
                                    <input type="hidden" name="change_type" value="email">
                                    <input type="hidden" name="from_settings" value="true">
                                    <button type="submit" class="submit-btn email-update-btn">Update Email</button>
                                </div>
                                <?php if (isset($_SESSION['error_message']) && isset($_SESSION['error_type']) && $_SESSION['error_type'] === 'email'): ?>
                                    <div class="error-message"><?php echo $_SESSION['error_message']; ?></div>
                                    <?php unset($_SESSION['error_message'], $_SESSION['error_type']); ?>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <div id="organization-container">
                        <h2 id="org">Organization</h2>
                        <div class="form-group">
                            <label id="org-label" for="organization">Current Organization</label>
                            <input type="text" id="organization" name="organization" value="<?php echo $organizationName; ?>" readonly>
                        </div>
                        <?php if ($has_pending_request): ?>
                            <div class="pending-request-info">
                                <p><strong>Pending Request:</strong> You have requested to join <strong><?php echo htmlspecialchars($pending_request['organization_name']); ?></strong></p>
                                <p class="request-dates">
                                    Requested: <?php echo date('M j, Y', strtotime($pending_request['request_date'])); ?><br>
                                    <?php if (!empty($pending_request['expiration_date'])): ?>
                                        Expires: <?php echo date('M j, Y', strtotime($pending_request['expiration_date'])); ?>
                                    <?php endif; ?>
                                </p>
                                <form method="post" action="cancel_request.php">
                                    <input type="hidden" name="request_id" value="<?php echo $pending_request['request_id']; ?>">
                                    <button type="submit" class="cancel-request-btn">Cancel Request</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <?php if ($hasOrganization): ?>
                                <!-- If user has org, show leave button -->
                                <button type="button" class="create-org-btn" onclick="showLeaveOrgModal()">Leave Organization</button>
                                
                                <!-- Check if user is an organization admin -->
                                <?php
                                // Check if user is the organization owner/creator
                                $check_owner_sql = "SELECT o.created_by FROM organizations o WHERE o.organization_id = ? AND o.created_by = ?";
                                $check_owner_stmt = $conn->prepare($check_owner_sql);
                                $check_owner_stmt->bind_param("ii", $organizationId, $user_id);
                                $check_owner_stmt->execute();
                                $check_owner_result = $check_owner_stmt->get_result();
                                $is_org_owner = ($check_owner_result->num_rows > 0);
                                
                                if ($is_org_owner): ?>
                                    <div class="admin-controls">
                                        <h4>Organization Owner Controls</h4>
                                        <a href="manage_org_requests.php" class="admin-btn" style="display: block; color: white; background-color: #0099ff; text-decoration: none; padding: 10px; border-radius: 5px; text-align: center; font-weight: bold; width: 100%; box-sizing: border-box;">Manage Membership Requests</a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- If user has no org, show options -->
                                <div class="org-actions">
                                    <button type="button" class="create-org-btn" onclick="window.location.href='join_organization.php'">Join an Organization</button>
                                    <button type="button" class="create-org-btn" onclick="checkPendingOrgRequests()">Request New Org</button>
                                </div>
                                <div id="org-request-error" class="error-message" style="display: none; margin-top: 15px;">
                                    You already have a pending organization creation request. Please wait for administrator approval or <a href="user_org_requests.php" style="color: #721c24; text-decoration: underline;">check your request status</a>.
                                </div>
                                <div style="margin-top: 15px;">
                                    <a href="user_org_requests.php" style="color: #0099ff; text-decoration: none;">View my organization requests</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                            <div class="admin-controls" style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">
                                <h4>Admin Controls</h4>
                                <a href="admin_org_requests.php" class="admin-btn" style="display: block; color: white; background-color: #0099ff; text-decoration: none; padding: 10px; border-radius: 5px; text-align: center; font-weight: bold; width: 100%; box-sizing: border-box;">Manage Organization Requests</a>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <br><br>
                <div id="change-password">
                    <h2>Change Password</h2>
                    <form action="verify_changes.php" method="POST" id="passwordForm">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_value" placeholder="Enter new password" required>
                            <input type="hidden" name="change_type" value="password">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                        </div>

                        <?php if (isset($_SESSION['error_message']) && isset($_SESSION['error_type']) && $_SESSION['error_type'] === 'password'): ?>
                            <div class="error-message"><?php echo $_SESSION['error_message']; ?></div>
                            <?php unset($_SESSION['error_message'], $_SESSION['error_type']); ?>
                        <?php endif; ?>

                        <div style="text-align: center;">
                        <button type="submit" class="submit-btn">Update Password</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
    <?php include('leave_orgmodal.php');?>
    <?php include('sidebar.php');?>
    <?php include('category_modal.php');?>
    <script>
        function showModal() {
            document.getElementById("categoryModal").style.display = "flex";
        }
        function hideModal() {
            document.getElementById("categoryModal").style.display = "none";
         }
        
        // Function to show leave organization confirmation modal
        function showLeaveOrgModal() {
            const modal = document.getElementById("leaveOrgModal");
            if (modal) {
                modal.style.display = "flex";
            } else {
                console.error("Leave organization modal not found");
            }
        }
        
        // Function to close the leave organization modal
        function closeLeaveOrgModal() {
            const modal = document.getElementById("leaveOrgModal");
            if (modal) {
                modal.style.display = "none";
            }
        }
        
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("categoryModal").style.display = "none";
            
            // Verify the leave organization modal exists
            const leaveOrgModal = document.getElementById("leaveOrgModal");
            if (!leaveOrgModal) {
                console.error("Leave organization modal element not found");
            }
            
            // Mobile menu toggle functionality
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const navLinks = document.getElementById('nav-links');
            
            if (mobileMenuToggle && navLinks) {
                mobileMenuToggle.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function(event) {
                    const isClickInsideNavbar = event.target.closest('.navbar');
                    const isClickOnToggle = event.target.closest('#mobile-menu-toggle');
                    
                    if (!isClickInsideNavbar || (isClickInsideNavbar && !isClickOnToggle && event.target.tagName !== 'A')) {
                        if (navLinks.classList.contains('active') && !isClickOnToggle) {
                            navLinks.classList.remove('active');
                        }
                    }
                });
            }
        });

        // Success Message Animation
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('successMessage');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.classList.add('show');
                }, 100);

                setTimeout(() => {
                    successMessage.classList.add('hide');
                    setTimeout(() => {
                        successMessage.remove();
                    }, 300);
                }, 3000);
            }

            // Password validation
            const passwordForm = document.getElementById('passwordForm');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.display = 'none';
            passwordForm.insertBefore(errorDiv, passwordForm.querySelector('button'));

            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                errorDiv.style.display = 'none';

                // Check if passwords match
                if (newPassword.value !== confirmPassword.value) {
                    errorDiv.textContent = 'New password and confirm password do not match.';
                    errorDiv.style.display = 'block';
                    return;
                }

                // Check password strength
                const passwordRegex = /^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
                if (!passwordRegex.test(newPassword.value)) {
                    errorDiv.textContent = 'Password must be at least 8 characters long, contain 1 uppercase letter, 1 number, and 1 special character.';
                    errorDiv.style.display = 'block';
                    return;
                }

                // If all validations pass, submit the form
                this.submit();
            });
        });
        
        // Check if user has pending organization creation requests
        function checkPendingOrgRequests() {
            // Make an AJAX request to check for pending org creation requests
            fetch('check_pending_org_requests.php')
                .then(response => response.json())
                .then(data => {
                    if (data.has_pending_request) {
                        // Show error message
                        document.getElementById('org-request-error').style.display = 'block';
                        // Scroll to the error message
                        document.getElementById('org-request-error').scrollIntoView({ behavior: 'smooth' });
                    } else {
                        // Redirect to create organization request page
                        window.location.href = 'create_organization_request.php';
                    }
                })
                .catch(error => {
                    console.error('Error checking pending requests:', error);
                    // Redirect anyway if there's an error with the check
                    window.location.href = 'create_organization_request.php';
                });
        }
        // Update the click event to use the specific ID
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('navbar-profile-icon').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.add('active');
                document.querySelector('.sidebar-overlay').classList.add('active');
            });
        });
    </script>
</body>
</html>