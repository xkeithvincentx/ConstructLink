<?php
/**
 * Transfer Statistics Cards Partial
 *
 * Displays statistics cards showing transfer counts and metrics
 *
 * Required Parameters:
 * @param array $transferStats Statistics data array with keys:
 *   - pending_verification (int)
 *   - my_pending_verifications (int)
 *   - pending_approval (int)
 *   - my_pending_approvals (int)
 *   - approved (int)
 *   - in_transit (int)
 *   - completed (int)
 *   - completion_rate (int)
 *   - temporary_transfers (int)
 *   - pending_returns (int)
 *   - permanent_transfers (int)
 *   - permanent_transfer_value (float)
 *   - canceled (int)
 * @param string $userRole Current user's role name
 * @param array $roleConfig Role configuration array
 */

if (!isset($transferStats) || !is_array($transferStats)) {
    return;
}

$userRole = $userRole ?? 'Guest';
$roleConfig = $roleConfig ?? [];
?>

<!-- Statistics Cards -->
<!-- Mobile: Collapsible, Desktop: Always visible -->
<div class="mb-4">
    <!-- Mobile Toggle Button -->
    <button class="btn btn-outline-secondary btn-sm w-100 d-md-none mb-3"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#statsCollapse"
            aria-expanded="false"
            aria-controls="statsCollapse"
            aria-label="Toggle statistics visibility">
        <i class="bi bi-bar-chart-line me-1" aria-hidden="true"></i>
        <span>View Statistics</span>
        <i class="bi bi-chevron-down ms-auto" aria-hidden="true"></i>
    </button>

    <!-- Collapsible on mobile, always visible on desktop -->
    <div class="collapse d-md-block" id="statsCollapse">
        <div class="row g-3">
            <!-- Pending Verification -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 transfer-stat-card" style="border-left: 4px solid var(--warning-color);">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-clock-history text-warning fs-5" aria-hidden="true"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small">Pending Verification</h6>
                                <h3 class="mb-0"><?= $transferStats['pending_verification'] ?? 0 ?></h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="bi bi-person me-1" aria-hidden="true"></i><?= $transferStats['my_pending_verifications'] ?? 0 ?> for your review
                        </p>
                        <?php if (in_array($userRole, $roleConfig['transfers/verify'] ?? []) && ($transferStats['my_pending_verifications'] ?? 0) > 0): ?>
                            <a href="?route=transfers&status=<?= urlencode('Pending Verification') ?>"
                               class="btn btn-sm btn-outline-warning w-100 mt-2"
                               aria-label="View and verify pending transfers">
                                <i class="bi bi-search me-1" aria-hidden="true"></i>Verify Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Pending Approval -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 transfer-stat-card" style="border-left: 4px solid var(--info-color);">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-person-check text-info fs-5" aria-hidden="true"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small">Pending Approval</h6>
                                <h3 class="mb-0"><?= $transferStats['pending_approval'] ?? 0 ?></h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="bi bi-person-check me-1" aria-hidden="true"></i><?= $transferStats['my_pending_approvals'] ?? 0 ?> for your approval
                        </p>
                        <?php if (in_array($userRole, $roleConfig['transfers/approve'] ?? []) && ($transferStats['my_pending_approvals'] ?? 0) > 0): ?>
                            <a href="?route=transfers&status=<?= urlencode('Pending Approval') ?>"
                               class="btn btn-sm btn-outline-info w-100 mt-2"
                               aria-label="View and approve pending transfers">
                                <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Approve Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Approved (Ready to Transfer) -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 transfer-stat-card" style="border-left: 4px solid var(--success-color);">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-check-circle text-success fs-5" aria-hidden="true"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small">Approved</h6>
                                <h3 class="mb-0"><?= $transferStats['approved'] ?? 0 ?></h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Ready for transfer
                        </p>
                    </div>
                </div>
            </div>

            <!-- In Transit -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 transfer-stat-card" style="border-left: 4px solid var(--primary-color);">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-truck text-primary fs-5" aria-hidden="true"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small">In Transit</h6>
                                <h3 class="mb-0"><?= $transferStats['in_transit'] ?? 0 ?></h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="bi bi-send me-1" aria-hidden="true"></i>Assets being transferred
                        </p>
                    </div>
                </div>
            </div>

            <!-- Completed -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 transfer-stat-card" style="border-left: 4px solid var(--neutral-color);">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-check-circle text-secondary fs-5" aria-hidden="true"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small">Completed</h6>
                                <h3 class="mb-0"><?= $transferStats['completed'] ?? 0 ?></h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="bi bi-percent me-1" aria-hidden="true"></i><?= $transferStats['completion_rate'] ?? 0 ?>% success rate
                        </p>
                    </div>
                </div>
            </div>

            <!-- Temporary Transfers -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 transfer-stat-card" style="border-left: 4px solid var(--neutral-color);">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-arrow-repeat text-secondary fs-5" aria-hidden="true"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small">Temporary Transfers</h6>
                                <h3 class="mb-0"><?= $transferStats['temporary_transfers'] ?? 0 ?></h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="bi bi-clock me-1" aria-hidden="true"></i><?= $transferStats['pending_returns'] ?? 0 ?> pending returns
                        </p>
                    </div>
                </div>
            </div>

            <!-- Permanent Transfers -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 transfer-stat-card" style="border-left: 4px solid var(--neutral-color);">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-arrow-right text-secondary fs-5" aria-hidden="true"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small">Permanent Transfers</h6>
                                <h3 class="mb-0"><?= $transferStats['permanent_transfers'] ?? 0 ?></h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="bi bi-currency-dollar me-1" aria-hidden="true"></i><?= formatCurrency($transferStats['permanent_transfer_value'] ?? 0) ?> value
                        </p>
                    </div>
                </div>
            </div>

            <!-- Canceled -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 transfer-stat-card" style="border-left: 4px solid var(--neutral-color);">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-x-circle text-secondary fs-5" aria-hidden="true"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small">Canceled</h6>
                                <h3 class="mb-0"><?= $transferStats['canceled'] ?? 0 ?></h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="bi bi-slash-circle me-1" aria-hidden="true"></i>Withdrawn requests
                        </p>
                    </div>
                </div>
            </div>
        </div><!-- End row -->
    </div><!-- End collapse -->
</div><!-- End statistics section -->
