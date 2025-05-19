<?php
session_start();
include 'db_connection.php';
include 'includes/error_handler.php';

// Check if admin is already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // Validate inputs
        if (empty($email)) {
            handle_validation_error("Email address is required", "email", "admin_login.php");
        }
        
        if (empty($password)) {
            handle_validation_error("Password is required", "password", "admin_login.php");
        }
        
        // Prepare SQL statement to prevent SQL injection
        $sql = "SELECT * FROM administrator WHERE email = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            handle_db_error("Database error occurred", $conn, "admin_login.php");
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // Verify password using bcrypt
            if (password_verify($password, $admin['password_hash'])) {
                // Set session variables
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_name'] = $admin['name'];
                
                // Update last login time
                $update_sql = "UPDATE administrator SET last_login = NOW() WHERE admin_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                
                if (!$update_stmt) {
                    log_error("Failed to prepare last login update query", ERROR_DATABASE, ['error' => $conn->error]);
                    // Continue despite error - last login update is not critical
                } else {
                    $update_stmt->bind_param("i", $admin['admin_id']);
                    $update_stmt->execute();
                }
                
                // Log successful login
                log_error("Admin logged in successfully", "auth", ['admin_id' => $admin['admin_id'], 'email' => $email]);
                
                // Redirect to admin dashboard
                header("Location: admin_dashboard.php");
                exit();
            } else {
                handle_error("Invalid email or password", ERROR_AUTH, "admin_login.php");
            }
        } else {
            handle_error("Invalid email or password", ERROR_AUTH, "admin_login.php");
        }
    } catch (Exception $e) {
        log_error("Admin login error", ERROR_GENERAL, [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        handle_error("An unexpected error occurred. Please try again.", ERROR_GENERAL, "admin_login.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/error_styles.css">
    <style>
        body {
            background: url('images/Mindanao.png');
            background-size: cover;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-container h2 {
            color: #0c1a36;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-control {
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 20px;
        }
        .btn-login {
            background: #0099ff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            width: 100%;
            font-weight: bold;
        }
        .btn-login:hover {
            background: #007acc;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php echo display_error_message(); ?>
        <form method="POST" action="">
            <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="Email" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-login">Login</button>
        </form>
    </div>
</body>
</html> 