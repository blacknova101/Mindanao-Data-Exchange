<?php
session_start();
include 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle force logout
if (isset($_POST['force_logout']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $sql = "DELETE FROM user_sessions WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Get analytics data
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetch_assoc()['count'];
$total_datasets = $conn->query("SELECT COUNT(*) as count FROM datasets")->fetch_assoc()['count'];
$total_organizations = $conn->query("SELECT COUNT(*) as count FROM organizations")->fetch_assoc()['count'];
$total_downloads = $conn->query("SELECT SUM(total_downloads) as count FROM dataset_batch_analytics")->fetch_assoc()['count'] ?? 0;

// Get currently online users
$sql = "SELECT u.*, us.last_activity, o.name as org_name 
        FROM users u 
        LEFT JOIN user_sessions us ON u.user_id = us.user_id 
        LEFT JOIN organizations o ON u.organization_id = o.organization_id 
        WHERE us.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
        AND u.is_active = 1 
        ORDER BY us.last_activity DESC";
$online_users = $conn->query($sql);

// Get recent activities
$sql = "SELECT 'upload' as type, d.title, db.created_at, u.first_name, u.last_name, o.name as org_name
        FROM datasets d
        JOIN dataset_batches db ON d.dataset_batch_id = db.dataset_batch_id
        JOIN users u ON d.user_id = u.user_id
        LEFT JOIN organizations o ON u.organization_id = o.organization_id
        WHERE db.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        UNION ALL
        SELECT 'registration' as type, CONCAT(u.first_name, ' ', u.last_name) as title, u.date_joined as created_at, u.first_name, u.last_name, o.name as org_name
        FROM users u
        LEFT JOIN organizations o ON u.organization_id = o.organization_id
        WHERE u.date_joined > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY created_at DESC
        LIMIT 10";
$recent_activities = $conn->query($sql);

// Get user registration data for chart
$user_registrations = $conn->query("
    SELECT DATE(date_joined) as date, COUNT(*) as count
    FROM users
    GROUP BY DATE(date_joined)
    ORDER BY date DESC
    LIMIT 30
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            background: #0c1a36;
            color: white;
            height: 100vh;
            position: fixed;
            padding: 20px;
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
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card i {
            font-size: 2em;
            color: #0099ff;
        }
        .activity-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        
        /* Maintenance task styling */
        .maintenance-task {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-left: 4px solid #0099ff;
        }
        
        .maintenance-task h5 {
            color: #0099ff;
            margin-bottom: 10px;
        }
        
        .maintenance-task p {
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .maintenance-task i {
            margin-right: 8px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 class="mb-4">Admin Panel</h3>
        <nav class="nav flex-column">
            <a class="nav-link active" href="#"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link" href="admin_users.php"><i class="fas fa-users"></i> Users</a>
            <a class="nav-link" href="admin_datasets.php"><i class="fas fa-database"></i> Datasets</a>
            <a class="nav-link" href="admin_organizations.php"><i class="fas fa-building"></i> Organizations</a>
            <a class="nav-link" href="admin_org_requests.php"><i class="fas fa-clipboard-list"></i> Org Requests</a>
            <a class="nav-link" href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a>
            <a class="nav-link" href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a class="nav-link" href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <h2 class="mb-4">Dashboard</h2>

        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-database"></i>
                    <h3><?php echo $total_datasets; ?></h3>
                    <p>Total Datasets</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-building"></i>
                    <h3><?php echo $total_organizations; ?></h3>
                    <p>Organizations</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-download"></i>
                    <h3><?php echo $total_downloads; ?></h3>
                    <p>Total Downloads</p>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Maintenance Tasks -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Maintenance Tasks</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="maintenance-task">
                            <h5><i class="fas fa-clock"></i> Organization Request Expiration</h5>
                            <p>Check for expired organization membership requests and notify users</p>
                            <a href="test_expire_requests.php" class="btn btn-primary btn-sm">Run Expiration Check</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="maintenance-task">
                            <h5><i class="fas fa-tasks"></i> Schedule Automatic Expiration</h5>
                            <p>Set up a scheduled task to automatically check for expired requests daily</p>
                            <a href="setup_expiration_task.bat" class="btn btn-outline-primary btn-sm" download>Download Task Setup Script</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Currently Online Users</h5>
                <button onclick="location.reload()" class="btn btn-sm btn-primary">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Organization</th>
                            <th>Last Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($online_users->num_rows === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center">No users currently online</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($user = $online_users->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($user['org_name'] ?? 'None'); ?></td>
                                <td><?php echo date('M d, Y H:i:s', strtotime($user['last_activity'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <input type="hidden" name="force_logout" value="1">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to force logout this user?')">
                                            <i class="fas fa-sign-out-alt"></i> Force Logout
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="activity-list">
                    <h4>Recent Activities</h4>
                    <?php if ($recent_activities->num_rows === 0): ?>
                        <p>No recent activities found.</p>
                    <?php else: ?>
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                    <p class="mb-0"><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></p>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="activity-list">
                    <h4>User Registrations</h4>
                    <canvas id="userRegistrationsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // User registrations chart
        const ctx = document.getElementById('userRegistrationsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column(array_reverse($user_registrations), 'date')); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_column(array_reverse($user_registrations), 'count')); ?>,
                    borderColor: '#0099ff',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>