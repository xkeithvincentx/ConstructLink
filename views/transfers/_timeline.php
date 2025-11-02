<?php
/**
 * Transfer Timeline Partial (Reusable)
 *
 * Displays transfer timeline with status progression
 * Used in: view.php, verify.php, complete.php, receive_return.php
 *
 * Required Parameters:
 * @param array $transfer Transfer record data
 */

if (!isset($transfer) || !is_array($transfer)) {
    return;
}
?>

<!-- Transfer Timeline -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-clock-history me-2" aria-hidden="true"></i>Transfer Timeline
        </h6>
    </div>
    <div class="card-body">
        <div class="timeline">
            <!-- Step 1: Transfer Requested -->
            <div class="timeline-item">
                <div class="timeline-marker bg-primary"></div>
                <div class="timeline-content">
                    <h6 class="timeline-title">Transfer Requested</h6>
                    <p class="timeline-text">
                        By: <?= htmlspecialchars($transfer['initiated_by_name'], ENT_QUOTES, 'UTF-8') ?><br>
                        <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['created_at'])) ?></small>
                    </p>
                </div>
            </div>

            <!-- Step 2: Verification/Approval -->
            <?php if ($transfer['status'] !== 'Pending Verification'): ?>
            <div class="timeline-item">
                <div class="timeline-marker bg-<?= $transfer['status'] === 'Canceled' ? 'danger' : 'success' ?>"></div>
                <div class="timeline-content">
                    <h6 class="timeline-title">
                        <?php if ($transfer['status'] === 'Canceled'): ?>
                            Transfer Canceled
                        <?php elseif ($transfer['status'] === 'Pending Approval'): ?>
                            Transfer Verified
                        <?php elseif (in_array($transfer['status'], ['Approved', 'In Transit', 'Received', 'Completed'])): ?>
                            Transfer Approved
                        <?php endif; ?>
                    </h6>
                    <p class="timeline-text">
                        <?php if (!empty($transfer['verified_by_name'])): ?>
                            By: <?= htmlspecialchars($transfer['verified_by_name'], ENT_QUOTES, 'UTF-8') ?><br>
                        <?php elseif (!empty($transfer['approved_by_name'])): ?>
                            By: <?= htmlspecialchars($transfer['approved_by_name'], ENT_QUOTES, 'UTF-8') ?><br>
                        <?php endif; ?>
                        <?php if (!empty($transfer['verification_date'])): ?>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['verification_date'])) ?></small>
                        <?php elseif (!empty($transfer['approval_date'])): ?>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['approval_date'])) ?></small>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 3: In Transit (if applicable) -->
            <?php if (in_array($transfer['status'], ['In Transit', 'Received', 'Completed'])): ?>
            <div class="timeline-item">
                <div class="timeline-marker bg-primary"></div>
                <div class="timeline-content">
                    <h6 class="timeline-title">Transfer Dispatched</h6>
                    <p class="timeline-text">
                        Asset in transit to destination project<br>
                        <?php if (!empty($transfer['dispatch_date'])): ?>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['dispatch_date'])) ?></small>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 4: Received -->
            <?php if (in_array($transfer['status'], ['Received', 'Completed'])): ?>
            <div class="timeline-item">
                <div class="timeline-marker bg-info"></div>
                <div class="timeline-content">
                    <h6 class="timeline-title">Transfer Received</h6>
                    <p class="timeline-text">
                        <?php if (!empty($transfer['received_by_name'])): ?>
                            By: <?= htmlspecialchars($transfer['received_by_name'], ENT_QUOTES, 'UTF-8') ?><br>
                        <?php endif; ?>
                        <?php if (!empty($transfer['receipt_date'])): ?>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['receipt_date'])) ?></small>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 5: Completed -->
            <?php if ($transfer['status'] === 'Completed'): ?>
            <div class="timeline-item">
                <div class="timeline-marker bg-success"></div>
                <div class="timeline-content">
                    <h6 class="timeline-title">Transfer Completed</h6>
                    <p class="timeline-text">
                        Asset moved to destination project<br>
                        <?php if (!empty($transfer['completed_by_name'])): ?>
                            By: <?= htmlspecialchars($transfer['completed_by_name'], ENT_QUOTES, 'UTF-8') ?><br>
                        <?php endif; ?>
                        <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['updated_at'])) ?></small>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 6: Asset Returned (if applicable) -->
            <?php if (!empty($transfer['actual_return'])): ?>
            <div class="timeline-item">
                <div class="timeline-marker bg-secondary"></div>
                <div class="timeline-content">
                    <h6 class="timeline-title">Asset Returned</h6>
                    <p class="timeline-text">
                        Asset returned to original project<br>
                        <small class="text-muted"><?= date('M j, Y g:i A', strtotime($transfer['actual_return'])) ?></small>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
