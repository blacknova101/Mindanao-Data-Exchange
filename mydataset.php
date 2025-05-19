<?php
session_start();
include('db_connection.php');

// Check if the user is logged in (ensure 'user_id' is set in the session)
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not authenticated
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;

function formatUrl($url) {
    if (empty($url)) {
        return '#';
    }
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "https://" . $url;
    }
    return $url;
}

$dataset_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$dataset_id) {
    echo "No dataset ID provided.";
    exit;
}

$sql = "
    SELECT d.*, db.visibility, u.first_name, u.last_name 
    FROM datasets d
    JOIN users u ON d.user_id = u.user_id 
    JOIN dataset_batches db ON d.dataset_batch_id = db.dataset_batch_id
    WHERE d.dataset_id = ?
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $dataset_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "Dataset not found.";
    exit;
}

$dataset = mysqli_fetch_assoc($result);
$batch_id = $dataset['dataset_batch_id'];
include 'batch_analytics.php';

if (!isset($_SESSION['viewed_batches'])) {
    $_SESSION['viewed_batches'] = [];
}

if (!in_array($batch_id, $_SESSION['viewed_batches'])) {
    increment_batch_views($conn, $batch_id);
    $_SESSION['viewed_batches'][] = $batch_id;
}
$analytics = get_batch_analytics($conn, $batch_id);

$versionsSql = "
    SELECT v.version_id, v.version_number, v.is_current, v.created_at
    FROM datasetversions v
    WHERE v.dataset_batch_id = ?
    ORDER BY v.version_number DESC
    LIMIT 5
";

$stmt = mysqli_prepare($conn, $versionsSql);
mysqli_stmt_bind_param($stmt, 'i', $batch_id);
mysqli_stmt_execute($stmt);
$versionsResult = mysqli_stmt_get_result($stmt);

// Count total versions
$countVersionsSql = "
    SELECT COUNT(*) as total_versions
    FROM datasetversions
    WHERE dataset_batch_id = ?
";

$stmt = mysqli_prepare($conn, $countVersionsSql);
mysqli_stmt_bind_param($stmt, 'i', $batch_id);
mysqli_stmt_execute($stmt);
$countResult = mysqli_stmt_get_result($stmt);
$countRow = mysqli_fetch_assoc($countResult);
$totalVersions = $countRow['total_versions'];

$currentVersion = null;
if (isset($versionsResult) && mysqli_num_rows($versionsResult) > 0) {
    mysqli_data_seek($versionsResult, 0);
    while ($version = mysqli_fetch_assoc($versionsResult)) {
        if ($version['is_current']) {
            $currentVersion = $version;
            break;
        }
    }
}

// Handle version switching if requested
if (isset($_GET['switch_to']) && is_numeric($_GET['switch_to'])) {
    $version_id = (int)$_GET['switch_to'];
    
    // Check if this version exists and belongs to this batch
    $checkVersionSql = "
        SELECT * FROM datasetversions 
        WHERE version_id = ? AND dataset_batch_id = ?
    ";
    $stmt = mysqli_prepare($conn, $checkVersionSql);
    mysqli_stmt_bind_param($stmt, 'ii', $version_id, $batch_id);
    mysqli_stmt_execute($stmt);
    $checkResult = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($checkResult) > 0) {
        $versionData = mysqli_fetch_assoc($checkResult);
        
        // Start a transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Mark all versions as not current
            $updateAllSql = "
                UPDATE datasetversions
                SET is_current = 0
                WHERE dataset_batch_id = ?
            ";
            $stmt = mysqli_prepare($conn, $updateAllSql);
            mysqli_stmt_bind_param($stmt, 'i', $batch_id);
            mysqli_stmt_execute($stmt);
            
            // Mark the selected version as current
            $updateCurrentSql = "
                UPDATE datasetversions
                SET is_current = 1
                WHERE version_id = ?
            ";
            $stmt = mysqli_prepare($conn, $updateCurrentSql);
            mysqli_stmt_bind_param($stmt, 'i', $version_id);
            mysqli_stmt_execute($stmt);
            
            // Update the main dataset with this version's data
            $updateDatasetSql = "
                UPDATE datasets
                SET title = ?,
                    description = ?,
                    start_period = ?,
                    end_period = ?,
                    category_id = ?,
                    source = ?,
                    link = ?,
                    location = ?
                WHERE dataset_batch_id = ?
            ";
            $stmt = mysqli_prepare($conn, $updateDatasetSql);
            mysqli_stmt_bind_param(
                $stmt, 
                'ssssisssi', 
                $versionData['title'],
                $versionData['description'],
                $versionData['start_period'],
                $versionData['end_period'],
                $versionData['category_id'],
                $versionData['source'],
                $versionData['link'],
                $versionData['location'],
                $batch_id
            );
            mysqli_stmt_execute($stmt);
            
            // Commit the transaction
            mysqli_commit($conn);
            
            // Redirect to the dataset page
            header("Location: mydataset.php?id=$dataset_id&switched=1");
            exit();
        } catch (Exception $e) {
            // Roll back the transaction if any query fails
            mysqli_rollback($conn);
            $error = "Failed to switch versions. Error: " . $e->getMessage();
        }
    }
}

// Get only the main dataset as a resource
$resourcesSql = "
    SELECT * FROM datasets 
    WHERE dataset_id = ?
