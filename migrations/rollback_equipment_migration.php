<?php
/**
 * ConstructLink™ Equipment Tables Migration Rollback
 *
 * Rolls back the equipment tables migration by restoring from backup tables
 *
 * Run: php migrations/rollback_equipment_migration.php
 */

// Define APP_ROOT to allow database.php access
define('APP_ROOT', dirname(__DIR__));

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Load core dependencies
require_once __DIR__ . '/../core/Database.php';

class EquipmentMigrationRollback {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        echo "=== ConstructLink™ Equipment Migration Rollback ===\n\n";
    }

    /**
     * Find the most recent backup tables
     */
    private function findBackupTables() {
        // Find equipment_types backup
        $sql = "SHOW TABLES LIKE 'equipment_types_backup_%'";
        $stmt = $this->db->query($sql);
        $typeBackups = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Find equipment_subtypes backup
        $sql = "SHOW TABLES LIKE 'equipment_subtypes_backup_%'";
        $stmt = $this->db->query($sql);
        $subtypeBackups = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($typeBackups) || empty($subtypeBackups)) {
            throw new Exception("No backup tables found! Cannot rollback.");
        }

        // Get most recent (sort descending, first is most recent)
        rsort($typeBackups);
        rsort($subtypeBackups);

        return [
            'equipment_types_backup' => $typeBackups[0],
            'equipment_subtypes_backup' => $subtypeBackups[0]
        ];
    }

    /**
     * Perform the rollback
     */
    public function rollback() {
        try {
            // Find backup tables
            echo "Finding backup tables...\n";
            $backups = $this->findBackupTables();
            echo "  Found: {$backups['equipment_types_backup']}\n";
            echo "  Found: {$backups['equipment_subtypes_backup']}\n\n";

            // Confirm with user
            echo "WARNING: This will:\n";
            echo "  1. Restore old equipment_types and equipment_subtypes data\n";
            echo "  2. Update inventory_items foreign keys back to old tables\n";
            echo "  3. Update foreign key constraints\n\n";
            echo "Are you sure you want to continue? (yes/no): ";

            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            if (trim($line) !== 'yes') {
                echo "Rollback cancelled.\n";
                return;
            }

            $this->db->beginTransaction();

            // Step 1: Drop new foreign key constraints
            echo "\nStep 1: Dropping new foreign key constraints...\n";
            $this->db->exec("ALTER TABLE inventory_items DROP FOREIGN KEY fk_inventory_equipment_type");
            $this->db->exec("ALTER TABLE inventory_items DROP FOREIGN KEY fk_inventory_subtype");
            echo "  ✓ Foreign keys dropped\n";

            // Step 2: Build mapping from new IDs back to old IDs
            echo "\nStep 2: Building ID mappings...\n";
            $typeMapping = $this->buildTypeMapping($backups['equipment_types_backup']);
            $subtypeMapping = $this->buildSubtypeMapping($backups['equipment_subtypes_backup']);

            // Step 3: Update inventory_items references back to old IDs
            echo "\nStep 3: Restoring inventory_items references...\n";
            $this->restoreItemReferences($typeMapping, $subtypeMapping);

            // Step 4: Restore old foreign key constraints
            echo "\nStep 4: Restoring old foreign key constraints...\n";
            $this->db->exec("
                ALTER TABLE inventory_items
                ADD CONSTRAINT fk_inventory_equipment_type
                FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(id)
            ");
            $this->db->exec("
                ALTER TABLE inventory_items
                ADD CONSTRAINT fk_inventory_subtype
                FOREIGN KEY (subtype_id) REFERENCES equipment_subtypes(id)
            ");
            echo "  ✓ Foreign key constraints restored\n";

            // Step 5: Clean up new tables (optional)
            echo "\nStep 5: Cleaning up migrated data...\n";
            echo "  Note: Keeping new tables intact. You can manually delete migrated records if needed.\n";

            $this->db->commit();

            echo "\n✓ ROLLBACK COMPLETED SUCCESSFULLY!\n\n";
            echo "The system is now using the original equipment_types and equipment_subtypes tables.\n";

        } catch (Exception $e) {
            $this->db->rollBack();
            echo "\n✗ ROLLBACK FAILED: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            exit(1);
        }
    }

    /**
     * Build mapping from new equipment_type IDs to old IDs
     */
    private function buildTypeMapping($backupTable) {
        $mapping = [];

        // Get records from backup that match current inventory_equipment_types
        $sql = "
            SELECT
                iet.id as new_id,
                et.id as old_id
            FROM inventory_equipment_types iet
            JOIN {$backupTable} et ON iet.name = et.name AND iet.category_id = et.category_id
        ";

        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $mapping[$row['new_id']] = $row['old_id'];
            echo "    Mapping: equipment_type NEW:{$row['new_id']} → OLD:{$row['old_id']}\n";
        }

        return $mapping;
    }

    /**
     * Build mapping from new subtype IDs to old IDs
     */
    private function buildSubtypeMapping($backupTable) {
        $mapping = [];

        $sql = "
            SELECT
                ist.id as new_id,
                est.id as old_id
            FROM inventory_subtypes ist
            JOIN {$backupTable} est ON ist.name = est.subtype_name
        ";

        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $mapping[$row['new_id']] = $row['old_id'];
            echo "    Mapping: subtype NEW:{$row['new_id']} → OLD:{$row['old_id']}\n";
        }

        return $mapping;
    }

    /**
     * Restore inventory_items references to old IDs
     */
    private function restoreItemReferences($typeMapping, $subtypeMapping) {
        $typeUpdates = 0;
        $subtypeUpdates = 0;

        // Restore equipment_type_id
        foreach ($typeMapping as $newId => $oldId) {
            $sql = "UPDATE inventory_items SET equipment_type_id = ? WHERE equipment_type_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$oldId, $newId]);
            $affected = $stmt->rowCount();
            if ($affected > 0) {
                $typeUpdates += $affected;
                echo "    ✓ Restored {$affected} items: equipment_type_id {$newId} → {$oldId}\n";
            }
        }

        // Restore subtype_id
        foreach ($subtypeMapping as $newId => $oldId) {
            $sql = "UPDATE inventory_items SET subtype_id = ? WHERE subtype_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$oldId, $newId]);
            $affected = $stmt->rowCount();
            if ($affected > 0) {
                $subtypeUpdates += $affected;
                echo "    ✓ Restored {$affected} items: subtype_id {$newId} → {$oldId}\n";
            }
        }

        echo "  Summary: {$typeUpdates} type references restored, {$subtypeUpdates} subtype references restored\n";
    }
}

// Run rollback
$rollback = new EquipmentMigrationRollback();
$rollback->rollback();
