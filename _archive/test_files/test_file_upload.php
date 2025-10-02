<?php
require_once 'config/config.php';
require_once 'core/ProcurementFileUploader.php';

echo "Testing ProcurementFileUploader functionality...\n\n";

// Test 1: Check upload directory creation
echo "Test 1: Upload directory check\n";
$uploadPath = ProcurementFileUploader::getUploadPath();
echo "Upload path: $uploadPath\n";

if (is_dir($uploadPath)) {
    echo "✓ Upload directory exists\n";
    if (is_writable($uploadPath)) {
        echo "✓ Upload directory is writable\n";
    } else {
        echo "✗ Upload directory is not writable\n";
    }
} else {
    echo "✗ Upload directory does not exist\n";
    echo "Creating directory...\n";
    if (mkdir($uploadPath, 0755, true)) {
        echo "✓ Upload directory created successfully\n";
    } else {
        echo "✗ Failed to create upload directory\n";
    }
}

// Test 2: File URL generation
echo "\nTest 2: File URL generation\n";
$testFilename = 'quote_file_12345_abc123.pdf';
$url = ProcurementFileUploader::getFileUrl($testFilename);
echo "Generated URL: $url\n";
echo ($url === '/uploads/procurement/' . $testFilename) ? "✓ URL generation works\n" : "✗ URL generation failed\n";

// Test 3: File existence check (should return false for non-existent file)
echo "\nTest 3: File existence check\n";
$exists = ProcurementFileUploader::fileExists($testFilename);
echo ($exists === false) ? "✓ File existence check works (returns false for non-existent file)\n" : "✗ File existence check failed\n";

// Test 4: Empty filename handling
echo "\nTest 4: Empty filename handling\n";
$emptyUrl = ProcurementFileUploader::getFileUrl('');
$emptyExists = ProcurementFileUploader::fileExists('');
$emptySize = ProcurementFileUploader::getFormattedFileSize('');

echo ($emptyUrl === null) ? "✓ Empty filename URL returns null\n" : "✗ Empty filename URL handling failed\n";
echo ($emptyExists === false) ? "✓ Empty filename existence returns false\n" : "✗ Empty filename existence handling failed\n";
echo ($emptySize === null) ? "✓ Empty filename size returns null\n" : "✗ Empty filename size handling failed\n";

// Test 5: Create a test file to verify write permissions
echo "\nTest 5: Write permission test\n";
$testFile = $uploadPath . 'test_write_permission.txt';
if (file_put_contents($testFile, 'Test content') !== false) {
    echo "✓ Write permission test passed\n";
    
    // Test file size formatting
    $formattedSize = ProcurementFileUploader::getFormattedFileSize('test_write_permission.txt');
    echo "Test file size: $formattedSize\n";
    
    // Cleanup test file
    unlink($testFile);
    echo "✓ Test file cleaned up\n";
} else {
    echo "✗ Write permission test failed\n";
}

// Test 6: Database migration check
echo "\nTest 6: Database schema check\n";
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if new columns exist
    $stmt = $pdo->query("DESCRIBE procurement_orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = [
        'purchase_receipt_file', 
        'supporting_evidence_file', 
        'file_upload_notes',
        'retroactive_current_state',
        'retroactive_target_status'
    ];
    
    $missingColumns = [];
    foreach ($requiredColumns as $required) {
        $found = false;
        foreach ($columns as $col) {
            if ($col['Field'] === $required) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missingColumns[] = $required;
        }
    }
    
    if (empty($missingColumns)) {
        echo "✓ All required database columns are present\n";
    } else {
        echo "✗ Missing database columns: " . implode(', ', $missingColumns) . "\n";
        echo "Run the migration: php run_migration.php\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database check failed: " . $e->getMessage() . "\n";
}

echo "\nFile upload system test completed!\n";
?>