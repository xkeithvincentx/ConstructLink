<?php
/**
 * Borrowed Tools Statistics Dashboard
 * Dedicated analytics and reporting view
 * Developed by: Ranoa Digital Solutions
 *
 * PURPOSE: Comprehensive statistical overview of borrowed tools module
 * - Role-specific metrics
 * - Trends and analytics
 * - Performance indicators
 * - Separate from operational views for clarity
 */

// Start output buffering
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';

// Permission check
if (!hasPermission('borrowed-tools/view_statistics')) {
    echo '<div class="alert alert-danger">You do not have permission to view statistics.</div>';
    ob_end_flush();
    return;
}

$isOperationalRole = in_array($userRole, ['Warehouseman', 'Site Inventory Clerk']);
$isManagementRole = in_array($userRole, ['Project Manager', 'Asset Director', 'Finance Director']);
?>

<!-- Page Header with Navigation -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-graph-up me-2"></i>Borrowed Tools Statistics
        </h2>
        <p class="text-muted mb-0">
            <?php if ($isOperationalRole): ?>
                Daily operational metrics and activity tracking
            <?php else: ?>
                System health overview and performance indicators
            <?php endif; ?>
        </p>
    </div>
    <div class="btn-group">
        <a href="?route=borrowed-tools" class="btn btn-outline-primary">
            <i class="bi bi-list-ul me-1"></i>View All Requests
        </a>
        <a href="?route=borrowed-tools/create-batch" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i>New Request
        </a>
    </div>
</div>

<!-- Role Indicator -->
<div class="alert alert-info alert-dismissible fade show mb-4">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Statistics View:</strong>
    Showing <?= $isOperationalRole ? 'operational' : 'management' ?> metrics for
    <span class="badge bg-primary"><?= htmlspecialchars($userRole) ?></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<!-- Statistics Cards (from existing partial) -->
<?php include APP_ROOT . '/views/borrowed-tools/partials/_statistics_cards.php'; ?>

<!-- Additional Analytics Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-week me-2"></i>Weekly Activity Trend
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-secondary">
                    <i class="bi bi-tools me-2"></i>
                    <strong>Future Enhancement:</strong> Weekly trend chart will be displayed here showing borrowed vs returned equipment over the past 4 weeks.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Borrowers / Most Borrowed Items -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-person-check me-2"></i>Top Borrowers (This Month)
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-secondary mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Future Enhancement:</strong> List of top 10 borrowers by frequency this month
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-box-seam me-2"></i>Most Borrowed Equipment
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-secondary mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Future Enhancement:</strong> List of most frequently borrowed tools/equipment
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MVA Workflow Performance (Management Only) -->
<?php if ($isManagementRole): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-diagram-3 me-2"></i>MVA Workflow Performance
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="display-6 text-muted">-</div>
                            <small class="text-muted">Avg. Verification Time</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="display-6 text-muted">-</div>
                            <small class="text-muted">Avg. Approval Time</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="display-6 text-muted">-</div>
                            <small class="text-muted">Avg. Total Processing</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="display-6 text-muted">-</div>
                            <small class="text-muted">Streamlined Batches</small>
                        </div>
                    </div>
                </div>
                <div class="alert alert-secondary mb-0 mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Future Enhancement:</strong> Detailed MVA workflow metrics including average processing times and efficiency indicators
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Overdue Summary -->
<?php if (!empty($overdueTools)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-danger shadow-sm">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Overdue Equipment Report
                </h6>
            </div>
            <div class="card-body">
                <p class="text-danger mb-3">
                    <strong><?= count($overdueTools) ?> item(s)</strong> are overdue for return. Immediate follow-up required.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Reference</th>
                                <th>Borrower</th>
                                <th>Expected Return</th>
                                <th class="text-end">Days Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overdueTools as $tool): ?>
                            <tr>
                                <td><?= htmlspecialchars($tool['asset_name']) ?></td>
                                <td><code><?= htmlspecialchars($tool['asset_ref']) ?></code></td>
                                <td><?= htmlspecialchars($tool['borrower_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($tool['expected_return'])) ?></td>
                                <td class="text-end">
                                    <span class="badge bg-danger"><?= $tool['days_overdue'] ?> days</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Export Options -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-download me-2"></i>Export & Reports
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-primary btn-sm" disabled>
                        <i class="bi bi-file-earmark-excel me-1"></i>Export to Excel
                    </button>
                    <button class="btn btn-outline-primary btn-sm" disabled>
                        <i class="bi bi-file-earmark-pdf me-1"></i>Generate PDF Report
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" disabled>
                        <i class="bi bi-printer me-1"></i>Print Statistics
                    </button>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="bi bi-info-circle me-1"></i>
                    Export functionality will be available in a future update
                </small>
            </div>
        </div>
    </div>
</div>

<style>
/* Statistics Page Specific Styles */
.display-6 {
    font-size: 2rem;
    font-weight: 300;
}

.card-header {
    border-bottom: 2px solid rgba(0,0,0,0.125);
}

/* Ensure mobile responsiveness */
@media (max-width: 768px) {
    .btn-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .btn-group .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<?php
// Capture content
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Borrowed Tools Statistics - ConstructLink™';
$pageHeader = 'Borrowed Tools Statistics Dashboard';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
    ['title' => 'Statistics', 'url' => null]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
