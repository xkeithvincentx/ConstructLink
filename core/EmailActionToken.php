<?php
/**
 * ConstructLink™ Email Action Token Manager
 * Secure token system for one-click email actions
 */

class EmailActionToken {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Generate a secure token for an email action
     *
     * @param string $actionType Type of action (transfer_verify, transfer_approve, etc.)
     * @param int $relatedId ID of the entity (transfer ID, etc.)
     * @param int $userId User who should perform the action
     * @param int $expiresInHours Token expiration in hours (default 48)
     * @return array ['success' => bool, 'token' => string, 'url' => string]
     */
    public function generateToken($actionType, $relatedId, $userId, $expiresInHours = 48) {
        try {
            // Generate cryptographically secure random token
            $token = bin2hex(random_bytes(32)); // 64 character hex string

            // Calculate expiration
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresInHours} hours"));

            // Insert token into database
            $sql = "INSERT INTO email_action_tokens
                    (token, action_type, related_id, user_id, expires_at)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$token, $actionType, $relatedId, $userId, $expiresAt]);

            // Generate action URL
            $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $url = "{$baseUrl}/?route=email-action&token={$token}";

            error_log("EmailActionToken: Generated token for {$actionType} (related_id: {$relatedId}, user_id: {$userId})");

            return [
                'success' => true,
                'token' => $token,
                'url' => $url,
                'expires_at' => $expiresAt
            ];

        } catch (Exception $e) {
            error_log("EmailActionToken::generateToken error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate token'
            ];
        }
    }

    /**
     * Validate and retrieve token information
     *
     * @param string $token The token to validate
     * @return array|null Token data or null if invalid
     */
    public function validateToken($token) {
        try {
            $sql = "SELECT * FROM email_action_tokens
                    WHERE token = ?
                      AND used_at IS NULL
                      AND expires_at > NOW()";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tokenData) {
                error_log("EmailActionToken: Invalid or expired token");
                return null;
            }

            return $tokenData;

        } catch (Exception $e) {
            error_log("EmailActionToken::validateToken error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mark token as used
     *
     * @param string $token The token to mark as used
     * @param string|null $ipAddress IP address of the user
     * @return bool Success
     */
    public function markTokenAsUsed($token, $ipAddress = null) {
        try {
            $sql = "UPDATE email_action_tokens
                    SET used_at = NOW(),
                        ip_address = ?
                    WHERE token = ?";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$ipAddress, $token]);

        } catch (Exception $e) {
            error_log("EmailActionToken::markTokenAsUsed error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Invalidate (mark as used) all tokens for a specific action
     *
     * @param string $actionType Action type
     * @param int $relatedId Related entity ID
     * @return bool Success
     */
    public function invalidateTokensForAction($actionType, $relatedId) {
        try {
            $sql = "UPDATE email_action_tokens
                    SET used_at = NOW()
                    WHERE action_type = ?
                      AND related_id = ?
                      AND used_at IS NULL";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$actionType, $relatedId]);

        } catch (Exception $e) {
            error_log("EmailActionToken::invalidateTokensForAction error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up expired tokens (older than 7 days)
     * Should be run as a cron job or scheduled task
     *
     * @return int Number of tokens deleted
     */
    public function cleanupExpiredTokens() {
        try {
            $sql = "DELETE FROM email_action_tokens
                    WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            return $stmt->rowCount();

        } catch (Exception $e) {
            error_log("EmailActionToken::cleanupExpiredTokens error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get user information from token
     *
     * @param string $token The token
     * @return array|null User data or null
     */
    public function getUserFromToken($token) {
        try {
            $sql = "SELECT u.*
                    FROM email_action_tokens eat
                    JOIN users u ON eat.user_id = u.id
                    WHERE eat.token = ?
                      AND eat.used_at IS NULL
                      AND eat.expires_at > NOW()";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$token]);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("EmailActionToken::getUserFromToken error: " . $e->getMessage());
            return null;
        }
    }
}
?>
