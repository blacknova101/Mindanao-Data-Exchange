<?php
session_start();
include('db_connection.php');
include('includes/error_handler.php');
include('includes/path_handler.php');

if (!isset($_SESSION['user_id'])) {
    handle_error("You must be logged in to update datasets.", ERROR_AUTH, "login.php");
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
    handle_error("You don't have permission to edit this dataset.", ERROR_PERMISSION, "dataset.php?id=$dataset_id");
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
        log_error("Creating new version", "dataset", [
            'version' => "v{$nextVersion}",
            'batch_id' => $batch_id,
            'dataset_id' => $dataset_id
        ]);

        // Handle file uploads
        $file_paths = [];
        if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
            // Get the base directory from the current file path
            $current_file_path = $dataset['file_path'];
            $base_dir = dirname($current_file_path);
            
            // Create version-specific directory using our path handling functions
            $version_folder = "v{$nextVersion}";
            $version_dir = $base_dir . '/' . $version_folder;
            ensure_directory_exists($version_dir);
            
            foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                    // Sanitize filename and make it unique
                    $original_name = sanitize_filename($_FILES['files']['name'][$key]);
                    $unique_filename = generate_unique_filename($version_dir, $original_name);
                    $target_file = $version_dir . '/' . $unique_filename;
                    
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $file_paths[] = $target_file;
                        
                        // Log successful file upload
                        log_error("File uploaded for new version", "file", [
                            'filename' => $unique_filename,
                            'path' => $target_file,
                            'version' => "v{$nextVersion}"
                        ]);
                    } else {
                        // Log file upload error
                        log_error("Failed to upload file", ERROR_FILE, [
                            'filename' => $original_name,
                            'error' => error_get_last()
                        ]);
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
        
        log_error("Creating version record", "dataset", [
            'version' => $version_number,
            'batch_id' => $batch_id,
            'file_path' => $main_file_path
        ]);
        
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
        log_error("Version created", "dataset", ['version_id' => $new_version_id]);
        
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
    set_success_message("Dataset updated successfully!");
    
    // Log success
    log_error("Dataset update successful", "dataset", [
        'dataset_id' => $dataset_id,
        'batch_id' => $batch_id,
        'update_type' => $update_type
    ]);
    
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
    
    log_error("Dataset update failed", ERROR_DATABASE, [
        'dataset_id' => $dataset_id,
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    handle_error("Error updating dataset: " . $e->getMessage(), ERROR_DATABASE);
    
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