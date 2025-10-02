<?php
/**
 * ConstructLink™ BIR Form 2307 Controller
 * Handles Certificate of Creditable Tax Withheld at Source operations
 */

class Bir2307Controller {
    
    private $bir2307Model;
    private $procurementModel;
    private $vendorModel;
    private $atcCodeModel;
    private $auth;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        
        // Initialize models
        $this->bir2307Model = new Bir2307Model();
        $this->procurementModel = new ProcurementOrderModel();
        $this->vendorModel = new VendorModel();
        $this->atcCodeModel = new AtcCodeModel();
    }
    
    /**
     * Generate BIR 2307 form for procurement order
     */
    public function generate() {
        echo "<h1>BIR 2307 Generation Test</h1>";
        echo "<p>PO ID: " . ($_GET['po_id'] ?? 'Not provided') . "</p>";
        
        try {
            $procurementOrderId = $_GET['po_id'] ?? 0;
            
            if (!$procurementOrderId) {
                echo "<p style='color: red;'>Error: No PO ID provided</p>";
                echo "<br><a href='?route=procurement-orders' class='btn btn-primary'>Go to Procurement Orders</a>";
                return;
            }
            
            // Test procurement order lookup
            $procurementOrder = $this->procurementModel->find($procurementOrderId);
            
            if (!$procurementOrder) {
                echo "<p style='color: red;'>Error: Procurement order not found</p>";
                echo "<br><a href='?route=procurement-orders' class='btn btn-primary'>Go to Procurement Orders</a>";
                return;
            }
            
            echo "<p style='color: green;'>✓ Found PO: {$procurementOrder['po_number']}</p>";
            echo "<p>Status: {$procurementOrder['status']}</p>";
            echo "<p>Vendor ID: {$procurementOrder['vendor_id']}</p>";
            
            // Test vendor lookup
            $vendor = $this->vendorModel->find($procurementOrder['vendor_id']);
            
            if (!$vendor) {
                echo "<p style='color: red;'>Error: Vendor not found</p>";
                echo "<br><a href='?route=procurement-orders/view&id=" . $procurementOrderId . "' class='btn btn-primary'>Go Back to PO</a>";
                return;
            }
            
            echo "<p style='color: green;'>✓ Found Vendor: {$vendor['name']}</p>";
            echo "<p>Vendor TIN: " . ($vendor['tin'] ?? 'Not set') . "</p>";
            
            if (empty($vendor['tin'])) {
                echo "<p style='color: orange;'>Warning: Vendor TIN is missing</p>";
                echo "<p>To generate BIR 2307, please add a TIN to the vendor record.</p>";
            } else {
                echo "<p style='color: green;'>✓ Vendor has TIN - Ready for BIR 2307 generation!</p>";
                echo "<p><strong>Next step:</strong> Implement full BIR form generation</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
        }
        
        echo "<br><a href='?route=procurement-orders/view&id=" . $procurementOrderId . "' class='btn btn-primary'>Go Back to PO</a>";
    }
}
                exit;
            }
            
            // Get procurement order
            $procurementOrder = $this->procurementModel->find($procurementOrderId);
            
            if (!$procurementOrder) {
                $_SESSION['error'] = 'Procurement order not found';
                header('Location: ?route=procurement-orders');
                exit;
            }
            
            // Check if form already exists
            $existingForm = $this->bir2307Model->getFormByProcurementOrderId($procurementOrderId);
            
            if ($existingForm) {
                // Redirect to view existing form
                header('Location: ?route=bir2307/view&id=' . $existingForm['id']);
                exit;
            }
            
            // Check if BIR tables exist before attempting generation
            $db = Database::getInstance()->getConnection();
            try {
                $checkSql = "SHOW TABLES LIKE 'bir_2307_forms'";
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->execute();
                
                if ($checkStmt->rowCount() === 0) {
                    $_SESSION['error'] = 'BIR 2307 system not configured. Please run the database migration first.';
                    header('Location: ?route=procurement-orders/view&id=' . $procurementOrderId);
                    exit;
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'Database error: BIR 2307 tables not found. Please run the migration.';
                header('Location: ?route=procurement-orders/view&id=' . $procurementOrderId);
                exit;
            }
            
            // Generate the form
            $result = $this->bir2307Model->generateForm($procurementOrderId, $user['id']);
            
            if ($result['success']) {
                $_SESSION['success'] = 'BIR Form 2307 generated successfully';
                header('Location: ?route=bir2307/view&id=' . $result['form']['id']);
            } else {
                $_SESSION['error'] = $result['message'] ?? 'Failed to generate BIR Form 2307';
                header('Location: ?route=procurement-orders/view&id=' . $procurementOrderId);
            }
            exit;
            
        } catch (Exception $e) {
            error_log("Generate BIR 2307 error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred while generating the form';
            header('Location: ?route=procurement-orders');
            exit;
        }
    }
    
    /**
     * View BIR 2307 form
     */
    public function view() {
        if (!$this->auth->isAuthenticated()) {
            header('Location: ?route=login');
            exit;
        }
        
        $user = $this->auth->getCurrentUser();
        
        try {
            $formId = $_GET['id'] ?? 0;
            
            if (!$formId) {
                $_SESSION['error'] = 'Invalid form ID';
                header('Location: ?route=procurement-orders');
                exit;
            }
            
            // Get form details
            $form = $this->bir2307Model->find($formId);
            
            if (!$form) {
                $_SESSION['error'] = 'Form not found';
                header('Location: ?route=procurement-orders');
                exit;
            }
            
            // Get related data
            $procurementOrder = $this->procurementModel->find($form['procurement_order_id']);
            $vendor = $this->vendorModel->find($form['vendor_id']);
            
            // Decode JSON data
            $form['income_payments'] = json_decode($form['income_payments'], true) ?: [];
            $form['money_payments'] = json_decode($form['money_payments'], true) ?: [];
            
            // Set page data
            $pageTitle = 'BIR Form 2307 - ' . $form['form_number'];
            
            // Load view
            include APP_ROOT . '/views/bir2307/view.php';
            
        } catch (Exception $e) {
            error_log("View BIR 2307 error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred while loading the form';
            header('Location: ?route=procurement-orders');
            exit;
        }
    }
    
    /**
     * Print preview for BIR 2307 form
     */
    public function printPreview() {
        if (!$this->auth->isAuthenticated()) {
            header('Location: ?route=login');
            exit;
        }
        
        $user = $this->auth->getCurrentUser();
        
        try {
            $formId = $_GET['id'] ?? 0;
            
            if (!$formId) {
                http_response_code(400);
                die('Invalid form ID');
            }
            
            // Get form details
            $form = $this->bir2307Model->find($formId);
            
            if (!$form) {
                http_response_code(404);
                die('Form not found');
            }
            
            // Get related data
            $procurementOrder = $this->procurementModel->find($form['procurement_order_id']);
            $vendor = $this->vendorModel->find($form['vendor_id']);
            
            // Decode JSON data
            $form['income_payments'] = json_decode($form['income_payments'], true) ?: [];
            $form['money_payments'] = json_decode($form['money_payments'], true) ?: [];
            
            // Get company info
            require_once APP_ROOT . '/config/company.php';
            $companyInfo = getCompanyInfo();
            
            // Update status to Printed if it was Generated
            if ($form['status'] === 'Generated') {
                $this->bir2307Model->updateFormStatus($formId, 'Printed', $user['id']);
            }
            
            // Set page data
            $pageTitle = 'BIR Form 2307 - Print Preview';
            
            // Load print preview template
            include APP_ROOT . '/views/bir2307/print-preview.php';
            
        } catch (Exception $e) {
            error_log("Print preview BIR 2307 error: " . $e->getMessage());
            http_response_code(500);
            die('An error occurred while generating the print preview');
        }
    }
    
    /**
     * List all BIR 2307 forms
     */
    public function index() {
        if (!$this->auth->isAuthenticated()) {
            header('Location: ?route=login');
            exit;
        }
        
        // Check permissions
        $user = $this->auth->getCurrentUser();
        if (!in_array($user['role_name'], ['System Admin', 'Finance Officer', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            exit;
        }
        
        try {
            // Get filters
            $quarter = $_GET['quarter'] ?? null;
            $year = $_GET['year'] ?? date('Y');
            $vendorId = $_GET['vendor_id'] ?? null;
            $status = $_GET['status'] ?? null;
            
            // Build query
            $sql = "
                SELECT f.*, v.name as vendor_name, v.tin as vendor_tin,
                       po.po_number, po.title as po_title,
                       u.full_name as generated_by_name
                FROM bir_2307_forms f
                LEFT JOIN vendors v ON f.vendor_id = v.id
                LEFT JOIN procurement_orders po ON f.procurement_order_id = po.id
                LEFT JOIN users u ON f.generated_by = u.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($quarter) {
                $sql .= " AND f.quarter = ?";
                $params[] = $quarter;
            }
            
            if ($year) {
                $sql .= " AND f.year = ?";
                $params[] = $year;
            }
            
            if ($vendorId) {
                $sql .= " AND f.vendor_id = ?";
                $params[] = $vendorId;
            }
            
            if ($status) {
                $sql .= " AND f.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY f.created_at DESC";
            
            $stmt = $this->bir2307Model->db->prepare($sql);
            $stmt->execute($params);
            $forms = $stmt->fetchAll();
            
            // Get vendors for filter dropdown
            $vendors = $this->vendorModel->getAll();
            
            // Set page data
            $pageTitle = 'BIR Form 2307 List';
            
            // Load view
            include APP_ROOT . '/views/bir2307/index.php';
            
        } catch (Exception $e) {
            error_log("List BIR 2307 forms error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred while loading the forms';
            header('Location: ?route=dashboard');
            exit;
        }
    }
    
    /**
     * Update form status
     */
    public function updateStatus() {
        if (!$this->auth->isAuthenticated()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $user = $this->auth->getCurrentUser();
        
        try {
            $formId = $_POST['form_id'] ?? 0;
            $status = $_POST['status'] ?? '';
            
            if (!$formId || !$status) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit;
            }
            
            $result = $this->bir2307Model->updateFormStatus($formId, $status, $user['id']);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Update BIR 2307 status error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'An error occurred']);
        }
    }
    
    /**
     * Get ATC codes for AJAX requests
     */
    public function getAtcCodes() {
        if (!$this->auth->isAuthenticated()) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
        
        try {
            $category = $_GET['category'] ?? null;
            $purchaseType = $_GET['purchase_type'] ?? null;
            
            if ($category) {
                $codes = $this->atcCodeModel->getAtcCodesByCategory($category);
            } elseif ($purchaseType) {
                // Map purchase type to category
                $categoryMap = [
                    'Goods' => 'Goods',
                    'Services' => 'Services',
                    'Rental' => 'Rental',
                    'Professional Services' => 'Professional/Talent Fees'
                ];
                
                $mappedCategory = $categoryMap[$purchaseType] ?? 'Services';
                $codes = $this->atcCodeModel->getAtcCodesByCategory($mappedCategory);
            } else {
                $codes = $this->atcCodeModel->getProcurementAtcCodes();
            }
            
            header('Content-Type: application/json');
            echo json_encode($codes);
            
        } catch (Exception $e) {
            error_log("Get ATC codes error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([]);
        }
    }
    
    /**
     * Calculate EWT for AJAX requests
     */
    public function calculateEwt() {
        if (!$this->auth->isAuthenticated()) {
            header('Content-Type: application/json');
            echo json_encode(['ewt_amount' => 0]);
            exit;
        }
        
        try {
            $atcCodeId = $_POST['atc_code_id'] ?? 0;
            $amount = $_POST['amount'] ?? 0;
            $includeVat = $_POST['include_vat'] ?? true;
            
            if (!$atcCodeId || !$amount) {
                header('Content-Type: application/json');
                echo json_encode(['ewt_amount' => 0]);
                exit;
            }
            
            $ewtAmount = $this->atcCodeModel->calculateEWT($atcCodeId, $amount, $includeVat);
            
            header('Content-Type: application/json');
            echo json_encode(['ewt_amount' => $ewtAmount]);
            
        } catch (Exception $e) {
            error_log("Calculate EWT error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['ewt_amount' => 0]);
        }
    }
    
    /**
     * Batch generate BIR 2307 forms for a quarter
     */
    public function batchGenerate() {
        if (!$this->auth->isAuthenticated()) {
            header('Location: ?route=login');
            exit;
        }
        
        // Check permissions
        if (!in_array($user['role_name'], ['System Admin', 'Finance Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            exit;
        }
        
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $quarter = $_POST['quarter'] ?? '';
                $year = $_POST['year'] ?? date('Y');
                
                // Get all procurement orders for the quarter
                $periodDetails = $this->getPeriodDetails($quarter, $year);
                
                $sql = "
                    SELECT po.* 
                    FROM procurement_orders po
                    WHERE po.created_at >= ? 
                        AND po.created_at <= ?
                        AND po.status IN ('Paid', 'Completed')
                        AND po.bir_2307_generated = 0
                ";
                
                $stmt = $this->procurementModel->db->prepare($sql);
                $stmt->execute([$periodDetails['period_from'], $periodDetails['period_to'] . ' 23:59:59']);
                $orders = $stmt->fetchAll();
                
                $generated = 0;
                $failed = 0;
                
                foreach ($orders as $order) {
                    $result = $this->bir2307Model->generateForm($order['id'], $user['id']);
                    if ($result['success']) {
                        $generated++;
                    } else {
                        $failed++;
                    }
                }
                
                $_SESSION['success'] = "Batch generation completed. Generated: $generated, Failed: $failed";
                header('Location: ?route=bir2307');
                exit;
            }
            
            // Show batch generation form
            $pageTitle = 'Batch Generate BIR Form 2307';
            include APP_ROOT . '/views/bir2307/batch-generate.php';
            
        } catch (Exception $e) {
            error_log("Batch generate BIR 2307 error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred during batch generation';
            header('Location: ?route=bir2307');
            exit;
        }
    }
    
    /**
     * Get period details for quarter
     */
    private function getPeriodDetails($quarter, $year) {
        $quarters = [
            '1st' => ['period_from' => "$year-01-01", 'period_to' => "$year-03-31"],
            '2nd' => ['period_from' => "$year-04-01", 'period_to' => "$year-06-30"],
            '3rd' => ['period_from' => "$year-07-01", 'period_to' => "$year-09-30"],
            '4th' => ['period_from' => "$year-10-01", 'period_to' => "$year-12-31"]
        ];
        
        return $quarters[$quarter] ?? $quarters['1st'];
    }
}