<?php
session_start();
include 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_query = "SELECT * FROM administrator WHERE admin_id = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin = $admin_result->fetch_assoc();

// Handle password change
$password_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (password_verify($current_password, $admin['password_hash'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 8) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_query = "UPDATE administrator SET password_hash = ? WHERE admin_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $new_password_hash, $admin_id);
                
                if ($update_stmt->execute()) {
                    $password_message = '<div class="alert alert-success">Password updated successfully!</div>';
                } else {
                    $password_message = '<div class="alert alert-danger">Error updating password. Please try again.</div>';
                }
            } else {
                $password_message = '<div class="alert alert-danger">New password must be at least 8 characters long.</div>';
            }
        } else {
            $password_message = '<div class="alert alert-danger">New passwords do not match.</div>';
        }
    } else {
        $password_message = '<div class="alert alert-danger">Current password is incorrect.</div>';
    }
}

// Handle email change
$email_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_email'])) {
    $new_email = $_POST['new_email'];
    $password = $_POST['email_password'];
    
    // Verify password
    if (password_verify($password, $admin['password_hash'])) {
        // Check if email is already used
        $check_query = "SELECT * FROM administrator WHERE email = ? AND admin_id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("si", $new_email, $admin_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            $update_query = "UPDATE administrator SET email = ? WHERE admin_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $new_email, $admin_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['admin_email'] = $new_email;
                $email_message = '<div class="alert alert-success">Email updated successfully!</div>';
                
                // Refresh admin info
                $stmt->execute();
                $admin_result = $stmt->get_result();
                $admin = $admin_result->fetch_assoc();
            } else {
                $email_message = '<div class="alert alert-danger">Error updating email. Please try again.</div>';
            }
        } else {
            $email_message = '<div class="alert alert-danger">This email is already in use by another administrator.</div>';
        }
    } else {
        $email_message = '<div class="alert alert-danger">Password is incorrect.</div>';
    }
}

// Get system settings
$settings = [
    'site_name' => 'Mindanao Data Exchange',
    'version' => '1.0',
    'admin_email' => 'admin@example.com',
    'max_upload_size' => '50MB',
    'allow_registrations' => true,
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Mindanao Data Exchange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: #0c1a36;
            color: white;
            height: 100vh;
            position: fixed;
            padding: 20px;
            width: 250px;
        }
        .sidebar .nav-link {
            color: white;
            padding: 10px 20px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
        }
        .sidebar .nav-link.active {
            background: #0099ff;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 class="mb-4">Admin Panel</h3>
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link" href="admin_users.php"><i class="fas fa-users"></i> Users</a>
            <a class="nav-link" href="admin_datasets.php"><i class="fas fa-database"></i> Datasets</a>
            <a class="nav-link" href="admin_organizations.php"><i class="fas fa-building"></i> Organizations</a>
            <a class="nav-link" href="admin_org_requests.php"><i class="fas fa-clipboard-list"></i> Org Requests</a>
            <a class="nav-link" href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a>
            <a class="nav-link active" href="#"><i class="fas fa-cog"></i> Settings</a>
            <a class="nav-link" href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <h2 class="mb-4">Admin Settings</h2>
        
        <div class="row">
            <div class="col-md-6">
                <!-- Account Settings -->
                <div class="settings-card">
                    <h3 class="mb-4"><i class="fas fa-user-cog"></i> Account Settings</h3>
                    
                    <div class="mb-4">
                        <h5>Admin Information</h5>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($admin['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?></p>
                        <p><strong>Account Created:</strong> <?php echo date('M d, Y', strtotime($admin['created_at'])); ?></p>
                        <p><strong>Last Login:</strong> <?php echo $admin['last_login'] ? date('M d, Y g:i A', strtotime($admin['last_login'])) : 'N/A'; ?></p>
                    </div>
                    
                    <!-- Change Password Form -->
                    <div class="mb-4">
                        <h5>Change Password</h5>
                        <?php echo $password_message; ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required 
                                       pattern=".{8,}" title="Password must be at least 8 characters long">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Update Password
                            </button>
                        </form>
                    </div>
                    
                    <!-- Change Email Form -->
                    <div>
                        <h5>Change Email</h5>
                        <?php echo $email_message; ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="new_email" class="form-label">New Email Address</label>
                                <input type="email" class="form-control" id="new_email" name="new_email" required>
                            </div>
                            <div class="mb-3">
                                <label for="email_password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="email_password" name="email_password" required>
                            </div>
                            <button type="submit" name="change_email" class="btn btn-primary">
                                <i class="fas fa-envelope"></i> Update Email
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- System Settings -->
                <div class="settings-card">
                    <h3 class="mb-4"><i class="fas fa-server"></i> System Information</h3>
                    
                    <div class="mb-4">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th>System Name</th>
                                    <td><?php echo htmlspecialchars($settings['site_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Version</th>
                                    <td><?php echo htmlspecialchars($settings['version']); ?></td>
                                </tr>
                                <tr>
                                    <th>PHP Version</th>
                                    <td><?php echo phpversion(); ?></td>
                                </tr>
                                <tr>
                                    <th>MySQL Version</th>
                                    <td><?php echo $conn->server_info; ?></td>
                                </tr>
                                <tr>
                                    <th>Max Upload Size</th>
                                    <td><?php echo htmlspecialchars($settings['max_upload_size']); ?></td>
                                </tr>
                                <tr>
                                    <th>Server Time</th>
                                    <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Database Stats -->
                    <div class="mb-4">
                        <h5>Database Statistics</h5>
                        <table class="table">
                            <tbody>
                                <?php 
                                $tables = [
                                    'Users' => 'users',
                                    'Datasets' => 'datasets',
                                    'Organizations' => 'organizations',
                                    'Comments' => 'datasetcomments',
                                    'Notifications' => 'notifications'
                                ];
                                
                                foreach ($tables as $label => $table) {
                                    $count_query = "SELECT COUNT(*) as count FROM $table";
                                    $count_result = $conn->query($count_query);
                                    $count = 0;
                                    
                                    if ($count_result) {
                                        $count = $count_result->fetch_assoc()['count'];
                                    }
                                    
                                    echo "<tr>
                                        <th>Total $label</th>
                                        <td>$count</td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div>
                        <h5>System Maintenance</h5>
                        <div class="d-grid gap-3">
                            <a href="#" class="btn btn-outline-primary">
                                <i class="fas fa-database"></i> Backup Database
                            </a>
                            <a href="#" class="btn btn-outline-secondary">
                                <i class="fas fa-broom"></i> Clear Cache
                            </a>
                            <a href="#" class="btn btn-outline-warning">
                                <i class="fas fa-file-archive"></i> Optimize Database
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 