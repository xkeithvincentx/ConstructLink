<?php
/**
 * ConstructLink™ Vendor Model - Enhanced with Bank Accounts and Payment Terms
 * Handles vendor management with comprehensive features
 */

class VendorModel extends BaseModel {
    protected $table = 'vendors';
    protected $fillable = [
        'name', 'contact_info', 'address', 'phone', 'email', 'contact_person',
        'payment_terms_id', 'categories', 'tax_id', 'is_preferred'
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
                'phone' => 'max:50',
                'payment_terms_id' => 'integer'
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
                'contact_info' => Validator::sanitize($data['contact_info'] ?? ''),
                'address' => Validator::sanitize($data['address'] ?? ''),
                'phone' => Validator::sanitize($data['phone'] ?? ''),
                'email' => Validator::sanitize($data['email'] ?? ''),
                'contact_person' => Validator::sanitize($data['contact_person'] ?? ''),
                'payment_terms_id' => !empty($data['payment_terms_id']) ? (int)$data['payment_terms_id'] : null,
                'tax_id' => Validator::sanitize($data['tax_id'] ?? ''),
                'is_preferred' => isset($data['is_preferred']) ? 1 : 0,
                'categories' => !empty($data['categories']) ? json_encode($data['categories']) : null
            ];
            
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
            
