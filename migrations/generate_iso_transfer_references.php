<?php
/**
 * ConstructLink™ ISO 55000 Transfer Reference Generator
 *
 * Generates proper ISO 55000:2024 compliant references for existing transfers
 * that currently have temporary TR-2025-XXXX references.
 *
 * This script is meant to be run via the web interface or CLI after the application is running.
 */

// Check if running from CLI or web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // Web-based execution - require login
    session_start();
    define('APP_ROOT', dirname(__DIR__));
    require_once APP_ROOT . '/config/config.php';
    require_once APP_ROOT . '/core/Autoloader.php';
    require_once APP_ROOT . '/core/helpers.php';

    $autoloader = new Autoloader();
    $autoloader->register();

    // Check authentication
    $auth = Auth::getInstance();
    if (!$auth->isAuthenticated()) {
        die('Authentication required. Please login first.');
    }

    $currentUser = $auth->getCurrentUser();
    $userRole = $currentUser['role_name'] ?? '';

    // Only System Admin can run migrations
    if ($userRole !== 'System Admin') {
        die('Access denied. Only System Admins can run migrations.');
    }

    echo "<pre>\n";
}

echo "ConstructLink™ ISO 55000 Transfer Reference Generator\n";
echo str_repeat("=", 70) . "\n\n";

try {
    $db = Database::getInstance()->getConnection();

    // Get all transfers with temporary references (TR-2025-XXXX format)
    $sql = "
        SELECT t.id, t.ref as current_ref, t.asset_id,
               a.category_id,
               a.primary_discipline,
               a.name as asset_name,
               c.name as category_name,
               c.iso_code as category_iso_code
        FROM transfers t
        LEFT JOIN assets a ON t.asset_id = a.id
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE t.ref LIKE 'TR-2025-%'
        ORDER BY t.id ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalCount = count($transfers);
    echo "Found {$totalCount} transfer(s) with temporary references.\n\n";

    if ($totalCount === 0) {
        echo "No transfers to update. All transfers already have ISO references.\n";
        if (!$isCLI) echo "</pre>";
        exit(0);
    }

    echo "Starting ISO 55000 reference generation...\n";
    echo str_repeat("-", 70) . "\n";

    $successCount = 0;
    $errorCount = 0;
    $errors = [];

    foreach ($transfers as $transfer) {
        $transferId = $transfer['id'];
        $currentRef = $transfer['current_ref'];
        $categoryId = $transfer['category_id'];
        $disciplineId = $transfer['primary_discipline'];
        $assetName = $transfer['asset_name'];

        try {
            // Generate ISO 55000 reference
            $isoReference = generateTransferReference($categoryId, $disciplineId);

            // Update the transfer record
            $updateSql = "UPDATE transfers SET ref = ? WHERE id = ?";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([$isoReference, $transferId]);

            $successCount++;
            echo sprintf(
                "[%d/%d] Transfer ID %d:\n",
                $successCount + $errorCount,
                $totalCount,
                $transferId
            );
            echo sprintf("  Asset: %s\n", $assetName);
            echo sprintf("  Old Ref: %s\n", $currentRef);
            echo sprintf("  New Ref: %s\n\n", $isoReference);

        } catch (Exception $e) {
            $errorCount++;
            $errorMsg = "Transfer ID {$transferId}: " . $e->getMessage();
            $errors[] = $errorMsg;
            echo sprintf(
                "[%d/%d] ERROR: %s\n\n",
                $successCount + $errorCount,
                $totalCount,
                $errorMsg
            );
        }
    }

    echo str_repeat("-", 70) . "\n";
    echo "\nGeneration Summary:\n";
    echo "  Total Transfers: {$totalCount}\n";
    echo "  Successfully Updated: {$successCount}\n";
    echo "  Errors: {$errorCount}\n";

    if ($errorCount > 0) {
        echo "\nErrors encountered:\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
    }

    echo "\nISO 55000 reference generation completed.\n";

    // Verify the results
    echo "\nVerifying results...\n";
    $verifySql = "SELECT COUNT(*) as count FROM transfers WHERE ref LIKE 'TR-2025-%'";
    $verifyStmt = $db->prepare($verifySql);
    $verifyStmt->execute();
    $remaining = $verifyStmt->fetchColumn();

    if ($remaining > 0) {
        echo "WARNING: {$remaining} transfer(s) still have temporary references.\n";
    } else {
        echo "SUCCESS: All transfers now have ISO 55000:2024 compliant references.\n";
    }

    if (!$isCLI) echo "</pre>";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    if (!$isCLI) echo "</pre>";
    exit(1);
}
?>
