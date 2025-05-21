<?php
session_start();
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']); // Sanitize the error message
}
if (isset($_SESSION['user_id'])) {
    // Redirect to login page if already logged in
    header("Location: homelogin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Selection - MDX</title>
    <link rel="stylesheet" href="assets/css/accountselection_responsive.css">
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        text-align: center;
        overflow-x: hidden;
        background-image: url('images/bg6.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
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
        margin: 10px auto;
        backdrop-filter: blur(10px);
        max-width: 1200px;
        width: 100%;
        margin-top: 30px;
        box-sizing: border-box;
    }
    
    .logo {
        display: flex;
        align-items: center;
        margin-right: 20px;
    }
    
    .logo img {
        height: auto;
        width: 80px;
        max-width: 100%;
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
        transition: transform 0.3s ease;
    }
    
    .nav-links a:hover {
        transform: scale(1.2);
    }
    
    .main-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    
    .account-selection-box {
        width: 85%;
        max-width: 1100px;
        background-color: white;
        border-radius: 10px;
        padding: 40px;
        margin: 20px auto;
        box-sizing: border-box;
    }
    
    .account-options {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 35px;
    }
    
    .account-options th {
        padding: 20px;
        text-align: center;
        font-weight: normal;
    }
    
    .account-options td {
        padding: 22px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #000;
    }
    
    .account-options tr:last-child td {
        border-bottom: none;
    }
    
    .account-options td:first-child {
        width: 50%;
        text-align: left;
    }
    
    .account-options td:not(:first-child) {
        width: 25%;
        text-align: center;
    }
    
    .feature-name {
        color: #0099ff;
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 8px;
    }
    
    .feature-desc {
        font-size: 14px;
        line-height: 1.4;
        margin: 0;
    }
    
    .account-type {
        font-size: 18px;
        color: #0099ff;
        margin-top: 5px;
    }
    
    .account-icon {
        width: 40px;
        height: auto;
    }
    
    .checkmark {
        width: 30px;
        height: 30px;
    }
    
    .action-buttons {
        text-align: center;
        margin-top: 20px;
    }
    
    .btn-next {
        background-color: #0099ff;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 10px 0;
        width: 180px;
        font-size: 24px;
        cursor: pointer;
        margin-bottom: 12px;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.3s ease;
    }
    
    .btn-next:hover {
        background-color: #007acc;
    }
    
    .btn-cancel {
        background-color: #0099ff;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 10px 0;
        width: 180px;
        font-size: 20px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.3s ease;
    }
    
    .btn-cancel:hover {
        background-color: #007acc;
    }
    
    .login-link {
        margin-top: 20px;
        font-size: 15px;
    }
    
    .login-link a {
        color: #0099ff;
        text-decoration: none;
        transition: text-decoration 0.3s ease;
    }
    
    .login-link a:hover {
        text-decoration: underline;
    }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
        </div>
        <nav class="nav-links">
            <a href="mindanaodataexchange.php">Home</a>
        </nav>
    </header>

    <div class="main-container">
        <div class="account-selection-box">
            <table class="account-options">
                <thead>
                    <tr>
                        <th></th>
                        <th>
                            <img src="images/user_icon.png" alt="Normal User" class="account-icon">
                            <div class="account-type">Normal MDX Account</div>
                        </th>
                        <th>
                            <img src="images/user_icon.png" alt="Organization" class="account-icon">
                            <div class="account-type">With Organization</div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="feature-name">SEARCH AND DOWNLOAD DATA</div>
                            <p class="feature-desc">Explore a vast collection of datasets from various sources. Filter, search, and download the data you need for analysis and research.</p>
                        </td>
                        <td>
                            <img src="images/check.png" alt="Available" class="checkmark">
                        </td>
                        <td>
                            <img src="images/check.png" alt="Available" class="checkmark">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="feature-name">CONTACT THE CONTRIBUTOR</div>
                            <p class="feature-desc">Connect with dataset contributors for inquiries, collaboration, or further details about their shared data.</p>
                        </td>
                        <td>
                            <img src="images/check.png" alt="Available" class="checkmark">
                        </td>
                        <td>
                            <img src="images/check.png" alt="Available" class="checkmark">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="feature-name">ADD DATA</div>
                            <p class="feature-desc">Contribute datasets to the platform, making them available for others to discover and use. Share valuable insights, research, or real-world data.</p>
                        </td>
                        <td>
                            <!-- No checkmark for normal account -->
                        </td>
                        <td>
                            <img src="images/check.png" alt="Available" class="checkmark">
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="action-buttons">
                <a href="registrationdetails.php" class="btn-next">Next</a><br>
                <a href="MindanaoDataExchange.php" class="btn-cancel">Cancel</a>
                <p class="login-link">Already have an account? <a href="login.php">Log in</a></p>
            </div>
        </div>
    </div>
</body>
</html>
