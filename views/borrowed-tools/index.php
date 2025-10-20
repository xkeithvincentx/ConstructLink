<?php
/**
 * Borrowed Tools Index View
 * Developed by: Ranoa Digital Solutions
 *
 * REFACTORED: Removed 900+ lines of inline JavaScript, converted modals to components,
 * added comprehensive ARIA labels, and centralized configuration.
 */

// Start output buffering to capture content
ob_start();

// Load ViewHelper for reusable components
require_once APP_ROOT . '/helpers/ViewHelper.php';

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';

// Group borrowed tools by batch_id for batch display
$groupedTools = [];
$singleTools = [];

foreach ($borrowedTools as $tool) {
    if (!empty($tool['batch_id'])) {
        if (!isset($groupedTools[$tool['batch_id']])) {
            $groupedTools[$tool['batch_id']] = [];
        }
        $groupedTools[$tool['batch_id']][] = $tool;
    } else {
        $singleTools[] = $tool;
    }
}

// Merge grouped batches and single items for display
$displayItems = [];
foreach ($groupedTools as $batchId => $batchItems) {
    if (count($batchItems) > 1) {
        $displayItems[] = [
            'type' => 'batch',
            'batch_id' => $batchId,
            'items' => $batchItems,
            'primary' => $batchItems[0]
        ];
    } else {
        $displayItems[] = [
            'type' => 'single',
            'item' => $batchItems[0]
        ];
    }
}
foreach ($singleTools as $tool) {
    $displayItems[] = [
        'type' => 'single',
        'item' => $tool
    ];
}

// Sort by ID descending
usort($displayItems, function($a, $b) {
    $idA = $a['type'] === 'batch' ? $a['primary']['id'] : $a['item']['id'];
    $idB = $b['type'] === 'batch' ? $b['primary']['id'] : $b['item']['id'];
    return $idB - $idA;
});

// Generate CSRF token for JavaScript
$csrfToken = CSRFProtection::generateToken();
?>

<!-- Borrowed Tools Module Container with Configuration -->
<div id="borrowed-tools-app"
     data-csrf-token="<?= htmlspecialchars($csrfToken) ?>"
     data-auto-refresh-interval="<?= config('business_rules.ui.auto_refresh_interval', 300) ?>">

<!-- Page Header & Action Buttons -->
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h2 class="mb-1">Borrowed Equipment</h2>
        <p class="text-muted mb-0">Manage and track all equipment borrowing requests</p>
    </div>

    <!-- Desktop: Action Buttons -->
    <div class="d-none d-md-flex gap-2">
        <a href="?route=borrowed-tools/statistics"
           class="btn btn-outline-info btn-sm"
           aria-label="View statistics dashboard">
            <i class="bi bi-graph-up me-1" aria-hidden="true"></i>Statistics
        </a>
        <a href="?route=borrowed-tools/print-blank-form"
           class="btn btn-outline-secondary btn-sm"
           target="_blank"
           aria-label="Print blank form">
            <i class="bi bi-printer me-1" aria-hidden="true"></i>Print Form
        </a>
        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'])): ?>
            <a href="?route=borrowed-tools/create-batch"
               class="btn btn-success btn-sm"
               aria-label="Create new borrow request">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>New Request
            </a>
        <?php endif; ?>
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                id="refreshBtn"
                aria-label="Refresh list">
            <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Refresh
        </button>
    </div>
</div>

<!-- Mobile: Action Buttons -->
<div class="d-md-none d-grid gap-2 mb-4">
    <a href="?route=borrowed-tools/statistics" class="btn btn-outline-info">
        <i class="bi bi-graph-up me-1"></i>View Statistics
    </a>
    <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'])): ?>
        <a href="?route=borrowed-tools/create-batch" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i>New Borrow Request
        </a>
    <?php endif; ?>
    <a href="?route=borrowed-tools/print-blank-form" class="btn btn-outline-secondary" target="_blank">
        <i class="bi bi-printer me-1"></i>Print Blank Form
    </a>
