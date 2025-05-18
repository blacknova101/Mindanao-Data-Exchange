<?php
session_start();
include('db_connection.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark a notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];
    $updateSql = "UPDATE user_notifications 
                  SET is_read = TRUE 
                  WHERE notification_id = $notification_id 
                  AND user_id = $user_id";
    mysqli_query($conn, $updateSql);
    
    // Redirect to remove the query parameter
    header("Location: notifications.php");
    exit();
}

// Mark all notifications as read if requested
if (isset($_GET['mark_all_read'])) {
    $updateAllSql = "UPDATE user_notifications 
                     SET is_read = TRUE 
                     WHERE user_id = $user_id";
    mysqli_query($conn, $updateAllSql);
    
    // Redirect to remove the query parameter
    header("Location: notifications.php");
    exit();
}

// Get all notifications for the current user
$notificationsSql = "
    SELECT * FROM user_notifications
    WHERE user_id = $user_id
    ORDER BY created_at DESC
";
$notificationsResult = mysqli_query($conn, $notificationsSql);

// Count unread notifications
$unreadSql = "SELECT COUNT(*) as count FROM user_notifications 
              WHERE user_id = $user_id AND is_read = FALSE";
$unreadResult = mysqli_query($conn, $unreadSql);
$unreadCount = mysqli_fetch_assoc($unreadResult)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Notifications</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5%;
            padding-left: 30px;
            background-color: #0099ff;
            color: #cfd9ff;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            margin: 10px 0;
            backdrop-filter: blur(10px);
            max-width: 1200px;
            width: 100%;
            margin-top: 30px;
            margin-left: auto;
            margin-right: auto;
            font-weight: bold;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo img {
            height: auto;
            width: 80px;
            max-width: 100%;
        }
        .nav-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-size: 18px;
            transition: transform 0.3s ease;
        }
        .nav-links a:hover {
            transform: scale(1.2);
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #007BFF;
            margin-bottom: 20px;
        }
        .actions {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            padding: 8px 16px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .unread-count {
            background-color: #ff3b30;
            color: white;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 14px;
            margin-left: 10px;
        }
        .notification {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
            transition: background-color 0.2s;
        }
        .notification:hover {
            background-color: #f9f9f9;
        }
        .notification.unread {
            background-color: #e6f7ff;
        }
        .notification.unread:hover {
            background-color: #d6f0ff;
        }
        .notification-icon {
            margin-right: 15px;
            font-size: 24px;
            color: #007BFF;
        }
        .notification-icon .fa-comment {
            color: #28a745;
        }
        .notification-icon .fa-reply {
            color: #17a2b8;
        }
        .notification-icon .fa-comments {
            color: #6f42c1;
        }
        .notification-content {
            flex-grow: 1;
        }
        .notification-message {
            margin: 0 0 5px 0;
            line-height: 1.4;
        }
        .notification-time {
            color: #666;
            font-size: 12px;
        }
        .notification-action {
            margin-left: 10px;
        }
        .notification-action a {
            color: #007BFF;
            text-decoration: none;
            font-size: 14px;
        }
        .notification-action a:hover {
            text-decoration: underline;
        }
        .empty-notifications {
            text-align: center;
            padding: 40px 0;
            color: #666;
        }
        #background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>
    
    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mindanao Data Exchange Logo">
            <h2>Notifications</h2>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
        </nav>
    </header>
    
    <div class="container">
        <div class="actions">
            <h1>
                Your Notifications
                <?php if ($unreadCount > 0): ?>
                    <span class="unread-count"><?php echo $unreadCount; ?> unread</span>
                <?php endif; ?>
            </h1>
            <?php if (mysqli_num_rows($notificationsResult) > 0 && $unreadCount > 0): ?>
                <a href="?mark_all_read=1" class="btn">Mark All as Read</a>
            <?php endif; ?>
        </div>
        
        <?php if (mysqli_num_rows($notificationsResult) > 0): ?>
            <div class="notifications-list">
                <?php while ($notification = mysqli_fetch_assoc($notificationsResult)): ?>
                    <div class="notification <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-icon">
                            <?php if ($notification['notification_type'] == 'access_approved'): ?>
                                <i class="fa-solid fa-check-circle"></i>
                            <?php elseif ($notification['notification_type'] == 'access_rejected'): ?>
                                <i class="fa-solid fa-times-circle"></i>
                            <?php elseif ($notification['notification_type'] == 'new_comment'): ?>
                                <i class="fa-solid fa-comment"></i>
                            <?php elseif ($notification['notification_type'] == 'comment_reply'): ?>
                                <i class="fa-solid fa-reply"></i>
                            <?php elseif ($notification['notification_type'] == 'comment_conversation'): ?>
                                <i class="fa-solid fa-comments"></i>
                            <?php elseif ($notification['notification_type'] == 'dataset_activity'): ?>
                                <i class="fa-solid fa-bell"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-bell"></i>
                            <?php endif; ?>
                        </div>
                        <div class="notification-content">
                            <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <p class="notification-time"><?php echo date('M j, Y, g:i a', strtotime($notification['created_at'])); ?></p>
                        </div>
                        <?php if (!$notification['is_read']): ?>
                            <div class="notification-action">
                                <a href="?mark_read=<?php echo $notification['notification_id']; ?>">Mark as Read</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($notification['notification_type'] == 'access_approved' && !empty($notification['related_id'])): ?>
                            <div class="notification-action">
                                <a href="dataset.php?id=<?php echo $notification['related_id']; ?>">View Dataset</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($notification['notification_type'] == 'new_comment' && !empty($notification['related_id'])): ?>
                            <div class="notification-action">
                                <a href="dataset.php?id=<?php echo $notification['related_id']; ?>#comments">View Comment</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($notification['notification_type'] == 'comment_reply' && !empty($notification['related_id'])): ?>
                            <div class="notification-action">
                                <a href="dataset.php?id=<?php echo $notification['related_id']; ?>#comments">View Reply</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($notification['notification_type'] == 'comment_conversation' && !empty($notification['related_id'])): ?>
                            <div class="notification-action">
                                <a href="dataset.php?id=<?php echo $notification['related_id']; ?>#comments">View Conversation</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($notification['notification_type'] == 'dataset_activity' && !empty($notification['related_id'])): ?>
                            <div class="notification-action">
                                <a href="dataset.php?id=<?php echo $notification['related_id']; ?>#comments">View Activity</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-notifications">
                <i class="fa-solid fa-bell-slash" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                <p>You don't have any notifications yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://kit.fontawesome.com/2c68a433da.js" crossorigin="anonymous"></script>
</body>
</html> 