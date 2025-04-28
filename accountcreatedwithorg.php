<?php
    session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account Created</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #e6f0fa;
        }
        .header {
            background-color: #0c2239;
            padding: 20px;
            text-align: center;
        }
        .header img {
            height: 60px;
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
        .title {
            font-size: 28px;
            margin-bottom: 20px;
        }
        .btn {
            margin-top: 30px;
            background-color: #0c2239;
            color: white;
            padding: 12px 30px;
            font-size: 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #1a3a5a;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="your-logo.png" alt="Logo">
    </div>
    <div class="container">
        <div class="progress">
            <div class="step">
                <div class="circle">✓</div>
                <div>Personal details</div>
            </div>
            <div class="step">
                <div class="circle">✓</div>
                <div>Verify email</div>
            </div>
            <div class="step active">
                <div class="circle">3</div>
                <div>Account created</div>
            </div>
            <div class="step">
                <div class="circle">4</div>
                <div>Select organization</div>
            </div>
        </div>

        <div class="title">Account Created!</div>

        <form action="selectorganization.php" method="POST">
            <button class="btn" type="submit">Next</button>
        </form>
    </div>
</body>
</html>
