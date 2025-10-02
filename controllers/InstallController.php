<?php
/**
 * ConstructLink™ Installation Controller
 * Handles system installation and database setup
 */

class InstallController {
    
    /**
     * Display installation page
     */
    public function index() {
        $errors = [];
        $messages = [];
        $step = $_GET['step'] ?? 1;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'test_database':
                    $result = $this->testDatabaseConnection();
                    if ($result['success']) {
                        $messages[] = $result['message'];
                        $step = 2;
                    } else {
                        $errors[] = $result['message'];
                    }
                    break;
                    
                case 'install_database':
                    $result = $this->installDatabase();
                    if ($result['success']) {
                        $messages[] = $result['message'];
                        $step = 3;
                    } else {
                        $errors[] = $result['message'];
                    }
                    break;
                    
                case 'complete_installation':
                    $result = $this->completeInstallation();
                    if ($result['success']) {
                        header('Location: ?route=login&message=installation_complete');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                    break;
            }
        }
        
        $pageTitle = 'Install ConstructLink™';
        include APP_ROOT . '/views/install/index.php';
    }
    
    /**
     * Test database connection
     */
    private function testDatabaseConnection() {
        try {
            $db = Database::getInstance();
            $connection = $db->getConnection();
            
            // Test the connection by running a simple query
            $stmt = $connection->query("SELECT 1");
            if ($stmt) {
                return [
                    'success' => true,
                    'message' => 'Database connection successful!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Database connection failed. Please check your configuration.'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Install database schema using DatabaseSchema class (same as setup.php)
     */
    private function installDatabase() {
        try {
            // Load the DatabaseSchema class
            require_once APP_ROOT . '/config/database.php';
            
            // Check if DatabaseSchema class exists
            if (!class_exists('DatabaseSchema')) {
                return [
                    'success' => false,
                    'message' => 'DatabaseSchema class not found. Please check config/database.php file.'
                ];
            }
            
            // Use the same DatabaseSchema class that setup.php uses
            $schema = new DatabaseSchema();
            
            // Capture output for feedback
            ob_start();
            $schema->createTables();
            $output = ob_get_clean();
            
            return [
                'success' => true,
                'message' => 'Database schema installed successfully! All tables and default data have been created.',
                'details' => $output
            ];
            
        } catch (Exception $e) {
            // If it's a permission error, provide helpful message
            if (strpos($e->getMessage(), 'Access denied') !== false || strpos($e->getMessage(), '1044') !== false) {
                return [
                    'success' => false,
                    'message' => 'Database user lacks CREATE privileges. Please contact your hosting provider to grant CREATE, ALTER, and DROP privileges to your database user, or use the manual installation method below.',
                    'manual_install' => true
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Database installation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Complete installation
     */
    private function completeInstallation() {
        try {
            // Load InstallModel for better organization
            require_once APP_ROOT . '/models/InstallModel.php';
            $installModel = new InstallModel();
            
            // Verify all tables exist
            $tableCheck = $installModel->checkRequiredTables();
            
            if (!$tableCheck['all_exist']) {
                return [
                    'success' => false,
                    'message' => 'Missing required tables: ' . implode(', ', $tableCheck['missing']) . '. Please reinstall the database.'
                ];
            }
            
            // Verify default admin user exists
            $adminCheck = $installModel->checkDefaultAdmin();
            if (!$adminCheck['exists']) {
                return [
                    'success' => false,
                    'message' => 'Default admin user not found. Please check the database installation.'
                ];
            }
            
            // Create installation marker file
            if (!$installModel->createInstallationMarker()) {
                return [
                    'success' => false,
                    'message' => 'Failed to create installation marker file.'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Installation completed successfully! Found ' . count($tableCheck['existing']) . ' tables with admin user ready.'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Installation completion failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if system is already installed
     */
    public static function isInstalled() {
        try {
            // Check installation marker file first
            $installFile = APP_ROOT . '/.installed';
            if (!file_exists($installFile)) {
                return false;
            }
            
            // Load InstallModel to verify installation
            require_once APP_ROOT . '/models/InstallModel.php';
            $installModel = new InstallModel();
            
            // Check if key tables exist and admin user is present
            $tableCheck = $installModel->checkRequiredTables();
            $adminCheck = $installModel->checkDefaultAdmin();
            
            return $tableCheck['all_exist'] && $adminCheck['exists'];
            
        } catch (Exception $e) {
            error_log("Installation check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get system requirements check
     */
    public function checkSystemRequirements() {
        $requirements = [
            'PHP Version >= 8.0' => [
                'status' => version_compare(PHP_VERSION, '8.0.0', '>='),
                'current' => PHP_VERSION
            ],
            'PDO Extension' => [
                'status' => extension_loaded('pdo'),
                'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled'
            ],
            'PDO MySQL Extension' => [
                'status' => extension_loaded('pdo_mysql'),
                'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled'
            ],
            'mbstring Extension' => [
                'status' => extension_loaded('mbstring'),
                'current' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled'
            ],
            'GD Extension' => [
                'status' => extension_loaded('gd'),
                'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled'
            ],
            'OpenSSL Extension' => [
                'status' => extension_loaded('openssl'),
                'current' => extension_loaded('openssl') ? 'Enabled' : 'Disabled'
            ],
            'cURL Extension' => [
                'status' => extension_loaded('curl'),
                'current' => extension_loaded('curl') ? 'Enabled' : 'Disabled'
            ],
            'Logs Directory Writable' => [
                'status' => is_writable(APP_ROOT . '/logs'),
                'current' => is_writable(APP_ROOT . '/logs') ? 'Writable' : 'Not Writable'
            ],
            'Uploads Directory Writable' => [
                'status' => is_writable(APP_ROOT . '/uploads'),
                'current' => is_writable(APP_ROOT . '/uploads') ? 'Writable' : 'Not Writable'
            ],
            'QR Directory Writable' => [
                'status' => is_writable(APP_ROOT . '/assets/qr'),
                'current' => is_writable(APP_ROOT . '/assets/qr') ? 'Writable' : 'Not Writable'
            ]
        ];
        
        return $requirements;
    }
}
?>
