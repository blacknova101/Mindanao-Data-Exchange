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

// Get total counts for dashboard stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_active_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetch_assoc()['count'];
$total_datasets = $conn->query("SELECT COUNT(*) as count FROM dataset_batches")->fetch_assoc()['count'];
$total_organizations = $conn->query("SELECT COUNT(*) as count FROM organizations")->fetch_assoc()['count'];
$total_categories = $conn->query("SELECT COUNT(*) as count FROM datasetcategories")->fetch_assoc()['count'];

// Get total views and downloads
$total_views = $conn->query("SELECT SUM(total_views) as count FROM dataset_batch_analytics")->fetch_assoc()['count'] ?? 0;
$total_downloads = $conn->query("SELECT SUM(total_downloads) as count FROM dataset_batch_analytics")->fetch_assoc()['count'] ?? 0;

// Get recent datasets (last 5)
$sql = "SELECT 
            db.dataset_batch_id,
            d.dataset_id,
            d.title,
            db.visibility,
            db.created_at,
            u.first_name,
            u.last_name,
            c.name AS category_name
        FROM dataset_batches db
        JOIN datasets d ON d.dataset_batch_id = db.dataset_batch_id
        JOIN users u ON db.user_id = u.user_id
        LEFT JOIN datasetcategories c ON d.category_id = c.category_id
        WHERE d.dataset_id = (
            SELECT MIN(dataset_id) FROM datasets WHERE dataset_batch_id = db.dataset_batch_id
        )
        ORDER BY db.created_at DESC
        LIMIT 5";
$recent_datasets = $conn->query($sql);

// Get recent users (last 5)
$sql = "SELECT u.*, o.name AS organization_name 
        FROM users u 
        LEFT JOIN organizations o ON u.organization_id = o.organization_id 
        ORDER BY u.date_joined DESC 
        LIMIT 5";
$recent_users = $conn->query($sql);

// Get recent organizations (last 5)
$sql = "SELECT o.*, COUNT(u.user_id) as member_count 
        FROM organizations o
        LEFT JOIN users u ON o.organization_id = u.organization_id
        GROUP BY o.organization_id
        ORDER BY o.created_at DESC
        LIMIT 5";
$recent_organizations = $conn->query($sql);

// Get public vs private datasets ratio
$public_datasets = $conn->query("SELECT COUNT(*) as count FROM dataset_batches WHERE visibility = 'Public'")->fetch_assoc()['count'];
$private_datasets = $conn->query("SELECT COUNT(*) as count FROM dataset_batches WHERE visibility = 'Private'")->fetch_assoc()['count'];

// Get top categories by dataset count
$sql = "SELECT c.name, COUNT(d.dataset_id) as dataset_count
        FROM datasetcategories c
        JOIN datasets d ON c.category_id = d.category_id
        GROUP BY c.category_id
        ORDER BY dataset_count DESC
        LIMIT 5";
$top_categories = $conn->query($sql);

// Get organizations with most members
$sql = "SELECT o.name, COUNT(u.user_id) as member_count
        FROM organizations o
        JOIN users u ON o.organization_id = u.organization_id
        GROUP BY o.organization_id
        ORDER BY member_count DESC
        LIMIT 5";
