<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration Form</title>
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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-group label {
            font-size: 16px;
            color: #333;
        }

        .input-group input {
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            background-color: #f8f9fa;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 16px;
            font-weight: bold;
            border: 1px solid #f5c6cb;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
            margin-top: 20px;
        }

        .btn-next {
            background-color: #0c2239;
            color: white;
            padding: 15px 60px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            width: 200px;
        }

        .btn-cancel {
            background: none;
            border: none;
            color: #333;
            font-size: 18px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="images/mdx_logo.png" alt="Logo">
    </div>
    <div class="container">
        <!-- Show error message if it exists -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="progress">
            <div class="step active">
                <div class="circle">1</div>
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
        
        <h1>Provide the following information</h1>
        
        <form action="registration_api.php" method="POST">
            <div class="form-grid">
                <div class="input-group">
                    <label for="firstname">First Name:</label>
                    <input type="text" name="firstname" id="firstname" required>
                </div>
                <div class="input-group">
                    <label for="lastname">Last Name:</label>
                    <input type="text" name="lastname" id="lastname" required>
                </div>

                <div class="input-group">
                    <label for="email_address">Email Address:</label>
                    <input type="text" name="email_address" id="email_address" value="<?php echo isset($_SESSION['email_address']) ? $_SESSION['email_address'] : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="re_enter_email">Re-enter Email Address:</label>
                    <input type="text" name="re_enter_email" id="re_enter_email" value="<?php echo isset($_SESSION['re_enter_email']) ? $_SESSION['re_enter_email'] : ''; ?>" required>
                </div>

                <div class="input-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <div class="input-group">
                    <label for="re_enter_pass">Re-enter Password:</label>
                    <input type="password" name="re_enter_pass" id="re_enter_pass" required>
                </div>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn-next">Next</button>
                <button type="button" class="btn-cancel">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>
