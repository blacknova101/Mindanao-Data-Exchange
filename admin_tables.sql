-- Modify administrator table if it exists
ALTER TABLE administrator 
    MODIFY COLUMN admin_id INT AUTO_INCREMENT,
    MODIFY COLUMN name VARCHAR(100) NOT NULL,
    MODIFY COLUMN email VARCHAR(100) NOT NULL,
    MODIFY COLUMN password_hash VARCHAR(255) NOT NULL,
    MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    MODIFY COLUMN last_login TIMESTAMP NULL;

-- Add unique index for email if it doesn't exist
SET @exist := (SELECT COUNT(1) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema = DATABASE()
    AND table_name = 'administrator' 
    AND index_name = 'idx_email');

SET @sql := IF(@exist = 0, 
    'ALTER TABLE administrator ADD UNIQUE INDEX idx_email (email)',
    'SELECT "Index idx_email already exists"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add is_active column to users table if it doesn't exist
ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;

-- Add role column to users table if it doesn't exist
ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin', 'moderator') DEFAULT 'user';

-- Create notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('in_app', 'email', 'both') NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES administrator(admin_id)
) ENGINE=InnoDB;

-- Create user notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS user_notifications (
    user_notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    notification_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (notification_id) REFERENCES notifications(notification_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_notification (user_id, notification_id)
) ENGINE=InnoDB;

-- Create indexes for faster notification queries if they don't exist
SET @exist := (SELECT COUNT(1) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema = DATABASE()
    AND table_name = 'user_notifications' 
    AND index_name = 'idx_user_notifications_user_id');

SET @sql := IF(@exist = 0, 
    'CREATE INDEX idx_user_notifications_user_id ON user_notifications(user_id)',
    'SELECT "Index idx_user_notifications_user_id already exists"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(1) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema = DATABASE()
    AND table_name = 'user_notifications' 
    AND index_name = 'idx_user_notifications_notification_id');

SET @sql := IF(@exist = 0, 
    'CREATE INDEX idx_user_notifications_notification_id ON user_notifications(notification_id)',
    'SELECT "Index idx_user_notifications_notification_id already exists"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(1) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema = DATABASE()
    AND table_name = 'notifications' 
    AND index_name = 'idx_notifications_created_at');

SET @sql := IF(@exist = 0, 
    'CREATE INDEX idx_notifications_created_at ON notifications(created_at)',
    'SELECT "Index idx_notifications_created_at already exists"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create user_sessions table to track logged-in users
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Add index for faster lookups
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_last_activity ON user_sessions(last_activity); 