<?php
/**
 * Statistics Cards Partial
 * Displays role-appropriate borrowed tools statistics dashboard cards
 *
 * Warehouseman/Operational roles: Day-to-day actionable metrics
 * Management/Oversight roles: Overall system health metrics
 */

$userRole = $user['role_name'] ?? 'Guest';
$isOperationalRole = in_array($userRole, ['Warehouseman', 'Site Inventory Clerk']);
$isManagementRole = in_array($userRole, ['Project Manager', 'Asset Director', 'Finance Director']);
?>

<!-- Borrowed Tools Statistics Cards -->
<!-- Mobile: Collapsible, Desktop: Always visible -->
<div class="mb-4">
    <!-- Mobile Toggle Button -->
    <button class="btn btn-outline-secondary btn-sm w-100 d-md-none mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#statsCollapse" aria-expanded="false" aria-controls="statsCollapse">
        <i class="bi bi-bar-chart-line me-1"></i>
        <span>View Statistics</span>
        <i class="bi bi-chevron-down ms-auto"></i>
    </button>

    <!-- Collapsible on mobile, always visible on desktop -->
    <div class="collapse d-md-block" id="statsCollapse">
        <div class="row g-3 mb-4">

            <?php if ($isOperationalRole): ?>
                <!-- OPERATIONAL ROLE CARDS (Warehouseman, Site Inventory Clerk) -->
                <!-- Focus: Today's actionable items and current status -->

                <!-- Today's Borrowed -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--success-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-calendar-check text-success fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Borrowed Today</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['borrowed_today'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-clock me-1"></i><?= date('M d, Y') ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Today's Returns -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-arrow-return-left text-primary fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Returned Today</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['returned_today'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-box-arrow-in-down me-1"></i>Items checked in
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Currently Out (Unreturned) -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--info-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-tools text-info fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Currently Out</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['borrowed'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-person-badge me-1"></i>Active borrowings
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Overdue Tools -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--danger-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-exclamation-triangle text-danger fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Overdue</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['overdue'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-telephone me-1"></i>Follow up needed
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Due Today -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--warning-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-calendar-event text-warning fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Due Today</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['due_today'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-bell me-1"></i>Expected returns
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Due This Week -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-calendar-week text-secondary fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Due This Week</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['due_this_week'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-calendar-range me-1"></i>Next 7 days
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Available Equipment -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--success-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-box-seam text-success fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Available Now</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['available_equipment'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-check-circle me-1"></i>Ready to borrow
                            </p>
                        </div>
                    </div>
                </div>

                <!-- This Week's Activity -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-graph-up text-secondary fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">This Week</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['activity_this_week'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-activity me-1"></i>Total transactions
                            </p>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- MANAGEMENT/OVERSIGHT ROLE CARDS -->
                <!-- Focus: System health, approval queues, overall metrics -->

                <!-- Pending Verification -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--warning-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-clock text-warning fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Pending Verification</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['pending_verification'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-person me-1"></i>Project Manager review
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pending Approval -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--info-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-hourglass-split text-info fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Pending Approval</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['pending_approval'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-shield-check me-1"></i>Director approval needed
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Currently Out -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-tools text-primary fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Currently Out</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['borrowed'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-person-badge me-1"></i>Active borrowings
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Overdue Tools -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--danger-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-exclamation-triangle text-danger fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Overdue</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['overdue'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-clock me-1"></i>Requires immediate action
                            </p>
                        </div>
                    </div>
                </div>

                <!-- This Month's Borrowings -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--success-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-calendar3 text-success fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">This Month</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['borrowed_this_month'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-graph-up me-1"></i><?= date('F Y') ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- This Month's Returns -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-arrow-return-left text-secondary fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Returned This Month</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['returned_this_month'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-check-circle me-1"></i>Completed returns
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Available Equipment -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--success-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-box-seam text-success fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Available Equipment</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['available_equipment'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-check-circle me-1"></i>Non-consumable items
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Total System Activity -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light p-2 me-3">
                                    <i class="bi bi-list-ul text-secondary fs-5"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1 small">Total Borrowings</h6>
                                    <h3 class="mb-0"><?= $borrowedToolStats['total_borrowings'] ?? 0 ?></h3>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">
                                <i class="bi bi-archive me-1"></i>All-time records
                            </p>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div><!-- End row -->
    </div><!-- End collapse -->
</div><!-- End statistics section -->
