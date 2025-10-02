-- ConstructLink™ Brand Workflow System
-- Enhances MVA workflow for unknown brand management
-- Author: Claude Code Assistant
-- Version: 1.0.0

-- Brand suggestions table for Warehouseman/Site Inventory Clerk suggestions
CREATE TABLE IF NOT EXISTS brand_suggestions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    suggested_name VARCHAR(100) NOT NULL,
    original_context VARCHAR(255), -- Context where brand was encountered
    suggested_by INT NOT NULL,
    asset_id INT NULL, -- Link to asset that triggered the suggestion
    category_context VARCHAR(100) NULL, -- Category where brand was used
    status ENUM('pending', 'approved', 'rejected', 'merged') DEFAULT 'pending',
    reviewed_by INT NULL,
    review_notes TEXT NULL,
    approved_brand_id INT NULL, -- Link to final brand if approved
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    
    FOREIGN KEY (suggested_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_brand_id) REFERENCES asset_brands(id) ON DELETE SET NULL,
    
    INDEX idx_brand_suggestions_status (status),
    INDEX idx_brand_suggestions_suggested_by (suggested_by),
    INDEX idx_brand_suggestions_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Unknown brand notifications for Asset Directors
CREATE TABLE IF NOT EXISTS unknown_brand_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    brand_name VARCHAR(100) NOT NULL,
    asset_id INT NOT NULL,
    asset_name VARCHAR(200) NOT NULL,
    created_by INT NOT NULL,
    category_context VARCHAR(100) NULL,
    project_context VARCHAR(100) NULL,
    notification_type ENUM('unknown_brand', 'brand_suggestion', 'brand_conflict') DEFAULT 'unknown_brand',
    status ENUM('pending', 'in_review', 'resolved', 'dismissed') DEFAULT 'pending',
    assigned_to INT NULL, -- Asset Director assigned to handle this
    resolution_notes TEXT NULL,
    related_suggestion_id INT NULL, -- Link to brand_suggestions if applicable
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (related_suggestion_id) REFERENCES brand_suggestions(id) ON DELETE SET NULL,
    
    INDEX idx_unknown_brand_notifications_status (status),
    INDEX idx_unknown_brand_notifications_assigned (assigned_to),
    INDEX idx_unknown_brand_notifications_created (created_at),
    INDEX idx_unknown_brand_notifications_brand (brand_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Brand workflow tracking for audit and process management
CREATE TABLE IF NOT EXISTS brand_workflow_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_type ENUM('brand_suggestion', 'unknown_brand_notification', 'asset_brand') NOT NULL,
    entity_id INT NOT NULL,
    action VARCHAR(50) NOT NULL, -- 'suggested', 'reviewed', 'approved', 'rejected', 'merged', etc.
    performed_by INT NOT NULL,
    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NULL,
    notes TEXT NULL,
    metadata JSON NULL, -- Additional context data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_brand_workflow_log_entity (entity_type, entity_id),
    INDEX idx_brand_workflow_log_performed (performed_by),
    INDEX idx_brand_workflow_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asset brand history for tracking brand changes on assets
CREATE TABLE IF NOT EXISTS asset_brand_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_id INT NOT NULL,
    old_brand_name VARCHAR(100) NULL,
    old_brand_id INT NULL,
    new_brand_name VARCHAR(100) NULL,
    new_brand_id INT NULL,
    changed_by INT NOT NULL,
    change_reason VARCHAR(255) NULL,
    workflow_reference VARCHAR(100) NULL, -- Reference to suggestion or notification
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (old_brand_id) REFERENCES asset_brands(id) ON DELETE SET NULL,
    FOREIGN KEY (new_brand_id) REFERENCES asset_brands(id) ON DELETE SET NULL,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_asset_brand_history_asset (asset_id),
    INDEX idx_asset_brand_history_changed (changed_by),
    INDEX idx_asset_brand_history_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default notification preferences for existing Asset Directors
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public)
VALUES 
    ('brand_workflow_notifications', 'true', 'boolean', 'Enable notifications for brand workflow events', false),
    ('auto_assign_brand_notifications', 'true', 'boolean', 'Automatically assign brand notifications to available Asset Directors', false),
    ('brand_suggestion_auto_approve_threshold', '0.95', 'string', 'Confidence threshold for auto-approving brand suggestions', false),
    ('brand_workflow_notification_email', 'true', 'boolean', 'Send email notifications for brand workflow events', false);

-- Create views for easy querying
CREATE OR REPLACE VIEW pending_brand_suggestions AS
SELECT 
    bs.id,
    bs.suggested_name,
    bs.original_context,
    bs.created_at,
    u.full_name as suggested_by_name,
    a.name as asset_name,
    a.ref as asset_ref,
    bs.category_context
FROM brand_suggestions bs
JOIN users u ON bs.suggested_by = u.id
LEFT JOIN assets a ON bs.asset_id = a.id
WHERE bs.status = 'pending'
ORDER BY bs.created_at DESC;

CREATE OR REPLACE VIEW active_unknown_brand_notifications AS
SELECT 
    ubn.id,
    ubn.brand_name,
    ubn.asset_name,
    ubn.created_at,
    u.full_name as created_by_name,
    ad.full_name as assigned_to_name,
    ubn.category_context,
    ubn.project_context,
    ubn.status
FROM unknown_brand_notifications ubn
JOIN users u ON ubn.created_by = u.id
LEFT JOIN users ad ON ubn.assigned_to = ad.id
WHERE ubn.status IN ('pending', 'in_review')
ORDER BY ubn.created_at DESC;

-- Grant permissions (adjust as needed based on your MySQL user setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON brand_suggestions TO 'constructlink'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON unknown_brand_notifications TO 'constructlink'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON brand_workflow_log TO 'constructlink'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON asset_brand_history TO 'constructlink'@'localhost';

-- Success message
SELECT 'Brand Workflow System tables created successfully!' AS message;