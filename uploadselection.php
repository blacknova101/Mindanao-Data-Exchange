<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $targetDirectory = "uploads/";
    $targetFile = $targetDirectory . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    
    if (file_exists($targetFile)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }

    
    if ($_FILES["fileToUpload"]["size"] > 5000000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    
    $allowedTypes = ["jpg", "png", "pdf", "csv", "txt"];
    if (!in_array($fileType, $allowedTypes)) {
        echo "Sorry, only JPG, PNG, PDF, CSV, and TXT files are allowed.";
        $uploadOk = 0;
    }

    
    if ($uploadOk == 1) {
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {
            echo "The file " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " has been uploaded.";
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}
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
        padding: 15px 2%;
        background-color: #0c1a36;
        color: white;
    }
    .logo {
        display: flex;
        align-items: center;
        margin-right: 30px;
    }
    .logo img {
    height: auto; 
    width: 100px; 
    max-width: 100%; 
    margin-left: 0; 
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
            <a onclick="showModal()">CATEGORY</a>
            <div class="profile-icon">
                <img src="images/avatarIconunknown.jpg" alt="Profile">
            </div>
        </nav>
        </header>
        <div class="modal" id="categoryModal">
        <div class="modal-content">
            <h2>Select a Category</h2>
            <div class="category-grid">
                <div>Business & Finance</div>
                <div>Education & Academia</div>
                <div>Science & Research</div>
                <div>Agriculture & Environment</div>
                <div>Technology & IT</div>
                <div>Government & Public Data</div>
                <div>Geography & Mapping</div>
                <div>Commerce & Consumer Data</div>
                <div>Social & Media</div>
                <div>Health & Medicine</div>
            </div>
            <button class="close-btn" onclick="hideModal()">Close</button>
        </div>
    </div>
    
    <script>
        function showModal() {
            document.getElementById("categoryModal").style.display = "flex";
        }
        function hideModal() {
            document.getElementById("categoryModal").style.display = "none";
        }
        document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("categoryModal").style.display = "none";
    });
    </script>
<div class="container">
    <h2>UPLOAD DATASET</h2>
    <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data">
        <label for="fileToUpload" class="drop-area">
            <img src="images/upload_button.png" alt="Upload Icon">
            <p>Drag & drop files to upload</p>
            <p>or</p>
            <a href="upload_fill.php">
                <button type="button" class="browse-btn">Browse</button>
            </a>
        </label>
        <input type="file" name="fileToUpload" id="fileToUpload" onchange="this.form.submit()" required>
    </form>
</div>

</body>
</html>