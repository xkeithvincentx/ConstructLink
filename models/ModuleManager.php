<?php
/**
 * ConstructLink™ Module Management System
 * Handles dynamic module loading, installation, and configuration
 */

class ModuleManager {
    private $db;
    private $systemSettingsModel;
    private $modulePath;
    private $configPath;
    private $loadedModules = [];
    private $moduleConfig = [];
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->systemSettingsModel = new SystemSettingsModel();
        $this->modulePath = APP_ROOT . '/modules/';
        $this->configPath = APP_ROOT . '/config/modules.json';
        
        // Ensure modules directory exists
        if (!is_dir($this->modulePath)) {
            mkdir($this->modulePath, 0755, true);
        }
        
        // Initialize module tracking tables
        $this->initializeModuleTables();
        
        // Load module configuration
        $this->loadModuleConfig();
    }
    
    /**
     * Get all available modules
     */
    public function getAvailableModules() {
        try {
            $modules = [];
            
            // Scan modules directory
            if (is_dir($this->modulePath)) {
                $directories = array_filter(glob($this->modulePath . '*'), 'is_dir');
                
                foreach ($directories as $dir) {
                    $moduleName = basename($dir);
                    $manifestPath = $dir . '/module.json';
                    
                    if (file_exists($manifestPath)) {
                        $manifest = json_decode(file_get_contents($manifestPath), true);
                        if ($manifest) {
                            $modules[$moduleName] = array_merge($manifest, [
                                'path' => $dir,
                                'installed' => $this->isModuleInstalled($moduleName),
                                'enabled' => $this->isModuleEnabled($moduleName),
                                'has_config' => file_exists($dir . '/config.php'),
                                'has_routes' => file_exists($dir . '/routes.php'),
                                'has_views' => is_dir($dir . '/views')
                            ]);
                        }
                    }
                }
            }
            
            return $modules;
        } catch (Exception $e) {
            error_log("Get available modules error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get installed modules
     */
    public function getInstalledModules() {
        try {
            $sql = "
                SELECT m.*, u.full_name as installed_by_name
                FROM modules m
                LEFT JOIN users u ON m.installed_by = u.id
                WHERE m.is_installed = 1
                ORDER BY m.display_order, m.name
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get installed modules error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get enabled modules
     */
    public function getEnabledModules() {
        try {
            $sql = "
                SELECT m.*, u.full_name as installed_by_name
                FROM modules m
                LEFT JOIN users u ON m.installed_by = u.id
                WHERE m.is_installed = 1 AND m.is_enabled = 1
                ORDER BY m.display_order, m.name
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get enabled modules error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Install a module
     */
    public function installModule($moduleName, $userId = null) {
        try {
            $moduleDir = $this->modulePath . $moduleName;
            $manifestPath = $moduleDir . '/module.json';
            
            if (!is_dir($moduleDir)) {
                return [
                    'success' => false,
                    'message' => 'Module directory not found: ' . $moduleName
                ];
            }
            
            if (!file_exists($manifestPath)) {
                return [
                    'success' => false,
                    'message' => 'Module manifest not found: module.json'
                ];
            }
            
            // Read module manifest
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (!$manifest) {
                return [
                    'success' => false,
                    'message' => 'Invalid module manifest'
                ];
            }
            
            // Validate required fields
            $requiredFields = ['name', 'version', 'description', 'author'];
            foreach ($requiredFields as $field) {
                if (empty($manifest[$field])) {
                    return [
                        'success' => false,
                        'message' => "Missing required field in manifest: {$field}"
                    ];
                }
            }
            
            // Check if already installed
            if ($this->isModuleInstalled($moduleName)) {
                return [
                    'success' => false,
                    'message' => 'Module is already installed'
                ];
            }
            
            // Check dependencies
            $dependencyCheck = $this->checkDependencies($manifest);
            if (!$dependencyCheck['success']) {
                return $dependencyCheck;
            }
            
            $this->db->beginTransaction();
            
            try {
                // Run installation scripts if they exist
                $installResult = $this->runInstallationScripts($moduleDir);
                if (!$installResult['success']) {
                    throw new Exception($installResult['message']);
                }
                
                // Insert module record
                $sql = "
                    INSERT INTO modules (
                        name, display_name, version, description, author, 
                        main_class, config_file, routes_file, 
                        is_installed, is_enabled, installed_at, installed_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1, NOW(), ?)
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $moduleName,
                    $manifest['display_name'] ?? $manifest['name'],
                    $manifest['version'],
                    $manifest['description'],
                    $manifest['author'],
                    $manifest['main_class'] ?? null,
                    file_exists($moduleDir . '/config.php') ? $moduleName . '/config.php' : null,
                    file_exists($moduleDir . '/routes.php') ? $moduleName . '/routes.php' : null,
                    $userId
                ]);
                
                $moduleId = $this->db->lastInsertId();
                
                // Insert module permissions if defined
                if (!empty($manifest['permissions'])) {
                    $this->installModulePermissions($moduleId, $manifest['permissions']);
                }
                
                // Insert module menu items if defined
                if (!empty($manifest['menu_items'])) {
                    $this->installModuleMenuItems($moduleId, $manifest['menu_items']);
                }
                
                // Update module configuration cache
                $this->updateModuleConfig();
                
                $this->db->commit();
                
                return [
                    'success' => true,
                    'message' => "Module '{$manifest['display_name']}' installed successfully",
                    'module_id' => $moduleId
                ];
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Install module error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Module installation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Uninstall a module
     */
    public function uninstallModule($moduleName, $userId = null) {
        try {
            if (!$this->isModuleInstalled($moduleName)) {
                return [
                    'success' => false,
                    'message' => 'Module is not installed'
                ];
            }
            
            $moduleDir = $this->modulePath . $moduleName;
            
            $this->db->beginTransaction();
            
            try {
                // Get module ID
                $sql = "SELECT id FROM modules WHERE name = ? AND is_installed = 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$moduleName]);
                $moduleId = $stmt->fetchColumn();
                
                if (!$moduleId) {
                    throw new Exception('Module record not found');
                }
                
                // Run uninstallation scripts if they exist
                $uninstallResult = $this->runUninstallationScripts($moduleDir);
                if (!$uninstallResult['success']) {
                    throw new Exception($uninstallResult['message']);
                }
                
                // Remove module permissions
                $sql = "DELETE FROM module_permissions WHERE module_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$moduleId]);
                
                // Remove module menu items
                $sql = "DELETE FROM module_menu_items WHERE module_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$moduleId]);
                
                // Mark module as uninstalled
                $sql = "
                    UPDATE modules 
                    SET is_installed = 0, is_enabled = 0, uninstalled_at = NOW(), uninstalled_by = ?
                    WHERE id = ?
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId, $moduleId]);
                
                // Update module configuration cache
                $this->updateModuleConfig();
                
                $this->db->commit();
                
                return [
                    'success' => true,
                    'message' => "Module '{$moduleName}' uninstalled successfully"
                ];
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Uninstall module error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Module uninstallation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enable/Disable a module
     */
    public function toggleModule($moduleName, $enabled = true, $userId = null) {
        try {
            if (!$this->isModuleInstalled($moduleName)) {
                return [
                    'success' => false,
                    'message' => 'Module is not installed'
                ];
            }
            
            $sql = "
                UPDATE modules 
                SET is_enabled = ?, updated_at = NOW(), updated_by = ?
                WHERE name = ? AND is_installed = 1
            ";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$enabled ? 1 : 0, $userId, $moduleName]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Update module configuration cache
                $this->updateModuleConfig();
                
                $action = $enabled ? 'enabled' : 'disabled';
                return [
                    'success' => true,
                    'message' => "Module '{$moduleName}' {$action} successfully"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update module status'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Toggle module error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update module status: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Load a module
     */
    public function loadModule($moduleName) {
        try {
            if (isset($this->loadedModules[$moduleName])) {
                return true; // Already loaded
            }
            
            if (!$this->isModuleEnabled($moduleName)) {
                return false; // Module not enabled
            }
            
            $moduleDir = $this->modulePath . $moduleName;
            $manifestPath = $moduleDir . '/module.json';
            
            if (!file_exists($manifestPath)) {
                return false;
            }
            
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (!$manifest) {
                return false;
            }
            
            // Load module main class if specified
            if (!empty($manifest['main_class'])) {
                $mainClassFile = $moduleDir . '/' . $manifest['main_class'] . '.php';
                if (file_exists($mainClassFile)) {
                    require_once $mainClassFile;
                    
                    // Initialize module if it has an init method
                    $className = $manifest['main_class'];
                    if (class_exists($className)) {
                        $moduleInstance = new $className();
                        if (method_exists($moduleInstance, 'init')) {
                            $moduleInstance->init();
                        }
                        $this->loadedModules[$moduleName] = $moduleInstance;
                    }
                }
            }
            
            // Load module configuration
            $configFile = $moduleDir . '/config.php';
            if (file_exists($configFile)) {
                $moduleConfig = include $configFile;
                if (is_array($moduleConfig)) {
                    $this->moduleConfig[$moduleName] = $moduleConfig;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Load module error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Load all enabled modules
     */
    public function loadAllModules() {
        $enabledModules = $this->getEnabledModules();
        $loadedCount = 0;
        
        foreach ($enabledModules as $module) {
            if ($this->loadModule($module['name'])) {
                $loadedCount++;
            }
        }
        
        return $loadedCount;
    }
    
    /**
     * Get module configuration
     */
    public function getModuleConfig($moduleName = null) {
        if ($moduleName) {
            return $this->moduleConfig[$moduleName] ?? [];
        }
        return $this->moduleConfig;
    }
    
    /**
     * Check if module is installed
     */
    private function isModuleInstalled($moduleName) {
        try {
            $sql = "SELECT COUNT(*) FROM modules WHERE name = ? AND is_installed = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$moduleName]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if module is enabled
     */
    private function isModuleEnabled($moduleName) {
        try {
            $sql = "SELECT COUNT(*) FROM modules WHERE name = ? AND is_installed = 1 AND is_enabled = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$moduleName]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check module dependencies
     */
    private function checkDependencies($manifest) {
        if (empty($manifest['dependencies'])) {
            return ['success' => true];
        }
        
        $missingDependencies = [];
        
        foreach ($manifest['dependencies'] as $dependency => $version) {
            if ($dependency === 'php') {
                if (version_compare(PHP_VERSION, $version, '<')) {
                    $missingDependencies[] = "PHP {$version} (current: " . PHP_VERSION . ")";
                }
            } elseif ($dependency === 'constructlink') {
                $currentVersion = $this->systemSettingsModel->getSetting('system_version', '1.0.0');
                if (version_compare($currentVersion, $version, '<')) {
                    $missingDependencies[] = "ConstructLink {$version} (current: {$currentVersion})";
                }
            } else {
                // Check if another module is installed
                if (!$this->isModuleInstalled($dependency)) {
                    $missingDependencies[] = "Module: {$dependency}";
                }
            }
        }
        
        if (!empty($missingDependencies)) {
            return [
                'success' => false,
                'message' => 'Missing dependencies: ' . implode(', ', $missingDependencies)
            ];
        }
        
        return ['success' => true];
    }
    
    /**
     * Run installation scripts
     */
    private function runInstallationScripts($moduleDir) {
        try {
            $installScript = $moduleDir . '/install.php';
            if (file_exists($installScript)) {
                // Capture any output from the install script
                ob_start();
                $result = include $installScript;
                $output = ob_get_clean();
                
                // If script returns false, consider it failed
                if ($result === false) {
                    return [
                        'success' => false,
                        'message' => 'Installation script failed' . ($output ? ': ' . $output : '')
                    ];
                }
            }
            
            // Run database migrations if they exist
            $sqlFile = $moduleDir . '/install.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $statements = array_filter(explode(';', $sql));
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $this->db->exec($statement);
                    }
                }
            }
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Installation script error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Run uninstallation scripts
     */
    private function runUninstallationScripts($moduleDir) {
        try {
            $uninstallScript = $moduleDir . '/uninstall.php';
            if (file_exists($uninstallScript)) {
                ob_start();
                $result = include $uninstallScript;
                $output = ob_get_clean();
                
                if ($result === false) {
                    return [
                        'success' => false,
                        'message' => 'Uninstallation script failed' . ($output ? ': ' . $output : '')
                    ];
                }
            }
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Uninstallation script error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Install module permissions
     */
    private function installModulePermissions($moduleId, $permissions) {
        foreach ($permissions as $permission) {
            $sql = "
                INSERT INTO module_permissions (module_id, permission_name, display_name, description)
                VALUES (?, ?, ?, ?)
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $moduleId,
                $permission['name'],
                $permission['display_name'] ?? $permission['name'],
                $permission['description'] ?? ''
            ]);
        }
    }
    
    /**
     * Install module menu items
     */
    private function installModuleMenuItems($moduleId, $menuItems) {
        foreach ($menuItems as $item) {
            $sql = "
                INSERT INTO module_menu_items (
                    module_id, title, url, icon, parent_id, display_order, permission_required
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $moduleId,
                $item['title'],
                $item['url'],
                $item['icon'] ?? null,
                $item['parent_id'] ?? null,
                $item['display_order'] ?? 0,
                $item['permission'] ?? null
            ]);
        }
    }
    
    /**
     * Update module configuration cache
     */
    private function updateModuleConfig() {
        try {
            $enabledModules = $this->getEnabledModules();
            file_put_contents($this->configPath, json_encode($enabledModules, JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            error_log("Update module config error: " . $e->getMessage());
        }
    }
    
    /**
     * Load module configuration from cache
     */
    private function loadModuleConfig() {
        if (file_exists($this->configPath)) {
            $config = json_decode(file_get_contents($this->configPath), true);
            if ($config) {
                foreach ($config as $module) {
                    $this->moduleConfig[$module['name']] = $module;
                }
            }
        }
    }
    
    /**
     * Initialize module tracking tables
     */
    private function initializeModuleTables() {
        try {
            // Modules table
            $sql = "
                CREATE TABLE IF NOT EXISTS modules (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL UNIQUE,
                    display_name VARCHAR(200) NOT NULL,
                    version VARCHAR(20) NOT NULL,
                    description TEXT,
                    author VARCHAR(100),
                    main_class VARCHAR(100),
                    config_file VARCHAR(255),
                    routes_file VARCHAR(255),
                    is_installed BOOLEAN DEFAULT FALSE,
                    is_enabled BOOLEAN DEFAULT FALSE,
                    display_order INT DEFAULT 0,
                    installed_at TIMESTAMP NULL,
                    installed_by INT,
                    uninstalled_at TIMESTAMP NULL,
                    uninstalled_by INT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    updated_by INT,
                    INDEX (is_installed),
                    INDEX (is_enabled),
                    INDEX (display_order),
                    FOREIGN KEY (installed_by) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (uninstalled_by) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
                )
            ";
            $this->db->exec($sql);
            
            // Module permissions table
            $sql = "
                CREATE TABLE IF NOT EXISTS module_permissions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    module_id INT NOT NULL,
                    permission_name VARCHAR(100) NOT NULL,
                    display_name VARCHAR(200) NOT NULL,
                    description TEXT,
                    UNIQUE KEY unique_module_permission (module_id, permission_name),
                    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
                )
            ";
            $this->db->exec($sql);
            
            // Module menu items table
            $sql = "
                CREATE TABLE IF NOT EXISTS module_menu_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    module_id INT NOT NULL,
                    title VARCHAR(100) NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    icon VARCHAR(50),
                    parent_id INT,
                    display_order INT DEFAULT 0,
                    permission_required VARCHAR(100),
                    INDEX (display_order),
                    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
                    FOREIGN KEY (parent_id) REFERENCES module_menu_items(id) ON DELETE CASCADE
                )
            ";
            $this->db->exec($sql);
            
        } catch (Exception $e) {
            error_log("Initialize module tables error: " . $e->getMessage());
        }
    }
}
?>