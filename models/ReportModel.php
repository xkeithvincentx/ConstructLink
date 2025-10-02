<?php
/**
 * ConstructLink™ Report Model
 * Handles report data aggregation and analytics
 */

class ReportModel extends BaseModel {
    protected $table = 'audit_logs'; // Using audit_logs as base table for reporting
    
    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStatistics() {
        try {
            $stats = [];
            
            // Asset statistics - using the correct method name
            $assetModel = new AssetModel();
            $stats['assets'] = $assetModel->getAssetStats();
            
            // Project statistics - using the correct method name
            $projectModel = new ProjectModel();
            $stats['projects'] = $projectModel->getProjectStatistics();
            
            // Basic counts for other modules (fallback approach)
            try {
                $withdrawalModel = new WithdrawalModel();
                $stats['withdrawals'] = [
                    'total' => $withdrawalModel->count(),
                    'pending' => $withdrawalModel->count(['status' => 'pending']),
                    'released' => $withdrawalModel->count(['status' => 'released']),
                    'returned' => $withdrawalModel->count(['status' => 'returned'])
                ];
            } catch (Exception $e) {
                $stats['withdrawals'] = ['total' => 0, 'pending' => 0, 'released' => 0, 'returned' => 0];
            }
            
            try {
                $transferModel = new TransferModel();
                $stats['transfers'] = [
                    'total' => $transferModel->count(),
                    'pending' => $transferModel->count(['status' => 'pending']),
                    'approved' => $transferModel->count(['status' => 'approved']),
                    'completed' => $transferModel->count(['status' => 'completed'])
                ];
            } catch (Exception $e) {
                $stats['transfers'] = ['total' => 0, 'pending' => 0, 'approved' => 0, 'completed' => 0];
            }
            
            try {
                $maintenanceModel = new MaintenanceModel();
                $stats['maintenance'] = [
                    'total' => $maintenanceModel->count(),
                    'scheduled' => $maintenanceModel->count(['status' => 'scheduled']),
                    'in_progress' => $maintenanceModel->count(['status' => 'in_progress']),
                    'completed' => $maintenanceModel->count(['status' => 'completed'])
                ];
            } catch (Exception $e) {
                $stats['maintenance'] = ['total' => 0, 'scheduled' => 0, 'in_progress' => 0, 'completed' => 0];
            }
            
            try {
                $incidentModel = new IncidentModel();
                $stats['incidents'] = [
                    'total' => $incidentModel->count(),
                    'under_investigation' => $incidentModel->count(['status' => 'under_investigation']),
                    'verified' => $incidentModel->count(['status' => 'verified']),
                    'resolved' => $incidentModel->count(['status' => 'resolved'])
                ];
            } catch (Exception $e) {
                $stats['incidents'] = ['total' => 0, 'under_investigation' => 0, 'verified' => 0, 'resolved' => 0];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("ReportModel::getDashboardStatistics error: " . $e->getMessage());
            return [
                'assets' => ['total_assets' => 0, 'available' => 0, 'in_use' => 0],
                'projects' => ['total_projects' => 0, 'active_projects' => 0],
                'withdrawals' => ['total' => 0, 'pending' => 0],
                'transfers' => ['total' => 0, 'pending' => 0],
                'maintenance' => ['total' => 0, 'scheduled' => 0],
                'incidents' => ['total' => 0, 'under_investigation' => 0]
            ];
        }
    }
    
    /**
     * Get asset utilization report
     */
    public function getAssetUtilizationReport($dateFrom, $dateTo, $projectId = null, $categoryId = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }
            
            if ($categoryId) {
                $conditions[] = "a.category_id = ?";
                $params[] = $categoryId;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    a.id,
                    a.ref,
                    a.name,
                    a.status,
                    c.name as category_name,
                    p.name as project_name,
                    p.location as project_location,
                    COUNT(w.id) as withdrawal_count,
                    COUNT(CASE WHEN w.status = 'released' THEN 1 END) as active_withdrawals,
                    AVG(CASE WHEN w.actual_return IS NOT NULL 
                        THEN DATEDIFF(w.actual_return, w.created_at) END) as avg_usage_days,
                    MAX(w.created_at) as last_used,
                    DATEDIFF(CURDATE(), MAX(w.created_at)) as days_since_last_use
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN withdrawals w ON a.id = w.asset_id 
                    AND w.created_at BETWEEN ? AND ?
                {$whereClause}
                GROUP BY a.id, a.ref, a.name, a.status, c.name, p.name, p.location
                ORDER BY withdrawal_count DESC, days_since_last_use DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge([$dateFrom, $dateTo], $params));
            $utilizationData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get idle assets (not used in the period)
            $idleAssetsSql = "
                SELECT 
                    a.id,
                    a.ref,
                    a.name,
                    a.status,
                    c.name as category_name,
                    p.name as project_name,
                    DATEDIFF(CURDATE(), a.created_at) as days_since_acquisition
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                WHERE a.id NOT IN (
                    SELECT DISTINCT asset_id 
                    FROM withdrawals 
                    WHERE created_at BETWEEN ? AND ?
                )
                AND a.status = 'available'
                {$whereClause}
                ORDER BY days_since_acquisition DESC
            ";
            
            $stmt = $this->db->prepare($idleAssetsSql);
            $stmt->execute(array_merge([$dateFrom, $dateTo], $params));
            $idleAssets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'utilization_data' => $utilizationData,
                'idle_assets' => $idleAssets,
                'summary' => [
                    'total_assets' => count($utilizationData),
                    'active_assets' => count(array_filter($utilizationData, fn($a) => $a['withdrawal_count'] > 0)),
                    'idle_assets' => count($idleAssets),
                    'avg_utilization' => count($utilizationData) > 0 ? 
                        array_sum(array_column($utilizationData, 'withdrawal_count')) / count($utilizationData) : 0
                ]
            ];
            
        } catch (Exception $e) {
            error_log("ReportModel::getAssetUtilizationReport error: " . $e->getMessage());
            return ['utilization_data' => [], 'idle_assets' => [], 'summary' => []];
        }
    }
    
    /**
     * Get withdrawal report
     */
    public function getWithdrawalReport($dateFrom, $dateTo, $projectId = null, $status = null) {
        try {
            $conditions = ["w.created_at BETWEEN ? AND ?"];
            $params = [$dateFrom, $dateTo];
            
            if ($projectId) {
                $conditions[] = "w.project_id = ?";
                $params[] = $projectId;
            }
            
            if ($status) {
                $conditions[] = "w.status = ?";
                $params[] = $status;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT 
                    w.*,
                    a.ref as asset_ref,
                    a.name as asset_name,
                    c.name as category_name,
                    p.name as project_name,
                    p.location as project_location,
                    u.full_name as withdrawn_by_name,
                    ur.full_name as released_by_name,
                    CASE 
                        WHEN w.expected_return IS NOT NULL AND w.status = 'released' 
                             AND w.expected_return < CURDATE()
                        THEN DATEDIFF(CURDATE(), w.expected_return)
                        ELSE 0
                    END as days_overdue,
                    CASE 
                        WHEN w.actual_return IS NOT NULL 
                        THEN DATEDIFF(w.actual_return, w.created_at)
                        WHEN w.status = 'released'
                        THEN DATEDIFF(CURDATE(), w.created_at)
                        ELSE NULL
                    END as days_out
                FROM withdrawals w
                LEFT JOIN assets a ON w.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON w.project_id = p.id
                LEFT JOIN users u ON w.withdrawn_by = u.id
                LEFT JOIN releases r ON w.id = r.withdrawal_id
                LEFT JOIN users ur ON r.released_by = ur.id
                {$whereClause}
                ORDER BY w.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate summary statistics
            $summary = [
                'total_withdrawals' => count($withdrawals),
                'pending_withdrawals' => count(array_filter($withdrawals, fn($w) => $w['status'] === 'pending')),
                'released_withdrawals' => count(array_filter($withdrawals, fn($w) => $w['status'] === 'released')),
                'returned_withdrawals' => count(array_filter($withdrawals, fn($w) => $w['status'] === 'returned')),
                'overdue_withdrawals' => count(array_filter($withdrawals, fn($w) => $w['days_overdue'] > 0)),
                'avg_days_out' => count($withdrawals) > 0 ? 
                    array_sum(array_filter(array_column($withdrawals, 'days_out'))) / 
                    count(array_filter(array_column($withdrawals, 'days_out'))) : 0
            ];
            
            return [
                'withdrawals' => $withdrawals,
                'summary' => $summary
            ];
            
        } catch (Exception $e) {
            error_log("ReportModel::getWithdrawalReport error: " . $e->getMessage());
            return ['withdrawals' => [], 'summary' => []];
        }
    }
    
    /**
     * Get transfer report
     */
    public function getTransferReport($dateFrom, $dateTo, $fromProject = null, $toProject = null) {
        try {
            $conditions = ["t.created_at BETWEEN ? AND ?"];
            $params = [$dateFrom, $dateTo];
            
            if ($fromProject) {
                $conditions[] = "t.from_project = ?";
                $params[] = $fromProject;
            }
            
            if ($toProject) {
                $conditions[] = "t.to_project = ?";
                $params[] = $toProject;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT 
                    t.*,
                    a.ref as asset_ref,
                    a.name as asset_name,
                    c.name as category_name,
                    pf.name as from_project_name,
                    pf.location as from_project_location,
                    pt.name as to_project_name,
                    pt.location as to_project_location,
                    ui.full_name as initiated_by_name,
                    ua.full_name as approved_by_name,
                    DATEDIFF(COALESCE(t.approval_date, CURDATE()), t.created_at) as days_to_approval,
                    CASE 
                        WHEN t.status = 'completed' 
                        THEN DATEDIFF(t.updated_at, t.created_at)
                        ELSE DATEDIFF(CURDATE(), t.created_at)
                    END as days_in_process
                FROM transfers t
                LEFT JOIN assets a ON t.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users ui ON t.initiated_by = ui.id
                LEFT JOIN users ua ON t.approved_by = ua.id
                {$whereClause}
                ORDER BY t.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate summary statistics
            $summary = [
                'total_transfers' => count($transfers),
                'pending_transfers' => count(array_filter($transfers, fn($t) => $t['status'] === 'pending')),
                'approved_transfers' => count(array_filter($transfers, fn($t) => $t['status'] === 'approved')),
                'completed_transfers' => count(array_filter($transfers, fn($t) => $t['status'] === 'completed')),
                'canceled_transfers' => count(array_filter($transfers, fn($t) => $t['status'] === 'canceled')),
                'permanent_transfers' => count(array_filter($transfers, fn($t) => $t['transfer_type'] === 'permanent')),
                'temporary_transfers' => count(array_filter($transfers, fn($t) => $t['transfer_type'] === 'temporary')),
                'avg_approval_time' => count($transfers) > 0 ? 
                    array_sum(array_filter(array_column($transfers, 'days_to_approval'))) / 
                    count(array_filter(array_column($transfers, 'days_to_approval'))) : 0
            ];
            
            return [
                'transfers' => $transfers,
                'summary' => $summary
            ];
            
        } catch (Exception $e) {
            error_log("ReportModel::getTransferReport error: " . $e->getMessage());
            return ['transfers' => [], 'summary' => []];
        }
    }
    
    /**
     * Get maintenance report
     */
    public function getMaintenanceReport($dateFrom, $dateTo, $type = null, $status = null) {
        try {
            $conditions = ["m.scheduled_date BETWEEN ? AND ?"];
            $params = [$dateFrom, $dateTo];
            
            if ($type) {
                $conditions[] = "m.type = ?";
                $params[] = $type;
            }
            
            if ($status) {
                $conditions[] = "m.status = ?";
                $params[] = $status;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT 
                    m.*,
                    a.ref as asset_ref,
                    a.name as asset_name,
                    c.name as category_name,
                    p.name as project_name,
                    p.location as project_location,
                    CASE 
                        WHEN m.completed_date IS NOT NULL 
                        THEN DATEDIFF(m.completed_date, m.scheduled_date)
                        WHEN m.status IN ('scheduled', 'in_progress')
                        THEN DATEDIFF(CURDATE(), m.scheduled_date)
                        ELSE NULL
                    END as days_variance,
                    CASE 
                        WHEN m.scheduled_date < CURDATE() AND m.status = 'scheduled'
                        THEN DATEDIFF(CURDATE(), m.scheduled_date)
                        ELSE 0
                    END as days_overdue
                FROM maintenance m
                LEFT JOIN assets a ON m.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                {$whereClause}
                ORDER BY m.scheduled_date DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate cost analysis
            $totalEstimated = array_sum(array_column($maintenance, 'estimated_cost'));
            $totalActual = array_sum(array_filter(array_column($maintenance, 'actual_cost')));
            
            $summary = [
                'total_maintenance' => count($maintenance),
                'scheduled_maintenance' => count(array_filter($maintenance, fn($m) => $m['status'] === 'scheduled')),
                'in_progress_maintenance' => count(array_filter($maintenance, fn($m) => $m['status'] === 'in_progress')),
                'completed_maintenance' => count(array_filter($maintenance, fn($m) => $m['status'] === 'completed')),
                'overdue_maintenance' => count(array_filter($maintenance, fn($m) => $m['days_overdue'] > 0)),
                'preventive_maintenance' => count(array_filter($maintenance, fn($m) => $m['type'] === 'preventive')),
                'corrective_maintenance' => count(array_filter($maintenance, fn($m) => $m['type'] === 'corrective')),
                'emergency_maintenance' => count(array_filter($maintenance, fn($m) => $m['type'] === 'emergency')),
                'total_estimated_cost' => $totalEstimated,
                'total_actual_cost' => $totalActual,
                'cost_variance' => $totalActual - $totalEstimated
            ];
            
            return [
                'maintenance' => $maintenance,
                'summary' => $summary
            ];
            
        } catch (Exception $e) {
            error_log("ReportModel::getMaintenanceReport error: " . $e->getMessage());
            return ['maintenance' => [], 'summary' => []];
        }
    }
    
    /**
     * Get incident report
     */
    public function getIncidentReport($dateFrom, $dateTo, $type = null, $projectId = null) {
        try {
            $conditions = ["i.date_reported BETWEEN ? AND ?"];
            $params = [$dateFrom, $dateTo];
            
            if ($type) {
                $conditions[] = "i.type = ?";
                $params[] = $type;
            }
            
            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT 
                    i.*,
                    a.ref as asset_ref,
                    a.name as asset_name,
                    c.name as category_name,
                    p.name as project_name,
                    p.location as project_location,
                    ur.full_name as reported_by_name,
                    ures.full_name as resolved_by_name,
                    CASE 
                        WHEN i.resolution_date IS NOT NULL 
                        THEN DATEDIFF(i.resolution_date, i.date_reported)
                        WHEN i.status NOT IN ('resolved', 'closed')
                        THEN DATEDIFF(CURDATE(), i.date_reported)
                        ELSE NULL
                    END as days_to_resolution,
                    CASE 
                        WHEN i.status NOT IN ('resolved', 'closed') AND i.severity = 'critical'
                             AND DATEDIFF(CURDATE(), i.date_reported) > 1
                        THEN 1
                        WHEN i.status NOT IN ('resolved', 'closed') AND i.severity = 'high'
                             AND DATEDIFF(CURDATE(), i.date_reported) > 3
                        THEN 1
                        WHEN i.status NOT IN ('resolved', 'closed') AND i.severity = 'medium'
                             AND DATEDIFF(CURDATE(), i.date_reported) > 7
                        THEN 1
                        ELSE 0
                    END as is_overdue
                FROM incidents i
                LEFT JOIN assets a ON i.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN users ur ON i.reported_by = ur.id
                LEFT JOIN users ures ON i.resolved_by = ures.id
                {$whereClause}
                ORDER BY i.date_reported DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate summary statistics
            $summary = [
                'total_incidents' => count($incidents),
                'under_investigation' => count(array_filter($incidents, fn($i) => $i['status'] === 'under_investigation')),
                'verified_incidents' => count(array_filter($incidents, fn($i) => $i['status'] === 'verified')),
                'resolved_incidents' => count(array_filter($incidents, fn($i) => $i['status'] === 'resolved')),
                'closed_incidents' => count(array_filter($incidents, fn($i) => $i['status'] === 'closed')),
                'overdue_incidents' => count(array_filter($incidents, fn($i) => $i['is_overdue'] == 1)),
                'lost_incidents' => count(array_filter($incidents, fn($i) => $i['type'] === 'lost')),
                'damaged_incidents' => count(array_filter($incidents, fn($i) => $i['type'] === 'damaged')),
                'stolen_incidents' => count(array_filter($incidents, fn($i) => $i['type'] === 'stolen')),
                'critical_incidents' => count(array_filter($incidents, fn($i) => $i['severity'] === 'critical')),
                'avg_resolution_time' => count($incidents) > 0 ? 
                    array_sum(array_filter(array_column($incidents, 'days_to_resolution'))) / 
                    count(array_filter(array_column($incidents, 'days_to_resolution'))) : 0
            ];
            
            return [
                'incidents' => $incidents,
                'summary' => $summary
            ];
            
        } catch (Exception $e) {
            error_log("ReportModel::getIncidentReport error: " . $e->getMessage());
            return ['incidents' => [], 'summary' => []];
        }
    }
    
    /**
     * Get incident trends data for charts
     */
    public function getIncidentTrends($dateFrom, $dateTo) {
        try {
            $sql = "
                SELECT 
                    DATE_FORMAT(date_reported, '%Y-%m') as month,
                    COUNT(*) as incident_count,
                    COUNT(CASE WHEN type = 'lost' THEN 1 END) as lost_count,
                    COUNT(CASE WHEN type = 'damaged' THEN 1 END) as damaged_count,
                    COUNT(CASE WHEN type = 'stolen' THEN 1 END) as stolen_count,
                    COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_count
                FROM incidents
                WHERE date_reported BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(date_reported, '%Y-%m')
                ORDER BY month ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("ReportModel::getIncidentTrends error: " . $e->getMessage());
            return [];
        }
    }
}
?>
