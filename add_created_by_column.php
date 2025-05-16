<?php
/**
 * Migration script to add created_by column to organizations table
 * 
 * This script adds a created_by column to track who created each organization
 * and populates it with the first admin of each organization
 */

// Include database connection
require_once('db_connection.php');

echo "Starting migration: Adding created_by column to organizations table...\n";

// Check if column already exists
$check_column_sql = "SHOW COLUMNS FROM organizations LIKE 'created_by'";
$check_column_result = $conn->query($check_column_sql);

if ($check_column_result->num_rows > 0) {
    echo "Column 'created_by' already exists. Checking existing values...\n";
    
    // Check the data in the column
    $check_data_sql = "SELECT organization_id, name, created_by FROM organizations";
    $check_data_result = $conn->query($check_data_sql);
    
    if ($check_data_result->num_rows > 0) {
        echo "Found " . $check_data_result->num_rows . " organizations:\n";
        while ($org = $check_data_result->fetch_assoc()) {
            $org_id = $org['organization_id'];
            $org_name = $org['name'];
            $created_by = $org['created_by'];
            echo "Organization ID: $org_id, Name: $org_name, Created By: " . ($created_by ?: "NULL") . "\n";
        }
    } else {
        echo "No organizations found in the database.\n";
    }
    
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Add created_by column
    $add_column_sql = "ALTER TABLE organizations ADD COLUMN created_by INT NULL";
    if ($conn->query($add_column_sql)) {
        echo "Added created_by column to organizations table.\n";
    } else {
        throw new Exception("Failed to add column: " . $conn->error);
    }

    // Find all organizations
    $orgs_sql = "SELECT organization_id FROM organizations";
    $orgs_result = $conn->query($orgs_sql);
    
    echo "Found " . $orgs_result->num_rows . " organizations to update.\n";
    
    if ($orgs_result->num_rows > 0) {
        // Prepare the update statement
        $update_sql = "UPDATE organizations SET created_by = ? WHERE organization_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        while ($org = $orgs_result->fetch_assoc()) {
            $org_id = $org['organization_id'];
            
            // Find the first admin user in the organization
            $find_admin_sql = "SELECT user_id FROM users 
                              WHERE organization_id = ? 
                              ORDER BY user_id ASC LIMIT 1";
            $find_admin_stmt = $conn->prepare($find_admin_sql);
            $find_admin_stmt->bind_param("i", $org_id);
            $find_admin_stmt->execute();
            $admin_result = $find_admin_stmt->get_result();
            
            if ($admin_result->num_rows > 0) {
                $admin = $admin_result->fetch_assoc();
                $admin_id = $admin['user_id'];
                
                // Update organization with creator ID
                $update_stmt->bind_param("ii", $admin_id, $org_id);
                $update_stmt->execute();
                
                echo "Organization ID $org_id: Set created_by to user ID $admin_id\n";
            } else {
                echo "WARNING: No admin found for organization ID $org_id\n";
            }
        }
    }
    
    // Add foreign key constraint
    $add_fk_sql = "ALTER TABLE organizations 
                  ADD CONSTRAINT fk_org_creator 
                  FOREIGN KEY (created_by) REFERENCES users(user_id)";
    if ($conn->query($add_fk_sql)) {
        echo "Added foreign key constraint for created_by column.\n";
    } else {
        throw new Exception("Failed to add foreign key constraint: " . $conn->error);
    }
    
    // Commit transaction
    $conn->commit();
    echo "Migration completed successfully.\n";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Migration failed. Changes have been rolled back.\n";
}

// Close connection
$conn->close(); 