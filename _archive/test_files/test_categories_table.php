<?php
define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== CATEGORIES TABLE STRUCTURE ===\n";
    $stmt = $db->query("DESCRIBE categories");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Default']}\n";
    }
    
    echo "\n=== CATEGORIES DATA ===\n";
    $stmt = $db->query("SELECT * FROM categories LIMIT 10");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($categories)) {
        echo "No categories found!\n";
    } else {
        foreach ($categories as $category) {
            echo "ID: {$category['id']} - Name: {$category['name']} - Status: " . (isset($category['is_active']) ? $category['is_active'] : 'unknown') . "\n";
        }
    }
    
    echo "\n=== BRANDS TABLE CHECK ===\n";
    $stmt = $db->query("SHOW TABLES LIKE 'asset_brands'");
    $brandTableExists = $stmt->fetch();
    
    if ($brandTableExists) {
        echo "asset_brands table exists\n";
        $stmt = $db->query("SELECT COUNT(*) FROM asset_brands");
        $brandCount = $stmt->fetchColumn();
        echo "Brands count: {$brandCount}\n";
        
        if ($brandCount > 0) {
            $stmt = $db->query("SELECT * FROM asset_brands LIMIT 3");
            $sampleBrands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            print_r($sampleBrands);
        }
    } else {
        echo "asset_brands table does not exist!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>