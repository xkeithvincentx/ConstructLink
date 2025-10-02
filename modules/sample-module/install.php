<?php
/**
 * Sample Module Installation Script
 * This file is executed when the module is installed
 */

try {
    // Perform any custom installation logic here
    
    // Example: Create directories
    $moduleDir = __DIR__;
    $uploadsDir = $moduleDir . '/uploads';
    $cacheDir = $moduleDir . '/cache';
    
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    // Example: Copy default files
    $defaultFiles = [
        'assets/sample.css' => APP_ROOT . '/assets/css/sample-module.css',
        'assets/sample.js' => APP_ROOT . '/assets/js/sample-module.js'
    ];
    
    foreach ($defaultFiles as $source => $destination) {
        $sourcePath = $moduleDir . '/' . $source;
        if (file_exists($sourcePath)) {
            $destDir = dirname($destination);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            copy($sourcePath, $destination);
        }
    }
    
    // Log successful installation
    error_log("Sample Module: Installation script executed successfully");
    
    // Return true to indicate success
    return true;
    
} catch (Exception $e) {
    // Log error
    error_log("Sample Module: Installation script failed - " . $e->getMessage());
    
    // Return false to indicate failure
    return false;
}