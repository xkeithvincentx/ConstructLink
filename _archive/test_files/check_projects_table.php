<?php
define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== PROJECTS TABLE STRUCTURE ===\n";
    $stmt = $db->query("DESCRIBE projects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "{$column['Field']} - {$column['Type']}\n";
    }
    
    echo "\n=== SAMPLE PROJECTS ===\n";
    $stmt = $db->query("SELECT * FROM projects LIMIT 5");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        $statusField = isset($project['status']) ? $project['status'] : (isset($project['is_active']) ? ($project['is_active'] ? 'active' : 'inactive') : 'unknown');
        echo "ID: {$project['id']} - Name: {$project['name']} - Status: {$statusField}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>