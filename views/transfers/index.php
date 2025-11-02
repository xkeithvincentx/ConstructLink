<?php
/**
 * Transfer Index View
 * Developed by: <?= SYSTEM_VENDOR ?>
 *
 * REFACTORED: Following borrowed-tools/index.php standards
 * - Made MVA workflow message collapsible/dismissible
 * - Removed statistics cards section for cleaner layout
 * - Added mobile-responsive button layouts
 * - Converted to AssetHelper for external JS loading
 * - Enhanced accessibility with comprehensive ARIA labels
 * - Improved separation of concerns
 */

// Start output buffering to capture content
ob_start();

// Load transfer-specific helpers
require_once APP_ROOT . '/core/TransferHelper.php';
require_once APP_ROOT . '/core/ReturnStatusHelper.php';
require_once APP_ROOT . '/core/InputValidator.php';
require_once APP_ROOT . '/helpers/BrandingHelper.php';

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Action Buttons -->
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <!-- Desktop: Action Buttons -->
    <div class="d-none d-md-flex gap-2">
        <?php if (in_array($userRole, $roleConfig['transfers/create'] ?? [])): ?>
            <a href="?route=transfers/create"
               class="btn btn-success btn-sm"
               aria-label="Create new transfer request">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>New Transfer
            </a>
        <?php endif; ?>
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                id="refreshBtn"
                onclick="location.reload()"
                aria-label="Refresh list">
            <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Refresh
        </button>
    </div>
</div>

<!-- Mobile: Action Buttons -->
<div class="d-md-none d-grid gap-2 mb-4">
    <?php if (in_array($userRole, $roleConfig['transfers/create'] ?? [])): ?>
        <a href="?route=transfers/create" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i>New Transfer Request
        </a>
    <?php endif; ?>
</div>

