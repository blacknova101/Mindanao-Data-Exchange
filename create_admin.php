<?php
include 'db_connection.php';

// Set your admin details here
$name = "adrian";  // Change this to your desired admin name
$email = "labisoresadrian@gmail.com";  // Change this to your desired admin email
$password = "09953835794Sf!";  // Change this to your desired password

// Hash the password using SHA256
$hashed_password = hash('sha256', $password);

// Insert into administrator table
$sql = "INSERT INTO administrator (name, email, password_hash) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $name, $email, $hashed_password);

if ($stmt->execute()) {
    echo "Admin account created successfully!";
} else {
    echo "Error creating admin account: " . $conn->error;
}

$stmt->close();
$conn->close();
?>