            // Add vendor tags if provided
            if (!empty($data['tags'])) {
                foreach ($data['tags'] as $tag) {
                    $tagResult = $this->addVendorTag($vendor['id'], $tag['name'], $tag['type'] ?? 'supply_category');
                    if (!$tagResult['success']) {
                        $this->rollback();
                        return $tagResult;
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
                'phone' => 'max:50',
                'payment_terms_id' => 'integer'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $this->beginTransaction();
            
            // Check for duplicate name (excluding current vendor)
            if (isset($data['name'])) {
                $existing = $this->findFirst(['name' => $data['name']]);
                if ($existing && $existing['id'] != $id) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Vendor name already exists'];
                }
            }
            
            // Prepare update data
            $updateData = [];
            $allowedFields = ['name', 'contact_info', 'address', 'phone', 'email', 'contact_person', 'payment_terms_id', 'tax_id'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = Validator::sanitize($data[$field]);
                }
            }
            
            if (isset($data['is_preferred'])) {
                $updateData['is_preferred'] = isset($data['is_preferred']) ? 1 : 0;
            }
            
            if (isset($data['categories'])) {
                $updateData['categories'] = !empty($data['categories']) ? json_encode($data['categories']) : null;
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
            
            // Update vendor tags if provided
            if (isset($data['tags'])) {
                // Remove existing tags
                $this->removeAllVendorTags($id);
                
                // Add new tags
                foreach ($data['tags'] as $tag) {
                    $tagResult = $this->addVendorTag($id, $tag['name'], $tag['type'] ?? 'supply_category');
                    if (!$tagResult['success']) {
                        $this->rollback();
                        return $tagResult;
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
            return ['success' => false, 'message' => 'Failed to update vendor'];
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
                'account_type' => 'required'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $sql = "INSERT INTO vendor_banks (vendor_id, bank_name, account_number, account_type, currency, bank_category, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $vendorId,
                Validator::sanitize($bankData['bank_name']),
                Validator::sanitize($bankData['account_number']),
                $bankData['account_type'] ?? 'Checking',
                $bankData['currency'] ?? 'PHP',
                $bankData['bank_category'] ?? 'Primary',
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
     * Add tag to vendor
     */
    public function addVendorTag($vendorId, $tagName, $tagType = 'supply_category') {
        try {
            $sql = "INSERT INTO vendor_tags (vendor_id, tag_name, tag_type) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$vendorId, Validator::sanitize($tagName), $tagType]);
            
            if ($result) {
                return ['success' => true, 'tag_id' => $this->db->lastInsertId()];
            } else {
                return ['success' => false, 'message' => 'Failed to add vendor tag'];
            }
            
        } catch (Exception $e) {
            error_log("Add vendor tag error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add vendor tag'];
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
     * Remove all vendor tags
     */
    public function removeAllVendorTags($vendorId) {
        try {
            $sql = "DELETE FROM vendor_tags WHERE vendor_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$vendorId]);
        } catch (Exception $e) {
            error_log("Remove vendor tags error: " . $e->getMessage());
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
                SELECT v.*, pt.term_name as payment_term_name
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
            $vendor['categories'] = json_decode($vendor['categories'], true) ?? [];
            
            // Get bank accounts
            $vendor['bank_accounts'] = $this->getVendorBanks($id);
            
            // Get tags
            $vendor['tags'] = $this->getVendorTags($id);
            
            return $vendor;
            
        } catch (Exception $e) {
            error_log("Get vendor with details error: " . $e->getMessage());
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
     * Get vendor tags
     */
    public function getVendorTags($vendorId) {
        try {
            $sql = "SELECT * FROM vendor_tags WHERE vendor_id = ? ORDER BY tag_type ASC, tag_name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get vendor tags error: " . $e->getMessage());
            return [];
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
            
            if (!empty($filters['tag'])) {
                $conditions[] = "EXISTS (SELECT 1 FROM vendor_tags vt WHERE vt.vendor_id = v.id AND vt.tag_name LIKE ?)";
                $params[] = "%{$filters['tag']}%";
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
                       COUNT(p.id) as procurement_count
                FROM vendors v
                LEFT JOIN payment_terms pt ON v.payment_terms_id = pt.id
                LEFT JOIN procurement p ON v.id = p.vendor_id
                {$whereClause}
                GROUP BY v.id
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}
            ";
            
            $stmt = $this->db->prepare($dataSql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Add categories and tags to each vendor
            foreach ($data as &$vendor) {
                $vendor['categories'] = json_decode($vendor['categories'], true) ?? [];
                $vendor['tags'] = $this->getVendorTags($vendor['id']);
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
    public function getVendorsByCategory($category) {
        try {
            $sql = "
                SELECT DISTINCT v.*
                FROM vendors v
                INNER JOIN vendor_tags vt ON v.id = vt.vendor_id
                WHERE vt.tag_name = ? AND vt.tag_type = 'supply_category'
                ORDER BY v.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$category]);
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
     * Get vendor statistics
     */
    public function getVendorStatistics() {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_vendors,
                    SUM(CASE WHEN is_preferred = 1 THEN 1 ELSE 0 END) as preferred_vendors,
                    COUNT(DISTINCT payment_terms_id) as payment_terms_used,
                    (SELECT COUNT(*) FROM vendor_banks WHERE is_active = 1) as total_bank_accounts,
                    (SELECT COUNT(DISTINCT vendor_id) FROM procurement WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as active_vendors_30d
                FROM vendors
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Get vendor statistics error: " . $e->getMessage());
            return [
                'total_vendors' => 0,
                'preferred_vendors' => 0,
                'payment_terms_used' => 0,
                'total_bank_accounts' => 0,
                'active_vendors_30d' => 0
            ];
        }
    }
    
    /**
     * Get vendors for dropdown/select
     */
    public function getVendorsForSelect($category = null) {
        try {
            $sql = "SELECT DISTINCT v.id, v.name FROM vendors v";
            $params = [];
            
            if ($category) {
                $sql .= " INNER JOIN vendor_tags vt ON v.id = vt.vendor_id WHERE vt.tag_name = ? AND vt.tag_type = 'supply_category'";
                $params[] = $category;
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
     * Log vendor activity
     */
    private function logVendorActivity($vendorId, $action, $description, $oldValues = null, $newValues = null) {
        try {
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
     * Delete vendor
     */
    public function deleteVendor($id) {
        try {
            $vendor = $this->find($id);
            if (!$vendor) {
                return ['success' => false, 'message' => 'Vendor not found'];
            }
            
            // Check if vendor has related procurement records
            $sql = "SELECT COUNT(*) FROM procurement WHERE vendor_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $procurementCount = $stmt->fetchColumn();
            
            if ($procurementCount > 0) {
                return ['success' => false, 'message' => 'Cannot delete vendor with existing procurement records'];
            }
            
            $this->beginTransaction();
            
            // Remove related data
            $this->removeAllVendorBanks($id);
            $this->removeAllVendorTags($id);
            
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
}
?>
