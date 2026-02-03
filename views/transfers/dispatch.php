<?php
/**
 * Transfers Module - Dispatch Transfer View
 *
 * Form to dispatch an approved transfer
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

        <!-- Dispatch Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-send me-2" aria-hidden="true"></i>Dispatch Confirmation
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

                <?php if (canDispatchTransfer($transfer, $user)): ?>
                <form method="POST"
                      action="?route=transfers/dispatch&id=<?= $transfer['id'] ?> ?>"
                      class="needs-validation"
                      novalidate>
                    <?= CSRFProtection::getTokenField() ?>

                    <!-- Dispatch Notes -->
                    <div class="mb-4">
                        <label for="dispatch_notes" class="form-label">Dispatch Notes</label>
                        <textarea class="form-control"
                                  id="dispatch_notes"
                                  name="dispatch_notes"
                                  rows="4"
                                  placeholder="Add notes about the dispatch (e.g., courier details, tracking number, condition notes)..."
                                  aria-label="Dispatch notes including courier and tracking details"><?= htmlspecialchars($formData['dispatch_notes'] ?? '') ?></textarea>
                        <div class="form-text">Add any relevant dispatch or shipping details</div>
                    </div>

                    <!-- Confirmation -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="confirm_dispatch"
                                   name="confirm_dispatch"
                                   value="1"
                                   required
                                   aria-required="true">
                            <label class="form-check-label" for="confirm_dispatch">
                                <strong>I confirm that the asset has been dispatched from <?= htmlspecialchars($transfer['from_project_name']) ?> and is now in transit to <?= htmlspecialchars($transfer['to_project_name']) ?>.</strong>
                            </label>
                            <div class="invalid-feedback">You must confirm the dispatch to proceed.</div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="button"
                                class="btn btn-outline-secondary"
                                onclick="history.back()"
                                aria-label="Cancel dispatch">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" aria-label="Confirm dispatch of asset">
                            <i class="bi bi-send me-1" aria-hidden="true"></i>Confirm Dispatch
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger mt-4" role="alert">
                    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>You do not have permission to dispatch this transfer or it is not in an approved status.
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
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Dispatch Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Before Dispatching</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Verify asset condition</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Pack asset securely</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Document courier details</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Obtain tracking information</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6>Who Can Dispatch</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> System Admin: Any transfer</li>
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> Site Inventory Clerk: From own project</li>
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> Only allowed in <strong>Approved</strong> status</li>
                    </ul>
                </div>

                <div class="alert alert-warning" role="alert">
                    <small>
                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                        <strong>Important:</strong> Once dispatched, the asset will be marked as in-transit until received.
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
                    <a href="?route=assets/view&id=<?= $transfer['inventory_item_id'] ?>"
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
$pageTitle = $branding['app_name'] . ' - Dispatch Transfer';
include APP_ROOT . '/views/layouts/main.php';
?>
