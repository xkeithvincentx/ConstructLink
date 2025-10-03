-- Create Notifications System for ConstructLink™
-- Provides notification functionality for workflow events

CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger', 'transfer', 'approval', 'workflow') DEFAULT 'info',
    url VARCHAR(500) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    related_type VARCHAR(50) NULL COMMENT 'Type of related entity (transfer, asset, project, etc.)',
    related_id INT NULL COMMENT 'ID of related entity',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_related (related_type, related_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
