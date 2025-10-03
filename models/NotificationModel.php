<?php
/**
 * ConstructLink™ Notification Model
 * Handles notification data and operations
 */

class NotificationModel extends BaseModel {
    protected $table = 'notifications';
    protected $fillable = [
        'user_id', 'title', 'message', 'type', 'url',
        'is_read', 'related_type', 'related_id', 'read_at'
    ];

    /**
     * Create a new notification
     */
    public function createNotification($userId, $title, $message, $type = 'info', $url = null, $relatedType = null, $relatedId = null) {
        try {
            $data = [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'url' => $url,
                'related_type' => $relatedType,
                'related_id' => $relatedId
            ];

            return $this->create($data);
        } catch (Exception $e) {
            error_log("NotificationModel::createNotification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notifications for a user
     */
    public function getUserNotifications($userId, $limit = 20, $offset = 0, $unreadOnly = false) {
        try {
            $sql = "
                SELECT * FROM {$this->table}
                WHERE user_id = ?
            ";

            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }

            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("NotificationModel::getUserNotifications error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unread notification count for user
     */
    public function getUnreadCount($userId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ? AND is_read = 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("NotificationModel::getUnreadCount error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId) {
        try {
            return $this->update($notificationId, [
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("NotificationModel::markAsRead error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId) {
        try {
            $sql = "UPDATE {$this->table} SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("NotificationModel::markAllAsRead error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete old read notifications (cleanup)
     */
    public function deleteOldNotifications($daysOld = 30) {
        try {
            $sql = "DELETE FROM {$this->table}
                    WHERE is_read = 1
                    AND read_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$daysOld]);
        } catch (Exception $e) {
            error_log("NotificationModel::deleteOldNotifications error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notifications related to a specific entity
     */
    public function getRelatedNotifications($relatedType, $relatedId) {
        try {
            $sql = "SELECT * FROM {$this->table}
                    WHERE related_type = ? AND related_id = ?
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$relatedType, $relatedId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("NotificationModel::getRelatedNotifications error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Notify multiple users
     */
    public function notifyMultipleUsers(array $userIds, $title, $message, $type = 'info', $url = null, $relatedType = null, $relatedId = null) {
        try {
            $this->beginTransaction();

            foreach ($userIds as $userId) {
                $this->createNotification($userId, $title, $message, $type, $url, $relatedType, $relatedId);
            }

            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            error_log("NotificationModel::notifyMultipleUsers error: " . $e->getMessage());
            return false;
        }
    }
}
?>
