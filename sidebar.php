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
</style>

<div class="sidebar-overlay"></div>
<div class="sidebar">
    <div class="profile">
        <img src="images/avatarIconunknown.jpg" alt="Profile">
         <span><?php echo isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Guest'; ?></span> 
         <span><?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Guest'; ?></span> 
    </div>
    <a href="#" class="menu-item">
        <i id="gear"class="fa-solid fa-gear"></i>
        <i id="user_settings">User Settings</i>
    </a>
    
    <a href="#" class="menu-item">
        <i class="fa-solid fa-file"></i>
        <i id="my_datasets" style="cursor: pointer;" onclick="window.location.href='mydataset.php';">My Datasets</i>
    </a>
    
    <a href="#" class="menu-item">
        <i class="fa-solid fa-envelope"></i>
        <i id="notifications">Notifications</i>
    </a>
    
    <a href="#" class="sign-out">
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
</script>


