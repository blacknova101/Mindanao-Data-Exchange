<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['organization_id'])) {
    header("Location: join_organization.php");
    exit();
}

$userId = $_SESSION['user_id'];
$orgId = $_POST['organization_id'];

// Update user's organization in the database
$sql = "UPDATE users SET organization_id = ?, user_type = 'with_organization' WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $orgId, $userId);
$stmt->execute();

$_SESSION['organization_id'] = $orgId;
$_SESSION['user_type'] = 'with_organization'; // Update session if you use this elsewhere

header("Location: user_settings.php");
exit();