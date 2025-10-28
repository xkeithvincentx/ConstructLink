<?php
/**
 * Filters Partial
 * Displays filter form for borrowed tools (mobile offcanvas + desktop card)
 *
 * REFACTORED: Eliminated duplicate filter logic through helper functions,
 * improved accessibility with ARIA labels
 */

// Ensure required variables exist (defensive check)
$projects = $projects ?? [];

/**
 * ============================================================================
 * INPUT VALIDATION HELPERS
 * ============================================================================
 *
 * Defense-in-depth validation for all filter parameters.
 * Prevents SQL injection, XSS, and invalid parameter attacks.
 *
 * Filter Flow:
 * 1. User input ($_GET) → Validation functions → $validatedFilters array
 * 2. Controller reads $_GET → Model applies filters
 * 3. Validated values prevent invalid queries while allowing all valid filters
 *
 * Status System Architecture:
 * - borrowed_tool_batches.status: Pending Verification, Pending Approval,
 *   Approved, Released, Partially Returned, Returned, Canceled
 * - borrowed_tools.status: Borrowed, Returned, Canceled
 * - Computed status: Query combines batch + individual statuses
 * - Special filter: Overdue (Borrowed items past due date)
 * ============================================================================
 */

/**
 * Validate status parameter against allowed values
 *
 * Includes all workflow statuses plus special filter values:
 * - Workflow statuses: Pending Verification, Pending Approval, Approved, Released, Borrowed, Partially Returned, Returned, Canceled
 * - Special filters: Overdue (handled as Borrowed + past due in model)
 *
 * @param string $status The status value to validate
 * @return string Validated status or empty string
 */
function validateStatus(string $status): string {
    $allowedStatuses = [
        // Standard workflow statuses
        'Pending Verification',
        'Pending Approval',
        'Approved',
        'Released',
        'Borrowed',              // Added: Primary active status from database
        'Partially Returned',
        'Returned',
        'Canceled',
        // Special filter values
        'Overdue'                // Added: Special filter handled in model as Borrowed + past due
    ];
    return in_array($status, $allowedStatuses, true) ? $status : '';
}

/**
 * Validate priority parameter against allowed values
 *
 * Priority filters apply special time-based or workflow-based conditions:
 * - 'overdue': Items with status='Borrowed' AND expected_return < TODAY
 * - 'due_soon': Items with status='Borrowed' AND expected_return within 3 days
 * - 'pending_action': Items with status IN ('Pending Verification', 'Pending Approval')
 *
 * These are handled in BorrowedToolModel::getBorrowedToolsWithFilters()
 *
 * @param string $priority The priority value to validate
 * @return string Validated priority or empty string
 */
function validatePriority(string $priority): string {
    $allowedPriorities = ['overdue', 'due_soon', 'pending_action'];
    return in_array($priority, $allowedPriorities, true) ? $priority : '';
}

/**
 * Validate date parameter format (Y-m-d)
 *
 * @param string $date The date value to validate
 * @return string Validated date or empty string
 */
function validateDate(string $date): string {
    if (empty($date)) {
        return '';
    }
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    return ($dateObj && $dateObj->format('Y-m-d') === $date) ? $date : '';
}

/**
 * Sanitize search input with length limit
 *
 * @param string $search The search query to sanitize
 * @param int $maxLength Maximum allowed length
 * @return string Sanitized search query
 */
function sanitizeSearchInput(string $search, int $maxLength = 100): string {
    // Remove any potential XSS attempts (additional layer beyond htmlspecialchars)
    $search = strip_tags($search);
    // Limit length
    return mb_substr(trim($search), 0, $maxLength);
}

// Validate and sanitize all $_GET parameters
$validatedFilters = [
    'status' => validateStatus($_GET['status'] ?? ''),
    'priority' => validatePriority($_GET['priority'] ?? ''),
    'project' => filter_var($_GET['project'] ?? '', FILTER_VALIDATE_INT) ?: '',
    'date_from' => validateDate($_GET['date_from'] ?? ''),
    'date_to' => validateDate($_GET['date_to'] ?? ''),
    'search' => sanitizeSearchInput($_GET['search'] ?? '')
];

// Calculate active filter count using validated parameters
$activeFilters = 0;
foreach ($validatedFilters as $value) {
    if (!empty($value)) {
        $activeFilters++;
    }
}

/**
 * Render status options based on role
 */
