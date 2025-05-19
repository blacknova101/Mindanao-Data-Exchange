<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        h1 {
            font-size: 28px;
            color: #0099ff;
            margin-bottom: 20px;
        }

        .icon {
            font-size: 60px;
            color: #0099ff;
            margin-bottom: 20px;
        }

        p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #555;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0099ff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: background-color 0.3s ease;
            margin: 5px;
        }

        .btn:hover {
            background-color: #0077cc;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid #0099ff;
            color: #0099ff;
        }

        .btn-outline:hover {
            background-color: #f0f7ff;
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
    </style>
</head>
<body>
<video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>
    <div class="container">
        <div class="icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1>Unauthorized Access</h1>
        <p>You do not have the necessary permissions to access this page.</p>
        <p>Please log in with the appropriate credentials or contact the administrator if you believe this is an error.</p>
        <div>
            <a href="login.php" class="btn">Log In</a>
            <a href="HomeLogin.php" class="btn btn-outline">Back to Home</a>
        </div>
    </div>
</body>
</html>
