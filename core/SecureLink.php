<?php
/**
 * ConstructLink™ SecureLink™ QR Code System
 * HMAC-based QR code generation and validation for asset authentication
 */

class SecureLink {
    private static $instance = null;
    private $hmacSecret;
    private $algorithm;
    
    private function __construct() {
        $this->hmacSecret = HMAC_SECRET_KEY;
        $this->algorithm = HMAC_ALGORITHM;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Generate SecureLink™ QR code for an asset
     */
    public function generateAssetQR($assetId, $assetRef, $additionalData = []) {
        try {
            // Create payload
            $payload = [
                'asset_id' => $assetId,
                'asset_ref' => $assetRef,
                'timestamp' => time(),
                'version' => '1.0',
                'system' => 'ConstructLink'
            ];
            
            // Add additional data if provided
            if (!empty($additionalData)) {
                $payload = array_merge($payload, $additionalData);
            }
            
            // Generate HMAC signature
            $dataString = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $signature = hash_hmac($this->algorithm, $dataString, $this->hmacSecret);
            
            // Create final QR data
            $qrData = [
                'data' => $payload,
                'signature' => $signature
            ];
            
            $qrString = base64_encode(json_encode($qrData));
            
            // Generate QR code image
            $qrCodePath = $this->generateQRImage($qrString, $assetRef);
            
            // Log QR generation
            $this->logQRGeneration($assetId, $qrString, $qrCodePath);
            
            return [
                'success' => true,
                'qr_data' => $qrString,
                'qr_path' => $qrCodePath,
                'validation_url' => $this->getValidationURL($qrString)
            ];
            
        } catch (Exception $e) {
            error_log("QR Generation Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate QR code'
            ];
        }
    }
    
    /**
     * Validate SecureLink™ QR code
     */
    public function validateQR($qrData, $expectedAssetId = null) {
        try {
            // Decode QR data
            $decodedData = json_decode(base64_decode($qrData), true);
            
            if (!$decodedData || !isset($decodedData['data']) || !isset($decodedData['signature'])) {
                return $this->createValidationResult(false, 'Invalid QR code format');
            }
            
            $payload = $decodedData['data'];
            $signature = $decodedData['signature'];
            
            // Verify HMAC signature
            $dataString = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $expectedSignature = hash_hmac($this->algorithm, $dataString, $this->hmacSecret);
            
            if (!hash_equals($expectedSignature, $signature)) {
                $this->logQRValidation($payload['asset_id'] ?? null, $qrData, 'invalid', 'Invalid signature');
                return $this->createValidationResult(false, 'Invalid QR code signature');
            }
            
            // Check required fields
            if (!isset($payload['asset_id']) || !isset($payload['asset_ref']) || !isset($payload['timestamp'])) {
                return $this->createValidationResult(false, 'Missing required QR data fields');
            }
            
            // Check if asset ID matches expected (if provided)
            if ($expectedAssetId && $payload['asset_id'] != $expectedAssetId) {
                return $this->createValidationResult(false, 'Asset ID mismatch');
            }
            
            // Check timestamp (QR codes expire after 1 year)
            $qrAge = time() - $payload['timestamp'];
            $maxAge = 365 * 24 * 3600; // 1 year
            
            if ($qrAge > $maxAge) {
                $this->logQRValidation($payload['asset_id'], $qrData, 'expired', 'QR code expired');
                return $this->createValidationResult(false, 'QR code has expired');
            }
            
            // Verify asset exists in database
            $assetInfo = $this->getAssetInfo($payload['asset_id']);
            if (!$assetInfo) {
                return $this->createValidationResult(false, 'Asset not found in database');
            }
            
            // Verify asset reference matches
            if ($assetInfo['ref'] !== $payload['asset_ref']) {
                return $this->createValidationResult(false, 'Asset reference mismatch');
            }
            
            // Log successful validation
            $this->logQRValidation($payload['asset_id'], $qrData, 'valid', 'QR code validated successfully');
            
            return $this->createValidationResult(true, 'QR code is valid', [
                'asset_id' => $payload['asset_id'],
                'asset_ref' => $payload['asset_ref'],
                'asset_info' => $assetInfo,
                'qr_generated' => date('Y-m-d H:i:s', $payload['timestamp']),
                'qr_age_days' => round($qrAge / (24 * 3600))
            ]);
            
        } catch (Exception $e) {
            error_log("QR Validation Error: " . $e->getMessage());
            return $this->createValidationResult(false, 'QR validation failed due to system error');
        }
    }
    
    /**
     * Generate QR code image using a simple QR library or external service
     */
    private function generateQRImage($qrData, $assetRef) {
        $qrDir = APP_ROOT . '/assets/qr/';
        
        // Create QR directory if it doesn't exist
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0755, true);
        }
        
        $filename = 'qr_' . $assetRef . '_' . time() . '.png';
        $filepath = $qrDir . $filename;
        
        // For development, create a simple placeholder image
        // In production, integrate with a proper QR library like endroid/qr-code
        if (ENV_MOCK_QR_GENERATION) {
            $this->createMockQRImage($filepath, $qrData, $assetRef);
        } else {
            $this->createRealQRImage($filepath, $qrData);
        }
        
