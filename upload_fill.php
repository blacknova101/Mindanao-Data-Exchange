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
        background-color: #E3F2FD;
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
        width: 60%;
        margin: 50px auto;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
        .header {
        display: flex;
        align-items: center;
        justify-content: space-between; 
        padding: 10px;
        border-bottom: 2px solid #ccc;
    }
        .header-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .create-btn {
    padding: 10px;
    background-color: #ccc;
    border: none;
    cursor: not-allowed;
}
    .close-btn {
        font-size: 24px;
        cursor: pointer;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        margin-bottom: 15px;
        margin-top: 15px;
    }
    .form-group label {
        font-weight: bold;
        margin-bottom: 5px;
    }
    .form-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    .flex-container {
        display: flex;
        gap: 20px;
    }
    .flex-item {
        flex: 1;
    }
    .form-item {
        flex: 1;
        min-width: 45%;
    }
    .form-group input, .form-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    .file-upload {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #ccc;
        padding: 10px;
        border-radius: 5px;
    }
    .footer {
        height: 50px;
        background: #ccc;
        margin-top: 20px;
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
        <div class="header">
    <div class="header-left">
        <span class="close-btn">&times;</span>
        <h2>UPLOAD DATASET</h2>
    </div>
        <button class="create-btn">Create</button>
    </div>
    <div class="form-group">
        <label for="title">DATASET TITLE</label>
        <input type="text" id="title" placeholder="Enter dataset title">
    </div>
    <div class="flex-container">
        <div class="form-group flex-item">
            <label for="visibility">VISIBILITY</label>
            <select id="visibility">
                <option>Choose Visibility</option>
                <option>Public</option>
                <option>Private</option>
            </select>
        </div>
        <div class="form-group flex-item">
            <label for="category">CATEGORY</label>
            <select id="category">
                <option>Choose Category</option>
                <option>Business and Finance</option>
                <option>Geography and Mapping</option>
            </select>
        </div>
    </div>
    <div class="flex-container">
        <div class="form-group flex-item">
            <label for="time-period">TIME PERIOD</label>
            <input type="text" id="time-period" placeholder="Add time period">
        </div>
        <div class="form-group flex-item">
            <label for="source">SOURCE</label>
            <input type="text" id="source" placeholder="Add source">
        </div>
    </div>
    <div class="flex-container">
        <div class="form-group flex-item">
            <label for="location">LOCATION</label>
            <input type="text" id="location" placeholder="Enter location">
        </div>
        <div class="form-group flex-item">
            <label for="link">LINK</label>
            <input type="text" id="link" placeholder="Add link">
        </div>
    </div>
    <div class="form-group">
        <label>Files</label>
        <div class="file-upload">
            <span>📄</span>
            <span>davao_urban_growth_statistics_2024.csv</span>
        </div>
    <div class="footer"></div>
    <?php include 'sidebar.php'; ?>
</body>
</html>