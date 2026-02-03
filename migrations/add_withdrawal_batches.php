<?php
/**
 * ConstructLink Migration: Add Withdrawal Batches Support
 * Creates withdrawal_batches table and related tables for batch processing
 */

// Bootstrap the application
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/Autoloader.php';
require_once APP_ROOT . '/core/helpers.php';

// Initialize autoloader
$autoloader = new Autoloader();
$autoloader->register();

try {
    $db = Database::getInstance()->getConnection();

    echo "=== Withdrawal Batches Migration ===\n\n";

    // Start transaction
    $db->beginTransaction();

    // 1. Create withdrawal_batches table
    echo "Creating withdrawal_batches table...\n";
    $sql1 = "CREATE TABLE IF NOT EXISTS withdrawal_batches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_reference VARCHAR(50) UNIQUE NOT NULL COMMENT 'WDR-PROJ-YYYY-NNNN',
        receiver_name VARCHAR(100) NOT NULL,
        receiver_contact VARCHAR(100),
        purpose TEXT,
        status VARCHAR(50) DEFAULT 'Pending Verification',

        -- MVA Workflow Fields
        issued_by INT,
        verified_by INT,
        approved_by INT,
        released_by INT,
        canceled_by INT,

        verification_date DATETIME,
        approval_date DATETIME,
        release_date DATETIME,
        cancellation_date DATETIME,

        verification_notes TEXT,
        approval_notes TEXT,
        release_notes TEXT,
        cancellation_reason TEXT,

        -- Batch Metadata
        total_items INT DEFAULT 0,
        total_quantity INT DEFAULT 0,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        FOREIGN KEY (issued_by) REFERENCES users(id),
        FOREIGN KEY (verified_by) REFERENCES users(id),
        FOREIGN KEY (approved_by) REFERENCES users(id),
        FOREIGN KEY (released_by) REFERENCES users(id),
        FOREIGN KEY (canceled_by) REFERENCES users(id),

        INDEX idx_status (status),
        INDEX idx_created_at (created_at),
        INDEX idx_batch_ref (batch_reference)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $db->exec($sql1);
    echo "✓ withdrawal_batches table created\n";

    // 2. Create withdrawal_batch_sequences table
    echo "Creating withdrawal_batch_sequences table...\n";
    $sql2 = "CREATE TABLE IF NOT EXISTS withdrawal_batch_sequences (
        project_id INT NOT NULL,
        year INT NOT NULL,
        last_sequence INT DEFAULT 0,
        PRIMARY KEY (project_id, year),
        FOREIGN KEY (project_id) REFERENCES projects(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $db->exec($sql2);
    echo "✓ withdrawal_batch_sequences table created\n";

    // 3. Check if batch_id column exists in withdrawals table
    echo "Checking withdrawals table structure...\n";
    $checkSql = "SHOW COLUMNS FROM withdrawals LIKE 'batch_id'";
    $stmt = $db->query($checkSql);
    $batchIdExists = $stmt->rowCount() > 0;

    if (!$batchIdExists) {
        echo "Adding batch_id column to withdrawals table...\n";
        $sql3 = "ALTER TABLE withdrawals
                 ADD COLUMN batch_id INT NULL AFTER id,
                 ADD INDEX idx_batch_id (batch_id)";
        $db->exec($sql3);

        // Add foreign key separately (some MySQL versions handle this better)
        $sql4 = "ALTER TABLE withdrawals
                 ADD FOREIGN KEY (batch_id) REFERENCES withdrawal_batches(id)";
        $db->exec($sql4);
        echo "✓ batch_id column added to withdrawals table\n";
    } else {
        echo "✓ batch_id column already exists in withdrawals table\n";
    }

    // 4. Create withdrawal_batch_logs table for audit trail
    echo "Creating withdrawal_batch_logs table...\n";
    $sql5 = "CREATE TABLE IF NOT EXISTS withdrawal_batch_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        user_id INT NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (batch_id) REFERENCES withdrawal_batches(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_batch_action (batch_id, action),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $db->exec($sql5);
    echo "✓ withdrawal_batch_logs table created\n";

    // Commit transaction if in one
    if ($db->inTransaction()) {
        $db->commit();
    }

    echo "\n=== Migration Completed Successfully ===\n";
    echo "Tables created:\n";
    echo "  - withdrawal_batches\n";
    echo "  - withdrawal_batch_sequences\n";
    echo "  - withdrawal_batch_logs\n";
    echo "  - withdrawals (modified with batch_id)\n";

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "ERROR: Migration failed\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Some tables may have been created successfully.\n";
    exit(1);
}
?>
