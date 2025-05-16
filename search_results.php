<?php
session_start();
include 'db_connection.php';

// Include session update to ensure organization_id is synchronized
include 'update_session.php';

// Check if the user is logged in (ensure 'user_id' is set in the session)
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not authenticated
    header("Location: login.php");
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Include the hasApprovedAccess function
function hasApprovedAccess($conn, $dataset_id, $user_id) {
    $query = "SELECT * FROM dataset_access_requests 
              WHERE dataset_id = $dataset_id 
              AND requester_id = $user_id 
              AND status = 'Approved'";
    $result = mysqli_query($conn, $query);
    return ($result && mysqli_num_rows($result) > 0);
}

if ($search) {
    $sql = "
        SELECT 
            db.dataset_batch_id,
            db.user_id,
            db.organization_id,
            db.visibility,
            u.first_name, u.last_name,
            o.name AS org_name,
            d.dataset_id,
            d.title AS dataset_title,
            d.description AS dataset_description,
            d.file_path,
            c.name AS category_name,
            (SELECT COUNT(*) FROM datasetratings r JOIN datasets d2 ON r.dataset_id = d2.dataset_id WHERE d2.dataset_batch_id = db.dataset_batch_id) AS upvotes,
            (SELECT COUNT(*) FROM datasetratings r JOIN datasets d2 ON r.dataset_id = d2.dataset_id WHERE d2.dataset_batch_id = db.dataset_batch_id AND r.user_id = {$_SESSION['user_id']}) AS user_upvoted
        FROM dataset_batches db
        JOIN users u ON db.user_id = u.user_id
        LEFT JOIN organizations o ON db.organization_id = o.organization_id
        LEFT JOIN datasets d ON d.dataset_id = (
            SELECT MIN(dataset_id) FROM datasets WHERE dataset_batch_id = db.dataset_batch_id
        )
        LEFT JOIN datasetcategories c ON d.category_id = c.category_id
        WHERE 
            u.first_name LIKE '%$search%'
            OR u.last_name LIKE '%$search%'
            OR o.name LIKE '%$search%'
            OR d.title LIKE '%$search%'
            OR d.description LIKE '%$search%'
            OR c.name LIKE '%$search%'
        ORDER BY db.dataset_batch_id DESC
    ";
    $page_title = "Search results for: " . htmlspecialchars($search);
} elseif ($category) {
    $sql = "
        SELECT 
            db.dataset_batch_id,
            db.user_id,
            db.organization_id,
            u.first_name, u.last_name,
            o.name AS org_name,
            d.dataset_id,
            d.title AS dataset_title,
            d.description AS dataset_description,
            d.file_path,
            c.name AS category_name,
            (SELECT COUNT(*) FROM datasetratings r JOIN datasets d2 ON r.dataset_id = d2.dataset_id WHERE d2.dataset_batch_id = db.dataset_batch_id) AS upvotes,
            (SELECT COUNT(*) FROM datasetratings r JOIN datasets d2 ON r.dataset_id = d2.dataset_id WHERE d2.dataset_batch_id = db.dataset_batch_id AND r.user_id = {$_SESSION['user_id']}) AS user_upvoted
        FROM dataset_batches db
        JOIN users u ON db.user_id = u.user_id
        LEFT JOIN organizations o ON db.organization_id = o.organization_id
        LEFT JOIN datasets d ON d.dataset_id = (
            SELECT MIN(dataset_id) FROM datasets WHERE dataset_batch_id = db.dataset_batch_id
        )
        LEFT JOIN datasetcategories c ON d.category_id = c.category_id
        WHERE c.name = '$category'
        ORDER BY db.dataset_batch_id DESC
    ";
    $page_title = htmlspecialchars($category);
} else {
    $sql = "
        SELECT 
            db.dataset_batch_id,
            db.user_id,
            db.organization_id,
            u.first_name, u.last_name,
            o.name AS org_name,
            d.dataset_id,
            d.title AS dataset_title,
            d.description AS dataset_description,
            d.file_path,
            c.name AS category_name,
            (SELECT COUNT(*) FROM datasetratings r JOIN datasets d2 ON r.dataset_id = d2.dataset_id WHERE d2.dataset_batch_id = db.dataset_batch_id) AS upvotes,
            (SELECT COUNT(*) FROM datasetratings r JOIN datasets d2 ON r.dataset_id = d2.dataset_id WHERE d2.dataset_batch_id = db.dataset_batch_id AND r.user_id = {$_SESSION['user_id']}) AS user_upvoted
        FROM dataset_batches db
        JOIN users u ON db.user_id = u.user_id
        LEFT JOIN organizations o ON db.organization_id = o.organization_id
        LEFT JOIN datasets d ON d.dataset_id = (
            SELECT MIN(dataset_id) FROM datasets WHERE dataset_batch_id = db.dataset_batch_id
        )
        LEFT JOIN datasetcategories c ON d.category_id = c.category_id
        ORDER BY db.dataset_batch_id DESC
    ";
    $page_title = "All Datasets";
}

