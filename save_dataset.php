<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Assuming session and db connection code is already correct
$user_id = $_SESSION['user_id'];

// Query to get user and organization details
$query = "SELECT o.organization_id, o.name AS org_name, u.first_name, u.last_name
          FROM users u
          JOIN organizations o ON u.organization_id = o.organization_id
          WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || !$result->num_rows) {
    $_SESSION['error_message'] = "User or organization not found.";
    header("Location: unauthorized.php");
    exit();
}

$row = $result->fetch_assoc();
if (isset($row['organization_id'])) {
    $organization_id = $row['organization_id']; // Fetch organization_id
    $_SESSION['organization_id'] = $organization_id; // <-- Fix: update session variable!
    $org_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['org_name']); // Clean for filesystem
    $first_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['first_name']);
    $last_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['last_name']);
    $user_folder = "{$first_name}_{$last_name}";
} else {
    $_SESSION['error_message'] = "No organization found for this user.";
    header("Location: unauthorized.php");
    exit();
}

// Store form data
$formData = [
    'title' => $_POST['title'],
    'description' => $_POST['description'],
    'start_period' => $_POST['start_period'],
    'end_period' => $_POST['end_period'],
    'source' => $_POST['source'],
    'link' => $_POST['link'],
    'visibility' => $_POST['visibility'],
    'category' => $_POST['category'],
    'locations' => isset($_POST['locations']) ? $_POST['locations'] : []
];

// Store form data in session for potential repopulation
$_SESSION['form_data'] = $formData;

// Form data
$title = $_POST['title'];
$description = $_POST['description'];
$start_period = $_POST['start_period'];
$end_period = $_POST['end_period'];
$source = $_POST['source'];
$link = $_POST['link'];
$visibility = ucfirst($_POST['visibility']); // Capitalize first letter to match database enum

// Handle custom category
$category_id = $_POST['category'];
if (strpos($category_id, 'custom:') === 0) {
    // Extract the custom category name
    $custom_category_name = substr($category_id, 7); // Remove 'custom:' prefix
    
    // Check if category already exists
    $check_query = "SELECT category_id FROM datasetcategories WHERE name = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $custom_category_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Category already exists, use its ID
        $category_row = $check_result->fetch_assoc();
        $category_id = $category_row['category_id'];
    } else {
        // Create new category
        $insert_category_query = "INSERT INTO datasetcategories (name) VALUES (?)";
        $insert_stmt = $conn->prepare($insert_category_query);
        $insert_stmt->bind_param("s", $custom_category_name);
        $insert_stmt->execute();
        $category_id = $insert_stmt->insert_id;
    }
}

// Handle locations
$location_string = '';
if (isset($_POST['locations']) && isset($_POST['locations'][0])) {
    $location = $_POST['locations'][0];
    if (!empty($location['province']) && !empty($location['city']) && !empty($location['barangay'])) {
        $location_string = $location['province'] . ' - ' . $location['city'] . ' - ' . $location['barangay'];
    }
}

// Validation errors
$validation_errors = [];

if (strtotime($start_period) > strtotime($end_period)) {
    $validation_errors['date'] = "Start date must be earlier than end date.";
}

// Category validation - allow for custom category format
if (empty($category_id)) {
    $validation_errors['category'] = "A category must be selected or created.";
}

// If it's not a custom category and not numeric, it's invalid
if (strpos($category_id, 'custom:') !== 0 && !is_numeric($category_id)) {
    $validation_errors['category'] = "Invalid category selected.";
}

// If there are validation errors, return to form with errors
if (!empty($validation_errors)) {
    $_SESSION['validation_errors'] = $validation_errors;
    $_SESSION['error_message'] = reset($validation_errors); // Set first error as main error message
    header("Location: upload_fill.php");
    exit();
}

// Insert dataset batch with organization_id and visibility
$query = "INSERT INTO dataset_batches (user_id, organization_id, visibility) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iis", $user_id, $organization_id, $visibility); // Include visibility in binding
$stmt->execute();
$dataset_batch_id = $stmt->insert_id;

// Create directory: org/first_last/batch_id
$base_dir = "uploads/{$org_name}/{$user_folder}/{$dataset_batch_id}";
if (!is_dir($base_dir)) {
    mkdir($base_dir, 0777, true); // recursive creation
}

// Move and record files
$first_file_path = null; // Track the first file path for the version record
$file_move_success = false; // Track if at least one file was moved successfully

// Debug information
error_log("Valid files in session: " . print_r($_SESSION['valid_files'], true));
error_log("Looking for files in base directory: {$base_dir}");

