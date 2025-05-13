<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle search
$search = $_GET['search'] ?? '';
$sql = "SELECT organization_id, name FROM organizations WHERE name LIKE ?";
$stmt = $conn->prepare($sql);
$searchParam = '%' . $search . '%';
$stmt->bind_param("s", $searchParam);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join an Organization</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5%;
            padding-left: 30px;
            background-color: #0099ff;
            color: #cfd9ff;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            margin: 10px 0;
            backdrop-filter: blur(10px);
            max-width: 1200px;
            width: 100%;
            margin-top: 30px;
            margin-left: auto;
            margin-right: auto;
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
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
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
        .container {
            max-width: 1200px;
            width: 90%;
            margin: 10px auto;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }
        .org-list {
            margin-top: 30px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 15px;
        }
        .org-list::-webkit-scrollbar {
            width: 8px;
        }
        .org-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .org-list::-webkit-scrollbar-thumb {
            background: #0099ff;
            border-radius: 4px;
        }
        .org-list::-webkit-scrollbar-thumb:hover {
            background: #007acc;
        }
        .org-item {
            padding: 12px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2px;
            font-size: 18px;
        }
        .org-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .join-btn {
            background: #0099ff;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 16px;
        }
        .join-btn:hover {
            background: #007acc;
        }
        .create-org-btn {
            background: #fff;
            color: #0099ff;
            border: 2px solid #0099ff;
            border-radius: 6px;
            padding: 12px 24px;
            margin-top: 0px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: 16px;
        }
        .create-org-btn:hover {
            background: #0099ff;
            color: #fff;
        }
        .not-found {
            color: #ff4d4d;
            margin-top: 25px;
            text-align: center;
            font-size: 18px;
            padding: 20px;
        }
        input[type="text"] {
            width: 80%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-right: 15px;
            font-size: 16px;
            padding-right:91px;
        }
        #background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        h2 {
            margin-bottom: 25px;
            color: #0099ff;
            font-size: 28px;
        }
        form {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>

    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
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

    <div class="container">
        <h2>Join an Organization</h2>
        <form method="get" action="join_organization.php">
            <input type="text" name="search" placeholder="Search organizations..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="join-btn">Search</button>
        </form>
        <div class="org-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="org-item">
                        <?php echo htmlspecialchars($row['name']); ?>
                        <form method="post" action="process_join_org.php" style="display:inline;">
                            <input type="hidden" name="organization_id" value="<?php echo $row['organization_id']; ?>">
                            <button type="submit" class="join-btn">Join</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="not-found">
                    No organizations found.
                </div>
            <?php endif; ?>
        </div>
        <div style="margin-top:30px; text-align: center;">
            <p>Don't see your organization?</p>
            <button class="create-org-btn" onclick="window.location.href='create_organization.php'">Create New Organization</button>
        </div>
    </div>
    <?php include('sidebar.php'); ?>
    <?php include('category_modal.php'); ?>
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