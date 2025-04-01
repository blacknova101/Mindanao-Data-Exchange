<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDX - Datasets</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #eef3fa;
        }
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 2%;
            background-color: #0c1a36;
            color: white;
        }
        .logo img {
            width: 80px;
            height: auto;
        }
        .search-bar {
            flex-grow: 1;
            margin-left: 50px;
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
        .search-dropdown ul {
            list-style: none;
            margin: 0;
            padding: 0;
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
        .search-dropdown ul li:hover {
            background: #e3f2fd;
        }
        .nav-links {
            display: flex;
            align-items: center;
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
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            margin-left: 20px;
        }
        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
        }
        .container {
            width: 90%;
            margin: auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .dataset-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .filters {
            display: flex;
            gap: 10px;
        }
        .filters button {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: #0c1a36;
            color: white;
            transition: background 0.3s;
        }
        .filters button:hover {
            background-color: #142d5e;
        }
        .add-data {
            padding: 8px 16px;
            background-color: #0c1a36;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .add-data:hover {
            background-color: #142d5e;
        }
        .dataset-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .dataset-item {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .dataset-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .dataset-info {
            padding: 10px;
        }
        .dataset-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* Ensures only 2 dataset-items per row */
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
    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange">
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
            <a href="#">DATASETS</a>
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
        <div class="dataset-header">
            <h2>DATASETS</h2>
            <button class="add-data">ADD DATA</button>
        </div>
        <div class="filters">
            <button>Most Popular</button>
            <button>All Datasets</button>
            <button>Science & Research</button>
        </div>
        <h3>Trending Datasets</h3>
        <div class="dataset-list">
            <div class="dataset-item">
            <a href="viewdataset.php">
                <img src="images/pic1.png" alt="Urban Expansion">
                <div class="dataset-info">
                    <h4>Global Urban Expansion Trends 2024</h4>
                    <p>A dataset on city growth, infrastructure development, and population density.</p>
                </div>
            </a>
            </div>
            <div class="dataset-item">
            <a href="viewdataset2.php">
                <img src="images/pic2.png" alt="Traffic Data">
                <div class="dataset-info">
                    <h4>Real-Time Traffic Patterns & Congestion Data</h4>
                    <p>Traffic flow and congestion data from major urban areas.</p>
                </div>
                </a>
            </div>
            <div class="dataset-item">
            <a href="viewdataset3.php">
                <img src="images/pic3.png" alt="Green Spaces">
                <div class="dataset-info">
                    <h4>Satellite Analysis of Green Spaces in Cities</h4>
                    <p>Data on urban greenery, parks, and environmental zones.</p>
                </div>
                </a>
            </div>
            <div class="dataset-item">
            <a href="viewdataset4.php">
                <img src="images/pic4.png" alt="Smart Cities">
                <div class="dataset-info">
                    <h4>Smart Cities: IoT & Infrastructure Data</h4>
                    <p>Information on smart city networks, IoT systems, and urban infrastructure.</p>
                </div>
                </a>
            </div>
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
</body>
</html>
