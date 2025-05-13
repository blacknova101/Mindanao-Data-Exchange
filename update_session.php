<?php
session_start();
include 'db_connection.php';

// Only update session if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once 'track_session.php';
    updateUserSession($_SESSION['user_id']);
}
?> 