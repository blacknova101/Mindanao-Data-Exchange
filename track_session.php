<?php
session_start();
include 'db_connection.php';

// Verify database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit();
} else {
    error_log("Database connection successful.");
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $session_id = session_id();
    $last_activity = date('Y-m-d H:i:s');

    // Debug information
    error_log("Tracking session for user: " . $user_id);
    error_log("Session ID: " . $session_id);

    // Check if session exists
    $sql = "SELECT * FROM user_sessions WHERE session_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        exit();
    }
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing session
        $sql = "UPDATE user_sessions SET last_activity = ? WHERE session_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            exit();
        }
        $stmt->bind_param("ss", $last_activity, $session_id);
        error_log("Updating existing session.");
    } else {
        // Create new session
        $sql = "INSERT INTO user_sessions (user_id, session_id, last_activity) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            exit();
        }
        $stmt->bind_param("iss", $user_id, $session_id, $last_activity);
        error_log("Creating new session.");
    }

    if (!$stmt->execute()) {
        error_log("SQL Error: " . $stmt->error);
        echo "SQL Error: " . $stmt->error . "<br>";  // Output to browser for debugging
    } else {
        error_log("Session successfully tracked.");
    }
} else {
    error_log("User ID is not set in the session.");
}

// Clean up old sessions (older than 30 minutes)
$sql = "DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
if (!$conn->query($sql)) {
    error_log("Failed to clean up old sessions: " . $conn->error);
} else {
    error_log("Old sessions cleaned up successfully.");
}