<?php
/**
 * ConstructLink™ Incident Controller
 * Handles incident reporting and management
 */

class IncidentController {
    private $auth;
    private $incidentModel;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->incidentModel = new IncidentModel();
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Display incident listing
     */
    public function index() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])) {
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
        if (!empty($_GET['severity'])) $filters['severity'] = $_GET['severity'];
        if (!empty($_GET['asset_id'])) $filters['asset_id'] = $_GET['asset_id'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
        if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
        
        try {
            // Get incidents with pagination
            $result = $this->incidentModel->getIncidentsWithFilters($filters, $page, $perPage);
            $incidents = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];
            
            // Get incident statistics
            $incidentStats = [
                'under_investigation' => 0,
                'verified' => 0,
                'resolved' => 0,
                'closed' => 0,
                'total_incidents' => 0
            ];
            
            try {
                $statsResult = $this->incidentModel->getIncidentStats();
                if ($statsResult) {
                    $incidentStats = array_merge($incidentStats, $statsResult);
                }
            } catch (Exception $e) {
                error_log("Incident stats error: " . $e->getMessage());
            }
            
            // Get critical incidents
            $criticalIncidents = [];
            try {
                $criticalIncidents = $this->incidentModel->getCriticalIncidents();
            } catch (Exception $e) {
                error_log("Critical incidents error: " . $e->getMessage());
            }
            
            $pageTitle = 'Asset Incidents - ConstructLink™';
            $pageHeader = 'Asset Incidents';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Incidents', 'url' => '?route=incidents']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/incidents/index.php';
            
        } catch (Exception $e) {
            error_log("Incident listing error: " . $e->getMessage());
            $error = 'Failed to load incident reports';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create incident form
     */
    public function create() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])) {
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
                'type' => $_POST['type'] ?? 'other',
                'description' => Validator::sanitize($_POST['description'] ?? ''),
                'date_reported' => $_POST['date_reported'] ?? date('Y-m-d'),
                'reported_by' => $_SESSION['user_id'],
                'severity' => $_POST['severity'] ?? 'medium',
                'location' => Validator::sanitize($_POST['location'] ?? ''),
                'witnesses' => Validator::sanitize($_POST['witnesses'] ?? '')
            ];
            
            $result = $this->incidentModel->createIncident($formData);
            
            if ($result['success']) {
                header('Location: ?route=incidents/view&id=' . $result['incident']['id'] . '&message=incident_reported');
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
            // Get form options - incidents can be reported for any asset
            $assets = $this->getAllAssetsForIncidentReporting();
            
            // Pre-select asset if provided
            if (!empty($_GET['asset_id'])) {
                $formData['asset_id'] = (int)$_GET['asset_id'];
            }
            
            $pageTitle = 'Report Incident - ConstructLink™';
            $pageHeader = 'Report Incident';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Incidents', 'url' => '?route=incidents'],
                ['title' => 'Report Incident', 'url' => '?route=incidents/create']
            ];
            
            include APP_ROOT . '/views/incidents/create.php';
            
        } catch (Exception $e) {
            error_log("Incident create form error: " . $e->getMessage());
            $error = 'Failed to load form data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    

    
    /**
     * View incident details
     */
    public function view() {
        $incidentId = $_GET['id'] ?? 0;
        
        if (!$incidentId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $incident = $this->incidentModel->getIncidentWithDetails($incidentId);
            
            if (!$incident) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Get related incidents for this asset
            $relatedIncidents = $this->incidentModel->getAssetIncidentHistory($incident['asset_id']);
            
            $pageTitle = 'Incident Details - ConstructLink™';
            $pageHeader = 'Incident #' . $incident['id'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Incidents', 'url' => '?route=incidents'],
                ['title' => 'View Details', 'url' => '?route=incidents/view&id=' . $incidentId]
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/incidents/view.php';
            
        } catch (Exception $e) {
            error_log("Incident view error: " . $e->getMessage());
            $error = 'Failed to load incident details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Verify incident (Verifier step)
     */
    public function investigate() {
        $incidentId = $_GET['id'] ?? 0;
        if (!$incidentId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $incident = $this->incidentModel->getIncidentWithDetails($incidentId);
            if (!$incident) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            
            // Check RBAC permissions
            if (!canVerifyIncident($incident, $currentUser)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            
            // Check status
            if ($incident['status'] !== 'Pending Verification') {
                $_SESSION['error'] = 'Incident is not in pending verification status';
                header('Location: ?route=incidents/view&id=' . $incidentId);
                exit;
            }
            
            $errors = [];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $verificationNotes = Validator::sanitize($_POST['verification_notes'] ?? '');
                
                if (empty($verificationNotes)) {
                    $errors[] = 'Verification notes are required';
                } else {
                    $result = $this->incidentModel->verifyIncident($incidentId, $currentUser['id'], $verificationNotes);
                    
                    if ($result['success']) {
                        header('Location: ?route=incidents/view&id=' . $incidentId . '&message=incident_verified');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Verify Incident - ConstructLink™';
            $pageHeader = 'Verify Incident #' . $incident['id'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Incidents', 'url' => '?route=incidents'],
                ['title' => 'Verify Incident', 'url' => '?route=incidents/investigate&id=' . $incidentId]
            ];
            
            include APP_ROOT . '/views/incidents/investigate.php';
            
        } catch (Exception $e) {
            error_log("Incident verification error: " . $e->getMessage());
            $error = 'Failed to process incident verification';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Authorize or Resolve incident (Authorizer step)
     */
    public function resolve() {
        $incidentId = $_GET['id'] ?? 0;
        if (!$incidentId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $incident = $this->incidentModel->getIncidentWithDetails($incidentId);
            if (!$incident) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            
            // Check RBAC permissions
            if (!canAuthorizeIncident($incident, $currentUser)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            
            // Check status - can authorize or resolve
            if (!in_array($incident['status'], ['Pending Authorization', 'Authorized'])) {
                $_SESSION['error'] = 'Incident is not in pending authorization or authorized status';
                header('Location: ?route=incidents/view&id=' . $incidentId);
                exit;
            }
            
            $errors = [];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $action = $_POST['action'] ?? 'authorize';
                $notes = Validator::sanitize($_POST['notes'] ?? '');
                
                if (empty($notes)) {
                    $errors[] = 'Notes are required';
                } else {
                    if ($action === 'authorize' && $incident['status'] === 'Pending Authorization') {
                        $result = $this->incidentModel->authorizeIncident($incidentId, $currentUser['id'], $notes);
                        $message = 'incident_authorized';
                    } elseif ($action === 'resolve' && in_array($incident['status'], ['Authorized', 'Pending Authorization'])) {
                        $result = $this->incidentModel->resolveIncident($incidentId, $currentUser['id'], $notes, $_POST['resolution_details'] ?? '');
                        $message = 'incident_resolved';
                    } else {
                        $errors[] = 'Invalid action for current status';
                        $result = ['success' => false];
                    }
                    
                    if ($result['success']) {
                        header('Location: ?route=incidents/view&id=' . $incidentId . '&message=' . $message);
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Resolve Incident - ConstructLink™';
            $pageHeader = 'Resolve Incident #' . $incident['id'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Incidents', 'url' => '?route=incidents'],
                ['title' => 'Resolve', 'url' => '?route=incidents/resolve&id=' . $incidentId]
            ];
            
            include APP_ROOT . '/views/incidents/resolve.php';
            
        } catch (Exception $e) {
            error_log("Incident resolve error: " . $e->getMessage());
            $error = 'Failed to process incident resolution';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Close incident (final step)
     */
    public function close() {
        $incidentId = $_GET['id'] ?? 0;
        
        if (!$incidentId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $incident = $this->incidentModel->getIncidentWithDetails($incidentId);
            
            if (!$incident) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            
            // Check RBAC permissions
            if (!canAuthorizeIncident($incident, $currentUser)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            
            if ($incident['status'] !== 'Resolved') {
                $_SESSION['error'] = 'Incident must be resolved before closing';
                header('Location: ?route=incidents/view&id=' . $incidentId);
                exit;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $closureNotes = Validator::sanitize($_POST['closure_notes'] ?? '');
                $result = $this->incidentModel->closeIncident($incidentId, $currentUser['id'], $closureNotes);
                
                if ($result['success']) {
                    header('Location: ?route=incidents/view&id=' . $incidentId . '&message=incident_closed');
                    exit;
                } else {
                    $_SESSION['error'] = $result['message'];
                    header('Location: ?route=incidents/view&id=' . $incidentId);
                    exit;
                }
            }
            
            $pageTitle = 'Close Incident - ConstructLink™';
            $pageHeader = 'Close Incident #' . $incident['id'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Incidents', 'url' => '?route=incidents'],
                ['title' => 'Close Incident', 'url' => '?route=incidents/close&id=' . $incidentId]
            ];
            
            include APP_ROOT . '/views/incidents/close.php';
            
        } catch (Exception $e) {
            error_log("Incident closure error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to close incident';
            header('Location: ?route=incidents/view&id=' . $incidentId);
            exit;
        }
    }
    
    /**
     * Cancel incident (any stage before Resolved)
     */
    public function cancel() {
        $incidentId = $_GET['id'] ?? 0;
        
        if (!$incidentId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $incident = $this->incidentModel->getIncidentWithDetails($incidentId);
            
            if (!$incident) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            
            // Check RBAC permissions - either verifier or authorizer can cancel
            if (!canVerifyIncident($incident, $currentUser) && !canAuthorizeIncident($incident, $currentUser)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            
            if (!in_array($incident['status'], ['Pending Verification', 'Pending Authorization', 'Authorized'])) {
                $_SESSION['error'] = 'Cannot cancel at this stage';
                header('Location: ?route=incidents/view&id=' . $incidentId);
                exit;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $reason = Validator::sanitize($_POST['reason'] ?? '');
                $result = $this->incidentModel->cancelIncident($incidentId, $currentUser['id'], $reason);
                
                if ($result['success']) {
                    header('Location: ?route=incidents/view&id=' . $incidentId . '&message=incident_canceled');
                    exit;
                } else {
                    $_SESSION['error'] = $result['message'];
                    header('Location: ?route=incidents/view&id=' . $incidentId);
                    exit;
                }
            }
            
            $pageTitle = 'Cancel Incident - ConstructLink™';
            $pageHeader = 'Cancel Incident #' . $incident['id'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Incidents', 'url' => '?route=incidents'],
                ['title' => 'Cancel Incident', 'url' => '?route=incidents/cancel&id=' . $incidentId]
            ];
            
            include APP_ROOT . '/views/incidents/cancel.php';
            
        } catch (Exception $e) {
            error_log("Incident cancellation error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to cancel incident';
            header('Location: ?route=incidents/view&id=' . $incidentId);
            exit;
        }
    }

    /**
     * Delete incident
     */
    public function delete() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=incidents');
            exit;
        }
        
        $incidentId = $_POST['incident_id'] ?? 0;
        
        if (!$incidentId) {
            $_SESSION['error'] = 'Invalid incident ID';
            header('Location: ?route=incidents');
            exit;
        }
        
        try {
            $result = $this->incidentModel->delete($incidentId);
            
            if ($result) {
                $_SESSION['success'] = 'Incident deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete incident';
            }
            
        } catch (Exception $e) {
            error_log("Incident deletion error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete incident';
        }
        
        header('Location: ?route=incidents');
        exit;
    }
    
    /**
     * Export incidents
     */
    public function export() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Build filters from GET parameters
            $filters = [];
            if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
            if (!empty($_GET['type'])) $filters['type'] = $_GET['type'];
            if (!empty($_GET['severity'])) $filters['severity'] = $_GET['severity'];
            if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
            if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
            
            // Get all incidents (no pagination for export)
            $result = $this->incidentModel->getIncidentsWithFilters($filters, 1, 10000);
            $incidents = $result['data'] ?? [];
            
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="incidents-export-' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, [
                'Incident ID', 'Asset Reference', 'Asset Name', 'Type', 'Severity', 
                'Description', 'Location', 'Witnesses', 'Status', 'Date Reported',
                'Reported By', 'Resolution Notes', 'Resolved By', 'Resolution Date',
                'Project', 'Category'
            ]);
            
            // CSV data
            foreach ($incidents as $incident) {
                fputcsv($output, [
                    $incident['id'],
                    $incident['asset_ref'],
                    $incident['asset_name'],
                    ucfirst($incident['type']),
                    ucfirst($incident['severity']),
                    $incident['description'],
                    $incident['location'],
                    $incident['witnesses'],
                    $incident['status'],
                    $incident['date_reported'],
                    $incident['reported_by_name'],
                    $incident['resolution_notes'],
                    $incident['resolved_by_name'],
                    $incident['resolution_date'],
                    $incident['project_name'],
                    $incident['category_name']
                ]);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            error_log("Incident export error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to export incidents';
            header('Location: ?route=incidents');
            exit;
        }
    }
    

    /**
     * Get all assets for incident reporting (incidents can happen to any asset)
     */
    private function getAllAssetsForIncidentReporting() {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "
                SELECT a.*, c.name as category_name, p.name as project_name,
                       CASE 
                           WHEN bt.asset_id IS NOT NULL THEN CONCAT(a.status, ' (Borrowed by ', bt.borrower_name, ')')
                           WHEN w.asset_id IS NOT NULL THEN CONCAT(a.status, ' (Withdrawn by ', w.receiver_name, ')')
                           WHEN t.asset_id IS NOT NULL THEN CONCAT(a.status, ' (In Transfer)')
                           ELSE a.status
                       END as detailed_status
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN borrowed_tools bt ON a.id = bt.asset_id AND bt.status = 'borrowed'
                LEFT JOIN withdrawals w ON a.id = w.asset_id AND w.status IN ('pending', 'released')
                LEFT JOIN transfers t ON a.id = t.asset_id AND t.status IN ('pending', 'approved')
                WHERE a.status != 'retired'
                  AND p.is_active = 1
                ORDER BY a.name ASC
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get assets for incident reporting error: " . $e->getMessage());
            return [];
        }
    }
}
?>
