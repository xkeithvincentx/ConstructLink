<?php
/**
 * ConstructLink™ Dashboard Controller
 * Main dashboard with statistics and overview
 */

class DashboardController {
    private $auth;
    private $dashboardModel;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        
        // Try to load DashboardModel, fallback to basic functionality if not available
        if (class_exists('DashboardModel')) {
            $this->dashboardModel = new DashboardModel();
        } else {
            // Load the DashboardModel manually if autoloader doesn't find it
            $dashboardModelPath = APP_ROOT . '/models/DashboardModel.php';
            if (file_exists($dashboardModelPath)) {
                require_once $dashboardModelPath;
                $this->dashboardModel = new DashboardModel();
            } else {
                $this->dashboardModel = null;
            }
        }
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Display main dashboard
     */
    public function index() {
        try {
            $user = $this->auth->getCurrentUser();
            $userRole = $user['role_name'];
            
            // Get dashboard data - use new model if available, fallback to basic stats
            if ($this->dashboardModel) {
                $dashboardData = $this->dashboardModel->getDashboardStats($userRole, $user['id']);
                $dashboardData = $this->flattenDashboardData($dashboardData);
            } else {
                // Fallback to basic dashboard data
                $dashboardData = $this->getBasicDashboardData();
            }
            
            // Load branding helper
            require_once APP_ROOT . '/helpers/BrandingHelper.php';

            $pageTitle = BrandingHelper::getPageTitle('Dashboard');
            $pageHeader = 'Dashboard';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard']
            ];
            
            include APP_ROOT . '/views/dashboard/index.php';
            
        } catch (Exception $e) {
            error_log("Dashboard index error: " . $e->getMessage());
            $error = 'Failed to load dashboard';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Get basic dashboard data (fallback when DashboardModel is not available)
     */
    private function getBasicDashboardData() {
        $data = [];
        
        try {
            $db = Database::getInstance()->getConnection();
            
            // Basic asset statistics
            $stmt = $db->query("SELECT COUNT(*) as total FROM assets");
            $data['total_assets'] = $stmt->fetch()['total'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as count FROM assets WHERE status = 'available'");
            $data['available_assets'] = $stmt->fetch()['count'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as count FROM assets WHERE status = 'in_use'");
            $data['in_use_assets'] = $stmt->fetch()['count'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as count FROM withdrawals WHERE status = 'pending'");
            $data['pending_withdrawals'] = $stmt->fetch()['count'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as count FROM projects WHERE is_active = 1");
            $data['active_projects'] = $stmt->fetch()['count'] ?? 0;
            
            // Additional basic stats
            $data['borrowed_assets'] = 0;
            $data['maintenance_assets'] = 0;
            $data['retired_assets'] = 0;
            $data['total_asset_value'] = 0;
            $data['overdue_withdrawals'] = 0;
            $data['recent_activities'] = [];
            $data['role_specific'] = [];
            
        } catch (Exception $e) {
            error_log("Basic dashboard data error: " . $e->getMessage());
            $data = [
                'total_assets' => 0,
                'available_assets' => 0,
                'in_use_assets' => 0,
                'pending_withdrawals' => 0,
                'active_projects' => 0,
                'borrowed_assets' => 0,
                'maintenance_assets' => 0,
                'retired_assets' => 0,
                'total_asset_value' => 0,
                'overdue_withdrawals' => 0,
                'recent_activities' => [],
                'role_specific' => []
            ];
        }
        
        return $data;
    }
    
    /**
     * Flatten dashboard data for view compatibility
     */
    private function flattenDashboardData($data) {
        $flattened = [];
        
        // Asset statistics
        if (isset($data['assets'])) {
            $flattened['total_assets'] = $data['assets']['total_assets'] ?? 0;
            $flattened['available_assets'] = $data['assets']['available_assets'] ?? 0;
            $flattened['in_use_assets'] = $data['assets']['in_use_assets'] ?? 0;
            $flattened['borrowed_assets'] = $data['assets']['borrowed_assets'] ?? 0;
            $flattened['maintenance_assets'] = $data['assets']['maintenance_assets'] ?? 0;
            $flattened['retired_assets'] = $data['assets']['retired_assets'] ?? 0;
            $flattened['total_asset_value'] = $data['assets']['total_value'] ?? 0;
        }
        
        // Project statistics
        if (isset($data['projects'])) {
            $flattened['total_projects'] = $data['projects']['total_projects'] ?? 0;
            $flattened['active_projects'] = $data['projects']['active_projects'] ?? 0;
            $flattened['inactive_projects'] = $data['projects']['inactive_projects'] ?? 0;
        }
        
        // Withdrawal statistics
        if (isset($data['withdrawals'])) {
            $flattened['total_withdrawals'] = $data['withdrawals']['total_withdrawals'] ?? 0;
            $flattened['pending_withdrawals'] = $data['withdrawals']['pending_withdrawals'] ?? 0;
            $flattened['released_withdrawals'] = $data['withdrawals']['released_withdrawals'] ?? 0;
            $flattened['returned_withdrawals'] = $data['withdrawals']['returned_withdrawals'] ?? 0;
            $flattened['overdue_withdrawals'] = $data['withdrawals']['overdue_withdrawals'] ?? 0;
        }
        
        // Maintenance statistics
        if (isset($data['maintenance'])) {
            $flattened['total_maintenance'] = $data['maintenance']['total_maintenance'] ?? 0;
            $flattened['scheduled_maintenance'] = $data['maintenance']['scheduled_maintenance'] ?? 0;
            $flattened['in_progress_maintenance'] = $data['maintenance']['in_progress_maintenance'] ?? 0;
            $flattened['completed_maintenance'] = $data['maintenance']['completed_maintenance'] ?? 0;
            $flattened['overdue_maintenance'] = $data['maintenance']['overdue_maintenance'] ?? 0;
        }
        
        // Incident statistics
        if (isset($data['incidents'])) {
            $flattened['total_incidents'] = $data['incidents']['total_incidents'] ?? 0;
            $flattened['under_investigation'] = $data['incidents']['under_investigation'] ?? 0;
            $flattened['verified_incidents'] = $data['incidents']['verified_incidents'] ?? 0;
            $flattened['resolved_incidents'] = $data['incidents']['resolved_incidents'] ?? 0;
            $flattened['lost_assets'] = $data['incidents']['lost_assets'] ?? 0;
            $flattened['damaged_assets'] = $data['incidents']['damaged_assets'] ?? 0;
            $flattened['stolen_assets'] = $data['incidents']['stolen_assets'] ?? 0;
        }
        
        // Recent activities
        $flattened['recent_activities'] = $data['recent_activities'] ?? [];
        
        // Role-specific data
        $flattened['role_specific'] = [];
        foreach (['admin', 'finance', 'asset_director', 'procurement', 'warehouse', 'project_manager', 'site_clerk'] as $role) {
            if (isset($data[$role])) {
                $flattened['role_specific'][$role] = $data[$role];
            }
        }
        
        // Budget utilization (for Finance Director)
        if (isset($data['budget_utilization'])) {
            $flattened['budget_utilization'] = $data['budget_utilization'];
        }
        
        // Pass through user role for view customization
        $user = $this->auth->getCurrentUser();
        $flattened['user_role'] = $user['role_name'] ?? 'Guest';
        $flattened['user_id'] = $user['id'] ?? null;
        
        return $flattened;
    }
    
    /**
     * Get dashboard statistics (AJAX endpoint)
     */
    public function getStats() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            $userRole = $user['role_name'];
            
            // Get fresh statistics - use new model if available, fallback to basic stats
            if ($this->dashboardModel) {
                $stats = $this->dashboardModel->getDashboardStats($userRole, $user['id']);
            } else {
                $stats = $this->getBasicDashboardData();
            }
            
            echo json_encode([
                'success' => true,
                'data' => $stats,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("Dashboard stats API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load statistics',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Get quick search results (AJAX endpoint)
     */
    public function quickSearch() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 2) {
            echo json_encode(['success' => true, 'results' => []]);
            return;
        }
        
        try {
            $assetModel = new AssetModel();
            $results = $assetModel->searchAssets($query, [], 10);
            
            $formattedResults = [];
            foreach ($results as $asset) {
                $formattedResults[] = [
                    'id' => $asset['id'],
                    'ref' => $asset['ref'],
                    'name' => $asset['name'],
                    'category' => $asset['category_name'] ?? 'N/A',
                    'project' => $asset['project_name'] ?? 'N/A',
                    'status' => $asset['status'],
                    'url' => '?route=assets/view&id=' . $asset['id']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'results' => $formattedResults,
                'query' => $query,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("Quick search error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Search failed',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Get chart data for dashboard widgets (AJAX endpoint)
     */
    public function getChartData() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $chartType = $_GET['type'] ?? '';
        
        try {
            $data = [];
            
            if ($this->dashboardModel) {
                switch ($chartType) {
                    case 'assets_by_category':
                        $data = $this->dashboardModel->getAssetsByCategory();
                        break;
                        
                    case 'assets_by_project':
                        $data = $this->dashboardModel->getAssetsByProject();
                        break;
                        
                    case 'acquisition_trends':
                        $months = (int)($_GET['months'] ?? 12);
                        $data = $this->dashboardModel->getAssetAcquisitionTrends($months);
                        break;
                        
                    default:
                        throw new Exception('Invalid chart type');
                }
            } else {
                // Fallback - return empty data for charts
                $data = [];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'type' => $chartType,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("Chart data error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load chart data',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Export dashboard data (CSV/Excel)
     */
    public function export() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $format = $_GET['format'] ?? 'csv';
        $user = $this->auth->getCurrentUser();
        
        try {
            // Get stats - use new model if available, fallback to basic stats
            if ($this->dashboardModel) {
                $stats = $this->dashboardModel->getDashboardStats($user['role_name'], $user['id']);
            } else {
                $stats = $this->getBasicDashboardData();
            }
            
            if ($format === 'csv') {
                $this->exportCSV($stats);
            } else {
                throw new Exception('Unsupported export format');
            }
            
        } catch (Exception $e) {
            error_log("Dashboard export error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Export failed']);
        }
    }
    
    /**
     * Export dashboard data as CSV
     */
    private function exportCSV($stats) {
        $filename = 'dashboard_stats_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        $output = fopen('php://output', 'w');
        
        // Write header
        fputcsv($output, ['Category', 'Metric', 'Value']);
        
        // Write asset statistics
        if (isset($stats['assets'])) {
            foreach ($stats['assets'] as $key => $value) {
                fputcsv($output, ['Assets', ucfirst(str_replace('_', ' ', $key)), $value]);
            }
        }
        
        // Write project statistics
        if (isset($stats['projects'])) {
            foreach ($stats['projects'] as $key => $value) {
                fputcsv($output, ['Projects', ucfirst(str_replace('_', ' ', $key)), $value]);
            }
        }
        
        // Write withdrawal statistics
        if (isset($stats['withdrawals'])) {
            foreach ($stats['withdrawals'] as $key => $value) {
                fputcsv($output, ['Withdrawals', ucfirst(str_replace('_', ' ', $key)), $value]);
            }
        }
        
        // Write maintenance statistics
        if (isset($stats['maintenance'])) {
            foreach ($stats['maintenance'] as $key => $value) {
                fputcsv($output, ['Maintenance', ucfirst(str_replace('_', ' ', $key)), $value]);
            }
        }
        
        // Write incident statistics
        if (isset($stats['incidents'])) {
            foreach ($stats['incidents'] as $key => $value) {
                fputcsv($output, ['Incidents', ucfirst(str_replace('_', ' ', $key)), $value]);
            }
        }
        
        fclose($output);
    }
    
    /**
     * Refresh dashboard cache
     */
    public function refreshCache() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            // Clear any cached data (if caching is implemented)
            // For now, just return success
            
            echo json_encode([
                'success' => true,
                'message' => 'Dashboard cache refreshed',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("Dashboard cache refresh error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to refresh cache',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
?>
