<?php
session_start();
include 'db_connection.php';
include 'update_session.php';

// Check if the user is logged in and is an admin
if ((!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') 
    && !isset($_SESSION['admin_id'])) {
    header("Location: unauthorized.php");
    exit();
}

// Set user_id based on which login system was used
$user_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : $_SESSION['user_id'];

// Process approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $admin_response = isset($_POST['admin_response']) ? trim($_POST['admin_response']) : '';
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'Approved' : 'Rejected';
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update request status with admin's ID directly
            $update_request_sql = "UPDATE organization_creation_requests 
                                  SET status = ?, admin_response = ?, reviewed_date = NOW(), reviewed_by = ? 
                                  WHERE request_id = ?";
            $update_request_stmt = $conn->prepare($update_request_sql);
            $update_request_stmt->bind_param("ssis", $status, $admin_response, $user_id, $request_id);
            $update_request_stmt->execute();
            
            // If approved, create the organization
            if ($action === 'approve') {
                // Get request data
                $request_data_sql = "SELECT user_id, name, type, contact_email, website_url, description FROM organization_creation_requests WHERE request_id = ?";
                $request_data_stmt = $conn->prepare($request_data_sql);
                $request_data_stmt->bind_param("i", $request_id);
                $request_data_stmt->execute();
                $request_data_result = $request_data_stmt->get_result();
                $request_data = $request_data_result->fetch_assoc();
                
                $requester_id = $request_data['user_id'];
                $org_name = $request_data['name'];
                $org_type = $request_data['type'];
                $org_email = $request_data['contact_email'];
                $org_website = $request_data['website_url'];
                $org_description = $request_data['description'];
                
                // Check if organization table has the needed columns
                $table_modified = false;
                
                // Check and add contact_email column if needed
                $check_email_column_sql = "SHOW COLUMNS FROM organizations LIKE 'contact_email'";
                $check_email_result = $conn->query($check_email_column_sql);
                if ($check_email_result->num_rows == 0) {
                    $alter_email_sql = "ALTER TABLE organizations ADD COLUMN contact_email VARCHAR(255) NULL";
                    $conn->query($alter_email_sql);
                    $table_modified = true;
                }
                
                // Check and add website_url column if needed
                $check_website_column_sql = "SHOW COLUMNS FROM organizations LIKE 'website_url'";
                $check_website_result = $conn->query($check_website_column_sql);
                if ($check_website_result->num_rows == 0) {
                    $alter_website_sql = "ALTER TABLE organizations ADD COLUMN website_url VARCHAR(255) NULL";
                    $conn->query($alter_website_sql);
                    $table_modified = true;
                }
                
                // Check and add type column if needed
                $check_type_column_sql = "SHOW COLUMNS FROM organizations LIKE 'type'";
                $check_type_result = $conn->query($check_type_column_sql);
                if ($check_type_result->num_rows == 0) {
                    $alter_type_sql = "ALTER TABLE organizations ADD COLUMN type VARCHAR(100) NULL";
                    $conn->query($alter_type_sql);
                    $table_modified = true;
                }
                
                // Check and add description column if needed
                $check_desc_column_sql = "SHOW COLUMNS FROM organizations LIKE 'description'";
                $check_desc_result = $conn->query($check_desc_column_sql);
                if ($check_desc_result->num_rows == 0) {
                    $alter_desc_sql = "ALTER TABLE organizations ADD COLUMN description TEXT NULL";
                    $conn->query($alter_desc_sql);
                    $table_modified = true;
                }
                
                // Create the SQL statement dynamically based on which columns exist
                $sql_fields = array("name", "created_by", "auto_accept");
                $sql_values = array("?", "?", "0");
                $sql_types = "si"; // string, integer
                $sql_params = array($org_name, $requester_id);
                
                // Add contact_email if column exists
                if ($check_email_result->num_rows > 0 || $table_modified) {
                    $sql_fields[] = "contact_email";
                    $sql_values[] = "?";
                    $sql_types .= "s";
                    $sql_params[] = $org_email;
                }
                
                // Add website_url if column exists
                if ($check_website_result->num_rows > 0 || $table_modified) {
                    $sql_fields[] = "website_url";
                    $sql_values[] = "?";
                    $sql_types .= "s";
                    $sql_params[] = $org_website;
                }
                
                // Add type if column exists
                if ($check_type_result->num_rows > 0 || $table_modified) {
                    $sql_fields[] = "type";
                    $sql_values[] = "?";
                    $sql_types .= "s";
                    $sql_params[] = $org_type;
                }
                
                // Add description if column exists
                if ($check_desc_result->num_rows > 0 || $table_modified) {
                    $sql_fields[] = "description";
                    $sql_values[] = "?";
                    $sql_types .= "s";
                    $sql_params[] = $org_description;
                }
                
                // Build the final SQL statement
                $fields_str = implode(", ", $sql_fields);
                $values_str = implode(", ", $sql_values);
                $create_org_sql = "INSERT INTO organizations ($fields_str) VALUES ($values_str)";
                
                // Prepare and execute the statement
                $create_org_stmt = $conn->prepare($create_org_sql);
                
                // Create the parameter references array for bind_param
                $params_refs = array();
                $params_refs[] = &$sql_types;
                foreach ($sql_params as $key => $value) {
                    $params_refs[] = &$sql_params[$key];
                }
                
                // Call bind_param with dynamic parameters
                call_user_func_array(array($create_org_stmt, 'bind_param'), $params_refs);
                $create_org_stmt->execute();
                
                $organization_id = $conn->insert_id;
                
                // Update user's organization
                $update_user_sql = "UPDATE users 
                                   SET organization_id = ?, user_type = 'with_organization' 
                                   WHERE user_id = ?";
                $update_user_stmt = $conn->prepare($update_user_sql);
                $update_user_stmt->bind_param("ii", $organization_id, $requester_id);
                $update_user_stmt->execute();
                
                // Notification message for approval
                $notification_message = "Your organization creation request for '{$org_name}' has been approved! You are now the owner of this organization.";
                if (!empty($admin_response)) {
                    $notification_message .= " Admin note: " . $admin_response;
                }
            } else {
                // Get request data for rejection notification
                $request_data_sql = "SELECT user_id, name FROM organization_creation_requests WHERE request_id = ?";
                $request_data_stmt = $conn->prepare($request_data_sql);
                $request_data_stmt->bind_param("i", $request_id);
                $request_data_stmt->execute();
                $request_data_result = $request_data_stmt->get_result();
                $request_data = $request_data_result->fetch_assoc();
                
                $requester_id = $request_data['user_id'];
                $org_name = $request_data['name'];
                
                // Notification message for rejection
                $notification_message = "Your organization creation request for '{$org_name}' has been rejected.";
                if (!empty($admin_response)) {
                    $notification_message .= " Reason: " . $admin_response;
                }
            }
            
            // Add notification for the requester
            $notification_type = ($action === 'approve') ? 'org_request_approved' : 'org_request_rejected';
            $notification_sql = "INSERT INTO user_notifications 
                               (user_id, message, related_id, notification_type, is_read) 
                               VALUES (?, ?, ?, ?, 0)";
            $notification_stmt = $conn->prepare($notification_sql);
            $notification_stmt->bind_param("isis", $requester_id, $notification_message, $request_id, $notification_type);
            $notification_stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            $_SESSION['success_message'] = "Organization request has been " . strtolower($status) . " successfully.";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $_SESSION['error_message'] = "Error processing request: " . $e->getMessage();
        }
        
        // Redirect to same page to prevent form resubmission
        header("Location: admin_org_requests.php");
        exit();
    }
}

