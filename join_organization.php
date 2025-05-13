<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle search
$search = $_GET['search'] ?? '';
$sql = "SELECT organization_id, name FROM organizations WHERE name LIKE ?";
$stmt = $conn->prepare($sql);
$searchParam = '%' . $search . '%';
$stmt->bind_param("s", $searchParam);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Join an Organization</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8faff; }
        .container { max-width: 500px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 0 10px #0099ff22; padding: 30px; }
        .org-list { margin-top: 20px; }
        .org-item { padding: 10px; border-bottom: 1px solid #eee; }
        .org-item:last-child { border-bottom: none; }
        .join-btn { background: #0099ff; color: #fff; border: none; border-radius: 5px; padding: 6px 14px; cursor: pointer; }
        .create-org-btn { background: #fff; color: #0099ff; border: 2px solid #0099ff; border-radius: 5px; padding: 8px 16px; margin-top: 20px; cursor: pointer; font-weight: bold; }
        .create-org-btn:hover { background: #0099ff; color: #fff; }
        .not-found { color: #ff4d4d; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Join an Organization</h2>
    <form method="get" action="join_organization.php">
        <input type="text" name="search" placeholder="Search organizations..." value="<?php echo htmlspecialchars($search); ?>" style="width:70%;padding:8px;">
        <button type="submit" class="join-btn" style="padding:8px 16px;">Search</button>
    </form>
    <div class="org-list">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="org-item">
                    <?php echo htmlspecialchars($row['name']); ?>
                    <form method="post" action="process_join_org.php" style="display:inline;">
                        <input type="hidden" name="organization_id" value="<?php echo $row['organization_id']; ?>">
                        <button type="submit" class="join-btn">Join</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="not-found">
                No organizations found.
            </div>
        <?php endif; ?>
    </div>
    <div style="margin-top:30px;">
        <p>Don't see your organization?</p>
        <button class="create-org-btn" onclick="window.location.href='create_organization.php'">Create New Organization</button>
    </div>
</div>
</body>
</html>