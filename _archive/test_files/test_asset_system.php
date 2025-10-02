<?php
/**
 * Asset Management System Test Script
 * Tests database tables and API endpoints for equipment types, subtypes, and brands
 */

// Set up environment
define('APP_ROOT', __DIR__);
require_once 'config/config.php';
require_once 'core/Database.php';

function logMessage($message) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
}

function testDatabaseTables() {
    logMessage("=== TESTING DATABASE TABLES ===");
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Test database connection
    if (!$db->testConnection()) {
        logMessage("❌ Database connection failed!");
        return false;
    }
    logMessage("✅ Database connection successful");
    
    // Get all tables
    $tables = $db->getAllTables();
    logMessage("📋 Found " . count($tables) . " tables in database");
    
    // Check for equipment-related tables
    $equipmentTables = ['equipment_types', 'equipment_subtypes', 'asset_subtypes', 'asset_brands'];
    
    foreach ($equipmentTables as $tableName) {
        if (in_array($tableName, $tables)) {
            logMessage("✅ Table '$tableName' exists");
            
            // Count records
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                logMessage("   📊 Records in '$tableName': " . $result['count']);
                
                // Show sample data
                if ($result['count'] > 0) {
                    $stmt = $conn->query("SELECT * FROM `$tableName` LIMIT 5");
                    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    logMessage("   📝 Sample data:");
                    foreach ($samples as $sample) {
                        logMessage("      " . json_encode($sample));
                    }
                }
            } catch (PDOException $e) {
                logMessage("   ❌ Error querying '$tableName': " . $e->getMessage());
            }
        } else {
            logMessage("❌ Table '$tableName' does not exist");
        }
    }
    
    // Check standard tables
    $standardTables = ['categories', 'assets', 'makers', 'vendors'];
    foreach ($standardTables as $tableName) {
        if (in_array($tableName, $tables)) {
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                logMessage("✅ '$tableName' table has " . $result['count'] . " records");
            } catch (PDOException $e) {
                logMessage("❌ Error querying '$tableName': " . $e->getMessage());
            }
        } else {
            logMessage("❌ Standard table '$tableName' missing");
        }
    }
    
    return true;
}

function testAPIEndpoints() {
    logMessage("\n=== TESTING API ENDPOINTS ===");
    
    $baseUrl = 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']);
    
    // Test equipment-types endpoint
    $endpoints = [
        '/api/equipment-types' => 'Equipment Types API',
        '/api/subtypes' => 'Subtypes API', 
        '/api/intelligent-naming' => 'Intelligent Naming API',
        '/api/asset-subtypes.php' => 'Asset Subtypes API'
    ];
    
    foreach ($endpoints as $endpoint => $description) {
        logMessage("Testing $description: $endpoint");
        
        $fullUrl = $baseUrl . $endpoint;
        
        // Test without parameters first
        $response = @file_get_contents($fullUrl);
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data !== null) {
                logMessage("   ✅ Endpoint returns valid JSON");
                logMessage("   📊 Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : ''));
            } else {
                logMessage("   ⚠️  Endpoint returns non-JSON: " . substr($response, 0, 100));
            }
        } else {
            logMessage("   ❌ Endpoint not accessible or returns error");
        }
        
        // Test with sample parameters
        if (strpos($endpoint, 'equipment-types') !== false) {
            $testUrl = $fullUrl . '?category_id=1';
            logMessage("   Testing with category_id=1: $testUrl");
            $response = @file_get_contents($testUrl);
            if ($response !== false) {
                logMessage("   ✅ Parameterized request works");
                logMessage("   📊 Response: " . substr($response, 0, 200));
            } else {
                logMessage("   ❌ Parameterized request failed");
            }
        }
        
        if (strpos($endpoint, 'subtypes') !== false) {
            $testUrl = $fullUrl . '?equipment_type_id=1';
            logMessage("   Testing with equipment_type_id=1: $testUrl");
            $response = @file_get_contents($testUrl);
            if ($response !== false) {
                logMessage("   ✅ Parameterized request works");
                logMessage("   📊 Response: " . substr($response, 0, 200));
            } else {
                logMessage("   ❌ Parameterized request failed");
            }
        }
    }
}

function testExistingAssets() {
    logMessage("\n=== TESTING EXISTING ASSETS ===");
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Get sample assets
        $stmt = $conn->query("SELECT * FROM assets LIMIT 10");
        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($assets)) {
            logMessage("❌ No assets found in database");
            return;
        }
        
        logMessage("✅ Found " . count($assets) . " sample assets");
        
        foreach ($assets as $asset) {
            logMessage("📋 Asset: " . $asset['ref'] . " - " . $asset['name']);
            
            // Check for new classification fields
            $hasEquipmentType = isset($asset['equipment_type_id']) && !empty($asset['equipment_type_id']);
            $hasSubtype = isset($asset['subtype_id']) && !empty($asset['subtype_id']);
            $hasBrand = isset($asset['brand_id']) && !empty($asset['brand_id']);
            
            logMessage("   Equipment Type ID: " . ($hasEquipmentType ? $asset['equipment_type_id'] : 'NULL'));
            logMessage("   Subtype ID: " . ($hasSubtype ? $asset['subtype_id'] : 'NULL'));  
            logMessage("   Brand ID: " . ($hasBrand ? $asset['brand_id'] : 'NULL'));
            
            // Check traditional fields
            logMessage("   Category ID: " . ($asset['category_id'] ?? 'NULL'));
            logMessage("   Maker ID: " . ($asset['maker_id'] ?? 'NULL'));
        }
        
    } catch (PDOException $e) {
        logMessage("❌ Error querying assets: " . $e->getMessage());
    }
}

function checkAssetTableSchema() {
    logMessage("\n=== CHECKING ASSETS TABLE SCHEMA ===");
    
    $db = Database::getInstance();
    
    try {
        $schema = $db->getTableSchema('assets');
        
        logMessage("📋 Assets table columns:");
        $equipmentFields = ['equipment_type_id', 'subtype_id', 'brand_id'];
        
        foreach ($schema as $column) {
            $fieldName = $column['Field'];
            $isEquipmentField = in_array($fieldName, $equipmentFields);
            $marker = $isEquipmentField ? '🎯' : '  ';
            logMessage("$marker $fieldName: {$column['Type']} ({$column['Null']}, {$column['Key']})");
        }
        
    } catch (PDOException $e) {
        logMessage("❌ Error getting schema: " . $e->getMessage());
    }
}

function testIntelligentNaming() {
    logMessage("\n=== TESTING INTELLIGENT NAMING ===");
    
    // Test the intelligent naming logic
    $testData = [
        'category_id' => 1,
        'equipment_type_id' => 1,
        'subtype_id' => 1,
        'brand_id' => 1,
        'model' => 'Test Model'
    ];
    
    $baseUrl = 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']);
    $url = $baseUrl . '/api/intelligent-naming.php';
    
    // Create POST data
    $postData = http_build_query($testData);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postData
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        logMessage("✅ Intelligent naming API responds");
        logMessage("📊 Response: " . $response);
        
        $data = json_decode($response, true);
        if ($data && isset($data['suggested_name'])) {
            logMessage("✅ Suggested name generated: " . $data['suggested_name']);
        }
    } else {
        logMessage("❌ Intelligent naming API failed");
    }
}

// Run all tests
logMessage("🚀 Starting Asset Management System Tests");
logMessage("================================================");

testDatabaseTables();
checkAssetTableSchema();
testExistingAssets();
testAPIEndpoints();
testIntelligentNaming();

logMessage("\n================================================");
logMessage("🏁 Asset Management System Tests Completed");
?>