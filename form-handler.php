<?php
session_start();
include 'db_connection.php';  // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the data from the form and check if it's set
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $visibility = isset($_POST['visibility']) ? $_POST['visibility'] : '';
    $category = isset($_POST['category']) ? $_POST['category'] : '';
    $start_period = isset($_POST['start_period']) ? $_POST['start_period'] : '';
    $end_period = isset($_POST['end_period']) ? $_POST['end_period'] : '';
    $source = isset($_POST['source']) ? $_POST['source'] : '';
    $location = isset($_POST['location']) ? $_POST['location'] : '';
    $link = isset($_POST['link']) ? $_POST['link'] : '';
    
    $file = isset($_FILES['fileToUpload']) ? $_FILES['fileToUpload'] : null;

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "You must be logged in to upload a dataset.";
        exit;
    }
    $user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

    // Validate the data
    if (empty($title) || empty($description) || empty($visibility) || empty($category) || empty($start_period) || empty($end_period) || empty($source) || empty($location) || empty($link)) {
        echo "All fields are required!";
    } else {
        // Handle file upload if a file was uploaded
        if ($file) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($file["name"]);
            $uploadOk = 1;
            $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Define allowed file types
            $allowedTypes = ["csv", "xls", "xlsx", "json"];

            // Check if file is too large
            if ($file["size"] > 5000000) {  // Limit file size to 5MB
                echo "Sorry, your file is too large.";
                $uploadOk = 0;
            }

            // Allow only certain file formats (CSV, XLS, XLSX, JSON)
            if (!in_array($fileType, $allowedTypes)) {
                $_SESSION['error_message'] = "Sorry, only CSV, Excel (XLS, XLSX), and JSON files are allowed.";
                $uploadOk = 0;
            }
// Handle and format the start and end periods (only month and year)
$start_period = isset($_POST['start_period']) ? $_POST['start_period'] : '';
$end_period = isset($_POST['end_period']) ? $_POST['end_period'] : '';

// Ensure they are in the correct format (YYYY-MM)
if (preg_match("/^\d{4}-\d{2}$/", $start_period) && preg_match("/^\d{4}-\d{2}$/", $end_period)) {
    // If the format is valid, store them as is
    $start_date = $start_period; // Store as YYYY-MM
    $end_date = $end_period;     // Store as YYYY-MM
} else {
    echo "Invalid date format. Please use the format YYYY-MM.";
    exit;
}
            // Try to upload the file if no errors
            if ($uploadOk == 1) {
                if (move_uploaded_file($file["tmp_name"], $target_file)) {
                    $_SESSION['uploaded_file'] = basename($file["name"]);
                    echo "The file " . htmlspecialchars(basename($file["name"])) . " has been uploaded.";
                } else {
                    echo "Sorry, there was an error uploading your file.";
                }
            }
        }


        // Insert the form data into the database
        $sql = "INSERT INTO datasets (title, description, visibility, category, start_period, end_period, source, location, link, file_path, user_id)
                VALUES ('$title', '$description', '$visibility', '$category', '$start_period', '$end_period', '$source', '$location', '$link', '$target_file', '$user_id')";

        if (mysqli_query($conn, $sql)) {
            // Redirect to a success page or a confirmation message
            header("Location: dataset_success.php");
            exit();
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
    }
}
?>
