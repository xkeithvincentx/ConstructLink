<?php
/**
 * Test the exact API calls that the forms are making
 */

define('APP_ROOT', __DIR__);
require_once 'config/config.php';
require_once 'core/Database.php';

function logMessage($message) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
}

function simulateApiCall($route, $params = []) {
    logMessage("Testing API call: $route with params: " . json_encode($params));
    
    // Simulate setting up the request like the router would
    $_GET['route'] = $route;
    foreach ($params as $key => $value) {
        $_GET[$key] = $value;
    }
    
    // Get the output
    ob_start();
    
    try {
        // Load controller based on route
        if (strpos($route, 'api/equipment-types') !== false) {
            require_once APP_ROOT . '/controllers/ApiController.php';
            
            // Create a mock session for authentication
            $_SESSION = [
                'user_id' => 1,
                'role' => 'System Admin',
                'authenticated' => true,
                'last_activity' => time()
            ];
            
            $controller = new ApiController();
            
            // The route says api/equipment-types but the method checks for action
            // Let's set the action based on the route
            if (strpos($route, 'api/equipment-types') !== false) {
                $_GET['action'] = 'equipment-types';
            } elseif (strpos($route, 'api/subtypes') !== false) {
                $_GET['action'] = 'subtypes';
            }
            
            $controller->intelligentNaming();
        }
        
        $output = ob_get_contents();
        return $output;
        
    } catch (Exception $e) {
        $output = ob_get_contents();
        return json_encode(['error' => $e->getMessage()]);
    } finally {
        ob_end_clean();
        // Clean up $_GET
        unset($_GET['route']);
        foreach ($params as $key => $value) {
            unset($_GET[$key]);
        }
        unset($_GET['action']);
    }
}

function testFormApiCalls() {
    logMessage("=== TESTING FORM API CALLS ===");
    
    // Test equipment-types API call (like edit form makes)
    $response = simulateApiCall('api/equipment-types', ['category_id' => 1]);
    logMessage("Equipment Types API Response:");
    logMessage($response);
    
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        logMessage("✅ Equipment Types API working - " . count($data['data']) . " items returned");
        
        // Test subtypes with first equipment type
        if (!empty($data['data'])) {
            $firstEquipmentTypeId = $data['data'][0]['id'];
            
            $response = simulateApiCall('api/subtypes', ['equipment_type_id' => $firstEquipmentTypeId]);
            logMessage("Subtypes API Response:");
            logMessage($response);
            
            $subtypeData = json_decode($response, true);
            if ($subtypeData && isset($subtypeData['success']) && $subtypeData['success']) {
                logMessage("✅ Subtypes API working - " . count($subtypeData['data']) . " items returned");
            } else {
                logMessage("❌ Subtypes API failed: " . ($subtypeData['message'] ?? 'Unknown error'));
            }
        }
    } else {
        logMessage("❌ Equipment Types API failed: " . ($data['message'] ?? 'Unknown error'));
    }
}

function checkRouterMapping() {
    logMessage("\n=== CHECKING ROUTER MAPPING ===");
    
    // Check if the routes.php file maps these correctly
    $routes = require APP_ROOT . '/routes.php';
    
    $apiRoutes = [
        'api/equipment-types',
        'api/subtypes', 
        'api/intelligent-naming'
    ];
    
    foreach ($apiRoutes as $route) {
        if (isset($routes[$route])) {
            $routeConfig = $routes[$route];
            logMessage("✅ Route '$route' is mapped to {$routeConfig['controller']}::{$routeConfig['action']}");
        } else {
            logMessage("❌ Route '$route' is not found in routes.php");
        }
    }
}

function checkForMissingParameters() {
    logMessage("\n=== CHECKING API PARAMETER HANDLING ===");
    
    // Test the API with missing action parameter (like the forms are calling)
    $_GET['route'] = 'api/equipment-types';
    $_GET['category_id'] = 1;
    
    ob_start();
    try {
        $_SESSION = [
            'user_id' => 1,
            'role' => 'System Admin', 
            'authenticated' => true,
            'last_activity' => time()
        ];
        
        require_once APP_ROOT . '/controllers/ApiController.php';
        $controller = new ApiController();
        
        logMessage("Testing API call WITHOUT action parameter (like forms do)");
        $controller->intelligentNaming();
        
        $output = ob_get_contents();
        logMessage("Response without action: " . $output);
        
    } catch (Exception $e) {
        logMessage("Error without action parameter: " . $e->getMessage());
    } finally {
        ob_end_clean();
        unset($_GET['route'], $_GET['category_id']);
    }
}

function inspectApiController() {
    logMessage("\n=== INSPECTING API CONTROLLER ===");
    
    require_once APP_ROOT . '/controllers/ApiController.php';
    
    $reflection = new ReflectionClass('ApiController');
    $intelligentNamingMethod = $reflection->getMethod('intelligentNaming');
    
    logMessage("✅ intelligentNaming method exists");
    
    // Read the method source to understand the action handling
    $filename = $reflection->getFileName();
    $startLine = $intelligentNamingMethod->getStartLine();
    $endLine = $intelligentNamingMethod->getEndLine();
    
    $lines = file($filename);
    $methodSource = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
    
    if (strpos($methodSource, "\$action = \$_GET['action']") !== false) {
        logMessage("❌ Method expects 'action' parameter from \$_GET");
    }
    
    if (strpos($methodSource, "case 'equipment-types'") !== false) {
        logMessage("✅ Method handles 'equipment-types' action");
    }
    
    if (strpos($methodSource, "case 'subtypes'") !== false) {
        logMessage("✅ Method handles 'subtypes' action");
    }
}

// Run all tests
logMessage("🚀 Starting Form API Call Tests");
logMessage("====================================================");

checkRouterMapping();
inspectApiController();
checkForMissingParameters();
testFormApiCalls();

logMessage("\n====================================================");
logMessage("🏁 Form API Call Tests Completed");
?>