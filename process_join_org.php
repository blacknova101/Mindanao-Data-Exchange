<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['organization_id'])) {
    error_log("Join organization failed: Missing user_id or organization_id");
    header("Location: join_organization.php");
    exit();
}

$userId = $_SESSION['user_id'];
$orgId = $_POST['organization_id'];

// First verify the organization exists
$check_org_sql = "SELECT organization_id FROM organizations WHERE organization_id = ?";
$check_stmt = $conn->prepare($check_org_sql);
$check_stmt->bind_param("i", $orgId);
$check_stmt->execute();
$org_result = $check_stmt->get_result();

if ($org_result->num_rows === 0) {
    error_log("Join organization failed: Organization ID $orgId not found");
    $_SESSION['error_message'] = "Organization not found.";
    header("Location: join_organization.php");
    exit();
}

// Update user's organization in the database
$sql = "UPDATE users SET organization_id = ?, user_type = 'with_organization' WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $orgId, $userId);

if ($stmt->execute()) {
    // Update session variables
    $_SESSION['organization_id'] = $orgId;
    $_SESSION['user_type'] = 'with_organization';
    
    error_log("User $userId successfully joined organization $orgId");
    $_SESSION['success_message'] = "Successfully joined the organization.";
    header("Location: user_settings.php");
} else {
    error_log("Join organization failed: " . $stmt->error);
    $_SESSION['error_message'] = "Failed to join organization. Please try again.";
    header("Location: join_organization.php");
}
exit();