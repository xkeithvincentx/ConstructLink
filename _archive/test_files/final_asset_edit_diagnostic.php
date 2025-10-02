<?php
/**
 * ConstructLink™ Final Asset Edit Form Diagnostic
 * 
 * This script provides a comprehensive diagnostic of the asset edit form
 * data loading process, identifying exactly where failures occur.
 */

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/IntelligentAssetNamer.php';

class FinalAssetEditDiagnostic {
    private $db;
    private $namer;
    private $results = [];
    
    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
            $this->namer = new IntelligentAssetNamer();
            $this->logResult('SUCCESS', 'Diagnostic initialized successfully');
        } catch (Exception $e) {
            $this->logResult('CRITICAL', 'Failed to initialize: ' . $e->getMessage());
        }
    }
    
    private function logResult($level, $message, $details = null) {
        $this->results[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'details' => $details
        ];
        
        $colors = [
            'CRITICAL' => "\033[41m", // Red background
            'ERROR' => "\033[31m",    // Red text
            'WARNING' => "\033[33m",  // Yellow text
            'SUCCESS' => "\033[32m",  // Green text
            'INFO' => "\033[36m"      // Cyan text
        ];
        
        $color = $colors[$level] ?? '';
        $reset = "\033[0m";
        
        echo "{$color}[{$level}]{$reset} {$message}\n";
        if ($details) {
            if (is_array($details) || is_object($details)) {
                echo "    " . json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            } else {
                echo "    {$details}\n";
            }
        }
        echo "\n";
    }
    
    public function runCompleteDiagnostic() {
        echo "╔═══════════════════════════════════════════════════════════════╗\n";
        echo "║            CONSTRUCTLINK ASSET EDIT FORM DIAGNOSTIC          ║\n";
        echo "╚═══════════════════════════════════════════════════════════════╝\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
        
        // 1. Database Connection and Schema Test
        $this->testDatabaseConnection();
        
        // 2. Asset Data Loading Test
        $this->testAssetDataLoading();
        
        // 3. Controller Data Preparation Test
        $this->testControllerDataPreparation();
        
        // 4. API Endpoints Test
        $this->testApiEndpoints();
        
        // 5. Form Dropdown Population Test
        $this->testFormDropdownPopulation();
        
        // 6. JavaScript/Frontend Test Instructions
        $this->provideFrontendTestInstructions();
        
        // 7. Generate Final Report
        $this->generateFinalReport();
        
        return $this->results;
    }
    
    private function testDatabaseConnection() {
        echo "━━━ 1. DATABASE CONNECTION TEST ━━━\n";
        
        try {
            $this->db->query("SELECT 1");
            $this->logResult('SUCCESS', 'Database connection working');
            
            // Test charset
            $result = $this->db->query("SELECT @@character_set_database, @@collation_database");
            $charset = $result->fetch(PDO::FETCH_NUM);
            $this->logResult('INFO', 'Database charset/collation', [$charset[0], $charset[1]]);
            
        } catch (Exception $e) {
            $this->logResult('CRITICAL', 'Database connection failed', $e->getMessage());
        }
    }
    
    private function testAssetDataLoading() {
        echo "━━━ 2. ASSET DATA LOADING TEST ━━━\n";
        
        try {
            // Get latest asset for testing
            $stmt = $this->db->query("SELECT id, ref, name FROM assets ORDER BY id DESC LIMIT 1");
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$asset) {
                $this->logResult('ERROR', 'No assets found in database');
                return;
            }
            
            $this->logResult('INFO', 'Testing with asset', [
                'id' => $asset['id'],
                'ref' => $asset['ref'],
                'name' => $asset['name']
            ]);
            
            // Test the getAssetWithDetails query structure
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
            ");
            
            $stmt->execute([$asset['id']]);
            $assetDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($assetDetails) {
                $this->logResult('SUCCESS', 'Asset data loading works correctly');
                
                // Check classification data
                $classification = [
                    'category_id' => $assetDetails['category_id'],
                    'category_name' => $assetDetails['category_name'],
                    'equipment_type_id' => $assetDetails['equipment_type_id'],
                    'equipment_type_name' => $assetDetails['equipment_type_name'],
                    'subtype_id' => $assetDetails['subtype_id'],
                    'subtype_name' => $assetDetails['subtype_name'],
                    'brand_id' => $assetDetails['brand_id'],
                    'brand_name' => $assetDetails['brand_name']
                ];
                
                $this->logResult('INFO', 'Asset classification data', $classification);
                
                // Check if asset has the minimum required data for editing
                $requiredFields = ['category_id', 'project_id', 'name'];
                $missingFields = [];
                
                foreach ($requiredFields as $field) {
                    if (empty($assetDetails[$field])) {
                        $missingFields[] = $field;
                    }
                }
                
                if (empty($missingFields)) {
                    $this->logResult('SUCCESS', 'Asset has all required fields for editing');
                } else {
                    $this->logResult('WARNING', 'Asset missing some required fields', $missingFields);
                }
                
            } else {
                $this->logResult('ERROR', 'Asset data loading failed - query returned no results');
            }
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Asset data loading test failed', $e->getMessage());
        }
    }
    
    private function testControllerDataPreparation() {
        echo "━━━ 3. CONTROLLER DATA PREPARATION TEST ━━━\n";
        
        try {
            // Test Categories (fixed query)
            $stmt = $this->db->query("SELECT * FROM categories ORDER BY name ASC");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($categories)) {
                $this->logResult('ERROR', 'No categories found - form will not work');
            } else {
                $this->logResult('SUCCESS', "Categories loaded successfully", ['count' => count($categories)]);
            }
            
            // Test Projects (fixed query - using is_active instead of status)
            $stmt = $this->db->query("SELECT * FROM projects WHERE is_active = 1 ORDER BY name ASC");
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($projects)) {
                $this->logResult('WARNING', 'No active projects found');
            } else {
                $this->logResult('SUCCESS', "Projects loaded successfully", ['count' => count($projects)]);
            }
            
            // Test Makers
            $stmt = $this->db->query("SELECT * FROM makers ORDER BY name ASC");
            $makers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->logResult('INFO', "Makers loaded", ['count' => count($makers)]);
            
            // Test Vendors
            $stmt = $this->db->query("SELECT * FROM vendors ORDER BY name ASC");
            $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->logResult('INFO', "Vendors loaded", ['count' => count($vendors)]);
            
            // Test Clients
            $stmt = $this->db->query("SELECT * FROM clients ORDER BY name ASC");
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->logResult('INFO', "Clients loaded", ['count' => count($clients)]);
            
            // Test Brands
            $stmt = $this->db->query("SELECT id, official_name, quality_tier FROM asset_brands WHERE is_active = 1 ORDER BY official_name ASC");
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($brands)) {
                $this->logResult('WARNING', 'No active brands found');
            } else {
                $this->logResult('SUCCESS', "Brands loaded successfully", ['count' => count($brands)]);
            }
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Controller data preparation failed', $e->getMessage());
        }
    }
    
    private function testApiEndpoints() {
        echo "━━━ 4. API ENDPOINTS TEST ━━━\n";
        
        try {
            // Test equipment-types endpoint
            $stmt = $this->db->query("
                SELECT c.id, c.name, COUNT(et.id) as equipment_type_count 
                FROM categories c 
                LEFT JOIN equipment_types et ON c.id = et.category_id 
                GROUP BY c.id 
                HAVING equipment_type_count > 0 
                LIMIT 1
            ");
            $categoryWithEquipment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($categoryWithEquipment) {
                try {
                    $equipmentTypes = $this->namer->getEquipmentTypesByCategory($categoryWithEquipment['id']);
                    
                    if (!empty($equipmentTypes)) {
                        $this->logResult('SUCCESS', 'Equipment-types API working', [
                            'category_id' => $categoryWithEquipment['id'],
                            'category_name' => $categoryWithEquipment['name'],
                            'equipment_types_count' => count($equipmentTypes),
                            'sample_equipment_types' => array_slice(array_column($equipmentTypes, 'name'), 0, 3)
                        ]);
                        
                        // Test subtypes endpoint
                        if (!empty($equipmentTypes)) {
                            $sampleEquipmentType = $equipmentTypes[0];
                            try {
                                $subtypes = $this->namer->getSubtypesByEquipmentType($sampleEquipmentType['id']);
                                
                                if (!empty($subtypes)) {
                                    $this->logResult('SUCCESS', 'Subtypes API working', [
                                        'equipment_type_id' => $sampleEquipmentType['id'],
                                        'equipment_type_name' => $sampleEquipmentType['name'],
                                        'subtypes_count' => count($subtypes),
                                        'sample_subtypes' => array_slice(array_column($subtypes, 'name'), 0, 3)
                                    ]);
                                } else {
                                    $this->logResult('WARNING', 'Subtypes API returns empty data', [
                                        'equipment_type_id' => $sampleEquipmentType['id']
                                    ]);
                                }
                            } catch (Exception $e) {
                                $this->logResult('ERROR', 'Subtypes API failed', $e->getMessage());
                            }
                        }
                    } else {
                        $this->logResult('WARNING', 'Equipment-types API returns empty data', [
                            'category_id' => $categoryWithEquipment['id']
                        ]);
                    }
                } catch (Exception $e) {
                    $this->logResult('ERROR', 'Equipment-types API failed', $e->getMessage());
                }
            } else {
                $this->logResult('WARNING', 'No categories with equipment types found for API testing');
            }
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'API endpoints test failed', $e->getMessage());
        }
    }
    
    private function testFormDropdownPopulation() {
        echo "━━━ 5. FORM DROPDOWN POPULATION TEST ━━━\n";
        
        try {
            // Test if an asset with classification data can properly populate the dropdowns
            $stmt = $this->db->query("
                SELECT a.id, a.ref, a.name, a.category_id, a.equipment_type_id, a.subtype_id, a.brand_id,
                       c.name as category_name,
                       et.name as equipment_type_name,
                       st.name as subtype_name,
                       b.official_name as brand_name
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN equipment_types et ON a.equipment_type_id = et.id
                LEFT JOIN asset_subtypes st ON a.subtype_id = st.id
                LEFT JOIN asset_brands b ON a.brand_id = b.id
                WHERE a.category_id IS NOT NULL 
                  AND a.equipment_type_id IS NOT NULL 
                  AND a.subtype_id IS NOT NULL
                ORDER BY a.id DESC
                LIMIT 1
            ");
            
            $fullyClassifiedAsset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($fullyClassifiedAsset) {
                $this->logResult('SUCCESS', 'Found fully classified asset for dropdown testing', [
                    'asset_id' => $fullyClassifiedAsset['id'],
                    'ref' => $fullyClassifiedAsset['ref'],
                    'category_id' => $fullyClassifiedAsset['category_id'],
                    'equipment_type_id' => $fullyClassifiedAsset['equipment_type_id'],
                    'subtype_id' => $fullyClassifiedAsset['subtype_id'],
                    'brand_id' => $fullyClassifiedAsset['brand_id']
                ]);
                
                // Test if the equipment types for this category exist
                $equipmentTypes = $this->namer->getEquipmentTypesByCategory($fullyClassifiedAsset['category_id']);
                $equipmentTypeExists = false;
                
                foreach ($equipmentTypes as $et) {
                    if ($et['id'] == $fullyClassifiedAsset['equipment_type_id']) {
                        $equipmentTypeExists = true;
                        break;
                    }
                }
                
                if ($equipmentTypeExists) {
                    $this->logResult('SUCCESS', 'Asset\'s equipment type found in category equipment types');
                } else {
                    $this->logResult('ERROR', 'Asset\'s equipment type NOT found in category equipment types - dropdown will not show selected value');
                }
                
                // Test if the subtypes for this equipment type exist
                if ($fullyClassifiedAsset['equipment_type_id']) {
                    $subtypes = $this->namer->getSubtypesByEquipmentType($fullyClassifiedAsset['equipment_type_id']);
                    $subtypeExists = false;
                    
                    foreach ($subtypes as $st) {
                        if ($st['id'] == $fullyClassifiedAsset['subtype_id']) {
                            $subtypeExists = true;
                            break;
                        }
                    }
                    
                    if ($subtypeExists) {
                        $this->logResult('SUCCESS', 'Asset\'s subtype found in equipment type subtypes');
                    } else {
                        $this->logResult('ERROR', 'Asset\'s subtype NOT found in equipment type subtypes - dropdown will not show selected value');
                    }
                }
                
            } else {
                $this->logResult('WARNING', 'No fully classified assets found for dropdown testing');
            }
            
        } catch (Exception $e) {
            $this->logResult('ERROR', 'Form dropdown population test failed', $e->getMessage());
        }
    }
    
    private function provideFrontendTestInstructions() {
        echo "━━━ 6. FRONTEND/JAVASCRIPT TEST INSTRUCTIONS ━━━\n";
        
        $this->logResult('INFO', 'To test JavaScript/Frontend issues, perform these steps:');
        
        $instructions = [
            '1. Open browser developer tools (F12)',
            '2. Go to the asset edit page: ?route=assets/edit&id={asset_id}',
            '3. Check Console tab for JavaScript errors',
            '4. Check Network tab to see if API calls are being made',
            '5. Test dropdown interactions:',
            '   - Select a category and see if equipment types load',
            '   - Select an equipment type and see if subtypes load',
            '6. Look for these specific API calls in Network tab:',
            '   - ?route=api/equipment-types&category_id={id}',
            '   - ?route=api/subtypes&equipment_type_id={id}',
            '7. Check if API responses return proper JSON data'
        ];
        
        foreach ($instructions as $instruction) {
            echo "    {$instruction}\n";
        }
        echo "\n";
        
        // Provide specific URLs for testing
        $stmt = $this->db->query("SELECT id FROM assets ORDER BY id DESC LIMIT 1");
        $latestAsset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($latestAsset) {
            $this->logResult('INFO', 'Test URLs', [
                'edit_form' => "?route=assets/edit&id={$latestAsset['id']}",
                'equipment_types_api' => "?route=api/equipment-types&category_id=1",
                'subtypes_api' => "?route=api/subtypes&equipment_type_id=1"
            ]);
        }
    }
    
    private function generateFinalReport() {
        echo "━━━ 7. FINAL DIAGNOSTIC REPORT ━━━\n";
        
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
            if ($count > 0) {
                $color = [
                    'CRITICAL' => "\033[41m",
                    'ERROR' => "\033[31m",
                    'WARNING' => "\033[33m", 
                    'SUCCESS' => "\033[32m",
                    'INFO' => "\033[36m"
                ][$level];
                echo "  {$color}{$level}: {$count}\033[0m\n";
            }
        }
        
        echo "\n";
        
        // Determine overall system health
        if ($levelCounts['CRITICAL'] > 0) {
            echo "🔴 \033[41mCRITICAL FAILURE\033[0m - System cannot function\n";
        } elseif ($levelCounts['ERROR'] > 0) {
            echo "🟠 \033[31mERRORS DETECTED\033[0m - Asset edit form may not work properly\n";
        } elseif ($levelCounts['WARNING'] > 0) {
            echo "🟡 \033[33mWARNINGS PRESENT\033[0m - Asset edit form should work with limited functionality\n";
        } else {
            echo "🟢 \033[32mALL SYSTEMS OPERATIONAL\033[0m - Asset edit form should work correctly\n";
        }
        
        echo "\n";
        
        // Provide specific recommendations
        echo "Recommendations:\n";
        
        $hasControllerDataErrors = false;
        $hasApiErrors = false;
        $hasDatabaseErrors = false;
        
        foreach ($this->results as $result) {
            if ($result['level'] === 'ERROR' || $result['level'] === 'CRITICAL') {
                if (strpos($result['message'], 'Controller data') !== false) {
                    $hasControllerDataErrors = true;
                }
                if (strpos($result['message'], 'API') !== false) {
                    $hasApiErrors = true;
                }
                if (strpos($result['message'], 'Database') !== false) {
                    $hasDatabaseErrors = true;
                }
            }
        }
        
        if ($hasDatabaseErrors) {
            echo "  1. Fix database connection and schema issues\n";
        }
        if ($hasControllerDataErrors) {
            echo "  2. Check and fix the AssetController edit method\n";
        }
        if ($hasApiErrors) {
            echo "  3. Debug API endpoints and IntelligentAssetNamer class\n";
        }
        
        if (!$hasControllerDataErrors && !$hasApiErrors && !$hasDatabaseErrors) {
            echo "  1. Based on diagnostics, the backend appears to be working\n";
            echo "  2. If users report issues, check:\n";
            echo "     - JavaScript console for frontend errors\n";
            echo "     - Network requests in browser dev tools\n";
            echo "     - Web server error logs\n";
            echo "  3. Test the edit form manually with a real asset\n";
        }
        
        echo "\nDiagnostic complete. Check the detailed logs above for specific issues.\n";
    }
}

// Run the complete diagnostic
$diagnostic = new FinalAssetEditDiagnostic();
$results = $diagnostic->runCompleteDiagnostic();

// Save results to file
file_put_contents(
    APP_ROOT . '/asset_edit_diagnostic_results.json', 
    json_encode($results, JSON_PRETTY_PRINT)
);

echo "\n📁 Detailed results saved to: asset_edit_diagnostic_results.json\n";
?>