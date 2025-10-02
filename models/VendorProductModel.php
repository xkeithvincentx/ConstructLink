<?php
/**
 * ConstructLink™ Vendor Product Model
 * Intelligent vendor-product matching system based on procurement order history
 * Provides smart product search, price comparison, and vendor recommendations
 */

class VendorProductModel extends BaseModel {
    protected $table = 'procurement_items';
    
    /**
     * Get vendor product catalog with intelligent search
     * @param array $filters Search filters
     * @return array
     */
    public function getVendorProductCatalog($filters = []) {
        try {
            $sql = "
                SELECT 
                    v.id as vendor_id,
                    v.name as vendor_name,
                    v.contact_person,
                    v.email,
                    v.phone,
                    v.is_preferred,
                    v.rating,
                    pi.item_name,
                    pi.description,
                    pi.specifications,
                    pi.model,
                    pi.brand,
                    pi.unit,
                    c.name as category_name,
                    c.id as category_id,
                    COUNT(pi.id) as order_frequency,
                    AVG(pi.unit_price) as avg_price,
                    MIN(pi.unit_price) as min_price,
                    MAX(pi.unit_price) as max_price,
                    MAX(po.created_at) as last_ordered,
                    SUM(pi.quantity) as total_quantity_ordered,
                    AVG(CASE WHEN po.delivery_status IN ('Delivered', 'Received') THEN 1 ELSE 0 END) as delivery_success_rate,
                    GROUP_CONCAT(DISTINCT pi.specifications ORDER BY po.created_at DESC SEPARATOR ' | ') as all_specifications
                FROM procurement_items pi
                INNER JOIN procurement_orders po ON pi.procurement_order_id = po.id
                INNER JOIN vendors v ON po.vendor_id = v.id
                LEFT JOIN categories c ON pi.category_id = c.id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Apply intelligent search filters
            if (!empty($filters['search'])) {
                $searchTerm = '%' . $filters['search'] . '%';
                $sql .= " AND (
                    pi.item_name LIKE ? OR 
                    pi.description LIKE ? OR 
                    pi.specifications LIKE ? OR 
                    pi.model LIKE ? OR 
                    pi.brand LIKE ? OR
                    v.name LIKE ?
                )";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            if (!empty($filters['category_id'])) {
                $sql .= " AND pi.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            if (!empty($filters['vendor_id'])) {
                $sql .= " AND v.id = ?";
                $params[] = $filters['vendor_id'];
            }
            
            if (!empty($filters['price_min'])) {
                $sql .= " AND pi.unit_price >= ?";
                $params[] = $filters['price_min'];
            }
            
            if (!empty($filters['price_max'])) {
                $sql .= " AND pi.unit_price <= ?";
                $params[] = $filters['price_max'];
            }
            
            if (!empty($filters['preferred_only'])) {
                $sql .= " AND v.is_preferred = 1";
            }
            
            if (!empty($filters['min_rating'])) {
                $sql .= " AND v.rating >= ?";
                $params[] = $filters['min_rating'];
            }
            
            // Group and order for intelligent results
            $sql .= "
                GROUP BY v.id, pi.item_name, COALESCE(pi.model, ''), COALESCE(pi.brand, ''), COALESCE(pi.category_id, 0)
            ";
            
            // Apply intelligent sorting
            $orderBy = $filters['sort_by'] ?? 'relevance';
            switch ($orderBy) {
                case 'price_low':
                    $sql .= " ORDER BY AVG(pi.unit_price) ASC, COUNT(pi.id) DESC";
                    break;
                case 'price_high':
                    $sql .= " ORDER BY AVG(pi.unit_price) DESC, COUNT(pi.id) DESC";
                    break;
                case 'frequency':
                    $sql .= " ORDER BY COUNT(pi.id) DESC, AVG(CASE WHEN po.delivery_status IN ('Delivered', 'Received') THEN 1 ELSE 0 END) DESC";
                    break;
                case 'vendor_rating':
                    $sql .= " ORDER BY v.rating DESC, COUNT(pi.id) DESC";
                    break;
                case 'recent':
                    $sql .= " ORDER BY MAX(po.created_at) DESC, COUNT(pi.id) DESC";
                    break;
                default: // relevance
                    $sql .= " ORDER BY 
                        (COUNT(pi.id) * 0.3 + 
                         AVG(CASE WHEN po.delivery_status IN ('Delivered', 'Received') THEN 1 ELSE 0 END) * 100 * 0.3 + 
                         COALESCE(v.rating, 0) * 20 * 0.2 + 
                         v.is_preferred * 20 * 0.2) DESC";
            }
            
            // Apply pagination
            $limit = $filters['limit'] ?? 50;
            $offset = (($filters['page'] ?? 1) - 1) * $limit;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get vendor product catalog error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get intelligent vendor recommendations for a specific product
     * @param string $productName
     * @param array $options
     * @return array
     */
    public function getVendorRecommendationsForProduct($productName, $options = []) {
        try {
            $sql = "
                SELECT 
                    v.id as vendor_id,
                    v.name as vendor_name,
                    v.contact_person,
                    v.email,
                    v.phone,
                    v.is_preferred,
                    v.rating,
                    pi.item_name,
                    pi.model,
                    pi.brand,
                    pi.unit,
                    AVG(pi.unit_price) as avg_price,
                    MIN(pi.unit_price) as best_price,
                    COUNT(pi.id) as times_ordered,
                    MAX(po.created_at) as last_ordered,
                    AVG(CASE WHEN po.delivery_status IN ('Delivered', 'Received') THEN 1 ELSE 0 END) * 100 as success_rate,
                    AVG(DATEDIFF(po.actual_delivery_date, po.scheduled_delivery_date)) as avg_delivery_delay,
                    -- Intelligence score calculation
                    (
                        (COUNT(pi.id) * 0.25) +                              -- Order frequency (25%)
                        (AVG(CASE WHEN po.delivery_status IN ('Delivered', 'Received') THEN 1 ELSE 0 END) * 100 * 0.25) + -- Success rate (25%)
                        (v.rating * 20 * 0.20) +                             -- Vendor rating (20%)
                        (v.is_preferred * 30 * 0.15) +                       -- Preferred status (15%)
                        (CASE WHEN AVG(pi.unit_price) <= (
                            SELECT AVG(unit_price) FROM procurement_items pi2 
                            INNER JOIN procurement_orders po2 ON pi2.procurement_order_id = po2.id
                            WHERE pi2.item_name LIKE ?
                        ) THEN 15 ELSE 0 END * 0.15)                         -- Competitive pricing (15%)
                    ) as recommendation_score
                FROM procurement_items pi
                INNER JOIN procurement_orders po ON pi.procurement_order_id = po.id
                INNER JOIN vendors v ON po.vendor_id = v.id
                WHERE pi.item_name LIKE ? OR pi.description LIKE ? OR pi.specifications LIKE ?
                GROUP BY v.id, pi.item_name, pi.model, pi.brand
                HAVING times_ordered > 0
                ORDER BY recommendation_score DESC, success_rate DESC, avg_price ASC
                LIMIT ?
            ";
            
            $searchTerm = '%' . $productName . '%';
            $limit = $options['limit'] ?? 10;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
            $recommendations = $stmt->fetchAll();
            
            // Add recommendation reasons
            foreach ($recommendations as &$rec) {
                $rec['recommendation_reasons'] = $this->generateRecommendationReasons($rec);
                $rec['price_competitiveness'] = $this->calculatePriceCompetitiveness($rec['avg_price'], $productName);
            }
            
            return $recommendations;
            
        } catch (Exception $e) {
            error_log("Get vendor recommendations error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get product price history and trends
     * @param string $productName
     * @param int $vendorId
     * @return array
     */
    public function getProductPriceHistory($productName, $vendorId = null) {
        try {
            $sql = "
                SELECT 
                    po.created_at as order_date,
                    pi.unit_price,
                    pi.quantity,
                    pi.item_name,
                    pi.model,
                    pi.brand,
                    v.name as vendor_name,
                    v.id as vendor_id,
                    po.po_number
                FROM procurement_items pi
                INNER JOIN procurement_orders po ON pi.procurement_order_id = po.id
                INNER JOIN vendors v ON po.vendor_id = v.id
                WHERE pi.item_name LIKE ?
            ";
            
            $params = ['%' . $productName . '%'];
            
            if ($vendorId) {
                $sql .= " AND v.id = ?";
                $params[] = $vendorId;
            }
            
            $sql .= " ORDER BY po.created_at DESC LIMIT 100";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $history = $stmt->fetchAll();
            
            // Calculate trends
            if (count($history) > 1) {
                $latest = $history[0]['unit_price'];
                $oldest = end($history)['unit_price'];
                $trendPercent = (($latest - $oldest) / $oldest) * 100;
                
                return [
                    'history' => $history,
                    'trend_percent' => round($trendPercent, 2),
                    'trend_direction' => $trendPercent > 5 ? 'increasing' : ($trendPercent < -5 ? 'decreasing' : 'stable'),
                    'current_avg' => round(array_sum(array_column(array_slice($history, 0, 5), 'unit_price')) / min(5, count($history)), 2),
                    'historical_avg' => round(array_sum(array_column($history, 'unit_price')) / count($history), 2)
                ];
            }
            
            return ['history' => $history];
            
        } catch (Exception $e) {
            error_log("Get product price history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get similar products from different vendors
     * @param string $productName
     * @param array $options
     * @return array
     */
    public function getSimilarProducts($productName, $options = []) {
        try {
            // Extract keywords from product name for similarity matching
            $keywords = $this->extractKeywords($productName);
            $keywordConditions = [];
            $params = [];
            
            foreach ($keywords as $keyword) {
                if (strlen($keyword) > 2) { // Skip very short keywords
                    $keywordConditions[] = "(pi.item_name LIKE ? OR pi.description LIKE ? OR pi.specifications LIKE ?)";
                    $searchTerm = '%' . $keyword . '%';
                    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                }
            }
            
            if (empty($keywordConditions)) {
                return [];
            }
            
            $sql = "
                SELECT 
                    v.id as vendor_id,
                    v.name as vendor_name,
                    v.is_preferred,
                    v.rating,
                    pi.item_name,
                    pi.description,
                    pi.model,
                    pi.brand,
                    pi.unit,
                    AVG(pi.unit_price) as avg_price,
                    COUNT(pi.id) as order_count,
                    MAX(po.created_at) as last_ordered,
                    -- Similarity score based on keyword matches
                    (
                        SELECT COUNT(*)
                        FROM (SELECT ? as search_term) st
                        WHERE MATCH(CONCAT(pi.item_name, ' ', IFNULL(pi.description, ''), ' ', IFNULL(pi.specifications, ''))) 
                              AGAINST(st.search_term IN BOOLEAN MODE)
                    ) as similarity_score
                FROM procurement_items pi
                INNER JOIN procurement_orders po ON pi.procurement_order_id = po.id
                INNER JOIN vendors v ON po.vendor_id = v.id
                WHERE (" . implode(" OR ", $keywordConditions) . ")
                AND pi.item_name != ?
                GROUP BY v.id, pi.item_name, pi.model, pi.brand
                HAVING order_count > 0
                ORDER BY similarity_score DESC, v.rating DESC, order_count DESC
                LIMIT ?
            ";
            
            $params[] = $productName; // For similarity score
            $params[] = $productName; // For exclusion
            $params[] = $options['limit'] ?? 20;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get similar products error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get vendor product statistics
     * @param int $vendorId
     * @return array
     */
    public function getVendorProductStats($vendorId) {
        try {
            $sql = "
                SELECT 
                    COUNT(DISTINCT pi.item_name) as unique_products,
                    COUNT(DISTINCT pi.category_id) as categories_served,
                    COUNT(DISTINCT pi.brand) as brands_offered,
                    AVG(pi.unit_price) as avg_product_price,
                    MIN(pi.unit_price) as min_product_price,
                    MAX(pi.unit_price) as max_product_price,
                    SUM(pi.quantity) as total_items_supplied,
                    COUNT(pi.id) as total_product_orders,
                    MAX(po.created_at) as last_product_order,
                    -- Top categories
                    (SELECT GROUP_CONCAT(DISTINCT c.name ORDER BY product_count DESC SEPARATOR ', ')
                     FROM (
                         SELECT pi2.category_id, COUNT(*) as product_count
                         FROM procurement_items pi2
                         INNER JOIN procurement_orders po2 ON pi2.procurement_order_id = po2.id
                         WHERE po2.vendor_id = ?
                         GROUP BY pi2.category_id
                         ORDER BY product_count DESC
                         LIMIT 5
                     ) top_cats
                     LEFT JOIN categories c ON top_cats.category_id = c.id
                    ) as top_categories
                FROM procurement_items pi
                INNER JOIN procurement_orders po ON pi.procurement_order_id = po.id
                WHERE po.vendor_id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$vendorId, $vendorId]);
            return $stmt->fetch() ?: [];
            
        } catch (Exception $e) {
            error_log("Get vendor product stats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Extract keywords from product name for similarity matching
     * @param string $productName
     * @return array
     */
    private function extractKeywords($productName) {
        // Remove common words and extract meaningful keywords
        $commonWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an'];
        $words = preg_split('/[\s\-_,()]+/', strtolower($productName));
        return array_diff($words, $commonWords);
    }
    
    /**
     * Generate recommendation reasons based on vendor metrics
     * @param array $vendorData
     * @return array
     */
    private function generateRecommendationReasons($vendorData) {
        $reasons = [];
        
        if ($vendorData['is_preferred']) {
            $reasons[] = "Preferred vendor status";
        }
        
        if ($vendorData['rating'] >= 4.5) {
            $reasons[] = "Excellent vendor rating (" . number_format($vendorData['rating'], 1) . "/5.0)";
        }
        
        if ($vendorData['success_rate'] >= 95) {
            $reasons[] = "High delivery success rate (" . number_format($vendorData['success_rate'], 1) . "%)";
        }
        
        if ($vendorData['times_ordered'] >= 5) {
            $reasons[] = "Frequently ordered from this vendor";
        }
        
        if (isset($vendorData['avg_delivery_delay']) && $vendorData['avg_delivery_delay'] <= 0) {
            $reasons[] = "Consistently on-time or early delivery";
        }
        
        return $reasons;
    }
    
    /**
     * Calculate price competitiveness
     * @param float $price
     * @param string $productName
     * @return array
     */
    private function calculatePriceCompetitiveness($price, $productName) {
        try {
            $sql = "
                SELECT 
                    AVG(pi.unit_price) as market_avg,
                    MIN(pi.unit_price) as market_min,
                    MAX(pi.unit_price) as market_max
                FROM procurement_items pi
                INNER JOIN procurement_orders po ON pi.procurement_order_id = po.id
                WHERE pi.item_name LIKE ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['%' . $productName . '%']);
            $market = $stmt->fetch();
            
            if ($market && $market['market_avg']) {
                $percentageDiff = (($price - $market['market_avg']) / $market['market_avg']) * 100;
                
                if ($percentageDiff <= -10) {
                    $status = 'excellent';
                } elseif ($percentageDiff <= 0) {
                    $status = 'good';
                } elseif ($percentageDiff <= 10) {
                    $status = 'fair';
                } else {
                    $status = 'high';
                }
                
                return [
                    'status' => $status,
                    'percentage_diff' => round($percentageDiff, 1),
                    'market_avg' => round($market['market_avg'], 2),
                    'market_min' => round($market['market_min'], 2),
                    'market_max' => round($market['market_max'], 2)
                ];
            }
            
            return ['status' => 'unknown'];
            
        } catch (Exception $e) {
            error_log("Calculate price competitiveness error: " . $e->getMessage());
            return ['status' => 'unknown'];
        }
    }
}
?>