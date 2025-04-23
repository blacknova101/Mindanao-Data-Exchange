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
        font-family: Arial, sans-serif;
        background-color: #e6f0fa;
        margin: 0;
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
  <div class="content">
    <h2>VERIFY YOUR EMAIL ADDRESS</h2>
    <p>We have sent an email to <strong><?php echo $_SESSION['email_address']; ?></strong> so that you can verify your email address. If you don’t see the email, please check your spam or junk folder.</p>
    <button class="btn" onclick="window.location.href='accountcreatedwithorg.php'">Next</button>
  </div>
</body>
</html>
