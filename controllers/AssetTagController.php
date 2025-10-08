<?php
/**
 * ConstructLink™ Asset Tag Controller
 * Handles QR tag generation, printing, and management
 */

class AssetTagController {
    private $auth;
    private $assetModel;
    private $tagGenerator;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->assetModel = new AssetModel();
        
        // Initialize QR tag generator with error handling
        try {
            $this->tagGenerator = new QRTagGenerator();
        } catch (Exception $e) {
            error_log("QRTagGenerator initialization error: " . $e->getMessage());
            $this->tagGenerator = null;
        }
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Tag management dashboard
     */
    public function tagManagement() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions - only certain roles can manage tags
        if (!in_array($userRole, ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            // Apply project-based access control for specific roles
            $projectFilter = null;
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                if (!empty($currentUser['current_project_id'])) {
                    $projectFilter = $currentUser['current_project_id'];
                } else {
                    // User has no project assigned, show empty results
                    $tagStats = [
                        'total_assets' => 0,
                        'qr_generated' => 0,
                        'needs_printing' => 0,
                        'needs_application' => 0,
                        'needs_verification' => 0,
                        'fully_tagged' => 0
                    ];
                    $assets = [];
                    $pagination = [
                        'current_page' => 1,
                        'per_page' => 20,
                        'total_count' => 0,
                        'total_pages' => 0
                    ];
                    $projects = [];
                    include APP_ROOT . '/views/assets/tag_management.php';
                    return;
                }
            }
            
            // Get tag statistics with project filtering
            $tagStats = $this->getTagStatistics($projectFilter);
            
            // Get filtered assets
            $page = (int)($_GET['page'] ?? 1);
            $perPage = 20;
            
            $filters = [];
            if (!empty($_GET['status'])) {
                switch ($_GET['status']) {
                    case 'needs_qr':
                        $filters['missing_qr'] = true;
                        break;
                    case 'needs_printing':
                        $filters['needs_printing'] = true;
                        break;
                    case 'needs_application':
                        $filters['needs_application'] = true;
                        break;
                    case 'needs_verification':
                        $filters['needs_verification'] = true;
                        break;
                    case 'fully_tagged':
                        $filters['fully_tagged'] = true;
                        break;
                }
            }
            
            // Apply project filtering for restricted roles
            if ($projectFilter) {
                $filters['project_id'] = $projectFilter;
            } elseif (!empty($_GET['project_id'])) {
                $filters['project_id'] = $_GET['project_id'];
            }
            
            if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
            
            $result = $this->getAssetsWithTagStatus($filters, $page, $perPage);
            $assets = $result['assets'];
            $pagination = $result['pagination'];
            
            // Get projects for filter dropdown based on role
            $projectModel = new ProjectModel();
            if ($projectFilter) {
                // For restricted roles, only show their assigned project
                $projects = [];
                if (!empty($currentUser['current_project_id'])) {
                    $userProject = $projectModel->find($currentUser['current_project_id']);
                    if ($userProject) {
                        $projects = [$userProject];
                    }
                }
            } else {
                // For admin roles, show all active projects
                $projects = $projectModel->getActiveProjects();
            }
            
            include APP_ROOT . '/views/assets/tag_management.php';
            
        } catch (Exception $e) {
            error_log("Tag management error: " . $e->getMessage());
            $errors[] = 'Failed to load tag management dashboard';
            include APP_ROOT . '/views/assets/tag_management.php';
        }
    }
    
    /**
     * Print single QR tag (HTML template for browser printing)
     */
    public function printTag() {
        $assetId = (int)($_GET['id'] ?? 0);
        
        if (!$assetId) {
            http_response_code(404);
            echo 'Asset not found';
            return;
        }
        
        try {
            $asset = $this->assetModel->getAssetWithDetails($assetId);
            
            if (!$asset) {
                http_response_code(404);
                echo 'Asset not found';
                return;
            }
            
            // Check project access for restricted roles
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                if (empty($currentUser['current_project_id']) || $asset['project_id'] != $currentUser['current_project_id']) {
                    http_response_code(403);
                    echo 'Access denied - Asset not in your assigned project';
                    return;
                }
            }
            
            // Check if asset has QR code
            if (empty($asset['qr_code'])) {
                // Generate QR code if missing
                $this->generateQRCode($assetId);
                $asset = $this->assetModel->getAssetWithDetails($assetId);
            }
            
            // Determine tag size based on asset category
            $tagSize = $this->determineTagSize($asset);
            
            // Mark as printed
            $this->markTagAsPrinted($assetId);
            
            // Include the printable tag template
            include APP_ROOT . '/views/assets/print_tag.php';
            
        } catch (Exception $e) {
            error_log("Print tag error: " . $e->getMessage());
            http_response_code(500);
            echo 'Failed to generate tag: ' . htmlspecialchars($e->getMessage());
        }
    }
    
    /**
     * Generate tag preview HTML
     */
    private function generateTagPreviewHtml($asset, $tagSize) {
        // QR code size mapping
        $qrSizeMap = [
            'micro' => '45',
            'compact' => '60', 
            'standard' => '80',
            'industrial' => '120',
            'materials' => '100',
            'infrastructure' => '150',
            // Legacy support
            'small' => '60',
            'medium' => '80', 
            'large' => '120',
            'consumable' => '100'
        ];
        $qrSize = $qrSizeMap[$tagSize] ?? '80';
        
        // Tag dimensions for preview (2x scale for visibility)
        $dimensionsMap = [
            'micro' => ['width' => 144, 'height' => 144],
            'compact' => ['width' => 192, 'height' => 240],
            'standard' => ['width' => 288, 'height' => 288],
            'industrial' => ['width' => 384, 'height' => 480],
            'materials' => ['width' => 576, 'height' => 288],
            'infrastructure' => ['width' => 576, 'height' => 768],
            // Legacy support
            'small' => ['width' => 192, 'height' => 240],
            'medium' => ['width' => 288, 'height' => 288],
            'large' => ['width' => 384, 'height' => 480],
            'consumable' => ['width' => 576, 'height' => 288]
        ];
        $dimensions = $dimensionsMap[$tagSize] ?? ['width' => 288, 'height' => 288];
        
        // Generate QR code URL
        $qrData = $asset['ref'];
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$qrSize}x{$qrSize}&data=" . urlencode($qrData);
        
        // Ref length mapping
        $refLengthMap = [
            'micro' => 8,
            'compact' => 10,
            'standard' => 15,
            'industrial' => 18,
            'materials' => 20,
            'infrastructure' => 25,
            // Legacy support
            'small' => 10,
            'medium' => 15,
            'large' => 18,
            'consumable' => 20
        ];
        $maxLength = $refLengthMap[$tagSize] ?? 15;
        
        // Status indicator
        $statusClass = 'status-' . str_replace('_', '-', $asset['status'] ?? 'available');
        
        $html = '
        <div style="text-align: center; padding: 20px;">
            <div class="qr-tag tag-' . htmlspecialchars($tagSize) . '" style="
                border: 2px solid #000;
                background: white;
                text-align: center;
                padding: 12px;
                display: inline-block;
                width: ' . $dimensions['width'] . 'px;
                height: ' . $dimensions['height'] . 'px;
                font-family: Arial, sans-serif;
                position: relative;
            ">
                <!-- SecureLink Branding -->
                <div style="
                    font-weight: bold;
                    color: #0066cc;
                    margin-bottom: 8px;
                    font-size: ' . ($tagSize === 'micro' ? '8px' : ($tagSize === 'compact' ? '10px' : ($tagSize === 'standard' ? '12px' : ($tagSize === 'industrial' ? '16px' : ($tagSize === 'materials' ? '14px' : '20px'))))) . ';
                ">SecureLink™</div>
                
                <!-- QR Code -->
                <div style="
                    background: #f8f9fa;
                    border: 1px solid #ddd;
                    margin: 0 auto 12px auto;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: ' . ($qrSize * 2) . 'px;
                    height: ' . ($qrSize * 2) . 'px;
                ">
                    <img src="' . $qrCodeUrl . '" alt="QR Code" style="max-width: 100%; max-height: 100%;">
                </div>
                
                <!-- Asset Reference -->
                <div style="
                    font-weight: bold;
                    margin-bottom: 6px;
                    word-wrap: break-word;
                    font-size: ' . ($tagSize === 'micro' ? '12px' : ($tagSize === 'compact' ? '16px' : ($tagSize === 'standard' ? '20px' : ($tagSize === 'industrial' ? '24px' : ($tagSize === 'materials' ? '22px' : '28px'))))) . ';
                ">
                    REF: ' . htmlspecialchars(substr($asset['ref'], 0, $maxLength)) . '
                </div>
                
                <!-- Asset Name -->
                <div style="
                    margin-bottom: 6px;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    line-height: 1.1;
                    font-size: ' . ($tagSize === 'micro' ? '10px' : ($tagSize === 'compact' ? '12px' : ($tagSize === 'standard' ? '16px' : ($tagSize === 'industrial' ? '20px' : ($tagSize === 'materials' ? '18px' : '24px'))))) . ';
                ">
                    ' . htmlspecialchars($asset['name']) . '
                </div>
                
                <!-- Additional Info -->
                <div style="
                    color: #666;
                    margin-top: 4px;
                    line-height: 1.0;
                    font-size: ' . ($tagSize === 'micro' ? '6px' : ($tagSize === 'compact' ? '8px' : ($tagSize === 'standard' ? '10px' : ($tagSize === 'industrial' ? '14px' : ($tagSize === 'materials' ? '12px' : '18px'))))) . ';
                ">';
        
        // Add tag-specific information based on size
        if ($tagSize === 'micro') {
            if (!empty($asset['sub_location'])) {
                $microLoc = str_replace(['Warehouse', 'Tool Room', 'Storage Area', 'Office', 'Field Storage'], ['WH', 'TR', 'ST', 'OF', 'FS'], $asset['sub_location']);
                $html .= '<span style="color: #0066cc;">' . htmlspecialchars(substr($microLoc, 0, 6)) . '</span>';
            }
        } elseif (in_array($tagSize, ['compact', 'small'])) {
            $locationText = '';
            if (!empty($asset['sub_location'])) {
                $subLoc = str_replace(['Warehouse', 'Tool Room', 'Storage Area', 'Office'], ['WH', 'TR', 'ST', 'OF'], $asset['sub_location']);
                $locationText = substr($subLoc, 0, 8);
            } elseif (!empty($asset['location'])) {
                $locationText = substr($asset['location'], 0, 8);
            } elseif (!empty($asset['project_name'])) {
                $locationText = substr($asset['project_name'], 0, 8);
            }
            if ($locationText) {
                $html .= '<span style="color: #0066cc;">' . htmlspecialchars($locationText) . '</span>';
            }
        } elseif (in_array($tagSize, ['standard', 'medium'])) {
            if (!empty($asset['project_name'])) {
                $html .= htmlspecialchars(substr($asset['project_name'], 0, 15)) . '<br>';
            }
            if (!empty($asset['sub_location'])) {
                $html .= '<span style="color: #0066cc;">@ ' . htmlspecialchars(substr($asset['sub_location'], 0, 12)) . '</span>';
            } elseif (!empty($asset['location'])) {
                $html .= '<span style="color: #0066cc;">@ ' . htmlspecialchars(substr($asset['location'], 0, 12)) . '</span>';
            }
        } elseif (in_array($tagSize, ['industrial', 'large'])) {
            if (!empty($asset['project_name'])) {
                $html .= 'Project: ' . htmlspecialchars(substr($asset['project_name'], 0, 18)) . '<br>';
            }
            if (!empty($asset['category_name'])) {
                $html .= htmlspecialchars(substr($asset['category_name'], 0, 18)) . '<br>';
            }
            if (!empty($asset['sub_location'])) {
                $html .= '<span style="color: #0066cc;">Location: ' . htmlspecialchars(substr($asset['sub_location'], 0, 15)) . '</span><br>';
            } elseif (!empty($asset['location'])) {
                $html .= '<span style="color: #0066cc;">Location: ' . htmlspecialchars(substr($asset['location'], 0, 15)) . '</span><br>';
            }
            if (!empty($asset['serial_number'])) {
                $html .= '<span style="color: #333; font-weight: bold;">S/N: ' . htmlspecialchars(substr($asset['serial_number'], 0, 12)) . '</span>';
            }
        } elseif (in_array($tagSize, ['materials', 'consumable'])) {
            if (!empty($asset['project_name'])) {
                $html .= htmlspecialchars(substr($asset['project_name'], 0, 20)) . '<br>';
            }
            if (!empty($asset['sub_location'])) {
                $html .= '<span style="color: #0066cc;">' . htmlspecialchars(substr($asset['sub_location'], 0, 18)) . '</span>';
            } elseif (!empty($asset['location'])) {
                $html .= '<span style="color: #0066cc;">' . htmlspecialchars(substr($asset['location'], 0, 18)) . '</span>';
            }
            if (!empty($asset['acquired_date'])) {
                $html .= '<br>Acquired: ' . date('m/Y', strtotime($asset['acquired_date']));
            }
        } elseif ($tagSize === 'infrastructure') {
            if (!empty($asset['project_name'])) {
                $html .= 'Project: ' . htmlspecialchars(substr($asset['project_name'], 0, 25)) . '<br>';
            }
            if (!empty($asset['category_name'])) {
                $html .= 'Type: ' . htmlspecialchars(substr($asset['category_name'], 0, 25)) . '<br>';
            }
            if (!empty($asset['sub_location'])) {
                $html .= '<span style="color: #0066cc;">Location: ' . htmlspecialchars(substr($asset['sub_location'], 0, 20)) . '</span><br>';
            } elseif (!empty($asset['location'])) {
                $html .= '<span style="color: #0066cc;">Location: ' . htmlspecialchars(substr($asset['location'], 0, 20)) . '</span><br>';
            }
            if (!empty($asset['serial_number'])) {
                $html .= '<span style="color: #333; font-weight: bold;">S/N: ' . htmlspecialchars(substr($asset['serial_number'], 0, 18)) . '</span><br>';
            }
            if (!empty($asset['maker_name'])) {
                $html .= 'Mfr: ' . htmlspecialchars(substr($asset['maker_name'], 0, 20)) . '<br>';
            }
            if (!empty($asset['acquired_date'])) {
                $html .= 'Installed: ' . date('M Y', strtotime($asset['acquired_date']));
            }
        }
        
        $html .= '
                </div>
            </div>
            <div style="margin-top: 15px; font-size: 14px; color: #666;">
                <strong>Tag Size:</strong> ' . ucfirst($tagSize) . ' (' . ($dimensions['width']/2) . 'px × ' . ($dimensions['height']/2) . 'px)
            </div>
        </div>';
        
        return $html;
    }
    
    /**
     * Determine appropriate tag size based on engineering discipline and asset characteristics
     */
    private function determineTagSize($asset) {
        $category = strtolower($asset['category_name'] ?? '');
        $name = strtolower($asset['name'] ?? '');
        
        // MICRO TAGS - Electronic components, sensors, small precision items
        if (strpos($category, 'sensor') !== false || 
            strpos($category, 'electronic') !== false ||
            strpos($category, 'component') !== false ||
            strpos($category, 'instrument') !== false ||
            strpos($name, 'sensor') !== false ||
            strpos($name, 'gauge') !== false ||
            strpos($name, 'meter') !== false) {
            return 'micro';
        }
        
        // COMPACT TAGS - Hand tools, portable instruments, small equipment
        if (strpos($category, 'hand tool') !== false || 
            strpos($category, 'small tool') !== false ||
            strpos($category, 'portable') !== false ||
            strpos($category, 'safety') !== false ||
            strpos($category, 'hardware') !== false ||
            strpos($name, 'wrench') !== false ||
            strpos($name, 'hammer') !== false ||
            strpos($name, 'screwdriver') !== false ||
            strpos($name, 'multimeter') !== false) {
            return 'compact';
        }
        
        // INFRASTRUCTURE TAGS - Permanent installations, structural elements
        if (strpos($category, 'structural') !== false || 
            strpos($category, 'bridge') !== false ||
            strpos($category, 'building') !== false ||
            strpos($category, 'infrastructure') !== false ||
            strpos($category, 'installation') !== false ||
            strpos($category, 'transformer') !== false ||
            strpos($category, 'panel') !== false ||
            strpos($name, 'beam') !== false ||
            strpos($name, 'column') !== false ||
            strpos($name, 'foundation') !== false ||
            strpos($name, 'transformer') !== false) {
            return 'infrastructure';
        }
        
        // INDUSTRIAL TAGS - Heavy machinery, generators, large equipment
        if (strpos($category, 'heavy equipment') !== false || 
            strpos($category, 'machinery') !== false ||
            strpos($category, 'generator') !== false ||
            strpos($category, 'compressor') !== false ||
            strpos($category, 'excavator') !== false ||
            strpos($category, 'crane') !== false ||
            strpos($category, 'bulldozer') !== false ||
            strpos($name, 'generator') !== false ||
            strpos($name, 'compressor') !== false ||
            strpos($name, 'excavator') !== false ||
            strpos($name, 'crane') !== false) {
            return 'industrial';
        }
        
        // MATERIALS TAGS - Consumables, materials, bulk items
        if (strpos($category, 'material') !== false || 
            strpos($category, 'consumable') !== false ||
            strpos($category, 'chemical') !== false ||
            strpos($category, 'fuel') !== false ||
            strpos($category, 'supply') !== false ||
            ($asset['is_consumable'] ?? false) ||
            strpos($name, 'concrete') !== false ||
            strpos($name, 'steel') !== false ||
            strpos($name, 'paint') !== false) {
            return 'materials';
        }
        
        // STANDARD TAGS - Default for power tools, general equipment, test instruments
        return 'standard';
    }
    
    /**
     * Print multiple QR tags (HTML template for browser printing)
     */
    public function printTags() {
        $assetIds = $_GET['ids'] ?? [];
        
        if (empty($assetIds) || !is_array($assetIds)) {
            http_response_code(400);
            echo 'No assets selected';
            return;
        }
        
        try {
            $assets = [];
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            $restrictedRole = in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman']);
            
            foreach ($assetIds as $assetId) {
                $asset = $this->assetModel->getAssetWithDetails((int)$assetId);
                if ($asset) {
                    // Check project access for restricted roles
                    if ($restrictedRole) {
                        if (empty($currentUser['current_project_id']) || $asset['project_id'] != $currentUser['current_project_id']) {
                            continue; // Skip assets not in user's project
                        }
                    }
                    
                    // Generate QR code if missing
                    if (empty($asset['qr_code'])) {
                        $this->generateQRCode($asset['id']);
                        $asset = $this->assetModel->getAssetWithDetails($asset['id']);
                    }
                    
                    // Add tag size to asset data
                    $asset['tag_size'] = $this->determineTagSize($asset);
                    $assets[] = $asset;
                }
            }
            
            if (empty($assets)) {
                http_response_code(404);
                echo 'No valid assets found';
                return;
            }
            
            // Mark all as printed
            foreach ($assets as $asset) {
                $this->markTagAsPrinted($asset['id']);
            }
            
            // Include the batch printable template
            include APP_ROOT . '/views/assets/print_tags_batch.php';
            
        } catch (Exception $e) {
            error_log("Batch print tags error: " . $e->getMessage());
            http_response_code(500);
            echo 'Failed to generate tags: ' . htmlspecialchars($e->getMessage());
        }
    }
    
    /**
     * Get tag preview (AJAX)
     */
    public function tagPreview() {
        $assetId = (int)($_GET['id'] ?? 0);
        
        if (!$assetId) {
            http_response_code(404);
            echo 'Asset not found';
            return;
        }
        
        try {
            $asset = $this->assetModel->getAssetWithDetails($assetId);
            
            if (!$asset) {
                http_response_code(404);
                echo 'Asset not found';
                return;
            }
            
            // Check project access for restricted roles
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                if (empty($currentUser['current_project_id']) || $asset['project_id'] != $currentUser['current_project_id']) {
                    http_response_code(403);
                    echo 'Access denied - Asset not in your assigned project';
                    return;
                }
            }
            
            // Use our new engineering-optimized tag size determination
            $tagSize = $this->determineTagSize($asset);
            
            // Generate preview HTML directly using our CSS classes
            $previewHtml = $this->generateTagPreviewHtml($asset, $tagSize);
            
            echo $previewHtml;
            
        } catch (Exception $e) {
            error_log("Tag preview error: " . $e->getMessage());
            http_response_code(500);
            echo 'Failed to generate preview';
        }
    }
    
    /**
     * Generate QR code for asset (AJAX)
     */
    public function generateQR() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        $assetId = (int)($_POST['asset_id'] ?? 0);
        
        if (!$assetId) {
            echo json_encode(['success' => false, 'message' => 'Asset ID required']);
            return;
        }
        
        try {
            $asset = $this->assetModel->getAssetWithDetails($assetId);
            
            if (!$asset) {
                echo json_encode(['success' => false, 'message' => 'Asset not found']);
                return;
            }
            
            // Check project access for restricted roles
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                if (empty($currentUser['current_project_id']) || $asset['project_id'] != $currentUser['current_project_id']) {
                    echo json_encode(['success' => false, 'message' => 'Access denied - Asset not in your assigned project']);
                    return;
                }
            }
            
            try {
                $this->generateQRCode($assetId);
                echo json_encode(['success' => true, 'message' => 'QR code generated successfully']);
            } catch (Exception $e) {
                // Return specific error message to user
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }

        } catch (Exception $e) {
            error_log("Generate QR API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Mark tags as applied (AJAX)
     */
    public function markTagsApplied() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        $assetIdsString = $_POST['asset_ids'] ?? '';
        $assetIds = array_filter(array_map('intval', explode(',', $assetIdsString)));
        
        if (empty($assetIds)) {
            echo json_encode(['success' => false, 'message' => 'No assets selected']);
            return;
        }
        
        try {
            $currentUser = $this->auth->getCurrentUser();
            $updatedCount = 0;
            
            foreach ($assetIds as $assetId) {
                if ($this->markTagAsApplied($assetId, $currentUser['id'])) {
                    $updatedCount++;
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => "Marked {$updatedCount} tag(s) as applied",
                'updated_count' => $updatedCount
            ]);
            
        } catch (Exception $e) {
            error_log("Mark tags applied API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }
    
    /**
     * Get tag statistics (AJAX)
     */
    public function tagStats() {
        header('Content-Type: application/json');
        
        try {
            $stats = $this->getTagStatistics();
            echo json_encode(['success' => true, 'stats' => $stats]);
            
        } catch (Exception $e) {
            error_log("Tag stats API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load statistics']);
        }
    }
    
    /**
     * Verify tag placement (AJAX)
     */
    public function verifyTag() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        $assetId = (int)($_POST['asset_id'] ?? 0);
        
        if (!$assetId) {
            echo json_encode(['success' => false, 'message' => 'Asset ID required']);
            return;
        }
        
        try {
            $asset = $this->assetModel->find($assetId);
            
            if (!$asset) {
                echo json_encode(['success' => false, 'message' => 'Asset not found']);
                return;
            }
            
            // Check if tag is applied
            if (empty($asset['qr_tag_applied'])) {
                echo json_encode(['success' => false, 'message' => 'QR tag must be applied before verification']);
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            
            $result = $this->assetModel->update($assetId, [
                'qr_tag_verified' => date('Y-m-d H:i:s'),
                'qr_tag_verified_by' => $currentUser['id']
            ]);
            
            if ($result) {
                $this->logTagAction($assetId, 'verified', $currentUser['id'], 'QR tag placement verified');
                echo json_encode(['success' => true, 'message' => 'QR tag verified successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to verify tag']);
            }
            
        } catch (Exception $e) {
            error_log("Verify tag API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }
    
    /**
     * Bulk verify tags (AJAX)
     */
    public function verifyTags() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        $assetIdsString = $_POST['asset_ids'] ?? '';
        $assetIds = array_filter(array_map('intval', explode(',', $assetIdsString)));
        
        if (empty($assetIds)) {
            echo json_encode(['success' => false, 'message' => 'No assets selected']);
            return;
        }
        
        try {
            $currentUser = $this->auth->getCurrentUser();
            $updatedCount = 0;
            $errors = [];
            
            foreach ($assetIds as $assetId) {
                // Check if asset exists and tag is applied
                $asset = $this->assetModel->find($assetId);
                
                if (!$asset) {
                    $errors[] = "Asset ID {$assetId} not found";
                    continue;
                }
                
                if (empty($asset['qr_tag_applied'])) {
                    $errors[] = "Asset {$asset['ref']} - QR tag must be applied before verification";
                    continue;
                }
                
                if (!empty($asset['qr_tag_verified'])) {
                    $errors[] = "Asset {$asset['ref']} - QR tag already verified";
                    continue;
                }
                
                // Mark as verified
                $result = $this->assetModel->update($assetId, [
                    'qr_tag_verified' => date('Y-m-d H:i:s'),
                    'qr_tag_verified_by' => $currentUser['id']
                ]);
                
                if ($result) {
                    $this->logTagAction($assetId, 'verified', $currentUser['id'], 'QR tag placement verified (bulk operation)');
                    $updatedCount++;
                } else {
                    $errors[] = "Failed to verify tag for asset {$asset['ref']}";
                }
            }
            
            $message = "Verified {$updatedCount} tag(s)";
            if (!empty($errors)) {
                $message .= ". Issues: " . implode(', ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= " and " . (count($errors) - 3) . " more";
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'updated_count' => $updatedCount,
                'error_count' => count($errors)
            ]);
            
        } catch (Exception $e) {
            error_log("Bulk verify tags API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }
    
    /**
     * Test PDF generation (for debugging)
     */
    public function testPDF() {
        if (!$this->tagGenerator) {
            http_response_code(500);
            echo 'QR tag generator not available';
            return;
        }
        
        try {
            // Start output buffering to catch any unexpected output
            ob_start();
            
            // Suppress errors that might contaminate PDF output
            $oldErrorReporting = error_reporting(E_ERROR | E_PARSE);
            
            $pdfContent = $this->tagGenerator->generateTestPDF();
            
            // Restore error reporting
            error_reporting($oldErrorReporting);
            
            // Clean any captured output
            $unexpectedOutput = ob_get_clean();
            if (!empty($unexpectedOutput)) {
                error_log("Unexpected output during PDF generation: " . $unexpectedOutput);
            }
            
            if (empty($pdfContent)) {
                throw new Exception('Test PDF content is empty');
            }
            
            if (substr($pdfContent, 0, 4) !== '%PDF') {
                error_log("Invalid test PDF content, first 100 chars: " . substr($pdfContent, 0, 100));
                throw new Exception('Generated test content is not a valid PDF');
            }
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="test.pdf"');
            header('Content-Length: ' . strlen($pdfContent));
            
            echo $pdfContent;
            exit;
            
        } catch (Exception $e) {
            error_log("Test PDF error: " . $e->getMessage());
            http_response_code(500);
            echo 'Test PDF generation failed: ' . htmlspecialchars($e->getMessage());
        }
    }
    
    /**
     * Generate QR code for asset
     */
    private function generateQRCode($assetId) {
        try {
            $asset = $this->assetModel->find($assetId);
            if (!$asset) {
                throw new Exception('Asset not found');
            }

            // Check if asset is consumable - QR codes only for capital assets
            if (isset($asset['is_consumable']) && $asset['is_consumable'] == 1) {
                throw new Exception('QR codes are not applicable for consumable items');
            }

            // Use SecureLink if available
            if (class_exists('SecureLink')) {
                $secureLink = SecureLink::getInstance();
                $result = $secureLink->generateAssetQR($assetId, $asset['ref']);

                if ($result && isset($result['success']) && $result['success']) {
                    $qrCode = $result['qr_data'];
                } else {
                    $errorMsg = $result['message'] ?? 'SecureLink QR generation failed';
                    throw new Exception($errorMsg);
                }
            } else {
                // Fallback: simple QR code data using asset reference
                $qrCode = base64_encode($asset['ref'] . '|' . time());
            }

            // Update asset with QR code
            $updateResult = $this->assetModel->update($assetId, ['qr_code' => $qrCode]);

            if (!$updateResult) {
                throw new Exception('Failed to save QR code to database');
            }

            // Log QR generation
            $this->logTagAction($assetId, 'generated', null, 'QR code generated');

            return true;

        } catch (Exception $e) {
            error_log("Generate QR code error for asset $assetId: " . $e->getMessage());
            throw $e; // Re-throw to provide specific error message
        }
    }
    
    /**
     * Mark tag as printed
     */
    private function markTagAsPrinted($assetId) {
        try {
            $currentUser = $this->auth->getCurrentUser();
            
            $result = $this->assetModel->update($assetId, [
                'qr_tag_printed' => date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                $this->logTagAction($assetId, 'printed', $currentUser['id'], 'QR tag printed');
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Mark tag printed error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark tag as applied
     */
    private function markTagAsApplied($assetId, $userId) {
        try {
            $result = $this->assetModel->update($assetId, [
                'qr_tag_applied' => date('Y-m-d H:i:s'),
                'qr_tag_applied_by' => $userId
            ]);
            
            if ($result) {
                $this->logTagAction($assetId, 'applied', $userId, 'QR tag applied to asset');
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Mark tag applied error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log tag action
     */
    private function logTagAction($assetId, $action, $userId, $notes = null) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                INSERT INTO qr_tag_logs (asset_id, action, user_id, notes, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$assetId, $action, $userId, $notes]);
            
        } catch (Exception $e) {
            error_log("Log tag action error: " . $e->getMessage());
        }
    }
    
    /**
     * Get tag statistics with optional project filtering
     */
    private function getTagStatistics($projectId = null) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $whereClause = "WHERE status != 'disposed'";
            $params = [];
            
            if ($projectId) {
                $whereClause .= " AND project_id = ?";
                $params[] = $projectId;
            }
            
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_assets,
                    SUM(CASE WHEN qr_code IS NOT NULL THEN 1 ELSE 0 END) as qr_generated,
                    SUM(CASE WHEN qr_code IS NOT NULL AND qr_tag_printed IS NULL THEN 1 ELSE 0 END) as needs_printing,
                    SUM(CASE WHEN qr_tag_printed IS NOT NULL AND qr_tag_applied IS NULL THEN 1 ELSE 0 END) as needs_application,
                    SUM(CASE WHEN qr_tag_applied IS NOT NULL AND qr_tag_verified IS NULL THEN 1 ELSE 0 END) as needs_verification,
                    SUM(CASE WHEN qr_tag_verified IS NOT NULL THEN 1 ELSE 0 END) as fully_tagged
                FROM assets 
                {$whereClause}
            ");
            
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: [
                'total_assets' => 0,
                'qr_generated' => 0,
                'needs_printing' => 0,
                'needs_application' => 0,
                'needs_verification' => 0,
                'fully_tagged' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Get tag statistics error: " . $e->getMessage());
            return [
                'total_assets' => 0,
                'qr_generated' => 0,
                'needs_printing' => 0,
                'needs_application' => 0,
                'needs_verification' => 0,
                'fully_tagged' => 0
            ];
        }
    }
    
    /**
     * Get assets with tag status
     */
    private function getAssetsWithTagStatus($filters = [], $page = 1, $perPage = 20) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $conditions = ["a.status != 'disposed'"];
            $params = [];
            
            // Apply filters
            if (isset($filters['missing_qr'])) {
                $conditions[] = "a.qr_code IS NULL";
            }
            
            if (isset($filters['needs_printing'])) {
                $conditions[] = "a.qr_code IS NOT NULL AND a.qr_tag_printed IS NULL";
            }
            
            if (isset($filters['needs_application'])) {
                $conditions[] = "a.qr_tag_printed IS NOT NULL AND a.qr_tag_applied IS NULL";
            }
            
            if (isset($filters['needs_verification'])) {
                $conditions[] = "a.qr_tag_applied IS NOT NULL AND a.qr_tag_verified IS NULL";
            }
            
            if (isset($filters['fully_tagged'])) {
                $conditions[] = "a.qr_tag_verified IS NOT NULL";
            }
            
            if (!empty($filters['project_id'])) {
                $conditions[] = "a.project_id = ?";
                $params[] = $filters['project_id'];
            }
            
            if (!empty($filters['search'])) {
                $conditions[] = "(a.ref LIKE ? OR a.name LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            // Get total count
            $countSql = "
                SELECT COUNT(*) 
                FROM assets a 
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                {$whereClause}
            ";
            
            $stmt = $db->prepare($countSql);
            $stmt->execute($params);
            $totalCount = $stmt->fetchColumn();
            
            // Get assets
            $offset = ($page - 1) * $perPage;
            
            $sql = "
                SELECT a.*, 
                       c.name as category_name, c.is_consumable,
                       p.name as project_name
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                {$whereClause}
                ORDER BY a.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'assets' => $assets,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_count' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Get assets with tag status error: " . $e->getMessage());
            return [
                'assets' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total_count' => 0,
                    'total_pages' => 0
                ]
            ];
        }
    }
}
?>