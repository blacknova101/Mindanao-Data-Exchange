<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;

// Get batch details
$batchSql = "
    SELECT d.*, db.visibility, u.first_name, u.last_name, u.email, u.organization_id, 
           o.name as organization_name, c.name as category_name
    FROM datasets d
    JOIN users u ON d.user_id = u.user_id 
    JOIN dataset_batches db ON d.dataset_batch_id = db.dataset_batch_id
    LEFT JOIN organizations o ON u.organization_id = o.organization_id
    LEFT JOIN datasetcategories c ON d.category_id = c.category_id
    WHERE d.dataset_batch_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $batchSql);
mysqli_stmt_bind_param($stmt, 'i', $batch_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo "Dataset batch not found.";
    exit;
}

$batch = mysqli_fetch_assoc($result);

// Get all versions
$versionsSql = "
    SELECT v.*, 
           DATE_FORMAT(v.created_at, '%M %e, %Y %h:%i %p') as formatted_date,
           CONCAT(u.first_name, ' ', u.last_name) as creator_name
    FROM datasetversions v
    JOIN users u ON v.created_by = u.user_id
    WHERE v.dataset_batch_id = ?
    ORDER BY v.version_number DESC
";

$stmt = mysqli_prepare($conn, $versionsSql);
mysqli_stmt_bind_param($stmt, 'i', $batch_id);
mysqli_stmt_execute($stmt);
$versionsResult = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Version History - <?php echo htmlspecialchars($batch['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
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

        .header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .header p {
            margin: 10px 0 0;
            color: #666;
        }

        .version-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .version-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.2s;
        }

        .version-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .version-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .version-number {
            font-size: 18px;
            font-weight: 600;
            color: #0099ff;
        }

        .version-date {
            color: #666;
            font-size: 14px;
        }

        .version-creator {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .version-changes {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .version-changes h4 {
            margin: 0 0 10px;
            color: #333;
        }

        .version-changes p {
            margin: 0;
            color: #666;
            line-height: 1.5;
        }

        .version-files {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .file-item {
            display: flex;
            align-items: center;
            background: #fff;
            padding: 10px 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        .file-item i {
            color: #0099ff;
            margin-right: 10px;
        }

        .file-item a {
            color: #333;
            text-decoration: none;
            flex: 1;
        }

        .file-item a:hover {
            color: #0099ff;
        }

        .current-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #0099ff;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .back-link i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
            <h2>Version History</h2>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
        </nav>
    </header>

    <div class="container">
        <a href="dataset.php?id=<?php echo $batch['dataset_id']; ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dataset
        </a>

        <div class="header">
            <h1>Version History: <?php echo htmlspecialchars($batch['title']); ?></h1>
            <p>Track all changes and updates to this dataset</p>
        </div>

        <div class="version-list">
            <?php while ($version = mysqli_fetch_assoc($versionsResult)): ?>
                <div class="version-item">
                    <div class="version-header">
                        <div>
                            <span class="version-number">
                                Version <?php echo htmlspecialchars($version['version_number']); ?>
                                <?php if ($version['is_current']): ?>
                                    <span class="current-badge">Current</span>
                                <?php endif; ?>
                            </span>
                            <div class="version-creator">
                                Created by <?php echo htmlspecialchars($version['creator_name']); ?>
                            </div>
                        </div>
                        <div class="version-date">
                            <?php echo $version['formatted_date']; ?>
                        </div>
                    </div>

                    <?php if (!empty($version['change_notes'])): ?>
                        <div class="version-changes">
                            <h4>Changes in this version:</h4>
                            <p><?php echo nl2br(htmlspecialchars($version['change_notes'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="version-files">
                        <div class="file-item">
                            <i class="fas fa-file"></i>
                            <a href="download.php?dataset_id=<?php echo $batch['dataset_id']; ?>&version=<?php echo $version['version_id']; ?>">
                                <?php echo htmlspecialchars(basename($version['file_path'])); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html> 