// Get the current filter (if any)
$current_filter = isset($_GET['visibility']) ? $_GET['visibility'] : '';

// Modify the SQL query to filter by visibility if specified
if (isset($_GET['visibility']) && in_array($_GET['visibility'], ['Public', 'Private'])) {
    $visibility_filter = mysqli_real_escape_string($conn, $_GET['visibility']);
    
    if (strpos($sql, 'WHERE') !== false) {
        // If there's already a WHERE clause, add AND
        $sql = str_replace('ORDER BY', "AND db.visibility = '$visibility_filter' ORDER BY", $sql);
    } else {
        // If there's no WHERE clause, add one
        $sql = str_replace('ORDER BY', "WHERE db.visibility = '$visibility_filter' ORDER BY", $sql);
    }
}

$result = mysqli_query($conn, $sql);

// Set this AFTER the session is updated
$upload_disabled = !isset($_SESSION['organization_id']) || $_SESSION['organization_id'] == null;

include 'batch_analytics.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Datasets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        html, body {
            height: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5%; /* Adjusted padding for a more compact navbar */
            padding-left: 30px;
            background-color: #0099ff; /* Transparent background */
            color: #cfd9ff;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            margin: 10px 0;
            backdrop-filter: blur(10px);
            max-width: 1200px; /* Limit the maximum width */
            width: 100%; /* Ensure it takes up the full width but doesn't exceed 1200px */
            margin-top:30px;
            margin-left: auto; /* Center align the navbar */
            margin-right: auto; /* Center align the navbar */
            font-weight: bold;
        }
        .logo {
        display: flex;
        align-items: center;
        }
        .logo img {
            height: auto;
            width: 80px; /* Adjust logo size */
            max-width: 100%;
        }
        .nav-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-size: 18px;
            transition: transform 0.3s ease; /* Smooth transition for scaling */
        }
        .nav-links a:hover {
            transform: scale(1.2); /* Scale up on hover */
        }

        h2 {
            text-align: center;
            color: #0c1a36;
            margin-bottom: 15px;
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
            text-align: left; /* Default alignment for text */
        }
        .dataset-download {
            text-align: center; 
            margin-top: 10px;
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
            display: flex;
            position: relative;
            justify-content: center;
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
            padding-top: 20px;
            padding-bottom: 20px;
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
            padding-top: 20px;
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
        #background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1; /* stays behind everything */
        }
        .no-datasets {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 500px; /* Full height of the viewport */
            text-align: center;
            color: #0c1a36;
        }
        .no-datasets p{
            margin-left: 38px;
        }

        .no-found-img {
            width: 250px;
            max-width: 90%;
            margin-bottom: 20px;
        }

        .no-datasets p {
            font-size: 28px;
            font-weight: bold;
        }
        .download-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 12px;
            background-color: #0099ff;
            color: white;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .download-btn:hover {
            background-color: #e65c00;
        }
        .tooltip-wrapper {
            position: relative;
            display: inline-block;
        }

        .tooltip-text {
            visibility: hidden;
            width: 260px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px 0;
            position: absolute;
            z-index: 1;
            bottom: 80%; /* Position above the button */
            left: 50%;
            margin-left: -130px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 14px;
            pointer-events: none;
        }

        .tooltip-wrapper:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .add-data-btn.disabled-link {
            background-color: #e0e0e0;
            color: #b0b0b0;
            cursor: not-allowed;
            pointer-events: none;
            margin-top: 21px;
            padding-bottom:19.5px;
        }
        .dataset-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 10px;
        }

        .dataset-download {
            flex: 1;
            text-align: center;
            padding-left: 70px;
        }

        .dataset-upvote {
            padding-top: 20px;
            text-align: right;
        }

        .dataset-upvote button {
            background-color: white;
            color: #333;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 5px 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
            width: 80px; /* Ensure a consistent button width */
            height: 30px; /* Set a fixed height for the button */
            box-sizing: border-box; /* Prevent padding from affecting size */
            display: inline-block; /* Keep the button inline to prevent shifting */
        }


        .dataset-upvote button:hover {
            background-color: #f0f0f0;  /* Light gray background on hover */
        }

        .dataset-upvote span {
            display: inline-block;
            margin-left: 10px;
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .dataset-upvote button.upvoted {
            border: 1px solid; /* Avoid layout shift */
            background-color: #f0f0f0; /* Change background color when upvoted */
            box-shadow: inset 0 0 0 1px black; /* Add a shadow instead of border */
        }
        .dataset-analytics {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-top: 25px;
        }

        .dataset-analytics .analytics-item {
            display: flex;
            align-items: center;
            color: #888;
        }

        .dataset-analytics .analytics-item i {
            margin-right: 4px;
        }

        .category-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
            font-weight: bold;
        }

        /* Add CSS for the visibility filter sidebar */
        .filter-sidebar {
            position: fixed;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            background-color: #fff;
            padding: 20px 15px;
            border-radius: 0 10px 10px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 100;
            width: 110px;
        }

        .filter-sidebar-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .filter-sidebar-title i {
            margin-right: 5px;
            color: #0099ff;
        }

        .filter-btn {
            padding: 8px 16px;
            background-color: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 20px;
            font-weight: bold;
            transition: all 0.3s ease;
            text-align: center;
        }

        .filter-btn:hover {
            background-color: #e0e0e0;
        }

        .filter-btn.active {
            background-color: #0099ff;
            color: white;
        }

        .visibility-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
            font-weight: bold;
        }

        .visibility-badge.public {
            background-color: #4CAF50;
            color: white;
        }

        .visibility-badge.private {
            background-color: #f44336;
            color: white;
        }
    </style>
