<?php
session_start();
include 'db_connection.php';

// Fetch all datasets (title and description only)
$sql = "
    SELECT d.title, d.description, u.first_name, u.last_name
    FROM datasets d
    JOIN users u ON d.user_id = u.user_id
    ORDER BY d.user_id DESC
";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Datasets</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 10px;
            padding-bottom: 10px;
            padding-left: 30px;
            padding-right: 10px;
            background-color: #0099ff; /* Transparent background */
            color: #cfd9ff;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            margin: 10px 0;
            backdrop-filter: blur(10px);
            width: 100%; /* Ensure it takes up the full width but doesn't exceed 1200px */
            margin-top:30px;
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
        .nav-links a {
            display: inline-block; /* Needed for transform to work */
            color: white;
            margin-left: 10px;
            margin-right: 27.5px;
            padding-right: 20px; /* Add some padding to make hover area bigger */
            text-decoration: none;
            font-size: 18px;
            transition: transform 0.3s ease, color 0.3s ease;
            font-weight: bold;
        }
        .nav-links a:hover {
            transform: scale(1.2); /* Scale up on hover */
        }


        h2 {
            text-align: center;
            color: #0c1a36;
            margin-bottom: 30px;
        }
        #wrapper {
            max-width: 1240px;
            margin: 5px auto;
            padding: 0 20px;
        }
        .dataset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
            gap: 20px;
            width: 100%;
        }

        .dataset-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Ensures uploader stays at bottom */
            height: 250px; /* Fixed height */
            padding: 20px;
            box-sizing: border-box;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .dataset-title {
            font-size: 20px;
            font-weight: bold;
            color: #0c1a36;
            margin-bottom: 10px;
        }

        .dataset-description {
            font-size: 14px;
            color: #333;
            line-height: 1.4;
            flex-grow: 1; /* Takes up remaining space */
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dataset-uploader {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }


        .dataset-card:hover {
            transform: translateY(-5px);
        }
        .dataset-title {
            font-size: 20px;
            font-weight: bold;
            color: #0c1a36;
            margin-bottom: 10px;
        }
        .dataset-title a {
            text-decoration: none;
            color: #0c1a36;
        }
        .dataset-description {
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }
        #wrapper{
            width: 100%;
            padding-top: 30px;
        }
        .search-bar {
            flex-grow: 1;
            display: flex;
            position: relative;
            margin-left: 50px; /* Adjust space for a smaller navbar */

        }
        .search-bar input {
            padding: 20px;
            width: 1000px;/* Reduced width */
            border: solid black 1px;
            border-radius:10px;
            margin-top: 20px;
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
            margin-top: 20px;
        }
        .add-data-btn {
            margin-top: 20px;
            padding: 8px 5px;
            padding-top: 15px;
            background-color: #0099ff;
            color: white;
            border: none;
            width: 100px;
            border-radius: 5px;
            text-decoration: none; /* Remove underline */
            display: inline-block;
            text-align: center;
            box-shadow: 0 5px 10px rgba(0,0,0,0.2), inset 0 2px 4px rgba(255,255,255,0.4);
            font-weight: bold;
            cursor: pointer;
        }
        .add-data-btn:hover {
            background-color: #007acc;
        }
        #category-btn {
            margin-top: 20px;
            padding: 8px 5px;
            padding-top: 15px;
            background-color: #4CAF50; /* Green color */
            color: white;
            border: none;
            width: 120px; /* Make it slightly wider than the add button */
            border-radius: 5px;
            text-decoration: none; /* Remove underline */
            display: inline-block;
            text-align: center;
            box-shadow: 0 5px 10px rgba(0,0,0,0.2), inset 0 2px 4px rgba(255,255,255,0.4);
            font-weight: bold;
            cursor: pointer;
            margin-right: 15px; /* Add some space between buttons */
        }

        #category-btn:hover {
            background-color: #45a049; /* Darker green on hover */
        }

    </style>
</head>
<body>

<div class="container">
<header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
            <h2>Available Datasets</h2>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
        </nav>
    </header>
    <form id="searchForm" action="search_results.php" method="GET">
        <div class="search-bar">
        <input type="text" name="search" placeholder="Search datasets" onfocus="showDropdown()" onblur="hideDropdown()">
        <button>
            <img src="images/search_icon.png" alt="Search">
        </button>
    </form>
    <a id="category-btn" onclick="showModal()" style="cursor: pointer;">CATEGORY</a>
    <a href="uploadselection.php" id="add-data-btn" class="add-data-btn">ADD DATA</a>
</div>
<div id="wrapper">
    <div class="dataset-grid">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="dataset-card">
                    <div class="dataset-title">
                        <a href="my_uploaded_dataset.php?title=<?= urlencode($row['title']) ?>">
                            <?= htmlspecialchars($row['title']) ?>
                        </a>
                    </div>
                    <div class="dataset-description">
                        <?= htmlspecialchars(mb_strimwidth($row['description'], 0, 255, '...')) ?>
                    </div>
                    <div class="dataset-uploader">
                    <br><br><br>
                        Uploaded by: <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No datasets found.</p>
        <?php endif; ?>
    </div>
        </div>

</div>
<?php include 'category_modal.php'; // Include the modal?>
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
</body>
</html>
