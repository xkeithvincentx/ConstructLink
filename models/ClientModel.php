<?php
/**
 * ConstructLink™ Client Model
 * Handles client management for client-supplied assets
 */

class ClientModel extends BaseModel {
    protected $table = 'clients';
    protected $fillable = ['name', 'contact_info', 'address', 'phone', 'email', 'contact_person', 'company_type'];
    
    /**
     * Get clients with asset count
     */
    public function getClientsWithAssetCount() {
        $sql = "
            SELECT 
                c.*,
                COUNT(a.id) as asset_count,
                COALESCE(SUM(a.acquisition_cost), 0) as total_value
            FROM {$this->table} c
            LEFT JOIN inventory_items a ON c.id = a.client_id AND a.is_client_supplied = 1
            GROUP BY c.id, c.name, c.contact_info, c.address, c.phone, c.email, c.contact_person, c.company_type, c.created_at, c.updated_at
            ORDER BY c.name
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get clients for dropdown
     */
    public function getClientsForDropdown() {
        return $this->findAll([], 'name ASC');
    }
    
    /**
     * Get active clients (all clients are considered active since no is_active field)
     */
    public function getActiveClients() {
        try {
            return $this->findAll([], 'name ASC');
        } catch (Exception $e) {
            error_log("ClientModel::getActiveClients error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search clients
     */
    public function searchClients($query, $limit = 50) {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE name LIKE ? OR contact_info LIKE ? OR email LIKE ? OR contact_person LIKE ? OR company_type LIKE ?
            ORDER BY name
            LIMIT ?
        ";
        
        $searchTerm = '%' . $query . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create client with validation
     */
    public function createClient($data) {
        try {
            // Validate required fields
            $errors = [];
            
            if (empty($data['name'])) {
                $errors[] = 'Client name is required';
            }
            
            // Check for duplicate name
            if ($this->findFirst(['name' => $data['name']])) {
                $errors[] = 'Client name already exists';
            }
            
            // Validate email if provided
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Prepare data according to schema
            $clientData = [
                'name' => Validator::sanitize($data['name']),
                'contact_info' => Validator::sanitize($data['contact_info'] ?? ''),
                'address' => Validator::sanitize($data['address'] ?? ''),
                'phone' => Validator::sanitize($data['phone'] ?? ''),
                'email' => Validator::sanitize($data['email'] ?? ''),
                'contact_person' => Validator::sanitize($data['contact_person'] ?? ''),
                'company_type' => Validator::sanitize($data['company_type'] ?? '')
            ];
            
            $clientId = $this->create($clientData);
            
            if ($clientId) {
                logActivity('client_created', "Client '{$clientData['name']}' created", 'clients', $clientId);
                
                return [
                    'success' => true,
                    'client' => array_merge($clientData, ['id' => $clientId]),
                    'message' => 'Client created successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create client'];
            }
            
        } catch (Exception $e) {
            error_log("Client creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Update client
     */
    public function updateClient($id, $data) {
        try {
            $client = $this->find($id);
            if (!$client) {
                return ['success' => false, 'message' => 'Client not found'];
            }
            
            // Validate required fields
            $errors = [];
            
            if (empty($data['name'])) {
                $errors[] = 'Client name is required';
            }
            
            // Check for duplicate name (excluding current client)
            $existing = $this->findFirst(['name' => $data['name']]);
            if ($existing && $existing['id'] != $id) {
                $errors[] = 'Client name already exists';
            }
            
            // Validate email if provided
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Prepare data according to schema
            $clientData = [
                'name' => Validator::sanitize($data['name']),
                'contact_info' => Validator::sanitize($data['contact_info'] ?? ''),
                'address' => Validator::sanitize($data['address'] ?? ''),
                'phone' => Validator::sanitize($data['phone'] ?? ''),
                'email' => Validator::sanitize($data['email'] ?? ''),
                'contact_person' => Validator::sanitize($data['contact_person'] ?? ''),
                'company_type' => Validator::sanitize($data['company_type'] ?? '')
            ];
            
            $success = $this->update($id, $clientData);
            
            if ($success) {
                logActivity('client_updated', "Client '{$clientData['name']}' updated", 'clients', $id);
                
                return [
                    'success' => true,
                    'client' => array_merge($clientData, ['id' => $id]),
                    'message' => 'Client updated successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update client'];
            }
            
        } catch (Exception $e) {
            error_log("Client update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Delete client
     */
    public function deleteClient($id) {
        try {
            $client = $this->find($id);
            if (!$client) {
                return ['success' => false, 'message' => 'Client not found'];
            }
            
            // Check if client has assets
            $assetCount = $this->db->prepare("SELECT COUNT(*) FROM inventory_items WHERE client_id = ?");
            $assetCount->execute([$id]);
            
            if ($assetCount->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Cannot delete client with existing assets'];
            }
            
            $success = $this->delete($id);
            
            if ($success) {
                logActivity('client_deleted', "Client '{$client['name']}' deleted", 'clients', $id);
                return ['success' => true, 'message' => 'Client deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete client'];
            }
            
        } catch (Exception $e) {
            error_log("Client deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get clients with filters and pagination
     */
    public function getClientsWithFilters($filters = [], $page = 1, $perPage = 20) {
        $conditions = [];
        $params = [];
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $conditions[] = "(c.name LIKE ? OR c.contact_info LIKE ? OR c.email LIKE ? OR c.contact_person LIKE ? OR c.company_type LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        // Filter by company type
        if (!empty($filters['company_type'])) {
            $conditions[] = "c.company_type = ?";
            $params[] = $filters['company_type'];
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Count total records
        $countSql = "
            SELECT COUNT(*) 
            FROM {$this->table} c
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated data
        $offset = ($page - 1) * $perPage;
        $orderBy = $filters['order_by'] ?? 'c.name ASC';
        
        $dataSql = "
            SELECT c.*,
                   COUNT(a.id) as assets_count,
                   COALESCE(SUM(a.acquisition_cost), 0) as total_value
            FROM {$this->table} c
            LEFT JOIN inventory_items a ON c.id = a.client_id AND a.is_client_supplied = 1
            {$whereClause}
            GROUP BY c.id, c.name, c.contact_info, c.address, c.phone, c.email, c.contact_person, c.company_type, c.created_at, c.updated_at
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
    }
    
    /**
     * Get client with detailed information
     */
    public function getClientWithDetails($id) {
        $sql = "
            SELECT c.*,
                   COUNT(a.id) as assets_count,
                   SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available_assets,
                   SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) as in_use_assets,
                   SUM(CASE WHEN a.status = 'under_maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                   COALESCE(SUM(a.acquisition_cost), 0) as total_value
            FROM {$this->table} c
            LEFT JOIN inventory_items a ON c.id = a.client_id AND a.is_client_supplied = 1
            WHERE c.id = ?
            GROUP BY c.id, c.name, c.contact_info, c.address, c.phone, c.email, c.contact_person, c.company_type, c.created_at, c.updated_at
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get client statistics
     */
    public function getClientStatistics($clientId = null) {
        $whereClause = $clientId ? "WHERE c.id = ?" : "";
        $params = $clientId ? [$clientId] : [];
        
        $sql = "
            SELECT 
                COUNT(DISTINCT c.id) as total_clients,
                COUNT(DISTINCT c.id) as active_clients,
                0 as inactive_clients,
                COUNT(a.id) as client_supplied_assets,
                COUNT(CASE WHEN c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_clients_30_days,
                COALESCE(SUM(a.acquisition_cost), 0) as total_value
            FROM {$this->table} c
            LEFT JOIN inventory_items a ON c.id = a.client_id AND a.is_client_supplied = 1
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get client-supplied assets summary
     */
    public function getClientSuppliedAssetsSummary($clientId) {
        $sql = "
            SELECT 
                c.name as category_name,
                COUNT(a.id) as asset_count,
                COALESCE(SUM(a.acquisition_cost), 0) as total_value,
                GROUP_CONCAT(DISTINCT a.status) as statuses
            FROM inventory_items a
            INNER JOIN categories c ON a.category_id = c.id
            WHERE a.client_id = ? AND a.is_client_supplied = 1
            GROUP BY c.id, c.name
            ORDER BY asset_count DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clientId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get top clients by asset value
     */
    public function getTopClientsByAssetValue($limit = 10) {
        $sql = "
            SELECT 
                c.name,
                c.company_type,
                COUNT(a.id) as asset_count,
                COALESCE(SUM(a.acquisition_cost), 0) as total_value
            FROM {$this->table} c
            INNER JOIN inventory_items a ON c.id = a.client_id AND a.is_client_supplied = 1
            GROUP BY c.id, c.name, c.company_type
            ORDER BY total_value DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get clients by company type
     */
    public function getClientsByCompanyType() {
        $sql = "
            SELECT 
                c.company_type,
                COUNT(DISTINCT c.id) as client_count,
                COUNT(a.id) as asset_count,
                COALESCE(SUM(a.acquisition_cost), 0) as total_value
            FROM {$this->table} c
            LEFT JOIN inventory_items a ON c.id = a.client_id AND a.is_client_supplied = 1
            WHERE c.company_type IS NOT NULL AND c.company_type != ''
            GROUP BY c.company_type
            ORDER BY client_count DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all company types from clients
     */
    public function getAllCompanyTypes() {
        $sql = "
            SELECT DISTINCT company_type
            FROM {$this->table}
            WHERE company_type IS NOT NULL AND company_type != ''
            ORDER BY company_type
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'company_type');
    }
    
    /**
     * Get client asset timeline
     */
    public function getClientAssetTimeline($clientId, $months = 12) {
        $sql = "
            SELECT 
                DATE_FORMAT(a.acquired_date, '%Y-%m') as month,
                COUNT(a.id) as asset_count,
                COALESCE(SUM(a.acquisition_cost), 0) as total_value
            FROM inventory_items a
            WHERE a.client_id = ? 
                AND a.is_client_supplied = 1
                AND a.acquired_date >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(a.acquired_date, '%Y-%m')
            ORDER BY month
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clientId, $months]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
