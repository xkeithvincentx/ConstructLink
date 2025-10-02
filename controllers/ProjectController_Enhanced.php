<?php
/**
 * ConstructLink™ Enhanced Project Controller
 * Handles project management with role-based authorization, user assignments, and multi-inventory support
 */

class ProjectController {
    private $auth;
    private $projectModel;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->projectModel = new ProjectModel();
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Display project listing with role-based filtering
     */
    public function index() {
        // Check permissions - enforce role-based authorization
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole([
            'Finance Director', 'Asset Director', 'Procurement Officer', 
            'Project Manager', 'Warehouseman', 'Site Inventory Clerk'
        ])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        
        // Build filters
        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['manager_id'])) $filters['manager_id'] = $_GET['manager_id'];
        if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
        if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        
        try {
            // Get projects with pagination and role-based filtering
            $result = $this->projectModel->getProjectsWithFilters($filters, $page, $perPage);
            $projects = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];
            
            // Get project statistics
            $projectStats = $this->projectModel->getProjectStatistics();
            
            // Get available project managers for filter
            $projectManagers = $this->projectModel->getAvailableProjectManagers();
            
            $pageTitle = 'Project Management - ConstructLink™';
            $pageHeader = 'Project Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Projects', 'url' => '?route=projects']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/projects/index.php';
            
        } catch (Exception $e) {
            error_log("Project listing error: " . $e->getMessage());
            $error = 'Failed to load projects';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create project form
     */
    public function create() {
        // Check permissions - only System Admin can create projects
        if (!$this->auth->hasRole(['System Admin'])) {
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
                'code' => strtoupper(Validator::sanitize($_POST['code'] ?? '')),
                'location' => Validator::sanitize($_POST['location'] ?? ''),
                'description' => Validator::sanitize($_POST['description'] ?? ''),
                'budget' => !empty($_POST['budget']) ? (float)$_POST['budget'] : null,
                'project_manager_id' => !empty($_POST['project_manager_id']) ? (int)$_POST['project_manager_id'] : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'start_date' => $_POST['start_date'] ?? null,
                'end_date' => $_POST['end_date'] ?? null
            ];
            
            $result = $this->projectModel->createProject($formData);
            
            if ($result['success']) {
                header('Location: ?route=projects/view&id=' . $result['project']['id'] . '&message=project_created');
                exit;
            } else {
                if (isset($result['errors'])) {
                    $errors = $result['errors'];
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        // Get available project managers
        $projectManagers = $this->projectModel->getAvailableProjectManagers();
        
        // Pass auth instance to view
        $auth = $this->auth;
        
        $pageTitle = 'Create Project - ConstructLink™';
        $pageHeader = 'Create New Project';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Projects', 'url' => '?route=projects'],
            ['title' => 'Create Project', 'url' => '?route=projects/create']
        ];
        
        include APP_ROOT . '/views/projects/create.php';
    }
    
    /**
     * Display edit project form
     */
    public function edit() {
        // Check permissions - only System Admin can edit projects
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $projectId = $_GET['id'] ?? 0;
        
        if (!$projectId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $project = $this->projectModel->getProjectWithDetails($projectId);
            
            if (!$project) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $formData = $project;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $formData = [
                    'name' => Validator::sanitize($_POST['name'] ?? ''),
                    'code' => strtoupper(Validator::sanitize($_POST['code'] ?? '')),
                    'location' => Validator::sanitize($_POST['location'] ?? ''),
                    'description' => Validator::sanitize($_POST['description'] ?? ''),
                    'budget' => !empty($_POST['budget']) ? (float)$_POST['budget'] : null,
                    'project_manager_id' => !empty($_POST['project_manager_id']) ? (int)$_POST['project_manager_id'] : null,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    'start_date' => $_POST['start_date'] ?? null,
                    'end_date' => $_POST['end_date'] ?? null
                ];
                
                $result = $this->projectModel->updateProject($projectId, $formData);
                
                if ($result['success']) {
                    header('Location: ?route=projects/view&id=' . $projectId . '&message=project_updated');
                    exit;
                } else {
                    if (isset($result['errors'])) {
                        $errors = $result['errors'];
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            // Get available project managers
            $projectManagers = $this->projectModel->getAvailableProjectManagers();
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            $pageTitle = 'Edit Project - ConstructLink™';
            $pageHeader = 'Edit Project: ' . htmlspecialchars($project['name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Projects', 'url' => '?route=projects'],
                ['title' => 'Edit Project', 'url' => '?route=projects/edit&id=' . $projectId]
            ];
            
            include APP_ROOT . '/views/projects/edit.php';
            
        } catch (Exception $e) {
            error_log("Project edit error: " . $e->getMessage());
            $error = 'Failed to load project details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * View project details with comprehensive information
     */
    public function view() {
        $projectId = $_GET['id'] ?? 0;
        
        if (!$projectId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $project = $this->projectModel->getProjectWithDetails($projectId);
            
            if (!$project) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check role-based access for project viewing
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            
            // Project Managers can only view their assigned projects (unless System Admin)
            if ($userRole === 'Project Manager' && 
                !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director']) &&
                $project['project_manager_id'] != $currentUser['id']) {
                
                // Check if user is assigned to this project
                $projectUsers = $this->projectModel->getProjectUsers($projectId);
                $userAssigned = false;
                foreach ($projectUsers as $assignedUser) {
                    if ($assignedUser['id'] == $currentUser['id']) {
                        $userAssigned = true;
                        break;
                    }
                }
                
                if (!$userAssigned) {
                    http_response_code(403);
                    include APP_ROOT . '/views/errors/403.php';
                    return;
                }
            }
            
            // Get project assets with role-based filtering
            $assetModel = new AssetModel();
            $projectAssets = $assetModel->getAssetsByProject($projectId);
            
            // Get project assigned users
            $projectUsers = $this->projectModel->getProjectUsers($projectId);
            
            // Get project activity summary
            $projectActivity = $this->projectModel->getProjectActivity($projectId);
            
            // Get recent withdrawals for this project
            $withdrawalModel = new WithdrawalModel();
            $recentWithdrawals = $withdrawalModel->getWithdrawalsWithFilters(['project_id' => $projectId], 1, 5);
            
            // Get recent procurement orders for this project
            $procurementModel = new ProcurementOrderModel();
            $recentProcurements = $procurementModel->getProcurementOrdersWithFilters(['project_id' => $projectId], 1, 5);
            
            // Hide financial data from unauthorized users
            $showFinancialData = $this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']);
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            $pageTitle = 'Project Details - ConstructLink™';
            $pageHeader = 'Project: ' . htmlspecialchars($project['name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Projects', 'url' => '?route=projects'],
                ['title' => 'View Details', 'url' => '?route=projects/view&id=' . $projectId]
            ];
            
            include APP_ROOT . '/views/projects/view.php';
            
        } catch (Exception $e) {
            error_log("Project view error: " . $e->getMessage());
            $error = 'Failed to load project details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Delete project
     */
    public function delete() {
        // Check permissions - only System Admin can delete projects
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $projectId = $_GET['id'] ?? 0;
        
        if (!$projectId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $result = $this->projectModel->deleteProject($projectId);
            
            if ($result['success']) {
                header('Location: ?route=projects&message=project_deleted');
                exit;
            } else {
                header('Location: ?route=projects&error=' . urlencode($result['message']));
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Project deletion error: " . $e->getMessage());
            header('Location: ?route=projects&error=Failed to delete project');
            exit;
        }
    }
    
    /**
     * Toggle project status
     */
    public function toggleStatus() {
        // Check permissions - only System Admin can toggle project status
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        $projectId = $_POST['project_id'] ?? 0;
        
        if (!$projectId) {
            echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
            return;
        }
        
        try {
            $result = $this->projectModel->toggleProjectStatus($projectId);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Project status toggle error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update project status']);
        }
    }
    
    /**
     * Assign user to project
     */
    public function assignUser() {
        // Check permissions - System Admin and Project Managers can assign users
        if (!$this->auth->hasRole(['System Admin', 'Project Manager'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $projectId = $_POST['project_id'] ?? 0;
        $userId = $_POST['user_id'] ?? 0;
        $notes = Validator::sanitize($_POST['notes'] ?? '');
        
        if (!$projectId || !$userId) {
            echo json_encode(['success' => false, 'message' => 'Project ID and User ID are required']);
            return;
        }
        
        try {
            // Additional check for Project Managers - they can only assign to their own projects
            $currentUser = $this->auth->getCurrentUser();
            if ($currentUser['role_name'] === 'Project Manager') {
                $project = $this->projectModel->find($projectId);
                if (!$project || $project['project_manager_id'] != $currentUser['id']) {
                    echo json_encode(['success' => false, 'message' => 'You can only assign users to your own projects']);
                    return;
                }
            }
            
            $result = $this->projectModel->assignUserToProject($userId, $projectId, $currentUser['id'], $notes);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("User assignment error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to assign user to project']);
        }
    }
    
    /**
     * Get project assets via AJAX with role-based filtering
     */
    public function getAssets() {
        $projectId = $_GET['project_id'] ?? 0;
        
        if (!$projectId) {
            echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
            return;
        }
        
        try {
            // Check if user has access to this project
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            
            if ($userRole === 'Project Manager' && 
                !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
                
                $project = $this->projectModel->find($projectId);
                if (!$project || $project['project_manager_id'] != $currentUser['id']) {
                    // Check if user is assigned to this project
                    $projectUsers = $this->projectModel->getProjectUsers($projectId);
                    $userAssigned = false;
                    foreach ($projectUsers as $assignedUser) {
                        if ($assignedUser['id'] == $currentUser['id']) {
                            $userAssigned = true;
                            break;
                        }
                    }
                    
                    if (!$userAssigned) {
                        echo json_encode(['success' => false, 'message' => 'Access denied']);
                        return;
                    }
                }
            }
            
            $assetModel = new AssetModel();
            $assets = $assetModel->getAssetsByProject($projectId);
            
            // Hide cost data from unauthorized users
            $showFinancialData = $this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']);
            
            if (!$showFinancialData) {
                foreach ($assets as &$asset) {
                    unset($asset['acquisition_cost']);
                    unset($asset['unit_cost']);
                }
            }
            
            echo json_encode([
                'success' => true,
                'assets' => $assets
            ]);
            
        } catch (Exception $e) {
            error_log("Get project assets error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load project assets']);
        }
    }
    
    /**
     * Get project statistics via AJAX
     */
    public function getStats() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $projectId = $_GET['project_id'] ?? null;
        
        try {
            $stats = $this->projectModel->getProjectStatistics($projectId);
            
            // Hide financial data from unauthorized users
            $showFinancialData = $this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']);
            
            if (!$showFinancialData) {
                unset($stats['total_asset_value']);
                unset($stats['total_procurement_value']);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            error_log("Get project stats error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load statistics']);
        }
    }
    
    /**
     * Export projects to Excel
     */
    public function export() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Build filters from GET parameters
            $filters = [];
            if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
            if (!empty($_GET['manager_id'])) $filters['manager_id'] = $_GET['manager_id'];
            if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
            if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
            if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
            
            // Get all projects (no pagination for export)
            $result = $this->projectModel->getProjectsWithFilters($filters, 1, 10000);
            $projects = $result['data'] ?? [];
            
            // Set headers for Excel download
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="projects_' . date('Y-m-d') . '.xls"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output Excel content
            echo '<table border="1">';
            echo '<tr>';
            echo '<th>ID</th>';
            echo '<th>Project Name</th>';
            echo '<th>Code</th>';
            echo '<th>Location</th>';
            echo '<th>Project Manager</th>';
            echo '<th>Status</th>';
            echo '<th>Assets Count</th>';
            echo '<th>Total Value</th>';
            echo '<th>Start Date</th>';
            echo '<th>End Date</th>';
            echo '<th>Created Date</th>';
            echo '</tr>';
            
            foreach ($projects as $project) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($project['id']) . '</td>';
                echo '<td>' . htmlspecialchars($project['name']) . '</td>';
                echo '<td>' . htmlspecialchars($project['code']) . '</td>';
                echo '<td>' . htmlspecialchars($project['location']) . '</td>';
                echo '<td>' . htmlspecialchars($project['project_manager_name'] ?? 'Not Assigned') . '</td>';
                echo '<td>' . ($project['is_active'] ? 'Active' : 'Inactive') . '</td>';
                echo '<td>' . ($project['assets_count'] ?? 0) . '</td>';
                echo '<td>' . number_format($project['total_value'] ?? 0, 2) . '</td>';
                echo '<td>' . ($project['start_date'] ? date('Y-m-d', strtotime($project['start_date'])) : '') . '</td>';
                echo '<td>' . ($project['end_date'] ? date('Y-m-d', strtotime($project['end_date'])) : '') . '</td>';
                echo '<td>' . date('Y-m-d H:i', strtotime($project['created_at'])) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            exit;
            
        } catch (Exception $e) {
            error_log("Project export error: " . $e->getMessage());
            header('Location: ?route=projects&error=export_failed');
            exit;
        }
    }
    
    /**
     * Get available users for project assignment
     */
    public function getAvailableUsers() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Project Manager'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $userModel = new UserModel();
            $users = $userModel->getActiveUsers();
            
            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
            
        } catch (Exception $e) {
            error_log("Get available users error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load users']);
        }
    }
}
?>
