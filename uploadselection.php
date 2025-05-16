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
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
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

        h1 {
            color: #0099ff;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .error-message {
            color: red;
            margin-bottom: 20px;
            font-size: 16px;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 450px;
            margin-top: 40px;
        }

        label {
            display: block;
            background-color: #0099ff;
            color: white;
            padding: 0px 16px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            width: auto;
            max-width: 100%;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
            font-size: 16px;
        }

        label:hover {
            background-color: #007acc;
        }

        input[type="file"] {
            display: none;
        }
        button {
            background-color: #0099ff;
            color: white;
            border: none;
            padding: 10px 16px; /* Reduced vertical and horizontal padding */
            border-radius: 6px; /* Slightly smaller radius */
            font-size: 16px;     /* Slightly smaller font */
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: auto;         /* Allow the button to size to its content */
            min-width: 150px;    /* Optional: gives a reasonable button width */
        }

        button:hover {
            background-color: #007acc;
        }

        .file-list {
            width: 100%;
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #0099ff;
            border-radius: 10px;
            background-color: #f9f9f9;
            max-height: 200px;
            overflow-y: auto;
        }

        .file-item {
            margin: 8px 0;
            padding: 5px;
            background-color: #e6f3ff;
            border-radius: 5px;
            font-size: 14px;
            color: #333;
        }

        .file-item:hover {
            background-color: #d4eaff;
        }

        .file-item a {
            color: #0099ff;
            text-decoration: none;
        }

        .file-item a:hover {
            text-decoration: underline;
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
        </nav>
    </header>



<?php if (isset($_SESSION['error_message'])): ?>
    <div class="error-message"><?php echo $_SESSION['error_message']; ?></div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data">
<h1>Upload Your Datasets</h1>
    <!-- Area to show selected file names -->
    <div class="file-list" id="fileList"></div>
    <label for="fileToUpload">
        <p>Click to Browse</p>
        <input type="file" name="fileToUpload[]" id="fileToUpload" multiple required onchange="displayFiles()">
    </label>

    <button type="submit">Upload Files</button>
</form>

<script>
    // Function to display the selected files
    function displayFiles() {
        const fileList = document.getElementById('fileList');
        const files = document.getElementById('fileToUpload').files;
        fileList.innerHTML = ''; // Clear previous file list

        // Loop through the selected files and display them
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileItem = document.createElement('div');
            fileItem.classList.add('file-item');
            fileItem.textContent = file.name;
            fileList.appendChild(fileItem);
        }
    }
</script>

</body>
</html>
