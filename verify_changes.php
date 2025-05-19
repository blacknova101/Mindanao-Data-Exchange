<?php
session_start();
include('db_connection.php');
include('includes/error_handler.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    handle_error("You must be logged in to make changes to your account.", ERROR_AUTH, "login.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $userId = $_SESSION['user_id'];
        $changeType = $_POST['change_type']; // 'email' or 'password'
        $newValue = $_POST['new_value'];
        $currentPassword = $_POST['current_password'] ?? null;
        $fromSettings = isset($_POST['from_settings']) ? $_POST['from_settings'] : false;
        
        // Debug log to trace the flow
        log_error("Processing change request", "auth", [
            'type' => $changeType, 
            'user_id' => $userId, 
            'from_settings' => $fromSettings ? 'true' : 'false'
        ]);
    
        // Verify current password first
        if ($currentPassword) {
            $sql = "SELECT password FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                handle_db_error("Database error occurred", $conn, "user_settings.php");
            }
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
    
            if (!password_verify($currentPassword, $user['password'])) {
                handle_validation_error("Current password is incorrect.", "current_password", "user_settings.php");
            }
        }
    
        // For email changes, verify the new email isn't already in use
        if ($changeType == 'email') {
            $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                handle_db_error("Database error occurred", $conn, "user_settings.php");
            }
            
            $stmt->bind_param("si", $newValue, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                handle_validation_error("This email is already registered to another account.", "email", "user_settings.php");
            }
    
            // Validate email format
            if (!filter_var($newValue, FILTER_VALIDATE_EMAIL)) {
                handle_validation_error("Please enter a valid email address.", "email", "user_settings.php");
            }
        }
    
        // For password changes, validate password strength
        if ($changeType == 'password') {
            if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $newValue)) {
                handle_validation_error("Password must be at least 8 characters long, contain 1 uppercase letter, 1 number, and 1 special character.", "password", "user_settings.php");
            }
    
            // Check if new password is same as current password
            $sql = "SELECT password FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                handle_db_error("Database error occurred", $conn, "user_settings.php");
            }
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($newValue, $user['password'])) {
                handle_validation_error("New password cannot be the same as your current password.", "password", "user_settings.php");
            }
        }
    
        // Generate verification code
        $verificationCode = rand(100000, 999999);
        $_SESSION['verification_code'] = $verificationCode;
        $_SESSION['pending_change'] = [
            'type' => $changeType,
            'value' => $newValue
        ];
    
        // Get user's email
        $sql = "SELECT email FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            handle_db_error("Database error occurred", $conn, "user_settings.php");
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $email = $user['email'];
    
        // Send verification email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'wazzupbymindex@gmail.com';
            $mail->Password = 'zigg xxbk opcb qsob';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
    
            $mail->setFrom('wazzupbymindex@gmail.com', 'Mindanao Data Exchange');
            $mail->addAddress($email);
    
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Changes';
            
            if ($changeType == 'email') {
                $mail->Body = "You are changing your email address to: $newValue<br><br>
                              Your verification code is: $verificationCode<br><br>
                              Please enter this code to confirm your email change.";
                $mail->AltBody = "You are changing your email address to: $newValue\n\n
                                 Your verification code is: $verificationCode\n\n
                                 Please enter this code to confirm your email change.";
            } else {
                $mail->Body = "Your verification code is: $verificationCode<br><br>
                              Please enter this code to confirm your password change.";
                $mail->AltBody = "Your verification code is: $verificationCode\n\n
                                 Please enter this code to confirm your password change.";
            }
    
            $mail->send();
            
            // Log successful email sending
            log_error("Verification email sent", "auth", [
                'type' => $changeType, 
                'user_id' => $userId, 
                'email' => $email
            ]);
            
            header("Location: verify_changes_code.php");
            exit();
        } catch (Exception $e) {
            log_error("Failed to send verification email", ERROR_GENERAL, [
                'error' => $mail->ErrorInfo,
                'email' => $email,
                'type' => $changeType
            ]);
            handle_error("Failed to send verification email. Please try again.", ERROR_GENERAL, "user_settings.php");
        }
    } catch (Exception $e) {
        log_error("Error processing change request", ERROR_GENERAL, [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        handle_error("An unexpected error occurred. Please try again.", ERROR_GENERAL, "user_settings.php");
    }
}
?> 