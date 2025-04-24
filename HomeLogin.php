<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDX</title>
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
        margin-left: 100px;
        display: flex;
        align-items: center;
        position: relative;
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
    .search-dropdown {
        position: absolute;
        top: 45px;
        left: 0;
        width: 100%;
        max-width: 300px;
        background: white;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: none;
        z-index: 10;
    }
    .search-dropdown ul li, .trending-title {
        padding: 10px;
        cursor: pointer;
        transition: background 0.3s;
        color: black;
    }
    .search-dropdown .trending-title {
        font-weight: bold;
        padding: 8px 10px;
        border-bottom: 1px solid #ccc;
        text-align: left;
    }
    .search-dropdown ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .search-dropdown ul li {
        padding: 10px;
        cursor: pointer;
        transition: background 0.3s;
        text-align: left;
    }
    .search-dropdown ul li:hover {
        background: #e3f2fd;
    }
    .nav-links a {
        color: white;
        margin-left: 20px;
        text-decoration: none;
        font-size: 18px;
    }
    .wrapper {
        padding: 50px 5%;
        margin-top: 50px;
    }
    h1 {
        font-size: 90px;
        font-weight: bold;
        margin-bottom: 20px;
    }
    .stats-box {
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: white;
        padding: 20px 0px 0px 0px;
        width: 30%;
        margin: 0 auto;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        font-size: 30px;
        font-weight: bold;
    }
    .stat {
        flex: 1;
        text-align: center;
    }
    .divider {
        width: 3px;
        background-color: black;
        height: 90px;
        margin-top: -20px
    }
    .upload-section {
        position: fixed;
        bottom: 20px;
        right: 20px;
    }
    .upload-btn {
        background: none;
        border: none;
        cursor: pointer;
        text-align: center;
    }
    .upload-btn img {
        width: 60px;
    }
    .upload-btn p {
        font-size: 16px;
        color: black;
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
        
    </style>
</head>
<body>
    <div id="wrapper">
        <header class="navbar">
            <div class="logo">
                <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
            </div>
            <div class="search-bar">
                <input type="text" placeholder="Search datasets" onfocus="showDropdown()" onblur="hideDropdown()">
                <button>
                    <img src="images/search_icon.png" alt="Search">
                </button>
                <div class="search-dropdown" id="searchDropdown">
                    <p class="trending-title">Trending</p>
                    <ul>
                        <li>Smart City IoT Sensor Readings</li>
                        <li>Davao City</li>
                        <li>Space Tourism Flight Records</li>
                        <li>Extreme Weather Events Database</li>
                    </ul>
                </div>
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
        <main class="wrapper">
            <h1>Mangasay <br> Data Exchange </h1>
            <p>Discover, Share, and Transform Data Seamlessly.</p>
            <div class="stats-box">
                <div class="stat">
                    <span class="stat-number">18,000</span>
                    <p>Datasets</p>
                </div>
                <div class="divider"></div>
                <div class="stat">
                    <span class="stat-number">2,000</span>
                    <p>Sources</p>
                </div>
            </div>
        </main>
        <div class="upload-section">
            <a href="uploadselection.php" class="upload-btn">
                <img src="images/upload_button.png" alt="Upload">
            </a>
            <p>Upload Data</p>
        </div>
    </div>
    <script>
        function showDropdown() {
            document.getElementById("searchDropdown").style.display = "block";
        }
        function hideDropdown() {
            setTimeout(() => {
                document.getElementById("searchDropdown").style.display = "none";
            }, 200);
        }
    </script>
    <?php include 'sidebar.php'; ?>
</body>
</html>