";
$stmt = mysqli_prepare($conn, $resourcesSql);
mysqli_stmt_bind_param($stmt, 'i', $dataset_id);
mysqli_stmt_execute($stmt);
$resourcesResult = mysqli_stmt_get_result($stmt);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo !empty($dataset['title']) ? htmlspecialchars($dataset['title']) : 'Untitled Dataset'; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
     body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f6f9;
      color: #333;
    }
    
    /* Version tabs styling */
    .version-tabs {
        display: flex;
        margin-bottom: -1px;
        position: relative;
        z-index: 2;
    }
    
    .version-tab {
        padding: 8px 16px;
        background-color: #e9ecef;
        border: 1px solid #dee2e6;
        border-bottom: none;
        border-radius: 6px 6px 0 0;
        margin-right: 4px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s;
        position: relative;
        top: 1px;
        text-decoration: none;
        color: #495057;
    }
    
    .version-tab.active {
        background-color: #fff;
        border-bottom: 1px solid #fff;
        color: #0099ff;
        font-weight: 600;
    }
    
    .version-tab:hover:not(.active) {
        background-color: #f1f3f5;
    }
    
    .version-tab.history {
        margin-left: auto;
        background-color: #f8f9fa;
    }
    
    .version-indicator {
        display: inline-block;
        padding: 3px 8px;
        background-color: #e9f5ff;
        color: #0099ff;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        margin-left: 10px;
    }
    
    /* Adjust container to work with tabs */
    .container {
      max-width: 1100px;
      margin: 40px auto;
      padding: 20px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      position: relative;
    }
    
    .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5%; /* Adjusted padding for a more compact navbar */
            padding-left: 30px;
            background-color: #0099ff; /* Transparent background */
            color: #cfd9ff;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            margin: 10px 0;
            backdrop-filter: blur(10px);
            max-width: 1200px; /* Limit the maximum width */
            width: 100%; /* Ensure it takes up the full width but doesn't exceed 1200px */
            margin-top:30px;
            margin-left: auto; /* Center align the navbar */
            margin-right: auto; /* Center align the navbar */
            font-weight: bold;
        }
        .logo {
        display: flex;
        align-items: center;
        }
        .logo img {
            height: auto;
            width: 80px; /* Adjust logo size */
            max-width: 100%;
        }
        .nav-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-size: 18px;
            transition: transform 0.3s ease; /* Smooth transition for scaling */
        }
        .nav-links a:hover {
            transform: scale(1.2); /* Scale up on hover */
        }
    
    .header-section {
      display: flex;
      flex-wrap: wrap;
      align-items: stretch;
      gap: 20px;
    }

    .header-left {
      flex: 1;
      min-width: 0;
      background: #007BFF;
      color: #fff;
      padding: 30px;
      border-radius: 8px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .header-left h2 {
      font-size: 18px;
      font-weight: 500;
      margin: 0 0 10px;
    }

    .header-left h1 {
      font-size: 26px;
      font-weight: 700;
      margin: 0;
      text-transform: uppercase;
    }

    .title-section {
      margin-top: 30px;
    }

    .title-section h1 {
      margin: 0;
      font-size: 24px;
      color: #222;
    }

    .title-section p {
      margin-top: 10px;
      margin-left: 10px;
      font-size: 16px;
      line-height: 1.5;
      color: #555;
    }

    .info-grid {
      display: flex;
      gap: 40px;
      margin-top: 40px;
      flex-wrap: wrap;
    }

    .info-box, .resources-box {
      flex: 1;
      min-width: 300px;
      background: #f9fbfd;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 20px;
    }

    .info-box h3, .resources-box h3 {
      margin-top: 0;
      margin-bottom: 16px;
      font-size: 18px;
      color: #007BFF;
      border-bottom: 1px solid #ccc;
      padding-bottom: 8px;
    }

    .info-item {
      margin-bottom: 10px;
    }

    .info-item strong {
      display: block;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .file-item {
      display: flex;
      align-items: center;
      margin-bottom: 12px;
      background: #fff;
      padding: 10px 12px;
      border-radius: 6px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    }

    .file-item i {
      margin-right: 10px;
      color: #007BFF;
    }

    .file-item a {
      color: #333;
      text-decoration: none;
    }

    .file-item a:hover {
      text-decoration: underline;
      color: #007BFF;
    }

    .file-item .file-size {
      margin-left: auto;
      color: #777;
      font-size: 0.9em;
    }

    .action-buttons {
      display: flex;
      gap: 10px;
      margin-top: 30px;
    }

    .btn {
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }

    .btn i {
      margin-right: 8px;
    }
    
    .btn-primary {
      background: #007BFF;
      color: white;
      border: none;
    }

    .btn-primary:hover {
      background: #0069d9;
    }

    .btn-secondary {
      background: #f8f9fa;
      color: #333;
      border: 1px solid #ddd;
    }

    .btn-secondary:hover {
      background: #e9ecef;
    }
    
    .btn-danger {
      background: #dc3545;
      color: white;
      border: none;
    }

    .btn-danger:hover {
      background: #c82333;
    }

    .metadata-section {
      margin-top: 40px;
    }

    .metadata-section h3 {
      font-size: 18px;
      color: #333;
      margin-bottom: 16px;
    }

    .metadata-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 16px;
    }

    .metadata-item {
      background: #f8f9fa;
      padding: 12px 16px;
      border-radius: 6px;
      border-left: 4px solid #007BFF;
    }

    .metadata-item strong {
      display: block;
      font-size: 0.9em;
      color: #666;
      margin-bottom: 4px;
    }

    .metadata-item span {
      font-weight: 500;
    }

    .upvote-section {
      display: flex;
      align-items: center;
      margin-top: 30px;
    }

    .upvote-btn {
      display: flex;
      align-items: center;
      background: #f8f9fa;
      border: 1px solid #ddd;
      border-radius: 30px;
      padding: 8px 16px;
      cursor: pointer;
      transition: all 0.2s;
    }

    .upvote-btn:hover {
      background: #e9ecef;
    }

    .upvote-btn i {
      color: #007BFF;
      margin-right: 8px;
    }

    .upvote-count {
      font-weight: 600;
    }

    .upvote-text {
      margin-left: 12px;
      color: #666;
    }
    
    .alert {
      padding: 12px 16px;
      border-radius: 6px;
      margin-bottom: 20px;
    }

    .alert-info {
      background: #cff4fc;
      color: #055160;
      border: 1px solid #b6effb;
    }

    .alert-warning {
      background: #fff3cd;
      color: #664d03;
      border: 1px solid #ffecb5;
    }

    .alert-success {
      background: #d1e7dd;
      color: #0f5132;
      border: 1px solid #badbcc;
    }

    .alert-danger {
      background: #f8d7da;
      color: #842029;
      border: 1px solid #f5c2c7;
    }
    
    #background-video {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: -1; /* stays behind everything */
    }
    
    /* Add missing styles */
    .visibility-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 14px;
        margin-left: 15px;
        font-weight: bold;
    }
    
    .visibility-badge.public {
        background-color: #4CAF50;
        color: white;
    }
    
    .visibility-badge.private {
        background-color: #f44336;
        color: white;
    }
    
    .download-btn {
      background-color: #0099ff !important;
      color: white !important;
      font-weight: bold !important;
      padding: 8px 14px !important;
      border-radius: 6px !important;
      text-decoration: none !important;
      transition: background-color 0.3s ease !important;
    }
    
    .download-btn:hover {
      background-color: #007acc !important;
    }
    
    .resources-scrollable {
      max-height: 300px;
      overflow-y: auto;
      padding-right: 10px;
    }
    
    /* Notification styles */
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: #00cc00;
      color: white;
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 1000;
      transform: translateX(150%);
      transition: transform 0.3s ease-in-out;
      font-weight: bold;
      display: flex;
      align-items: center;
    }
    
    .notification.comment {
      background-color: #0099ff;
    }
    
    .notification i {
      margin-right: 10px;
      font-size: 18px;
    }
    
    .notification.show {
      transform: translateX(0);
    }
    
    .notification.hide {
      transform: translateX(150%);
    }
    
    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.6);
      backdrop-filter: blur(4px);
      animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    .modal-content {
      background-color: #fff;
      margin: 5% auto;
      padding: 35px;
      border-radius: 15px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.2);
      width: 650px;
      max-width: 90%;
      transform: translateY(20px);
      animation: slideUp 0.4s ease forwards;
      border-top: 5px solid #0099ff;
    }
    
    @keyframes slideUp {
      to { transform: translateY(0); }
    }
    
    .close {
      color: #888;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      transition: color 0.2s;
      margin-top: -25px;
      margin-right: -20px;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
    }
    
    .close:hover,
    .close:focus {
      color: #0099ff;
      background-color: #f0f7ff;
    }
    
    .modal h3 {
      color: #0099ff;
      font-size: 24px;
      margin-top: 0;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid #e9f5ff;
      position: relative;
    }
    
    .modal h3:after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      width: 80px;
      height: 2px;
      background-color: #0099ff;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
      font-size: 15px;
    }
    
    .form-group textarea,
    .form-group input[type="text"],
    .form-group input[type="url"],
    .form-group input[type="date"],
    .form-group select {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-family: inherit;
      font-size: 15px;
      transition: all 0.2s;
      background-color: #f9fbfd;
      box-sizing: border-box;
    }
    
    .form-group textarea:focus,
    .form-group input[type="text"]:focus,
    .form-group input[type="url"]:focus,
    .form-group input[type="date"]:focus,
    .form-group select:focus {
      border-color: #0099ff;
      outline: none;
      box-shadow: 0 0 0 3px rgba(0, 153, 255, 0.15);
      background-color: #fff;
    }
    
    .form-group textarea {
      min-height: 120px;
      resize: vertical;
    }
    
    .form-help {
      font-size: 12px;
      color: #666;
      margin-top: 6px;
    }
    
    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      margin-top: 30px;
    }
    
    .cancel-btn {
      padding: 10px 20px;
      background-color: #f1f1f1;
      color: #333;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
    }
    
    .cancel-btn:hover {
      background-color: #e0e0e0;
      transform: translateY(-2px);
    }
    
    .submit-btn {
      padding: 10px 24px;
      background-color: #0099ff;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
    }
    
    .submit-btn:hover {
      background-color: #0080dd;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 153, 255, 0.3);
    }

    .char-counter {
      font-size: 12px;
      color: #777;
      text-align: right;
      margin-top: 5px;
    }
    
    .char-counter.warning {
      color: #f0ad4e;
    }
    
    .char-counter.limit {
      color: #d9534f;
      font-weight: bold;
    }
    
    /* Radio button styling */
    .radio-group {
      display: flex;
      gap: 20px;
      margin: 15px 0;
    }
    
    .radio-group label {
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      padding: 10px 15px;
      background-color: #f8f9fa;
      border-radius: 8px;
      transition: all 0.2s;
      font-weight: 500;
    }
    
    .radio-group label:hover {
      background-color: #e9f5ff;
    }
    
    .radio-group input[type="radio"] {
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      width: 18px;
      height: 18px;
      min-width: 18px;
      min-height: 18px;
      max-width: 18px;
      max-height: 18px;
      flex: 0 0 18px;
      border: 2px solid #ccc;
      border-radius: 50%;
      outline: none;
      transition: all 0.2s;
      position: relative;
      padding: 0;
      margin: 0;
      vertical-align: middle;
      box-sizing: border-box;
    }
    
    .radio-group input[type="radio"]:checked {
      border-color: #0099ff;
    }
    
    .radio-group input[type="radio"]:checked::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 10px;
      height: 10px;
      background-color: #0099ff;
      border-radius: 50%;
    }

    /* File upload styling */
    .file-upload-container {
      border: 2px dashed #0099ff;
      padding: 25px;
      border-radius: 12px;
      text-align: center;
      margin-bottom: 15px;
      background-color: #f0f7ff;
      transition: all 0.2s;
    }
    
    .file-upload-container:hover {
      background-color: #e9f5ff;
    }
    
    .file-upload-container input[type="file"] {
      width: 100%;
      cursor: pointer;
      font-size: 15px;
    }
    
    .file-list {
      margin-top: 15px;
      text-align: left;
      max-height: 200px;
      overflow-y: auto;
      padding-right: 5px;
    }
    
    .file-item {
      display: flex;
      align-items: center;
      padding: 10px 15px;
      background: #fff;
      margin: 8px 0;
      border-radius: 8px;
      border: 1px solid #e9ecef;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      transition: transform 0.2s;
    }
    
    /* Version notes styling */
    .version-notes {
      background-color: #f0f7ff;
      padding: 20px;
      border-radius: 8px;
      border-left: 4px solid #0099ff;
      margin-top: 15px;
    }
    
    /* Form rows for the modal */
    .form-row {
      display: flex;
      gap: 30px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    
    .form-row .form-group {
      flex: 1 1 240px;
      min-width: 0; /* Prevents flex item overflow */
    }
    
    @media (max-width: 768px) {
      .modal-content {
        width: 95%;
        padding: 20px;
        margin: 10% auto;
      }
      
      .form-row {
        flex-direction: column;
        gap: 15px;
      }
      
      .radio-group {
        flex-direction: column;
        gap: 10px;
      }
    }

    /* Comment section styles with specific IDs */
    #form {
      margin-top: 30px;
      padding: 20px;
      background-color: #f8f9fa;
      border-radius: 8px;
      border: 1px solid #dee2e6;
    }
    
    #form h3 {
      margin-top: 0;
      color: #333;
      border-bottom: 1px solid #dee2e6;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }
    
    #form textarea {
      width: 98%;
      padding: 10px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      resize: vertical;
      margin-bottom: 15px;
      box-sizing: border-box;
    }
    
    #form button {
      background-color: #0099ff;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    
    #form button:hover {
      background-color: #007bff;
    }
    
    .comment-item {
      margin-bottom: 15px;
      padding: 15px;
      border-bottom: 1px solid #dee2e6;
      background-color: #fff;
      border-radius: 4px;
    }
    
    .comment-item strong {
      color: #495057;
      font-size: 16px;
    }
    
    .comment-item small {
      color: #6c757d;
      font-size: 12px;
      margin-left: 5px;
    }
    
    .comment-item p {
      margin-top: 10px;
      color: #212529;
      line-height: 1.5;
    }
    
    .no-comments {
      color: #6c757d;
      font-style: italic;
    }
    
    /* Add specific styling for the Edit Dataset button */
    #editDatasetBtn {
        padding: 10px 20px !important;
        font-size: 16px !important;
        background-color: #0099ff !important;
        color: white !important;
        border: none !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        font-weight: 600 !important;
        transition: all 0.2s !important;
        text-decoration: none !important;
    }
    
    #editDatasetBtn:hover {
        background-color: #007bff !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(0, 153, 255, 0.3) !important;
    }

    /* Comment Submit Button */
    #commentSubmitBtn {
        background-color: #0099ff !important;
        color: white !important;
        border: none !important;
        padding: 10px 20px !important;
        border-radius: 4px !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        transition: background-color 0.2s !important;
    }
    
    #commentSubmitBtn:hover {
        background-color: #007bff !important;
    }
    
    /* Download button */
    #downloadBtn {
        background-color: #0099ff !important;
        color: white !important;
        font-weight: bold !important;
        padding: 8px 14px !important;
        border-radius: 6px !important;
        text-decoration: none !important;
        transition: background-color 0.3s ease !important;
        display: inline-block !important;
    }
    
    #downloadBtn:hover {
        background-color: #007acc !important;
    }

    /* Comment actions */
    .comment-actions {
      position: absolute;
      top: 10px;
      right: 10px;
      display: flex;
      gap: 5px;
    }
    
    .btn-edit-comment,
    .btn-delete-comment {
      background: none;
      border: none;
      font-size: 12px;
      cursor: pointer;
      padding: 2px 8px;
      border-radius: 3px;
      color: #fff;
    }
    
    .btn-edit-comment {
      background-color: #28a745;
    }
    
    .btn-delete-comment {
      background-color: #dc3545;
    }
    
    .btn-edit-comment:hover {
      background-color: #218838;
    }
    
    .btn-delete-comment:hover {
      background-color: #c82333;
    }
    
    #editCommentModal .modal-content {
      max-width: 500px;
    }
    
    #editCommentModal textarea {
      width: 100%;
      padding: 10px;
      min-height: 100px;
      margin-bottom: 15px;
    }
    
    /* Comment reply styles */
    .comment-reply-actions {
      margin-top: 10px;
    }
    
    .btn-reply {
      background: none;
      border: none;
      color: #0099ff;
      font-size: 13px;
      cursor: pointer;
      padding: 2px 8px;
      transition: color 0.2s;
    }
    
    .btn-reply:hover {
      color: #007bff;
      text-decoration: underline;
    }
    
    .reply-form {
      margin: 10px 0 10px 20px;
      padding: 10px;
      border-left: 3px solid #17a2b8;
      background-color: #f8f9fa;
      border-radius: 0 4px 4px 0;
    }
    
    .reply-form textarea {
      width: 100%;
      padding: 8px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      resize: vertical;
      margin-bottom: 10px;
    }
    
    .reply-form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    
    .btn-cancel-reply {
      background-color: #f8f9fa;
      border: 1px solid #ced4da;
      border-radius: 4px;
      padding: 4px 10px;
      cursor: pointer;
      font-size: 12px;
    }
    
    .btn-submit-reply {
      background-color: #17a2b8;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 4px 10px;
      cursor: pointer;
      font-size: 12px;
      transition: background-color 0.2s;
    }
    
    .btn-submit-reply:hover {
      background-color: #138496;
    }
    
    .comment-replies {
      margin-left: 20px;
      margin-top: 10px;
      border-left: 2px solid #e9ecef;
      padding-left: 15px;
    }
    
    .reply-item {
      background-color: #f8f9fa;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 8px;
      border-left: 2px solid #17a2b8;
    }
    
    .reply-item strong {
      color: #495057;
      font-size: 14px;
    }
    
    .reply-item small {
      color: #6c757d;
      font-size: 11px;
      margin-left: 5px;
    }
    
    .reply-item p {
      margin-top: 5px;
      color: #212529;
      line-height: 1.4;
      font-size: 14px;
    }
    
    /* Position adjustment for comment items */
    .comment-item {
      position: relative;
    }
  </style>
