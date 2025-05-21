<?php
session_start();
// If redirect needed based on session status
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - MDX</title>
    <link rel="stylesheet" href="assets/css/accountselection_responsive.css">
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        text-align: center;
        overflow-x: hidden;
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
        width: 95%;
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
    
    .success-box {
        width: 85%;
        max-width: 800px;
        background-color: white;
        border-radius: 10px;
        padding: 40px 20px;
        margin: 20px auto;
        box-sizing: border-box;
    }
    
    .progress {
        display: flex;
        justify-content: space-between;
        width: 100%;
        margin: 0 auto 30px;
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
    
    /* All circles active on the success page */
    #circle1, #circle2, #circle3 {
        background-color: #0099ff;
        color: white;
    }
    
    h1 {
        font-size: 3.5em;
        margin: 30px 0;
        color: #0099ff;
        word-wrap: break-word;
    }
    
    small {
        display: block;
        font-size: 16px;
        color: #555;
        margin-bottom: 20px;
    }
    
    .countdown {
        margin: 20px auto;
        font-size: 36px;
        font-weight: bold;
        color: #0099ff;
    }
    
    .btn-login {
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
        margin-top: 20px;
    }
    
    .btn-login:hover {
        background-color: #007acc;
    }
    
    @media (max-width: 768px) {
        .success-box {
            padding: 30px 15px;
        }
        
        h1 {
            font-size: 2.5em;
            margin: 20px 0;
        }
        
        .countdown {
            font-size: 30px;
        }
    }
    
    @media (max-width: 576px) {
        .success-box {
            padding: 20px 10px;
        }
        
        h1 {
            font-size: 2em;
            margin: 15px 0;
        }
        
        .countdown {
            font-size: 24px;
        }
        
        .circle {
            width: 35px;
            height: 35px;
        }
    }
    </style>
</head>
<body>
    <video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>

    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mindanao Data Exchange Logo">
        </div>

        <nav class="nav-links">
            <a href="mindanaodataexchange.php">Home</a>
        </nav>
    </header>

    <div class="main-container">
        <div class="success-box">
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
        
            <h1>Account Created!</h1>
            <small>You will be redirected to the login page in...</small>
            <div class="countdown" id="countdown">5</div>
            <a href="login.php" class="btn-login">Go to login</a>
        </div>
    </div>
  
    <script>
        // Countdown logic
        let countdown = 5;
        const countdownElement = document.getElementById('countdown');
        const redirectUrl = 'login.php'; // URL to redirect to
        
        const countdownInterval = setInterval(function() {
            countdown--;
            countdownElement.textContent = countdown;
            
            // Redirect when countdown reaches 0
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = redirectUrl;
            }
        }, 1000); // Update every second
    </script>
</body>
</html> 