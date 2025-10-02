<?php
/**
 * ConstructLink™ Maker Controller
 * Handles manufacturer/brand management operations
 */

class MakerController {
    private $auth;
    private $makerModel;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->makerModel = new MakerModel();
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Display maker listing
     */
    public function index() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        
        // Build filters
        $filters = [];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        if (!empty($_GET['country'])) $filters['country'] = $_GET['country'];
        
        try {
            // Get makers with pagination
            $result = $this->makerModel->getMakersWithFilters($filters, $page, $perPage);
            $makers = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];
            
            // Get maker statistics
            $makerStats = $this->makerModel->getMakerStatistics();
            
            // Get all countries for filter dropdown
            $countries = $this->makerModel->getAllCountries();
            
            $pageTitle = 'Manufacturer Management - ConstructLink™';
            $pageHeader = 'Manufacturer Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Manufacturers', 'url' => '?route=makers']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/makers/index.php';
            
        } catch (Exception $e) {
            error_log("Maker listing error: " . $e->getMessage());
            $error = 'Failed to load manufacturers';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create maker form
     */
    public function create() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        $formData = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            $formData = [
                'name' => Validator::sanitize($_POST['name'] ?? ''),
                'country' => Validator::sanitize($_POST['country'] ?? ''),
                'website' => Validator::sanitize($_POST['website'] ?? ''),
                'description' => Validator::sanitize($_POST['description'] ?? '')
            ];
            
            $result = $this->makerModel->createMaker($formData);
            
            if ($result['success']) {
                header('Location: ?route=makers/view&id=' . $result['maker']['id'] . '&message=maker_created');
                exit;
            } else {
                if (isset($result['errors'])) {
                    $errors = $result['errors'];
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        $pageTitle = 'Create Manufacturer - ConstructLink™';
        $pageHeader = 'Create New Manufacturer';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Manufacturers', 'url' => '?route=makers'],
            ['title' => 'Create Manufacturer', 'url' => '?route=makers/create']
        ];
        
        include APP_ROOT . '/views/makers/create.php';
    }
    
    /**
     * Display edit maker form
     */
    public function edit() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $makerId = $_GET['id'] ?? 0;
        
        if (!$makerId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $maker = $this->makerModel->getMakerWithDetails($makerId);
            
            if (!$maker) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $formData = $maker;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $formData = [
                    'name' => Validator::sanitize($_POST['name'] ?? ''),
                    'country' => Validator::sanitize($_POST['country'] ?? ''),
                    'website' => Validator::sanitize($_POST['website'] ?? ''),
                    'description' => Validator::sanitize($_POST['description'] ?? '')
                ];
                
                $result = $this->makerModel->updateMaker($makerId, $formData);
                
                if ($result['success']) {
                    header('Location: ?route=makers/view&id=' . $makerId . '&message=maker_updated');
                    exit;
                } else {
                    if (isset($result['errors'])) {
                        $errors = $result['errors'];
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Edit Manufacturer - ConstructLink™';
            $pageHeader = 'Edit Manufacturer: ' . htmlspecialchars($maker['name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Manufacturers', 'url' => '?route=makers'],
                ['title' => 'Edit Manufacturer', 'url' => '?route=makers/edit&id=' . $makerId]
            ];
            
            include APP_ROOT . '/views/makers/edit.php';
            
        } catch (Exception $e) {
            error_log("Maker edit error: " . $e->getMessage());
            $error = 'Failed to load manufacturer details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * View maker details
     */
    public function view() {
        $makerId = $_GET['id'] ?? 0;
        
        if (!$makerId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $maker = $this->makerModel->getMakerWithDetails($makerId);
            
            if (!$maker) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Get maker assets
            $assetModel = new AssetModel();
            $makerAssets = $assetModel->findAll(['maker_id' => $makerId], 'name ASC', 50);
            
            // Get maker asset categories
            $makerCategories = $this->makerModel->getMakerAssetCategories($makerId);
            
            $pageTitle = 'Manufacturer Details - ConstructLink™';
            $pageHeader = 'Manufacturer: ' . htmlspecialchars($maker['name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Manufacturers', 'url' => '?route=makers'],
                ['title' => 'View Details', 'url' => '?route=makers/view&id=' . $makerId]
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/makers/view.php';
            
        } catch (Exception $e) {
            error_log("Maker view error: " . $e->getMessage());
            $error = 'Failed to load manufacturer details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Delete maker
     */
    public function delete() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $makerId = $_GET['id'] ?? 0;
        
        if (!$makerId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $result = $this->makerModel->deleteMaker($makerId);
            
            if ($result['success']) {
                header('Location: ?route=makers&message=maker_deleted');
                exit;
            } else {
                header('Location: ?route=makers&error=' . urlencode($result['message']));
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Maker deletion error: " . $e->getMessage());
            header('Location: ?route=makers&error=Failed to delete manufacturer');
            exit;
        }
    }
    
    /**
     * Export makers data
     */
    public function export() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $format = $_GET['format'] ?? 'csv';
        
        try {
            $makers = $this->makerModel->getMakersWithAssetCount();
            
            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="manufacturers_' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($output, [
                    'ID', 'Name', 'Country', 'Website', 'Description', 
                    'Asset Count', 'Total Value', 'Created At'
                ]);
                
                // CSV data
                foreach ($makers as $maker) {
                    fputcsv($output, [
                        $maker['id'],
                        $maker['name'],
                        $maker['country'],
                        $maker['website'],
                        $maker['description'],
                        $maker['asset_count'],
                        $maker['total_value'],
                        $maker['created_at']
                    ]);
                }
                
                fclose($output);
            }
            
        } catch (Exception $e) {
            error_log("Export makers error: " . $e->getMessage());
            $error = 'Failed to export manufacturers data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Get makers for dropdown via AJAX
     */
    public function getForDropdown() {
        try {
            $makers = $this->makerModel->getActiveMakers();
            
            echo json_encode([
                'success' => true,
                'makers' => $makers
            ]);
            
        } catch (Exception $e) {
            error_log("Get makers dropdown error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load manufacturers']);
        }
    }
    
    /**
     * Get maker statistics via AJAX
     */
    public function getStats() {
        try {
            $stats = $this->makerModel->getMakerStatistics();
            $topMakers = $this->makerModel->getTopMakersByAssetCount(5);
            $countriesData = $this->makerModel->getMakersByCountry();
            
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'top_makers' => $topMakers,
                'countries' => $countriesData
            ]);
            
        } catch (Exception $e) {
            error_log("Get maker stats error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load statistics']);
        }
    }
    
    /**
     * Search makers via AJAX
     */
    public function search() {
        $query = $_GET['q'] ?? '';
        $limit = (int)($_GET['limit'] ?? 20);
        
        if (empty($query)) {
            echo json_encode(['success' => false, 'message' => 'Search query is required']);
            return;
        }
        
        try {
            $makers = $this->makerModel->searchMakers($query, $limit);
            
            echo json_encode([
                'success' => true,
                'makers' => $makers
            ]);
            
        } catch (Exception $e) {
            error_log("Search makers error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to search manufacturers']);
        }
    }
}
?>
