-- ConstructLink Database Migration
-- Add Performance Indexes for Borrowed Tools Module
-- Author: Ranoa Digital Solutions
-- Date: 2025-10-19
--
-- Purpose: Eliminate N+1 queries and improve query performance for borrowed tools
-- Impact: Significant performance improvement for:
--   - Batch status queries
--   - Asset availability checks
--   - Overdue tools detection
--   - Workflow status filtering

-- ============================================================================
-- INDEX 1: borrowed_tools (batch_id, status)
-- Purpose: Optimize batch status aggregation queries
-- Query improved: BorrowedToolBatchModel::returnBatch() line 668-677
-- ============================================================================
CREATE INDEX IF NOT EXISTS idx_borrowed_tools_batch_status
ON borrowed_tools(batch_id, status);

-- ============================================================================
-- INDEX 2: assets (project_id, status)
-- Purpose: Optimize project-based asset queries and availability checks
-- Query improved: BorrowedToolController::index() line 204-216
-- ============================================================================
CREATE INDEX IF NOT EXISTS idx_assets_project_status
ON assets(project_id, status);

-- ============================================================================
-- INDEX 3: borrowed_tools (expected_return, status)
-- Purpose: Optimize overdue detection and due date queries
-- Query improved: BorrowedToolBatchModel::getOverdueBatchCount() line 905-933
-- ============================================================================
CREATE INDEX IF NOT EXISTS idx_borrowed_tools_expected_return_status
ON borrowed_tools(expected_return, status);

-- ============================================================================
-- INDEX 4: assets (status, workflow_status, category_id)
-- Purpose: Optimize asset availability filtering with comprehensive criteria
-- Query improved: BorrowedToolController::getAvailableAssetsForBorrowing() line 1111-1135
-- ============================================================================
CREATE INDEX IF NOT EXISTS idx_assets_status_workflow_category
ON assets(status, workflow_status, category_id);

-- ============================================================================
-- ROLLBACK MIGRATION (if needed)
-- ============================================================================
-- DROP INDEX IF EXISTS idx_borrowed_tools_batch_status ON borrowed_tools;
-- DROP INDEX IF EXISTS idx_assets_project_status ON assets;
-- DROP INDEX IF EXISTS idx_borrowed_tools_expected_return_status ON borrowed_tools;
-- DROP INDEX IF EXISTS idx_assets_status_workflow_category ON assets;
