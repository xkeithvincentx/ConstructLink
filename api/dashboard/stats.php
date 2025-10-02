<?php
/**
 * ConstructLink™ Dashboard Statistics API
 * Provides real-time dashboard statistics
 */

// Define APP_ROOT correctly for API files
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(__FILE__)));
}

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and core classes
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Autoloader.php';
require_once APP_ROOT . '/core/helpers.php';

// Initialize autoloader
$autoloader = new Autoloader();
$autoloader->register();

// Load required classes
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/Auth.php';
require_once APP_ROOT . '/models/BaseModel.php';
require_once APP_ROOT . '/models/DashboardModel.php';
require_once APP_ROOT . '/models/AssetModel.php';
require_once APP_ROOT . '/models/WithdrawalModel.php';
require_once APP_ROOT . '/models/MaintenanceModel.php';
require_once APP_ROOT . '/models/IncidentModel.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Check authentication
    $auth = Auth::getInstance();
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Get user information for role-based stats
    $user = $auth->getCurrentUser();
    $userRole = $user['role_name'] ?? null;
    $userId = $user['id'] ?? null;
    
    // Use the new DashboardModel for comprehensive statistics
    $dashboardModel = new DashboardModel();
    $stats = $dashboardModel->getDashboardStats($userRole, $userId);
    
    // Format response for API consistency
    $response = [
        'success' => true,
        'data' => $stats,
        'user' => [
            'role' => $userRole,
            'id' => $userId,
            'name' => $user['full_name'] ?? 'Unknown'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Dashboard stats API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load dashboard statistics',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
