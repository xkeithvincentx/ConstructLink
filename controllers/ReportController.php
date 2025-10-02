<?php
/**
 * ConstructLink™ Report Controller
 * Handles report generation and analytics with comprehensive data analysis
 */

class ReportController {
    private $auth;
    private $reportModel;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->reportModel = new ReportModel();
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Display reports dashboard
     */
    public function index() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Project Manager'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Get comprehensive dashboard statistics
            $dashboardStats = $this->reportModel->getDashboardStatistics();
            
            $pageTitle = 'Reports Dashboard - ConstructLink™';
            $pageHeader = 'Reports & Analytics';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Reports', 'url' => '?route=reports']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/reports/index.php';
            
        } catch (Exception $e) {
            error_log("Reports dashboard error: " . $e->getMessage());
            $error = 'Failed to load reports dashboard';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Asset utilization report
     */
    public function utilization() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Project Manager'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        // Get date range from query parameters
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-t');
        $projectId = $_GET['project_id'] ?? null;
        $categoryId = $_GET['category_id'] ?? null;
        
        try {
            // Get utilization data using ReportModel
            $utilizationReport = $this->reportModel->getAssetUtilizationReport($dateFrom, $dateTo, $projectId, $categoryId);
            
            // Get filter options
            $projectModel = new ProjectModel();
            $categoryModel = new CategoryModel();
            
            $projects = $projectModel->getActiveProjects();
            $categories = $categoryModel->getCategoriesForDropdown();
            
            $pageTitle = 'Asset Utilization Report - ConstructLink™';
            $pageHeader = 'Asset Utilization Analysis';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Reports', 'url' => '?route=reports'],
                ['title' => 'Asset Utilization', 'url' => '?route=reports/utilization']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/reports/utilization.php';
            
        } catch (Exception $e) {
            error_log("Utilization report error: " . $e->getMessage());
            $error = 'Failed to generate utilization report';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Withdrawal report
     */
    public function withdrawals() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Asset Director', 'Project Manager', 'Warehouseman'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        // Get filters from query parameters
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-t');
        $projectId = $_GET['project_id'] ?? null;
        $status = $_GET['status'] ?? null;
        
        try {
            // Get withdrawal data using ReportModel
            $withdrawalReport = $this->reportModel->getWithdrawalReport($dateFrom, $dateTo, $projectId, $status);
            
            // Get filter options
            $projectModel = new ProjectModel();
            $projects = $projectModel->getActiveProjects();
            
            $pageTitle = 'Withdrawal Report - ConstructLink™';
            $pageHeader = 'Withdrawal Analysis';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Reports', 'url' => '?route=reports'],
                ['title' => 'Withdrawals', 'url' => '?route=reports/withdrawals']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/reports/withdrawals.php';
            
        } catch (Exception $e) {
            error_log("Withdrawal report error: " . $e->getMessage());
            $error = 'Failed to generate withdrawal report';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Transfer report
     */
    public function transfers() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        // Get filters from query parameters
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-t');
        $fromProject = $_GET['from_project'] ?? null;
        $toProject = $_GET['to_project'] ?? null;
        
        try {
            // Get transfer data using ReportModel
            $transferReport = $this->reportModel->getTransferReport($dateFrom, $dateTo, $fromProject, $toProject);
            
            // Get filter options
            $projectModel = new ProjectModel();
            $projects = $projectModel->getActiveProjects();
            
            $pageTitle = 'Transfer Report - ConstructLink™';
            $pageHeader = 'Transfer Analysis';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Reports', 'url' => '?route=reports'],
                ['title' => 'Transfers', 'url' => '?route=reports/transfers']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/reports/transfers.php';
            
        } catch (Exception $e) {
            error_log("Transfer report error: " . $e->getMessage());
            $error = 'Failed to generate transfer report';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Maintenance report
     */
    public function maintenance() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        // Get filters from query parameters
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-t');
        $type = $_GET['type'] ?? null;
        $status = $_GET['status'] ?? null;
        
        try {
            // Get maintenance data using ReportModel
            $maintenanceReport = $this->reportModel->getMaintenanceReport($dateFrom, $dateTo, $type, $status);
            
            // Get filter options
            $projectModel = new ProjectModel();
            $projects = $projectModel->getActiveProjects();
            
            $pageTitle = 'Maintenance Report - ConstructLink™';
            $pageHeader = 'Maintenance Analysis';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Reports', 'url' => '?route=reports'],
                ['title' => 'Maintenance', 'url' => '?route=reports/maintenance']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/reports/maintenance.php';
            
        } catch (Exception $e) {
            error_log("Maintenance report error: " . $e->getMessage());
            $error = 'Failed to generate maintenance report';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Incident report
     */
    public function incidents() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        // Get filters from query parameters
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-t');
        $type = $_GET['type'] ?? null;
        $projectId = $_GET['project_id'] ?? null;
        
        try {
            // Get incident data using ReportModel
            $incidentReport = $this->reportModel->getIncidentReport($dateFrom, $dateTo, $type, $projectId);
            $incidentTrends = $this->reportModel->getIncidentTrends($dateFrom, $dateTo);
            
            // Get filter options
            $projectModel = new ProjectModel();
            $projects = $projectModel->getActiveProjects();
            
            $pageTitle = 'Incident Report - ConstructLink™';
            $pageHeader = 'Incident Analysis';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Reports', 'url' => '?route=reports'],
                ['title' => 'Incidents', 'url' => '?route=reports/incidents']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/reports/incidents.php';
            
        } catch (Exception $e) {
            error_log("Incident report error: " . $e->getMessage());
            $error = 'Failed to generate incident report';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Export report data to CSV
     */
    public function export() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Project Manager'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        $reportType = $_GET['type'] ?? '';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-t');
        
        try {
            $filename = '';
            $headers = [];
            $data = [];
            
            switch ($reportType) {
                case 'utilization':
                    $report = $this->reportModel->getAssetUtilizationReport($dateFrom, $dateTo, $_GET['project_id'] ?? null, $_GET['category_id'] ?? null);
                    $filename = "asset_utilization_report_{$dateFrom}_to_{$dateTo}.csv";
                    $headers = ['Asset Ref', 'Asset Name', 'Category', 'Project', 'Status', 'Withdrawal Count', 'Active Withdrawals', 'Avg Usage Days', 'Last Used', 'Days Since Last Use'];
                    $data = $report['utilization_data'];
                    break;
                    
                case 'withdrawals':
                    $report = $this->reportModel->getWithdrawalReport($dateFrom, $dateTo, $_GET['project_id'] ?? null, $_GET['status'] ?? null);
                    $filename = "withdrawal_report_{$dateFrom}_to_{$dateTo}.csv";
                    $headers = ['Asset Ref', 'Asset Name', 'Project', 'Receiver', 'Status', 'Created Date', 'Expected Return', 'Days Out', 'Days Overdue'];
                    $data = $report['withdrawals'];
                    break;
                    
                case 'transfers':
                    $report = $this->reportModel->getTransferReport($dateFrom, $dateTo, $_GET['from_project'] ?? null, $_GET['to_project'] ?? null);
                    $filename = "transfer_report_{$dateFrom}_to_{$dateTo}.csv";
                    $headers = ['Asset Ref', 'Asset Name', 'From Project', 'To Project', 'Type', 'Status', 'Created Date', 'Days to Approval', 'Days in Process'];
                    $data = $report['transfers'];
                    break;
                    
                case 'maintenance':
                    $report = $this->reportModel->getMaintenanceReport($dateFrom, $dateTo, $_GET['type'] ?? null, $_GET['status'] ?? null);
                    $filename = "maintenance_report_{$dateFrom}_to_{$dateTo}.csv";
                    $headers = ['Asset Ref', 'Asset Name', 'Type', 'Status', 'Scheduled Date', 'Completed Date', 'Estimated Cost', 'Actual Cost', 'Days Variance'];
                    $data = $report['maintenance'];
                    break;
                    
                case 'incidents':
                    $report = $this->reportModel->getIncidentReport($dateFrom, $dateTo, $_GET['type'] ?? null, $_GET['project_id'] ?? null);
                    $filename = "incident_report_{$dateFrom}_to_{$dateTo}.csv";
                    $headers = ['Asset Ref', 'Asset Name', 'Type', 'Severity', 'Status', 'Date Reported', 'Resolution Date', 'Days to Resolution', 'Is Overdue'];
                    $data = $report['incidents'];
                    break;
                    
                default:
                    throw new Exception('Invalid report type');
            }
            
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Write CSV headers
            fputcsv($output, $headers);
            
            // Write CSV data
            foreach ($data as $row) {
                $csvRow = [];
                foreach ($headers as $header) {
                    $key = strtolower(str_replace(' ', '_', $header));
                    $csvRow[] = $row[$key] ?? '';
                }
                fputcsv($output, $csvRow);
            }
            
            fclose($output);
            
        } catch (Exception $e) {
            error_log("Report export error: " . $e->getMessage());
            http_response_code(500);
            echo 'Export failed: ' . $e->getMessage();
        }
    }
}
?>
