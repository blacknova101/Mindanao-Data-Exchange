<?php
session_start();
include 'db_connection.php';

// Only allow admin access
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit("Admin access required");
}

// Include the expiration script
require_once('expire_requests.php');

// Redirect back to admin dashboard
header("Location: admin_dashboard.php?message=Expiration+check+completed");
exit; 