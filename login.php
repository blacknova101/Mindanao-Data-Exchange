<?php
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']); // Sanitize the error message
}
?>

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
            text-align: center;
            overflow: hidden;
        }
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5%; /* Adjusted padding for a more compact navbar */
            padding-left: 30px;
            background-color: rgba(0, 153, 255, 0.5); /* Transparent background */
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
        .nav-links {
            display: flex; 
            gap: 20px;
        }
        .nav-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-size: 18px;
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
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
        .login-container {
            margin-top: 100px;
            background: #cfd9ff;
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
            background-color:rgb(142, 155, 226);
        }
        #flashbang {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s ease;
            z-index: 9999; /* Make sure it's on top of everything */
        }

        </style>
</head>
<body>
<div id="flashbang"></div>

    <video autoplay muted loop id="background-video">
        <source src="videos/background.mp4" type="video/mp4">
    </video>
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
            
            <?php if (isset($error_message)): ?>
                <div style="color: red; font-size: 16px; margin-bottom: 20px;">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <form action="login_api.php" method="POST">
                <div class="input-container">
                    <img src="images/user_icon.png" alt="User Icon">
                    <input type="text" name="email" placeholder="Email address" required>
                </div>
                <div class="input-container">
                    <img src="images/password_icon.png" alt="Password Icon">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <a href="#" class="forgot-password">Forgot password?</a>
                <button type="submit" class="login">LOGIN</button>
            </form>
            <a href="AccountSelectionPage.php" class="sign-up">Sign up</a>
        </div>
    </div>
    <script>
        document.querySelector('.login').addEventListener('click', function(e) {
            e.preventDefault(); // Stop form from submitting immediately

            const flashbang = document.getElementById('flashbang');
            flashbang.style.opacity = 1; // Show white screen

            // Wait 500ms for the flash, then submit the form
            setTimeout(() => {
                this.closest('form').submit(); // Now submit
            }, 500); // match the CSS transition time
        });
</script>

</body>
</html>
