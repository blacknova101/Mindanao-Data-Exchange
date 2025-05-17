<?php
session_start();
include 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Delete comment functionality
if (isset($_POST['delete_comment'])) {
    $comment_id = $_POST['comment_id'];
    
    $delete_query = "DELETE FROM datasetcomments WHERE comment_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $comment_id);
    
    if ($stmt->execute()) {
        // Log the action
        $admin_id = $_SESSION['admin_id'];
        $action = "Deleted comment ID: " . $comment_id;
        $log_query = "INSERT INTO admin_logs (admin_id, action, timestamp) VALUES (?, ?, NOW())";
        
        // Check if admin_logs table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'admin_logs'");
        if ($table_check->num_rows > 0) {
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("is", $admin_id, $action);
            $log_stmt->execute();
        }
        
        $_SESSION['success_message'] = "Comment deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Error deleting comment.";
    }
    
    header("Location: admin_datasets.php");
    exit();
}

// Check if required tables exist
$required_tables = [
    'datasets' => false,
    'datasetcomments' => false,
    'datasetversions' => false,
    'dataset_batch_analytics' => false
];

$tables_exist = true;
foreach (array_keys($required_tables) as $table) {
    $check_query = "SHOW TABLES LIKE '$table'";
    $result = $conn->query($check_query);
    if ($result->num_rows > 0) {
        $required_tables[$table] = true;
    } else {
        $tables_exist = false;
    }
}

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$title = "Datasets";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            padding-top: 0;
            background-color: #f8f9fa;
        }
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
        .admin-header {
            background-color: transparent;
            color: #333;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .comment-card {
            border-left: 4px solid #007bff;
        }
        .dataset-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .table-responsive {
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .admin-nav {
            margin-bottom: 20px;
        }
        .error-container {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 class="mb-4">Admin Panel</h3>
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link" href="admin_users.php"><i class="fas fa-users"></i> Users</a>
            <a class="nav-link active" href="#"><i class="fas fa-database"></i> Datasets</a>
            <a class="nav-link" href="admin_organizations.php"><i class="fas fa-building"></i> Organizations</a>
            <a class="nav-link" href="admin_org_requests.php"><i class="fas fa-clipboard-list"></i> Org Requests</a>
            <a class="nav-link" href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a>
            <a class="nav-link" href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a class="nav-link" href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="admin-header">
            <h1><i class="fas fa-database"></i> <?php echo $title; ?></h1>
            <p>Manage datasets, comments, and versions</p>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!$tables_exist): ?>
            <div class="error-container">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-danger"><i class="fas fa-exclamation-triangle"></i> Missing Tables</h2>
                        <p>Some required database tables are missing:</p>
                        <ul class="list-group mb-3">
                            <?php foreach ($required_tables as $table => $exists): ?>
                                <?php if (!$exists): ?>
                                    <li class="list-group-item list-group-item-danger"><?php echo $table; ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        <p>You need to create these tables to use this functionality.</p>
                        <a href="create_missing_tables.php" class="btn btn-primary">Create Missing Tables</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Tabs for different views -->
            <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="datasets-tab" data-bs-toggle="tab" data-bs-target="#datasets" type="button" role="tab" aria-controls="datasets" aria-selected="true">
                        <i class="fas fa-table"></i> Datasets
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab" aria-controls="comments" aria-selected="false">
                        <i class="fas fa-comments"></i> Comments
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="versions-tab" data-bs-toggle="tab" data-bs-target="#versions" type="button" role="tab" aria-controls="versions" aria-selected="false">
                        <i class="fas fa-code-branch"></i> Versions
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button" role="tab" aria-controls="analytics" aria-selected="false">
                        <i class="fas fa-chart-line"></i> Analytics
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="adminTabsContent">
                <!-- Datasets Tab -->
                <div class="tab-pane fade show active" id="datasets" role="tabpanel" aria-labelledby="datasets-tab">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h3><i class="fas fa-table"></i> All Datasets</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>User</th>
                                            <th>Category</th>
                                            <th>Visibility</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Get datasets with user and category info
                                        $dataset_query = "
                                            SELECT d.dataset_id, d.title, d.visibility,
                                                   u.first_name, u.last_name, 
                                                   dc.name as category_name
                                            FROM datasets d
                                            LEFT JOIN users u ON d.user_id = u.user_id
                                            LEFT JOIN datasetcategories dc ON d.category_id = dc.category_id
                                            ORDER BY d.dataset_id DESC
                                            LIMIT ?, ?";
                                        
                                        $stmt = $conn->prepare($dataset_query);
                                        $stmt->bind_param("ii", $offset, $limit);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . $row['dataset_id'] . "</td>";
                                                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                                                echo "<td>" . $row['visibility'] . "</td>";
                                                echo "<td>
                                                        <a href='dataset.php?id=" . $row['dataset_id'] . "' class='btn btn-sm btn-info'><i class='fas fa-eye'></i> View</a>
                                                      </td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center'>No datasets found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php
                            // Pagination for datasets
                            $count_query = "SELECT COUNT(*) as total FROM datasets";
                            $count_result = $conn->query($count_query);
                            $count_row = $count_result->fetch_assoc();
                            $total_datasets = $count_row['total'];
                            $total_pages = ceil($total_datasets / $limit);
                            
                            if ($total_pages > 1):
                            ?>
                            <nav aria-label="Dataset pagination">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">Previous</a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Comments Tab -->
                <div class="tab-pane fade" id="comments" role="tabpanel" aria-labelledby="comments-tab">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h3><i class="fas fa-comments"></i> Dataset Comments</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get comments with dataset and user info
                            $comment_query = "
                                SELECT c.comment_id, c.dataset_id, c.comment_text, c.timestamp,
                                       d.title as dataset_title,
                                       u.first_name, u.last_name
                                FROM datasetcomments c
                                LEFT JOIN datasets d ON c.dataset_id = d.dataset_id
                                LEFT JOIN users u ON c.user_id = u.user_id
                                ORDER BY c.timestamp DESC
                                LIMIT 20";
                            
                            $comment_result = $conn->query($comment_query);
                            
                            if ($comment_result && $comment_result->num_rows > 0) {
                                while ($comment = $comment_result->fetch_assoc()) {
                            ?>
                                <div class="card comment-card mb-3">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($comment['first_name'] . " " . $comment['last_name']); ?></strong>
                                            <small class="text-muted ms-2">
                                                <?php echo date("M d, Y g:i A", strtotime($comment['timestamp'])); ?>
                                            </small>
                                        </div>
                                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                            <button type="submit" name="delete_comment" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                                        <div class="dataset-info">
                                            <small class="text-muted">
                                                Dataset: <a href="dataset.php?id=<?php echo $comment['dataset_id']; ?>">
                                                    <?php echo htmlspecialchars($comment['dataset_title']); ?>
                                                </a>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php
                                }
                            } else {
                                echo "<div class='alert alert-info'>No comments found.</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Versions Tab -->
                <div class="tab-pane fade" id="versions" role="tabpanel" aria-labelledby="versions-tab">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h3><i class="fas fa-code-branch"></i> Dataset Versions</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Dataset</th>
                                            <th>Version</th>
                                            <th>Created By</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Get versions with creator info
                                        $version_query = "
                                            SELECT v.version_id, v.dataset_batch_id, v.version_number, 
                                                   v.title, v.created_at, v.is_current,
                                                   u.first_name, u.last_name
                                            FROM datasetversions v
                                            LEFT JOIN users u ON v.created_by = u.user_id
                                            ORDER BY v.created_at DESC
                                            LIMIT 20";
                                        
                                        $version_result = $conn->query($version_query);
                                        
                                        if ($version_result && $version_result->num_rows > 0) {
                                            while ($version = $version_result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . $version['version_id'] . "</td>";
                                                echo "<td>" . htmlspecialchars($version['title']) . "</td>";
                                                echo "<td>" . $version['version_number'] . "</td>";
                                                echo "<td>" . htmlspecialchars($version['first_name'] . " " . $version['last_name']) . "</td>";
                                                echo "<td>" . date("M d, Y", strtotime($version['created_at'])) . "</td>";
                                                echo "<td>" . ($version['is_current'] ? '<span class="badge bg-success">Current</span>' : '<span class="badge bg-secondary">Previous</span>') . "</td>";
                                                echo "<td>
                                                        <a href='dataset.php?batch_id=" . $version['dataset_batch_id'] . "&version=" . $version['version_id'] . "' class='btn btn-sm btn-info'>
                                                            <i class='fas fa-eye'></i> View
                                                        </a>
                                                      </td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='7' class='text-center'>No versions found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Analytics Tab -->
                <div class="tab-pane fade" id="analytics" role="tabpanel" aria-labelledby="analytics-tab">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h3><i class="fas fa-chart-line"></i> Dataset Analytics</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            // Check if dataset_batch_analytics table exists
                            if ($required_tables['dataset_batch_analytics']) {
                                // Top viewed datasets by batch
                                $views_query = "
                                    SELECT db.dataset_batch_id, d.dataset_id, d.title, dba.total_views
                                    FROM dataset_batch_analytics dba
                                    JOIN dataset_batches db ON dba.dataset_batch_id = db.dataset_batch_id
                                    JOIN datasets d ON db.dataset_batch_id = d.dataset_batch_id
                                    GROUP BY db.dataset_batch_id
                                    ORDER BY dba.total_views DESC
                                    LIMIT 10";
                                
                                $views_result = $conn->query($views_query);
                                
                                if ($views_result && $views_result->num_rows > 0) {
                                    echo "<h5>Top Viewed Datasets</h5>";
                                    echo "<div class='table-responsive'>";
                                    echo "<table class='table table-striped'>";
                                    echo "<thead class='table-dark'><tr><th>Batch ID</th><th>Dataset</th><th>Views</th></tr></thead>";
                                    echo "<tbody>";
                                    
                                    while ($view = $views_result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $view['dataset_batch_id'] . "</td>";
                                        echo "<td><a href='dataset.php?id=" . $view['dataset_id'] . "'>" . htmlspecialchars($view['title']) . "</a></td>";
                                        echo "<td>" . $view['total_views'] . "</td>";
                                        echo "</tr>";
                                    }
                                    
                                    echo "</tbody></table></div>";
                                    
                                    // Show downloads report
                                    $downloads_query = "
                                        SELECT db.dataset_batch_id, d.dataset_id, d.title, dba.total_downloads
                                        FROM dataset_batch_analytics dba
                                        JOIN dataset_batches db ON dba.dataset_batch_id = db.dataset_batch_id
                                        JOIN datasets d ON db.dataset_batch_id = d.dataset_batch_id
                                        GROUP BY db.dataset_batch_id
                                        ORDER BY dba.total_downloads DESC
                                        LIMIT 10";
                                    
                                    $downloads_result = $conn->query($downloads_query);
                                    
                                    if ($downloads_result && $downloads_result->num_rows > 0) {
                                        echo "<h5 class='mt-4'>Top Downloaded Datasets</h5>";
                                        echo "<div class='table-responsive'>";
                                        echo "<table class='table table-striped'>";
                                        echo "<thead class='table-dark'><tr><th>Batch ID</th><th>Dataset</th><th>Downloads</th></tr></thead>";
                                        echo "<tbody>";
                                        
                                        while ($download = $downloads_result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . $download['dataset_batch_id'] . "</td>";
                                            echo "<td><a href='dataset.php?id=" . $download['dataset_id'] . "'>" . htmlspecialchars($download['title']) . "</a></td>";
                                            echo "<td>" . $download['total_downloads'] . "</td>";
                                            echo "</tr>";
                                        }
                                        
                                        echo "</tbody></table></div>";
                                    }
                                    
                                    // Overall statistics
                                    $stats_query = "
                                        SELECT 
                                            SUM(total_views) as total_views,
                                            SUM(total_downloads) as total_downloads,
                                            MAX(last_accessed) as last_activity
                                        FROM dataset_batch_analytics";
                                    
                                    $stats_result = $conn->query($stats_query);
                                    
                                    if ($stats_result && $stats_row = $stats_result->fetch_assoc()) {
                                        echo "<div class='row mt-4'>";
                                        echo "<div class='col-md-4'>";
                                        echo "<div class='card bg-info text-white'>";
                                        echo "<div class='card-body text-center'>";
                                        echo "<h5 class='card-title'>Total Views</h5>";
                                        echo "<p class='display-4'>" . number_format($stats_row['total_views']) . "</p>";
                                        echo "</div></div></div>";
                                        
                                        echo "<div class='col-md-4'>";
                                        echo "<div class='card bg-success text-white'>";
                                        echo "<div class='card-body text-center'>";
                                        echo "<h5 class='card-title'>Total Downloads</h5>";
                                        echo "<p class='display-4'>" . number_format($stats_row['total_downloads']) . "</p>";
                                        echo "</div></div></div>";
                                        
                                        echo "<div class='col-md-4'>";
                                        echo "<div class='card bg-primary text-white'>";
                                        echo "<div class='card-body text-center'>";
                                        echo "<h5 class='card-title'>Last Activity</h5>";
                                        echo "<p class='h4'>" . date('M d, Y g:i A', strtotime($stats_row['last_activity'])) . "</p>";
                                        echo "</div></div></div>";
                                        echo "</div>"; // end row
                                    }
                                } else {
                                    echo "<div class='alert alert-info'>No view data available yet.</div>";
                                }
                            } else {
                                echo "<div class='alert alert-warning'>Analytics feature requires the 'dataset_batch_analytics' table.</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>