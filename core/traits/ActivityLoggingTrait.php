<?php
/**
 * ConstructLink™ Activity Logging Trait
 *
 * Provides shared activity logging functionality for models.
 * Eliminates duplicate logActivity() methods across models.
 *
 * Usage:
 *   class YourModel extends BaseModel {
 *       use ActivityLoggingTrait;
 *
 *       public function someMethod() {
 *           $this->logActivity('action', 'Description', 'table_name', $recordId);
 *       }
 *   }
 *
 * @package ConstructLink
 * @version 1.0.0
 */

trait ActivityLoggingTrait {
    /**
     * Log activity to activity_logs table
     *
     * @param string $action Action performed (e.g., 'create', 'update', 'delete', 'verify_batch')
     * @param string $description Human-readable description of the action
     * @param string $table Table name affected
     * @param int $recordId Record ID affected
     * @return void
     */
    private function logActivity($action, $description, $table, $recordId) {
        try {
            $auth = Auth::getInstance();
            $user = $auth->getCurrentUser();

            $sql = "INSERT INTO activity_logs (user_id, action, description, table_name, record_id, ip_address, user_agent, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $user['id'] ?? null,
                $action,
                $description,
                $table,
                $recordId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Log error but don't throw exception to avoid disrupting main operation
            error_log("Activity logging error: " . $e->getMessage());
        }
    }
}
