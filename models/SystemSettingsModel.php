<?php
/**
 * ConstructLink™ Enhanced System Settings Model
 * Handles comprehensive system configuration settings with advanced features
 */

class SystemSettingsModel extends BaseModel {
    protected $table = 'system_settings';
    protected $fillable = ['setting_key', 'setting_value', 'description', 'is_public', 'updated_by'];

    // Cache for frequently accessed settings
    private static $settingsCache = [];
    private static $cacheExpiry = 300; // 5 minutes

    /**
     * Get a setting value by key with caching
     */
    public function getSetting($key, $default = null) {
        try {
            // Check cache first
            if (isset(self::$settingsCache[$key]) && 
                self::$settingsCache[$key]['expiry'] > time()) {
                return self::$settingsCache[$key]['value'];
            }

            $sql = "SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$key]);
            $result = $stmt->fetchColumn();
            
            $value = $result !== false ? $result : $default;
            
            // Cache the result
            self::$settingsCache[$key] = [
                'value' => $value,
                'expiry' => time() + self::$cacheExpiry
            ];
            
            return $value;
        } catch (Exception $e) {
            error_log("Get setting error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Get setting value with type casting
     */
    public function getSettingValue($key, $default = null, $type = 'string') {
        $value = $this->getSetting($key, $default);
        
        switch ($type) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'array':
                return is_string($value) ? json_decode($value, true) : $value;
            default:
                return $value;
        }
    }

    /**
     * Update a setting value with validation and audit
     */
    public function updateSetting($key, $value, $description = null, $isPublic = null, $userId = null) {
        try {
            // Validate setting
            if (!$this->validateSetting($key, $value)) {
                return [
                    'success' => false,
                    'message' => 'Invalid setting value for key: ' . $key
                ];
            }

            // Get old value for audit
            $oldValue = $this->getSetting($key);

            // Prepare update data
            $updateData = [
                'setting_value' => $value,
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($description !== null) {
                $updateData['description'] = $description;
            }

            if ($isPublic !== null) {
                $updateData['is_public'] = $isPublic ? 1 : 0;
            }

            // Build SQL dynamically
            $setParts = [];
            $params = [];
            foreach ($updateData as $field => $val) {
                $setParts[] = "$field = ?";
                $params[] = $val;
            }
            $params[] = $key;

            $sql = "UPDATE system_settings SET " . implode(', ', $setParts) . " WHERE setting_key = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                // Clear cache for this key
                unset(self::$settingsCache[$key]);
                
                // Log the change
                $this->logSettingChange($key, $oldValue, $value, $userId);
                
                return [
                    'success' => true,
                    'message' => 'Setting updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update setting'
                ];
            }
        } catch (Exception $e) {
            error_log("Update setting error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }

    /**
     * Update multiple settings in one transaction
     */
    public function updateMultipleSettings($settings, $userId = null) {
        try {
            $this->db->beginTransaction();
            
            $updated = 0;
            $errors = [];
            
            foreach ($settings as $key => $data) {
                $value = $data['value'] ?? $data;
                $isPublic = $data['is_public'] ?? null;
                $description = $data['description'] ?? null;
                
                $result = $this->updateSetting($key, $value, $description, $isPublic, $userId);
                
                if ($result['success']) {
                    $updated++;
                } else {
                    $errors[] = "Failed to update {$key}: " . $result['message'];
                }
            }
            
            if (empty($errors)) {
                $this->db->commit();
                return [
                    'success' => true,
                    'message' => "Successfully updated {$updated} settings"
                ];
            } else {
                $this->db->rollback();
                return [
                    'success' => false,
                    'message' => 'Some settings failed to update',
                    'errors' => $errors
                ];
            }
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Update multiple settings error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Transaction failed'
            ];
        }
    }

    /**
     * Get all public settings with caching
     */
    public function getPublicSettings() {
        try {
            $cacheKey = 'public_settings';
            
            if (isset(self::$settingsCache[$cacheKey]) && 
                self::$settingsCache[$cacheKey]['expiry'] > time()) {
                return self::$settingsCache[$cacheKey]['value'];
            }

            $sql = "SELECT setting_key, setting_value FROM system_settings WHERE is_public = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            // Cache the result
            self::$settingsCache[$cacheKey] = [
                'value' => $settings,
                'expiry' => time() + self::$cacheExpiry
            ];
            
            return $settings;
        } catch (Exception $e) {
            error_log("Get public settings error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all settings (admin only) with enhanced information
     */
    public function getAllSettings() {
        try {
            $sql = "
                SELECT ss.*, u.full_name as updated_by_name 
                FROM system_settings ss 
                LEFT JOIN users u ON ss.updated_by = u.id 
                ORDER BY ss.setting_key ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get all settings error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get system statistics for admin dashboard
     */
    public function getSystemStats() {
        try {
            $stats = [];
            
            // Database statistics
            $stats['database'] = $this->getDatabaseStats();
            
            // User statistics
            $stats['users'] = $this->getUserStats();
            
            // Asset statistics
            $stats['assets'] = $this->getAssetStats();
            
            // System information
            $stats['system'] = $this->getSystemInfo();
            
            return $stats;
        } catch (Exception $e) {
            error_log("Get system stats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate setting value based on key
     */
    private function validateSetting($key, $value) {
        $validationRules = [
            'maintenance_mode' => ['type' => 'boolean'],
            'session_timeout' => ['type' => 'integer', 'min' => 60, 'max' => 86400],
            'vat_rate' => ['type' => 'float', 'min' => 0, 'max' => 100],
            'ewt_rate' => ['type' => 'float', 'min' => 0, 'max' => 100],
            'low_value_threshold' => ['type' => 'float', 'min' => 0],
            'email_notifications' => ['type' => 'boolean'],
            'qr_code_enabled' => ['type' => 'boolean'],
        ];

        if (!isset($validationRules[$key])) {
            return true; // No specific validation rule
        }

        $rule = $validationRules[$key];
        
        switch ($rule['type']) {
            case 'boolean':
                return in_array($value, ['0', '1', 0, 1, true, false], true);
            case 'integer':
                $intVal = (int)$value;
                return (string)$intVal === (string)$value &&
                       $intVal >= ($rule['min'] ?? PHP_INT_MIN) &&
                       $intVal <= ($rule['max'] ?? PHP_INT_MAX);
            case 'float':
                $floatVal = (float)$value;
                return is_numeric($value) &&
                       $floatVal >= ($rule['min'] ?? -PHP_FLOAT_MAX) &&
                       $floatVal <= ($rule['max'] ?? PHP_FLOAT_MAX);
            default:
                return true;
        }
    }

    /**
     * Log setting changes for audit
     */
    private function logSettingChange($key, $oldValue, $newValue, $userId) {
        try {
            $sql = "
                INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, description, created_at)
                VALUES (?, 'UPDATE', 'system_settings', NULL, ?, ?, ?, NOW())
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $userId,
                json_encode(['setting_key' => $key, 'setting_value' => $oldValue]),
                json_encode(['setting_key' => $key, 'setting_value' => $newValue]),
                "System setting '{$key}' changed from '{$oldValue}' to '{$newValue}'"
            ]);
        } catch (Exception $e) {
            error_log("Log setting change error: " . $e->getMessage());
        }
    }

    /**
     * Get database statistics
     */
    private function getDatabaseStats() {
        try {
            $stats = [];
            
            // Database size
            $sql = "
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['size_mb'] = $stmt->fetchColumn();
            
            // Table counts
            $tables = ['users', 'assets', 'projects', 'requests', 'procurement_orders', 
                      'withdrawals', 'transfers', 'incidents', 'maintenance'];
            
            foreach ($tables as $table) {
                $sql = "SELECT COUNT(*) FROM `{$table}`";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $stats['tables'][$table] = $stmt->fetchColumn();
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Get database stats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user statistics
     */
    private function getUserStats() {
        try {
            $stats = [];
            
            // Total users
            $sql = "SELECT COUNT(*) FROM users";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['total'] = $stmt->fetchColumn();
            
            // Active users
            $sql = "SELECT COUNT(*) FROM users WHERE is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['active'] = $stmt->fetchColumn();
            
            // Recent logins (30 days)
            $sql = "SELECT COUNT(DISTINCT user_id) FROM user_login_logs WHERE login_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['recent_logins'] = $stmt->fetchColumn();
            
            // Users by role
            $sql = "
                SELECT r.name, COUNT(u.id) as count
                FROM roles r
                LEFT JOIN users u ON r.id = u.role_id AND u.is_active = 1
                GROUP BY r.id, r.name
                ORDER BY count DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            error_log("Get user stats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get asset statistics
     */
    private function getAssetStats() {
        try {
            $stats = [];
            
            // Total assets
            $sql = "SELECT COUNT(*) FROM assets";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['total'] = $stmt->fetchColumn();
            
            // Total value
            $sql = "SELECT SUM(acquisition_cost) FROM assets WHERE acquisition_cost IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['total_value'] = $stmt->fetchColumn() ?: 0;
            
            // Assets by status
            $sql = "
                SELECT status, COUNT(*) as count
                FROM assets
                GROUP BY status
                ORDER BY count DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            error_log("Get asset stats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get system information
     */
    private function getSystemInfo() {
        try {
            $stats = [];
            
            // PHP version
            $stats['php_version'] = PHP_VERSION;
            
            // MySQL version
            $sql = "SELECT VERSION()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['mysql_version'] = $stmt->fetchColumn();
            
            // Server software
            $stats['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
            
            // Server name
            $stats['server_name'] = $_SERVER['SERVER_NAME'] ?? 'Unknown';
            
            // Memory usage
            $stats['memory_usage_mb'] = round(memory_get_usage(true) / 1024 / 1024, 2);
            
            // Disk space
            if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
                $free = disk_free_space(APP_ROOT);
                $total = disk_total_space(APP_ROOT);
                if ($free !== false && $total !== false) {
                    $stats['disk_free_gb'] = round($free / 1024 / 1024 / 1024, 2);
                    $stats['disk_total_gb'] = round($total / 1024 / 1024 / 1024, 2);
                    $stats['disk_used_percent'] = round((($total - $free) / $total) * 100, 1);
                }
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Get system info error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear settings cache
     */
    public function clearCache() {
        self::$settingsCache = [];
    }

    /**
     * Reset settings to defaults
     */
    public function resetToDefaults($userId = null) {
        try {
            $this->db->beginTransaction();
            
            $defaultSettings = [
                'maintenance_mode' => '0',
                'asset_ref_prefix' => 'CL',
                'qr_code_enabled' => '1',
                'email_notifications' => '0',
                'session_timeout' => '28800',
                'po_prefix' => 'PO',
                'request_prefix' => 'REQ',
                'auto_approve_low_value' => '0',
                'low_value_threshold' => '5000.00',
                'require_justification_above' => '10000.00',
                'default_vat_rate' => '12.00',
                'default_ewt_rate' => '2.00'
            ];
            
            foreach ($defaultSettings as $key => $value) {
                $this->updateSetting($key, $value, null, null, $userId);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Settings reset to defaults successfully'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Reset to defaults error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to reset settings'
            ];
        }
    }
}