function renderStatusOptions(Auth $auth, string $currentStatus = ''): string {
    $options = ['<option value="">All Statuses</option>'];

    $statusOptions = [
        ['value' => 'Pending Verification', 'label' => 'Pending Verification', 'roles' => ['System Admin', 'Project Manager', 'Asset Director']],
        ['value' => 'Pending Approval', 'label' => 'Pending Approval', 'roles' => ['System Admin', 'Asset Director', 'Finance Director']],
        ['value' => 'Approved', 'label' => 'Approved', 'roles' => ['System Admin', 'Warehouseman', 'Site Inventory Clerk']],
        ['value' => 'Released', 'label' => 'Released', 'roles' => []],
        ['value' => 'Borrowed', 'label' => 'Currently Out', 'roles' => []],
        ['value' => 'Partially Returned', 'label' => 'Partially Returned', 'roles' => []],
        ['value' => 'Returned', 'label' => 'Returned', 'roles' => []],
        ['value' => 'Canceled', 'label' => 'Canceled', 'roles' => ['System Admin', 'Asset Director', 'Project Manager']],
    ];

    foreach ($statusOptions as $option) {
        if (empty($option['roles']) || $auth->hasRole($option['roles'])) {
            $selected = $currentStatus === $option['value'] ? 'selected' : '';
            $options[] = sprintf(
                '<option value="%s" %s>%s</option>',
                htmlspecialchars($option['value']),
                $selected,
                htmlspecialchars($option['label'])
            );
        }
    }

    return implode("\n", $options);
}

/**
 * Render priority options
 */
function renderPriorityOptions(string $currentPriority = ''): string {
    $priorities = [
        '' => 'All Priorities',
        'overdue' => 'Overdue Items',
        'due_soon' => 'Due Soon (3 days)',
        'pending_action' => 'Needs My Action'
    ];

    $options = [];
    foreach ($priorities as $value => $label) {
        $selected = $currentPriority === $value ? 'selected' : '';
        $options[] = sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($value),
            $selected,
            htmlspecialchars($label)
        );
    }

    return implode("\n", $options);
}

/**
 * Render project options
 *
 * @param array $projects Array of project data
 * @param string|int $currentProject Currently selected project ID
 * @return string HTML options string
 */
function renderProjectOptions(array $projects, $currentProject = ''): string {
    $options = ['<option value="">All Projects</option>'];

    foreach ($projects as $project) {
        // Use strict comparison with type casting to prevent type juggling
        $selected = (string)$currentProject === (string)$project['id'] ? 'selected' : '';
        $options[] = sprintf(
            '<option value="%s" %s>%s - %s</option>',
            htmlspecialchars($project['id']),
            $selected,
            htmlspecialchars($project['code']),
            htmlspecialchars($project['name'])
        );
    }

    return implode("\n", $options);
}

/**
 * Render quick action buttons
 */
function renderQuickActions(Auth $auth): string {
    $buttons = [];

    if ($auth->hasRole(['System Admin', 'Project Manager'])) {
        $buttons[] = '<button type="button" class="btn btn-outline-warning btn-sm quick-filter-btn" data-quick-filter="Pending Verification" aria-label="Filter pending verifications"><i class="bi bi-clock me-1" aria-hidden="true"></i>My Verifications</button>';
    }

    if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])) {
        $buttons[] = '<button type="button" class="btn btn-outline-info btn-sm quick-filter-btn" data-quick-filter="Pending Approval" aria-label="Filter pending approvals"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>My Approvals</button>';
    }

    $buttons[] = '<button type="button" class="btn btn-outline-primary btn-sm quick-filter-btn" data-quick-filter="Borrowed" aria-label="Filter currently borrowed items"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Currently Out</button>';
    $buttons[] = '<button type="button" class="btn btn-outline-danger btn-sm quick-filter-btn" data-quick-filter="overdue" aria-label="Filter overdue items"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Overdue</button>';

    return implode("\n", $buttons);
}
?>