// Get all organization creation requests
$requests_sql = "
    SELECT 
        ocr.request_id,
        ocr.user_id,
        ocr.name,
        ocr.type,
        ocr.contact_email,
        ocr.website_url,
        ocr.description,
        ocr.status,
        ocr.admin_response,
        ocr.request_date,
        ocr.reviewed_date,
        u.first_name,
        u.last_name,
        u.email,
        COUNT(ord.document_id) AS document_count
    FROM 
        organization_creation_requests ocr
    JOIN 
        users u ON ocr.user_id = u.user_id
    LEFT JOIN 
        organization_request_documents ord ON ocr.request_id = ord.request_id
    GROUP BY 
        ocr.request_id
    ORDER BY 
        CASE 
            WHEN ocr.status = 'Pending' THEN 0
            WHEN ocr.status = 'Approved' THEN 1
            ELSE 2
        END,
        ocr.request_date DESC
";

$requests_result = $conn->query($requests_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Requests - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: #0c1a36;
            color: white;
            height: 100vh;
            position: fixed;
            padding: 20px;
        }
        .sidebar .nav-link {
            color: white;
            padding: 10px 20px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
        }
        .sidebar .nav-link.active {
            background: #0099ff;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .request-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
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
        .badge-pending {
            background-color: #ffc107;
        }
        .badge-approved {
            background-color: #28a745;
        }
        .badge-rejected {
            background-color: #dc3545;
        }
        .request-description {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
            margin-bottom: 20px;
            white-space: pre-wrap;
        }
        .document-link {
            display: inline-block;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-decoration: none;
            color: #0099ff;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .document-link:hover {
            background-color: #e9ecef;
            text-decoration: none;
        }
        .btn-respond {
            min-width: 120px;
        }
        .modal-content {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 class="mb-4">Admin Panel</h3>
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a class="nav-link" href="admin_users.php"><i class="fas fa-users"></i> Users</a>
            <a class="nav-link" href="admin_datasets.php"><i class="fas fa-database"></i> Datasets</a>
            <a class="nav-link" href="admin_organizations.php"><i class="fas fa-building"></i> Organizations</a>
            <a class="nav-link active" href="#"><i class="fas fa-clipboard-list"></i> Org Requests</a>
            <a class="nav-link" href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a>
            <a class="nav-link" href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a class="nav-link" href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <h2 class="mb-4">Organization Creation Requests</h2>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']); 
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']); 
                ?>
            </div>
        <?php endif; ?>
        
        <?php if ($requests_result->num_rows > 0): ?>
            <?php while ($request = $requests_result->fetch_assoc()): ?>
                <div class="request-card">
                    <div class="request-header">
                        <h3 class="request-title"><?php echo htmlspecialchars($request['name']); ?></h3>
                        <span class="badge <?php echo 'badge-' . strtolower($request['status']); ?>">
                            <?php echo $request['status']; ?>
                        </span>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Requester:</strong> <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
                            <p><strong>Organization Type:</strong> <?php echo htmlspecialchars($request['type']); ?></p>
                            <p><strong>Contact Email:</strong> <?php echo htmlspecialchars($request['contact_email']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($request['website_url'])): ?>
                                <p><strong>Website:</strong> <a href="<?php echo htmlspecialchars($request['website_url']); ?>" target="_blank"><?php echo htmlspecialchars($request['website_url']); ?></a></p>
                            <?php endif; ?>
                            <p><strong>Request Date:</strong> <?php echo date('M j, Y, g:i a', strtotime($request['request_date'])); ?></p>
                            <?php if ($request['status'] !== 'Pending' && !empty($request['reviewed_date'])): ?>
                                <p><strong>Reviewed Date:</strong> <?php echo date('M j, Y, g:i a', strtotime($request['reviewed_date'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Description:</strong>
                        <div class="request-description"><?php echo nl2br(htmlspecialchars($request['description'])); ?></div>
                    </div>
                    
                    <?php if ($request['status'] !== 'Pending' && !empty($request['admin_response'])): ?>
                        <div class="mb-3">
                            <strong>Admin Response:</strong>
                            <div class="request-description"><?php echo nl2br(htmlspecialchars($request['admin_response'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <strong>Supporting Documents (<?php echo $request['document_count']; ?>):</strong>
                        <div class="mt-2">
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
                                    <i class="fas fa-file-alt me-2"></i><?php echo htmlspecialchars($document['document_name']); ?>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    
                    <?php if ($request['status'] === 'Pending'): ?>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-success me-2 btn-respond" onclick="showResponseForm(<?php echo $request['request_id']; ?>, 'approve')">
                                <i class="fas fa-check me-2"></i>Approve
                            </button>
                            <button type="button" class="btn btn-danger btn-respond" onclick="showResponseForm(<?php echo $request['request_id']; ?>, 'reject')">
                                <i class="fas fa-times me-2"></i>Reject
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No organization requests found.
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Response Form Modal -->
    <div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="responseModalTitle">Organization Request Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="responseForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="response_request_id">
                        <input type="hidden" name="action" id="response_action">
                        
                        <div class="mb-3">
                            <label for="admin_response" class="form-label">Response Message:</label>
                            <textarea name="admin_response" id="admin_response" class="form-control" rows="4" placeholder="Enter your response or reason..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn" id="confirmActionBtn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show response form modal
        function showResponseForm(requestId, action) {
            document.getElementById('response_request_id').value = requestId;
            document.getElementById('response_action').value = action;
            
            const modalTitle = document.getElementById('responseModalTitle');
            const confirmBtn = document.getElementById('confirmActionBtn');
            
            if (action === 'approve') {
                modalTitle.textContent = 'Approve Organization Request';
                confirmBtn.textContent = 'Approve';
                confirmBtn.className = 'btn btn-success';
                document.getElementById('admin_response').placeholder = 'Enter an optional approval message...';
            } else {
                modalTitle.textContent = 'Reject Organization Request';
                confirmBtn.textContent = 'Reject';
                confirmBtn.className = 'btn btn-danger';
                document.getElementById('admin_response').placeholder = 'Enter a reason for rejection...';
            }
            
            var responseModal = new bootstrap.Modal(document.getElementById('responseModal'));
            responseModal.show();
        }
    </script>
</body>
</html> 