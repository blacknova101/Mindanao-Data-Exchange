<?php
session_start();
include('db_connection.php');  // Include your DB connection file

// Assuming the verification code is stored in session during registration
$correctVerificationCode = $_SESSION['verification_code'];

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $enteredCode = $_POST['verification_code'];

    // Check if the entered verification code matches the one saved in the session
    if ($enteredCode == $correctVerificationCode) {
        // Verification successful, store user data in the database
        if (isset($_SESSION['pending_user'])) {
            $user = $_SESSION['pending_user'];
            $firstName = $user['first_name'];
            $lastName = $user['last_name'];
            $email = $user['email'];
            $password = $user['password'];

            // Insert user data into the database
            $sql = "INSERT INTO users (first_name, last_name, email, password) 
                    VALUES ('$firstName', '$lastName', '$email', '$password')";

            if (mysqli_query($conn, $sql)) {
                // Clear the session after successful insertion
                unset($_SESSION['pending_user']);
                $_SESSION['verified'] = true;

                // Redirect to account creation page
                header("Location: registration_success.html");
                exit();
            } else {
                $_SESSION['message'] = "Error storing user data in the database.";
                $_SESSION['verified'] = false;
                header("Location: verifyemailaddressnoorg.php");
                exit();
            }
        }
    } else {
        // Verification failed, set error message and redirect back to the verification page
        $_SESSION['message'] = "Incorrect verification code. Please try again.";
        $_SESSION['verified'] = false;
        header("Location: verifyemailaddressnoorg.php");
        exit();
    }
}
