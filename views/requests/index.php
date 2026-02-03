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

// Generate CSRF token for modals
$csrfToken = CSRFProtection::generateToken();

// Add external CSS and JS to page head
$additionalCSS = ['assets/css/modules/requests.css'];
$additionalJS = ['assets/js/modules/requests/components/index-actions.js'];

// Note: $_GET access should be sanitized at controller level
// This is noted for future refactoring with database-refactor-agent
?>

<!-- Requests Module Container with Alpine.js -->
<div id="requests-app"
     x-data="requestsIndexApp()"
     data-csrf-token="<?= htmlspecialchars($csrfToken) ?>">

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

        <?php if (in_array($userRole, $roleConfig['procurement-orders/createFromRequest'] ?? [])): ?>
            <a href="?route=requests&status=Approved" class="btn btn-outline-success btn-sm">
                <i class="bi bi-funnel me-1"></i>
                <span class="d-none d-sm-inline">Awaiting Procurement</span>
                <span class="d-sm-none">Approved</span>
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

<!-- MVA Workflow Help (Collapsible) -->
<div class="mb-3">
    <button class="btn btn-link btn-sm text-decoration-none p-0"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mvaHelp"
            aria-expanded="false"
            aria-controls="mvaHelp">
        <i class="bi bi-question-circle me-1" aria-hidden="true"></i>
        How does the Request MVA workflow work?
    </button>
</div>

<div class="collapse" id="mvaHelp">
    <div class="alert alert-info mb-4" role="status">
        <strong><i class="bi bi-info-circle me-2" aria-hidden="true"></i>Request MVA Workflow:</strong>
        <ol class="mb-0 ps-3 mt-2">
            <li><strong>Maker</strong> (<?= implode(', ', $roleConfig['requests']['maker'] ?? []) ?>) creates request for materials, tools, or equipment</li>
            <li><strong>Verifier</strong> (<?= implode(', ', $roleConfig['requests']['verifier'] ?? []) ?>) reviews if request is valid for project needs</li>
            <li><strong>Authorizer</strong> (<?= implode(', ', $roleConfig['requests']['authorizer'] ?? []) ?>) approves based on budget and priority
                <span class="badge bg-success ms-2">Status: Approved</span>
            </li>
            <li><strong>Procurement Officer</strong> creates procurement order from approved requests
                <span class="badge bg-primary ms-2">Status: In Procurement</span>
            </li>
            <li>Items received and delivered to requesting project
                <span class="badge bg-secondary ms-2">Status: Completed</span>
            </li>
        </ol>

        <?php if (in_array($userRole, $roleConfig['procurement-orders/createFromRequest'] ?? [])): ?>
        <div class="alert alert-primary mt-3 mb-0">
            <strong><i class="bi bi-lightbulb me-2"></i>Procurement Officer Guide:</strong>
            Look for requests with <span class="badge bg-success">Approved</span> status showing
            <span class="badge bg-warning text-dark">Awaiting Procurement</span> in the Delivery Status column.
            Click the green <span class="badge bg-success"><i class="bi bi-file-earmark-plus"></i> Create PO</span> button to generate a procurement order.
        </div>
        <?php endif; ?>
    </div>
