<?php
/**
 * ConstructLink™ Asset Classification Debug Test
 * Comprehensive testing script to diagnose why equipment classification data isn't being saved
 * 
 * This script tests:
 * 1. Database schema verification
 * 2. Form submission simulation 
 * 3. JavaScript/AJAX functionality
 * 4. Database query patterns
 * 5. Form validation issues
 * 6. Controller debug information
 */

// Include necessary files
define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/core/Autoloader.php';

// Initialize autoloader and dependencies
$autoloader = new Autoloader();
$autoloader->register();

try {
    // Initialize database connection
    $db = Database::getInstance()->getConnection();
    
    echo "<h1>ConstructLink Asset Classification Debug Report</h1>";
    echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p><hr>";
    
    // ===== TEST 1: DATABASE SCHEMA VERIFICATION =====
    echo "<h2>Test 1: Database Schema Verification</h2>";
    
    try {
        // Check if equipment classification fields exist in assets table
        $stmt = $db->query("DESCRIBE assets");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasEquipmentTypeId = false;
        $hasSubtypeId = false;
        
        echo "<h3>Assets Table Schema:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra'] ?? '') . "</td>";
            echo "</tr>";
            
            if ($column['Field'] === 'equipment_type_id') $hasEquipmentTypeId = true;
            if ($column['Field'] === 'subtype_id') $hasSubtypeId = true;
        }
        echo "</table>";
        
        echo "<h3>Classification Fields Status:</h3>";
        echo "<p><strong>equipment_type_id present:</strong> " . ($hasEquipmentTypeId ? "✅ YES" : "❌ NO") . "</p>";
        echo "<p><strong>subtype_id present:</strong> " . ($hasSubtypeId ? "✅ YES" : "❌ NO") . "</p>";
        
        if (!$hasEquipmentTypeId || !$hasSubtypeId) {
            echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
            echo "<strong>CRITICAL ISSUE:</strong> Classification fields are missing from the assets table!<br>";
            echo "You need to run the asset_subtypes_system.sql migration to add these fields.";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error checking schema: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // ===== TEST 2: CHECK CLASSIFICATION TABLES =====
    echo "<h2>Test 2: Classification Tables Verification</h2>";
    
    $classificationTables = ['asset_equipment_types', 'asset_subtypes', 'asset_specification_templates'];
    
    foreach ($classificationTables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p><strong>$table:</strong> " . $result['count'] . " records</p>";
            
            if ($result['count'] > 0) {
                // Show sample data
                $stmt = $db->query("SELECT * FROM `$table` LIMIT 3");
                $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<details><summary>Sample data</summary>";
                echo "<pre>" . print_r($samples, true) . "</pre>";
                echo "</details>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'><strong>$table:</strong> Table not found - " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // ===== TEST 3: EXISTING ASSET DATA ANALYSIS =====
    echo "<h2>Test 3: Existing Asset Data Analysis</h2>";
    
    try {
        // Check existing assets for classification data
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total_assets,
                COUNT(equipment_type_id) as assets_with_equipment_type,
                COUNT(subtype_id) as assets_with_subtype,
                MIN(created_at) as oldest_asset,
                MAX(created_at) as newest_asset
            FROM assets
        ");
        $assetStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Asset Classification Statistics:</h3>";
        echo "<ul>";
        echo "<li><strong>Total Assets:</strong> " . $assetStats['total_assets'] . "</li>";
        echo "<li><strong>Assets with Equipment Type ID:</strong> " . $assetStats['assets_with_equipment_type'] . " (" . ($assetStats['total_assets'] > 0 ? round($assetStats['assets_with_equipment_type'] / $assetStats['total_assets'] * 100, 1) : 0) . "%)</li>";
        echo "<li><strong>Assets with Subtype ID:</strong> " . $assetStats['assets_with_subtype'] . " (" . ($assetStats['total_assets'] > 0 ? round($assetStats['assets_with_subtype'] / $assetStats['total_assets'] * 100, 1) : 0) . "%)</li>";
        echo "<li><strong>Date Range:</strong> " . $assetStats['oldest_asset'] . " to " . $assetStats['newest_asset'] . "</li>";
        echo "</ul>";
        
        // Show recent assets without classification
        if ($hasEquipmentTypeId && $hasSubtypeId) {
            $stmt = $db->query("
                SELECT id, ref, name, created_at, equipment_type_id, subtype_id
                FROM assets 
                WHERE equipment_type_id IS NULL OR subtype_id IS NULL
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $unclassifiedAssets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($unclassifiedAssets) > 0) {
                echo "<h3>Recent Assets Without Classification:</h3>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Reference</th><th>Name</th><th>Created</th><th>Equipment Type ID</th><th>Subtype ID</th></tr>";
                foreach ($unclassifiedAssets as $asset) {
                    echo "<tr>";
                    echo "<td>" . $asset['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($asset['ref']) . "</td>";
                    echo "<td>" . htmlspecialchars($asset['name']) . "</td>";
                    echo "<td>" . $asset['created_at'] . "</td>";
                    echo "<td>" . ($asset['equipment_type_id'] ?? 'NULL') . "</td>";
                    echo "<td>" . ($asset['subtype_id'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error analyzing asset data: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // ===== TEST 4: FORM SUBMISSION SIMULATION =====
    echo "<h2>Test 4: Form Submission Simulation</h2>";
    
    // Simulate form data that should include equipment classification
    $testFormData = [
        'ref' => '',
        'name' => 'Test MIG Welder',
        'description' => 'Test asset for classification debugging',
        'category_id' => 13, // Assuming Welding Equipment category
        'project_id' => 1,
        'maker_id' => 1,
        'vendor_id' => 1,
        'equipment_type_id' => 1, // Test equipment type
        'subtype_id' => 1, // Test subtype
        'acquired_date' => date('Y-m-d'),
        'acquisition_cost' => 5000.00,
        'model' => 'Test Model',
        'serial_number' => 'TEST-001'
    ];
    
    echo "<h3>Test Form Data:</h3>";
    echo "<pre>" . print_r($testFormData, true) . "</pre>";
    
    // Test if we can create an asset with classification data
    try {
        $assetModel = new AssetModel();
        
        // Test the sanitization and preparation logic from AssetController
        $sanitizedData = [
            'ref' => !empty($testFormData['ref']) ? $testFormData['ref'] : null,
            'name' => $testFormData['name'],
            'description' => $testFormData['description'],
            'category_id' => (int)$testFormData['category_id'],
            'project_id' => (int)$testFormData['project_id'],
            'maker_id' => !empty($testFormData['maker_id']) ? (int)$testFormData['maker_id'] : null,
            'vendor_id' => !empty($testFormData['vendor_id']) ? (int)$testFormData['vendor_id'] : null,
            'equipment_type_id' => !empty($testFormData['equipment_type_id']) ? (int)$testFormData['equipment_type_id'] : null,
            'subtype_id' => !empty($testFormData['subtype_id']) ? (int)$testFormData['subtype_id'] : null,
            'acquired_date' => $testFormData['acquired_date'],
            'acquisition_cost' => (float)$testFormData['acquisition_cost'],
            'model' => $testFormData['model'],
            'serial_number' => $testFormData['serial_number']
        ];
        
        echo "<h3>Sanitized Data:</h3>";
        echo "<pre>" . print_r($sanitizedData, true) . "</pre>";
        
        // Test if the model has the correct allowed fields
        echo "<h3>AssetModel Allowed Fields Check:</h3>";
        $reflection = new ReflectionClass($assetModel);
        $properties = $reflection->getProperties();
        
        $allowedFieldsProperty = null;
        foreach ($properties as $property) {
            if ($property->getName() === 'allowedFields') {
                $property->setAccessible(true);
                $allowedFields = $property->getValue($assetModel);
                echo "<p>Model has allowedFields property: ✅ YES</p>";
                echo "<p>Equipment type fields in allowed list:</p>";
                echo "<ul>";
                echo "<li>equipment_type_id: " . (in_array('equipment_type_id', $allowedFields) ? "✅ YES" : "❌ NO") . "</li>";
                echo "<li>subtype_id: " . (in_array('subtype_id', $allowedFields) ? "✅ YES" : "❌ NO") . "</li>";
                echo "</ul>";
                break;
            }
        }
        
        if (!$allowedFieldsProperty) {
            echo "<p>Model allowedFields property: ❌ NOT FOUND</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error testing form simulation: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // ===== TEST 5: API ENDPOINTS TEST =====
    echo "<h2>Test 5: API Endpoints Test</h2>";
    
    // Test if the API endpoints for equipment types and subtypes are working
    echo "<h3>Testing API Endpoints:</h3>";
    
    // We can't make HTTP requests from this script, but we can test the core functionality
    try {
        if (class_exists('ApiController')) {
            echo "<p>ApiController class: ✅ EXISTS</p>";
            
            // Check if the intelligent-naming endpoint exists
            if (method_exists('ApiController', 'intelligentNaming')) {
                echo "<p>intelligentNaming method: ✅ EXISTS</p>";
            } else {
                echo "<p>intelligentNaming method: ❌ NOT FOUND</p>";
            }
        } else {
            echo "<p>ApiController class: ❌ NOT FOUND</p>";
        }
        
        // Test if AssetSubtypeManager exists
        if (class_exists('AssetSubtypeManager')) {
            echo "<p>AssetSubtypeManager class: ✅ EXISTS</p>";
        } else {
            echo "<p>AssetSubtypeManager class: ❌ NOT FOUND</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error testing API: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // ===== TEST 6: JAVASCRIPT FUNCTIONALITY TEST =====
    echo "<h2>Test 6: JavaScript Functionality Analysis</h2>";
    
    // Check if the JavaScript files exist and contain the required functions
    $jsFiles = [
        'assets/js/app.js',
        'assets/js/asset-standardizer.js'
    ];
    
    foreach ($jsFiles as $jsFile) {
        $fullPath = APP_ROOT . '/' . $jsFile;
        if (file_exists($fullPath)) {
            echo "<p><strong>$jsFile:</strong> ✅ EXISTS</p>";
            
            $jsContent = file_get_contents($fullPath);
            
            // Check for key functions
            $keyFunctions = [
                'equipment_type_id',
                'subtype_id', 
                'loadEquipmentTypes',
                'loadSubtypes'
            ];
            
            foreach ($keyFunctions as $func) {
                if (strpos($jsContent, $func) !== false) {
                    echo "<p>&nbsp;&nbsp;• Contains '$func': ✅ YES</p>";
                } else {
                    echo "<p>&nbsp;&nbsp;• Contains '$func': ❌ NO</p>";
                }
            }
        } else {
            echo "<p><strong>$jsFile:</strong> ❌ NOT FOUND</p>";
        }
    }
    
    // ===== TEST 7: GENERATE SUMMARY AND RECOMMENDATIONS =====
    echo "<h2>Test 7: Summary and Recommendations</h2>";
    
    $issues = [];
    $recommendations = [];
    
    if (!$hasEquipmentTypeId || !$hasSubtypeId) {
        $issues[] = "Database schema is missing equipment classification fields";
        $recommendations[] = "Run the asset_subtypes_system.sql migration to add equipment_type_id and subtype_id fields to the assets table";
    }
    
    if ($hasEquipmentTypeId && $hasSubtypeId && $assetStats['assets_with_equipment_type'] == 0) {
        $issues[] = "No assets have equipment classification data";
        $recommendations[] = "Test form submission to verify data is being captured and saved correctly";
    }
    
    echo "<h3>Issues Found:</h3>";
    if (count($issues) > 0) {
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li style='color: red;'>❌ " . htmlspecialchars($issue) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>✅ No critical issues detected in basic testing</p>";
    }
    
    echo "<h3>Recommendations:</h3>";
    if (count($recommendations) > 0) {
        echo "<ol>";
        foreach ($recommendations as $rec) {
            echo "<li>" . htmlspecialchars($rec) . "</li>";
        }
        echo "</ol>";
    } else {
        echo "<p>Proceed with more detailed testing of form submissions and JavaScript functionality</p>";
    }
    
    // ===== TEST 8: LIVE TEST FORM =====
    if ($hasEquipmentTypeId && $hasSubtypeId) {
        echo "<h2>Test 8: Live Test Form</h2>";
        echo "<p>Use this form to test asset creation with equipment classification:</p>";
        
        ?>
        <form action="?route=assets/create" method="POST" style="border: 1px solid #ccc; padding: 20px; max-width: 500px;">
            <h4>Test Asset Creation</h4>
            <p><label>Asset Name: <input type="text" name="name" value="Test Classification Asset" required></label></p>
            <p><label>Description: <textarea name="description">Test asset for equipment classification debugging</textarea></label></p>
            <p><label>Category ID: <input type="number" name="category_id" value="13" required></label></p>
            <p><label>Project ID: <input type="number" name="project_id" value="1" required></label></p>
            <p><label>Equipment Type ID: <input type="number" name="equipment_type_id" value="1"></label></p>
            <p><label>Subtype ID: <input type="number" name="subtype_id" value="1"></label></p>
            <p><label>Acquired Date: <input type="date" name="acquired_date" value="<?= date('Y-m-d') ?>" required></label></p>
            <p><label>Acquisition Cost: <input type="number" name="acquisition_cost" value="1000" step="0.01"></label></p>
            <p><input type="submit" value="Create Test Asset" style="background: #007cba; color: white; padding: 10px 20px;"></p>
            <p style="font-size: 12px; color: #666;">Note: This will actually create an asset in your database</p>
        </form>
        <?php
    }
    
    echo "<hr><p><strong>Debug test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; border: 1px solid red;'>";
    echo "<h2>Critical Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>