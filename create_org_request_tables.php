<?php
include 'db_connection.php';

// Create the organization_creation_requests table
$create_org_requests_table = "CREATE TABLE IF NOT EXISTS organization_creation_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    website_url VARCHAR(255),
    description TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    admin_response TEXT,
    request_date DATETIME NOT NULL,
    reviewed_date DATETIME,
    reviewed_by INT,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
) ENGINE=InnoDB";

// Create the organization_request_documents table
$create_documents_table = "CREATE TABLE IF NOT EXISTS organization_request_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    document_path VARCHAR(255) NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES organization_creation_requests(request_id) ON DELETE CASCADE
) ENGINE=InnoDB";

// Execute the queries
if ($conn->query($create_org_requests_table) === TRUE) {
    echo "Organization creation requests table created successfully<br>";
} else {
    echo "Error creating organization creation requests table: " . $conn->error . "<br>";
}

if ($conn->query($create_documents_table) === TRUE) {
    echo "Organization request documents table created successfully<br>";
} else {
    echo "Error creating organization request documents table: " . $conn->error . "<br>";
}

// Create directory for organization request documents if it doesn't exist
$upload_dir = "uploads/org_requests/";
if (!is_dir($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "Document upload directory created successfully<br>";
    } else {
        echo "Error creating document upload directory<br>";
    }
}

echo "Migration completed.";
?> 