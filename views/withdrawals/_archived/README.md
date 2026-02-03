# Archived Single-Item Withdrawal Views

**Date Archived**: 2025-11-06
**Reason**: Consolidation to batch-primary system

These views were deprecated in favor of the unified batch withdrawal system.
The batch system handles both single-item and multi-item withdrawals.

## Archived Files

- `create.php` - Single-item withdrawal form (replaced by create-batch.php)
- `verify.php` - Single-item verify page (replaced by batch verify workflow)
- `approve.php` - Single-item approve page (replaced by batch approve workflow)
- `release.php` - Single-item release page (replaced by batch release workflow)

## Migration

All withdrawal operations now use the batch system:
- Single item: Add 1 item to batch cart
- Multiple items: Add multiple items to batch cart
- Workflow: Unified MVA (Maker-Verifier-Authorizer) process

## Pattern Reference

This follows the borrowed-tools pattern where:
- `/borrowed-tools/create` redirects to `/borrowed-tools/create-batch`
- Batch system is primary interface
- Single operations handled as batch-of-1

## Rollback

If rollback is needed:
1. Move files back from _archived/ to parent directory
2. Remove redirect in WithdrawalController::create()
3. Revert navigation links
