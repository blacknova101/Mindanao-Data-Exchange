<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user is already part of an organization
$check_user_sql = "SELECT organization_id FROM users WHERE user_id = ?";
$check_user_stmt = $conn->prepare($check_user_sql);
$check_user_stmt->bind_param("i", $user_id);
$check_user_stmt->execute();
$user_result = $check_user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$has_organization = !empty($user_data['organization_id']);

// Check if user already has a pending request
$check_pending_sql = "SELECT omr.*, o.name as organization_name 
                     FROM organization_membership_requests omr 
                     JOIN organizations o ON omr.organization_id = o.organization_id 
                     WHERE omr.user_id = ? AND omr.status = 'Pending'";
$check_pending_stmt = $conn->prepare($check_pending_sql);
$check_pending_stmt->bind_param("i", $user_id);
$check_pending_stmt->execute();
$pending_result = $check_pending_stmt->get_result();
$has_pending_request = ($pending_result->num_rows > 0);
$pending_request = $has_pending_request ? $pending_result->fetch_assoc() : null;

// Only search for organizations if user doesn't have an organization or pending request
$organizations = [];
if (!$has_organization && !$has_pending_request) {
    // Handle search
    $search = $_GET['search'] ?? '';
    $sql = "SELECT organization_id, name FROM organizations WHERE name LIKE ? AND created_by IS NOT NULL";
    $stmt = $conn->prepare($sql);
    $searchParam = '%' . $search . '%';
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join an Organization</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
            align-items: center;
            justify-content: center;
        }
        
        /* Join Modal specific styles */
        #joinModal {
            z-index: 1001; /* Higher than category modal */
        }
        
        #joinModal .modal-content {
            background: #fff;
            border: 2px solid #0099ff;
            color: #333;
            box-shadow: 0 0 20px rgba(0, 153, 255, 0.2);
            margin: 15% auto;
            padding: 30px;
            border-radius: 16px;
            width: 450px;
            max-width: 90%;
            text-align: center;
            position: relative;
        }
        
        #joinModal h3 {
            margin-top: 0;
            color: #0099ff;
            margin-bottom: 20px;
        }
        
        #joinModal .close {
            position: absolute;
            top: 15px;
            right: 20px;
            color: #999;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        #joinModal .close:hover {
            color: #0099ff;
        }
        
        .message-field {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px;
            border: 1.5px solid #0099ff;
            border-radius: 5px;
            min-height: 80px;
            resize: vertical;
            font-family: inherit;
            box-sizing: border-box;
            background: #fff;
        }
        
        .modal-btn {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
            min-width: 120px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 5px;
            line-height: 1;
        }
        
        .cancel-btn {
            background: #fff;
            color: #0099ff;
            border: 1.5px solid #0099ff;
            transition: all 0.3s ease;
        }
        
        .cancel-btn:hover {
            background: #0099ff;
            color: #fff;
        }
        
        .submit-btn {
            background: #0099ff;
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: #007acc;
        }
        
        .modal-buttons {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            width: 100%;
        }
        
        /* Responsive styles for small screens */
        @media (max-width: 480px) {
            .modal-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .modal-btn {
                width: 100%;
                margin: 3px 0;
            }
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-info p {
            margin: 10px 0;
        }
        
        .alert-info a {
            color: #005085;
            text-decoration: underline;
        }
        
        .alert-info .btn {
            display: inline-block;
            margin-top: 10px;
            background-color: #0099ff;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .alert-info .btn:hover {
            background-color: #007acc;
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
        
        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if($has_organization): ?>
            <div class="alert alert-info">
                <p>You are already a member of an organization. You must leave your current organization before joining another one.</p>
                <p><a href="user_settings.php" class="btn">Back to User Settings</a></p>
            </div>
        <?php elseif($has_pending_request): ?>
            <div class="alert alert-info">
                <p>You already have a pending request to join <strong><?php echo htmlspecialchars($pending_request['organization_name']); ?></strong>.</p>
                <p>Your request was submitted on <?php echo date('M j, Y', strtotime($pending_request['request_date'])); ?>.</p>
                <?php if(!empty($pending_request['expiration_date'])): ?>
                    <p>Your request will expire on <?php echo date('M j, Y', strtotime($pending_request['expiration_date'])); ?> if not processed.</p>
                <?php endif; ?>
                <p>Please wait for the organization admin to review your request or cancel it from your <a href="user_settings.php">User Settings</a> page if you wish to request joining a different organization.</p>
            </div>
        <?php else: ?>
            <p>Note: When you request to join an organization, an admin will need to approve your request before you become a member.</p>
            
            <form method="get" action="join_organization.php">
                <input type="text" name="search" placeholder="Search organizations..." value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
                <button type="submit" class="join-btn">Search</button>
            </form>
            <div class="org-list">
                <?php if (isset($result) && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="org-item">
                            <?php echo htmlspecialchars($row['name']); ?>
                            <button type="button" class="join-btn" onclick="openJoinModal(<?php echo $row['organization_id']; ?>, '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>')">
                                Request to Join
                            </button>
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
                <button class="create-org-btn" onclick="checkPendingOrgRequests()">Request New Organization</button>
                <div id="org-request-error" class="alert alert-error" style="display: none; margin-top: 15px;">
                    You already have a pending organization creation request. Please wait for administrator approval or check your request status in <a href="user_org_requests.php">My Org Requests</a>.
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Join Organization Modal -->
    <div id="joinModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeJoinModal()">&times;</span>
            <h3>Request to Join <span id="modalOrgName"></span></h3>
            <p>Please provide a brief message about why you want to join this organization.</p>
            
            <form method="post" action="process_join_org.php" id="joinForm">
                <input type="hidden" name="organization_id" id="modalOrgId">
                <textarea name="message" class="message-field" placeholder="Your message to the organization admins..." required></textarea>
                
                <div class="modal-buttons">
                    <button type="button" class="modal-btn cancel-btn" onclick="closeJoinModal()">Cancel</button>
                    <button type="submit" class="modal-btn submit-btn">Submit Request</button>
                </div>
            </form>
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
        
        // Join modal functions
        function openJoinModal(orgId, orgName) {
            document.getElementById("modalOrgId").value = orgId;
            document.getElementById("modalOrgName").textContent = orgName;
            document.getElementById("joinModal").style.display = "flex";
        }
        
        function closeJoinModal() {
            document.getElementById("joinModal").style.display = "none";
            document.getElementById("joinForm").reset();
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const joinModal = document.getElementById("joinModal");
            if (event.target == joinModal) {
                closeJoinModal();
            }
            
            const categoryModal = document.getElementById("categoryModal");
            if (event.target == categoryModal) {
                hideModal();
            }
        }
        
        // Allow Escape key to close modals
        document.addEventListener("keydown", function(event) {
            if (event.key === "Escape") {
                closeJoinModal();
                hideModal();
            }
        });
        
        // Check if user has pending organization creation requests
        function checkPendingOrgRequests() {
            // Make an AJAX request to check for pending org creation requests
            fetch('check_pending_org_requests.php')
                .then(response => response.json())
                .then(data => {
                    if (data.has_pending_request) {
                        // Show error message
                        document.getElementById('org-request-error').style.display = 'block';
                        // Scroll to the error message
                        document.getElementById('org-request-error').scrollIntoView({ behavior: 'smooth' });
                    } else {
                        // Redirect to create organization request page
                        window.location.href = 'create_organization_request.php';
                    }
                })
                .catch(error => {
                    console.error('Error checking pending requests:', error);
                    // Redirect anyway if there's an error with the check
                    window.location.href = 'create_organization_request.php';
                });
        }
    </script>
</body>
</html>