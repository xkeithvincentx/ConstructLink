<?php
/**
 * Simple API Test - Direct method calls
 */

// Set up environment
define('APP_ROOT', __DIR__);
require_once 'config/config.php';
require_once 'core/Database.php';

function logMessage($message) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
}

function testIntelligentNamerDirectly() {
    logMessage("=== TESTING INTELLIGENT NAMER DIRECTLY ===");
    
    try {
        require_once APP_ROOT . '/core/IntelligentAssetNamer.php';
        $namer = new IntelligentAssetNamer();
        
        logMessage("✅ IntelligentAssetNamer loaded successfully");
        
        // Test getting equipment types for category 1 (Tools)
        $equipmentTypes = $namer->getEquipmentTypesByCategory(1);
        logMessage("Equipment types for category 1: " . count($equipmentTypes) . " found");
        
        foreach ($equipmentTypes as $type) {
            logMessage("  - " . $type['name'] . " (ID: " . $type['id'] . ")");
        }
        
        if (!empty($equipmentTypes)) {
            $firstTypeId = $equipmentTypes[0]['id'];
            
            // Test getting subtypes
            $subtypes = $namer->getSubtypesByEquipmentType($firstTypeId);
            logMessage("Subtypes for equipment type {$firstTypeId}: " . count($subtypes) . " found");
            
            foreach ($subtypes as $subtype) {
                logMessage("  - " . $subtype['subtype_name'] . " (ID: " . $subtype['id'] . ")");
            }
            
            if (!empty($subtypes)) {
                $firstSubtypeId = $subtypes[0]['id'];
                
                // Test name generation
                $nameData = $namer->generateAssetName($firstTypeId, $firstSubtypeId, 'DeWalt', 'DW744');
                logMessage("Generated name data:");
                logMessage("  Suggested name: " . ($nameData['suggested_name'] ?? 'N/A'));
                logMessage("  Components: " . json_encode($nameData['components'] ?? []));
            }
        }
        
        // Test different categories
        for ($catId = 1; $catId <= 5; $catId++) {
            $types = $namer->getEquipmentTypesByCategory($catId);
            logMessage("Category $catId has " . count($types) . " equipment types");
        }
        
    } catch (Exception $e) {
        logMessage("❌ Error testing IntelligentNamer: " . $e->getMessage());
        logMessage("Stack trace: " . $e->getTraceAsString());
    }
}

function testExistingAssetClassification() {
    logMessage("\n=== TESTING EXISTING ASSET CLASSIFICATION ===");
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Check if any existing assets have the new classification fields populated
        $stmt = $conn->query("
            SELECT 
                ref, name,
                equipment_type_id, 
                subtype_id, 
                brand_id,
                category_id,
                maker_id
            FROM assets 
            WHERE equipment_type_id IS NOT NULL 
               OR subtype_id IS NOT NULL 
               OR brand_id IS NOT NULL
            LIMIT 10
        ");
        
        $classifiedAssets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($classifiedAssets)) {
            logMessage("❌ No existing assets have classification data");
            
            // Let's look at a few assets to see their current data
            $stmt = $conn->query("SELECT ref, name, category_id, maker_id FROM assets LIMIT 5");
            $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            logMessage("Sample existing assets:");
            foreach ($assets as $asset) {
                logMessage("  - {$asset['ref']}: {$asset['name']} (Cat: {$asset['category_id']}, Maker: {$asset['maker_id']})");
            }
        } else {
            logMessage("✅ Found " . count($classifiedAssets) . " assets with classification data");
            
            foreach ($classifiedAssets as $asset) {
                logMessage("  - {$asset['ref']}: {$asset['name']}");
                logMessage("    Equipment Type: " . ($asset['equipment_type_id'] ?? 'NULL'));
                logMessage("    Subtype: " . ($asset['subtype_id'] ?? 'NULL'));
                logMessage("    Brand: " . ($asset['brand_id'] ?? 'NULL'));
            }
        }
        
    } catch (Exception $e) {
        logMessage("❌ Error testing asset classification: " . $e->getMessage());
    }
}

