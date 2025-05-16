-- Table for user notifications
CREATE TABLE IF NOT EXISTS user_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    related_id INT,
    notification_type VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (is_read)
);

-- Table for dataset access requests if not already created
CREATE TABLE IF NOT EXISTS dataset_access_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    dataset_id INT NOT NULL,
    requester_id INT NOT NULL,
    owner_id INT NOT NULL,
    request_date TIMESTAMP NOT NULL,
    status VARCHAR(20) NOT NULL,
    CONSTRAINT unique_request UNIQUE(dataset_id, requester_id)
);

-- Table for organization membership requests
CREATE TABLE IF NOT EXISTS organization_membership_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    user_id INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Approved', 'Rejected', 'Expired') DEFAULT 'Pending',
    message TEXT,
    admin_response TEXT,
    expiration_date TIMESTAMP NULL,
    CONSTRAINT unique_org_request UNIQUE(organization_id, user_id)
);

-- Add auto_accept column to organizations table if it doesn't exist
ALTER TABLE organizations ADD COLUMN IF NOT EXISTS auto_accept BOOLEAN DEFAULT FALSE; 