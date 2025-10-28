-- Migration: Create system_branding table
-- Date: 2025-10-28
-- Purpose: Convert hardcoded branding from config/company.php to database-driven system
-- Related: WAREHOUSEMAN_DASHBOARD_UX_AUDIT.md

-- Create system_branding table
CREATE TABLE IF NOT EXISTS system_branding (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) NOT NULL DEFAULT 'V CUTAMORA CONSTRUCTION INC.',
    app_name VARCHAR(255) NOT NULL DEFAULT 'ConstructLink™',
    tagline VARCHAR(500) DEFAULT 'QUALITY WORKS AND CLIENT SATISFACTION IS OUR GAME',
    logo_url VARCHAR(500) DEFAULT '/assets/images/company-logo.png',
    favicon_url VARCHAR(500) DEFAULT '/assets/images/favicon.ico',
    primary_color VARCHAR(7) NOT NULL DEFAULT '#6B7280',
    secondary_color VARCHAR(7) NOT NULL DEFAULT '#9CA3AF',
    accent_color VARCHAR(7) NOT NULL DEFAULT '#059669',
    success_color VARCHAR(7) NOT NULL DEFAULT '#059669',
    warning_color VARCHAR(7) NOT NULL DEFAULT '#D97706',
    danger_color VARCHAR(7) NOT NULL DEFAULT '#DC2626',
    info_color VARCHAR(7) NOT NULL DEFAULT '#2563EB',
    contact_email VARCHAR(255) DEFAULT 'info@vcutamora.com',
    contact_phone VARCHAR(50) DEFAULT '+63 XXX XXX XXXX',
    address TEXT,
    footer_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default branding values from config/company.php
INSERT INTO system_branding (
    id,
    company_name,
    app_name,
    tagline,
    logo_url,
    favicon_url,
    primary_color,
    secondary_color,
    accent_color,
    success_color,
    warning_color,
    danger_color,
    info_color,
    contact_email,
    contact_phone,
    address,
    footer_text
) VALUES (
    1,
    'V CUTAMORA CONSTRUCTION INC.',
    'ConstructLink™',
    'QUALITY WORKS AND CLIENT SATISFACTION IS OUR GAME',
    '/assets/images/company-logo.png',
    '/assets/images/favicon.ico',
    '#6B7280',
    '#9CA3AF',
    '#059669',
    '#059669',
    '#D97706',
    '#DC2626',
    '#2563EB',
    'info@vcutamora.com',
    '+63 XXX XXX XXXX',
    'Your Company Address Here',
    '© 2025 V CUTAMORA CONSTRUCTION INC. All rights reserved. Powered by ConstructLink™'
) ON DUPLICATE KEY UPDATE
    updated_at = CURRENT_TIMESTAMP;

-- Add indexes for performance
CREATE INDEX idx_system_branding_id ON system_branding(id);

-- Verification query
SELECT
    id,
    company_name,
    app_name,
    tagline,
    success_color,
    warning_color,
    danger_color,
    info_color,
    created_at
FROM system_branding
WHERE id = 1;
