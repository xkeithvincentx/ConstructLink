<?php
/**
 * Sample Module for ConstructLink™
 * Demonstrates module development best practices
 */

class SampleModule {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadConfig();
    }
    
    /**
     * Initialize the module
     */
    public function init() {
        // Module initialization code
        error_log("Sample Module initialized successfully");
        
        // Register any event hooks or global functions here
        $this->registerHooks();
    }
    
    /**
     * Install the module
     */
    public function install() {
        try {
            // Run any installation logic
            error_log("Sample Module installation completed");
            return true;
        } catch (Exception $e) {
            error_log("Sample Module installation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Uninstall the module
     */
    public function uninstall() {
        try {
            // Cleanup logic
            error_log("Sample Module uninstallation completed");
            return true;
        } catch (Exception $e) {
            error_log("Sample Module uninstallation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get module dashboard widget
     */
    public function getDashboardWidget() {
        return [
            'title' => 'Sample Module',
            'content' => 'This is a sample module widget!',
            'color' => 'primary'
        ];
    }
    
    /**
     * Load module configuration
     */
    private function loadConfig() {
        $configFile = __DIR__ . '/config.php';
        if (file_exists($configFile)) {
            $this->config = include $configFile;
        } else {
            $this->config = [];
        }
    }
    
    /**
     * Register module hooks
     */
    private function registerHooks() {
        // Example: Register a hook for dashboard widgets
        // In a real implementation, this would integrate with the main application's hook system
    }
    
    /**
     * Get module configuration
     */
    public function getConfig($key = null, $default = null) {
        if ($key === null) {
            return $this->config;
        }
        
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Update module configuration
     */
    public function updateConfig($key, $value) {
        $this->config[$key] = $value;
        
        // Save to database or file
        // This is a simplified example
        try {
            $sql = "
                INSERT INTO module_settings (module_name, setting_key, setting_value, updated_at)
                VALUES ('sample-module', ?, ?, NOW())
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$key, $value, $value]);
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to update sample module config: " . $e->getMessage());
            return false;
        }
    }
}