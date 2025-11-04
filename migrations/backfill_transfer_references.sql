-- ConstructLink™ Transfer Reference Backfill Migration
-- Generates temporary sequential references for existing transfers
-- These will be replaced with proper ISO 55000 references on the next transfer operation
-- or when the PHP-based backfill script is run properly.

-- For now, give each existing transfer a simple sequential reference
-- Format: TR-2025-XXXX where XXXX is the transfer ID padded to 4 digits

UPDATE transfers
SET ref = CONCAT('TR-2025-', LPAD(id, 4, '0'))
WHERE ref IS NULL OR ref = '';

-- Verify the update
SELECT
    COUNT(*) as total_transfers,
    SUM(CASE WHEN ref IS NOT NULL AND ref != '' THEN 1 ELSE 0 END) as transfers_with_ref,
    SUM(CASE WHEN ref IS NULL OR ref = '' THEN 1 ELSE 0 END) as transfers_without_ref
FROM transfers;

-- Show some examples
SELECT id, ref, asset_id, status, created_at
FROM transfers
ORDER BY id DESC
LIMIT 10;
