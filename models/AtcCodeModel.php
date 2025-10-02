<?php
/**
 * ConstructLink™ ATC Code Model
 * Manages Alphanumeric Tax Codes for BIR Form 2307
 */

class AtcCodeModel extends BaseModel {
    protected $table = 'atc_codes';
    protected $fillable = [
        'code', 'description', 'rate', 'category', 'nature_of_payment',
        'is_vat_inclusive', 'is_active'
    ];
    
    /**
     * Get all active ATC codes
     */
    public function getActiveAtcCodes() {
        try {
            $sql = "SELECT * FROM atc_codes WHERE is_active = 1 ORDER BY category, code";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get active ATC codes error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get ATC codes by category
     */
    public function getAtcCodesByCategory($category) {
        try {
            $sql = "SELECT * FROM atc_codes WHERE category = ? AND is_active = 1 ORDER BY code";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$category]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get ATC codes by category error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get ATC codes grouped by category
     */
    public function getAtcCodesGrouped() {
        try {
            $sql = "SELECT * FROM atc_codes WHERE is_active = 1 ORDER BY category, code";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $codes = $stmt->fetchAll();
            
            // Group by category
            $grouped = [];
            foreach ($codes as $code) {
                $category = $code['category'];
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [];
                }
                $grouped[$category][] = $code;
            }
            
            return $grouped;
        } catch (Exception $e) {
            error_log("Get ATC codes grouped error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get ATC code by code
     */
    public function getAtcCodeByCode($code) {
        try {
            $sql = "SELECT * FROM atc_codes WHERE code = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$code]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get ATC code by code error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculate EWT amount based on ATC code
     */
    public function calculateEWT($atcCodeId, $amount, $includeVat = true) {
        try {
            $atcCode = $this->find($atcCodeId);
            if (!$atcCode) {
                return 0;
            }
            
            // Base amount for calculation
            $baseAmount = $amount;
            
            // If VAT is included and should be considered
            if ($includeVat && $atcCode['is_vat_inclusive']) {
                // Amount already includes VAT, use as is
                $baseAmount = $amount;
            } elseif (!$includeVat && !$atcCode['is_vat_inclusive']) {
                // Need to exclude VAT from calculation
                // Assuming 12% VAT
                $baseAmount = $amount / 1.12;
            }
            
            // Calculate EWT
            $ewtAmount = $baseAmount * ($atcCode['rate'] / 100);
            
            return round($ewtAmount, 2);
        } catch (Exception $e) {
            error_log("Calculate EWT error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get ATC codes for procurement (commonly used for goods and services)
     */
    public function getProcurementAtcCodes() {
        try {
            $sql = "
                SELECT * FROM atc_codes 
                WHERE category IN ('Goods', 'Services', 'Rental', 'Professional/Talent Fees')
                    AND is_active = 1 
                ORDER BY 
                    CASE category
                        WHEN 'Goods' THEN 1
                        WHEN 'Services' THEN 2
                        WHEN 'Rental' THEN 3
                        WHEN 'Professional/Talent Fees' THEN 4
                        ELSE 5
                    END,
                    rate ASC,
                    code ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get procurement ATC codes error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create ATC code
     */
    public function createAtcCode($data) {
        try {
            // Validate required fields
            if (empty($data['code']) || empty($data['description']) || !isset($data['rate'])) {
                return ['success' => false, 'message' => 'Code, description, and rate are required'];
            }
            
            // Check for duplicate code
            if ($this->getAtcCodeByCode($data['code'])) {
                return ['success' => false, 'message' => 'ATC code already exists'];
            }
            
            $atcData = [
                'code' => strtoupper(Validator::sanitize($data['code'])),
                'description' => Validator::sanitize($data['description']),
                'rate' => (float)$data['rate'],
                'category' => $data['category'] ?? 'Other',
                'nature_of_payment' => Validator::sanitize($data['nature_of_payment'] ?? ''),
                'is_vat_inclusive' => isset($data['is_vat_inclusive']) ? 1 : 0,
                'is_active' => isset($data['is_active']) ? 1 : 0
            ];
            
            $result = $this->create($atcData);
            
            if ($result) {
                return ['success' => true, 'atc_code' => $result];
            } else {
                return ['success' => false, 'message' => 'Failed to create ATC code'];
            }
            
        } catch (Exception $e) {
            error_log("Create ATC code error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create ATC code'];
        }
    }
    
    /**
     * Update ATC code
     */
    public function updateAtcCode($id, $data) {
        try {
            $atcCode = $this->find($id);
            if (!$atcCode) {
                return ['success' => false, 'message' => 'ATC code not found'];
            }
            
            // Check for duplicate code (excluding current)
            if (isset($data['code'])) {
                $existing = $this->getAtcCodeByCode($data['code']);
                if ($existing && $existing['id'] != $id) {
                    return ['success' => false, 'message' => 'ATC code already exists'];
                }
            }
            
            $updateData = [];
            $allowedFields = ['code', 'description', 'rate', 'category', 'nature_of_payment'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    if ($field === 'code') {
                        $updateData[$field] = strtoupper(Validator::sanitize($data[$field]));
                    } elseif ($field === 'rate') {
                        $updateData[$field] = (float)$data[$field];
                    } else {
                        $updateData[$field] = Validator::sanitize($data[$field]);
                    }
                }
            }
            
            if (isset($data['is_vat_inclusive'])) {
                $updateData['is_vat_inclusive'] = $data['is_vat_inclusive'] ? 1 : 0;
            }
            
            if (isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'] ? 1 : 0;
            }
            
            $result = $this->update($id, $updateData);
            
            if ($result) {
                return ['success' => true, 'atc_code' => $result];
            } else {
                return ['success' => false, 'message' => 'Failed to update ATC code'];
            }
            
        } catch (Exception $e) {
            error_log("Update ATC code error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update ATC code'];
        }
    }
    
    /**
     * Get common ATC codes for dropdown
     */
    public function getCommonAtcCodesForDropdown() {
        try {
            $sql = "
                SELECT 
                    id,
                    CONCAT(code, ' - ', LEFT(description, 50), ' (', rate, '%)') as display_text,
                    code,
                    rate,
                    category
                FROM atc_codes 
                WHERE is_active = 1 
                    AND category IN ('Goods', 'Services', 'Rental')
                ORDER BY 
                    CASE 
                        WHEN code = 'WC156' THEN 1  -- Goods 1%
                        WHEN code = 'WC157' THEN 2  -- Services 2%
                        WHEN code = 'WC030' THEN 3  -- Rental 5%
                        ELSE 4
                    END,
                    category,
                    rate
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get common ATC codes for dropdown error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search ATC codes
     */
    public function searchAtcCodes($searchTerm) {
        try {
            $searchTerm = '%' . $searchTerm . '%';
            $sql = "
                SELECT * FROM atc_codes 
                WHERE (code LIKE ? OR description LIKE ? OR nature_of_payment LIKE ?)
                    AND is_active = 1
                ORDER BY code
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Search ATC codes error: " . $e->getMessage());
            return [];
        }
    }
}