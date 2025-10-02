<?php
/**
 * ConstructLink™ Maintenance Controller
 * Handles asset maintenance scheduling and tracking
 */

class MaintenanceController {
    private $auth;
    private $maintenanceModel;
    private $roleConfig;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->maintenanceModel = new MaintenanceModel();
        $this->roleConfig = require APP_ROOT . '/config/roles.php';
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Check if user has maintenance permission following MVA structure
     */
    private function hasMaintenancePermission($action, $maintenance = null) {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if ($userRole === 'System Admin') return true;

        // Check role config for specific action
        $allowedRoles = $this->roleConfig['maintenance/' . $action] ?? [];
        if (in_array($userRole, $allowedRoles)) return true;

        // MVA workflow specific checks
        if ($maintenance) {
            switch ($action) {
                case 'verify':
                    // Only Project Manager can verify (Verifier role)
                    return in_array($userRole, $this->roleConfig['maintenance']['verifier'] ?? []);
                    
                case 'authorize':
                    // Only Asset Director can authorize (Authorizer role)
                    return in_array($userRole, $this->roleConfig['maintenance']['authorizer'] ?? []);
                    
                case 'start':
                case 'complete':
                    // Asset Director or System Admin can start/complete
                    return in_array($userRole, ['Asset Director', 'System Admin']);
                    
                case 'cancel':
                    // Ownership-based cancellation or authorized roles
                    if ($maintenance['created_by'] == $currentUser['id']) return true;
                    return in_array($userRole, $this->roleConfig['maintenance/cancel'] ?? []);
                    
                case 'edit':
                    // Only System Admin or Asset Director can edit
                    return in_array($userRole, ['System Admin', 'Asset Director']);
            }
        }

        return false;
    }
    
