<?php
/**
 * ConstructLink™ Admin Controller
 * Handles system administration and settings management
 */

class AdminController {
    private $auth;
    private $systemSettingsModel;
    private $upgradeManager;
    private $backupManager;
    private $moduleManager;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->systemSettingsModel = new SystemSettingsModel();
        $this->upgradeManager = new SystemUpgradeManager();
        
        // Initialize BackupManager if available
        if (class_exists('BackupManager')) {
            $this->backupManager = new BackupManager();
        }
        
        // Initialize ModuleManager if available
        if (class_exists('ModuleManager')) {
            $this->moduleManager = new ModuleManager();
        }
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
        
        // Check if user is System Admin
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            exit;
        }
    }
    
    /**
     * Display admin dashboard
     */
    public function index() {
        try {
            // Get system statistics
            $systemStats = $this->systemSettingsModel->getSystemStats();
            
            // Get all system settings
            $settings = $this->systemSettingsModel->getAllSettings();
            
            // Get recent activity logs
            $recentActivity = $this->getRecentActivity();
            
            // Get system health status
            $systemHealth = $this->getSystemHealth();
            
            $pageTitle = 'System Administration - ConstructLink™';
            $pageHeader = 'System Administration';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'System Admin', 'url' => '?route=admin']
            ];
            
            include APP_ROOT . '/views/admin/index.php';
            
        } catch (Exception $e) {
            error_log("Admin dashboard error: " . $e->getMessage());
            $error = 'Failed to load admin dashboard';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display system settings
     */
    public function settings() {
        $errors = [];
        $messages = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            try {
                $currentUser = $this->auth->getCurrentUser();
                $settings = [];
                
                // Process form data
                foreach ($_POST as $key => $value) {
                    if ($key !== 'csrf_token' && $key !== 'action') {
                        $settings[$key] = [
                            'value' => Validator::sanitize($value),
                            'is_public' => isset($_POST[$key . '_public']) ? 1 : 0
                        ];
                    }
                }
                
                if (!empty($settings)) {
                    $result = $this->systemSettingsModel->updateMultipleSettings($settings, $currentUser['id']);
                    
                    if ($result['success']) {
                        $messages[] = $result['message'];
                    } else {
                        $errors[] = $result['message'];
                        if (isset($result['errors'])) {
                            $errors = array_merge($errors, $result['errors']);
                        }
                    }
                }
                
            } catch (Exception $e) {
                error_log("Settings update error: " . $e->getMessage());
                $errors[] = 'Failed to update settings';
            }
        }
        
        try {
            // Get all settings
            $settings = $this->systemSettingsModel->getAllSettings();
            
            $pageTitle = 'System Settings - ConstructLink™';
            $pageHeader = 'System Settings';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'System Admin', 'url' => '?route=admin'],
                ['title' => 'Settings', 'url' => '?route=admin/settings']
            ];
            
            include APP_ROOT . '/views/admin/settings.php';
            
        } catch (Exception $e) {
            error_log("Settings page error: " . $e->getMessage());
            $error = 'Failed to load settings';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display system logs
     */
    public function logs() {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 50;
        $logType = $_GET['type'] ?? 'activity';
        
        try {
            if ($logType === 'audit') {
                $logs = $this->getAuditLogs($page, $perPage);
            } else {
                $logs = $this->getActivityLogs($page, $perPage);
            }
            
            $pageTitle = 'System Logs - ConstructLink™';
            $pageHeader = 'System Logs';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'System Admin', 'url' => '?route=admin'],
                ['title' => 'Logs', 'url' => '?route=admin/logs']
            ];
            
            include APP_ROOT . '/views/admin/logs.php';
            
        } catch (Exception $e) {
            error_log("Logs page error: " . $e->getMessage());
            $error = 'Failed to load logs';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display system maintenance
     */
    public function maintenance() {
        $errors = [];
        $messages = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            $action = $_POST['action'] ?? '';
            
            try {
                switch ($action) {
                    case 'enable_maintenance':
                        $result = $this->enableMaintenanceMode();
                        break;
                    case 'disable_maintenance':
                        $result = $this->disableMaintenanceMode();
                        break;
                    case 'clear_cache':
                        $result = $this->clearCache();
                        break;
                    case 'optimize_database':
                        $result = $this->optimizeDatabase();
                        break;
                    case 'backup_database':
                        $result = $this->backupDatabase();
                        break;
                    default:
                        $result = ['success' => false, 'message' => 'Invalid action'];
                }
                
                if ($result['success']) {
                    $messages[] = $result['message'];
                } else {
                    $errors[] = $result['message'];
                }
                
            } catch (Exception $e) {
                error_log("Maintenance action error: " . $e->getMessage());
                $errors[] = 'Failed to perform maintenance action';
            }
        }
        
        try {
            // Get maintenance status
            $maintenanceMode = $this->systemSettingsModel->getSettingValue('maintenance_mode', '0') === '1';
            
            // Get system health
            $systemHealth = $this->getSystemHealth();
            
            $pageTitle = 'System Maintenance - ConstructLink™';
            $pageHeader = 'System Maintenance';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'System Admin', 'url' => '?route=admin'],
                ['title' => 'Maintenance', 'url' => '?route=admin/maintenance']
            ];
            
            include APP_ROOT . '/views/admin/maintenance.php';
            
        } catch (Exception $e) {
            error_log("Maintenance page error: " . $e->getMessage());
            $error = 'Failed to load maintenance page';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display user management
     */
    public function users() {
        try {
            // Get user statistics
            $userModel = new UserModel();
            $users = $userModel->getAllUsersWithRoles();
            
            // Get role statistics
            $roleModel = new RoleModel();
            $roles = $roleModel->findAll();
            
            $pageTitle = 'User Management - ConstructLink™';
            $pageHeader = 'User Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'System Admin', 'url' => '?route=admin'],
                ['title' => 'Users', 'url' => '?route=admin/users']
            ];
            
            include APP_ROOT . '/views/admin/users.php';
            
        } catch (Exception $e) {
            error_log("Admin users page error: " . $e->getMessage());
            $error = 'Failed to load user management';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Reset settings to defaults
     */
    public function resetSettings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        try {
            $currentUser = $this->auth->getCurrentUser();
            $result = $this->systemSettingsModel->resetToDefaults($currentUser['id']);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Reset settings error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to reset settings']);
        }
    }

    /**
     * Display system upgrade page
     */
    public function upgrades() {
        try {
            // Get current version and available upgrades
            $currentVersion = $this->upgradeManager->getCurrentVersion();
            $availableVersions = $this->upgradeManager->getAvailableVersions();
            $pendingMigrations = $this->upgradeManager->getPendingMigrations();
            $upgradeHistory = $this->upgradeManager->getUpgradeHistory(10);
            
            // Check system integrity
            $systemIntegrity = $this->upgradeManager->validateSystemIntegrity();
            
            $pageTitle = 'System Upgrades - ConstructLink™';
            $pageHeader = 'System Upgrades';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'System Admin', 'url' => '?route=admin'],
                ['title' => 'Upgrades', 'url' => '?route=admin/upgrades']
            ];
            
            include APP_ROOT . '/views/admin/upgrades.php';
            
        } catch (Exception $e) {
            error_log("Upgrades page error: " . $e->getMessage());
            $error = 'Failed to load upgrades page';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Execute system upgrade
     */
    public function executeUpgrade() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $targetVersion = $input['version'] ?? '';
            
            if (empty($targetVersion)) {
                echo json_encode(['success' => false, 'message' => 'Target version is required']);
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            $result = $this->upgradeManager->upgradeToVersion($targetVersion, $currentUser['id']);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Execute upgrade error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Upgrade execution failed']);
        }
    }

    /**
     * Execute pending migrations
     */
    public function executeMigrations() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        try {
            $currentUser = $this->auth->getCurrentUser();
            $result = $this->upgradeManager->executePendingMigrations($currentUser['id']);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Execute migrations error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Migration execution failed']);
        }
    }

    /**
     * Check system integrity
     */
    public function checkIntegrity() {
        try {
            $result = $this->upgradeManager->validateSystemIntegrity();
            echo json_encode([
                'success' => true,
                'integrity' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("Check integrity error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Integrity check failed']);
        }
    }

    /**
     * Get system health for real-time monitoring (API endpoint)
     */
    public function getSystemHealthAPI() {
        try {
            $health = $this->getSystemHealth();
            echo json_encode([
                'success' => true,
                'health' => $health
            ]);
            
        } catch (Exception $e) {
            error_log("Get system health API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get system health']);
        }
    }

    /**
     * Display backup management page
     */
    public function backups() {
        try {
            $backups = [];
            $schedules = [];
            
            if ($this->backupManager) {
                $backups = $this->backupManager->getBackupHistory(20);
                $schedules = $this->backupManager->getBackupSchedules();
            }
            
            $pageTitle = 'Backup Management - ConstructLink™';
            $pageHeader = 'Backup Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'System Admin', 'url' => '?route=admin'],
                ['title' => 'Backups', 'url' => '?route=admin/backups']
            ];
            
            include APP_ROOT . '/views/admin/backups.php';
            
        } catch (Exception $e) {
            error_log("Backups page error: " . $e->getMessage());
            $error = 'Failed to load backups page';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Create manual backup
     */
    public function createBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        try {
            if (!$this->backupManager) {
                echo json_encode(['success' => false, 'message' => 'Backup manager not available']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $description = $input['description'] ?? 'Manual backup';
            
            $currentUser = $this->auth->getCurrentUser();
            $result = $this->backupManager->createBackup(null, $description, $currentUser['id']);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Create backup error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Backup creation failed']);
        }
    }

    /**
     * Module management page
     */
    public function modules() {
        try {
            $availableModules = [];
            $installedModules = [];
            
            if ($this->moduleManager) {
                $availableModules = $this->moduleManager->getAvailableModules();
                $installedModules = $this->moduleManager->getInstalledModules();
            }
            
            $pageTitle = 'Module Management - ConstructLink™';
            $pageHeader = 'Module Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'System Admin', 'url' => '?route=admin'],
                ['title' => 'Modules', 'url' => '?route=admin/modules']
            ];
            
            include APP_ROOT . '/views/admin/modules.php';
            
        } catch (Exception $e) {
            error_log("Modules page error: " . $e->getMessage());
            $error = 'Failed to load modules page';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Install module
     */
    public function installModule() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        try {
            if (!$this->moduleManager) {
                echo json_encode(['success' => false, 'message' => 'Module manager not available']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $moduleName = $input['module_name'] ?? '';
            
            if (empty($moduleName)) {
                echo json_encode(['success' => false, 'message' => 'Module name is required']);
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            $result = $this->moduleManager->installModule($moduleName, $currentUser['id']);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Install module error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Module installation failed']);
        }
    }

    /**
     * Uninstall module
     */
    public function uninstallModule() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        try {
            if (!$this->moduleManager) {
                echo json_encode(['success' => false, 'message' => 'Module manager not available']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $moduleName = $input['module_name'] ?? '';
            
            if (empty($moduleName)) {
                echo json_encode(['success' => false, 'message' => 'Module name is required']);
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            $result = $this->moduleManager->uninstallModule($moduleName, $currentUser['id']);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Uninstall module error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Module uninstallation failed']);
        }
    }

    /**
     * Toggle module (enable/disable)
     */
    public function toggleModule() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        try {
            if (!$this->moduleManager) {
                echo json_encode(['success' => false, 'message' => 'Module manager not available']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $moduleName = $input['module_name'] ?? '';
            $enabled = $input['enabled'] ?? false;
            
            if (empty($moduleName)) {
                echo json_encode(['success' => false, 'message' => 'Module name is required']);
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            $result = $this->moduleManager->toggleModule($moduleName, $enabled, $currentUser['id']);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Toggle module error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Module toggle failed']);
        }
    }

    /**
     * Security monitoring page
     */
    public function security() {
        try {
            $securityManager = null;
            if (class_exists('SecurityManager')) {
                $securityManager = new SecurityManager();
            }
            
            $securityLogs = [];
            $activeSessions = [];
            $failedLogins = [];
            
            if ($securityManager) {
                $securityLogs = $securityManager->getSecurityLogs(50);
                $activeSessions = $securityManager->getActiveSessions();
                $failedLogins = $securityManager->getFailedLogins(100);
            }
            
            $pageTitle = 'Security Monitoring - ConstructLink™';
            $pageHeader = 'Security Monitoring';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'System Admin', 'url' => '?route=admin'],
                ['title' => 'Security', 'url' => '?route=admin/security']
            ];
            
            include APP_ROOT . '/views/admin/security.php';
            
        } catch (Exception $e) {
            error_log("Security page error: " . $e->getMessage());
            $error = 'Failed to load security page';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Get system health status
     */
    private function getSystemHealth() {
        $health = [
            'overall' => 'good',
            'checks' => []
        ];
        
        try {
            // Database connection
            $dbHealth = $this->checkDatabaseHealth();
            $health['checks']['database'] = $dbHealth;
            
            // File permissions
            $fileHealth = $this->checkFilePermissions();
            $health['checks']['files'] = $fileHealth;
            
            // Disk space
            $diskHealth = $this->checkDiskSpace();
            $health['checks']['disk'] = $diskHealth;
            
            // Memory usage
            $memoryHealth = $this->checkMemoryUsage();
            $health['checks']['memory'] = $memoryHealth;
            
            // Determine overall health
            $issues = 0;
            foreach ($health['checks'] as $check) {
                if ($check['status'] !== 'good') {
                    $issues++;
                }
            }
            
            if ($issues === 0) {
                $health['overall'] = 'good';
            } elseif ($issues <= 2) {
                $health['overall'] = 'warning';
            } else {
                $health['overall'] = 'critical';
            }
            
        } catch (Exception $e) {
            error_log("System health check error: " . $e->getMessage());
            $health['overall'] = 'unknown';
        }
        
        return $health;
    }
    
    /**
     * Check database health
     */
    private function checkDatabaseHealth() {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT 1");
            
            return [
                'status' => 'good',
                'message' => 'Database connection is healthy'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check file permissions
     */
    private function checkFilePermissions() {
        $paths = [
            APP_ROOT . '/logs',
            APP_ROOT . '/uploads',
            APP_ROOT . '/assets/qr'
        ];
        
        $issues = [];
        foreach ($paths as $path) {
            if (!is_writable($path)) {
                $issues[] = basename($path);
            }
        }
        
        if (empty($issues)) {
            return [
                'status' => 'good',
                'message' => 'All directories are writable'
            ];
        } else {
            return [
                'status' => 'warning',
                'message' => 'Some directories are not writable: ' . implode(', ', $issues)
            ];
        }
    }
    
    /**
     * Check disk space
     */
    private function checkDiskSpace() {
        if (!function_exists('disk_free_space') || !function_exists('disk_total_space')) {
            return [
                'status' => 'unknown',
                'message' => 'Disk space functions not available'
            ];
        }
        
        $free = disk_free_space(APP_ROOT);
        $total = disk_total_space(APP_ROOT);
        
        if ($free === false || $total === false) {
            return [
                'status' => 'unknown',
                'message' => 'Unable to determine disk space'
            ];
        }
        
        $usedPercent = (($total - $free) / $total) * 100;
        
        if ($usedPercent > 90) {
            return [
                'status' => 'critical',
                'message' => sprintf('Disk space critically low: %.1f%% used', $usedPercent)
            ];
        } elseif ($usedPercent > 80) {
            return [
                'status' => 'warning',
                'message' => sprintf('Disk space getting low: %.1f%% used', $usedPercent)
            ];
        } else {
            return [
                'status' => 'good',
                'message' => sprintf('Disk space healthy: %.1f%% used', $usedPercent)
            ];
        }
    }
    
    /**
     * Check memory usage
     */
    private function checkMemoryUsage() {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit === '-1') {
            return [
                'status' => 'good',
                'message' => 'Memory limit is unlimited'
            ];
        }
        
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
        $usagePercent = ($memoryUsage / $memoryLimitBytes) * 100;
        
        if ($usagePercent > 90) {
            return [
                'status' => 'critical',
                'message' => sprintf('Memory usage critical: %.1f%%', $usagePercent)
            ];
        } elseif ($usagePercent > 75) {
            return [
                'status' => 'warning',
                'message' => sprintf('Memory usage high: %.1f%%', $usagePercent)
            ];
        } else {
            return [
                'status' => 'good',
                'message' => sprintf('Memory usage normal: %.1f%%', $usagePercent)
            ];
        }
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit($limit) {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int)$limit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Get recent activity logs
     */
    private function getRecentActivity($limit = 10) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT al.*, u.full_name as user_name
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get recent activity error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get activity logs with pagination
     */
    private function getActivityLogs($page = 1, $perPage = 50) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $db = Database::getInstance()->getConnection();
            
            // Count total records
            $countStmt = $db->prepare("SELECT COUNT(*) FROM activity_logs");
            $countStmt->execute();
            $total = $countStmt->fetchColumn();
            
            // Get paginated data
            $stmt = $db->prepare("
                SELECT al.*, u.full_name as user_name
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$perPage, $offset]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'data' => $logs,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                    'has_next' => $page < ceil($total / $perPage),
                    'has_prev' => $page > 1
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Get activity logs error: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_next' => false,
                    'has_prev' => false
                ]
            ];
        }
    }
    
    /**
     * Get audit logs with pagination
     */
    private function getAuditLogs($page = 1, $perPage = 50) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $db = Database::getInstance()->getConnection();
            
            // Count total records
            $countStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs");
            $countStmt->execute();
            $total = $countStmt->fetchColumn();
            
            // Get paginated data
            $stmt = $db->prepare("
                SELECT al.*, u.full_name as user_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$perPage, $offset]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'data' => $logs,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                    'has_next' => $page < ceil($total / $perPage),
                    'has_prev' => $page > 1
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Get audit logs error: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_next' => false,
                    'has_prev' => false
                ]
            ];
        }
    }
    
    /**
     * Enable maintenance mode
     */
    private function enableMaintenanceMode() {
        $currentUser = $this->auth->getCurrentUser();
        return $this->systemSettingsModel->updateSetting('maintenance_mode', '1', 'System maintenance mode enabled', false, $currentUser['id']);
    }
    
    /**
     * Disable maintenance mode
     */
    private function disableMaintenanceMode() {
        $currentUser = $this->auth->getCurrentUser();
        return $this->systemSettingsModel->updateSetting('maintenance_mode', '0', 'System maintenance mode disabled', false, $currentUser['id']);
    }
    
    /**
     * Clear cache
     */
    private function clearCache() {
        try {
            // Clear session cache if using file-based sessions
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            
            // Clear any application cache files
            $cacheDir = APP_ROOT . '/cache';
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            
            return ['success' => true, 'message' => 'Cache cleared successfully'];
            
        } catch (Exception $e) {
            error_log("Clear cache error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to clear cache'];
        }
    }
    
    /**
     * Optimize database
     */
    private function optimizeDatabase() {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get all tables
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $optimized = 0;
            foreach ($tables as $table) {
                $stmt = $db->prepare("OPTIMIZE TABLE `{$table}`");
                if ($stmt->execute()) {
                    $optimized++;
                }
            }
            
            return ['success' => true, 'message' => "Optimized {$optimized} database tables"];
            
        } catch (Exception $e) {
            error_log("Optimize database error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to optimize database'];
        }
    }
    
    /**
     * Backup database
     */
    private function backupDatabase() {
        try {
            $backupDir = APP_ROOT . '/backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupDir . '/' . $filename;
            
            // Simple backup using mysqldump if available
            $command = sprintf(
                'mysqldump -h%s -u%s -p%s %s > %s',
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                $filepath
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($filepath)) {
                return ['success' => true, 'message' => "Database backup created: {$filename}"];
            } else {
                return ['success' => false, 'message' => 'Failed to create database backup'];
            }
            
        } catch (Exception $e) {
            error_log("Backup database error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create database backup'];
        }
    }
    
    /**
     * Asset Standardization Management
     */
    public function assetStandardization() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions - only System Admin and Asset Director can access
        if (!in_array($userRole, ['System Admin', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        include APP_ROOT . '/views/admin/asset-standardization.php';
    }
    
    /**
     * Brand Workflow Management
     */
    public function brandWorkflow() {
        $auth = Auth::getInstance();
        $user = $auth->getCurrentUser();
        
        // Check permissions
        $allowedRoles = ['System Admin', 'Asset Director'];
        if (!in_array($user['role_name'], $allowedRoles)) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        include APP_ROOT . '/views/admin/brand-workflow.php';
    }
}
?>
