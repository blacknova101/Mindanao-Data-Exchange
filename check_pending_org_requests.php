<?php
session_start();
include 'db_connection.php';

// Initialize response
$response = [
    'has_pending_request' => false
];

// Only proceed if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Check for pending organization creation requests
    $check_pending_sql = "SELECT request_id FROM organization_creation_requests 
                         WHERE user_id = ? AND status = 'Pending'";
    $check_pending_stmt = $conn->prepare($check_pending_sql);
    $check_pending_stmt->bind_param("i", $user_id);
    $check_pending_stmt->execute();
    $pending_result = $check_pending_stmt->get_result();
    
    // If there are pending requests, set has_pending_request to true
    if ($pending_result->num_rows > 0) {
        $response['has_pending_request'] = true;
    }
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);
exit; 