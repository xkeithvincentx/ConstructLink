<?php
/**
 * ConstructLink™ QR Code Validation API
 * Validates SecureLink™ QR codes
 */

// Include application bootstrap
require_once __DIR__ . '/../index.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Get QR data from request
    $qrData = $_GET['data'] ?? $_POST['data'] ?? '';
    
    if (empty($qrData)) {
        http_response_code(400);
        echo json_encode([
            'valid' => false,
            'message' => 'QR data is required',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Validate QR code using SecureLink
    $secureLink = SecureLink::getInstance();
    $result = $secureLink->validateQR($qrData);
    
    // Return validation result
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("QR validation API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'valid' => false,
        'message' => 'QR validation failed due to server error',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