        return '/assets/qr/' . $filename;
    }
    
    /**
     * Create a mock QR image for development
     */
    private function createMockQRImage($filepath, $qrData, $assetRef) {
        $width = QR_CODE_SIZE;
        $height = QR_CODE_SIZE;
        
        $image = imagecreate($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $blue = imagecolorallocate($image, 0, 100, 200);
        
        // Fill background
        imagefill($image, 0, 0, $white);
        
        // Draw border
        imagerectangle($image, 0, 0, $width-1, $height-1, $black);
        
        // Add text
        $text1 = "SecureLink QR";
        $text2 = "Asset: " . $assetRef;
        $text3 = "Scan to validate";
        
        imagestring($image, 3, 10, 20, $text1, $blue);
        imagestring($image, 2, 10, 50, $text2, $black);
        imagestring($image, 1, 10, 80, $text3, $black);
        
        // Draw some QR-like pattern
        for ($i = 0; $i < 20; $i++) {
            for ($j = 0; $j < 20; $j++) {
                if (($i + $j) % 3 == 0) {
                    imagefilledrectangle($image, 
                        10 + $i * 8, 100 + $j * 4, 
                        16 + $i * 8, 103 + $j * 4, 
                        $black
                    );
                }
            }
        }
        
        imagepng($image, $filepath);
        imagedestroy($image);
    }
    
    /**
     * Create real QR image using QR library
     */
    private function createRealQRImage($filepath, $qrData) {
        // This would integrate with a real QR library like endroid/qr-code
        // For now, create a placeholder
        $this->createMockQRImage($filepath, $qrData, 'REAL');
    }
    
    /**
     * Get validation URL for QR code
     */
    private function getValidationURL($qrData) {
        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        return $baseUrl . '/api/validate-qr?data=' . urlencode($qrData);
    }
    
    /**
     * Get asset information from database
     */
    private function getAssetInfo($assetId) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT a.*, c.name as category_name, p.name as project_name, 
                       m.name as maker_name, v.name as vendor_name
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                WHERE a.id = ?
            ");
            $stmt->execute([$assetId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Asset info retrieval error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Log QR code generation
     */
    private function logQRGeneration($assetId, $qrData, $qrPath) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, ip_address, user_agent)
                VALUES (?, 'qr_generated', 'assets', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $assetId,
                json_encode(['qr_path' => $qrPath]),
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("QR generation log error: " . $e->getMessage());
        }
    }
    
    /**
     * Log QR code validation
     */
    private function logQRValidation($assetId, $qrData, $result, $message) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO qr_validations (asset_id, qr_code, validation_result, scanned_by, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $assetId,
                substr($qrData, 0, 255), // Truncate if too long
                $result,
                $_SESSION['user_id'] ?? null,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("QR validation log error: " . $e->getMessage());
        }
    }
    
    /**
     * Create validation result array
     */
    private function createValidationResult($isValid, $message, $data = []) {
        return [
            'valid' => $isValid,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $data
        ];
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Regenerate QR code for an asset
     */
    public function regenerateAssetQR($assetId) {
        try {
            $assetInfo = $this->getAssetInfo($assetId);
            if (!$assetInfo) {
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            // Delete old QR file if exists
            if ($assetInfo['qr_code_path']) {
                $oldPath = APP_ROOT . $assetInfo['qr_code_path'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            // Generate new QR code
            $result = $this->generateAssetQR($assetId, $assetInfo['ref']);
            
            if ($result['success']) {
                // Update asset with new QR path
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("UPDATE inventory_items SET qr_code_path = ? WHERE id = ?");
                $stmt->execute([$result['qr_path'], $assetId]);
                
                // Log regeneration
                $stmt = $db->prepare("
                    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address)
                    VALUES (?, 'qr_regenerated', 'assets', ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'] ?? null,
                    $assetId,
                    json_encode(['old_qr_path' => $assetInfo['qr_code_path']]),
                    json_encode(['new_qr_path' => $result['qr_path']]),
                    $this->getClientIP()
                ]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("QR regeneration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to regenerate QR code'];
        }
    }
    
    /**
     * Batch validate multiple QR codes
     */
    public function batchValidateQR($qrDataArray) {
        $results = [];
        
        foreach ($qrDataArray as $index => $qrData) {
            $results[$index] = $this->validateQR($qrData);
        }
        
        return $results;
    }
    
    /**
     * Get QR validation statistics
     */
    public function getValidationStats($assetId = null, $dateFrom = null, $dateTo = null) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($assetId) {
                $whereClause .= " AND asset_id = ?";
                $params[] = $assetId;
            }
            
            if ($dateFrom) {
                $whereClause .= " AND created_at >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereClause .= " AND created_at <= ?";
                $params[] = $dateTo;
            }
            
            $stmt = $db->prepare("
                SELECT 
                    validation_result,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM qr_validations 
                $whereClause
                GROUP BY validation_result, DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("QR stats error: " . $e->getMessage());
            return [];
        }
    }
}
?>
