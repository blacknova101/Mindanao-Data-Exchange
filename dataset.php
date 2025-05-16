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

// Handle dataset access request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_access'])) {
    if (!$user_id) {
        echo "You must be logged in to request access.";
        exit;
    }

    // Process the access request with reason and document
    $request_reason = isset($_POST['request_reason']) ? mysqli_real_escape_string($conn, substr($_POST['request_reason'], 0, 500)) : '';
    
    // Handle file upload
    $document_path = '';
    if (isset($_FILES['verification_document']) && $_FILES['verification_document']['error'] == 0) {
        $upload_dir = 'verification_documents/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['verification_document']['name'], PATHINFO_EXTENSION);
        $file_name = 'verification_' . $dataset_id . '_' . $user_id . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;
        
        // Move uploaded file to target directory
        if (move_uploaded_file($_FILES['verification_document']['tmp_name'], $target_file)) {
            $document_path = $target_file;
        } else {
            $_SESSION['request_message'] = "Error uploading verification document.";
            header("Location: dataset.php?id=$dataset_id");
            exit;
        }
    }

    // Check if a request already exists
    $checkRequestSql = "SELECT * FROM dataset_access_requests 
                        WHERE dataset_id = $dataset_id AND requester_id = $user_id";
    $checkResult = mysqli_query($conn, $checkRequestSql);
    
    $canSubmitNewRequest = true;
    $requestExists = mysqli_num_rows($checkResult) > 0;
    
    if ($requestExists) {
        $existingRequest = mysqli_fetch_assoc($checkResult);
        // Allow new request only if previous one was rejected or no pending/approved request exists
        if ($existingRequest['status'] === 'Pending' || $existingRequest['status'] === 'Approved') {
            $canSubmitNewRequest = false;
        }
    }
    
    if ($canSubmitNewRequest) {
        // If request was rejected before, update the existing request
        if ($requestExists) {
            $updateRequest = "
                UPDATE dataset_access_requests
                SET request_date = NOW(),
                    status = 'Pending',
                    reason = '$request_reason',
                    verification_document = '$document_path'
                WHERE dataset_id = $dataset_id 
                AND requester_id = $user_id
            ";
            if (mysqli_query($conn, $updateRequest)) {
                $_SESSION['request_message'] = "Access request resubmitted successfully.";
            } else {
                $_SESSION['request_message'] = "Error resubmitting request: " . mysqli_error($conn);
            }
        } else {
            // Insert brand new request
            $insertRequest = "
                INSERT INTO dataset_access_requests (
                    dataset_id, 
                    requester_id, 
                    owner_id, 
                    request_date, 
                    status, 
                    reason,
                    verification_document
                ) VALUES (
                    $dataset_id, 
                    $user_id, 
                    (SELECT user_id FROM datasets WHERE dataset_id = $dataset_id), 
                    NOW(), 
                    'Pending',
                    '$request_reason',
                    '$document_path'
                )
            ";
            if (mysqli_query($conn, $insertRequest)) {
                $_SESSION['request_message'] = "Access request submitted successfully.";
            } else {
                $_SESSION['request_message'] = "Error submitting request: " . mysqli_error($conn);
            }
        }
    } else {
        if ($existingRequest['status'] === 'Approved') {
            $_SESSION['request_message'] = "Your request has already been approved. You can download this dataset.";
        } else {
            $_SESSION['request_message'] = "You already have a pending request for this dataset.";
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: dataset.php?id=$dataset_id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {
    if (!$user_id) {
        echo "You must be logged in to comment.";
        exit;
    }

    $comment_text = mysqli_real_escape_string($conn, $_POST['comment_text']);

    $insertComment = "
        INSERT INTO datasetcomments (dataset_id, user_id, comment_text, timestamp)
        VALUES ($dataset_id, $user_id, '$comment_text', NOW())
    ";
    mysqli_query($conn, $insertComment);
}

$sql = "
    SELECT 
        d.*, 
        u.first_name, 
        u.last_name,
        u.email,
        db.visibility,
        DATE_FORMAT(d.start_period, '%M %e, %Y') AS formatted_start_period,
        DATE_FORMAT(d.end_period, '%M %e, %Y') AS formatted_end_period
    FROM datasets d
    JOIN users u ON d.user_id = u.user_id 
    JOIN dataset_batches db ON d.dataset_batch_id = db.dataset_batch_id
    WHERE d.dataset_id = $dataset_id
";


$result = mysqli_query($conn, $sql); // <--- Moved here

if (!$result || mysqli_num_rows($result) === 0) {
    echo "Dataset not found.";
    exit;
}

$dataset = mysqli_fetch_assoc($result);
$batch_id = $dataset['dataset_batch_id']; // Assuming correct column name

// Store whether this is a private dataset not owned by current user
$is_private_unowned = ($dataset['visibility'] == 'Private' && $dataset['user_id'] != $_SESSION['user_id']);

// Check if user has already requested access to this dataset
$has_requested_access = false;
$has_approved_access = false;
$has_rejected_access = false;
$request_status = '';

if ($is_private_unowned) {
    $requestCheckSql = "SELECT * FROM dataset_access_requests 
                       WHERE dataset_id = $dataset_id AND requester_id = $user_id";
    $requestResult = mysqli_query($conn, $requestCheckSql);
    if ($requestResult && mysqli_num_rows($requestResult) > 0) {
        $request = mysqli_fetch_assoc($requestResult);
        $request_status = $request['status'];
        $has_requested_access = ($request_status === 'Pending');
        $has_approved_access = ($request_status === 'Approved');
        $has_rejected_access = ($request_status === 'Rejected');
    }
}

$batchDatasetsSql = "
    SELECT * FROM datasets
    WHERE dataset_batch_id = $batch_id
";
$batchDatasetsResult = mysqli_query($conn, $batchDatasetsSql);


$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "Dataset not found.";
    exit;
}

include 'batch_analytics.php';

if (!isset($_SESSION['viewed_batches'])) {
    $_SESSION['viewed_batches'] = [];
}

if (!in_array($batch_id, $_SESSION['viewed_batches'])) {
    increment_batch_views($conn, $batch_id);
    $_SESSION['viewed_batches'][] = $batch_id;
}
$analytics = get_batch_analytics($conn, $batch_id);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo !empty($dataset['title']) ? htmlspecialchars($dataset['title']) : 'Untitled Dataset'; ?></title>
  <style>
     body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f6f9;
      color: #333;
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

    .container {
      max-width: 1100px;
      margin: 40px auto;
      padding: 20px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
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
      font-style: normal;
      font-size: 18px;
      margin-right: 10px;
    }

    .file-item a {
      flex-grow: 1;
      text-decoration: none;
      color: #007BFF;
    }

    .file-item a:hover {
      text-decoration: underline;
    }

    .download-btn {
        background-color: #0099ff;
        color: white;
        font-weight: bold;
        padding: 8px 14px;
        border-radius: 6px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }
    .download-btn:hover {
        background-color: #007acc;
    }
    
    .request-btn {
        background-color: #ff9900;
        color: white;
        font-weight: bold;
        padding: 10px 16px;
        border-radius: 6px;
        text-decoration: none;
        transition: background-color 0.3s ease;
        display: inline-block;
        margin-top: 15px;
        cursor: pointer;
        border: none;
    }
    .request-btn:hover {
        background-color: #e68a00;
    }
    .request-btn:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
    }

    .file-download {
        margin-right: 5px; /* Adds space between the icon and the text */
    }
    
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
    
    form{
      width: 100%;
    }
    form textarea {
      width: 1080px;
      padding: 10px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
    form button {
      background-color: #007BFF;
      color: white;
      padding: 10px 16px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    form button:hover {
      background-color: #0056b3;
    }
    .resources-scrollable {
      max-height: 300px; /* Adjust height as needed */
      overflow-y: auto;
      padding-right: 10px; /* Optional: to avoid scrollbar overlapping text */
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
    
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
      color: #721c24;
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
    }
    .alert.success {
      color: #155724;
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
    }


    @media (max-width: 768px) {
      .header-section {
        flex-direction: column;
      }
      .info-grid {
        flex-direction: column;
      }
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
      background-color: rgba(0,0,0,0.4);
    }
    
    .modal-content {
      background-color: #fff;
      margin: 10% auto;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      width: 500px;
      max-width: 90%;
    }
    
    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    
    .close:hover,
    .close:focus {
      color: black;
      text-decoration: none;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }
    
    .form-group textarea {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      resize: vertical;
    }
    
    .form-group input[type="file"] {
      padding: 8px 0;
    }
    
    .form-help {
      font-size: 12px;
      color: #666;
      margin-top: 5px;
    }
    
    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }
    
    .cancel-btn {
      padding: 8px 16px;
      background-color: #f1f1f1;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    
    .submit-btn {
      padding: 8px 16px;
      background-color: #ff9900;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    
    .submit-btn:hover {
      background-color: #e68a00;
    }

    .char-counter {
      font-size: 12px;
      color: #777;
      text-align: right;
      margin-top: 5px;
    }
    
    /* Add styling for when the user approaches the limit */
    .char-counter.warning {
      color: #f0ad4e;
    }
    
    /* Add styling for when the user reaches the limit */
    .char-counter.limit {
      color: #d9534f;
      font-weight: bold;
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
            <h2><?php echo !empty($dataset['title']) ? htmlspecialchars($dataset['title']) : 'Untitled Dataset'; ?></h2>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
        </nav>
    </header>
  <div class="container">
    
  <div class="header-section">
      <div class="header-left">
      <span><?php echo !empty($dataset['source']) ? htmlspecialchars($dataset['source']) : 'Unknown'; ?></span>
      <h1>
        <?php echo !empty($dataset['title']) ? htmlspecialchars($dataset['title']) : 'Untitled Dataset'; ?>
        <span class="visibility-badge <?= strtolower($dataset['visibility']) ?>">
          <?= $dataset['visibility'] ?>
        </span>
      </h1>
      <?php if ($is_private_unowned): ?>
        <?php if ($has_approved_access): ?>
          <div style="margin-top: 10px; font-size: 14px; color: #d4edda;">
            Your access request was approved. You can download this private dataset.
          </div>
        <?php elseif ($has_requested_access): ?>
          <div style="margin-top: 10px; font-size: 14px; color: #fff3cd;">
            Your access request is pending. You'll be notified when it's approved.
          </div>
        <?php elseif ($has_rejected_access): ?>
          <div style="margin-top: 10px; font-size: 14px; color: #ffcccc;">
            Your previous request was rejected. You may submit a new request.
          </div>
        <?php else: ?>
          <div style="margin-top: 10px; font-size: 14px; color: #ffcccc;">
            Note: This is a private dataset. Request access to download it.
          </div>
        <?php endif; ?>
      <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($_SESSION['request_message'])): ?>
    <div class="alert <?php echo strpos($_SESSION['request_message'], 'successfully') !== false ? 'success' : ''; ?>">
        <?php echo $_SESSION['request_message']; ?>
        <?php unset($_SESSION['request_message']); ?>
    </div>
    <?php endif; ?>
    
    <div class="title-section">
      <p><?php echo !empty($dataset['description']) ? nl2br(htmlspecialchars($dataset['description'])) : 'No description available.'; ?></p>
      </div>
      

    <div class="info-grid">
      <div class="info-box">
        <h3>Additional Information</h3>
        <div class="info-item">
          <strong>Uploader</strong>
          <span><?php echo htmlspecialchars($dataset['first_name'] . ' ' . $dataset['last_name']); ?></span>
          <span>(<?php echo htmlspecialchars($dataset['email']); ?>)</span>
        </div>
        <div class="info-item">
          <strong>Time Period</strong>
          <?php 
            $start = !empty($dataset['formatted_start_period']) ? $dataset['formatted_start_period'] : 'Unknown';
            $end = !empty($dataset['formatted_end_period']) ? $dataset['formatted_end_period'] : 'Unknown';
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
        
        <?php if ($is_private_unowned): ?>
          <?php if ($has_approved_access): ?>
            <button type="button" class="request-btn" style="background-color: #28a745;" disabled>
              Access Granted
            </button>
          <?php elseif ($has_requested_access): ?>
            <button type="button" class="request-btn" disabled>
              Access Requested
            </button>
          <?php elseif ($has_rejected_access): ?>
            <button type="button" class="request-btn" style="background-color: #ff5722;" onclick="openRequestModal()">
              Request Again
            </button>
          <?php else: ?>
            <button type="button" class="request-btn" onclick="openRequestModal()">
              Request this Dataset
            </button>
          <?php endif; ?>
        <?php endif; ?>
      </div>
      <div class="resources-box">
        <h3>Data and Resources</h3>
        <?php if ($batchDatasetsResult && mysqli_num_rows($batchDatasetsResult) > 0): ?>
          <div class="resources-scrollable" style="display: flex; flex-direction: column; gap: 10px;">
            <?php while ($ds = mysqli_fetch_assoc($batchDatasetsResult)): ?>
              <div style="background: #ffffff; border: 1px solid #ddd; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center;">
                <div>
                  <?php if ($dataset['visibility'] == 'Public' || $dataset['user_id'] == $_SESSION['user_id'] || $has_approved_access): ?>
                  <a href="download.php?dataset_id=<?php echo $ds['dataset_id']; ?>" style="color: #007BFF; text-decoration: none;">
                    <?php echo htmlspecialchars(basename($ds['file_path'])); ?>
                  </a>
                  <?php else: ?>
                  <span style="color: #666;">
                    <?php echo htmlspecialchars(basename($ds['file_path'])); ?>
                  </span>
                  <?php endif; ?>
                </div>
                <?php if ($dataset['visibility'] == 'Public' || $dataset['user_id'] == $_SESSION['user_id'] || $has_approved_access): ?>
                  <a href="download.php?dataset_id=<?php echo $ds['dataset_id']; ?>" class="download-btn">⬇️ Download</a>              
                <?php else: ?>
                  <span class="download-btn" style="background-color: #ccc; cursor: not-allowed;">⬇️ Private</span>
                <?php endif; ?>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <p>No other datasets in this batch.</p>
        <?php endif; ?>
      </div>

      
    </div>
<div id="form">
        <hr>
      <h3>Comments</h3>

      <!-- Comment Form -->
      <form method="POST">
          <textarea name="comment_text" rows="4" placeholder="Write your comment here..." required></textarea><br>
          <button type="submit">Post Comment</button>
      </form>

      <br>

      <!-- Comment List -->
      <?php
      $commentSql = "
          SELECT dc.comment_text, dc.timestamp, u.first_name, u.last_name 
          FROM datasetcomments dc
          JOIN users u ON dc.user_id = u.user_id
          WHERE dc.dataset_id = $dataset_id
          ORDER BY dc.timestamp DESC
      ";

      $commentResult = mysqli_query($conn, $commentSql);

      if (mysqli_num_rows($commentResult) > 0) {
          while ($comment = mysqli_fetch_assoc($commentResult)) {
              echo "<div style='margin-bottom:15px; padding:10px; border-bottom:1px solid #ccc;'>";
              echo "<strong>" . htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) . "</strong><br>";
              echo "<small>" . $comment['timestamp'] . "</small>";
              echo "<p>" . nl2br(htmlspecialchars($comment['comment_text'])) . "</p>";
              echo "</div>";
          }
      } else {
          echo "<p>No comments yet.</p>";
      }
      ?>
      </div>

  </div>

  <!-- Add modal for access request -->
  <div id="requestModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="document.getElementById('requestModal').style.display='none'">&times;</span>
      <h3>Request Access to Dataset</h3>
      <p>Please provide a reason for requesting access to this dataset and upload a verification document.</p>
      
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="request_access" value="1">
        
        <div class="form-group">
          <label for="request_reason">Reason for Request:</label>
          <textarea name="request_reason" id="request_reason" rows="4" maxlength="500" required onkeyup="countChars()"></textarea>
          <div class="char-counter"><span id="charCount">0</span>/500 characters</div>
          <p class="form-help">Briefly explain why you need access to this dataset (max 500 characters). Be concise and specific.</p>
        </div>
        
        <div class="form-group">
          <label for="verification_document">Verification Document:</label>
          <input type="file" name="verification_document" id="verification_document">
          <p class="form-help">Upload a document that verifies your identity or affiliation (PDF, JPG, or PNG).</p>
        </div>
        
        <div class="form-actions">
          <button type="button" class="cancel-btn" onclick="document.getElementById('requestModal').style.display='none'">Cancel</button>
          <button type="submit" class="submit-btn">Submit Request</button>
        </div>
      </form>
    </div>
  </div>

</body>
</html>

<script>
        // Show category modal
        function showModal() {
            document.getElementById("categoryModal").style.display = "flex";
        }
        
        // Hide category modal
        function hideModal() {
            document.getElementById("categoryModal").style.display = "none";
        }
        
        // Open request modal and initialize character counter
        function openRequestModal() {
            document.getElementById('requestModal').style.display = 'block';
            countChars(); // Initialize character counter
        }
        
        // Count characters in the request reason textarea
        function countChars() {
            const textarea = document.getElementById('request_reason');
            const charCount = document.getElementById('charCount');
            const counter = document.querySelector('.char-counter');
            const maxLength = parseInt(textarea.getAttribute('maxlength'));
            const currentLength = textarea.value.length;
            
            charCount.textContent = currentLength;
            
            // Update styling based on how close to the limit
            if (currentLength >= maxLength) {
                counter.className = 'char-counter limit';
            } else if (currentLength >= maxLength * 0.8) {
                counter.className = 'char-counter warning';
            } else {
                counter.className = 'char-counter';
            }
        }
        
        // Initialize character counter on document load
        document.addEventListener('DOMContentLoaded', function() {
            // Reset the form when modal is closed
            document.querySelector('.close').addEventListener('click', function() {
                document.getElementById('request_reason').value = '';
                countChars();
            });
        });
    </script>
