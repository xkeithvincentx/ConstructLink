<?php
/**
 * API Endpoints Test Script
 * Tests the asset management API endpoints properly with authentication and routing
 */

// Set up environment
define('APP_ROOT', __DIR__);
require_once 'config/config.php';
require_once 'core/Database.php';
require_once 'core/Auth.php';

function logMessage($message) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
}

function testWithAuth($url, $postData = null) {
    // Start output buffering to catch any output from the API endpoints
    ob_start();
    
    try {
        // Simulate being logged in by setting up a session
        session_start();
        $_SESSION['user_id'] = 1; // Admin user
        $_SESSION['role'] = 'System Admin';
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time();
        
        // Store original $_GET and $_POST
        $originalGet = $_GET ?? [];
        $originalPost = $_POST ?? [];
        
        // Parse URL to get route and parameters
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            $_GET = $queryParams;
        }
        
        if ($postData) {
            $_POST = $postData;
            $_SERVER['REQUEST_METHOD'] = 'POST';
        } else {
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }
        
        // Set the route parameter for the router
        if (strpos($path, 'api/equipment-types') !== false) {
            $_GET['route'] = 'api/equipment-types';
            $_GET['action'] = 'equipment-types';
        } elseif (strpos($path, 'api/subtypes') !== false) {
            $_GET['route'] = 'api/subtypes';
            $_GET['action'] = 'subtypes';
        } elseif (strpos($path, 'api/intelligent-naming') !== false) {
            $_GET['route'] = 'api/intelligent-naming';
            $_GET['action'] = 'generate-name';
        }
        
        // Initialize the Auth system
        $auth = new Auth();
        
        // Load the ApiController
        require_once APP_ROOT . '/controllers/ApiController.php';
        $apiController = new ApiController();
        
        // Call the appropriate method
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'equipment-types':
                case 'subtypes':
                case 'generate-name':
                    $apiController->intelligentNaming();
                    break;
            }
        }
        
        // Get the output
        $output = ob_get_contents();
        
        // Restore original values
        $_GET = $originalGet;
        $_POST = $originalPost;
        unset($_SERVER['REQUEST_METHOD']);
        
        return $output;
        
    } catch (Exception $e) {
        $output = ob_get_contents();
        logMessage("Exception caught: " . $e->getMessage());
        return json_encode(['error' => $e->getMessage(), 'output' => $output]);
    } finally {
        ob_end_clean();
    }
}

function testApiEndpoints() {
    logMessage("=== TESTING API ENDPOINTS WITH AUTHENTICATION ===");
    
    // Test equipment types API
    logMessage("Testing Equipment Types API...");
    $response = testWithAuth('api/equipment-types?category_id=1');
    logMessage("Equipment Types Response: " . $response);
    
    if ($response && $response !== '') {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            logMessage("✅ Equipment Types API working - returned " . count($data['data'] ?? []) . " items");
        } else {
            logMessage("❌ Equipment Types API failed: " . ($data['message'] ?? 'Unknown error'));
        }
    } else {
        logMessage("❌ Equipment Types API returned empty response");
    }
    
    // Test subtypes API
    logMessage("\nTesting Subtypes API...");
    $response = testWithAuth('api/subtypes?equipment_type_id=1');
    logMessage("Subtypes Response: " . $response);
    
    if ($response && $response !== '') {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            logMessage("✅ Subtypes API working - returned " . count($data['data'] ?? []) . " items");
        } else {
            logMessage("❌ Subtypes API failed: " . ($data['message'] ?? 'Unknown error'));
        }
    } else {
        logMessage("❌ Subtypes API returned empty response");
    }
    
    // Test intelligent naming API
    logMessage("\nTesting Intelligent Naming API...");
    $response = testWithAuth('api/intelligent-naming?equipment_type_id=1&subtype_id=1&brand=DeWalt&model=DW744');
    logMessage("Intelligent Naming Response: " . $response);
    
    if ($response && $response !== '') {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            logMessage("✅ Intelligent Naming API working");
            if (isset($data['data']['suggested_name'])) {
                logMessage("   Generated name: " . $data['data']['suggested_name']);
            }
        } else {
            logMessage("❌ Intelligent Naming API failed: " . ($data['message'] ?? 'Unknown error'));
        }
    } else {
        logMessage("❌ Intelligent Naming API returned empty response");
    }
}

