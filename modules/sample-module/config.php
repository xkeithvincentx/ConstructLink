<?php
/**
 * Sample Module Configuration
 */

return [
    'enabled_features' => [
        'dashboard_widget' => true,
        'api_integration' => false,
        'custom_reports' => true
    ],
    
    'settings' => [
        'max_items_per_page' => 25,
        'cache_timeout' => 300,
        'debug_mode' => false
    ],
    
    'appearance' => [
        'theme_color' => '#007bff',
        'icon' => 'bi-puzzle',
        'show_in_sidebar' => true
    ],
    
    'permissions' => [
        'default_role' => 'sample_module.view',
        'admin_role' => 'sample_module.manage'
    ]
];