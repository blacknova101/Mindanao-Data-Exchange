<?php
    session_start();

    if(
        $_SERVER['REQUEST_METHOD'] == "POST")
        {
        $_SESSION['first_name'] = $_POST['firstname'];
        $_SESSION['last_name'] = $_POST['lastname'];
        $_SESSION['email address'] = $_POST['email address'];
        $_SESSION['re_enter_email'] = $_POST['re_enter_email'];
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['re_enter_pass'] = $_POST['re_enter_pass'];

        header("Location: verifyemailaddresswithorg.php");
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
    <form action="registrationdetailswithorg.php" method="POST">
    First Name: <input type="text" name="firstname" id="firstname" required><br>
    Last Name:  <input type="text" name="lastname" id="lastname" required><br>
    Email Address:     <input type="text" name="email address" id="email address" required><br>
    Re-enter Email Address:     <input type="text" name="re_enter_email" id="re_enter_email" required><br>
    username:   <input type="text" name="username" id="username" required><br>
    Re-enter Password:   <input type="text" name="re_enter_pass" id="re_enter_pass" required><br>
    <input type="submit" value="Submit">
    </form>
</body>
</html>