<!-- Filters -->
<!-- Mobile: Offcanvas, Desktop: Card -->
<div class="mb-4">
    <!-- Mobile Filter Button (Sticky) -->
    <div class="d-md-none position-sticky top-0 z-3 bg-body py-2 mb-3 filters-mobile-sticky">
        <button class="btn btn-primary w-100"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#filterOffcanvas"
                aria-label="Open filters panel"
                aria-expanded="false"
                aria-controls="filterOffcanvas">
            <i class="bi bi-funnel me-1" aria-hidden="true"></i>
            Filters
            <?php if ($activeFilters > 0): ?>
                <span class="badge bg-warning text-dark ms-1" aria-label="<?= $activeFilters ?> active filters"><?= $activeFilters ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Mobile: Offcanvas Filter Panel -->
    <div class="offcanvas offcanvas-bottom d-md-none filters-offcanvas"
         tabindex="-1"
         id="filterOffcanvas"
         aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="filterOffcanvasLabel">
                <i class="bi bi-funnel me-2" aria-hidden="true"></i>Filter Borrowed Tools
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close filters panel"></button>
        </div>
        <div class="offcanvas-body">
            <form method="GET" action="?route=borrowed-tools" id="filter-form-mobile" role="search">
                <!-- Status Filter -->
                <div class="mb-3">
                    <label for="status-mobile" class="form-label">Status</label>
                    <select class="form-select" id="status-mobile" name="status">
                        <?= renderStatusOptions($auth, $validatedFilters['status']) ?>
                    </select>
                </div>

                <!-- Priority Filter - For Management Roles -->
                <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Project Manager'])): ?>
                    <div class="mb-3">
                        <label for="priority-mobile" class="form-label">Priority</label>
                        <select class="form-select" id="priority-mobile" name="priority">
                            <?= renderPriorityOptions($validatedFilters['priority']) ?>
                        </select>
                    </div>
                <?php else: ?>
                    <!-- Hidden priority field for quick filter buttons (users without management roles) -->
                    <input type="hidden" name="priority" value="<?= htmlspecialchars($validatedFilters['priority']) ?>">
                <?php endif; ?>

                <!-- Project Filter - For Project Managers and Site Staff -->
                <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Site Inventory Clerk']) && !empty($projects)): ?>
                    <div class="mb-3">
                        <label for="project-mobile" class="form-label">Project</label>
                        <select class="form-select" id="project-mobile" name="project">
                            <?= renderProjectOptions($projects, $validatedFilters['project']) ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Date Range Filters -->
                <div class="mb-3">
                    <label for="date_from-mobile" class="form-label">Date From</label>
                    <input type="date"
                           class="form-control"
                           id="date_from-mobile"
                           name="date_from"
                           aria-describedby="date_from_help"
                           value="<?= htmlspecialchars($validatedFilters['date_from']) ?>">
                    <small id="date_from_help" class="form-text text-muted">Start of date range</small>
                </div>
                <div class="mb-3">
                    <label for="date_to-mobile" class="form-label">Date To</label>
                    <input type="date"
                           class="form-control"
                           id="date_to-mobile"
                           name="date_to"
                           aria-describedby="date_to_help"
                           value="<?= htmlspecialchars($validatedFilters['date_to']) ?>">
                    <small id="date_to_help" class="form-text text-muted">End of date range</small>
                </div>

                <!-- Search Field -->
                <div class="mb-3">
                    <label for="search-mobile" class="form-label">Search</label>
                    <input type="text"
                           class="form-control"
                           id="search-mobile"
                           name="search"
                           placeholder="Reference, equipment name, borrower..."
                           aria-describedby="search_help"
                           value="<?= htmlspecialchars($validatedFilters['search']) ?>">
                    <small id="search_help" class="form-text text-muted">Search by reference, equipment, or borrower name</small>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1" aria-hidden="true"></i>Apply Filters
                    </button>
                    <a href="?route=borrowed-tools" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear All
                    </a>
                </div>

                <!-- Quick Action Buttons -->
                <hr class="my-3">
                <div class="d-grid gap-2">
                    <?= renderQuickActions($auth) ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Desktop: Card (always visible) -->
    <div class="card d-none d-md-block">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="bi bi-funnel me-2" aria-hidden="true"></i>Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" id="filter-form" class="row g-3" role="search">
                <input type="hidden" name="route" value="borrowed-tools">

                <!-- Hidden priority field for quick filter buttons (always present for JavaScript) -->
                <?php if (!$auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Project Manager'])): ?>
                    <input type="hidden" name="priority" value="<?= htmlspecialchars($validatedFilters['priority']) ?>">
                <?php endif; ?>

                <!-- Search Field -->
                <div class="col-lg-4 col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text"
                           class="form-control form-control-sm"
                           id="search"
                           name="search"
                           placeholder="Reference, equipment name, borrower..."
                           aria-describedby="search_desktop_help"
                           value="<?= htmlspecialchars($validatedFilters['search']) ?>">
                    <small id="search_desktop_help" class="form-text text-muted visually-hidden">Search by reference, equipment, or borrower name</small>
                </div>

                <!-- Status Filter -->
                <div class="col-lg-2 col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select form-select-sm" id="status" name="status">
                        <?= renderStatusOptions($auth, $validatedFilters['status']) ?>
                    </select>
                </div>

                <!-- Priority Filter - For Management Roles -->
                <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Project Manager'])): ?>
                    <div class="col-lg-2 col-md-4">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select form-select-sm" id="priority" name="priority">
                            <?= renderPriorityOptions($validatedFilters['priority']) ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Project Filter - For Project Managers and Site Staff -->
                <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Site Inventory Clerk']) && !empty($projects)): ?>
                    <div class="col-lg-2 col-md-6">
                        <label for="project" class="form-label">Project</label>
                        <select class="form-select form-select-sm" id="project" name="project">
                            <?= renderProjectOptions($projects, $validatedFilters['project']) ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Date Range Filters -->
                <div class="col-lg-2 col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date"
                           class="form-control form-control-sm"
                           id="date_from"
                           name="date_from"
                           value="<?= htmlspecialchars($validatedFilters['date_from']) ?>">
                </div>
                <div class="col-lg-2 col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date"
                           class="form-control form-control-sm"
                           id="date_to"
                           name="date_to"
                           value="<?= htmlspecialchars($validatedFilters['date_to']) ?>">
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary btn-sm" aria-label="Apply filters">
                            <i class="bi bi-search me-1" aria-hidden="true"></i>Apply Filters
                        </button>
                        <a href="?route=borrowed-tools" class="btn btn-outline-secondary btn-sm" aria-label="Clear all filters">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear All
                        </a>

                        <!-- Divider -->
                        <div class="vr d-none d-lg-block filter-divider"></div>

                        <!-- Quick Action Buttons -->
                        <?= renderQuickActions($auth) ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
