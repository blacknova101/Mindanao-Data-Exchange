<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Assuming session and db connection code is already correct
$user_id = $_SESSION['user_id'];

// Query to get user and organization details
$query = "SELECT o.organization_id, o.name AS org_name, u.first_name, u.last_name
          FROM users u
          JOIN organizations o ON u.organization_id = o.organization_id
          WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || !$result->num_rows) {
    $_SESSION['error_message'] = "User or organization not found.";
    header("Location: unauthorized.php");
    exit();
}

$row = $result->fetch_assoc();
if (isset($row['organization_id'])) {
    $organization_id = $row['organization_id']; // Fetch organization_id
    $org_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['org_name']); // Clean for filesystem
    $first_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['first_name']);
    $last_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['last_name']);
    $user_folder = "{$first_name}_{$last_name}";
} else {
    $_SESSION['error_message'] = "No organization found for this user.";
    header("Location: unauthorized.php");
    exit();
}

// Form data
$title = $_POST['title'];
$description = $_POST['description'];
$start_period = $_POST['start_period'];
$end_period = $_POST['end_period'];
$source = $_POST['source'];
$link = $_POST['link'];
$location = $_POST['location'];
$visibility = $_POST['visibility'];
$category_id = $_POST['category'];  // Capture the selected category ID

if (strtotime($start_period) > strtotime($end_period)) {
    $_SESSION['error_message'] = "Start date must be earlier than end date.";
    header("Location: upload_fill.php");
    exit();
}
if (empty($category_id) || !is_numeric($category_id)) {
    $_SESSION['error_message'] = "Invalid category selected.";
    header("Location: upload_fill.php");
    exit();
}

// Insert dataset batch with organization_id
$query = "INSERT INTO dataset_batches (user_id, organization_id) VALUES (?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $organization_id); // Bind both user_id and organization_id
$stmt->execute();
$dataset_batch_id = $stmt->insert_id;

// Create directory: org/first_last/batch_id
$base_dir = "uploads/{$org_name}/{$user_folder}/{$dataset_batch_id}";
if (!is_dir($base_dir)) {
    mkdir($base_dir, 0777, true); // recursive creation
}

// Move and record files
foreach ($_SESSION['valid_files'] as $temp_path) {
    $file_name = basename($temp_path);
    $new_path = "{$base_dir}/{$file_name}";
    if (rename($temp_path, $new_path)) {
        $query = "INSERT INTO datasets (dataset_batch_id, user_id, file_path, title, description, start_period, end_period, source, link, location, category_id, visibility)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iisssssssssi", $dataset_batch_id, $user_id, $new_path, $title, $description, $start_period, $end_period, $source, $link, $location, $category_id, $visibility);
        $stmt->execute();
    }
}

unset($_SESSION['valid_files']);
$_SESSION['success_message'] = "Dataset added successfully!";
header("Location: dataset_success.php");
exit();


?>
