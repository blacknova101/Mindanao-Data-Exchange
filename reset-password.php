<?php
session_start();
include('db_connection.php'); // Include your DB connection

// Check if the user is trying to reset the password
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = $_POST['new_password'];
    $reNewPassword = $_POST['re_new_password'];

    // Check if both passwords match
    if ($newPassword != $reNewPassword) {
        $_SESSION['error_message'] = 'Error: Passwords do not match.';
        header("Location: reset-password.php");
        exit();
    }

    // Check if new password meets the complexity requirements
    if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $newPassword)) {
        $_SESSION['error_message'] = 'Error: Password must be at least 8 characters long, contain 1 uppercase letter, 1 number, and 1 special character.';
        header("Location: reset-password.php");
        exit();
    }

    // Check if new password is the same as the previous password
    $email = $_SESSION['reset_email'];
    $query = "SELECT password FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
    $previousPassword = $user['password']; // Previous password in sha256 format

    // Hash the new password to compare with the previous one (sha256)
    $hashedNewPassword = hash('sha256', $newPassword);

    if ($hashedNewPassword === $previousPassword) {
        $_SESSION['error_message'] = 'Error: New password cannot be the same as the previous password.';
        header("Location: reset-password.php");
        exit();
    }

    // If all checks pass, update the password
    $hashedNewPassword = hash('sha256', $newPassword); // Hash the new password before saving it
    $updateQuery = "UPDATE users SET password = '$hashedNewPassword' WHERE email = '$email'";

    if (mysqli_query($conn, $updateQuery)) {
        // Password updated successfully
        unset($_SESSION['reset_code'], $_SESSION['reset_email']);
        header("Location: login.php"); // Redirect to login page
        exit();
    } else {
        $_SESSION['error_message'] = 'Error updating password. Please try again.';
        header("Location: reset-password.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
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

    .container input[type="password"] {
        width: 470px;
        padding: 12px;
        margin-bottom: 20px;
        border: 2px solid #0099ff;
        border-radius: 8px;
        font-size: 16px;
        outline: none;
        transition: border-color 0.3s ease;
    }

    .container input[type="password"]:focus {
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
        color: red;
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
        <form action="reset-password.php" method="POST">
            <h2>Set New Password</h2>
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>
            <label for="re_new_password">Re-enter New Password:</label>
            <input type="password" id="re_new_password" name="re_new_password" required>
            <button type="submit">Reset Password</button>
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
