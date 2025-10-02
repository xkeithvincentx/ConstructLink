<?php
/**
 * ConstructLink™ Procurement Order Controller
 * Handles multi-item procurement orders
 */

class ProcurementOrderController {
    private $auth;
    private $procurementOrderModel;
    private $procurementItemModel;
    private $roleConfig;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->roleConfig = require APP_ROOT . '/config/roles.php';
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
        
        // Initialize models
        $this->procurementOrderModel = new ProcurementOrderModel();
        $this->procurementItemModel = new ProcurementItemModel();
    }
    
    /**
     * Get project IDs that the current user is assigned to
     * Returns array of project IDs based on user role and assignments
     */
    private function getUserAssignedProjectIds($userId, $userRole) {
        return $this->procurementOrderModel->getUserAssignedProjectIds($userId, $userRole);
    }
    
    /**
     * Display procurement orders listing
     */
    public function index() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/view'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        
        // Get current user info
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Build filters
        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['project_id'])) $filters['project_id'] = $_GET['project_id'];
        if (!empty($_GET['vendor_id'])) $filters['vendor_id'] = $_GET['vendor_id'];
        if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
        if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        
        // Apply project assignment filtering for specific roles
        if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
            $assignedProjectIds = $this->getUserAssignedProjectIds($currentUser['id'], $userRole);
            if (!empty($assignedProjectIds)) {
                $filters['assigned_project_ids'] = $assignedProjectIds;
                error_log("DEBUG: Index method applying filter for projects: " . implode(',', $assignedProjectIds));
            } else {
                // If user has no assigned projects, show no results
                $filters['assigned_project_ids'] = [-1];
                error_log("DEBUG: Index method - No assigned projects found, filtering to show no results");
            }
        }
        
        try {
            // Get procurement orders with pagination
            $result = $this->procurementOrderModel->getProcurementOrdersWithFilters($filters, $page, $perPage);
            $procurementOrders = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];
            
            // Get statistics
            $stats = $this->procurementOrderModel->getProcurementStatistics();
            
            // Get delivery alerts with project assignment filtering
            $alertProjectIds = null;
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                $alertProjectIds = $assignedProjectIds ?? $this->getUserAssignedProjectIds($currentUser['id'], $userRole);
            }
            $deliveryAlerts = $this->procurementOrderModel->getOrdersWithDeliveryAlerts($userRole, $currentUser['id'], $alertProjectIds);
            
            // Get filter options
            $projectModel = new ProjectModel();
            $vendorModel = new VendorModel();
            
            $projects = $projectModel->getActiveProjects();
            $vendors = $vendorModel->findAll([], "name ASC", 100);
            
            $pageTitle = 'Procurement Orders - ConstructLink™';
            $pageHeader = 'Procurement Orders';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/procurement-orders/index.php';
            
        } catch (Exception $e) {
            error_log("Procurement orders listing error: " . $e->getMessage());
            $error = 'Failed to load procurement orders';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create procurement order form
     */
    public function create() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/create'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        $formData = [];
        $items = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            // Handle file uploads FIRST
            require_once APP_ROOT . '/core/ProcurementFileUploader.php';
            $fileResult = ProcurementFileUploader::handleProcurementFiles($_FILES);
            if (!empty($fileResult['errors'])) {
                $errors = array_merge($errors, $fileResult['errors']);
            }
            
            // Process form submission
            $formData = [
                'vendor_id' => (int)($_POST['vendor_id'] ?? 0),
                'project_id' => (int)($_POST['project_id'] ?? 0),
                'title' => Validator::sanitize($_POST['title'] ?? ''),
                'package_scope' => Validator::sanitize($_POST['package_scope'] ?? ''),
                'work_breakdown' => Validator::sanitize($_POST['work_breakdown'] ?? ''),
                'budget_allocation' => (float)($_POST['budget_allocation'] ?? 0),
                'justification' => Validator::sanitize($_POST['justification'] ?? ''),
                'date_needed' => !empty($_POST['date_needed']) ? $_POST['date_needed'] : null,
                'delivery_method' => Validator::sanitize($_POST['delivery_method'] ?? ''),
                'delivery_location' => Validator::sanitize($_POST['delivery_location'] ?? ''),
                'quotation_number' => Validator::sanitize($_POST['quotation_number'] ?? ''),
                'quotation_date' => !empty($_POST['quotation_date']) ? $_POST['quotation_date'] : null,
                'vat_rate' => (float)($_POST['vat_rate'] ?? 12.00),
                'ewt_rate' => (float)($_POST['ewt_rate'] ?? 1.00),
                'handling_fee' => (float)($_POST['handling_fee'] ?? 0),
                'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
                'notes' => Validator::sanitize($_POST['notes'] ?? ''),
                'requested_by' => $this->auth->getCurrentUser()['id'],
                'status' => 'Draft'
            ];
            
            // Add uploaded files to form data
            $formData = array_merge($formData, $fileResult['files']);
            
            // Process items
            if (!empty($_POST['items'])) {
                foreach ($_POST['items'] as $itemData) {
                    if (!empty($itemData['item_name']) && !empty($itemData['quantity']) && !empty($itemData['unit_price'])) {
                        $items[] = [
                            'item_name' => Validator::sanitize($itemData['item_name']),
                            'description' => Validator::sanitize($itemData['description'] ?? ''),
                            'specifications' => Validator::sanitize($itemData['specifications'] ?? ''),
                            'model' => Validator::sanitize($itemData['model'] ?? ''),
                            'brand' => Validator::sanitize($itemData['brand'] ?? ''),
                            'category_id' => (int)($itemData['category_id'] ?? 0) ?: null,
                            'quantity' => (int)$itemData['quantity'],
                            'unit' => Validator::sanitize($itemData['unit'] ?? 'pcs'),
                            'unit_price' => (float)$itemData['unit_price'],
                            'item_notes' => Validator::sanitize($itemData['item_notes'] ?? '')
                        ];
                    }
                }
            }
            
            // Validate
            if (empty($formData['vendor_id'])) {
                $errors[] = 'Vendor is required';
            }
            if (empty($formData['project_id'])) {
                $errors[] = 'Project is required';
            }
            if (empty($formData['title'])) {
                $errors[] = 'Title is required';
            }
            if (empty($items)) {
                $errors[] = 'At least one item is required';
            }
            
            if (empty($errors)) {
                try {
                    $result = $this->procurementOrderModel->createProcurementOrder($formData, $items);
                    
                    if ($result['success']) {
                        header('Location: ?route=procurement-orders/view&id=' . $result['procurement_order']['id'] . '&message=procurement_order_created');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                    
                } catch (Exception $e) {
                    error_log("Procurement order creation error: " . $e->getMessage());
                    $errors[] = 'Failed to create procurement order.';
                }
            }
        }
        
        // Get form options
        try {
            $projectModel = new ProjectModel();
            $vendorModel = new VendorModel();
            $categoryModel = new CategoryModel();
            
            $projects = $projectModel->getActiveProjects();
            $vendors = $vendorModel->findAll([], "name ASC");
            $categories = $categoryModel->findAll([], "name ASC");
            
            $pageTitle = 'Create Procurement Order - ConstructLink™';
            $pageHeader = 'Create Procurement Order';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Create Order', 'url' => '?route=procurement-orders/create']
            ];
            
            include APP_ROOT . '/views/procurement-orders/create.php';
            
        } catch (Exception $e) {
            error_log("Procurement order create form error: " . $e->getMessage());
            $error = 'Failed to load form data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * View procurement order details
     */
    public function view() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/view'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $orderId = $_GET['id'] ?? 0;
        
        if (!$orderId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithItems($orderId);
            
            if (!$procurementOrder) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check project assignment access for specific roles
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                $assignedProjectIds = $this->getUserAssignedProjectIds($currentUser['id'], $userRole);
                if (empty($assignedProjectIds) || !in_array($procurementOrder['project_id'], $assignedProjectIds)) {
                    http_response_code(403);
                    include APP_ROOT . '/views/errors/403.php';
                    return;
                }
            }
            
            // Get items summary
            $itemsSummary = $this->procurementItemModel->getItemsSummary($orderId);
            
            $pageTitle = 'Procurement Order Details - ConstructLink™';
            $pageHeader = 'Procurement Order #' . $procurementOrder['po_number'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'View Order', 'url' => '?route=procurement-orders/view&id=' . $orderId]
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/procurement-orders/view.php';
            
        } catch (Exception $e) {
            error_log("Procurement order view error: " . $e->getMessage());
            $error = 'Failed to load procurement order details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Edit procurement order
     */
    public function edit() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/edit'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $orderId = $_GET['id'] ?? 0;
        
        if (!$orderId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithItems($orderId);
            
            if (!$procurementOrder) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check project assignment access for specific roles
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                $assignedProjectIds = $this->getUserAssignedProjectIds($currentUser['id'], $userRole);
                if (empty($assignedProjectIds) || !in_array($procurementOrder['project_id'], $assignedProjectIds)) {
                    http_response_code(403);
                    include APP_ROOT . '/views/errors/403.php';
                    return;
                }
            }
            
            // Check if order can be edited - allow Draft, For Revision, and retroactive POs in Draft status
            $editableStatuses = ['Draft', 'For Revision'];
            if ($procurementOrder['is_retroactive'] && $procurementOrder['status'] === 'Draft') {
                $editableStatuses[] = 'Draft'; // Already included but explicit for retroactive logic
            }
            
            if (!in_array($procurementOrder['status'], $editableStatuses)) {
                $errors[] = 'This procurement order cannot be edited in its current status.';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                // Handle file uploads FIRST
                require_once APP_ROOT . '/core/ProcurementFileUploader.php';
                $existingFiles = [
                    'quote_file' => $procurementOrder['quote_file'] ?? null,
                    'purchase_receipt_file' => $procurementOrder['purchase_receipt_file'] ?? null,
                    'supporting_evidence_file' => $procurementOrder['supporting_evidence_file'] ?? null
                ];
                $fileResult = ProcurementFileUploader::handleProcurementFiles($_FILES, $existingFiles);
                if (!empty($fileResult['errors'])) {
                    $errors = array_merge($errors, $fileResult['errors']);
                }
                
                // Process form submission (similar to create but with update logic)
                $formData = [
                    'vendor_id' => (int)($_POST['vendor_id'] ?? 0),
                    'project_id' => (int)($_POST['project_id'] ?? 0),
                    'title' => Validator::sanitize($_POST['title'] ?? ''),
                    'package_scope' => Validator::sanitize($_POST['package_scope'] ?? ''),
                    'expected_delivery' => !empty($_POST['expected_delivery']) ? $_POST['expected_delivery'] : null,
                    'quotation_number' => Validator::sanitize($_POST['quotation_number'] ?? ''),
                    'quotation_date' => !empty($_POST['quotation_date']) ? $_POST['quotation_date'] : null,
                    'notes' => Validator::sanitize($_POST['notes'] ?? ''),
                    'subtotal' => (float)($_POST['subtotal'] ?? 0),
                    'vat_rate' => (float)($_POST['tax_rate'] ?? 0),
                    'vat_amount' => (float)($_POST['tax_amount'] ?? 0),
                    'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
                    'net_total' => (float)($_POST['net_total'] ?? 0),
                    'delivery_method' => Validator::sanitize($_POST['delivery_method'] ?? ''),
                    'delivery_location' => Validator::sanitize($_POST['delivery_location'] ?? '')
                ];
                
                // Update PO number if provided
                if (!empty($_POST['po_number'])) {
                    $formData['po_number'] = Validator::sanitize($_POST['po_number']);
                }
                
                // Add uploaded files to form data
                $formData = array_merge($formData, $fileResult['files']);
                
                // Handle file upload notes
                if (!empty($_POST['file_upload_notes'])) {
                    $formData['file_upload_notes'] = Validator::sanitize($_POST['file_upload_notes']);
                }
                
                // Handle retroactive reason updates (if this is a retroactive PO)
                if ($procurementOrder['is_retroactive'] && !empty($_POST['retroactive_reason'])) {
                    $formData['retroactive_reason'] = Validator::sanitize($_POST['retroactive_reason']);
                }
                
                // Process items
                $items = [];
                if (!empty($_POST['items'])) {
                    foreach ($_POST['items'] as $itemData) {
                        if (!empty($itemData['item_name']) && !empty($itemData['quantity']) && !empty($itemData['unit_price'])) {
                            $item = [
                                'item_name' => Validator::sanitize($itemData['item_name']),
                                'description' => Validator::sanitize($itemData['description'] ?? ''),
                                'specifications' => Validator::sanitize($itemData['specifications'] ?? ''),
                                'model' => Validator::sanitize($itemData['model'] ?? ''),
                                'brand' => Validator::sanitize($itemData['brand'] ?? ''),
                                'category_id' => (int)($itemData['category_id'] ?? 0) ?: null,
                                'quantity' => (int)$itemData['quantity'],
                                'unit' => Validator::sanitize($itemData['unit'] ?? 'pcs'),
                                'unit_price' => (float)$itemData['unit_price'],
                                'item_notes' => Validator::sanitize($itemData['item_notes'] ?? '')
                            ];
                            
                            if (!empty($itemData['id'])) {
                                $item['id'] = (int)$itemData['id'];
                            }
                            
                            $items[] = $item;
                        }
                    }
                }
                
                // Validate
                if (empty($formData['vendor_id'])) {
                    $errors[] = 'Vendor is required';
                }
                if (empty($formData['project_id'])) {
                    $errors[] = 'Project is required';
                }
                if (empty($formData['title'])) {
                    $errors[] = 'Title is required';
                }
                if (empty($items)) {
                    $errors[] = 'At least one item is required';
                }
                
                if (empty($errors)) {
                    try {
                        // Determine the action and set appropriate status
                        $action = $_POST['action'] ?? 'save_draft';
                        
                        if ($action === 'submit') {
                            $formData['status'] = 'Pending';
                            if ($procurementOrder['is_retroactive']) {
                                $message = 'retroactive_po_submitted';
                            } else {
                                $message = 'procurement_order_submitted';
                            }
                        } elseif ($action === 'submit_retrospective' && $procurementOrder['is_retroactive']) {
                            // Special action for retroactive PO submission for approval
                            $formData['status'] = 'Pending';
                            $message = 'retroactive_po_submitted_for_approval';
                        } else {
                            $formData['status'] = 'Draft';
                            if ($procurementOrder['is_retroactive']) {
                                $message = 'retroactive_po_updated';
                            } else {
                                $message = 'procurement_order_updated';
                            }
                        }
                        
                        // Update procurement order
                        $updateResult = $this->procurementOrderModel->update($orderId, $formData);
                        
                        if ($updateResult) {
                            // Update items
                            $itemsResult = $this->procurementItemModel->bulkUpdateItems($orderId, $items);
                            
                            if ($itemsResult['success']) {
                                header('Location: ?route=procurement-orders/view&id=' . $orderId . '&message=' . $message);
                                exit;
                            } else {
                                $errors[] = $itemsResult['message'];
                            }
                        } else {
                            $errors[] = 'Failed to update procurement order';
                        }
                        
                    } catch (Exception $e) {
                        error_log("Procurement order update error: " . $e->getMessage());
                        $errors[] = 'Failed to update procurement order.';
                    }
                }
            }
            
            // Extract items from procurement order
            $items = $procurementOrder['items'] ?? [];
            
            // Get form options
            $projectModel = new ProjectModel();
            $vendorModel = new VendorModel();
            $categoryModel = new CategoryModel();
            
            $projects = $projectModel->getActiveProjects();
            $vendors = $vendorModel->findAll([], "name ASC");
            $categories = $categoryModel->findAll([], "name ASC");
            
            $pageTitle = 'Edit Procurement Order - ConstructLink™';
            $pageHeader = 'Edit Procurement Order #' . $procurementOrder['po_number'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Edit Order', 'url' => '?route=procurement-orders/edit&id=' . $orderId]
            ];
            
            include APP_ROOT . '/views/procurement-orders/edit.php';
            
        } catch (Exception $e) {
            error_log("Procurement order edit error: " . $e->getMessage());
            $error = 'Failed to load procurement order for editing';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Approve procurement order
     */
    public function approve() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/approve'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $orderId = $_GET['id'] ?? 0;
        if (!$orderId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        $errors = [];
        try {
            $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithItems($orderId);
            if (!$procurementOrder) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            $currentUser = $this->auth->getCurrentUser();
            // Workflow-specific: Project Manager must be assigned to the project
            if ($currentUser['role_name'] === 'Project Manager' && ($procurementOrder['project_manager_id'] ?? null) != $currentUser['id']) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            // Check if order can be approved
            if (!in_array($procurementOrder['status'], ['Pending', 'Reviewed'])) {
                $errors[] = 'This procurement order cannot be approved in its current status.';
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                $action = $_POST['action'] ?? '';
                $notes = Validator::sanitize($_POST['notes'] ?? '');
                if (empty($errors)) {
                    if ($action === 'approve') {
                        $result = $this->procurementOrderModel->updateStatus($orderId, 'Approved', $currentUser['id'], $notes);
                    } elseif ($action === 'reject') {
                        $result = $this->procurementOrderModel->updateStatus($orderId, 'Rejected', $currentUser['id'], $notes);
                    } elseif ($action === 'revise') {
                        $result = $this->procurementOrderModel->updateStatus($orderId, 'For Revision', $currentUser['id'], $notes);
                    } else {
                        $result = ['success' => false, 'message' => 'Invalid action'];
                    }
                    if ($result['success']) {
                        header('Location: ?route=procurement-orders/view&id=' . $orderId . '&message=procurement_order_' . strtolower($action) . 'd');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            $pageTitle = 'Approve Procurement Order - ConstructLink™';
            $pageHeader = 'Approve Procurement Order #' . $procurementOrder['po_number'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Approve Order', 'url' => '?route=procurement-orders/approve&id=' . $orderId]
            ];
            include APP_ROOT . '/views/procurement-orders/approve.php';
        } catch (Exception $e) {
            error_log("Procurement order approval error: " . $e->getMessage());
            $error = 'Failed to process approval request';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Receive procurement order (confirm receipt by warehouseman)
     */
    public function receive() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/receive'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        $currentUser = $this->auth->getCurrentUser();
        $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithDetails($id);
        // Workflow-specific: Project Manager must be assigned to the project
        if ($currentUser['role_name'] === 'Project Manager' && ($procurementOrder['project_manager_id'] ?? null) != $currentUser['id']) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        $errors = [];
        $messages = [];
        try {
            $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithDetails($id);
            if (!$procurementOrder) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            // Check if order can be received - must be approved and not already received
            if ($procurementOrder['status'] === 'Received') {
                $errors[] = 'This order has already been received.';
            } elseif (!in_array($procurementOrder['status'], ['Approved', 'Scheduled for Delivery', 'In Transit', 'Delivered'])) {
                $errors[] = 'Only approved orders in the delivery process can be received. Current status: ' . $procurementOrder['status'];
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                $receiptData = [
                    'receipt_notes' => Validator::sanitize($_POST['receipt_notes'] ?? ''),
                    'quality_notes' => Validator::sanitize($_POST['quality_notes'] ?? ''),
                    'has_discrepancy' => $_POST['has_discrepancy'] ?? 'no',
                    'discrepancy_type' => $_POST['discrepancy_type'] ?? null,
                    'discrepancy_details' => Validator::sanitize($_POST['discrepancy_details'] ?? '')
                ];
                // Update quantity_received for each item
                if (!empty($_POST['items'])) {
                    $itemModel = new ProcurementItemModel();
                    foreach ($_POST['items'] as $itemId => $itemData) {
                        $newQuantityReceived = isset($itemData['quantity_received']) ? (int)$itemData['quantity_received'] : 0;
                        $previouslyReceived = isset($itemData['previously_received']) ? (int)$itemData['previously_received'] : 0;
                        $totalQuantityReceived = $previouslyReceived + $newQuantityReceived;
                        $qualityNotes = isset($itemData['quality_notes']) ? Validator::sanitize($itemData['quality_notes']) : null;
                        
                        $itemModel->updateDeliveryStatus($itemId, 'Complete', $totalQuantityReceived, $qualityNotes);
                    }
                }
                if (empty($errors)) {
                    $result = $this->procurementOrderModel->confirmReceipt($id, $receiptData, $this->auth->getCurrentUser()['id']);
                    if ($result['success']) {
                        header('Location: ?route=procurement-orders/view&id=' . $id . '&message=order_received');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            $pageTitle = 'Receive Order - ConstructLink™';
            $pageHeader = 'Receive Procurement Order: ' . $procurementOrder['po_number'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Receive Order', 'url' => '?route=procurement-orders/receive&id=' . $id]
            ];
            include APP_ROOT . '/views/procurement-orders/receive.php';
        } catch (Exception $e) {
            error_log("Procurement order receive error: " . $e->getMessage());
            $error = 'Failed to load order for receiving';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Generate assets from procurement order
     */
    public function generateAssets() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/generateAssets'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $orderId = $_GET['id'] ?? 0;
        if (!$orderId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        $errors = [];
        $messages = [];
        try {
            $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithItems($orderId);
            if (!$procurementOrder) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check project assignment access for specific roles
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                $assignedProjectIds = $this->getUserAssignedProjectIds($currentUser['id'], $userRole);
                if (empty($assignedProjectIds) || !in_array($procurementOrder['project_id'], $assignedProjectIds)) {
                    http_response_code(403);
                    include APP_ROOT . '/views/errors/403.php';
                    return;
                }
            }
            
            // Check if assets can be generated - Allow for both 'Received' status and 'Delivered' delivery status
            if ($procurementOrder['status'] !== 'Received' && $procurementOrder['delivery_status'] !== 'Delivered') {
                $errors[] = 'Assets can only be generated from received or delivered procurement orders.';
            }
            // Get items ready for asset generation
            $availableItems = $this->procurementItemModel->getItemsForAssetGeneration($orderId);
            // Get all items for display (including those already converted)
            $allItems = $this->procurementItemModel->getItemsForAssetDisplay($orderId);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                $selectedItems = $_POST['items'] ?? [];
                $generationNotes = Validator::sanitize($_POST['generation_notes'] ?? '');
                $generatedAssets = 0;
                $currentUser = $this->auth->getCurrentUser();
                // Check if any items were submitted
                if (empty($selectedItems)) {
                    $errors[] = 'No items were selected. Please select items and specify quantities.';
                } else {
                    // Validate that at least one item has valid selection and quantity
                    $hasValidItems = false;
                    foreach ($selectedItems as $itemId => $itemData) {
                        $isSelected = isset($itemData['selected']) && $itemData['selected'] == '1';
                        $quantity = (int)($itemData['quantity'] ?? 0);
                        if ($isSelected && $quantity > 0) {
                            $hasValidItems = true;
                            break;
                        }
                    }
                    if (!$hasValidItems) {
                        $errors[] = 'No valid items selected. Please select items and specify quantities greater than 0.';
                    }
                }
                if (empty($errors)) {
                    try {
                        $db = Database::getInstance()->getConnection();
                        $db->beginTransaction();
                        foreach ($selectedItems as $itemId => $itemData) {
                            // Check if item is selected and has quantity > 0
                            $isSelected = isset($itemData['selected']) && $itemData['selected'] == '1';
                            $quantity = (int)($itemData['quantity'] ?? 0);
                            if (!$isSelected || $quantity <= 0) {
                                continue;
                            }
                            // Find the item details
                            $item = null;
                            foreach ($availableItems as $availableItem) {
                                if ($availableItem['id'] == $itemId) {
                                    $item = $availableItem;
                                    break;
                                }
                            }
                            if ($item && $item['available_for_generation'] > 0) {
                                // Limit quantity to available amount
                                $quantity = min($quantity, $item['available_for_generation']);
                                // Get a valid category_id if the item's category_id is null or invalid
                                $categoryId = $item['category_id'];
                                if (!$categoryId) {
                                    // Get the first available category from the database
                                    $catSql = "SELECT id FROM categories ORDER BY id ASC LIMIT 1";
                                    $catStmt = $db->prepare($catSql);
                                    $catStmt->execute();
                                    $catResult = $catStmt->fetch();
                                    $categoryId = $catResult ? $catResult['id'] : null;
                                    // If no categories exist, create a default one
                                    if (!$categoryId) {
                                        $createCatSql = "INSERT INTO categories (name, description, created_at) VALUES ('General', 'Default category for assets', NOW())";
                                        $createCatStmt = $db->prepare($createCatSql);
                                        $createCatStmt->execute();
                                        $categoryId = $db->lastInsertId();
                                    }
                                }
                                // Fetch is_consumable from the categories table for this item's category_id
                                $isConsumable = false;
                                if ($categoryId) {
                                    $catStmt = $db->prepare("SELECT is_consumable FROM categories WHERE id = ?");
                                    $catStmt->execute([$categoryId]);
                                    $catRow = $catStmt->fetch();
                                    $isConsumable = $catRow && $catRow['is_consumable'] == 1;
                                }
                                
                                if ($isConsumable) {
                                    // Consumable: one asset with total quantity
                                    $assetData = [
                                        'ref' => $this->generateAssetReference(),
                                        'category_id' => $categoryId,
                                        'name' => $item['item_name'],
                                        'description' => $item['description'],
                                        'project_id' => $procurementOrder['project_id'],
                                        'vendor_id' => $procurementOrder['vendor_id'],
                                        'procurement_order_id' => $orderId,
                                        'procurement_item_id' => $itemId,
                                        'acquired_date' => date('Y-m-d'),
                                        'status' => 'available',
                                        'is_client_supplied' => 0,
                                        'quantity' => $quantity,
                                        'available_quantity' => $quantity,
                                        'acquisition_cost' => $item['unit_price'],
                                        'unit_cost' => $item['unit_price'],
                                        'model' => $item['model'],
                                        'specifications' => $item['specifications']
                                    ];
                                    $columns = implode(", ", array_keys($assetData));
                                    $placeholders = implode(", ", array_fill(0, count($assetData), '?'));
                                    $sql = "INSERT INTO assets ($columns) VALUES ($placeholders)";
                                    $stmt = $db->prepare($sql);
                                    $result = $stmt->execute(array_values($assetData));
                                    if ($result) {
                                        $assetId = $db->lastInsertId();
                                        // Link asset to procurement_assets
                                        $linkSql = "INSERT INTO procurement_assets (asset_id, procurement_order_id, procurement_item_id, created_at) VALUES (?, ?, ?, NOW())";
                                        $linkStmt = $db->prepare($linkSql);
                                        $linkStmt->execute([$assetId, $orderId, $itemId]);
                                        $generatedAssets++;
                                    } else {
                                        $errors[] = 'Failed to create asset for item: ' . $item['item_name'];
                                    }
                                } else {
                                    // Non-consumable: one asset per quantity
                                    for ($i = 0; $i < $quantity; $i++) {
                                        $assetData = [
                                            'ref' => $this->generateAssetReference(),
                                            'category_id' => $categoryId,
                                            'name' => $item['item_name'],
                                            'description' => $item['description'],
                                            'project_id' => $procurementOrder['project_id'],
                                            'vendor_id' => $procurementOrder['vendor_id'],
                                            'procurement_order_id' => $orderId,
                                            'procurement_item_id' => $itemId,
                                            'acquired_date' => date('Y-m-d'),
                                            'status' => 'available',
                                            'is_client_supplied' => 0,
                                            'quantity' => 1,
                                            'available_quantity' => 1,
                                            'acquisition_cost' => $item['unit_price'],
                                            'unit_cost' => $item['unit_price'],
                                            'model' => $item['model'],
                                            'specifications' => $item['specifications']
                                        ];
                                        $columns = implode(", ", array_keys($assetData));
                                        $placeholders = implode(", ", array_fill(0, count($assetData), '?'));
                                        $sql = "INSERT INTO assets ($columns) VALUES ($placeholders)";
                                        $stmt = $db->prepare($sql);
                                        $result = $stmt->execute(array_values($assetData));
                                        if ($result) {
                                            $assetId = $db->lastInsertId();
                                            // Link asset to procurement_assets
                                            $linkSql = "INSERT INTO procurement_assets (asset_id, procurement_order_id, procurement_item_id, created_at) VALUES (?, ?, ?, NOW())";
                                            $linkStmt = $db->prepare($linkSql);
                                            $linkStmt->execute([$assetId, $orderId, $itemId]);
                                            $generatedAssets++;
                                        } else {
                                            $errors[] = 'Failed to create asset for item: ' . $item['item_name'];
                                        }
                                    }
                                }
                                
                                // Log asset generation
                                if ($generatedAssets > 0) {
                                    logActivity('assets_generated', "Generated {$generatedAssets} assets from procurement order #{$procurementOrder['po_number']} during receipt confirmation", 'assets', $orderId);
                                    
                                    // Log procurement activity
                                    try {
                                        $this->procurementOrderModel->logProcurementActivity(
                                            $orderId, 
                                            $currentUser['id'], 
                                            'assets_generated_on_receipt', 
                                            null, 
                                            null, 
                                            "Generated {$generatedAssets} assets automatically upon receipt confirmation"
                                        );
                                    } catch (Exception $logError) {
                                        error_log("Failed to log procurement activity: " . $logError->getMessage());
                                    }
                                }
                                
                                $db->commit();
                                
                            }
                        }
                        
                    } catch (Exception $e) {
                        if (isset($db) && $db->inTransaction()) {
                            $db->rollBack();
                        }
                        error_log("Asset generation error: " . $e->getMessage());
                        $errors[] = 'Failed to generate assets.';
                    }
                }
            }
            $pageTitle = 'Generate Assets - ConstructLink™';
            $pageHeader = 'Generate Assets for Order: ' . $procurementOrder['po_number'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Generate Assets', 'url' => '?route=procurement-orders/generateAssets&id=' . $orderId]
            ];
            include APP_ROOT . '/views/procurement-orders/generate-assets.php';
        } catch (Exception $e) {
            error_log("Generate assets error: " . $e->getMessage());
            $error = 'Failed to generate assets.';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Cancel procurement order
     */
    public function cancel() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/cancel'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $orderId = $_GET['id'] ?? 0;
        
        if (!$orderId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        
        try {
            $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithItems($orderId);
            
            if (!$procurementOrder) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check project assignment access for specific roles
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                $assignedProjectIds = $this->getUserAssignedProjectIds($currentUser['id'], $userRole);
                if (empty($assignedProjectIds) || !in_array($procurementOrder['project_id'], $assignedProjectIds)) {
                    http_response_code(403);
                    include APP_ROOT . '/views/errors/403.php';
                    return;
                }
            }
            
            // Check if order can be canceled
            if (!in_array($procurementOrder['status'], ['Draft', 'Pending', 'Reviewed'])) {
                $errors[] = 'This procurement order cannot be canceled in its current status.';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $cancellationReason = Validator::sanitize($_POST['cancellation_reason'] ?? '');
                $customReason = Validator::sanitize($_POST['custom_reason'] ?? '');
                $cancellationNotes = Validator::sanitize($_POST['cancellation_notes'] ?? '');
                $currentUser = $this->auth->getCurrentUser();
                
                // Build comprehensive reason string
                $reason = '';
                if ($cancellationReason === 'other') {
                    if (empty($customReason)) {
                        $errors[] = 'Please specify the custom cancellation reason';
                    } else {
                        $reason = $customReason;
                    }
                } elseif (!empty($cancellationReason)) {
                    // Convert snake_case to readable format
                    $reason = ucwords(str_replace('_', ' ', $cancellationReason));
                } else {
                    $errors[] = 'Cancellation reason is required';
                }
                
                // Add notes to reason if provided
                if (!empty($cancellationNotes)) {
                    $reason .= (!empty($reason) ? '. ' : '') . $cancellationNotes;
                }
                
                // Validate that we have a complete reason
                if (empty($reason)) {
                    $errors[] = 'Cancellation reason is required';
                }
                
                if (empty($errors)) {
                    $result = $this->procurementOrderModel->cancelOrder($orderId, $reason, $currentUser['id']);
                    
                    if ($result['success']) {
                        header('Location: ?route=procurement-orders&message=procurement_order_canceled');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Cancel Procurement Order - ConstructLink™';
            $pageHeader = 'Cancel Procurement Order #' . $procurementOrder['po_number'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Cancel Order', 'url' => '?route=procurement-orders/cancel&id=' . $orderId]
            ];
            
            // Pass the order data with expected variable name for the view
            $order = $procurementOrder;
            
            include APP_ROOT . '/views/procurement-orders/cancel.php';
            
        } catch (Exception $e) {
            error_log("Procurement order cancellation error: " . $e->getMessage());
            $error = 'Failed to process cancellation request';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Create procurement order from approved request
     */
    public function createFromRequest() {
        $requestId = $_GET['request_id'] ?? 0;
        
        if (!$requestId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        // Check permissions - only Procurement Officers can create POs from requests
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/createFromRequest'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        $formData = [];
        $items = [];
        
        try {
            // Get request details
            $requestModel = new RequestModel();
            $request = $requestModel->getRequestWithDetails($requestId);
            
            if (!$request) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check if request can be procured
            $canProcure = $requestModel->canBeProcured($requestId);
            if (!$canProcure['can_procure']) {
                $errors[] = $canProcure['reason'];
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                // Process form submission
                $formData = [
                    'vendor_id' => (int)($_POST['vendor_id'] ?? 0),
                    'title' => Validator::sanitize($_POST['title'] ?? ''),
                    'package_scope' => Validator::sanitize($_POST['package_scope'] ?? ''),
                    'work_breakdown' => Validator::sanitize($_POST['work_breakdown'] ?? ''),
                    'budget_allocation' => (float)($_POST['budget_allocation'] ?? 0),
                    'justification' => Validator::sanitize($_POST['justification'] ?? ''),
                    'date_needed' => !empty($_POST['date_needed']) ? $_POST['date_needed'] : null,
                    'vat_rate' => (float)($_POST['vat_rate'] ?? 12.00),
                    'ewt_rate' => (float)($_POST['ewt_rate'] ?? 1.00),
                    'handling_fee' => (float)($_POST['handling_fee'] ?? 0),
                    'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
                    'notes' => Validator::sanitize($_POST['notes'] ?? ''),
                    'requested_by' => $this->auth->getCurrentUser()['id'],
                    'status' => 'Draft'
                ];
                
                // Process items
                if (!empty($_POST['items'])) {
                    foreach ($_POST['items'] as $itemData) {
                        if (!empty($itemData['item_name']) && !empty($itemData['quantity']) && !empty($itemData['unit_price'])) {
                            $items[] = [
                                'item_name' => Validator::sanitize($itemData['item_name']),
                                'description' => Validator::sanitize($itemData['description'] ?? ''),
                                'specifications' => Validator::sanitize($itemData['specifications'] ?? ''),
                                'model' => Validator::sanitize($itemData['model'] ?? ''),
                                'brand' => Validator::sanitize($itemData['brand'] ?? ''),
                                'category_id' => (int)($itemData['category_id'] ?? 0) ?: null,
                                'quantity' => (int)$itemData['quantity'],
                                'unit' => Validator::sanitize($itemData['unit'] ?? 'pcs'),
                                'unit_price' => (float)$itemData['unit_price'],
                                'item_notes' => Validator::sanitize($itemData['item_notes'] ?? '')
                            ];
                        }
                    }
                }
                
                // Validate
                if (empty($formData['vendor_id'])) {
                    $errors[] = 'Vendor is required';
                }
                if (empty($formData['title'])) {
                    $errors[] = 'Title is required';
                }
                if (empty($items)) {
                    $errors[] = 'At least one item is required';
                }
                
                if (empty($errors)) {
                    try {
                        $result = $this->procurementOrderModel->createFromRequest($requestId, $formData, $items);
                        
                        if ($result['success']) {
                            header('Location: ?route=procurement-orders/view&id=' . $result['procurement_order']['id'] . '&message=procurement_order_created_from_request');
                            exit;
                        } else {
                            $errors[] = $result['message'];
                        }
                        
                    } catch (Exception $e) {
                        error_log("Procurement order from request creation error: " . $e->getMessage());
                        $errors[] = 'Failed to create procurement order from request.';
                    }
                }
            }
            
            // Pre-populate form data from request
            if (empty($formData)) {
                $formData = [
                    'title' => 'PO for Request: ' . $request['description'],
                    'date_needed' => $request['date_needed'],
                    'justification' => 'Created from approved request #' . $request['id'] . ': ' . $request['description'],
                    'budget_allocation' => $request['estimated_cost'] ?? 0
                ];
                
                // Pre-populate items based on request
                $items = [[
                    'item_name' => $request['description'],
                    'description' => $request['description'],
                    'quantity' => $request['quantity'] ?? 1,
                    'unit' => $request['unit'] ?? 'pcs',
                    'unit_price' => $request['estimated_cost'] ?? 0,
                    'category_id' => null
                ]];
            }
            
            // Get form options
            $projectModel = new ProjectModel();
            $vendorModel = new VendorModel();
            $categoryModel = new CategoryModel();
            
            $projects = $projectModel->getActiveProjects();
            $vendors = $vendorModel->findAll([], "name ASC");
            $categories = $categoryModel->findAll([], "name ASC");
            
            $pageTitle = 'Create PO from Request - ConstructLink™';
            $pageHeader = 'Create Procurement Order from Request #' . $request['id'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Requests', 'url' => '?route=requests'],
                ['title' => 'View Request', 'url' => '?route=requests/view&id=' . $requestId],
                ['title' => 'Create PO', 'url' => '?route=procurement-orders/createFromRequest&request_id=' . $requestId]
            ];
            
            include APP_ROOT . '/views/procurement-orders/create-from-request.php';
            
        } catch (Exception $e) {
            error_log("Create PO from request error: " . $e->getMessage());
            $error = 'Failed to load request data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Get approved requests available for procurement
     */
    public function getApprovedRequests() {
        // Check permissions - only Procurement Officers can see this
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/approved-requests'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            $requestModel = new RequestModel();
            $approvedRequests = $requestModel->getApprovedRequestsForProcurement();
            
            $pageTitle = 'Approved Requests - ConstructLink™';
            $pageHeader = 'Approved Requests Available for Procurement';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Approved Requests', 'url' => '?route=procurement-orders/approved-requests']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/procurement-orders/approved-requests.php';
            
        } catch (Exception $e) {
            error_log("Get approved requests error: " . $e->getMessage());
            $error = 'Failed to load approved requests';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * API endpoint to get items for a procurement order
     */
    public function getItems() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $orderId = $_GET['order_id'] ?? 0;
        
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Order ID required']);
            return;
        }
        
        try {
            $items = $this->procurementItemModel->getItemsByOrderId($orderId);
            
            echo json_encode([
                'success' => true,
                'items' => $items
            ]);
            
        } catch (Exception $e) {
            error_log("Get procurement items API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load items']);
        }
    }

    /**
     * API endpoint to get procurement statistics
     */
    public function getStats() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            $projectId = $_GET['project_id'] ?? null;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            
            $stats = $this->procurementOrderModel->getProcurementStatistics($projectId, $dateFrom, $dateTo);
            echo json_encode(['success' => true, 'stats' => $stats]);
            
        } catch (Exception $e) {
            error_log("Get stats API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get statistics']);
        }
    }

    /**
     * API endpoint to get delivery alerts
     */
    public function getDeliveryAlerts() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            $userRole = $this->auth->getCurrentUser()['role_name'] ?? '';
            $userId = $this->auth->getCurrentUser()['id'] ?? null;
            
            $alerts = $this->procurementOrderModel->getOrdersWithDeliveryAlerts($userRole, $userId);
            echo json_encode(['success' => true, 'alerts' => $alerts]);
            
        } catch (Exception $e) {
            error_log("Get delivery alerts API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to get delivery alerts']);
        }
    }
    
    /**
     * Schedule delivery for approved procurement order
     */
    public function scheduleDelivery() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/schedule-delivery'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithDetails($id);
            
            if (!$procurementOrder) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check if order can be scheduled for delivery
            if ($procurementOrder['status'] !== 'Approved') {
                $errors[] = 'Only approved orders can be scheduled for delivery.';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $deliveryData = [
                    'scheduled_date' => $_POST['scheduled_date'] ?? '',
                    'delivery_method' => $_POST['delivery_method'] ?? '',
                    'delivery_location' => Validator::sanitize($_POST['delivery_location'] ?? ''),
                    'tracking_number' => Validator::sanitize($_POST['tracking_number'] ?? ''),
                    'notes' => Validator::sanitize($_POST['delivery_notes'] ?? '')
                ];
                
                // Use category-aware delivery validation
                $validationErrors = validateDeliveryData($deliveryData, $id);
                $errors = array_merge($errors, $validationErrors);
                
                if (empty($errors)) {
                    $result = $this->procurementOrderModel->scheduleDelivery($id, $deliveryData, $this->auth->getCurrentUser()['id']);
                    
                    if ($result['success']) {
                        header('Location: ?route=procurement-orders/view&id=' . $id . '&message=delivery_scheduled');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Schedule Delivery - ConstructLink™';
            $pageHeader = 'Schedule Delivery: ' . $procurementOrder['po_number'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Schedule Delivery', 'url' => '?route=procurement-orders/schedule-delivery&id=' . $id]
            ];
            
            include APP_ROOT . '/views/procurement-orders/schedule-delivery.php';
            
        } catch (Exception $e) {
            error_log("Schedule delivery error: " . $e->getMessage());
            $error = 'Failed to load order for delivery scheduling';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Update delivery status
     */
    public function updateDeliveryStatus() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/update-delivery'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithDetails($id);
            
            if (!$procurementOrder) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $status = $_POST['delivery_status'] ?? '';
                $notes = Validator::sanitize($_POST['status_notes'] ?? '');
                $actualDate = $_POST['actual_date'] ?? null;
                
                // Validate required fields
                if (empty($status)) {
                    $errors[] = 'Delivery status is required';
                }
                
                if ($status === 'Delivered' && empty($actualDate)) {
                    $errors[] = 'Actual delivery date is required when marking as delivered';
                }
                
                // Validate status transitions
                $validTransitions = [
                    'Pending' => ['Scheduled', 'In Transit', 'Delivered'],  // From approved orders that haven't been scheduled yet
                    'Scheduled' => ['In Transit', 'Delivered', 'Delayed'],
                    'In Transit' => ['Delivered', 'Delayed', 'Failed Delivery'],
                    'Delivered' => ['Delivered'], // Can stay delivered
                    'Delayed' => ['In Transit', 'Delivered', 'Failed Delivery'],
                    'Failed Delivery' => ['Scheduled', 'In Transit'] // Can retry delivery
                ];
                
                $currentStatus = $procurementOrder['delivery_status'] ?? 'Pending';
                if (!isset($validTransitions[$currentStatus]) || !in_array($status, $validTransitions[$currentStatus])) {
                    $errors[] = 'Invalid status transition from ' . $currentStatus . ' to ' . $status;
                }
                
                if (empty($errors)) {
                    $result = $this->procurementOrderModel->updateDeliveryStatus($id, $status, $this->auth->getCurrentUser()['id'], $notes, $actualDate);
                    
                    if ($result['success']) {
                        header('Location: ?route=procurement-orders/view&id=' . $id . '&message=delivery_status_updated');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Update Delivery Status - ConstructLink™';
            $pageHeader = 'Update Delivery Status: ' . $procurementOrder['po_number'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Update Delivery', 'url' => '?route=procurement-orders/update-delivery&id=' . $id]
            ];
            
            include APP_ROOT . '/views/procurement-orders/update-delivery.php';
            
        } catch (Exception $e) {
            error_log("Update delivery status error: " . $e->getMessage());
            $error = 'Failed to load order for delivery update';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Show orders ready for delivery scheduling
     */
    public function readyForDelivery() {
        // Check permissions
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/ready-for-delivery'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Get current user info for filtering
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            
            // Get assigned project IDs for role-based filtering
            $assignedProjectIds = null;
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                $assignedProjectIds = $this->getUserAssignedProjectIds($currentUser['id'], $userRole);
                if (empty($assignedProjectIds)) {
                    $assignedProjectIds = [-1]; // Show no results if no assigned projects
                }
            }
            
            $orders = $this->procurementOrderModel->getOrdersReadyForDelivery($assignedProjectIds);
            
            $pageTitle = 'Orders Ready for Delivery - ConstructLink™';
            $pageHeader = 'Orders Ready for Delivery Scheduling';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Ready for Delivery', 'url' => '?route=procurement-orders/ready-for-delivery']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/procurement-orders/ready-for-delivery.php';
            
        } catch (Exception $e) {
            error_log("Ready for delivery error: " . $e->getMessage());
            $error = 'Failed to load orders ready for delivery';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Show orders for receipt confirmation
     */
    public function forReceipt() {
        // Check permissions
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/for-receipt'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Get current user info for filtering
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            
            // Get assigned project IDs for role-based filtering
            $assignedProjectIds = null;
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                $assignedProjectIds = $this->getUserAssignedProjectIds($currentUser['id'], $userRole);
                if (empty($assignedProjectIds)) {
                    $assignedProjectIds = [-1]; // Show no results if no assigned projects
                }
            }
            
            $ordersForReceipt = $this->procurementOrderModel->getOrdersForReceipt($assignedProjectIds);
            
            $pageTitle = 'Orders for Receipt - ConstructLink™';
            $pageHeader = 'Orders Awaiting Receipt Confirmation';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'For Receipt', 'url' => '?route=procurement-orders/for-receipt']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/procurement-orders/for-receipt.php';
            
        } catch (Exception $e) {
            error_log("For receipt error: " . $e->getMessage());
            $error = 'Failed to load orders for receipt';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Confirm receipt of delivery (by warehouseman) with optional asset generation
     */
    public function confirmReceipt() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/confirm-receipt'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        // Check permissions - Warehousemen, Site Inventory Clerks, and Project Managers can confirm receipt (hierarchical)
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithDetails($id);
        $canPM = (
            $userRole === 'Project Manager' &&
            ($procurementOrder['project_manager_id'] ?? null) == $currentUser['id']
        );
        $canClerk = (
            ($userRole === 'Site Inventory Clerk' || $userRole === 'Project Manager') &&
            ($procurementOrder['project_id'] ?? null) && $this->auth->hasRole(['Site Inventory Clerk', 'Project Manager'])
        );
        $canWarehouse = $this->auth->hasRole(['Warehouseman', 'Site Inventory Clerk', 'Project Manager']);
        if (!$this->auth->hasRole(['System Admin', 'Asset Director']) && !$canPM && !$canClerk && !$canWarehouse) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithDetails($id);
            
            if (!$procurementOrder) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $receiptData = [
                    'receipt_notes' => Validator::sanitize($_POST['receipt_notes'] ?? ''),
                    'quality_notes' => Validator::sanitize($_POST['quality_notes'] ?? ''),
                    'has_discrepancy' => $_POST['has_discrepancy'] ?? 'no',
                    'discrepancy_type' => $_POST['discrepancy_type'] ?? null,
                    'discrepancy_details' => Validator::sanitize($_POST['discrepancy_details'] ?? '')
                ];
                
                $generateAssets = isset($_POST['generate_assets']) && $_POST['generate_assets'] == '1';
                
                // Validate discrepancy details if discrepancy is reported
                if ($receiptData['has_discrepancy'] === 'yes') {
                    if (empty($receiptData['discrepancy_type'])) {
                        $errors[] = 'Discrepancy type is required when reporting a discrepancy';
                    }
                    if (empty($receiptData['discrepancy_details'])) {
                        $errors[] = 'Discrepancy details are required when reporting a discrepancy';
                    }
                }
                
                if (empty($errors)) {
                    $result = $this->procurementOrderModel->confirmReceipt($id, $receiptData, $this->auth->getCurrentUser()['id']);
                    
                    if ($result['success']) {
                        // If asset generation is requested and receipt was successful
                        if ($generateAssets) {
                            try {
                                // Get items for asset generation
                                $availableItems = $this->procurementItemModel->getItemsForAssetGeneration($id);
                                $generatedAssets = 0;
                                $currentUser = $this->auth->getCurrentUser();
                                
                                if (!empty($availableItems)) {
                                    $db = Database::getInstance()->getConnection();
                                    $db->beginTransaction();
                                    
                                    try {
                                        foreach ($availableItems as $item) {
                                            // Get a valid category_id if the item's category_id is null or invalid
                                            $categoryId = $item['category_id'];
                                            if (!$categoryId) {
                                                // Get the first available category from the database
                                                $catSql = "SELECT id FROM categories ORDER BY id ASC LIMIT 1";
                                                $catStmt = $db->prepare($catSql);
                                                $catStmt->execute();
                                                $catResult = $catStmt->fetch();
                                                $categoryId = $catResult ? $catResult['id'] : null;
                                                
                                                // If no categories exist, create a default one
                                                if (!$categoryId) {
                                                    $createCatSql = "INSERT INTO categories (name, description, created_at) VALUES ('General', 'Default category for assets', NOW())";
                                                    $createCatStmt = $db->prepare($createCatSql);
                                                    $createCatStmt->execute();
                                                    $categoryId = $db->lastInsertId();
                                                }
                                            }
                                            
                                            // Fetch is_consumable from the categories table for this item's category_id
                                            $isConsumable = false;
                                            if ($categoryId) {
                                                $catStmt = $db->prepare("SELECT is_consumable FROM categories WHERE id = ?");
                                                $catStmt->execute([$categoryId]);
                                                $catRow = $catStmt->fetch();
                                                $isConsumable = $catRow && $catRow['is_consumable'] == 1;
                                            }
                                            
                                            $quantity = (int)$item['available_for_generation'];
                                            
                                            if ($quantity > 0) {
                                                if ($isConsumable) {
                                                    // Consumable: one asset with total quantity
                                                    $assetData = [
                                                        'ref' => $this->generateAssetReference(),
                                                        'category_id' => $categoryId,
                                                        'name' => $item['item_name'],
                                                        'description' => $item['description'],
                                                        'project_id' => $procurementOrder['project_id'],
                                                        'vendor_id' => $procurementOrder['vendor_id'],
                                                        'procurement_order_id' => $orderId,
                                                        'procurement_item_id' => $itemId,
                                                        'acquired_date' => date('Y-m-d'),
                                                        'status' => 'available',
                                                        'is_client_supplied' => 0,
                                                        'quantity' => $quantity,
                                                        'available_quantity' => $quantity,
                                                        'acquisition_cost' => $item['unit_price'],
                                                        'unit_cost' => $item['unit_price'],
                                                        'model' => $item['model'],
                                                        'specifications' => $item['specifications']
                                                    ];
                                                    $columns = implode(", ", array_keys($assetData));
                                                    $placeholders = implode(", ", array_fill(0, count($assetData), '?'));
                                                    $sql = "INSERT INTO assets ($columns) VALUES ($placeholders)";
                                                    $stmt = $db->prepare($sql);
                                                    $result = $stmt->execute(array_values($assetData));
                                                    if ($result) {
                                                        $assetId = $db->lastInsertId();
                                                        // Link asset to procurement_assets
                                                        $linkSql = "INSERT INTO procurement_assets (asset_id, procurement_order_id, procurement_item_id, created_at) VALUES (?, ?, ?, NOW())";
                                                        $linkStmt = $db->prepare($linkSql);
                                                        $linkStmt->execute([$assetId, $orderId, $itemId]);
                                                        $generatedAssets++;
                                                    } else {
                                                        $errors[] = 'Failed to create asset for item: ' . $item['item_name'];
                                                    }
                                                } else {
                                                    // Non-consumable: one asset per quantity
                                                    for ($i = 0; $i < $quantity; $i++) {
                                                        $assetData = [
                                                            'ref' => $this->generateAssetReference(),
                                                            'category_id' => $categoryId,
                                                            'name' => $item['item_name'],
                                                            'description' => $item['description'],
                                                            'project_id' => $procurementOrder['project_id'],
                                                            'vendor_id' => $procurementOrder['vendor_id'],
                                                            'procurement_order_id' => $orderId,
                                                            'procurement_item_id' => $itemId,
                                                            'acquired_date' => date('Y-m-d'),
                                                            'status' => 'available',
                                                            'is_client_supplied' => 0,
                                                            'quantity' => 1,
                                                            'available_quantity' => 1,
                                                            'acquisition_cost' => $item['unit_price'],
                                                            'unit_cost' => $item['unit_price'],
                                                            'model' => $item['model'],
                                                            'specifications' => $item['specifications']
                                                        ];
                                                        $columns = implode(", ", array_keys($assetData));
                                                        $placeholders = implode(", ", array_fill(0, count($assetData), '?'));
                                                        $sql = "INSERT INTO assets ($columns) VALUES ($placeholders)";
                                                        $stmt = $db->prepare($sql);
                                                        $result = $stmt->execute(array_values($assetData));
                                                        if ($result) {
                                                            $assetId = $db->lastInsertId();
                                                            // Link asset to procurement_assets
                                                            $linkSql = "INSERT INTO procurement_assets (asset_id, procurement_order_id, procurement_item_id, created_at) VALUES (?, ?, ?, NOW())";
                                                            $linkStmt = $db->prepare($linkSql);
                                                            $linkStmt->execute([$assetId, $orderId, $itemId]);
                                                            $generatedAssets++;
                                                        } else {
                                                            $errors[] = 'Failed to create asset for item: ' . $item['item_name'];
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            // Log asset generation
                                            if ($generatedAssets > 0) {
                                                logActivity('assets_generated', "Generated {$generatedAssets} assets from procurement order #{$procurementOrder['po_number']} during receipt confirmation", 'assets', $orderId);
                                                
                                                // Log procurement activity
                                                try {
                                                    $this->procurementOrderModel->logProcurementActivity(
                                                        $orderId, 
                                                        $currentUser['id'], 
                                                        'assets_generated_on_receipt', 
                                                        null, 
                                                        null, 
                                                        "Generated {$generatedAssets} assets automatically upon receipt confirmation"
                                                    );
                                                } catch (Exception $logError) {
                                                    error_log("Failed to log procurement activity: " . $logError->getMessage());
                                                }
                                            }
                                            
                                            $db->commit();
                                            
                                        }
                                    } catch (Exception $assetError) {
                                        $db->rollback();
                                        error_log("Asset generation error during receipt: " . $assetError->getMessage());
                                        // Don't fail the receipt confirmation if asset generation fails
                                    }
                                }
                                
                            } catch (Exception $assetError) {
                                error_log("Asset generation error: " . $assetError->getMessage());
                                // Don't fail the receipt confirmation if asset generation fails
                            }
                        }
                        
                        header('Location: ?route=procurement-orders/view&id=' . $id . '&message=receipt_confirmed');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Confirm Receipt - ConstructLink™';
            $pageHeader = 'Confirm Receipt: ' . $procurementOrder['po_number'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Confirm Receipt', 'url' => '?route=procurement-orders/confirm-receipt&id=' . $id]
            ];
            
            include APP_ROOT . '/views/procurement-orders/confirm-receipt.php';
            
        } catch (Exception $e) {
            error_log("Confirm receipt error: " . $e->getMessage());
            $error = 'Failed to load order for receipt confirmation';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Resolve delivery discrepancy
     */
    public function resolveDiscrepancy() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/resolve-discrepancy'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithDetails($id);
            
            if (!$procurementOrder) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $resolutionData = [
                    'resolution_notes' => Validator::sanitize($_POST['resolution_notes'] ?? ''),
                    'resolution_action' => $_POST['resolution_action'] ?? 'document_only'
                ];
                
                // Validate required fields
                if (empty($resolutionData['resolution_notes'])) {
                    $errors[] = 'Resolution notes are required';
                }
                
                if (empty($errors)) {
                    $result = $this->procurementOrderModel->resolveDiscrepancy($id, $resolutionData, $this->auth->getCurrentUser()['id']);
                    
                    if ($result['success']) {
                        header('Location: ?route=procurement-orders/view&id=' . $id . '&message=discrepancy_resolved');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Resolve Discrepancy - ConstructLink™';
            $pageHeader = 'Resolve Discrepancy: ' . $procurementOrder['po_number'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Resolve Discrepancy', 'url' => '?route=procurement-orders/resolve-discrepancy&id=' . $id]
            ];
            
            include APP_ROOT . '/views/procurement-orders/resolve-discrepancy.php';
            
        } catch (Exception $e) {
            error_log("Resolve discrepancy error: " . $e->getMessage());
            $error = 'Failed to load order for discrepancy resolution';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Resolve item-level discrepancies
     */
    public function resolveItemDiscrepancy() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/resolve-discrepancy'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=procurement-orders/resolve-discrepancy&id=' . $id);
            exit;
        }

        $errors = [];
        
        try {
            CSRFProtection::validateRequest();
            
            $resolveItems = $_POST['resolve_items'] ?? [];
            $itemResolutionNotes = $_POST['item_resolution_notes'] ?? [];
            
            if (empty($resolveItems)) {
                $errors[] = 'Please select at least one item to resolve';
            }
            
            // Validate that all selected items have resolution notes
            foreach ($resolveItems as $itemId) {
                if (empty($itemResolutionNotes[$itemId])) {
                    $errors[] = 'Resolution notes are required for all selected items';
                    break;
                }
            }
            
            if (empty($errors)) {
                $procurementItemModel = new ProcurementItemModel();
                $successCount = 0;
                $failCount = 0;
                
                foreach ($resolveItems as $itemId) {
                    $resolutionNotes = Validator::sanitize($itemResolutionNotes[$itemId]);
                    $result = $procurementItemModel->resolveItemDiscrepancy($itemId, $this->auth->getCurrentUser()['id'], $resolutionNotes);
                    
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failCount++;
                        error_log("Failed to resolve item discrepancy for item ID: $itemId - " . $result['message']);
                    }
                }
                
                if ($successCount > 0) {
                    $message = "Successfully resolved discrepancies for $successCount item(s)";
                    if ($failCount > 0) {
                        $message .= " ($failCount failed)";
                    }
                    header('Location: ?route=procurement-orders/view&id=' . $id . '&message=item_discrepancies_resolved&count=' . $successCount);
                } else {
                    $errors[] = 'Failed to resolve any item discrepancies';
                }
            }
            
            if (!empty($errors)) {
                $errorString = implode('|', $errors);
                header('Location: ?route=procurement-orders/resolve-discrepancy&id=' . $id . '&error=' . urlencode($errorString));
            }
            
        } catch (Exception $e) {
            error_log("Resolve item discrepancy error: " . $e->getMessage());
            header('Location: ?route=procurement-orders/resolve-discrepancy&id=' . $id . '&error=' . urlencode('Failed to resolve item discrepancies'));
        }
        
        exit;
    }
    
    /**
     * Show delivery performance metrics
     */
    public function deliveryPerformance() {
        // Check permissions
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/delivery-performance'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Get filter parameters
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            $projectId = $_GET['project_id'] ?? null;
            
            // Get delivery performance metrics
            $metrics = $this->procurementOrderModel->getDeliveryPerformanceMetrics($dateFrom, $dateTo, $projectId);
            $summary = getDeliveryPerformanceSummary($metrics);
            
            // Get recent delivery alerts
            $alerts = $this->procurementOrderModel->getOrdersWithDeliveryAlerts();
            
            $pageTitle = 'Delivery Performance - ConstructLink™';
            $pageHeader = 'Delivery Performance Metrics';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Delivery Performance', 'url' => '?route=procurement-orders/delivery-performance']
            ];
            
            // Pass data to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/procurement-orders/delivery-performance.php';
            
        } catch (Exception $e) {
            error_log("Delivery performance error: " . $e->getMessage());
            $error = 'Failed to load delivery performance data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Consolidated delivery management view - combines ready for delivery, schedule delivery, and in transit
     */
    public function deliveryManagement() {
        // Check permissions
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/delivery-management'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Get current user info for filtering
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            
            // Get assigned project IDs for role-based filtering
            $assignedProjectIds = null;
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                $assignedProjectIds = $this->getUserAssignedProjectIds($currentUser['id'], $userRole);
                if (empty($assignedProjectIds)) {
                    $assignedProjectIds = [-1]; // Show no results if no assigned projects
                }
            }
            
            // Get orders at different delivery stages with project filtering
            $readyForDelivery = $this->procurementOrderModel->getOrdersReadyForDelivery($assignedProjectIds);
            $scheduledOrders = $this->procurementOrderModel->getOrdersByDeliveryStatus('Scheduled', $assignedProjectIds);
            $inTransitOrders = $this->procurementOrderModel->getOrdersByDeliveryStatus('In Transit', $assignedProjectIds);
            
            // Get orders for receipt and asset generation with project filtering
            $ordersForReceipt = $this->procurementOrderModel->getOrdersForReceipt($assignedProjectIds);
            $ordersForAssets = $this->procurementOrderModel->getReceivedOrders($assignedProjectIds);
            
            // Get statistics for each stage
            $stats = [
                'ready_count' => count($readyForDelivery),
                'scheduled_count' => count($scheduledOrders),
                'in_transit_count' => count($inTransitOrders),
                'for_receipt_count' => count($ordersForReceipt),
                'for_assets_count' => count($ordersForAssets),
                'total_value' => array_sum(array_column($readyForDelivery, 'total_value')) + 
                               array_sum(array_column($scheduledOrders, 'total_value')) + 
                               array_sum(array_column($inTransitOrders, 'total_value')) +
                               array_sum(array_column($ordersForReceipt, 'total_value')) +
                               array_sum(array_column($ordersForAssets, 'total_value'))
            ];
            
            $pageTitle = 'Delivery Management - ConstructLink™';
            $pageHeader = 'Delivery Management Dashboard';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Delivery Management', 'url' => '?route=procurement-orders/delivery-management']
            ];
            
            // Pass data to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/procurement-orders/delivery-management.php';
            
        } catch (Exception $e) {
            error_log("Delivery management error: " . $e->getMessage());
            $error = 'Failed to load delivery management data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Consolidated receipt and assets view - combines for receipt and generate assets
     */
    public function receiptAssets() {
        // Check permissions
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/receipt-assets'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Get orders for receipt
            $ordersForReceipt = $this->procurementOrderModel->getOrdersForReceipt();
            
            // Get orders ready for asset generation
            $ordersForAssets = $this->procurementOrderModel->getReceivedOrders();
            
            // Get statistics
            $stats = [
                'for_receipt_count' => count($ordersForReceipt),
                'for_assets_count' => count($ordersForAssets),
                'total_value' => array_sum(array_column($ordersForReceipt, 'total_value')) + 
                               array_sum(array_column($ordersForAssets, 'total_value'))
            ];
            
            $pageTitle = 'Receipt & Assets - ConstructLink™';
            $pageHeader = 'Receipt & Asset Generation Dashboard';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Receipt & Assets', 'url' => '?route=procurement-orders/receipt-assets']
            ];
            
            // Pass data to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/procurement-orders/receipt-assets.php';
            
        } catch (Exception $e) {
            error_log("Receipt assets error: " . $e->getMessage());
            $error = 'Failed to load receipt and assets data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Enhanced performance dashboard - consolidates all performance metrics
     */
    public function performanceDashboard() {
        // Check permissions
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/performance-dashboard'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Get filter parameters
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            $projectId = $_GET['project_id'] ?? null;
            
            // Get comprehensive performance metrics
            $deliveryMetrics = $this->procurementOrderModel->getDeliveryPerformanceMetrics($dateFrom, $dateTo, $projectId);
            $overallStats = $this->procurementOrderModel->getOverallProcurementStats($dateFrom, $dateTo, $projectId);
            $supplierPerformance = $this->procurementOrderModel->getSupplierPerformanceMetrics($dateFrom, $dateTo);
            
            // Get delivery alerts
            $alerts = $this->procurementOrderModel->getOrdersWithDeliveryAlerts();
            
            // Get summary data
            $summary = getDeliveryPerformanceSummary($deliveryMetrics);
            
            $pageTitle = 'Performance Dashboard - ConstructLink™';
            $pageHeader = 'Procurement Performance Dashboard';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Performance Dashboard', 'url' => '?route=procurement-orders/performance-dashboard']
            ];
            
            // Pass data to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/procurement-orders/performance-dashboard.php';
            
        } catch (Exception $e) {
            error_log("Performance dashboard error: " . $e->getMessage());
            $error = 'Failed to load performance dashboard data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Generate asset reference number
     */
    private function generateAssetReference() {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get the highest existing reference number
            $sql = "SELECT ref FROM assets WHERE ref LIKE 'CL%' ORDER BY ref DESC LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result) {
                // Extract number from reference like "CL2024001"
                $lastRef = $result['ref'];
                $number = (int)substr($lastRef, -4); // Get last 4 digits
                $nextNumber = $number + 1;
            } else {
                $nextNumber = 1;
            }
            
            return 'CL' . date('Y') . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
        } catch (Exception $e) {
            error_log("Generate asset reference error: " . $e->getMessage());
            return 'CL' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Submit procurement order for approval
     */
    public function submitForApproval() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/create'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            try {
                $procurementOrder = $this->procurementOrderModel->find($id);
                
                if (!$procurementOrder) {
                    http_response_code(404);
                    include APP_ROOT . '/views/errors/404.php';
                    return;
                }
                
                if ($procurementOrder['status'] !== 'Draft') {
                    header('Location: ?route=procurement-orders/view&id=' . $id . '&error=invalid_status');
                    exit;
                }
                
                // Update status to Pending
                $result = $this->procurementOrderModel->updateStatus($id, 'Pending', $this->auth->getCurrentUser()['id'], 'Submitted for approval');
                
                if ($result) {
                    header('Location: ?route=procurement-orders/view&id=' . $id . '&message=submitted_for_approval');
                    exit;
                } else {
                    header('Location: ?route=procurement-orders/view&id=' . $id . '&error=submit_failed');
                    exit;
                }
                
            } catch (Exception $e) {
                error_log("Submit for approval error: " . $e->getMessage());
                header('Location: ?route=procurement-orders/view&id=' . $id . '&error=submit_failed');
                exit;
            }
        }
        
        // If not POST, redirect back
        header('Location: ?route=procurement-orders/view&id=' . $id);
        exit;
    }

    /**
     * Export procurement orders to CSV/Excel
     */
    public function export() {
        // Check permissions
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/export'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Build filters
            $filters = [];
            if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
            if (!empty($_GET['project_id'])) $filters['project_id'] = $_GET['project_id'];
            if (!empty($_GET['vendor_id'])) $filters['vendor_id'] = $_GET['vendor_id'];
            if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
            if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
            
            // Get all procurement orders (no pagination for export)
            $result = $this->procurementOrderModel->getProcurementOrdersWithFilters($filters, 1, 10000);
            $procurementOrders = $result['data'] ?? [];
            
            // Set headers for CSV download
            $filename = 'procurement_orders_' . date('Y-m-d_H-i-s') . '.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Create CSV output
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, [
                'PO Number', 'Title', 'Vendor', 'Project', 'Status', 'Delivery Status',
                'Subtotal', 'VAT Amount', 'EWT Amount', 'Net Total', 'Requested By',
                'Approved By', 'Date Needed', 'Created Date'
            ]);
            
            // CSV data
            foreach ($procurementOrders as $order) {
                fputcsv($output, [
                    $order['po_number'],
                    $order['title'],
                    $order['vendor_name'],
                    $order['project_name'],
                    $order['status'],
                    $order['delivery_status'],
                    $order['subtotal'],
                    $order['vat_amount'],
                    $order['ewt_amount'],
                    $order['net_total'],
                    $order['requested_by_name'],
                    $order['approved_by_name'],
                    $order['date_needed'],
                    $order['created_at']
                ]);
            }
            
            fclose($output);
            
        } catch (Exception $e) {
            error_log("Procurement orders export error: " . $e->getMessage());
            header('Location: ?route=procurement-orders&error=export_failed');
            exit;
        }
    }

    
    
    
    /**
     * Print preview for procurement order
     */
    public function printPreview() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/print-preview'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $orderId = $_GET['id'] ?? 0;
        
        if (!$orderId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $procurementOrder = $this->procurementOrderModel->getProcurementOrderWithItems($orderId);
            
            if (!$procurementOrder) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check project assignment access for specific roles
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk', 'Warehouseman'])) {
                $assignedProjectIds = $this->getUserAssignedProjectIds($currentUser['id'], $userRole);
                if (empty($assignedProjectIds) || !in_array($procurementOrder['project_id'], $assignedProjectIds)) {
                    http_response_code(403);
                    include APP_ROOT . '/views/errors/403.php';
                    return;
                }
            }
            
            // Check if order status allows print preview
            if (!$this->canPrintPreview($procurementOrder['status'])) {
                $allowedStatuses = $this->getAllowedPrintStatuses();
                $_SESSION['error'] = 'Print preview is only available for orders with status: ' . implode(', ', $allowedStatuses) . '. Current status: ' . $procurementOrder['status'];
                header('Location: ?route=procurement-orders/view&id=' . $orderId);
                exit;
            }
            
            // Get vendor address
            $procurementOrder['vendor_address'] = $this->getVendorAddress($procurementOrder['vendor_id']);
            
            // Set page title and load print view
            $pageTitle = 'Purchase Order - ' . $procurementOrder['po_number'];
            
            include APP_ROOT . '/views/procurement-orders/print-preview.php';
            
        } catch (Exception $e) {
            error_log("Print preview error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to load print preview: ' . $e->getMessage();
            header('Location: ?route=procurement-orders/view&id=' . $orderId);
            exit;
        }
    }
    
    
    /**
     * Get allowed statuses for print preview
     */
    private function getAllowedPrintStatuses() {
        return [
            'Pending',
            'Approved',
            'Scheduled for Delivery', 
            'In Transit',
            'Delivered',
            'Received'
        ];
    }
    
    /**
     * Check if status allows print preview
     */
    private function canPrintPreview($status) {
        return in_array($status, $this->getAllowedPrintStatuses());
    }
    
    /**
     * Get vendor address from vendor table
     */
    private function getVendorAddress($vendorId) {
        try {
            $vendorModel = new VendorModel();
            $vendor = $vendorModel->find($vendorId);
            
            if ($vendor && !empty($vendor['address'])) {
                return $vendor['address'];
            }
            
            return 'Address not provided';
            
        } catch (Exception $e) {
            error_log("Get vendor address error: " . $e->getMessage());
            return 'Address not available';
        }
    }
    
    /**
     * Create retroactive procurement order for items purchased without PO
     */
    public function createRetrospective() {
        if (!$this->auth->hasRole($this->roleConfig['procurement-orders/create'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        $formData = [];
        $items = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            // Process form submission - similar to regular create but with retroactive fields
            $formData = [
                'vendor_id' => (int)($_POST['vendor_id'] ?? 0),
                'project_id' => (int)($_POST['project_id'] ?? 0),
                'title' => Validator::sanitize($_POST['title'] ?? ''),
                'package_scope' => Validator::sanitize($_POST['package_scope'] ?? ''),
                'work_breakdown' => Validator::sanitize($_POST['work_breakdown'] ?? ''),
                'budget_allocation' => (float)($_POST['budget_allocation'] ?? 0),
                'justification' => Validator::sanitize($_POST['justification'] ?? ''),
                'date_needed' => !empty($_POST['date_needed']) ? $_POST['date_needed'] : null,
                'delivery_method' => Validator::sanitize($_POST['delivery_method'] ?? ''),
                'delivery_location' => Validator::sanitize($_POST['delivery_location'] ?? ''),
                'quotation_number' => Validator::sanitize($_POST['quotation_number'] ?? ''),
                'quotation_date' => !empty($_POST['quotation_date']) ? $_POST['quotation_date'] : null,
                'vat_rate' => (float)($_POST['vat_rate'] ?? 12.00),
                'ewt_rate' => (float)($_POST['ewt_rate'] ?? 1.00),
                'handling_fee' => (float)($_POST['handling_fee'] ?? 0),
                'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
                'notes' => Validator::sanitize($_POST['notes'] ?? ''),
                'requested_by' => $this->auth->getCurrentUser()['id'],
                // Retroactive-specific fields
                'retroactive_reason' => Validator::sanitize($_POST['reason'] ?? ''),
            ];
            
            $currentState = $_POST['current_state'] ?? 'not_delivered';
            
            // Validate required fields
            if (empty($formData['vendor_id'])) {
                $errors[] = 'Vendor is required';
            }
            if (empty($formData['project_id'])) {
                $errors[] = 'Project is required';
            }
            if (empty($formData['title'])) {
                $errors[] = 'Title is required';
            }
            if (empty($formData['retroactive_reason'])) {
                $errors[] = 'Reason for retroactive PO is required';
            }
            
            // Process items - reuse existing logic
            if (!empty($_POST['items'])) {
                foreach ($_POST['items'] as $index => $item) {
                    if (empty($item['item_name']) || empty($item['quantity']) || empty($item['unit_price'])) {
                        continue; // Skip empty items
                    }
                    
                    $items[] = [
                        'item_name' => Validator::sanitize($item['item_name']),
                        'description' => Validator::sanitize($item['description'] ?? ''),
                        'specifications' => Validator::sanitize($item['specifications'] ?? ''),
                        'model' => Validator::sanitize($item['model'] ?? ''),
                        'brand' => Validator::sanitize($item['brand'] ?? ''),
                        'category_id' => !empty($item['category_id']) ? (int)$item['category_id'] : null,
                        'quantity' => (int)$item['quantity'],
                        'unit' => Validator::sanitize($item['unit'] ?? 'pcs'),
                        'unit_price' => (float)$item['unit_price'],
                        'quantity_received' => (int)($item['quantity_received'] ?? $item['quantity']), // For retroactive, might already be received
                    ];
                }
            }
            
            if (empty($items)) {
                $errors[] = 'At least one item is required';
            }
            
            // Add delivery validation for retrospective PO
            if (!empty($formData['delivery_method']) || !empty($formData['delivery_location'])) {
                // For retrospective validation, we can't use procurement order ID since it doesn't exist yet
                // We'll validate based on the items' categories
                $hasPhysicalItems = false;
                $hasServiceItems = false;
                
                foreach ($items as $item) {
                    if (!empty($item['category_id'])) {
                        // Check category type from database
                        try {
                            $categoryModel = new CategoryModel();
                            $category = $categoryModel->find($item['category_id']);
                            if ($category) {
                                if ($category['generates_assets'] ?? true) {
                                    $hasPhysicalItems = true;
                                } else {
                                    $hasServiceItems = true;
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Error checking category for retrospective validation: " . $e->getMessage());
                            $hasPhysicalItems = true; // Default to physical for safety
                        }
                    }
                }
                
                // Basic delivery field validation based on detected category types
                if (empty($formData['delivery_method'])) {
                    if ($hasServiceItems && !$hasPhysicalItems) {
                        $errors[] = 'Service delivery method is required';
                    } else {
                        $errors[] = 'Delivery method is required';
                    }
                }
                
                if (empty($formData['delivery_location'])) {
                    if ($hasServiceItems && !$hasPhysicalItems) {
                        $errors[] = 'Service location is required';
                    } else {
                        $errors[] = 'Delivery location is required';
                    }
                }
            }
            
            // Create retroactive PO if no errors
            if (empty($errors)) {
                $result = $this->procurementOrderModel->createRetrospectivePO($formData, $items, $currentState);
                
                if ($result['success']) {
                    header('Location: ?route=procurement-orders/view&id=' . $result['procurement_order']['id'] . '&message=retroactive_created');
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        try {
            // Load form data - reuse existing logic
            $vendorModel = new VendorModel();
            $projectModel = new ProjectModel();
            $categoryModel = new CategoryModel();
            
            $vendors = $vendorModel->findAll(['is_active' => 1], "name ASC");
            $projects = $projectModel->findAll(['is_active' => 1], "name ASC");
            $categories = $categoryModel->findAll([], "name ASC");
            
            // Define reason options
            $reasonOptions = [
                'EMERGENCY_PURCHASE' => 'Emergency Purchase - Urgent operational need',
                'DIRECT_PICKUP' => 'Direct Pickup - Collected from supplier directly',
                'SMALL_VALUE' => 'Small Value - Below procurement threshold',
                'CASH_PURCHASE' => 'Cash Purchase - Immediate need cash purchase',
                'VENDOR_SAMPLE' => 'Vendor Sample - Demo unit retained for project',
                'WARRANTY_REPLACEMENT' => 'Warranty Replacement - Logistics cost for warranty items',
                'INTER_COMPANY' => 'Inter-Company - Purchase from related entity',
                'AUDIT_DISCOVERY' => 'Audit Discovery - Undocumented purchase found'
            ];
            
            $pageTitle = 'Create Retroactive PO - ConstructLink™';
            $pageHeader = 'Create Retroactive Procurement Order';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'Retroactive PO', 'url' => '?route=procurement-orders/create-retrospective']
            ];
            
            include APP_ROOT . '/views/procurement-orders/create-retrospective.php';
            
        } catch (Exception $e) {
            error_log("Retroactive procurement order create form error: " . $e->getMessage());
            $error = 'Failed to load form data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Serve files securely with authentication and authorization checks
     */
    public function serveFile() {
        try {
            $orderId = $_GET['id'] ?? 0;
            $fileType = $_GET['type'] ?? '';
            
            if (!$orderId || !$fileType) {
                http_response_code(400);
                die('Invalid file request');
            }
            
            // Get procurement order
            $procurementOrder = $this->procurementOrderModel->find($orderId);
            if (!$procurementOrder) {
                http_response_code(404);
                die('Procurement order not found');
            }
            
            // Check user permissions - can view if user has general view access or is related to the order
            $canView = $this->auth->hasRole($this->roleConfig['procurement-orders/view'] ?? ['System Admin']);
            if (!$canView) {
                // Check if user is related to this order (requester, approver, etc.)
                $userId = $this->auth->getCurrentUser()['id'];
                $canView = ($procurementOrder['requested_by'] == $userId || 
                           $procurementOrder['approved_by'] == $userId ||
                           $procurementOrder['verified_by'] == $userId);
            }
            
            if (!$canView) {
                http_response_code(403);
                die('Access denied');
            }
            
            // Validate file type and get filename
            $validTypes = ['quote_file', 'purchase_receipt_file', 'supporting_evidence_file'];
            if (!in_array($fileType, $validTypes)) {
                http_response_code(400);
                die('Invalid file type');
            }
            
            $filename = $procurementOrder[$fileType] ?? '';
            if (empty($filename)) {
                http_response_code(404);
                die('File not found');
            }
            
            // Construct file path
            require_once APP_ROOT . '/core/ProcurementFileUploader.php';
            $filePath = ProcurementFileUploader::getUploadPath() . $filename;
            
            if (!file_exists($filePath)) {
                http_response_code(404);
                die('File not found on server');
            }
            
            // Get file info
            $fileSize = filesize($filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            
            // Set appropriate headers based on action
            $action = $_GET['action'] ?? 'view';
            
            if ($action === 'download') {
                // Force download
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            } else {
                // Inline viewing
                header('Content-Type: ' . $mimeType);
                header('Content-Disposition: inline; filename="' . basename($filename) . '"');
            }
            
            header('Content-Length: ' . $fileSize);
            header('Accept-Ranges: bytes');
            header('X-Content-Type-Options: nosniff');
            
            // Output file
            readfile($filePath);
            exit;
            
        } catch (Exception $e) {
            error_log("File serving error: " . $e->getMessage());
            http_response_code(500);
            die('File serving error');
        }
    }

    /**
     * Get file preview for supported file types
     */
    public function previewFile() {
        try {
            $orderId = $_GET['id'] ?? 0;
            $fileType = $_GET['type'] ?? '';
            
            if (!$orderId || !$fileType) {
                http_response_code(400);
                die('Invalid preview request');
            }
            
            // Get procurement order
            $procurementOrder = $this->procurementOrderModel->find($orderId);
            if (!$procurementOrder) {
                http_response_code(404);
                die('Procurement order not found');
            }
            
            // Check permissions (same as serveFile)
            $canView = $this->auth->hasRole($this->roleConfig['procurement-orders/view'] ?? ['System Admin']);
            if (!$canView) {
                $userId = $this->auth->getCurrentUser()['id'];
                $canView = ($procurementOrder['requested_by'] == $userId || 
                           $procurementOrder['approved_by'] == $userId ||
                           $procurementOrder['verified_by'] == $userId);
            }
            
            if (!$canView) {
                http_response_code(403);
                die('Access denied');
            }
            
            // Validate file type
            $validTypes = ['quote_file', 'purchase_receipt_file', 'supporting_evidence_file'];
            if (!in_array($fileType, $validTypes)) {
                http_response_code(400);
                die('Invalid file type');
            }
            
            $filename = $procurementOrder[$fileType] ?? '';
            if (empty($filename)) {
                http_response_code(404);
                die('File not found');
            }
            
            // Construct file path
            require_once APP_ROOT . '/core/ProcurementFileUploader.php';
            $filePath = ProcurementFileUploader::getUploadPath() . $filename;
            
            if (!file_exists($filePath)) {
                http_response_code(404);
                die('File not found on server');
            }
            
            // Get file extension
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            // Return preview content based on file type
            header('Content-Type: application/json');
            
            $response = [
                'filename' => $filename,
                'type' => $extension,
                'size' => filesize($filePath),
                'url' => "?route=procurement-orders/file&id={$orderId}&type={$fileType}&action=view"
            ];
            
            // For images, provide thumbnail info
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $imageInfo = getimagesize($filePath);
                $response['width'] = $imageInfo[0] ?? 0;
                $response['height'] = $imageInfo[1] ?? 0;
            }
            
            echo json_encode($response);
            exit;
            
        } catch (Exception $e) {
            error_log("File preview error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Preview generation failed']);
            exit;
        }
    }
}
