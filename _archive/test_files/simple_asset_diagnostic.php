<?php
/**
 * Simple Asset Edit Form Diagnostic Tool
 * 
 * This lightweight diagnostic script tests database content and API endpoints
 * without relying on the full framework to identify data loading issues.
 */

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';

class SimpleAssetDiagnostic {
    private $db;
    private $results = [];
    
    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
            $this->logResult('SUCCESS', 'Database connection established');
        } catch (Exception $e) {
            $this->logResult('CRITICAL', 'Database connection failed: ' . $e->getMessage());
        }
    }
    
    private function logResult($level, $message, $details = null) {
        $this->results[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'details' => $details
        ];
        
        echo "[{$level}] {$message}\n";
        if ($details) {
            if (is_array($details) || is_object($details)) {
                echo "    Details: " . json_encode($details, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "    Details: {$details}\n";
            }
        }
        echo "\n";
    }
    
    public function runDiagnostic() {
        echo "=== SIMPLE ASSET EDIT FORM DIAGNOSTIC ===\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
        
        $this->testAssetData();
        $this->testDropdownData();
        $this->testClassificationData();
        $this->testDataRelationships();
        $this->testAssetClassificationStatus();
        $this->generateSummaryReport();
        
        return $this->results;
    }
    
    private function testAssetData() {
        echo "--- 1. ASSET DATA TEST ---\n";
        
        try {
            // Get sample asset with details
            $query = "
                SELECT a.*, 
                       c.name as category_name,
                       p.name as project_name,
                       v.name as vendor_name,
                       m.name as maker_name,
                       cl.name as client_name
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN clients cl ON a.client_id = cl.id
                ORDER BY a.id DESC
                LIMIT 5
            ";
            
            $stmt = $this->db->query($query);
            $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($assets)) {
                $this->logResult('ERROR', 'No assets found in database');
                return;
            }
            
            $this->logResult('SUCCESS', 'Assets found in database', ['count' => count($assets)]);
            
            foreach ($assets as $asset) {
                $classificationStatus = [
                    'has_category' => !empty($asset['category_id']),
                    'has_project' => !empty($asset['project_id']),
                    'has_equipment_type' => !empty($asset['equipment_type_id']),
                    'has_subtype' => !empty($asset['subtype_id']),
                    'has_brand' => !empty($asset['brand_id'])
                ];
                
                $this->logResult('INFO', 'Asset classification status', [
                    'asset_id' => $asset['id'],
                    'ref' => $asset['ref'],
                    'name' => $asset['name'],
                    'classification' => $classificationStatus
                ]);
            }
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Failed to test asset data: ' . $e->getMessage());
        }
    }
    
    private function testDropdownData() {
        echo "--- 2. DROPDOWN DATA TEST ---\n";
        
        try {
            // Test Categories
            $categoriesCount = $this->db->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
            if ($categoriesCount == 0) {
                $this->logResult('ERROR', 'No active categories found - Edit form will be broken');
            } else {
                $this->logResult('SUCCESS', "Found {$categoriesCount} active categories");
                
                // Get sample categories
                $stmt = $this->db->query("SELECT id, name, generates_assets FROM categories WHERE is_active = 1 LIMIT 3");
                $sampleCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->logResult('INFO', 'Sample categories', $sampleCategories);
            }
            
            // Test Projects
            $projectsCount = $this->db->query("SELECT COUNT(*) FROM projects WHERE status = 'active'")->fetchColumn();
            if ($projectsCount == 0) {
                $this->logResult('WARNING', 'No active projects found');
            } else {
                $this->logResult('SUCCESS', "Found {$projectsCount} active projects");
            }
            
            // Test Makers
            $makersCount = $this->db->query("SELECT COUNT(*) FROM makers")->fetchColumn();
            $this->logResult('INFO', "Found {$makersCount} makers");
            
            // Test Vendors
            $vendorsCount = $this->db->query("SELECT COUNT(*) FROM vendors")->fetchColumn();
            $this->logResult('INFO', "Found {$vendorsCount} vendors");
            
            // Test Clients
            $clientsCount = $this->db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
            $this->logResult('INFO', "Found {$clientsCount} clients");
            
            // Test Brands
            $brandsCount = $this->db->query("SELECT COUNT(*) FROM asset_brands WHERE is_active = 1")->fetchColumn();
            if ($brandsCount == 0) {
                $this->logResult('WARNING', 'No active brands found');
            } else {
                $this->logResult('SUCCESS', "Found {$brandsCount} active brands");
                
                // Get sample brands
                $stmt = $this->db->query("SELECT id, official_name, quality_tier FROM asset_brands WHERE is_active = 1 LIMIT 3");
                $sampleBrands = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->logResult('INFO', 'Sample brands', $sampleBrands);
            }
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Failed to test dropdown data: ' . $e->getMessage());
        }
    }
    
    private function testClassificationData() {
        echo "--- 3. CLASSIFICATION DATA TEST ---\n";
        
        try {
            // Test Equipment Types
            $equipmentTypesCount = $this->db->query("SELECT COUNT(*) FROM equipment_types")->fetchColumn();
            if ($equipmentTypesCount == 0) {
                $this->logResult('WARNING', 'No equipment types found - Classification dropdowns will be empty');
            } else {
                $this->logResult('SUCCESS', "Found {$equipmentTypesCount} equipment types");
                
                // Sample equipment types by category
                $stmt = $this->db->query("
                    SELECT et.*, c.name as category_name 
                    FROM equipment_types et 
                    LEFT JOIN categories c ON et.category_id = c.id 
                    ORDER BY c.name, et.name 
                    LIMIT 5
                ");
                $sampleEquipmentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->logResult('INFO', 'Sample equipment types', $sampleEquipmentTypes);
            }
            
            // Test Asset Subtypes
            $subtypesCount = $this->db->query("SELECT COUNT(*) FROM asset_subtypes")->fetchColumn();
            if ($subtypesCount == 0) {
                $this->logResult('WARNING', 'No asset subtypes found - Subtype dropdowns will be empty');
            } else {
                $this->logResult('SUCCESS', "Found {$subtypesCount} asset subtypes");
                
                // Sample subtypes by equipment type
                $stmt = $this->db->query("
                    SELECT st.*, et.name as equipment_type_name 
                    FROM asset_subtypes st 
                    LEFT JOIN equipment_types et ON st.equipment_type_id = et.id 
                    ORDER BY et.name, st.name 
                    LIMIT 5
                ");
                $sampleSubtypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->logResult('INFO', 'Sample asset subtypes', $sampleSubtypes);
            }
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Failed to test classification data: ' . $e->getMessage());
        }
    }
    
    private function testDataRelationships() {
        echo "--- 4. DATA RELATIONSHIPS TEST ---\n";
        
        try {
            // Test Category -> Equipment Type relationships
            $stmt = $this->db->query("
                SELECT c.id as category_id, c.name as category_name, 
                       COUNT(et.id) as equipment_type_count
                FROM categories c
                LEFT JOIN equipment_types et ON c.id = et.category_id
                WHERE c.is_active = 1
                GROUP BY c.id
                ORDER BY equipment_type_count DESC
            ");
            $categoryEquipmentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $categoriesWithEquipment = 0;
            foreach ($categoryEquipmentTypes as $rel) {
                if ($rel['equipment_type_count'] > 0) {
                    $categoriesWithEquipment++;
                }
            }
            
            $this->logResult('INFO', 'Category-Equipment Type relationships', [
                'total_categories' => count($categoryEquipmentTypes),
                'categories_with_equipment_types' => $categoriesWithEquipment,
                'sample' => array_slice($categoryEquipmentTypes, 0, 5)
            ]);
            
            // Test Equipment Type -> Subtype relationships
            $stmt = $this->db->query("
                SELECT et.id as equipment_type_id, et.name as equipment_type_name,
                       COUNT(st.id) as subtype_count
                FROM equipment_types et
                LEFT JOIN asset_subtypes st ON et.id = st.equipment_type_id
                GROUP BY et.id
                ORDER BY subtype_count DESC
                LIMIT 10
            ");
            $equipmentTypeSubtypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->logResult('INFO', 'Equipment Type-Subtype relationships', $equipmentTypeSubtypes);
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Failed to test data relationships: ' . $e->getMessage());
        }
    }
    
    private function testAssetClassificationStatus() {
        echo "--- 5. ASSET CLASSIFICATION STATUS ---\n";
        
        try {
            // Count assets by classification completeness
            $totalAssets = $this->db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
            
            $fullyClassified = $this->db->query("
                SELECT COUNT(*) FROM assets 
                WHERE category_id IS NOT NULL 
                AND equipment_type_id IS NOT NULL 
                AND subtype_id IS NOT NULL
            ")->fetchColumn();
            
            $partiallyClassified = $this->db->query("
                SELECT COUNT(*) FROM assets 
                WHERE category_id IS NOT NULL 
                AND (equipment_type_id IS NULL OR subtype_id IS NULL)
            ")->fetchColumn();
            
            $unclassified = $this->db->query("
                SELECT COUNT(*) FROM assets 
                WHERE category_id IS NULL
            ")->fetchColumn();
            
            $this->logResult('INFO', 'Asset classification status', [
                'total_assets' => $totalAssets,
                'fully_classified' => $fullyClassified,
                'partially_classified' => $partiallyClassified,
                'unclassified' => $unclassified,
                'classification_percentage' => $totalAssets > 0 ? round(($fullyClassified / $totalAssets) * 100, 2) . '%' : '0%'
            ]);
            
            // Get sample assets with their classification status
            $stmt = $this->db->query("
                SELECT a.id, a.ref, a.name,
                       CASE 
                           WHEN a.category_id IS NULL THEN 'unclassified'
                           WHEN a.equipment_type_id IS NULL OR a.subtype_id IS NULL THEN 'partially_classified'
                           ELSE 'fully_classified'
                       END as classification_status,
                       c.name as category_name,
                       et.name as equipment_type_name,
                       st.name as subtype_name
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN equipment_types et ON a.equipment_type_id = et.id
                LEFT JOIN asset_subtypes st ON a.subtype_id = st.id
                ORDER BY a.id DESC
                LIMIT 10
            ");
            $sampleAssetsClassification = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->logResult('INFO', 'Sample asset classifications', $sampleAssetsClassification);
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Failed to test asset classification status: ' . $e->getMessage());
        }
    }
    
    private function generateSummaryReport() {
        echo "\n=== DIAGNOSTIC SUMMARY REPORT ===\n";
        
        $levelCounts = [
            'CRITICAL' => 0,
            'ERROR' => 0,
            'WARNING' => 0,
            'SUCCESS' => 0,
            'INFO' => 0
        ];
        
        foreach ($this->results as $result) {
            $levelCounts[$result['level']]++;
        }
        
        echo "Results Summary:\n";
        foreach ($levelCounts as $level => $count) {
            echo "  {$level}: {$count}\n";
        }
        
        echo "\nDiagnosis:\n";
        
        if ($levelCounts['CRITICAL'] > 0) {
            echo "❌ CRITICAL ISSUES FOUND - System is not functional\n";
        } elseif ($levelCounts['ERROR'] > 0) {
            echo "⚠️  ERRORS FOUND - Asset edit form will not work properly\n";
        } elseif ($levelCounts['WARNING'] > 0) {
            echo "⚡ WARNINGS FOUND - Asset edit form should work but may have limited functionality\n";
        } else {
            echo "✅ NO CRITICAL ISSUES - Asset edit form should work properly\n";
        }
        
        echo "\nKey Findings:\n";
        
        $hasCategories = false;
        $hasEquipmentTypes = false;
        $hasSubtypes = false;
        $hasBrands = false;
        
        foreach ($this->results as $result) {
            if (strpos($result['message'], 'active categories') !== false && $result['level'] === 'SUCCESS') {
                $hasCategories = true;
            }
            if (strpos($result['message'], 'equipment types') !== false && $result['level'] === 'SUCCESS') {
                $hasEquipmentTypes = true;
            }
            if (strpos($result['message'], 'asset subtypes') !== false && $result['level'] === 'SUCCESS') {
                $hasSubtypes = true;
            }
            if (strpos($result['message'], 'active brands') !== false && $result['level'] === 'SUCCESS') {
                $hasBrands = true;
            }
        }
        
        echo "• Categories: " . ($hasCategories ? "✅ Available" : "❌ Missing") . "\n";
        echo "• Equipment Types: " . ($hasEquipmentTypes ? "✅ Available" : "❌ Missing") . "\n";
        echo "• Asset Subtypes: " . ($hasSubtypes ? "✅ Available" : "❌ Missing") . "\n";
        echo "• Brands: " . ($hasBrands ? "✅ Available" : "⚠️  Missing") . "\n";
        
        echo "\nNext Steps:\n";
        if (!$hasCategories) {
            echo "1. Add categories to the database\n";
        }
        if (!$hasEquipmentTypes) {
            echo "2. Add equipment types linked to categories\n";
        }
        if (!$hasSubtypes) {
            echo "3. Add asset subtypes linked to equipment types\n";
        }
        if ($hasCategories && $hasEquipmentTypes && $hasSubtypes) {
            echo "1. Test the edit form with a real asset\n";
            echo "2. Check browser console for JavaScript errors\n";
            echo "3. Test API endpoints manually via browser\n";
        }
    }
}

// Run the diagnostic
$diagnostic = new SimpleAssetDiagnostic();
$results = $diagnostic->runDiagnostic();

// If running via web browser, also output as JSON
if (!empty($_SERVER['HTTP_HOST'])) {
    echo "\n\n=== JSON OUTPUT ===\n";
    echo json_encode($results, JSON_PRETTY_PRINT);
}
?>