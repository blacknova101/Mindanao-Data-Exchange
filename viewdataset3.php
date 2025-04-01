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
            margin-top: 20px;
        }
        .dataset-header h2 {
            color: #0c1a36;
        }
        .dataset-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dataset-text {
            width: 60%;
        }
        .dataset-image {
            width: 35%;
        }
        .dataset-image img {
            width: 100%;
            border-radius: 8px;
        }
        .info-resources-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .additional-info, .resources {
            padding: 15px;
            background: #f1f1f1;
            border-radius: 8px;
        }
        .resources ul {
            list-style-type: none;
            padding: 0;
        }
        .resources ul li {
            padding: 5px 0;
        }
        .dataset-image {
            width: 10%;
            height: 10%;
        }
        .info-image img {
            width: 95%;
            height: 300px;
            display: block;
            margin: auto;
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
            <h2>Satellite Analysis of Green Spaces in Cities</h2>
        </div>
        <div class="dataset-content">
            <div class="dataset-text">
                <p>This dataset provides an analysis of urban green spaces using satellite imagery and remote sensing data. It includes information on vegetation coverage, park distribution, and changes in green space density over time, helping urban planners assess environmental sustainability and urban heat island effects.</p>
            </div>  
            <div class="dataset-image">
                <img src="images/viewpic3.png" alt="Stock Market Data">
            </div>
        </div>
        <div class="info-image">
            <img src="images/viewpic3.1.png" alt="Additional Data Visualization">
        </div>
        <div class="info-resources-container">
            <div class="additional-info">
                <h3>Additional Information</h3>
                <p><strong>Time Period:</strong> January 1, 2018 - December 31, 2023</p>
                <p><strong>Location:</strong> Philippines</p>
                <p><strong>Source:</strong> Philippine Environmental Monitoring Agency (PEMA)</p>
                <p><strong>Link:</strong> <a href="#">www.pfdi-research.org/stock-market-data-2024</a></p>
            </div>
            <div class="resources">
                <h3>Data and Resources</h3>
                <ul>
                    <li><a href="#">stock-market-data-2024.csv</a></li>
                    <li><a href="#">stock-market-data-2024.json</a></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>