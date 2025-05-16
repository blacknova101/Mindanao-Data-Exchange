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
$visibility_check = "SELECT db.visibility, d.user_id, d.dataset_id
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

$has_permission = false;

// Case 1: Dataset is public
if ($dataset_info['visibility'] === 'Public') {
    $has_permission = true;
}
// Case 2: User owns the dataset
elseif ($dataset_info['user_id'] == $user_id) {
    $has_permission = true;
}
// Case 3: User has approved access request
else {
    $dataset_id = $dataset_info['dataset_id'];
    $access_check = "SELECT * FROM dataset_access_requests
                    WHERE dataset_id = $dataset_id
                    AND requester_id = $user_id
                    AND status = 'Approved'";
    $access_result = mysqli_query($conn, $access_check);
    if ($access_result && mysqli_num_rows($access_result) > 0) {
        $has_permission = true;
    }
}

// Only allow download if user has permission
if (!$has_permission) {
    header("Location: dataset.php?id=" . $dataset_info['dataset_id'] . "&error=permission");
    exit();
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