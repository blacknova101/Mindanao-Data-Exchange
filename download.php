<?php
session_start();
include('db_connection.php');
include('batch_analytics.php'); // Include analytics functions

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$dataset_id = isset($_GET['dataset_id']) ? (int)$_GET['dataset_id'] : 0;

if (!$dataset_id) {
    die("No dataset specified.");
}

// Get the dataset details
$sql = "SELECT d.*, db.visibility, d.user_id as owner_id, d.dataset_batch_id
        FROM datasets d
        JOIN dataset_batches db ON d.dataset_batch_id = db.dataset_batch_id
        WHERE d.dataset_id = $dataset_id";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {
    die("Dataset not found.");
}

$dataset = mysqli_fetch_assoc($result);
$file_path = $dataset['file_path'];
$batch_id = $dataset['dataset_batch_id'];

// Check if the user has permission to download
$has_permission = false;

// Case 1: Public dataset - anyone can download
if ($dataset['visibility'] === 'Public') {
    $has_permission = true;
}
// Case 2: User is the owner of the dataset
elseif ($dataset['owner_id'] == $user_id) {
    $has_permission = true;
}
// Case 3: User has an approved access request
else {
    $access_sql = "SELECT * FROM dataset_access_requests 
                  WHERE dataset_id = $dataset_id 
                  AND requester_id = $user_id 
                  AND status = 'Approved'";
    $access_result = mysqli_query($conn, $access_sql);
    if (mysqli_num_rows($access_result) > 0) {
        $has_permission = true;
    }
}

// Track download if permitted
if ($has_permission) {
    // Increment download counter
    $update_sql = "UPDATE datasets SET downloads = downloads + 1 WHERE dataset_id = $dataset_id";
    mysqli_query($conn, $update_sql);
    
    // Update batch analytics
    increment_batch_downloads($conn, $batch_id);
    
    // Check if file exists
    if (!file_exists($file_path)) {
        die("File not found on server.");
    }
    
    // Set appropriate headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Output file and exit
    readfile($file_path);
    exit;
} else {
    // User doesn't have permission
    header('Location: dataset.php?id=' . $dataset_id . '&error=permission');
    exit();
}
?>