</head>
<body>
<video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>

<?php if (isset($_GET['switched']) && $_GET['switched'] == '1'): ?>
<div class="notification" id="mydataset_versionMessage">
    <i class="fas fa-check-circle"></i> Version switched successfully
</div>
<?php endif; ?>

<?php if (isset($_GET['comment_added']) && $_GET['comment_added'] == '1'): ?>
<div class="notification comment" id="mydataset_commentMessage">
    <i class="fas fa-comment"></i> Comment added successfully
</div>
<?php endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="notification" id="mydataset_successMessage">
    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
    <?php unset($_SESSION['success_message']); ?>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="notification comment" id="mydataset_errorMessage" style="background-color: #dc3545;">
    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
    <?php unset($_SESSION['error_message']); ?>
</div>
<?php endif; ?>

<script>
// Success Message Animation
document.addEventListener('DOMContentLoaded', function() {
    // Handle version message
    const versionMessage = document.getElementById('mydataset_versionMessage');
    if (versionMessage) {
        setTimeout(() => {
            versionMessage.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            versionMessage.classList.add('hide');
            setTimeout(() => {
                versionMessage.remove();
            }, 300);
        }, 3000);
    }
    
    // Handle comment message
    const commentMessage = document.getElementById('mydataset_commentMessage');
    if (commentMessage) {
        setTimeout(() => {
            commentMessage.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            commentMessage.classList.add('hide');
            setTimeout(() => {
                commentMessage.remove();
            }, 300);
        }, 3000);
    }
    
    // Handle success message
    const successMessage = document.getElementById('mydataset_successMessage');
    if (successMessage) {
        setTimeout(() => {
            successMessage.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            successMessage.classList.add('hide');
            setTimeout(() => {
                successMessage.remove();
            }, 300);
        }, 3000);
    }
    
    // Handle error message
    const errorMessage = document.getElementById('mydataset_errorMessage');
    if (errorMessage) {
        setTimeout(() => {
            errorMessage.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            errorMessage.classList.add('hide');
            setTimeout(() => {
                errorMessage.remove();
            }, 300);
        }, 4000); // Show errors a bit longer
    }
});
</script>

