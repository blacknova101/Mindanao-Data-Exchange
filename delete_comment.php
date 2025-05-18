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
if (!isset($_POST['comment_id']) || !isset($_POST['dataset_id']) || !isset($_POST['return_page'])) {
    $_SESSION['error_message'] = "Missing required parameters for deleting comment.";
    header("Location: HomeLogin.php");
    exit;
}

$comment_id = (int)$_POST['comment_id'];
$dataset_id = (int)$_POST['dataset_id'];
$return_page = $_POST['return_page'];

// Verify the user owns this comment
$checkOwnership = "SELECT user_id FROM datasetcomments WHERE comment_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $checkOwnership);
mysqli_stmt_bind_param($stmt, 'ii', $comment_id, $user_id);
mysqli_stmt_execute($stmt);
$ownershipResult = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($ownershipResult) > 0) {
    // Delete the comment
    $deleteComment = "DELETE FROM datasetcomments WHERE comment_id = ?";
    $stmt = mysqli_prepare($conn, $deleteComment);
    mysqli_stmt_bind_param($stmt, 'i', $comment_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Comment deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Error deleting comment: " . mysqli_error($conn);
    }
} else {
    $_SESSION['error_message'] = "You don't have permission to delete this comment.";
}

// Redirect back to the appropriate page
header("Location: {$return_page}?id={$dataset_id}");
exit;
?> 