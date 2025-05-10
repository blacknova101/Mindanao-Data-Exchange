<?php
session_start();
include('db_connection.php'); // Include your DB connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // or include PHPMailer if not using Composer

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Check if email exists in the database
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        // Email found, generate reset code
        $resetCode = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $resetCode;

        // Send the reset code to the user's email
        $mail = new PHPMailer(true);
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';  
            $mail->SMTPAuth = true;
            $mail->Username = 'wazzupbymindex@gmail.com'; 
            $mail->Password = 'zigg xxbk opcb qsob';  // your app-specific password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('wazzupbymindex@gmail.com', 'Mindanao Data Exchange');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code';
            $mail->Body    = 'Your password reset code is: ' . $resetCode;
            $mail->AltBody = 'Your password reset code is: ' . $resetCode;

            // Send email
            $mail->send();
            header("Location: verify-reset-code.php"); // Redirect to verify code page
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
            header("Location: forgot-password.php"); // Redirect back if error
            exit();
        }
    } else {
        $_SESSION['error_message'] = 'No account found with that email.';
        header("Location: forgot-password.php"); // Redirect back if email not found
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
</head>
<body>
    <form action="forgot-password.php" method="POST">
        <h2>Forgot Password</h2>
        <label for="email">Enter your email address:</label>
        <input type="email" id="email" name="email" required>
        <button type="submit">Send Reset Code</button>
    </form>

    <?php
    if (isset($_SESSION['error_message'])) {
        echo '<p style="color:red;">' . $_SESSION['error_message'] . '</p>';
        unset($_SESSION['error_message']);
    }
    ?>
</body>
</html>
