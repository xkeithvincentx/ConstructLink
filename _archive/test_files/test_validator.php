<?php
// Test script to check validator directly
define('APP_ROOT', __DIR__);

// Include necessary files
require_once 'config/Database.php';
// Don't include database.php as it conflicts
require_once 'core/AssetDataQualityValidator.php';

try {
    echo "Testing Asset Data Quality Validator\n";
    echo "=====================================\n\n";
    
    // Initialize validator
    $validator = new AssetDataQualityValidator();
    echo "✓ Validator initialized\n";
    
    // Test data - simulating asset 178
    $testAsset = [
        'id' => 178,
        'ref' => 'CON-LEG-IT-GN-0001',
        'name' => 'Laptop (IT Equipment) - Legacy Asset',
        'category_id' => 1,
        'category_name' => 'IT Equipment',
        'equipment_type_id' => 1,
        'equipment_type_name' => 'Laptop',
        'subtype_id' => 163,
        'subtype_name' => 'Office Laptop',
        'project_id' => 1,
        'project_name' => 'JCLDS - BMS Package',
        'quantity' => 1,
        'brand_id' => null,
        'brand_name' => null,
        'discipline_tags' => null,
        'discipline_names' => null,
        'sub_discipline_names' => null,
        'location' => 'Warehouse',
        'description' => 'Legacy laptop for office use',
        'acquisition_cost' => 50000,
        'serial_number' => null,
        'model' => null,
        'workflow_status' => 'pending_verification'
    ];
    
    echo "\nTest Asset Data:\n";
    echo "- Name: " . $testAsset['name'] . "\n";
    echo "- Category: " . $testAsset['category_name'] . "\n";
    echo "- Equipment Type: " . $testAsset['equipment_type_name'] . "\n";
    echo "- Subtype: " . $testAsset['subtype_name'] . "\n";
    echo "- Project: " . $testAsset['project_name'] . "\n";
    
    // Run validation
    echo "\nRunning validation...\n";
    $results = $validator->validateAsset($testAsset, 'Site Inventory Clerk');
    
    echo "\nValidation Results:\n";
    echo "- Overall Score: " . $results['overall_score'] . "%\n";
    echo "- Completeness Score: " . $results['completeness_score'] . "%\n";
    echo "- Accuracy Score: " . $results['accuracy_score'] . "%\n";
    echo "- Total Rules Applied: " . count($results['validation_results']) . "\n";
    echo "- Errors: " . count($results['errors']) . "\n";
    echo "- Warnings: " . count($results['warnings']) . "\n";
    echo "- Info: " . count($results['info']) . "\n";
    
    if (!empty($results['errors'])) {
        echo "\nErrors Found:\n";
        foreach ($results['errors'] as $error) {
            echo "  ❌ " . $error . "\n";
        }
    }
    
    if (!empty($results['warnings'])) {
        echo "\nWarnings Found:\n";
        foreach ($results['warnings'] as $warning) {
            echo "  ⚠️ " . $warning . "\n";
        }
    }
    
    if (!empty($results['info'])) {
        echo "\nInfo Messages:\n";
        foreach ($results['info'] as $info) {
            echo "  ℹ️ " . $info . "\n";
        }
    }
    
    echo "\n✅ Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>