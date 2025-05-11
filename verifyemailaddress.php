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
            html, body {
        height: auto;
        overflow: auto;
    }

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
        padding: 10px 5%; /* Adjusted padding for a more compact navbar */
        padding-left: 30px;
        background-color:  #0099ff; /* Transparent background */
        color: #cfd9ff;
        border-radius: 20px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        position: relative;
        margin: 10px 0;
        backdrop-filter: blur(10px);
        max-width: 1200px; /* Limit the maximum width */
        width: 100%; /* Ensure it takes up the full width but doesn't exceed 1200px */
        margin-top:30px;
        margin-left: auto; /* Center align the navbar */
        margin-right: auto; /* Center align the navbar */
    }

    .logo {
        display: flex;
        align-items: center;
        margin-right: 20px;
    }
    .logo img {
        height: auto;
        width: 80px; /* Adjust logo size */
        max-width: 100%;
    }
    .nav-links a {
        color: white;
        margin-left: 20px;
        text-decoration: none;
        font-size: 18px;
        transition: transform 0.3s ease; /* Smooth transition for scaling */
    }
    .nav-links a:hover {
        transform: scale(1.2); /* Scale up the link by 20% */
    }

    #background-video {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: -1; /* stays behind everything */
    }
    .nav-links {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .container {
      max-width: 600px;
      margin: 50px auto;
      background-color: white;
      padding: 40px;
      padding-top: 30px;
      text-align: center;
      border-radius: 12px;
      box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
    }
      h2 {
        font-size: 2.5em;
        color: #0099ff;
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
        background-color: #0099ff;
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
        .progress {
            display: flex;
            justify-content: space-between;
            max-width: 600px;
            margin: 30px auto;
            height:68px;
        }

        .step {
            text-align: center;
        }
        .circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 0 auto 10px;
            margin-top:10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: black; /* default black */
            color: white;
            font-weight: bold;
        }

        /* Specific styling for Circle 1 */
        #circle2 {
            background-color: #0099ff; /* blue */
            color: white;
        }
        #circle1 {
            background-color: #0099ff; /* blue */
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
    </div>
    

</body>
</html>
<!--  <div class="content">
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
