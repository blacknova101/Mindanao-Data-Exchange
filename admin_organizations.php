<?php
session_start();
include 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get all organizations
$sql = "SELECT o.*, COUNT(u.user_id) as member_count, 
        CASE WHEN o.created_by IS NULL THEN 1 ELSE 0 END as no_owner
        FROM organizations o
        LEFT JOIN users u ON o.organization_id = u.organization_id
        GROUP BY o.organization_id
        ORDER BY o.name";
$organizations = $conn->query($sql);

// Get members of a specific organization if org_id is provided
$org_id = isset($_GET['org_id']) ? intval($_GET['org_id']) : null;
$org_name = '';

if ($org_id) {
    // Get organization name
    $org_sql = "SELECT name FROM organizations WHERE organization_id = ?";
    $stmt = $conn->prepare($org_sql);
    $stmt->bind_param("i", $org_id);
    $stmt->execute();
    $org_result = $stmt->get_result();
    if ($org_row = $org_result->fetch_assoc()) {
        $org_name = $org_row['name'];
    }
    
    // Get organization members
    $members_sql = "SELECT u.*, 
                   (SELECT COUNT(*) FROM datasets d WHERE d.user_id = u.user_id) as dataset_count
                   FROM users u 
                   WHERE u.organization_id = ? 
                   ORDER BY u.first_name, u.last_name";
    $stmt = $conn->prepare($members_sql);
    $stmt->bind_param("i", $org_id);
    $stmt->execute();
    $members = $stmt->get_result();
}

// Handle remove member from organization
if (isset($_POST['remove_member']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    // Update user record to remove organization association
    $sql = "UPDATE users SET organization_id = NULL WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        header("Location: admin_organizations.php?org_id=$org_id&message=User removed from organization successfully.");
        exit();
    } else {
        $error = "Failed to remove user from organization.";
    }
}

// Handle delete organization 
if (isset($_POST['delete_org']) && isset($_POST['org_id'])) {
    $org_id_to_delete = intval($_POST['org_id']);
    
    // First update all users to remove them from this organization
    $sql = "UPDATE users SET organization_id = NULL WHERE organization_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $org_id_to_delete);
    $stmt->execute();
    
    // Now delete the organization
    $sql = "DELETE FROM organizations WHERE organization_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $org_id_to_delete);
    
    if ($stmt->execute()) {
        header("Location: admin_organizations.php?message=Organization deleted successfully.");
        exit();
    } else {
        $error = "Failed to delete organization.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Organizations</title>
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
        .org-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        .org-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .alert {
            margin-bottom: 20px;
        }
        .member-card {
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .member-card .card-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-back {
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
            <a class="nav-link active" href="admin_organizations.php"><i class="fas fa-building"></i> Organizations</a>
            <a class="nav-link" href="admin_org_requests.php"><i class="fas fa-clipboard-list"></i> Org Requests</a>
            <a class="nav-link" href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a>
            <a class="nav-link" href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a class="nav-link" href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($org_id): ?>
            <!-- Organization Members View -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Members of <?php echo htmlspecialchars($org_name); ?></h2>
                <a href="admin_organizations.php" class="btn btn-primary btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Organizations
                </a>
            </div>

            <?php if ($members->num_rows === 0): ?>
                <div class="alert alert-info">
                    This organization has no members.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php while ($member = $members->fetch_assoc()): ?>
                        <div class="col-md-6">
                            <div class="card member-card">
                                <div class="card-body">
                                    <div>
                                        <h5><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></h5>
                                        <p class="mb-0">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($member['email']); ?><br>
                                            <i class="fas fa-database"></i> Datasets: <?php echo intval($member['dataset_count']); ?><br>
                                            <i class="fas fa-calendar"></i> Joined: <?php echo date('M d, Y', strtotime($member['date_joined'])); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to remove this user from the organization?');">
                                            <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                                            <input type="hidden" name="remove_member" value="1">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-user-minus"></i> Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Organizations List View -->
            <h2 class="mb-4">All Organizations</h2>
            
            <div class="row">
                <?php if ($organizations->num_rows === 0): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            No organizations found.
                        </div>
                    </div>
                <?php else: ?>
                    <?php while ($org = $organizations->fetch_assoc()): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card org-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($org['name']); ?>
                                        <?php if ($org['no_owner']): ?>
                                            <span class="badge bg-warning">No Owner</span>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="card-text">
                                        <i class="fas fa-users"></i> Members: <?php echo $org['member_count']; ?><br>
                                        <i class="fas fa-calendar"></i> Created: <?php echo date('M d, Y', strtotime($org['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="admin_organizations.php?org_id=<?php echo $org['organization_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-users"></i> View Members
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this organization? All members will be removed from it.');">
                                        <input type="hidden" name="org_id" value="<?php echo $org['organization_id']; ?>">
                                        <input type="hidden" name="delete_org" value="1">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 