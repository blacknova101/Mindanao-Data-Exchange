<?php
session_start();
include 'db_connection.php'; // Make sure this contains your $conn connection

// Get the title from URL
$title = isset($_GET['title']) ? $_GET['title'] : '';

// Sanitize input
$safe_title = mysqli_real_escape_string($conn, $title);

// Fetch dataset info from DB
$sql = "SELECT * FROM datasets WHERE title = '$safe_title'";
$result = mysqli_query($conn, $sql);

// HTML starts here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dataset Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: #fff;
            max-width: 700px;
            margin: 40px auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .field {
            margin-bottom: 15px;
        }
        .field label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .field p {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 6px;
            color: #333;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            text-decoration: none;
            color: #007BFF;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
<?php
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    ?>
    <h2>Dataset Details</h2>

    <div class="field">
        <label>Title</label>
        <p><?= htmlspecialchars($row['title']) ?></p>
    </div>

    <div class="field">
        <label>Description</label>
        <p><?= nl2br(htmlspecialchars($row['description'])) ?></p>
    </div>

    <div class="field">
        <label>Visibility</label>
        <p><?= htmlspecialchars($row['visibility']) ?></p>
    </div>

    <div class="field">
        <label>Category</label>
        <p><?= htmlspecialchars($row['category']) ?></p>
    </div>

    <div class="field">
        <label>Start Period</label>
        <p><?= htmlspecialchars($row['start_period']) ?></p>
    </div>

    <div class="field">
        <label>End Period</label>
        <p><?= htmlspecialchars($row['end_period']) ?></p>
    </div>

    <div class="field">
        <label>Source</label>
        <p><?= htmlspecialchars($row['source']) ?></p>
    </div>

    <div class="field">
        <label>Location</label>
        <p><?= htmlspecialchars($row['location']) ?></p>
    </div>

    <div class="field">
        <label>Link</label>
        <p><a href="<?= htmlspecialchars($row['link']) ?>" target="_blank"><?= htmlspecialchars($row['link']) ?></a></p>
    </div>

    <a class="back-link" href="javascript:history.back()">← Back</a>

<?php
} else {
    echo "<p>Dataset not found.</p>";
}
?>
</div>
</body>
</html>
