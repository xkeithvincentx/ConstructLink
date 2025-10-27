-- Fix Batch Status Inconsistency
-- Problem: Batches marked as "Returned" but have items still borrowed
-- Caused by: Previous code always set batch to "Returned" even for partial returns
-- Solution: Reset batch status to "In Use" where items are still borrowed

-- Step 1: Identify affected batches
SELECT
    b.id,
    b.batch_reference,
    b.status as batch_status,
    COUNT(bt.id) as total_items,
    SUM(CASE WHEN bt.status = 'returned' THEN 1 ELSE 0 END) as items_returned,
    SUM(CASE WHEN bt.status != 'returned' THEN 1 ELSE 0 END) as items_still_borrowed
FROM borrowed_tool_batches b
LEFT JOIN borrowed_tools bt ON bt.batch_id = b.id
WHERE b.status = 'returned'
GROUP BY b.id
HAVING items_still_borrowed > 0
ORDER BY b.id DESC;

-- Step 2: Fix the inconsistency
-- Reset batch status to "in_use" and clear returned_at timestamp
UPDATE borrowed_tool_batches b
SET
    status = 'in_use',
    returned_at = NULL,
    returned_by = NULL
WHERE b.status = 'returned'
AND EXISTS (
    SELECT 1
    FROM borrowed_tools bt
    WHERE bt.batch_id = b.id
    AND bt.status != 'returned'
);

-- Step 3: Verify the fix
SELECT
    b.id,
    b.batch_reference,
    b.status as batch_status,
    COUNT(bt.id) as total_items,
    SUM(CASE WHEN bt.status = 'returned' THEN 1 ELSE 0 END) as items_returned,
    SUM(CASE WHEN bt.status != 'returned' THEN 1 ELSE 0 END) as items_still_borrowed
FROM borrowed_tool_batches b
LEFT JOIN borrowed_tools bt ON bt.batch_id = b.id
WHERE b.id IN (
    SELECT DISTINCT b2.id
    FROM borrowed_tool_batches b2
    LEFT JOIN borrowed_tools bt2 ON bt2.batch_id = b2.id
    WHERE b2.status = 'returned'
    OR bt2.status != 'returned'
)
GROUP BY b.id
ORDER BY b.id DESC
LIMIT 20;

-- For specific batch BRW-CDOJCLDSBMS-2025-0009:
-- UPDATE borrowed_tool_batches
-- SET status = 'in_use', returned_at = NULL, returned_by = NULL
-- WHERE batch_reference = 'BRW-CDOJCLDSBMS-2025-0009';
