<?php
/**
 * Transfers Module - Cancel Transfer View
 *
 * Form to cancel a transfer request (MVA RBAC refactored)
 *
 * @package ConstructLink
 * @subpackage Transfers
 * @version 2.0
 */

// Load transfer-specific helpers
require_once APP_ROOT . '/core/TransferHelper.php';
require_once APP_ROOT . '/core/ReturnStatusHelper.php';
require_once APP_ROOT . '/core/InputValidator.php';
require_once APP_ROOT . '/helpers/BrandingHelper.php';

// Get branding
$branding = BrandingHelper::loadBranding();

// Load module CSS
$moduleCSS = ['/assets/css/modules/transfers.css'];

// Start output buffering
ob_start();
?>

<!-- Action Buttons -->
<div class="d-flex justify-content-end align-items-center mb-4">
    <a href="?route=transfers/view&id=<?= $transfer['id'] ?>"
       class="btn btn-outline-secondary btn-sm"
       aria-label="Back to transfer details">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
        <span class="d-none d-sm-inline">Back to Transfer</span>
    </a>
</div>

<!-- Transfer Information -->
<div class="row">
    <div class="col-lg-8">
        <!-- Transfer Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Transfer Request Details
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Transfer ID:</strong> #<?= htmlspecialchars($transfer['id']) ?></p>
                        <p><strong>Asset:</strong> <?= htmlspecialchars($transfer['asset_ref']) ?> - <?= htmlspecialchars($transfer['asset_name']) ?></p>
                        <p><strong>Transfer Type:</strong> <?= ucfirst($transfer['transfer_type']) ?></p>
                        <p><strong>Current Status:</strong> <?= TransferHelper::renderStatusBadge($transfer['status']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>From:</strong> <?= htmlspecialchars($transfer['from_project_name']) ?></p>
                        <p><strong>To:</strong> <?= htmlspecialchars($transfer['to_project_name']) ?></p>
                        <p><strong>Requested By:</strong> <?= htmlspecialchars($transfer['initiated_by_name']) ?></p>
                        <p><strong>Request Date:</strong> <?= date('M j, Y', strtotime($transfer['created_at'])) ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <p><strong>Original Reason:</strong></p>
                        <p class="text-muted"><?= htmlspecialchars($transfer['reason']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancellation Form -->
        <?php if (canCancelTransfer($transfer, $user)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-x-circle me-2" aria-hidden="true"></i>Cancellation Details
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <h6><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST"
                      action="?route=transfers/cancel&id=<?= $transfer['id'] ?>"
                      class="needs-validation"
                      novalidate>
                    <?= CSRFProtection::getTokenField() ?>

                    <!-- Cancellation Reason -->
                    <div class="mb-4">
                        <label for="cancel_reason" class="form-label">Reason for Cancellation <span class="text-danger">*</span></label>
                        <textarea class="form-control"
                                  id="cancel_reason"
                                  name="cancel_reason"
                                  rows="4"
                                  required
                                  placeholder="Please explain why this transfer request is being canceled..."
                                  aria-label="Reason for canceling transfer request"
                                  aria-required="true"><?= htmlspecialchars($formData['cancel_reason'] ?? '') ?></textarea>
                        <div class="form-text">Provide a clear explanation for the cancellation</div>
                        <div class="invalid-feedback">Please provide a reason for cancellation.</div>
                    </div>

                    <!-- Cancellation Impact Warning -->
                    <div class="alert alert-warning" role="alert">
                        <h6><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Cancellation Impact</h6>
                        <p class="mb-2">Canceling this transfer will:</p>
                        <ul class="mb-0">
                            <li>Mark the transfer request as "Canceled"</li>
                            <li>Keep the asset at its current location</li>
                            <li>Notify relevant parties of the cancellation</li>
                            <li>Prevent any further action on this transfer</li>
                            <?php if ($transfer['status'] === 'Approved'): ?>
                                <li><strong>Note:</strong> This transfer has already been approved</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Confirmation -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="confirm_cancellation"
                                   name="confirm_cancellation"
                                   value="1"
                                   required
                                   aria-required="true">
                            <label class="form-check-label" for="confirm_cancellation">
                                <strong>I understand that this action cannot be undone and confirm the cancellation of this transfer request.</strong>
                            </label>
                            <div class="invalid-feedback">You must confirm the cancellation to proceed.</div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="button"
                                class="btn btn-outline-secondary"
                                onclick="history.back()"
                                aria-label="Go back without canceling">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Go Back
                        </button>
                        <button type="submit" class="btn btn-danger" aria-label="Cancel transfer request">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancel Transfer Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-danger mt-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>You do not have permission to cancel this transfer or it is not in a cancelable status.
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar with Guidelines -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Cancellation Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Valid Reasons for Cancellation</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Asset no longer needed</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Project requirements changed</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Asset became unavailable</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Alternative solution found</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Budget constraints</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6>Who Can Cancel (MVA Workflow)</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> System Admin: Any transfer</li>
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> Request Initiator: Own requests (if still pending)</li>
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> Project Manager: Project transfers (if still pending)</li>
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> Only allowed in <strong>Pending Verification</strong> or <strong>Pending Approval</strong> status</li>
                    </ul>
                </div>

                <div class="alert alert-info" role="alert">
                    <small>
                        <i class="bi bi-lightbulb me-1" aria-hidden="true"></i>
                        <strong>Alternative:</strong> Consider modifying the transfer instead of canceling if the requirements have only slightly changed.
                    </small>
                </div>
            </div>
        </div>

        <!-- Asset Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box-seam me-2" aria-hidden="true"></i>Asset Details
                </h6>
            </div>
            <div class="card-body">
                <p><strong>Reference:</strong><br><?= htmlspecialchars($transfer['asset_ref']) ?></p>
                <p><strong>Name:</strong><br><?= htmlspecialchars($transfer['asset_name']) ?></p>
                <p><strong>Category:</strong><br><?= htmlspecialchars($transfer['category_name'] ?? 'Unknown') ?></p>
                <p><strong>Current Status:</strong><br>
                    <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $transfer['asset_status'])) ?></span>
                </p>
                <p><strong>Current Location:</strong><br><?= htmlspecialchars($transfer['from_project_name']) ?></p>

                <div class="d-grid">
                    <a href="?route=assets/view&id=<?= $transfer['asset_id'] ?>"
                       class="btn btn-outline-primary btn-sm"
                       aria-label="View detailed asset information">
                        <i class="bi bi-eye me-1" aria-hidden="true"></i>View Asset Details
                    </a>
                </div>
            </div>
        </div>

        <!-- Alternative Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2" aria-hidden="true"></i>Alternative Actions
                </h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Instead of canceling, you might consider:</p>
                <div class="d-grid gap-2">
                    <a href="?route=transfers/create"
                       class="btn btn-outline-success btn-sm"
                       aria-label="Create new transfer request">
                        <i class="bi bi-plus me-1" aria-hidden="true"></i>Create New Transfer
                    </a>
                    <a href="?route=transfers"
                       class="btn btn-outline-info btn-sm"
                       aria-label="View all transfers">
                        <i class="bi bi-list me-1" aria-hidden="true"></i>View All Transfers
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Transfers Module JS -->
<script src="<?= ASSETS_URL ?>/js/modules/transfers.js"></script>

<?php
$content = ob_get_clean();
$pageTitle = $branding['app_name'] . ' - Cancel Transfer';
include APP_ROOT . '/views/layouts/main.php';
?>
