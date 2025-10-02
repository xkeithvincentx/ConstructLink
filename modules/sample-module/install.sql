-- Sample Module Database Schema
-- This file demonstrates how to create database tables for a module

-- Create module settings table
CREATE TABLE IF NOT EXISTS module_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_name VARCHAR(100) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_module_setting (module_name, setting_key),
    INDEX (module_name)
);

-- Sample module data table
CREATE TABLE IF NOT EXISTS sample_module_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (status),
    INDEX (created_at),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert sample data
INSERT INTO sample_module_data (title, description, status) VALUES
('Sample Item 1', 'This is a sample item created by the module installation', 'active'),
('Sample Item 2', 'Another sample item to demonstrate module functionality', 'active');

-- Insert default module settings
INSERT INTO module_settings (module_name, setting_key, setting_value) VALUES
('sample-module', 'installation_date', NOW()),
('sample-module', 'version', '1.0.0'),
('sample-module', 'enabled', 'true')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);