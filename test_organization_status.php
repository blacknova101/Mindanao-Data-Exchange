<?php
session_start();
include 'db_connection.php';

// Include session update to ensure organization_id is synchronized
include 'update_session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>You must be logged in to view this page.</p>";
    echo "<p><a href='login.php'>Log in</a></p>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's information from the database
$user_sql = "SELECT u.*, o.name AS organization_name 
             FROM users u 
             LEFT JOIN organizations o ON u.organization_id = o.organization_id 
             WHERE u.user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Get organization membership request status
$request_sql = "SELECT status, request_date, organization_id 
                FROM organization_membership_requests 
                WHERE user_id = ? 
                ORDER BY request_date DESC 
                LIMIT 1";
$request_stmt = $conn->prepare($request_sql);
$request_stmt->bind_param("i", $user_id);
$request_stmt->execute();
$request_result = $request_stmt->get_result();
$request_data = $request_result->fetch_assoc();

// Display HTML
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Status Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0099ff;
        }
        h2 {
            color: #444;
            margin-top: 20px;
        }
        .data-section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 200px;
        }
        .status-approved {
            color: green;
            font-weight: bold;
        }
        .status-pending {
            color: orange;
            font-weight: bold;
        }
        .status-rejected {
            color: red;
            font-weight: bold;
        }
        .actions {
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #0099ff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #0077cc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Organization Status Test</h1>
        
        <div class="data-section">
            <h2>Session Data:</h2>
            <p><span class="label">User ID:</span> <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
            <p><span class="label">Organization ID:</span> <?php echo $_SESSION['organization_id'] ?? 'Not set'; ?></p>
            <p><span class="label">User Type:</span> <?php echo $_SESSION['user_type'] ?? 'Not set'; ?></p>
        </div>
        
        <div class="data-section">
            <h2>Database Data:</h2>
            <p><span class="label">User ID:</span> <?php echo $user_data['user_id']; ?></p>
            <p><span class="label">Name:</span> <?php echo $user_data['first_name'] . ' ' . $user_data['last_name']; ?></p>
            <p><span class="label">Organization ID:</span> <?php echo $user_data['organization_id'] ?? 'Not set'; ?></p>
            <p><span class="label">Organization Name:</span> <?php echo $user_data['organization_name'] ?? 'Not set'; ?></p>
            <p><span class="label">User Type:</span> <?php echo $user_data['user_type']; ?></p>
        </div>
        
        <?php if ($request_data): ?>
        <div class="data-section">
            <h2>Latest Organization Request:</h2>
            <p><span class="label">Status:</span> 
                <span class="status-<?php echo strtolower($request_data['status']); ?>">
                    <?php echo $request_data['status']; ?>
                </span>
            </p>
            <p><span class="label">Request Date:</span> <?php echo $request_data['request_date']; ?></p>
            <p><span class="label">Organization ID:</span> <?php echo $request_data['organization_id']; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="HomeLogin.php" class="btn">Back to Home</a>
            <a href="uploadselection.php" class="btn">Try Upload</a>
        </div>
    </div>
</body>
</html> 