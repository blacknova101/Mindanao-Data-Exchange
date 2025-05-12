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
    SELECT d.*, u.first_name, u.last_name 
    FROM datasets d
    JOIN users u ON d.user_id = u.user_id 
    WHERE d.dataset_id = $dataset_id
";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "Dataset not found.";
    exit;
}

$dataset = mysqli_fetch_assoc($result);
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
        display: inline-block;
        padding: 10px 15px;
        background-color: #0099ff;
        color: white;
        font-weight: bold;
        border-radius: 5px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .download-btn:hover {
        background-color: #007acc;
    }

    .file-download {
        margin-right: 5px; /* Adds space between the icon and the text */
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


    @media (max-width: 768px) {
      .header-section {
        flex-direction: column;
      }
      .info-grid {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
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
      <h1><?php echo !empty($dataset['title']) ? htmlspecialchars($dataset['title']) : 'Untitled Dataset'; ?></h1>
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
              function formatPeriod($period) {
                  if (!$period || $period === '0000-00') {
                      return 'Unknown';
                  }

                  $date = DateTime::createFromFormat('Y-m', $period);
                  return $date ? $date->format('F Y') : 'Invalid date';
              }

              $start = formatPeriod($dataset['start_period']);
              $end = formatPeriod($dataset['end_period']);

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
        <div class="file-item">
          <i>📄</i>
            <a href="<?php echo !empty($dataset['file_path']) ? htmlspecialchars($dataset['file_path']) : '#'; ?>">
              <?php echo !empty($dataset['file_path']) ? basename($dataset['file_path']) : 'No file uploaded'; ?>
            </a>
            <div class="dataset-download">
            <a href="<?php echo !empty($dataset['file_path']) ? htmlspecialchars($dataset['file_path']) : '#'; ?>" download class="download-btn">
                  <span class="file-download">⬇️</span> Download
              </a>
          </div>
        </div>
        
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
  
</body>
</html>
