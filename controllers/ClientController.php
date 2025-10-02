<?php
/**
 * ConstructLink™ Client Controller
 * Handles client management operations
 */

class ClientController {
    private $auth;
    private $clientModel;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->clientModel = new ClientModel();
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Display client listing
     */
    public function index() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer', 'Project Manager'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        
        // Build filters
        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        
        try {
            // Get clients with pagination
            $result = $this->clientModel->getClientsWithFilters($filters, $page, $perPage);
            $clients = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];
            
            // Get client statistics
            $clientStats = $this->clientModel->getClientStatistics();
            
            $pageTitle = 'Client Management - ConstructLink™';
            $pageHeader = 'Client Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Clients', 'url' => '?route=clients']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/clients/index.php';
            
        } catch (Exception $e) {
            error_log("Client listing error: " . $e->getMessage());
            $error = 'Failed to load clients';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create client form
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
                'contact_info' => Validator::sanitize($_POST['contact_info'] ?? ''),
                'address' => Validator::sanitize($_POST['address'] ?? ''),
                'phone' => Validator::sanitize($_POST['phone'] ?? ''),
                'email' => Validator::sanitize($_POST['email'] ?? ''),
                'contact_person' => Validator::sanitize($_POST['contact_person'] ?? ''),
                'company_type' => Validator::sanitize($_POST['company_type'] ?? ''),
                'tax_id' => Validator::sanitize($_POST['tax_id'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $result = $this->clientModel->createClient($formData);
            
            if ($result['success']) {
                header('Location: ?route=clients/view&id=' . $result['client']['id'] . '&message=client_created');
                exit;
            } else {
                if (isset($result['errors'])) {
                    $errors = $result['errors'];
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        $pageTitle = 'Create Client - ConstructLink™';
        $pageHeader = 'Create New Client';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Clients', 'url' => '?route=clients'],
            ['title' => 'Create Client', 'url' => '?route=clients/create']
        ];
        
        include APP_ROOT . '/views/clients/create.php';
    }
    
    /**
     * Display edit client form
     */
    public function edit() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $clientId = $_GET['id'] ?? 0;
        
        if (!$clientId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $client = $this->clientModel->getClientWithDetails($clientId);
            
            if (!$client) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $formData = $client;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $formData = [
                    'name' => Validator::sanitize($_POST['name'] ?? ''),
                    'contact_info' => Validator::sanitize($_POST['contact_info'] ?? ''),
                    'address' => Validator::sanitize($_POST['address'] ?? ''),
                    'phone' => Validator::sanitize($_POST['phone'] ?? ''),
                    'email' => Validator::sanitize($_POST['email'] ?? ''),
                    'contact_person' => Validator::sanitize($_POST['contact_person'] ?? ''),
                    'company_type' => Validator::sanitize($_POST['company_type'] ?? ''),
                    'tax_id' => Validator::sanitize($_POST['tax_id'] ?? ''),
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];
                
                $result = $this->clientModel->updateClient($clientId, $formData);
                
                if ($result['success']) {
                    header('Location: ?route=clients/view&id=' . $clientId . '&message=client_updated');
                    exit;
                } else {
                    if (isset($result['errors'])) {
                        $errors = $result['errors'];
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Edit Client - ConstructLink™';
            $pageHeader = 'Edit Client: ' . htmlspecialchars($client['name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Clients', 'url' => '?route=clients'],
                ['title' => 'Edit Client', 'url' => '?route=clients/edit&id=' . $clientId]
            ];
            
            include APP_ROOT . '/views/clients/edit.php';
            
        } catch (Exception $e) {
            error_log("Client edit error: " . $e->getMessage());
            $error = 'Failed to load client details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * View client details
     */
    public function view() {
        $clientId = $_GET['id'] ?? 0;
        
        if (!$clientId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $client = $this->clientModel->getClientWithDetails($clientId);
            
            if (!$client) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Get client-supplied assets
            $assetModel = new AssetModel();
            $clientAssets = $assetModel->getAssetsByClient($clientId);
            
            // Get client statistics
            $clientStats = $this->clientModel->getClientStatistics($clientId);
            
            $pageTitle = 'Client Details - ConstructLink™';
            $pageHeader = 'Client: ' . htmlspecialchars($client['name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Clients', 'url' => '?route=clients'],
                ['title' => 'View Details', 'url' => '?route=clients/view&id=' . $clientId]
            ];
            
            include APP_ROOT . '/views/clients/view.php';
            
        } catch (Exception $e) {
            error_log("Client view error: " . $e->getMessage());
            $error = 'Failed to load client details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Delete client
     */
    public function delete() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $clientId = $_GET['id'] ?? 0;
        
        if (!$clientId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $result = $this->clientModel->deleteClient($clientId);
            
            if ($result['success']) {
                header('Location: ?route=clients&message=client_deleted');
                exit;
            } else {
                header('Location: ?route=clients&error=' . urlencode($result['message']));
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Client deletion error: " . $e->getMessage());
            header('Location: ?route=clients&error=Failed to delete client');
            exit;
        }
    }
    
    /**
     * Toggle client status
     */
    public function toggleStatus() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Procurement Officer'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        $clientId = $_POST['client_id'] ?? 0;
        
        if (!$clientId) {
            echo json_encode(['success' => false, 'message' => 'Invalid client ID']);
            return;
        }
        
        try {
            $result = $this->clientModel->toggleClientStatus($clientId);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Client status toggle error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update client status']);
        }
    }
    
    /**
     * Get client assets via AJAX
     */
    public function getAssets() {
        $clientId = $_GET['client_id'] ?? 0;
        
        if (!$clientId) {
            echo json_encode(['success' => false, 'message' => 'Invalid client ID']);
            return;
        }
        
        try {
            $assetModel = new AssetModel();
            $assets = $assetModel->getAssetsByClient($clientId);
            
            echo json_encode([
                'success' => true,
                'assets' => $assets
            ]);
            
        } catch (Exception $e) {
            error_log("Get client assets error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load client assets']);
        }
    }
    
    /**
     * Get clients for dropdown via AJAX
     */
    public function getForDropdown() {
        try {
            $clients = $this->clientModel->getActiveClients();
            
            echo json_encode([
                'success' => true,
                'clients' => $clients
            ]);
            
        } catch (Exception $e) {
            error_log("Get clients dropdown error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load clients']);
        }
    }
}
?>
