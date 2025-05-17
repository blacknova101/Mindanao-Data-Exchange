<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$dataset_id = isset($_POST['dataset_id']) ? (int)$_POST['dataset_id'] : 0;
$batch_id = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : 0;
$update_type = isset($_POST['update_type']) ? $_POST['update_type'] : 'update';

// Verify dataset ownership
$checkOwnershipSql = "SELECT * FROM datasets WHERE dataset_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $checkOwnershipSql);
mysqli_stmt_bind_param($stmt, 'ii', $dataset_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error_message'] = "You don't have permission to edit this dataset.";
    header("Location: dataset.php?id=$dataset_id");
    exit();
}

$dataset = mysqli_fetch_assoc($result);

// Get form data
$title = mysqli_real_escape_string($conn, $_POST['title']);
$description = mysqli_real_escape_string($conn, $_POST['description']);
$start_period = mysqli_real_escape_string($conn, $_POST['start_period']);
$end_period = mysqli_real_escape_string($conn, $_POST['end_period']);
$source = mysqli_real_escape_string($conn, $_POST['source']);
$link = mysqli_real_escape_string($conn, $_POST['link']);
$location = mysqli_real_escape_string($conn, $_POST['location']);
$change_notes = isset($_POST['change_notes']) ? mysqli_real_escape_string($conn, $_POST['change_notes']) : '';

// Start transaction
mysqli_begin_transaction($conn);

try {
    if ($update_type === 'new_version') {
        // Get the next version number
        $versionSql = "SELECT MAX(CAST(SUBSTRING(version_number, 2) AS UNSIGNED)) as max_version 
                      FROM datasetversions 
                      WHERE dataset_batch_id = ?";
        $stmt = mysqli_prepare($conn, $versionSql);
        mysqli_stmt_bind_param($stmt, 'i', $batch_id);
        mysqli_stmt_execute($stmt);
        $versionResult = mysqli_stmt_get_result($stmt);
        $versionRow = mysqli_fetch_assoc($versionResult);
        $nextVersion = ($versionRow['max_version'] ?? 0) + 1;

        // Debug output to check what's happening
        error_log("Creating new version: v{$nextVersion} for batch_id: {$batch_id}");

        // Handle file uploads
        $file_paths = [];
        if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
            $upload_dir = dirname($dataset['file_path']);
            // Create version-specific directory
            $version_dir = $upload_dir . "/v{$nextVersion}";
            if (!file_exists($version_dir)) {
                mkdir($version_dir, 0777, true);
            }
            
            foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                    // Use original filename instead of creating a new one
                    $original_name = $_FILES['files']['name'][$key];
                    $target_file = $version_dir . '/' . $original_name;
                    
                    // If file with same name exists, append a unique identifier
                    if (file_exists($target_file)) {
                        $file_info = pathinfo($original_name);
                        $target_file = $version_dir . '/' . $file_info['filename'] . '_' . time() . '.' . $file_info['extension'];
                    }
                    
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $file_paths[] = $target_file;
                    }
                }
            }
        }

        // If no new files uploaded, use existing file path
        if (empty($file_paths)) {
            $file_paths[] = $dataset['file_path'];
        }

        // Use the first file as the main file path for the version record
        $main_file_path = $file_paths[0];

        // Create a single new version entry in datasetversions table
        $version_number = "v{$nextVersion}";
        $category_id = $dataset['category_id'];
        
        error_log("Creating version {$version_number} for batch_id: {$batch_id} with main file: {$main_file_path}");
        
        $insertVersionSql = "
            INSERT INTO datasetversions (
                dataset_batch_id, version_number, file_path, title, description,
                start_period, end_period, category_id, source, link, location,
                created_by, is_current, change_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
        ";
        $stmt = mysqli_prepare($conn, $insertVersionSql);
        mysqli_stmt_bind_param(
            $stmt,
            'issssssisssis',
            $batch_id,
            $version_number,
            $main_file_path,
            $title,
            $description,
            $start_period,
            $end_period,
            $category_id,
            $source,
            $link,
            $location,
            $user_id,
            $change_notes
        );
        mysqli_stmt_execute($stmt);
        
        // Get the new version_id
        $new_version_id = mysqli_insert_id($conn);
        error_log("Version created with ID: {$new_version_id}");
        
        // Mark all other versions as not current
        $updateVersionsSql = "
            UPDATE datasetversions 
            SET is_current = 0 
            WHERE dataset_batch_id = ? AND version_id != ?
        ";
        $stmt = mysqli_prepare($conn, $updateVersionsSql);
        mysqli_stmt_bind_param($stmt, 'ii', $batch_id, $new_version_id);
        mysqli_stmt_execute($stmt);

        // Update main dataset record with the first file
        $updateDatasetSql = "
            UPDATE datasets 
            SET title = ?, description = ?, start_period = ?, end_period = ?,
                source = ?, link = ?, location = ?, file_path = ?
            WHERE dataset_id = ?
        ";
        $stmt = mysqli_prepare($conn, $updateDatasetSql);
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssi',
            $title,
            $description,
            $start_period,
            $end_period,
            $source,
            $link,
            $location,
            $main_file_path,
            $dataset_id
        );
        mysqli_stmt_execute($stmt);
    } else {
        // Simple update of current version
        $updateDatasetSql = "
            UPDATE datasets 
            SET title = ?, description = ?, start_period = ?, end_period = ?,
                source = ?, link = ?, location = ?
            WHERE dataset_id = ?
        ";
        $stmt = mysqli_prepare($conn, $updateDatasetSql);
        mysqli_stmt_bind_param(
            $stmt,
            'sssssssi',
            $title,
            $description,
            $start_period,
            $end_period,
            $source,
            $link,
            $location,
            $dataset_id
        );
        mysqli_stmt_execute($stmt);

        // Update current version record
        $updateVersionSql = "
            UPDATE datasetversions 
            SET title = ?, description = ?, start_period = ?, end_period = ?,
                source = ?, link = ?, location = ?
            WHERE dataset_batch_id = ? AND is_current = 1
        ";
        $stmt = mysqli_prepare($conn, $updateVersionSql);
        mysqli_stmt_bind_param(
            $stmt,
            'sssssssi',
            $title,
            $description,
            $start_period,
            $end_period,
            $source,
            $link,
            $location,
            $batch_id
        );
        mysqli_stmt_execute($stmt);
    }

    // Commit transaction
    mysqli_commit($conn);
    $_SESSION['success_message'] = "Dataset updated successfully!";
    
    // Log success for debugging
    if ($update_type === 'new_version') {
        error_log("Transaction committed successfully. Created version {$version_number} with ID {$new_version_id} for batch {$batch_id}");
    } else {
        error_log("Transaction committed successfully. Updated dataset {$dataset_id} in batch {$batch_id}");
    }
    
    // Check if there's a return_to parameter to know where to redirect
    $return_to = isset($_POST['return_to']) ? $_POST['return_to'] : 'dataset.php';
    
    if ($return_to === 'mydataset.php') {
        header("Location: mydataset.php?id=$dataset_id");
    } else {
        header("Location: dataset.php?id=$dataset_id");
    }
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    $_SESSION['error_message'] = "Error updating dataset: " . $e->getMessage();
    
    // Check if there's a return_to parameter to know where to redirect
    $return_to = isset($_POST['return_to']) ? $_POST['return_to'] : 'dataset.php';
    
    if ($return_to === 'mydataset.php') {
        header("Location: mydataset.php?id=$dataset_id");
    } else {
        header("Location: dataset.php?id=$dataset_id");
    }
    exit();
}
?> 