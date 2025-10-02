<?php
/**
 * Direct API Endpoint Test
 * Tests the API endpoints that the edit form uses without going through the web interface
 */

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/Auth.php';
require_once APP_ROOT . '/core/IntelligentAssetNamer.php';

class DirectApiTest {
    private $db;
    private $namer;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->namer = new IntelligentAssetNamer();
    }
    
    public function testEquipmentTypesApi() {
        echo "=== TESTING EQUIPMENT TYPES API ===\n";
        
        try {
            // Get a sample category
            $stmt = $this->db->query("SELECT id, name FROM categories LIMIT 1");
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$category) {
                echo "❌ No categories found for testing\n";
                return;
            }
            
            echo "Testing with category: {$category['name']} (ID: {$category['id']})\n";
            
            // Test the API method directly
            $equipmentTypes = $this->namer->getEquipmentTypesByCategory($category['id']);
            
            if (empty($equipmentTypes)) {
                echo "⚠️  No equipment types found for category {$category['id']}\n";
                
                // Check if there are any equipment types at all
                $stmt = $this->db->query("SELECT COUNT(*) FROM equipment_types WHERE category_id = ?");
                $stmt->execute([$category['id']]);
                $count = $stmt->fetchColumn();
                echo "Equipment types in database for this category: {$count}\n";
                
                if ($count === 0) {
                    // Try another category
                    $stmt = $this->db->query("SELECT c.id, c.name, COUNT(et.id) as et_count 
                                            FROM categories c 
                                            LEFT JOIN equipment_types et ON c.id = et.category_id 
                                            GROUP BY c.id 
                                            HAVING et_count > 0 
                                            LIMIT 1");
                    $categoryWithEquipment = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($categoryWithEquipment) {
                        echo "Trying category with equipment: {$categoryWithEquipment['name']} (ID: {$categoryWithEquipment['id']})\n";
                        $equipmentTypes = $this->namer->getEquipmentTypesByCategory($categoryWithEquipment['id']);
                        
                        if (!empty($equipmentTypes)) {
                            echo "✅ Equipment Types API working for category {$categoryWithEquipment['id']}\n";
                            echo "Found " . count($equipmentTypes) . " equipment types:\n";
                            foreach (array_slice($equipmentTypes, 0, 3) as $et) {
                                echo "  - {$et['name']}\n";
                            }
                        }
                    }
                }
            } else {
                echo "✅ Equipment Types API working\n";
                echo "Found " . count($equipmentTypes) . " equipment types:\n";
                foreach (array_slice($equipmentTypes, 0, 3) as $et) {
                    echo "  - {$et['name']}\n";
                }
            }
            
        } catch (Exception $e) {
            echo "❌ Equipment Types API failed: " . $e->getMessage() . "\n";
        }
    }
    
    public function testSubtypesApi() {
        echo "\n=== TESTING SUBTYPES API ===\n";
        
        try {
            // Get a sample equipment type that has subtypes
            $stmt = $this->db->query("
                SELECT et.id, et.name, COUNT(st.id) as subtype_count 
                FROM equipment_types et 
                LEFT JOIN asset_subtypes st ON et.id = st.equipment_type_id 
                GROUP BY et.id 
                HAVING subtype_count > 0 
                LIMIT 1
            ");
            $equipmentType = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$equipmentType) {
                echo "⚠️  No equipment types with subtypes found for testing\n";
                return;
            }
            
            echo "Testing with equipment type: {$equipmentType['name']} (ID: {$equipmentType['id']})\n";
            
            // Test the API method directly
            $subtypes = $this->namer->getSubtypesByEquipmentType($equipmentType['id']);
            
            if (empty($subtypes)) {
                echo "❌ Subtypes API returned empty results\n";
            } else {
                echo "✅ Subtypes API working\n";
                echo "Found " . count($subtypes) . " subtypes:\n";
                foreach (array_slice($subtypes, 0, 5) as $subtype) {
                    echo "  - {$subtype['name']}\n";
                }
            }
            
        } catch (Exception $e) {
            echo "❌ Subtypes API failed: " . $e->getMessage() . "\n";
        }
    }
    
    public function testFormDataLoading() {
        echo "\n=== TESTING FORM DATA LOADING ===\n";
        
        try {
            // Test categories loading (as used by the controller)
            $stmt = $this->db->query("SELECT * FROM categories ORDER BY name ASC");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($categories)) {
                echo "❌ No categories found\n";
            } else {
                echo "✅ Categories loaded: " . count($categories) . " found\n";
            }
            
            // Test projects loading
            $stmt = $this->db->query("SELECT * FROM projects WHERE status = 'active' ORDER BY name ASC");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($projects)) {
                echo "⚠️  No active projects found\n";
            } else {
                echo "✅ Projects loaded: " . count($projects) . " found\n";
            }
            
            // Test makers loading
            $stmt = $this->db->query("SELECT * FROM makers ORDER BY name ASC");
            $makers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "ℹ️  Makers loaded: " . count($makers) . " found\n";
            
            // Test vendors loading
            $stmt = $this->db->query("SELECT * FROM vendors ORDER BY name ASC");
            $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "ℹ️  Vendors loaded: " . count($vendors) . " found\n";
            
            // Test clients loading
            $stmt = $this->db->query("SELECT * FROM clients ORDER BY name ASC");
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "ℹ️  Clients loaded: " . count($clients) . " found\n";
            
            // Test brands loading (as used by the controller)
            $stmt = $this->db->query("SELECT id, official_name, quality_tier FROM asset_brands WHERE is_active = 1 ORDER BY official_name ASC");
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($brands)) {
                echo "⚠️  No active brands found\n";
            } else {
                echo "✅ Brands loaded: " . count($brands) . " found\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Form data loading failed: " . $e->getMessage() . "\n";
        }
    }
    
    public function testAssetLoading() {
        echo "\n=== TESTING ASSET LOADING ===\n";
        
        try {
            // Get a sample asset and test if it loads properly
            $stmt = $this->db->query("SELECT id FROM assets ORDER BY id DESC LIMIT 1");
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$asset) {
                echo "⚠️  No assets found for testing\n";
                return;
            }
            
            echo "Testing asset loading with ID: {$asset['id']}\n";
            
            // Test the same query the AssetModel uses for getAssetWithDetails
            $stmt = $this->db->prepare("
                SELECT a.*, 
                       c.name as category_name,
                       p.name as project_name,
                       v.name as vendor_name,
                       m.name as maker_name,
                       cl.name as client_name,
                       et.name as equipment_type_name,
                       st.name as subtype_name,
                       b.official_name as brand_name
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN clients cl ON a.client_id = cl.id
                LEFT JOIN equipment_types et ON a.equipment_type_id = et.id
                LEFT JOIN asset_subtypes st ON a.subtype_id = st.id
                LEFT JOIN asset_brands b ON a.brand_id = b.id
                WHERE a.id = ?
                LIMIT 1
            ");
            
            $stmt->execute([$asset['id']]);
            $assetData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$assetData) {
                echo "❌ Asset data loading failed\n";
                return;
            }
            
            echo "✅ Asset data loaded successfully\n";
            echo "Asset details:\n";
            echo "  - ID: {$assetData['id']}\n";
            echo "  - Ref: {$assetData['ref']}\n";
            echo "  - Name: {$assetData['name']}\n";
            echo "  - Category: " . ($assetData['category_name'] ?? 'Not set') . "\n";
            echo "  - Project: " . ($assetData['project_name'] ?? 'Not set') . "\n";
            echo "  - Equipment Type: " . ($assetData['equipment_type_name'] ?? 'Not set') . "\n";
            echo "  - Subtype: " . ($assetData['subtype_name'] ?? 'Not set') . "\n";
            echo "  - Brand: " . ($assetData['brand_name'] ?? 'Not set') . "\n";
            
            // Check if the asset has the necessary IDs for dropdown population
            $classificationData = [
                'category_id' => $assetData['category_id'],
                'equipment_type_id' => $assetData['equipment_type_id'],
                'subtype_id' => $assetData['subtype_id'],
                'brand_id' => $assetData['brand_id']
            ];
            
            echo "Classification IDs for dropdown population:\n";
            foreach ($classificationData as $field => $value) {
                echo "  - {$field}: " . ($value ?? 'NULL') . "\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Asset loading failed: " . $e->getMessage() . "\n";
        }
    }
}

// Run the tests
echo "ConstructLink Asset Edit Form - Direct API Test\n";
echo "==============================================\n\n";

$tester = new DirectApiTest();
$tester->testFormDataLoading();
$tester->testEquipmentTypesApi();
$tester->testSubtypesApi();
$tester->testAssetLoading();

echo "\n=== TEST COMPLETE ===\n";
?>