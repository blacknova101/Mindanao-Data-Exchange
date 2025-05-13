<?php
session_start();
include 'db_connection.php'; // Make sure this file sets up $conn

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id']; // Use session user_id instead of POST

// Check if the user has an organization
$sql = "SELECT u.organization_id, o.name as org_name 
        FROM users u 
        LEFT JOIN organizations o ON u.organization_id = o.organization_id 
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// Debug information
error_log("User ID: " . $userId);
error_log("Organization ID: " . ($userData['organization_id'] ?? 'NULL'));
error_log("Organization Name: " . ($userData['org_name'] ?? 'NULL'));

if ($userData && $userData['organization_id'] !== null) {
    // Remove the user from the organization
    $sql = "UPDATE users SET organization_id = NULL, user_type = 'normal' WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $_SESSION['organization_id'] = null;
        $_SESSION['user_type'] = 'normal';
        $_SESSION['success_message'] = "Successfully left the organization.";
        header("Location: user_settings.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Failed to leave the organization. Please try again.";
        header("Location: user_settings.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "You are not part of any organization.";
    header("Location: user_settings.php");
    exit();
}
?>
