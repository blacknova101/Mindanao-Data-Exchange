<?php
session_start();
include 'db_connection.php';
include 'update_session.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user already has a pending organization request
$check_pending_sql = "SELECT * FROM organization_creation_requests WHERE user_id = ? AND status = 'Pending'";
$check_pending_stmt = $conn->prepare($check_pending_sql);
$check_pending_stmt->bind_param("i", $user_id);
$check_pending_stmt->execute();
$pending_result = $check_pending_stmt->get_result();

if ($pending_result->num_rows > 0) {
    $_SESSION['error_message'] = "You already have a pending organization creation request. Please wait for administrator approval.";
    header("Location: user_settings.php");
    exit();
}

// Check if user is already part of an organization
$check_org_sql = "SELECT organization_id FROM users WHERE user_id = ? AND organization_id IS NOT NULL";
$check_org_stmt = $conn->prepare($check_org_sql);
$check_org_stmt->bind_param("i", $user_id);
$check_org_stmt->execute();
$org_result = $check_org_stmt->get_result();

if ($org_result->num_rows > 0) {
    $_SESSION['error_message'] = "You are already a member of an organization. You must leave your current organization before creating a new one.";
    header("Location: user_settings.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $type = trim($_POST['type']);
    $contact_email = trim($_POST['contact_email']);
    $website_url = trim($_POST['website_url']);
    $description = trim($_POST['description']);
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Organization name is required";
    }
    
    if (empty($type)) {
        $errors[] = "Organization type is required";
    }
    
    if (empty($contact_email)) {
        $errors[] = "Contact email is required";
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (!empty($website_url) && !filter_var($website_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Invalid website URL format";
    }
    
    // Check if no documents were uploaded
    if (empty($_FILES['documents']['name'][0])) {
        $errors[] = "At least one document proof is required";
    }
    
    // If no errors, process the request
    if (empty($errors)) {
        // Create organization creation request
        $insert_request_sql = "INSERT INTO organization_creation_requests 
                              (user_id, name, type, contact_email, website_url, description, status, request_date) 
                              VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())";
        
        $insert_stmt = $conn->prepare($insert_request_sql);
        $insert_stmt->bind_param("isssss", $user_id, $name, $type, $contact_email, $website_url, $description);
        
        if ($insert_stmt->execute()) {
            $request_id = $conn->insert_id;
            
            // Create directory to store documents
            $upload_dir = "uploads/org_requests/" . $request_id . "/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $document_paths = [];
            $valid_uploads = true;
            
            // Process each uploaded document
            foreach ($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['documents']['name'][$key];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Check file extension
                    $allowed_exts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                    if (!in_array($file_ext, $allowed_exts)) {
                        $errors[] = "File {$file_name} has an invalid extension. Allowed: " . implode(', ', $allowed_exts);
                        $valid_uploads = false;
                        continue;
                    }
                    
                    // Generate unique filename
                    $new_filename = uniqid('doc_') . '.' . $file_ext;
                    $destination = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $document_paths[] = $destination;
                        
                        // Make sure we store only the relative path, not full URL
                        $relative_path = $destination;
                        
                        // Insert document record
                        $insert_doc_sql = "INSERT INTO organization_request_documents 
                                          (request_id, document_path, document_name) 
                                          VALUES (?, ?, ?)";
                        $insert_doc_stmt = $conn->prepare($insert_doc_sql);
                        $insert_doc_stmt->bind_param("iss", $request_id, $relative_path, $file_name);
                        
                        if (!$insert_doc_stmt->execute()) {
                            $errors[] = "Error saving document record: " . $insert_doc_stmt->error;
                            $valid_uploads = false;
                        }
                    } else {
                        $errors[] = "Error uploading file {$file_name}";
                        $valid_uploads = false;
                    }
                } else {
                    $errors[] = "Error with file upload: " . $_FILES['documents']['error'][$key];
                    $valid_uploads = false;
                }
            }
            
            // If everything is successful
            if ($valid_uploads) {
                // Add notification for admins
                $admin_sql = "SELECT user_id FROM users WHERE user_type = 'admin'";
                $admin_result = $conn->query($admin_sql);
                
                if ($admin_result->num_rows > 0) {
                    while ($admin = $admin_result->fetch_assoc()) {
                        $notification_message = "New organization creation request: '{$name}' by user #{$user_id}";
                        $notification_sql = "INSERT INTO user_notifications 
                                           (user_id, message, related_id, notification_type, is_read) 
                                           VALUES (?, ?, ?, 'org_creation_request', 0)";
                        $notification_stmt = $conn->prepare($notification_sql);
                        $notification_stmt->bind_param("isi", $admin['user_id'], $notification_message, $request_id);
                        $notification_stmt->execute();
                    }
                }
                
                $_SESSION['success_message'] = "Your organization creation request has been submitted successfully. An administrator will review your request.";
                header("Location: user_settings.php");
                exit();
            } else {
                // If document uploads failed, delete the request
                $delete_sql = "DELETE FROM organization_creation_requests WHERE request_id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("i", $request_id);
                $delete_stmt->execute();
                
                // Clean up any uploaded files
                foreach ($document_paths as $path) {
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
                
                // Delete the folder
                if (is_dir($upload_dir)) {
                    rmdir($upload_dir);
                }
            }
        } else {
            $errors[] = "Error creating organization request: " . $insert_stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Organization Request</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fc;
        }
        
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 5%;
            padding-left: 30px;
            background-color: #0099ff;
            color: #ffffff;
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
            z-index: 1000;
            min-height: 60px;
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
        
        .logo h2 {
            color: white;
            margin: 0;
            font-size: 22px;
            white-space: nowrap;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-size: 18px;
            transition: transform 0.3s ease;
            font-weight: bold;
        }
        
        .nav-links a:hover {
            transform: scale(1.2);
        }
        
        /* Mobile menu toggle button */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
        }
        
        .mobile-menu-toggle i {
            display: block;
        }

        /* Responsive styles for the navbar */
        @media screen and (max-width: 768px) {
            .navbar {
                padding: 10px;
                border-radius: 15px;
                width: 90%;
                max-width: 90%;
                position: relative;
                z-index: 2;
            }
            
            .mobile-menu-toggle {
                display: block;
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .logo {
                flex-direction: row;
                align-items: center;
                max-width: 80%;
            }
            
            .logo img {
                width: 50px;
                margin-right: 12px;
            }
            
            .logo h2 {
                margin: 0;
                font-size: 24px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .nav-links {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                flex-direction: column;
                background-color: #0099ff;
                padding: 10px 0;
                border-radius: 0 0 15px 15px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                display: none;
                z-index: 9999;
            }
            
            .nav-links.active {
                display: flex;
            }
            
            .nav-links a {
                width: 100%;
                text-align: center;
                padding: 10px 0;
                margin: 0;
            }
        }

        @media screen and (max-width: 480px) {
            .navbar {
                padding: 8px 10px;
            }
            
            .logo img {
                width: 50px;
            }
            
            .logo h2 {
                font-size: 16px;
            }
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }
        
        h1 {
            color: #0099ff;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        form {
            display: flex;
            flex-direction: column;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="url"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .documents-section {
            margin-bottom: 20px;
        }
        
        .document-inputs {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .document-inputs input[type="file"] {
            flex: 1;
            padding: 5px;
        }
        
        .document-inputs button {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            margin-left: 10px;
            cursor: pointer;
        }
        
        .add-document-btn {
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            margin-top: 10px;
            cursor: pointer;
            width: fit-content;
        }
        
        button[type="submit"] {
            background-color: #0099ff;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        
        button[type="submit"]:hover {
            background-color: #007acc;
        }
        
        .requirements {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .requirements h3 {
            margin-top: 0;
            color: #0099ff;
        }
        
        .requirements ul {
            margin-bottom: 0;
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
        
        /* Responsive adjustments for form elements */
        @media screen and (max-width: 600px) {
            .document-inputs {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .document-inputs input[type="file"] {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .document-inputs button {
                margin-left: 0;
                width: 100%;
            }
            
            .add-document-btn {
                width: 100%;
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
            <img src="images/mdx_logo.png" alt="Mindanao Data Exchange Logo">
            <h2>Create Organization</h2>
        </div>
        <button class="mobile-menu-toggle" id="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <nav class="nav-links" id="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="datasets.php">ALL DATASETS</a>
            <a href="mydatasets.php">MY DATASETS</a>
            <a href="user_settings.php">SETTINGS</a>
        </nav>
    </header>
    
    <div class="container">
        <h1>Request to Create an Organization</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="requirements">
            <h3>Requirements:</h3>
            <ul>
                <li>Organization name, type, and contact email are required</li>
                <li>Website URL is optional but must be valid if provided</li>
                <li>At least one document proof of your organization is required (PDF, DOC, DOCX, JPG, JPEG, PNG)</li>
                <li>Your request will be reviewed by an administrator</li>
                <li>You will be notified when your request is approved or rejected</li>
            </ul>
        </div>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Organization Name*</label>
                <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="type">Organization Type*</label>
                <select id="type" name="type" required>
                    <option value="" disabled <?php echo !isset($type) ? 'selected' : ''; ?>>Select Organization Type</option>
                    <option value="Government Agency" <?php echo isset($type) && $type === 'Government Agency' ? 'selected' : ''; ?>>Government Agency</option>
                    <option value="Educational Institution" <?php echo isset($type) && $type === 'Educational Institution' ? 'selected' : ''; ?>>Educational Institution</option>
                    <option value="Non-profit Organization" <?php echo isset($type) && $type === 'Non-profit Organization' ? 'selected' : ''; ?>>Non-profit Organization</option>
                    <option value="Private Company" <?php echo isset($type) && $type === 'Private Company' ? 'selected' : ''; ?>>Private Company</option>
                    <option value="Research Institute" <?php echo isset($type) && $type === 'Research Institute' ? 'selected' : ''; ?>>Research Institute</option>
                    <option value="Other" <?php echo isset($type) && $type === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="contact_email">Contact Email*</label>
                <input type="email" id="contact_email" name="contact_email" value="<?php echo isset($contact_email) ? htmlspecialchars($contact_email) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="website_url">Website URL (Optional)</label>
                <input type="url" id="website_url" name="website_url" value="<?php echo isset($website_url) ? htmlspecialchars($website_url) : ''; ?>" placeholder="https://example.com">
            </div>
            
            <div class="form-group">
                <label for="description">Organization Description*</label>
                <textarea id="description" name="description" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
            </div>
            
            <div class="documents-section">
                <label>Document Proof(s)*</label>
                <p>Upload documents that prove your organization's authenticity (registration certificates, official letters, etc.)</p>
                
                <div id="document-container">
                    <div class="document-inputs">
                        <input type="file" name="documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                    </div>
                </div>
                
                <button type="button" class="add-document-btn" onclick="addDocumentInput()">+ Add Another Document</button>
            </div>
            
            <button type="submit">Submit Request</button>
        </form>
    </div>
    
    <script>
        // Document input functions
        function addDocumentInput() {
            const container = document.getElementById('document-container');
            const newInput = document.createElement('div');
            newInput.className = 'document-inputs';
            
            newInput.innerHTML = `
                <input type="file" name="documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                <button type="button" onclick="removeDocumentInput(this)">Remove</button>
            `;
            
            container.appendChild(newInput);
        }
        
        function removeDocumentInput(button) {
            const inputDiv = button.parentElement;
            inputDiv.remove();
        }
        
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const navLinks = document.getElementById('nav-links');
            
            mobileMenuToggle.addEventListener('click', function() {
                navLinks.classList.toggle('active');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInsideNavbar = event.target.closest('.navbar');
                if (!isClickInsideNavbar && navLinks.classList.contains('active')) {
                    navLinks.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html> 