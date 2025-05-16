<?php
/**
 * Mindanao Data Exchange - Run Database Migration
 * 
 * This script runs the migration to add the created_by column to organizations table
 */

// Start session for possible login check
session_start();

// Security check - only allow access to admins
if (!isset($_SESSION['admin_id'])) {
    // Not an admin, show error
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 40px;
                text-align: center;
            }
            .error {
                color: #dc3545;
                font-weight: bold;
                margin-bottom: 20px;
            }
            .button {
                display: inline-block;
                background: #0099ff;
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                text-decoration: none;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <h1>Access Denied</h1>
        <div class="error">You must be an admin to access this page.</div>
        <a href="admin_login.php" class="button">Login as Admin</a>
    </body>
    </html>';
    exit();
}

// Include the migration script
include_once('add_created_by_column.php');

// After migration, redirect to admin dashboard
header("Location: admin_dashboard.php?message=Migration+completed+successfully");
exit(); 