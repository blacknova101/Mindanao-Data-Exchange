<?php
session_start();
include('db_connection.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'Approved' : 'Rejected';
        
        // Get request data before updating
        $requestDataSql = "
            SELECT 
                dar.dataset_id,
                dar.requester_id,
                d.title AS dataset_title
            FROM 
                dataset_access_requests dar
            JOIN 
                datasets d ON dar.dataset_id = d.dataset_id
            WHERE 
                dar.request_id = $request_id 
                AND dar.owner_id = $user_id";
        
        $requestDataResult = mysqli_query($conn, $requestDataSql);
        $requestData = mysqli_fetch_assoc($requestDataResult);
        
        if ($requestData) {
            // Update the request status
            $updateSql = "UPDATE dataset_access_requests 
                        SET status = '$status' 
                        WHERE request_id = $request_id 
                        AND owner_id = $user_id";
            
            mysqli_query($conn, $updateSql);
            
            // Add notification for the requester
            $requester_id = $requestData['requester_id'];
            $dataset_title = $requestData['dataset_title'];
            $dataset_id = $requestData['dataset_id'];
            
            if ($action === 'approve') {
                $message = "Your request for access to dataset '$dataset_title' has been approved. You can now download the dataset.";
                $notification_type = "access_approved";
            } else {
                $message = "Your request for access to dataset '$dataset_title' has been rejected. You may submit a new request with additional information if needed.";
                $notification_type = "access_rejected";
            }
            
            // Insert notification
            $insertNotificationSql = "
                INSERT INTO user_notifications (
                    user_id,
                    message,
                    related_id,
                    notification_type,
                    is_read
                ) VALUES (
                    $requester_id,
                    '" . mysqli_real_escape_string($conn, $message) . "',
                    $dataset_id,
                    '$notification_type',
                    FALSE
                )";
            
            mysqli_query($conn, $insertNotificationSql);
            
            // For approved requests, update permissions
            if ($action === 'approve') {
                // This is where you would add permissions logic
                // For example, adding to a dataset_permissions table
                // Or you can set this up directly in the download.php logic
            }
            
            $_SESSION['message'] = "Request has been " . strtolower($status) . " and the requester has been notified.";
        } else {
            $_SESSION['message'] = "Error: Request not found or you don't have permission to modify it.";
        }
    }
}

// Fetch all requests for datasets owned by the current user
$requestsSql = "
    SELECT 
        dar.request_id,
        dar.dataset_id,
        dar.requester_id,
        dar.request_date,
        dar.status,
        dar.reason,
        dar.verification_document,
        d.title AS dataset_title,
        u.first_name,
        u.last_name,
        u.email
    FROM 
        dataset_access_requests dar
    JOIN 
        datasets d ON dar.dataset_id = d.dataset_id
    JOIN 
        users u ON dar.requester_id = u.user_id
    WHERE 
        dar.owner_id = $user_id
    ORDER BY 
        dar.status = 'Pending' DESC,
        dar.request_date DESC
";

