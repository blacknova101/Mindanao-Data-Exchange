<?php
session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$dataset_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get dataset details
$sql = "
    SELECT 
        d.dataset_id,
        d.dataset_batch_id,
        d.title,
        d.description,
        d.start_period,
        d.end_period,
        d.category_id,
        d.source,
        d.link,
        d.location,
        d.visibility,
        c.name AS category_name
    FROM datasets d
    LEFT JOIN datasetcategories c ON d.category_id = c.category_id
    WHERE d.dataset_id = ? AND d.user_id = ?
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $dataset_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: mydatasets.php");
    exit();
}

$dataset = mysqli_fetch_assoc($result);

// Get all categories for the dropdown
$categories_sql = "SELECT * FROM datasetcategories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_sql);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_period = $_POST['start_period'];
    $end_period = $_POST['end_period'];
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $source = trim($_POST['source']);
    $link = trim($_POST['link']);
    $location = trim($_POST['location']);
    $visibility = $_POST['visibility'];

    $update_sql = "
        UPDATE datasets 
        SET title = ?, 
            description = ?, 
            start_period = ?,
            end_period = ?,
            category_id = ?,
            source = ?,
            link = ?,
            location = ?,
            visibility = ?
        WHERE dataset_id = ? AND user_id = ?
    ";

    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, 'ssssissssii', 
        $title, 
        $description, 
        $start_period,
        $end_period,
        $category_id,
        $source,
        $link,
        $location,
        $visibility,
        $dataset_id, 
        $user_id
    );
    
    if (mysqli_stmt_execute($update_stmt)) {
        header("Location: mydatasets.php");
        exit();
    } else {
        $error = "Failed to update dataset. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Dataset</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        html, body {
            height: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
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

        #wrapper {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        .edit-form {
            text-align: left;
            width: 100%;
            box-sizing: border-box;
        }

        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-sizing: border-box;
        }

        .form-section h3 {
            color: #0c1a36;
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #0099ff;
        }

        .form-group {
            margin-bottom: 20px;
            width: 100%;
            box-sizing: border-box;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #0c1a36;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            width: 100%;
            box-sizing: border-box;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
            min-width: 0; /* Prevents flex items from overflowing */
        }

        .readonly-field {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .save-btn,
        .cancel-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .save-btn {
            background-color: #4CAF50;
            color: white;
        }

        .save-btn:hover {
            background-color: #45a049;
        }

        .cancel-btn {
            background-color: #f44336;
            color: white;
        }

        .cancel-btn:hover {
            background-color: #da190b;
        }

        .error-message {
            color: #f44336;
            margin-bottom: 20px;
            text-align: center;
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
    </style>
</head>
<body>
    <video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>

    <div class="container">
        <header class="navbar">
            <div class="logo">
                <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
                <h2>Edit Dataset</h2>
            </div>
            <nav class="nav-links">
                <a href="mydatasets.php">BACK TO MY DATASETS</a>
            </nav>
        </header>

        <div id="wrapper">
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form class="edit-form" method="POST">
                <!-- Basic Information Section -->
                <div class="form-section">
                    <h3>Basic Information</h3>
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($dataset['title']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" required><?= htmlspecialchars($dataset['description']) ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_period">Start Period</label>
                            <input type="date" id="start_period" name="start_period" value="<?= htmlspecialchars($dataset['start_period']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="end_period">End Period</label>
                            <input type="date" id="end_period" name="end_period" value="<?= htmlspecialchars($dataset['end_period']) ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Category and Source Section -->
                <div class="form-section">
                    <h3>Category and Source</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?= $category['category_id'] ?>" <?= $category['category_id'] == $dataset['category_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="source">Source</label>
                            <input type="text" id="source" name="source" value="<?= htmlspecialchars($dataset['source']) ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Location and Link Section -->
                <div class="form-section">
                    <h3>Location and Link</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" value="<?= htmlspecialchars($dataset['location']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="link">Link</label>
                            <input type="text" id="link" name="link" value="<?= htmlspecialchars($dataset['link']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="visibility">Visibility</label>
                        <select id="visibility" name="visibility" required>
                            <option value="Public" <?= $dataset['visibility'] === 'Public' ? 'selected' : '' ?>>Public</option>
                            <option value="Private" <?= $dataset['visibility'] === 'Private' ? 'selected' : '' ?>>Private</option>
                        </select>
                    </div>
                </div>

                <div class="form-buttons">
                    <a href="mydatasets.php" class="cancel-btn">Cancel</a>
                    <button type="submit" class="save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 