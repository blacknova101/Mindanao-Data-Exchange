<?php
session_start();
include 'db_connection.php'; // Ensure your DB connection is here

// Include session update to ensure organization_id is synchronized
include 'update_session.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the user's organization name
$user_id = $_SESSION['user_id'];
$query = "SELECT o.name AS org_name 
          FROM users u 
          JOIN organizations o ON u.organization_id = o.organization_id 
          WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || !$result->num_rows) {
    $_SESSION['error_message'] = "Organization not found.";
    header("Location: unauthorized.php");
    exit();
}
$row = $result->fetch_assoc();
$organizationName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['org_name']); // Sanitize folder name
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Dataset</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
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
            max-width: 1327px;
            width: 95%;
            margin-top: 30px;
            margin-left: auto;
            margin-right: auto;
            font-weight: bold;
            box-sizing: border-box;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: auto;
            width: 80px;
            max-width: 100%;
            margin-right: 15px;
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

        .main-container {
            width: 90%;
            max-width: 800px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
            margin: 0 auto;
        }

        h1 {
            color: #0c1a36;
            margin-bottom: 20px;
            font-size: 26px;
            text-align: center;
        }

        .error-message {
            color: #ff3d3d;
            background-color: #ffecec;
            border-left: 4px solid #ff3d3d;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 16px;
            width: 100%;
            max-width: 500px;
            box-sizing: border-box;
        }

        .upload-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 550px;
            margin-top: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-sizing: border-box;
            overflow: hidden;
        }

        .upload-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .upload-icon {
            font-size: 50px;
            color: #0099ff;
            margin-bottom: 15px;
        }

        .file-input-container {
            width: 100%;
            text-align: center;
            margin-bottom: 20px;
            box-sizing: border-box;
        }

        .file-input-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #e9f7ff;
            border: 2px dashed #0099ff;
            border-radius: 12px;
            padding: 20px 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .file-input-label:hover {
            background-color: #d4eeff;
            border-color: #007acc;
        }

        .file-input-label p {
            margin: 10px 0 0;
            font-size: 16px;
            color: #0099ff;
            font-weight: bold;
        }

        .file-input-label small {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        input[type="file"] {
            display: none;
        }

        .submit-button {
            background-color: #0099ff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: auto;
            min-width: 150px;
            max-width: 180px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px rgba(0, 153, 255, 0.2);
            margin-top: 10px;
            margin-left: auto;
            margin-right: auto;
        }

        .submit-button:hover {
            background-color: #007acc;
            box-shadow: 0 6px 8px rgba(0, 153, 255, 0.3);
            transform: translateY(-2px);
        }

        .submit-button:active {
            transform: translateY(1px);
            box-shadow: 0 2px 4px rgba(0, 153, 255, 0.3);
        }

        .submit-button i {
            margin-right: 8px;
        }

        .file-list {
            width: 100%;
            margin-top: 5px;
            margin-bottom: 20px;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background-color: #f9f9f9;
            max-height: 180px;
            overflow-y: auto;
            box-sizing: border-box;
        }

        .file-item {
            display: flex;
            align-items: center;
            margin: 8px 0;
            padding: 8px 12px;
            background-color: #e6f3ff;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            word-break: break-word;
            overflow: hidden;
        }

        .file-item:hover {
            background-color: #d4eaff;
            transform: translateX(5px);
        }

        .file-item i {
            margin-right: 10px;
            color: #0099ff;
            font-size: 16px;
            flex-shrink: 0;
        }

        .file-item-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: calc(100% - 30px);
        }

        .empty-file-list {
            text-align: center;
            color: #888;
            padding: 15px;
            font-style: italic;
        }

        form {
            width: 100%;
            box-sizing: border-box;
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

        @media (max-width: 768px) {
            .upload-card {
                width: 100%;
                padding: 25px 15px;
                max-width: 100%;
            }
            
            .navbar {
                width: 95%;
                padding: 10px 15px;
            }
            
            .logo img {
                width: 60px;
            }
            
            .main-container {
                width: 95%;
                padding: 15px 10px;
            }
        }
    </style>
</head>
<body>
<video autoplay muted loop id="background-video">
    <source src="videos/bg6.mp4" type="video/mp4">
</video>

<header class="navbar">
    <div class="logo">
        <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
        <h2>Upload Datasets</h2>
    </div>
    <nav class="nav-links">
        <a href="HomeLogin.php">HOME</a>
        <a href="datasets.php">DATASETS</a>
    </nav>
</header>

<div class="main-container">
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="upload-card">
        <div class="upload-icon">
            <i class="fas fa-cloud-upload-alt"></i>
        </div>
        <h1>Upload Your Datasets</h1>
        
        <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data" style="width: 100%; display: flex; flex-direction: column; align-items: center;">
            <div class="file-input-container">
                <label for="fileToUpload" class="file-input-label">
                    <i class="fas fa-file-upload" style="font-size: 32px; color: #0099ff;"></i>
                    <p>Click or drag files here</p>
                    <small>Support for multiple files</small>
                    <input type="file" name="fileToUpload[]" id="fileToUpload" multiple required onchange="displayFiles()">
                </label>
            </div>
            
            <div class="file-list" id="fileList">
                <div class="empty-file-list" id="emptyFileList">No files selected</div>
            </div>
            
            <button type="submit" class="submit-button">
                <i class="fas fa-upload"></i> Upload Files
            </button>
        </form>
    </div>
</div>

<script>
    // Function to display the selected files
    function displayFiles() {
        const fileList = document.getElementById('fileList');
        const emptyFileList = document.getElementById('emptyFileList');
        const files = document.getElementById('fileToUpload').files;
        
        fileList.innerHTML = ''; // Clear previous file list
        
        if (files.length === 0) {
            fileList.innerHTML = '<div class="empty-file-list">No files selected</div>';
            return;
        }

        // Loop through the selected files and display them
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileItem = document.createElement('div');
            fileItem.classList.add('file-item');
            
            // Determine icon based on file type
            let icon = 'fa-file';
            const extension = file.name.split('.').pop().toLowerCase();
            
            if (['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(extension)) {
                icon = 'fa-file-image';
            } else if (['pdf'].includes(extension)) {
                icon = 'fa-file-pdf';
            } else if (['doc', 'docx'].includes(extension)) {
                icon = 'fa-file-word';
            } else if (['xls', 'xlsx', 'csv'].includes(extension)) {
                icon = 'fa-file-excel';
            } else if (['zip', 'rar', '7z'].includes(extension)) {
                icon = 'fa-file-archive';
            } else if (['txt', 'rtf'].includes(extension)) {
                icon = 'fa-file-alt';
            }
            
            fileItem.innerHTML = `<i class="fas ${icon}"></i><span class="file-item-name">${file.name}</span>`;
            fileList.appendChild(fileItem);
        }
    }
</script>

</body>
</html>
