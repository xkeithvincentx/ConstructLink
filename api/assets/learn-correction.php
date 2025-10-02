<?php
/**
 * Learn Correction API
 * Allows users to teach the system about corrections
 */

require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../models/BaseModel.php';
require_once '../../core/AssetStandardizer.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Initialize authentication
    $auth = Auth::getInstance();
    
    // Check if user is authenticated
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }
    
    $currentUser = $auth->getCurrentUser();
    $userId = $currentUser['id'];
    
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    // Validate required fields
    $original = trim($data['original'] ?? '');
    $corrected = trim($data['corrected'] ?? '');
    $context = $data['context'] ?? 'tool_name';
    
    if (empty($original) || empty($corrected)) {
        echo json_encode([
            'success' => false,
            'message' => 'Both original and corrected text are required'
        ]);
        exit;
    }
    
    if ($original === $corrected) {
        echo json_encode([
            'success' => false,
            'message' => 'Original and corrected text must be different'
        ]);
        exit;
    }
    
    // Validate context
    $allowedContexts = ['tool_name', 'brand', 'material', 'category', 'specification'];
    if (!in_array($context, $allowedContexts)) {
        $context = 'tool_name';
    }
    
    // Initialize standardizer
    $standardizer = AssetStandardizer::getInstance();
    
    // Learn the correction
    $success = $standardizer->learnCorrection($original, $corrected, $userId, $context);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Correction learned successfully',
            'data' => [
                'original' => $original,
                'corrected' => $corrected,
                'context' => $context,
                'learned_by' => $currentUser['full_name'] ?? 'User'
            ]
        ]);
        
        // Log the activity
        if (function_exists('logActivity')) {
            logActivity(
                'spelling_correction_learned',
                "User taught system: '$original' -> '$corrected' (context: $context)",
                'asset_spelling_corrections',
                null,
                $userId
            );
        }
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to learn correction'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Learn correction API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process correction',
        'error' => $e->getMessage()
    ]);
}
?>