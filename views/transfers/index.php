<?php
/**
 * Transfer Index View
 * Displays list of transfers with statistics, filters, and actions
 */

// Load transfer-specific helpers
require_once APP_ROOT . '/core/TransferHelper.php';
require_once APP_ROOT . '/core/ReturnStatusHelper.php';
require_once APP_ROOT . '/core/InputValidator.php';
require_once APP_ROOT . '/helpers/BrandingHelper.php';

// Load module CSS
$moduleCSS = ['/assets/css/modules/transfers.css'];

// Start output buffering
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <!-- Primary Actions (Left) -->
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Primary actions">
        <?php if (in_array($userRole, $roleConfig['transfers/create'] ?? [])): ?>
            <a href="?route=transfers/create"
               class="btn btn-primary btn-sm"
               aria-label="Create new transfer request">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>
                <span class="d-none d-sm-inline">New Transfer</span>
                <span class="d-sm-none">Create</span>
            </a>
        <?php endif; ?>
    </div>
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
        <div class="alert alert-success alert-dismissible fade show" role="alert">
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

<!-- Statistics Cards Partial -->
<?php include __DIR__ . '/_statistics_cards.php'; ?>

<!-- MVA Workflow Info Banner (Hidden on mobile to save space) -->
<div class="alert alert-info mb-4 d-none d-md-block" role="alert">
    <strong><i class="bi bi-info-circle me-2" aria-hidden="true"></i>MVA Workflow:</strong>
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-info">Authorizer</span> (Asset Director) →
    <span class="badge bg-success">Approved</span> →
    <span class="badge bg-primary">In Transit</span> →
    <span class="badge bg-secondary">Completed</span>
</div>

<!-- Overdue Returns Alert -->
<?php if (!empty($overdueReturns)): ?>
    <div class="alert alert-warning" role="alert">
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

<!-- Include external JavaScript -->
<script src="<?= ASSETS_URL ?>/js/modules/transfers.js"></script>

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
