<?php
session_start();
include 'db_connection.php';
include 'update_session.php';
include 'includes/error_handler.php';
include 'includes/path_handler.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    handle_error("You must be logged in to view your organization requests.", ERROR_AUTH, "login.php");
}

$user_id = $_SESSION['user_id'];

// Cancel request if action is cancel and request_id is provided
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['request_id'])) {
    $request_id = (int)$_GET['request_id'];
    
    // Check if the request belongs to the user and is still pending
    $check_sql = "SELECT request_id FROM organization_creation_requests 
                  WHERE request_id = ? AND user_id = ? AND status = 'Pending'";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        handle_db_error("Database error occurred", $conn, "user_org_requests.php");
    }
    
    $check_stmt->bind_param("ii", $request_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Get documents for this request
            $docs_sql = "SELECT document_path FROM organization_request_documents WHERE request_id = ?";
            $docs_stmt = $conn->prepare($docs_sql);
            
            if (!$docs_stmt) {
                throw new Exception("Failed to prepare document query: " . $conn->error);
            }
            
            $docs_stmt->bind_param("i", $request_id);
            $docs_stmt->execute();
            $docs_result = $docs_stmt->get_result();
            
            // Delete documents from filesystem
            while ($doc = $docs_result->fetch_assoc()) {
                $file_path = $doc['document_path'];
                
                // Convert to absolute path if necessary
                if (strpos($file_path, '/') !== 0 && strpos($file_path, ':\\') !== 1) {
                    $file_path = get_absolute_path($file_path);
                }
                
                if (file_exists($file_path)) {
                    if (unlink($file_path)) {
                        log_error("Document deleted", "file", [
                            'path' => $file_path,
                            'request_id' => $request_id
                        ]);
                    } else {
                        log_error("Failed to delete document", ERROR_FILE, [
                            'path' => $file_path,
                            'error' => error_get_last()
                        ]);
                    }
                }
            }
            
            // Delete request and documents records
            $delete_sql = "DELETE FROM organization_creation_requests WHERE request_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            
            if (!$delete_stmt) {
                throw new Exception("Failed to prepare delete query: " . $conn->error);
            }
            
            $delete_stmt->bind_param("i", $request_id);
            
            if ($delete_stmt->execute()) {
                // Remove folder if empty
                $folder_path = get_uploads_dir("org_requests/{$request_id}");
                
                if (is_dir($folder_path) && count(scandir($folder_path)) <= 2) { // Only . and .. entries
                    if (rmdir($folder_path)) {
                        log_error("Request folder removed", "file", ['path' => $folder_path]);
                    } else {
                        log_error("Failed to remove request folder", ERROR_FILE, [
                            'path' => $folder_path,
                            'error' => error_get_last()
                        ]);
                    }
                }
                
                $conn->commit();
                set_success_message("Your organization request has been cancelled.");
                
                log_error("Organization request cancelled", "org_request", [
                    'request_id' => $request_id,
                    'user_id' => $user_id
                ]);
            } else {
                throw new Exception("Failed to delete request: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            
            log_error("Error cancelling organization request", ERROR_GENERAL, [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $request_id
            ]);
            
            handle_error("Error cancelling request: " . $e->getMessage(), ERROR_GENERAL, "user_org_requests.php");
        }
        
        // Redirect to same page to prevent refresh issues
        header("Location: user_org_requests.php");
        exit();
    } else {
        handle_error("Request not found or cannot be cancelled.", ERROR_PERMISSION, "user_org_requests.php");
    }
}

// Get user's organization creation requests
$requests_sql = "
    SELECT 
        ocr.request_id,
        ocr.name,
        ocr.type,
        ocr.status,
        ocr.request_date,
        ocr.reviewed_date,
        ocr.admin_response,
        COUNT(ord.document_id) AS document_count
    FROM 
        organization_creation_requests ocr
    LEFT JOIN 
        organization_request_documents ord ON ocr.request_id = ord.request_id
    WHERE 
        ocr.user_id = ?
    GROUP BY 
        ocr.request_id
    ORDER BY 
        ocr.request_date DESC
