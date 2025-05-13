<?php
session_start();
include 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle user status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    
    if ($_POST['action'] == 'toggle_status') {
        $sql = "UPDATE users SET is_active = NOT is_active WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    } elseif ($_POST['action'] == 'delete') {
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$sql = "SELECT u.*, o.name as organization_name 
        FROM users u 
        LEFT JOIN organizations o ON u.organization_id = o.organization_id 
        ORDER BY u.date_joined DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total users count
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_pages = ceil($total_users / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .user-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .user-actions {
            display: flex;
            gap: 10px;
        }
        .pagination {
            margin-top: 20px;
        }
        .search-box {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 class="mb-4">Admin Panel</h3>
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link active" href="#"><i class="fas fa-users"></i> Users</a>
            <a class="nav-link" href="admin_datasets.php"><i class="fas fa-database"></i> Datasets</a>
            <a class="nav-link" href="admin_organizations.php"><i class="fas fa-building"></i> Organizations</a>
            <a class="nav-link" href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a>
            <a class="nav-link" href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a class="nav-link" href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <h2 class="mb-4">User Management</h2>

        <div class="search-box">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Search users..." id="searchInput">
                <button class="btn btn-primary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>

        <?php foreach ($users as $user): ?>
            <div class="user-card">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Organization:</strong> <?php echo htmlspecialchars($user['organization_name'] ?? 'None'); ?></p>
                        <p><strong>Role:</strong> <?php echo ucfirst($user['user_type']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($user['date_joined'])); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <div class="user-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="fas fa-power-off"></i> Toggle Status
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const userCards = document.querySelectorAll('.user-card');
            
            userCards.forEach(card => {
                const userName = card.querySelector('h5').textContent.toLowerCase();
                const userEmail = card.querySelector('.text-muted').textContent.toLowerCase();
                const userOrg = card.querySelector('p:nth-child(1)').textContent.toLowerCase();
                
                if (userName.includes(searchText) || userEmail.includes(searchText) || userOrg.includes(searchText)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 