<?php
session_start();
include 'db_connection.php'; // Make sure this file sets up $conn

if (!isset($_POST['user_id'])) {
    echo "Invalid request: No user ID provided.";
    exit();
}

$userId = $_POST['user_id'];

// Check if the user has an organization
$sql = "SELECT organization_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if ($userData && $userData['organization_id'] !== NULL) {
    // Remove the user from the organization and set user_type to 'normal'
    $sql = "UPDATE users SET organization_id = NULL, user_type = 'normal' WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $_SESSION['organization_id'] = null;
        $_SESSION['user_type'] = 'normal'; // Update session if you use this elsewhere
        header("Location: user_settings.php");
        exit();
    } else {
        echo "Failed to leave the organization. Please try again.";
    }
} else {
    echo "You are not part of any organization.";
}
?>
