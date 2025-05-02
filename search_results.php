<?php
session_start();
include 'db_connection.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

if ($search) {
    // Search by title or description
    $sql = "SELECT title, description, category, user_id FROM datasets 
        WHERE title LIKE '%$search%' 
        OR description LIKE '%$search%' 
        OR category LIKE '%$search%' 
        OR user_id LIKE '%$search%' 
        ORDER BY user_id DESC";
    $page_title = "Search results for: " . htmlspecialchars($search);
} elseif ($category) {
    // Filter by category
    $sql = "SELECT title, description FROM datasets 
            WHERE category = '$category'
            ORDER BY user_id DESC";
    $page_title = htmlspecialchars($category);
} else {
    // Show all datasets
    $sql = "SELECT title, description FROM datasets ORDER BY user_id DESC";
    $page_title = "All Datasets";
}

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
        .dataset-grid {
            display: flex;
            flex-direction: column;
            gap: 2px;
            width: 100%;
        }

        .dataset-card {
            width: 100%;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
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
    </style>
</head>
<body>

<div class="container">
<header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
            <h2><?php echo $page_title; ?></h2>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
        </nav>
    </header> 
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
                        <?= htmlspecialchars(mb_strimwidth($row['description'], 0, 120, '...')) ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No datasets found.</p>
        <?php endif; ?>
    </div>
        </div>

</div>

</body>
</html>