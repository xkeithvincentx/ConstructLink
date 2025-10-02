<?php
/**
 * ConstructLink™ Install Model
 * Handles installation-specific database operations
 */

class InstallModel extends BaseModel {
    
    /**
     * Check if all required tables exist
     */
    public function checkRequiredTables() {
        $requiredTables = [
            'users', 'roles', 'user_sessions', 'projects', 'categories', 
            'assets', 'withdrawals', 'releases', 'transfers', 'borrowed_tools',
            'incidents', 'maintenance', 'clients', 'vendors', 'makers',
            'audit_logs', 'system_settings'
        ];
        
        $missingTables = [];
        $existingTables = [];
        
        foreach ($requiredTables as $table) {
            try {
                $stmt = $this->db->query("SELECT 1 FROM {$table} LIMIT 1");
                if ($stmt !== false) {
                    $existingTables[] = $table;
                } else {
                    $missingTables[] = $table;
                }
            } catch (Exception $e) {
                $missingTables[] = $table;
            }
        }
        
        return [
            'required' => $requiredTables,
            'existing' => $existingTables,
            'missing' => $missingTables,
            'all_exist' => empty($missingTables)
        ];
    }
    
    /**
     * Get database connection status
     */
    public function getDatabaseStatus() {
        try {
            $connection = $this->db->getConnection();
            $stmt = $connection->query("SELECT VERSION() as version, DATABASE() as database_name");
            $result = $stmt->fetch();
            
            return [
                'connected' => true,
                'version' => $result['version'] ?? 'Unknown',
                'database' => $result['database_name'] ?? 'Unknown',
                'host' => DB_HOST,
                'user' => DB_USER
            ];
        } catch (Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify default admin user exists
     */
    public function checkDefaultAdmin() {
        try {
            $stmt = $this->db->prepare("SELECT id, username FROM users WHERE username = 'admin' LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch();
            
            return [
                'exists' => !empty($admin),
                'admin' => $admin
            ];
        } catch (Exception $e) {
            return [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get installation statistics
     */
    public function getInstallationStats() {
        $stats = [
            'tables' => $this->checkRequiredTables(),
            'database' => $this->getDatabaseStatus(),
            'admin' => $this->checkDefaultAdmin()
        ];
        
        // Count records in key tables
        $tableCounts = [];
        $keyTables = ['users', 'roles', 'projects', 'categories', 'assets'];
        
        foreach ($keyTables as $table) {
            try {
                $stmt = $this->db->query("SELECT COUNT(*) as count FROM {$table}");
                $result = $stmt->fetch();
                $tableCounts[$table] = $result['count'] ?? 0;
            } catch (Exception $e) {
                $tableCounts[$table] = 'Error';
            }
        }
        
        $stats['record_counts'] = $tableCounts;
        
        return $stats;
    }
    
    /**
     * Create installation marker
     */
    public function createInstallationMarker() {
        try {
            $installFile = APP_ROOT . '/.installed';
            $installData = [
                'installed_at' => date('Y-m-d H:i:s'),
                'version' => APP_VERSION ?? '1.0.0',
                'database_host' => DB_HOST,
                'database_name' => DB_NAME
            ];
            
            return file_put_contents($installFile, json_encode($installData, JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            error_log("Installation marker creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check installation marker
     */
    public function checkInstallationMarker() {
        $installFile = APP_ROOT . '/.installed';
        
        if (!file_exists($installFile)) {
            return [
                'exists' => false,
                'data' => null
            ];
        }
        
        try {
            $content = file_get_contents($installFile);
            $data = json_decode($content, true);
            
            return [
                'exists' => true,
                'data' => $data,
                'file_path' => $installFile
            ];
        } catch (Exception $e) {
            return [
                'exists' => true,
                'data' => null,
                'error' => 'Invalid installation marker file'
            ];
        }
    }
}
?>
