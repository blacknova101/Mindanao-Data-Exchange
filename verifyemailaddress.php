<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email</title>
    <link rel="stylesheet" href="assets/css/verifyemail_responsive.css">
    <style>
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

    #background-video {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: -1;
    }
    .nav-links {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .container {
      max-width: 800px;
      width: 85%;
      margin: 20px auto;
      background-color: white;
      padding: 30px;
      text-align: center;
      border-radius: 12px;
      box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
      box-sizing: border-box;
    }
    
    .content {
      width: 100%;
    }
    
    h2 {
        font-size: 2.5em;
        color: #0099ff;
        margin-bottom: 20px;
    }

    p {
        font-size: 16px;
        color: #555;
        margin-bottom: 20px;
    }

    .verification-form {
        margin-top: 30px;
        background-color: #f8f8f8;
        padding: 20px;
        border-radius: 6px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        width: 100%;
        box-sizing: border-box;
    }

    .verification-form label {
        display: block;
        font-size: 18px;
        color: #333;
        margin-bottom: 10px;
    }

    .verification-form input[type="text"] {
        width: 100%;
        padding: 12px;
        font-size: 16px;
        border-radius: 4px;
        border: 1px solid #ccc;
        margin-bottom: 20px;
        box-sizing: border-box;
    }

    .verification-form button[type="submit"] {
        background-color: #0099ff;
        color: white;
        border: none;
        padding: 12px 30px;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
        width: 100%;
        transition: background-color 0.3s ease;
    }

    .verification-form button[type="submit"]:hover {
        background-color: #007acc;
    }

    .message {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        width: 100%;
        box-sizing: border-box;
    }

    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .progress {
        display: flex;
        justify-content: space-between;
        width: 100%;
        margin: 20px auto;
        height: auto;
    }

    .step {
        text-align: center;
    }
    
    .circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin: 0 auto 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: black;
        color: white;
        font-weight: bold;
    }

    /* Specific styling for Circle 1 and 2 */
    #circle1, #circle2 {
        background-color: #0099ff;
        color: white;
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
    <div class="progress">
        <div class="step active">
            <div id="circle1" class="circle">1</div>
            <div>Personal details</div>
        </div>
        <div class="step active">
            <div id="circle2" class="circle">2</div>
            <div>Verify email</div>
        </div>
        <div class="step active">
            <div id="circle3" class="circle">3</div>
            <div>Account created</div>
        </div>
    </div>
        
    <div class="content">
        <h2>VERIFY YOUR EMAIL ADDRESS</h2>
        <p>We have sent an email to <strong><?php echo $_SESSION['pending_user']['email']; ?></strong> so that you can verify your email address. If you don't see the email, please check your spam or junk folder.</p>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo ($_SESSION['verified']) ? 'success' : 'error'; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="verification-form">
            <form action="verify_code.php" method="POST">
                <label for="verification_code">Enter the verification code sent to your email:</label>
                <input type="text" id="verification_code" name="verification_code" required>
                <button type="submit">Verify</button>
            </form>
        </div>  
    </div>
</div>
</body>
</html>
