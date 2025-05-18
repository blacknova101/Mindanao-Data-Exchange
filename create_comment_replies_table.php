<?php
// Include database connection
include('db_connection.php');

// SQL to create comment replies table
$sql = "
CREATE TABLE IF NOT EXISTS comment_replies (
    reply_id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    reply_text TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comment_id) REFERENCES datasetcomments(comment_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Execute the SQL
if ($conn->query($sql) === TRUE) {
    echo "Comment replies table created successfully.";
} else {
    echo "Error creating comment replies table: " . $conn->error;
}

// Close the connection
$conn->close();
?> 