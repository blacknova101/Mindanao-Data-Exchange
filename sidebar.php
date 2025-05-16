<?php
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not authenticated
    header("Location: login.php");
    exit();
}

// Get user data if not already in session
if (!isset($_SESSION['first_name'])) {
    include 'db_connection.php';
    $sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
    }
}

// Get count of pending access requests for this user
$request_count = 0;
if (isset($_SESSION['user_id'])) {
    include_once 'db_connection.php';
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
    include_once 'db_connection.php';
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
?>
<style>
    .sidebar {
        width: 250px;
        height: 100vh;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        position: fixed;
        right: -250px;
        top: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        transition: right 0.3s ease;
        z-index: 1001;
    }

    .sidebar.active {
        right: 0;
    }

    .profile {
        display: flex;
        align-items: center;
        padding-top: 65px;
        padding-left:20px;
        padding-bottom: 20px;
        margin-bottom: 30px;
        background-color:rgba(12, 26, 54, 0.8);
    }


    .profile img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 10px;
    }

    .profile span {
        padding-top: -10px;
        color: #ffffff;
        font-size: 18px;
    }

    .menu-item {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        color: #cfd9ff;
        text-decoration: none;
        transition: background-color 0.3s;
        padding-left: 40px;
    }
    .menu-item i {
        margin-right: 30px; 
    }
    #user_settings{
       padding-left: 0px;
    }
    #my_datasets{
       padding-left: 3px;
    }
    #notifications{
       padding-left: 1px;
    }

    .menu-item:hover {
        background-color: #cce0ff;
        color:rgb(0, 0, 0);
    }

    .menu-item img {
        width: 20px;
        height: 20px;
        margin-right: 10px;
    }

    .sign-out {
        margin-top: auto;
        background-color: #0c1a36;
        padding: 15px 20px;
        color: #ffffff;
        text-decoration: none;
        display: flex;
        align-items: center;
    }

    .sign-out img {
        width: 20px;
        height: 20px;
        margin-right: 10px;
    }

    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        right: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .sidebar-overlay.active {
        display: block;
    }
    
    .notification-badge {
        background-color: #ff3b30;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        margin-left: 5px;
    }
    
    .submenu {
        display: none;
        padding-left: 75px;
    }
    
    .submenu a {
        display: block;
        color: #cfd9ff;
        text-decoration: none;
        padding: 10px 0;
        transition: color 0.3s;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .submenu a:hover {
        color: #0099ff;
    }
    
    .menu-item.active .submenu {
        display: block;
    }
</style>

<div class="sidebar-overlay"></div>
<div class="sidebar">
    <div class="profile">
        <img src="images/avatarIconunknown.jpg" alt="Profile">
        <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
    </div>
    <a href="user_settings.php" class="menu-item">
        <i id="gear"class="fa-solid fa-gear"></i>
        <span id="user_settings">User Settings</i>
    </a>
    
    <a href="create_organization_request.php" class="menu-item">
        <i class="fa-solid fa-building"></i>
        <span>Create Organization</span>
    </a>
    
    <a href="user_org_requests.php" class="menu-item">
        <i class="fa-solid fa-clipboard-check"></i>
        <span>My Org Requests</span>
    </a>
    
    <a href="#" class="menu-item">
        <i class="fa-solid fa-file"></i>
        <i id="my_datasets" style="cursor: pointer;" onclick="window.location.href='mydatasets.php';">My Datasets</i>
    </a>
    
    <a href="#" class="menu-item notifications-menu">
        <i class="fa-solid fa-envelope"></i>
        <i id="notifications">Notifications</i>
        <?php if ($total_count > 0): ?>
            <span class="notification-badge"><?php echo $total_count; ?></span>
        <?php endif; ?>
    </a>
    <div class="submenu">
        <a href="notifications.php">
            All Notifications
            <?php if ($notif_count > 0): ?>
                <span class="notification-badge"><?php echo $notif_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="manage_requests.php">
            Access Requests
            <?php if ($request_count > 0): ?>
                <span class="notification-badge"><?php echo $request_count; ?></span>
            <?php endif; ?>
        </a>
    </div>
    
    <a href="logout.php" class="sign-out">
        <img src="images/signout-icon.png" alt="Sign Out">
        Sign Out
    </a>
</div>
<script src="https://kit.fontawesome.com/2c68a433da.js" crossorigin="anonymous"></script>
<script>
document.querySelector('.profile-icon').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.add('active');
    document.querySelector('.sidebar-overlay').classList.add('active');
});

document.querySelector('.sidebar-overlay').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.remove('active');
    document.querySelector('.sidebar-overlay').classList.remove('active');
});
document.querySelector('.sign-out').addEventListener('click', function() {
    window.location.href = 'mindanaodataexchange.php';
});

// Toggle submenu for notifications
document.querySelector('.notifications-menu').addEventListener('click', function(e) {
    e.preventDefault();
    this.classList.toggle('active');
    const submenu = document.querySelector('.submenu');
    if (this.classList.contains('active')) {
        submenu.style.display = 'block';
    } else {
        submenu.style.display = 'none';
    }
});
</script>


