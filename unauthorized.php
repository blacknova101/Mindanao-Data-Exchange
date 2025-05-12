<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8d7da;
            color: #721c24;
            text-align: center;
            padding: 50px;
            overflow: hidden;
        }

        h1 {
            font-size: 36px;
        }

        p {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 20px;
            background-color: #f5c6cb;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn:hover {
            background-color: #d6a7ab;
        }

        #jumpscare {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: black;
            z-index: 9999;
            justify-content: center;
            align-items: center;
         }


        #jumpscare img {
            max-width: 100%;
            max-height: 100%;
            animation: shake 0.4s infinite;
        }

        @keyframes shake {
            0% { transform: translate(0, 0) scale(1.05); }
            20% { transform: translate(-5px, 5px) scale(1.1); }
            40% { transform: translate(5px, -5px) scale(1.1); }
            60% { transform: translate(-5px, -5px) scale(1.1); }
            80% { transform: translate(5px, 5px) scale(1.1); }
            100% { transform: translate(0, 0) scale(1.05); }
        }
    </style>
</head>
<body onclick="startScare()">

    <h1>Unauthorized Access</h1>
    <img src="images/jn.png" alt="Unauthorized Access" style="max-width: 200px; margin: 20px 0;">
    <p>You do not have the necessary permissions to view this page.</p>
    <p>If you believe this is an error, please <a href="login.php" onclick="scareThenRedirectToLogin()">log in</a> again.</p>
    <p><button onclick="scareThenRedirect()" class="btn">Go Back to Home</button></p>

    <div id="jumpscare">
        <img src="images/jn.png" alt="SCARY!">
    </div>

    <audio id="scream-audio" src="songs/scream.mp3" preload="auto"></audio>

    <script>
    function scareThenRedirect() {
        // Show the jumpscare
        document.getElementById("jumpscare").style.display = "flex";

        // Play scream sound
        const scream = document.getElementById("scream-audio");
        scream.play().catch((error) => {
        console.warn("Autoplay prevented:", error);
        });

        // Redirect after 5 seconds
        setTimeout(() => {
        window.location.href = "HomeLogin.php";
        }, 5000);
    }
</script>
</body>
</html>
