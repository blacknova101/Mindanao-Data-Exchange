<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['first_name'] = $_POST['firstname'];
    $_SESSION['last_name'] = $_POST['lastname'];
    $_SESSION['email_address'] = $_POST['email_address'];
    $_SESSION['re_enter_email'] = $_POST['re_enter_email'];
    $_SESSION['password'] = $_POST['password'];
    $_SESSION['re_enter_pass'] = $_POST['re_enter_pass'];

    $emailMatch = $_SESSION['email_address'] === $_SESSION['re_enter_email'];
    $passwordMatch = $_SESSION['password'] === $_SESSION['re_enter_pass'];

    if ($emailMatch && $passwordMatch) {
        header("Location: verifyemailaddressnoorg.php");
        exit();
    } elseif ($emailMatch && !$passwordMatch) {
        echo "Password does not match.";
    } elseif (!$emailMatch && $passwordMatch) {
        echo "Email does not match.";
    } else {
        echo "Email and Password do not match.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>
</head>
<body>
    <form action="registrationdetailsnoorg.php" method="POST">
    First Name: <input type="text" name="firstname" id="firstname" required><br>
    Last Name:  <input type="text" name="lastname" id="lastname" required><br>
    Email Address:     <input type="text" name="email_address" id="email_address" required><br>
    Re-enter Email Address:     <input type="text" name="re_enter_email" id="re_enter_email" required><br>
    Password:   <input type="password" name="password" id="password" required><br>
    Re-enter Password:   <input type="password" name="re_enter_pass" id="re_enter_pass" required><br>
    <input type="submit" value="Submit">
    </form>
</body>
</html>
