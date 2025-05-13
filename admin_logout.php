<?php
session_start();
include 'db_connection.php';

// Clear admin session
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_name']);

// Redirect to admin login
header("Location: admin_login.php");
exit();
?> 