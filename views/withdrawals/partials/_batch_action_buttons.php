<?php
/**
 * Withdrawal Batch Action Buttons Partial
 * Permission-based action buttons for batch detail view
 *
 * Required Variables:
 * - $batch: Batch data array
 * - $auth: Auth instance
 */

require_once APP_ROOT . '/helpers/WithdrawalViewHelper.php';

// Check permissions
$canVerify = WithdrawalViewHelper::canVerify($batch, $auth);
$canApprove = WithdrawalViewHelper::canApprove($batch, $auth);
$canRelease = WithdrawalViewHelper::canRelease($batch, $auth);
$canCancel = WithdrawalViewHelper::canCancel($batch, $auth);
$hasAnyAction = WithdrawalViewHelper::hasAnyAction($batch, $auth);

// Don't show card if no actions available
if (!$hasAnyAction) {
    return;
}
?>

<!-- Batch Actions Card -->
<div class="batch-actions-card">
    <h6 class="mb-3">
        <i class="bi bi-lightning-fill me-2"></i>Available Actions
    </h6>

    <div class="action-button-group">
        <!-- Verify Button -->
        <?php if ($canVerify): ?>
            <button type="button"
                    class="btn btn-warning"
                    data-bs-toggle="modal"
                    data-bs-target="#verifyBatchModal"
                    data-batch-id="<?= $batch['id'] ?>"
                    data-batch-ref="<?= WithdrawalViewHelper::e($batch['reference']) ?>">
                <i class="bi bi-check-square"></i>
                <span>Verify Batch</span>
            </button>
        <?php endif; ?>

        <!-- Approve Button -->
        <?php if ($canApprove): ?>
            <button type="button"
                    class="btn btn-success"
                    data-bs-toggle="modal"
                    data-bs-target="#approveBatchModal"
                    data-batch-id="<?= $batch['id'] ?>"
                    data-batch-ref="<?= WithdrawalViewHelper::e($batch['reference']) ?>">
                <i class="bi bi-shield-check"></i>
                <span>Approve Batch</span>
            </button>
        <?php endif; ?>

        <!-- Release Button -->
        <?php if ($canRelease): ?>
            <button type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#releaseBatchModal"
                    data-batch-id="<?= $batch['id'] ?>"
                    data-batch-ref="<?= WithdrawalViewHelper::e($batch['reference']) ?>">
                <i class="bi bi-box-arrow-right"></i>
                <span>Release to Receiver</span>
            </button>
        <?php endif; ?>

        <!-- Cancel Button -->
        <?php if ($canCancel): ?>
            <button type="button"
                    class="btn btn-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#cancelBatchModal"
                    data-batch-id="<?= $batch['id'] ?>"
                    data-batch-ref="<?= WithdrawalViewHelper::e($batch['reference']) ?>">
                <i class="bi bi-x-circle"></i>
                <span>Cancel Batch</span>
            </button>
        <?php endif; ?>

        <!-- Always show View/Print options -->
        <a href="index.php?route=withdrawals/batch/view&id=<?= $batch['id'] ?>&print=1"
           class="btn btn-outline-secondary"
           target="_blank">
            <i class="bi bi-printer"></i>
            <span>Print</span>
        </a>

        <a href="index.php?route=withdrawals"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
            <span>Back to List</span>
        </a>
    </div>

    <!-- Action Info -->
    <div class="mt-3 pt-3 border-top">
        <small class="text-muted">
            <?php if ($canVerify): ?>
                <i class="bi bi-info-circle me-1"></i>
                As Project Manager, you can verify this withdrawal request.
            <?php elseif ($canApprove): ?>
                <i class="bi bi-info-circle me-1"></i>
                As Director, you can approve this verified withdrawal request.
            <?php elseif ($canRelease): ?>
                <i class="bi bi-info-circle me-1"></i>
                As Warehouseman, you can release these items to the receiver.
            <?php elseif ($canCancel): ?>
                <i class="bi bi-info-circle me-1"></i>
                You can cancel this withdrawal request if needed.
            <?php endif; ?>
        </small>
    </div>
</div>

<!-- Verify Modal -->
<?php if ($canVerify): ?>
<div class="modal fade" id="verifyBatchModal" tabindex="-1" aria-labelledby="verifyBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verifyBatchModalLabel">
                    <i class="bi bi-check-square me-2"></i>Verify Withdrawal Batch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=withdrawals/batch/verify" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">

                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        You are verifying withdrawal batch <strong><?= WithdrawalViewHelper::e($batch['reference']) ?></strong>
                    </div>

                    <div class="mb-3">
                        <label for="verification_notes" class="form-label">Verification Notes (Optional)</label>
                        <textarea class="form-control"
                                  id="verification_notes"
                                  name="verification_notes"
                                  rows="3"
                                  placeholder="Add any notes about this verification..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-square me-1"></i>Verify Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Approve Modal -->
<?php if ($canApprove): ?>
<div class="modal fade" id="approveBatchModal" tabindex="-1" aria-labelledby="approveBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveBatchModalLabel">
                    <i class="bi bi-shield-check me-2"></i>Approve Withdrawal Batch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=withdrawals/batch/approve" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">

                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="bi bi-info-circle me-2"></i>
                        You are approving withdrawal batch <strong><?= WithdrawalViewHelper::e($batch['reference']) ?></strong>
                    </div>

                    <div class="mb-3">
                        <label for="approval_notes" class="form-label">Approval Notes (Optional)</label>
                        <textarea class="form-control"
                                  id="approval_notes"
                                  name="approval_notes"
                                  rows="3"
                                  placeholder="Add any notes about this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-shield-check me-1"></i>Approve Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Release Modal -->
<?php if ($canRelease): ?>
<div class="modal fade" id="releaseBatchModal" tabindex="-1" aria-labelledby="releaseBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="releaseBatchModalLabel">
                    <i class="bi bi-box-arrow-right me-2"></i>Release Withdrawal Batch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=withdrawals/batch/release" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">

                <div class="modal-body">
                    <div class="alert alert-primary">
                        <i class="bi bi-info-circle me-2"></i>
                        You are releasing withdrawal batch <strong><?= WithdrawalViewHelper::e($batch['reference']) ?></strong>
                        to <strong><?= WithdrawalViewHelper::e($batch['receiver_name']) ?></strong>
                    </div>

                    <div class="mb-3">
                        <label for="release_notes" class="form-label">Release Notes (Optional)</label>
                        <textarea class="form-control"
                                  id="release_notes"
                                  name="release_notes"
                                  rows="3"
                                  placeholder="Add any notes about this release..."></textarea>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> This will deduct the quantities from inventory.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-right me-1"></i>Release Items
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Cancel Modal -->
<?php if ($canCancel): ?>
<div class="modal fade" id="cancelBatchModal" tabindex="-1" aria-labelledby="cancelBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelBatchModalLabel">
                    <i class="bi bi-x-circle me-2"></i>Cancel Withdrawal Batch
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?route=withdrawals/batch/cancel" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">

                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        You are about to cancel withdrawal batch <strong><?= WithdrawalViewHelper::e($batch['reference']) ?></strong>
                    </div>

                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control"
                                  id="cancellation_reason"
                                  name="cancellation_reason"
                                  rows="3"
                                  required
                                  placeholder="Please explain why this withdrawal is being canceled..."></textarea>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        This action cannot be undone. The items will remain in inventory.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Batch</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>Cancel Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