";

$requests_stmt = $conn->prepare($requests_sql);

if (!$requests_stmt) {
    handle_db_error("Failed to prepare requests query", $conn, "HomeLogin.php");
}

$requests_stmt->bind_param("i", $user_id);
$requests_stmt->execute();
$requests_result = $requests_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Organization Requests</title>
    <link rel="stylesheet" href="assets/css/error_styles.css">
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
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
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
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .new-request-btn {
            display: block;
            width: fit-content;
            margin: 0 auto 20px;
            background-color: #0099ff;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: background-color 0.3s;
        }
        
        .new-request-btn:hover {
            background-color: #007acc;
        }
        
        .request-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .request-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
        }
        
        .request-title {
            font-size: 18px;
            font-weight: bold;
            color: #0099ff;
            margin: 0;
        }
        
        .request-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .status-approved {
            background-color: #28a745;
            color: white;
        }
        
        .status-rejected {
            background-color: #dc3545;
            color: white;
        }
        
        .request-info {
            margin-bottom: 15px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        
        .info-value {
            flex: 1;
        }
        
        .documents-section {
            margin-bottom: 15px;
        }
        
        .document-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .document-link {
            display: inline-block;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-decoration: none;
            color: #0099ff;
            transition: background-color 0.2s;
            font-size: 14px;
        }
        
        .document-link:hover {
            background-color: #e9ecef;
        }
        
        .admin-response {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
            white-space: pre-wrap;
        }
        
        .request-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .cancel-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .cancel-btn:hover {
            background-color: #c82333;
        }
        
        .view-btn {
            background-color: #0099ff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .view-btn:hover {
            background-color: #007bff;
        }
        
        .no-requests {
            text-align: center;
            padding: 30px 0;
            color: #6c757d;
        }
        
        .no-requests p {
            margin-bottom: 20px;
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
    </style>
</head>
<body>
    <video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>
    
    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mindanao Data Exchange Logo">
            <h2>My Organization Requests</h2>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="user_settings.php">SETTINGS</a>
        </nav>
    </header>
    
    <div class="container">
        <h1>My Organization Requests</h1>
        
        <?php echo display_error_message(); ?>
        <?php echo display_success_message(); ?>
        
        <?php
        // Check if user can make a new request
        $can_make_request = true;
        
        // Check if user already has a pending request
        $check_pending_sql = "SELECT request_id FROM organization_creation_requests WHERE user_id = ? AND status = 'Pending'";
        $check_pending_stmt = $conn->prepare($check_pending_sql);
        $check_pending_stmt->bind_param("i", $user_id);
        $check_pending_stmt->execute();
        $check_pending_result = $check_pending_stmt->get_result();
        
        if ($check_pending_result->num_rows > 0) {
            $can_make_request = false;
        }
        
        // Check if user is already in an organization
        $check_org_sql = "SELECT organization_id FROM users WHERE user_id = ? AND organization_id IS NOT NULL";
        $check_org_stmt = $conn->prepare($check_org_sql);
        $check_org_stmt->bind_param("i", $user_id);
        $check_org_stmt->execute();
        $check_org_result = $check_org_stmt->get_result();
        
        if ($check_org_result->num_rows > 0) {
            $can_make_request = false;
            
            if ($check_pending_result->num_rows === 0) {
                echo '<div class="alert alert-danger">You are already a member of an organization. You must leave your current organization before creating a new one.</div>';
            }
        }
        
        if ($can_make_request):
        ?>
            <a href="create_organization_request.php" class="new-request-btn">Create New Organization Request</a>
        <?php endif; ?>
        
        <?php if ($requests_result->num_rows > 0): ?>
            <div class="request-list">
                <?php while ($request = $requests_result->fetch_assoc()): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <h3 class="request-title"><?php echo htmlspecialchars($request['name']); ?></h3>
                            <span class="request-status status-<?php echo strtolower($request['status']); ?>">
                                <?php echo $request['status']; ?>
                            </span>
                        </div>
                        
                        <div class="request-info">
                            <div class="info-row">
                                <span class="info-label">Organization Type:</span>
                                <span class="info-value"><?php echo htmlspecialchars($request['type']); ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">Request Date:</span>
                                <span class="info-value"><?php echo date('F j, Y, g:i a', strtotime($request['request_date'])); ?></span>
                            </div>
                            
                            <?php if ($request['status'] !== 'Pending' && !empty($request['reviewed_date'])): ?>
                                <div class="info-row">
                                    <span class="info-label">Reviewed Date:</span>
                                    <span class="info-value"><?php echo date('F j, Y, g:i a', strtotime($request['reviewed_date'])); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($request['admin_response'])): ?>
                                <div class="info-row">
                                    <span class="info-label">Admin Response:</span>
                                    <div class="admin-response"><?php echo nl2br(htmlspecialchars($request['admin_response'])); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="documents-section">
                            <span class="info-label">Supporting Documents:</span>
                            <span class="info-value"><?php echo $request['document_count']; ?> document(s) submitted</span>
                            
                            <?php if ($request['document_count'] > 0): ?>
                                <div class="document-links">
                                    <?php
                                        // Get documents for this request
                                        $documents_sql = "SELECT document_id, document_path, document_name FROM organization_request_documents WHERE request_id = ?";
                                        $documents_stmt = $conn->prepare($documents_sql);
                                        $documents_stmt->bind_param("i", $request['request_id']);
                                        $documents_stmt->execute();
                                        $documents_result = $documents_stmt->get_result();
                                        
                                        while ($document = $documents_result->fetch_assoc()):
                                            // Process document path to handle possible localhost URLs
                                            $doc_path = $document['document_path'];
                                            
                                            // Remove http://localhost or https://localhost if present
                                            if (strpos($doc_path, 'http://localhost') === 0) {
                                                // Extract the path part after the domain
                                                $parsed_url = parse_url($doc_path);
                                                $doc_path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
                                                
                                                // Remove any double slashes
                                                $doc_path = ltrim($doc_path, '/');
                                            }
                                    ?>
                                        <a href="<?php echo htmlspecialchars($doc_path); ?>" target="_blank" class="document-link">
                                            <?php echo htmlspecialchars($document['document_name']); ?>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="request-actions">
                            <?php if ($request['status'] === 'Pending'): ?>
                                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?action=cancel&request_id=<?php echo $request['request_id']; ?>" 
                                   class="cancel-btn"
                                   onclick="return confirmCancel(event)">
                                    Cancel Request
                                </a>
                            <?php elseif ($request['status'] === 'Approved'): ?>
                                <a href="HomeLogin.php" class="view-btn">Go to Dashboard</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-requests">
                <p>You haven't made any organization creation requests yet.</p>
                <?php if ($can_make_request): ?>
                    <p>Use the button at the top of this page to create a new request.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Custom Confirmation Modal -->
    <div id="customConfirmModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
        <div style="background: white; width: 400px; padding: 20px; border-radius: 10px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); box-shadow: 0 0 15px rgba(0,0,0,0.3);">
            <h3 style="color: #dc3545; margin-top: 0;">Cancel Organization Request</h3>
            <p>Are you sure you want to cancel this request? This action cannot be undone.</p>
            <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button id="cancelModalBtn" style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 5px; margin-right: 10px; cursor: pointer;">No, Keep Request</button>
                <button id="confirmModalBtn" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">Yes, Cancel Request</button>
            </div>
        </div>
    </div>
    
    <script>
        let pendingRequestUrl = '';
        
        function confirmCancel(event) {
            event.preventDefault();
            pendingRequestUrl = event.target.href;
            
            const modal = document.getElementById('customConfirmModal');
            modal.style.display = 'block';
            
            return false;
        }
        
        document.getElementById('cancelModalBtn').addEventListener('click', function() {
            document.getElementById('customConfirmModal').style.display = 'none';
        });
        
        document.getElementById('confirmModalBtn').addEventListener('click', function() {
            window.location.href = pendingRequestUrl;
        });
    </script>
</body>
</html> 