<?php
// Database connection (can be reused across pages)
$servername = "localhost";
$username = "root";
$password = "12345";
$dbname = "mangasaydb";

// Create a single connection
if (!isset($conn)) {
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
}
?>
