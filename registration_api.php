<?php
session_start();
include('db_connection.php'); // Include your DB connection
include('includes/error_handler.php'); // Include error handler
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    try {
        $firstName = $_POST['firstname'];
        $lastName = $_POST['lastname'];
        $email = $_POST['email_address'];
        $reEnterEmail = $_POST['re_enter_email'];
        $password = $_POST['password'];
        $reEnterPassword = $_POST['re_enter_pass'];
        
        // Validate required fields
        if (
            empty($firstName) || empty($lastName) || empty($email) || empty($reEnterEmail)
            || empty($password) || empty($reEnterPassword)
        ) {
            handle_validation_error('Please fill in all required fields.', 'all_fields', 'registrationdetails.php');
        }
    
        // Validate email format
        if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $email)) {
            $_SESSION['form_data'] = [
                'firstname' => $firstName,
                'lastname' => $lastName,
                'password' => $password,
                're_enter_pass' => $reEnterPassword
            ];
            handle_validation_error('Invalid email format.', 'email', 'registrationdetails.php');
        }
        
        // Validate name format
        if (!preg_match("/^[a-zA-Z\s'-]+$/", $firstName) || !preg_match("/^[a-zA-Z\s'-]+$/", $lastName)) {
            $_SESSION['form_data'] = [
                'email_address' => $email,
                're_enter_email' => $reEnterEmail,
                'password' => $password,
                're_enter_pass' => $reEnterPassword
            ];
            handle_validation_error('Names can only contain letters, spaces, hyphens, and apostrophes.', 'name', 'registrationdetails.php');
        }
        
        // Validate password strength
        if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $password)) {
            $_SESSION['form_data'] = [
                'firstname' => $firstName,
                'lastname' => $lastName,
                'email_address' => $email,
                're_enter_email' => $reEnterEmail
            ];
            handle_validation_error('Password must be at least 8 characters long, contain 1 uppercase letter, 1 number, and 1 special character.', 'password', 'registrationdetails.php');
        }
        
        // Check if email and password match
        $emailMatch = ($email === $reEnterEmail);
        $passwordMatch = ($password === $reEnterPassword);
    
        if (!$emailMatch || !$passwordMatch) {
            $_SESSION['form_data'] = [
                'firstname' => $firstName,
                'lastname' => $lastName
            ];
            
            if (!$emailMatch) {
                handle_validation_error('Email addresses do not match.', 'email_match', 'registrationdetails.php');
            } else {
                handle_validation_error('Passwords do not match.', 'password_match', 'registrationdetails.php');
            }
        }
    
        // Check if the email already exists in the database
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            handle_db_error('Database error occurred.', $conn, 'registrationdetails.php');
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            // Email already exists, show an error message
            $_SESSION['form_data'] = [
                'firstname' => $firstName,
                'lastname' => $lastName
            ];
            handle_validation_error('Email is already registered.', 'email_exists', 'registrationdetails.php');
        }
        
        // Hash the password using bcrypt
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
        // Store the user data temporarily
        $_SESSION['pending_user'] = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $hashedPassword
        ];
    
        // Generate verification code
        $verificationCode = rand(100000, 999999);
        $_SESSION['verification_code'] = $verificationCode;
    
        // Send the verification email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();  // Set mailer to use SMTP
            $mail->Host = 'smtp.gmail.com';  
            $mail->SMTPAuth = true;
            $mail->Username = 'wazzupbymindex@gmail.com'; 
            $mail->Password = 'zigg xxbk opcb qsob';  
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
            $mail->Port = 587;  
    
            // Recipients
            $mail->setFrom('wazzupbymindex@gmail.com', 'Mindanao Data Exchange');
            $mail->addAddress($email, $firstName);
    
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Verification Code';
            $mail->Body    = 'Your verification code is: ' . $verificationCode;
            $mail->AltBody = 'Your verification code is: ' . $verificationCode;
    
            $mail->send();
    
            // Redirect to verification page
            header("Location: verifyemailaddress.php");
            exit();
    
        } catch (Exception $e) {
            log_error("Email sending failed", ERROR_GENERAL, [
                'error' => $mail->ErrorInfo,
                'email' => $email
            ]);
            handle_error("Failed to send verification email. Please try again.", ERROR_GENERAL, "registrationdetails.php");
        }
    } catch (Exception $e) {
        log_error("Registration error", ERROR_GENERAL, [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        handle_error("An unexpected error occurred. Please try again.", ERROR_GENERAL, "registrationdetails.php");
    }
}
?>
