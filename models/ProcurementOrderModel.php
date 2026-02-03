<?php
/**
 * ConstructLink™ Procurement Order Model
 * Handles multi-item procurement orders
 */

class ProcurementOrderModel extends BaseModel {
    protected $table = 'procurement_orders';
    protected $fillable = [
        'po_number', 'request_id', 'vendor_id', 'project_id', 'title', 'quote_file',
        'quotation_number', 'quotation_date',
        'delivery_status', 'delivery_date', 'scheduled_delivery_date', 'actual_delivery_date',
        'delivery_method', 'delivery_location', 'tracking_number', 'delivery_notes',
        'package_scope', 'work_breakdown', 'budget_allocation', 'justification', 
        'subtotal', 'vat_rate', 'vat_amount', 'ewt_rate', 'ewt_amount', 
        'handling_fee', 'discount_amount', 'net_total', 'status', 'requested_by', 
        'approved_by', 'scheduled_by', 'delivered_by', 'received_by', 'received_at',
        'scheduled_at', 'delivered_at', 'date_needed', 'notes', 'quality_check_notes',
        'delivery_discrepancy_notes', 'is_retroactive', 'retroactive_reason',
        'purchase_receipt_file', 'supporting_evidence_file', 'file_upload_notes',
        'retroactive_current_state', 'retroactive_target_status'
    ];

    /**
     * Create procurement order with items
     */
    public function createProcurementOrder($orderData, $items = []) {
        try {
            $this->beginTransaction();

            // Generate PO number if not provided
            if (empty($orderData['po_number'])) {
                $orderData['po_number'] = $this->generatePONumber();
            }

            // If linked to a request, validate and link
            if (!empty($orderData['request_id'])) {
                $requestModel = new RequestModel();
                $canProcure = $requestModel->canBeProcured($orderData['request_id']);
                
                if (!$canProcure['can_procure']) {
                    $this->rollback();
                    return ['success' => false, 'message' => $canProcure['reason']];
                }
            }

            // Calculate totals from items
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += ($item['quantity'] * $item['unit_price']);
            }

            $orderData['subtotal'] = $subtotal;
            $orderData['vat_amount'] = $subtotal * (($orderData['vat_rate'] ?? 12) / 100);
            $orderData['ewt_amount'] = $subtotal * (($orderData['ewt_rate'] ?? 2) / 100);
            $orderData['net_total'] = $subtotal + $orderData['vat_amount'] - $orderData['ewt_amount'] + 
                                     ($orderData['handling_fee'] ?? 0) - ($orderData['discount_amount'] ?? 0);

            // Create procurement order
            $procurementOrder = $this->create($orderData);
            if (!$procurementOrder) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create procurement order'];
            }

