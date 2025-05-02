<?php
session_start();
include 'db_connection.php';  // Include your database connection

// Function to fetch and display datasets uploaded by the logged-in user
function displayUserDatasets($user_id, $conn) {
    $sql = "SELECT title, file_path FROM datasets WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);

    // Check if any datasets are found
    if (mysqli_num_rows($result) > 0) {
        // Loop through each dataset and display it
        while ($row = mysqli_fetch_assoc($result)) {
            $title = $row['title'];
            $filePath = $row['file_path'];
            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

            // Display dataset info with a nice design
            echo "<div class='dataset-item'>
                <div class='dataset-info'>
                    <p><a href='my_uploaded_dataset.php?title=" . urlencode($title) . "'>
                        <strong>" . htmlspecialchars($title) . "</strong>
                    </a></p>
                    <p><em>File: $fileExtension</em></p>
                </div>
                <div class='download-btn'>
                    <a href='$filePath' download>Download</a>
                </div>
            </div>";

        }
    } else {
        echo "<p>No datasets found for this user.</p>";
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to view your datasets.";
    exit;
}

$user_id = $_SESSION['user_id'];  // Get logged-in user's ID
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Datasets</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fb;
            margin: 0;
            padding: 0;
        }
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5%;
            padding-left: 30px;
            background-color: rgba(0, 153, 255, 0.5);
            color:rgb(0, 0, 0);
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            margin: 10px 0;
            backdrop-filter: blur(10px);
            max-width: 1200px;
            width: 100%;
            margin-top:30px;
            margin-left: auto;
            margin-right: auto;
        }
        .navbar h1 {
            margin: 0;
            font-size: 28px;
        }
        .container {
            width: 60%;
            margin: 40px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .dataset-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .dataset-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #ffffff;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .dataset-info {
            font-size: 18px;
        }
        .dataset-info p {
            margin: 0;
        }
        .download-btn a {
            text-decoration: none;
            background-color: #007bff;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .download-btn a:hover {
            background-color: #0056b3;
        }
        .no-datasets {
            font-size: 18px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <h1>My Datasets</h1>
    </div>

    <div class="container">
        <div class="dataset-list">
            <?php
                // Call function to display datasets for the logged-in user
                displayUserDatasets($user_id, $conn);
            ?>
        </div>
    </div>

</body>
</html>
