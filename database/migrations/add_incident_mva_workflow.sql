-- Migration: Add MVA Workflow Support to Incidents Table
-- Date: 2024-01-XX
-- Description: Update incidents table to support Maker-Verifier-Authorizer workflow

-- Backup existing data
CREATE TABLE incidents_backup AS SELECT * FROM incidents;

-- Update status enum to support MVA workflow
ALTER TABLE incidents 
MODIFY COLUMN status ENUM(
    'Pending Verification',    -- Maker step completed, waiting for Verifier
    'Pending Authorization',   -- Verifier step completed, waiting for Authorizer  
    'Authorized',             -- Authorizer step completed, ready for resolution
    'Resolved',               -- Incident has been resolved
    'Closed',                 -- Incident is closed (final step)
    'Canceled'                -- Incident was canceled at any stage
) NOT NULL DEFAULT 'Pending Verification';

-- Add MVA workflow tracking fields
ALTER TABLE incidents 
ADD COLUMN verified_by INT(11) DEFAULT NULL AFTER resolved_by,
ADD COLUMN verification_date TIMESTAMP NULL DEFAULT NULL AFTER verified_by,
ADD COLUMN authorized_by INT(11) DEFAULT NULL AFTER verification_date,
ADD COLUMN authorization_date TIMESTAMP NULL DEFAULT NULL AFTER authorized_by,
ADD COLUMN closed_by INT(11) DEFAULT NULL AFTER authorization_date,
ADD COLUMN closure_date TIMESTAMP NULL DEFAULT NULL AFTER closed_by,
ADD COLUMN closure_notes TEXT AFTER closure_date,
ADD COLUMN canceled_by INT(11) DEFAULT NULL AFTER closure_notes,
ADD COLUMN cancellation_date TIMESTAMP NULL DEFAULT NULL AFTER canceled_by,
ADD COLUMN cancellation_reason TEXT AFTER cancellation_date;

-- Add foreign key constraints for workflow fields
ALTER TABLE incidents 
ADD CONSTRAINT incidents_ibfk_4 FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL,
ADD CONSTRAINT incidents_ibfk_5 FOREIGN KEY (authorized_by) REFERENCES users (id) ON DELETE SET NULL,
ADD CONSTRAINT incidents_ibfk_6 FOREIGN KEY (closed_by) REFERENCES users (id) ON DELETE SET NULL,
ADD CONSTRAINT incidents_ibfk_7 FOREIGN KEY (canceled_by) REFERENCES users (id) ON DELETE SET NULL;

-- Add indexes for workflow fields
CREATE INDEX idx_incidents_verified_by ON incidents (verified_by);
CREATE INDEX idx_incidents_authorized_by ON incidents (authorized_by);
CREATE INDEX idx_incidents_closed_by ON incidents (closed_by);
CREATE INDEX idx_incidents_canceled_by ON incidents (canceled_by);
CREATE INDEX idx_incidents_status_workflow ON incidents (status, verified_by, authorized_by);

-- Update existing records to use new statuses
-- Convert legacy statuses to new MVA statuses
UPDATE incidents SET status = 'Pending Verification' WHERE status = 'under_investigation';
UPDATE incidents SET status = 'Pending Authorization' WHERE status = 'verified';
UPDATE incidents SET status = 'Resolved' WHERE status = 'resolved';
UPDATE incidents SET status = 'Closed' WHERE status = 'closed';

-- Add comments for documentation
ALTER TABLE incidents 
MODIFY COLUMN status ENUM(
    'Pending Verification',    -- Maker step completed, waiting for Verifier
    'Pending Authorization',   -- Verifier step completed, waiting for Authorizer  
    'Authorized',             -- Authorizer step completed, ready for resolution
    'Resolved',               -- Incident has been resolved
    'Closed',                 -- Incident is closed (final step)
    'Canceled'                -- Incident was canceled at any stage
) NOT NULL DEFAULT 'Pending Verification' COMMENT 'MVA Workflow Status';

-- Verify migration
SELECT 'Migration completed successfully' as status;
SELECT COUNT(*) as total_incidents FROM incidents;
SELECT status, COUNT(*) as count FROM incidents GROUP BY status; 