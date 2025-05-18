<?php
// Include database connection
include('db_connection.php');

// SQL to create notifications table if it doesn't exist
$sql = "
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'general',
    reference_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Execute the SQL
if ($conn->query($sql) === TRUE) {
    echo "Notifications table created successfully or already exists.";
} else {
    echo "Error creating notifications table: " . $conn->error;
}

// Close the connection
$conn->close();
?> 