<?php
/**
 * Test ISO 55000:2024 Reference Generation
 */

// Bootstrap the application
define('APP_ROOT', __DIR__);
require_once __DIR__ . '/core/Autoloader.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/helpers.php';

echo "=== ISO 55000:2024 Reference Generation Test ===\n\n";

// Test 1: Regular asset references
echo "1. Testing Regular Asset References:\n";

$testCases = [
    ['category_id' => 1, 'discipline_id' => 1, 'expected_pattern' => 'CON-2025-EQ-CV-\d{4}'],
    ['category_id' => 2, 'discipline_id' => 3, 'expected_pattern' => 'CON-2025-TO-ME-\d{4}'],
    ['category_id' => null, 'discipline_id' => null, 'expected_pattern' => 'CON-2025-EQ-GN-\d{4}'],
];

foreach ($testCases as $i => $test) {
    $ref = generateAssetReference($test['category_id'], $test['discipline_id'], false);
    $matches = preg_match('/' . $test['expected_pattern'] . '/', $ref);
    
    echo sprintf("   Test %d: %s - %s\n", 
        $i + 1, 
        $ref, 
        $matches ? "✅ PASS" : "❌ FAIL"
    );
}

echo "\n2. Testing Legacy Asset References:\n";

$legacyTests = [
    ['category_id' => 1, 'discipline_id' => 2, 'expected_pattern' => 'CON-LEG-EQ-ST-\d{4}'],
    ['category_id' => 3, 'discipline_id' => 4, 'expected_pattern' => 'CON-LEG-VE-EL-\d{4}'],
];

foreach ($legacyTests as $i => $test) {
    $ref = generateAssetReference($test['category_id'], $test['discipline_id'], true);
    $matches = preg_match('/' . $test['expected_pattern'] . '/', $ref);
    
    echo sprintf("   Test %d: %s - %s\n", 
        $i + 1, 
        $ref, 
        $matches ? "✅ PASS" : "❌ FAIL"
    );
}

echo "\n3. Testing Reference Parsing:\n";

require_once __DIR__ . '/core/ISO55000ReferenceGenerator.php';
$generator = new ISO55000ReferenceGenerator();

$sampleRefs = [
    'CON-2025-EQ-ME-0001',
    'CON-LEG-TO-EL-0042',
    'CON-2025-VE-CV-0123'
];

foreach ($sampleRefs as $ref) {
    $parsed = $generator->parseReference($ref);
    $description = $generator->describeReference($ref);
    
    echo sprintf("   %s:\n", $ref);
    echo sprintf("     Components: %s\n", $parsed ? 'Valid ISO format' : 'Invalid format');
    echo sprintf("     Description: %s\n", $description);
}

echo "\n4. Testing Category and Discipline Mapping:\n";

// Test with actual database data
try {
    $db = Database::getInstance()->getConnection();
    
    // Get sample categories
    $stmt = $db->query("SELECT id, name FROM categories LIMIT 3");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get sample disciplines  
    $stmt = $db->query("SELECT id, name FROM asset_disciplines WHERE parent_id IS NULL LIMIT 3");
    $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Categories mapped:\n";
    foreach ($categories as $category) {
        $ref = generateAssetReference($category['id'], null, false);
        echo sprintf("     %s → %s\n", $category['name'], $ref);
    }
    
    echo "   Disciplines mapped:\n";
    foreach ($disciplines as $discipline) {
        $ref = generateAssetReference(null, $discipline['id'], false);
        echo sprintf("     %s → %s\n", $discipline['name'], $ref);
    }
    
} catch (Exception $e) {
    echo "   Database test error: " . $e->getMessage() . "\n";
}

echo "\n5. Testing Uniqueness:\n";

// Generate multiple references to ensure uniqueness
$refs = [];
for ($i = 0; $i < 5; $i++) {
    $ref = generateAssetReference(1, 1, false);
    $refs[] = $ref;
}

$unique = array_unique($refs);
$isUnique = count($refs) === count($unique);

echo sprintf("   Generated %d references, %d unique: %s\n", 
    count($refs), 
    count($unique), 
    $isUnique ? "✅ PASS" : "❌ FAIL"
);

echo "\nReferences generated:\n";
foreach ($refs as $ref) {
    echo "   - $ref\n";
}

echo "\n=== Test Complete ===\n";
?>