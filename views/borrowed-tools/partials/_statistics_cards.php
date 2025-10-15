<?php
/**
 * Statistics Cards Partial
 * Displays borrowed tools statistics dashboard cards
 */
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

            <!-- Ready to Issue -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100" style="border-left: 4px solid var(--success-color);">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-check-circle text-success fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small">Ready to Issue</h6>
                                <h3 class="mb-0"><?= $borrowedToolStats['approved'] ?? 0 ?></h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="bi bi-box-arrow-right me-1"></i>Awaiting release
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

            <!-- Returned -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-arrow-return-left text-secondary fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small">Returned</h6>
                                <h3 class="mb-0"><?= $borrowedToolStats['returned'] ?? 0 ?></h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="bi bi-check-circle me-1"></i>Completed batches
                        </p>
                    </div>
                </div>
            </div>

            <!-- Canceled -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-x-circle text-secondary fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1 small">Canceled</h6>
                                <h3 class="mb-0"><?= $borrowedToolStats['canceled'] ?? 0 ?></h3>
                            </div>
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="bi bi-slash-circle me-1"></i>Withdrawn requests
                        </p>
                    </div>
                </div>
            </div>

            <!-- Total Borrowings -->
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
                            <i class="bi bi-archive me-1"></i>All batch records
                        </p>
                    </div>
                </div>
            </div>
        </div><!-- End row -->
    </div><!-- End collapse -->
</div><!-- End statistics section -->
