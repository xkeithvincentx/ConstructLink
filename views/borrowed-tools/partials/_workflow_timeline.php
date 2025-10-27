<?php
/**
 * Workflow Timeline Partial
 * Collapsible MVA workflow history for borrowed tool requests
 *
 * Required Variables:
 * - $batch: Batch data with workflow timestamps and user information
 * - $collapseId: Unique ID for the collapse element (default: 'workflowTimeline')
 * - $showExpanded: Boolean to show expanded by default (default: false)
 */

$collapseId = $collapseId ?? 'workflowTimeline';
$showExpanded = $showExpanded ?? false;
?>

<!-- Workflow Timeline Card (Collapsible) -->
<div class="card shadow-sm">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-clock-history me-2"></i>Workflow Timeline
            </h6>
            <button class="btn btn-sm btn-outline-secondary"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#<?= $collapseId ?>"
                    aria-expanded="<?= $showExpanded ? 'true' : 'false' ?>"
                    aria-controls="<?= $collapseId ?>">
                <i class="bi bi-chevron-down"></i>
                <span class="ms-1">Show Details</span>
            </button>
        </div>
    </div>
    <div class="collapse <?= $showExpanded ? 'show' : '' ?>" id="<?= $collapseId ?>">
        <div class="card-body">
            <div class="timeline">
                <!-- Created/Issued -->
                <div class="timeline-item mb-3">
                    <div class="d-flex align-items-start">
                        <div class="timeline-marker me-3">
                            <i class="bi bi-circle-fill text-success"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>Created</strong>
                                    <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($batch['created_at'])) ?></small>
                                    <br><small class="text-muted">by <?= htmlspecialchars($batch['issued_by_name']) ?></small>
                                </div>
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Verified -->
                <?php if ($batch['verified_by']): ?>
                    <div class="timeline-item mb-3">
                        <div class="d-flex align-items-start">
                            <div class="timeline-marker me-3">
                                <i class="bi bi-circle-fill text-success"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>Verified</strong>
                                        <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($batch['verification_date'])) ?></small>
                                        <br><small class="text-muted">by <?= htmlspecialchars($batch['verified_by_name']) ?></small>
                                        <?php if ($batch['verification_notes']): ?>
                                            <br><small class="text-muted fst-italic">"<?= htmlspecialchars($batch['verification_notes']) ?>"</small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-check-square"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($batch['status'] === 'Pending Verification'): ?>
                    <div class="timeline-item mb-3">
                        <div class="d-flex align-items-start">
                            <div class="timeline-marker me-3">
                                <i class="bi bi-circle text-warning"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>Awaiting Verification</strong>
                                        <br><small class="text-muted">Project Manager review required</small>
                                    </div>
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-hourglass-split"></i> Pending
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Approved -->
                <?php if ($batch['approved_by']): ?>
                    <div class="timeline-item mb-3">
                        <div class="d-flex align-items-start">
                            <div class="timeline-marker me-3">
                                <i class="bi bi-circle-fill text-success"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>Approved</strong>
                                        <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($batch['approval_date'])) ?></small>
                                        <br><small class="text-muted">by <?= htmlspecialchars($batch['approved_by_name']) ?></small>
                                        <?php if ($batch['approval_notes']): ?>
                                            <br><small class="text-muted fst-italic">"<?= htmlspecialchars($batch['approval_notes']) ?>"</small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-info">
                                        <i class="bi bi-shield-check"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($batch['status'] === 'Pending Approval'): ?>
                    <div class="timeline-item mb-3">
                        <div class="d-flex align-items-start">
                            <div class="timeline-marker me-3">
                                <i class="bi bi-circle text-info"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>Awaiting Approval</strong>
                                        <br><small class="text-muted">Director authorization required</small>
                                    </div>
                                    <span class="badge bg-info">
                                        <i class="bi bi-hourglass-split"></i> Pending
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Released -->
                <?php if ($batch['released_by']): ?>
                    <div class="timeline-item mb-3">
                        <div class="d-flex align-items-start">
                            <div class="timeline-marker me-3">
                                <i class="bi bi-circle-fill text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>Released to Borrower</strong>
                                        <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($batch['release_date'])) ?></small>
                                        <br><small class="text-muted">by <?= htmlspecialchars($batch['released_by_name']) ?></small>
                                        <?php if ($batch['release_notes']): ?>
                                            <br><small class="text-muted fst-italic">"<?= htmlspecialchars($batch['release_notes']) ?>"</small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-primary">
                                        <i class="bi bi-box-arrow-up"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($batch['status'] === 'Approved'): ?>
                    <div class="timeline-item mb-3">
                        <div class="d-flex align-items-start">
                            <div class="timeline-marker me-3">
                                <i class="bi bi-circle text-success"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>Ready for Release</strong>
                                        <br><small class="text-muted">Warehouseman can release to borrower</small>
                                    </div>
                                    <span class="badge bg-success">
                                        <i class="bi bi-hourglass-split"></i> Ready
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Returned -->
                <?php if ($batch['returned_by']): ?>
                    <div class="timeline-item mb-3">
                        <div class="d-flex align-items-start">
                            <div class="timeline-marker me-3">
                                <i class="bi bi-circle-fill text-secondary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>Returned</strong>
                                        <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($batch['return_date'])) ?></small>
                                        <br><small class="text-muted">by <?= htmlspecialchars($batch['returned_by_name']) ?></small>
                                        <?php if ($batch['return_notes']): ?>
                                            <br><small class="text-muted fst-italic">"<?= htmlspecialchars($batch['return_notes']) ?>"</small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-box-arrow-down"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Canceled -->
                <?php if ($batch['canceled_by']): ?>
                    <div class="timeline-item mb-3">
                        <div class="d-flex align-items-start">
                            <div class="timeline-marker me-3">
                                <i class="bi bi-x-circle-fill text-danger"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>Canceled</strong>
                                        <br><small class="text-muted"><?= date('M d, Y H:i', strtotime($batch['cancellation_date'])) ?></small>
                                        <br><small class="text-muted">by <?= htmlspecialchars($batch['canceled_by_name']) ?></small>
                                        <?php if ($batch['cancellation_reason']): ?>
                                            <br><small class="text-danger fst-italic">Reason: <?= htmlspecialchars($batch['cancellation_reason']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
