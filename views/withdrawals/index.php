<?php
/**
 * Consumable Withdrawals Index View
 *
 * REFACTORED: Matches borrowed-tools pattern with Alpine.js filters,
 * unified single/batch display, and clean minimal design
 */

// Start output buffering to capture content
ob_start();

// Load ViewHelper for reusable components
require_once APP_ROOT . '/helpers/ViewHelper.php';

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';

// Group withdrawals by batch_id for batch display
$groupedWithdrawals = [];
$singleWithdrawals = [];

foreach ($withdrawals as $withdrawal) {
    if (!empty($withdrawal['batch_id'])) {
        if (!isset($groupedWithdrawals[$withdrawal['batch_id']])) {
            $groupedWithdrawals[$withdrawal['batch_id']] = [];
        }
        $groupedWithdrawals[$withdrawal['batch_id']][] = $withdrawal;
    } else {
        $singleWithdrawals[] = $withdrawal;
    }
}

// Merge grouped batches and single items for display
$displayItems = [];
foreach ($groupedWithdrawals as $batchId => $batchItems) {
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
foreach ($singleWithdrawals as $withdrawal) {
    $displayItems[] = [
        'type' => 'single',
        'item' => $withdrawal
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

<!-- Withdrawals Module Container with Configuration -->
<div id="withdrawals-app"
     x-data="withdrawalsIndexApp()"
     data-csrf-token="<?= htmlspecialchars($csrfToken) ?>">

<!-- Action Buttons -->
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <!-- Desktop: Action Buttons -->
    <div class="d-none d-md-flex gap-2">
        <?php if (hasPermission('withdrawals/create')): ?>
            <a href="?route=withdrawals/create-batch"
               class="btn btn-success btn-sm"
               aria-label="Create new withdrawal request">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>New Withdrawal
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
    <?php if (hasPermission('withdrawals/create')): ?>
        <a href="?route=withdrawals/create-batch" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i>New Withdrawal Request
        </a>
    <?php endif; ?>
</div>

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
            <li><strong>Maker</strong> (Warehouseman) creates withdrawal request for consumables</li>
            <li><strong>Verifier</strong> (Site Inventory Clerk) verifies consumables match inventory records</li>
            <li><strong>Authorizer</strong> (Project Manager) approves usage for project requirements
                <span class="badge bg-primary ms-2">Quantities Reserved</span>
            </li>
            <li>Warehouseman releases consumables (physically hands over to receiver)</li>
            <li><em>Optional:</em> If consumables are unused, they can be returned (quantity restored)</li>
        </ol>
        <div class="alert alert-warning mt-3 mb-0">
            <strong>Important:</strong> Inventory quantities are <strong>reserved at approval</strong> (step 3), not at release.
            This ensures items are committed when the Authorizer approves, preventing double-booking.
        </div>
    </div>
</div>

<!-- Filters with Alpine.js -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-funnel me-2" aria-hidden="true"></i>Filters
        </h6>
        <button type="button"
                class="btn btn-sm btn-link text-decoration-none p-0"
                @click="clearFilters()"
                x-show="filters.status || filters.receiver || filters.dateFrom || filters.dateTo"
                x-transition>
            <i class="bi bi-x-circle me-1"></i>Clear All
        </button>
    </div>
    <div class="card-body">
        <form @submit.prevent="applyFilters()">
            <div class="row g-3">
                <!-- Status Filter -->
                <div class="col-lg-3 col-md-6">
                    <label for="filterStatus" class="form-label">
                        <i class="bi bi-funnel-fill me-1 text-muted" aria-hidden="true"></i>Status
                    </label>
                    <select id="filterStatus"
                            class="form-select"
                            x-model="filters.status"
                            @change="applyFilters()">
                        <option value="">All Statuses</option>
                        <option value="Pending Verification">Pending Verification</option>
                        <option value="Pending Approval">Pending Approval</option>
                        <option value="Approved">Approved</option>
                        <option value="Released">Released</option>
                        <option value="Returned">Returned</option>
                        <option value="Canceled">Canceled</option>
                    </select>
                </div>

                <!-- Receiver Search -->
                <div class="col-lg-3 col-md-6">
                    <label for="filterReceiver" class="form-label">
                        <i class="bi bi-person me-1 text-muted" aria-hidden="true"></i>Receiver
                    </label>
                    <div class="input-group">
                        <input type="text"
                               id="filterReceiver"
                               class="form-control"
                               x-model="filters.receiver"
                               placeholder="Search receiver..."
                               @keyup.enter="applyFilters()">
                        <button class="btn btn-outline-secondary"
                                type="button"
                                @click="filters.receiver = ''; applyFilters()"
                                x-show="filters.receiver"
                                x-transition
                                aria-label="Clear receiver filter">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>

                <!-- Date From -->
                <div class="col-lg-2 col-md-6">
                    <label for="filterDateFrom" class="form-label">
                        <i class="bi bi-calendar-event me-1 text-muted" aria-hidden="true"></i>From
                    </label>
                    <input type="date"
                           id="filterDateFrom"
                           class="form-control"
                           x-model="filters.dateFrom"
                           @change="applyFilters()">
                </div>

                <!-- Date To -->
                <div class="col-lg-2 col-md-6">
                    <label for="filterDateTo" class="form-label">
                        <i class="bi bi-calendar-check me-1 text-muted" aria-hidden="true"></i>To
                    </label>
                    <input type="date"
                           id="filterDateTo"
                           class="form-control"
                           x-model="filters.dateTo"
                           @change="applyFilters()">
                </div>

                <!-- Actions -->
                <div class="col-lg-2 col-md-12">
                    <label class="form-label d-none d-lg-block">&nbsp;</label>
                    <button type="submit"
                            class="btn btn-primary w-100">
                        <i class="bi bi-search me-1" aria-hidden="true"></i>Apply Filters
                    </button>
                </div>
            </div>

            <!-- Active Filters Display -->
            <div x-show="filters.status || filters.receiver || filters.dateFrom || filters.dateTo"
                 x-transition
                 class="mt-3 pt-3 border-top">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <small class="text-muted fw-semibold">Active Filters:</small>

                    <span x-show="filters.status"
                          x-transition
                          class="badge bg-primary">
                        Status: <span x-text="filters.status"></span>
                        <button type="button"
                                class="btn-close btn-close-white ms-1"
                                style="font-size: 0.6rem;"
                                @click="filters.status = ''; applyFilters()"
                                aria-label="Remove status filter"></button>
                    </span>

                    <span x-show="filters.receiver"
                          x-transition
                          class="badge bg-info">
                        Receiver: <span x-text="filters.receiver"></span>
                        <button type="button"
                                class="btn-close btn-close-white ms-1"
                                style="font-size: 0.6rem;"
                                @click="filters.receiver = ''; applyFilters()"
                                aria-label="Remove receiver filter"></button>
                    </span>

                    <span x-show="filters.dateFrom"
                          x-transition
                          class="badge bg-success">
                        From: <span x-text="filters.dateFrom"></span>
                        <button type="button"
                                class="btn-close btn-close-white ms-1"
                                style="font-size: 0.6rem;"
                                @click="filters.dateFrom = ''; applyFilters()"
                                aria-label="Remove date from filter"></button>
                    </span>

                    <span x-show="filters.dateTo"
                          x-transition
                          class="badge bg-warning">
                        To: <span x-text="filters.dateTo"></span>
                        <button type="button"
                                class="btn-close btn-close-white ms-1"
                                style="font-size: 0.6rem;"
                                @click="filters.dateTo = ''; applyFilters()"
                                aria-label="Remove date to filter"></button>
                    </span>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Withdrawals Table -->
<?php include APP_ROOT . '/views/withdrawals/partials/_withdrawals_list.php'; ?>

<!-- Withdrawal Verification Modal -->
<?php
ob_start();
?>
<input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
<input type="hidden" name="batch_id" value="">
<input type="hidden" name="is_single_item" value="0" id="verifyIsSingleItem">

<div class="alert alert-info" role="alert">
    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
    Review all items in this withdrawal batch and confirm they match inventory records.
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

$id = 'withdrawalVerifyModal';
$title = 'Verify Withdrawal Batch';
$icon = 'check-circle';
$headerClass = 'bg-warning';
$body = $modalBody;
$actions = $modalActions;
$size = 'lg';
$formAction = 'index.php?route=withdrawals/batch/verify';
$formMethod = 'POST';

include APP_ROOT . '/views/components/modal.php';
?>

<!-- Withdrawal Approval Modal -->
<?php
ob_start();
?>
<input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
<input type="hidden" name="batch_id" value="">
<input type="hidden" name="is_single_item" value="0" id="approveIsSingleItem">

<div class="alert alert-success" role="alert">
    <i class="bi bi-shield-check me-2" aria-hidden="true"></i>
    <strong>Approval Authorization:</strong> Review all items and approve this withdrawal batch.
</div>
<div class="alert alert-warning" role="alert">
    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
    <strong>Important:</strong> Approving this request will <strong>reserve quantities</strong> from inventory immediately.
    Quantities shown as "Available Now" reflect real-time inventory. If another user has withdrawn items since this request was created, approval may fail.
</div>

<div class="batch-modal-items mb-3">
    <!-- Items will be loaded here via JavaScript -->
</div>

<div class="mb-3">
    <label for="approval_notes" class="form-label">Approval Notes</label>
    <textarea class="form-control"
              id="approval_notes"
              name="approval_notes"
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
    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Approve Batch
</button>
<?php
$modalActions = ob_get_clean();

$id = 'withdrawalApproveModal';
$title = 'Approve Withdrawal Batch';
$icon = 'shield-check';
$headerClass = 'bg-success text-white';
$body = $modalBody;
$actions = $modalActions;
$size = 'lg';
$formAction = 'index.php?route=withdrawals/batch/approve';

include APP_ROOT . '/views/components/modal.php';
?>

<!-- Withdrawal Release Modal -->
<?php
ob_start();
?>
<input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
<input type="hidden" name="batch_id" value="">
<input type="hidden" name="is_single_item" value="0" id="releaseIsSingleItem">

<div class="alert alert-info" role="alert">
    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
    <span id="releaseModalDescription">Confirm that all consumables in this batch are being physically released to the receiver. <strong>Note:</strong> Quantities were already reserved at approval - this step confirms physical handover.</span>
</div>

<div class="batch-modal-items mb-3">
    <!-- Items will be loaded here via JavaScript -->
</div>

<!-- Release Checklist -->
<div class="card bg-light mb-3">
    <div class="card-header">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-clipboard-check me-2" aria-hidden="true"></i>Release Verification Checklist
        </h6>
    </div>
    <div class="card-body">
        <div class="form-check">
            <input class="form-check-input"
                   type="checkbox"
                   id="check_receiver_verified"
                   name="check_receiver_verified"
                   required>
            <label class="form-check-label" for="check_receiver_verified">
                <strong>Receiver identity verified and matches request</strong>
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input"
                   type="checkbox"
                   id="check_quantity_verified"
                   name="check_quantity_verified"
                   required>
            <label class="form-check-label" for="check_quantity_verified">
                <strong>Quantities verified against inventory records</strong>
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input"
                   type="checkbox"
                   id="check_condition_documented"
                   name="check_condition_documented"
                   required>
            <label class="form-check-label" for="check_condition_documented">
                <strong>Consumable condition documented</strong>
            </label>
        </div>

        <hr class="my-3">

        <div class="form-check">
            <input class="form-check-input"
                   type="checkbox"
                   id="check_receiver_acknowledged"
                   name="check_receiver_acknowledged"
                   required>
            <label class="form-check-label" for="check_receiver_acknowledged">
                <strong>Receiver acknowledged receipt and responsibility</strong>
            </label>
        </div>
    </div>
</div>

<!-- Release Notes -->
<div class="mb-3">
    <label for="release_notes" class="form-label">Release Notes</label>
    <textarea class="form-control"
              id="release_notes"
              name="release_notes"
              rows="3"
              placeholder="Document the condition of consumables and any special instructions"
              aria-describedby="release_notes_help"></textarea>
    <small id="release_notes_help" class="form-text text-muted">Add any relevant notes about the release process</small>
</div>

<!-- Warning Message -->
<div class="alert alert-warning mb-0" role="alert">
    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
    <strong>Confirmation:</strong> By completing this release, you confirm that consumables have been physically handed over to the receiver. Inventory quantities were reserved at approval.
</div>
<?php
$modalBody = ob_get_clean();

ob_start();
?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-info" id="completeReleaseBtn">
    <i class="bi bi-box-arrow-up me-1" aria-hidden="true"></i>Complete Release
</button>
<?php
$modalActions = ob_get_clean();

$id = 'withdrawalReleaseModal';
$title = 'Release Consumables';
$icon = 'box-arrow-up';
$headerClass = 'bg-info text-white';
$body = $modalBody;
$actions = $modalActions;
$size = 'xl';
$formAction = 'index.php?route=withdrawals/batch/release';

include APP_ROOT . '/views/components/modal.php';
?>

<!-- Withdrawal Return Modal -->
<div class="modal fade" id="withdrawalReturnModal" tabindex="-1" aria-labelledby="withdrawalReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="withdrawalReturnModalLabel">
                    <i class="bi bi-box-arrow-down me-2" aria-hidden="true"></i>Return Consumables
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="withdrawalReturnForm">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>" id="returnCsrfToken">
                    <input type="hidden" name="batch_id" value="" id="returnBatchId">
                    <input type="hidden" name="is_single_item" value="0" id="returnIsSingleItem">

                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                        <strong>Return Instructions:</strong> Enter the quantity being returned for each item. Partial returns are allowed. Select condition and add notes as needed.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="withdrawalReturnTable">
                            <thead class="table-secondary">
                                <tr>
                                    <th class="col-return-num">#</th>
                                    <th class="col-return-equipment">Consumable</th>
                                    <th class="col-return-reference">Reference</th>
                                    <th class="col-return-withdrawn text-center">Qty Withdrawn</th>
                                    <th class="col-return-returning text-center">Returning Now</th>
                                    <th class="col-return-condition">Condition</th>
                                    <th class="col-return-notes">Notes</th>
                                </tr>
                            </thead>
                            <tbody id="withdrawalReturnItems">
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

<!-- Withdrawal Cancel Modal -->
<div class="modal fade" id="withdrawalCancelModal" tabindex="-1" aria-labelledby="withdrawalCancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="withdrawalCancelModalLabel">
                    <i class="bi bi-x-circle me-2" aria-hidden="true"></i>Cancel Withdrawal
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="withdrawalCancelForm" method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="withdrawal_id" value="" id="cancelWithdrawalId">
                    <input type="hidden" name="is_batch" value="0" id="cancelIsBatch">

                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
                        <strong>Warning:</strong> This action cannot be undone. Are you sure you want to cancel this withdrawal?
                    </div>

                    <div class="mb-3">
                        <label for="cancel_reason" class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                        <select class="form-select" id="cancel_reason" name="cancellation_reason" required>
                            <option value="">Select a reason</option>
                            <option value="no_longer_needed">No longer needed</option>
                            <option value="wrong_item">Wrong item requested</option>
                            <option value="duplicate_request">Duplicate request</option>
                            <option value="project_delayed">Project delayed/postponed</option>
                            <option value="budget_constraints">Budget constraints</option>
                            <option value="other">Other (specify below)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="cancelCustomReasonDiv" style="display: none;">
                        <label for="cancel_custom_reason" class="form-label">Please specify <span class="text-danger">*</span></label>
                        <textarea class="form-control"
                                  id="cancel_custom_reason"
                                  name="custom_reason"
                                  rows="2"
                                  placeholder="Please provide details..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="cancel_notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control"
                                  id="cancel_notes"
                                  name="reason"
                                  rows="3"
                                  placeholder="Optional: Provide any additional context..."
                                  aria-describedby="cancel_notes_help"></textarea>
                        <small id="cancel_notes_help" class="form-text text-muted">Add any relevant notes about the cancellation</small>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmCancel" required>
                        <label class="form-check-label" for="confirmCancel">
                            I understand this action cannot be undone
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Withdrawal</button>
                    <button type="submit" class="btn btn-danger" id="confirmCancelBtn">
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancel Withdrawal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<!-- Load Alpine.js component -->
<script type="module">
import { withdrawalsIndexApp } from '/assets/js/withdrawals/withdrawals-index.js';
window.withdrawalsIndexApp = withdrawalsIndexApp;
</script>

<!-- Refresh functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            window.location.reload();
        });
    }

    // Cancel modal functionality
    const cancelModal = document.getElementById('withdrawalCancelModal');
    if (cancelModal) {
        cancelModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const withdrawalId = button.getAttribute('data-withdrawal-id');
            const isBatch = button.getAttribute('data-is-batch') === 'true';

            // Update form fields
            document.getElementById('cancelWithdrawalId').value = withdrawalId;
            document.getElementById('cancelIsBatch').value = isBatch ? '1' : '0';

            // Set form action
            const form = document.getElementById('withdrawalCancelForm');
            if (isBatch) {
                form.action = '?route=withdrawals/batch/cancel&id=' + withdrawalId;
            } else {
                form.action = '?route=withdrawals/cancel&id=' + withdrawalId;
            }

            // Reset form
            form.reset();
            document.getElementById('cancelCustomReasonDiv').style.display = 'none';
        });

        // Toggle custom reason field
        document.getElementById('cancel_reason').addEventListener('change', function() {
            const customReasonDiv = document.getElementById('cancelCustomReasonDiv');
            const customReasonField = document.getElementById('cancel_custom_reason');

            if (this.value === 'other') {
                customReasonDiv.style.display = 'block';
                customReasonField.required = true;
            } else {
                customReasonDiv.style.display = 'none';
                customReasonField.required = false;
                customReasonField.value = '';
            }
        });
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Consumable Withdrawals - ConstructLink™';
$pageHeader = 'Consumable Withdrawals';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Consumable Withdrawals', 'url' => '?route=withdrawals']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
