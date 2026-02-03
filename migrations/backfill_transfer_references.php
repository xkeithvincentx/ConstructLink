<?php
/**
 * ConstructLink™ Transfer Reference Backfill Migration
 *
 * Generates ISO 55000:2024 compliant references for existing transfer records
 * that don't have references yet.
 *
 * This script:
 * 1. Fetches all transfers without references
 * 2. Gets asset details (category, discipline) for each transfer
 * 3. Generates ISO 55000 compliant references
 * 4. Updates transfer records with generated references
 */

// Bootstrap the application
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Autoloader.php';
require_once APP_ROOT . '/core/helpers.php';

// Initialize autoloader
$autoloader = new Autoloader();
$autoloader->register();

echo "ConstructLink™ Transfer Reference Backfill Migration\n";
echo str_repeat("=", 60) . "\n\n";

try {
    $db = Database::getInstance()->getConnection();

    // Get all transfers without references
    $sql = "
        SELECT t.id, t.inventory_item_id,
               a.category_id,
               a.primary_discipline
        FROM transfers t
        LEFT JOIN inventory_items a ON t.inventory_item_id = a.id
        WHERE t.ref IS NULL OR t.ref = ''
        ORDER BY t.id ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalCount = count($transfers);
    echo "Found {$totalCount} transfer(s) without references.\n\n";

    if ($totalCount === 0) {
        echo "No transfers to backfill. All transfers already have references.\n";
        exit(0);
    }

    // Confirm before proceeding
    echo "This will generate ISO 55000:2024 compliant references for {$totalCount} transfer(s).\n";
    echo "Continue? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);

    if (trim(strtolower($line)) !== 'yes') {
        echo "Migration cancelled.\n";
        exit(0);
    }

    echo "\nStarting backfill process...\n";
    echo str_repeat("-", 60) . "\n";

    $successCount = 0;
    $errorCount = 0;
    $errors = [];

    foreach ($transfers as $index => $transfer) {
        $transferId = $transfer['id'];
        $categoryId = $transfer['category_id'];
        $disciplineId = $transfer['primary_discipline'];

        try {
            // Generate reference using the helper function
            $reference = generateTransferReference($categoryId, $disciplineId);

            // Update the transfer record
            $updateSql = "UPDATE transfers SET ref = ? WHERE id = ?";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([$reference, $transferId]);

            $successCount++;
            echo sprintf(
                "[%d/%d] Transfer ID %d: Generated reference %s\n",
                $successCount + $errorCount,
                $totalCount,
                $transferId,
                $reference
            );

        } catch (Exception $e) {
            $errorCount++;
            $errorMsg = "Transfer ID {$transferId}: " . $e->getMessage();
            $errors[] = $errorMsg;
            echo sprintf(
                "[%d/%d] ERROR: %s\n",
                $successCount + $errorCount,
                $totalCount,
                $errorMsg
            );
        }
    }

    echo str_repeat("-", 60) . "\n";
    echo "\nBackfill Summary:\n";
    echo "  Total Transfers: {$totalCount}\n";
    echo "  Successfully Updated: {$successCount}\n";
    echo "  Errors: {$errorCount}\n";

    if ($errorCount > 0) {
        echo "\nErrors encountered:\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
    }

    echo "\nMigration completed.\n";

    // Verify the results
    echo "\nVerifying results...\n";
    $verifySql = "SELECT COUNT(*) as count FROM transfers WHERE ref IS NULL OR ref = ''";
    $verifyStmt = $db->prepare($verifySql);
    $verifyStmt->execute();
    $remaining = $verifyStmt->fetchColumn();

    if ($remaining > 0) {
        echo "WARNING: {$remaining} transfer(s) still without references.\n";
    } else {
        echo "SUCCESS: All transfers now have references.\n";
    }

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
