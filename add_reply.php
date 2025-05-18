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
if (!isset($_POST['reply_text']) || !isset($_POST['comment_id']) || !isset($_POST['dataset_id']) || !isset($_POST['return_page'])) {
    $_SESSION['error_message'] = "Missing required parameters for adding reply.";
    header("Location: HomeLogin.php");
    exit;
}

$reply_text = $_POST['reply_text'];
$comment_id = (int)$_POST['comment_id'];
$dataset_id = (int)$_POST['dataset_id'];
$return_page = $_POST['return_page'];

// Insert the new reply
$insertReply = "
    INSERT INTO comment_replies (comment_id, user_id, reply_text, timestamp)
    VALUES (?, ?, ?, NOW())
";

$stmt = mysqli_prepare($conn, $insertReply);
mysqli_stmt_bind_param($stmt, 'iis', $comment_id, $user_id, $reply_text);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success_message'] = "Reply added successfully.";
    
    // Get information for notification
    $getCommentInfo = "
        SELECT dc.user_id AS comment_owner_id, CONCAT(u.first_name, ' ', u.last_name) AS replier_name, 
               d.title AS dataset_title, d.user_id AS dataset_owner_id
        FROM datasetcomments dc
        JOIN users u ON u.user_id = ?
        JOIN datasets d ON d.dataset_id = dc.dataset_id
        WHERE dc.comment_id = ?
    ";
    $stmt = mysqli_prepare($conn, $getCommentInfo);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $comment_id);
    mysqli_stmt_execute($stmt);
    $infoResult = mysqli_stmt_get_result($stmt);
    
    if ($infoData = mysqli_fetch_assoc($infoResult)) {
        $comment_owner_id = $infoData['comment_owner_id'];
        $dataset_owner_id = $infoData['dataset_owner_id'];
        $replier_name = $infoData['replier_name'];
        $dataset_title = $infoData['dataset_title'];
        
        // Get all previous repliers (excluding current user)
        $getPreviousRepliers = "
            SELECT DISTINCT user_id 
            FROM comment_replies 
            WHERE comment_id = ? AND user_id != ?
        ";
        $stmt = mysqli_prepare($conn, $getPreviousRepliers);
        mysqli_stmt_bind_param($stmt, 'ii', $comment_id, $user_id);
        mysqli_stmt_execute($stmt);
        $repliersResult = mysqli_stmt_get_result($stmt);
        
        // Create a set of users who have already been notified
        $notifiedUsers = array($user_id); // Don't notify yourself
        
        // Don't notify if replying to your own comment
        if ($comment_owner_id != $user_id) {
            // Notify the comment owner
            $notificationMessage = "$replier_name replied to your comment on dataset: $dataset_title";
            $insertNotification = "
                INSERT INTO user_notifications (user_id, message, related_id, notification_type, is_read)
                VALUES (?, ?, ?, 'comment_reply', 0)
            ";
            $stmt = mysqli_prepare($conn, $insertNotification);
            mysqli_stmt_bind_param($stmt, 'isi', $comment_owner_id, $notificationMessage, $dataset_id);
            mysqli_stmt_execute($stmt);
            
            // Add to notified users
            $notifiedUsers[] = $comment_owner_id;
        }
        
        // Notify all previous repliers
        while ($replier = mysqli_fetch_assoc($repliersResult)) {
            $replier_id = $replier['user_id'];
            
            // Check if this user has already been notified (e.g., they might be the comment owner)
            if (!in_array($replier_id, $notifiedUsers)) {
                $notificationMessage = "$replier_name also replied to a comment you participated in on dataset: $dataset_title";
                $insertNotification = "
                    INSERT INTO user_notifications (user_id, message, related_id, notification_type, is_read)
                    VALUES (?, ?, ?, 'comment_conversation', 0)
                ";
                $stmt = mysqli_prepare($conn, $insertNotification);
                mysqli_stmt_bind_param($stmt, 'isi', $replier_id, $notificationMessage, $dataset_id);
                mysqli_stmt_execute($stmt);
                
                // Add to notified users
                $notifiedUsers[] = $replier_id;
            }
        }
        
        // Also notify the dataset owner if they are not the commenter or replier
        if ($dataset_owner_id != $user_id && !in_array($dataset_owner_id, $notifiedUsers)) {
            $notificationMessage = "$replier_name replied to a comment on your dataset: $dataset_title";
            $insertNotification = "
                INSERT INTO user_notifications (user_id, message, related_id, notification_type, is_read)
                VALUES (?, ?, ?, 'dataset_activity', 0)
            ";
            $stmt = mysqli_prepare($conn, $insertNotification);
            mysqli_stmt_bind_param($stmt, 'isi', $dataset_owner_id, $notificationMessage, $dataset_id);
            mysqli_stmt_execute($stmt);
        }
    }
} else {
    $_SESSION['error_message'] = "Error adding reply: " . mysqli_error($conn);
}

// Redirect back to the appropriate page with an anchor to the specific comment
header("Location: {$return_page}?id={$dataset_id}#comment-{$comment_id}");
exit; 