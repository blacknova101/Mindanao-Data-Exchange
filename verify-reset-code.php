<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enteredCode = $_POST['reset_code'];

    if ($enteredCode == $_SESSION['reset_code']) {
        header("Location: reset-password.php"); // Redirect to reset password page
        exit();
    } else {
        $_SESSION['error_message'] = 'Incorrect verification code.';
        header("Location: verify-reset-code.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Reset Code</title>
</head>
<body>
    <form action="verify-reset-code.php" method="POST">
        <h2>Enter the Reset Code</h2>
        <label for="reset_code">Reset Code:</label>
        <input type="text" id="reset_code" name="reset_code" required>
        <button type="submit">Verify</button>
    </form>

    <?php
    if (isset($_SESSION['error_message'])) {
        echo '<p style="color:red;">' . $_SESSION['error_message'] . '</p>';
        unset($_SESSION['error_message']);
    }
    ?>
</body>
</html>
