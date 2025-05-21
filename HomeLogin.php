<?php
session_start();
include 'db_connection.php'; // Include your database connection file

// Include session update to ensure organization_id is synchronized
include 'update_session.php';

if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not authenticated
    header("Location: index.php");
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

// Query to count the number of datasets in the database
$sql = "SELECT COUNT(*) AS dataset_count FROM datasets";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$dataset_count = $row['dataset_count']; // Store the dataset count
// Query to count the number of unique users (distinct user_id) in the datasets table
$sql_sources = "SELECT COUNT(DISTINCT user_id) AS unique_sources FROM datasets";
$result_sources = mysqli_query($conn, $sql_sources);
$row_sources = mysqli_fetch_assoc($result_sources);
$sources_count = $row_sources['unique_sources']; // Store the unique sources count
$upload_disabled = !isset($_SESSION['organization_id']) || $_SESSION['organization_id'] == null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDX</title>
    <link rel="stylesheet" href="assets/css/homelogin.css">
    <script src="https://kit.fontawesome.com/2c68a433da.js" crossorigin="anonymous"></script>
    <style>
    /* Additional styles that are not in the external CSS file */
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
        background: #cfd9ff;
    }
    .nav-links a {
        color: white;
        text-decoration: none;
        transition: transform 0.3s ease;
    }
    .nav-links a:hover {
        transform: scale(1.2);
    }
    h1 {
        font-weight: 600;
        margin-bottom: 20px;
        color: rgba(0, 153, 255, 0.8);
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }
    h1:hover {
        color: rgba(0, 172, 255, 1);
        text-shadow: 2px 2px 5px rgba(233, 230, 230, 0.3);
    }
    #tagline {
        color: rgba(0, 153, 255, 0.8);
        text-align: center;
        margin-top: 10px;
        text-shadow: 1px 1px 5px rgba(255, 253, 253, 0.67);
        transition: all 0.3s ease;
    }
    #tagline:hover {
        color: rgba(0, 172, 255, 1);
        text-shadow: 2px 2px 8px rgb(255, 253, 253);
    }
    .stat {
        flex: 1;
        text-align: center;
        color: rgba(28, 132, 227, 0.8);
    }
    .divider {
        background-color: black;
    }
    .profile-icon img {
        width: 150%;
        height: auto;
        border-radius: 50%;
        object-fit: cover;
        cursor: pointer;
    }
    .profile-icon img:hover {
        transform: scale(1.2);
    }
    .profile-icon {
        border-radius: 50%;
        background-color: white; 
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .tooltip-text {
        position: absolute;
        top: 50%;
        right: 110%;
        transform: translateY(-50%);
        background-color: #333;
        color: #fff;
        padding: 6px 10px;
        border-radius: 5px;
        font-size: 14px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease;
        z-index: 100;
    }
    .upload-btn:hover .tooltip-text {
        opacity: 1;
        visibility: visible;
    }
    .upload-wrapper {
        position: relative;
        display: inline-block;
    }
    .upload-wrapper.has-tooltip:hover .tooltip-text {
        opacity: 1;
        visibility: visible;
    }
    .upload-btn.disabled {
        pointer-events: none;
        opacity: 0.5;
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
        padding: 0;
        line-height: 18px;
        text-align: center;
    }
    .upload-section {
        position: fixed;
        bottom: 20px;
        right: 20px;
        color: rgba(0, 153, 255, 0.8);
    }
    
    .upload-btn {
        display: inline-block;
        padding: 20px;
        background-color: rgba(0, 153, 255, 0.8);
        border-radius: 8px; /* Rounded corners */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Subtle shadow */
        transition: all 0.3s ease;
    }

    .upload-btn i {
        font-size: 40px; /* Larger icon size */
        color: #ffffff; /* White color for the icon */
    }

    .upload-btn:hover {
        background-color: #a0b6f3; /* Darker blue when hovered */
        transform: scale(1.1); /* Slightly increase size on hover */
    }
    
    .upload-wrapper {
        position: relative;
        display: inline-block;
    }
    
    .upload-wrapper.has-tooltip:hover .tooltip-text {
        opacity: 1;
        visibility: visible;
    }
    
    .upload-btn.disabled {
        pointer-events: none;
        opacity: 0.5;
    }
    </style>
</head>
<body>
    <video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>

    <div id="wrapper">
        <header class="navbar">
            <div class="logo">
                <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
            </div>
            <form id="searchForm" action="search_results.php" method="GET" class="search-bar">
                <input type="text" name="search" placeholder="Search datasets" onfocus="showDropdown()" onblur="hideDropdown()">
                <button type="submit" aria-label="Search">
                    <img src="images/search_icon.png" alt="Search">
                </button>
                <div id="searchDropdown" class="search-dropdown">
                    <div class="trending-title">Trending Searches</div>
                    <ul id="trendingSearches">
                        <!-- Trending searches will be loaded here -->
                    </ul>
                </div>
            </form>
            <nav class="nav-links">
                <a href="HomeLogin.php">HOME</a>
                <a href="datasets.php">DATASETS</a>
                <a onclick="showModal()" style="cursor: pointer;">CATEGORY</a>
                <div class="profile-icon" id="navbar-profile-icon">
                    <img src="images/avatarIconunknown.jpg" alt="Profile">
                    <?php if ($total_count > 0): ?>
                        <span class="notification-badge"><?php echo $total_count; ?></span>
                    <?php endif; ?>
                </div>
            </nav>
        </header>
    
        <script>
            function showDropdown() {
                var dropdown = document.getElementById("searchDropdown");
                if (dropdown) {
                    dropdown.style.display = "block";
                }
            }
            
            function hideDropdown() {
                setTimeout(() => {
                    var dropdown = document.getElementById("searchDropdown");
                    if (dropdown) {
                        dropdown.style.display = "none";
                    }
                }, 200);
            }
            
            function showModal() {
                document.getElementById("categoryModal").style.display = "flex";
            }
            
            function hideModal() {
                document.getElementById("categoryModal").style.display = "none";
            }
            
            document.addEventListener("DOMContentLoaded", function() {
                document.getElementById("categoryModal").style.display = "none";
                
                // Update the click event to use the specific ID
                document.getElementById('navbar-profile-icon').addEventListener('click', function() {
                    document.querySelector('.sidebar').classList.add('active');
                    document.querySelector('.sidebar-overlay').classList.add('active');
                });
            });
        </script>
        
        <main class="wrapper">
            <h1>Mangasay <br> Data Exchange </h1>
            <p id="tagline">Discover, Share, and Transform Data Seamlessly.</p>
            <div class="stats-box">
                <div class="stat">
                <span class="stat-number"><?= number_format($dataset_count) ?></span>
                    <p>Dataset Files</p>
                </div>
                <div class="divider"></div>
                <div class="stat">
                <span class="stat-number"><?= number_format($sources_count) ?></span> <!-- Dynamic Sources Count -->
                <p>Sources</p>
                </div>
            </div>
        </main>

        <div class="upload-section">
            <div class="upload-wrapper <?php echo $upload_disabled ? 'has-tooltip' : ''; ?>">
                <a href="uploadselection.php" class="upload-btn <?php echo $upload_disabled ? 'disabled' : ''; ?>">
                    <i class="fa-solid fa-upload"></i>
                </a>
                <?php if ($upload_disabled): ?>
                    <span class="tooltip-text">You must be part of an organization to upload datasets.</span>
                <?php endif; ?>
                <p>Upload Data</p>
            </div>
        </div>
        
        <?php include 'sidebar.php'; ?>
        <?php include 'category_modal.php'; // Include the modal?>
        
        <script src="search.js"></script>
    </div><!-- End of wrapper -->

    <!-- Footer with updated styling -->
    <footer>
        <div class="footer-container">
            <!-- Left section with copyright -->
            <div class="footer-column">
                <h3 class="footer-heading">Mangasay Data Exchange</h3>
                <div class="footer-divider"></div>
                <p class="footer-text">&copy; <?php echo date('Y'); ?> MDX</p>
                <p class="footer-text">All Rights Reserved</p>
                <p class="footer-text">Mindanao, Philippines</p>
            </div>
            
            <!-- Center section with description -->
            <div class="footer-column">
                <h3 class="footer-heading">About MDX</h3>
                <div class="footer-divider"></div>
                <p class="footer-description">
                    MDX is Mindanao's premier open data platform, connecting organizations and researchers to discover, 
                    share and transform data across the region. Our mission is to build a collaborative data ecosystem 
                    that empowers decision-makers, researchers, and communities throughout Mindanao.
                </p>
            </div>
            
            <!-- Right section with links and social -->
            <div class="footer-column">
                <h3 class="footer-heading">Connect With Us</h3>
                <div class="footer-divider"></div>
                
                <!-- Social media icons -->
                <div class="social-icons">
                    <a href="https://x.com/MindanaoE66996" target="_blank" class="social-link">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://www.facebook.com/61576255121231" target="_blank" class="social-link">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://www.instagram.com/mindanaodataexchange/" target="_blank" class="social-link">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.linkedin.com/in/mindanao-data-exchange-270b97366/" target="_blank" class="social-link">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
                
                <!-- Important links as horizontal layout -->
                <div class="footer-links">
                    <a href="about.php" class="footer-link">About Us</a>
                    <a href="privacy.php" class="footer-link">Privacy Policy</a>
                    <a href="terms.php" class="footer-link">Terms of Service</a>
                    <a href="contact.php" class="footer-link">Contact</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
