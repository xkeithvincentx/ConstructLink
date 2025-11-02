<?php
/**
 * Transfers Module - Approve Transfer View
 *
 * Form to approve a transfer request in MVA workflow
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
    <a href="?route=transfers/view&id=<?= $transfer['id'] ?> ?>"
       class="btn btn-outline-secondary btn-sm"
       aria-label="Back to transfer details">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
        <span class="d-none d-sm-inline">Back to Details</span>
    </a>
</div>

<!-- Transfer Information -->
<div class="row">
    <div class="col-lg-8">
        <!-- Transfer Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Transfer Request Summary
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Transfer ID:</strong> #<?= htmlspecialchars($transfer['id']) ?></p>
                        <p><strong>Asset:</strong> <?= htmlspecialchars($transfer['asset_ref']) ?> - <?= htmlspecialchars($transfer['asset_name']) ?></p>
                        <p><strong>Transfer Type:</strong> <?= ucfirst($transfer['transfer_type']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>From:</strong> <?= htmlspecialchars($transfer['from_project_name']) ?></p>
                        <p><strong>To:</strong> <?= htmlspecialchars($transfer['to_project_name']) ?></p>
                        <p><strong>Requested By:</strong> <?= htmlspecialchars($transfer['initiated_by_name']) ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <p><strong>Reason:</strong></p>
                        <p class="text-muted"><?= htmlspecialchars($transfer['reason']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2" aria-hidden="true"></i>Approval Decision
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

                <?php if (canAuthorizeTransfer($transfer, $user)): ?>
                <form method="POST"
                      action="?route=transfers/approve&id=<?= $transfer['id'] ?> ?>"
                      class="needs-validation"
                      novalidate>
                    <?= CSRFProtection::getTokenField() ?>

                    <!-- Approval Notes -->
                    <div class="mb-4">
                        <label for="approval_notes" class="form-label">Approval Notes</label>
                        <textarea class="form-control"
                                  id="approval_notes"
                                  name="approval_notes"
                                  rows="4"
                                  placeholder="Add any notes or conditions for this approval..."
                                  aria-label="Approval notes or conditions"><?= htmlspecialchars($formData['approval_notes'] ?? '') ?></textarea>
                        <div class="form-text">Optional notes about the approval decision</div>
                    </div>

                    <!-- Confirmation -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="confirm_approval"
                                   name="confirm_approval"
                                   value="1"
                                   required
                                   aria-required="true">
                            <label class="form-check-label" for="confirm_approval">
                                <strong>I confirm that I have reviewed this transfer request and approve it for execution.</strong>
                            </label>
                            <div class="invalid-feedback">You must confirm the approval to proceed.</div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="button"
                                class="btn btn-outline-secondary"
                                onclick="history.back()"
                                aria-label="Cancel approval">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-success" aria-label="Approve transfer request">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Approve Transfer
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger mt-4" role="alert">
                    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>You do not have permission to approve this transfer or it is not in an approvable status.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar with Guidelines -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Approval Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Before Approving</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Verify asset availability</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Confirm business justification</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Check project requirements</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Ensure proper authorization</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6>Who Can Approve (MVA Workflow)</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> System Admin: Any transfer</li>
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> Finance Director/Asset Director: If allowed by workflow</li>
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> Only allowed in <strong>Pending Approval</strong> status</li>
                    </ul>
                </div>

                <div class="alert alert-warning" role="alert">
                    <small>
                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                        <strong>Important:</strong> Once approved, the transfer can be executed and the asset will be moved.
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

                <div class="d-grid">
                    <a href="?route=assets/view&id=<?= $transfer['asset_id'] ?> ?>"
                       class="btn btn-outline-primary btn-sm"
                       aria-label="View detailed asset information">
                        <i class="bi bi-eye me-1" aria-hidden="true"></i>View Asset Details
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
$pageTitle = $branding['app_name'] . ' - Approve Transfer';
include APP_ROOT . '/views/layouts/main.php';
?>
