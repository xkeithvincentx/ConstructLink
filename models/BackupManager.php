<?php
/**
 * ConstructLink™ Backup Manager
 * Handles automated backup scheduling, verification, and management
 */

class BackupManager {
    private $db;
    private $systemSettingsModel;
    private $backupPath;
    private $maxBackupAge; // days
    private $maxBackupSize; // bytes
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->systemSettingsModel = new SystemSettingsModel();
        $this->backupPath = APP_ROOT . '/backups/';
        $this->maxBackupAge = 30; // Keep backups for 30 days
        $this->maxBackupSize = 1073741824; // 1GB
        
        // Ensure backup directory exists
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
        
        // Initialize backup tracking table
        $this->initializeBackupTable();
    }

    /**
     * Create a new backup
     */
    public function createBackup($filename = null, $description = null, $userId = null, $type = 'manual') {
        try {
            if (!$filename) {
                $timestamp = date('Y-m-d_H-i-s');
                $filename = "backup_{$timestamp}.sql";
            }
            
            $filepath = $this->backupPath . $filename;
            
            // Check available disk space
            if (!$this->checkDiskSpace()) {
                return [
                    'success' => false,
                    'message' => 'Insufficient disk space for backup'
                ];
            }

            // Create the backup
            $result = $this->performDatabaseBackup($filepath);
            
            if ($result['success']) {
                // Verify backup integrity
                $verification = $this->verifyBackup($filepath);
                
                // Record backup in database
                $backupRecord = $this->recordBackup($filename, $description, $userId, $type, $verification['valid']);
                
                // Clean old backups if needed
                $this->cleanupOldBackups();
                
                return [
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'backup_file' => $filename,
                    'backup_id' => $backupRecord['id'],
                    'size' => filesize($filepath),
                    'verified' => $verification['valid']
                ];
            } else {
                return $result;
            }
            
        } catch (Exception $e) {
            error_log("Create backup error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Backup creation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Schedule automatic backup
     */
    public function scheduleBackup($frequency, $time, $description = null, $userId = null) {
        try {
            $validFrequencies = ['daily', 'weekly', 'monthly'];
            if (!in_array($frequency, $validFrequencies)) {
                return [
                    'success' => false,
                    'message' => 'Invalid backup frequency'
                ];
            }

            // Validate time format (HH:MM)
            if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
                return [
                    'success' => false,
                    'message' => 'Invalid time format. Use HH:MM'
                ];
            }

            $sql = "
                INSERT INTO backup_schedules (frequency, scheduled_time, description, created_by, is_active)
                VALUES (?, ?, ?, ?, 1)
            ";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$frequency, $time, $description, $userId]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Backup schedule created successfully',
                    'schedule_id' => $this->db->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create backup schedule'
                ];
            }

        } catch (Exception $e) {
            error_log("Schedule backup error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to schedule backup'
            ];
        }
    }

    /**
     * Get backup schedules
     */
    public function getBackupSchedules() {
        try {
            $sql = "
                SELECT bs.*, u.full_name as created_by_name,
                       (SELECT MAX(created_at) FROM backups WHERE type = 'scheduled') as last_scheduled_backup
                FROM backup_schedules bs
                LEFT JOIN users u ON bs.created_by = u.id
                WHERE bs.is_active = 1
                ORDER BY bs.created_at DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get backup schedules error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Execute scheduled backups
     */
    public function executeScheduledBackups() {
        try {
            $schedules = $this->getBackupSchedules();
            $executed = 0;
            $errors = [];

            foreach ($schedules as $schedule) {
                if ($this->shouldExecuteSchedule($schedule)) {
                    $description = "Scheduled {$schedule['frequency']} backup";
                    $result = $this->createBackup(null, $description, null, 'scheduled');
                    
                    if ($result['success']) {
                        $executed++;
                        $this->updateScheduleLastRun($schedule['id']);
                    } else {
                        $errors[] = "Schedule {$schedule['id']}: " . $result['message'];
                    }
                }
            }

            return [
                'success' => true,
                'executed' => $executed,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            error_log("Execute scheduled backups error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to execute scheduled backups'
            ];
        }
    }

    /**
     * Verify backup integrity
     */
    public function verifyBackup($filepath) {
        try {
            if (!file_exists($filepath)) {
                return [
                    'valid' => false,
                    'issues' => ['Backup file does not exist']
                ];
            }

            $issues = [];
            
            // Check file size
            $filesize = filesize($filepath);
            if ($filesize === 0) {
                $issues[] = 'Backup file is empty';
            } elseif ($filesize < 1024) {
                $issues[] = 'Backup file suspiciously small';
            }

            // Check file header (basic SQL dump validation)
            $handle = fopen($filepath, 'r');
            if ($handle) {
                $firstLine = fgets($handle);
                fclose($handle);
                
                if (!preg_match('/^(--|\/\*|DROP|CREATE|INSERT|SET)/i', trim($firstLine))) {
                    $issues[] = 'Backup file does not appear to be a valid SQL dump';
                }
            }

            // Check for corruption indicators
            $content = file_get_contents($filepath, false, null, 0, 1024);
            if (strpos($content, "\0") !== false) {
                $issues[] = 'Backup file may be corrupted (contains null bytes)';
            }

            // Additional integrity checks could be added here
            // such as checksum verification, table structure validation, etc.

            return [
                'valid' => empty($issues),
                'issues' => $issues,
                'size' => $filesize,
                'verified_at' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            error_log("Verify backup error: " . $e->getMessage());
            return [
                'valid' => false,
                'issues' => ['Verification failed: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Restore from backup
     */
    public function restoreFromBackup($backupId, $userId = null) {
        try {
            // Get backup information
            $backup = $this->getBackupById($backupId);
            if (!$backup) {
                return [
                    'success' => false,
                    'message' => 'Backup not found'
                ];
            }

            $filepath = $this->backupPath . $backup['filename'];
            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'message' => 'Backup file not found'
                ];
            }

            // Verify backup before restore
            $verification = $this->verifyBackup($filepath);
            if (!$verification['valid']) {
                return [
                    'success' => false,
                    'message' => 'Backup verification failed: ' . implode(', ', $verification['issues'])
                ];
            }

            // Create a pre-restore backup
            $preRestoreBackup = $this->createBackup(null, 'Pre-restore backup', $userId, 'pre_restore');
            if (!$preRestoreBackup['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to create pre-restore backup'
                ];
            }

            // Execute restore
            $result = $this->performDatabaseRestore($filepath);
            
            if ($result['success']) {
                // Log the restore operation
                $this->logRestoreOperation($backupId, $userId, $preRestoreBackup['backup_id']);
                
                return [
                    'success' => true,
                    'message' => 'Database restored successfully',
                    'pre_restore_backup' => $preRestoreBackup['backup_file']
                ];
            } else {
                return $result;
            }

        } catch (Exception $e) {
            error_log("Restore from backup error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Restore operation failed'
            ];
        }
    }

    /**
     * Get backup history
     */
    public function getBackupHistory($limit = 50) {
        try {
            $sql = "
                SELECT b.*, u.full_name as created_by_name
                FROM backups b
                LEFT JOIN users u ON b.created_by = u.id
                ORDER BY b.created_at DESC
                LIMIT ?
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            
            $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add file size information
            foreach ($backups as &$backup) {
                $filepath = $this->backupPath . $backup['filename'];
                $backup['file_exists'] = file_exists($filepath);
                $backup['file_size'] = $backup['file_exists'] ? filesize($filepath) : 0;
                $backup['file_size_formatted'] = $this->formatBytes($backup['file_size']);
            }
            
            return $backups;
        } catch (Exception $e) {
            error_log("Get backup history error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete backup
     */
    public function deleteBackup($backupId, $userId = null) {
        try {
            $backup = $this->getBackupById($backupId);
            if (!$backup) {
                return [
                    'success' => false,
                    'message' => 'Backup not found'
                ];
            }

            $filepath = $this->backupPath . $backup['filename'];
            
            // Delete physical file
            if (file_exists($filepath)) {
                if (!unlink($filepath)) {
                    return [
                        'success' => false,
                        'message' => 'Failed to delete backup file'
                    ];
                }
            }

            // Update database record
            $sql = "UPDATE backups SET deleted = 1, deleted_by = ?, deleted_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$userId, $backupId]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Backup deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update backup record'
                ];
            }

        } catch (Exception $e) {
            error_log("Delete backup error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete backup'
            ];
        }
    }

    /**
     * Clean up old backups
     */
    public function cleanupOldBackups() {
        try {
            $cutoffDate = date('Y-m-d', strtotime("-{$this->maxBackupAge} days"));
            
            // Get old backups
            $sql = "
                SELECT id, filename 
                FROM backups 
                WHERE created_at < ? AND deleted = 0 AND type != 'manual'
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cutoffDate]);
            $oldBackups = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $deleted = 0;
            foreach ($oldBackups as $backup) {
                $result = $this->deleteBackup($backup['id'], null);
                if ($result['success']) {
                    $deleted++;
                }
            }

            return [
                'success' => true,
                'deleted_count' => $deleted
            ];

        } catch (Exception $e) {
            error_log("Cleanup old backups error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to cleanup old backups'
            ];
        }
    }

    /**
     * Get backup statistics
     */
    public function getBackupStats() {
        try {
            $stats = [];
            
            // Total backups
            $sql = "SELECT COUNT(*) FROM backups WHERE deleted = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['total_backups'] = $stmt->fetchColumn();
            
            // Backup types
            $sql = "
                SELECT type, COUNT(*) as count 
                FROM backups 
                WHERE deleted = 0 
                GROUP BY type
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Total backup size
            $totalSize = 0;
            $backups = $this->getBackupHistory(1000); // Get all backups
            foreach ($backups as $backup) {
                if ($backup['file_exists']) {
                    $totalSize += $backup['file_size'];
                }
            }
            $stats['total_size'] = $totalSize;
            $stats['total_size_formatted'] = $this->formatBytes($totalSize);
            
            // Latest backup
            $sql = "
                SELECT created_at, type 
                FROM backups 
                WHERE deleted = 0 
                ORDER BY created_at DESC 
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $latest = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['latest_backup'] = $latest;
            
            return $stats;
        } catch (Exception $e) {
            error_log("Get backup stats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Initialize backup tracking tables
     */
    private function initializeBackupTable() {
        try {
            // Backups table
            $sql = "
                CREATE TABLE IF NOT EXISTS backups (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    description TEXT,
                    type ENUM('manual', 'scheduled', 'pre_restore', 'pre_upgrade') DEFAULT 'manual',
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    file_size BIGINT DEFAULT 0,
                    verified BOOLEAN DEFAULT FALSE,
                    verification_result JSON,
                    deleted BOOLEAN DEFAULT FALSE,
                    deleted_by INT,
                    deleted_at TIMESTAMP NULL,
                    INDEX (created_at),
                    INDEX (type),
                    INDEX (deleted),
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
                )
            ";
            $this->db->exec($sql);

            // Backup schedules table
            $sql = "
                CREATE TABLE IF NOT EXISTS backup_schedules (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
                    scheduled_time TIME NOT NULL,
                    description TEXT,
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_run TIMESTAMP NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    INDEX (frequency),
                    INDEX (is_active),
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
                )
            ";
            $this->db->exec($sql);

            // Backup restore log table
            $sql = "
                CREATE TABLE IF NOT EXISTS backup_restore_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    backup_id INT NOT NULL,
                    restored_by INT,
                    restored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    pre_restore_backup_id INT,
                    notes TEXT,
                    INDEX (restored_at),
                    FOREIGN KEY (backup_id) REFERENCES backups(id),
                    FOREIGN KEY (restored_by) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (pre_restore_backup_id) REFERENCES backups(id)
                )
            ";
            $this->db->exec($sql);

        } catch (Exception $e) {
            error_log("Initialize backup table error: " . $e->getMessage());
        }
    }

    /**
     * Perform database backup using mysqldump
     */
    private function performDatabaseBackup($filepath) {
        try {
            $command = sprintf(
                'mysqldump -h%s -u%s -p%s --single-transaction --routines --triggers %s > %s 2>&1',
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                $filepath
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                return [
                    'success' => true,
                    'message' => 'Database backup completed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Database backup failed: ' . implode(' ', $output)
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Backup command execution failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Perform database restore
     */
    private function performDatabaseRestore($filepath) {
        try {
            $command = sprintf(
                'mysql -h%s -u%s -p%s %s < %s 2>&1',
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                $filepath
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                return [
                    'success' => true,
                    'message' => 'Database restore completed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Database restore failed: ' . implode(' ', $output)
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Restore command execution failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Record backup in database
     */
    private function recordBackup($filename, $description, $userId, $type, $verified) {
        try {
            $filepath = $this->backupPath . $filename;
            $filesize = file_exists($filepath) ? filesize($filepath) : 0;

            $sql = "
                INSERT INTO backups (filename, description, type, created_by, file_size, verified)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$filename, $description, $type, $userId, $filesize, $verified]);

            return [
                'id' => $this->db->lastInsertId(),
                'success' => true
            ];

        } catch (Exception $e) {
            error_log("Record backup error: " . $e->getMessage());
            return [
                'id' => null,
                'success' => false
            ];
        }
    }

    /**
     * Check if sufficient disk space is available
     */
    private function checkDiskSpace() {
        if (!function_exists('disk_free_space')) {
            return true; // Cannot check, assume OK
        }

        $freeSpace = disk_free_space($this->backupPath);
        return $freeSpace > $this->maxBackupSize * 2; // Need at least 2x backup size free
    }

    /**
     * Get backup by ID
     */
    private function getBackupById($id) {
        try {
            $sql = "SELECT * FROM backups WHERE id = ? AND deleted = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if schedule should be executed
     */
    private function shouldExecuteSchedule($schedule) {
        $now = new DateTime();
        $lastRun = $schedule['last_run'] ? new DateTime($schedule['last_run']) : null;
        $scheduledTime = DateTime::createFromFormat('H:i', $schedule['scheduled_time']);
        
        // Set the scheduled time for today
        $todayScheduled = new DateTime();
        $todayScheduled->setTime($scheduledTime->format('H'), $scheduledTime->format('i'));

        switch ($schedule['frequency']) {
            case 'daily':
                return !$lastRun || 
                       ($lastRun->format('Y-m-d') < $now->format('Y-m-d') && $now >= $todayScheduled);
            
            case 'weekly':
                $weekAgo = (clone $now)->modify('-7 days');
                return !$lastRun || 
                       ($lastRun < $weekAgo && $now->format('w') == 0 && $now >= $todayScheduled); // Sunday
            
            case 'monthly':
                $monthAgo = (clone $now)->modify('-1 month');
                return !$lastRun || 
                       ($lastRun < $monthAgo && $now->format('j') == 1 && $now >= $todayScheduled); // 1st of month
            
            default:
                return false;
        }
    }

    /**
     * Update schedule last run time
     */
    private function updateScheduleLastRun($scheduleId) {
        try {
            $sql = "UPDATE backup_schedules SET last_run = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$scheduleId]);
        } catch (Exception $e) {
            error_log("Update schedule last run error: " . $e->getMessage());
        }
    }

    /**
     * Log restore operation
     */
    private function logRestoreOperation($backupId, $userId, $preRestoreBackupId) {
        try {
            $sql = "
                INSERT INTO backup_restore_log (backup_id, restored_by, pre_restore_backup_id)
                VALUES (?, ?, ?)
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$backupId, $userId, $preRestoreBackupId]);
        } catch (Exception $e) {
            error_log("Log restore operation error: " . $e->getMessage());
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
?>