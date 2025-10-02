-- Fix return_status enum to remove unnecessary 'return_initiated' value
-- This should be run if the return workflow is not working properly

-- First, check current enum values
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'transfers' 
  AND COLUMN_NAME = 'return_status';

-- Update any 'return_initiated' values to 'in_return_transit' (if any exist)
UPDATE transfers 
SET return_status = 'in_return_transit' 
WHERE return_status = 'return_initiated';

-- Modify the enum to remove 'return_initiated'
ALTER TABLE transfers 
MODIFY COLUMN return_status ENUM('not_returned','in_return_transit','returned') 
NOT NULL DEFAULT 'not_returned';

-- Verify the change
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'transfers' 
  AND COLUMN_NAME = 'return_status';

-- Check if there are transfers with in_transit assets but not_returned status (these need fixing)
SELECT t.id, t.status, t.return_status, a.status as asset_status, a.ref as asset_ref
FROM transfers t
JOIN assets a ON t.asset_id = a.id  
WHERE t.transfer_type = 'temporary' 
  AND t.status = 'Completed'
  AND a.status = 'in_transit' 
  AND t.return_status = 'not_returned';