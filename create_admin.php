<?php
session_start();
include 'db_connection.php';
include 'includes/error_handler.php';

// Set your admin details here
$name = "adrian";  // Change this to your desired admin name
$email = "labisoresadrian@gmail.com";  // Change this to your desired admin email
$password = "09953835794Sf!";  // Change this to your desired password

try {
    // Validate inputs
    if (empty($name) || empty($email) || empty($password)) {
        handle_error("Admin name, email, and password are required", ERROR_VALIDATION);
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handle_error("Invalid email format", ERROR_VALIDATION);
    }
    
    // Validate password strength
    if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $password)) {
        handle_error("Password must be at least 8 characters long, contain 1 uppercase letter, 1 number, and 1 special character", ERROR_VALIDATION);
    }
    
    // Check if admin already exists
    $check_sql = "SELECT admin_id FROM administrator WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        handle_db_error("Database error occurred", $conn);
    }
    
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        handle_error("Admin with this email already exists", ERROR_VALIDATION);
    }
    
    // Hash the password using bcrypt
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert into administrator table
    $sql = "INSERT INTO administrator (name, email, password_hash) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        handle_db_error("Database error occurred", $conn);
    }
    
    $stmt->bind_param("sss", $name, $email, $hashed_password);
    
    if ($stmt->execute()) {
        // Log admin creation
        log_error("Admin account created successfully", "auth", ['name' => $name, 'email' => $email]);
        
        echo '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); background-color: #fff; text-align: center;">
            <h2 style="color: #0099ff;">Admin Account Created Successfully</h2>
            <p>Name: ' . htmlspecialchars($name) . '</p>
            <p>Email: ' . htmlspecialchars($email) . '</p>
            <p style="margin-top: 20px;"><a href="admin_login.php" style="background-color: #0099ff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;">Go to Admin Login</a></p>
        </div>';
    } else {
        handle_db_error("Error creating admin account", $conn);
    }
} catch (Exception $e) {
    log_error("Admin creation error", ERROR_GENERAL, [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    handle_error("An unexpected error occurred. Please try again.", ERROR_GENERAL);
}

$stmt->close();
$conn->close();
?>