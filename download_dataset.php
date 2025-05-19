<?php
session_start();
include 'db_connection.php';
include 'includes/error_handler.php';
include 'includes/path_handler.php';

try {
    // Check if dataset_id is provided
    if (!isset($_GET['dataset_id'])) {
        throw new Exception("Dataset ID is required");
    }
    
    $dataset_id = (int)$_GET['dataset_id'];
    
    // Get dataset information
    $sql = "SELECT d.*, dv.file_path, dv.version_number 
            FROM datasets d 
            JOIN datasetversions dv ON d.dataset_batch_id = dv.dataset_batch_id 
            WHERE d.dataset_id = ? AND dv.is_current = 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $dataset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Dataset not found");
    }
    
    $dataset = $result->fetch_assoc();
    
    // Get the file path
    $file_path = $dataset['file_path'];
    
    // Convert to absolute path if necessary
    if (strpos($file_path, '/') !== 0 && strpos($file_path, ':\\') !== 1) {
        $file_path = get_absolute_path($file_path);
    }
    
    // Check if file exists
    if (!file_exists($file_path)) {
        log_error("Dataset file not found for download", ERROR_FILE, [
            'dataset_id' => $dataset_id,
            'file_path' => $file_path
        ]);
        throw new Exception("Dataset file not found");
    }
    
    // Get file information
    $file_name = basename($file_path);
    $file_size = filesize($file_path);
    $file_extension = get_file_extension($file_path);
    
    // Set appropriate content type based on file extension
    switch ($file_extension) {
        case 'csv':
            $content_type = 'text/csv';
            break;
        case 'json':
            $content_type = 'application/json';
            break;
        case 'xlsx':
            $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            break;
        case 'xls':
            $content_type = 'application/vnd.ms-excel';
            break;
        case 'xml':
            $content_type = 'application/xml';
            break;
        default:
            $content_type = 'application/octet-stream';
    }
    
    // Create a clean filename for download
    $safe_title = sanitize_filename($dataset['title']);
    $version = $dataset['version_number'] ?? 'v1';
    $download_filename = "{$safe_title}_{$version}.{$file_extension}";
    
    // Log the download
    log_error("Dataset download initiated", "dataset", [
        'dataset_id' => $dataset_id,
        'file_name' => $file_name,
        'user_id' => $_SESSION['user_id'] ?? 'guest'
    ]);
    
    // Record the download in the database if user is logged in
    if (isset($_SESSION['user_id'])) {
        $log_sql = "INSERT INTO datasetaccesslogs (user_id, dataset_id, action) VALUES (?, ?, 'View')";
        $log_stmt = $conn->prepare($log_sql);
        
        if ($log_stmt) {
            $user_id = $_SESSION['user_id'];
            $log_stmt->bind_param("ii", $user_id, $dataset_id);
            $log_stmt->execute();
        }
    }
    
    // Set headers for download
    header("Content-Type: {$content_type}");
    header("Content-Disposition: attachment; filename=\"{$download_filename}\"");
    header("Content-Length: {$file_size}");
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: 0");
    header("Pragma: public");
    
    // Output the file
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    // Log the error
    log_error("Dataset download error", ERROR_GENERAL, [
        'exception' => $e->getMessage(),
        'dataset_id' => $dataset_id ?? null,
        'trace' => $e->getTraceAsString()
    ]);
    
    // Redirect to error page or show error message
    $_SESSION['error_message'] = "Error downloading dataset: " . $e->getMessage();
    header("Location: datasets.php");
    exit;
}
?> 