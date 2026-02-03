<?php
/**
 * Test Script for IntelligentAssetNamer Migration Fix
 *
 * This script tests all methods that were affected by the
 * inventory_subtypes table migration to ensure they work correctly
 * with the new schema.
 */

// Bootstrap the application
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/IntelligentAssetNamer.php';

// Initialize
$namer = new IntelligentAssetNamer();
$errors = [];
$successes = [];

echo "\n=== TESTING IntelligentAssetNamer Migration Fix ===\n\n";

// Test 1: Get first equipment type and subtype for testing
echo "Test 1: Getting sample equipment type and subtype...\n";
try {
    $db = Database::getInstance()->getConnection();

    // Get a sample equipment type
    $stmt = $db->query("SELECT id, name FROM inventory_equipment_types WHERE is_active = 1 LIMIT 1");
    $equipmentType = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($equipmentType) {
        $successes[] = "✓ Found equipment type: {$equipmentType['name']} (ID: {$equipmentType['id']})";

        // Get a sample subtype for this equipment type
        $stmt = $db->prepare("SELECT id, name FROM inventory_subtypes WHERE equipment_type_id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$equipmentType['id']]);
        $subtype = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($subtype) {
            $successes[] = "✓ Found subtype: {$subtype['name']} (ID: {$subtype['id']})";
            $equipmentTypeId = $equipmentType['id'];
            $subtypeId = $subtype['id'];
        } else {
            $errors[] = "✗ No subtypes found for equipment type ID: {$equipmentType['id']}";
            die("\nCannot continue without sample data.\n");
        }
    } else {
        $errors[] = "✗ No equipment types found in database";
        die("\nCannot continue without sample data.\n");
    }
} catch (Exception $e) {
    $errors[] = "✗ Test 1 Failed: " . $e->getMessage();
    die("\nCannot continue.\n");
}

echo "\n";

// Test 2: Generate Asset Name
echo "Test 2: Testing generateAssetName() method...\n";
try {
    $result = $namer->generateAssetName($equipmentTypeId, $subtypeId, 'Test Brand', 'Model-X');

    if (isset($result['generated_name'])) {
        $successes[] = "✓ Generated name: {$result['generated_name']}";

        if (isset($result['name_components'])) {
            $successes[] = "✓ Name components returned: " . json_encode($result['name_components']);
        }

        if (isset($result['equipment_info'])) {
            $successes[] = "✓ Equipment info returned";

            // Check for new columns
            if (isset($result['equipment_info']['technical_name'])) {
                $successes[] = "✓ Technical name field present: {$result['equipment_info']['technical_name']}";
            }
            if (isset($result['equipment_info']['specifications_template'])) {
                $successes[] = "✓ Specifications template field present";
            }
        }
    } else {
        $errors[] = "✗ generateAssetName() did not return generated_name";
    }
} catch (Exception $e) {
    $errors[] = "✗ Test 2 Failed: " . $e->getMessage();
}

echo "\n";

// Test 3: Get Intelligent Unit
echo "Test 3: Testing getIntelligentUnit() method...\n";
try {
    $unit = $namer->getIntelligentUnit($equipmentTypeId, $subtypeId);

    if ($unit) {
        $successes[] = "✓ Got intelligent unit: $unit";
    } else {
        $errors[] = "✗ getIntelligentUnit() returned empty value";
    }
} catch (Exception $e) {
    $errors[] = "✗ Test 3 Failed: " . $e->getMessage();
}

echo "\n";

// Test 4: Get Subtypes By Equipment Type
echo "Test 4: Testing getSubtypesByEquipmentType() method...\n";
try {
    $subtypes = $namer->getSubtypesByEquipmentType($equipmentTypeId);

    if (is_array($subtypes) && count($subtypes) > 0) {
        $successes[] = "✓ Retrieved " . count($subtypes) . " subtype(s)";

        $firstSubtype = $subtypes[0];

        // Check for new column structure
        if (isset($firstSubtype['subtype_name'])) {
            $successes[] = "✓ Subtype name field present: {$firstSubtype['subtype_name']}";
        } else {
            $errors[] = "✗ Missing subtype_name field (aliased from 'name')";
        }

        if (isset($firstSubtype['technical_name'])) {
            $successes[] = "✓ Technical name field present";
        }

        if (isset($firstSubtype['specifications_template'])) {
            $successes[] = "✓ Specifications template field present";
        }

        if (isset($firstSubtype['discipline_tags'])) {
            $successes[] = "✓ Discipline tags field present";
        }

        // Check that old columns are NOT present
        $oldColumns = ['material_type', 'power_source', 'size_category', 'application_area'];
        $foundOldColumns = array_intersect($oldColumns, array_keys($firstSubtype));

        if (empty($foundOldColumns)) {
            $successes[] = "✓ No old column references found";
        } else {
            $errors[] = "✗ Found old column references: " . implode(', ', $foundOldColumns);
        }
    } else {
        $errors[] = "✗ getSubtypesByEquipmentType() returned no results";
    }
} catch (Exception $e) {
    $errors[] = "✗ Test 4 Failed: " . $e->getMessage();
}

echo "\n";

// Test 5: Get Equipment Type Details
echo "Test 5: Testing getEquipmentTypeDetails() method...\n";
try {
    $details = $namer->getEquipmentTypeDetails($equipmentTypeId);

    if ($details) {
        $successes[] = "✓ Got equipment type details";

        if (isset($details['subtypes']) && is_array($details['subtypes'])) {
            $successes[] = "✓ Subtypes included in details: " . count($details['subtypes']) . " subtype(s)";
        }
    } else {
        $errors[] = "✗ getEquipmentTypeDetails() returned null";
    }
} catch (Exception $e) {
    $errors[] = "✗ Test 5 Failed: " . $e->getMessage();
}

echo "\n";

// Test 6: Get Suggestions (Search)
echo "Test 6: Testing getSuggestions() method...\n";
try {
    $suggestions = $namer->getSuggestions('drill');

    if (is_array($suggestions)) {
        $successes[] = "✓ Got suggestions: " . count($suggestions) . " result(s)";

        if (count($suggestions) > 0) {
            $firstSuggestion = $suggestions[0];

            if (isset($firstSuggestion['generated_name'])) {
                $successes[] = "✓ Suggestion includes generated_name: {$firstSuggestion['generated_name']}";
            }

            if (isset($firstSuggestion['confidence'])) {
                $successes[] = "✓ Suggestion includes confidence score: {$firstSuggestion['confidence']}";
            }

            if (isset($firstSuggestion['discipline_tags'])) {
                $successes[] = "✓ Suggestion includes discipline_tags (new field)";
            }

            // Check that old field 'application_area' is NOT present
            if (!isset($firstSuggestion['application_area'])) {
                $successes[] = "✓ Old 'application_area' field correctly not present";
            } else {
                $errors[] = "✗ Old 'application_area' field still present";
            }
        }
    } else {
        $errors[] = "✗ getSuggestions() did not return array";
    }
} catch (Exception $e) {
    $errors[] = "✗ Test 6 Failed: " . $e->getMessage();
}

echo "\n";

// Test 7: Database Column Verification
echo "Test 7: Verifying database schema matches expectations...\n";
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("DESCRIBE inventory_subtypes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $expectedColumns = ['id', 'equipment_type_id', 'name', 'code', 'technical_name',
                        'description', 'discipline_tags', 'specifications_template',
                        'is_active', 'created_at', 'updated_at'];

    $oldColumns = ['subtype_name', 'material_type', 'power_source', 'size_category', 'application_area'];

    $missingColumns = array_diff($expectedColumns, $columns);
    $unexpectedOldColumns = array_intersect($oldColumns, $columns);

    if (empty($missingColumns)) {
        $successes[] = "✓ All expected columns present in inventory_subtypes";
    } else {
        $errors[] = "✗ Missing expected columns: " . implode(', ', $missingColumns);
    }

    if (empty($unexpectedOldColumns)) {
        $successes[] = "✓ No old deprecated columns found in table";
    } else {
        $errors[] = "✗ Found old deprecated columns still in table: " . implode(', ', $unexpectedOldColumns);
    }
} catch (Exception $e) {
    $errors[] = "✗ Test 7 Failed: " . $e->getMessage();
}

echo "\n";

// Print Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 60) . "\n\n";

echo "SUCCESSES (" . count($successes) . "):\n";
foreach ($successes as $success) {
    echo "  $success\n";
}

echo "\n";

if (count($errors) > 0) {
    echo "ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $error) {
        echo "  $error\n";
    }
    echo "\n";
    echo "❌ TESTS FAILED\n\n";
    exit(1);
} else {
    echo "✅ ALL TESTS PASSED!\n\n";
    echo "The IntelligentAssetNamer class has been successfully migrated\n";
    echo "to work with the new inventory_subtypes schema.\n\n";
    exit(0);
}
?>
