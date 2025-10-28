<?php
/**
 * ConstructLink™ Dashboard Model
 * Handles dashboard statistics and data aggregation
 */

class DashboardModel extends BaseModel {
    
    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStats($userRole = null, $userId = null) {
        try {
            // Get current user data to determine project filtering
            $currentUser = null;
            if ($userId) {
                $stmt = $this->db->prepare("SELECT current_project_id FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            $stats = [
                'assets' => $this->getAssetStats($userRole, $currentUser),
                'projects' => $this->getProjectStats(),
                'withdrawals' => $this->getWithdrawalStats($userRole, $currentUser),
                'maintenance' => $this->getMaintenanceStats($userRole, $currentUser),
                'incidents' => $this->getIncidentStats($userRole, $currentUser),
                'recent_activities' => $this->getRecentActivities($userId, 10)
            ];
            
            // Add role-specific data
            if ($userRole) {
                $stats = array_merge($stats, $this->getRoleSpecificStats($userRole, $userId));
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            return $this->getDefaultStats();
        }
    }
    
    /**
     * Get asset statistics
     */
    public function getAssetStats($userRole = null, $currentUser = null) {
        try {
            // Determine if we need project-specific filtering
            $projectSpecificRoles = ['Project Manager', 'Warehouseman', 'Site Inventory Clerk'];
            $filterByProject = false;
            $projectId = null;
            
            if ($userRole && in_array($userRole, $projectSpecificRoles) && $currentUser && $currentUser['current_project_id']) {
                $filterByProject = true;
                $projectId = $currentUser['current_project_id'];
            }
            
            $sql = "
                SELECT 
                    COUNT(*) as total_assets,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_assets,
                    SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use_assets,
                    SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed_assets,
                    SUM(CASE WHEN status = 'under_maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                    SUM(CASE WHEN status = 'retired' THEN 1 ELSE 0 END) as retired_assets,
                    SUM(CASE WHEN acquisition_cost IS NOT NULL THEN acquisition_cost ELSE 0 END) as total_value
                FROM assets
            ";
            
            $params = [];
            if ($filterByProject) {
                $sql .= " WHERE project_id = ?";
                $params[] = $projectId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Asset stats error: " . $e->getMessage());
            return [
                'total_assets' => 0,
                'available_assets' => 0,
                'in_use_assets' => 0,
                'borrowed_assets' => 0,
                'maintenance_assets' => 0,
                'retired_assets' => 0,
                'total_value' => 0
            ];
        }
    }
    
    /**
     * Get project statistics
     */
    public function getProjectStats() {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_projects,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_projects
                FROM projects
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Project stats error: " . $e->getMessage());
            return [
                'total_projects' => 0,
                'active_projects' => 0,
                'inactive_projects' => 0
            ];
        }
    }
    
    /**
     * Get withdrawal statistics
     */
    public function getWithdrawalStats($userRole = null, $currentUser = null) {
        try {
            // Determine if we need project-specific filtering
            $projectSpecificRoles = ['Project Manager', 'Warehouseman', 'Site Inventory Clerk'];
            $filterByProject = false;
            $projectId = null;
            
            if ($userRole && in_array($userRole, $projectSpecificRoles) && $currentUser && $currentUser['current_project_id']) {
                $filterByProject = true;
                $projectId = $currentUser['current_project_id'];
            }
            
            $sql = "
                SELECT 
                    COUNT(*) as total_withdrawals,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_withdrawals,
                    SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) as released_withdrawals,
                    SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_withdrawals,
                    SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled_withdrawals,
                    SUM(CASE WHEN status = 'released' AND expected_return < CURDATE() THEN 1 ELSE 0 END) as overdue_withdrawals
                FROM withdrawals
            ";
            
            $params = [];
            if ($filterByProject) {
                $sql .= " WHERE project_id = ?";
                $params[] = $projectId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Withdrawal stats error: " . $e->getMessage());
            return [
                'total_withdrawals' => 0,
                'pending_withdrawals' => 0,
                'released_withdrawals' => 0,
                'returned_withdrawals' => 0,
                'canceled_withdrawals' => 0,
                'overdue_withdrawals' => 0
            ];
        }
    }
    
    /**
     * Get maintenance statistics
     */
    public function getMaintenanceStats($userRole = null, $currentUser = null) {
        try {
            // Determine if we need project-specific filtering
            $projectSpecificRoles = ['Project Manager', 'Warehouseman', 'Site Inventory Clerk'];
            $filterByProject = false;
            $projectId = null;
            
            if ($userRole && in_array($userRole, $projectSpecificRoles) && $currentUser && $currentUser['current_project_id']) {
                $filterByProject = true;
                $projectId = $currentUser['current_project_id'];
            }
            
            $sql = "
                SELECT 
                    COUNT(*) as total_maintenance,
                    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_maintenance,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_maintenance,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_maintenance,
                    SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled_maintenance,
                    SUM(CASE WHEN status IN ('scheduled', 'in_progress') AND scheduled_date < CURDATE() THEN 1 ELSE 0 END) as overdue_maintenance
                FROM maintenance m
                JOIN assets a ON m.asset_id = a.id
            ";
            
            $params = [];
            if ($filterByProject) {
                $sql .= " WHERE a.project_id = ?";
                $params[] = $projectId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Maintenance stats error: " . $e->getMessage());
            return [
                'total_maintenance' => 0,
                'scheduled_maintenance' => 0,
                'in_progress_maintenance' => 0,
                'completed_maintenance' => 0,
                'canceled_maintenance' => 0,
                'overdue_maintenance' => 0
            ];
        }
    }
    
    /**
     * Get incident statistics
     */
    public function getIncidentStats($userRole = null, $currentUser = null) {
        try {
            // Determine if we need project-specific filtering
            $projectSpecificRoles = ['Project Manager', 'Warehouseman', 'Site Inventory Clerk'];
            $filterByProject = false;
            $projectId = null;
            
            if ($userRole && in_array($userRole, $projectSpecificRoles) && $currentUser && $currentUser['current_project_id']) {
                $filterByProject = true;
                $projectId = $currentUser['current_project_id'];
            }
            
            $sql = "
                SELECT 
                    COUNT(*) as total_incidents,
                    SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) as under_investigation,
                    SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified_incidents,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_incidents,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_incidents,
                    SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_assets,
                    SUM(CASE WHEN type = 'damaged' THEN 1 ELSE 0 END) as damaged_assets,
                    SUM(CASE WHEN type = 'stolen' THEN 1 ELSE 0 END) as stolen_assets
                FROM incidents i
                JOIN assets a ON i.asset_id = a.id
            ";
            
            $params = [];
            if ($filterByProject) {
                $sql .= " WHERE a.project_id = ?";
                $params[] = $projectId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Incident stats error: " . $e->getMessage());
            return [
                'total_incidents' => 0,
                'under_investigation' => 0,
                'verified_incidents' => 0,
                'resolved_incidents' => 0,
                'closed_incidents' => 0,
                'lost_assets' => 0,
                'damaged_assets' => 0,
                'stolen_assets' => 0
            ];
        }
    }
    
    /**
     * Get recent activities
     */
    public function getRecentActivities($userId = null, $limit = 10) {
        try {
            $sql = "
                SELECT 
                    al.action,
                    al.description,
                    al.table_name,
                    al.record_id,
                    al.created_at,
                    u.full_name as user_name
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
            ";
            
            $params = [];
            if ($userId) {
                $sql .= " WHERE al.user_id = ?";
                $params[] = $userId;
            }
            
            $sql .= " ORDER BY al.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Recent activities error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get role-specific statistics
     */
    public function getRoleSpecificStats($userRole, $userId = null) {
        $stats = [];
        
        try {
            switch ($userRole) {
                case 'System Admin':
                    $stats = array_merge($stats, $this->getAdminStats());
                    break;
                    
                case 'Finance Director':
                    $stats = array_merge($stats, $this->getFinanceStats());
                    break;
                    
                case 'Asset Director':
                    $stats = array_merge($stats, $this->getAssetDirectorStats());
                    break;
                    
                case 'Procurement Officer':
                    $stats = array_merge($stats, $this->getProcurementStats());
                    break;
                    
                case 'Warehouseman':
                    $stats = array_merge($stats, $this->getWarehouseStats($userId));
                    break;
                    
                case 'Project Manager':
                    $stats = array_merge($stats, $this->getProjectManagerStats($userId));
                    break;
                    
                case 'Site Inventory Clerk':
                    $stats = array_merge($stats, $this->getSiteClerkStats($userId));
                    break;
            }
            
        } catch (Exception $e) {
            error_log("Role-specific stats error: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Get admin-specific statistics
     */
    private function getAdminStats() {
        try {
            $sql = "
                SELECT 
                    COUNT(DISTINCT u.id) as total_users,
                    SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END) as active_users,
                    COUNT(DISTINCT us.id) as active_sessions
                FROM users u
                LEFT JOIN user_sessions us ON u.id = us.user_id AND us.last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return ['admin' => $stmt->fetch(PDO::FETCH_ASSOC)];
            
        } catch (Exception $e) {
            error_log("Admin stats error: " . $e->getMessage());
            return ['admin' => ['total_users' => 0, 'active_users' => 0, 'active_sessions' => 0]];
        }
    }
    
    /**
     * Get finance-specific statistics
     */
    private function getFinanceStats() {
        try {
            // Asset value statistics
            $assetSql = "
                SELECT 
                    SUM(CASE WHEN acquisition_cost IS NOT NULL THEN acquisition_cost ELSE 0 END) as total_asset_value,
                    AVG(CASE WHEN acquisition_cost IS NOT NULL THEN acquisition_cost ELSE 0 END) as avg_asset_value,
                    COUNT(CASE WHEN acquisition_cost > 10000 THEN 1 END) as high_value_assets
                FROM assets
            ";
            
            $stmt = $this->db->prepare($assetSql);
            $stmt->execute();
            $assetStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pending financial approvals
            $pendingSql = "
                SELECT 
                    COUNT(CASE WHEN r.status = 'Reviewed' AND r.estimated_cost > 10000 THEN 1 END) as pending_high_value_requests,
                    COUNT(CASE WHEN po.status = 'Reviewed' AND po.net_total > 10000 THEN 1 END) as pending_high_value_procurement,
                    COUNT(CASE WHEN t.status = 'Pending Approval' THEN 1 END) as pending_transfers,
                    COUNT(CASE WHEN m.status = 'scheduled' AND m.estimated_cost > 5000 THEN 1 END) as pending_maintenance_approval
                FROM requests r
                LEFT JOIN procurement_orders po ON 1=1
                LEFT JOIN transfers t ON 1=1  
                LEFT JOIN maintenance m ON 1=1
                WHERE r.status = 'Reviewed' OR po.status = 'Reviewed' OR t.status = 'Pending Approval' OR m.status = 'scheduled'
            ";
            
            $stmt = $this->db->prepare($pendingSql);
            $stmt->execute();
            $pendingStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Budget utilization by project
            $budgetSql = "
                SELECT 
                    p.name as project_name,
                    p.budget,
                    SUM(CASE WHEN a.acquisition_cost IS NOT NULL THEN a.acquisition_cost ELSE 0 END) as utilized
                FROM projects p
                LEFT JOIN assets a ON p.id = a.project_id
                WHERE p.is_active = 1
                GROUP BY p.id, p.name, p.budget
                ORDER BY utilized DESC
                LIMIT 5
            ";
            
            $stmt = $this->db->prepare($budgetSql);
            $stmt->execute();
            $budgetStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'finance' => array_merge($assetStats, $pendingStats),
                'budget_utilization' => $budgetStats
            ];
            
        } catch (Exception $e) {
            error_log("Finance stats error: " . $e->getMessage());
            return [
                'finance' => [
                    'total_asset_value' => 0, 
                    'avg_asset_value' => 0, 
                    'high_value_assets' => 0,
                    'pending_high_value_requests' => 0,
                    'pending_high_value_procurement' => 0,
                    'pending_transfers' => 0,
                    'pending_maintenance_approval' => 0
                ],
                'budget_utilization' => []
            ];
        }
    }
    
    /**
     * Get asset director-specific statistics
     */
    private function getAssetDirectorStats() {
        try {
            // Asset status overview
            $assetSql = "
                SELECT 
                    COUNT(CASE WHEN status = 'under_maintenance' THEN 1 END) as assets_under_maintenance,
                    COUNT(CASE WHEN status = 'retired' THEN 1 END) as retired_assets,
                    COUNT(DISTINCT category_id) as categories_in_use,
                    COUNT(CASE WHEN status = 'disposed' THEN 1 END) as disposed_assets
                FROM assets
            ";
            
            $stmt = $this->db->prepare($assetSql);
            $stmt->execute();
            $assetStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pending actions
            $pendingSql = "
                SELECT 
                    COUNT(CASE WHEN po.status = 'Pending' THEN 1 END) as pending_procurement_verification,
                    COUNT(CASE WHEN dt.status = 'Discrepancy Reported' THEN 1 END) as pending_discrepancies,
                    COUNT(CASE WHEN i.status = 'Pending Authorization' THEN 1 END) as pending_incident_resolution,
                    COUNT(CASE WHEN m.status = 'scheduled' THEN 1 END) as pending_maintenance_authorization
                FROM procurement_orders po
                LEFT JOIN delivery_tracking dt ON 1=1
                LEFT JOIN incidents i ON 1=1
                LEFT JOIN maintenance m ON 1=1
                WHERE po.status = 'Pending' OR dt.status = 'Discrepancy Reported' 
                    OR i.status = 'Pending Authorization' OR m.status = 'scheduled'
            ";
            
            $stmt = $this->db->prepare($pendingSql);
            $stmt->execute();
            $pendingStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Asset utilization rate
            $utilizationSql = "
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status IN ('in_use', 'borrowed') THEN 1 END) as in_use,
                    ROUND((COUNT(CASE WHEN status IN ('in_use', 'borrowed') THEN 1 END) * 100.0 / COUNT(*)), 2) as utilization_rate
                FROM assets
                WHERE status NOT IN ('retired', 'disposed')
            ";
            
            $stmt = $this->db->prepare($utilizationSql);
            $stmt->execute();
            $utilizationStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'asset_director' => array_merge($assetStats, $pendingStats, $utilizationStats)
            ];
            
        } catch (Exception $e) {
            error_log("Asset director stats error: " . $e->getMessage());
            return ['asset_director' => [
                'assets_under_maintenance' => 0, 
                'retired_assets' => 0, 
                'categories_in_use' => 0,
                'disposed_assets' => 0,
                'pending_procurement_verification' => 0,
                'pending_discrepancies' => 0,
                'pending_incident_resolution' => 0,
                'pending_maintenance_authorization' => 0,
                'utilization_rate' => 0
            ]];
        }
    }
    
    /**
     * Get procurement-specific statistics
     */
    private function getProcurementStats() {
        try {
            // Vendor and maker statistics
            $vendorSql = "
                SELECT 
                    COUNT(DISTINCT v.id) as active_vendors,
                    COUNT(DISTINCT m.id) as active_makers,
                    COUNT(DISTINCT CASE WHEN v.is_preferred = 1 THEN v.id END) as preferred_vendors
                FROM vendors v
                LEFT JOIN makers m ON 1=1
            ";
            
            $stmt = $this->db->prepare($vendorSql);
            $stmt->execute();
            $vendorStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Procurement activity
            $activitySql = "
                SELECT 
                    COUNT(CASE WHEN po.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as recent_po_count,
                    COUNT(CASE WHEN po.status = 'Draft' THEN 1 END) as draft_orders,
                    COUNT(CASE WHEN po.status = 'Approved' AND po.delivery_status = 'Pending' THEN 1 END) as pending_delivery,
                    COUNT(CASE WHEN r.status = 'Approved' AND r.procurement_id IS NULL THEN 1 END) as approved_requests_pending_po
                FROM procurement_orders po
                LEFT JOIN requests r ON 1=1
                WHERE po.created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) 
                    OR r.status = 'Approved'
            ";
            
            $stmt = $this->db->prepare($activitySql);
            $stmt->execute();
            $activityStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delivery performance
            $deliverySql = "
                SELECT 
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' 
                        AND po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) as on_time_deliveries,
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' THEN 1 END) as total_deliveries,
                    ROUND(AVG(CASE WHEN po.delivery_status = 'Delivered' 
                        THEN DATEDIFF(po.actual_delivery_date, po.scheduled_delivery_date) END), 1) as avg_delivery_variance
                FROM procurement_orders po
                WHERE po.scheduled_delivery_date IS NOT NULL
            ";
            
            $stmt = $this->db->prepare($deliverySql);
            $stmt->execute();
            $deliveryStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'procurement' => array_merge($vendorStats, $activityStats, $deliveryStats)
            ];
            
        } catch (Exception $e) {
            error_log("Procurement stats error: " . $e->getMessage());
            return ['procurement' => [
                'active_vendors' => 0, 
                'active_makers' => 0, 
                'preferred_vendors' => 0,
                'recent_po_count' => 0,
                'draft_orders' => 0,
                'pending_delivery' => 0,
                'approved_requests_pending_po' => 0,
                'on_time_deliveries' => 0,
                'total_deliveries' => 0,
                'avg_delivery_variance' => 0
            ]];
        }
    }
    
    /**
     * Get warehouse-specific statistics
     *
     * FIXED: Added missing daily summary metrics (received_today, released_today, tools_issued_today, tools_returned_today)
     * ADDED: QR tag management statistics (printing, application, verification)
     * ADDED: Asset location management statistics
     * ADDED: In-transit asset tracking
     * ADDED: Asset condition monitoring
     * IMPROVED: Category-specific low stock thresholds with fallback
     *
     * @param int|null $userId User ID to get project-specific stats
     * @return array Warehouse statistics array
     */
    private function getWarehouseStats($userId = null) {
        try {
            // Get user's current project
            $currentProjectId = null;
            if ($userId) {
                $projectSql = "SELECT current_project_id FROM users WHERE id = ?";
                $stmt = $this->db->prepare($projectSql);
                $stmt->execute([$userId]);
                $currentProjectId = $stmt->fetchColumn();
            }

            // Withdrawal statistics (project-specific)
            $withdrawalSql = "
                SELECT
                    COUNT(CASE WHEN status = 'Approved' THEN 1 END) as pending_releases,
                    COUNT(CASE WHEN status = 'Released' AND expected_return < CURDATE() THEN 1 END) as overdue_returns,
                    COUNT(CASE WHEN status = 'Released' THEN 1 END) as active_withdrawals,
                    COUNT(CASE WHEN status = 'Released' AND DATE(release_date) = CURDATE() THEN 1 END) as released_today
                FROM withdrawals
            ";

            $params = [];
            if ($currentProjectId) {
                $withdrawalSql .= " WHERE project_id = ?";
                $params[] = $currentProjectId;
            }

            $stmt = $this->db->prepare($withdrawalSql);
            $stmt->execute($params);
            $withdrawalStats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Borrowed tools statistics (project-specific)
            $toolsSql = "
                SELECT
                    COUNT(CASE WHEN bt.status = 'Borrowed' THEN 1 END) as borrowed_tools,
                    COUNT(CASE WHEN bt.status = 'Overdue' THEN 1 END) as overdue_tools,
                    COUNT(CASE WHEN bt.status = 'Pending Verification' THEN 1 END) as pending_tool_requests,
                    COUNT(CASE WHEN bt.status IN ('Borrowed', 'Approved')
                        AND DATE(bt.borrowed_date) = CURDATE() THEN 1 END) as tools_issued_today,
                    COUNT(CASE WHEN bt.status = 'Returned'
                        AND DATE(bt.actual_return) = CURDATE() THEN 1 END) as tools_returned_today
                FROM borrowed_tools bt
                JOIN assets a ON bt.asset_id = a.id
            ";

            $toolParams = [];
            if ($currentProjectId) {
                $toolsSql .= " WHERE a.project_id = ?";
                $toolParams[] = $currentProjectId;
            }

            $stmt = $this->db->prepare($toolsSql);
            $stmt->execute($toolParams);
            $toolsStats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Delivery receipt statistics (project-specific)
            $deliverySql = "
                SELECT
                    COUNT(CASE WHEN po.delivery_status = 'Scheduled' THEN 1 END) as scheduled_deliveries,
                    COUNT(CASE WHEN po.delivery_status = 'In Transit' THEN 1 END) as in_transit_deliveries,
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' AND po.received_by IS NULL THEN 1 END) as awaiting_receipt,
                    COUNT(CASE WHEN po.delivery_status = 'Received'
                        AND DATE(po.received_at) = CURDATE() THEN 1 END) as received_today
                FROM procurement_orders po
                WHERE po.delivery_status IN ('Scheduled', 'In Transit', 'Delivered', 'Received')
            ";

            $deliveryParams = [];
            if ($currentProjectId) {
                $deliverySql .= " AND po.project_id = ?";
                $deliveryParams[] = $currentProjectId;
            }

            $stmt = $this->db->prepare($deliverySql);
            $stmt->execute($deliveryParams);
            $deliveryStats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Stock levels with improved threshold logic (project-specific)
            $stockSql = "
                SELECT
                    COUNT(CASE WHEN a.status = 'available' AND c.is_consumable = 1 THEN 1 END) as consumable_stock,
                    COUNT(CASE WHEN a.status = 'available' AND c.is_consumable = 0 THEN 1 END) as tool_stock,
                    COUNT(CASE WHEN c.is_consumable = 1
                        AND a.available_quantity <= COALESCE(c.low_stock_threshold, 3) THEN 1 END) as low_stock_items,
                    COUNT(CASE WHEN c.is_consumable = 1
                        AND a.available_quantity <= COALESCE(c.critical_stock_threshold, 1) THEN 1 END) as critical_stock_items,
                    COUNT(CASE WHEN c.is_consumable = 1
                        AND a.available_quantity = 0 THEN 1 END) as out_of_stock_items
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                WHERE a.status = 'available'
            ";

            $stockParams = [];
            if ($currentProjectId) {
                $stockSql .= " AND a.project_id = ?";
                $stockParams[] = $currentProjectId;
            }

            $stmt = $this->db->prepare($stockSql);
            $stmt->execute($stockParams);
            $stockStats = $stmt->fetch(PDO::FETCH_ASSOC);

            // QR Tag Management Statistics (project-specific)
            $qrTagSql = "
                SELECT
                    COUNT(CASE WHEN qr_tag_printed IS NULL OR qr_tag_printed < 1 THEN 1 END) as qr_needs_printing,
                    COUNT(CASE WHEN qr_tag_applied IS NULL THEN 1 END) as qr_needs_application,
                    COUNT(CASE WHEN qr_tag_verified IS NULL THEN 1 END) as qr_needs_verification,
                    COUNT(CASE WHEN qr_tag_printed IS NOT NULL
                        AND qr_tag_applied IS NOT NULL
                        AND qr_tag_verified IS NOT NULL THEN 1 END) as qr_fully_tagged,
                    COUNT(CASE WHEN DATE(qr_tag_printed) = CURDATE() THEN 1 END) as qr_printed_today,
                    COUNT(CASE WHEN DATE(qr_tag_applied) = CURDATE() THEN 1 END) as qr_applied_today,
                    COUNT(CASE WHEN DATE(qr_tag_verified) = CURDATE() THEN 1 END) as qr_verified_today
                FROM assets
                WHERE status NOT IN ('retired', 'disposed')
            ";

            $qrParams = [];
            if ($currentProjectId) {
                $qrTagSql .= " AND project_id = ?";
                $qrParams[] = $currentProjectId;
            }

            $stmt = $this->db->prepare($qrTagSql);
            $stmt->execute($qrParams);
            $qrTagStats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Asset Location Management (project-specific)
            $locationSql = "
                SELECT
                    COUNT(CASE WHEN location IS NULL OR location = '' THEN 1 END) as missing_location,
                    COUNT(CASE WHEN location IS NOT NULL
                        AND updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1 END) as location_needs_verification,
                    COUNT(DISTINCT location) as total_locations
                FROM assets
                WHERE status IN ('available', 'in_use', 'borrowed')
            ";

            $locationParams = [];
            if ($currentProjectId) {
                $locationSql .= " AND project_id = ?";
                $locationParams[] = $currentProjectId;
            }

            $stmt = $this->db->prepare($locationSql);
            $stmt->execute($locationParams);
            $locationStats = $stmt->fetch(PDO::FETCH_ASSOC);

            // In-Transit and Condition Monitoring (project-specific)
            $assetStatusSql = "
                SELECT
                    COUNT(CASE WHEN status = 'in_transit' THEN 1 END) as assets_in_transit,
                    COUNT(CASE WHEN current_condition = 'Fair' THEN 1 END) as fair_condition,
                    COUNT(CASE WHEN current_condition IN ('Poor', 'Damaged') THEN 1 END) as poor_damaged_condition,
                    COUNT(CASE WHEN status = 'under_maintenance' THEN 1 END) as under_maintenance
                FROM assets
                WHERE status NOT IN ('retired', 'disposed')
            ";

            $assetStatusParams = [];
            if ($currentProjectId) {
                $assetStatusSql .= " AND project_id = ?";
                $assetStatusParams[] = $currentProjectId;
            }

            $stmt = $this->db->prepare($assetStatusSql);
            $stmt->execute($assetStatusParams);
            $assetStatusStats = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'warehouse' => array_merge(
                    $withdrawalStats,
                    $toolsStats,
                    $deliveryStats,
                    $stockStats,
                    $qrTagStats,
                    $locationStats,
                    $assetStatusStats
                )
            ];

        } catch (Exception $e) {
            error_log("Warehouse stats error: " . $e->getMessage());
            return ['warehouse' => [
                'pending_releases' => 0,
                'overdue_returns' => 0,
                'active_withdrawals' => 0,
                'released_today' => 0,
                'borrowed_tools' => 0,
                'overdue_tools' => 0,
                'pending_tool_requests' => 0,
                'tools_issued_today' => 0,
                'tools_returned_today' => 0,
                'scheduled_deliveries' => 0,
                'in_transit_deliveries' => 0,
                'awaiting_receipt' => 0,
                'received_today' => 0,
                'consumable_stock' => 0,
                'tool_stock' => 0,
                'low_stock_items' => 0,
                'critical_stock_items' => 0,
                'out_of_stock_items' => 0,
                'qr_needs_printing' => 0,
                'qr_needs_application' => 0,
                'qr_needs_verification' => 0,
                'qr_fully_tagged' => 0,
                'qr_printed_today' => 0,
                'qr_applied_today' => 0,
                'qr_verified_today' => 0,
                'missing_location' => 0,
                'location_needs_verification' => 0,
                'total_locations' => 0,
                'assets_in_transit' => 0,
                'fair_condition' => 0,
                'poor_damaged_condition' => 0,
                'under_maintenance' => 0
            ]];
        }
    }
    
    /**
     * Get project manager-specific statistics
     */
    private function getProjectManagerStats($userId) {
        try {
            // Get user's current project
            $projectSql = "SELECT current_project_id FROM users WHERE id = ?";
            $stmt = $this->db->prepare($projectSql);
            $stmt->execute([$userId]);
            $currentProjectId = $stmt->fetchColumn();
            
            // Project overview
            $overviewSql = "
                SELECT 
                    COUNT(DISTINCT p.id) as managed_projects,
                    SUM(CASE WHEN p.id = ? THEN 1 ELSE 0 END) as current_project,
                    COUNT(DISTINCT up.project_id) as assigned_projects
                FROM projects p
                LEFT JOIN user_projects up ON p.id = up.project_id AND up.user_id = ? AND up.is_active = 1
                WHERE p.is_active = 1 AND (p.project_manager_id = ? OR up.user_id = ?)
            ";
            
            $stmt = $this->db->prepare($overviewSql);
            $stmt->execute([$currentProjectId, $userId, $userId, $userId]);
            $overviewStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Current project assets and resources
            $resourceSql = "
                SELECT 
                    COUNT(CASE WHEN a.status = 'available' THEN 1 END) as available_project_assets,
                    COUNT(CASE WHEN a.status = 'in_use' THEN 1 END) as in_use_project_assets,
                    SUM(CASE WHEN a.acquisition_cost IS NOT NULL THEN a.acquisition_cost ELSE 0 END) as project_asset_value
                FROM assets a
                WHERE a.project_id = ?
            ";
            
            $stmt = $this->db->prepare($resourceSql);
            $stmt->execute([$currentProjectId ?: 0]);
            $resourceStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pending actions
            $pendingSql = "
                SELECT 
                    COUNT(CASE WHEN r.status = 'Submitted' THEN 1 END) as pending_request_reviews,
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' AND po.received_by IS NULL THEN 1 END) as pending_receipt_confirmations,
                    COUNT(CASE WHEN t.status = 'Pending Verification' THEN 1 END) as pending_transfer_approvals,
                    COUNT(CASE WHEN w.status = 'Pending Approval' THEN 1 END) as pending_withdrawal_approvals,
                    COUNT(CASE WHEN i.status = 'Pending Verification' THEN 1 END) as pending_incident_investigations
                FROM requests r
                LEFT JOIN procurement_orders po ON r.project_id = po.project_id
                LEFT JOIN transfers t ON r.project_id = t.from_project OR r.project_id = t.to_project
                LEFT JOIN withdrawals w ON r.project_id = w.project_id
                LEFT JOIN incidents i ON 1=1
                WHERE r.project_id = ? OR po.project_id = ? OR t.from_project = ? 
                    OR t.to_project = ? OR w.project_id = ?
            ";
            
            $stmt = $this->db->prepare($pendingSql);
            $projectId = $currentProjectId ?: 0;
            $stmt->execute([$projectId, $projectId, $projectId, $projectId, $projectId]);
            $pendingStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'project_manager' => array_merge($overviewStats, $resourceStats, $pendingStats)
            ];
            
        } catch (Exception $e) {
            error_log("Project manager stats error: " . $e->getMessage());
            return ['project_manager' => [
                'managed_projects' => 0, 
                'current_project' => 0,
                'assigned_projects' => 0,
                'available_project_assets' => 0, 
                'in_use_project_assets' => 0,
                'project_asset_value' => 0,
                'pending_request_reviews' => 0,
                'pending_receipt_confirmations' => 0,
                'pending_transfer_approvals' => 0,
                'pending_withdrawal_approvals' => 0,
                'pending_incident_investigations' => 0
            ]];
        }
    }
    
    /**
     * Get site clerk-specific statistics
     */
    private function getSiteClerkStats($userId = null) {
        try {
            // Get user's current project
            $currentProjectId = null;
            if ($userId) {
                $projectSql = "SELECT current_project_id FROM users WHERE id = ?";
                $stmt = $this->db->prepare($projectSql);
                $stmt->execute([$userId]);
                $currentProjectId = $stmt->fetchColumn();
            }
            
            // Daily activity statistics (project-specific)
            $dailySql = "
                SELECT 
                    COUNT(CASE WHEN bt.created_at >= CURDATE() AND bt.status = 'Borrowed' THEN 1 END) as tools_borrowed_today,
                    COUNT(CASE WHEN bt.actual_return IS NOT NULL AND DATE(bt.actual_return) = CURDATE() THEN 1 END) as tools_returned_today,
                    COUNT(CASE WHEN r.created_at >= CURDATE() THEN 1 END) as requests_created_today
                FROM borrowed_tools bt
                JOIN assets a1 ON bt.asset_id = a1.id
                LEFT JOIN requests r ON r.project_id = a1.project_id
                WHERE (bt.created_at >= CURDATE() OR r.created_at >= CURDATE())
            ";
            
            $dailyParams = [];
            if ($currentProjectId) {
                $dailySql .= " AND a1.project_id = ?";
                $dailyParams[] = $currentProjectId;
            }
            
            $stmt = $this->db->prepare($dailySql);
            $stmt->execute($dailyParams);
            $dailyStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pending actions (project-specific)
            $pendingSql = "
                SELECT 
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' AND po.received_by IS NULL THEN 1 END) as deliveries_to_verify,
                    COUNT(CASE WHEN t.status = 'Approved' AND t.received_by IS NULL THEN 1 END) as transfers_to_receive,
                    COUNT(CASE WHEN w.status = 'Pending Verification' THEN 1 END) as withdrawals_to_verify,
                    COUNT(CASE WHEN r.status = 'Draft' THEN 1 END) as draft_requests
                FROM procurement_orders po
                LEFT JOIN transfers t ON 1=1
                LEFT JOIN withdrawals w ON 1=1
                LEFT JOIN requests r ON 1=1
                WHERE (po.delivery_status = 'Delivered' OR t.status = 'Approved' 
                    OR w.status = 'Pending Verification' OR r.status = 'Draft')
            ";
            
            $pendingParams = [];
            if ($currentProjectId) {
                $pendingSql .= " AND (po.project_id = ? OR t.to_project = ? OR w.project_id = ? OR r.project_id = ?)";
                $pendingParams = [$currentProjectId, $currentProjectId, $currentProjectId, $currentProjectId];
            }
            
            $stmt = $this->db->prepare($pendingSql);
            $stmt->execute($pendingParams);
            $pendingStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Incident management (project-specific)
            $incidentSql = "
                SELECT 
                    COUNT(CASE WHEN i.status = 'Pending Verification' THEN 1 END) as open_incidents,
                    COUNT(CASE WHEN i.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as recent_incidents,
                    COUNT(CASE WHEN i.type = 'lost' AND i.status NOT IN ('Resolved', 'Closed') THEN 1 END) as lost_items,
                    COUNT(CASE WHEN i.type = 'damaged' AND i.status NOT IN ('Resolved', 'Closed') THEN 1 END) as damaged_items
                FROM incidents i
                JOIN assets a2 ON i.asset_id = a2.id
            ";
            
            $incidentParams = [];
            if ($currentProjectId) {
                $incidentSql .= " WHERE a2.project_id = ?";
                $incidentParams[] = $currentProjectId;
            }
            
            $stmt = $this->db->prepare($incidentSql);
            $stmt->execute($incidentParams);
            $incidentStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Site inventory levels (project-specific)
            $inventorySql = "
                SELECT 
                    COUNT(CASE WHEN a.status = 'available' THEN 1 END) as available_on_site,
                    COUNT(CASE WHEN a.status = 'in_use' THEN 1 END) as in_use_on_site,
                    COUNT(CASE WHEN c.is_consumable = 1 AND a.available_quantity < 10 THEN 1 END) as low_stock_alerts
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                WHERE (a.location LIKE '%Site%' OR a.project_id IS NOT NULL)
            ";
            
            $inventoryParams = [];
            if ($currentProjectId) {
                $inventorySql .= " AND a.project_id = ?";
                $inventoryParams[] = $currentProjectId;
            }
            
            $stmt = $this->db->prepare($inventorySql);
            $stmt->execute($inventoryParams);
            $inventoryStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'site_clerk' => array_merge($dailyStats, $pendingStats, $incidentStats, $inventoryStats)
            ];
            
        } catch (Exception $e) {
            error_log("Site clerk stats error: " . $e->getMessage());
            return ['site_clerk' => [
                'tools_borrowed_today' => 0, 
                'tools_returned_today' => 0,
                'requests_created_today' => 0,
                'deliveries_to_verify' => 0,
                'transfers_to_receive' => 0,
                'withdrawals_to_verify' => 0,
                'draft_requests' => 0,
                'open_incidents' => 0, 
                'recent_incidents' => 0,
                'lost_items' => 0,
                'damaged_items' => 0,
                'available_on_site' => 0,
                'in_use_on_site' => 0,
                'low_stock_alerts' => 0
            ]];
        }
    }
    
    /**
     * Get default statistics (fallback)
     */
    private function getDefaultStats() {
        return [
            'assets' => [
                'total_assets' => 0,
                'available_assets' => 0,
                'in_use_assets' => 0,
                'borrowed_assets' => 0,
                'maintenance_assets' => 0,
                'retired_assets' => 0,
                'total_value' => 0
            ],
            'projects' => [
                'total_projects' => 0,
                'active_projects' => 0,
                'inactive_projects' => 0
            ],
            'withdrawals' => [
                'total_withdrawals' => 0,
                'pending_withdrawals' => 0,
                'released_withdrawals' => 0,
                'returned_withdrawals' => 0,
                'canceled_withdrawals' => 0,
                'overdue_withdrawals' => 0
            ],
            'maintenance' => [
                'total_maintenance' => 0,
                'scheduled_maintenance' => 0,
                'in_progress_maintenance' => 0,
                'completed_maintenance' => 0,
                'canceled_maintenance' => 0,
                'overdue_maintenance' => 0
            ],
            'incidents' => [
                'total_incidents' => 0,
                'under_investigation' => 0,
                'verified_incidents' => 0,
                'resolved_incidents' => 0,
                'closed_incidents' => 0,
                'lost_assets' => 0,
                'damaged_assets' => 0,
                'stolen_assets' => 0
            ],
            'recent_activities' => []
        ];
    }
    
    /**
     * Get assets by category for charts
     */
    public function getAssetsByCategory() {
        try {
            $sql = "
                SELECT 
                    c.name as category_name,
                    COUNT(a.id) as asset_count,
                    SUM(CASE WHEN a.acquisition_cost IS NOT NULL THEN a.acquisition_cost ELSE 0 END) as total_value
                FROM categories c
                LEFT JOIN assets a ON c.id = a.category_id
                GROUP BY c.id, c.name
                ORDER BY asset_count DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Assets by category error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get assets by project for charts
     */
    public function getAssetsByProject() {
        try {
            $sql = "
                SELECT 
                    p.name as project_name,
                    COUNT(a.id) as asset_count,
                    SUM(CASE WHEN a.acquisition_cost IS NOT NULL THEN a.acquisition_cost ELSE 0 END) as total_value
                FROM projects p
                LEFT JOIN assets a ON p.id = a.project_id
                WHERE p.is_active = 1
                GROUP BY p.id, p.name
                ORDER BY asset_count DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Assets by project error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get monthly asset acquisition trends
     */
    public function getAssetAcquisitionTrends($months = 12) {
        try {
            $sql = "
                SELECT 
                    DATE_FORMAT(acquired_date, '%Y-%m') as month,
                    COUNT(*) as acquisitions,
                    SUM(CASE WHEN acquisition_cost IS NOT NULL THEN acquisition_cost ELSE 0 END) as total_cost
                FROM assets
                WHERE acquired_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(acquired_date, '%Y-%m')
                ORDER BY month ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$months]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Asset acquisition trends error: " . $e->getMessage());
            return [];
        }
    }
}
?>
