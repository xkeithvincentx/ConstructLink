<?php
/**
 * ConstructLink™ Equipment Tables Migration
 *
 * Migrates data from old equipment_types/equipment_subtypes tables
 * to new inventory_equipment_types/inventory_subtypes tables
 *
 * SAFE MIGRATION - Creates backups and provides rollback capability
 *
 * Run: php migrations/migrate_equipment_to_inventory_tables.php
 */

// Define APP_ROOT to allow database.php access
define('APP_ROOT', dirname(__DIR__));

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Load core dependencies
require_once __DIR__ . '/../core/Database.php';

class EquipmentTablesMigration {

    private $db;
    private $dryRun = false; // Set to true to preview changes without executing

    public function __construct($dryRun = false) {
        $this->db = Database::getInstance()->getConnection();
        $this->dryRun = $dryRun;
        echo "=== ConstructLink™ Equipment Tables Migration ===\n";
        echo "Mode: " . ($dryRun ? "DRY RUN (Preview Only)" : "LIVE EXECUTION") . "\n\n";
    }

    /**
     * Run the complete migration
     */
    public function migrate() {
        try {
            // Step 1: Analyze current state
            echo "Step 1: Analyzing current database state...\n";
            $this->analyzeCurrentState();

            // Step 2: Create backup tables (DDL - causes implicit commit, can't be in transaction)
            echo "\nStep 2: Creating backup tables...\n";
            $backupTimestamp = $this->createBackupTables();

            // Begin transaction for data operations
            if (!$this->dryRun) {
                $this->db->beginTransaction();
            }

            // Step 3: Migrate equipment_types → inventory_equipment_types
            echo "\nStep 3: Migrating equipment_types → inventory_equipment_types...\n";
            $equipmentTypeMapping = $this->migrateEquipmentTypes();

            // Step 4: Migrate equipment_subtypes → inventory_subtypes
            echo "\nStep 4: Migrating equipment_subtypes → inventory_subtypes...\n";
            $subtypeMapping = $this->migrateSubtypes($equipmentTypeMapping);

            // Step 5: Drop old foreign key constraints (must be done BEFORE updating references)
            echo "\nStep 5: Dropping old foreign key constraints...\n";
            $this->dropOldForeignKeyConstraints();

            // Step 6: Update inventory_items references
            echo "\nStep 6: Updating inventory_items foreign key references...\n";
            $this->updateInventoryItemsReferences($equipmentTypeMapping, $subtypeMapping);

            // Step 7: Add new foreign key constraints
            echo "\nStep 7: Adding new foreign key constraints...\n";
            $this->addNewForeignKeyConstraints();

            // Step 8: Verify migration
            echo "\nStep 8: Verifying migration integrity...\n";
            $this->verifyMigration();

            if ($this->dryRun) {
                echo "\n=== DRY RUN COMPLETE - No changes made ===\n";
            } else {
                // Note: DDL statements (ALTER TABLE) auto-commit in MySQL, so transaction may already be committed
                if ($this->db->inTransaction()) {
                    echo "\n=== Committing changes... ===\n";
                    $this->db->commit();
                }
                echo "\n✓ MIGRATION COMPLETED SUCCESSFULLY!\n\n";
                echo "Backup tables created:\n";
                echo "  - equipment_types_backup_{$backupTimestamp}\n";
                echo "  - equipment_subtypes_backup_{$backupTimestamp}\n";
                echo "\nTo rollback, run: php migrations/rollback_equipment_migration.php\n";
            }

        } catch (Exception $e) {
            if (!$this->dryRun && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo "\n✗ MIGRATION FAILED: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            exit(1);
        }
    }

    /**
     * Step 1: Analyze current database state
     */
    private function analyzeCurrentState() {
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM equipment_types) as old_equipment_types,
                (SELECT COUNT(*) FROM equipment_subtypes) as old_equipment_subtypes,
                (SELECT COUNT(*) FROM inventory_equipment_types) as new_equipment_types,
                (SELECT COUNT(*) FROM inventory_subtypes) as new_subtypes,
                (SELECT COUNT(*) FROM inventory_items WHERE equipment_type_id IS NOT NULL) as items_using_old_type,
                (SELECT COUNT(*) FROM inventory_items WHERE subtype_id IS NOT NULL) as items_using_old_subtype
        ";

        $result = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);

