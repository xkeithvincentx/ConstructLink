-- Create Email Action Tokens Table for ConstructLink™
-- Enables secure one-click actions from email links

CREATE TABLE IF NOT EXISTS email_action_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(64) UNIQUE NOT NULL COMMENT 'Secure random token',
    action_type ENUM('transfer_verify', 'transfer_approve', 'transfer_dispatch', 'transfer_receive', 'transfer_return_receive', 'transfer_cancel', 'procurement_approve', 'procurement_schedule', 'procurement_receive') NOT NULL,
    related_id INT NOT NULL COMMENT 'ID of the transfer/entity',
    user_id INT NOT NULL COMMENT 'User who should perform the action',
    expires_at TIMESTAMP NOT NULL COMMENT 'Token expiration (24-48 hours)',
    used_at TIMESTAMP NULL COMMENT 'When token was used (null if not used)',
    ip_address VARCHAR(45) NULL COMMENT 'IP address that used the token',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_action_type (action_type),
    INDEX idx_expires_at (expires_at),
    INDEX idx_used_at (used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
