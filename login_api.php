<?php
session_start(); // Start the session to track the user's login state
include('db_connection.php');

// 1. Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the data from the form
    $email = $_POST['email'];
    $pass = $_POST['password'];

    // Hash the input password using SHA-256
    $hashedPassword = hash('sha256', $pass);

    // 2. Query to check if the user exists in the database
    $sql = "SELECT * FROM users WHERE email = ?"; // Using email instead of username
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email); // 's' means string type
    $stmt->execute();
    $result = $stmt->get_result();

    // 3. If the user exists, verify the password
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($hashedPassword === $row['password']) { // Compare hashed passwords
            // Successful login
            $_SESSION['email'] = $email; // Store email in session
            $_SESSION['user_id'] = $row['user_id']; // Store user ID in session
            $_SESSION['first_name'] = $row['first_name']; // Store user's name in session
            $_SESSION['last_name'] = $row['last_name']; // Store user's name in session
            $_SESSION['organization_id'] = $row['organization_id']; // Store organization ID in session

            $updateSql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("i", $row['user_id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Check if the user has an organization
            if (empty($row['organization_id'])) {
                $_SESSION['has_organization'] = false;
            } else {
                $_SESSION['has_organization'] = true;
            }

            header("Location: HomeLogin.php"); // Redirect to home page after login
            exit();
        } else {
            // Invalid password
            header("Location: login.php?error=Invalid password");
            exit();
        }
    } else {
        // No user found
        header("Location: login.php?error=No user found with that email");
        exit();
    }

    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page if not authenticated
        header("Location: login.php");
        exit();
    }

    // Close connection
    $stmt->close();
    $conn->close();
}
?>
