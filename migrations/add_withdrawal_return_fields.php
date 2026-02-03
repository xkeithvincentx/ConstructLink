<?php
/**
 * Migration: Add return tracking fields to withdrawals table
 * Adds: returned_quantity, return_condition, return_item_notes
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    echo "Adding return tracking fields to withdrawals table...\n";

    // Add return tracking columns
    $sql = "
        ALTER TABLE `withdrawals`
        ADD COLUMN `returned_quantity` int(11) DEFAULT NULL AFTER `return_date`,
        ADD COLUMN `return_condition` enum('Good','Fair','Damaged','Consumed') DEFAULT NULL AFTER `returned_quantity`,
        ADD COLUMN `return_item_notes` text DEFAULT NULL AFTER `return_condition`
    ";

    $db->exec($sql);

    echo "✓ Successfully added returned_quantity column\n";
    echo "✓ Successfully added return_condition column\n";
    echo "✓ Successfully added return_item_notes column\n";
    echo "\nMigration completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
