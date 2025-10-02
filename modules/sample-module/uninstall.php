<?php
/**
 * Sample Module Uninstallation Script
 * This file is executed when the module is uninstalled
 */

try {
    // Perform cleanup operations
    
    // Example: Remove created directories (be careful with this!)
    $moduleDir = __DIR__;
    $uploadsDir = $moduleDir . '/uploads';
    $cacheDir = $moduleDir . '/cache';
    
    // Remove cache directory and contents
    if (is_dir($cacheDir)) {
        $files = array_diff(scandir($cacheDir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $cacheDir . '/' . $file;
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
        rmdir($cacheDir);
    }
    
    // Note: Be careful about removing uploads directory as it may contain user data
    // Only remove if you're certain it's safe to do so
    
    // Example: Remove copied files
    $filesToRemove = [
        APP_ROOT . '/assets/css/sample-module.css',
        APP_ROOT . '/assets/js/sample-module.js'
    ];
    
    foreach ($filesToRemove as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    // Example: Clean up database data (optional - be very careful!)
    // Uncomment the following lines only if you want to completely remove all module data
    /*
    $db = Database::getInstance()->getConnection();
    
    // Remove module-specific data
    $db->exec("DROP TABLE IF EXISTS sample_module_data");
    
    // Remove module settings (keep this commented to preserve settings on reinstall)
    // $db->exec("DELETE FROM module_settings WHERE module_name = 'sample-module'");
    */
    
    // Log successful uninstallation
    error_log("Sample Module: Uninstallation script executed successfully");
    
    // Return true to indicate success
    return true;
    
} catch (Exception $e) {
    // Log error
    error_log("Sample Module: Uninstallation script failed - " . $e->getMessage());
    
    // Return false to indicate failure
    return false;
}