</head>
<body>
<video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>

<div class="container">
<header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
            <h2><?php echo $page_title; ?></h2>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="mydatasets.php">MY DATASETS</a>
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
    <span class="tooltip-wrapper">
        <a href="<?= $upload_disabled ? '#' : 'uploadselection.php' ?>" 
        id="add-data-btn" 
        class="add-data-btn<?= $upload_disabled ? ' disabled-link' : '' ?>">
            ADD DATA
        </a>
        <?php if ($upload_disabled): ?>
            <span class="tooltip-text">You must be part of an organization to upload datasets.</span>
        <?php endif; ?>
    </span>
    </div>
<div id="wrapper">
    <?php include 'view_toggle.php'; ?>
    
    <!-- Card View -->
    <div class="dataset-grid">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php
                    $batch_id = $row['dataset_batch_id'];
                    $analytics = get_batch_analytics($conn, $batch_id);
                ?>
                <div class="dataset-card">
                    <div class="dataset-title">
                    <a href="dataset.php?id=<?= $row['dataset_id'] ?>&title=<?= urlencode($row['dataset_title']) ?>">
                        <?= htmlspecialchars($row['dataset_title']) ?>
                    </a>
                    <span class="visibility-badge <?= strtolower($row['visibility']) ?>">
                        <?= $row['visibility'] ?>
                    </span>
                    </div>
                    <div class="dataset-description">
                        <?= htmlspecialchars(mb_strimwidth($row['dataset_description'], 0, 255, '...')) ?>
                    </div>
                    <div class="dataset-uploader">
                        <br><br><br>
                        Uploaded by: <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                    </div>
                    <div class="dataset-actions">
                        <div class="dataset-analytics">
                            <span class="analytics-item" title="Views">
                                <i class="fa-regular fa-eye"></i>
                                <?= $analytics['total_views'] ?>
                            </span>
                            <span class="analytics-item" title="Downloads">
                                <i class="fa-solid fa-download"></i>
                                <?= $analytics['total_downloads'] ?>
                            </span>
                        </div>
                        <div class="dataset-download">
                            <?php 
                                $is_private_unowned = ($row['visibility'] == 'Private' && $row['user_id'] != $_SESSION['user_id']);
                                $has_access = hasApprovedAccess($conn, $row['dataset_id'], $_SESSION['user_id']);
                                if (!$is_private_unowned || $has_access): 
                            ?>
                                <a href="download_batch.php?batch_id=<?= $row['dataset_batch_id'] ?>" class="download-btn">Download</a>
                            <?php else: ?>
                                <span class="download-btn" style="background-color: #ccc; cursor: not-allowed;">Private</span>
                            <?php endif; ?>
                        </div>
                        <div class="dataset-upvote" data-id="<?= $row['dataset_id'] ?>">
                            <button class="<?= $row['user_upvoted'] == 1 ? 'upvoted' : '' ?>" onclick="upvoteDataset(<?= $row['dataset_id'] ?>)">
                                <?= $row['user_upvoted'] == 1 ? '⬆ Upvoted' : '⬆ Upvote' ?>
                            </button>
                            <span id="upvote-count-<?= $row['dataset_id'] ?>"><?= $row['upvotes'] ?></span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- Table View -->
    <table class="dataset-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Category</th>
                <th>Analytics</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Reset the result pointer to the beginning
            mysqli_data_seek($result, 0);
            
            if (mysqli_num_rows($result) > 0): 
                while ($row = mysqli_fetch_assoc($result)): 
                    $batch_id = $row['dataset_batch_id'];
                    $analytics = get_batch_analytics($conn, $batch_id);
            ?>
                <tr>
                    <td>
                        <a href="dataset.php?id=<?= $row['dataset_id'] ?>&title=<?= urlencode($row['dataset_title']) ?>">
                            <?= htmlspecialchars($row['dataset_title']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars(mb_strimwidth($row['dataset_description'], 0, 100, '...')) ?></td>
                    <td>
                        <?php if (!empty($row['category_name'])): ?>
                            <span class="category-badge"><?= htmlspecialchars($row['category_name']) ?></span>
                        <?php else: ?>
                            <span class="category-badge">Uncategorized</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="table-analytics">
                            <span class="analytics-item" title="Views">
                                <i class="fa-regular fa-eye"></i> <?= $analytics['total_views'] ?>
                            </span>
                            <span class="analytics-item" title="Downloads">
                                <i class="fa-solid fa-download"></i> <?= $analytics['total_downloads'] ?>
                            </span>
                            <span class="analytics-item" title="Upvotes">
                                <i class="fa-solid fa-arrow-up"></i> <?= $row['upvotes'] ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="table-actions">
                            <div class="table-download">
                                <?php 
                                    $is_private_unowned = ($row['visibility'] == 'Private' && $row['user_id'] != $_SESSION['user_id']);
                                    $has_access = hasApprovedAccess($conn, $row['dataset_id'], $_SESSION['user_id']);
                                    if (!$is_private_unowned || $has_access): 
                                ?>
                                    <a href="download_batch.php?batch_id=<?= $row['dataset_batch_id'] ?>" class="download-btn">Download</a>
                                <?php else: ?>
                                    <span class="download-btn" style="background-color: #ccc; cursor: not-allowed;">Private</span>
                                <?php endif; ?>
                            </div>
                            <div class="table-upvote" data-id="<?= $row['dataset_id'] ?>">
                                <button class="<?= $row['user_upvoted'] == 1 ? 'upvoted' : '' ?>" onclick="upvoteDataset(<?= $row['dataset_id'] ?>)">
                                    <?= $row['user_upvoted'] == 1 ? '⬆ Upvoted' : '⬆ Upvote' ?>
                                </button>
                                <span class="table-upvote-count"><?= $row['upvotes'] ?></span>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php 
                endwhile; 
            endif; 
            ?>
        </tbody>
    </table>
    
    <!-- No datasets found message outside of the grid -->
    <?php if (mysqli_num_rows($result) == 0): ?>
        <div class="no-datasets">
            <img src="images/no-found1.png" alt="No data" class="no-found-img">
            <p>No dataset found.</p>
        </div>
    <?php endif; ?>
</div>

        </div>
        <br><br>
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

    function upvoteDataset(datasetId) {
        const upvoteButton = document.querySelector(`[data-id="${datasetId}"] button`);
        const countSpan = document.getElementById(`upvote-count-${datasetId}`);
        
        // Check if the button is already upvoted (has 'upvoted' class)
        const isUpvoted = upvoteButton.classList.contains('upvoted');

        // Toggle the class based on the upvote state
        if (isUpvoted) {
            upvoteButton.classList.remove('upvoted');
            upvoteButton.textContent = '⬆ Upvote'; // Change text to 'Upvote' when unvoted
        } else {
            upvoteButton.classList.add('upvoted');
            upvoteButton.textContent = '⬆ Upvoted'; // Change text to 'Upvoted' when clicked
        }

        fetch('upvote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `dataset_id=${datasetId}`
        })
        .then(response => response.json())
        .then(data => {
            let current = parseInt(countSpan.textContent);

            if (data.status === 'voted' && !isUpvoted) {
                countSpan.textContent = current + 1;
            } else if (data.status === 'unvoted' && isUpvoted) {
                countSpan.textContent = current - 1;
            } else if (data.status === 'unauthorized') {
                alert('You must be logged in to upvote.');
            } else {
                alert('Something went wrong.');
            }
        });
    }
    </script>

<!-- Add visibility filter sidebar after the opening body tag -->
<div class="filter-sidebar">
    <div class="filter-sidebar-title">
        <i class="fa-solid fa-filter"></i>
        VISIBILITY
    </div>
    <a href="search_results.php?<?= isset($_GET['search']) ? 'search=' . urlencode($_GET['search']) : (isset($_GET['category']) ? 'category=' . urlencode($_GET['category']) : '') ?>" class="filter-btn <?= $current_filter == '' ? 'active' : '' ?>">All</a>
    <a href="search_results.php?<?= isset($_GET['search']) ? 'search=' . urlencode($_GET['search']) . '&' : (isset($_GET['category']) ? 'category=' . urlencode($_GET['category']) . '&' : '') ?>visibility=Public" class="filter-btn <?= $current_filter == 'Public' ? 'active' : '' ?>">Public</a>
    <a href="search_results.php?<?= isset($_GET['search']) ? 'search=' . urlencode($_GET['search']) . '&' : (isset($_GET['category']) ? 'category=' . urlencode($_GET['category']) . '&' : '') ?>visibility=Private" class="filter-btn <?= $current_filter == 'Private' ? 'active' : '' ?>">Private</a>
</div>

</body>
</html>