<?php
session_start();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Email</title>
    <meta charset="UTF-8">
    <style>
        
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #e6f0fa;
        }
        .header {
            background-color: #0c2239;
            padding: 15px 25.65px;
        }
        .header img {
            height: 95px;
            width: 100px;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: white;
            padding: 40px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }
        .progress { 
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            text-align: center;
            flex: 1;
            color: #666;
        }
        .step .circle {
            width: 30px;
            height: 30px;
            line-height: 30px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background-color: #0c2239;
            color: white;
        }
        .step.active .circle {
            background-color: white;
            color: #0c2239;
            border: 2px solid #0c2239;
        }
        .step.active {
            font-weight: bold;
            color: #0c2239;
        }
        h1 {
            font-size: 2.5em;
            margin-bottom: 40px;
            color: #333;
        }
    .content {
        background-color: white;
        max-width: 600px;
        margin: 40px auto;
        padding: 40px;
        text-align: center;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .btn {
        background-color: #0d1b2a;
        color: white;
        border: none;
        padding: 12px 30px;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
    }
    h2 {
        margin-bottom: 20px;
    }
    </style>
</head>
<body>
<div class="header">
    <img src="mdx_logo.png" alt="Logo">
</div>
<div class="container">
        <div class="progress">
            <div class="step">
                <div class="circle">✓</div>
                <div>Personal details</div>
            </div>
            <div class="step active">
                <div class="circle">2</div>
                <div>Verify email</div>
            </div>
            <div class="step active">
                <div class="circle">3</div>
                <div>Account created</div>
            </div> 
        </div>
        <div class="content">
            <h2>VERIFY YOUR EMAIL ADDRESS</h2>
            <p>We have sent an email to <strong><?php echo $_SESSION['email_address']; ?></strong> so that you can verify your email address. If you don’t see the email, please check your spam or junk folder.</p>
            <button class="btn" onclick="window.location.href='accountcreatednoorg.php'">Next</button>
        </div>
</div>
</body>
</html>