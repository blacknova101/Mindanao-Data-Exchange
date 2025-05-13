<?php
session_start();
include 'db_connection.php';
include 'batch_analytics.php'; // <-- Add this line

if (!isset($_GET['batch_id'])) {
    die('Batch ID not specified.');
}

$batch_id = intval($_GET['batch_id']);

// Increment batch downloads
increment_batch_downloads($conn, $batch_id); // <-- Add this line

$sql = "SELECT file_path FROM datasets WHERE dataset_batch_id = $batch_id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die('No datasets found for this batch.');
}

$zip = new ZipArchive();
$zipFileName = "batch_$batch_id.zip";
$zipFilePath = __DIR__ . "/temp_zips/$zipFileName";

if (!file_exists('temp_zips')) {
    mkdir('temp_zips', 0777, true);
}

if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    while ($row = mysqli_fetch_assoc($result)) {
        $filePath = $row['file_path'];
        if (file_exists($filePath)) {
            $zip->addFile($filePath, basename($filePath));
        }
    }
    $zip->close();

    // Serve the zip file
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename=' . $zipFileName);
    header('Content-Length: ' . filesize($zipFilePath));
    readfile($zipFilePath);

    // Optional: delete the zip after download
    unlink($zipFilePath);
    exit();
} else {
    die('Failed to create ZIP file.');
}
?>