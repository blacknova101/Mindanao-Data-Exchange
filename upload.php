<?php
session_start();
include 'db_connection.php'; // Ensure your DB connection is here
include 'includes/error_handler.php'; // Include error handler
include 'includes/path_handler.php'; // Include path handler

// Include session update to ensure organization_id is synchronized
include 'update_session.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    handle_error("You must be logged in to upload files.", ERROR_AUTH, "login.php");
}

$user_id = $_SESSION['user_id'];
$allowedTypes = ["csv", "xls", "xlsx", "json", "xml"];
$maxFileSize = 100 * 1024 * 1024; // 100MB
$validFiles = [];
$errors = [];

try {
    // Get the user's organization name
    $query = "SELECT o.name AS org_name 
              FROM users u 
              JOIN organizations o ON u.organization_id = o.organization_id 
              WHERE u.user_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        handle_db_error("Database error occurred", $conn, "uploadselection.php");
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || !$result->num_rows) {
        handle_error("Organization not found. You must be part of an organization to upload datasets.", ERROR_PERMISSION, "uploadselection.php");
    }
    
    $row = $result->fetch_assoc();
    $organizationName = sanitize_filename($row['org_name']); // Sanitize folder name using our function
    
    // Create the organization's directory inside the uploads folder
    $uploadDir = get_uploads_dir('datasets/' . $organizationName);
    
    // Create a temporary unique folder for this upload session
    $sessionFolder = uniqid('temp_');
    $sessionPath = $uploadDir . '/' . $sessionFolder;
    ensure_directory_exists($sessionPath);
    
    // Store the session directory for later cleanup if needed
    $_SESSION['current_upload_dir'] = $sessionPath;
    
    // Check for and clean up old temporary folders (older than 24 hours)
    $tempDirs = glob($uploadDir . '/temp_*');
    foreach ($tempDirs as $dir) {
        // Skip the current session directory
        if ($dir != $sessionPath) {
            // If directory is older than 24 hours, remove it
            if (is_dir($dir) && (time() - filemtime($dir) > 86400)) {
                array_map('unlink', glob("$dir/*"));
                rmdir($dir);
            }
        }
    }
    
    // Process each uploaded file
    foreach ($_FILES['fileToUpload']['name'] as $key => $fileName) {
        $tmpName = $_FILES['fileToUpload']['tmp_name'][$key];
        $fileSize = $_FILES['fileToUpload']['size'][$key];
        $fileError = $_FILES['fileToUpload']['error'][$key];
        $extension = get_file_extension($fileName);
    
        // File upload error check
        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "$fileName: upload error.";
            continue;
        }
    
        // File size validation
        if ($fileSize > $maxFileSize) {
            $errors[] = "$fileName: exceeds 100MB limit.";
            continue;
        }
    
        // File type validation
        if (!validate_file_type($fileName, $allowedTypes)) {
            $errors[] = "$fileName: invalid file type. Allowed types: " . implode(", ", $allowedTypes);
            continue;
        }
    
        // Generate a unique filename to avoid conflicts
        $safeFileName = generate_unique_filename($sessionPath, $fileName);
        $filePath = $sessionPath . '/' . $safeFileName;
        
        // Move the file to the session folder
        if (move_uploaded_file($tmpName, $filePath)) {
            $validFiles[] = $filePath;
        } else {
            $errors[] = "$fileName: file move failed.";
            log_error("File upload failed", ERROR_FILE, [
                'filename' => $fileName,
                'tmp_name' => $tmpName,
                'destination' => $filePath,
                'error' => error_get_last()
            ]);
        }
    }
    
    // If validation fails, show error message and stay on the upload page
    if (!empty($errors)) {
        handle_error(implode("<br>", $errors), ERROR_VALIDATION, "uploadselection.php");
    }
    
    // If files are valid, proceed to the next step
    if ($validFiles) {
        $_SESSION['valid_files'] = $validFiles;
        // Store the uploaded file paths to the session
        $_SESSION['uploaded_file'] = $validFiles;
        
        // Log successful upload
        log_error("Files uploaded successfully", "upload", [
            'user_id' => $user_id,
            'organization' => $organizationName,
            'file_count' => count($validFiles)
        ]);
        
        header("Location: upload_fill.php"); // Redirect to dataset details page
        exit();
    } else {
        handle_error("No valid files were uploaded.", ERROR_VALIDATION, "uploadselection.php");
    }
} catch (Exception $e) {
    log_error("Upload error", ERROR_GENERAL, [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    handle_error("An unexpected error occurred. Please try again.", ERROR_GENERAL, "uploadselection.php");
}
?>
