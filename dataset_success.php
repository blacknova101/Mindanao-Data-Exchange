<?php
session_start();

// Check if the user is logged in (ensure 'user_id' is set in the session)
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not authenticated
    header("Location: login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Upload Successful</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f0f8ff;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      overflow: hidden;
    }
    .container {
      background: white;
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      text-align: center;
      max-width: 500px;
      width: 100%;
      z-index: 2;
      position: relative;
    }
    .button {
      background-color: #0c1a36;
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      font-size: 16px;
      cursor: pointer;
      margin-top: 20px;
    }
    .button:hover {
      background-color: #092045;
    }
    #jumpscareVideo {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      object-fit: cover;
      z-index: 999;
      display: none;
    }
  </style>
</head>
<body>

  <!-- Jump scare video -->
  <video id="jumpscareVideo" autoplay>
    <source src="videos/jumpscare.mp4" type="video/mp4">
    Your browser does not support the video tag.
  </video>

  <!-- Background music -->
  <audio id="bgMusic" loop>
    <source src="songs/fettywap.mp3" type="audio/mpeg">
    Your browser does not support the audio element.
  </audio>

  <!-- Main content -->
  <div class="container" id="mainContent" style="display:none;">
    <h1>Upload Successful!</h1>
    <p>Your dataset has been successfully uploaded.</p>
    <p>Thank you for contributing to the community!</p>
    <a href="datasets.php" class="button">Go to Datasets</a>
  </div>

  <script>
    const video = document.getElementById('jumpscareVideo');
    const music = document.getElementById('bgMusic');
    const content = document.getElementById('mainContent');

    // Show video, then hide it after playback
    window.addEventListener('load', () => {
      video.style.display = 'block';
      video.play();

      video.onended = () => {
        video.style.display = 'none';
        content.style.display = 'block';
        music.play();
      };
    });
  </script>

</body>
</html>

<!--<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Successful</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f8ff;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .container h1 {
            color: #4caf50;
            font-size: 32px;
        }
        .container p {
            font-size: 18px;
            margin: 20px 0;
        }
        .button {
            background-color: #0c1a36;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #092045;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Upload Successful!</h1>
        <p>Your dataset has been successfully uploaded.</p>
        <p>Thank you for contributing to the community!</p>
        <a href="datasets.php" class="button">Go to Datasets</a>
    </div>

</body>
</html>-->