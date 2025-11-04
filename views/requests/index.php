<?php
/**
 * ConstructLink™ Request Index View - Unified Request Management
 *
 * Refactored to use partials and external resources following DRY principles.
 * Statistics cards now use reusable partial (150+ lines reduction).
 * All inline JavaScript and styles have been extracted.
 *
 * @version 2.0.0
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';

$roleConfig = require APP_ROOT . '/config/roles.php';

// Add external CSS and JS to page head
$additionalCSS = ['assets/css/modules/requests.css'];
$additionalJS = ['assets/js/modules/requests/components/index-actions.js'];

// Note: $_GET access should be sanitized at controller level
// This is noted for future refactoring with database-refactor-agent
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4 gap-2">
    <!-- Primary Actions (Left) -->
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Primary actions">
        <?php if (in_array($user['role_name'], $roleConfig['requests/create'] ?? [])): ?>
            <a href="?route=requests/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">New Request</span>
                <span class="d-sm-none">Create</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Secondary Actions (Right) -->
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Secondary actions">
        <?php if (in_array($user['role_name'], $roleConfig['requests/export'] ?? [])): ?>
            <a href="?route=requests/export<?= !empty($_GET) ? '&' . http_build_query($_GET) : '' ?>" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download me-1"></i>
                <span class="d-none d-sm-inline">Export</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php
    $messages = [
        'request_created' => ['type' => 'success', 'text' => 'Request has been created successfully.'],
        'request_updated' => ['type' => 'success', 'text' => 'Request has been updated successfully.'],
        'request_submitted' => ['type' => 'success', 'text' => 'Request has been submitted for review.'],
        'request_approved' => ['type' => 'success', 'text' => 'Request has been approved successfully.'],
        'request_declined' => ['type' => 'danger', 'text' => 'Request has been declined.'],
        'request_forwarded' => ['type' => 'info', 'text' => 'Request has been forwarded for further review.']
    ];

    $message = $messages[$_GET['message']] ?? null;
    if ($message):
    ?>
        <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= $message['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'export_failed'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>Failed to export requests. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Statistics Cards - Using Partial (DRY Refactoring) -->
<div class="row g-3 mb-4">
    <?php
    // Load statistics cards configuration
    $statisticsCards = include APP_ROOT . '/views/requests/_partials/_statistics-cards-data.php';

    // Render each card using the reusable partial
    foreach ($statisticsCards as $card) {
        $title = $card['title'];
        $value = $card['value'];
        $icon = $card['icon'];
        $color = $card['color'];
        $description = $card['description'];
        $actionUrl = $card['actionUrl'];
        $actionLabel = $card['actionLabel'];
        $actionBadge = $card['actionBadge'] ?? 0;

        include APP_ROOT . '/views/requests/_partials/_statistics-card.php';
    }
    ?>
</div>

<!-- MVA Workflow Info Banner -->
<?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Project Manager', 'Procurement Officer'])): ?>
<div class="alert alert-info mb-4" role="status">
    <strong><i class="bi bi-info-circle me-2"></i>MVA Workflow:</strong>
    <span class="badge bg-info">Maker</span> (Site Inventory Clerk) →
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-success">Authorizer</span> (Asset Director) →
    <span class="badge bg-primary">In Procurement</span> →
    <span class="badge bg-secondary">Completed</span>
</div>
<?php endif; ?>

<!-- Delivery Alerts Section -->
<?php if (isset($deliveryAlerts) && !empty($deliveryAlerts)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delivery Alerts
                    <span class="badge bg-dark ms-2"><?= count($deliveryAlerts) ?></span>
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach (array_slice($deliveryAlerts, 0, 3) as $alert): ?>
                    <div class="col-md-4">
                        <div class="alert alert-<?= ($alert['type'] ?? 'info') === 'overdue' ? 'danger' : (($alert['type'] ?? 'info') === 'discrepancy' ? 'warning' : 'info') ?> mb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?= htmlspecialchars($alert['title'] ?? 'Alert') ?></strong>
                                    <div class="small mt-1"><?= htmlspecialchars($alert['message'] ?? '') ?></div>
                                    <small class="text-muted">Request #<?= $alert['request_id'] ?? 'N/A' ?></small>
                                </div>
                                <a href="?route=requests/view&id=<?= $alert['request_id'] ?? 0 ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($deliveryAlerts) > 3): ?>
                <div class="text-center">
                    <button class="btn btn-sm btn-outline-warning" type="button" aria-label="Show all delivery alerts">
                        <i class="bi bi-chevron-down me-1"></i>Show All Alerts (<?= count($deliveryAlerts) - 3 ?> more)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=requests" class="row g-3">
            <div class="col-md-2">
                <label class="form-label" for="filter-status">Status</label>
                <select name="status" id="filter-status" class="form-select" aria-label="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="Draft" <?= ($_GET['status'] ?? '') === 'Draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="Submitted" <?= ($_GET['status'] ?? '') === 'Submitted' ? 'selected' : '' ?>>Submitted</option>
                    <option value="Reviewed" <?= ($_GET['status'] ?? '') === 'Reviewed' ? 'selected' : '' ?>>Reviewed</option>
                    <option value="Forwarded" <?= ($_GET['status'] ?? '') === 'Forwarded' ? 'selected' : '' ?>>Forwarded</option>
                    <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Declined" <?= ($_GET['status'] ?? '') === 'Declined' ? 'selected' : '' ?>>Declined</option>
                    <option value="Procured" <?= ($_GET['status'] ?? '') === 'Procured' ? 'selected' : '' ?>>Procured</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="filter-type">Request Type</label>
                <select name="request_type" id="filter-type" class="form-select" aria-label="Filter by request type">
                    <option value="">All Types</option>
                    <option value="Material" <?= ($_GET['request_type'] ?? '') === 'Material' ? 'selected' : '' ?>>Material</option>
                    <option value="Tool" <?= ($_GET['request_type'] ?? '') === 'Tool' ? 'selected' : '' ?>>Tool</option>
                    <option value="Equipment" <?= ($_GET['request_type'] ?? '') === 'Equipment' ? 'selected' : '' ?>>Equipment</option>
                    <option value="Service" <?= ($_GET['request_type'] ?? '') === 'Service' ? 'selected' : '' ?>>Service</option>
                    <option value="Petty Cash" <?= ($_GET['request_type'] ?? '') === 'Petty Cash' ? 'selected' : '' ?>>Petty Cash</option>
                    <option value="Other" <?= ($_GET['request_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="filter-project">Project</label>
                <select name="project_id" id="filter-project" class="form-select" aria-label="Filter by project">
                    <option value="">All Projects</option>
                    <?php if (isset($projects) && is_array($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>"
                                    <?= ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="filter-urgency">Urgency</label>
                <select name="urgency" id="filter-urgency" class="form-select" aria-label="Filter by urgency">
                    <option value="">All Urgency</option>
                    <option value="Normal" <?= ($_GET['urgency'] ?? '') === 'Normal' ? 'selected' : '' ?>>Normal</option>
                    <option value="Urgent" <?= ($_GET['urgency'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                    <option value="Critical" <?= ($_GET['urgency'] ?? '') === 'Critical' ? 'selected' : '' ?>>Critical</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="filter-date-from">Date From</label>
                <input type="date" class="form-control" name="date_from" id="filter-date-from"
                       value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>"
                       aria-label="Filter from date">
            </div>

            <div class="col-md-2">
                <label class="form-label" for="filter-date-to">Date To</label>
                <input type="date" class="form-control" name="date_to" id="filter-date-to"
                       value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>"
                       aria-label="Filter to date">
            </div>

            <div class="col-md-8">
                <label class="form-label" for="filter-search">Search</label>
                <input type="text" class="form-control" name="search" id="filter-search"
                       placeholder="Search by description, project, or requester..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                       aria-label="Search requests">
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=requests" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Request List</h6>
        <small class="text-muted">
            Showing <?= count($requests ?? []) ?> of <?= $pagination['total'] ?? 0 ?> requests
        </small>
    </div>
    <div class="card-body">
        <?php if (!empty($requests)): ?>
            <div class="table-responsive">
                <table class="table table-hover" aria-label="Requests table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Type</th>
                            <th scope="col">Description</th>
                            <th scope="col">Project</th>
                            <th scope="col">Urgency</th>
                            <th scope="col">Status</th>
                            <th scope="col">Delivery Status</th>
                            <th scope="col">Procurement</th>
                            <th scope="col">Requested By</th>
                            <th scope="col">Date Created</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr class="<?= $request['urgency'] === 'Critical' ? 'table-danger' : ($request['urgency'] === 'Urgent' ? 'table-warning' : '') ?>">
                                <td>
                                    <a href="?route=requests/view&id=<?= $request['id'] ?>" class="text-decoration-none">
                                        #<?= $request['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($request['request_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars(substr($request['description'], 0, 50)) ?><?= strlen($request['description']) > 50 ? '...' : '' ?></div>
                                    <?php if ($request['category']): ?>
                                        <small class="text-muted"><?= htmlspecialchars($request['category']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($request['project_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $urgency = $request['urgency'];
                                    $includeIcon = true;
                                    $size = 'normal';
                                    include APP_ROOT . '/views/requests/_partials/_badge-urgency.php';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $request['status'];
                                    $includeIcon = false;
                                    $size = 'normal';
                                    include APP_ROOT . '/views/requests/_partials/_badge-status.php';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $deliveryStatus = $request['overall_delivery_status'] ?? 'Not Started';
                                    $includeIcon = false;
                                    $size = 'small';
                                    include APP_ROOT . '/views/requests/_partials/_badge-delivery.php';
                                    ?>

                                    <!-- Delivery Alert Icons -->
                                    <?php if (isset($request['has_delivery_alert']) && $request['has_delivery_alert']): ?>
                                        <br><i class="bi bi-exclamation-triangle text-warning small" title="Has delivery alerts"></i>
                                    <?php endif; ?>

                                    <?php if (isset($request['is_overdue']) && $request['is_overdue']): ?>
                                        <br><i class="bi bi-clock text-danger small" title="Overdue delivery"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($request['procurement_id']) && $request['procurement_id']): ?>
                                        <a href="?route=procurement-orders/view&id=<?= $request['procurement_id'] ?>" class="text-decoration-none">
                                            <span class="badge bg-info small">
                                                PO #<?= htmlspecialchars($request['po_number'] ?? $request['procurement_id']) ?>
                                            </span>
                                        </a>
                                        <?php if (isset($request['procurement_status'])): ?>
                                            <br>
                                            <?php
                                            $procurementStatus = $request['procurement_status'];
                                            $includeIcon = false;
                                            $size = 'small';
                                            include APP_ROOT . '/views/requests/_partials/_badge-procurement.php';
                                            ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">No PO</span>
                                        <?php if (canCreatePOFromRequest($request, $userRole)): ?>
                                            <br>
                                            <a href="?route=procurement-orders/createFromRequest&request_id=<?= $request['id'] ?>" class="btn btn-xs btn-outline-primary">
                                                <i class="bi bi-plus-circle"></i> Create PO
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small"><?= htmlspecialchars($request['requested_by_name']) ?></div>
                                </td>
                                <td>
                                    <div class="small"><?= date('M j, Y', strtotime($request['created_at'])) ?></div>
                                    <small class="text-muted"><?= date('g:i A', strtotime($request['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?php $currentUser = $auth->getCurrentUser(); ?>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=requests/view&id=<?= $request['id'] ?>"
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($request['status'] === 'Submitted' && in_array($user['role_name'], $roleConfig['requests/review'] ?? [])): ?>
                                            <a href="?route=requests/review&id=<?= $request['id'] ?>"
                                               class="btn btn-outline-info" title="Review/Forward">
                                                <i class="bi bi-arrow-right-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (in_array($request['status'], ['Reviewed', 'Forwarded']) && in_array($user['role_name'], $roleConfig['requests/approve'] ?? [])): ?>
                                            <a href="?route=requests/approve&id=<?= $request['id'] ?>"
                                               class="btn btn-outline-success" title="Approve">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($request['status'] === 'Approved' && in_array($user['role_name'], $roleConfig['requests/generate-po'] ?? []) && empty($request['procurement_id'])): ?>
                                            <a href="?route=requests/generate-po&request_id=<?= $request['id'] ?>"
                                               class="btn btn-outline-primary" title="Create PO">
                                                <i class="bi bi-plus-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Request pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=requests&page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?route=requests&page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=requests&page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-clipboard-x display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No requests found</h5>
                <p class="text-muted">Try adjusting your filters or create a new request.</p>
                <?php if (in_array($user['role_name'], $roleConfig['requests/create'] ?? [])): ?>
                    <a href="?route=requests/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create First Request
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Request Management - ConstructLink™';
$pageHeader = 'Request Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Requests', 'url' => '?route=requests']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