function testDatabaseDirectly() {
    logMessage("\n=== TESTING DATABASE QUERIES DIRECTLY ===");
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Test equipment types query
        $stmt = $conn->prepare("SELECT * FROM equipment_types WHERE category_id = ? AND is_active = 1 LIMIT 5");
        $stmt->execute([1]);
        $equipmentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logMessage("✅ Equipment Types query returned " . count($equipmentTypes) . " results");
        foreach ($equipmentTypes as $type) {
            logMessage("   - " . $type['name'] . " (ID: " . $type['id'] . ")");
        }
        
        if (!empty($equipmentTypes)) {
            $firstTypeId = $equipmentTypes[0]['id'];
            
            // Test subtypes query
            $stmt = $conn->prepare("SELECT * FROM equipment_subtypes WHERE equipment_type_id = ? AND is_active = 1 LIMIT 5");
            $stmt->execute([$firstTypeId]);
            $subtypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            logMessage("✅ Subtypes query returned " . count($subtypes) . " results for equipment type " . $firstTypeId);
            foreach ($subtypes as $subtype) {
                logMessage("   - " . $subtype['subtype_name'] . " (ID: " . $subtype['id'] . ")");
            }
        }
        
        // Test brands query
        $stmt = $conn->prepare("SELECT * FROM asset_brands WHERE is_active = 1 LIMIT 5");
        $stmt->execute();
        $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logMessage("✅ Brands query returned " . count($brands) . " results");
        foreach ($brands as $brand) {
            logMessage("   - " . $brand['official_name'] . " (ID: " . $brand['id'] . ")");
        }
        
    } catch (Exception $e) {
        logMessage("❌ Database test error: " . $e->getMessage());
    }
}

function checkCoreClasses() {
    logMessage("\n=== CHECKING CORE CLASSES ===");
    
    // Check if IntelligentAssetNamer exists
    if (file_exists(APP_ROOT . '/core/IntelligentAssetNamer.php')) {
        logMessage("✅ IntelligentAssetNamer.php exists");
        
        require_once APP_ROOT . '/core/IntelligentAssetNamer.php';
        
        if (class_exists('IntelligentAssetNamer')) {
            logMessage("✅ IntelligentAssetNamer class can be loaded");
            
            try {
                $namer = new IntelligentAssetNamer();
                logMessage("✅ IntelligentAssetNamer can be instantiated");
                
                // Test method existence
                if (method_exists($namer, 'getEquipmentTypesByCategory')) {
                    logMessage("✅ Method getEquipmentTypesByCategory exists");
                } else {
                    logMessage("❌ Method getEquipmentTypesByCategory missing");
                }
                
                if (method_exists($namer, 'getSubtypesByEquipmentType')) {
                    logMessage("✅ Method getSubtypesByEquipmentType exists");
                } else {
                    logMessage("❌ Method getSubtypesByEquipmentType missing");
                }
                
            } catch (Exception $e) {
                logMessage("❌ Error instantiating IntelligentAssetNamer: " . $e->getMessage());
            }
        } else {
            logMessage("❌ IntelligentAssetNamer class not found after require");
        }
    } else {
        logMessage("❌ IntelligentAssetNamer.php file not found");
    }
}

// Run all tests
logMessage("🚀 Starting Comprehensive API Testing");
logMessage("====================================================");

checkCoreClasses();
testDatabaseDirectly();
testApiEndpoints();

logMessage("\n====================================================");
logMessage("🏁 API Testing Completed");
?>