-- =====================================================================================
-- Performance Optimization Migration for Borrowed Tools Module
-- =====================================================================================
-- Purpose: Eliminate N+1 queries and improve query performance with composite indexes
-- Date: 2025-10-20
-- Author: Performance Optimization Agent
--
-- PERFORMANCE IMPROVEMENTS:
-- 1. N+1 Query Elimination: getAvailableAssetsForBorrowing() optimized from 300+ queries to 1 query
-- 2. Composite indexes for faster JOIN operations
-- 3. Index optimization for frequently used filters
-- =====================================================================================

-- Composite index for borrowed_tools: asset_id + status
-- This optimizes the LEFT JOIN in getAvailableAssetsForBorrowing()
-- Improves query: WHERE bt.asset_id = a.id AND bt.status IN (...)
CREATE INDEX IF NOT EXISTS idx_borrowed_tools_asset_status
ON borrowed_tools(asset_id, status);

-- Composite index for withdrawals: asset_id + status
-- Optimizes withdrawal checks in available assets query
CREATE INDEX IF NOT EXISTS idx_withdrawals_asset_status
ON withdrawals(asset_id, status);

-- Composite index for transfers: asset_id + status
-- Optimizes transfer checks in available assets query
CREATE INDEX IF NOT EXISTS idx_transfers_asset_status
ON transfers(asset_id, status);

-- Composite index for assets: status + category_id + project_id
-- Optimizes filtering in getAvailableAssetsForBorrowing()
-- This index is already covered by idx_assets_status_workflow_category

-- =====================================================================================
-- VERIFICATION QUERIES
-- =====================================================================================

-- Test 1: Verify indexes were created
SHOW INDEX FROM borrowed_tools WHERE Key_name = 'idx_borrowed_tools_asset_status';
SHOW INDEX FROM withdrawals WHERE Key_name = 'idx_withdrawals_asset_status';
SHOW INDEX FROM transfers WHERE Key_name = 'idx_transfers_asset_status';

-- Test 2: Explain the optimized available assets query
EXPLAIN SELECT
    a.id,
    a.ref,
    a.name,
    a.status,
    c.name as category_name,
    c.is_consumable,
    p.name as project_name,
    a.acquisition_cost,
    COUNT(DISTINCT bt.id) as active_borrowings,
    COUNT(DISTINCT w.id) as active_withdrawals,
    COUNT(DISTINCT t.id) as active_transfers
FROM assets a
LEFT JOIN categories c ON a.category_id = c.id
LEFT JOIN projects p ON a.project_id = p.id
LEFT JOIN borrowed_tools bt ON a.id = bt.asset_id
    AND bt.status IN ('Pending Verification', 'Pending Approval', 'Approved', 'Borrowed', 'Overdue')
LEFT JOIN withdrawals w ON a.id = w.asset_id
    AND w.status IN ('pending', 'released')
LEFT JOIN transfers t ON a.id = t.asset_id
    AND t.status IN ('pending', 'approved')
WHERE a.status = 'available'
  AND p.is_active = 1
  AND c.is_consumable = 0
GROUP BY a.id
HAVING active_borrowings = 0
   AND active_withdrawals = 0
   AND active_transfers = 0;

-- =====================================================================================
-- PERFORMANCE ANALYSIS
-- =====================================================================================

-- Before optimization: 300+ queries for 100 assets
-- Each asset triggered:
--   1. SELECT COUNT(*) FROM borrowed_tools WHERE asset_id = ?
--   2. SELECT COUNT(*) FROM withdrawals WHERE asset_id = ?
--   3. SELECT COUNT(*) FROM transfers WHERE asset_id = ?
-- Total: 1 + (N * 3) queries where N = number of assets

-- After optimization: 1 query for any number of assets
-- Single query uses LEFT JOIN with GROUP BY and HAVING clause
-- Performance gain: ~99.7% reduction in database calls

-- Expected execution time:
-- - Without indexes: 200-500ms for 100 assets
-- - With composite indexes: 20-50ms for 100 assets
-- - Performance improvement: 4-10x faster

-- =====================================================================================
-- ROLLBACK SCRIPT (if needed)
-- =====================================================================================

-- DROP INDEX IF EXISTS idx_borrowed_tools_asset_status ON borrowed_tools;
-- DROP INDEX IF EXISTS idx_withdrawals_asset_status ON withdrawals;
-- DROP INDEX IF EXISTS idx_transfers_asset_status ON transfers;
