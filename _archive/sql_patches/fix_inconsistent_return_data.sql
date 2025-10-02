-- Fix inconsistent return workflow data
-- Problem: Transfers with assets in 'in_transit' but return_status still 'not_returned'
-- This causes the "Initiate Return" button to still show when it shouldn't

-- Step 1: Identify problematic transfers
SELECT 
    'BEFORE FIX - Problematic Transfers:' as status,
    COUNT(*) as count
FROM transfers t
JOIN assets a ON t.asset_id = a.id  
WHERE t.transfer_type = 'temporary' 
  AND t.status = 'Completed'
  AND a.status = 'in_transit' 
  AND t.return_status = 'not_returned';

-- Step 2: Show the actual problematic records
SELECT 
    t.id as transfer_id,
    t.status as transfer_status,
    t.return_status,
    a.status as asset_status,
    a.ref as asset_ref,
    t.transfer_type,
    'NEEDS_FIX' as action_needed
FROM transfers t
JOIN assets a ON t.asset_id = a.id  
WHERE t.transfer_type = 'temporary' 
  AND t.status = 'Completed'
  AND a.status = 'in_transit' 
  AND t.return_status = 'not_returned';

-- Step 3: Fix the inconsistent data
UPDATE transfers t
JOIN assets a ON t.asset_id = a.id  
SET t.return_status = 'in_return_transit'
WHERE t.transfer_type = 'temporary' 
  AND t.status = 'Completed'
  AND a.status = 'in_transit' 
  AND t.return_status = 'not_returned';

-- Step 4: Verify the fix worked
SELECT 
    'AFTER FIX - Remaining Problematic Transfers:' as status,
    COUNT(*) as count
FROM transfers t
JOIN assets a ON t.asset_id = a.id  
WHERE t.transfer_type = 'temporary' 
  AND t.status = 'Completed'
  AND a.status = 'in_transit' 
  AND t.return_status = 'not_returned';

-- Step 5: Show the corrected records
SELECT 
    t.id as transfer_id,
    t.status as transfer_status,
    t.return_status,
    a.status as asset_status,
    a.ref as asset_ref,
    t.transfer_type,
    'FIXED' as action_taken
FROM transfers t
JOIN assets a ON t.asset_id = a.id  
WHERE t.transfer_type = 'temporary' 
  AND t.status = 'Completed'
  AND a.status = 'in_transit' 
  AND t.return_status = 'in_return_transit';

-- Step 6: Summary of all return statuses
SELECT 
    'SUMMARY - All Return Statuses:' as info,
    t.return_status,
    COUNT(*) as count
FROM transfers t
WHERE t.transfer_type = 'temporary'
GROUP BY t.return_status
ORDER BY t.return_status;