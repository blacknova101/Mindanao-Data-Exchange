<?php
session_start();
include('db_connection.php');
include('batch_analytics.php'); // <-- include analytics functions

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['dataset_id'])) {
    die('No dataset specified.');
}

$dataset_id = (int)$_GET['dataset_id'];
$user_id = $_SESSION['user_id'];

// Get the file path, batch id, and visibility for the dataset
$sql = "SELECT d.file_path, d.dataset_batch_id, d.user_id, db.visibility 
        FROM datasets d
        JOIN dataset_batches db ON d.dataset_batch_id = db.dataset_batch_id
        WHERE d.dataset_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}
$stmt->bind_param("i", $dataset_id);
$stmt->execute();
$result = $stmt->get_result();
$dataset = $result->fetch_assoc();

if (!$dataset) {
    die('File not found.');
}

// Check if user has permission to download this file
if ($dataset['visibility'] === 'Private' && $dataset['user_id'] != $user_id) {
    die('You do not have permission to download this private dataset.');
}

$file_path = $dataset['file_path'];
$batch_id = $dataset['dataset_batch_id'];

// Update analytics: increment total_downloads for dataset
$analytics_sql = "INSERT INTO datasetanalytics (dataset_id, total_downloads, last_accessed)
                  VALUES (?, 1, NOW())
                  ON DUPLICATE KEY UPDATE
                      total_downloads = total_downloads + 1,
                      last_accessed = NOW()";
$stmt = $conn->prepare($analytics_sql);
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}
$stmt->bind_param("i", $dataset_id);
if (!$stmt->execute()) {
    die('Execute failed: ' . $stmt->error);
}
$stmt->close();

// Update analytics: increment total_downloads for batch
increment_batch_downloads($conn, $batch_id);

// Serve the file for download
if (file_exists($file_path)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
} else {
    die('File does not exist.');
}
?>