// Check if we have a current upload directory set
if (isset($_SESSION['current_upload_dir']) && is_dir($_SESSION['current_upload_dir'])) {
    error_log("Current upload directory exists: " . $_SESSION['current_upload_dir']);
    // Get all files from the current upload directory
    $upload_files = glob($_SESSION['current_upload_dir'] . "/*");
    
    if (!empty($upload_files)) {
        foreach ($upload_files as $temp_path) {
            if (is_file($temp_path)) {
                $file_name = basename($temp_path);
                $new_path = "{$base_dir}/{$file_name}";
                
                error_log("Moving file from {$temp_path} to {$new_path}");
                
                if (rename($temp_path, $new_path)) {
                    $query = "INSERT INTO datasets (dataset_batch_id, user_id, file_path, title, description, start_period, end_period, source, link, location, category_id, visibility)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("iissssssssis", $dataset_batch_id, $user_id, $new_path, $title, $description, $start_period, $end_period, $source, $link, $location_string, $category_id, $visibility);
                    $stmt->execute();
                    
                    // Store the first file path for the version record
                    if ($first_file_path === null) {
                        $first_file_path = $new_path;
                    }
                    
                    $file_move_success = true;
                    error_log("Successfully moved file to {$new_path}");
                } else {
                    error_log("Failed to move file from {$temp_path} to {$new_path}");
                }
            }
        }
    } else {
        error_log("No files found in current upload directory: " . $_SESSION['current_upload_dir']);
    }
} else {
    // Original file path handling as fallback
    foreach ($_SESSION['valid_files'] as $temp_path) {
        $file_name = basename($temp_path);
        $new_path = "{$base_dir}/{$file_name}";
        
        error_log("Attempting to move file from {$temp_path} to {$new_path}");
        
        // Check if source file exists
        if (file_exists($temp_path)) {
            if (rename($temp_path, $new_path)) {
                $query = "INSERT INTO datasets (dataset_batch_id, user_id, file_path, title, description, start_period, end_period, source, link, location, category_id, visibility)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iissssssssis", $dataset_batch_id, $user_id, $new_path, $title, $description, $start_period, $end_period, $source, $link, $location_string, $category_id, $visibility);
                $stmt->execute();
                
                // Store the first file path for the version record
                if ($first_file_path === null) {
                    $first_file_path = $new_path;
                }
                
                $file_move_success = true;
                error_log("Successfully moved file to {$new_path}");
            } else {
                error_log("Failed to move file from {$temp_path} to {$new_path}");
            }
        } else {
            error_log("Source file not found at {$temp_path}");
        }
    }
}

// If no files were successfully moved, create a placeholder file to ensure we have a valid file_path
if (!$file_move_success || $first_file_path === null) {
    error_log("No files were successfully moved - creating placeholder file");
    $placeholder_path = "{$base_dir}/placeholder.txt";
    file_put_contents($placeholder_path, "This is a placeholder file for dataset {$dataset_batch_id}.");
    $first_file_path = $placeholder_path;
    
    // Insert a dataset record for the placeholder
    $query = "INSERT INTO datasets (dataset_batch_id, user_id, file_path, title, description, start_period, end_period, source, link, location, category_id, visibility)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iissssssssis", $dataset_batch_id, $user_id, $first_file_path, $title, $description, $start_period, $end_period, $source, $link, $location_string, $category_id, $visibility);
    $stmt->execute();
}

// Create the initial Version 1 record
$version_number = "v1";
$change_notes = "Initial upload";

// Following the exact pattern from update_dataset.php
$insertVersionSql = "
    INSERT INTO datasetversions (
        dataset_batch_id, version_number, file_path, title, description,
        start_period, end_period, category_id, source, link, location,
        created_by, is_current, change_notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
";

$stmt = $conn->prepare($insertVersionSql);
$stmt->bind_param(
    'issssssisssis',
    $dataset_batch_id,
    $version_number,
    $first_file_path,
    $title,
    $description,
    $start_period,
    $end_period,
    $category_id,
    $source,
    $link,
    $location_string,
    $user_id,
    $change_notes
);
$stmt->execute();

// Clear form data from session on successful submission
unset($_SESSION['form_data']);
unset($_SESSION['validation_errors']);
unset($_SESSION['valid_files']);

// Clean up the temporary upload directory if it exists
if (isset($_SESSION['current_upload_dir']) && is_dir($_SESSION['current_upload_dir'])) {
    // Files are already moved to their permanent location, so we can remove the temp directory
    unset($_SESSION['current_upload_dir']);
}

$_SESSION['success_message'] = "Dataset added successfully!";
$_SESSION['upload_success'] = true;
header("Location: dataset_success.php");
exit();


?>
