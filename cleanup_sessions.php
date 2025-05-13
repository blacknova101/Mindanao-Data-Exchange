<?php
include 'db_connection.php';

// Delete sessions older than 30 minutes
$sql = "DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
$conn->query($sql);

// Delete sessions for users that are no longer active
$sql = "DELETE us FROM user_sessions us 
        LEFT JOIN users u ON us.user_id = u.user_id 
        WHERE u.user_id IS NULL OR u.is_active = 0";
$conn->query($sql);
?> 