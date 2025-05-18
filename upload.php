<?php
session_start();
include 'db_connection.php'; // Ensure your DB connection is here

// Include session update to ensure organization_id is synchronized
include 'update_session.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$allowedTypes = ["csv", "xls", "xlsx", "json", "xml"];
$maxFileSize = 100 * 1024 * 1024; // 100MB
$validFiles = [];
$errors = [];

// Get the user's organization name
$query = "SELECT o.name AS org_name 
          FROM users u 
          JOIN organizations o ON u.organization_id = o.organization_id 
          WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || !$result->num_rows) {
    $_SESSION['error_message'] = "Organization not found.";
    header("Location: uploadselection.php");
    exit();
}
$row = $result->fetch_assoc();
$organizationName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['org_name']); // Sanitize folder name

// Create the organization's directory inside the uploads folder if it doesn't exist
$uploadDir = "uploads/" . $organizationName . "/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true); // Create directory if it doesn't exist
}

// Create a temporary unique folder for this upload session
$sessionFolder = uniqid('temp_');
$sessionDir = $uploadDir . $sessionFolder . '/';
mkdir($sessionDir, 0777, true);

// Store the session directory for later cleanup if needed
$_SESSION['current_upload_dir'] = $sessionDir;

// Check for and clean up old temporary folders (older than 24 hours)
$tempDirs = glob($uploadDir . 'temp_*');
foreach ($tempDirs as $dir) {
    // Skip the current session directory
    if ($dir != $sessionDir) {
        // If directory is older than 24 hours, remove it
        if (is_dir($dir) && (time() - filemtime($dir) > 86400)) {
            array_map('unlink', glob("$dir/*"));
            rmdir($dir);
        }
    }
}

foreach ($_FILES['fileToUpload']['name'] as $key => $fileName) {
    $tmpName = $_FILES['fileToUpload']['tmp_name'][$key];
    $fileSize = $_FILES['fileToUpload']['size'][$key];
    $fileError = $_FILES['fileToUpload']['error'][$key];
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

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
    if (!in_array($extension, $allowedTypes)) {
        $errors[] = "$fileName: invalid file type. Allowed types: " . implode(", ", $allowedTypes);
        continue;
    }

    // Keep original filename - no renaming needed since files are in a unique session folder
    $filePath = $sessionDir . $fileName;
    
    // Move the file to the session folder
    if (move_uploaded_file($tmpName, $filePath)) {
        $validFiles[] = $filePath;
    } else {
        $errors[] = "$fileName: file move failed.";
    }
}

// If validation fails, show error message and stay on the upload page
if (!empty($errors)) {
    $_SESSION['error_message'] = implode("<br>", $errors);
    header("Location: uploadselection.php");
    exit();
}

// If files are valid, proceed to the next step
if ($validFiles) {
    $_SESSION['valid_files'] = $validFiles;
    // Store the uploaded file paths to the session
    $_SESSION['uploaded_file'] = $validFiles;
    header("Location: upload_fill.php"); // Redirect to dataset details page
    exit();
}
?>
