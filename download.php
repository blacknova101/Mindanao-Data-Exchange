<?php
session_start();
include('db_connection.php');
include('batch_analytics.php'); // <-- include analytics functions

if (!isset($_GET['dataset_id'])) {
    die('No dataset specified.');
}

$dataset_id = (int)$_GET['dataset_id'];

// Get the file path and batch id for the dataset
$sql = "SELECT file_path, dataset_batch_id FROM datasets WHERE dataset_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}
$stmt->bind_param("i", $dataset_id);
$stmt->execute();
$stmt->bind_result($file_path, $batch_id);
if (!$stmt->fetch()) {
    die('File not found.');
}
$stmt->close();

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