</div>

<!-- Quick Stats Summary (Minimal) -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-primary">
            <div class="card-body py-2 px-3">
                <small class="text-muted d-block">Currently Out</small>
                <h4 class="mb-0 text-primary"><?= $borrowedToolStats['borrowed'] ?? 0 ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-danger">
            <div class="card-body py-2 px-3">
                <small class="text-muted d-block">Overdue</small>
                <h4 class="mb-0 text-danger"><?= $borrowedToolStats['overdue'] ?? 0 ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-warning">
            <div class="card-body py-2 px-3">
                <small class="text-muted d-block">Pending Approval</small>
                <h4 class="mb-0 text-warning"><?= ($borrowedToolStats['pending_verification'] ?? 0) + ($borrowedToolStats['pending_approval'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-success">
            <div class="card-body py-2 px-3">
                <small class="text-muted d-block">Available</small>
                <h4 class="mb-0 text-success"><?= $borrowedToolStats['available_equipment'] ?? 0 ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- MVA Workflow Info Banner -->
<div class="alert alert-info mb-4" role="status" aria-label="MVA workflow information">
    <strong><i class="bi bi-info-circle me-2" aria-hidden="true"></i>MVA Workflow:</strong>
    <span class="badge bg-primary">Maker</span> (Warehouseman) →
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-info">Authorizer</span> (Asset/Finance Director) →
    <span class="badge bg-success">Approved</span> →
    <span class="badge bg-primary">Borrowed</span> →
    <span class="badge bg-secondary">Returned</span>
</div>

<!-- Filters -->
<?php include APP_ROOT . '/views/borrowed-tools/partials/_filters.php'; ?>

<!-- Borrowed Tools Table -->
<?php include APP_ROOT . '/views/borrowed-tools/partials/_borrowed_tools_list.php'; ?>

<!-- Overdue Tools Alert -->
<?php if (!empty($overdueTools)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Overdue Tools Alert
                </h6>
            </div>
            <div class="card-body">
                <p class="text-danger mb-3">
                    <strong><?= count($overdueTools) ?> tool(s)</strong> are overdue for return. Please follow up with borrowers.
                </p>
                <div class="row">
                    <?php foreach (array_slice($overdueTools, 0, 6) as $overdueTool): ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                <div>
                                    <strong><?= htmlspecialchars($overdueTool['asset_name']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        Borrowed by: <?= htmlspecialchars($overdueTool['borrower_name']) ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger">
                                        <?= $overdueTool['days_overdue'] ?> days overdue
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($overdueTools) > 6): ?>
                    <p class="text-muted mt-2">And <?= count($overdueTools) - 6 ?> more overdue tools...</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Load borrowed tools module CSS -->
<?php
require_once APP_ROOT . '/helpers/AssetHelper.php';
AssetHelper::loadModuleCSS('borrowed-tools');
?>

<!-- Batch Verification Modal -->
<?php
ob_start();
?>
<input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
<input type="hidden" name="batch_id" value="">

<div class="alert alert-info" role="alert">
    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
    Review all items in this batch and confirm they match the physical equipment on-site.
</div>

<div class="batch-modal-items mb-3">
    <!-- Items will be loaded here via JavaScript -->
</div>

<div class="mb-3">
    <label for="verification_notes" class="form-label">Verification Notes</label>
    <textarea class="form-control"
              id="verification_notes"
              name="verification_notes"
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
    <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Verify Batch
</button>
<?php
$modalActions = ob_get_clean();

$id = 'batchVerifyModal';
$title = 'Verify Batch';
$icon = 'check-circle';
$headerClass = 'bg-warning';
$body = $modalBody;
$actions = $modalActions;
$size = 'lg';
$formAction = 'index.php?route=borrowed-tools/batch/verify';
$formMethod = 'POST';

include APP_ROOT . '/views/components/modal.php';
?>

<!-- Batch Authorization Modal -->
<?php
ob_start();
?>
<input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
<input type="hidden" name="batch_id" value="">

<div class="alert alert-success" role="alert">
    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
    Review all items and authorize this batch for release.
</div>

<div class="batch-modal-items mb-3">
    <!-- Items will be loaded here via JavaScript -->
</div>

<div class="mb-3">
    <label for="approval_notes" class="form-label">Authorization Notes</label>
    <textarea class="form-control"
              id="approval_notes"
              name="approval_notes"
              rows="3"
              placeholder="Optional notes about the authorization"
              aria-describedby="approval_notes_help"></textarea>
    <small id="approval_notes_help" class="form-text text-muted">Add any relevant notes about the authorization</small>
</div>
<?php
$modalBody = ob_get_clean();

ob_start();
?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-success">
    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Authorize Batch
</button>
<?php
$modalActions = ob_get_clean();

$id = 'batchAuthorizeModal';
$title = 'Authorize Batch';
$icon = 'shield-check';
$headerClass = 'bg-success text-white';
$body = $modalBody;
$actions = $modalActions;
$size = 'lg';
$formAction = 'index.php?route=borrowed-tools/batch/approve';

include APP_ROOT . '/views/components/modal.php';
?>

<!-- Batch Release Modal -->
<?php
ob_start();
?>
<input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
<input type="hidden" name="batch_id" value="">

<div class="alert alert-info" role="alert">
    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
    Confirm that all items in this batch are being released to the borrower.
</div>

<div class="batch-modal-items mb-3">
    <!-- Items will be loaded here via JavaScript -->
</div>

<div class="mb-3">
    <label for="release_notes" class="form-label">Release Notes</label>
    <textarea class="form-control"
              id="release_notes"
              name="release_notes"
              rows="3"
              placeholder="Optional notes about the release"
              aria-describedby="release_notes_help"></textarea>
    <small id="release_notes_help" class="form-text text-muted">Add any relevant notes about the release</small>
</div>
<?php
$modalBody = ob_get_clean();

ob_start();
?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-info">
    <i class="bi bi-box-arrow-up me-1" aria-hidden="true"></i>Release Batch
</button>
<?php
$modalActions = ob_get_clean();

$id = 'batchReleaseModal';
$title = 'Release Batch';
$icon = 'box-arrow-up';
$headerClass = 'bg-info text-white';
$body = $modalBody;
$actions = $modalActions;
$size = 'lg';
$formAction = 'index.php?route=borrowed-tools/batch/release';

include APP_ROOT . '/views/components/modal.php';
?>

<!-- Batch Return Modal -->
<div class="modal fade" id="batchReturnModal" tabindex="-1" aria-labelledby="batchReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="batchReturnModalLabel">
                    <i class="bi bi-box-arrow-down me-2" aria-hidden="true"></i>Return Equipment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="batchReturnForm">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="" id="returnCsrfToken">
                    <input type="hidden" name="batch_id" value="" id="returnBatchId">

                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                        Enter the quantity returned for each item. Check the condition of each item.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="batchReturnTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 23%">Equipment</th>
                                    <th style="width: 12%">Reference</th>
                                    <th style="width: 7%" class="text-center">Borrowed</th>
                                    <th style="width: 7%" class="text-center">Returned</th>
                                    <th style="width: 7%" class="text-center">Remaining</th>
                                    <th style="width: 9%" class="text-center">Return Now</th>
                                    <th style="width: 12%">Condition</th>
                                    <th style="width: 12%">Notes</th>
                                    <th style="width: 6%" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="batchReturnItems">
                                <!-- Items will be populated via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-3">
                        <label for="return_notes" class="form-label">Overall Return Notes</label>
                        <textarea class="form-control"
                                  id="return_notes"
                                  name="return_notes"
                                  rows="3"
                                  placeholder="Optional notes about the return"
                                  aria-describedby="return_notes_help"></textarea>
                        <small id="return_notes_help" class="form-text text-muted">Add any relevant notes about the overall return</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="processReturnBtn">
                        <i class="bi bi-box-arrow-down me-1" aria-hidden="true"></i>Process Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Batch Extend Modal -->
<div class="modal fade" id="batchExtendModal" tabindex="-1" aria-labelledby="batchExtendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="batchExtendModalLabel">
                    <i class="bi bi-calendar-plus me-2" aria-hidden="true"></i>Extend Batch Return Date
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="batchExtendForm">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="" id="extendCsrfToken">
                    <input type="hidden" name="batch_id" value="" id="extendBatchId">

                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                        Select which items in this batch to extend. You can extend all items or only specific items that are still borrowed.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="batchExtendTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th style="width: 5%">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               id="selectAllExtend"
                                               aria-label="Select all items"
                                               title="Select All">
                                    </th>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 30%">Equipment</th>
                                    <th style="width: 15%">Reference</th>
                                    <th style="width: 10%" class="text-center">Borrowed</th>
                                    <th style="width: 10%" class="text-center">Remaining</th>
                                    <th style="width: 15%">Current Return Date</th>
                                    <th style="width: 10%">Status</th>
                                </tr>
                            </thead>
                            <tbody id="batchExtendItems">
                                <!-- Items will be populated via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="new_expected_return" class="form-label">
                                    New Expected Return Date <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       class="form-control"
                                       id="new_expected_return"
                                       name="new_expected_return"
                                       required
                                       aria-describedby="new_expected_return_help">
                                <small id="new_expected_return_help" class="text-muted">All selected items will be extended to this date</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="extend_reason" class="form-label">
                            Reason for Extension <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control"
                                  id="extend_reason"
                                  name="reason"
                                  rows="3"
                                  placeholder="Provide reason for extending the return date"
                                  required
                                  aria-describedby="extend_reason_help"></textarea>
                        <small id="extend_reason_help" class="form-text text-muted">Explain why the extension is needed</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info" id="processExtendBtn">
                        <i class="bi bi-calendar-plus me-1" aria-hidden="true"></i>Extend Selected Items
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Incident Report Modal -->
<div class="modal fade" id="quickIncidentModal" tabindex="-1" aria-labelledby="quickIncidentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="quickIncidentModalLabel">
                    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Report Incident
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickIncidentForm">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>" id="incidentCsrfToken">
                    <input type="hidden" name="asset_id" value="" id="incidentAssetId">
                    <input type="hidden" name="borrowed_tool_id" value="" id="incidentBorrowedToolId">

                    <div class="alert alert-info" role="alert">
                        <strong id="incidentEquipmentName"></strong><br>
                        <small>Reference: <code id="incidentAssetRef"></code></small>
                    </div>

                    <div class="mb-3">
                        <label for="incident_type" class="form-label">Incident Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="incident_type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="lost">Lost</option>
                            <option value="damaged">Damaged</option>
                            <option value="stolen">Stolen</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="incident_severity" class="form-label">Severity</label>
                        <select class="form-select" id="incident_severity" name="severity">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="incident_description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control"
                                  id="incident_description"
                                  name="description"
                                  rows="4"
                                  required
                                  placeholder="Describe what happened, when it occurred, and any relevant details..."
                                  aria-describedby="incident_description_help"></textarea>
                        <small id="incident_description_help" class="form-text text-muted">Provide detailed information about the incident</small>
                    </div>

                    <div class="mb-3">
                        <label for="incident_location" class="form-label">Location</label>
                        <input type="text"
                               class="form-control"
                               id="incident_location"
                               name="location"
                               placeholder="Where did this incident occur?"
                               aria-describedby="incident_location_help">
                        <small id="incident_location_help" class="form-text text-muted">Optional: Specify where the incident occurred</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="submitIncidentBtn">
                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Report Incident
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Close borrowed-tools-app container -->
</div>

<!-- Load external JavaScript module -->
<?php
AssetHelper::loadModuleJS('borrowed-tools/init', ['type' => 'module']);
?>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Borrowed Equipment - ConstructLink™';
$pageHeader = 'Borrowed Equipment Requests';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Equipment', 'url' => '?route=borrowed-tools']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
