<?php
/**
 * Action Buttons Partial - Borrowed Tools Detail View
 * Reusable action buttons for batch detail view
 * Uses CSS classes for responsive layout to eliminate HTML duplication
 *
 * Required variables:
 * - $batch: Batch data array with id and status
 *
 * Uses BorrowedToolsViewHelper for status checks
 *
 * @package ConstructLink
 * @subpackage Views
 */

// Load helper for status constants
require_once APP_ROOT . '/helpers/BorrowedToolsViewHelper.php';

// Extract status for readability
$status = $batch['status'];
$batchId = $batch['id'];
?>

<div class="action-buttons-container">
    <!-- Back to List -->
    <a href="?route=borrowed-tools"
       class="btn btn-outline-secondary"
       aria-label="Return to borrowed equipment list">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to List
    </a>

    <!-- Verify Request (Pending Verification) -->
    <?php if ($status === BorrowedToolsViewHelper::STATUS_PENDING_VERIFICATION && hasRole(['Project Manager', 'System Admin'])): ?>
        <a href="?route=borrowed-tools/batch/verify&id=<?= $batchId ?>"
           class="btn btn-warning"
           aria-label="Verify this borrowing request">
            <i class="bi bi-check-square me-1" aria-hidden="true"></i>Verify Request
        </a>
    <?php endif; ?>

    <!-- Approve Request (Pending Approval) -->
    <?php if ($status === BorrowedToolsViewHelper::STATUS_PENDING_APPROVAL && hasRole(['Asset Director', 'Finance Director', 'System Admin'])): ?>
        <a href="?route=borrowed-tools/batch/approve&id=<?= $batchId ?>"
           class="btn btn-info"
           aria-label="Approve this borrowing request">
            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Approve Request
        </a>
    <?php endif; ?>

    <!-- Release to Borrower (Approved) -->
    <?php if ($status === BorrowedToolsViewHelper::STATUS_APPROVED && hasRole(['Warehouseman', 'System Admin'])): ?>
        <a href="?route=borrowed-tools/batch/release&id=<?= $batchId ?>"
           class="btn btn-success"
           aria-label="Release equipment to borrower">
            <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>Release to Borrower
        </a>
    <?php endif; ?>

    <!-- Cancel Request (Cancelable statuses) -->
    <?php if (BorrowedToolsViewHelper::isCancelable($status)): ?>
        <a href="?route=borrowed-tools/batch/cancel&id=<?= $batchId ?>"
           class="btn btn-danger"
           aria-label="Cancel this borrowing request">
            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancel Request
        </a>
    <?php endif; ?>

    <!-- Print Form -->
    <a href="?route=borrowed-tools/batch/print&id=<?= $batchId ?>"
       class="btn btn-outline-primary"
       target="_blank"
       aria-label="Print borrowing form, opens in new window">
        <i class="bi bi-printer me-1" aria-hidden="true"></i>Print Form
        <span class="visually-hidden">(opens in new window)</span>
    </a>
</div>
