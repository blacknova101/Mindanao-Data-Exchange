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
$version_id = isset($_GET['version']) ? (int)$_GET['version'] : 0;
$direct_file = isset($_GET['direct_file']) ? $_GET['direct_file'] : '';

// Handle direct file download if specified
if (!empty($direct_file)) {
    // Perform basic security check to ensure the file is within the uploads directory
    $uploads_base_path = realpath(dirname(__FILE__) . '/uploads');
    $requested_file = realpath($direct_file);
    
    if ($requested_file && strpos($requested_file, $uploads_base_path) === 0 && file_exists($requested_file)) {
        // Set appropriate headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($requested_file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($requested_file));
        
        // Clear output buffer
        ob_clean();
        flush();
        
        // Output file and exit
        readfile($requested_file);
        exit;
    } else {
        die("Invalid file path or file not found.");
    }
}

if (!$dataset_id) {
    die("No dataset specified.");
}

// Get the dataset details
$sql = "SELECT d.*, db.visibility, d.user_id as owner_id, d.dataset_batch_id
        FROM datasets d
        JOIN dataset_batches db ON d.dataset_batch_id = db.dataset_batch_id
        WHERE d.dataset_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $dataset_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    die("Dataset not found.");
}

$dataset = mysqli_fetch_assoc($result);
$batch_id = $dataset['dataset_batch_id'];

// If version is specified, get that version's file
if ($version_id) {
    $version_sql = "SELECT file_path FROM datasetversions WHERE version_id = ? AND dataset_batch_id = ?";
    $stmt = mysqli_prepare($conn, $version_sql);
    mysqli_stmt_bind_param($stmt, 'ii', $version_id, $batch_id);
    mysqli_stmt_execute($stmt);
    $version_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($version_result) > 0) {
        $version_data = mysqli_fetch_assoc($version_result);
        $file_path = $version_data['file_path'];
    } else {
        $file_path = $dataset['file_path']; // Fallback to current version
    }
} else {
    $file_path = $dataset['file_path'];
}

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
                  WHERE dataset_id = ? 
                  AND requester_id = ? 
                  AND status = 'Approved'";
    $stmt = mysqli_prepare($conn, $access_sql);
    mysqli_stmt_bind_param($stmt, 'ii', $dataset_id, $user_id);
    mysqli_stmt_execute($stmt);
    $access_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($access_result) > 0) {
        $has_permission = true;
    }
}

// Track download if permitted
if ($has_permission) {
    // Check if downloads column exists in datasets table
    $check_column_sql = "SHOW COLUMNS FROM datasets LIKE 'downloads'";
    $column_result = mysqli_query($conn, $check_column_sql);
    
    if (mysqli_num_rows($column_result) > 0) {
        // Increment download counter if column exists
        $update_sql = "UPDATE datasets SET downloads = downloads + 1 WHERE dataset_id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, 'i', $dataset_id);
        mysqli_stmt_execute($stmt);
    }
    
    // Update batch analytics
    increment_batch_downloads($conn, $batch_id);
    
    // Check if file exists
    if (!file_exists($file_path)) {
        die("File not found on server: " . htmlspecialchars($file_path));
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