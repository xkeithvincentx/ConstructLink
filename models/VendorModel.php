<?php
/**
 * ConstructLink™ Vendor Model - Enhanced with Bank Accounts and Payment Terms
 * Handles vendor management with comprehensive procurement workflow features
 * Aligned with schema_complete.sql structure
 */

class VendorModel extends BaseModel {
    protected $table = 'vendors';
    protected $fillable = [
        'name', 'vendor_type', 'registered_name', 'contact_info', 'address', 'zip_code',
        'phone', 'email', 'contact_person', 'first_name', 'middle_name', 'last_name',
        'payment_terms_id', 'categories', 'tax_id', 'tin', 'rdo_code', 
        'is_preferred', 'rating', 'notes'
    ];
    
    /**
     * Create vendor with validation and related data
     */
    public function createVendor($data) {
        try {
            // Validate required fields
            $validation = $this->validate($data, [
                'name' => 'required|max:200',
                'email' => 'email|max:100',
                'phone' => 'max:50'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $this->beginTransaction();
            
            // Check if vendor name already exists
            if ($this->findFirst(['name' => $data['name']])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Vendor name already exists'];
            }
            
            // Prepare vendor data
            $vendorData = [
                'name' => Validator::sanitize($data['name']),
                'vendor_type' => $data['vendor_type'] ?? 'Company',
                'registered_name' => Validator::sanitize($data['registered_name'] ?? ''),
                'contact_info' => Validator::sanitize($data['contact_info'] ?? ''),
                'address' => Validator::sanitize($data['address'] ?? ''),
                'zip_code' => Validator::sanitize($data['zip_code'] ?? ''),
                'phone' => Validator::sanitize($data['phone'] ?? ''),
                'email' => Validator::sanitize($data['email'] ?? ''),
                'contact_person' => Validator::sanitize($data['contact_person'] ?? ''),
                'payment_terms_id' => !empty($data['payment_terms_id']) ? (int)$data['payment_terms_id'] : null,
                'tax_id' => Validator::sanitize($data['tax_id'] ?? ''),
                'tin' => Validator::sanitize($data['tin'] ?? ''),
                'rdo_code' => Validator::sanitize($data['rdo_code'] ?? ''),
                'is_preferred' => isset($data['is_preferred']) ? 1 : 0,
                'rating' => !empty($data['rating']) ? (float)$data['rating'] : null,
                'notes' => Validator::sanitize($data['notes'] ?? ''),
                'categories' => !empty($data['categories']) ? json_encode($data['categories']) : null
            ];
            
            // Add individual name fields for sole proprietors
            if ($data['vendor_type'] === 'Sole Proprietor') {
                $vendorData['first_name'] = Validator::sanitize($data['first_name'] ?? '');
                $vendorData['middle_name'] = Validator::sanitize($data['middle_name'] ?? '');
                $vendorData['last_name'] = Validator::sanitize($data['last_name'] ?? '');
            }
            
            // Create vendor
            $vendor = $this->create($vendorData);
            
            if (!$vendor) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create vendor'];
            }
            
            // Add bank accounts if provided
            if (!empty($data['bank_accounts'])) {
                foreach ($data['bank_accounts'] as $bankData) {
                    $bankResult = $this->addVendorBank($vendor['id'], $bankData);
                    if (!$bankResult['success']) {
                        $this->rollback();
                        return $bankResult;
                    }
                }
            }
            
            // Add vendor category assignments if provided
            if (!empty($data['category_ids'])) {
                foreach ($data['category_ids'] as $categoryId) {
                    $categoryResult = $this->addVendorCategoryAssignment($vendor['id'], $categoryId);
                    if (!$categoryResult['success']) {
                        $this->rollback();
                        return $categoryResult;
                    }
                }
            }
            
            // Log activity
            $this->logVendorActivity($vendor['id'], 'vendor_created', 'Vendor created', null, $vendorData);
            
            $this->commit();
            return ['success' => true, 'vendor' => $vendor];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Vendor creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create vendor'];
        }
    }
    
    /**
     * Update vendor with related data
     */
    public function updateVendor($id, $data) {
        try {
            $vendor = $this->find($id);
            if (!$vendor) {
                return ['success' => false, 'message' => 'Vendor not found'];
            }
            
            // Validate data
            $validation = $this->validate($data, [
                'name' => 'max:200',
                'email' => 'email|max:100',
                'phone' => 'max:50'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $this->beginTransaction();
            
            // Check for duplicate name (excluding current vendor)
            if (isset($data['name']) && !empty(trim($data['name']))) {
                $sql = "SELECT id FROM vendors WHERE name = ? AND id != ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([trim($data['name']), $id]);
                if ($stmt->fetch()) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Vendor name already exists'];
                }
            }
            
            // Prepare update data
            $updateData = [];
            $allowedFields = ['name', 'contact_info', 'address', 'phone', 'email', 'contact_person', 'payment_terms_id', 'tax_id', 'tin', 'vendor_type', 'rdo_code', 'rating', 'notes'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    if ($field === 'payment_terms_id') {
                        $updateData[$field] = !empty($data[$field]) ? (int)$data[$field] : null;
                    } elseif ($field === 'rating') {
                        $updateData[$field] = !empty($data[$field]) ? (float)$data[$field] : null;
                    } else {
                        $updateData[$field] = Validator::sanitize($data[$field] ?? '');
                    }
                }
            }
            
            $updateData['is_preferred'] = isset($data['is_preferred']) ? 1 : 0;
            
            if (array_key_exists('categories', $data)) {
                $updateData['categories'] = !empty($data['categories']) && is_array($data['categories']) ? json_encode($data['categories']) : null;
            }
            
            // Update vendor
            $oldData = $vendor;
            $updatedVendor = $this->update($id, $updateData);
            
            if (!$updatedVendor) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update vendor'];
            }
            
            // Update bank accounts if provided
            if (isset($data['bank_accounts'])) {
                // Remove existing bank accounts
                $this->removeAllVendorBanks($id);
                
                // Add new bank accounts
                foreach ($data['bank_accounts'] as $bankData) {
                    $bankResult = $this->addVendorBank($id, $bankData);
                    if (!$bankResult['success']) {
                        $this->rollback();
                        return $bankResult;
                    }
                }
            }
            
            // Update vendor category assignments if provided
            if (isset($data['category_ids'])) {
                // Remove existing category assignments
                $this->removeAllVendorCategoryAssignments($id);
                
                // Add new category assignments
                foreach ($data['category_ids'] as $categoryId) {
                    $categoryResult = $this->addVendorCategoryAssignment($id, $categoryId);
                    if (!$categoryResult['success']) {
                        $this->rollback();
                        return $categoryResult;
                    }
                }
            }
            
            // Log activity
            $this->logVendorActivity($id, 'vendor_updated', 'Vendor updated', $oldData, $updateData);
            
            $this->commit();
            return ['success' => true, 'vendor' => $updatedVendor];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Vendor update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update vendor: ' . $e->getMessage()];
        }
    }
    