            // Create procurement items
            $procurementItemModel = new ProcurementItemModel();
            foreach ($items as $item) {
                $item['procurement_order_id'] = $procurementOrder['id'];
                $createdItem = $procurementItemModel->create($item);
                if (!$createdItem) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Failed to create procurement item'];
                }
            }

            // Link to request if provided
            if (!empty($orderData['request_id'])) {
                $requestModel = new RequestModel();
                $linkResult = $requestModel->linkToProcurementOrder($orderData['request_id'], $procurementOrder['id']);
                
                if (!$linkResult['success']) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Failed to link request: ' . $linkResult['message']];
                }
                
                // Log the request linkage
                $this->logProcurementActivity($procurementOrder['id'], $orderData['requested_by'], 'request_linked', 
                    null, null, "Linked to request ID: {$orderData['request_id']}");
            }

            // Log activity
            $this->logActivity('procurement_order_created', 'Procurement order created: ' . $orderData['po_number'], 'procurement_orders', $procurementOrder['id']);

            // Send email notification if status is Pending
            if ($procurementOrder['status'] === 'Pending') {
                $this->sendProcurementNotification($procurementOrder['id'], 'created', $orderData['requested_by']);
            }

            $this->commit();
            return ['success' => true, 'procurement_order' => $procurementOrder];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Create procurement order error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create procurement order'];
        }
    }

    /**
     * Get procurement orders with filters and pagination
     */
    public function getProcurementOrdersWithFilters($filters = [], $page = 1, $perPage = 20) {
        try {
            $conditions = [];
            $params = [];

            // Apply filters
            if (!empty($filters['status'])) {
                $conditions[] = "po.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['project_id'])) {
                $conditions[] = "po.project_id = ?";
                $params[] = $filters['project_id'];
            }

            if (!empty($filters['vendor_id'])) {
                $conditions[] = "po.vendor_id = ?";
                $params[] = $filters['vendor_id'];
            }

            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(po.created_at) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(po.created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            if (!empty($filters['search'])) {
                $conditions[] = "(po.po_number LIKE ? OR po.title LIKE ? OR v.name LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            // Apply project assignment filtering
            if (!empty($filters['assigned_project_ids'])) {
                $placeholders = str_repeat('?,', count($filters['assigned_project_ids']) - 1) . '?';
                $conditions[] = "po.project_id IN ({$placeholders})";
                $params = array_merge($params, $filters['assigned_project_ids']);
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            // Count total records
            $countSql = "
                SELECT COUNT(*) 
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id
                {$whereClause}
            ";

            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();

            // Get paginated data
            $offset = ($page - 1) * $perPage;
            $orderBy = $filters['order_by'] ?? 'po.created_at DESC';

            $dataSql = "
                SELECT po.*, 
                       v.name as vendor_name,
                       p.name as project_name, p.code as project_code,
                       u.full_name as requested_by_name,
                       ua.full_name as approved_by_name,
                       ur.full_name as received_by_name,
                       (SELECT COUNT(*) FROM procurement_items pi WHERE pi.procurement_order_id = po.id) as item_count
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN projects p ON po.project_id = p.id
                LEFT JOIN users u ON po.requested_by = u.id
                LEFT JOIN users ua ON po.approved_by = ua.id
                LEFT JOIN users ur ON po.received_by = ur.id
                {$whereClause}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}
            ";

            $stmt = $this->db->prepare($dataSql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();

            return [
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                    'has_next' => $page < ceil($total / $perPage),
                    'has_prev' => $page > 1
                ]
            ];

        } catch (Exception $e) {
            error_log("Get procurement orders with filters error: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_next' => false,
                    'has_prev' => false
                ]
            ];
        }
    }

    /**
     * Get procurement order with items and details
     */
    public function getProcurementOrderWithItems($id) {
        try {
            $sql = "
                SELECT po.*, 
                       v.name as vendor_name, v.contact_person as vendor_contact,
                       v.phone as vendor_phone, v.email as vendor_email,
                       p.name as project_name, p.code as project_code,
                       u.full_name as requested_by_name,
                       ua.full_name as approved_by_name,
                       ur.full_name as received_by_name
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN projects p ON po.project_id = p.id
                LEFT JOIN users u ON po.requested_by = u.id
                LEFT JOIN users ua ON po.approved_by = ua.id
                LEFT JOIN users ur ON po.received_by = ur.id
                WHERE po.id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                return null;
            }

            // Get items
            $itemsSql = "
                SELECT pi.*, c.name as category_name
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                WHERE pi.procurement_order_id = ?
                ORDER BY pi.id
            ";

            $stmt = $this->db->prepare($itemsSql);
            $stmt->execute([$id]);
            $order['items'] = $stmt->fetchAll();

            return $order;

        } catch (Exception $e) {
            error_log("Get procurement order with items error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update procurement order status
     */
    public function updateStatus($id, $status, $userId = null, $notes = null) {
        try {
            $this->beginTransaction();

            $updateData = ['status' => $status];
            
            if ($status === 'Approved' && $userId) {
                $updateData['approved_by'] = $userId;
            } elseif ($status === 'Received' && $userId) {
                $updateData['received_by'] = $userId;
                $updateData['received_at'] = date('Y-m-d H:i:s');
            }

            if ($notes) {
                $updateData['notes'] = $notes;
            }

            $result = $this->update($id, $updateData);
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update status'];
            }

            // Log the status change
            $this->logProcurementActivity($id, $userId, 'status_change', null, $status, "Status changed to {$status}");

            // Send email notification based on status change
            $this->sendProcurementNotification($id, 'status_changed', $userId, $status);

            $this->commit();
            return ['success' => true, 'message' => 'Status updated successfully'];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Update procurement order status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update status'];
        }
    }

    /**
     * Generate PO number
     */
    private function generatePONumber() {
        try {
            // Get current sequence from system settings
            $settingsModel = new SystemSettingsModel();
            $prefix = $settingsModel->getSetting('po_prefix', 'PO');
            $sequence = (int)$settingsModel->getSetting('current_po_sequence', 1);

            $poNumber = $prefix . date('Y') . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // Update sequence
            $settingsModel->updateSetting('current_po_sequence', $sequence + 1);

            return $poNumber;

        } catch (Exception $e) {
            error_log("Generate PO number error: " . $e->getMessage());
            return 'PO' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Get procurement statistics
     */
    public function getProcurementStatistics($projectId = null, $dateFrom = null, $dateTo = null) {
        try {
            $conditions = [];
            $params = [];

            if ($projectId) {
                $conditions[] = "project_id = ?";
                $params[] = $projectId;
            }

            if ($dateFrom) {
                $conditions[] = "DATE(created_at) >= ?";
                $params[] = $dateFrom;
            }

            if ($dateTo) {
                $conditions[] = "DATE(created_at) <= ?";
                $params[] = $dateTo;
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Received' THEN 1 ELSE 0 END) as received,
                    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(net_total) as total_value,
                    AVG(net_total) as average_value
                FROM procurement_orders 
                {$whereClause}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            return $result ?: [
                'total_orders' => 0,
                'draft' => 0,
                'pending' => 0,
                'approved' => 0,
                'received' => 0,
                'rejected' => 0,
                'total_value' => 0,
                'average_value' => 0
            ];

        } catch (Exception $e) {
            error_log("Get procurement statistics error: " . $e->getMessage());
            return [
                'total_orders' => 0,
                'draft' => 0,
                'pending' => 0,
                'approved' => 0,
                'received' => 0,
                'rejected' => 0,
                'total_value' => 0,
                'average_value' => 0
            ];
        }
    }

    /**
     * Log procurement activity
     */
    public function logProcurementActivity($procurementOrderId, $userId, $action, $oldStatus = null, $newStatus = null, $description = null) {
        try {
            $sql = "INSERT INTO procurement_logs (procurement_order_id, user_id, action, old_status, new_status, description, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId, $userId, $action, $oldStatus, $newStatus, $description]);
        } catch (Exception $e) {
            error_log("Log procurement activity error: " . $e->getMessage());
        }
    }

    /**
     * Get received procurement orders for asset generation
     */
    public function getReceivedOrders($assignedProjectIds = null) {
        try {
            // Include orders that are received OR delivered (for discrepancy handling)
            $conditions = ["(po.status = 'Received' OR po.delivery_status = 'Delivered')"];
            $params = [];
            
            // Apply project assignment filtering if provided
            if ($assignedProjectIds !== null && !empty($assignedProjectIds)) {
                $placeholders = str_repeat('?,', count($assignedProjectIds) - 1) . '?';
                $conditions[] = "po.project_id IN ({$placeholders})";
                $params = array_merge($params, $assignedProjectIds);
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT po.*, 
                       v.name as vendor_name,
                       p.name as project_name,
                       (SELECT COUNT(*) FROM procurement_items pi WHERE pi.procurement_order_id = po.id) as item_count,
                       po.net_total as total_value,
                       (SELECT COUNT(*) FROM procurement_items pi 
                        WHERE pi.procurement_order_id = po.id 
                        AND (COALESCE(pi.quantity_received, pi.quantity) - COALESCE(
                            (SELECT COUNT(*) FROM inventory_items a WHERE a.procurement_item_id = pi.id), 0
                        )) > 0) as items_available_for_generation
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN projects p ON po.project_id = p.id
                {$whereClause}
                HAVING items_available_for_generation > 0
                ORDER BY po.received_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get received orders error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get procurement order items for asset generation
     */
    public function getOrderItems($orderId) {
        try {
            $sql = "
                SELECT pi.*, c.name as category_name,
                       po.project_id, po.vendor_id,
                       (pi.quantity_received - COALESCE(
                           (SELECT COUNT(*) FROM procurement_inventory pi_inv WHERE pi_inv.procurement_item_id = pi.id), 0
                       )) as available_for_generation
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                LEFT JOIN procurement_orders po ON pi.procurement_order_id = po.id
                WHERE pi.procurement_order_id = ?
                  AND pi.delivery_status IN ('Complete', 'Partial')
                  AND pi.quantity_received > 0
                ORDER BY pi.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get order items error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get items available for asset generation (excluding already generated)
     * Enhanced to respect business category rules
     */
    public function getAvailableItemsForAssetGeneration($orderId) {
        try {
            $sql = "
                SELECT pi.*, 
                       c.name as category_name,
                       c.generates_assets,
                       c.asset_type,
                       c.capitalization_threshold,
                       c.auto_expense_below_threshold,
                       c.depreciation_applicable,
                       po.project_id, po.vendor_id,
                       (pi.quantity_received - COALESCE(
                           (SELECT COUNT(*) FROM procurement_inventory pi_inv WHERE pi_inv.procurement_item_id = pi.id), 0
                       )) as available_for_generation,
                       CASE 
                           WHEN c.generates_assets = 0 THEN 'expense_only'
                           WHEN c.generates_assets = 1 AND c.capitalization_threshold > 0 
                                AND pi.unit_price < c.capitalization_threshold 
                                AND c.auto_expense_below_threshold = 1 THEN 'below_threshold'
                           ELSE 'eligible'
                       END as asset_generation_status
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                LEFT JOIN procurement_orders po ON pi.procurement_order_id = po.id
                WHERE pi.procurement_order_id = ?
                  AND pi.delivery_status IN ('Complete', 'Partial')
                  AND pi.quantity_received > 0
                  AND c.generates_assets = 1  -- Only asset-generating categories
                  AND (c.capitalization_threshold = 0 
                       OR pi.unit_price >= c.capitalization_threshold 
                       OR c.auto_expense_below_threshold = 0)  -- Respect threshold rules
                HAVING available_for_generation > 0
                ORDER BY pi.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get available items for asset generation error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get non-asset-generating items (for expense processing)
     */
    public function getNonAssetGeneratingItems($orderId) {
        try {
            $sql = "
                SELECT pi.*, 
                       c.name as category_name,
                       c.generates_assets,
                       c.asset_type,
                       c.expense_category,
                       c.business_description,
                       po.project_id, po.vendor_id,
                       CASE c.expense_category
                           WHEN 'professional_services' THEN 'Professional Services'
                           WHEN 'maintenance' THEN 'Maintenance & Repair'
                           WHEN 'operating' THEN 'Operating Expenses'
                           WHEN 'regulatory' THEN 'Regulatory & Compliance'
                           ELSE 'Other Expenses'
                       END as expense_type_display
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                LEFT JOIN procurement_orders po ON pi.procurement_order_id = po.id
                WHERE pi.procurement_order_id = ?
                  AND pi.delivery_status IN ('Complete', 'Partial')
                  AND pi.quantity_received > 0
                  AND c.generates_assets = 0  -- Only non-asset-generating categories
                ORDER BY c.expense_category, pi.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get non-asset generating items error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get items below capitalization threshold (for auto-expensing)
     */
    public function getItemsBelowThreshold($orderId) {
        try {
            $sql = "
                SELECT pi.*, 
                       c.name as category_name,
                       c.generates_assets,
                       c.asset_type,
                       c.capitalization_threshold,
                       c.auto_expense_below_threshold,
                       po.project_id, po.vendor_id,
                       (pi.unit_price - c.capitalization_threshold) as threshold_variance
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                LEFT JOIN procurement_orders po ON pi.procurement_order_id = po.id
                WHERE pi.procurement_order_id = ?
                  AND pi.delivery_status IN ('Complete', 'Partial')
                  AND pi.quantity_received > 0
                  AND c.generates_assets = 1  -- Asset-generating category
                  AND c.capitalization_threshold > 0  -- Has threshold
                  AND pi.unit_price < c.capitalization_threshold  -- Below threshold
                  AND c.auto_expense_below_threshold = 1  -- Auto-expense enabled
                ORDER BY pi.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get items below threshold error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if procurement order is completely processed (all items handled appropriately)
     */
    public function isOrderCompletelyProcessed($orderId) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_items,
                    COUNT(CASE 
                        WHEN c.generates_assets = 1 
                             AND (c.capitalization_threshold = 0 
                                  OR pi.unit_price >= c.capitalization_threshold 
                                  OR c.auto_expense_below_threshold = 0)
                        THEN 1 
                    END) as asset_eligible_items,
                    COUNT(CASE 
                        WHEN c.generates_assets = 0 
                             OR (c.generates_assets = 1 
                                 AND c.capitalization_threshold > 0 
                                 AND pi.unit_price < c.capitalization_threshold 
                                 AND c.auto_expense_below_threshold = 1)
                        THEN 1 
                    END) as expense_items,
                    COALESCE(SUM(
                        CASE
                            WHEN c.generates_assets = 1
                                 AND (c.capitalization_threshold = 0
                                      OR pi.unit_price >= c.capitalization_threshold
                                      OR c.auto_expense_below_threshold = 0)
                            THEN (SELECT COUNT(*) FROM procurement_inventory pi_inv WHERE pi_inv.procurement_item_id = pi.id)
                            ELSE 0
                        END
                    ), 0) as assets_generated,
                    -- Check if all expense items are marked as processed (implementation dependent)
                    COUNT(CASE 
                        WHEN (c.generates_assets = 0 
                              OR (c.generates_assets = 1 
                                  AND c.capitalization_threshold > 0 
                                  AND pi.unit_price < c.capitalization_threshold 
                                  AND c.auto_expense_below_threshold = 1))
                             AND pi.expense_processed = 1
                        THEN 1 
                    END) as expenses_processed
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                WHERE pi.procurement_order_id = ?
                  AND pi.delivery_status IN ('Complete', 'Partial')
                  AND pi.quantity_received > 0
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return ['is_complete' => false, 'reason' => 'No processed items found'];
            }
            
            $assetEligibleItems = (int)$result['asset_eligible_items'];
            $assetsGenerated = (int)$result['assets_generated'];
            $expenseItems = (int)$result['expense_items'];
            $expensesProcessed = (int)$result['expenses_processed'];
            
            $assetProcessingComplete = ($assetEligibleItems == $assetsGenerated);
            $expenseProcessingComplete = ($expenseItems == $expensesProcessed);
            
            return [
                'is_complete' => ($assetProcessingComplete && $expenseProcessingComplete),
                'asset_processing_complete' => $assetProcessingComplete,
                'expense_processing_complete' => $expenseProcessingComplete,
                'stats' => [
                    'total_items' => (int)$result['total_items'],
                    'asset_eligible_items' => $assetEligibleItems,
                    'assets_generated' => $assetsGenerated,
                    'expense_items' => $expenseItems,
                    'expenses_processed' => $expensesProcessed
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Check order processing completion error: " . $e->getMessage());
            return ['is_complete' => false, 'reason' => 'Database error'];
        }
    }

    /**
     * Get comprehensive procurement order processing status
     */
    public function getProcurementProcessingStatus($orderId) {
        try {
            $sql = "
                SELECT 
                    po.*,
                    COUNT(pi.id) as total_items,
                    COUNT(CASE WHEN pi.delivery_status = 'Complete' THEN 1 END) as completed_items,
                    COUNT(CASE WHEN pi.delivery_status = 'Partial' THEN 1 END) as partial_items,
                    COUNT(CASE WHEN pi.delivery_status = 'Pending' THEN 1 END) as pending_items,
                    COUNT(CASE 
                        WHEN c.generates_assets = 1 
                             AND (c.capitalization_threshold = 0 
                                  OR pi.unit_price >= c.capitalization_threshold 
                                  OR c.auto_expense_below_threshold = 0)
                        THEN 1 
                    END) as asset_generating_items,
                    COUNT(CASE 
                        WHEN c.generates_assets = 0 
                             OR (c.generates_assets = 1 
                                 AND c.capitalization_threshold > 0 
                                 AND pi.unit_price < c.capitalization_threshold 
                                 AND c.auto_expense_below_threshold = 1)
                        THEN 1 
                    END) as expense_only_items,
                    v.name as vendor_name,
                    p.name as project_name
                FROM procurement_orders po
                LEFT JOIN procurement_items pi ON po.id = pi.procurement_order_id
                LEFT JOIN categories c ON pi.category_id = c.id
                LEFT JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN projects p ON po.project_id = p.id
                WHERE po.id = ?
                GROUP BY po.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            $orderData = $stmt->fetch();
            
            if (!$orderData) {
                return null;
            }
            
            // Get detailed item breakdown
            $orderData['asset_eligible_items'] = $this->getAvailableItemsForAssetGeneration($orderId);
            $orderData['expense_items'] = $this->getNonAssetGeneratingItems($orderId);
            $orderData['below_threshold_items'] = $this->getItemsBelowThreshold($orderId);
            $orderData['processing_status'] = $this->isOrderCompletelyProcessed($orderId);
            
            return $orderData;
            
        } catch (Exception $e) {
            error_log("Get procurement processing status error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cancel procurement order
     */
    public function cancelOrder($id, $reason, $userId = null) {
        try {
            $this->beginTransaction();

            // Get current order
            $order = $this->find($id);
            if (!$order) {
                $this->rollback();
                return ['success' => false, 'message' => 'Procurement order not found'];
            }

            // Check if order can be canceled
            if (!in_array($order['status'], ['Draft', 'Pending', 'Reviewed'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Cannot cancel order in current status'];
            }

            // Update order status
            $updateData = [
                'status' => 'Rejected', // Using 'Rejected' as it's already in the schema
                'notes' => $reason
            ];

            $result = $this->update($id, $updateData);
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to cancel order'];
            }

            // Log the cancellation
            $this->logProcurementActivity($id, $userId, 'order_canceled', $order['status'], 'Rejected', "Order canceled: {$reason}");

            $this->commit();
            return ['success' => true, 'message' => 'Order canceled successfully'];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Cancel procurement order error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to cancel order'];
        }
    }

    /**
     * Create procurement order from approved request
     */
    public function createFromRequest($requestId, $orderData, $items = []) {
        try {
            // Validate request
            $requestModel = new RequestModel();
            $canProcure = $requestModel->canBeProcured($requestId);
            
            if (!$canProcure['can_procure']) {
                return ['success' => false, 'message' => $canProcure['reason']];
            }
            
            $request = $canProcure['request'];
            
            // Set request-specific data
            $orderData['request_id'] = $requestId;
            $orderData['project_id'] = $request['project_id'];
            
            // If title not provided, use request description
            if (empty($orderData['title'])) {
                $orderData['title'] = 'PO for Request: ' . $request['description'];
            }
            
            // If date_needed not provided, use request date_needed
            if (empty($orderData['date_needed']) && !empty($request['date_needed'])) {
                $orderData['date_needed'] = $request['date_needed'];
            }
            
            // Create the procurement order with request linkage (this handles its own transaction)
            $result = $this->createProcurementOrder($orderData, $items);
            
            if (!$result['success']) {
                return $result;
            }
            
            // Log the creation from request (outside of transaction since createProcurementOrder already committed)
            try {
                $this->logProcurementActivity($result['procurement_order']['id'], $orderData['requested_by'], 
                    'created_from_request', null, null, "Procurement order created from request ID: {$requestId}");
            } catch (Exception $logError) {
                // Log error but don't fail the whole process
                error_log("Failed to log procurement activity: " . $logError->getMessage());
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Create procurement order from request error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create procurement order from request'];
        }
    }
    
    /**
     * Get procurement orders with request information
     */
    public function getProcurementOrdersWithRequests($filters = [], $page = 1, $perPage = 20) {
        try {
            $conditions = [];
            $params = [];

            // Apply filters
            if (!empty($filters['status'])) {
                $conditions[] = "po.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['project_id'])) {
                $conditions[] = "po.project_id = ?";
                $params[] = $filters['project_id'];
            }

            if (!empty($filters['vendor_id'])) {
                $conditions[] = "po.vendor_id = ?";
                $params[] = $filters['vendor_id'];
            }
            
            if (!empty($filters['has_request'])) {
                if ($filters['has_request'] === 'yes') {
                    $conditions[] = "po.request_id IS NOT NULL";
                } elseif ($filters['has_request'] === 'no') {
                    $conditions[] = "po.request_id IS NULL";
                }
            }

            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(po.created_at) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(po.created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            if (!empty($filters['search'])) {
                $conditions[] = "(po.po_number LIKE ? OR po.title LIKE ? OR v.name LIKE ? OR r.description LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            // Count total records
            $countSql = "
                SELECT COUNT(*) 
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN requests r ON po.request_id = r.id
                {$whereClause}
            ";

            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();

            // Get paginated data
            $offset = ($page - 1) * $perPage;
            $orderBy = $filters['order_by'] ?? 'po.created_at DESC';

            $dataSql = "
                SELECT po.*, 
                       v.name as vendor_name,
                       p.name as project_name, p.code as project_code,
                       u.full_name as requested_by_name,
                       ua.full_name as approved_by_name,
                       ur.full_name as received_by_name,
                       r.description as request_description,
                       r.urgency as request_urgency,
                       r.request_type,
                       ur_req.full_name as request_requested_by_name,
                       (SELECT COUNT(*) FROM procurement_items pi WHERE pi.procurement_order_id = po.id) as item_count
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN projects p ON po.project_id = p.id
                LEFT JOIN users u ON po.requested_by = u.id
                LEFT JOIN users ua ON po.approved_by = ua.id
                LEFT JOIN users ur ON po.received_by = ur.id
                LEFT JOIN requests r ON po.request_id = r.id
                LEFT JOIN users ur_req ON r.requested_by = ur_req.id
                {$whereClause}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}
            ";

            $stmt = $this->db->prepare($dataSql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();

            return [
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                    'has_next' => $page < ceil($total / $perPage),
                    'has_prev' => $page > 1
                ]
            ];

        } catch (Exception $e) {
            error_log("Get procurement orders with requests error: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_next' => false,
                    'has_prev' => false
                ]
            ];
        }
    }
    
    /**
     * Schedule delivery for approved procurement order
     */
    public function scheduleDelivery($id, $deliveryData, $userId) {
        try {
            $this->beginTransaction();
            
            // Get current order
            $order = $this->find($id);
            if (!$order) {
                $this->rollback();
                return ['success' => false, 'message' => 'Procurement order not found'];
            }
            
            if ($order['status'] !== 'Approved') {
                $this->rollback();
                return ['success' => false, 'message' => 'Only approved orders can be scheduled for delivery'];
            }
            
            // Update procurement order with delivery information
            $updateData = [
                'status' => 'Scheduled for Delivery',
                'delivery_status' => 'Scheduled',
                'scheduled_delivery_date' => $deliveryData['scheduled_date'],
                'delivery_method' => $deliveryData['delivery_method'],
                'delivery_location' => $deliveryData['delivery_location'],
                'tracking_number' => $deliveryData['tracking_number'] ?? null,
                'delivery_notes' => $deliveryData['notes'] ?? null,
                'scheduled_by' => $userId,
                'scheduled_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->update($id, $updateData);
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to schedule delivery'];
            }
            
            // Create delivery tracking record
            $trackingData = [
                'procurement_order_id' => $id,
                'status' => 'Scheduled',
                'tracking_number' => $deliveryData['tracking_number'] ?? null,
                'delivery_method' => $deliveryData['delivery_method'],
                'delivery_location' => $deliveryData['delivery_location'],
                'scheduled_date' => $deliveryData['scheduled_date'],
                'updated_by' => $userId,
                'notes' => $deliveryData['notes'] ?? null
            ];
            
            $this->createDeliveryTracking($trackingData);
            
            // Log the activity
            $this->logProcurementActivity($id, $userId, 'delivery_scheduled', 'Approved', 'Scheduled for Delivery', 
                "Delivery scheduled for {$deliveryData['scheduled_date']} via {$deliveryData['delivery_method']}");
            
            // Notify stakeholders
            $this->notifyStakeholders($id, 'delivery_scheduled', 
                "Delivery has been scheduled for PO #{$order['po_number']} on {$deliveryData['scheduled_date']}", 
                [
                    'scheduled_date' => $deliveryData['scheduled_date'],
                    'delivery_method' => $deliveryData['delivery_method'],
                    'delivery_location' => $deliveryData['delivery_location']
                ]);
            
            $this->commit();
            return ['success' => true, 'message' => 'Delivery scheduled successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Schedule delivery error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to schedule delivery'];
        }
    }
    
    /**
     * Update delivery status (In Transit, Delivered)
     */
    public function updateDeliveryStatus($id, $status, $userId, $notes = null, $actualDate = null) {
        try {
            $this->beginTransaction();
            
            // Get current order
            $order = $this->find($id);
            if (!$order) {
                $this->rollback();
                return ['success' => false, 'message' => 'Procurement order not found'];
            }
            
            // Update procurement order
            $updateData = [
                'delivery_status' => $status,
                'status' => $status === 'Delivered' ? 'Delivered' : ($status === 'In Transit' ? 'In Transit' : $order['status'])
            ];
            
            if ($status === 'Delivered' && $actualDate) {
                $updateData['actual_delivery_date'] = $actualDate;
                $updateData['delivered_by'] = $userId;
                $updateData['delivered_at'] = date('Y-m-d H:i:s');
            }
            
            $result = $this->update($id, $updateData);
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update delivery status'];
            }
            
            // Create delivery tracking record
            $trackingData = [
                'procurement_order_id' => $id,
                'status' => $status,
                'actual_date' => $actualDate,
                'updated_by' => $userId,
                'notes' => $notes
            ];
            
            $this->createDeliveryTracking($trackingData);
            
            // Log the activity
            $this->logProcurementActivity($id, $userId, 'delivery_status_updated', $order['delivery_status'], $status, 
                "Delivery status updated to {$status}" . ($notes ? ": {$notes}" : ''));
            
            $this->commit();
            return ['success' => true, 'message' => 'Delivery status updated successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Update delivery status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update delivery status'];
        }
    }
    
    /**
     * Confirm receipt of delivery (by warehouseman)
     */
    public function confirmReceipt($id, $receiptData, $userId) {
        try {
            $this->beginTransaction();
            
            // Get current order
            $order = $this->find($id);
            if (!$order) {
                $this->rollback();
                return ['success' => false, 'message' => 'Procurement order not found'];
            }
            
            // Prevent receipt confirmation for orders that are already received
            if ($order['status'] === 'Received') {
                $this->rollback();
                return ['success' => false, 'message' => 'Order has already been received'];
            }
            
            // Check if order is in a valid state for receipt confirmation
            // Valid states: Approved (direct delivery), Scheduled for Delivery, In Transit, Delivered
            $validStatuses = ['Approved', 'Scheduled for Delivery', 'In Transit', 'Delivered'];
            if (!in_array($order['status'], $validStatuses)) {
                $this->rollback();
                return ['success' => false, 'message' => 'Order must be approved and in delivery process to confirm receipt. Current status: ' . $order['status']];
            }
            
            // Check for manual discrepancy flag
            $hasDiscrepancy = !empty($receiptData['has_discrepancy']) && $receiptData['has_discrepancy'] === 'yes';
            
            // Check for automatic discrepancy based on quantities and create item-level discrepancy notes
            if (!$hasDiscrepancy) {
                // Get all items and check if any have partial quantities
                $stmt = $this->db->prepare("
                    SELECT pi.*, pi.quantity_received 
                    FROM procurement_items pi 
                    WHERE pi.procurement_order_id = ?
                ");
                $stmt->execute([$id]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $itemsWithDiscrepancies = [];
                foreach ($items as $item) {
                    if ($item['quantity_received'] < $item['quantity']) {
                        $hasDiscrepancy = true;
                        $shortfall = $item['quantity'] - $item['quantity_received'];
                        $discrepancyNote = "Quantity discrepancy: Ordered {$item['quantity']}, received {$item['quantity_received']} (shortfall: {$shortfall})";
                        
                        // Update item with discrepancy details
                        $stmt2 = $this->db->prepare("
                            UPDATE procurement_items 
                            SET discrepancy_notes = ?, discrepancy_type = 'Quantity Mismatch' 
                            WHERE id = ?
                        ");
                        $stmt2->execute([$discrepancyNote, $item['id']]);
                        
                        $itemsWithDiscrepancies[] = $item['item_name'];
                    }
                }
                
                if ($hasDiscrepancy && empty($receiptData['discrepancy_details'])) {
                    $receiptData['discrepancy_details'] = "Quantity discrepancy detected for items: " . implode(', ', $itemsWithDiscrepancies);
                }
            }
            
            // Update procurement order
            $updateData = [
                'status' => 'Received',
                'delivery_status' => $hasDiscrepancy ? 'Partial' : 'Received',
                'received_by' => $userId,
                'received_at' => date('Y-m-d H:i:s'),
                'quality_check_notes' => $receiptData['quality_notes'] ?? null
            ];
            
            if ($hasDiscrepancy) {
                $updateData['delivery_discrepancy_notes'] = $receiptData['discrepancy_details'] ?? null;
            }
            
            $result = $this->update($id, $updateData);
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to confirm receipt'];
            }
            
            // Create delivery tracking record
            $trackingData = [
                'procurement_order_id' => $id,
                'status' => $hasDiscrepancy ? 'Discrepancy Reported' : 'Received',
                'actual_date' => date('Y-m-d'),
                'updated_by' => $userId,
                'notes' => $receiptData['receipt_notes'] ?? null
            ];
            
            if ($hasDiscrepancy) {
                $trackingData['discrepancy_type'] = $receiptData['discrepancy_type'] ?? null;
                $trackingData['discrepancy_details'] = $receiptData['discrepancy_details'] ?? null;
            }
            
            $this->createDeliveryTracking($trackingData);
            
            // Log the activity
            $this->logProcurementActivity($id, $userId, 'receipt_confirmed', $order['status'], 'Received', 
                $hasDiscrepancy ? 'Receipt confirmed with discrepancies' : 'Receipt confirmed - all items received as expected');
            
            // Notify stakeholders about receipt confirmation
            $this->notifyStakeholders($id, 'receipt_confirmed', $hasDiscrepancy);
            
            // If there's a discrepancy, send additional notification to key stakeholders
            if ($hasDiscrepancy) {
                $this->notifyStakeholders($id, 'discrepancy_reported');
            }
            
            $this->commit();
            return ['success' => true, 'message' => 'Receipt confirmed successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Confirm receipt error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to confirm receipt'];
        }
    }
    
    /**
     * Create delivery tracking record
     */
    private function createDeliveryTracking($data) {
        try {
            $sql = "INSERT INTO delivery_tracking (
                procurement_order_id, status, tracking_number, delivery_method, 
                delivery_location, scheduled_date, actual_date, updated_by, notes,
                discrepancy_type, discrepancy_details, resolution_notes, resolved_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            // Ensure date fields are properly handled - convert empty strings to NULL
            $scheduledDate = !empty($data["scheduled_date"]) ? $data["scheduled_date"] : null;
            $actualDate = !empty($data["actual_date"]) ? $data["actual_date"] : null;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data["procurement_order_id"],
                $data["status"],
                $data["tracking_number"] ?? null,
                $data["delivery_method"] ?? null,
                $data["delivery_location"] ?? null,
                $scheduledDate,
                $actualDate,
                $data["updated_by"],
                $data["notes"] ?? null,
                $data["discrepancy_type"] ?? null,
                $data["discrepancy_details"] ?? null,
                $data["resolution_notes"] ?? null,
                $data["resolved_by"] ?? null
            ]);
            
        } catch (Exception $e) {
            error_log("Create delivery tracking error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get delivery tracking history for a procurement order
     */
    public function getDeliveryTracking($procurementOrderId) {
        try {
            $sql = "
                SELECT dt.*, 
                       u1.full_name as updated_by_name,
                       u2.full_name as resolved_by_name
                FROM delivery_tracking dt
                LEFT JOIN users u1 ON dt.updated_by = u1.id
                LEFT JOIN users u2 ON dt.resolved_by = u2.id
                WHERE dt.procurement_order_id = ?
                ORDER BY dt.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get delivery tracking error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get procurement orders ready for delivery scheduling
     */
    public function getOrdersReadyForDelivery($assignedProjectIds = null) {
        try {
            $conditions = ["po.status = 'Approved'", "(po.delivery_status IS NULL OR po.delivery_status = 'Pending')"];
            $params = [];
            
            // Apply project assignment filtering if provided
            if ($assignedProjectIds !== null && !empty($assignedProjectIds)) {
                $placeholders = str_repeat('?,', count($assignedProjectIds) - 1) . '?';
                $conditions[] = "po.project_id IN ({$placeholders})";
                $params = array_merge($params, $assignedProjectIds);
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT po.*, 
                       v.name as vendor_name,
                       p.name as project_name, p.code as project_code,
                       u.full_name as requested_by_name,
                       ua.full_name as approved_by_name,
                       (SELECT COUNT(*) FROM procurement_items pi WHERE pi.procurement_order_id = po.id) as item_count,
                       po.net_total as total_value
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN projects p ON po.project_id = p.id
                LEFT JOIN users u ON po.requested_by = u.id
                LEFT JOIN users ua ON po.approved_by = ua.id
                {$whereClause}
                ORDER BY po.date_needed ASC, po.created_at ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get orders ready for delivery error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get orders in transit or delivered (for warehouseman)
     */
    public function getOrdersForReceipt($assignedProjectIds = null) {
        try {
            $conditions = [
                "po.status NOT IN ('Draft', 'Pending', 'Rejected', 'Received')",
                "po.status IN ('Approved', 'Scheduled for Delivery', 'In Transit', 'Delivered')"
            ];
            $params = [];
            
            // Apply project assignment filtering if provided
            if ($assignedProjectIds !== null && !empty($assignedProjectIds)) {
                $placeholders = str_repeat('?,', count($assignedProjectIds) - 1) . '?';
                $conditions[] = "po.project_id IN ({$placeholders})";
                $params = array_merge($params, $assignedProjectIds);
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT po.*, 
                       v.name as vendor_name,
                       p.name as project_name, p.code as project_code,
                       u.full_name as requested_by_name,
                       us.full_name as scheduled_by_name,
                       ud.full_name as delivered_by_name,
                       (SELECT COUNT(*) FROM procurement_items pi WHERE pi.procurement_order_id = po.id) as item_count,
                       po.net_total as total_value
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN projects p ON po.project_id = p.id
                LEFT JOIN users u ON po.requested_by = u.id
                LEFT JOIN users us ON po.scheduled_by = us.id
                LEFT JOIN users ud ON po.delivered_by = ud.id
                {$whereClause}
                ORDER BY 
                    CASE 
                        WHEN po.delivery_status = 'Delivered' THEN 1 
                        WHEN po.delivery_status = 'In Transit' THEN 2 
                        ELSE 3 
                    END,
                    po.scheduled_delivery_date ASC, 
                    po.created_at ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get orders for receipt error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get procurement order with detailed information including delivery tracking
     */
    public function getProcurementOrderWithDetails($id) {
        try {
            // Get main procurement order details
            $sql = "
                SELECT po.*, 
                       v.name as vendor_name, v.contact_person as vendor_contact,
                       v.phone as vendor_phone, v.email as vendor_email,
                       p.name as project_name, p.code as project_code,
                       u1.full_name as requested_by_name,
                       u2.full_name as approved_by_name,
                       u3.full_name as received_by_name,
                       u4.full_name as scheduled_by_name,
                       u5.full_name as delivered_by_name,
                       r.description as request_description, r.urgency as request_urgency
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN projects p ON po.project_id = p.id
                LEFT JOIN users u1 ON po.requested_by = u1.id
                LEFT JOIN users u2 ON po.approved_by = u2.id
                LEFT JOIN users u3 ON po.received_by = u3.id
                LEFT JOIN users u4 ON po.scheduled_by = u4.id
                LEFT JOIN users u5 ON po.delivered_by = u5.id
                LEFT JOIN requests r ON po.request_id = r.id
                WHERE po.id = ?
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $procurementOrder = $stmt->fetch();
            
            if (!$procurementOrder) {
                return false;
            }
            
            // Get procurement items
            $itemsSql = "
                SELECT pi.*, c.name as category_name
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                WHERE pi.procurement_order_id = ?
                ORDER BY pi.id ASC
            ";
            
            $stmt = $this->db->prepare($itemsSql);
            $stmt->execute([$id]);
            $procurementOrder['items'] = $stmt->fetchAll();
            
            // Get delivery tracking history
            $procurementOrder['delivery_tracking'] = $this->getDeliveryTracking($id);
            
            return $procurementOrder;
            
        } catch (Exception $e) {
            error_log("Get procurement order with details error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get procurement orders by request ID (for request view)
     */
    public function getProcurementOrdersByRequest($requestId) {
        try {
            $sql = "
                SELECT po.*, 
                       v.name as vendor_name, v.contact_person as vendor_contact,
                       p.name as project_name, p.code as project_code,
                       u1.full_name as requested_by_name,
                       u2.full_name as approved_by_name,
                       u3.full_name as received_by_name,
                       u4.full_name as scheduled_by_name,
                       u5.full_name as delivered_by_name,
                       (SELECT COUNT(*) FROM procurement_items pi WHERE pi.procurement_order_id = po.id) as item_count
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN projects p ON po.project_id = p.id
                LEFT JOIN users u1 ON po.requested_by = u1.id
                LEFT JOIN users u2 ON po.approved_by = u2.id
                LEFT JOIN users u3 ON po.received_by = u3.id
                LEFT JOIN users u4 ON po.scheduled_by = u4.id
                LEFT JOIN users u5 ON po.delivered_by = u5.id
                WHERE po.request_id = ?
                ORDER BY po.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$requestId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get procurement orders by request error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Notify stakeholders about procurement order status changes
     */
    private function notifyStakeholders($procurementOrderId, $action, $hasDiscrepancy = false) {
        try {
            // Get procurement order details
            $order = $this->getProcurementOrderWithDetails($procurementOrderId);
            if (!$order) {
                return;
            }
            
            // Get stakeholders based on action
            $stakeholders = $this->getStakeholdersForNotification($order, $action);
            
            // Create notification message
            $message = $this->buildNotificationMessage($order, $action, $hasDiscrepancy);
            
            // Log notification for each stakeholder (in a real system, this would send emails/SMS)
            foreach ($stakeholders as $stakeholder) {
                $this->logProcurementActivity(
                    $procurementOrderId,
                    $stakeholder['id'],
                    'stakeholder_notified',
                    null,
                    null,
                    "Notification sent to {$stakeholder['role']}: {$message}"
                );
            }
            
        } catch (Exception $e) {
            error_log("Stakeholder notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Get stakeholders who should be notified based on action
     */
    private function getStakeholdersForNotification($order, $action) {
        try {
            $stakeholders = [];
            
            // Get users by role
            $sql = "
                SELECT u.id, u.full_name, u.email, r.name as role
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.is_active = 1 AND r.name IN (?, ?, ?, ?, ?, ?)
            ";
            
            $roles = ['Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'];
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($roles);
            $allStakeholders = $stmt->fetchAll();
            
            // Filter stakeholders based on action
            foreach ($allStakeholders as $stakeholder) {
                $shouldNotify = false;
                
                switch ($action) {
                    case 'delivery_scheduled':
                        $shouldNotify = in_array($stakeholder['role'], ['Warehouseman', 'Project Manager', 'Site Inventory Clerk', 'Asset Director']);
                        break;
                    case 'delivery_status_updated':
                        $shouldNotify = in_array($stakeholder['role'], ['Procurement Officer', 'Warehouseman', 'Project Manager', 'Asset Director']);
                        break;
                    case 'receipt_confirmed':
                        $shouldNotify = in_array($stakeholder['role'], ['Finance Director', 'Asset Director', 'Procurement Officer', 'Project Manager']);
                        break;
                    case 'discrepancy_reported':
                        $shouldNotify = in_array($stakeholder['role'], ['Finance Director', 'Asset Director', 'Procurement Officer']);
                        break;
                    case 'discrepancy_resolved':
                        $shouldNotify = in_array($stakeholder['role'], ['Finance Director', 'Asset Director', 'Procurement Officer', 'Project Manager', 'Warehouseman', 'Site Inventory Clerk']);
                        break;
                }
                
                if ($shouldNotify) {
                    $stakeholders[] = $stakeholder;
                }
            }
            
            // Add project-specific stakeholders
            if ($order['project_id']) {
                $projectSql = "
                    SELECT u.id, u.full_name, u.email, 'Project Manager' as role
                    FROM projects p
                    LEFT JOIN users u ON p.project_manager_id = u.id
                    WHERE p.id = ? AND u.is_active = 1
                ";
                
                $stmt = $this->db->prepare($projectSql);
                $stmt->execute([$order['project_id']]);
                $projectManager = $stmt->fetch();
                
                if ($projectManager && !in_array($projectManager['id'], array_column($stakeholders, 'id'))) {
                    $stakeholders[] = $projectManager;
                }
            }
            
            return $stakeholders;
            
        } catch (Exception $e) {
            error_log("Get stakeholders for notification error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Build notification message based on action
     */
    private function buildNotificationMessage($order, $action, $hasDiscrepancy = false) {
        $poNumber = $order['po_number'];
        $projectName = $order['project_name'];
        
        switch ($action) {
            case 'delivery_scheduled':
                return "Delivery scheduled for PO #{$poNumber} ({$projectName}) - Expected: " . date('M j, Y', strtotime($order['scheduled_delivery_date']));
            case 'delivery_status_updated':
                return "Delivery status updated for PO #{$poNumber} ({$projectName}) - Status: {$order['delivery_status']}";
            case 'receipt_confirmed':
                if ($hasDiscrepancy) {
                    return "Receipt confirmed with discrepancies for PO #{$poNumber} ({$projectName}) - Review required";
                } else {
                    return "Receipt confirmed for PO #{$poNumber} ({$projectName}) - All items received as expected";
                }
            case 'discrepancy_reported':
                return "Delivery discrepancy reported for PO #{$poNumber} ({$projectName}) - Immediate attention required";
            case 'discrepancy_resolved':
                return "Delivery discrepancy resolved for PO #{$poNumber} ({$projectName}) - Issue has been addressed";
            default:
                return "Status update for PO #{$poNumber} ({$projectName})";
        }
    }
    
    /**
     * Get orders with delivery alerts (for API and dashboard)
     */
    public function getOrdersWithDeliveryAlerts($userRole = null, $userId = null, $assignedProjectIds = null) {
        try {
            $conditions = [];
            $params = [];
            
            // Role-based filtering
            if ($userRole === 'Procurement Officer') {
                $conditions[] = "po.status IN ('Approved', 'Scheduled for Delivery', 'In Transit')";
            } elseif ($userRole === 'Warehouseman') {
                $conditions[] = "po.delivery_status IN ('Delivered', 'In Transit')";
            } elseif ($userRole === 'Asset Director') {
                // Asset Director can see all delivery alerts
            } else {
                // Default filtering for other roles
                $conditions[] = "po.status IN ('Approved', 'Scheduled for Delivery', 'In Transit', 'Delivered')";
            }
            
            // Add overdue delivery condition
            $conditions[] = "(po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received'))";
            
            // Add discrepancy condition - only show unresolved discrepancies
            $conditions[] = "(po.delivery_discrepancy_notes IS NOT NULL AND po.delivery_discrepancy_notes != '' 
                            AND NOT EXISTS (
                                SELECT 1 FROM delivery_tracking dt 
                                WHERE dt.procurement_order_id = po.id 
                                AND dt.status = 'Resolved'
                            ))";
            
            // Combine alert conditions with OR
            $alertConditions = "(" . implode(" OR ", $conditions) . ")";
            
            // Add project assignment filtering with AND
            $finalConditions = [$alertConditions];
            if ($assignedProjectIds !== null && !empty($assignedProjectIds)) {
                $placeholders = str_repeat('?,', count($assignedProjectIds) - 1) . '?';
                $finalConditions[] = "po.project_id IN ({$placeholders})";
                $params = array_merge($params, $assignedProjectIds);
            }
            
            $whereClause = "WHERE " . implode(" AND ", $finalConditions);
            
            $sql = "
                SELECT po.*, 
                       v.name as vendor_name,
                       p.name as project_name, p.code as project_code,
                       u.full_name as requested_by_name,
                       us.full_name as scheduled_by_name,
                       CASE 
                           WHEN po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received') THEN 'Overdue'
                           WHEN po.delivery_discrepancy_notes IS NOT NULL AND po.delivery_discrepancy_notes != '' 
                                AND NOT EXISTS (
                                    SELECT 1 FROM delivery_tracking dt 
                                    WHERE dt.procurement_order_id = po.id 
                                    AND dt.status = 'Resolved'
                                ) THEN 'Discrepancy'
                           ELSE 'Alert'
                       END as alert_type,
                       CASE 
                           WHEN po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received') THEN DATEDIFF(CURDATE(), po.scheduled_delivery_date)
                           ELSE 0
                       END as days_overdue
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN projects p ON po.project_id = p.id
                LEFT JOIN users u ON po.requested_by = u.id
                LEFT JOIN users us ON po.scheduled_by = us.id
                {$whereClause}
                ORDER BY 
                    CASE 
                        WHEN po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received') THEN 1
                        WHEN po.delivery_discrepancy_notes IS NOT NULL AND po.delivery_discrepancy_notes != '' 
                             AND NOT EXISTS (
                                 SELECT 1 FROM delivery_tracking dt 
                                 WHERE dt.procurement_order_id = po.id 
                                 AND dt.status = 'Resolved'
                             ) THEN 2
                        ELSE 3
                    END,
                    po.scheduled_delivery_date ASC
                LIMIT 50
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get orders with delivery alerts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Resolve delivery discrepancy
     */
    public function resolveDiscrepancy($id, $resolutionData, $userId) {
        try {
            $this->beginTransaction();
            
            // Get current order
            $order = $this->find($id);
            if (!$order) {
                $this->rollback();
                return ['success' => false, 'message' => 'Procurement order not found'];
            }
            
            if (empty($order['delivery_discrepancy_notes'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'No discrepancy to resolve'];
            }
            
            // Update delivery tracking with resolution
            $sql = "UPDATE delivery_tracking 
                    SET resolution_notes = ?, resolved_by = ?, resolved_at = NOW()
                    WHERE procurement_order_id = ? AND discrepancy_details IS NOT NULL
                    ORDER BY created_at DESC LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $resolutionData['resolution_notes'],
                $userId,
                $id
            ]);
            
            // Create new tracking entry for resolution
            $trackingData = [
                'procurement_order_id' => $id,
                'status' => 'Resolved',
                'updated_by' => $userId,
                'notes' => $resolutionData['resolution_notes'],
                'resolution_notes' => $resolutionData['resolution_notes'],
                'resolved_by' => $userId
            ];
            
            $this->createDeliveryTracking($trackingData);
            
            // Handle resolution action
            $resolutionAction = $resolutionData['resolution_action'] ?? 'document_only';
            
            switch ($resolutionAction) {
                case 'reschedule_delivery':
                    // Return to approved status for re-scheduling delivery
                    // Keep existing quantity_received values - don't reset them
                    $this->update($id, [
                        'delivery_status' => 'Pending',
                        'status' => 'Approved',
                        'delivery_discrepancy_notes' => null // Clear discrepancy since it's being re-delivered
                    ]);
                    
                    // Create tracking entry for re-schedule
                    $trackingData = [
                        'procurement_order_id' => $id,
                        'status' => 'Rescheduled for Re-delivery',
                        'updated_by' => $userId,
                        'notes' => 'Order rescheduled for re-delivery of remaining items: ' . $resolutionData['resolution_notes']
                    ];
                    $this->createDeliveryTracking($trackingData);
                    break;
                    
                case 'mark_complete':
                    // Accept partial delivery as complete
                    $this->update($id, [
                        'delivery_status' => 'Complete',
                        'status' => 'Received'
                    ]);
                    break;
                    
                case 'document_only':
                default:
                    // No status change, just document the resolution
                    break;
            }
            
            // Log the resolution with appropriate status
            $newStatus = 'Documented';
            if ($resolutionAction === 'reschedule_delivery') {
                $newStatus = 'Rescheduled for Delivery';
            } elseif ($resolutionAction === 'mark_complete') {
                $newStatus = 'Accepted as Complete';
            }
            
            $this->logProcurementActivity($id, $userId, 'discrepancy_resolved', 
                $order['delivery_status'], $newStatus, 
                "Discrepancy resolved (" . ucfirst(str_replace('_', ' ', $resolutionAction)) . "): " . $resolutionData['resolution_notes']);
            
            // Notify stakeholders about discrepancy resolution
            $this->notifyStakeholders($id, 'discrepancy_resolved');
            
            $this->commit();
            return ['success' => true, 'message' => 'Discrepancy resolved successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Resolve discrepancy error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to resolve discrepancy'];
        }
    }
    
    /**
     * Resolve discrepancy for a specific item within a procurement order
     */
    public function resolveItemDiscrepancy($orderId, $itemId, $resolutionData, $userId) {
        try {
            $this->beginTransaction();
            
            // Get current order and item
            $order = $this->find($orderId);
            if (!$order) {
                $this->rollback();
                return ['success' => false, 'message' => 'Procurement order not found'];
            }
            
            // Get the specific item
            $stmt = $this->db->prepare("SELECT * FROM procurement_items WHERE id = ? AND procurement_order_id = ?");
            $stmt->execute([$itemId, $orderId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                $this->rollback();
                return ['success' => false, 'message' => 'Procurement item not found'];
            }
            
            // Check if item has a discrepancy to resolve
            if (empty($item['discrepancy_notes']) && $item['quantity_received'] >= $item['quantity']) {
                $this->rollback();
                return ['success' => false, 'message' => 'No discrepancy found for this item'];
            }
            
            // Update the item with resolution details
            $updateData = [
                'discrepancy_resolved_at' => date('Y-m-d H:i:s'),
                'discrepancy_resolved_by' => $userId
            ];
            
            // If resolution notes are provided, update the discrepancy notes
            if (!empty($resolutionData['resolution_notes'])) {
                $existingNotes = $item['discrepancy_notes'] ?? '';
                $resolverName = $this->getUserName($userId);
                $timestamp = date('Y-m-d H:i:s');
                $resolutionNote = "\n\n[RESOLVED {$timestamp} by {$resolverName}]: " . $resolutionData['resolution_notes'];
                $updateData['discrepancy_notes'] = $existingNotes . $resolutionNote;
            }
            
            $stmt = $this->db->prepare("
                UPDATE procurement_items 
                SET discrepancy_resolved_at = ?, discrepancy_resolved_by = ?, discrepancy_notes = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $updateData['discrepancy_resolved_at'],
                $updateData['discrepancy_resolved_by'],
                $updateData['discrepancy_notes'] ?? $item['discrepancy_notes'],
                $itemId
            ]);
            
            // Create item-level delivery tracking entry
            $trackingData = [
                'procurement_order_id' => $orderId,
                'procurement_item_id' => $itemId,
                'status' => 'Resolved',
                'updated_by' => $userId,
                'notes' => $resolutionData['resolution_notes'] ?? 'Item discrepancy resolved',
                'resolution_notes' => $resolutionData['resolution_notes'] ?? null,
                'resolved_by' => $userId
            ];
            
            $this->createDeliveryTracking($trackingData);
            
            // Check if all items in the order have been resolved
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total_items,
                       COUNT(CASE WHEN discrepancy_resolved_at IS NOT NULL OR quantity_received >= quantity THEN 1 END) as resolved_items
                FROM procurement_items 
                WHERE procurement_order_id = ?
            ");
            $stmt->execute([$orderId]);
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If all items are resolved, update order-level status
            if ($counts['total_items'] == $counts['resolved_items']) {
                $this->update($orderId, [
                    'delivery_discrepancy_notes' => null // Clear order-level discrepancy
                ]);
                
                // Create order-level resolution tracking
                $orderTrackingData = [
                    'procurement_order_id' => $orderId,
                    'status' => 'Resolved',
                    'updated_by' => $userId,
                    'notes' => 'All item discrepancies have been resolved',
                    'resolved_by' => $userId
                ];
                $this->createDeliveryTracking($orderTrackingData);
            }
            
            // Log the item-level resolution
            $this->logProcurementActivity($orderId, $userId, 'item_discrepancy_resolved', 
                'Discrepancy', 'Resolved', 
                "Item discrepancy resolved for '{$item['item_name']}': " . ($resolutionData['resolution_notes'] ?? 'No additional notes'));
            
            $this->commit();
            return ['success' => true, 'message' => 'Item discrepancy resolved successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Resolve item discrepancy error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to resolve item discrepancy'];
        }
    }
    
    /**
     * Get items with unresolved discrepancies for a procurement order
     */
    public function getItemsWithUnresolvedDiscrepancies($orderId) {
        try {
            $sql = "
                SELECT pi.*, c.name as category_name
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                WHERE pi.procurement_order_id = ?
                AND (
                    pi.quantity_received < pi.quantity 
                    OR (pi.discrepancy_notes IS NOT NULL AND pi.discrepancy_resolved_at IS NULL)
                )
                ORDER BY pi.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get items with unresolved discrepancies error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get items that are eligible for asset generation (no unresolved discrepancies)
     */
    public function getItemsEligibleForAssetGeneration($orderId) {
        try {
            $sql = "
                SELECT pi.*, c.name as category_name,
                       (pi.quantity_received - COALESCE(
                           (SELECT COUNT(*) FROM inventory_items a WHERE a.procurement_item_id = pi.id), 0
                       )) as available_for_generation
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                WHERE pi.procurement_order_id = ?
                AND pi.quantity_received > 0
                AND (
                    pi.quantity_received >= pi.quantity 
                    OR pi.discrepancy_resolved_at IS NOT NULL
                )
                HAVING available_for_generation > 0
                ORDER BY pi.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get items eligible for asset generation error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Helper method to get user name for resolution notes
     */
    private function getUserName($userId) {
        try {
            $stmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? $user['full_name'] : 'Unknown User';
        } catch (Exception $e) {
            return 'Unknown User';
        }
    }
    
    /**
     * Get delivery performance metrics
     */
    public function getDeliveryPerformanceMetrics($dateFrom = null, $dateTo = null, $projectId = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($dateFrom) {
                $conditions[] = "po.created_at >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "po.created_at <= ?";
                $params[] = $dateTo;
            }
            
            if ($projectId) {
                $conditions[] = "po.project_id = ?";
                $params[] = $projectId;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN po.delivery_status = 'Received' THEN 1 ELSE 0 END) as completed_deliveries,
                    SUM(CASE WHEN po.delivery_status IN ('Scheduled', 'In Transit') THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received') THEN 1 ELSE 0 END) as overdue_deliveries,
                    SUM(CASE WHEN po.delivery_discrepancy_notes IS NOT NULL AND po.delivery_discrepancy_notes != '' THEN 1 ELSE 0 END) as deliveries_with_discrepancies,
                    AVG(CASE 
                        WHEN po.actual_delivery_date IS NOT NULL AND po.scheduled_delivery_date IS NOT NULL 
                        THEN DATEDIFF(po.actual_delivery_date, po.scheduled_delivery_date)
                        ELSE NULL 
                    END) as avg_delivery_variance_days
                FROM procurement_orders po
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $metrics = $stmt->fetch();
            
            // Calculate percentages
            if ($metrics['total_orders'] > 0) {
                $metrics['completion_rate'] = round(($metrics['completed_deliveries'] / $metrics['total_orders']) * 100, 2);
                $metrics['overdue_rate'] = round(($metrics['overdue_deliveries'] / $metrics['total_orders']) * 100, 2);
                $metrics['discrepancy_rate'] = round(($metrics['deliveries_with_discrepancies'] / $metrics['total_orders']) * 100, 2);
            } else {
                $metrics['completion_rate'] = 0;
                $metrics['overdue_rate'] = 0;
                $metrics['discrepancy_rate'] = 0;
            }
            
            return $metrics;
            
        } catch (Exception $e) {
            error_log("Get delivery performance metrics error: " . $e->getMessage());
            return [
                'total_orders' => 0,
                'completed_deliveries' => 0,
                'in_progress' => 0,
                'overdue_deliveries' => 0,
                'deliveries_with_discrepancies' => 0,
                'avg_delivery_variance_days' => 0,
                'completion_rate' => 0,
                'overdue_rate' => 0,
                'discrepancy_rate' => 0
            ];
        }
    }
    


    /**
     * Send procurement order email notifications
     */
    private function sendProcurementNotification($orderId, $action, $actorId, $newStatus = null) {
        try {
            require_once APP_ROOT . '/core/ProcurementEmailTemplates.php';
            $emailTemplates = new ProcurementEmailTemplates();

            $userModel = new UserModel();
            $order = $this->getProcurementOrderWithDetails($orderId);

            if (!$order) {
                error_log("Cannot send procurement notification: Order #{$orderId} not found");
                return;
            }

            switch ($action) {
                case 'created':
                    // Send approval request to Finance Director
                    if ($order['status'] === 'Pending') {
                        $financeDirectors = $userModel->getUsersByRole('Finance Director');
                        foreach ($financeDirectors as $director) {
                            $emailTemplates->sendApprovalRequest($order, $director);
                        }
                    }
                    break;

                case 'status_changed':
                    if ($newStatus === 'Approved') {
                        // Send schedule delivery request to Procurement Officer
                        $procurementOfficers = $userModel->getUsersByRole('Procurement Officer');
                        foreach ($procurementOfficers as $officer) {
                            $emailTemplates->sendScheduleDeliveryRequest($order, $officer);
                        }
                    } elseif ($newStatus === 'Delivered') {
                        // Send delivery notification to Warehouseman
                        $warehousemen = $userModel->getUsersByRole('Warehouseman');
                        foreach ($warehousemen as $warehouseman) {
                            $emailTemplates->sendDeliveryNotification($order, $warehouseman);
                        }
                    } elseif ($newStatus === 'Received') {
                        // Send completion notification to all parties
                        $usersToNotify = [];

                        // Add requester
                        if ($order['requested_by']) {
                            $requester = $userModel->find($order['requested_by']);
                            if ($requester) $usersToNotify[] = $requester;
                        }

                        // Add approver
                        if ($order['approved_by']) {
                            $approver = $userModel->find($order['approved_by']);
                            if ($approver) $usersToNotify[] = $approver;
                        }

                        // Add receiver
                        if ($order['received_by'] && $order['received_by'] != $actorId) {
                            $receiver = $userModel->find($order['received_by']);
                            if ($receiver) $usersToNotify[] = $receiver;
                        }

                        if (!empty($usersToNotify)) {
                            $emailTemplates->sendCompletedNotification($order, $usersToNotify);
                        }
                    } elseif ($newStatus === 'Rejected') {
                        // Send rejection notification to requester
                        if ($order['requested_by']) {
                            $requester = $userModel->find($order['requested_by']);
                            if ($requester) {
                                $emailTemplates->sendRejectionNotification($order, $requester, $order['notes'] ?? null);
                            }
                        }
                    } elseif ($newStatus === 'For Revision') {
                        // Send revision request to Procurement Officer or requester
                        if ($order['requested_by']) {
                            $requester = $userModel->find($order['requested_by']);
                            if ($requester) {
                                $emailTemplates->sendRevisionRequest($order, $requester, $order['notes'] ?? null);
                            }
                        }
                    }
                    break;
            }

        } catch (Exception $e) {
            error_log("Procurement notification error: " . $e->getMessage());
            // Don't throw - email failures shouldn't break procurement operations
        }
    }

    /**
     * Log general activity
     */
    private function logActivity($action, $description, $table, $recordId) {
        try {
            $auth = Auth::getInstance();
            $user = $auth->getCurrentUser();
            
            $sql = "INSERT INTO activity_logs (user_id, action, description, table_name, record_id, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $user['id'] ?? null,
                $action,
                $description,
                $table,
                $recordId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Activity logging error: " . $e->getMessage());
        }
    }

    /**
     * Get procurement orders by delivery status
     */
    public function getOrdersByDeliveryStatus($status, $assignedProjectIds = null) {
        try {
            $conditions = ["po.delivery_status = ?"];
            $params = [$status];
            
            // Apply project assignment filtering if provided
            if ($assignedProjectIds !== null && !empty($assignedProjectIds)) {
                $placeholders = str_repeat('?,', count($assignedProjectIds) - 1) . '?';
                $conditions[] = "po.project_id IN ({$placeholders})";
                $params = array_merge($params, $assignedProjectIds);
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT po.*, 
                       v.name as vendor_name, v.name as supplier_name,
                       p.name as project_name, p.code as project_code,
                       u.full_name as requested_by_name,
                       po.net_total as total_value
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id  
                LEFT JOIN projects p ON po.project_id = p.id
                LEFT JOIN users u ON po.requested_by = u.id
                {$whereClause}
                ORDER BY po.scheduled_delivery_date ASC, po.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting orders by delivery status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get overall procurement statistics
     */
    public function getOverallProcurementStats($dateFrom = null, $dateTo = null, $projectId = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($dateFrom) {
                $conditions[] = "po.created_at >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "po.created_at <= ?";
                $params[] = $dateTo . ' 23:59:59';
            }
            
            if ($projectId) {
                $conditions[] = "po.project_id = ?";
                $params[] = $projectId;
            }
            
            $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get overall statistics
            $sql = "
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(po.net_total) as total_value,
                    AVG(po.net_total) as avg_order_value,
                    COUNT(CASE WHEN po.status = 'Draft' THEN 1 END) as draft_count,
                    COUNT(CASE WHEN po.status = 'Pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN po.status = 'Approved' THEN 1 END) as approved_count,
                    COUNT(CASE WHEN po.delivery_status = 'In Transit' THEN 1 END) as in_transit_count,
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' THEN 1 END) as delivered_count
                FROM procurement_orders po
                $whereClause
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Prepare status distribution for charts
            $stats['status_labels'] = ['Draft', 'Pending', 'Approved', 'In Transit', 'Delivered'];
            $stats['status_counts'] = [
                $stats['draft_count'],
                $stats['pending_count'], 
                $stats['approved_count'],
                $stats['in_transit_count'],
                $stats['delivered_count']
            ];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error getting overall procurement stats: " . $e->getMessage());
            return [
                'total_orders' => 0,
                'total_value' => 0,
                'avg_order_value' => 0,
                'status_labels' => ['Draft', 'Pending', 'Approved', 'In Transit', 'Delivered'],
                'status_counts' => [0, 0, 0, 0, 0]
            ];
        }
    }

    /**
     * Get supplier performance metrics
     */
    public function getSupplierPerformanceMetrics($dateFrom = null, $dateTo = null) {
        try {
            $conditions = ["po.status != 'Draft'"];
            $params = [];
            
            if ($dateFrom) {
                $conditions[] = "po.created_at >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "po.created_at <= ?";
                $params[] = $dateTo . ' 23:59:59';
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
            
            $sql = "
                SELECT 
                    v.name as supplier_name,
                    COUNT(*) as total_orders,
                    SUM(po.net_total) as total_value,
                    AVG(po.net_total) as avg_order_value,
                    COUNT(CASE WHEN po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) as on_time_deliveries,
                    COUNT(CASE WHEN po.actual_delivery_date IS NOT NULL THEN 1 END) as completed_deliveries,
                    ROUND(
                        (COUNT(CASE WHEN po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) * 100.0) / 
                        NULLIF(COUNT(CASE WHEN po.actual_delivery_date IS NOT NULL THEN 1 END), 0), 
                        1
                    ) as on_time_rate,
                    AVG(
                        CASE WHEN po.actual_delivery_date IS NOT NULL AND po.order_date IS NOT NULL 
                        THEN DATEDIFF(po.actual_delivery_date, po.order_date) 
                        END
                    ) as avg_lead_time
                FROM procurement_orders po
                LEFT JOIN vendors v ON po.vendor_id = v.id
                $whereClause
                GROUP BY v.id, v.name
                HAVING total_orders > 0
                ORDER BY on_time_rate DESC, total_orders DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting supplier performance metrics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get project IDs that a user is assigned to based on their role
     */
    public function getUserAssignedProjectIds($userId, $userRole) {
        $projectIds = [];
        
        try {
            // For Project Managers, get projects they're assigned as manager
            if ($userRole === 'Project Manager') {
                $stmt = $this->db->prepare("
                    SELECT id FROM projects 
                    WHERE project_manager_id = ? AND is_active = 1
                ");
                $stmt->execute([$userId]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $projectIds[] = $row['id'];
                }
            }
            
            // For Site Inventory Clerk and Warehouseman, get projects from user_projects table
            if (in_array($userRole, ['Site Inventory Clerk', 'Warehouseman'])) {
                $stmt = $this->db->prepare("
                    SELECT DISTINCT up.project_id 
                    FROM user_projects up 
                    JOIN projects p ON up.project_id = p.id 
                    WHERE up.user_id = ? AND up.is_active = 1 AND p.is_active = 1
                ");
                $stmt->execute([$userId]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $projectIds[] = $row['project_id'];
                }
            }
            
        } catch (Exception $e) {
            error_log("Error getting user assigned projects: " . $e->getMessage());
        }
        
        // Debug logging
        error_log("DEBUG: getUserAssignedProjectIds - UserID: $userId, Role: $userRole, Found Projects: " . implode(',', $projectIds));
        
        return $projectIds;
    }
    
    /**
     * Create retroactive procurement order for items purchased without PO
     * Uses existing createProcurementOrder method with appropriate status
     */
    public function createRetrospectivePO($orderData, $items = [], $currentState = 'not_delivered') {
        try {
            // Mark as retroactive
            $orderData['is_retroactive'] = 1;
            
            // Ensure retroactive reason is set
            if (empty($orderData['retroactive_reason'])) {
                $orderData['retroactive_reason'] = 'Post-purchase documentation';
            }
            
            // Set initial status to Draft for editing, store current state for later submission
            $orderData['status'] = 'Draft';
            $orderData['retroactive_current_state'] = $currentState;
            
            // Set target status based on current item state (for reference)
            switch ($currentState) {
                case 'not_delivered':
                    // Items purchased but not yet delivered - target: Approved
                    $orderData['retroactive_target_status'] = 'Approved';
                    break;
                    
                case 'delivered':
                    // Items delivered but not yet received in warehouse - target: Delivered  
                    $orderData['retroactive_target_status'] = 'Delivered';
                    break;
                    
                case 'received':
                    // Items already received and in use - target: Received
                    $orderData['retroactive_target_status'] = 'Received';
                    break;
                    
                default:
                    // Safe default - allows full workflow
                    $orderData['retroactive_target_status'] = 'Approved';
            }
            
            // Call existing createProcurementOrder method - no new logic needed
            $result = $this->createProcurementOrder($orderData, $items);
            
            if ($result['success']) {
                // Log retroactive creation
                $this->logProcurementActivity(
                    $result['procurement_order']['id'], 
                    $orderData['requested_by'], 
                    'retroactive_po_created', 
                    null, 
                    $orderData['status'], 
                    "Retroactive PO created: {$orderData['retroactive_reason']}"
                );
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Create retroactive procurement order error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create retroactive procurement order'];
        }
    }

    /**
     * Determine which file types are logically appropriate for a procurement order
     * based on its type (regular/retroactive) and status
     */
    public static function getAllowedFileTypes($isRetroactive = false, $status = 'Draft') {
        $allowedTypes = [];
        
        // Vendor quotation is always logical (obtained before or during PO process)
        $allowedTypes['quote_file'] = [
            'allowed' => true,
            'required' => false,
            'label' => 'Vendor Quotation',
            'help' => 'Upload vendor quotation or price list'
        ];
        
        if ($isRetroactive) {
            // Retroactive PO: Purchase already happened, so receipt is required
            $allowedTypes['purchase_receipt_file'] = [
                'allowed' => true,
                'required' => true,
                'label' => 'Purchase Receipt/Invoice',
                'help' => 'Upload purchase receipt or sales invoice (REQUIRED for retroactive PO)'
            ];
            
            $allowedTypes['supporting_evidence_file'] = [
                'allowed' => true,
                'required' => false,
                'label' => 'Additional Evidence',
                'help' => 'Upload any additional supporting documentation'
            ];
            
        } else {
            // Regular PO: Conditional based on status
            switch ($status) {
                case 'Draft':
                case 'Pending':
                case 'Reviewed':
                case 'For Revision':
                case 'Approved':
                case 'Scheduled for Delivery':
                case 'In Transit':
                    // Pre-delivery: No receipt exists yet
                    $allowedTypes['purchase_receipt_file'] = [
                        'allowed' => false,
                        'required' => false,
                        'label' => 'Purchase Receipt/Invoice',
                        'help' => 'Not available - purchase not completed yet'
                    ];
                    
                    $allowedTypes['supporting_evidence_file'] = [
                        'allowed' => false,
                        'required' => false,
                        'label' => 'Supporting Documents',
                        'help' => 'Not available - purchase not completed yet'
                    ];
                    break;
                    
                case 'Delivered':
                case 'Received':
                    // Post-delivery: Receipt should now be available
                    $allowedTypes['purchase_receipt_file'] = [
                        'allowed' => true,
                        'required' => false,
                        'label' => 'Purchase Receipt/Invoice',
                        'help' => 'Upload purchase receipt or sales invoice (now that items are delivered)'
                    ];
                    
                    $allowedTypes['supporting_evidence_file'] = [
                        'allowed' => true,
                        'required' => false,
                        'label' => 'Supporting Documents',
                        'help' => 'Upload any additional supporting documentation'
                    ];
                    break;
                    
                case 'Rejected':
                    // Rejected: Only quotation makes sense
                    $allowedTypes['purchase_receipt_file'] = [
                        'allowed' => false,
                        'required' => false,
                        'label' => 'Purchase Receipt/Invoice',
                        'help' => 'Not applicable - order was rejected'
                    ];
                    
                    $allowedTypes['supporting_evidence_file'] = [
                        'allowed' => false,
                        'required' => false,
                        'label' => 'Supporting Documents',
                        'help' => 'Not applicable - order was rejected'
                    ];
                    break;
            }
        }
        
        return $allowedTypes;
    }

    /**
     * Check if a file type is allowed for a given PO
     */
    public static function isFileTypeAllowed($fileType, $isRetroactive = false, $status = 'Draft') {
        $allowedTypes = self::getAllowedFileTypes($isRetroactive, $status);
        return $allowedTypes[$fileType]['allowed'] ?? false;
    }
}
