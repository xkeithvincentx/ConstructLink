<?php
/**
 * ConstructLink™ Maker Model
 * Handles asset manufacturers/makers data operations
 */

class MakerModel extends BaseModel {
    protected $table = 'makers';
    protected $fillable = ['name', 'country', 'website', 'description'];
    
    /**
     * Create maker with validation
     */
    public function createMaker($data) {
        $validation = $this->validate($data, [
            'name' => 'required|max:200',
            'country' => 'max:100',
            'website' => 'url|max:255',
            'description' => 'max:1000'
        ]);
        
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        // Check for duplicate name
        if ($this->findFirst(['name' => $data['name']])) {
            return ['success' => false, 'errors' => ['Manufacturer name already exists']];
        }
        
        try {
            $makerData = [
                'name' => $data['name'],
                'country' => $data['country'] ?? null,
                'website' => $data['website'] ?? null,
                'description' => $data['description'] ?? null
            ];
            
            $maker = $this->create($makerData);
            
            if ($maker) {
                // Log activity
                logActivity('maker_created', "Manufacturer '{$makerData['name']}' created", 'makers', $maker['id']);
                
                return ['success' => true, 'maker' => $maker];
            } else {
                return ['success' => false, 'message' => 'Failed to create manufacturer'];
            }
            
        } catch (Exception $e) {
            error_log("Maker creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create manufacturer'];
        }
    }
    
    /**
     * Update maker
     */
    public function updateMaker($id, $data) {
        $validation = $this->validate($data, [
            'name' => 'required|max:200',
            'country' => 'max:100',
            'website' => 'url|max:255',
            'description' => 'max:1000'
        ]);
        
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        // Check for duplicate name (excluding current maker)
        $existing = $this->findFirst(['name' => $data['name']]);
        if ($existing && $existing['id'] != $id) {
            return ['success' => false, 'errors' => ['Manufacturer name already exists']];
        }
        
        try {
            $makerData = [
                'name' => $data['name'],
                'country' => $data['country'] ?? null,
                'website' => $data['website'] ?? null,
                'description' => $data['description'] ?? null
            ];
            
            $updated = $this->update($id, $makerData);
            
            if ($updated) {
                // Log activity
                logActivity('maker_updated', "Manufacturer '{$makerData['name']}' updated", 'makers', $id);
                
                return ['success' => true, 'maker' => array_merge($makerData, ['id' => $id])];
            } else {
                return ['success' => false, 'message' => 'Failed to update manufacturer'];
            }
            
        } catch (Exception $e) {
            error_log("Maker update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update manufacturer'];
        }
    }
    
    /**
     * Delete maker
     */
    public function deleteMaker($id) {
        try {
            $maker = $this->find($id);
            if (!$maker) {
                return ['success' => false, 'message' => 'Manufacturer not found'];
            }
            
            // Check if maker has assets
            $assetCount = $this->db->prepare("SELECT COUNT(*) FROM assets WHERE maker_id = ?");
            $assetCount->execute([$id]);
            
            if ($assetCount->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Cannot delete manufacturer with existing assets'];
            }
            
            $deleted = $this->delete($id);
            
            if ($deleted) {
                // Log activity
                logActivity('maker_deleted', "Manufacturer '{$maker['name']}' deleted", 'makers', $id);
                
                return ['success' => true, 'message' => 'Manufacturer deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete manufacturer'];
            }
            
        } catch (Exception $e) {
            error_log("Maker deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete manufacturer'];
        }
    }
    
    /**
     * Get makers with filters and pagination
     */
    public function getMakersWithFilters($filters = [], $page = 1, $perPage = 20) {
        $conditions = [];
        $params = [];
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $conditions[] = "(m.name LIKE ? OR m.country LIKE ? OR m.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        // Filter by country
        if (!empty($filters['country'])) {
            $conditions[] = "m.country = ?";
            $params[] = $filters['country'];
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Count total records
        $countSql = "
            SELECT COUNT(*) 
            FROM {$this->table} m
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated data
        $offset = ($page - 1) * $perPage;
        $orderBy = $filters['order_by'] ?? 'm.name ASC';
        
        $dataSql = "
            SELECT m.*,
                   COUNT(a.id) as assets_count,
                   COALESCE(SUM(a.acquisition_cost), 0) as total_value,
                   GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as popular_categories
            FROM {$this->table} m
            LEFT JOIN assets a ON m.id = a.maker_id
            LEFT JOIN categories c ON a.category_id = c.id
            {$whereClause}
            GROUP BY m.id, m.name, m.country, m.website, m.description, m.created_at, m.updated_at
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
     * Get maker with detailed information
     */
    public function getMakerWithDetails($id) {
        $sql = "
            SELECT m.*,
                   COUNT(a.id) as assets_count,
                   SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available_assets,
                   SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) as in_use_assets,
                   SUM(CASE WHEN a.status = 'under_maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                   COALESCE(SUM(a.acquisition_cost), 0) as total_value,
                   GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as categories
            FROM {$this->table} m
            LEFT JOIN assets a ON m.id = a.maker_id
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE m.id = ?
            GROUP BY m.id, m.name, m.country, m.website, m.description, m.created_at, m.updated_at
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get maker statistics
     */
    public function getMakerStatistics($makerId = null) {
        $whereClause = $makerId ? "WHERE m.id = ?" : "";
        $params = $makerId ? [$makerId] : [];
        
        $sql = "
            SELECT 
                COUNT(DISTINCT m.id) as total_makers,
                COUNT(DISTINCT m.id) as active_makers,
                0 as inactive_makers,
                COUNT(a.id) as total_assets,
                COUNT(DISTINCT m.country) as countries_represented,
                COALESCE(SUM(a.acquisition_cost), 0) as total_value
            FROM {$this->table} m
            LEFT JOIN assets a ON m.id = a.maker_id
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get makers with asset count
     */
    public function getMakersWithAssetCount() {
        try {
            $sql = "
                SELECT 
                    m.*,
                    COUNT(a.id) as asset_count,
                    COALESCE(SUM(a.acquisition_cost), 0) as total_value
                FROM {$this->table} m
                LEFT JOIN assets a ON m.id = a.maker_id
                GROUP BY m.id, m.name, m.country, m.website, m.description, m.created_at, m.updated_at
                ORDER BY m.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("MakerModel::getMakersWithAssetCount error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get active makers (all makers are considered active since no is_active field)
     */
    public function getActiveMakers() {
        try {
            return $this->findAll([], 'name ASC');
        } catch (Exception $e) {
            error_log("MakerModel::getActiveMakers error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get makers for dropdown
     */
    public function getMakersForDropdown() {
        return $this->getActiveMakers();
    }
    
    /**
     * Search makers
     */
    public function searchMakers($query, $limit = 50) {
        try {
            $sql = "
                SELECT *
                FROM {$this->table}
                WHERE name LIKE ? OR country LIKE ? OR description LIKE ?
                ORDER BY name ASC
                LIMIT ?
            ";
            
            $searchTerm = '%' . $query . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("MakerModel::searchMakers error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get top makers by asset count
     */
    public function getTopMakersByAssetCount($limit = 10) {
        try {
            $sql = "
                SELECT 
                    m.name,
                    m.country,
                    COUNT(a.id) as asset_count,
                    COALESCE(SUM(a.acquisition_cost), 0) as total_value
                FROM {$this->table} m
                INNER JOIN assets a ON m.id = a.maker_id
                GROUP BY m.id, m.name, m.country
                ORDER BY asset_count DESC
                LIMIT ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("MakerModel::getTopMakersByAssetCount error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get makers by country
     */
    public function getMakersByCountry() {
        try {
            $sql = "
                SELECT 
                    m.country,
                    COUNT(DISTINCT m.id) as maker_count,
                    COUNT(a.id) as asset_count,
                    COALESCE(SUM(a.acquisition_cost), 0) as total_value
                FROM {$this->table} m
                LEFT JOIN assets a ON m.id = a.maker_id
                WHERE m.country IS NOT NULL AND m.country != ''
                GROUP BY m.country
                ORDER BY maker_count DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("MakerModel::getMakersByCountry error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get maker asset categories
     */
    public function getMakerAssetCategories($makerId) {
        try {
            $sql = "
                SELECT 
                    c.name as category_name,
                    COUNT(a.id) as asset_count,
                    COALESCE(SUM(a.acquisition_cost), 0) as total_value,
                    GROUP_CONCAT(DISTINCT a.status) as statuses
                FROM assets a
                INNER JOIN categories c ON a.category_id = c.id
                WHERE a.maker_id = ?
                GROUP BY c.id, c.name
                ORDER BY asset_count DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$makerId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("MakerModel::getMakerAssetCategories error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all countries from makers
     */
    public function getAllCountries() {
        try {
            $sql = "
                SELECT DISTINCT country
                FROM {$this->table}
                WHERE country IS NOT NULL AND country != ''
                ORDER BY country ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'country');
            
        } catch (Exception $e) {
            error_log("MakerModel::getAllCountries error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get maker performance metrics
     */
    public function getMakerPerformance($makerId, $dateFrom = null, $dateTo = null) {
        try {
            $conditions = ["a.maker_id = ?"];
            $params = [$makerId];
            
            if ($dateFrom) {
                $conditions[] = "DATE(a.created_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "DATE(a.created_at) <= ?";
                $params[] = $dateTo;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT 
                    COUNT(a.id) as total_assets,
                    COUNT(CASE WHEN a.status = 'available' THEN 1 END) as available_count,
                    COUNT(CASE WHEN a.status = 'in_use' THEN 1 END) as in_use_count,
                    COUNT(CASE WHEN a.status = 'under_maintenance' THEN 1 END) as maintenance_count,
                    COALESCE(AVG(a.acquisition_cost), 0) as avg_cost,
                    COALESCE(SUM(a.acquisition_cost), 0) as total_value,
                    COUNT(DISTINCT a.category_id) as categories_count,
                    COUNT(DISTINCT a.project_id) as projects_count
                FROM assets a
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("MakerModel::getMakerPerformance error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get maker trends
     */
    public function getMakerTrends($dateFrom, $dateTo) {
        try {
            $sql = "
                SELECT 
                    DATE(a.created_at) as date,
                    m.name as maker_name,
                    COUNT(a.id) as assets_added,
                    COALESCE(SUM(a.acquisition_cost), 0) as total_value
                FROM assets a
                INNER JOIN makers m ON a.maker_id = m.id
                WHERE DATE(a.created_at) BETWEEN ? AND ?
                GROUP BY DATE(a.created_at), m.id, m.name
                ORDER BY date ASC, assets_added DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFrom, $dateTo]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("MakerModel::getMakerTrends error: " . $e->getMessage());
            return [];
        }
    }
}
?>
