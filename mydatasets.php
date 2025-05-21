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

// Initialize the total_count variable
$total_count = 0;

// Get count of pending access requests for this user
$request_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $requestCountSql = "SELECT COUNT(*) as count FROM dataset_access_requests 
                        WHERE owner_id = $user_id AND status = 'Pending'";
    $requestCountResult = mysqli_query($conn, $requestCountSql);
    if ($requestCountResult) {
        $row = mysqli_fetch_assoc($requestCountResult);
        $request_count = $row['count'];
    }
}

// Get count of unread notifications for this user
$notif_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $notifCountSql = "SELECT COUNT(*) as count FROM user_notifications 
                      WHERE user_id = $user_id AND is_read = FALSE";
    $notifCountResult = mysqli_query($conn, $notifCountSql);
    if ($notifCountResult) {
        $row = mysqli_fetch_assoc($notifCountResult);
        $notif_count = $row['count'];
    }
}

// Total count for badge display (requests + notifications)
$total_count = $request_count + $notif_count;

$user_id = $_SESSION['user_id'];

// Get the current filter (if any)
$current_filter = isset($_GET['visibility']) ? $_GET['visibility'] : '';

$sql = "
    SELECT 
        d.dataset_id,
        d.dataset_batch_id,
        d.title,
        d.description, 
        d.file_path,
        u.first_name, 
        u.last_name,
        c.name AS category_name,
        c.category_id,
        db.visibility,
        (SELECT COUNT(*) FROM datasetratings r JOIN datasets d2 ON r.dataset_id = d2.dataset_id WHERE d2.dataset_batch_id = d.dataset_batch_id) AS upvotes,
        (SELECT COUNT(*) FROM datasetratings r JOIN datasets d2 ON r.dataset_id = d2.dataset_id WHERE d2.dataset_batch_id = d.dataset_batch_id AND r.user_id = ?) AS user_upvoted
    FROM datasets d
    JOIN users u ON d.user_id = u.user_id
    JOIN dataset_batches db ON d.dataset_batch_id = db.dataset_batch_id
    LEFT JOIN datasetcategories c ON d.category_id = c.category_id
    WHERE d.user_id = ?
      AND d.dataset_id = (
          SELECT MIN(d2.dataset_id) FROM datasets d2 WHERE d2.dataset_batch_id = d.dataset_batch_id AND d2.user_id = ?
      )
";

// Add visibility filter if specified
if (isset($_GET['visibility']) && in_array($_GET['visibility'], ['Public', 'Private'])) {
    $visibility_filter = mysqli_real_escape_string($conn, $_GET['visibility']);
    $sql .= " AND db.visibility = '$visibility_filter'";
}

$sql .= " ORDER BY d.dataset_id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'iii', $user_id, $user_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Set this AFTER the session is updated
$upload_disabled = !isset($_SESSION['organization_id']) || $_SESSION['organization_id'] == null;

include 'batch_analytics.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>All Datasets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/datasets_styles.css">

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
        
        /* Filter Sidebar Styles */
        .filter-sidebar {
            display: none; /* Hide the sidebar version */
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
        .search-bar {
            display: flex;
            position: relative;
            justify-content: center;
        }
        .search-bar input {
            padding: 20px;
            width: 100%;
            max-width: 1000px;
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
        
        /* All dataset-related styles now come from datasets_styles.css */

        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white; 
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 70px; /* Reduced margin */
        }
        .profile-icon img {
            width: 150%;
            height: auto;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
        }
        .profile-icon img:hover {
            transform: scale(1.2); /* Slightly enlarge the image on hover */
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        /* Updated notification badge styles for navbar */
        .nav-links .profile-icon {
            position: relative;
        }
        
        .nav-links .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff3b30;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            padding: 0;               /* Remove extra padding */
            line-height: 18px;        /* Match height for vertical centering */
            text-align: center;       /* Ensure text is centered */
        }

        /* Mobile menu toggle button */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        /* Responsive styles for the navbar */
        @media screen and (max-width: 768px) {
            .navbar {
                padding: 10px;
                border-radius: 15px;
                width: 90%; /* Smaller width on mobile */
                max-width: 90%;
                position: relative;
                z-index: 2; /* Give navbar highest z-index */
            }
            
            .mobile-menu-toggle {
                display: block;
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .logo img {
                width: 50px;
            }
            
            .nav-links {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                flex-direction: column;
                background-color: #0099ff;
                padding: 10px 0;
                border-radius: 0 0 15px 15px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                display: none;
                z-index: 9999; /* Same as navbar to ensure it stays on top */
            }
            
            .nav-links.active {
                display: flex;
            }
            
            .nav-links a {
                width: 100%;
                text-align: center;
                padding: 10px 0;
                margin: 0;
            }
            
            .profile-icon {
                margin: 10px auto 0;
            }
        }

        /* Add this to fix z-index issues */
        .search-bar, #category-btn, .add-data-btn, #wrapper {
            position: relative;
            z-index: 1; /* Lower z-index than navbar */
        }

        @media screen and (max-width: 480px) {
            .navbar {
                padding: 8px 10px;
            }
            
            .logo img {
                width: 50px;
            }
        }

    </style>
</head>
<body>
<video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>

<!-- Visibility filter sidebar -->
<!-- This is now handled by the controls-wrapper -->

<div class="container">
<header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
            <h2>My Datasets</h2>
        </div>
        <button class="mobile-menu-toggle" id="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <nav class="nav-links" id="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="datasets.php">ALL DATASETS</a>
            <div class="profile-icon" id="navbar-profile-icon">
                <img src="images/avatarIconunknown.jpg" alt="Profile">
                <?php if ($total_count > 0): ?>
                    <span class="notification-badge"><?php echo $total_count; ?></span>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <form id="searchForm" action="my_search_results.php" method="GET">
        <div class="search-bar">
        <input type="text" name="search" placeholder="Search my datasets" onfocus="showDropdown()" onblur="hideDropdown()">
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
                <?php
    // Set variables required by dataset_layout.php
    $page_title = "My Datasets";
    $filter_base_url = "mydatasets.php";
    $is_my_datasets = true; // Flag for conditional rendering in the layout
    
    // Include the common layout
    include 'dataset_layout.php';
    ?>
</div>


        </div>
        <br><br>
</div>
<?php include 'category_modal.php'; // Include the modal?>
<?php include 'sidebar.php'; // Include the sidebar ?>
<script>
        function showModal() {
            document.getElementById("categoryModal").style.display = "flex";
        }
        function hideModal() {
            document.getElementById("categoryModal").style.display = "none";
        }
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("categoryModal").style.display = "none";
            
            // Initialize view toggle
            const savedView = localStorage.getItem('datasetViewPreference');
            if (savedView) {
                toggleView(savedView);
            }
            
            // Mobile menu toggle functionality
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const navLinks = document.getElementById('nav-links');
            
            mobileMenuToggle.addEventListener('click', function() {
                navLinks.classList.toggle('active');
            });
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
    // Update the click event to use the specific ID
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('navbar-profile-icon').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.add('active');
            document.querySelector('.sidebar-overlay').classList.add('active');
        });
    });
    </script>
</body>
</html>
