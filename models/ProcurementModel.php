<?php
/**
 * ConstructLink™ Procurement Model - Complete Implementation
 * Handles procurement workflow with asset generation and project scoping
 */

class ProcurementModel extends BaseModel {
    protected $table = 'procurement_orders';
    protected $fillable = [
        'po_number', 'request_id', 'vendor_id', 'project_id', 'item_name', 'description',
        'quantity', 'unit', 'unit_price', 'subtotal', 'vat_amount', 'handling_fee',
        'ewt_amount', 'net_total', 'total_cost', 'quote_file', 'delivery_status',
        'delivery_date', 'package_scope', 'status', 'requested_by', 'approved_by',
        'date_needed', 'notes', 'model', 'specifications', 'justification',
        'budget_allocation', 'work_breakdown', 'received_by', 'received_at',
        'quality_check_notes'
    ];
    
    /**
     * Create procurement order with validation and PO number generation
     */
    public function createProcurement($data) {
        try {
            // Validate required fields
            $validation = $this->validate($data, [
                'vendor_id' => 'required|integer',
                'project_id' => 'required|integer',
                'item_name' => 'required|max:255',
                'quantity' => 'required|integer|min:1',
                'unit_price' => 'required|numeric|min:0',
                'requested_by' => 'required|integer'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $this->beginTransaction();
            
            // Check project access for user
            $userModel = new UserModel();
            $currentUser = Auth::getInstance()->getCurrentUser();
            
            if (!$userModel->hasProjectAccess($currentUser['id'], $data['project_id'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Access denied: You do not have access to this project'];
            }
            
            // Generate PO number
            $poNumber = $this->generatePONumber();
            
            // Calculate totals
            $subtotal = $data['quantity'] * $data['unit_price'];
            $vatAmount = $subtotal * 0.12; // 12% VAT
            $ewtAmount = $subtotal * 0.02; // 2% EWT
            $handlingFee = $data['handling_fee'] ?? 0;
            $netTotal = $subtotal + $vatAmount + $handlingFee - $ewtAmount;
            
            // Prepare procurement data
            $procurementData = [
                'po_number' => $poNumber,
                'request_id' => !empty($data['request_id']) ? (int)$data['request_id'] : null,
                'vendor_id' => (int)$data['vendor_id'],
                'project_id' => (int)$data['project_id'],
                'item_name' => Validator::sanitize($data['item_name']),
                'description' => Validator::sanitize($data['description'] ?? ''),
                'quantity' => (int)$data['quantity'],
                'unit' => Validator::sanitize($data['unit'] ?? 'pcs'),
                'unit_price' => (float)$data['unit_price'],
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'handling_fee' => $handlingFee,
                'ewt_amount' => $ewtAmount,
                'net_total' => $netTotal,
                'total_cost' => $netTotal,
                'quote_file' => $data['quote_file'] ?? null,
                'delivery_status' => 'Pending',
                'package_scope' => Validator::sanitize($data['package_scope'] ?? ''),
                'status' => 'Pending',
                'requested_by' => (int)$data['requested_by'],
                'date_needed' => !empty($data['date_needed']) ? $data['date_needed'] : null,
                'notes' => Validator::sanitize($data['notes'] ?? ''),
                'model' => Validator::sanitize($data['model'] ?? ''),
                'specifications' => Validator::sanitize($data['specifications'] ?? ''),
                'justification' => Validator::sanitize($data['justification'] ?? ''),
                'budget_allocation' => !empty($data['budget_allocation']) ? (float)$data['budget_allocation'] : null,
                'work_breakdown' => Validator::sanitize($data['work_breakdown'] ?? '')
            ];
            
            // Create procurement record
            $procurement = $this->create($procurementData);
            
            if (!$procurement) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create procurement order'];
            }
            
            // Update linked request status if applicable
            if (!empty($data['request_id'])) {
                $requestModel = new RequestModel();
                $requestModel->update($data['request_id'], [
                    'status' => 'Procured',
                    'procurement_id' => $procurement['id']
                ]);
            }
            
            // Log activity
            $this->logProcurementActivity($procurement['id'], 'procurement_created', 'Procurement order created', null, $procurementData);
            
            $this->commit();
            return ['success' => true, 'procurement' => $procurement];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Procurement creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create procurement order'];
        }
    }
    
    /**
     * Approve procurement order
     */
    public function approveProcurement($id, $approvedBy, $notes = null) {
        try {
            $procurement = $this->find($id);
            if (!$procurement) {
                return ['success' => false, 'message' => 'Procurement order not found'];
            }
            
            if ($procurement['status'] !== 'Pending') {
                return ['success' => false, 'message' => 'Procurement order is not in pending status'];
            }
            
            $this->beginTransaction();
            
            $oldData = $procurement;
            $updateData = [
                'status' => 'Approved',
                'approved_by' => $approvedBy,
                'notes' => $notes ? ($procurement['notes'] ? $procurement['notes'] . "\n\nApproval Notes: " . $notes : "Approval Notes: " . $notes) : $procurement['notes']
            ];
            
            $result = $this->update($id, $updateData);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to approve procurement order'];
            }
            
            // Log activity
            $this->logProcurementActivity($id, 'procurement_approved', 'Procurement order approved', $oldData, $updateData);
            
            $this->commit();
            return ['success' => true, 'procurement' => $result];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Procurement approval error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve procurement order'];
        }
    }
    
    /**
     * Reject procurement order
     */
    public function rejectProcurement($id, $rejectedBy, $reason) {
        try {
            $procurement = $this->find($id);
            if (!$procurement) {
                return ['success' => false, 'message' => 'Procurement order not found'];
            }
            
            if ($procurement['status'] !== 'Pending') {
                return ['success' => false, 'message' => 'Procurement order is not in pending status'];
            }
            
            $this->beginTransaction();
            
            $oldData = $procurement;
            $updateData = [
                'status' => 'Rejected',
                'notes' => $procurement['notes'] ? $procurement['notes'] . "\n\nRejection Reason: " . $reason : "Rejection Reason: " . $reason
            ];
            
            $result = $this->update($id, $updateData);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to reject procurement order'];
            }
            
            // Update linked request status if applicable
            if ($procurement['request_id']) {
                $requestModel = new RequestModel();
                $requestModel->update($procurement['request_id'], [
                    'status' => 'Declined',
                    'remarks' => $reason
                ]);
            }
            
            // Log activity
            $this->logProcurementActivity($id, 'procurement_rejected', 'Procurement order rejected: ' . $reason, $oldData, $updateData);
            
            $this->commit();
            return ['success' => true, 'procurement' => $result];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Procurement rejection error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reject procurement order'];
        }
    }
    
    /**
     * Receive procurement and generate assets
     */
    public function receiveProcurement($id, $receivedBy, $deliveryData) {
        try {
            $procurement = $this->getProcurementWithDetails($id);
            if (!$procurement) {
                return ['success' => false, 'message' => 'Procurement order not found'];
            }
            
            if ($procurement['status'] !== 'Approved') {
                return ['success' => false, 'message' => 'Procurement order is not approved'];
            }
            
            $this->beginTransaction();
            
            // Update procurement status
            $oldData = $procurement;
            $updateData = [
                'status' => 'Delivered',
                'delivery_status' => 'Complete',
                'delivery_date' => $deliveryData['delivery_date'] ?? date('Y-m-d'),
                'received_by' => $receivedBy,
                'received_at' => date('Y-m-d H:i:s'),
                'quality_check_notes' => Validator::sanitize($deliveryData['delivery_notes'] ?? '')
            ];
            
            $result = $this->update($id, $updateData);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update procurement status'];
            }
            
            // Generate assets from procurement
            $assetResult = $this->generateAssetsFromProcurement($id, $deliveryData);
            
            if (!$assetResult['success']) {
                $this->rollback();
                return $assetResult;
            }
            
            // Log activity
            $this->logProcurementActivity($id, 'procurement_received', 'Procurement order received and assets generated', $oldData, $updateData);
            
            $this->commit();
            return [
                'success' => true, 
                'procurement' => $result,
                'assets_created' => $assetResult['assets_created'],
                'message' => 'Procurement received successfully and ' . count($assetResult['assets_created']) . ' asset(s) created'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Procurement receive error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to receive procurement order'];
        }
    }
    
    /**
     * Generate assets from procurement
     */
    private function generateAssetsFromProcurement($procurementId, $deliveryData) {
        try {
            $procurement = $this->getProcurementWithDetails($procurementId);
            if (!$procurement) {
                return ['success' => false, 'message' => 'Procurement not found'];
            }
            
            $assetModel = new AssetModel();
            $createdAssets = [];
            
            // Generate asset reference prefix
            $assetRefPrefix = $this->getAssetRefPrefix();
            
            // Create assets based on quantity
            for ($i = 1; $i <= $procurement['quantity']; $i++) {
                // Generate unique asset reference
                $assetRef = $this->generateAssetReference($assetRefPrefix);
                
                // Get serial number if provided
                $serialNumber = $deliveryData["serial_number_{$i}"] ?? null;
                
                $assetData = [
                    'ref' => $assetRef,
                    'category_id' => $this->getOrCreateCategory($procurement['item_name']),
                    'name' => $procurement['item_name'],
                    'description' => $procurement['description'] ?: $procurement['specifications'],
                    'project_id' => $procurement['project_id'],
                    'vendor_id' => $procurement['vendor_id'],
                    'acquired_date' => $deliveryData['delivery_date'] ?? date('Y-m-d'),
                    'status' => 'available',
                    'is_client_supplied' => 0,
                    'acquisition_cost' => $procurement['unit_price'],
                    'serial_number' => $serialNumber,
                    'model' => $procurement['model'] ?: $deliveryData['model'],
                    'procurement_id' => $procurementId,
                    'unit_cost' => $procurement['unit_price']
                ];
                
                $asset = $assetModel->create($assetData);
                
                if (!$asset) {
                    return ['success' => false, 'message' => "Failed to create asset #{$i}"];
                }
                
                // Create procurement-asset link
                $this->linkProcurementAsset($procurementId, $asset['id'], $serialNumber);
                
                $createdAssets[] = $asset;
            }
            
            return ['success' => true, 'assets_created' => $createdAssets];
            
        } catch (Exception $e) {
            error_log("Generate assets from procurement error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate assets'];
        }
    }
    
    /**
     * Link procurement to generated asset
     */
    private function linkProcurementAsset($procurementId, $assetId, $serialNumber = null) {
        try {
            $sql = "INSERT INTO procurement_inventory (procurement_order_id, inventory_item_id, serial_number, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$procurementId, $assetId, $serialNumber]);
        } catch (Exception $e) {
            error_log("Link procurement asset error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get or create category for item
     */
    private function getOrCreateCategory($itemName) {
        try {
            $categoryModel = new CategoryModel();
            
            // Try to find existing category
            $category = $categoryModel->findFirst(['name' => $itemName]);
            
            if ($category) {
                return $category['id'];
            }
            
            // Create new category
            $newCategory = $categoryModel->create([
                'name' => $itemName,
                'description' => "Auto-created from procurement: {$itemName}",
                'is_consumable' => 0
            ]);
            
            return $newCategory ? $newCategory['id'] : 1; // Default to category ID 1 if creation fails
            
        } catch (Exception $e) {
            error_log("Get or create category error: " . $e->getMessage());
            return 1; // Default category
        }
    }
    
    /**
     * Generate PO number
     */
    private function generatePONumber() {
        try {
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
     * Generate asset reference
     */
    private function generateAssetReference($prefix) {
        try {
            // Get next sequence number
            $sql = "SELECT MAX(CAST(SUBSTRING(ref, LENGTH(?) + 1) AS UNSIGNED)) as max_seq FROM inventory_items WHERE ref LIKE ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$prefix, $prefix . '%']);
            $result = $stmt->fetch();
            
            $nextSeq = ($result['max_seq'] ?? 0) + 1;
            
            return $prefix . str_pad($nextSeq, 6, '0', STR_PAD_LEFT);
            
        } catch (Exception $e) {
            error_log("Generate asset reference error: " . $e->getMessage());
            return $prefix . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Get asset reference prefix
     */
    private function getAssetRefPrefix() {
        try {
            $settingsModel = new SystemSettingsModel();
            return $settingsModel->getSetting('asset_ref_prefix', 'CL');
        } catch (Exception $e) {
            return 'CL';
        }
    }
    
    /**
     * Get procurement with detailed information
     */
    public function getProcurementWithDetails($id) {
        try {
            $sql = "
                SELECT p.*, 
                       v.name as vendor_name, v.contact_person as vendor_contact, v.phone as vendor_phone,
                       pr.name as project_name, pr.location as project_location,
                       u.full_name as requested_by_name,
                       ua.full_name as approved_by_name,
                       ur.full_name as received_by_name,
                       r.description as request_description,
                       pt.term_name as payment_term_name
                FROM procurement p
                LEFT JOIN vendors v ON p.vendor_id = v.id
                LEFT JOIN projects pr ON p.project_id = pr.id
                LEFT JOIN users u ON p.requested_by = u.id
                LEFT JOIN users ua ON p.approved_by = ua.id
                LEFT JOIN users ur ON p.received_by = ur.id
                LEFT JOIN requests r ON p.request_id = r.id
                LEFT JOIN payment_terms pt ON v.payment_terms_id = pt.id
                WHERE p.id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Get procurement with details error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get procurements with filters and pagination
     */
    public function getProcurementWithFilters($filters = [], $page = 1, $perPage = 20) {
        try {
            $conditions = [];
            $params = [];
            
            // Project scoping for non-admin users
            $currentUser = Auth::getInstance()->getCurrentUser();
            $userModel = new UserModel();
            
            if (!in_array($currentUser['role_name'], ['System Admin', 'Finance Director', 'Asset Director'])) {
                if ($currentUser['current_project_id']) {
                    $conditions[] = "p.project_id = ?";
                    $params[] = $currentUser['current_project_id'];
                }
            }
            
            // Apply filters
            if (!empty($filters['status'])) {
                $conditions[] = "p.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['vendor_id'])) {
                $conditions[] = "p.vendor_id = ?";
                $params[] = $filters['vendor_id'];
            }
            
            if (!empty($filters['project_id'])) {
                $conditions[] = "p.project_id = ?";
                $params[] = $filters['project_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(p.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(p.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['search'])) {
                $conditions[] = "(p.po_number LIKE ? OR p.item_name LIKE ? OR v.name LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            // Count total records
            $countSql = "
                SELECT COUNT(*) 
                FROM procurement p
                LEFT JOIN vendors v ON p.vendor_id = v.id
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            
            // Get paginated data
            $offset = ($page - 1) * $perPage;
            $orderBy = $filters['order_by'] ?? 'p.created_at DESC';
            
            $dataSql = "
                SELECT p.*, 
                       v.name as vendor_name,
                       pr.name as project_name,
                       u.full_name as requested_by_name
                FROM procurement p
                LEFT JOIN vendors v ON p.vendor_id = v.id
                LEFT JOIN projects pr ON p.project_id = pr.id
                LEFT JOIN users u ON p.requested_by = u.id
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
            error_log("Get procurement with filters error: " . $e->getMessage());
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
     * Get procurement statistics
     */
    public function getProcurementStatistics($projectId = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($projectId) {
                $conditions[] = "project_id = ?";
                $params[] = $projectId;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    COUNT(*) as total_procurement,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'For Revision' THEN 1 ELSE 0 END) as for_revision,
                    SUM(net_total) as total_value,
                    AVG(net_total) as average_value
                FROM procurement 
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result ?: [
                'total_procurement' => 0,
                'pending' => 0,
                'approved' => 0,
                'delivered' => 0,
                'rejected' => 0,
                'for_revision' => 0,
                'total_value' => 0,
                'average_value' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Get procurement statistics error: " . $e->getMessage());
            return [
                'total_procurement' => 0,
                'pending' => 0,
                'approved' => 0,
                'delivered' => 0,
                'rejected' => 0,
                'for_revision' => 0,
                'total_value' => 0,
                'average_value' => 0
            ];
        }
    }
    
    /**
     * Get assets generated from procurement
     */
    public function getProcurementAssets($procurementId) {
        try {
            $sql = "
                SELECT a.*, pi.serial_number as procurement_serial
                FROM inventory_items a
                INNER JOIN procurement_inventory pi ON a.id = pi.inventory_item_id
                WHERE pi.procurement_order_id = ?
                ORDER BY a.created_at ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get procurement assets error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Log procurement activity
     */
    private function logProcurementActivity($procurementId, $action, $description, $oldValues = null, $newValues = null) {
        try {
            $auth = Auth::getInstance();
            $user = $auth->getCurrentUser();
            
            $sql = "INSERT INTO procurement_logs (procurement_id, user_id, action, old_status, new_status, description, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $procurementId,
                $user['id'] ?? null,
                $action,
                $oldValues['status'] ?? null,
                $newValues['status'] ?? null,
                $description
            ]);
        } catch (Exception $e) {
            error_log("Procurement activity logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Get received procurements (for asset creation dropdown) - Legacy and Multi-item
     */
    public function getReceivedProcurements() {
        try {
            // Get legacy single-item procurements
            $legacyProcurements = $this->getLegacyReceivedProcurements();
            
            // Get multi-item procurement orders (check if class exists)
            $multiItemProcurements = [];
            if (class_exists('ProcurementOrderModel')) {
                $procurementOrderModel = new ProcurementOrderModel();
                if (method_exists($procurementOrderModel, 'getReceivedProcurementOrders')) {
                    $multiItemProcurements = $procurementOrderModel->getReceivedProcurementOrders();
                }
            }
            
            // Combine and format results
            $allProcurements = [];
            
            // Add legacy procurements
            foreach ($legacyProcurements as $procurement) {
                $allProcurements[] = [
                    'id' => $procurement['id'],
                    'type' => 'legacy',
                    'po_number' => $procurement['po_number'],
                    'title' => $procurement['item_name'],
                    'description' => $procurement['description'],
                    'vendor_name' => $procurement['vendor_name'],
                    'project_name' => $procurement['project_name'],
                    'received_at' => $procurement['received_at'],
                    'total_value' => $procurement['quantity'] * $procurement['unit_price'],
                    'item_count' => 1
                ];
            }
            
            // Add multi-item procurements
            foreach ($multiItemProcurements as $order) {
                $allProcurements[] = [
                    'id' => $order['id'],
                    'type' => 'multi_item',
                    'po_number' => $order['po_number'],
                    'title' => $order['title'],
                    'description' => $order['title'],
                    'vendor_name' => $order['vendor_name'],
                    'project_name' => $order['project_name'],
                    'received_at' => $order['received_at'],
                    'total_value' => $order['net_total'],
                    'item_count' => $order['item_count']
                ];
            }
            
            // Sort by received date (most recent first)
            usort($allProcurements, function($a, $b) {
                return strtotime($b['received_at']) - strtotime($a['received_at']);
            });
            
            return $allProcurements;
            
        } catch (Exception $e) {
            error_log("Get received procurements error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get legacy received procurements
     */
    private function getLegacyReceivedProcurements() {
        try {
            $sql = "
                SELECT p.id, p.po_number, p.item_name, p.description, p.quantity, 
                       p.unit_price, p.vendor_id, p.project_id, p.received_at,
                       v.name as vendor_name,
                       pr.name as project_name
                FROM procurement p
                LEFT JOIN vendors v ON p.vendor_id = v.id
                LEFT JOIN projects pr ON p.project_id = pr.id
                WHERE p.status = 'Delivered' 
                  AND p.received_at IS NOT NULL
                ORDER BY p.received_at DESC, p.po_number DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get legacy received procurements error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create multi-item procurement order (new method)
     */
    public function createMultiItemProcurement($orderData, $items = []) {
        try {
            if (!class_exists('ProcurementOrderModel')) {
                return ['success' => false, 'message' => 'Multi-item procurement not available'];
            }
            
            $procurementOrderModel = new ProcurementOrderModel();
            if (!method_exists($procurementOrderModel, 'createProcurementOrder')) {
                return ['success' => false, 'message' => 'Multi-item procurement method not available'];
            }
            
            return $procurementOrderModel->createProcurementOrder($orderData, $items);
            
        } catch (Exception $e) {
            error_log("Create multi-item procurement error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create multi-item procurement'];
        }
    }
    
    /**
     * Get procurement details (supports both legacy and multi-item)
     */
    public function getProcurementDetails($id, $type = 'legacy') {
        try {
            if ($type === 'multi_item') {
                if (!class_exists('ProcurementOrderModel')) {
                    return false;
                }
                
                $procurementOrderModel = new ProcurementOrderModel();
                if (!method_exists($procurementOrderModel, 'getProcurementOrderWithItems')) {
                    return false;
                }
                
                return $procurementOrderModel->getProcurementOrderWithItems($id);
            } else {
                // Legacy procurement
                return $this->getProcurementWithDetails($id);
            }
            
        } catch (Exception $e) {
            error_log("Get procurement details error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete procurement (only if not approved/delivered)
     */
    public function deleteProcurement($id) {
        try {
            $procurement = $this->find($id);
            if (!$procurement) {
                return ['success' => false, 'message' => 'Procurement order not found'];
            }
            
            if (in_array($procurement['status'], ['Approved', 'Delivered'])) {
                return ['success' => false, 'message' => 'Cannot delete approved or delivered procurement orders'];
            }
            
            $this->beginTransaction();

            // Remove related data
            $sql = "DELETE FROM procurement_inventory WHERE procurement_order_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            // Delete procurement
            $result = $this->delete($id);
            
            if ($result) {
                // Log activity
                $this->logProcurementActivity($id, 'procurement_deleted', 'Procurement order deleted', $procurement, null);
                
                $this->commit();
                return ['success' => true, 'message' => 'Procurement order deleted successfully'];
            } else {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to delete procurement order'];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Procurement deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete procurement order'];
        }
    }
}
?>
