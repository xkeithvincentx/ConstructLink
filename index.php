<?php
/**
 * ConstructLink™ by Ranoa Digital Solutions
 * Asset and Inventory Management System for V CUTAMORA CONSTRUCTION INC.
 * 
 * Main application entry point
 */

// Start session and set error reporting
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define application constants
define('APP_ROOT', __DIR__);
define('APP_VERSION', '1.0.0');
define('APP_NAME', 'ConstructLink™');

// Include Composer autoloader for third-party packages
require_once APP_ROOT . '/vendor/autoload.php';

// Include configuration and autoloader
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Autoloader.php';
require_once APP_ROOT . '/core/helpers.php';

// Initialize autoloader
$autoloader = new Autoloader();
$autoloader->register();

// Load core classes that are needed immediately
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/CSRFProtection.php';
require_once APP_ROOT . '/core/Validator.php';
require_once APP_ROOT . '/core/RateLimit.php';
require_once APP_ROOT . '/core/Auth.php';
require_once APP_ROOT . '/core/Router.php';
require_once APP_ROOT . '/core/QRTagGenerator.php';
require_once APP_ROOT . '/core/SecureLink.php';

// Load base model
require_once APP_ROOT . '/models/BaseModel.php';

// Load all model files explicitly to ensure they're available
$modelFiles = [
    'UserModel.php',
    'AssetModel.php',
    'CategoryModel.php',
    'ProjectModel.php',
    'VendorModel.php',
    'VendorIntelligenceModel.php',
    'VendorProductModel.php',
    'MakerModel.php',
    'ClientModel.php',
    'RoleModel.php',
    'WithdrawalModel.php',
    'TransferModel.php',
    'MaintenanceModel.php',
    'IncidentModel.php',
    'BorrowedToolModel.php',
    'ProcurementModel.php',
    'ProcurementOrderModel.php',
    'ProcurementItemModel.php',
    'AtcCodeModel.php',
    'Bir2307Model.php',
    'ReportModel.php',
    'SystemSettingsModel.php',
    'SystemUpgradeManager.php',
    'BackupManager.php',
    'UpdateManager.php',
    'ModuleManager.php',
    'RequestModel.php'
];

foreach ($modelFiles as $modelFile) {
    $modelPath = APP_ROOT . '/models/' . $modelFile;
    if (file_exists($modelPath)) {
        require_once $modelPath;
    }
}

// Load database schema after Database class is loaded
require_once APP_ROOT . '/config/database.php';

// Include routes
require_once APP_ROOT . '/routes.php';

// Initialize application
try {
    $router = new Router();
    $router->handleRequest();
} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    
    // Show user-friendly error page
    http_response_code(500);
    include APP_ROOT . '/views/errors/500.php';
}
?>
