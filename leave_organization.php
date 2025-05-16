<?php
session_start();
include('db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$transfer_error = "";
$can_leave = true;

// Check if the user belongs to an organization and get organization details
$check_org_sql = "SELECT u.organization_id, o.name, o.created_by, 
                 (o.created_by = ?) AS is_owner
        FROM users u 
                 JOIN organizations o ON u.organization_id = o.organization_id 
        WHERE u.user_id = ?";
$check_org_stmt = $conn->prepare($check_org_sql);
$check_org_stmt->bind_param("ii", $user_id, $user_id);
$check_org_stmt->execute();
$org_result = $check_org_stmt->get_result();

if ($org_result->num_rows === 0) {
    // User doesn't belong to an organization
    $_SESSION['error_message'] = "You don't belong to any organization.";
    header("Location: user_settings.php");
    exit();
}

$org_data = $org_result->fetch_assoc();
$organization_id = $org_data['organization_id'];
$organization_name = $org_data['name'];
$is_owner = $org_data['is_owner'];

// Handle ownership transfer if the user is the owner
if ($is_owner) {
    // Get list of possible users to transfer ownership to
    $get_members_sql = "SELECT user_id, first_name, last_name
                       FROM users 
                       WHERE organization_id = ? AND user_id != ? 
                       ORDER BY date_joined ASC";
    $get_members_stmt = $conn->prepare($get_members_sql);
    $get_members_stmt->bind_param("ii", $organization_id, $user_id);
    $get_members_stmt->execute();
    $members_result = $get_members_stmt->get_result();
    
    if ($members_result->num_rows === 0) {
        // User is the only member of the organization - they can safely leave
        // The organization will be left without an owner
        $can_leave = true;
    } else {
        // There are other members - ownership should be transferred
        $can_leave = false;
        
        // Handle automatic transfer if requested
        if (isset($_POST['auto_transfer']) && $_POST['auto_transfer'] === 'yes') {
            // Find the earliest member to become the new owner
            $members_result->data_seek(0); // Reset result pointer
            $new_owner = $members_result->fetch_assoc();
            $new_owner_id = $new_owner['user_id'];
            
            // Update organization with new owner
            $update_org_sql = "UPDATE organizations SET created_by = ? WHERE organization_id = ?";
            $update_org_stmt = $conn->prepare($update_org_sql);
            $update_org_stmt->bind_param("ii", $new_owner_id, $organization_id);
            
            if ($update_org_stmt->execute()) {
                // Send notification to the new owner
                $notification_message = "You have been automatically assigned as the owner of the organization \"$organization_name\" because the previous owner has left.";
                $notification_type = "org_ownership_transfer";
                
                $notification_sql = "INSERT INTO user_notifications (user_id, message, related_id, notification_type) 
                                   VALUES (?, ?, ?, ?)";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("isis", $new_owner_id, $notification_message, $organization_id, $notification_type);
                $notification_stmt->execute();
                
                $new_owner_name = $new_owner['first_name'] . ' ' . $new_owner['last_name'];
                $_SESSION['success_message'] = "Organization ownership has been transferred to $new_owner_name. You may now leave the organization.";
                $can_leave = true;
            } else {
                $transfer_error = "Failed to transfer ownership. Please try again or transfer ownership manually.";
            }
        }
        // Handle manual transfer if specified
        else if (isset($_POST['new_owner_id'])) {
            $new_owner_id = (int)$_POST['new_owner_id'];
            
            // Make sure the new owner is a member of the organization
            $check_member_sql = "SELECT user_id, first_name, last_name FROM users 
                               WHERE user_id = ? AND organization_id = ?";
            $check_member_stmt = $conn->prepare($check_member_sql);
            $check_member_stmt->bind_param("ii", $new_owner_id, $organization_id);
            $check_member_stmt->execute();
            $check_result = $check_member_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $new_owner = $check_result->fetch_assoc();
                $new_owner_name = $new_owner['first_name'] . ' ' . $new_owner['last_name'];
                
                // Update organization with new owner
                $update_org_sql = "UPDATE organizations SET created_by = ? WHERE organization_id = ?";
                $update_org_stmt = $conn->prepare($update_org_sql);
                $update_org_stmt->bind_param("ii", $new_owner_id, $organization_id);
                
                if ($update_org_stmt->execute()) {
                    // Send notification to the new owner
                    $notification_message = "You have been assigned as the owner of the organization \"$organization_name\".";
                    $notification_type = "org_ownership_transfer";
                    
                    $notification_sql = "INSERT INTO user_notifications (user_id, message, related_id, notification_type) 
                                       VALUES (?, ?, ?, ?)";
                    $notification_stmt = $conn->prepare($notification_sql);
                    $notification_stmt->bind_param("isis", $new_owner_id, $notification_message, $organization_id, $notification_type);
                    $notification_stmt->execute();
                    
                    $_SESSION['success_message'] = "Organization ownership has been transferred to $new_owner_name. You may now leave the organization.";
                    $can_leave = true;
                } else {
                    $transfer_error = "Failed to transfer ownership. Please try again.";
                }
            } else {
                $transfer_error = "Selected user is not a member of the organization.";
            }
        }
    }
}

