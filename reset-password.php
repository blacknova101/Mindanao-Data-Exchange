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
</head>
<body>
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
</body>
</html>
