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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <title>Your Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
            z-index: 1000;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo img {
            height: auto;
            width: 80px;
            max-width: 100%;
            margin-right: 15px;
        }
        .logo h2 {
            color: white;
            margin: 0;
            font-size: 22px;
            white-space: nowrap;
        }
        .nav-links {
            display: flex;
            align-items: center;
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
        
        /* Mobile menu toggle button */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
        }
        
        .mobile-menu-toggle i {
            display: block;
        }

        /* Responsive styles for the navbar */
        @media screen and (max-width: 768px) {
            .navbar {
                padding: 10px;
                border-radius: 15px;
                width: 90%;
                max-width: 90%;
                position: relative;
                z-index: 2;
            }
            
            .mobile-menu-toggle {
                display: block;
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .logo {
                flex-direction: row;
                align-items: center;
                text-align: center;
                max-width: 80%;
            }
            
            .logo img {
                width: 50px;
                margin-right: 12px;
            }
            
            .logo h2 {
                margin: 0;
                font-size: 22px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .nav-links {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                flex-direction: column;
                background-color: #0099ff;
                padding: 10px 0;
                border-radius: 0 0 15px 15px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                display: none;
                z-index: 9999;
            }
            
            .nav-links.active {
                display: flex;
            }
            
            .nav-links a {
                width: 100%;
                text-align: center;
                padding: 10px 0;
                margin: 0;
            }
        }

        @media screen and (max-width: 480px) {
            .navbar {
                padding: 8px 10px;
            }
            
            .logo img {
                width: 45px;
                margin-right: 10px;
            }
            
            .logo h2 {
                font-size: 18px;
                text-align: center;
            }
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
            flex-wrap: wrap;
            gap: 10px;
            width: 100%;
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
            display: block;
            text-align: center;
            white-space: nowrap;
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
            display: inline-block;
            vertical-align: middle;
        }
        .notification {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
            transition: background-color 0.2s;
            flex-wrap: wrap;
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
            flex-shrink: 0;
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
            min-width: 0; /* Prevent text from overflowing container */
        }
        .notification-message {
            margin: 0 0 5px 0;
            line-height: 1.4;
            word-wrap: break-word; /* Allow long words to break */
        }
        .notification-time {
            color: #666;
            font-size: 12px;
        }
        .notification-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .notification-action {
            margin-left: 10px;
        }
        .notification-action a {
            color: #007BFF;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
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
        
        /* Responsive styles for other elements */
        @media screen and (max-width: 768px) {
            .container {
                width: 90%;
                padding: 15px;
                margin: 20px auto;
            }
            
            .actions {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
            }
            
            .actions h1 {
                margin-bottom: 10px;
                width: 100%;
            }
            
            .actions .btn {
                width: 100%;
                margin-bottom: 10px;
                box-sizing: border-box;
            }
            
            h1 {
                font-size: 22px;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
            }
            
            .unread-count {
                margin-left: 0;
            }
            
            .notification {
                padding: 12px 10px;
            }
            
            .notification-icon {
                font-size: 20px;
                margin-right: 10px;
            }
            
            .notification-actions {
                width: 100%;
                justify-content: flex-start;
                margin-top: 10px;
                margin-left: 35px; /* Align with content */
            }
            
            .notification-action {
                margin-left: 0;
            }
        }
        
        @media screen and (max-width: 480px) {
            .container {
                padding: 10px;
            }
            
            h1 {
                font-size: 20px;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
            
            .notification-icon {
                font-size: 18px;
            }
            
            .notification-message {
                font-size: 14px;
            }
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
        <button class="mobile-menu-toggle" id="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <nav class="nav-links" id="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="datasets.php">ALL DATASETS</a>
            <a href="mydatasets.php">MY DATASETS</a>
            <a href="user_settings.php">SETTINGS</a>
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
                                <i class="fas fa-check-circle"></i>
                            <?php elseif ($notification['notification_type'] == 'access_rejected'): ?>
                                <i class="fas fa-times-circle"></i>
                            <?php elseif ($notification['notification_type'] == 'new_comment'): ?>
                                <i class="fas fa-comment"></i>
                            <?php elseif ($notification['notification_type'] == 'comment_reply'): ?>
                                <i class="fas fa-reply"></i>
                            <?php elseif ($notification['notification_type'] == 'comment_conversation'): ?>
                                <i class="fas fa-comments"></i>
                            <?php elseif ($notification['notification_type'] == 'dataset_activity'): ?>
                                <i class="fas fa-bell"></i>
                            <?php else: ?>
                                <i class="fas fa-bell"></i>
                            <?php endif; ?>
                        </div>
                        <div class="notification-content">
                            <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <p class="notification-time"><?php echo date('M j, Y, g:i a', strtotime($notification['created_at'])); ?></p>
                        </div>
                        <div class="notification-actions">
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
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-notifications">
                <i class="fas fa-bell-slash" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                <p>You don't have any notifications yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const navLinks = document.getElementById('nav-links');
            
            mobileMenuToggle.addEventListener('click', function() {
                navLinks.classList.toggle('active');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInsideNavbar = event.target.closest('.navbar');
                if (!isClickInsideNavbar && navLinks.classList.contains('active')) {
                    navLinks.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html> 