$requestsResult = mysqli_query($conn, $requestsSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Manage Dataset Access Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            color: #333;
            overflow-x: hidden;
            min-width: 320px;
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
            box-sizing: border-box;
            z-index: 1000;
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
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        h1, h2 {
            color: #007BFF;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .requests-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }
        .requests-table th, .requests-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        .requests-table th:nth-child(1) { width: 15%; }  /* Dataset */
        .requests-table th:nth-child(2) { width: 15%; }  /* Requester */
        .requests-table th:nth-child(3) { width: 15%; }  /* Date */
        .requests-table th:nth-child(4) { width: 25%; }  /* Reason - wider column */
        .requests-table th:nth-child(5) { width: 10%; }  /* Verification */
        .requests-table th:nth-child(6) { width: 10%; }  /* Status */
        .requests-table th:nth-child(7) { width: 10%; }  /* Actions */
        .requests-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #007BFF;
        }
        .requests-table tr:hover {
            background-color: #f1f1f1;
        }
        .btn {
            padding: 8px 12px;
            margin-right: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            color: white;
        }
        .btn-approve {
            background-color: #28a745;
        }
        .btn-approve:hover {
            background-color: #218838;
        }
        .btn-reject {
            background-color: #dc3545;
        }
        .btn-reject:hover {
            background-color: #c82333;
        }
        .btn-view {
            background-color: #0099ff;
            text-decoration: none;
            display: inline-block;
        }
        .btn-view:hover {
            background-color: #007bff;
        }
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        .status-approved {
            color: #28a745;
            font-weight: bold;
        }
        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }
        .no-requests {
            padding: 20px;
            text-align: center;
            color: #6c757d;
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
        .reason-text {
            font-size: 14px;
            background-color: #f9f9f9;
            border-radius: 4px;
            padding: 0;
            max-width: 250px;
        }
        
        .reason-preview {
            line-height: 1.4;
            max-height: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            padding: 8px;
        }
        
        .see-all-btn {
            background-color: transparent;
            color: #007BFF;
            border: none;
            padding: 2px 8px;
            font-size: 12px;
            cursor: pointer;
            display: block;
            text-align: right;
            width: 100%;
            transition: background-color 0.2s;
        }
        
        .see-all-btn:hover {
            text-decoration: underline;
        }
        
        .hidden-reason {
            display: none;
        }
        
        .document-link {
            color: #0099ff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .document-link:hover {
            text-decoration: underline;
        }
        
        /* Modal Styles */
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
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 8px;
            width: 50%;
            max-width: 600px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        #modal-reason-content {
            max-height: 300px;
            overflow-y: auto;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
            margin-top: 10px;
            line-height: 1.5;
            white-space: pre-line;
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
            color: #000;
            text-decoration: none;
        }
        
        /* Updated modal content styling */
        #reasonModal #modal-reason-content {
            max-height: 350px;
            overflow-y: auto;
            padding: 18px;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-top: 15px;
            line-height: 1.8;
            font-size: 15px;
            letter-spacing: 0.3px;
            white-space: normal;
            color: #333;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            white-space: pre-wrap !important;
            word-break: break-word !important;
            max-width: 100% !important;
        }
        
        #reasonModal #modal-reason-content * {
            white-space: pre-wrap !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            line-height: 1.8 !important;
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
        
        /* Responsive styles */
        @media screen and (max-width: 992px) {
            .container {
                width: 90%;
                padding: 15px;
            }
            
            .navbar {
                padding: 10px 20px;
                width: 90%;
            }
            
            .logo h2 {
                font-size: 18px;
            }
            
            .modal-content {
                width: 70%;
            }
            
            .reason-text {
                max-width: 200px;
            }
        }
        
        @media screen and (max-width: 768px) {
            .navbar {
                padding: 10px 15px;
                width: calc(100% - 20px);
                margin-left: 10px;
                margin-right: 10px;
            }
            
            .mobile-menu-toggle {
                display: block;
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .logo img {
                width: 50px;
            }
            
            .logo h2 {
                font-size: 16px;
                margin-left: 10px;
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
                z-index: 1000;
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
            
            .container {
                padding: 10px;
                margin: 20px auto;
            }
            
            /* Table responsive */
            .requests-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
            }
            
            .requests-table thead, 
            .requests-table tbody,
            .requests-table tr {
                display: block;
                width: 100%;
            }
            
            .requests-table tr {
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 10px;
            }
            
            .requests-table td, 
            .requests-table th {
                display: flex;
                flex-direction: column;
                border: none;
                padding: 8px 5px;
                text-align: left;
                white-space: normal;
            }
            
            .requests-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            .requests-table td {
                position: relative;
                padding-left: 50%;
                border-bottom: 1px solid #eee;
            }
            
            .requests-table td::before {
                position: absolute;
                left: 10px;
                width: 45%;
                white-space: nowrap;
                font-weight: bold;
                color: #007BFF;
            }
            
            .requests-table td:nth-of-type(1)::before { content: "Dataset"; }
            .requests-table td:nth-of-type(2)::before { content: "Requester"; }
            .requests-table td:nth-of-type(3)::before { content: "Date Requested"; }
            .requests-table td:nth-of-type(4)::before { content: "Reason"; }
            .requests-table td:nth-of-type(5)::before { content: "Verification"; }
            .requests-table td:nth-of-type(6)::before { content: "Status"; }
            .requests-table td:nth-of-type(7)::before { content: "Actions"; }
            
            .btn {
                margin-bottom: 5px;
                width: 100%;
                box-sizing: border-box;
                text-align: center;
            }
            
            .reason-text {
                max-width: 100%;
                width: 100%;
            }
            
            .modal-content {
                width: 90%;
                margin: 30% auto;
                padding: 15px;
            }
        }
        
        @media screen and (max-width: 480px) {
            .navbar {
                padding: 8px 10px;
            }
            
            .logo img {
                width: 40px;
            }
            
            .logo h2 {
                font-size: 14px;
                margin-left: 8px;
            }
            
            h1 {
                font-size: 20px;
            }
            
            .container {
                padding: 8px;
                margin: 15px auto;
                width: 95%;
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
            <h2>Manage Access Requests</h2>
        </div>
        <button class="mobile-menu-toggle" id="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <nav class="nav-links" id="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="datasets.php">ALL DATASETS</a>
            <a href="mydatasets.php">MY DATASETS</a>
            <a href="user_settings.php"><i class="fas fa-user-circle"></i></a>
        </nav>
    </header>
    
    <div class="container">
        <h1>Dataset Access Requests</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']); 
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (mysqli_num_rows($requestsResult) > 0): ?>
            <table class="requests-table">
                <thead>
                    <tr>
                        <th>Dataset</th>
                        <th>Requester</th>
                        <th>Date Requested</th>
                        <th>Reason</th>
                        <th>Verification</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($request = mysqli_fetch_assoc($requestsResult)): ?>
                        <tr>
                            <td>
                                <a href="dataset.php?id=<?php echo $request['dataset_id']; ?>">
                                    <?php echo htmlspecialchars($request['dataset_title']); ?>
                                </a>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                <br>
                                <small><?php echo htmlspecialchars($request['email']); ?></small>
                            </td>
                            <td><?php echo date('M j, Y, g:i a', strtotime($request['request_date'])); ?></td>
                            <td>
                                <div class="reason-text">
                                    <?php if (!empty($request['reason'])): ?>
                                        <div class="reason-preview">
                                            <?php echo nl2br(htmlspecialchars($request['reason'])); ?>
                                        </div>
                                        <button type="button" class="see-all-btn" onclick="showReasonModal(<?php echo $request['request_id']; ?>)">Show More</button>
                                        <input type="hidden" id="reason-<?php echo $request['request_id']; ?>" value="<?php echo htmlspecialchars($request['reason']); ?>">
                                    <?php else: ?>
                                        <em>No reason provided</em>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($request['verification_document'])): ?>
                                    <a href="<?php echo htmlspecialchars($request['verification_document']); ?>" target="_blank" class="document-link">
                                        <i class="fa-solid fa-file-alt"></i> View Document
                                    </a>
                                <?php else: ?>
                                    <em>No document</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-<?php echo strtolower($request['status']); ?>">
                                    <?php echo $request['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($request['status'] === 'Pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-approve">Approve</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-reject">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <a href="dataset.php?id=<?php echo $request['dataset_id']; ?>" class="btn btn-view">View Dataset</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-requests">
                <p>You don't have any dataset access requests to manage.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Reason Modal -->
    <div id="reasonModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideReasonModal()">&times;</span>
            <h3>Request Reason</h3>
            <div id="modal-reason-content"></div>
        </div>
    </div>

    <script>
        // Function to show the reason modal
        function showReasonModal(requestId) {
            // Get the full reason content
            const fullReason = document.getElementById('reason-' + requestId).value;
            
            // Create a simple paragraph for the text
            const reasonParagraph = document.createElement('p');
            reasonParagraph.style.margin = '0';
            reasonParagraph.style.padding = '0';
            reasonParagraph.style.whiteSpace = 'pre-wrap';
            reasonParagraph.style.wordBreak = 'break-word';
            reasonParagraph.textContent = fullReason;
            
            // Clear previous content and add the paragraph
            const modalContent = document.getElementById('modal-reason-content');
            modalContent.innerHTML = '';
            modalContent.appendChild(reasonParagraph);
            
            // Display the modal
            document.getElementById('reasonModal').style.display = 'block';
        }
        
        // Function to hide the reason modal
        function hideReasonModal() {
            document.getElementById('reasonModal').style.display = 'none';
        }
        
        // Close the modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('reasonModal');
            if (event.target == modal) {
                hideReasonModal();
            }
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