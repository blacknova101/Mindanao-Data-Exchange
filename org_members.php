<?php
session_start();
include('db_connection.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if the user is part of an organization and is an owner or moderator
$check_access_sql = "SELECT u.organization_id, o.name, o.created_by, 
                     (o.created_by = ?) AS is_owner
                    FROM users u 
                    JOIN organizations o ON u.organization_id = o.organization_id 
                    WHERE u.user_id = ? AND o.created_by = ?";
$check_access_stmt = $conn->prepare($check_access_sql);
$check_access_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$check_access_stmt->execute();
$access_result = $check_access_stmt->get_result();

if ($access_result->num_rows === 0) {
    // If not the owner, check if the user is just a regular member
    $check_member_sql = "SELECT u.organization_id, o.name, o.created_by, 
                          (o.created_by = ?) AS is_owner
                         FROM users u 
                         JOIN organizations o ON u.organization_id = o.organization_id 
                         WHERE u.user_id = ?";
    $check_member_stmt = $conn->prepare($check_member_sql);
    $check_member_stmt->bind_param("ii", $user_id, $user_id);
    $check_member_stmt->execute();
    $member_result = $check_member_stmt->get_result();
    
    if ($member_result->num_rows === 0) {
        // Not authorized
        $_SESSION['error_message'] = "You do not have permission to manage organization members.";
        header("Location: user_settings.php");
        exit();
    }
    
    $access_data = $member_result->fetch_assoc();
    $organization_id = $access_data['organization_id'];
    $organization_name = $access_data['name'];
    $is_owner = $access_data['is_owner'];
    $is_admin = false; // Regular member, not an admin
} else {
    $access_data = $access_result->fetch_assoc();
    $organization_id = $access_data['organization_id'];
    $organization_name = $access_data['name'];
    $is_owner = $access_data['is_owner'];
    $is_admin = true; // Owner is also an admin
}

// Handle member role changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['member_id'])) {
        $action = $_POST['action'];
        $member_id = (int)$_POST['member_id'];
        
        // Security check: Prevent users from modifying their own permissions
        if ($member_id === $user_id) {
            $_SESSION['error_message'] = "You cannot modify your own permissions.";
        } 
        // Only owners can promote/demote moderators
        else if ($action === 'promote' || $action === 'demote') {
            if (!$is_owner) {
                $_SESSION['error_message'] = "Only the organization owner can change moderator status.";
            } else {
                // Instead of setting is_org_admin, we'll track moderators in a different way
                // For now, we'll show a message indicating this feature is currently being revised
                $_SESSION['error_message'] = "Moderator management is currently being updated. Please check back later.";
                
                // For future implementation:
                // We should create a separate table 'organization_roles' to track moderators
                // This would allow us to properly implement this feature
            }
        } 
        // Handle member removal (only owners and moderators can do this)
        else if ($action === 'remove') {
            if (!$is_owner && !$is_admin) {
                $_SESSION['error_message'] = "You do not have permission to remove members.";
            } else {
                // Get member's info to check if they're the owner
                $check_member_sql = "SELECT (o.created_by = u.user_id) AS is_owner 
                                     FROM users u 
                                     JOIN organizations o ON u.organization_id = o.organization_id 
                                     WHERE u.user_id = ? AND u.organization_id = ?";
                $check_member_stmt = $conn->prepare($check_member_sql);
                $check_member_stmt->bind_param("ii", $member_id, $organization_id);
                $check_member_stmt->execute();
                $member_result = $check_member_stmt->get_result();
                
                if ($member_result->num_rows > 0) {
                    $member_data = $member_result->fetch_assoc();
                    $member_is_owner = $member_data['is_owner'];
                    
                    // Regular admins cannot remove the owner, and no one can remove the owner 
                    if ($member_is_owner) {
                        $_SESSION['error_message'] = "The organization owner cannot be removed.";
                    } else {
                        // Remove member from organization
                        $update_sql = "UPDATE users SET organization_id = NULL, user_type = 'Normal' 
                                     WHERE user_id = ? AND organization_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("ii", $member_id, $organization_id);
                        
                        if ($update_stmt->execute()) {
                            $_SESSION['success_message'] = "Member has been removed from the organization.";
                            
                            // Send notification to the member
                            $notification_message = "You have been removed from the organization \"$organization_name\".";
                            $notification_type = "org_removal";
                            
                            $notification_sql = "INSERT INTO user_notifications (user_id, message, related_id, notification_type) 
                                               VALUES (?, ?, ?, ?)";
                            $notification_stmt = $conn->prepare($notification_sql);
                            $notification_stmt->bind_param("isis", $member_id, $notification_message, $organization_id, $notification_type);
                            $notification_stmt->execute();
                        } else {
                            $_SESSION['error_message'] = "Failed to remove member.";
                        }
                    }
                } else {
                    $_SESSION['error_message'] = "Member not found in your organization.";
                }
            }
        }
        // Handle transferring ownership
        else if ($action === 'make_owner' && $is_owner) {
            // Update organization record to set new owner
            $update_org_sql = "UPDATE organizations SET created_by = ? WHERE organization_id = ? AND created_by = ?";
            $update_org_stmt = $conn->prepare($update_org_sql);
            $update_org_stmt->bind_param("iii", $member_id, $organization_id, $user_id);
            
            if ($update_org_stmt->execute() && $conn->affected_rows > 0) {
                $_SESSION['success_message'] = "Organization ownership has been transferred.";
                
                // Send notification to the new owner
                $notification_message = "You are now the owner of the organization \"$organization_name\".";
                $notification_type = "org_ownership_transfer";
                
                $notification_sql = "INSERT INTO user_notifications (user_id, message, related_id, notification_type) 
                                   VALUES (?, ?, ?, ?)";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("isis", $member_id, $notification_message, $organization_id, $notification_type);
                $notification_stmt->execute();
                
                // Redirect after ownership transfer
                header("Location: org_members.php");
                exit();
            } else {
                $_SESSION['error_message'] = "Failed to transfer ownership.";
            }
        }
    }
}

