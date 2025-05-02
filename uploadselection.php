<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Dataset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('images/Mindanao.png');
            background-size: cover;
            background-attachment: fixed;
            text-align: center;
        }
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5%;
            padding-left: 30px;
            background-color: rgba(0, 153, 255, 0.5);
            color: #cfd9ff;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            margin: 10px 0;
            backdrop-filter: blur(10px);
            max-width: 1200px;
            width: 100%;
            margin-top:30px;
            margin-left: auto;
            margin-right: auto;
        }
        .logo {
            display: flex;
            align-items: center;
            margin-right: 20px;
        }
        .logo img {
            height: auto;
            width: 80px;
        }
        .search-bar {
            flex-grow: 1;
            margin-left: 20px;
            display: flex;
            align-items: center;
        }
        .search-bar input {
            padding: 10px;
            width: 100%;
            max-width: 300px;
            border-radius: 5px;
            border: none;
        }
        .search-bar button {
            background: none;
            border: none;
            cursor: pointer;
        }
        .search-bar img {
            width: 20px;
            height: 20px;
            margin-left: -50px;
        }
        .nav-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-size: 18px;
        }
        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white; 
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 70px;
        }
        .profile-icon img {
            width: 150%;
            height: auto;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .stats-box {
                flex-direction: column;
                width: 80%;
                font-size: 24px;
            }
            .divider {
                width: 100%;
                height: 2px;
                margin: 20px 0;
            }
            h1 {
                font-size: 40px;
            }
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .container {
            width: 70%;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
        }
        h2 {
            padding: 20px;
            margin: 0 auto;
            font-size: 18px;
            font-weight: bold;
            text-align: left;
            margin-bottom: 20px;
        }
        #uploadForm {
            padding: 20px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .drop-area {
            border: 2px dashed black;
            padding: 50px 200px 50px 200px;
            margin: 0 auto;
        }
        .drop-area.dragover {
            background-color: #e6ebff;
            border-color: #0c1a36;
        }
        .drop-area img {
            width: 50px;
            margin-bottom: 10px;
        }
        .drop-area p {
            font-size: 16px;
            color: #333;
            margin: 5px 0;
        }
        input[type="file"] {
            display: none;
        }
        .browse-btn {
            background-color: #0c1a36;
            color: white;
            padding: 10px;
            margin: 0 auto;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            display: block;
            margin-top: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 50%;
            max-width: 600px;
            text-align: center;
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .category-grid div {
            padding: 10px;
            background: #e3f2fd;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .category-grid div:hover {
            background: #bbdefb;
        }
        .close-btn {
            background: red;
            color: white;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
        }
        @media (max-width: 600px) {
            .container {
                width: 90%;
            }
        }
        #errormessage{
            color: red;
        }
    </style>
</head>
<body>

<div id="wrapper">
    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
        </div>
        <div class="search-bar">
            <input type="text" placeholder="Search datasets">
            <button>
                <img src="images/search_icon.png" alt="Search">
            </button>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="datasets.php">DATASETS</a>
            <a onclick="showModal()" style="cursor: pointer;">CATEGORY</a>
            <div class="profile-icon">
                <img src="images/avatarIconunknown.jpg" alt="Profile">
            </div>
        </nav>
    </header>

    <div class="container">
        <h2>UPLOAD DATASET</h2>
        <div id="errormessage">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
            </div>
            <?php unset($_SESSION['error_message']); // Unset the message after displaying it ?>
        <?php endif; ?>
        </div>
        <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data">
            <label for="fileToUpload" class="drop-area">
                <img src="images/upload_button.png" alt="Upload Icon">
                <p>Drag & drop files to upload</p>
                <p>or</p>
                <button type="button" class="browse-btn" onclick="document.getElementById('fileToUpload').click();">Browse</button>
            </label>
            <input type="file" name="fileToUpload" id="fileToUpload" onchange="this.form.submit()" required>
        </form>
    </div>

    <?php include 'sidebar.php'; ?>
</div>

</body>
</html>
