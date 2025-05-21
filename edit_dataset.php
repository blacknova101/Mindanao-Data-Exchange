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
        d.file_path,
        c.name AS category_name,
        v.version_number,
        v.version_id
    FROM datasets d
    LEFT JOIN datasetcategories c ON d.category_id = c.category_id
    LEFT JOIN datasetversions v ON v.dataset_batch_id = d.dataset_batch_id AND v.is_current = 1
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

// Function to properly handle newlines in descriptions
function fixNewlines($text) {
    // First, normalize all newlines to a standard format
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    
    // Then, handle any escaped newlines that might be in the text
    $text = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $text);
    
    return $text;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    // Get description and fix newlines
    $description = fixNewlines($_POST['description']);
    $start_period = $_POST['start_period'];
    $end_period = $_POST['end_period'];
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $source = trim($_POST['source']);
    $link = trim($_POST['link']);
    $location = trim($_POST['location']);
    $visibility = $_POST['visibility'];
    $update_type = $_POST['update_type'];
    $change_notes = isset($_POST['change_notes']) ? trim($_POST['change_notes']) : '';

    // Start a transaction to ensure all updates happen or none
    mysqli_begin_transaction($conn);

    try {
        if ($update_type === 'new_version') {
            // Get the next version number
            $versionSql = "SELECT MAX(CAST(SUBSTRING(version_number, 2) AS UNSIGNED)) as max_version 
                          FROM datasetversions 
                          WHERE dataset_batch_id = ?";
            $stmt = mysqli_prepare($conn, $versionSql);
            mysqli_stmt_bind_param($stmt, 'i', $dataset['dataset_batch_id']);
            mysqli_stmt_execute($stmt);
            $versionResult = mysqli_stmt_get_result($stmt);
            $versionRow = mysqli_fetch_assoc($versionResult);
            $nextVersion = ($versionRow['max_version'] ?? 0) + 1;

            // Sanitize inputs
            $title = htmlspecialchars(trim($title));
            
            // Sanitize description while preserving newlines
            // First apply fixNewlines again to ensure all newlines are properly handled
            $description = fixNewlines($description);
            // Then sanitize HTML
            $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
            
            $source = htmlspecialchars(trim($source));
            $link = htmlspecialchars(trim($link));
            $location = htmlspecialchars(trim($location));
            $change_notes = htmlspecialchars(trim($change_notes));

            // Handle file uploads
            $main_file_path = $dataset['file_path']; // Default to current file
            
            if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
                // Create version-specific directory
                $upload_base_dir = dirname($dataset['file_path']);
                $version_dir = $upload_base_dir . "/v{$nextVersion}";
                
                // Create directory if it doesn't exist
                if (!file_exists($version_dir)) {
                    mkdir($version_dir, 0777, true);
                }
                
                $uploaded_files = [];
                $file_count = count($_FILES['files']['name']);
                
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                        // Get original filename and sanitize it
                        $original_name = $_FILES['files']['name'][$i];
                        $sanitized_name = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $original_name);
                        
                        // Create unique filename
                        $file_extension = pathinfo($sanitized_name, PATHINFO_EXTENSION);
                        $new_file_name = "file_" . ($i + 1) . "_" . time() . "." . $file_extension;
                        $target_file = $version_dir . '/' . $new_file_name;
                        
                        if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $target_file)) {
                            $uploaded_files[] = [
                                'path' => $target_file,
                                'original_name' => $original_name
                            ];
                        }
                    }
                }
                
                // Use the first uploaded file as the main file path
                if (!empty($uploaded_files)) {
                    $main_file_path = $uploaded_files[0]['path'];
                }
            }

            // Create new version
            $version_number = "v{$nextVersion}";
            $insertVersionSql = "
                INSERT INTO datasetversions (
                    dataset_batch_id, version_number, file_path, title, description,
                    start_period, end_period, category_id, source, link, location,
                    created_by, is_current, change_notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
            ";
            $stmt = mysqli_prepare($conn, $insertVersionSql);
            mysqli_stmt_bind_param(
                $stmt,
                'issssssisssis',
                $dataset['dataset_batch_id'],
                $version_number,
                $main_file_path,
                $title,
                $description,
                $start_period,
                $end_period,
                $category_id,
                $source,
                $link,
                $location,
                $user_id,
                $change_notes
            );
            mysqli_stmt_execute($stmt);
            
            // If we have additional files, store them in a related files table or handle as needed
            // This would require adding a new table for additional files
            
            // Mark all other versions as not current
            $updateVersionsSql = "
                UPDATE datasetversions 
                SET is_current = 0 
                WHERE dataset_batch_id = ? AND version_number != ?
            ";
            $stmt = mysqli_prepare($conn, $updateVersionsSql);
            mysqli_stmt_bind_param($stmt, 'is', $dataset['dataset_batch_id'], $version_number);
            mysqli_stmt_execute($stmt);

            // Update main dataset record
            $updateDatasetSql = "
                UPDATE datasets 
                SET title = ?, description = ?, start_period = ?, end_period = ?,
                    category_id = ?, source = ?, link = ?, location = ?, 
                    visibility = ?, file_path = ?
                WHERE dataset_id = ?
            ";
            $stmt = mysqli_prepare($conn, $updateDatasetSql);
            mysqli_stmt_bind_param(
                $stmt,
                'ssssisssssi',
                $title,
                $description,
                $start_period,
                $end_period,
                $category_id,
                $source,
                $link,
                $location,
                $visibility,
                $main_file_path,
                $dataset_id
            );
            mysqli_stmt_execute($stmt);

        } else {
            // Simple update of current version
            
            // Sanitize description while preserving newlines for regular update
            $description = fixNewlines($description);
            $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
            
            $updateDatasetSql = "
                UPDATE datasets 
                SET title = ?, description = ?, start_period = ?, end_period = ?,
                    category_id = ?, source = ?, link = ?, location = ?, visibility = ?
                WHERE dataset_id = ?
            ";
            $stmt = mysqli_prepare($conn, $updateDatasetSql);
            mysqli_stmt_bind_param(
                $stmt,
                'ssssissssi',
                $title,
                $description,
                $start_period,
                $end_period,
                $category_id,
                $source,
                $link,
                $location,
                $visibility,
                $dataset_id
            );
            mysqli_stmt_execute($stmt);

            // Update current version record
            $updateVersionSql = "
                UPDATE datasetversions 
                SET title = ?, description = ?, start_period = ?, end_period = ?,
                    category_id = ?, source = ?, link = ?, location = ?
                WHERE dataset_batch_id = ? AND is_current = 1
            ";
            $stmt = mysqli_prepare($conn, $updateVersionSql);
            mysqli_stmt_bind_param(
                $stmt,
                'ssssisssi',
                $title,
                $description,
                $start_period,
                $end_period,
                $category_id,
                $source,
                $link,
                $location,
                $dataset['dataset_batch_id']
            );
            mysqli_stmt_execute($stmt);
        }

        // Update visibility in dataset_batches table
        $batch_update_sql = "
            UPDATE dataset_batches 
            SET visibility = ?
            WHERE dataset_batch_id = ?
        ";
        $batch_update_stmt = mysqli_prepare($conn, $batch_update_sql);
        mysqli_stmt_bind_param($batch_update_stmt, 'si', 
            $visibility,
            $dataset['dataset_batch_id']
        );
        mysqli_stmt_execute($batch_update_stmt);

        // Update visibility for all datasets in the batch
        $all_datasets_update_sql = "
            UPDATE datasets 
            SET visibility = ?
            WHERE dataset_batch_id = ? AND user_id = ?
        ";
        $all_datasets_update_stmt = mysqli_prepare($conn, $all_datasets_update_sql);
        mysqli_stmt_bind_param($all_datasets_update_stmt, 'sii', 
            $visibility,
            $dataset['dataset_batch_id'], 
            $user_id
        );
        mysqli_stmt_execute($all_datasets_update_stmt);

        // Commit the transaction
        mysqli_commit($conn);
        
        header("Location: dataset.php?id=$dataset_id");
        exit();
    } catch (Exception $e) {
        // Roll back the transaction if any query fails
        mysqli_rollback($conn);
        $error = "Failed to update dataset. Please try again. Error: " . $e->getMessage();
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
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
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
            color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            margin: 10px 0;
            backdrop-filter: blur(10px);
            max-width: 1200px;
            width: 95%;
            margin-top: 30px;
            margin-left: auto;
            margin-right: auto;
            font-weight: bold;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
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
            transition: transform 0.3s ease, color 0.3s;
            padding: 8px 15px;
            border-radius: 8px;
        }

        .nav-links a:hover {
            transform: scale(1.05);
            background-color: rgba(255, 255, 255, 0.15);
        }

        #wrapper {
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        .edit-form {
            text-align: left;
            width: 100%;
            box-sizing: border-box;
        }

        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-sizing: border-box;
            border: 1px solid #e9ecef;
            transition: box-shadow 0.3s ease;
        }

        .form-section:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .form-section h3 {
            color: #0099ff;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0099ff;
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 25px;
            width: 100%;
            box-sizing: border-box;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="url"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="date"]:focus,
        .form-group input[type="url"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #0099ff;
            box-shadow: 0 0 0 3px rgba(0, 153, 255, 0.2);
            outline: none;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
            line-height: 1.5;
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
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            font-size: 16px;
            border: none;
        }

        .btn-primary {
            background-color: #0099ff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0080dd;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 153, 255, 0.3);
        }

        .btn-secondary {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        .error-message {
            color: #dc3545;
            margin-bottom: 20px;
            text-align: center;
            padding: 15px;
            background-color: #f8d7da;
            border-radius: 8px;
            border: 1px solid #f5c2c7;
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

        .radio-group {
            display: flex;
            gap: 25px;
            margin: 15px 0;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px 15px;
            border-radius: 8px;
            transition: background-color 0.2s;
        }

        .radio-group label:hover {
            background-color: #e9f5ff;
        }

        .radio-group input[type="radio"] {
            margin: 0;
            width: 18px;
            height: 18px;
            accent-color: #0099ff;
        }

        .file-upload-container {
            border: 2px dashed #0099ff;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            background-color: #e9f5ff;
            transition: background-color 0.3s, border-color 0.3s;
        }

        .file-upload-container:hover {
            background-color: #d6edff;
            border-color: #0080dd;
        }

        .file-upload-container input[type="file"] {
            cursor: pointer;
            font-size: 16px;
        }

        .file-list {
            margin-top: 15px;
            text-align: left;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: #fff;
            margin: 8px 0;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: transform 0.2s;
        }

        .file-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .file-item i {
            margin-right: 12px;
            color: #0099ff;
            font-size: 18px;
        }

        .version-notes {
            display: none;
            background-color: #e9f5ff;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #0099ff;
            margin-top: 20px;
            transition: all 0.3s;
        }

        .form-help {
            font-size: 14px;
            color: #666;
            margin-top: 8px;
            font-style: italic;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-primary, .submit-btn {
            background-color: #0099ff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary:hover, .submit-btn:hover {
            background-color: #0080dd;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 153, 255, 0.3);
        }

        .cancel-btn {
            padding: 12px 24px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .cancel-btn:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            #wrapper {
                padding: 20px;
                margin: 20px;
                width: auto;
            }
            
            .navbar {
                flex-direction: column;
                gap: 10px;
                padding: 15px;
            }
            
            .nav-links {
                width: 100%;
                display: flex;
                justify-content: center;
            }
            
            .nav-links a {
                margin: 0 10px;
            }
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
                <a href="homelogin.php">HOME</a>
                <a href="mydatasets.php">MY DATASETS</a>
            </nav>
        </header>

        <div id="wrapper">
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="edit-form">
                <div class="form-section">
                    <h3>Update Type</h3>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="update_type" value="update" checked> Update Current Version
                        </label>
                        <label>
                            <input type="radio" name="update_type" value="new_version"> Create New Version
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Dataset Information</h3>
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($dataset['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars(fixNewlines($dataset['description'])); ?></textarea>
                        <p class="form-help">Use Enter or Shift+Enter for line breaks. Line breaks will be preserved when saving.</p>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category:</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo ($category['category_id'] == $dataset['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_period">Start Period:</label>
                        <input type="date" id="start_period" name="start_period" value="<?php echo $dataset['start_period']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="end_period">End Period:</label>
                        <input type="date" id="end_period" name="end_period" value="<?php echo $dataset['end_period']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="source">Source:</label>
                        <input type="text" id="source" name="source" value="<?php echo htmlspecialchars($dataset['source']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="link">Link:</label>
                        <input type="url" id="link" name="link" value="<?php echo htmlspecialchars($dataset['link']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($dataset['location']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="visibility">Visibility:</label>
                        <select id="visibility" name="visibility" required>
                            <option value="Public" <?php echo ($dataset['visibility'] == 'Public') ? 'selected' : ''; ?>>Public</option>
                            <option value="Private" <?php echo ($dataset['visibility'] == 'Private') ? 'selected' : ''; ?>>Private</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Files</h3>
                    <div class="file-upload-container">
                        <input type="file" name="files[]" id="files" multiple>
                        <div id="fileList" class="file-list"></div>
                    </div>
                    <p class="form-help">Select new files to upload. Leave empty to keep existing files.</p>
                </div>

                <div class="form-section version-notes">
                    <h3>Version Change Notes</h3>
                    <div class="form-group">
                        <label for="change_notes">Describe what changed in this version:</label>
                        <textarea id="change_notes" name="change_notes" rows="3" placeholder="Describe what changed in this version..."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="dataset.php?id=<?php echo $dataset_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Handle file selection display
        document.getElementById('files').addEventListener('change', function(e) {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';
            
            Array.from(e.target.files).forEach(file => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <i class="fas fa-file"></i>
                    <span>${file.name}</span>
                    <span style="margin-left: auto; color: #666;">${(file.size / 1024).toFixed(1)} KB</span>
                `;
                fileList.appendChild(fileItem);
            });
        });

        // Show/hide version notes based on update type
        document.querySelectorAll('input[name="update_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const versionNotes = document.querySelector('.version-notes');
                versionNotes.style.display = this.value === 'new_version' ? 'block' : 'none';
            });
        });
        
        // Process description before form submission to ensure newlines are preserved
        document.querySelector('form.edit-form').addEventListener('submit', function(e) {
            // Get the description textarea
            const descriptionTextarea = document.getElementById('description');
            if (descriptionTextarea) {
                // Replace any literal '\r\n' with actual newlines
                const value = descriptionTextarea.value;
                const cleanedValue = value.replace(/\\r\\n|\\n|\\r/g, '\n');
                descriptionTextarea.value = cleanedValue;
            }
        });
    </script>
</body>
</html> 