<header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
            <h2><?php echo !empty($dataset['title']) ? htmlspecialchars($dataset['title']) : 'Untitled Dataset'; ?></h2>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
        </nav>
    </header>
    
  <div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h2>
          <?php echo htmlspecialchars($dataset['title']); ?>
          <?php if (isset($currentVersion)): ?>
          <span class="version-indicator"><?php echo htmlspecialchars($currentVersion['version_number']); ?></span>
          <?php endif; ?>
        </h2>
      </div>
      <?php if ($dataset['user_id'] == $user_id): ?>
      <div>
          <button id="editDatasetBtn" onclick="openEditModal()" class="btn btn-primary">
              <i class="fas fa-edit"></i> Edit Dataset
          </button>
      </div>
      <?php endif; ?>
    </div>
    
    <!-- Version tabs -->
    <div class="version-tabs">
      <?php if (isset($versionsResult) && mysqli_num_rows($versionsResult) > 0): ?>
        <?php mysqli_data_seek($versionsResult, 0); ?>
        <?php while ($version = mysqli_fetch_assoc($versionsResult)): ?>
          <a href="mydataset.php?id=<?php echo $dataset_id; ?>&switch_to=<?php echo $version['version_id']; ?>" 
             class="version-tab <?php echo ($version['is_current'] ? 'active' : ''); ?>">
            <?php echo htmlspecialchars($version['version_number']); ?>
          </a>
        <?php endwhile; ?>
        
        <?php if ($totalVersions > 5): ?>
          <a href="dataset_versions.php?batch_id=<?php echo $batch_id; ?>" class="version-tab history">
            <i class="fas fa-history"></i> History (<?php echo $totalVersions; ?>)
          </a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    
    <div class="header-section">
      <div class="header-left">
      <span><?php echo !empty($dataset['source']) ? htmlspecialchars($dataset['source']) : 'Unknown'; ?></span>
      <h1>
        <?php echo !empty($dataset['title']) ? htmlspecialchars($dataset['title']) : 'Untitled Dataset'; ?>
        <?php if (isset($dataset['visibility'])): ?>
        <span class="visibility-badge <?php echo strtolower($dataset['visibility']); ?>">
          <?php echo $dataset['visibility']; ?>
        </span>
        <?php endif; ?>
      </h1>
        </div>
    </div>
    <div class="title-section">
      <p><?php echo !empty($dataset['description']) ? nl2br(htmlspecialchars($dataset['description'])) : 'No description available.'; ?></p>
    </div>
      

    <div class="info-grid">
      <div class="info-box">
        <h3>Additional Information</h3>
        <div class="info-item">
          <strong>Time Period</strong>
          <?php 
              // Convert date format from Y-m to month name and year
              function formatPeriodDate($period) {
                  if (!$period || $period === '0000-00' || empty($period)) {
                      return 'Unknown';
                  }
                  
                  // If it's already in YYYY-MM-DD format, extract just year and month
                  if (strlen($period) > 7) {
                      $period = substr($period, 0, 7); // Get YYYY-MM portion
                  }
                  
                  $date = DateTime::createFromFormat('Y-m', $period);
                  return $date ? $date->format('F Y') : 'Invalid date';
              }

              $start = formatPeriodDate($dataset['start_period']);
              $end = formatPeriodDate($dataset['end_period']);

              echo htmlspecialchars("$start – $end");
          ?>
        </div>
        <div class="info-item">
          <strong>Location</strong>
          <span><?php echo !empty($dataset['location']) ? htmlspecialchars($dataset['location']) : 'N/A'; ?></span>
          </div>
        <div class="info-item">
          <strong>Source</strong>
          <span><?php echo !empty($dataset['source']) ? htmlspecialchars($dataset['source']) : 'Unknown'; ?></span>
        </div>
        <div class="info-item">
            <strong>Links</strong>
            <a href="<?php echo formatUrl($dataset['link']); ?>" target="_blank">
                <?php echo !empty($dataset['link']) ? htmlspecialchars($dataset['link']) : 'No link available'; ?>
            </a>
        </div>
      </div>

      <div class="resources-box">
        <h3>Data and Resources</h3>
        
        <?php
        // Get the current version information
        if (isset($currentVersion) && $currentVersion) {
            $currentVersionId = $currentVersion['version_id'];
            
            // Get the current version details
            $versionDetailSql = "
                SELECT * FROM datasetversions 
                WHERE version_id = ?
            ";
            $stmt = mysqli_prepare($conn, $versionDetailSql);
            mysqli_stmt_bind_param($stmt, 'i', $currentVersionId);
            mysqli_stmt_execute($stmt);
            $versionDetail = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            
            // Get the main dataset file
            $resourcesSql = "
                SELECT * FROM datasets 
                WHERE dataset_id = ?
            ";
            $stmt = mysqli_prepare($conn, $resourcesSql);
            mysqli_stmt_bind_param($stmt, 'i', $dataset_id);
            mysqli_stmt_execute($stmt);
            $resourcesResult = mysqli_stmt_get_result($stmt);
            
            // Get additional files from the same version directory if they exist
            $additionalFiles = [];
            if (isset($versionDetail['file_path'])) {
                $versionDir = dirname($versionDetail['file_path']);
                if (file_exists($versionDir) && is_dir($versionDir)) {
                    $files = scandir($versionDir);
                    foreach ($files as $file) {
                        if ($file != '.' && $file != '..' && !is_dir($versionDir . '/' . $file) && $versionDir . '/' . $file != $dataset['file_path']) {
                            $additionalFiles[] = [
                                'file_path' => $versionDir . '/' . $file,
                                'dataset_id' => $dataset_id
                            ];
                        }
                    }
                }
            }
            
            // Display the main dataset file and additional files
            if ($resourcesResult && mysqli_num_rows($resourcesResult) > 0):
            ?>
            <div class="resources-scrollable" style="display: flex; flex-direction: column; gap: 10px;">
              <?php
              // Display main dataset file
              while ($ds = mysqli_fetch_assoc($resourcesResult)): ?>
              <div style="background: #ffffff; border: 1px solid #ddd; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center;">
                <div>
                  <?php if (!isset($dataset['visibility']) || $dataset['visibility'] == 'Public' || $dataset['user_id'] == $user_id): ?>
                  <a href="download.php?dataset_id=<?php echo $ds['dataset_id']; ?>" style="color: #007BFF; text-decoration: none;">
                    <?php echo !empty($ds['file_path']) ? basename($ds['file_path']) : 'No file uploaded'; ?>
                  </a>
                  <?php else: ?>
                  <span style="color: #666;">
                    <?php echo !empty($ds['file_path']) ? basename($ds['file_path']) : 'No file uploaded'; ?>
                  </span>
                  <?php endif; ?>
                </div>
                <?php if (!isset($dataset['visibility']) || $dataset['visibility'] == 'Public' || $dataset['user_id'] == $user_id): ?>
                  <a href="download.php?dataset_id=<?php echo $ds['dataset_id']; ?>" class="download-btn">⬇️ Download</a>
                <?php else: ?>
                  <span class="download-btn" style="background-color: #ccc; cursor: not-allowed;">⬇️ Private</span>
                <?php endif; ?>
              </div>
              <?php endwhile; ?>
              
              <?php 
              // Display additional files
              foreach ($additionalFiles as $addFile): ?>
              <div style="background: #ffffff; border: 1px solid #ddd; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center;">
                <div>
                  <?php if (!isset($dataset['visibility']) || $dataset['visibility'] == 'Public' || $dataset['user_id'] == $user_id): ?>
                  <a href="download.php?direct_file=<?php echo urlencode($addFile['file_path']); ?>" style="color: #007BFF; text-decoration: none;">
                    <?php echo htmlspecialchars(basename($addFile['file_path'])); ?>
                  </a>
                  <?php else: ?>
                  <span style="color: #666;">
                    <?php echo htmlspecialchars(basename($addFile['file_path'])); ?>
                  </span>
                  <?php endif; ?>
                </div>
                <?php if (!isset($dataset['visibility']) || $dataset['visibility'] == 'Public' || $dataset['user_id'] == $user_id): ?>
                  <a href="download.php?direct_file=<?php echo urlencode($addFile['file_path']); ?>" class="download-btn">⬇️ Download</a>
                <?php else: ?>
                  <span class="download-btn" style="background-color: #ccc; cursor: not-allowed;">⬇️ Private</span>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
              <p>No resources available.</p>
            <?php endif;
        } else {
            echo "<p>No resources available.</p>";
        }
        ?>
      </div>
      
    </div>
