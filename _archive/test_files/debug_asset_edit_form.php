<?php
/**
 * ConstructLink™ Asset Edit Form Diagnostic Tool
 * 
 * This comprehensive diagnostic script will test all components involved 
 * in the asset edit form data loading process to identify where failures occur.
 */

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/models/BaseModel.php';
require_once APP_ROOT . '/models/AssetModel.php';
require_once APP_ROOT . '/models/CategoryModel.php';
require_once APP_ROOT . '/models/ProjectModel.php';
require_once APP_ROOT . '/models/MakerModel.php';
require_once APP_ROOT . '/models/VendorModel.php';
require_once APP_ROOT . '/models/ClientModel.php';
require_once APP_ROOT . '/core/IntelligentAssetNamer.php';

class AssetEditFormDiagnostic {
    private $db;
    private $results = [];
    private $assetModel;
    private $namer;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
            $this->assetModel = new AssetModel();
            $this->namer = new IntelligentAssetNamer();
            $this->logResult('INFO', 'Diagnostic system initialized successfully');
        } catch (Exception $e) {
            $this->logResult('CRITICAL', 'Failed to initialize diagnostic system: ' . $e->getMessage());
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
    
    public function runDiagnostic($assetId = null) {
        echo "=== CONSTRUCTLINK ASSET EDIT FORM DIAGNOSTIC ===\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
        
        // 1. Test Database Connection
        $this->testDatabaseConnection();
        
        // 2. Test Asset Data Loading
        if ($assetId) {
            $this->testAssetDataLoading($assetId);
        } else {
            $this->testSampleAssetDataLoading();
        }
        
        // 3. Test Controller Data Preparation
        $this->testControllerDataPreparation();
        
        // 4. Test API Endpoints
        $this->testApiEndpoints();
        
        // 5. Test Database Content
        $this->testDatabaseContent();
        
        // 6. Test Relationships
        $this->testDataRelationships();
        
        // 7. Summary Report
        $this->generateSummaryReport();
        
        return $this->results;
    }
    
    private function testDatabaseConnection() {
        echo "--- 1. DATABASE CONNECTION TEST ---\n";
        
        try {
            $this->db->query("SELECT 1");
            $this->logResult('SUCCESS', 'Database connection is working');
            
            // Test database charset
            $result = $this->db->query("SELECT @@character_set_database, @@collation_database");
            $charset = $result->fetch(PDO::FETCH_NUM);
            $this->logResult('INFO', 'Database charset/collation', $charset);
            
        } catch (Exception $e) {
            $this->logResult('CRITICAL', 'Database connection failed', $e->getMessage());
        }
    }
    
    private function testAssetDataLoading($assetId) {
        echo "--- 2. ASSET DATA LOADING TEST (ID: {$assetId}) ---\n";
        
        try {
            // Test getAssetWithDetails method
            $asset = $this->assetModel->getAssetWithDetails($assetId);
            
            if (!$asset) {
                $this->logResult('ERROR', 'Asset not found with ID: ' . $assetId);
                return;
            }
            
            $this->logResult('SUCCESS', 'Asset data loaded successfully');
            
            // Check critical fields
            $criticalFields = ['id', 'name', 'ref', 'category_id', 'project_id'];
            $missingFields = [];
            
            foreach ($criticalFields as $field) {
                if (empty($asset[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if ($missingFields) {
                $this->logResult('WARNING', 'Asset has missing critical fields', $missingFields);
            } else {
                $this->logResult('SUCCESS', 'All critical asset fields are present');
            }
            
            // Check classification fields
            $classificationFields = ['equipment_type_id', 'subtype_id', 'brand_id'];
            $classificationData = [];
            
            foreach ($classificationFields as $field) {
                $classificationData[$field] = $asset[$field] ?? null;
            }
            
            $this->logResult('INFO', 'Asset classification fields', $classificationData);
            
            // Test full asset data
            $this->logResult('INFO', 'Asset data summary', [
                'id' => $asset['id'],
                'ref' => $asset['ref'],
                'name' => $asset['name'],
                'category_id' => $asset['category_id'],
                'category_name' => $asset['category_name'] ?? 'Not loaded',
                'project_id' => $asset['project_id'],
                'project_name' => $asset['project_name'] ?? 'Not loaded',
                'equipment_type_id' => $asset['equipment_type_id'],
                'subtype_id' => $asset['subtype_id'],
                'brand_id' => $asset['brand_id']
            ]);
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Failed to load asset data', $e->getMessage());
        }
    }
    
    private function testSampleAssetDataLoading() {
        echo "--- 2. SAMPLE ASSET DATA LOADING TEST ---\n";
        
        try {
            // Get a sample asset from the database
            $stmt = $this->db->query("SELECT id FROM assets LIMIT 1");
            $sampleAsset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sampleAsset) {
                $this->testAssetDataLoading($sampleAsset['id']);
            } else {
                $this->logResult('WARNING', 'No assets found in database for testing');
            }
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Failed to get sample asset', $e->getMessage());
        }
    }
    
    private function testControllerDataPreparation() {
        echo "--- 3. CONTROLLER DATA PREPARATION TEST ---\n";
        
        try {
            // Test Categories loading
            $categoryModel = new CategoryModel();
            $categories = $categoryModel->getActiveCategories();
            
            if (empty($categories)) {
                $this->logResult('ERROR', 'No categories loaded - this will break the form');
            } else {
                $this->logResult('SUCCESS', 'Categories loaded successfully', [
                    'count' => count($categories),
                    'sample' => array_slice($categories, 0, 3)
                ]);
            }
            
            // Test Projects loading
            $projectModel = new ProjectModel();
            $projects = $projectModel->getActiveProjects();
            
            if (empty($projects)) {
                $this->logResult('WARNING', 'No projects loaded');
            } else {
                $this->logResult('SUCCESS', 'Projects loaded successfully', [
                    'count' => count($projects),
                    'sample' => array_slice($projects, 0, 2)
                ]);
            }
            
            // Test Makers loading
            $makerModel = new MakerModel();
            $makers = $makerModel->findAll([], 'name ASC');
            
            $this->logResult('INFO', 'Makers loaded', [
                'count' => count($makers),
                'sample' => array_slice($makers, 0, 3)
            ]);
            
            // Test Vendors loading
            $vendorModel = new VendorModel();
            $vendors = $vendorModel->findAll([], 'name ASC');
            
            $this->logResult('INFO', 'Vendors loaded', [
                'count' => count($vendors),
                'sample' => array_slice($vendors, 0, 3)
            ]);
            
            // Test Clients loading
            $clientModel = new ClientModel();
            $clients = $clientModel->findAll([], 'name ASC');
            
            $this->logResult('INFO', 'Clients loaded', [
                'count' => count($clients),
                'sample' => array_slice($clients, 0, 3)
            ]);
            
            // Test Brands loading
            $brandQuery = "SELECT id, official_name, quality_tier FROM asset_brands WHERE is_active = 1 ORDER BY official_name ASC";
            $brandStmt = $this->db->query($brandQuery);
            $brands = $brandStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($brands)) {
                $this->logResult('WARNING', 'No brands loaded');
            } else {
                $this->logResult('SUCCESS', 'Brands loaded successfully', [
                    'count' => count($brands),
                    'sample' => array_slice($brands, 0, 3)
                ]);
            }
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Controller data preparation failed', $e->getMessage());
        }
    }
    
    private function testApiEndpoints() {
        echo "--- 4. API ENDPOINTS TEST ---\n";
        
        try {
            // Test equipment-types endpoint
            $categoryStmt = $this->db->query("SELECT id FROM categories WHERE generates_assets = 1 LIMIT 1");
            $sampleCategory = $categoryStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sampleCategory) {
                try {
                    $equipmentTypes = $this->namer->getEquipmentTypesByCategory($sampleCategory['id']);
                    
                    if (empty($equipmentTypes)) {
                        $this->logResult('WARNING', 'Equipment types API returns empty data for category', $sampleCategory['id']);
                    } else {
                        $this->logResult('SUCCESS', 'Equipment types API working', [
                            'category_id' => $sampleCategory['id'],
                            'count' => count($equipmentTypes),
                            'sample' => array_slice($equipmentTypes, 0, 3)
                        ]);
                    }
                } catch (Exception $e) {
                    $this->logResult('ERROR', 'Equipment types API failed', $e->getMessage());
                }
                
                // Test subtypes endpoint
                if (!empty($equipmentTypes)) {
                    $sampleEquipmentType = $equipmentTypes[0];
                    try {
                        $subtypes = $this->namer->getSubtypesByEquipmentType($sampleEquipmentType['id']);
                        
                        if (empty($subtypes)) {
                            $this->logResult('WARNING', 'Subtypes API returns empty data for equipment type', $sampleEquipmentType['id']);
                        } else {
                            $this->logResult('SUCCESS', 'Subtypes API working', [
                                'equipment_type_id' => $sampleEquipmentType['id'],
                                'count' => count($subtypes),
                                'sample' => array_slice($subtypes, 0, 3)
                            ]);
                        }
                    } catch (Exception $e) {
                        $this->logResult('ERROR', 'Subtypes API failed', $e->getMessage());
                    }
                }
            } else {
                $this->logResult('WARNING', 'No categories with generates_assets=1 found for API testing');
            }
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'API endpoints test failed', $e->getMessage());
        }
    }
    
    private function testDatabaseContent() {
        echo "--- 5. DATABASE CONTENT VERIFICATION ---\n";
        
        try {
            // Check categories table
            $categoriesCount = $this->db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
            $activeCategoriesCount = $this->db->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
            $this->logResult('INFO', 'Categories in database', [
                'total' => $categoriesCount,
                'active' => $activeCategoriesCount
            ]);
            
            // Check equipment_types table
            $equipmentTypesCount = $this->db->query("SELECT COUNT(*) FROM equipment_types")->fetchColumn();
            $this->logResult('INFO', 'Equipment types in database', ['count' => $equipmentTypesCount]);
            
            // Check asset_subtypes table
            $subtypesCount = $this->db->query("SELECT COUNT(*) FROM asset_subtypes")->fetchColumn();
            $this->logResult('INFO', 'Asset subtypes in database', ['count' => $subtypesCount]);
            
            // Check asset_brands table
            $brandsCount = $this->db->query("SELECT COUNT(*) FROM asset_brands WHERE is_active = 1")->fetchColumn();
            $this->logResult('INFO', 'Active brands in database', ['count' => $brandsCount]);
            
            // Check projects table
            $projectsCount = $this->db->query("SELECT COUNT(*) FROM projects WHERE status = 'active'")->fetchColumn();
            $this->logResult('INFO', 'Active projects in database', ['count' => $projectsCount]);
            
            // Check assets with classification
            $classifiedAssetsCount = $this->db->query("
                SELECT COUNT(*) FROM assets 
                WHERE equipment_type_id IS NOT NULL AND subtype_id IS NOT NULL
            ")->fetchColumn();
            $totalAssetsCount = $this->db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
            
            $this->logResult('INFO', 'Assets with classification', [
                'classified' => $classifiedAssetsCount,
                'total' => $totalAssetsCount,
                'percentage' => $totalAssetsCount > 0 ? round(($classifiedAssetsCount / $totalAssetsCount) * 100, 2) . '%' : '0%'
            ]);
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Database content verification failed', $e->getMessage());
        }
    }
    
    private function testDataRelationships() {
        echo "--- 6. DATA RELATIONSHIPS TEST ---\n";
        
        try {
            // Test category -> equipment type relationship
            $relationshipQuery = "
                SELECT c.id as category_id, c.name as category_name, 
                       COUNT(et.id) as equipment_type_count
                FROM categories c
                LEFT JOIN equipment_types et ON c.id = et.category_id
                WHERE c.is_active = 1 AND c.generates_assets = 1
                GROUP BY c.id
                ORDER BY equipment_type_count DESC
                LIMIT 5
            ";
            
            $stmt = $this->db->query($relationshipQuery);
            $categoryEquipmentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->logResult('INFO', 'Category -> Equipment Type relationships', $categoryEquipmentTypes);
            
            // Test equipment type -> subtype relationship
            $subtypeRelationshipQuery = "
                SELECT et.id as equipment_type_id, et.name as equipment_type_name,
                       COUNT(st.id) as subtype_count
                FROM equipment_types et
                LEFT JOIN asset_subtypes st ON et.id = st.equipment_type_id
                GROUP BY et.id
                ORDER BY subtype_count DESC
                LIMIT 5
            ";
            
            $stmt = $this->db->query($subtypeRelationshipQuery);
            $equipmentTypeSubtypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->logResult('INFO', 'Equipment Type -> Subtype relationships', $equipmentTypeSubtypes);
            
            // Check for orphaned records
            $orphanedEquipmentTypes = $this->db->query("
                SELECT COUNT(*) FROM equipment_types et 
                LEFT JOIN categories c ON et.category_id = c.id 
                WHERE c.id IS NULL
            ")->fetchColumn();
            
            if ($orphanedEquipmentTypes > 0) {
                $this->logResult('WARNING', 'Orphaned equipment types found', ['count' => $orphanedEquipmentTypes]);
            }
            
            $orphanedSubtypes = $this->db->query("
                SELECT COUNT(*) FROM asset_subtypes st 
                LEFT JOIN equipment_types et ON st.equipment_type_id = et.id 
                WHERE et.id IS NULL
            ")->fetchColumn();
            
            if ($orphanedSubtypes > 0) {
                $this->logResult('WARNING', 'Orphaned subtypes found', ['count' => $orphanedSubtypes]);
            }
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Data relationships test failed', $e->getMessage());
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
            echo "⚠️  ERRORS FOUND - Asset edit form may not work properly\n";
        } elseif ($levelCounts['WARNING'] > 0) {
            echo "⚡ WARNINGS FOUND - Asset edit form should work but may have limited functionality\n";
        } else {
            echo "✅ NO CRITICAL ISSUES - Asset edit form should work properly\n";
        }
        
        echo "\nRecommendations:\n";
        
        // Analyze results and provide specific recommendations
        foreach ($this->results as $result) {
            if ($result['level'] === 'CRITICAL' || $result['level'] === 'ERROR') {
                echo "• Fix: " . $result['message'] . "\n";
            }
        }
        
        if ($levelCounts['CRITICAL'] === 0 && $levelCounts['ERROR'] === 0) {
            echo "• System appears to be working correctly\n";
            echo "• If users report issues, check browser console for JavaScript errors\n";
            echo "• Verify network connectivity for API calls\n";
        }
    }
}

// Run the diagnostic
$diagnostic = new AssetEditFormDiagnostic();

// Check if a specific asset ID was provided via command line or GET parameter
$assetId = null;
if (isset($argv[1])) {
    $assetId = intval($argv[1]);
} elseif (isset($_GET['asset_id'])) {
    $assetId = intval($_GET['asset_id']);
}

$results = $diagnostic->runDiagnostic($assetId);

// If running via web browser, also output as JSON
if (!empty($_SERVER['HTTP_HOST'])) {
    echo "\n\n=== JSON OUTPUT ===\n";
    echo json_encode($results, JSON_PRETTY_PRINT);
}
?>