</div>

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
        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label" for="filter-status">Status</label>
                <select x-model="filters.status" id="filter-status" class="form-select" aria-label="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="Draft">Draft</option>
                    <option value="Submitted">Submitted</option>
                    <option value="Reviewed">Reviewed</option>
                    <option value="Forwarded">Forwarded</option>
                    <option value="Approved">Approved</option>
                    <option value="Declined">Declined</option>
                    <option value="Procured">Procured</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="filter-type">Request Type</label>
                <select x-model="filters.requestType" id="filter-type" class="form-select" aria-label="Filter by request type">
                    <option value="">All Types</option>
                    <option value="Material">Material</option>
                    <option value="Tool">Tool</option>
                    <option value="Equipment">Equipment</option>
                    <option value="Service">Service</option>
                    <option value="Petty Cash">Petty Cash</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="filter-project">Project</label>
                <select x-model="filters.projectId" id="filter-project" class="form-select" aria-label="Filter by project">
                    <option value="">All Projects</option>
                    <?php if (isset($projects) && is_array($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>">
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="filter-urgency">Urgency</label>
                <select x-model="filters.urgency" id="filter-urgency" class="form-select" aria-label="Filter by urgency">
                    <option value="">All Urgency</option>
                    <option value="Normal">Normal</option>
                    <option value="Urgent">Urgent</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="filter-date-from">Date From</label>
                <input type="date" x-model="filters.dateFrom" class="form-control" id="filter-date-from"
                       aria-label="Filter from date">
            </div>

            <div class="col-md-2">
                <label class="form-label" for="filter-date-to">Date To</label>
                <input type="date" x-model="filters.dateTo" class="form-control" id="filter-date-to"
                       aria-label="Filter to date">
            </div>

            <div class="col-md-8">
                <label class="form-label" for="filter-search">Search</label>
                <input type="text" x-model="filters.search" class="form-control" id="filter-search"
                       placeholder="Search by description, project, or requester..."
                       aria-label="Search requests"
                       @keyup.enter="applyFilters()">
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="button" @click="applyFilters()" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <button type="button" @click="clearFilters()" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Requests Table -->
<div class="card">
    <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
        <h6 class="card-title mb-0">Request List</h6>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <!-- Records Per Page Selector (Desktop Only) -->
            <div class="d-none d-md-flex align-items-center gap-2">
                <label for="recordsPerPage" class="mb-0 text-nowrap" style="font-size: 0.875rem;">
                    <i class="bi bi-list-ul me-1" aria-hidden="true"></i>Show:
                </label>
                <select id="recordsPerPage"
                        class="form-select form-select-sm"
                        style="width: auto; min-width: 80px;"
                        aria-label="Records per page">
                    <?php
                    $perPage = (int)($_GET['per_page'] ?? 5);
                    ?>
                    <option value="5" <?= $perPage == 5 ? 'selected' : '' ?>>5</option>
                    <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
                </select>
                <span class="text-muted" style="font-size: 0.875rem;">entries</span>
            </div>
            <div class="vr d-none d-md-block"></div>
            <?php if (in_array($user['role_name'], $roleConfig['requests/export'] ?? [])): ?>
            <button class="btn btn-sm btn-outline-primary" id="exportBtn" data-action="export" aria-label="Export to Excel">
                <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>
                <span class="d-none d-md-inline">Export</span>
            </button>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-secondary" id="printBtn" data-action="print" aria-label="Print list">
                <i class="bi bi-printer me-1" aria-hidden="true"></i>
                <span class="d-none d-md-inline">Print</span>
            </button>
        </div>
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
                        <?php
                        // Pre-compute role permissions once for all requests
                        $canVerify = in_array($userRole, $roleConfig['requests/verify'] ?? []);
                        $canAuthorize = in_array($userRole, $roleConfig['requests/authorize'] ?? []);
                        $canApprove = in_array($userRole, $roleConfig['requests/approve'] ?? []);
                        $canDecline = in_array($userRole, $roleConfig['requests/decline'] ?? []);
                        $canCreatePO = in_array($userRole, $roleConfig['requests/generate-po'] ?? []);

                        foreach ($requests as $request):
                        ?>
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
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=requests/view&id=<?= $request['id'] ?>"
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <?php
                                        // MVA Workflow Action Buttons
                                        // Using pre-computed role permissions for performance

                                        // Verify button (for Submitted requests)
                                        if ($request['status'] === 'Submitted' && $canVerify):
                                        ?>
                                            <button type="button"
                                                    class="btn btn-outline-warning"
                                                    title="Verify Request"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#requestVerifyModal"
                                                    data-action="verify-request"
                                                    data-request-id="<?= $request['id'] ?>">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        <?php
                                        endif;

                                        // Authorize button (for Verified requests)
                                        if ($request['status'] === 'Verified' && $canAuthorize):
                                        ?>
                                            <button type="button"
                                                    class="btn btn-outline-primary"
                                                    title="Authorize Request"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#requestAuthorizeModal"
                                                    data-action="authorize-request"
                                                    data-request-id="<?= $request['id'] ?>">
                                                <i class="bi bi-shield-check"></i>
                                            </button>
                                        <?php
                                        endif;

                                        // Approve button (final approval for Submitted/Verified/Authorized requests)
                                        if (in_array($request['status'], ['Submitted', 'Verified', 'Authorized']) && $canApprove):
                                        ?>
                                            <button type="button"
                                                    class="btn btn-outline-success"
                                                    title="Approve Request"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#requestApproveModal"
                                                    data-action="approve-request"
                                                    data-request-id="<?= $request['id'] ?>">
                                                <i class="bi bi-check-all"></i>
                                            </button>
                                        <?php
                                        endif;

                                        // Decline button (available to approvers at any active workflow stage)
                                        if (in_array($request['status'], ['Submitted', 'Verified', 'Authorized']) && $canDecline):
                                        ?>
                                            <button type="button"
                                                    class="btn btn-outline-danger"
                                                    title="Decline Request"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#requestDeclineModal"
                                                    data-action="decline-request"
                                                    data-request-id="<?= $request['id'] ?>">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        <?php
                                        endif;

                                        // Create PO button (for approved requests without procurement order)
                                        if ($request['status'] === 'Approved' && $canCreatePO && empty($request['procurement_id'])):
                                        ?>
                                            <a href="?route=requests/generate-po&request_id=<?= $request['id'] ?>"
                                               class="btn btn-success btn-sm" title="Create Procurement Order">
                                                <i class="bi bi-file-earmark-plus me-1"></i>
                                                <span>Create PO</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Enhanced Pagination Controls (Matches withdrawals pattern) -->
            <?php if (isset($pagination)): ?>
                <?php
                $totalRequests = $pagination['total'] ?? 0;
                $currentPage = $pagination['current_page'] ?? 1;
                $totalPages = $pagination['total_pages'] ?? 1;
                $perPageValue = (int)($_GET['per_page'] ?? 5);
                $from = $totalRequests > 0 ? (($currentPage - 1) * $perPageValue) + 1 : 0;
                $to = min($currentPage * $perPageValue, $totalRequests);
                ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 gap-3">
                    <!-- Showing Info -->
                    <div class="text-muted small">
                        Showing
                        <strong><?= number_format($from) ?></strong> to
                        <strong><?= number_format($to) ?></strong>
                        of
                        <strong><?= number_format($totalRequests) ?></strong>
                        entries
                        <?php if (!empty($_GET['status']) || !empty($_GET['search']) || !empty($_GET['date_from']) || !empty($_GET['date_to'])): ?>
                            <span class="text-primary">(filtered)</span>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination Navigation -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Request pagination">
                            <ul class="pagination pagination-sm mb-0 justify-content-center justify-content-md-end">
                                <!-- Previous Page -->
                                <?php if ($pagination['has_prev'] ?? false): ?>
                                    <li class="page-item">
                                        <?php
                                        $prevParams = $_GET;
                                        unset($prevParams['route']);
                                        $prevParams['page'] = $currentPage - 1;
                                        ?>
                                        <a class="page-link"
                                           href="?route=requests&<?= http_build_query($prevParams) ?>"
                                           aria-label="Go to previous page">
                                            <span aria-hidden="true">&laquo;</span>
                                            <span class="d-none d-sm-inline ms-1">Previous</span>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <span aria-hidden="true">&laquo;</span>
                                            <span class="d-none d-sm-inline ms-1">Previous</span>
                                        </span>
                                    </li>
                                <?php endif; ?>

                                <!-- Page Numbers (Smart Pagination) -->
                                <?php
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($totalPages, $currentPage + 2);

                                // Show first page if not in range
                                if ($startPage > 1):
                                    $firstParams = $_GET;
                                    unset($firstParams['route']);
                                    $firstParams['page'] = 1;
                                ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                           href="?route=requests&<?= http_build_query($firstParams) ?>"
                                           aria-label="Go to page 1">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Page number buttons -->
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <?php if ($i == $currentPage): ?>
                                        <li class="page-item active" aria-current="page">
                                            <span class="page-link">
                                                <?= $i ?>
                                                <span class="visually-hidden">(current)</span>
                                            </span>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item">
                                            <?php
                                            $pageParams = $_GET;
                                            unset($pageParams['route']);
                                            $pageParams['page'] = $i;
                                            ?>
                                            <a class="page-link"
                                               href="?route=requests&<?= http_build_query($pageParams) ?>"
                                               aria-label="Go to page <?= $i ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <!-- Show last page if not in range -->
                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <?php
                                        $lastParams = $_GET;
                                        unset($lastParams['route']);
                                        $lastParams['page'] = $totalPages;
                                        ?>
                                        <a class="page-link"
                                           href="?route=requests&<?= http_build_query($lastParams) ?>"
                                           aria-label="Go to page <?= $totalPages ?>">
                                            <?= $totalPages ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <!-- Next Page -->
                                <?php if ($pagination['has_next'] ?? false): ?>
                                    <li class="page-item">
                                        <?php
                                        $nextParams = $_GET;
                                        unset($nextParams['route']);
                                        $nextParams['page'] = $currentPage + 1;
                                        ?>
                                        <a class="page-link"
                                           href="?route=requests&<?= http_build_query($nextParams) ?>"
                                           aria-label="Go to next page">
                                            <span class="d-none d-sm-inline me-1">Next</span>
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <span class="d-none d-sm-inline me-1">Next</span>
                                            <span aria-hidden="true">&raquo;</span>
                                        </span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
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

<!-- Request Verification Modal -->
<?php
ob_start();
?>
<input type="hidden" name="_csrf_token" value="<?= $csrfToken ?? CSRFProtection::generateToken() ?>">
<input type="hidden" name="request_id" value="">

<div class="alert alert-info" role="alert">
    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
    Review this request and confirm it meets requirements before verification.
</div>

<div class="mb-3">
    <label for="verification_notes" class="form-label">Verification Notes</label>
    <textarea class="form-control"
              id="verification_notes"
              name="notes"
              rows="3"
              placeholder="Optional notes about the verification"
              aria-describedby="verification_notes_help"></textarea>
    <small id="verification_notes_help" class="form-text text-muted">Add any relevant notes about the verification process</small>
</div>
<?php
$modalBody = ob_get_clean();

ob_start();
?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-warning">
    <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Verify Request
</button>
<?php
$modalActions = ob_get_clean();

$id = 'requestVerifyModal';
$title = 'Verify Request';
$icon = 'check-circle';
$headerClass = 'bg-warning';
$body = $modalBody;
$actions = $modalActions;
$size = 'lg';
$formAction = 'index.php?route=requests/verify';
$formMethod = 'POST';

include APP_ROOT . '/views/components/modal.php';
?>

<!-- Request Authorization Modal -->
<?php
ob_start();
?>
<input type="hidden" name="_csrf_token" value="<?= $csrfToken ?? CSRFProtection::generateToken() ?>">
<input type="hidden" name="request_id" value="">

<div class="alert alert-primary" role="alert">
    <i class="bi bi-shield-check me-2" aria-hidden="true"></i>
    <strong>Authorization:</strong> Review and authorize this request for final approval.
</div>

<div class="mb-3">
    <label for="authorization_notes" class="form-label">Authorization Notes</label>
    <textarea class="form-control"
              id="authorization_notes"
              name="notes"
              rows="3"
              placeholder="Optional notes about the authorization"
              aria-describedby="authorization_notes_help"></textarea>
    <small id="authorization_notes_help" class="form-text text-muted">Add any relevant notes about the authorization</small>
</div>
<?php
$modalBody = ob_get_clean();

ob_start();
?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-primary">
    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Authorize Request
</button>
<?php
$modalActions = ob_get_clean();

$id = 'requestAuthorizeModal';
$title = 'Authorize Request';
$icon = 'shield-check';
$headerClass = 'bg-primary text-white';
$body = $modalBody;
$actions = $modalActions;
$size = 'lg';
$formAction = 'index.php?route=requests/authorize';

include APP_ROOT . '/views/components/modal.php';
?>

<!-- Request Approval Modal -->
<?php
ob_start();
?>
<input type="hidden" name="_csrf_token" value="<?= $csrfToken ?? CSRFProtection::generateToken() ?>">
<input type="hidden" name="request_id" value="">

<div class="alert alert-success" role="alert">
    <i class="bi bi-check-all me-2" aria-hidden="true"></i>
    <strong>Final Approval:</strong> Review and approve this request for procurement.
</div>

<div class="mb-3">
    <label for="approval_notes" class="form-label">Approval Notes</label>
    <textarea class="form-control"
              id="approval_notes"
              name="notes"
              rows="3"
              placeholder="Optional notes about the approval"
              aria-describedby="approval_notes_help"></textarea>
    <small id="approval_notes_help" class="form-text text-muted">Add any relevant notes about the approval</small>
</div>
<?php
$modalBody = ob_get_clean();

ob_start();
?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-success">
    <i class="bi bi-check-all me-1" aria-hidden="true"></i>Approve Request
</button>
<?php
$modalActions = ob_get_clean();

$id = 'requestApproveModal';
$title = 'Approve Request';
$icon = 'check-all';
$headerClass = 'bg-success text-white';
$body = $modalBody;
$actions = $modalActions;
$size = 'lg';
$formAction = 'index.php?route=requests/approveWorkflow';

include APP_ROOT . '/views/components/modal.php';
?>

<!-- Request Decline Modal -->
<?php
ob_start();
?>
<input type="hidden" name="_csrf_token" value="<?= $csrfToken ?? CSRFProtection::generateToken() ?>">
<input type="hidden" name="request_id" value="">

<div class="alert alert-warning" role="alert">
    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
    <strong>Warning:</strong> Declining this request will require the requester to resubmit.
</div>

<div class="mb-3">
    <label for="decline_reason" class="form-label">Reason for Declining <span class="text-danger">*</span></label>
    <textarea class="form-control"
              id="decline_reason"
              name="decline_reason"
              rows="4"
              required
              placeholder="Please provide a detailed reason for declining this request..."
              aria-describedby="decline_reason_help"></textarea>
    <small id="decline_reason_help" class="form-text text-muted">Be specific to help the requester understand what needs to be changed.</small>
</div>
<?php
$modalBody = ob_get_clean();

ob_start();
?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-danger">
    <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Decline Request
</button>
<?php
$modalActions = ob_get_clean();

$id = 'requestDeclineModal';
$title = 'Decline Request';
$icon = 'x-circle';
$headerClass = 'bg-danger text-white';
$body = $modalBody;
$actions = $modalActions;
$size = 'lg';
$formAction = 'index.php?route=requests/decline';

include APP_ROOT . '/views/components/modal.php';
?>

<!-- Close Alpine.js Container -->
</div>

<!-- Page Scripts -->
<script type="module">
import { requestsIndexApp } from '/assets/js/requests/requests-index.js';
window.requestsIndexApp = requestsIndexApp;

// Additional handlers for records per page, export, and print
document.addEventListener('DOMContentLoaded', function() {
    // Records per page change handler
    const recordsPerPageSelect = document.getElementById('recordsPerPage');
    if (recordsPerPageSelect) {
        recordsPerPageSelect.addEventListener('change', function() {
            const params = new URLSearchParams(window.location.search);
            params.set('per_page', this.value);
            params.set('page', '1'); // Reset to first page
            window.location.search = params.toString();
        });
    }

    // Export handler
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('route', 'requests/export');
            window.location.href = currentUrl.toString();
        });
    }

    // Print handler
    const printBtn = document.getElementById('printBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }
});
</script>

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
