-- Fix asset location issues in return workflow
-- This script diagnoses and fixes assets that are at wrong locations

-- 1. Diagnose current asset locations vs expected locations
SELECT 
    'Asset Location Diagnosis:' as info,
    t.id as transfer_id,
    a.ref as asset_ref,
    t.status as transfer_status,
    t.return_status,
    a.status as asset_status,
    a.project_id as current_project_id,
    pc.name as current_project_name,
    t.from_project as origin_project_id,
    pf.name as origin_project_name,
    t.to_project as destination_project_id,
    pt.name as destination_project_name,
    CASE 
        WHEN t.return_status = 'not_returned' AND a.project_id != t.to_project THEN 'ERROR: Asset should be at destination'
        WHEN t.return_status = 'in_return_transit' AND a.project_id NOT IN (t.from_project, t.to_project) THEN 'ERROR: Asset at wrong location during return'
        WHEN t.return_status = 'returned' AND a.project_id != t.from_project THEN 'ERROR: Asset should be back at origin'
        WHEN t.return_status = 'in_return_transit' AND a.project_id = t.from_project THEN 'WARNING: Asset already at origin during return transit'
        ELSE 'OK'
    END as location_status
FROM transfers t
JOIN assets a ON t.asset_id = a.id
LEFT JOIN projects pc ON a.project_id = pc.id
LEFT JOIN projects pf ON t.from_project = pf.id
LEFT JOIN projects pt ON t.to_project = pt.id
WHERE t.transfer_type = 'temporary' 
  AND t.status = 'Completed'
ORDER BY t.id DESC;

-- 2. Fix specific issues

-- Fix Issue 1: Completed transfers where asset should be at destination but isn't
UPDATE assets a
JOIN transfers t ON a.id = t.asset_id
SET a.project_id = t.to_project
WHERE t.transfer_type = 'temporary'
  AND t.status = 'Completed'
  AND t.return_status = 'not_returned'
  AND a.project_id != t.to_project
  AND a.project_id != t.from_project; -- Don't move if already at origin (might be manually moved)

-- Fix Issue 2: Returns in transit but asset already at origin (set return as completed)
UPDATE transfers t
JOIN assets a ON t.asset_id = a.id
SET t.return_status = 'returned',
    t.return_receipt_date = COALESCE(t.return_receipt_date, NOW()),
    t.actual_return = COALESCE(t.actual_return, CURDATE())
WHERE t.transfer_type = 'temporary'
  AND t.return_status = 'in_return_transit'
  AND a.project_id = t.from_project
  AND a.status = 'available';

-- Fix Issue 3: Returns marked as completed but asset not at origin
UPDATE assets a
JOIN transfers t ON a.id = t.asset_id
SET a.project_id = t.from_project,
    a.status = 'available'
WHERE t.transfer_type = 'temporary'
  AND t.return_status = 'returned'
  AND a.project_id != t.from_project;

-- 3. Handle assets that are in_transit but at wrong location
-- Move them to the appropriate location based on return status
UPDATE assets a
JOIN transfers t ON a.id = t.asset_id
SET a.project_id = CASE 
    WHEN t.return_status = 'not_returned' THEN t.to_project
    WHEN t.return_status = 'in_return_transit' THEN t.from_project
    WHEN t.return_status = 'returned' THEN t.from_project
    ELSE t.to_project
END
WHERE t.transfer_type = 'temporary'
  AND t.status = 'Completed'
  AND a.status = 'in_transit'
  AND a.project_id NOT IN (t.from_project, t.to_project);

-- 4. Verification - show any remaining issues
SELECT 
    'Remaining Issues After Fix:' as summary,
    COUNT(*) as issue_count
FROM transfers t
JOIN assets a ON t.asset_id = a.id
WHERE t.transfer_type = 'temporary' 
  AND t.status = 'Completed'
  AND (
    (t.return_status = 'not_returned' AND a.project_id != t.to_project) OR
    (t.return_status = 'returned' AND a.project_id != t.from_project) OR
    (t.return_status = 'in_return_transit' AND a.project_id NOT IN (t.from_project, t.to_project))
  );

-- 5. Show current state summary
SELECT 
    'Final Status Summary:' as info,
    t.return_status,
    COUNT(*) as count,
    GROUP_CONCAT(DISTINCT 
        CASE 
            WHEN a.project_id = t.from_project THEN 'at_origin'
            WHEN a.project_id = t.to_project THEN 'at_destination'
            ELSE 'at_other'
        END
    ) as asset_locations
FROM transfers t
JOIN assets a ON t.asset_id = a.id
WHERE t.transfer_type = 'temporary' 
  AND t.status = 'Completed'
GROUP BY t.return_status
ORDER BY t.return_status;