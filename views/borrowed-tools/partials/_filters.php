<?php
/**
 * Filters Partial
 * Displays filter form for borrowed tools (mobile offcanvas + desktop card)
 */
?>

<!-- Filters -->
<!-- Mobile: Offcanvas, Desktop: Card -->
<div class="mb-4">
    <!-- Mobile Filter Button (Sticky) -->
    <div class="d-md-none position-sticky top-0 z-3 bg-body py-2 mb-3" style="z-index: 1020;">
        <button class="btn btn-primary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
            <i class="bi bi-funnel me-1"></i>
            Filters
            <?php
            $activeFilters = 0;
            if (!empty($_GET['status'])) $activeFilters++;
            if (!empty($_GET['priority'])) $activeFilters++;
            if (!empty($_GET['project'])) $activeFilters++;
            if (!empty($_GET['date_from'])) $activeFilters++;
            if (!empty($_GET['date_to'])) $activeFilters++;
            if (!empty($_GET['search'])) $activeFilters++;
            if ($activeFilters > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $activeFilters ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Mobile: Offcanvas Filter Panel -->
    <div class="offcanvas offcanvas-bottom d-md-none" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel" style="height: 85vh;">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="filterOffcanvasLabel">
                <i class="bi bi-funnel me-2"></i>Filter Borrowed Tools
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form method="GET" action="?route=borrowed-tools">
                <!-- Status Filter - Role-based Options -->
                <div class="mb-3">
                    <label for="status-mobile" class="form-label">Status</label>
                    <select class="form-select" id="status-mobile" name="status">
                        <option value="">All Statuses</option>
                        <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Asset Director'])): ?>
                            <option value="Pending Verification" <?= ($_GET['status'] ?? '') === 'Pending Verification' ? 'selected' : '' ?>>
                                Pending Verification
                            </option>
                        <?php endif; ?>
                        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                            <option value="Pending Approval" <?= ($_GET['status'] ?? '') === 'Pending Approval' ? 'selected' : '' ?>>
                                Pending Approval
                            </option>
                        <?php endif; ?>
                        <?php if ($auth->hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk'])): ?>
                            <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>
                                Approved
                            </option>
                        <?php endif; ?>
                        <option value="Released" <?= ($_GET['status'] ?? '') === 'Released' ? 'selected' : '' ?>>
                            Released
                        </option>
                        <option value="Partially Returned" <?= ($_GET['status'] ?? '') === 'Partially Returned' ? 'selected' : '' ?>>
                            Partially Returned
                        </option>
                        <option value="Returned" <?= ($_GET['status'] ?? '') === 'Returned' ? 'selected' : '' ?>>
                            Returned
                        </option>
                        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])): ?>
                            <option value="Canceled" <?= ($_GET['status'] ?? '') === 'Canceled' ? 'selected' : '' ?>>
                                Canceled
                            </option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Priority Filter - For Management Roles -->
                <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Project Manager'])): ?>
                    <div class="mb-3">
                        <label for="priority-mobile" class="form-label">Priority</label>
                        <select class="form-select" id="priority-mobile" name="priority">
                            <option value="">All Priorities</option>
                            <option value="overdue" <?= ($_GET['priority'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue Items</option>
                            <option value="due_soon" <?= ($_GET['priority'] ?? '') === 'due_soon' ? 'selected' : '' ?>>Due Soon (3 days)</option>
                            <option value="pending_action" <?= ($_GET['priority'] ?? '') === 'pending_action' ? 'selected' : '' ?>>Needs My Action</option>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Project Filter - For Project Managers and Site Staff -->
                <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Site Inventory Clerk']) && !empty($projects)): ?>
                    <div class="mb-3">
                        <label for="project-mobile" class="form-label">Project</label>
                        <select class="form-select" id="project-mobile" name="project">
                            <option value="">All Projects</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>" <?= ($_GET['project'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($project['code']) ?> - <?= htmlspecialchars($project['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Date Range Filters -->
                <div class="mb-3">
                    <label for="date_from-mobile" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from-mobile" name="date_from"
                           value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="date_to-mobile" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to-mobile" name="date_to"
                           value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                </div>

                <!-- Search Field -->
                <div class="mb-3">
                    <label for="search-mobile" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search-mobile" name="search"
                           placeholder="Reference, equipment name, borrower..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Apply Filters
                    </button>
                    <a href="?route=borrowed-tools" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear All
                    </a>
                </div>

                <!-- Quick Action Buttons -->
                <hr class="my-3">
                <div class="d-grid gap-2">
                    <?php if ($auth->hasRole(['System Admin', 'Project Manager'])): ?>
                        <button type="button" class="btn btn-outline-warning" onclick="quickFilter('Pending Verification')">
                            <i class="bi bi-clock me-1"></i>My Verifications
                        </button>
                    <?php endif; ?>
                    <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                        <button type="button" class="btn btn-outline-info" onclick="quickFilter('Pending Approval')">
                            <i class="bi bi-shield-check me-1"></i>My Approvals
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-primary" onclick="quickFilter('Borrowed')">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Currently Out
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="quickFilter('overdue')">
                        <i class="bi bi-exclamation-triangle me-1"></i>Overdue
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Desktop: Card (always visible) -->
    <div class="card d-none d-md-block">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="bi bi-funnel me-2"></i>Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" id="filter-form" class="row g-3">
                <input type="hidden" name="route" value="borrowed-tools">

                <!-- Search Field -->
                <div class="col-lg-4 col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control form-control-sm" id="search" name="search"
                           placeholder="Reference, equipment name, borrower..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>

                <!-- Status Filter - Role-based Options -->
                <div class="col-lg-2 col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select form-select-sm" id="status" name="status">
                        <option value="">All Statuses</option>
                        <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Asset Director'])): ?>
                            <option value="Pending Verification" <?= ($_GET['status'] ?? '') === 'Pending Verification' ? 'selected' : '' ?>>
                                Pending Verification
                            </option>
                        <?php endif; ?>
                        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                            <option value="Pending Approval" <?= ($_GET['status'] ?? '') === 'Pending Approval' ? 'selected' : '' ?>>
                                Pending Approval
                            </option>
                        <?php endif; ?>
                        <?php if ($auth->hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk'])): ?>
                            <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>
                                Approved
                            </option>
                        <?php endif; ?>
                        <option value="Released" <?= ($_GET['status'] ?? '') === 'Released' ? 'selected' : '' ?>>
                            Released
                        </option>
                        <option value="Partially Returned" <?= ($_GET['status'] ?? '') === 'Partially Returned' ? 'selected' : '' ?>>
                            Partially Returned
                        </option>
                        <option value="Returned" <?= ($_GET['status'] ?? '') === 'Returned' ? 'selected' : '' ?>>
                            Returned
                        </option>
                        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])): ?>
                            <option value="Canceled" <?= ($_GET['status'] ?? '') === 'Canceled' ? 'selected' : '' ?>>
                                Canceled
                            </option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Project Filter - For Project Managers and Site Staff -->
                <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Site Inventory Clerk']) && !empty($projects)): ?>
                    <div class="col-lg-2 col-md-6">
                        <label for="project" class="form-label">Project</label>
                        <select class="form-select form-select-sm" id="project" name="project">
                            <option value="">All Projects</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>" <?= ($_GET['project'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($project['code']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Date Range Filters -->
                <div class="col-lg-2 col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control form-control-sm" id="date_from" name="date_from"
                           value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                </div>
                <div class="col-lg-2 col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control form-control-sm" id="date_to" name="date_to"
                           value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                </div>

                <!-- Action Buttons -->
                <div class="col-12 d-flex align-items-end gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Apply Filters
                    </button>
                    <a href="?route=borrowed-tools" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Clear All
                    </a>

                    <!-- Quick Action Buttons -->
                    <?php if ($auth->hasRole(['System Admin', 'Project Manager'])): ?>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="quickFilter('Pending Verification')">
                            <i class="bi bi-clock me-1"></i>My Verifications
                        </button>
                    <?php endif; ?>
                    <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="quickFilter('Pending Approval')">
                            <i class="bi bi-shield-check me-1"></i>My Approvals
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="quickFilter('Borrowed')">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Currently Out
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="quickFilter('overdue')">
                        <i class="bi bi-exclamation-triangle me-1"></i>Overdue
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
