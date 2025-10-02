-- Diagnostic queries to identify return workflow issues
-- Run these to understand current state and fix any inconsistencies

-- 1. Show all temporary transfers with their return status and asset status
SELECT 
    t.id as transfer_id,
    t.status as transfer_status,
    t.return_status,
    a.status as asset_status,
    a.ref as asset_ref,
    a.project_id as current_project,
    t.from_project,
    t.to_project,
    pf.name as from_project_name,
    pt.name as to_project_name,
    t.return_initiation_date,
    CASE 
        WHEN t.return_status = 'in_return_transit' AND a.status != 'in_transit' THEN 'MISMATCH: Return in transit but asset not in_transit'
        WHEN t.return_status = 'not_returned' AND a.status = 'in_transit' THEN 'MISMATCH: Asset in transit but return not initiated'
        WHEN t.return_status = 'returned' AND a.project_id != t.from_project THEN 'MISMATCH: Return completed but asset not at origin'
        ELSE 'OK'
    END as status_check
FROM transfers t
JOIN assets a ON t.asset_id = a.id
LEFT JOIN projects pf ON t.from_project = pf.id
LEFT JOIN projects pt ON t.to_project = pt.id
WHERE t.transfer_type = 'temporary'
  AND t.status = 'Completed'
ORDER BY t.id DESC;

-- 2. Find specific problematic cases
-- Returns in transit but assets not in_transit status
SELECT 
    'Returns in transit but assets not in_transit:' as issue_type,
    t.id as transfer_id,
    a.ref as asset_ref,
    t.return_status,
    a.status as asset_status,
    a.project_id as current_project,
    t.to_project as should_be_at_project
FROM transfers t
JOIN assets a ON t.asset_id = a.id
WHERE t.transfer_type = 'temporary'
  AND t.return_status = 'in_return_transit'
  AND a.status NOT IN ('in_transit')
UNION ALL
-- Assets in transit but returns not initiated
SELECT 
    'Assets in transit but returns not initiated:' as issue_type,
    t.id as transfer_id,
    a.ref as asset_ref,
    t.return_status,
    a.status as asset_status,
    a.project_id as current_project,
    t.to_project as should_be_at_project
FROM transfers t
JOIN assets a ON t.asset_id = a.id
WHERE t.transfer_type = 'temporary'
  AND t.status = 'Completed'
  AND t.return_status = 'not_returned'
  AND a.status = 'in_transit';

-- 3. Fix return workflow inconsistencies
-- Fix case 1: Returns in transit but asset not in_transit (set asset to in_transit)
UPDATE assets a
JOIN transfers t ON a.id = t.asset_id
SET a.status = 'in_transit'
WHERE t.transfer_type = 'temporary'
  AND t.return_status = 'in_return_transit'
  AND a.status = 'available';

-- Fix case 2: Asset in_transit but return not initiated (set return status)
UPDATE transfers t
JOIN assets a ON t.asset_id = a.id
SET t.return_status = 'in_return_transit',
    t.return_initiation_date = COALESCE(t.return_initiation_date, NOW())
WHERE t.transfer_type = 'temporary'
  AND t.status = 'Completed'
  AND t.return_status = 'not_returned'
  AND a.status = 'in_transit';

-- 4. Verification query - run after fixes
SELECT 
    'After fixes - remaining issues:' as summary,
    COUNT(*) as count
FROM transfers t
JOIN assets a ON t.asset_id = a.id
WHERE t.transfer_type = 'temporary'
  AND t.status = 'Completed'
  AND (
    (t.return_status = 'in_return_transit' AND a.status NOT IN ('in_transit', 'available')) OR
    (t.return_status = 'not_returned' AND a.status = 'in_transit') OR
    (t.return_status = 'returned' AND a.project_id != t.from_project)
  );

-- 5. Show summary of return status distribution
SELECT 
    'Return Status Summary:' as info,
    return_status,
    COUNT(*) as count
FROM transfers 
WHERE transfer_type = 'temporary' 
  AND status = 'Completed'
GROUP BY return_status
ORDER BY return_status;