// Handle the actual leaving of the organization
if (isset($_POST['confirm_leave']) && $_POST['confirm_leave'] === 'yes' && isset($_POST['confirm_text'])) {
    // Check if confirmation text is correct
    if (strtoupper($_POST['confirm_text']) !== 'LEAVE') {
        $_SESSION['error_message'] = "Please type 'LEAVE' to confirm that you want to leave the organization.";
        header("Location: user_settings.php");
        exit();
    }
    
    // Proceed with leaving the organization if the user can leave
    if ($can_leave) {
        // Remove user from organization
        $leave_sql = "UPDATE users SET organization_id = NULL, user_type = 'Normal' WHERE user_id = ?";
        $leave_stmt = $conn->prepare($leave_sql);
        $leave_stmt->bind_param("i", $user_id);
        
        if ($leave_stmt->execute()) {
            $_SESSION['success_message'] = "You have successfully left the organization.";
            
            // Check if there are any members left in the organization
            $check_members_sql = "SELECT COUNT(*) as member_count FROM users WHERE organization_id = ?";
            $check_members_stmt = $conn->prepare($check_members_sql);
            $check_members_stmt->bind_param("i", $organization_id);
            $check_members_stmt->execute();
            $count_result = $check_members_stmt->get_result();
            $member_count = $count_result->fetch_assoc()['member_count'];
            
            if ($member_count == 0) {
                // If no members left, delete the organization
                $delete_org_sql = "DELETE FROM organizations WHERE organization_id = ?";
                $delete_org_stmt = $conn->prepare($delete_org_sql);
                $delete_org_stmt->bind_param("i", $organization_id);
                
                if ($delete_org_stmt->execute()) {
                    $_SESSION['success_message'] = "You have successfully left the organization and it has been deleted since there are no more members.";
                } else {
                    // If deletion fails, just mark it as having no owner
                    $update_org_sql = "UPDATE organizations SET created_by = NULL WHERE organization_id = ?";
                    $update_org_stmt = $conn->prepare($update_org_sql);
                    $update_org_stmt->bind_param("i", $organization_id);
                    $update_org_stmt->execute();
                }
            } else if ($member_count > 0 && $is_owner) {
                // If there are members but the owner is leaving without transferring ownership
                // Mark the organization as having no owner
                $update_org_sql = "UPDATE organizations SET created_by = NULL WHERE organization_id = ?";
                $update_org_stmt = $conn->prepare($update_org_sql);
                $update_org_stmt->bind_param("i", $organization_id);
                $update_org_stmt->execute();
            }
            
            // Update session variables
            $_SESSION['organization_id'] = null;
            $_SESSION['user_type'] = 'Normal';
            
            header("Location: user_settings.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to leave organization. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Organization</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            color: #333;
            margin: 0;
            padding: 0;
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
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #007BFF;
        }
        h2 {
            margin-top: 30px;
        }
        .warning {
            color: #e74c3c;
            font-weight: bold;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            margin-right: 10px;
            text-align: center;
            min-width: 180px;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-primary {
            background-color: #0099ff;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .btn-primary:hover {
            background-color: #0089e6;
        }
        .member-row {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .member-info {
            flex: 1;
        }
        .member-role {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 4px;
            color: white;
            background-color: #6c757d;
            margin-left: 10px;
        }
        .role-admin {
            background-color: #20c997;
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
        
        /* Confirmation form styling */
        form input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 15px;
            width: 300px;
            max-width: 100%;
            font-size: 16px;
        }
        
        /* Improved button alignment */
        form {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        .button-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        @media (max-width: 576px) {
            .btn {
                width: 100%;
                margin-right: 0;
            }
            
            form input[type="text"] {
                width: 100%;
            }
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
            <h2>Leave Organization</h2>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="user_settings.php">SETTINGS</a>
        </nav>
    </header>
    
    <div class="container">
        <h1>Leave Organization: <?php echo htmlspecialchars($organization_name); ?></h1>
        
        <?php if ($transfer_error): ?>
            <div class="alert alert-danger">
                <?php echo $transfer_error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($is_owner && !$can_leave): ?>
            <div class="warning">
                <p>You are currently the owner of this organization. Before leaving, you must transfer ownership to another member.</p>
            </div>
            
            <?php if ($members_result->num_rows > 0): ?>
                <h2>Transfer Ownership</h2>
                <p>Select a member to transfer ownership to:</p>
                
                <form method="POST">
                    <?php 
                    $members_result->data_seek(0); // Reset result pointer 
                    while ($member = $members_result->fetch_assoc()):
                    ?>
                        <div class="member-row">
                            <div class="member-info">
                                <input type="radio" name="new_owner_id" value="<?php echo $member['user_id']; ?>" id="member_<?php echo $member['user_id']; ?>" required>
                                <label for="member_<?php echo $member['user_id']; ?>">
                                    <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                    <span class="member-role">Member</span>
                                </label>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <div class="button-container">
                        <button type="submit" class="btn btn-primary">Transfer Ownership</button>
                    </div>
                </form>
                
                <h2>Automatic Transfer</h2>
                <p>Alternatively, the system can automatically transfer ownership to the earliest member:</p>
                
                <form method="POST">
                    <input type="hidden" name="auto_transfer" value="yes">
                    <div class="button-container">
                        <button type="submit" class="btn btn-secondary">Auto-Transfer Ownership</button>
                    </div>
                </form>
            <?php else: ?>
                <p>There are no other members in this organization. If you leave, the organization will be without an owner.</p>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($can_leave): ?>
            <h2>Confirm Departure</h2>
            <p>Are you sure you want to leave the organization "<?php echo htmlspecialchars($organization_name); ?>"?</p>
            <p class="warning">This action cannot be undone. You will need to request to join the organization again if you change your mind.</p>
            
            <form method="POST">
                <input type="hidden" name="confirm_leave" value="yes">
                <input type="text" name="confirm_text" placeholder="Type 'LEAVE' to confirm" required>
                <div class="button-container">
                    <button type="submit" class="btn btn-danger">Leave Organization</button>
                    <a href="user_settings.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
