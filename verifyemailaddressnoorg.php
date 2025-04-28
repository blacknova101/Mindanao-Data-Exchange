<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email</title>
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
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
      }

      h2 {
        margin-bottom: 20px;
        font-size: 24px;
        color: #333;
      }

      p {
        font-size: 16px;
        color: #555;
      }

      .verification-form {
        margin-top: 30px;
        background-color: #f8f8f8;
        padding: 20px;
        border-radius: 6px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
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
        background-color: #0d1b2a;
        color: white;
        border: none;
        padding: 12px 30px;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
        width: 100%;
      }

      .verification-form button[type="submit"]:hover {
        background-color: #1e2b3b;
      }

      .message {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
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

    </style>
</head>
<body>

  <div class="content">
    <h2>VERIFY YOUR EMAIL ADDRESS</h2>
    <p>We have sent an email to <strong><?php echo $_SESSION['pending_user']['email']; ?></strong> so that you can verify your email address. If you don’t see the email, please check your spam or junk folder.</p>
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
</body>
</html>