$top_organizations = $conn->query($sql);
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
                    <i class="fas fa-eye"></i>
                    <h3><?php echo $total_views; ?></h3>
                    <p>Total Views</p>
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

        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-building"></i>
                    <h3><?php echo $total_organizations; ?></h3>
                    <p>Organizations</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-tags"></i>
                    <h3><?php echo $total_categories; ?></h3>
                    <p>Categories</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-user-check"></i>
                    <h3><?php echo $total_active_users; ?></h3>
                    <p>Active Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-lock"></i>
                    <h3><?php echo $public_datasets; ?> / <?php echo $private_datasets; ?></h3>
                    <p>Public / Private</p>
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

        <div class="row">
            <!-- Recent Datasets -->
            <div class="col-md-6">
                <div class="widget">
                    <div class="widget-title">
                        <i class="fas fa-database"></i> Recent Datasets
                    </div>
                    <div class="widget-body">
                        <?php if ($recent_datasets->num_rows === 0): ?>
                            <p class="text-muted">No datasets found</p>
                        <?php else: ?>
                            <?php while ($dataset = $recent_datasets->fetch_assoc()): ?>
                                <div class="recent-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($dataset['title']); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($dataset['first_name'] . ' ' . $dataset['last_name']); ?> |
                                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($dataset['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <span class="badge <?php echo $dataset['visibility'] == 'Public' ? 'badge-public' : 'badge-private'; ?>">
                                                <?php echo $dataset['visibility']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <div class="text-center mt-3">
                                <a href="admin_datasets.php" class="btn btn-sm btn-primary">View All Datasets</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="col-md-6">
                <div class="widget">
                    <div class="widget-title">
                        <i class="fas fa-users"></i> Recent Users
                    </div>
                    <div class="widget-body">
                        <?php if ($recent_users->num_rows === 0): ?>
                            <p class="text-muted">No users found</p>
                        <?php else: ?>
                            <?php while ($user = $recent_users->fetch_assoc()): ?>
                                <div class="recent-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?> | 
                                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($user['date_joined'])); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <span class="badge <?php echo $user['is_active'] ? 'badge-public' : 'badge-private'; ?>">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <div class="text-center mt-3">
                                <a href="admin_users.php" class="btn btn-sm btn-primary">View All Users</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Analytics Widgets Row -->
        <div class="row">
            <!-- Top Categories -->
            <div class="col-md-6">
                <div class="widget">
                    <div class="widget-title">
                        <i class="fas fa-tags"></i> Top Categories
                    </div>
                    <div class="widget-body">
                        <?php if ($top_categories->num_rows === 0): ?>
                            <p class="text-muted">No categories found</p>
                        <?php else: ?>
                            <?php 
                            $max_count = 0;
                            $categories = [];
                            while ($category = $top_categories->fetch_assoc()) {
                                $categories[] = $category;
                                if ($category['dataset_count'] > $max_count) {
                                    $max_count = $category['dataset_count'];
                                }
                            }
                            ?>
                            
                            <?php foreach ($categories as $category): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                                        <span><?php echo $category['dataset_count']; ?> datasets</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?php echo ($category['dataset_count'] / $max_count) * 100; ?>%" 
                                             aria-valuenow="<?php echo $category['dataset_count']; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="<?php echo $max_count; ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Top Organizations -->
            <div class="col-md-6">
                <div class="widget">
                    <div class="widget-title">
                        <i class="fas fa-building"></i> Top Organizations
                    </div>
                    <div class="widget-body">
                        <?php if ($top_organizations->num_rows === 0): ?>
                            <p class="text-muted">No organizations found</p>
                        <?php else: ?>
                            <?php 
                            $max_count = 0;
                            $orgs = [];
                            while ($org = $top_organizations->fetch_assoc()) {
                                $orgs[] = $org;
                                if ($org['member_count'] > $max_count) {
                                    $max_count = $org['member_count'];
                                }
                            }
                            ?>
                            
                            <?php foreach ($orgs as $org): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><?php echo htmlspecialchars($org['name']); ?></span>
                                        <span><?php echo $org['member_count']; ?> members</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo ($org['member_count'] / $max_count) * 100; ?>%" 
                                             aria-valuenow="<?php echo $org['member_count']; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="<?php echo $max_count; ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="admin_organizations.php" class="btn btn-sm btn-primary">View All Organizations</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Organizations Widget -->
        <div class="row">
            <div class="col-md-12">
                <div class="widget">
                    <div class="widget-title">
                        <i class="fas fa-building"></i> Recent Organizations
                    </div>
                    <div class="widget-body">
                        <div class="row">
                            <?php if ($recent_organizations->num_rows === 0): ?>
                                <div class="col-12">
                                    <p class="text-muted">No organizations found</p>
                                </div>
                            <?php else: ?>
                                <?php while ($org = $recent_organizations->fetch_assoc()): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($org['name']); ?></h5>
                                                <p class="card-text">
                                                    <i class="fas fa-users"></i> Members: <?php echo $org['member_count']; ?><br>
                                                    <i class="fas fa-calendar"></i> Created: <?php echo date('M d, Y', strtotime($org['created_at'])); ?>
                                                </p>
                                                <a href="admin_organizations.php?org_id=<?php echo $org['organization_id']; ?>" class="btn btn-sm btn-primary">
                                                    View Members
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>
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