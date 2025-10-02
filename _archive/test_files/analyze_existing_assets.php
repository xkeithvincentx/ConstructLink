<?php
/**
 * ConstructLink™ Existing Assets Analysis
 * Analyzes current assets in database to identify patterns with equipment classification
 */

define('APP_ROOT', __DIR__);

// Include configuration
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h1>Existing Assets Analysis Report</h1>";
    echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p><hr>";
    
    // Check if assets table exists and has data
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM assets");
        $totalAssets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<h2>Database Overview</h2>";
        echo "<p><strong>Total Assets:</strong> {$totalAssets}</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error accessing assets table: " . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    }
    
    if ($totalAssets == 0) {
        echo "<p>No assets found in database. Cannot analyze classification patterns.</p>";
        exit;
    }
    
    // Check table structure
    echo "<h2>Table Structure Analysis</h2>";
    $stmt = $db->query("SHOW COLUMNS FROM assets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasEquipmentTypeId = false;
    $hasSubtypeId = false;
    $hasGeneratedName = false;
    $hasNameComponents = false;
    
    echo "<h3>Equipment Classification Related Columns:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        if (in_array($column['Field'], ['equipment_type_id', 'subtype_id', 'generated_name', 'name_components', 'standardized_name'])) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
            
            if ($column['Field'] === 'equipment_type_id') $hasEquipmentTypeId = true;
            if ($column['Field'] === 'subtype_id') $hasSubtypeId = true;
            if ($column['Field'] === 'generated_name') $hasGeneratedName = true;
            if ($column['Field'] === 'name_components') $hasNameComponents = true;
        }
    }
    echo "</table>";
    
    echo "<h3>Column Status:</h3>";
    echo "<ul>";
    echo "<li>equipment_type_id: " . ($hasEquipmentTypeId ? "✅ Present" : "❌ Missing") . "</li>";
    echo "<li>subtype_id: " . ($hasSubtypeId ? "✅ Present" : "❌ Missing") . "</li>";
    echo "<li>generated_name: " . ($hasGeneratedName ? "✅ Present" : "❌ Missing") . "</li>";
    echo "<li>name_components: " . ($hasNameComponents ? "✅ Present" : "❌ Missing") . "</li>";
    echo "</ul>";
    
    if (!$hasEquipmentTypeId || !$hasSubtypeId) {
        echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #ff0000; margin: 10px 0;'>";
        echo "<strong>CRITICAL FINDING:</strong> Equipment classification columns are missing from the assets table.<br>";
        echo "This explains why equipment classification data is not being saved.<br>";
        echo "<strong>Solution:</strong> Run the asset_subtypes_system.sql migration to add these columns.";
        echo "</div>";
        
        // Show the SQL that needs to be run
        echo "<h3>Required SQL Migration:</h3>";
        echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
        echo "ALTER TABLE assets \n";
        echo "ADD COLUMN equipment_type_id INT NULL AFTER category_id,\n";
        echo "ADD COLUMN subtype_id INT NULL AFTER equipment_type_id;\n";
        echo "</pre>";
    } else {
        // Analyze existing data if columns exist
        echo "<h2>Classification Data Analysis</h2>";
        
        try {
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    COUNT(equipment_type_id) as with_equipment_type,
                    COUNT(subtype_id) as with_subtype,
                    COUNT(CASE WHEN equipment_type_id IS NOT NULL AND subtype_id IS NOT NULL THEN 1 END) as with_both
                FROM assets
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h3>Classification Statistics:</h3>";
            echo "<ul>";
            echo "<li><strong>Total Assets:</strong> {$stats['total']}</li>";
            echo "<li><strong>With Equipment Type ID:</strong> {$stats['with_equipment_type']} (" . round($stats['with_equipment_type']/$stats['total']*100, 1) . "%)</li>";
            echo "<li><strong>With Subtype ID:</strong> {$stats['with_subtype']} (" . round($stats['with_subtype']/$stats['total']*100, 1) . "%)</li>";
            echo "<li><strong>With Both Classifications:</strong> {$stats['with_both']} (" . round($stats['with_both']/$stats['total']*100, 1) . "%)</li>";
            echo "</ul>";
            
            // Show recent assets without classification
            echo "<h3>Recent Assets Without Full Classification:</h3>";
            $stmt = $db->query("
                SELECT id, ref, name, created_at, equipment_type_id, subtype_id
                FROM assets 
                WHERE equipment_type_id IS NULL OR subtype_id IS NULL
                ORDER BY created_at DESC 
                LIMIT 15
            ");
            $recentUnclassified = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($recentUnclassified) > 0) {
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Reference</th><th>Name</th><th>Created Date</th><th>Equipment Type</th><th>Subtype</th></tr>";
                foreach ($recentUnclassified as $asset) {
                    echo "<tr>";
                    echo "<td>{$asset['id']}</td>";
                    echo "<td>" . htmlspecialchars($asset['ref']) . "</td>";
                    echo "<td>" . htmlspecialchars($asset['name']) . "</td>";
                    echo "<td>{$asset['created_at']}</td>";
                    echo "<td style='text-align: center;'>" . ($asset['equipment_type_id'] ?? '<em>NULL</em>') . "</td>";
                    echo "<td style='text-align: center;'>" . ($asset['subtype_id'] ?? '<em>NULL</em>') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color: green;'>✅ All assets have complete classification data!</p>";
            }
            
            // Show assets WITH classification (if any)
            $stmt = $db->query("
                SELECT id, ref, name, created_at, equipment_type_id, subtype_id
                FROM assets 
                WHERE equipment_type_id IS NOT NULL AND subtype_id IS NOT NULL
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $classifiedAssets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($classifiedAssets) > 0) {
                echo "<h3>Assets WITH Classification Data (Recent):</h3>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Reference</th><th>Name</th><th>Created Date</th><th>Equipment Type</th><th>Subtype</th></tr>";
                foreach ($classifiedAssets as $asset) {
                    echo "<tr>";
                    echo "<td>{$asset['id']}</td>";
                    echo "<td>" . htmlspecialchars($asset['ref']) . "</td>";
                    echo "<td>" . htmlspecialchars($asset['name']) . "</td>";
                    echo "<td>{$asset['created_at']}</td>";
                    echo "<td style='text-align: center; color: green;'><strong>{$asset['equipment_type_id']}</strong></td>";
                    echo "<td style='text-align: center; color: green;'><strong>{$asset['subtype_id']}</strong></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 10px 0;'>";
                echo "<strong>IMPORTANT FINDING:</strong> No assets have equipment classification data.<br>";
                echo "This suggests that either:<br>";
                echo "1. The form is not sending the classification data<br>";
                echo "2. The controller is not processing the classification data<br>";
                echo "3. Users are not selecting equipment types and subtypes<br>";
                echo "4. There's a JavaScript error preventing the dropdowns from working";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error analyzing classification data: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Check for patterns in asset creation dates
    echo "<h2>Asset Creation Timeline</h2>";
    try {
        $stmt = $db->query("
            SELECT 
                DATE(created_at) as creation_date,
                COUNT(*) as assets_created,
                COUNT(equipment_type_id) as with_equipment_type
            FROM assets 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY creation_date DESC
            LIMIT 10
        ");
        $timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($timeline) > 0) {
            echo "<h3>Recent Asset Creation (Last 30 Days):</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>Date</th><th>Assets Created</th><th>With Equipment Type</th><th>Classification Rate</th></tr>";
            foreach ($timeline as $day) {
                $rate = $day['assets_created'] > 0 ? round($day['with_equipment_type']/$day['assets_created']*100, 1) : 0;
                $rateColor = $rate > 0 ? 'green' : 'red';
                echo "<tr>";
                echo "<td>{$day['creation_date']}</td>";
                echo "<td style='text-align: center;'>{$day['assets_created']}</td>";
                echo "<td style='text-align: center;'>{$day['with_equipment_type']}</td>";
                echo "<td style='text-align: center; color: {$rateColor};'><strong>{$rate}%</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No assets created in the last 30 days.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error analyzing timeline: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Check categories and their relationship to classification
    echo "<h2>Category Analysis</h2>";
    try {
        $stmt = $db->query("
            SELECT 
                c.id, 
                c.name as category_name,
                COUNT(a.id) as total_assets,
                COUNT(a.equipment_type_id) as with_classification
            FROM categories c
            LEFT JOIN assets a ON c.id = a.category_id
            GROUP BY c.id, c.name
            HAVING total_assets > 0
            ORDER BY total_assets DESC
        ");
        $categoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($categoryStats) > 0) {
            echo "<h3>Assets by Category (Classification Status):</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>Category ID</th><th>Category Name</th><th>Total Assets</th><th>With Classification</th><th>Rate</th></tr>";
            foreach ($categoryStats as $cat) {
                $rate = $cat['total_assets'] > 0 ? round($cat['with_classification']/$cat['total_assets']*100, 1) : 0;
                $rateColor = $rate > 0 ? 'green' : 'red';
                echo "<tr>";
                echo "<td>{$cat['id']}</td>";
                echo "<td>" . htmlspecialchars($cat['category_name']) . "</td>";
                echo "<td style='text-align: center;'>{$cat['total_assets']}</td>";
                echo "<td style='text-align: center;'>{$cat['with_classification']}</td>";
                echo "<td style='text-align: center; color: {$rateColor};'><strong>{$rate}%</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error analyzing categories: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Generate summary and next steps
    echo "<h2>Summary and Recommendations</h2>";
    
    $findings = [];
    $nextSteps = [];
    
    if (!$hasEquipmentTypeId || !$hasSubtypeId) {
        $findings[] = "CRITICAL: Equipment classification columns missing from database";
        $nextSteps[] = "Run the asset_subtypes_system.sql migration immediately";
    } elseif ($totalAssets > 0) {
        $stmt = $db->query("SELECT COUNT(*) as classified FROM assets WHERE equipment_type_id IS NOT NULL AND subtype_id IS NOT NULL");
        $classifiedCount = $stmt->fetch(PDO::FETCH_ASSOC)['classified'];
        
        if ($classifiedCount == 0) {
            $findings[] = "No assets have equipment classification data despite database support";
            $nextSteps[] = "Test form submission to verify data capture and processing";
            $nextSteps[] = "Check JavaScript console for errors during asset creation";
            $nextSteps[] = "Verify API endpoints for equipment types and subtypes are working";
        } elseif ($classifiedCount < $totalAssets * 0.5) {
            $findings[] = "Only " . round($classifiedCount/$totalAssets*100, 1) . "% of assets have classification data";
            $nextSteps[] = "Investigate why some assets lack classification data";
            $nextSteps[] = "Check if there are form validation issues";
        }
    }
    
    if (count($findings) > 0) {
        echo "<h3>Key Findings:</h3>";
        echo "<ul>";
        foreach ($findings as $finding) {
            echo "<li style='margin: 5px 0;'>" . htmlspecialchars($finding) . "</li>";
        }
        echo "</ul>";
    }
    
    if (count($nextSteps) > 0) {
        echo "<h3>Recommended Next Steps:</h3>";
        echo "<ol>";
        foreach ($nextSteps as $step) {
            echo "<li style='margin: 5px 0;'>" . htmlspecialchars($step) . "</li>";
        }
        echo "</ol>";
    }
    
    echo "<hr><p><strong>Analysis completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; border: 1px solid red;'>";
    echo "<h2>Analysis Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>