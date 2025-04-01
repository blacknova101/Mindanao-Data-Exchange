<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MDX</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('images/Mindanao.png');
            background-size: cover;
            background-attachment: fixed;
            text-align: center;
            overflow: hidden;
        }
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 2%;
            background-color: #0c1a36;
            color: white;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo img {
        height: auto; 
        width: 100px; 
        max-width: 100%; 
        margin-left: 0; 
        }
        .nav-links {
            display: flex; 
            gap: 20px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 18px;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
        .login-container {
            margin-top: 100px;
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
            width: 350px;
            text-align: center;
        }
        .logo-container img {
            background-color: #0c1a36;
            border-radius: 15px;
            padding: 10px;
            width: 80px;
        }
        .input-container {
            display: flex;
            align-items: center;
            background-color: #E3F2FD;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 10px;
            margin: 10px 0;
        }
        .input-container img {
            width: 20px;
            margin-right: 10px;
        }
        .input-container input {
            border: none;
            background: none;
            outline: none;
            width: 100%;
            font-size: 16px;
        }
        .forgot-password {
            text-align: left;
            margin: 20px 0 5px 10px;
            display: block;
        }
        .sign-up {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        button {
            background-color: #0c1a36;
            color: white;
            padding: 10px;
            border: none;
            width: 100%;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
        }
        button:hover {
            background-color: #092045;
        }
        a {
            color: #0c1a36;
            text-decoration: none;
            font-size: 16px;
        }
        a:hover {
            text-decoration: underline;
        }
        .login {
        display: block;
        background-color: #0c1a36;
        color: white;
        padding: 14px 25px;
        text-align: center;
        text-decoration: none;
        border-radius: 5px;
        font-size: 18px;
        font-weight: bold;
        margin-top: 10px;
        }

        .login:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
        </div>
        <nav class="nav-links">
            <a href="MindanaoDataExchange.php">Home</a>
            <a href="AccountSelectionPage.php">Sign up</a>
        </nav>
    </header>

    <div class="container">
        <div class="login-container">
            <div class="logo-container">
                <img src="images/mdx_logo.png" alt="MDX Logo">
            </div>
            <form action="dashboard.php" method="POST">
                <div class="input-container">
                    <img src="images/user_icon.png" alt="User Icon">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="input-container">
                    <img src="images/password_icon.png" alt="Password Icon">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <a href="#" class="forgot-password">Forgot password?</a>
                <a href="HomeLogin.php" class="login">LOGIN</a>
            </form>
            <a href="AccountSelectionPage.php" class="sign-up">Sign up</a>
        </div>
    </div>
</body>
</html>
