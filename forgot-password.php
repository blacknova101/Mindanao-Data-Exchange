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
    <style>
    html, body {
        height: auto;
        overflow: hidden;
    }

    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        text-align: center;
    }
    .navbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 5%; /* Adjusted padding for a more compact navbar */
        padding-left: 30px;
        background-color:  #0099ff; /* Transparent background */
        color: #cfd9ff;
        border-radius: 20px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        position: relative;
        margin: 10px 0;
        backdrop-filter: blur(10px);
        max-width: 1200px; /* Limit the maximum width */
        width: 100%; /* Ensure it takes up the full width but doesn't exceed 1200px */
        margin-top:30px;
        margin-left: auto; /* Center align the navbar */
        margin-right: auto; /* Center align the navbar */
    }

    .logo {
        display: flex;
        align-items: center;
        margin-right: 20px;
    }
    .logo img {
        height: auto;
        width: 80px; /* Adjust logo size */
        max-width: 100%;
    }
    .nav-links a {
        color: white;
        margin-left: 20px;
        text-decoration: none;
        font-size: 18px;
        transition: transform 0.3s ease; /* Smooth transition for scaling */
    }
    .nav-links a:hover {
        transform: scale(1.2); /* Scale up the link by 20% */
    }

    #background-video {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: -1; /* stays behind everything */
    }
    .nav-links {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .container {
        max-width: 500px;
        margin: 80px auto;
        background-color: white;
        padding: 40px 30px;
        border-radius: 16px;
        box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
        text-align: left;
    }

    .container h2 {
        color: #0099ff;
        margin-bottom: 20px;
        text-align: center;
    }

    .container label {
        display: block;
        font-weight: bold;
        margin-bottom: 8px;
        color: #333;
    }

    .container input[type="email"] {
        width: 470px;
        padding: 12px;
        margin-bottom: 20px;
        border: 2px solid #0099ff;
        border-radius: 8px;
        font-size: 16px;
        outline: none;
        transition: border-color 0.3s ease;
    }

    .container input[type="email"]:focus {
        border-color: #006dcc;
    }

    .container button {
        background-color: #0099ff;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        width: 100%;
        transition: background-color 0.3s ease;
    }

    .container button:hover {
        background-color: #007acc;
    }

    .container p {
        text-align: center;
        margin-top: 15px;
    }
    </style>
</head>
<body>
    <video autoplay muted loop id="background-video">
                <source src="videos/bg6.mp4" type="video/mp4">
    </video>

    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
        </div>

        <nav class="nav-links">
            <a href="mindanaodataexchange.php">Home</a>
        </nav>
    </header>

    <div class="container">
        <form action="forgot-password.php" method="POST">
            <h2>Forgot Password</h2>
            <label for="email">Enter your email address:</label><br>
            <input type="email" id="email" name="email" required>
            <button type="submit">Send Reset Code</button>
        </form>
        <?php
        if (isset($_SESSION['error_message'])) {
            echo '<p style="color:red;">' . $_SESSION['error_message'] . '</p>';
            unset($_SESSION['error_message']);
        }
        ?>
    </div>
</body>
</html>
