<?php
/**
 * Withdrawal Batch Workflow Timeline Partial
 * Visual timeline showing MVA workflow progress
 *
 * Required Variables:
 * - $batch: Batch data with workflow timestamps and user information
 * - $showHorizontal: Boolean to use horizontal timeline (default: true)
 */

require_once APP_ROOT . '/helpers/WithdrawalViewHelper.php';

$showHorizontal = $showHorizontal ?? true;
$steps = WithdrawalViewHelper::getWorkflowSteps($batch);
?>

<!-- Workflow Timeline Card -->
<div class="workflow-timeline-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">
            <i class="bi bi-clock-history me-2"></i>Workflow Timeline
        </h6>
        <span class="badge <?= WithdrawalViewHelper::getBatchStatusBadgeClass($batch['status']) ?>">
            <?= htmlspecialchars($batch['status']) ?>
        </span>
    </div>

    <div class="timeline-container">
        <?php if ($showHorizontal): ?>
            <!-- Horizontal Timeline (Desktop) -->
            <div class="timeline-horizontal">
                <?php foreach ($steps as $step): ?>
                    <div class="timeline-step <?= $step['status'] ?>">
                        <div class="timeline-step-icon">
                            <i class="<?= $step['icon'] ?>"></i>
                        </div>
                        <div class="timeline-step-label">
                            <?= htmlspecialchars($step['label']) ?>
                        </div>
                        <div class="timeline-step-details">
                            <?php if ($step['date']): ?>
                                <div class="timeline-step-date">
                                    <?= WithdrawalViewHelper::formatDate($step['date'], 'M d, Y') ?>
                                </div>
                                <div class="timeline-step-user">
                                    <?= htmlspecialchars($step['user']) ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted">
                                    <small>Pending</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Vertical Timeline (Mobile/Alternative) -->
            <div class="timeline-vertical">
                <?php foreach ($steps as $step): ?>
                    <div class="timeline-item mb-3 <?= $step['status'] ?>">
                        <div class="d-flex align-items-start">
                            <div class="timeline-marker me-3">
                                <div class="timeline-step-icon-small">
                                    <i class="<?= $step['icon'] ?>"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= htmlspecialchars($step['label']) ?></strong>
                                        <?php if ($step['date']): ?>
                                            <br><small class="text-muted">
                                                <?= WithdrawalViewHelper::formatDateTime($step['date']) ?>
                                            </small>
                                            <br><small class="text-muted">
                                                by <?= htmlspecialchars($step['user']) ?>
                                            </small>
                                        <?php else: ?>
                                            <br><small class="text-muted">Pending</small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge <?= $step['status'] === 'completed' ? 'bg-success' : ($step['status'] === 'current' ? 'bg-primary' : 'bg-secondary') ?>">
                                        <?= ucfirst($step['status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Workflow Notes (if any) -->
    <?php if (!empty($batch['verification_notes']) || !empty($batch['approval_notes']) || !empty($batch['cancellation_reason'])): ?>
        <div class="mt-4 pt-3 border-top">
            <h6 class="text-muted mb-2">
                <i class="bi bi-chat-left-text me-1"></i>Workflow Notes
            </h6>
            <?php if (!empty($batch['verification_notes'])): ?>
                <div class="alert alert-light py-2 mb-2">
                    <small>
                        <strong>Verification:</strong>
                        <?= htmlspecialchars($batch['verification_notes']) ?>
                    </small>
                </div>
            <?php endif; ?>
            <?php if (!empty($batch['approval_notes'])): ?>
                <div class="alert alert-light py-2 mb-2">
                    <small>
                        <strong>Approval:</strong>
                        <?= htmlspecialchars($batch['approval_notes']) ?>
                    </small>
                </div>
            <?php endif; ?>
            <?php if (!empty($batch['cancellation_reason'])): ?>
                <div class="alert alert-danger py-2 mb-2">
                    <small>
                        <strong>Cancellation Reason:</strong>
                        <?= htmlspecialchars($batch['cancellation_reason']) ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
