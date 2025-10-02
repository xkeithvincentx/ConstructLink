<?php
/**
 * ConstructLink™ System Upgrade Manager
 * Handles system version control, database migrations, and upgrade processes
 */

class SystemUpgradeManager {
    private $db;
    private $systemSettingsModel;
    private $migrationPath;
    private $backupPath;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->systemSettingsModel = new SystemSettingsModel();
        $this->migrationPath = APP_ROOT . '/database/migrations/';
        $this->backupPath = APP_ROOT . '/backups/';
        
        // Ensure backup directory exists
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
        
        // Initialize migration tracking table if it doesn't exist
        $this->initializeMigrationTable();
    }

    /**
     * Get current system version
     */
    public function getCurrentVersion() {
        return $this->systemSettingsModel->getSetting('system_version', '1.0.0');
    }

    /**
     * Get available versions/updates
     */
    public function getAvailableVersions() {
        try {
            $versions = [
                '2.0.0' => [
                    'version' => '2.0.0',
                    'release_date' => '2025-01-15',
                    'description' => 'Enhanced MVA workflow system with improved security',
                    'migrations' => [
                        'update_maintenance_mva_workflow.sql',
                        'update_borrowed_tools_mva_workflow.sql',
                        'update_transfers_mva_workflow.sql',
                        'update_withdrawals_mva_workflow.sql',
                        'add_incident_mva_workflow.sql'
                    ],
                    'features' => [
                        'Enhanced Maker-Verifier-Authorizer workflow',
                        'Improved audit logging',
                        'Advanced system monitoring',
                        'Enhanced security features',
                        'Performance optimizations'
                    ],
                    'breaking_changes' => []
                ],
                '2.1.0' => [
                    'version' => '2.1.0',
                    'release_date' => '2025-02-15',
                    'description' => 'Advanced backup system and automated maintenance',
                    'migrations' => [
                        'add_backup_schedules.sql',
                        'add_maintenance_schedules.sql',
                        'enhance_audit_logs.sql'
                    ],
                    'features' => [
                        'Automated backup scheduling',
                        'Advanced maintenance automation',
                        'Real-time system monitoring',
                        'Enhanced reporting capabilities'
                    ],
                    'breaking_changes' => []
                ]
            ];
            
            $currentVersion = $this->getCurrentVersion();
            $available = [];
            
            foreach ($versions as $version => $info) {
                if (version_compare($version, $currentVersion, '>')) {
                    $available[] = $info;
                }
            }
            
            return $available;
        } catch (Exception $e) {
            error_log("Get available versions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if system needs upgrade
     */
    public function needsUpgrade() {
        $available = $this->getAvailableVersions();
        return !empty($available);
    }

    /**
     * Get pending migrations
     */
    public function getPendingMigrations() {
        try {
            $allMigrations = $this->getAllMigrationFiles();
            $executedMigrations = $this->getExecutedMigrations();
            
            return array_diff($allMigrations, $executedMigrations);
        } catch (Exception $e) {
            error_log("Get pending migrations error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all migration files
     */
    private function getAllMigrationFiles() {
        $migrations = [];
        if (is_dir($this->migrationPath)) {
            $files = scandir($this->migrationPath);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $migrations[] = $file;
                }
            }
        }
        sort($migrations);
        return $migrations;
    }

    /**
     * Get executed migrations from database
     */
    private function getExecutedMigrations() {
        try {
            $sql = "SELECT migration_file FROM system_migrations WHERE executed = 1 ORDER BY executed_at ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            error_log("Get executed migrations error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Execute a specific migration
     */
    public function executeMigration($migrationFile, $userId = null) {
        try {
            $migrationPath = $this->migrationPath . $migrationFile;
            
            if (!file_exists($migrationPath)) {
                return [
                    'success' => false,
                    'message' => 'Migration file not found: ' . $migrationFile
                ];
            }

            // Check if migration already executed
            if ($this->isMigrationExecuted($migrationFile)) {
                return [
                    'success' => false,
                    'message' => 'Migration already executed: ' . $migrationFile
                ];
            }

            $this->db->beginTransaction();

            try {
                // Read and execute migration SQL
                $sql = file_get_contents($migrationPath);
                
                // Split SQL statements (basic implementation)
                $statements = $this->splitSqlStatements($sql);
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement) && !$this->isComment($statement)) {
                        $this->db->exec($statement);
                    }
                }

                // Record migration execution
                $this->recordMigrationExecution($migrationFile, $userId);

                $this->db->commit();

                return [
                    'success' => true,
                    'message' => 'Migration executed successfully: ' . $migrationFile
                ];

            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Execute migration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Migration execution failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Execute all pending migrations
     */
    public function executePendingMigrations($userId = null) {
        try {
            $pendingMigrations = $this->getPendingMigrations();
            
            if (empty($pendingMigrations)) {
                return [
                    'success' => true,
                    'message' => 'No pending migrations to execute'
                ];
            }

            $results = [];
            $errors = [];

            foreach ($pendingMigrations as $migration) {
                $result = $this->executeMigration($migration, $userId);
                $results[] = $result;
                
                if (!$result['success']) {
                    $errors[] = $result['message'];
                    break; // Stop on first error
                }
            }

            if (empty($errors)) {
                return [
                    'success' => true,
                    'message' => 'All pending migrations executed successfully',
                    'executed_count' => count($pendingMigrations),
                    'results' => $results
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Migration execution failed',
                    'errors' => $errors,
                    'results' => $results
                ];
            }

        } catch (Exception $e) {
            error_log("Execute pending migrations error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to execute pending migrations'
            ];
        }
    }

    /**
     * Perform system upgrade to specified version
     */
    public function upgradeToVersion($targetVersion, $userId = null) {
        try {
            $currentVersion = $this->getCurrentVersion();
            
            if (version_compare($targetVersion, $currentVersion, '<=')) {
                return [
                    'success' => false,
                    'message' => 'Target version must be higher than current version'
                ];
            }

            // Create backup before upgrade
            $backupResult = $this->createPreUpgradeBackup($targetVersion);
            if (!$backupResult['success']) {
                return $backupResult;
            }

            $this->db->beginTransaction();

            try {
                // Execute version-specific migrations
                $versionInfo = $this->getVersionInfo($targetVersion);
                if ($versionInfo && isset($versionInfo['migrations'])) {
                    foreach ($versionInfo['migrations'] as $migration) {
                        $result = $this->executeMigration($migration, $userId);
                        if (!$result['success']) {
                            throw new Exception('Migration failed: ' . $result['message']);
                        }
                    }
                }

                // Update system version
                $updateResult = $this->systemSettingsModel->updateSetting(
                    'system_version', 
                    $targetVersion, 
                    'System upgraded to version ' . $targetVersion,
                    false,
                    $userId
                );

                if (!$updateResult['success']) {
                    throw new Exception('Failed to update system version');
                }

                // Log upgrade
                $this->logUpgrade($currentVersion, $targetVersion, $userId);

                $this->db->commit();

                return [
                    'success' => true,
                    'message' => "System successfully upgraded from {$currentVersion} to {$targetVersion}",
                    'backup_file' => $backupResult['backup_file'] ?? null
                ];

            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("System upgrade error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'System upgrade failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get upgrade history
     */
    public function getUpgradeHistory($limit = 50) {
        try {
            $sql = "
                SELECT ug.*, u.full_name as upgraded_by_name
                FROM system_upgrades ug
                LEFT JOIN users u ON ug.upgraded_by = u.id
                ORDER BY ug.upgraded_at DESC
                LIMIT ?
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get upgrade history error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate system integrity
     */
    public function validateSystemIntegrity() {
        $issues = [];

        try {
            // Check database structure
            $dbIssues = $this->validateDatabaseStructure();
            if (!empty($dbIssues)) {
                $issues['database'] = $dbIssues;
            }

            // Check file permissions
            $permissionIssues = $this->validateFilePermissions();
            if (!empty($permissionIssues)) {
                $issues['permissions'] = $permissionIssues;
            }

            // Check configuration
            $configIssues = $this->validateConfiguration();
            if (!empty($configIssues)) {
                $issues['configuration'] = $configIssues;
            }

            return [
                'valid' => empty($issues),
                'issues' => $issues
            ];

        } catch (Exception $e) {
            error_log("Validate system integrity error: " . $e->getMessage());
            return [
                'valid' => false,
                'issues' => ['system' => ['Failed to validate system integrity']]
            ];
        }
    }

    /**
     * Initialize migration tracking table
     */
    private function initializeMigrationTable() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS system_migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration_file VARCHAR(255) NOT NULL UNIQUE,
                    executed BOOLEAN DEFAULT TRUE,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    executed_by INT,
                    INDEX (executed),
                    INDEX (executed_at),
                    FOREIGN KEY (executed_by) REFERENCES users(id) ON DELETE SET NULL
                )
            ";
            $this->db->exec($sql);

            $sql = "
                CREATE TABLE IF NOT EXISTS system_upgrades (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    from_version VARCHAR(20) NOT NULL,
                    to_version VARCHAR(20) NOT NULL,
                    upgraded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    upgraded_by INT,
                    backup_file VARCHAR(255),
                    notes TEXT,
                    INDEX (upgraded_at),
                    FOREIGN KEY (upgraded_by) REFERENCES users(id) ON DELETE SET NULL
                )
            ";
            $this->db->exec($sql);

        } catch (Exception $e) {
            error_log("Initialize migration table error: " . $e->getMessage());
        }
    }

    /**
     * Check if migration was executed
     */
    private function isMigrationExecuted($migrationFile) {
        try {
            $sql = "SELECT COUNT(*) FROM system_migrations WHERE migration_file = ? AND executed = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$migrationFile]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Record migration execution
     */
    private function recordMigrationExecution($migrationFile, $userId = null) {
        try {
            $sql = "
                INSERT INTO system_migrations (migration_file, executed, executed_at, executed_by)
                VALUES (?, TRUE, NOW(), ?)
                ON DUPLICATE KEY UPDATE executed = TRUE, executed_at = NOW(), executed_by = ?
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$migrationFile, $userId, $userId]);
        } catch (Exception $e) {
            error_log("Record migration execution error: " . $e->getMessage());
        }
    }

    /**
     * Split SQL statements
     */
    private function splitSqlStatements($sql) {
        // Remove comments and split by semicolon
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        $statements = explode(';', $sql);
        return array_filter($statements, function($stmt) {
            return !empty(trim($stmt));
        });
    }

    /**
     * Check if line is a comment
     */
    private function isComment($line) {
        $trimmed = trim($line);
        return empty($trimmed) || 
               strpos($trimmed, '--') === 0 || 
               strpos($trimmed, '/*') === 0 ||
               strpos($trimmed, '#') === 0;
    }

    /**
     * Create pre-upgrade backup
     */
    private function createPreUpgradeBackup($version) {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "pre_upgrade_{$version}_{$timestamp}.sql";
            $filepath = $this->backupPath . $filename;

            // Use BackupManager if available, otherwise fallback to mysqldump
            if (class_exists('BackupManager')) {
                $backupManager = new BackupManager();
                $result = $backupManager->createBackup($filename, 'Pre-upgrade backup for version ' . $version);
            } else {
                // Fallback to basic mysqldump
                $command = sprintf(
                    'mysqldump -h%s -u%s -p%s %s > %s',
                    DB_HOST,
                    DB_USER,
                    DB_PASS,
                    DB_NAME,
                    $filepath
                );
                
                exec($command, $output, $returnCode);
                
                $result = [
                    'success' => $returnCode === 0 && file_exists($filepath),
                    'backup_file' => $filename
                ];
            }

            return $result;

        } catch (Exception $e) {
            error_log("Create pre-upgrade backup error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create backup before upgrade'
            ];
        }
    }

    /**
     * Get version information
     */
    private function getVersionInfo($version) {
        $versions = $this->getAvailableVersions();
        foreach ($versions as $versionInfo) {
            if ($versionInfo['version'] === $version) {
                return $versionInfo;
            }
        }
        return null;
    }

    /**
     * Log upgrade
     */
    private function logUpgrade($fromVersion, $toVersion, $userId) {
        try {
            $sql = "
                INSERT INTO system_upgrades (from_version, to_version, upgraded_at, upgraded_by, notes)
                VALUES (?, ?, NOW(), ?, ?)
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $fromVersion,
                $toVersion,
                $userId,
                "System upgraded from {$fromVersion} to {$toVersion}"
            ]);
        } catch (Exception $e) {
            error_log("Log upgrade error: " . $e->getMessage());
        }
    }

    /**
     * Validate database structure
     */
    private function validateDatabaseStructure() {
        $issues = [];
        
        try {
            // Check required tables exist
            $requiredTables = [
                'users', 'roles', 'projects', 'assets', 'system_settings',
                'requests', 'procurement_orders', 'audit_logs', 'activity_logs'
            ];

            foreach ($requiredTables as $table) {
                try {
                    // Try to query the table to see if it exists
                    $sql = "SELECT 1 FROM `{$table}` LIMIT 1";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute();
                } catch (Exception $e) {
                    // If the query fails, the table likely doesn't exist
                    if (strpos($e->getMessage(), "doesn't exist") !== false || 
                        strpos($e->getMessage(), "Table") !== false) {
                        $issues[] = "Missing required table: {$table}";
                    }
                }
            }

        } catch (Exception $e) {
            $issues[] = "Database structure validation failed: " . $e->getMessage();
        }

        return $issues;
    }

    /**
     * Validate file permissions
     */
    private function validateFilePermissions() {
        $issues = [];
        
        $requiredWritablePaths = [
            APP_ROOT . '/logs',
            APP_ROOT . '/uploads',
            APP_ROOT . '/backups',
            APP_ROOT . '/assets/qr'
        ];

        foreach ($requiredWritablePaths as $path) {
            if (!is_dir($path)) {
                $issues[] = "Missing directory: {$path}";
            } elseif (!is_writable($path)) {
                $issues[] = "Directory not writable: {$path}";
            }
        }

        return $issues;
    }

    /**
     * Validate configuration
     */
    private function validateConfiguration() {
        $issues = [];
        
        // Check required settings
        $requiredSettings = [
            'system_name', 'company_name', 'system_version',
            'asset_ref_prefix', 'po_prefix', 'request_prefix'
        ];

        foreach ($requiredSettings as $setting) {
            $value = $this->systemSettingsModel->getSetting($setting);
            if (empty($value)) {
                $issues[] = "Missing required setting: {$setting}";
            }
        }

        return $issues;
    }
}
?>