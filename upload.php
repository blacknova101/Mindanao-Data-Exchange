<?php
session_start();

// Check if the user is logged in (ensure 'user_id' is set in the session)
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not authenticated
    header("Location: login.php");
    exit();
}
?>
<?php
session_start(); // Start the session at the top
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $targetDirectory = "uploads/";
    $targetFile = $targetDirectory . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if file already exists
    if (file_exists($targetFile)) {
        $_SESSION['error_message'] = "Sorry, file already exists.";
        $uploadOk = 0;
    }

    // Check file size (5MB max)
    if ($_FILES["fileToUpload"]["size"] > 5000000) {
        $_SESSION['error_message'] = "Sorry, your file is too large.";
        $uploadOk = 0;
        header("Location: uploadselection.php");
        exit();
    }

    // Define allowed file types (CSV, XLS, XLSX, and JSON)
    $allowedTypes = ["csv", "xls", "xlsx", "json"];
    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['error_message'] = "Sorry, only CSV, Excel (XLS, XLSX), and JSON files are allowed.";
        $uploadOk = 0;
        header("Location: uploadselection.php");
        exit();
    }

    // If everything is ok, try to upload the file
    if ($uploadOk == 1) {
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {
            $_SESSION['success_message'] = "The file " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " has been uploaded.";
            $_SESSION['uploaded_file'] = basename($_FILES["fileToUpload"]["name"]); // Store file name in session
            // Redirect to success page after successful upload
            header("Location: upload_fill.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Sorry, there was an error uploading your file.";
        }
    } else {
        // If upload failed, redirect to upload page with error
        header("Location: uploadselection.php");
        exit();
    }
}
?>
