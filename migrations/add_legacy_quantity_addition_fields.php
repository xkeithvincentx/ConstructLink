<?php
/**
 * Migration: Add Legacy Quantity Addition Fields
 *
 * Adds fields to support pending quantity additions for consumable items
 * in the legacy MVA workflow. When a Warehouseman tries to create a duplicate
 * consumable item, the quantity is added as "pending" and goes through the
 * legacy MVA workflow (Maker → Verifier → Authorizer).
 *
 * This is separate from the restock request workflow which involves procurement.
 *
 * Database: constructlink_db
 * Table: inventory_items
 *
 * @package ConstructLink
 * @version 1.0.0
 * @created 2025-11-11
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';

class AddLegacyQuantityAdditionFields {
    private $db;

    public function __construct() {
        $database = Database::getInstance();
        $this->db = $database->getConnection();
    }

    /**
     * Run the migration
     */
    public function up() {
        try {
            echo "Starting migration: Add Legacy Quantity Addition Fields\n";
            echo "========================================================\n\n";

            // Check if columns already exist
            $stmt = $this->db->query("SHOW COLUMNS FROM inventory_items LIKE 'pending_quantity_addition'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Columns already exist. Skipping migration.\n";
                return true;
            }

            echo "Adding columns to inventory_items table...\n";

            // Add pending_quantity_addition field
            $sql1 = "ALTER TABLE inventory_items
                     ADD COLUMN pending_quantity_addition INT(11) DEFAULT 0
                     COMMENT 'Quantity pending approval through legacy MVA workflow'
                     AFTER available_quantity";

            $this->db->exec($sql1);
            echo "✓ Added pending_quantity_addition column\n";

            // Add pending_addition_made_by field
            $sql2 = "ALTER TABLE inventory_items
                     ADD COLUMN pending_addition_made_by INT(11) NULL
                     COMMENT 'Warehouseman who added the pending quantity'
                     AFTER pending_quantity_addition";

            $this->db->exec($sql2);
            echo "✓ Added pending_addition_made_by column\n";

            // Add pending_addition_date field
            $sql3 = "ALTER TABLE inventory_items
                     ADD COLUMN pending_addition_date TIMESTAMP NULL
                     COMMENT 'When the pending quantity addition was made'
                     AFTER pending_addition_made_by";

            $this->db->exec($sql3);
            echo "✓ Added pending_addition_date column\n";

            // Add foreign key for pending_addition_made_by
            $sql4 = "ALTER TABLE inventory_items
                     ADD CONSTRAINT fk_inventory_pending_addition_made_by
                     FOREIGN KEY (pending_addition_made_by) REFERENCES users(id)
                     ON DELETE SET NULL ON UPDATE CASCADE";

            $this->db->exec($sql4);
            echo "✓ Added foreign key constraint for pending_addition_made_by\n";

            // Add index for workflow queries
            $sql5 = "ALTER TABLE inventory_items
                     ADD INDEX idx_pending_quantity_workflow (pending_quantity_addition, workflow_status)";

            $this->db->exec($sql5);
            echo "✓ Added index for workflow queries\n";

            echo "\n========================================================\n";
            echo "Migration completed successfully!\n\n";
            echo "Summary:\n";
            echo "- Added pending_quantity_addition column (INT)\n";
            echo "- Added pending_addition_made_by column (INT, FK to users)\n";
            echo "- Added pending_addition_date column (TIMESTAMP)\n";
            echo "- Added foreign key constraint\n";
            echo "- Added workflow index\n\n";

            return true;

        } catch (PDOException $e) {
            echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Rollback the migration
     */
    public function down() {
        try {
            echo "Rolling back migration: Add Legacy Quantity Addition Fields\n";
            echo "============================================================\n\n";

            // Check if columns exist
            $stmt = $this->db->query("SHOW COLUMNS FROM inventory_items LIKE 'pending_quantity_addition'");
            if ($stmt->rowCount() === 0) {
                echo "✓ Columns don't exist. Nothing to rollback.\n";
                return true;
            }

            echo "Removing columns from inventory_items table...\n";

            // Drop foreign key first
            $sql1 = "ALTER TABLE inventory_items
                     DROP FOREIGN KEY fk_inventory_pending_addition_made_by";

            $this->db->exec($sql1);
            echo "✓ Dropped foreign key constraint\n";

            // Drop index
            $sql2 = "ALTER TABLE inventory_items
                     DROP INDEX idx_pending_quantity_workflow";

            $this->db->exec($sql2);
            echo "✓ Dropped workflow index\n";

            // Drop columns
            $sql3 = "ALTER TABLE inventory_items
                     DROP COLUMN pending_addition_date,
                     DROP COLUMN pending_addition_made_by,
                     DROP COLUMN pending_quantity_addition";

            $this->db->exec($sql3);
            echo "✓ Dropped pending quantity addition columns\n";

            echo "\n============================================================\n";
            echo "Rollback completed successfully!\n\n";

            return true;

        } catch (PDOException $e) {
            echo "\n✗ Rollback failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Run migration if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ConstructLink™ - Database Migration                      ║\n";
    echo "║  Add Legacy Quantity Addition Fields                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";

    $migration = new AddLegacyQuantityAdditionFields();

    // Check command line argument
    $action = $argv[1] ?? 'up';

    if ($action === 'down' || $action === 'rollback') {
        $result = $migration->down();
    } else {
        $result = $migration->up();
    }

    exit($result ? 0 : 1);
}
