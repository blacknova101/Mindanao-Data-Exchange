<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['verification_code']) || !isset($_SESSION['pending_change'])) {
    header("Location: user_settings.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enteredCode = $_POST['verification_code'];
    $correctCode = $_SESSION['verification_code'];
    $pendingChange = $_SESSION['pending_change'];

    if ($enteredCode == $correctCode) {
        $userId = $_SESSION['user_id'];
        
        if ($pendingChange['type'] == 'email') {
            // Update email
            $newEmail = $pendingChange['value'];
            $sql = "UPDATE users SET email = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $newEmail, $userId);
            
            if ($stmt->execute()) {
                $_SESSION['email'] = $newEmail;
                $_SESSION['success_message'] = "Email updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update email. Please try again.";
            }
        } else if ($pendingChange['type'] == 'password') {
            // Update password
            $newPassword = hash('sha256', $pendingChange['value']);
            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $newPassword, $userId);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Password updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update password. Please try again.";
            }
        }

        // Clear verification data
        unset($_SESSION['verification_code']);
        unset($_SESSION['pending_change']);
        
        header("Location: user_settings.php");
        exit();
    } else {
        $error = "Invalid verification code. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Changes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5%;
            padding-left: 30px;
            background-color: #0099ff;
            color: #cfd9ff;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            margin: 10px 0;
            backdrop-filter: blur(10px);
            max-width: 1200px;
            width: 100%;
            margin-top: 30px;
            margin-left: auto;
            margin-right: auto;
            font-weight: bold;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo img {
            height: auto;
            width: 80px;
            max-width: 100%;
        }
        .search-bar {
            flex-grow: 1;
            display: flex;
            align-items: center;
            position: relative;
            margin-left: -190px;
        }
        .search-bar input {
            padding: 8px;
            width: 400px;
            border-radius: 5px;
            border: none;
        }
        .search-bar button {
            background: none;
            border: none;
            cursor: pointer;
        }
        .search-bar img {
            width: 20px;
            height: 20px;
            margin-left: -50px;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-size: 18px;
        }
        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 70px;
        }
        .profile-icon img {
            width: 150%;
            height: auto;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
        }
        .container {
            width: 30%;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
        }
        .verification-form {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }
        .verification-form h2 {
            color: #0099ff;
            margin-bottom: 20px;
        }
        .verification-form input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .verification-form button {
            background-color: #0099ff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
            margin-top: 10px;
        }
        .verification-form button:hover {
            background-color: #007acc;
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        #background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
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
        <form id="searchForm" action="search_results.php" method="GET">
            <div class="search-bar">
                <input type="text" name="search" placeholder="Search datasets" onfocus="showDropdown()" onblur="hideDropdown()">
                <button>
                    <img src="images/search_icon.png" alt="Search">
                </button>
            </div>
        </form>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="datasets.php">DATASETS</a>
            <a onclick="showModal()" style="cursor: pointer;">CATEGORY</a>
            <div class="profile-icon">
                <img src="images/avatarIconunknown.jpg" alt="Profile">
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="verification-form">
            <h2>Verify Changes</h2>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            <form action="verify_changes_code.php" method="POST">
                <input type="text" name="verification_code" placeholder="Enter verification code" required>
                <button type="submit">Verify Changes</button>
            </form>
        </div>
    </div>

    <?php include('category_modal.php'); ?>
    <script>
        function showModal() {
            document.getElementById("categoryModal").style.display = "flex";
        }
        function hideModal() {
            document.getElementById("categoryModal").style.display = "none";
        }
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("categoryModal").style.display = "none";
        });
    </script>
</body>
</html> 