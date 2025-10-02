<?php
/**
 * ConstructLink™ GitHub Integration Update Manager
 * Industry-standard update system for shared hosting environments
 */

class UpdateManager {
    private $db;
    private $systemSettingsModel;
    private $backupManager;
    private $githubRepo;
    private $githubToken;
    private $updatePath;
    private $tempPath;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->systemSettingsModel = new SystemSettingsModel();
        $this->backupManager = new BackupManager();
        
        // GitHub configuration
        $this->githubRepo = 'yourusername/constructlink'; // Configure this
        $this->githubToken = $this->systemSettingsModel->getSetting('github_token', '');
        
        // Paths for updates
        $this->updatePath = APP_ROOT . '/updates/';
        $this->tempPath = APP_ROOT . '/temp/';
        
        // Ensure directories exist
        $this->ensureDirectories();
        $this->initializeUpdateTables();
    }

    /**
     * Check for available updates from GitHub
     */
    public function checkForUpdates() {
        try {
            $currentVersion = $this->systemSettingsModel->getSetting('system_version', '1.0.0');
            
            // Fetch latest releases from GitHub API
            $releases = $this->fetchGitHubReleases();
            
            $availableUpdates = [];
            foreach ($releases as $release) {
                if (version_compare($release['tag_name'], $currentVersion, '>')) {
                    $availableUpdates[] = [
                        'version' => $release['tag_name'],
                        'name' => $release['name'],
                        'description' => $release['body'],
                        'published_at' => $release['published_at'],
                        'download_url' => $release['zipball_url'],
                        'is_prerelease' => $release['prerelease'],
                        'assets' => $release['assets']
                    ];
                }
            }
            
            return [
                'success' => true,
                'current_version' => $currentVersion,
                'updates_available' => count($availableUpdates),
                'updates' => $availableUpdates
            ];
            
        } catch (Exception $e) {
            error_log("Check updates error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to check for updates: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download and install system update
     */
    public function installUpdate($version, $userId = null) {
        try {
            // Step 1: Validate update
            $updateInfo = $this->getUpdateInfo($version);
            if (!$updateInfo) {
                return [
                    'success' => false,
                    'message' => 'Update version not found'
                ];
            }

            // Step 2: Create backup
            $backupResult = $this->createPreUpdateBackup($version, $userId);
            if (!$backupResult['success']) {
                return $backupResult;
            }

            // Step 3: Enable maintenance mode
            $this->enableMaintenanceMode();

            try {
                // Step 4: Download update
                $downloadResult = $this->downloadUpdate($updateInfo);
                if (!$downloadResult['success']) {
                    throw new Exception($downloadResult['message']);
                }

                // Step 5: Verify integrity
                $verifyResult = $this->verifyUpdateIntegrity($downloadResult['file_path']);
                if (!$verifyResult['success']) {
                    throw new Exception($verifyResult['message']);
                }

                // Step 6: Extract and apply update
                $extractResult = $this->extractAndApplyUpdate($downloadResult['file_path'], $version);
                if (!$extractResult['success']) {
                    throw new Exception($extractResult['message']);
                }

                // Step 7: Run migrations
                $migrationResult = $this->runUpdateMigrations($version);
                if (!$migrationResult['success']) {
                    throw new Exception($migrationResult['message']);
                }

                // Step 8: Update system version
                $this->systemSettingsModel->updateSetting('system_version', $version, 
                    'System updated to version ' . $version, false, $userId);

                // Step 9: Log successful update
                $this->logUpdate($version, $userId, $backupResult['backup_id']);

                // Step 10: Disable maintenance mode
                $this->disableMaintenanceMode();

                // Cleanup
                $this->cleanupTempFiles();

                return [
                    'success' => true,
                    'message' => "System successfully updated to version {$version}",
                    'backup_id' => $backupResult['backup_id']
                ];

            } catch (Exception $e) {
                // Disable maintenance mode on error
                $this->disableMaintenanceMode();
                
                // Cleanup temp files
                $this->cleanupTempFiles();
                
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Install update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Update installation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Rollback to previous version
     */
    public function rollbackUpdate($backupId, $userId = null) {
        try {
            // Enable maintenance mode
            $this->enableMaintenanceMode();

            // Restore from backup
            $restoreResult = $this->backupManager->restoreFromBackup($backupId, $userId);
            
            if ($restoreResult['success']) {
                // Log rollback
                $this->logRollback($backupId, $userId);
                
                // Disable maintenance mode
                $this->disableMaintenanceMode();
                
                return [
                    'success' => true,
                    'message' => 'System successfully rolled back'
                ];
            } else {
                $this->disableMaintenanceMode();
                return $restoreResult;
            }

        } catch (Exception $e) {
            $this->disableMaintenanceMode();
            error_log("Rollback update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Rollback failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get update history
     */
    public function getUpdateHistory($limit = 20) {
        try {
            $sql = "
                SELECT su.*, u.full_name as updated_by_name
                FROM system_updates su
                LEFT JOIN users u ON su.updated_by = u.id
                ORDER BY su.updated_at DESC
                LIMIT ?
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get update history error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch releases from GitHub API
     */
    private function fetchGitHubReleases() {
        $url = "https://api.github.com/repos/{$this->githubRepo}/releases";
        
        $context = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: ConstructLink-UpdateManager/1.0',
                    'Accept: application/vnd.github.v3+json'
                ]
            ]
        ];

        // Add GitHub token if available
        if (!empty($this->githubToken)) {
            $context['http']['header'][] = 'Authorization: token ' . $this->githubToken;
        }

        $response = file_get_contents($url, false, stream_context_create($context));
        
        if ($response === false) {
            throw new Exception('Failed to fetch releases from GitHub');
        }

        $releases = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from GitHub API');
        }

        return $releases;
    }

    /**
     * Get update information for specific version
     */
    private function getUpdateInfo($version) {
        $releases = $this->fetchGitHubReleases();
        
        foreach ($releases as $release) {
            if ($release['tag_name'] === $version) {
                return $release;
            }
        }
        
        return null;
    }

    /**
     * Download update from GitHub
     */
    private function downloadUpdate($updateInfo) {
        try {
            $downloadUrl = $updateInfo['zipball_url'];
            $fileName = 'update_' . $updateInfo['tag_name'] . '_' . time() . '.zip';
            $filePath = $this->tempPath . $fileName;

            // Download with cURL for better control
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $downloadUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ConstructLink-UpdateManager/1.0');
            
            if (!empty($this->githubToken)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: token ' . $this->githubToken
                ]);
            }

            $data = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || $data === false) {
                throw new Exception('Failed to download update file');
            }

            file_put_contents($filePath, $data);

            return [
                'success' => true,
                'file_path' => $filePath,
                'file_size' => filesize($filePath)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify update file integrity
     */
    private function verifyUpdateIntegrity($filePath) {
        try {
            // Check if file exists and has content
            if (!file_exists($filePath) || filesize($filePath) === 0) {
                return [
                    'success' => false,
                    'message' => 'Update file is empty or missing'
                ];
            }

            // Check if it's a valid ZIP file
            $zip = new ZipArchive();
            $result = $zip->open($filePath, ZipArchive::CHECKCONS);
            
            if ($result !== TRUE) {
                return [
                    'success' => false,
                    'message' => 'Update file is not a valid ZIP archive'
                ];
            }

            $zip->close();

            return [
                'success' => true,
                'message' => 'Update file integrity verified'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Integrity verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract and apply update files
     */
    private function extractAndApplyUpdate($filePath, $version) {
        try {
            $extractPath = $this->tempPath . 'extract_' . time() . '/';
            
            // Create extraction directory
            if (!mkdir($extractPath, 0755, true)) {
                throw new Exception('Failed to create extraction directory');
            }

            // Extract ZIP file
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== TRUE) {
                throw new Exception('Failed to open update ZIP file');
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // Find the extracted folder (GitHub ZIP contains folder with repo name)
            $extractedItems = scandir($extractPath);
            $sourceFolder = null;
            
            foreach ($extractedItems as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($extractPath . $item)) {
                    $sourceFolder = $extractPath . $item . '/';
                    break;
                }
            }

            if (!$sourceFolder) {
                throw new Exception('Could not find extracted source folder');
            }

            // Apply updates (copy files)
            $this->copyUpdateFiles($sourceFolder, APP_ROOT . '/');

            return [
                'success' => true,
                'message' => 'Update files applied successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'File extraction failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Copy update files to application directory
     */
    private function copyUpdateFiles($source, $destination) {
        $excludePatterns = [
            'config/config.php',     // Don't overwrite database config
            'uploads/',              // Don't touch uploaded files
            'backups/',              // Don't touch backups
            'logs/',                 // Don't touch logs
            '.git/',                 // Don't copy git files
            'temp/',                 // Don't copy temp files
            'updates/'               // Don't copy updates folder
        ];

        $this->recursiveCopy($source, $destination, $excludePatterns);
    }

    /**
     * Recursive file copy with exclusions
     */
    private function recursiveCopy($source, $destination, $excludePatterns = []) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = str_replace($source, '', $item->getPathname());
            
            // Check if file should be excluded
            $shouldExclude = false;
            foreach ($excludePatterns as $pattern) {
                if (strpos($relativePath, $pattern) === 0) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            $target = $destination . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                // Create directory if it doesn't exist
                $targetDir = dirname($target);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                copy($item->getPathname(), $target);
            }
        }
    }

    /**
     * Run database migrations for update
     */
    private function runUpdateMigrations($version) {
        try {
            // Check if there are version-specific migrations
            $migrationPath = APP_ROOT . '/database/migrations/updates/' . $version . '/';
            
            if (!is_dir($migrationPath)) {
                // No migrations for this version
                return [
                    'success' => true,
                    'message' => 'No migrations required for this version'
                ];
            }

            $migrationFiles = glob($migrationPath . '*.sql');
            sort($migrationFiles);

            foreach ($migrationFiles as $migrationFile) {
                $sql = file_get_contents($migrationFile);
                
                // Split and execute SQL statements
                $statements = array_filter(explode(';', $sql));
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $this->db->exec($statement);
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'Migrations executed successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create backup before update
     */
    private function createPreUpdateBackup($version, $userId) {
        $description = "Pre-update backup for version {$version}";
        return $this->backupManager->createBackup(null, $description, $userId, 'pre_update');
    }

    /**
     * Enable maintenance mode
     */
    private function enableMaintenanceMode() {
        $this->systemSettingsModel->updateSetting('maintenance_mode', '1');
    }

    /**
     * Disable maintenance mode
     */
    private function disableMaintenanceMode() {
        $this->systemSettingsModel->updateSetting('maintenance_mode', '0');
    }

    /**
     * Log successful update
     */
    private function logUpdate($version, $userId, $backupId) {
        try {
            $sql = "
                INSERT INTO system_updates (version, updated_by, backup_id, status, updated_at)
                VALUES (?, ?, ?, 'completed', NOW())
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$version, $userId, $backupId]);
        } catch (Exception $e) {
            error_log("Log update error: " . $e->getMessage());
        }
    }

    /**
     * Log rollback operation
     */
    private function logRollback($backupId, $userId) {
        try {
            $sql = "
                INSERT INTO system_updates (version, updated_by, backup_id, status, updated_at, notes)
                VALUES ('rollback', ?, ?, 'rollback', NOW(), 'System rolled back from backup')
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $backupId]);
        } catch (Exception $e) {
            error_log("Log rollback error: " . $e->getMessage());
        }
    }

    /**
     * Cleanup temporary files
     */
    private function cleanupTempFiles() {
        try {
            $files = glob($this->tempPath . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                } elseif (is_dir($file)) {
                    $this->recursiveDelete($file);
                }
            }
        } catch (Exception $e) {
            error_log("Cleanup temp files error: " . $e->getMessage());
        }
    }

    /**
     * Recursive directory deletion
     */
    private function recursiveDelete($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->recursiveDelete($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Ensure required directories exist
     */
    private function ensureDirectories() {
        $directories = [$this->updatePath, $this->tempPath];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Initialize update tracking tables
     */
    private function initializeUpdateTables() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS system_updates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    version VARCHAR(20) NOT NULL,
                    updated_by INT,
                    backup_id INT,
                    status ENUM('completed', 'failed', 'rollback') DEFAULT 'completed',
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    notes TEXT,
                    INDEX (updated_at),
                    INDEX (version),
                    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (backup_id) REFERENCES backups(id) ON DELETE SET NULL
                )
            ";
            $this->db->exec($sql);
        } catch (Exception $e) {
            error_log("Initialize update tables error: " . $e->getMessage());
        }
    }
}
?>