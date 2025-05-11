<?php
session_start();

// Check if the user is logged in (ensure 'user_id' is set in the session)
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not authenticated
    header("Location: login.php");
    exit();
} elseif (!isset($_SESSION['has_organization']) || $_SESSION['has_organization'] === false) {
    // Redirect to an error page if no organization_id is found
    header("Location: unauthorized.php"); // Or login page if preferred
    exit();
} elseif (!isset($_SESSION['uploaded_file']) || empty($_SESSION['uploaded_file'])) {
    // Handle the case where no file has been uploaded
    $_SESSION['upload_error'] = "No file has been uploaded yet.";
    header("Location: uploadselection.php"); // Redirect to a custom error page or show message on the current page
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Upload Successful</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f0f8ff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      background-color: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
      text-align: center;
      width: 80%;
      max-width: 500px;
    }

    h1 {
      color: #0099ff;
      font-size: 36px;
      margin-bottom: 20px;
    }

    p {
      color: #333;
      font-size: 18px;
      margin-bottom: 20px;
    }

    a {
      background-color: #0099ff;
      color: white;
      padding: 12px 24px;
      text-decoration: none;
      font-size: 16px;
      border-radius: 5px;
      transition: background-color 0.3s ease;
    }

    a:hover {
      background-color: #007acc;
    }
  </style>
</head>
<body>

  <!-- Main content -->
  <div class="container" id="mainContent">
    <h1>Upload Successful!</h1>
    <p>Your dataset has been successfully uploaded.</p>
    <p>Thank you for contributing to the community!</p>
    <a href="datasets.php">Go to Datasets</a>
  </div>

</body>
</html>
