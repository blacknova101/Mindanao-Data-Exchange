<style>
    .sidebar {
        width: 250px;
        height: 100vh;
        background-color: #e6f2ff;
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
        padding: 20px;
        margin-bottom: 30px;
        background-color: #0c1a36;
    }

    .profile img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 10px;
    }

    .profile span {
        color: #ffffff;
        font-size: 18px;
    }

    .menu-item {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        color: #333;
        text-decoration: none;
        transition: background-color 0.3s;
    }

    .menu-item:hover {
        background-color: #cce0ff;
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
</style>

<div class="sidebar-overlay"></div>
<div class="sidebar">
    <div class="profile">
        <img src="images/avatarIconunknown.jpg" alt="Profile">
        <span>Onkan</span>
    </div>
    
    <a href="#" class="menu-item">
        <img src="images/settings-icon.png" alt="Settings">
        User Settings
    </a>
    
    <a href="#" class="menu-item">
        <img src="images/dataset-icon.png" alt="Datasets">
        My Datasets
    </a>
    
    <a href="#" class="menu-item">
        <img src="images/notification-icon.png" alt="Notifications">
        Notifications
    </a>
    
    <a href="#" class="sign-out">
        <img src="images/signout-icon.png" alt="Sign Out">
        Sign Out
    </a>
</div>

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
</script>