function createTestAsset() {
    logMessage("\n=== TESTING ASSET CREATION ===");
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // First, let's see if we can create an asset with classification data
        $testRef = 'TEST_' . time();
        
        $sql = "INSERT INTO assets (
            ref, name, category_id, equipment_type_id, subtype_id, brand_id,
            project_id, acquired_date, status, quantity, available_quantity,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            $testRef,
            'Test DeWalt Drill',
            2, // Equipment category
            1, // Equipment type (assuming exists)
            1, // Subtype (assuming exists) 
            1, // Brand (DeWalt)
            1, // Project (Head Office)
            date('Y-m-d'),
            'available',
            1,
            1
        ]);
        
        if ($result) {
            $assetId = $conn->lastInsertId();
            logMessage("✅ Created test asset: $testRef (ID: $assetId)");
            
            // Now read it back to confirm the data was saved
            $stmt = $conn->prepare("
                SELECT a.*, et.name as equipment_type_name, es.subtype_name, ab.official_name as brand_name
                FROM assets a
                LEFT JOIN equipment_types et ON a.equipment_type_id = et.id
                LEFT JOIN equipment_subtypes es ON a.subtype_id = es.id  
                LEFT JOIN asset_brands ab ON a.brand_id = ab.id
                WHERE a.id = ?
            ");
            
            $stmt->execute([$assetId]);
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            logMessage("Test asset data:");
            logMessage("  Ref: " . $asset['ref']);
            logMessage("  Name: " . $asset['name']);
            logMessage("  Equipment Type: " . ($asset['equipment_type_name'] ?? 'NULL') . " (ID: " . ($asset['equipment_type_id'] ?? 'NULL') . ")");
            logMessage("  Subtype: " . ($asset['subtype_name'] ?? 'NULL') . " (ID: " . ($asset['subtype_id'] ?? 'NULL') . ")");
            logMessage("  Brand: " . ($asset['brand_name'] ?? 'NULL') . " (ID: " . ($asset['brand_id'] ?? 'NULL') . ")");
            
            // Clean up test asset
            $conn->prepare("DELETE FROM assets WHERE id = ?")->execute([$assetId]);
            logMessage("✅ Test asset cleaned up");
            
        } else {
            logMessage("❌ Failed to create test asset");
        }
        
    } catch (Exception $e) {
        logMessage("❌ Error testing asset creation: " . $e->getMessage());
    }
}

function testLegacyAssetForm() {
    logMessage("\n=== TESTING LEGACY ASSET FORM DATA REQUIREMENTS ===");
    
    // Check what the legacy form needs and what's available
    $viewFile = APP_ROOT . '/views/assets/legacy_create.php';
    
    if (file_exists($viewFile)) {
        logMessage("✅ Legacy create form exists");
        
        // Read the form and look for dropdown population code
        $content = file_get_contents($viewFile);
        
        if (strpos($content, 'equipment_types') !== false) {
            logMessage("✅ Form references equipment_types");
        }
        
        if (strpos($content, 'subtypes') !== false) {
            logMessage("✅ Form references subtypes");
        }
        
        if (strpos($content, 'asset_brands') !== false || strpos($content, 'brands') !== false) {
            logMessage("✅ Form references brands");
        }
        
        // Look for AJAX calls or API endpoints
        if (strpos($content, 'api/equipment-types') !== false) {
            logMessage("✅ Form calls equipment-types API");
        }
        
        if (strpos($content, 'api/subtypes') !== false) {
            logMessage("✅ Form calls subtypes API");
        }
        
    } else {
        logMessage("❌ Legacy create form not found");
    }
}

// Run all tests
logMessage("🚀 Starting Simple API and Asset Management Tests");
logMessage("====================================================");

testIntelligentNamerDirectly();
testExistingAssetClassification();
createTestAsset();
testLegacyAssetForm();

logMessage("\n====================================================");
logMessage("🏁 Testing Completed");
?>