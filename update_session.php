<?php
/**
 * Update Session Organization Info
 * 
 * This script checks if the user's organization_id in the session matches
 * what's in the database, and updates it if necessary.
 */

// Only proceed if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get user's current organization from database
    $check_org_sql = "SELECT organization_id, user_type FROM users WHERE user_id = ?";
    $check_org_stmt = $conn->prepare($check_org_sql);
    $check_org_stmt->bind_param("i", $user_id);
    $check_org_stmt->execute();
    $result = $check_org_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $db_organization_id = $user_data['organization_id'];
        $db_user_type = $user_data['user_type'];
        
        // If database has org_id but session doesn't, update session
        if ($db_organization_id !== null && 
            (!isset($_SESSION['organization_id']) || $_SESSION['organization_id'] != $db_organization_id)) {
            $_SESSION['organization_id'] = $db_organization_id;
            $_SESSION['user_type'] = $db_user_type;
            
            // Log the session update
            error_log("Session updated for user $user_id: organization_id set to $db_organization_id");
        }
        
        // Check if session has org_id but database doesn't (possible after leaving org)
        if ($db_organization_id === null && isset($_SESSION['organization_id'])) {
            unset($_SESSION['organization_id']);
            $_SESSION['user_type'] = $db_user_type;
            
            // Log the session update
            error_log("Session updated for user $user_id: organization_id unset");
        }
    }
}
?> 