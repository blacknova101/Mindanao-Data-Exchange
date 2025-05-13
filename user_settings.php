<?php
session_start();
include 'db_connection.php'; // Make sure this file sets up $conn

// Check if the user is logged in (ensure 'user_id' is set in the session)
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not authenticated
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];


$sql = "SELECT u.first_name, u.last_name, u.email, o.name AS organization_name, o.organization_id
        FROM users u
        LEFT JOIN organizations o ON u.organization_id = o.organization_id
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$organizationName = $userData['organization_name'] ?? '';
$organizationId = $userData['organization_id'] ?? null; // <-- Add this line
$hasOrganization = !empty($organizationName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('images/Mindanao.png');
            background-size: cover;
            background-attachment: fixed;
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
            width: 80px;
            max-width: 100%;
        }
        .search-bar {
            flex-grow: 1;
            display: flex;
            align-items: center;
            position: relative;
            margin-left: -190px; /* Adjust space for a smaller navbar */
        }
        .search-bar input {
            padding: 8px;
            width: 400px;
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
        .settings-container h2 {
            margin: 0 auto;
            font-size: 25px;
            font-weight: bold;
            text-align: left;
            color: black;
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
        #errormessage{
            color: red;
        }
        .settings-container {
            width: 100%; /* Full width */
            max-width: 1200px; /* Ensure it matches the max-width of the navbar */
            margin: 40px auto; /* Centering the container */
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }

        @media (max-width: 1200px) {
            .settings-container {
                padding: 20px;
            }
        }

        @media (max-width: 600px) {
            .settings-container {
                width: 90%;
            }
        }



        .profile-container {
            width: 500px;
            display: flex;
            justify-content: space-between; /* Ensure the inputs are spaced out */
            gap: 30px; /* Add space between the fields */
        }

        .form-group {
            padding-top: 10px;
            padding-left: 15px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input {
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .form-group input[type="file"] {
            padding: 5px;
        }
        #email, #current_password, #new_password, #confirm_password {
            width: 468px; /* Set the width of the email input box */
        }
        #first_name, #last_name {
            width: 200px; /* Ensure both fields fit within their respective containers */
        }
        #confirmpass{
            padding-bottom: 20px;
        }

        .submit-btn {
            padding: 12px 20px;
            background-color: #0099ff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: #007acc;
        }
        #profpic-firstname {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 10px;
        }

        #avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
        }

        #header #first_name, #header #last_name {
            font-size: 25px;
            font-weight: bold;
        }

        .section-divider {
            border: none;
            border-top: 2px solid black;
            margin-bottom: 10px;
        }
        #first_name[readonly],
        #last_name[readonly] {
            background-color: #d3d3d3; /* Gray background */
            cursor: not-allowed; /* Disable the cursor to indicate the field is not editable */
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
        #profile {
            width: 525px;
            padding: 25px;
            background-color: #f2f2f2; /* Light gray background to differentiate from white */
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        #change-password {
            width: 525px;
            padding: 25px;
            background-color: #f2f2f2; /* Light gray background for distinction */
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        #profile-wrapper{
            display: flex;
            gap: 50px;
        }
        #organization-container {
            width: 525px;
            padding: 25px;
            background-color: #f2f2f2; /* Light gray background for distinction */
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .create-org-btn {
            background-color: #0099ff;
            color: white;
            padding: 10px;
            margin-top: 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-left: 15px;
        }
        #org{
            margin-bottom: 23px;
        }
        /* Add this for disabled state */
        .create-org-btn:disabled {
            background-color: #cccccc;
            color: #666666;
            cursor: not-allowed;
        }
        .leave-org-modal-content {
            background: #fff; /* white background */
            border: 2px solid #0099ff; /* blue border */
            color: #ff4d4d; /* red text for caution */
            box-shadow: 0 0 20px #0099ff22; /* subtle blue shadow */
            padding: 30px 20px;
            border-radius: 16px;
            max-width: 400px;
            margin: auto;
            text-align: center;
            position: relative;
        }
        .leave-org-modal-content h3 {
            color: #ff4d4d; /* red for caution */
            margin-bottom: 10px;
        }
        .leave-org-modal-content p {
            color: #0099ff; /* blue for instructions */
            margin-bottom: 20px;
        }
        .leave-org-modal-content input[type="text"] {
            border: 1.5px solid #0099ff; /* blue border */
            border-radius: 5px;
            padding: 8px;
            width: 80%;
            margin-bottom: 15px;
            color: #222;
            background: #fff;
        }
        .leave-org-modal-content button[type="submit"] {
            background: #0099ff; /* blue button */
            color: #fff;         /* white text */
            border: none;
            border-radius: 5px;
            padding: 10px 18px;
            font-weight: bold;
            margin-right: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .leave-org-modal-content button[type="submit"]:hover {
            background: #007acc;
        }
        .leave-org-modal-content button[type="button"] {
            background: #fff; /* white background */
            color: #0099ff;   /* blue text */
            border: 1.5px solid #0099ff; /* blue border */
            border-radius: 5px;
            padding: 10px 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .leave-org-modal-content button[type="button"]:hover {
            background: #0099ff;
            color: #fff;
        }
        .leave-org-modal-content .warning-icon {
            font-size: 40px;
            color: #ff4d4d; /* red icon */
            margin-bottom: 10px;
            display: block;
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
        <form id="searchForm" action="search_results.php" method="GET">
            <div class="search-bar">
                <input type="text" name="search" placeholder="Search datasets" onfocus="showDropdown()" onblur="hideDropdown()">
                <button>
                    <img src="images/search_icon.png" alt="Search">
                </button>
                
            </div>
        </form>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="datasets.php">DATASETS</a>
            <a onclick="showModal()" style="cursor: pointer;">CATEGORY</a>
            <div class="profile-icon">
                <img src="images/avatarIconunknown.jpg" alt="Profile">
            </div>
        </nav>
    </header> 
    <div class="settings-container">
        <div id="header">
        <h2>User Settings</h2>
            <form action="save_settings.php" method="POST" enctype="multipart/form-data">
                <div id="profpic-firstname">
                    <div>
                        <img id="avatar" src="images/user_icon.png" alt="User Icon">
                    </div>
                    <div>
                        <span id="first_name"><?php echo $_SESSION['first_name']; ?></span>
                        <span id="last_name"><?php echo $_SESSION['last_name']; ?></span>
                    </div>
                </div>
                <hr class="section-divider">
            </div>
            <div id="profile-wrapper">
                <div id="profile">
                    <h2>Profile</h2>
                    <div class="profile-container">
                        <div id="display1" class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo $_SESSION['first_name']; ?>" required readonly>
                        </div>
                        <div id="display2" class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo $_SESSION['last_name']; ?>" required readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" required>
                    </div>
                </div>

                <div id="organization-container">
                    <h2 id="org">Organization</h2>
                    <div class="form-group">
                        <input type="text" id="organization" name="organization" value="<?php echo $organizationName; ?>" readonly>
                    </div>
                    <!-- Disable the 'Create New Organization' button if the user has an organization -->
                    <?php if (!$hasOrganization): ?>
                        <button type="button" class="create-org-btn" onclick="window.location.href='join_organization.php'">Join an Organization</button>
                    <?php else: ?>
                        <!-- If user has org, show leave button -->
                        <button type="button" class="create-org-btn" onclick="showLeaveOrgModal()">Leave Organization</button>
                    <?php endif; ?>
                </div>

            </div>

            <br><br>
            <div id="change-password">
                <h2>Change Password</h2>
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                </div>

                <div id="confirmpass" class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                </div>
            </div>

            <button type="submit" class="submit-btn">Save Changes</button>
        </form>
    </div>
</div>
<?php include('leave_orgmodal.php');?>
<?php include('sidebar.php');?>
<?php include('category_modal.php');?>
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