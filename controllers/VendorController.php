<?php
/**
 * ConstructLink™ Vendor Controller - Enhanced with Comprehensive Features
 * Handles vendor/supplier management operations with role-based access control
 * Aligned with schema_complete.sql structure
 */

class VendorController {
    private $auth;
    private $vendorModel;
    private $vendorIntelligenceModel;
    private $vendorProductModel;
    private $db;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->db = Database::getInstance()->getConnection();
        $this->vendorModel = new VendorModel();
        $this->vendorIntelligenceModel = new VendorIntelligenceModel();
        $this->vendorProductModel = new VendorProductModel();
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Display vendor listing with enhanced filtering
     */
    public function index() {
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        
        // Build filters
        $filters = [];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        if (!empty($_GET['payment_terms_id'])) $filters['payment_terms_id'] = $_GET['payment_terms_id'];
        if (isset($_GET['is_preferred'])) $filters['is_preferred'] = $_GET['is_preferred'];
        if (!empty($_GET['category_id'])) $filters['category_id'] = $_GET['category_id'];
        if (!empty($_GET['rating_min'])) $filters['rating_min'] = $_GET['rating_min'];
        
        try {
            // Get vendors with pagination
            $result = $this->vendorModel->getVendorsWithFilters($filters, $page, $perPage);
            $vendors = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];
            
            // Get vendor statistics
            $vendorStats = $this->vendorModel->getVendorStatistics();
            
            // Get filter options
            $paymentTerms = $this->vendorModel->getPaymentTerms();
            $vendorCategories = $this->vendorModel->getVendorCategories();
            
            $pageTitle = 'Vendor Management - ConstructLink™';
            $pageHeader = 'Vendor Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Vendors', 'url' => '?route=vendors']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/vendors/index.php';
            
        } catch (Exception $e) {
            error_log("Vendor listing error: " . $e->getMessage());
            $error = 'Failed to load vendors';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create vendor form
     */
    public function create() {
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Procurement Officer'])) {
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
                'contact_info' => Validator::sanitize($_POST['contact_info'] ?? ''),
                'address' => Validator::sanitize($_POST['address'] ?? ''),
                'phone' => Validator::sanitize($_POST['phone'] ?? ''),
                'email' => Validator::sanitize($_POST['email'] ?? ''),
                'contact_person' => Validator::sanitize($_POST['contact_person'] ?? ''),
                'payment_terms_id' => !empty($_POST['payment_terms']) && is_array($_POST['payment_terms']) ? (int)$_POST['payment_terms'][0] : (!empty($_POST['payment_terms_id']) ? (int)$_POST['payment_terms_id'] : null),
                'tin' => Validator::sanitize($_POST['tin'] ?? ''),
                'vendor_type' => Validator::sanitize($_POST['vendor_type'] ?? ''),
                'rdo_code' => Validator::sanitize($_POST['rdo_code'] ?? ''),
                'is_preferred' => isset($_POST['is_preferred']) ? 1 : 0,
                'rating' => !empty($_POST['rating']) ? (float)$_POST['rating'] : null,
                'notes' => Validator::sanitize($_POST['notes'] ?? ''),
                'categories' => $_POST['categories'] ?? [],
                'bank_accounts' => []
            ];
            
            // Process bank accounts if provided
            if (!empty($_POST['bank_accounts'])) {
                foreach ($_POST['bank_accounts'] as $bankData) {
                    if (!empty($bankData['bank_name']) && !empty($bankData['account_number'])) {
                        $formData['bank_accounts'][] = [
                            'bank_name' => Validator::sanitize($bankData['bank_name']),
                            'account_number' => Validator::sanitize($bankData['account_number']),
                            'account_name' => Validator::sanitize($bankData['account_name'] ?? ''),
                            'account_type' => $bankData['account_type'] ?? 'Checking',
                            'currency' => $bankData['currency'] ?? 'PHP',
                            'bank_category' => $bankData['bank_category'] ?? 'Primary',
                            'swift_code' => Validator::sanitize($bankData['swift_code'] ?? ''),
                            'branch' => Validator::sanitize($bankData['branch'] ?? ''),
                            'is_active' => isset($bankData['is_active']) ? 1 : 1
                        ];
                    }
                }
            }
            
            $result = $this->vendorModel->createVendor($formData);
            
            if ($result['success']) {
                header('Location: ?route=vendors/view&id=' . $result['vendor']['id'] . '&message=vendor_created');
                exit;
            } else {
                if (isset($result['errors'])) {
                    $errors = $result['errors'];
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        // Get form options
        $paymentTerms = $this->vendorModel->getPaymentTerms();
        $vendorCategories = $this->vendorModel->getVendorCategories();
        
        $pageTitle = 'Create Vendor - ConstructLink™';
        $pageHeader = 'Create New Vendor';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Vendors', 'url' => '?route=vendors'],
            ['title' => 'Create Vendor', 'url' => '?route=vendors/create']
        ];
        
        include APP_ROOT . '/views/vendors/create.php';
    }
    
    /**
     * Display edit vendor form
     */
    public function edit() {
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $vendorId = $_GET['id'] ?? 0;
        
        if (!$vendorId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $vendor = $this->vendorModel->getVendorWithDetails($vendorId);
            
            if (!$vendor) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $formData = $vendor;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $formData = [
                    'name' => Validator::sanitize($_POST['name'] ?? ''),
                    'contact_info' => Validator::sanitize($_POST['contact_info'] ?? ''),
                    'address' => Validator::sanitize($_POST['address'] ?? ''),
                    'phone' => Validator::sanitize($_POST['phone'] ?? ''),
                    'email' => Validator::sanitize($_POST['email'] ?? ''),
                    'contact_person' => Validator::sanitize($_POST['contact_person'] ?? ''),
                    'payment_terms_id' => !empty($_POST['payment_terms']) && is_array($_POST['payment_terms']) ? (int)$_POST['payment_terms'][0] : (!empty($_POST['payment_terms_id']) ? (int)$_POST['payment_terms_id'] : null),
                    'tin' => Validator::sanitize($_POST['tin'] ?? ''),
                    'vendor_type' => Validator::sanitize($_POST['vendor_type'] ?? ''),
                    'rdo_code' => Validator::sanitize($_POST['rdo_code'] ?? ''),
                    'is_preferred' => isset($_POST['is_preferred']) ? 1 : 0,
                    'rating' => !empty($_POST['rating']) ? (float)$_POST['rating'] : null,
                    'notes' => Validator::sanitize($_POST['notes'] ?? ''),
                    'categories' => $_POST['categories'] ?? [],
                    'bank_accounts' => []
                ];
                
                // Process bank accounts if provided
                if (!empty($_POST['bank_accounts'])) {
                    foreach ($_POST['bank_accounts'] as $bankData) {
                        if (!empty($bankData['bank_name']) && !empty($bankData['account_number'])) {
                            $formData['bank_accounts'][] = [
                                'bank_name' => Validator::sanitize($bankData['bank_name']),
                                'account_number' => Validator::sanitize($bankData['account_number']),
                                'account_name' => Validator::sanitize($bankData['account_name'] ?? ''),
                                'account_type' => $bankData['account_type'] ?? 'Checking',
                                'currency' => $bankData['currency'] ?? 'PHP',
                                'bank_category' => $bankData['bank_category'] ?? 'Primary',
                                'swift_code' => Validator::sanitize($bankData['swift_code'] ?? ''),
                                'branch' => Validator::sanitize($bankData['branch'] ?? ''),
                                'is_active' => isset($bankData['is_active']) ? 1 : 1
                            ];
                        }
                    }
                }
                
                $result = $this->vendorModel->updateVendor($vendorId, $formData);
                
                if ($result['success']) {
                    header('Location: ?route=vendors/view&id=' . $vendorId . '&message=vendor_updated');
                    exit;
                } else {
                    if (isset($result['errors'])) {
                        $errors = $result['errors'];
                    } else {
                        $errors[] = $result['message'];
                    }
                    
                }
            }
            
            // Get form options
            $paymentTerms = $this->vendorModel->getPaymentTerms();
            $vendorCategories = $this->vendorModel->getVendorCategories();
            
            $pageTitle = 'Edit Vendor - ConstructLink™';
            $pageHeader = 'Edit Vendor: ' . htmlspecialchars($vendor['name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Vendors', 'url' => '?route=vendors'],
                ['title' => 'Edit Vendor', 'url' => '?route=vendors/edit&id=' . $vendorId]
            ];
            
            include APP_ROOT . '/views/vendors/edit.php';
            
        } catch (Exception $e) {
            error_log("Vendor edit error: " . $e->getMessage());
            $error = 'Failed to load vendor details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * View vendor details with comprehensive information
     */
    public function view() {
        $vendorId = $_GET['id'] ?? 0;
        
        if (!$vendorId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            $vendor = $this->vendorModel->getVendorWithBankDetails($vendorId);
            
            if (!$vendor) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Get vendor assets by category (hide cost data from unauthorized users)
            $vendorAssetCategories = [];
            if ($this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])) {
                $vendorAssetCategories = $this->vendorModel->getVendorAssetsByCategory($vendorId);
            }
            
            // Get vendor statistics
            $vendorStats = $this->vendorModel->getVendorStatistics($vendorId);
            
            // Get vendor intelligence data if user has access
            $vendorIntelligence = null;
            if ($this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])) {
                $performanceScore = $this->vendorIntelligenceModel->calculateVendorPerformanceScore($vendorId);
                $riskScore = $this->vendorIntelligenceModel->calculateVendorRiskScore($vendorId);
                
                $vendorIntelligence = [
                    'performance_score' => $performanceScore['overall_score'] ?? 0,
                    'performance_grade' => $this->getPerformanceGrade($performanceScore['overall_score'] ?? 0),
                    'risk_score' => $riskScore['overall_score'] ?? 0,
                    'risk_level' => $this->getRiskLevel($riskScore['overall_score'] ?? 0)
                ];
            }
            
            // Get vendor activity log
            $vendorActivityLog = $this->vendorModel->getVendorActivityLog($vendorId, 10);
            
            $pageTitle = 'Vendor Details - ConstructLink™';
            $pageHeader = 'Vendor: ' . htmlspecialchars($vendor['name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Vendors', 'url' => '?route=vendors'],
                ['title' => 'View Details', 'url' => '?route=vendors/view&id=' . $vendorId]
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/vendors/view.php';
            
        } catch (Exception $e) {
            error_log("Vendor view error: " . $e->getMessage());
            $error = 'Failed to load vendor details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Delete vendor with proper validation
     */
    public function delete() {
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $vendorId = $_GET['id'] ?? 0;
        
        if (!$vendorId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $result = $this->vendorModel->deleteVendor($vendorId);
            
            if ($result['success']) {
                header('Location: ?route=vendors&message=vendor_deleted');
                exit;
            } else {
                header('Location: ?route=vendors&error=' . urlencode($result['message']));
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Vendor deletion error: " . $e->getMessage());
            header('Location: ?route=vendors&error=Failed to delete vendor');
            exit;
        }
    }
    
    /**
     * Toggle vendor preferred status
     */
    public function toggleStatus() {
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Procurement Officer'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        $vendorId = $_POST['vendor_id'] ?? 0;
        
        if (!$vendorId) {
            echo json_encode(['success' => false, 'message' => 'Invalid vendor ID']);
            return;
        }
        
        try {
            $result = $this->vendorModel->toggleVendorStatus($vendorId);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Vendor status toggle error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update vendor status']);
        }
    }
    
    /**
     * Manage vendor bank accounts
     */
    public function manageBanks() {
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Procurement Officer', 'Finance Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $vendorId = $_GET['vendor_id'] ?? 0;
        
        if (!$vendorId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $vendor = $this->vendorModel->find($vendorId);
            if (!$vendor) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Handle POST requests for adding/updating bank accounts
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $action = $_POST['action'] ?? '';
                
                if ($action === 'add_bank') {
                    $bankData = [
                        'bank_name' => Validator::sanitize($_POST['bank_name'] ?? ''),
                        'account_number' => Validator::sanitize($_POST['account_number'] ?? ''),
                        'account_name' => Validator::sanitize($_POST['account_name'] ?? ''),
                        'account_type' => $_POST['account_type'] ?? 'Checking',
                        'currency' => $_POST['currency'] ?? 'PHP',
                        'bank_category' => $_POST['bank_category'] ?? 'Primary',
                        'swift_code' => Validator::sanitize($_POST['swift_code'] ?? ''),
                        'branch' => Validator::sanitize($_POST['branch'] ?? ''),
                        'is_active' => isset($_POST['is_active']) ? 1 : 1
                    ];
                    
                    $result = $this->vendorModel->addVendorBank($vendorId, $bankData);
                    
                    if ($result['success']) {
                        $messages[] = 'Bank account added successfully';
                    } else {
                        if (isset($result['errors'])) {
                            $errors = array_merge($errors, $result['errors']);
                        } else {
                            $errors[] = $result['message'];
                        }
                    }
                }
                
                if ($action === 'update_bank') {
                    $bankId = $_POST['bank_id'] ?? 0;
                    $bankData = [
                        'bank_name' => Validator::sanitize($_POST['bank_name'] ?? ''),
                        'account_number' => Validator::sanitize($_POST['account_number'] ?? ''),
                        'account_name' => Validator::sanitize($_POST['account_name'] ?? ''),
                        'account_type' => $_POST['account_type'] ?? 'Checking',
                        'currency' => $_POST['currency'] ?? 'PHP',
                        'bank_category' => $_POST['bank_category'] ?? 'Primary',
                        'swift_code' => Validator::sanitize($_POST['swift_code'] ?? ''),
                        'branch' => Validator::sanitize($_POST['branch'] ?? ''),
                        'is_active' => isset($_POST['is_active']) ? 1 : 1
                    ];
                    
                    $result = $this->vendorModel->updateVendorBank($bankId, $bankData);
                    
                    if ($result['success']) {
                        $messages[] = 'Bank account updated successfully';
                    } else {
                        if (isset($result['errors'])) {
                            $errors = array_merge($errors, $result['errors']);
                        } else {
                            $errors[] = $result['message'];
                        }
                    }
                }
                
                if ($action === 'delete_bank') {
                    $bankId = $_POST['bank_id'] ?? 0;
                    $result = $this->vendorModel->deleteVendorBank($bankId);
                    
                    if ($result['success']) {
                        $messages[] = 'Bank account deleted successfully';
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            // Get vendor bank accounts
            $vendorBanks = $this->vendorModel->getVendorBanks($vendorId);
            
            $pageTitle = 'Manage Bank Accounts - ConstructLink™';
            $pageHeader = 'Bank Accounts: ' . htmlspecialchars($vendor['name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Vendors', 'url' => '?route=vendors'],
                ['title' => 'View Vendor', 'url' => '?route=vendors/view&id=' . $vendorId],
                ['title' => 'Bank Accounts', 'url' => '?route=vendors/manageBanks&vendor_id=' . $vendorId]
            ];
            
            include APP_ROOT . '/views/vendors/manage_banks.php';
            
        } catch (Exception $e) {
            error_log("Vendor bank management error: " . $e->getMessage());
            $error = 'Failed to load bank account management';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Get payment terms via AJAX
     */
    public function getPaymentTerms() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            $paymentTerms = $this->vendorModel->getPaymentTerms();
            
            echo json_encode([
                'success' => true,
                'payment_terms' => $paymentTerms
            ]);
            
        } catch (Exception $e) {
            error_log("Get payment terms error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load payment terms']);
        }
    }
    
    /**
     * Get vendors by category via AJAX
     */
    public function getByCategory() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $categoryId = $_GET['category_id'] ?? 0;
        
        if (!$categoryId) {
            echo json_encode(['success' => false, 'message' => 'Category ID is required']);
            return;
        }
        
        try {
            $vendors = $this->vendorModel->getVendorsByCategory($categoryId);
            
            echo json_encode([
                'success' => true,
                'vendors' => $vendors
            ]);
            
        } catch (Exception $e) {
            error_log("Get vendors by category error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load vendors']);
        }
    }
    
    /**
     * Get vendors for dropdown via AJAX
     */
    public function getForDropdown() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $categoryId = $_GET['category_id'] ?? null;
        
        try {
            $vendors = $this->vendorModel->getVendorsForSelect($categoryId);
            
            echo json_encode([
                'success' => true,
                'vendors' => $vendors
            ]);
            
        } catch (Exception $e) {
            error_log("Get vendors dropdown error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load vendors']);
        }
    }
    
    /**
     * Get vendor statistics via AJAX
     */
    public function getStats() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Procurement Officer'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        $vendorId = $_GET['vendor_id'] ?? null;
        
        try {
            $stats = $this->vendorModel->getVendorStatistics($vendorId);
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            error_log("Get vendor stats error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load statistics']);
        }
    }
    
    /**
     * Export vendors to Excel
     */
    public function export() {
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Build filters from GET parameters
            $filters = [];
            if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
            if (!empty($_GET['payment_terms_id'])) $filters['payment_terms_id'] = $_GET['payment_terms_id'];
            if (isset($_GET['is_preferred'])) $filters['is_preferred'] = $_GET['is_preferred'];
            if (!empty($_GET['category_id'])) $filters['category_id'] = $_GET['category_id'];
            
            // Get all vendors (no pagination for export)
            $result = $this->vendorModel->getVendorsWithFilters($filters, 1, 10000);
            $vendors = $result['data'] ?? [];
            
            // Set headers for Excel download
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="vendors_' . date('Y-m-d') . '.xls"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output Excel content
            echo '<table border="1">';
            echo '<tr>';
            echo '<th>ID</th>';
            echo '<th>Name</th>';
            echo '<th>Contact Person</th>';
            echo '<th>Email</th>';
            echo '<th>Phone</th>';
            echo '<th>Address</th>';
            echo '<th>Payment Terms</th>';
            echo '<th>TIN</th>';
            echo '<th>Preferred</th>';
            echo '<th>Rating</th>';
            echo '<th>Bank Accounts</th>';
            echo '<th>Procurement Orders</th>';
            echo '<th>Total Value</th>';
            echo '<th>Created Date</th>';
            echo '</tr>';
            
            foreach ($vendors as $vendor) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($vendor['id']) . '</td>';
                echo '<td>' . htmlspecialchars($vendor['name']) . '</td>';
                echo '<td>' . htmlspecialchars($vendor['contact_person']) . '</td>';
                echo '<td>' . htmlspecialchars($vendor['email']) . '</td>';
                echo '<td>' . htmlspecialchars($vendor['phone']) . '</td>';
                echo '<td>' . htmlspecialchars($vendor['address']) . '</td>';
                echo '<td>' . htmlspecialchars($vendor['payment_term_name'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($vendor['tin']) . '</td>';
                echo '<td>' . ($vendor['is_preferred'] ? 'Yes' : 'No') . '</td>';
                echo '<td>' . ($vendor['rating'] ? number_format($vendor['rating'], 2) : 'N/A') . '</td>';
                echo '<td>' . ($vendor['bank_count'] ?? 0) . '</td>';
                echo '<td>' . ($vendor['procurement_count'] ?? 0) . '</td>';
                
                // Hide financial data from unauthorized users
                if ($this->auth->hasRole(['System Admin', 'Finance Director'])) {
                    echo '<td>' . number_format($vendor['total_procurement_value'] ?? 0, 2) . '</td>';
                } else {
                    echo '<td>***</td>';
                }
                
                echo '<td>' . date('Y-m-d H:i', strtotime($vendor['created_at'])) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            exit;
            
        } catch (Exception $e) {
            error_log("Vendor export error: " . $e->getMessage());
            header('Location: ?route=vendors&error=export_failed');
            exit;
        }
    }
    
    /**
     * ==================== VENDOR INTELLIGENCE FEATURES ====================
     * Enhanced vendor management with AI-driven insights and analytics
     */
    
    /**
     * Display vendor intelligence dashboard
     */
    public function intelligenceDashboard() {
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Asset Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Get vendor overview statistics
            $vendorStats = $this->vendorModel->getVendorStatistics();
            
            // Get top performing vendors
            $topVendors = $this->getTopPerformingVendors(10);
            
            // Get vendor risk summary
            $riskSummary = $this->getVendorRiskSummary();
            
            // Get procurement insights
            $procurementInsights = $this->getProcurementInsights();
            
            // Get delivery performance trends
            $deliveryTrends = $this->getDeliveryPerformanceTrends();
            
            $pageTitle = 'Vendor Intelligence Dashboard - ConstructLink™';
            $pageHeader = 'Vendor Intelligence Dashboard';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Vendors', 'url' => '?route=vendors'],
                ['title' => 'Intelligence Dashboard', 'url' => '?route=vendors/intelligenceDashboard']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/vendors/vendor_intelligence_dashboard.php';
            
        } catch (Exception $e) {
            error_log("Vendor intelligence dashboard error: " . $e->getMessage());
            $error = 'Failed to load vendor intelligence dashboard';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display vendor performance analysis
     */
    public function performanceAnalysis() {
        $vendorId = $_GET['id'] ?? 0;
        
        if (!$vendorId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Asset Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            $vendor = $this->vendorModel->find($vendorId);
            
            if (!$vendor) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Get comprehensive performance analysis
            $performanceData = $this->vendorIntelligenceModel->calculateVendorPerformanceScore($vendorId);
            
            // Get risk assessment
            $riskData = $this->vendorIntelligenceModel->calculateVendorRiskScore($vendorId);
            
            // Get trend analysis
            $trendData = $this->vendorIntelligenceModel->getVendorTrendAnalysis($vendorId, 12);
            
            // Get recommendations
            $recommendations = $this->generateVendorRecommendations($vendorId, $performanceData, $riskData);
            
            $pageTitle = 'Vendor Performance Analysis - ConstructLink™';
            $pageHeader = 'Performance Analysis: ' . htmlspecialchars($vendor['name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Vendors', 'url' => '?route=vendors'],
                ['title' => 'Intelligence Dashboard', 'url' => '?route=vendors/intelligenceDashboard'],
                ['title' => 'Performance Analysis', 'url' => '?route=vendors/performanceAnalysis&id=' . $vendorId]
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/vendors/vendor_performance_analysis.php';
            
        } catch (Exception $e) {
            error_log("Vendor performance analysis error: " . $e->getMessage());
            $error = 'Failed to load vendor performance analysis';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display vendor comparison tool
     */
    public function vendorComparison() {
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Asset Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $vendorIds = $_GET['vendor_ids'] ?? [];
        if (is_string($vendorIds)) {
            $vendorIds = explode(',', $vendorIds);
        }
        $vendorIds = array_filter(array_map('intval', $vendorIds));
        
        $comparisonData = [];
        $errors = [];
        
        if (!empty($vendorIds)) {
            try {
                $comparisonData = $this->vendorIntelligenceModel->getVendorComparisonData($vendorIds);
            } catch (Exception $e) {
                error_log("Vendor comparison error: " . $e->getMessage());
                $errors[] = 'Failed to load comparison data';
            }
        }
        
        // Get all vendors for selection
        $allVendors = $this->vendorModel->getVendorsForSelect();
        
        $pageTitle = 'Vendor Comparison - ConstructLink™';
        $pageHeader = 'Vendor Comparison Tool';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Vendors', 'url' => '?route=vendors'],
            ['title' => 'Intelligence Dashboard', 'url' => '?route=vendors/intelligenceDashboard'],
            ['title' => 'Vendor Comparison', 'url' => '?route=vendors/vendorComparison']
        ];
        
        // Pass auth instance to view
        $auth = $this->auth;
        
        include APP_ROOT . '/views/vendors/vendor_comparison.php';
    }
    
    /**
     * Display vendor risk assessment
     */
    public function riskAssessment() {
        $vendorId = $_GET['id'] ?? 0;
        
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Asset Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $riskAssessments = [];
        $selectedVendor = null;
        
        if ($vendorId) {
            try {
                $selectedVendor = $this->vendorModel->find($vendorId);
                if ($selectedVendor) {
                    $riskAssessments = [$this->vendorIntelligenceModel->calculateVendorRiskScore($vendorId)];
                }
            } catch (Exception $e) {
                error_log("Single vendor risk assessment error: " . $e->getMessage());
                $errors[] = 'Failed to load risk assessment';
            }
        } else {
            // Get risk assessment for all vendors
            try {
                $allVendors = $this->vendorModel->getActiveVendors();
                foreach ($allVendors as $vendor) {
                    $riskData = $this->vendorIntelligenceModel->calculateVendorRiskScore($vendor['id']);
                    $riskData['vendor_name'] = $vendor['name'];
                    $riskAssessments[] = $riskData;
                }
                
                // Sort by risk score (highest first)
                usort($riskAssessments, function($a, $b) {
                    return $b['overall_risk_score'] <=> $a['overall_risk_score'];
                });
                
            } catch (Exception $e) {
                error_log("All vendors risk assessment error: " . $e->getMessage());
                $errors[] = 'Failed to load risk assessments';
            }
        }
        
        $pageTitle = 'Vendor Risk Assessment - ConstructLink™';
        $pageHeader = $selectedVendor ? 
            'Risk Assessment: ' . htmlspecialchars($selectedVendor['name']) : 
            'Vendor Risk Assessment Overview';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Vendors', 'url' => '?route=vendors'],
            ['title' => 'Intelligence Dashboard', 'url' => '?route=vendors/intelligenceDashboard'],
            ['title' => 'Risk Assessment', 'url' => '?route=vendors/riskAssessment']
        ];
        
        // Pass auth instance to view
        $auth = $this->auth;
        
        include APP_ROOT . '/views/vendors/vendor_risk_assessment.php';
    }
    
    /**
     * Get vendor recommendations for procurement
     */
    public function getVendorRecommendations() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Asset Director', 'Procurement Officer'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        $categoryId = $_GET['category_id'] ?? null;
        $projectId = $_GET['project_id'] ?? null;
        $budgetRange = $_GET['budget_range'] ?? null;
        
        try {
            $recommendations = $this->vendorIntelligenceModel->getVendorRecommendations($categoryId, $projectId, $budgetRange);
            
            echo json_encode([
                'success' => true,
                'recommendations' => $recommendations
            ]);
            
        } catch (Exception $e) {
            error_log("Get vendor recommendations error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get recommendations']);
        }
    }
    
    /**
     * Get vendor performance data via AJAX
     */
    public function getPerformanceData() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $vendorId = $_GET['vendor_id'] ?? 0;
        
        if (!$vendorId) {
            echo json_encode(['success' => false, 'message' => 'Vendor ID is required']);
            return;
        }
        
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Asset Director', 'Procurement Officer'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $performanceData = $this->vendorIntelligenceModel->calculateVendorPerformanceScore($vendorId);
            
            echo json_encode([
                'success' => true,
                'data' => $performanceData
            ]);
            
        } catch (Exception $e) {
            error_log("Get vendor performance data error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get performance data']);
        }
    }
    
    /**
     * Get vendor risk data via AJAX
     */
    public function getRiskData() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $vendorId = $_GET['vendor_id'] ?? 0;
        
        if (!$vendorId) {
            echo json_encode(['success' => false, 'message' => 'Vendor ID is required']);
            return;
        }
        
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Asset Director', 'Procurement Officer'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $riskData = $this->vendorIntelligenceModel->calculateVendorRiskScore($vendorId);
            
            echo json_encode([
                'success' => true,
                'data' => $riskData
            ]);
            
        } catch (Exception $e) {
            error_log("Get vendor risk data error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get risk data']);
        }
    }
    
    /**
     * Get vendor trend data via AJAX
     */
    public function getTrendData() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $vendorId = $_GET['vendor_id'] ?? 0;
        $months = $_GET['months'] ?? 12;
        
        if (!$vendorId) {
            echo json_encode(['success' => false, 'message' => 'Vendor ID is required']);
            return;
        }
        
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Asset Director', 'Procurement Officer'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $trendData = $this->vendorIntelligenceModel->getVendorTrendAnalysis($vendorId, $months);
            
            echo json_encode([
                'success' => true,
                'data' => $trendData
            ]);
            
        } catch (Exception $e) {
            error_log("Get vendor trend data error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get trend data']);
        }
    }
    
    /**
     * Get intelligence dashboard data via AJAX
     */
    public function getIntelligenceDashboardData() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Asset Director', 'Procurement Officer'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $dashboardData = [
                'vendor_stats' => $this->vendorModel->getVendorStatistics(),
                'top_vendors' => $this->getTopPerformingVendors(5),
                'risk_summary' => $this->getVendorRiskSummary(),
                'procurement_insights' => $this->getProcurementInsights(),
                'delivery_trends' => $this->getDeliveryPerformanceTrends()
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $dashboardData
            ]);
            
        } catch (Exception $e) {
            error_log("Get intelligence dashboard data error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to get dashboard data']);
        }
    }
    
    // ==================== HELPER METHODS ====================
    
    /**
     * Get top performing vendors
     */
    private function getTopPerformingVendors($limit = 10) {
        try {
            $allVendors = $this->vendorModel->getActiveVendors();
            $topVendors = [];
            
            foreach ($allVendors as $vendor) {
                $performance = $this->vendorIntelligenceModel->calculateVendorPerformanceScore($vendor['id']);
                $vendor['performance_score'] = $performance['overall_score'];
                $vendor['performance_grade'] = $performance['grade'];
                $topVendors[] = $vendor;
            }
            
            // Sort by performance score
            usort($topVendors, function($a, $b) {
                return $b['performance_score'] <=> $a['performance_score'];
            });
            
            return array_slice($topVendors, 0, $limit);
            
        } catch (Exception $e) {
            error_log("Get top performing vendors error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get vendor risk summary
     */
    private function getVendorRiskSummary() {
        try {
            $allVendors = $this->vendorModel->getActiveVendors();
            $riskSummary = [
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
                'minimal' => 0
            ];
            
            foreach ($allVendors as $vendor) {
                $risk = $this->vendorIntelligenceModel->calculateVendorRiskScore($vendor['id']);
                $level = strtolower($risk['risk_level']);
                if (isset($riskSummary[$level])) {
                    $riskSummary[$level]++;
                }
            }
            
            return $riskSummary;
            
        } catch (Exception $e) {
            error_log("Get vendor risk summary error: " . $e->getMessage());
            return ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'minimal' => 0];
        }
    }
    
    /**
     * Get procurement insights
     */
    private function getProcurementInsights() {
        try {
            $sql = "
                SELECT 
                    COUNT(DISTINCT po.vendor_id) as active_vendors,
                    COUNT(po.id) as total_orders,
                    SUM(po.net_total) as total_value,
                    AVG(po.net_total) as avg_order_value,
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) as on_time_deliveries
                FROM procurement_orders po
                WHERE po.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            ";
            
            $stmt = $this->vendorIntelligenceModel->getDatabase()->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetch();
            
            $insights = [
                'active_vendors' => $data['active_vendors'] ?? 0,
                'total_orders' => $data['total_orders'] ?? 0,
                'total_value' => $data['total_value'] ?? 0,
                'avg_order_value' => $data['avg_order_value'] ?? 0,
                'on_time_rate' => $data['total_orders'] > 0 ? 
                    ($data['on_time_deliveries'] / $data['total_orders']) * 100 : 0,
                'completion_rate' => $data['total_orders'] > 0 ? 
                    ($data['delivered_orders'] / $data['total_orders']) * 100 : 0
            ];
            
            return $insights;
            
        } catch (Exception $e) {
            error_log("Get procurement insights error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get delivery performance trends
     */
    private function getDeliveryPerformanceTrends() {
        try {
            $sql = "
                SELECT 
                    DATE_FORMAT(po.created_at, '%Y-%m') as month,
                    COUNT(po.id) as total_orders,
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) as on_time_deliveries,
                    AVG(CASE 
                        WHEN po.actual_delivery_date IS NOT NULL AND po.scheduled_delivery_date IS NOT NULL 
                        THEN DATEDIFF(po.actual_delivery_date, po.scheduled_delivery_date) 
                        ELSE 0 
                    END) as avg_delay
                FROM procurement_orders po
                WHERE po.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(po.created_at, '%Y-%m')
                ORDER BY month ASC
            ";
            
            $stmt = $this->vendorIntelligenceModel->getDatabase()->prepare($sql);
            $stmt->execute();
            $trends = $stmt->fetchAll();
            
            foreach ($trends as &$trend) {
                $trend['on_time_rate'] = $trend['total_orders'] > 0 ? 
                    ($trend['on_time_deliveries'] / $trend['total_orders']) * 100 : 0;
                $trend['completion_rate'] = $trend['total_orders'] > 0 ? 
                    ($trend['delivered_orders'] / $trend['total_orders']) * 100 : 0;
            }
            
            return $trends;
            
        } catch (Exception $e) {
            error_log("Get delivery performance trends error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate vendor recommendations based on performance and risk data
     */
    private function generateVendorRecommendations($vendorId, $performanceData, $riskData) {
        $recommendations = [];
        
        // Performance-based recommendations
        if ($performanceData['overall_score'] < 60) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'Performance',
                'message' => 'Vendor performance is below acceptable levels. Consider implementing improvement measures or finding alternative suppliers.',
                'priority' => 'high'
            ];
        } elseif ($performanceData['overall_score'] < 75) {
            $recommendations[] = [
                'type' => 'info',
                'category' => 'Performance',
                'message' => 'Vendor performance has room for improvement. Consider discussing performance enhancement opportunities.',
                'priority' => 'medium'
            ];
        }
        
        // Risk-based recommendations
        if ($riskData['overall_risk_score'] > 75) {
            $recommendations[] = [
                'type' => 'danger',
                'category' => 'Risk',
                'message' => 'High risk vendor detected. Immediate review and risk mitigation strategies recommended.',
                'priority' => 'critical'
            ];
        } elseif ($riskData['overall_risk_score'] > 50) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'Risk',
                'message' => 'Moderate risk levels identified. Enhanced monitoring and backup supplier identification advised.',
                'priority' => 'high'
            ];
        }
        
        // Add specific recommendations based on metrics
        foreach ($performanceData['metrics'] as $category => $metric) {
            if ($metric['score'] < 50) {
                switch ($category) {
                    case 'delivery':
                        $recommendations[] = [
                            'type' => 'warning',
                            'category' => 'Delivery',
                            'message' => 'Poor delivery performance detected. Establish stricter delivery SLAs and monitoring.',
                            'priority' => 'high'
                        ];
                        break;
                    case 'quality':
                        $recommendations[] = [
                            'type' => 'warning',
                            'category' => 'Quality',
                            'message' => 'Quality issues identified. Implement enhanced quality control and inspection processes.',
                            'priority' => 'high'
                        ];
                        break;
                    case 'cost':
                        $recommendations[] = [
                            'type' => 'info',
                            'category' => 'Cost',
                            'message' => 'Cost competitiveness could be improved. Negotiate better pricing or seek alternative suppliers.',
                            'priority' => 'medium'
                        ];
                        break;
                }
            }
        }
        
        // If no issues found
        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'success',
                'category' => 'Overall',
                'message' => 'Vendor is performing well across all metrics. Continue current partnership and monitoring practices.',
                'priority' => 'low'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * ==================== MVA WORKFLOW SUPPORT ====================
     * Maker-Verifier-Authorizer workflow implementation for vendor operations
     */
    
    /**
     * Create vendor with MVA workflow (MAKER stage)
     */
    public function createWithWorkflow() {
        // Check permissions - Only authorized makers can create
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Procurement Officer'])) {
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
                'contact_info' => Validator::sanitize($_POST['contact_info'] ?? ''),
                'address' => Validator::sanitize($_POST['address'] ?? ''),
                'phone' => Validator::sanitize($_POST['phone'] ?? ''),
                'email' => Validator::sanitize($_POST['email'] ?? ''),
                'contact_person' => Validator::sanitize($_POST['contact_person'] ?? ''),
                'payment_terms_id' => !empty($_POST['payment_terms']) && is_array($_POST['payment_terms']) ? (int)$_POST['payment_terms'][0] : (!empty($_POST['payment_terms_id']) ? (int)$_POST['payment_terms_id'] : null),
                'tin' => Validator::sanitize($_POST['tin'] ?? ''),
                'vendor_type' => Validator::sanitize($_POST['vendor_type'] ?? ''),
                'rdo_code' => Validator::sanitize($_POST['rdo_code'] ?? ''),
                'is_preferred' => isset($_POST['is_preferred']) ? 1 : 0,
                'rating' => !empty($_POST['rating']) ? (float)$_POST['rating'] : null,
                'notes' => Validator::sanitize($_POST['notes'] ?? ''),
                'categories' => $_POST['categories'] ?? [],
                'bank_accounts' => [],
                'workflow_type' => 'vendor_creation',
                'maker_id' => $currentUser['id'],
                'maker_comments' => Validator::sanitize($_POST['maker_comments'] ?? ''),
                'status' => 'pending_verification'
            ];
            
            // Process bank accounts if provided
            if (!empty($_POST['bank_accounts'])) {
                foreach ($_POST['bank_accounts'] as $bankData) {
                    if (!empty($bankData['bank_name']) && !empty($bankData['account_number'])) {
                        $formData['bank_accounts'][] = [
                            'bank_name' => Validator::sanitize($bankData['bank_name']),
                            'account_number' => Validator::sanitize($bankData['account_number']),
                            'account_name' => Validator::sanitize($bankData['account_name'] ?? ''),
                            'account_type' => $bankData['account_type'] ?? 'Checking',
                            'currency' => $bankData['currency'] ?? 'PHP',
                            'bank_category' => $bankData['bank_category'] ?? 'Primary',
                            'swift_code' => Validator::sanitize($bankData['swift_code'] ?? ''),
                            'branch' => Validator::sanitize($bankData['branch'] ?? ''),
                            'is_active' => isset($bankData['is_active']) ? 1 : 1
                        ];
                    }
                }
            }
            
            $result = $this->vendorModel->createVendorWithWorkflow($formData);
            
            if ($result['success']) {
                // Log the workflow action
                $this->logWorkflowAction('vendor_creation', $result['workflow_id'], 'created', 
                    'Vendor creation request submitted for verification', $currentUser['id']);
                
                header('Location: ?route=vendors/workflowStatus&id=' . $result['workflow_id'] . '&message=vendor_submitted_for_verification');
                exit;
            } else {
                if (isset($result['errors'])) {
                    $errors = $result['errors'];
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        // Get form options
        $paymentTerms = $this->vendorModel->getPaymentTerms();
        $vendorCategories = $this->vendorModel->getVendorCategories();
        
        $pageTitle = 'Create Vendor (MVA Workflow) - ConstructLink™';
        $pageHeader = 'Create New Vendor with Workflow';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Vendors', 'url' => '?route=vendors'],
            ['title' => 'Create Vendor (Workflow)', 'url' => '?route=vendors/createWithWorkflow']
        ];
        
        include APP_ROOT . '/views/vendors/create_workflow.php';
    }
    
    /**
     * Verify vendor creation (VERIFIER stage)
     */
    public function verifyCreation() {
        $workflowId = $_GET['id'] ?? 0;
        
        if (!$workflowId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        // Check permissions - System Admin and Finance Director can verify
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $workflowData = $this->vendorModel->getWorkflowData($workflowId);
            
            if (!$workflowData || $workflowData['workflow_type'] !== 'vendor_creation') {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check if already processed
            if ($workflowData['status'] !== 'pending_verification') {
                $messages[] = 'This workflow has already been processed.';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $action = $_POST['action'] ?? '';
                $verifierComments = Validator::sanitize($_POST['verifier_comments'] ?? '');
                
                if ($action === 'approve') {
                    $result = $this->vendorModel->approveVendorWorkflow($workflowId, $currentUser['id'], $verifierComments);
                    
                    if ($result['success']) {
                        // Log the workflow action
                        $this->logWorkflowAction('vendor_creation', $workflowId, 'verified', 
                            'Vendor creation verified and approved for authorization', $currentUser['id']);
                        
                        header('Location: ?route=vendors/workflowStatus&id=' . $workflowId . '&message=vendor_verified');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                } elseif ($action === 'reject') {
                    $result = $this->vendorModel->rejectVendorWorkflow($workflowId, $currentUser['id'], $verifierComments, 'verification');
                    
                    if ($result['success']) {
                        // Log the workflow action
                        $this->logWorkflowAction('vendor_creation', $workflowId, 'rejected_verification', 
                            'Vendor creation rejected during verification', $currentUser['id']);
                        
                        header('Location: ?route=vendors/workflowStatus&id=' . $workflowId . '&message=vendor_rejected');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Verify Vendor Creation - ConstructLink™';
            $pageHeader = 'Verify Vendor Creation Request';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Vendors', 'url' => '?route=vendors'],
                ['title' => 'Verify Creation', 'url' => '?route=vendors/verifyCreation&id=' . $workflowId]
            ];
            
            include APP_ROOT . '/views/vendors/verify_creation.php';
            
        } catch (Exception $e) {
            error_log("Vendor verification error: " . $e->getMessage());
            $error = 'Failed to load verification details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Authorize vendor creation (AUTHORIZER stage)
     */
    public function authorizeCreation() {
        $workflowId = $_GET['id'] ?? 0;
        
        if (!$workflowId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        // Check permissions - Only System Admin can authorize
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin') {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $workflowData = $this->vendorModel->getWorkflowData($workflowId);
            
            if (!$workflowData || $workflowData['workflow_type'] !== 'vendor_creation') {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check if ready for authorization
            if ($workflowData['status'] !== 'pending_authorization') {
                $messages[] = 'This workflow is not ready for authorization or has already been processed.';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $action = $_POST['action'] ?? '';
                $authorizerComments = Validator::sanitize($_POST['authorizer_comments'] ?? '');
                
                if ($action === 'authorize') {
                    $result = $this->vendorModel->finalizeVendorWorkflow($workflowId, $currentUser['id'], $authorizerComments);
                    
                    if ($result['success']) {
                        // Log the workflow action
                        $this->logWorkflowAction('vendor_creation', $workflowId, 'authorized', 
                            'Vendor creation authorized and finalized', $currentUser['id']);
                        
                        header('Location: ?route=vendors/view&id=' . $result['vendor_id'] . '&message=vendor_created_successfully');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                } elseif ($action === 'reject') {
                    $result = $this->vendorModel->rejectVendorWorkflow($workflowId, $currentUser['id'], $authorizerComments, 'authorization');
                    
                    if ($result['success']) {
                        // Log the workflow action
                        $this->logWorkflowAction('vendor_creation', $workflowId, 'rejected_authorization', 
                            'Vendor creation rejected during authorization', $currentUser['id']);
                        
                        header('Location: ?route=vendors/workflowStatus&id=' . $workflowId . '&message=vendor_rejected');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Authorize Vendor Creation - ConstructLink™';
            $pageHeader = 'Authorize Vendor Creation Request';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Vendors', 'url' => '?route=vendors'],
                ['title' => 'Authorize Creation', 'url' => '?route=vendors/authorizeCreation&id=' . $workflowId]
            ];
            
            include APP_ROOT . '/views/vendors/authorize_creation.php';
            
        } catch (Exception $e) {
            error_log("Vendor authorization error: " . $e->getMessage());
            $error = 'Failed to load authorization details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display workflow status
     */
    public function workflowStatus() {
        $workflowId = $_GET['id'] ?? 0;
        
        if (!$workflowId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            $workflowData = $this->vendorModel->getWorkflowData($workflowId);
            
            if (!$workflowData) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Get workflow history
            $workflowHistory = $this->vendorModel->getWorkflowHistory($workflowId);
            
            $pageTitle = 'Workflow Status - ConstructLink™';
            $pageHeader = 'Vendor Workflow Status';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Vendors', 'url' => '?route=vendors'],
                ['title' => 'Workflow Status', 'url' => '?route=vendors/workflowStatus&id=' . $workflowId]
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/vendors/workflow_status.php';
            
        } catch (Exception $e) {
            error_log("Workflow status error: " . $e->getMessage());
            $error = 'Failed to load workflow status';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display pending workflows for current user
     */
    public function pendingWorkflows() {
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Finance Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        
        try {
            // Get pending workflows based on user role
            $pendingWorkflows = [];
            
            if ($userRole === 'System Admin') {
                // System Admin can see all pending workflows
                $pendingWorkflows = $this->vendorModel->getPendingWorkflows(null, $page, $perPage);
            } elseif ($this->auth->hasRole(['Finance Director'])) {
                // Finance Director sees pending verification workflows
                $pendingWorkflows = $this->vendorModel->getPendingWorkflows('pending_verification', $page, $perPage);
            } elseif ($this->auth->hasRole(['Procurement Officer'])) {
                // Procurement Officer sees their own submitted workflows
                $pendingWorkflows = $this->vendorModel->getUserWorkflows($currentUser['id'], $page, $perPage);
            }
            
            $pageTitle = 'Pending Workflows - ConstructLink™';
            $pageHeader = 'Pending Vendor Workflows';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Vendors', 'url' => '?route=vendors'],
                ['title' => 'Pending Workflows', 'url' => '?route=vendors/pendingWorkflows']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/vendors/pending_workflows.php';
            
        } catch (Exception $e) {
            error_log("Pending workflows error: " . $e->getMessage());
            $error = 'Failed to load pending workflows';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Log workflow actions for audit trail
     */
    private function logWorkflowAction($workflowType, $workflowId, $action, $description, $userId) {
        try {
            $this->vendorModel->logWorkflowAction([
                'workflow_type' => $workflowType,
                'workflow_id' => $workflowId,
                'action' => $action,
                'description' => $description,
                'user_id' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Workflow logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Helper method to get performance grade from score
     */
    private function getPerformanceGrade($score) {
        if ($score >= 95) return 'A+';
        if ($score >= 90) return 'A';
        if ($score >= 85) return 'A-';
        if ($score >= 80) return 'B+';
        if ($score >= 75) return 'B';
        if ($score >= 70) return 'B-';
        if ($score >= 65) return 'C+';
        if ($score >= 60) return 'C';
        if ($score >= 55) return 'C-';
        if ($score >= 50) return 'D';
        return 'F';
    }
    
    /**
     * Helper method to get risk level from score
     */
    private function getRiskLevel($score) {
        if ($score >= 80) return 'Critical';
        if ($score >= 60) return 'High';
        if ($score >= 40) return 'Medium';
        if ($score >= 20) return 'Low';
        return 'Minimal';
    }
    
    /**
     * Intelligent Vendor Product Catalog
     * Shows products available from vendors based on procurement history
     */
    public function productCatalog() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Get filters from request
            $filters = [
                'search' => $_GET['search'] ?? '',
                'category_id' => $_GET['category_id'] ?? '',
                'vendor_id' => $_GET['vendor_id'] ?? '',
                'price_min' => $_GET['price_min'] ?? '',
                'price_max' => $_GET['price_max'] ?? '',
                'preferred_only' => isset($_GET['preferred_only']) ? 1 : 0,
                'min_rating' => $_GET['min_rating'] ?? '',
                'sort_by' => $_GET['sort_by'] ?? 'relevance',
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => 25
            ];
            
            // Get product catalog data
            $productCatalog = $this->vendorProductModel->getVendorProductCatalog($filters);
            
            // Get filter options
            $categories = $this->getCategories();
            $vendors = $this->vendorModel->getVendorsForSelectEnhanced();
            
            // Get catalog statistics
            $catalogStats = $this->getCatalogStatistics($filters);
            
            // Calculate pagination
            $totalResults = $catalogStats['total_products'] ?? 0;
            $totalPages = ceil($totalResults / $filters['limit']);
            $pagination = [
                'current_page' => $filters['page'],
                'total_pages' => $totalPages,
                'total_results' => $totalResults,
                'per_page' => $filters['limit']
            ];
            
            $pageTitle = 'Vendor Product Catalog - ConstructLink™';
            $pageHeader = 'Intelligent Vendor Product Catalog';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Vendors', 'url' => '?route=vendors'],
                ['title' => 'Product Catalog', 'url' => '?route=vendors/productCatalog']
            ];
            
            $auth = $this->auth;
            
            include APP_ROOT . '/views/vendors/product_catalog.php';
            
        } catch (Exception $e) {
            error_log("Vendor product catalog error: " . $e->getMessage());
            $error = 'Failed to load vendor product catalog';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Get vendor recommendations for a specific product (AJAX endpoint)
     */
    public function getProductRecommendations() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        try {
            $productName = $_GET['product'] ?? '';
            $limit = (int)($_GET['limit'] ?? 10);
            
            if (empty($productName)) {
                echo json_encode(['error' => 'Product name is required']);
                return;
            }
            
            $recommendations = $this->vendorProductModel->getVendorRecommendationsForProduct($productName, ['limit' => $limit]);
            
            echo json_encode([
                'success' => true,
                'recommendations' => $recommendations
            ]);
            
        } catch (Exception $e) {
            error_log("Get product recommendations error: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to get recommendations']);
        }
    }
    
    /**
     * Get product price history (AJAX endpoint)
     */
    public function getProductPriceHistory() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        try {
            $productName = $_GET['product'] ?? '';
            $vendorId = $_GET['vendor_id'] ?? null;
            
            if (empty($productName)) {
                echo json_encode(['error' => 'Product name is required']);
                return;
            }
            
            $priceHistory = $this->vendorProductModel->getProductPriceHistory($productName, $vendorId);
            
            echo json_encode([
                'success' => true,
                'data' => $priceHistory
            ]);
            
        } catch (Exception $e) {
            error_log("Get product price history error: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to get price history']);
        }
    }
    
    /**
     * Get similar products (AJAX endpoint)
     */
    public function getSimilarProducts() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        try {
            $productName = $_GET['product'] ?? '';
            $limit = (int)($_GET['limit'] ?? 20);
            
            if (empty($productName)) {
                echo json_encode(['error' => 'Product name is required']);
                return;
            }
            
            $similarProducts = $this->vendorProductModel->getSimilarProducts($productName, ['limit' => $limit]);
            
            echo json_encode([
                'success' => true,
                'products' => $similarProducts
            ]);
            
        } catch (Exception $e) {
            error_log("Get similar products error: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to get similar products']);
        }
    }
    
    /**
     * Vendor Product Search API endpoint for intelligent search suggestions
     */
    public function productSearch() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        try {
            $query = $_GET['q'] ?? '';
            $limit = (int)($_GET['limit'] ?? 10);
            
            if (strlen($query) < 2) {
                echo json_encode(['suggestions' => []]);
                return;
            }
            
            // Get intelligent search suggestions
            $suggestions = $this->vendorProductModel->getVendorProductCatalog([
                'search' => $query,
                'limit' => $limit,
                'sort_by' => 'relevance'
            ]);
            
            // Format for autocomplete
            $formattedSuggestions = [];
            foreach ($suggestions as $item) {
                $formattedSuggestions[] = [
                    'label' => $item['item_name'] . ($item['model'] ? ' - ' . $item['model'] : ''),
                    'value' => $item['item_name'],
                    'vendor' => $item['vendor_name'],
                    'price' => number_format($item['avg_price'], 2),
                    'category' => $item['category_name'] ?? 'Uncategorized'
                ];
            }
            
            echo json_encode([
                'success' => true,
                'suggestions' => $formattedSuggestions
            ]);
            
        } catch (Exception $e) {
            error_log("Product search error: " . $e->getMessage());
            echo json_encode(['error' => 'Search failed']);
        }
    }
    
    /**
     * Get categories for filter dropdown
     */
    private function getCategories() {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT c.id, c.name 
                FROM categories c 
                INNER JOIN procurement_items pi ON c.id = pi.category_id
                ORDER BY c.name
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get categories error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get catalog statistics for dashboard display
     */
    private function getCatalogStatistics($filters) {
        try {
            $sql = "
                SELECT 
                    COUNT(DISTINCT CONCAT(v.id, '-', pi.item_name, '-', pi.model)) as total_products,
                    COUNT(DISTINCT v.id) as total_vendors,
                    COUNT(DISTINCT pi.category_id) as total_categories,
                    AVG(pi.unit_price) as avg_price,
                    MIN(pi.unit_price) as min_price,
                    MAX(pi.unit_price) as max_price
                FROM procurement_items pi
                INNER JOIN procurement_orders po ON pi.procurement_order_id = po.id
                INNER JOIN vendors v ON po.vendor_id = v.id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Apply same filters as main query
            if (!empty($filters['search'])) {
                $searchTerm = '%' . $filters['search'] . '%';
                $sql .= " AND (pi.item_name LIKE ? OR pi.description LIKE ? OR pi.specifications LIKE ?)";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            if (!empty($filters['category_id'])) {
                $sql .= " AND pi.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() ?: [];
            
        } catch (Exception $e) {
            error_log("Get catalog statistics error: " . $e->getMessage());
            return [];
        }
    }
}
?>