<!-- Messages -->
<?php
$message = InputValidator::sanitizeString($_GET['message'] ?? '');
if ($message):
    $messages = [
        'transfer_created' => ['icon' => 'check-circle', 'text' => 'Transfer request created successfully!'],
        'transfer_streamlined' => ['icon' => 'lightning-fill', 'text' => 'Transfer completed with streamlined process! Ready for final completion.'],
        'transfer_simplified' => ['icon' => 'check2-circle', 'text' => 'Transfer created with simplified process! Awaiting final approval.'],
        'transfer_verified' => ['icon' => 'check-circle', 'text' => 'Transfer request verified successfully!'],
        'transfer_approved' => ['icon' => 'check-circle', 'text' => 'Transfer request approved successfully!'],
        'transfer_received' => ['icon' => 'check-circle', 'text' => 'Transfer received successfully!'],
        'transfer_completed' => ['icon' => 'check-circle', 'text' => 'Transfer completed successfully!'],
        'transfer_canceled' => ['icon' => 'check-circle', 'text' => 'Transfer request canceled successfully!'],
        'asset_returned' => ['icon' => 'check-circle', 'text' => 'Asset returned successfully!']
    ];

    if (isset($messages[$message])):
        $msg = $messages[$message];
        ?>
        <div class="alert alert-success alert-dismissible fade show" role="status">
            <i class="bi bi-<?= $msg['icon'] ?> me-2" aria-hidden="true"></i><?= htmlspecialchars($msg['text'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close notification"></button>
        </div>
    <?php endif;
endif;

$error = InputValidator::sanitizeString($_GET['error'] ?? '');
if ($error === 'export_failed'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Failed to export transfers. Please try again.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close notification"></button>
    </div>
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
        How does the MVA workflow work?
    </button>
</div>

<div class="collapse" id="mvaHelp">
    <div class="alert alert-info mb-4" role="status">
        <strong><i class="bi bi-info-circle me-2" aria-hidden="true"></i>MVA Workflow:</strong>
        <ol class="mb-0 ps-3 mt-2">
            <li><strong>Maker</strong> creates transfer request</li>
            <li><strong>Verifier</strong> (Project Manager) verifies equipment and destination details</li>
            <li><strong>Authorizer</strong> (Asset/Finance Director) approves transfer authorization</li>
            <li>Transfer marked as <span class="badge bg-success">Approved</span>, ready for dispatch</li>
            <li>Asset dispatched and marked <span class="badge bg-primary">In Transit</span></li>
            <li>Receiving location confirms receipt (status: <span class="badge bg-secondary">Completed</span>)</li>
        </ol>
    </div>
</div>

<!-- Overdue Returns Alert -->
<?php if (!empty($overdueReturns)): ?>
    <div class="alert alert-warning mb-4" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Overdue Returns Alert
        </h6>
        <p class="mb-2">There are <?= count($overdueReturns) ?> overdue temporary transfer(s) that require immediate attention:</p>
        <ul class="mb-0">
            <?php foreach (array_slice($overdueReturns, 0, 3) as $overdue): ?>
                <li>
                    <strong><?= htmlspecialchars($overdue['asset_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    (<?= htmlspecialchars($overdue['asset_ref'], ENT_QUOTES, 'UTF-8') ?>) -
                    <?= $overdue['days_overdue'] ?> days overdue
                    <a href="?route=transfers/view&id=<?= $overdue['id'] ?>" class="ms-2">View Details</a>
                </li>
            <?php endforeach; ?>
            <?php if (count($overdueReturns) > 3): ?>
                <li><em>... and <?= count($overdueReturns) - 3 ?> more</em></li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Filters Partial -->
<?php include __DIR__ . '/_filters.php'; ?>

<!-- Transfers Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Transfer Requests</h6>
        <div class="d-flex gap-2">
            <?php if (in_array($userRole, $roleConfig['transfers/export'] ?? [])): ?>
                <button class="btn btn-sm btn-outline-primary"
                        onclick="exportToExcel()"
                        aria-label="Export transfers to Excel">
                    <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Export
                </button>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-secondary"
                    onclick="printTable()"
                    aria-label="Print transfers table">
                <i class="bi bi-printer me-1" aria-hidden="true"></i>Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($transfers)): ?>
            <div class="text-center py-5">
                <i class="bi bi-arrow-left-right display-1 text-muted" aria-hidden="true"></i>
                <h5 class="mt-3 text-muted">No transfer requests found</h5>
                <p class="text-muted">Try adjusting your filters or create a new transfer request.</p>
                <?php if (in_array($userRole, $roleConfig['transfers/create'] ?? [])): ?>
                    <a href="?route=transfers/create"
                       class="btn btn-primary"
                       aria-label="Create new transfer request">
                        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Create Transfer Request
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Mobile Card View Partial -->
            <?php include __DIR__ . '/_mobile_cards.php'; ?>

            <!-- Desktop Table View Partial -->
            <?php include __DIR__ . '/_table.php'; ?>

            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Transfers pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <?php
                            $prevParams = $_GET;
                            unset($prevParams['route']);
                            $prevParams['page'] = $pagination['current_page'] - 1;
                            $prevQuery = http_build_query($prevParams);
                            ?>
                            <li class="page-item">
                                <a class="page-link"
                                   href="?route=transfers&<?= $prevQuery ?>"
                                   aria-label="Go to previous page">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <?php
                            $pageParams = $_GET;
                            unset($pageParams['route']);
                            $pageParams['page'] = $i;
                            $pageQuery = http_build_query($pageParams);
                            ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link"
                                   href="?route=transfers&<?= $pageQuery ?>"
                                   aria-label="Go to page <?= $i ?>"
                                   <?= $i === $pagination['current_page'] ? 'aria-current="page"' : '' ?>>
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <?php
                            $nextParams = $_GET;
                            unset($nextParams['route']);
                            $nextParams['page'] = $pagination['current_page'] + 1;
                            $nextQuery = http_build_query($nextParams);
                            ?>
                            <li class="page-item">
                                <a class="page-link"
                                   href="?route=transfers&<?= $nextQuery ?>"
                                   aria-label="Go to next page">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Load module CSS -->
<?php
require_once APP_ROOT . '/helpers/AssetHelper.php';
AssetHelper::loadModuleCSS('transfers');
?>

<!-- Load external JavaScript module -->
<?php
AssetHelper::loadModuleJS('transfers', ['type' => 'module']);
?>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables - Use branding helper
$branding = BrandingHelper::loadBranding();
$pageTitle = $branding['app_name'] . ' - Asset Transfers';
$pageHeader = 'Asset Transfers';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Transfers', 'url' => '?route=transfers']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
