<?php
session_start();
include 'db_connection.php';

// Include session update to ensure organization_id is synchronized
include 'update_session.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's database info
$user_query = "SELECT u.*, o.name as org_name 
               FROM users u 
               LEFT JOIN organizations o ON u.organization_id = o.organization_id 
               WHERE u.user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// For testing: Check upload disabled status
$upload_disabled = !isset($_SESSION['organization_id']) || $_SESSION['organization_id'] == null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Debug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h1, h2 {
            color: #0099ff;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .data-row {
            display: flex;
            margin-bottom: 5px;
        }
        .data-label {
            width: 200px;
            font-weight: bold;
        }
        .data-value {
            flex-grow: 1;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .nav {
            margin-top: 20px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
        .nav a {
            margin-right: 15px;
            color: #0099ff;
            text-decoration: none;
        }
        .nav a:hover {
            text-decoration: underline;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1>Session Debug Information</h1>
    
    <div class="section">
        <h2>Session Data</h2>
        <div class="data-row">
            <div class="data-label">User ID:</div>
            <div class="data-value"><?= $_SESSION['user_id'] ?? 'Not set' ?></div>
        </div>
        <div class="data-row">
            <div class="data-label">Organization ID:</div>
            <div class="data-value"><?= $_SESSION['organization_id'] ?? 'Not set' ?></div>
        </div>
        <div class="data-row">
            <div class="data-label">User Type:</div>
            <div class="data-value"><?= $_SESSION['user_type'] ?? 'Not set' ?></div>
        </div>
    </div>
    
    <div class="section">
        <h2>Database Data</h2>
        <div class="data-row">
            <div class="data-label">Name:</div>
            <div class="data-value"><?= $user_data['first_name'] . ' ' . $user_data['last_name'] ?></div>
        </div>
        <div class="data-row">
            <div class="data-label">Organization ID:</div>
            <div class="data-value"><?= $user_data['organization_id'] ?? 'Not set' ?></div>
        </div>
        <div class="data-row">
            <div class="data-label">Organization Name:</div>
            <div class="data-value"><?= $user_data['org_name'] ?? 'Not set' ?></div>
        </div>
        <div class="data-row">
            <div class="data-label">User Type:</div>
            <div class="data-value"><?= $user_data['user_type'] ?></div>
        </div>
    </div>
    
    <div class="section">
        <h2>Upload Status</h2>
        <div class="data-row">
            <div class="data-label">Upload Disabled:</div>
            <div class="data-value">
                <?php if ($upload_disabled): ?>
                    <span class="error">YES - You cannot upload datasets</span>
                    <div class="warning">
                        You must be part of an organization to upload datasets.
                    </div>
                <?php else: ?>
                    <span class="success">NO - You can upload datasets</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="nav">
        <h3>Test Navigation</h3>
        <p>Click these links to test if your upload access works correctly:</p>
        <a href="HomeLogin.php">Home</a>
        <a href="uploadselection.php">Upload Dataset</a>
        <a href="datasets.php">All Datasets</a>
        <a href="mydatasets.php">My Datasets</a>
    </div>
</body>
</html> 