    /**
     * Display maintenance listing
     */
    public function index() {
        // Check permissions using role config
        $allowedRoles = $this->roleConfig['maintenance']['viewer'] ?? [];
        if (!$this->auth->hasRole($allowedRoles)) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        
        // Build filters
        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['type'])) $filters['type'] = $_GET['type'];
        if (!empty($_GET['priority'])) $filters['priority'] = $_GET['priority'];
        if (!empty($_GET['asset_id'])) $filters['asset_id'] = $_GET['asset_id'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
        if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
        
        try {
            // Get maintenance records with pagination
            $result = $this->maintenanceModel->getMaintenanceWithFilters($filters, $page, $perPage);
            $maintenance = $result['data'];
            $pagination = $result['pagination'];
            
            // Get maintenance statistics
            $maintenanceStats = [
                'scheduled' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'canceled' => 0,
                'overdue' => 0
            ];
            
            try {
                $statsResult = $this->maintenanceModel->getMaintenanceStats();
                if ($statsResult) {
                    $maintenanceStats = array_merge($maintenanceStats, $statsResult);
                }
            } catch (Exception $e) {
                error_log("Maintenance stats error: " . $e->getMessage());
            }
            
            // Get overdue maintenance
            $overdueMaintenance = [];
            try {
                $overdueMaintenance = $this->maintenanceModel->getOverdueMaintenance();
            } catch (Exception $e) {
                error_log("Overdue maintenance error: " . $e->getMessage());
            }
            
            $pageTitle = 'Asset Maintenance - ConstructLink™';
            $pageHeader = 'Asset Maintenance';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Maintenance', 'url' => '?route=maintenance']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/maintenance/index.php';
            
        } catch (Exception $e) {
            error_log("Maintenance listing error: " . $e->getMessage());
            $error = 'Failed to load maintenance records';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create maintenance form
     */
    public function create() {
        // Check permissions
        if (!$this->hasMaintenancePermission('create')) {
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
                'asset_id' => (int)($_POST['asset_id'] ?? 0),
                'type' => $_POST['type'] ?? 'preventive',
                'description' => Validator::sanitize($_POST['description'] ?? ''),
                'scheduled_date' => $_POST['scheduled_date'] ?? date('Y-m-d'),
                'estimated_cost' => !empty($_POST['estimated_cost']) ? (float)$_POST['estimated_cost'] : null,
                'assigned_to' => Validator::sanitize($_POST['assigned_to'] ?? ''),
                'priority' => $_POST['priority'] ?? 'medium',
                'created_by' => $this->auth->getCurrentUser()['id']
            ];
            
            $result = $this->maintenanceModel->createMaintenance($formData);
            
            if ($result['success']) {
                header('Location: ?route=maintenance/view&id=' . $result['maintenance']['id'] . '&message=maintenance_created');
                exit;
            } else {
                if (isset($result['errors'])) {
                    $errors = $result['errors'];
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        try {
            // Get form options - only non-consumable assets that can be maintained
            $assetModel = new AssetModel();
            $assets = $assetModel->getMaintenableAssets();
            
            // Debug: log the assets count
            error_log("Maintainable assets found: " . count($assets));
            
            $pageTitle = 'Schedule Maintenance - ConstructLink™';
            $pageHeader = 'Schedule Maintenance';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Maintenance', 'url' => '?route=maintenance'],
                ['title' => 'Schedule Maintenance', 'url' => '?route=maintenance/create']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/maintenance/create.php';
            
        } catch (Exception $e) {
            error_log("Maintenance create form error: " . $e->getMessage());
            $error = 'Failed to load form data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Verify maintenance request
     */
    public function verify() {
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        $maintenance = $this->maintenanceModel->getMaintenanceWithDetails($id);
        if (!$maintenance) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        if (!$this->hasMaintenancePermission('verify', $maintenance)) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();

            $notes = Validator::sanitize($_POST['verification_notes'] ?? '');
            $verifiedBy = $this->auth->getCurrentUser()['id'];

            $result = $this->maintenanceModel->verifyMaintenance($id, $verifiedBy, $notes);

            if ($result['success']) {
                header('Location: ?route=maintenance/view&id=' . $id . '&message=maintenance_verified');
                exit;
            } else {
                $errors[] = $result['message'];
            }
        }

        $pageTitle = 'Verify Maintenance - ConstructLink™';
        $pageHeader = 'Verify Maintenance #' . $maintenance['id'];
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Maintenance', 'url' => '?route=maintenance'],
            ['title' => 'Verify Maintenance', 'url' => '?route=maintenance/verify&id=' . $id]
        ];

        include APP_ROOT . '/views/maintenance/verify.php';
    }
    
    /**
     * Authorize maintenance request
     */
    public function authorize() {
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        $maintenance = $this->maintenanceModel->getMaintenanceWithDetails($id);
        if (!$maintenance) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        if (!$this->hasMaintenancePermission('authorize', $maintenance)) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();

            $notes = Validator::sanitize($_POST['authorization_notes'] ?? '');
            $authorizedBy = $this->auth->getCurrentUser()['id'];

            $result = $this->maintenanceModel->authorizeMaintenance($id, $authorizedBy, $notes);

            if ($result['success']) {
                header('Location: ?route=maintenance/view&id=' . $id . '&message=maintenance_authorized');
                exit;
            } else {
                $errors[] = $result['message'];
            }
        }

        $pageTitle = 'Authorize Maintenance - ConstructLink™';
        $pageHeader = 'Authorize Maintenance #' . $maintenance['id'];
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Maintenance', 'url' => '?route=maintenance'],
            ['title' => 'Authorize Maintenance', 'url' => '?route=maintenance/authorize&id=' . $id]
        ];

        include APP_ROOT . '/views/maintenance/authorize.php';
    }
    
    /**
     * Start maintenance work
     */
    public function start() {
        $maintenanceId = $_GET['id'] ?? 0;
        if (!$maintenanceId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        try {
            $maintenance = $this->maintenanceModel->getMaintenanceWithDetails($maintenanceId);
            if (!$maintenance) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check permissions for starting maintenance
            if (!$this->hasMaintenancePermission('start', $maintenance)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            
            // Additional check: maintenance must be approved to start
            if ($maintenance['status'] !== 'Approved') {
                $_SESSION['error'] = 'Maintenance must be approved before it can be started';
                header('Location: ?route=maintenance/view&id=' . $maintenanceId);
                exit;
            }
            
            $assignedTo = $_POST['assigned_to'] ?? null;
            $result = $this->maintenanceModel->startMaintenance($maintenanceId, $assignedTo);
            
            if ($result['success']) {
                header('Location: ?route=maintenance/view&id=' . $maintenanceId . '&message=maintenance_started');
                exit;
            } else {
                $_SESSION['error'] = $result['message'];
                header('Location: ?route=maintenance/view&id=' . $maintenanceId);
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Maintenance start error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to start maintenance';
            header('Location: ?route=maintenance/view&id=' . $maintenanceId);
            exit;
        }
    }
    
    /**
     * Complete maintenance
     */
    public function complete() {
        $maintenanceId = $_GET['id'] ?? 0;
        if (!$maintenanceId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        try {
            $maintenance = $this->maintenanceModel->getMaintenanceWithDetails($maintenanceId);
            if (!$maintenance) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check permissions for completing maintenance
            if (!$this->hasMaintenancePermission('complete', $maintenance)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            
            // Additional check: maintenance must be in progress to complete
            if ($maintenance['status'] !== 'in_progress') {
                $_SESSION['error'] = 'Maintenance must be in progress before it can be completed';
                header('Location: ?route=maintenance/view&id=' . $maintenanceId);
                exit;
            }
            
            $errors = [];
            $messages = [];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $completionData = [
                    'completion_notes' => Validator::sanitize($_POST['completion_notes'] ?? ''),
                    'actual_cost' => !empty($_POST['actual_cost']) ? (float)$_POST['actual_cost'] : null,
                    'parts_used' => Validator::sanitize($_POST['parts_used'] ?? ''),
                    'next_maintenance_date' => !empty($_POST['next_maintenance_date']) ? $_POST['next_maintenance_date'] : null
                ];
                
                $result = $this->maintenanceModel->completeMaintenance($maintenanceId, $completionData);
                
                if ($result['success']) {
                    header('Location: ?route=maintenance/view&id=' . $maintenanceId . '&message=maintenance_completed');
                    exit;
                } else {
                    if (isset($result['errors'])) {
                        $errors = $result['errors'];
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Complete Maintenance - ConstructLink™';
            $pageHeader = 'Complete Maintenance';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Maintenance', 'url' => '?route=maintenance'],
                ['title' => 'Complete Maintenance', 'url' => '?route=maintenance/complete&id=' . $maintenanceId]
            ];
            
            include APP_ROOT . '/views/maintenance/complete.php';
            
        } catch (Exception $e) {
            error_log("Maintenance complete error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to complete maintenance';
            header('Location: ?route=maintenance/view&id=' . $maintenanceId);
            exit;
        }
    }
    
    /**
     * View maintenance details
     */
    public function view() {
        $maintenanceId = $_GET['id'] ?? 0;
        
        if (!$maintenanceId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $maintenance = $this->maintenanceModel->getMaintenanceWithDetails($maintenanceId);
            
            if (!$maintenance) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Get maintenance history for this asset
            $maintenanceHistory = $this->maintenanceModel->getAssetMaintenanceHistory($maintenance['asset_id']);
            
            $pageTitle = 'Maintenance Details - ConstructLink™';
            $pageHeader = 'Maintenance #' . $maintenance['id'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Maintenance', 'url' => '?route=maintenance'],
                ['title' => 'View Details', 'url' => '?route=maintenance/view&id=' . $maintenanceId]
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/maintenance/view.php';
            
        } catch (Exception $e) {
            error_log("Maintenance view error: " . $e->getMessage());
            $error = 'Failed to load maintenance details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Edit maintenance record
     */
    public function edit() {
        // Check permissions using MVA structure
        if (!$this->hasMaintenancePermission('edit')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $maintenanceId = $_GET['id'] ?? 0;
        
        if (!$maintenanceId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $maintenance = $this->maintenanceModel->find($maintenanceId);
            
            if (!$maintenance) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $formData = $maintenance;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $formData = [
                    'description' => Validator::sanitize($_POST['description'] ?? ''),
                    'scheduled_date' => $_POST['scheduled_date'] ?? '',
                    'priority' => $_POST['priority'] ?? 'medium',
                    'estimated_cost' => !empty($_POST['estimated_cost']) ? (float)$_POST['estimated_cost'] : null,
                    'assigned_to' => Validator::sanitize($_POST['assigned_to'] ?? '')
                ];
                
                if (empty($formData['description'])) {
                    $errors[] = 'Description is required';
                }
                
                if (empty($formData['scheduled_date'])) {
                    $errors[] = 'Scheduled date is required';
                }
                
                if (empty($errors)) {
                    $result = $this->maintenanceModel->update($maintenanceId, $formData);
                    
                    if ($result) {
                        header('Location: ?route=maintenance/view&id=' . $maintenanceId . '&message=maintenance_updated');
                        exit;
                    } else {
                        $errors[] = 'Failed to update maintenance record.';
                    }
                }
            }
            
            // Get form options
            $assetModel = new AssetModel();
            $assets = $assetModel->findAll([], 'name ASC');
            
            $pageTitle = 'Edit Maintenance - ConstructLink™';
            $pageHeader = 'Edit Maintenance';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Maintenance', 'url' => '?route=maintenance'],
                ['title' => 'Edit Maintenance', 'url' => '?route=maintenance/edit&id=' . $maintenanceId]
            ];
            
            include APP_ROOT . '/views/maintenance/edit.php';
            
        } catch (Exception $e) {
            error_log("Maintenance edit error: " . $e->getMessage());
            $error = 'Failed to load maintenance record';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Cancel maintenance
     */
    public function cancel() {
        // Check permissions using MVA structure
        if (!$this->hasMaintenancePermission('cancel')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=maintenance');
            exit;
        }
        
        $maintenanceId = $_POST['maintenance_id'] ?? 0;
        $reason = $_POST['reason'] ?? '';
        
        if (!$maintenanceId) {
            $_SESSION['error'] = 'Invalid maintenance ID';
            header('Location: ?route=maintenance');
            exit;
        }
        
        try {
            $result = $this->maintenanceModel->cancelMaintenance($maintenanceId, $reason);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
        } catch (Exception $e) {
            error_log("Maintenance cancellation error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to cancel maintenance';
        }
        
        header('Location: ?route=maintenance/view&id=' . $maintenanceId);
        exit;
    }
    
    /**
     * Delete maintenance record
     */
    public function delete() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=maintenance');
            exit;
        }
        
        $maintenanceId = $_POST['maintenance_id'] ?? 0;
        
        if (!$maintenanceId) {
            $_SESSION['error'] = 'Invalid maintenance ID';
            header('Location: ?route=maintenance');
            exit;
        }
        
        try {
            $result = $this->maintenanceModel->delete($maintenanceId);
            
            if ($result) {
                $_SESSION['success'] = 'Maintenance record deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete maintenance record';
            }
            
        } catch (Exception $e) {
            error_log("Maintenance deletion error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete maintenance record';
        }
        
        header('Location: ?route=maintenance');
        exit;
    }
    
    /**
     * Get maintenance calendar data (AJAX)
     */
    public function calendar() {
        header('Content-Type: application/json');
        
        try {
            $start = $_GET['start'] ?? date('Y-m-01');
            $end = $_GET['end'] ?? date('Y-m-t');
            
            $maintenanceSchedule = $this->maintenanceModel->getMaintenanceSchedule($start, $end);
            
            // Format for calendar
            $events = [];
            foreach ($maintenanceSchedule as $maintenance) {
                $events[] = [
                    'id' => $maintenance['id'],
                    'title' => $maintenance['asset_name'] . ' - ' . ucfirst($maintenance['type']),
                    'start' => $maintenance['scheduled_date'],
                    'className' => 'maintenance-' . $maintenance['type'],
                    'url' => '?route=maintenance/view&id=' . $maintenance['id']
                ];
            }
            
            echo json_encode($events);
            
        } catch (Exception $e) {
            error_log("Maintenance calendar error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load calendar data']);
        }
    }
    
    /**
     * Generate preventive maintenance schedule
     */
    public function generateSchedule() {
        // Check permissions - only authorized users can generate schedules
        if (!$this->auth->hasRole(['System Admin', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=maintenance');
            exit;
        }
        
        $assetId = (int)($_POST['asset_id'] ?? 0);
        $intervalMonths = (int)($_POST['interval_months'] ?? 6);
        $scheduleMonths = (int)($_POST['schedule_months'] ?? 12);
        
        if (!$assetId) {
            $_SESSION['error'] = 'Invalid asset ID';
            header('Location: ?route=maintenance');
            exit;
        }
        
        try {
            $result = $this->maintenanceModel->generatePreventiveSchedule($assetId, $intervalMonths, $scheduleMonths);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
        } catch (Exception $e) {
            error_log("Maintenance schedule generation error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to generate maintenance schedule';
        }
        
        header('Location: ?route=maintenance');
        exit;
    }
}
?>