        echo "  Old equipment_types:        {$result['old_equipment_types']} records\n";
        echo "  Old equipment_subtypes:     {$result['old_equipment_subtypes']} records\n";
        echo "  New inventory_equipment_types: {$result['new_equipment_types']} records\n";
        echo "  New inventory_subtypes:     {$result['new_subtypes']} records\n";
        echo "  Items using old type FK:    {$result['items_using_old_type']} items\n";
        echo "  Items using old subtype FK: {$result['items_using_old_subtype']} items\n";
    }

    /**
     * Step 2: Create backup tables
     * Returns the timestamp used for backup table names
     */
    private function createBackupTables() {
        $timestamp = date('Ymd_His');

        // Backup equipment_types
        $sql = "CREATE TABLE equipment_types_backup_{$timestamp} AS SELECT * FROM equipment_types";
        if (!$this->dryRun) {
            $this->db->exec($sql);
        }
        echo "  ✓ Created equipment_types_backup_{$timestamp}\n";

        // Backup equipment_subtypes
        $sql = "CREATE TABLE equipment_subtypes_backup_{$timestamp} AS SELECT * FROM equipment_subtypes";
        if (!$this->dryRun) {
            $this->db->exec($sql);
        }
        echo "  ✓ Created equipment_subtypes_backup_{$timestamp}\n";

        return $timestamp;
    }

    /**
     * Generate unique code for a name within a category
     */
    private function generateUniqueCode($name, $categoryId, $tableName) {
        // Start with first 3-6 letters uppercase
        $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 6));
        if (strlen($baseCode) < 3) {
            $baseCode = strtoupper(substr($name, 0, 6));
        }

        $code = $baseCode;
        $counter = 1;

        // Check if code exists for this category
        while (true) {
            if ($this->dryRun) {
                // In dry run, just return the code
                return $code;
            }

            $checkSql = "SELECT COUNT(*) as count FROM {$tableName}
                         WHERE category_id = ? AND code = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$categoryId, $code]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] == 0) {
                return $code;
            }

            // Code exists, try with number suffix
            $code = $baseCode . $counter;
            $counter++;

            // Safety limit
            if ($counter > 100) {
                throw new Exception("Could not generate unique code for '{$name}' in category {$categoryId}");
            }
        }
    }

    /**
     * Step 3: Migrate equipment_types → inventory_equipment_types
     * Returns mapping of old IDs to new IDs
     */
    private function migrateEquipmentTypes() {
        $mapping = [];

        // Get all equipment_types
        $sql = "SELECT * FROM equipment_types WHERE is_active = 1 ORDER BY id";
        $stmt = $this->db->query($sql);
        $equipmentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $inserted = 0;
        $skipped = 0;

        foreach ($equipmentTypes as $type) {
            // Check if already exists in new table (by name and category_id)
            $checkSql = "SELECT id FROM inventory_equipment_types
                         WHERE name = ? AND category_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$type['name'], $type['category_id']]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Already exists, use existing ID in mapping
                $mapping[$type['id']] = $existing['id'];
                $skipped++;
                echo "    - Skipped '{$type['name']}' (already exists)\n";
            } else {
                // Insert into new table
                $insertSql = "INSERT INTO inventory_equipment_types
                             (category_id, name, code, description, is_active, created_at, updated_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?)";

                // Generate unique code for this category
                $code = $this->generateUniqueCode($type['name'], $type['category_id'], 'inventory_equipment_types');

                if (!$this->dryRun) {
                    $insertStmt = $this->db->prepare($insertSql);
                    $insertStmt->execute([
                        $type['category_id'],
                        $type['name'],
                        $code,
                        $type['description'],
                        $type['is_active'],
                        $type['created_at'],
                        $type['updated_at']
                    ]);
                    $newId = $this->db->lastInsertId();
                    $mapping[$type['id']] = $newId;
                } else {
                    $mapping[$type['id']] = 'DRY_RUN_' . $type['id'];
                }
                $inserted++;
                echo "    + Inserted '{$type['name']}' (OLD:{$type['id']} → NEW:" . $mapping[$type['id']] . ", code: {$code})\n";
            }
        }

        echo "  Summary: {$inserted} inserted, {$skipped} skipped\n";
        return $mapping;
    }

    /**
     * Step 4: Migrate equipment_subtypes → inventory_subtypes
     * Returns mapping of old IDs to new IDs
     */
    private function migrateSubtypes($equipmentTypeMapping) {
        $mapping = [];

        // Get all equipment_subtypes
        $sql = "SELECT * FROM equipment_subtypes WHERE is_active = 1 ORDER BY id";
        $stmt = $this->db->query($sql);
        $subtypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $inserted = 0;
        $skipped = 0;

        foreach ($subtypes as $subtype) {
            // Get new equipment_type_id from mapping
            $newEquipmentTypeId = $equipmentTypeMapping[$subtype['equipment_type_id']] ?? null;

            if (!$newEquipmentTypeId) {
                echo "    ! Warning: Could not find mapping for equipment_type_id {$subtype['equipment_type_id']}\n";
                continue;
            }

            // Check if already exists
            $checkSql = "SELECT id FROM inventory_subtypes
                         WHERE name = ? AND equipment_type_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$subtype['subtype_name'], $newEquipmentTypeId]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $mapping[$subtype['id']] = $existing['id'];
                $skipped++;
                echo "    - Skipped '{$subtype['subtype_name']}' (already exists)\n";
            } else {
                // Generate code (first 3 letters + number)
                $code = strtoupper(substr($subtype['subtype_name'], 0, 3)) . str_pad($subtype['id'], 3, '0', STR_PAD_LEFT);

                // Insert into new table
                $insertSql = "INSERT INTO inventory_subtypes
                             (equipment_type_id, name, code, technical_name, description,
                              discipline_tags, specifications_template, is_active, created_at, updated_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                // Convert technical_specs to specifications_template
                $specsTemplate = $subtype['technical_specs'] ?? null;

                if (!$this->dryRun) {
                    $insertStmt = $this->db->prepare($insertSql);
                    $insertStmt->execute([
                        $newEquipmentTypeId,
                        $subtype['subtype_name'],
                        $code,
                        $subtype['subtype_name'], // Use subtype_name as technical_name
                        $subtype['application_area'],
                        $subtype['discipline_tags'],
                        $specsTemplate,
                        $subtype['is_active'],
                        $subtype['created_at'],
                        $subtype['updated_at']
                    ]);
                    $newId = $this->db->lastInsertId();
                    $mapping[$subtype['id']] = $newId;
                } else {
                    $mapping[$subtype['id']] = 'DRY_RUN_' . $subtype['id'];
                }
                $inserted++;
                echo "    + Inserted '{$subtype['subtype_name']}' (OLD:{$subtype['id']} → NEW:" . $mapping[$subtype['id']] . ")\n";
            }
        }

        echo "  Summary: {$inserted} inserted, {$skipped} skipped\n";
        return $mapping;
    }

    /**
     * Step 5: Update inventory_items references
     */
    private function updateInventoryItemsReferences($equipmentTypeMapping, $subtypeMapping) {
        // Update equipment_type_id references
        $typeUpdates = 0;
        foreach ($equipmentTypeMapping as $oldId => $newId) {
            if (!$this->dryRun) {
                $sql = "UPDATE inventory_items SET equipment_type_id = ? WHERE equipment_type_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$newId, $oldId]);
                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    $typeUpdates += $affected;
                    echo "    ✓ Updated {$affected} items: equipment_type_id {$oldId} → {$newId}\n";
                }
            } else {
                echo "    [DRY RUN] Would update equipment_type_id {$oldId} → {$newId}\n";
            }
        }

        // Update subtype_id references
        $subtypeUpdates = 0;
        foreach ($subtypeMapping as $oldId => $newId) {
            if (!$this->dryRun) {
                $sql = "UPDATE inventory_items SET subtype_id = ? WHERE subtype_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$newId, $oldId]);
                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    $subtypeUpdates += $affected;
                    echo "    ✓ Updated {$affected} items: subtype_id {$oldId} → {$newId}\n";
                }
            } else {
                echo "    [DRY RUN] Would update subtype_id {$oldId} → {$newId}\n";
            }
        }

        echo "  Summary: {$typeUpdates} equipment_type_id updates, {$subtypeUpdates} subtype_id updates\n";
    }

    /**
     * Step 5: Drop old foreign key constraints
     */
    private function dropOldForeignKeyConstraints() {
        if ($this->dryRun) {
            echo "  [DRY RUN] Would drop old foreign key constraints\n";
            return;
        }

        // Drop old foreign keys
        echo "  - Dropping old foreign key: fk_inventory_equipment_type\n";
        $this->db->exec("ALTER TABLE inventory_items DROP FOREIGN KEY fk_inventory_equipment_type");

        echo "  - Dropping old foreign key: fk_inventory_subtype\n";
        $this->db->exec("ALTER TABLE inventory_items DROP FOREIGN KEY fk_inventory_subtype");

        echo "  ✓ Old foreign key constraints dropped\n";
    }

    /**
     * Step 7: Add new foreign key constraints
     */
    private function addNewForeignKeyConstraints() {
        if ($this->dryRun) {
            echo "  [DRY RUN] Would add new foreign key constraints\n";
            return;
        }

        // Add new foreign keys
        echo "  + Adding new foreign key: fk_inventory_equipment_type → inventory_equipment_types\n";
        $this->db->exec("
            ALTER TABLE inventory_items
            ADD CONSTRAINT fk_inventory_equipment_type
            FOREIGN KEY (equipment_type_id) REFERENCES inventory_equipment_types(id)
        ");

        echo "  + Adding new foreign key: fk_inventory_subtype → inventory_subtypes\n";
        $this->db->exec("
            ALTER TABLE inventory_items
            ADD CONSTRAINT fk_inventory_subtype
            FOREIGN KEY (subtype_id) REFERENCES inventory_subtypes(id)
        ");

        echo "  ✓ New foreign key constraints added successfully\n";
    }

    /**
     * Step 7: Verify migration integrity
     */
    private function verifyMigration() {
        if ($this->dryRun) {
            echo "  [DRY RUN] Skipping verification (no actual changes made)\n";
            return;
        }

        // Check for orphaned references
        $sql = "
            SELECT COUNT(*) as orphaned_types
            FROM inventory_items i
            LEFT JOIN inventory_equipment_types et ON i.equipment_type_id = et.id
            WHERE i.equipment_type_id IS NOT NULL AND et.id IS NULL
        ";
        $result = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);

        if ($result['orphaned_types'] > 0) {
            throw new Exception("Found {$result['orphaned_types']} orphaned equipment_type_id references!");
        }
        echo "  ✓ No orphaned equipment_type_id references\n";

        $sql = "
            SELECT COUNT(*) as orphaned_subtypes
            FROM inventory_items i
            LEFT JOIN inventory_subtypes st ON i.subtype_id = st.id
            WHERE i.subtype_id IS NOT NULL AND st.id IS NULL
        ";
        $result = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);

        if ($result['orphaned_subtypes'] > 0) {
            throw new Exception("Found {$result['orphaned_subtypes']} orphaned subtype_id references!");
        }
        echo "  ✓ No orphaned subtype_id references\n";

        // Verify counts
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM inventory_equipment_types) as new_equipment_types,
                (SELECT COUNT(*) FROM inventory_subtypes) as new_subtypes
        ";
        $result = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);

        echo "  ✓ Final counts: {$result['new_equipment_types']} equipment types, {$result['new_subtypes']} subtypes\n";
    }
}

// Parse command line arguments
$dryRun = in_array('--dry-run', $argv ?? []);

// Run migration
$migration = new EquipmentTablesMigration($dryRun);
$migration->migrate();
