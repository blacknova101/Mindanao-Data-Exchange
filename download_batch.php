<?php
session_start();
include 'db_connection.php';
include 'batch_analytics.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['batch_id'])) {
    die('Batch ID not specified.');
}

$batch_id = intval($_GET['batch_id']);
$user_id = $_SESSION['user_id'];

// Check if the dataset is public or owned by the current user
$visibility_check = "SELECT db.visibility, d.user_id 
                    FROM dataset_batches db
                    JOIN datasets d ON db.dataset_batch_id = d.dataset_batch_id
                    WHERE db.dataset_batch_id = ? 
                    LIMIT 1";
$stmt = $conn->prepare($visibility_check);
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$result_visibility = $stmt->get_result();
$dataset_info = $result_visibility->fetch_assoc();

if (!$dataset_info) {
    die('Dataset not found.');
}

// Only allow download if public OR the user owns the dataset
if ($dataset_info['visibility'] === 'Private' && $dataset_info['user_id'] != $user_id) {
    die('You do not have permission to download this private dataset.');
}

// Increment batch downloads
increment_batch_downloads($conn, $batch_id);

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