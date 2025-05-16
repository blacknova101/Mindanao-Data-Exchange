<?php
session_start();
include('db_connection.php');

// Assuming user ID is stored in session
$user_id = $_SESSION['user_id'];  // Adjust according to your session structure

// Check if user already has an organization
$check_user_org_query = "SELECT organization_id FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $check_user_org_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $organization_id);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($organization_id) {
    // User already has an organization
    $error = "You already belong to an organization and cannot create a new one.";
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $contact_email = trim($_POST['contact_email']);
    $website_url = trim($_POST['website_url']);
    $org_type = isset($_POST['org_type']) ? trim($_POST['org_type']) : ''; // Fallback value
    $org_type_specify = trim($_POST['org_type_specify'] ?? '');

    // Determine final organization type
    if ($org_type === 'Other' && empty($org_type_specify)) {
        $error = "Please specify the organization type.";
    } else {
        $final_type = ($org_type === 'Other') ? $org_type_specify : $org_type;
    }

    // Basic validation
    if (empty($name) || empty($contact_email)) {
        $error = "Name and contact email are required.";
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if organization already exists
        $check_query = "SELECT COUNT(*) as count FROM organizations WHERE name = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $count);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($count > 0) {
            $error = "Organization already exists.";
        } else {
            // Insert the new organization
            $insert_query = "INSERT INTO organizations (name, contact_email, website_url, type, created_at, created_by) VALUES (?, ?, ?, ?, NOW(), ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "ssssi", $name, $contact_email, $website_url, $final_type, $user_id);
        
            if (mysqli_stmt_execute($stmt)) {
                // Update user's organization_id and user_type
                $organization_id = mysqli_insert_id($conn);
                $update_user_query = "UPDATE users SET organization_id = ?, user_type = 'with_organization' WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $update_user_query);
                mysqli_stmt_bind_param($stmt, "ii", $organization_id, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Update session variables so UI updates immediately
                    $_SESSION['organization_id'] = $organization_id;
                    $_SESSION['user_type'] = 'with_organization';
            
                    // Set success message in session and redirect
                    $_SESSION['success_message'] = "Organization created successfully.";
                    header("Location: user_settings.php");
                    exit();
                } else {
                    $error = "Error updating user record: " . mysqli_error($conn);
                    error_log("Error updating user record: " . mysqli_error($conn));
                }
            } else {
                $error = "Error creating organization: " . mysqli_error($conn);
                error_log("Error creating organization: " . mysqli_error($conn));
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Organization</title>
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
        .navbar h2 {
            color: white;
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
        form { 
            max-width: 800px;  /* Increased from 500px to 800px */
            width: 90%;  /* Added width percentage for responsiveness */
            margin: 40px auto; 
            background: white; 
            padding: 40px;  /* Increased padding from 30px to 40px */
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        input, select { 
            width: 100%; 
            padding: 12px; 
            margin: 8px 0 20px 0; 
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #0099ff;
            box-shadow: 0 0 5px rgba(0, 153, 255, 0.3);
        }
        button { 
            width: 100%; 
            padding: 12px; 
            margin: 20px 0 10px 0; 
            background-color: #0099ff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #007acc;
        }
        .message { 
            margin: 0 0 20px 0;
            text-align: center;
            font-size: 16px;
        }
        .message.error {
            color: #ff4d4d;
        }
        .message.success { 
            color: #00cc00;
        }
        label { 
            margin-top: 10px; 
            display: block; 
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }
        select {
            background-color: white;
            cursor: pointer;
        }
        #specifyContainer {
            margin-top: -10px;
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
            <h2>Create Organization</h2>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
        </nav>
    </header>

    <form method="POST" action="">
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>

        <label for="name">Organization Name</label>
        <input type="text" id="name" name="name" placeholder="Enter organization name" required>
        
        <label for="contact_email">Contact Email</label>
        <input type="email" id="contact_email" name="contact_email" placeholder="Enter contact email" required>
        
        <label for="website_url">Website URL (optional)</label>
        <input type="url" id="website_url" name="website_url" placeholder="Enter website URL">
        
        <label for="org_type">Organization Type</label>
        <select name="org_type" id="org_type" required onchange="toggleSpecifyField()">
            <option value="">-- Select Type --</option>
            <option value="Academic">Academic</option>
            <option value="Government">Government</option>
            <option value="Non-Profit">Non-Profit</option>
            <option value="Commercial">Commercial</option>
            <option value="Other">Other</option>
        </select>

        <div id="specifyContainer" style="display:none;">
            <label for="org_type_specify">Specify Organization Type</label>
            <input type="text" id="org_type_specify" name="org_type_specify" placeholder="Please specify organization type">
        </div>

        <button type="submit">Create Organization</button>
    </form>

    <script>
    function toggleSpecifyField() {
        const select = document.getElementById('org_type');
        const specify = document.getElementById('specifyContainer');
        specify.style.display = (select.value === 'Other') ? 'block' : 'none';
    }
    </script>
</body>
</html>