<div id="form">
        <hr>
      <h3 id="comments">Comments</h3>

      <!-- Comment Form -->
      <form method="POST" action="add_comment.php">
          <input type="hidden" name="dataset_id" value="<?php echo $dataset_id; ?>">
          <input type="hidden" name="return_page" value="mydataset.php">
          <textarea name="comment_text" rows="4" placeholder="Write your comment here..." required></textarea><br>
          <button type="submit" id="commentSubmitBtn">Post Comment</button>
      </form>

      <br>

      <!-- Comment List -->
      <?php
      $commentSql = "
          SELECT dc.comment_id, dc.comment_text, dc.timestamp, dc.user_id, u.first_name, u.last_name 
          FROM datasetcomments dc
          JOIN users u ON dc.user_id = u.user_id
          WHERE dc.dataset_id = ?
          ORDER BY dc.timestamp DESC
      ";

      $stmt = mysqli_prepare($conn, $commentSql);
      mysqli_stmt_bind_param($stmt, 'i', $dataset_id);
      mysqli_stmt_execute($stmt);
      $commentResult = mysqli_stmt_get_result($stmt);

      if (mysqli_num_rows($commentResult) > 0) {
          while ($comment = mysqli_fetch_assoc($commentResult)) {
              echo '<div class="comment-item" id="comment-' . $comment['comment_id'] . '">';
              echo '<strong>' . htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) . '</strong>';
              echo '<small>' . date('F j, Y \a\t g:i a', strtotime($comment['timestamp'])) . '</small>';
              
              // Add edit and delete buttons for comment owner
              if ($user_id == $comment['user_id']) {
                  echo '<div class="comment-actions">';
                  echo '<button class="btn-edit-comment" onclick="editComment(' . $comment['comment_id'] . ', \'' . htmlspecialchars(addslashes($comment['comment_text'])) . '\')">Edit</button>';
                  echo '<button class="btn-delete-comment" onclick="deleteComment(' . $comment['comment_id'] . ')">Delete</button>';
                  echo '</div>';
              }
              
              echo '<p>' . nl2br(htmlspecialchars($comment['comment_text'])) . '</p>';
              
              // Add reply button
              echo '<div class="comment-reply-actions">';
              echo '<button class="btn-reply" onclick="showReplyForm(' . $comment['comment_id'] . ')">Reply</button>';
              echo '</div>';
              
              // Add hidden reply form
              echo '<div class="reply-form" id="reply-form-' . $comment['comment_id'] . '" style="display: none;">';
              echo '<form method="POST" action="add_reply.php">';
              echo '<input type="hidden" name="comment_id" value="' . $comment['comment_id'] . '">';
              echo '<input type="hidden" name="dataset_id" value="' . $dataset_id . '">';
              echo '<input type="hidden" name="return_page" value="mydataset.php">';
              echo '<textarea name="reply_text" rows="2" placeholder="Write your reply..." required></textarea>';
              echo '<div class="reply-form-actions">';
              echo '<button type="button" class="btn-cancel-reply" onclick="hideReplyForm(' . $comment['comment_id'] . ')">Cancel</button>';
              echo '<button type="submit" class="btn-submit-reply">Reply</button>';
              echo '</div>';
              echo '</form>';
              echo '</div>';
              
              // Get and display replies for this comment
              $repliesSql = "
                  SELECT r.*, u.first_name, u.last_name 
                  FROM comment_replies r
                  JOIN users u ON r.user_id = u.user_id
                  WHERE r.comment_id = ?
                  ORDER BY r.timestamp ASC
              ";
              $repliesStmt = mysqli_prepare($conn, $repliesSql);
              mysqli_stmt_bind_param($repliesStmt, 'i', $comment['comment_id']);
              mysqli_stmt_execute($repliesStmt);
              $repliesResult = mysqli_stmt_get_result($repliesStmt);
              
              if (mysqli_num_rows($repliesResult) > 0) {
                  echo '<div class="comment-replies">';
                  while ($reply = mysqli_fetch_assoc($repliesResult)) {
                      echo '<div class="reply-item" id="reply-' . $reply['reply_id'] . '">';
                      echo '<strong>' . htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']) . '</strong>';
                      echo '<small>' . date('F j, Y \a\t g:i a', strtotime($reply['timestamp'])) . '</small>';
                      echo '<p>' . nl2br(htmlspecialchars($reply['reply_text'])) . '</p>';
                      echo '</div>';
                  }
                  echo '</div>';
              }
              
              echo '</div>'; // End comment-item
          }
      } else {
          echo '<p class="no-comments">No comments yet.</p>';
      }
      ?>
      </div>

  </div>
  
  <!-- Add Edit Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content" style="width: 80%; max-width: 800px;">
        <span class="close" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
        <h3>Edit Dataset</h3>
        
        <form method="POST" action="update_dataset.php" enctype="multipart/form-data">
            <input type="hidden" name="dataset_id" value="<?php echo $dataset_id; ?>">
            <input type="hidden" name="batch_id" value="<?php echo $batch_id; ?>">
            <input type="hidden" name="return_to" value="mydataset.php">
            
            <div class="form-group">
                <label>Update Type:</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="update_type" value="update" checked> Update Current Version
                    </label>
                    <label>
                        <input type="radio" name="update_type" value="new_version"> Create New Version
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($dataset['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea name="description" id="description" required><?php echo htmlspecialchars($dataset['description']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_period">Start Period:</label>
                    <input type="date" name="start_period" id="start_period" value="<?php echo $dataset['start_period']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_period">End Period:</label>
                    <input type="date" name="end_period" id="end_period" value="<?php echo $dataset['end_period']; ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="source">Source:</label>
                    <input type="text" name="source" id="source" value="<?php echo htmlspecialchars($dataset['source']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="link">Link:</label>
                    <input type="url" name="link" id="link" value="<?php echo htmlspecialchars($dataset['link']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="location">Location:</label>
                <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($dataset['location']); ?>" required>
            </div>

            <div class="form-group">
                <label for="files">Files:</label>
                <div class="file-upload-container">
                    <input type="file" name="files[]" id="files" multiple>
                    <div id="fileList" class="file-list"></div>
                </div>
                <p class="form-help">Select new files to upload. Leave empty to keep existing files.</p>
            </div>

            <div class="form-group version-notes" style="display: none;">
                <label for="change_notes">Version Change Notes:</label>
                <textarea name="change_notes" id="change_notes" rows="3" placeholder="Describe what changed in this version..."></textarea>
                <p class="form-help">These notes will help users understand what changed in this version.</p>
            </div>

            <div class="form-actions">
                <button type="button" id="cancelEditBtn" class="cancel-btn" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                <button type="submit" id="saveChangesBtn" class="submit-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Add modal for comment editing -->
<div id="editCommentModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('editCommentModal').style.display='none'">&times;</span>
    <h3>Edit Comment</h3>
    
    <form method="POST" action="edit_comment.php">
      <input type="hidden" name="comment_id" id="edit_comment_id">
      <input type="hidden" name="dataset_id" value="<?php echo $dataset_id; ?>">
      <input type="hidden" name="return_page" value="mydataset.php">
      <div class="form-group">
        <textarea name="comment_text" id="edit_comment_text" rows="4" required></textarea>
      </div>
      <div class="form-actions">
        <button type="button" class="cancel-btn" onclick="document.getElementById('editCommentModal').style.display='none'">Cancel</button>
        <button type="submit" class="submit-btn">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Add modal for comment deletion -->
<div id="deleteCommentModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('deleteCommentModal').style.display='none'">&times;</span>
    <h3>Delete Comment</h3>
    <p>Are you sure you want to delete this comment? This action cannot be undone.</p>
    
    <form method="POST" action="delete_comment.php">
      <input type="hidden" name="comment_id" id="delete_comment_id">
      <input type="hidden" name="dataset_id" value="<?php echo $dataset_id; ?>">
      <input type="hidden" name="return_page" value="mydataset.php">
      <div class="form-actions">
        <button type="button" class="cancel-btn" onclick="document.getElementById('deleteCommentModal').style.display='none'">Cancel</button>
        <button type="submit" class="submit-btn" style="background-color: #dc3545;">Delete Comment</button>
      </div>
    </form>
  </div>
</div>

<script>
    function openEditModal() {
        document.getElementById('editModal').style.display = 'block';
    }

    // Function to edit a comment
    function editComment(commentId, commentText) {
        document.getElementById('edit_comment_id').value = commentId;
        document.getElementById('edit_comment_text').value = commentText.replace(/\\'/g, "'");
        document.getElementById('editCommentModal').style.display = 'block';
    }

    // Function to delete a comment with custom modal
    function deleteComment(commentId) {
        document.getElementById('delete_comment_id').value = commentId;
        document.getElementById('deleteCommentModal').style.display = 'block';
    }

    // Function to show reply form
    function showReplyForm(commentId) {
        document.getElementById('reply-form-' + commentId).style.display = 'block';
    }
    
    // Function to hide reply form
    function hideReplyForm(commentId) {
        document.getElementById('reply-form-' + commentId).style.display = 'none';
    }

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
</script>
</body>
</html>
