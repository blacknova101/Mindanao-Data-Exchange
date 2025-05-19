<?php
session_start();
include('db_connection.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not authenticated
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;

// Check if we have all required parameters
if (!isset($_POST['comment_text']) || !isset($_POST['dataset_id']) || !isset($_POST['return_page'])) {
    $_SESSION['error_message'] = "Missing required parameters for adding comment.";
    header("Location: HomeLogin.php");
    exit;
}

$comment_text = $_POST['comment_text'];
$dataset_id = (int)$_POST['dataset_id'];
$return_page = $_POST['return_page'];

// Insert the new comment
$insertComment = "
    INSERT INTO datasetcomments (dataset_id, user_id, comment_text, timestamp)
    VALUES (?, ?, ?, NOW())
";

$stmt = mysqli_prepare($conn, $insertComment);
mysqli_stmt_bind_param($stmt, 'iis', $dataset_id, $user_id, $comment_text);

if (mysqli_stmt_execute($stmt)) {
    // Don't set session message to avoid it appearing on other pages
    // $_SESSION['success_message'] = "Comment added successfully.";
    
    // Get dataset owner info for notification
    $getDatasetOwner = "
        SELECT d.user_id AS owner_id, CONCAT(u.first_name, ' ', u.last_name) AS commenter_name, d.title AS dataset_title
        FROM datasets d
        LEFT JOIN users u ON u.user_id = ?
        WHERE d.dataset_id = ?
    ";
    $stmt = mysqli_prepare($conn, $getDatasetOwner);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $dataset_id);
    mysqli_stmt_execute($stmt);
    $ownerResult = mysqli_stmt_get_result($stmt);
    
    if ($ownerData = mysqli_fetch_assoc($ownerResult)) {
        $owner_id = $ownerData['owner_id'];
        $commenter_name = $ownerData['commenter_name'];
        $dataset_title = $ownerData['dataset_title'];
        
        // Don't notify if owner is commenting on their own dataset
        if ($owner_id != $user_id) {
            // Create a notification for the dataset owner
            $notificationMessage = "$commenter_name commented on your dataset: $dataset_title";
            $insertNotification = "
                INSERT INTO user_notifications (user_id, message, related_id, notification_type, is_read)
                VALUES (?, ?, ?, 'new_comment', 0)
            ";
            $stmt = mysqli_prepare($conn, $insertNotification);
            mysqli_stmt_bind_param($stmt, 'isi', $owner_id, $notificationMessage, $dataset_id);
            mysqli_stmt_execute($stmt);
        }
    }
    
    // Redirect back to the appropriate page with comment_added parameter
    header("Location: {$return_page}?id={$dataset_id}&comment_added=1");
    exit;
} else {
    $_SESSION['error_message'] = "Error adding comment: " . mysqli_error($conn);
    
    // Redirect back to the appropriate page
    header("Location: {$return_page}?id={$dataset_id}");
    exit;
} 