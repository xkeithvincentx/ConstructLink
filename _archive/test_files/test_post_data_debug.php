<?php
/**
 * ConstructLink™ POST Data Debug Test
 * Specifically tests how POST data is being handled during asset creation
 */

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/core/Autoloader.php';

// Initialize autoloader
$autoloader = new Autoloader();
$autoloader->register();

echo "<h1>POST Data Debug Test</h1>";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p><hr>";

// Simulate the exact POST data that would come from the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Received POST Data</h2>";
    
    echo "<h3>Raw POST Data:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    echo "<h3>Equipment Classification Fields:</h3>";
    echo "<ul>";
    echo "<li><strong>equipment_type_id:</strong> " . ($_POST['equipment_type_id'] ?? 'NOT SET') . " (Type: " . gettype($_POST['equipment_type_id'] ?? null) . ")</li>";
    echo "<li><strong>subtype_id:</strong> " . ($_POST['subtype_id'] ?? 'NOT SET') . " (Type: " . gettype($_POST['subtype_id'] ?? null) . ")</li>";
    echo "</ul>";
    
    // Test the exact sanitization logic from AssetController
    echo "<h3>Controller Processing Simulation:</h3>";
    
    $formData = [
        'equipment_type_id' => !empty($_POST['equipment_type_id']) ? (int)$_POST['equipment_type_id'] : null,
        'subtype_id' => !empty($_POST['subtype_id']) ? (int)$_POST['subtype_id'] : null,
    ];
    
    echo "<p><strong>After sanitization:</strong></p>";
    echo "<ul>";
    echo "<li>equipment_type_id: " . var_export($formData['equipment_type_id'], true) . "</li>";
    echo "<li>subtype_id: " . var_export($formData['subtype_id'], true) . "</li>";
    echo "</ul>";
    
    // Test if these values would be saved
    echo "<h3>Database Save Test:</h3>";
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Test if we can insert these values
        $testData = [
            'ref' => 'DEBUG-' . time(),
            'name' => 'Debug Test Asset',
            'category_id' => 1,
            'project_id' => 1,
            'acquired_date' => date('Y-m-d'),
            'equipment_type_id' => $formData['equipment_type_id'],
            'subtype_id' => $formData['subtype_id']
        ];
        
        // Check if columns exist first
        $stmt = $db->query("SHOW COLUMNS FROM assets LIKE 'equipment_type_id'");
        $hasEquipmentType = $stmt->rowCount() > 0;
        
        $stmt = $db->query("SHOW COLUMNS FROM assets LIKE 'subtype_id'");
        $hasSubtype = $stmt->rowCount() > 0;
        
        echo "<p>Database schema check:</p>";
        echo "<ul>";
        echo "<li>equipment_type_id column exists: " . ($hasEquipmentType ? "✅ YES" : "❌ NO") . "</li>";
        echo "<li>subtype_id column exists: " . ($hasSubtype ? "✅ YES" : "❌ NO") . "</li>";
        echo "</ul>";
        
        if ($hasEquipmentType && $hasSubtype) {
            // Build SQL dynamically
            $fields = [];
            $placeholders = [];
            $values = [];
            
            foreach ($testData as $field => $value) {
                if ($value !== null) {
                    $fields[] = $field;
                    $placeholders[] = ':' . $field;
                    $values[$field] = $value;
                }
            }
            
            $sql = "INSERT INTO assets (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            echo "<p><strong>SQL Query:</strong></p>";
            echo "<pre>" . $sql . "</pre>";
            
            echo "<p><strong>Bound Values:</strong></p>";
            echo "<pre>" . print_r($values, true) . "</pre>";
            
            // Don't actually execute to avoid creating test data
            echo "<p style='color: blue;'>⚠️ SQL preparation test successful (not executed to avoid creating test data)</p>";
            
        } else {
            echo "<p style='color: red;'>❌ Cannot test insert - required columns are missing</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} else {
    echo "<h2>POST Data Test Form</h2>";
    echo "<p>Use this form to test how POST data is being processed:</p>";
    
    ?>
    <form method="POST" style="border: 1px solid #ccc; padding: 20px; max-width: 600px;">
        <h3>Test Equipment Classification Data</h3>
        
        <p>
            <label>Equipment Type ID: 
                <select name="equipment_type_id">
                    <option value="">-- Select Equipment Type --</option>
                    <option value="1">Type 1</option>
                    <option value="2">Type 2</option>
                    <option value="3">Type 3</option>
                </select>
            </label>
        </p>
        
        <p>
            <label>Subtype ID: 
                <select name="subtype_id">
                    <option value="">-- Select Subtype --</option>
                    <option value="1">Subtype 1</option>
                    <option value="2">Subtype 2</option>
                    <option value="3">Subtype 3</option>
                </select>
            </label>
        </p>
        
        <p>
            <label>Name: <input type="text" name="name" value="Test Asset" required></label>
        </p>
        
        <p>
            <label>Category ID: <input type="number" name="category_id" value="1" required></label>
        </p>
        
        <p>
            <label>Project ID: <input type="number" name="project_id" value="1" required></label>
        </p>
        
        <p>
            <input type="submit" value="Test POST Data Processing" style="background: #007cba; color: white; padding: 10px 20px;">
        </p>
    </form>
    
    <h2>Manual Testing Instructions</h2>
    <ol>
        <li>Fill out the form above and submit it</li>
        <li>Check how the equipment_type_id and subtype_id values are being processed</li>
        <li>Verify the sanitization and database preparation logic</li>
        <li>Compare with the actual asset creation form behavior</li>
    </ol>
    
    <h2>JavaScript Testing</h2>
    <p>Test if the JavaScript dropdown functionality is working:</p>
    
    <script>
    // Test if jQuery is loaded
    if (typeof jQuery !== 'undefined') {
        console.log('jQuery is loaded');
        document.write('<p style="color: green;">✅ jQuery is available</p>');
    } else {
        console.log('jQuery is not loaded');
        document.write('<p style="color: red;">❌ jQuery is not available</p>');
    }
    
    // Test equipment type dropdown functionality
    function testEquipmentTypeDropdown() {
        const equipmentSelect = document.querySelector('select[name="equipment_type_id"]');
        const subtypeSelect = document.querySelector('select[name="subtype_id"]');
        
        if (equipmentSelect && subtypeSelect) {
            console.log('Dropdown elements found');
            
            equipmentSelect.addEventListener('change', function() {
                console.log('Equipment type changed to:', this.value);
                
                // Simulate AJAX call that would populate subtypes
                if (this.value) {
                    subtypeSelect.innerHTML = '<option value="">Loading...</option>';
                    
                    // Simulate API response
                    setTimeout(() => {
                        subtypeSelect.innerHTML = `
                            <option value="">-- Select Subtype --</option>
                            <option value="10">Subtype for Type ${this.value}-A</option>
                            <option value="11">Subtype for Type ${this.value}-B</option>
                        `;
                    }, 500);
                } else {
                    subtypeSelect.innerHTML = '<option value="">-- Select Subtype --</option>';
                }
            });
            
            document.write('<p style="color: green;">✅ Dropdown event handlers attached</p>');
        } else {
            document.write('<p style="color: red;">❌ Dropdown elements not found</p>');
        }
    }
    
    // Run test when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', testEquipmentTypeDropdown);
    } else {
        testEquipmentTypeDropdown();
    }
    </script>
    
    <?php
}
?>