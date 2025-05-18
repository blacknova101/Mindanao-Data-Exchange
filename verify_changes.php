<?php
session_start();
include('db_connection.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['user_id'];
    $changeType = $_POST['change_type']; // 'email' or 'password'
    $newValue = $_POST['new_value'];
    $currentPassword = $_POST['current_password'] ?? null;
    $fromSettings = isset($_POST['from_settings']) ? $_POST['from_settings'] : false;
    
    // Debug log to trace the flow
    error_log("Processing change request - Type: $changeType, User: $userId, From Settings: " . ($fromSettings ? 'true' : 'false'));

    // Verify current password first
    if ($currentPassword) {
        $hashedPassword = hash('sha256', $currentPassword);
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user['password'] !== $hashedPassword) {
            $_SESSION['error_message'] = "Current password is incorrect.";
            $_SESSION['error_type'] = 'password';
            header("Location: user_settings.php");
            exit();
        }
    }

    // For email changes, verify the new email isn't already in use
    if ($changeType == 'email') {
        $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $newValue, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = "This email is already registered to another account.";
            $_SESSION['error_type'] = 'email';
            header("Location: user_settings.php");
            exit();
        }

        // Validate email format
        if (!filter_var($newValue, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_message'] = "Please enter a valid email address.";
            $_SESSION['error_type'] = 'email';
            header("Location: user_settings.php");
            exit();
        }
    }

    // For password changes, validate password strength
    if ($changeType == 'password') {
        if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $newValue)) {
            $_SESSION['error_message'] = "Password must be at least 8 characters long, contain 1 uppercase letter, 1 number, and 1 special character.";
            $_SESSION['error_type'] = 'password';
            header("Location: user_settings.php");
            exit();
        }

        // Check if new password is same as current password
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (hash('sha256', $newValue) === $user['password']) {
            $_SESSION['error_message'] = "New password cannot be the same as your current password.";
            $_SESSION['error_type'] = 'password';
            header("Location: user_settings.php");
            exit();
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
        header("Location: verify_changes_code.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Failed to send verification email. Please try again.";
        $_SESSION['error_type'] = $changeType;
        header("Location: user_settings.php");
        exit();
    }
}
?> 