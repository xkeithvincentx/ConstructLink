<?php
require_once 'config/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test if new columns exist
    $stmt = $pdo->query("DESCRIBE procurement_orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $newColumns = ['purchase_receipt_file', 'supporting_evidence_file', 'file_upload_notes', 'retroactive_current_state', 'retroactive_target_status'];
    
    echo "Checking for new columns:\n";
    foreach ($newColumns as $newCol) {
        $exists = false;
        foreach ($columns as $col) {
            if ($col['Field'] === $newCol) {
                $exists = true;
                break;
            }
        }
        echo "$newCol: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>