// Fetch all members of the organization
$members_sql = "
    SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.date_joined,
        u.last_login,
        (o.created_by = u.user_id) AS is_owner
    FROM 
        users u
    JOIN 
        organizations o ON u.organization_id = o.organization_id
    WHERE 
        u.organization_id = ?
    ORDER BY 
        is_owner DESC,
        u.last_name ASC,
        u.first_name ASC
";

$members_stmt = $conn->prepare($members_sql);
$members_stmt->bind_param("i", $organization_id);
$members_stmt->execute();
$members_result = $members_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Members - <?php echo htmlspecialchars($organization_name); ?></title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            color: #333;
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
        .nav-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-size: 18px;
            transition: transform 0.3s ease;
        }
        .nav-links a:hover {
            transform: scale(1.2);
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #007BFF;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .members-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }
        .members-table th, .members-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        .members-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #007BFF;
        }
        .members-table tr:hover {
            background-color: #f1f1f1;
        }
        .btn {
            padding: 6px 12px;
            margin-right: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            color: white;
            display: inline-block;
        }
        .btn-promote {
            background-color: #28a745;
        }
        .btn-promote:hover {
            background-color: #218838;
        }
        .btn-demote {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-demote:hover {
            background-color: #e0a800;
        }
        .btn-remove {
            background-color: #dc3545;
        }
        .btn-remove:hover {
            background-color: #c82333;
        }
        .btn-transfer {
            background-color: #17a2b8;
        }
        .btn-transfer:hover {
            background-color: #138496;
        }
        .role-tag {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }
        .role-owner {
            background-color: #8557D3;
            color: white;
        }
        .role-moderator {
            background-color: #20c997;
            color: white;
        }
        .role-member {
            background-color: #6c757d;
            color: white;
        }
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            background-color: #e9ecef;
            object-fit: cover;
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
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 8px;
            width: 50%;
            max-width: 500px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }
        .modal-buttons button {
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>
    
    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mindanao Data Exchange Logo">
            <h2>Organization Members</h2>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="user_settings.php">SETTINGS</a>
            <a href="manage_org_requests.php">REQUESTS</a>
        </nav>
    </header>
    
    <div class="container">
        <h1>Members of <?php echo htmlspecialchars($organization_name); ?></h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']); 
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']); 
                ?>
            </div>
        <?php endif; ?>
        
        <div class="member-info">
            <p>
                <strong>Organization Roles:</strong>
                <br>
                <span class="role-tag role-owner">Owner</span> - Can manage all aspects of the organization, transfer ownership, and manage members.
                <br>
                <span class="role-tag role-member">Member</span> - Regular organization member with standard permissions.
            </p>
        </div>
        
        <?php if ($members_result->num_rows > 0): ?>
            <table class="members-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">Member</th>
                        <th style="width: 15%;">Role</th>
                        <th style="width: 15%;">Joined</th>
                        <th style="width: 15%;">Last Active</th>
                        <th style="width: 25%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($member = $members_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <img src="images/avatarIconunknown.jpg" alt="Profile" class="user-avatar">
                                    <div>
                                        <strong><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></strong>
                                        <br>
                                        <small><?php echo htmlspecialchars($member['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($member['is_owner']): ?>
                                    <span class="role-tag role-owner">Owner</span>
                                <?php else: ?>
                                    <span class="role-tag role-member">Member</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($member['date_joined'])); ?>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($member['last_login'])); ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($is_owner && $member['user_id'] != $user_id): ?>
                                        <?php if (!$member['is_owner']): ?>
                                            <button type="button" class="btn btn-remove" onclick="confirmRemove(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>')">Remove</button>
                                            
                                            <button type="button" class="btn btn-transfer" onclick="confirmTransfer(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>')">Make Owner</button>
                                        <?php endif; ?>
                                    <?php elseif ($is_admin && !$member['is_owner'] && $member['user_id'] != $user_id): ?>
                                        <button type="button" class="btn btn-remove" onclick="confirmRemove(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>')">Remove</button>
                                    <?php elseif ($member['user_id'] == $user_id): ?>
                                        <span class="action-note">This is you</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-members">
                <p>There are no members in your organization yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Remove Member Confirmation Modal -->
    <div id="removeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModals()">&times;</span>
            <h3>Confirm Member Removal</h3>
            <p id="removeMessage">Are you sure you want to remove this member from your organization?</p>
            <form id="removeForm" method="POST">
                <input type="hidden" name="member_id" id="removeMemberId">
                <input type="hidden" name="action" value="remove">
                <div class="modal-buttons">
                    <button type="button" class="btn" style="background-color: #6c757d;" onclick="closeModals()">Cancel</button>
                    <button type="submit" class="btn btn-remove">Remove Member</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Transfer Ownership Confirmation Modal -->
    <div id="transferModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModals()">&times;</span>
            <h3>Transfer Organization Ownership</h3>
            <p id="transferMessage">Are you sure you want to transfer ownership of this organization?</p>
            <p><strong>Warning:</strong> Once you transfer ownership, you will no longer be the owner and cannot reverse this action yourself. The new owner will have full control over the organization.</p>
            <form id="transferForm" method="POST">
                <input type="hidden" name="member_id" id="transferMemberId">
                <input type="hidden" name="action" value="make_owner">
                <div class="modal-buttons">
                    <button type="button" class="btn" style="background-color: #6c757d;" onclick="closeModals()">Cancel</button>
                    <button type="submit" class="btn btn-transfer">Transfer Ownership</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show remove confirmation modal
        function confirmRemove(memberId, memberName) {
            document.getElementById('removeMemberId').value = memberId;
            document.getElementById('removeMessage').textContent = 
                `Are you sure you want to remove ${memberName} from your organization?`;
            document.getElementById('removeModal').style.display = 'block';
        }
        
        // Show transfer confirmation modal
        function confirmTransfer(memberId, memberName) {
            document.getElementById('transferMemberId').value = memberId;
            document.getElementById('transferMessage').textContent = 
                `Are you sure you want to transfer ownership of this organization to ${memberName}?`;
            document.getElementById('transferModal').style.display = 'block';
        }
        
        // Close all modals
        function closeModals() {
            document.getElementById('removeModal').style.display = 'none';
            document.getElementById('transferModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                closeModals();
            }
        }
    </script>
</body>
</html> 