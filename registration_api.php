<?php
session_start();
include('db_connection.php'); // Include your DB connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $firstName = $_POST['firstname'];
    $lastName = $_POST['lastname'];
    $email = $_POST['email_address'];
    $reEnterEmail = $_POST['re_enter_email'];
    $password = $_POST['password'];
    $reEnterPassword = $_POST['re_enter_pass'];

    if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $email)) {
        $_SESSION['error_message'] = 'Error: Invalid email format.';
        header("Location: registrationdetailsnoorg.php");
        exit();
    }
    if (!preg_match("/^[a-zA-Z\s'-]+$/", $firstName) || !preg_match("/^[a-zA-Z\s'-]+$/", $lastName)) {
        $_SESSION['error_message'] = 'Error: Names can only contain letters, spaces, hyphens, and apostrophes.';
        header("Location: registrationdetailsnoorg.php");
        exit();
    }
    if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $password)) {
        $_SESSION['error_message'] = 'Error: Password must be at least 8 characters long, contain 1 uppercase letter, 1 number, and 1 special character.';
        header("Location: registrationdetailsnoorg.php");
        exit();
    }
    // Check if email and password match
    $emailMatch = ($email === $reEnterEmail);
    $passwordMatch = ($password === $reEnterPassword);

    if ($emailMatch && $passwordMatch) {
        // Check if the email already exists in the database
        $query = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            // Email already exists, show an error message
            $_SESSION['error_message'] = 'Error: Email is already registered.';
            header("Location: registrationdetailsnoorg.php"); // use your real form page filename
            exit();
        } else {
            // Hash the password for security
            $hashedPassword = hash('sha256', $password);

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
                header("Location: verifyemailaddressnoorg.php");
                exit();

            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        }
    } else {
        echo $_SESSION['error_message'] = 'Error: Email or Password does not match.';
        header("Location: registrationdetailsnoorg.php");
        exit();        
    }
}
?>