    /**
     * Add bank account to vendor
     */
    public function addVendorBank($vendorId, $bankData) {
        try {
            $validation = $this->validate($bankData, [
                'bank_name' => 'required|max:255',
                'account_number' => 'required|max:100',
                'account_name' => 'required|max:255',
                'account_type' => 'required'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $sql = "INSERT INTO vendor_banks (vendor_id, bank_name, account_number, account_name, account_type, currency, bank_category, swift_code, branch, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $vendorId,
                Validator::sanitize($bankData['bank_name']),
                Validator::sanitize($bankData['account_number']),
                Validator::sanitize($bankData['account_name']),
                $bankData['account_type'] ?? 'Checking',
                $bankData['currency'] ?? 'PHP',
                $bankData['bank_category'] ?? 'Primary',
                Validator::sanitize($bankData['swift_code'] ?? ''),
                Validator::sanitize($bankData['branch'] ?? ''),
                isset($bankData['is_active']) ? 1 : 1
            ]);
            
            if ($result) {
                return ['success' => true, 'bank_id' => $this->db->lastInsertId()];
            } else {
                return ['success' => false, 'message' => 'Failed to add bank account'];
            }
            
        } catch (Exception $e) {
            error_log("Add vendor bank error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add bank account'];
        }
    }
    
    /**
     * Update vendor bank account
     */
    public function updateVendorBank($bankId, $bankData) {
        try {
            $validation = $this->validate($bankData, [
                'bank_name' => 'required|max:255',
                'account_number' => 'required|max:100',
                'account_name' => 'required|max:255',
                'account_type' => 'required'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $sql = "UPDATE vendor_banks SET 
                    bank_name = ?, account_number = ?, account_name = ?, account_type = ?, 
                    currency = ?, bank_category = ?, swift_code = ?, branch = ?, is_active = ?, 
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                Validator::sanitize($bankData['bank_name']),
                Validator::sanitize($bankData['account_number']),
                Validator::sanitize($bankData['account_name']),
                $bankData['account_type'] ?? 'Checking',
                $bankData['currency'] ?? 'PHP',
                $bankData['bank_category'] ?? 'Primary',
                Validator::sanitize($bankData['swift_code'] ?? ''),
                Validator::sanitize($bankData['branch'] ?? ''),
                isset($bankData['is_active']) ? 1 : 1,
                $bankId
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Bank account updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update bank account'];
            }
            
        } catch (Exception $e) {
            error_log("Update vendor bank error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update bank account'];
        }
    }
    
    /**
     * Delete vendor bank account
     */
    public function deleteVendorBank($bankId) {
        try {
            $sql = "DELETE FROM vendor_banks WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$bankId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Bank account deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete bank account'];
            }
            
        } catch (Exception $e) {
            error_log("Delete vendor bank error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete bank account'];
        }
    }
    
    /**
     * Add vendor category assignment
     */
    public function addVendorCategoryAssignment($vendorId, $categoryId) {
        try {
            $sql = "INSERT INTO vendor_category_assignments (vendor_id, category_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$vendorId, $categoryId]);
            
            if ($result) {
                return ['success' => true, 'assignment_id' => $this->db->lastInsertId()];
            } else {
                return ['success' => false, 'message' => 'Failed to add vendor category assignment'];
            }
            
        } catch (Exception $e) {
            error_log("Add vendor category assignment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add vendor category assignment'];
        }
    }
    
    /**
     * Remove all vendor bank accounts
     */
    public function removeAllVendorBanks($vendorId) {
        try {
            $sql = "DELETE FROM vendor_banks WHERE vendor_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$vendorId]);
        } catch (Exception $e) {
            error_log("Remove vendor banks error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove all vendor category assignments
     */
    public function removeAllVendorCategoryAssignments($vendorId) {
        try {
            $sql = "DELETE FROM vendor_category_assignments WHERE vendor_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$vendorId]);
        } catch (Exception $e) {
            error_log("Remove vendor category assignments error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get vendor with all related data
     */
    public function getVendorWithDetails($id) {
        try {
            // Get vendor basic info
            $sql = "
                SELECT v.*, pt.term_name as payment_term_name, pt.description as payment_term_description,
                       pt.days as payment_days, pt.percentage_upfront
                FROM vendors v
                LEFT JOIN payment_terms pt ON v.payment_terms_id = pt.id
                WHERE v.id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $vendor = $stmt->fetch();
            
            if (!$vendor) {
                return false;
            }
            
            // Decode categories JSON
            $vendor['categories'] = $vendor['categories'] ? json_decode($vendor['categories'], true) ?? [] : [];
            
            // Get bank accounts
            $vendor['bank_accounts'] = $this->getVendorBanks($id);
            
            // Get category assignments
            $vendor['assigned_categories'] = $this->getVendorCategoryAssignments($id);
            
            return $vendor;
            
        } catch (Exception $e) {
            error_log("Get vendor with details error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get vendor with bank details
     */
    public function getVendorWithBankDetails($id) {
        try {
            $vendor = $this->getVendorWithDetails($id);
            if (!$vendor) {
                return false;
            }
            
            // Get procurement statistics
            $vendor['procurement_stats'] = $this->getVendorProcurementStats($id);
            
            return $vendor;
            
        } catch (Exception $e) {
            error_log("Get vendor with bank details error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get vendor bank accounts
     */
    public function getVendorBanks($vendorId) {
        try {
            $sql = "SELECT * FROM vendor_banks WHERE vendor_id = ? AND is_active = 1 ORDER BY bank_category ASC, created_at ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get vendor banks error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get vendor category assignments
     */
    public function getVendorCategoryAssignments($vendorId) {
        try {
            $sql = "
                SELECT vca.*, vc.name as category_name, vc.description as category_description
                FROM vendor_category_assignments vca
                LEFT JOIN vendor_categories vc ON vca.category_id = vc.id
                WHERE vca.vendor_id = ?
                ORDER BY vc.name ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get vendor category assignments error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get vendor procurement statistics
     */
    public function getVendorProcurementStats($vendorId) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(net_total) as total_value,
                    AVG(net_total) as average_order_value,
                    COUNT(CASE WHEN status = 'Delivered' THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN status IN ('Pending', 'Approved') THEN 1 END) as pending_orders,
                    MAX(created_at) as last_order_date
                FROM procurement_orders
                WHERE vendor_id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            $stats = $stmt->fetch();
            
            return $stats ?: [
                'total_orders' => 0,
                'total_value' => 0,
                'average_order_value' => 0,
                'completed_orders' => 0,
                'pending_orders' => 0,
                'last_order_date' => null
            ];
            
        } catch (Exception $e) {
            error_log("Get vendor procurement stats error: " . $e->getMessage());
            return [
                'total_orders' => 0,
                'total_value' => 0,
                'average_order_value' => 0,
                'completed_orders' => 0,
                'pending_orders' => 0,
                'last_order_date' => null
            ];
        }
    }
    
    /**
     * Get vendors with filters and pagination
     */
    public function getVendorsWithFilters($filters = [], $page = 1, $perPage = 20) {
        try {
            $conditions = [];
            $params = [];
            
            // Apply filters
            if (!empty($filters['search'])) {
                $conditions[] = "(v.name LIKE ? OR v.contact_person LIKE ? OR v.email LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            if (!empty($filters['payment_terms_id'])) {
                $conditions[] = "v.payment_terms_id = ?";
                $params[] = $filters['payment_terms_id'];
            }
            
            if (isset($filters['is_preferred'])) {
                $conditions[] = "v.is_preferred = ?";
                $params[] = $filters['is_preferred'];
            }
            
            if (!empty($filters['category_id'])) {
                $conditions[] = "EXISTS (SELECT 1 FROM vendor_category_assignments vca WHERE vca.vendor_id = v.id AND vca.category_id = ?)";
                $params[] = $filters['category_id'];
            }
            
            if (!empty($filters['rating_min'])) {
                $conditions[] = "v.rating >= ?";
                $params[] = $filters['rating_min'];
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            // Count total records
            $countSql = "SELECT COUNT(*) FROM vendors v {$whereClause}";
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            
            // Get paginated data
            $offset = ($page - 1) * $perPage;
            $orderBy = $filters['order_by'] ?? 'v.name ASC';
            
            $dataSql = "
                SELECT v.*, pt.term_name as payment_term_name,
                       COUNT(po.id) as procurement_count,
                       COALESCE(SUM(po.net_total), 0) as total_procurement_value
                FROM vendors v
                LEFT JOIN payment_terms pt ON v.payment_terms_id = pt.id
                LEFT JOIN procurement_orders po ON v.id = po.vendor_id
                {$whereClause}
                GROUP BY v.id
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}
            ";
            
            $stmt = $this->db->prepare($dataSql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Add categories and bank accounts to each vendor
            foreach ($data as &$vendor) {
                $vendor['categories'] = $vendor['categories'] ? json_decode($vendor['categories'], true) ?? [] : [];
                $vendor['assigned_categories'] = $this->getVendorCategoryAssignments($vendor['id']);
                $vendor['bank_count'] = count($this->getVendorBanks($vendor['id']));
            }
            
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
            error_log("Get vendors with filters error: " . $e->getMessage());
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
     * Get vendors by supply category
     */
    public function getVendorsByCategory($categoryId) {
        try {
            $sql = "
                SELECT DISTINCT v.*, pt.term_name as payment_term_name
                FROM vendors v
                INNER JOIN vendor_category_assignments vca ON v.id = vca.vendor_id
                LEFT JOIN payment_terms pt ON v.payment_terms_id = pt.id
                WHERE vca.category_id = ?
                ORDER BY v.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$categoryId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get vendors by category error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get preferred vendors
     */
    public function getPreferredVendors() {
        try {
            $sql = "
                SELECT v.*, pt.term_name as payment_term_name
                FROM vendors v
                LEFT JOIN payment_terms pt ON v.payment_terms_id = pt.id
                WHERE v.is_preferred = 1
                ORDER BY v.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get preferred vendors error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get active vendors
     */
    public function getActiveVendors() {
        try {
            $sql = "
                SELECT v.id, v.name, v.contact_person, v.email, v.phone
                FROM vendors v
                ORDER BY v.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get active vendors error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get vendor statistics for dashboard and analytics
     */
    public function getVendorStatistics($vendorId = null) {
        try {
            if ($vendorId) {
                // Specific vendor statistics - procurement-focused metrics
                $sql = "
                    SELECT 
                        v.id,
                        v.name,
                        v.rating,
                        v.is_preferred,
                        COUNT(po.id) as total_orders,
                        COUNT(CASE WHEN po.delivery_status IN ('Delivered', 'Received') OR po.status IN ('Delivered', 'Received') THEN 1 END) as completed_orders,
                        COUNT(CASE WHEN po.status = 'Rejected' THEN 1 END) as cancelled_orders,
                        COUNT(CASE WHEN po.delivery_status IN ('Pending', 'Scheduled') OR po.status IN ('Draft', 'Pending', 'Reviewed', 'Approved', 'Scheduled for Delivery') THEN 1 END) as pending_orders,
                        SUM(CASE WHEN po.net_total IS NOT NULL THEN po.net_total ELSE 0 END) as total_value,
                        AVG(CASE WHEN po.net_total IS NOT NULL THEN po.net_total ELSE 0 END) as avg_order_value,
                        COUNT(CASE WHEN po.actual_delivery_date IS NOT NULL AND po.scheduled_delivery_date IS NOT NULL 
                                   AND po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) as on_time_deliveries,
                        (COUNT(CASE WHEN po.actual_delivery_date IS NOT NULL AND po.scheduled_delivery_date IS NOT NULL 
                                   AND po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) * 100.0 / 
                         NULLIF(COUNT(CASE WHEN po.actual_delivery_date IS NOT NULL AND po.scheduled_delivery_date IS NOT NULL THEN 1 END), 0)) as on_time_rate,
                        (COUNT(CASE WHEN po.delivery_status IN ('Delivered', 'Received') OR po.status IN ('Delivered', 'Received') THEN 1 END) * 100.0 / NULLIF(COUNT(po.id), 0)) as completion_rate,
                        (SELECT COUNT(*) FROM vendor_banks WHERE vendor_id = ? AND is_active = 1) as total_bank_accounts,
                        (SELECT COUNT(*) FROM vendor_category_assignments WHERE vendor_id = ?) as total_categories
                    FROM vendors v
                    LEFT JOIN procurement_orders po ON v.id = po.vendor_id
                    WHERE v.id = ?
                    GROUP BY v.id, v.name, v.rating, v.is_preferred
                ";
                $params = [$vendorId, $vendorId, $vendorId];
            } else {
                // Overall vendor statistics for dashboard
                $sql = "
                    SELECT 
                        COUNT(v.id) as total_vendors,
                        COUNT(CASE WHEN v.is_preferred = 1 THEN 1 END) as preferred_vendors,
                        COUNT(CASE WHEN EXISTS(
                            SELECT 1 FROM procurement_orders po 
                            WHERE po.vendor_id = v.id 
                            AND po.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        ) THEN 1 END) as active_vendors_30d,
                        AVG(CASE WHEN v.rating IS NOT NULL THEN v.rating END) as average_rating,
                        COUNT(CASE WHEN v.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_vendors_30d,
                        (SELECT COUNT(*) FROM vendor_banks WHERE is_active = 1) as total_bank_accounts,
                        (SELECT COUNT(*) FROM vendor_category_assignments) as total_category_assignments
                    FROM vendors v
                    WHERE 1=1
                ";
                $params = [];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            if ($vendorId) {
                // Return specific vendor statistics format
                return [
                    'id' => (int)($result['id'] ?? 0),
                    'name' => $result['name'] ?? '',
                    'rating' => (float)($result['rating'] ?? 0),
                    'is_preferred' => (bool)($result['is_preferred'] ?? false),
                    'total_orders' => (int)($result['total_orders'] ?? 0),
                    'completed_orders' => (int)($result['completed_orders'] ?? 0),
                    'cancelled_orders' => (int)($result['cancelled_orders'] ?? 0),
                    'pending_orders' => (int)($result['pending_orders'] ?? 0),
                    'total_value' => (float)($result['total_value'] ?? 0),
                    'avg_order_value' => (float)($result['avg_order_value'] ?? 0),
                    'on_time_deliveries' => (int)($result['on_time_deliveries'] ?? 0),
                    'on_time_rate' => (float)($result['on_time_rate'] ?? 0),
                    'completion_rate' => (float)($result['completion_rate'] ?? 0),
                    'total_bank_accounts' => (int)($result['total_bank_accounts'] ?? 0),
                    'total_categories' => (int)($result['total_categories'] ?? 0)
                ];
            } else {
                // Return overall statistics format for dashboard
                return [
                    'total_vendors' => (int)($result['total_vendors'] ?? 0),
                    'preferred_vendors' => (int)($result['preferred_vendors'] ?? 0),
                    'active_vendors_30d' => (int)($result['active_vendors_30d'] ?? 0),
                    'average_rating' => (float)($result['average_rating'] ?? 0),
                    'new_vendors_30d' => (int)($result['new_vendors_30d'] ?? 0),
                    'total_bank_accounts' => (int)($result['total_bank_accounts'] ?? 0),
                    'total_category_assignments' => (int)($result['total_category_assignments'] ?? 0)
                ];
            }
            
        } catch (Exception $e) {
            error_log("Vendor statistics error: " . $e->getMessage());
            return [
                'total_vendors' => 0,
                'preferred_vendors' => 0,
                'active_vendors_30d' => 0,
                'average_rating' => 0,
                'new_vendors_30d' => 0,
                'total_bank_accounts' => 0,
                'total_category_assignments' => 0
            ];
        }
    }
    
    /**
     * Get vendors for dropdown/select
     */
    public function getVendorsForSelect($categoryId = null) {
        try {
            $sql = "SELECT DISTINCT v.id, v.name FROM vendors v";
            $params = [];
            
            if ($categoryId) {
                $sql .= " INNER JOIN vendor_category_assignments vca ON v.id = vca.vendor_id WHERE vca.category_id = ?";
                $params[] = $categoryId;
            }
            
            $sql .= " ORDER BY v.name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get vendors for select error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Toggle vendor status (preferred/not preferred)
     */
    public function toggleVendorStatus($vendorId) {
        try {
            $vendor = $this->find($vendorId);
            if (!$vendor) {
                return ['success' => false, 'message' => 'Vendor not found'];
            }
            
            $newStatus = $vendor['is_preferred'] ? 0 : 1;
            $result = $this->update($vendorId, ['is_preferred' => $newStatus]);
            
            if ($result) {
                $statusText = $newStatus ? 'preferred' : 'not preferred';
                $this->logVendorActivity($vendorId, 'status_changed', "Vendor marked as {$statusText}", 
                    ['is_preferred' => $vendor['is_preferred']], ['is_preferred' => $newStatus]);
                
                return ['success' => true, 'new_status' => $newStatus, 'message' => "Vendor status updated to {$statusText}"];
            } else {
                return ['success' => false, 'message' => 'Failed to update vendor status'];
            }
            
        } catch (Exception $e) {
            error_log("Toggle vendor status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update vendor status'];
        }
    }
    
    /**
     * Delete vendor
     */
    public function deleteVendor($id) {
        try {
            $vendor = $this->find($id);
            if (!$vendor) {
                return ['success' => false, 'message' => 'Vendor not found'];
            }
            
            // Check if vendor has related procurement records
            $sql = "SELECT COUNT(*) FROM procurement_orders WHERE vendor_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $procurementCount = $stmt->fetchColumn();
            
            if ($procurementCount > 0) {
                return ['success' => false, 'message' => 'Cannot delete vendor with existing procurement records'];
            }
            
            // Check if vendor has related assets
            $sql = "SELECT COUNT(*) FROM inventory_items WHERE vendor_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $assetCount = $stmt->fetchColumn();
            
            if ($assetCount > 0) {
                return ['success' => false, 'message' => 'Cannot delete vendor with existing assets'];
            }
            
            $this->beginTransaction();
            
            // Remove related data
            $this->removeAllVendorBanks($id);
            $this->removeAllVendorCategoryAssignments($id);
            
            // Delete vendor
            $result = $this->delete($id);
            
            if ($result) {
                // Log activity
                $this->logVendorActivity($id, 'vendor_deleted', 'Vendor deleted', $vendor, null);
                
                $this->commit();
                return ['success' => true, 'message' => 'Vendor deleted successfully'];
            } else {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to delete vendor'];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Vendor deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete vendor'];
        }
    }
    
    /**
     * Get all payment terms
     */
    public function getPaymentTerms() {
        try {
            $sql = "SELECT * FROM payment_terms ORDER BY term_name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get payment terms error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all vendor categories
     */
    public function getVendorCategories() {
        try {
            $sql = "SELECT * FROM vendor_categories ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get vendor categories error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get vendor assets by category
     */
    public function getVendorAssetsByCategory($vendorId) {
        try {
            $sql = "
                SELECT c.name as category_name, COUNT(a.id) as asset_count, 
                       SUM(a.acquisition_cost) as total_value
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                WHERE a.vendor_id = ?
                GROUP BY c.id, c.name
                ORDER BY asset_count DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get vendor assets by category error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update vendor enhanced with categories and payment terms
     */
    public function updateVendorEnhanced($id, $data) {
        try {
            $vendor = $this->find($id);
            if (!$vendor) {
                return ['success' => false, 'message' => 'Vendor not found'];
            }
            
            $this->beginTransaction();
            
            // Update basic vendor info
            $result = $this->updateVendor($id, $data);
            
            if (!$result['success']) {
                $this->rollback();
                return $result;
            }
            
            // Update payment terms if provided
            if (isset($data['payment_terms'])) {
                foreach ($data['payment_terms'] as $termData) {
                    // Handle payment terms updates if needed
                }
            }
            
            $this->commit();
            return ['success' => true, 'vendor' => $result['vendor']];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Enhanced vendor update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update vendor'];
        }
    }
    
    /**
     * Get vendor activity log
     */
    public function getVendorActivityLog($vendorId, $limit = 20) {
        try {
            $sql = "
                SELECT vl.*, u.full_name as user_name
                FROM vendor_logs vl
                LEFT JOIN users u ON vl.user_id = u.id
                WHERE vl.vendor_id = ?
                ORDER BY vl.created_at DESC
                LIMIT ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId, $limit]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get vendor activity log error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Log vendor activity
     */
    private function logVendorActivity($vendorId, $action, $description, $oldValues = null, $newValues = null) {
        try {
            // Skip logging if Auth class is not available (for command line usage)
            if (!class_exists('Auth')) {
                return;
            }
            
            $auth = Auth::getInstance();
            $user = $auth->getCurrentUser();
            
            $sql = "INSERT INTO vendor_logs (vendor_id, user_id, action, description, old_values, new_values, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $vendorId,
                $user['id'] ?? null,
                $action,
                $description,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null
            ]);
        } catch (Exception $e) {
            error_log("Vendor activity logging error: " . $e->getMessage());
        }
    }
    
    /**
     * ==================== INTELLIGENCE & ANALYTICS ====================
     * Advanced data methods for vendor intelligence features
     */
    
    
    /**
     * Get active vendors with intelligence analysis data
     */
    public function getActiveVendorsWithAnalysis() {
        try {
            $sql = "
                SELECT v.*, 
                       COUNT(po.id) as order_count,
                       MAX(po.created_at) as last_order_date
                FROM vendors v
                LEFT JOIN procurement_orders po ON v.id = po.vendor_id
                WHERE 1=1
                GROUP BY v.id
                HAVING order_count > 0
                ORDER BY v.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get active vendors error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get vendors for select dropdown with enhanced intelligence data
     */
    public function getVendorsForSelectEnhanced($categoryId = null) {
        try {
            $sql = "
                SELECT DISTINCT v.id, v.name, v.is_preferred, v.rating
                FROM vendors v
                LEFT JOIN vendor_category_assignments vca ON v.id = vca.vendor_id
                WHERE 1=1
            ";
            
            $params = [];
            if ($categoryId) {
                $sql .= " AND vca.category_id = ?";
                $params[] = $categoryId;
            }
            
            $sql .= " ORDER BY v.is_preferred DESC, v.name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get vendors for select error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get vendor with enhanced intelligence details including banks and categories
     */
    public function getVendorWithIntelligenceDetails($vendorId) {
        try {
            $vendor = $this->find($vendorId);
            if (!$vendor) {
                return null;
            }
            
            // Get bank accounts
            $vendor['bank_accounts'] = $this->getVendorBanks($vendorId);
            
            // Get category assignments
            $vendor['categories'] = $this->getVendorCategories($vendorId);
            
            // Get payment terms
            if ($vendor['payment_terms_id']) {
                $sql = "SELECT * FROM payment_terms WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$vendor['payment_terms_id']]);
                $vendor['payment_terms'] = $stmt->fetch();
            }
            
            return $vendor;
            
        } catch (Exception $e) {
            error_log("Get vendor with details error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get vendor with enhanced bank and analytics details
     */
    public function getVendorWithAnalyticsDetails($vendorId) {
        try {
            $vendor = $this->getVendorWithIntelligenceDetails($vendorId);
            if (!$vendor) {
                return null;
            }
            
            // Add procurement statistics
            $sql = "
                SELECT 
                    COUNT(po.id) as total_orders,
                    SUM(po.net_total) as total_value,
                    AVG(po.net_total) as avg_order_value,
                    COUNT(CASE WHEN po.delivery_status = 'Delivered' THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN po.actual_delivery_date <= po.scheduled_delivery_date THEN 1 END) as on_time_orders
                FROM procurement_orders po
                WHERE po.vendor_id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            $stats = $stmt->fetch();
            
            $vendor['procurement_stats'] = [
                'total_orders' => (int)($stats['total_orders'] ?? 0),
                'total_value' => (float)($stats['total_value'] ?? 0),
                'avg_order_value' => (float)($stats['avg_order_value'] ?? 0),
                'completed_orders' => (int)($stats['completed_orders'] ?? 0),
                'on_time_orders' => (int)($stats['on_time_orders'] ?? 0),
                'completion_rate' => $stats['total_orders'] > 0 ? 
                    ($stats['completed_orders'] / $stats['total_orders']) * 100 : 0,
                'on_time_rate' => $stats['total_orders'] > 0 ? 
                    ($stats['on_time_orders'] / $stats['total_orders']) * 100 : 0
            ];
            
            return $vendor;
            
        } catch (Exception $e) {
            error_log("Get vendor with bank details error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * ==================== MVA WORKFLOW SUPPORT ====================
     * Maker-Verifier-Authorizer workflow implementation
     */
    
    /**
     * Create vendor with workflow
     */
    public function createVendorWithWorkflow($data) {
        try {
            $this->beginTransaction();
            
            // Create workflow record
            $workflowSql = "
                INSERT INTO vendor_workflows 
                (workflow_type, status, vendor_data, maker_id, maker_comments, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ";
            
            $stmt = $this->db->prepare($workflowSql);
            $result = $stmt->execute([
                $data['workflow_type'],
                $data['status'],
                json_encode($data),
                $data['maker_id'],
                $data['maker_comments'] ?? ''
            ]);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create workflow'];
            }
            
            $workflowId = $this->db->lastInsertId();
            
            $this->commit();
            return ['success' => true, 'workflow_id' => $workflowId];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Create vendor workflow error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create vendor workflow'];
        }
    }
    
    /**
     * Get workflow data
     */
    public function getWorkflowData($workflowId) {
        try {
            $sql = "
                SELECT vw.*, 
                       um.full_name as maker_name,
                       uv.full_name as verifier_name,
                       ua.full_name as authorizer_name
                FROM vendor_workflows vw
                LEFT JOIN users um ON vw.maker_id = um.id
                LEFT JOIN users uv ON vw.verifier_id = uv.id
                LEFT JOIN users ua ON vw.authorizer_id = ua.id
                WHERE vw.id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workflowId]);
            $workflow = $stmt->fetch();
            
            if ($workflow && $workflow['vendor_data']) {
                $workflow['vendor_data'] = $workflow['vendor_data'] ? json_decode($workflow['vendor_data'], true) : [];
            }
            
            return $workflow;
            
        } catch (Exception $e) {
            error_log("Get workflow data error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Approve vendor workflow (VERIFIER stage)
     */
    public function approveVendorWorkflow($workflowId, $verifierId, $comments) {
        try {
            $this->beginTransaction();
            
            $sql = "
                UPDATE vendor_workflows 
                SET status = 'pending_authorization', 
                    verifier_id = ?, 
                    verifier_comments = ?, 
                    verified_at = NOW()
                WHERE id = ? AND status = 'pending_verification'
            ";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$verifierId, $comments, $workflowId]);
            
            if (!$result || $stmt->rowCount() === 0) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to approve workflow or workflow not in correct state'];
            }
            
            $this->commit();
            return ['success' => true, 'message' => 'Workflow approved for authorization'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Approve vendor workflow error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve workflow'];
        }
    }
    
    /**
     * Reject vendor workflow
     */
    public function rejectVendorWorkflow($workflowId, $userId, $comments, $stage) {
        try {
            $this->beginTransaction();
            
            $status = $stage === 'verification' ? 'rejected_verification' : 'rejected_authorization';
            $field = $stage === 'verification' ? 'verifier' : 'authorizer';
            
            $sql = "
                UPDATE vendor_workflows 
                SET status = ?, 
                    {$field}_id = ?, 
                    {$field}_comments = ?, 
                    rejected_at = NOW()
                WHERE id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$status, $userId, $comments, $workflowId]);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to reject workflow'];
            }
            
            $this->commit();
            return ['success' => true, 'message' => 'Workflow rejected successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Reject vendor workflow error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reject workflow'];
        }
    }
    
    /**
     * Finalize vendor workflow (AUTHORIZER stage)
     */
    public function finalizeVendorWorkflow($workflowId, $authorizerId, $comments) {
        try {
            $this->beginTransaction();
            
            // Get workflow data
            $workflow = $this->getWorkflowData($workflowId);
            if (!$workflow || $workflow['status'] !== 'pending_authorization') {
                $this->rollback();
                return ['success' => false, 'message' => 'Workflow not ready for authorization'];
            }
            
            // Create the actual vendor
            $vendorData = $workflow['vendor_data'];
            $vendorResult = $this->createVendor($vendorData);
            
            if (!$vendorResult['success']) {
                $this->rollback();
                return $vendorResult;
            }
            
            // Update workflow status
            $sql = "
                UPDATE vendor_workflows 
                SET status = 'authorized', 
                    authorizer_id = ?, 
                    authorizer_comments = ?, 
                    vendor_id = ?,
                    authorized_at = NOW()
                WHERE id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$authorizerId, $comments, $vendorResult['vendor']['id'], $workflowId]);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to finalize workflow'];
            }
            
            $this->commit();
            return ['success' => true, 'vendor_id' => $vendorResult['vendor']['id']];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Finalize vendor workflow error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to finalize workflow'];
        }
    }
    
    /**
     * Get pending workflows
     */
    public function getPendingWorkflows($status = null, $page = 1, $perPage = 20) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $whereClause = "";
            $params = [];
            
            if ($status) {
                $whereClause = "WHERE vw.status = ?";
                $params[] = $status;
            } else {
                $whereClause = "WHERE vw.status IN ('pending_verification', 'pending_authorization')";
            }
            
            $sql = "
                SELECT vw.*, 
                       um.full_name as maker_name,
                       uv.full_name as verifier_name
                FROM vendor_workflows vw
                LEFT JOIN users um ON vw.maker_id = um.id
                LEFT JOIN users uv ON vw.verifier_id = uv.id
                {$whereClause}
                ORDER BY vw.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $perPage;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $workflows = $stmt->fetchAll();
            
            // Decode vendor data
            foreach ($workflows as &$workflow) {
                if ($workflow['vendor_data']) {
                    $workflow['vendor_data'] = $workflow['vendor_data'] ? json_decode($workflow['vendor_data'], true) : [];
                }
            }
            
            return $workflows;
            
        } catch (Exception $e) {
            error_log("Get pending workflows error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user workflows
     */
    public function getUserWorkflows($userId, $page = 1, $perPage = 20) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $sql = "
                SELECT vw.*, 
                       uv.full_name as verifier_name,
                       ua.full_name as authorizer_name
                FROM vendor_workflows vw
                LEFT JOIN users uv ON vw.verifier_id = uv.id
                LEFT JOIN users ua ON vw.authorizer_id = ua.id
                WHERE vw.maker_id = ?
                ORDER BY vw.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $perPage, $offset]);
            $workflows = $stmt->fetchAll();
            
            // Decode vendor data
            foreach ($workflows as &$workflow) {
                if ($workflow['vendor_data']) {
                    $workflow['vendor_data'] = $workflow['vendor_data'] ? json_decode($workflow['vendor_data'], true) : [];
                }
            }
            
            return $workflows;
            
        } catch (Exception $e) {
            error_log("Get user workflows error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get workflow history
     */
    public function getWorkflowHistory($workflowId) {
        try {
            $sql = "
                SELECT vwh.*, u.full_name as user_name
                FROM vendor_workflow_history vwh
                LEFT JOIN users u ON vwh.user_id = u.id
                WHERE vwh.workflow_id = ?
                ORDER BY vwh.created_at ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workflowId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get workflow history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Log workflow action
     */
    public function logWorkflowAction($actionData) {
        try {
            $sql = "
                INSERT INTO vendor_workflow_history 
                (workflow_id, workflow_type, action, description, user_id, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $actionData['workflow_id'],
                $actionData['workflow_type'],
                $actionData['action'],
                $actionData['description'],
                $actionData['user_id'],
                $actionData['ip_address'],
                $actionData['user_agent']
            ]);
            
        } catch (Exception $e) {
            error_log("Log workflow action error: " . $e->getMessage());
        }
    }
}
?>
