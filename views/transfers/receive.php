<?php
/**
 * Transfers Module - Receive Transfer View
 *
 * TO Project Manager receives and completes transfer in one step
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
        <span class="d-none d-sm-inline">Back to Transfer</span>
    </a>
</div>

<!-- Transfer Information -->
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Transfer Details
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Transfer ID:</strong><br>
                        <span class="text-muted">#<?= htmlspecialchars($transfer['id']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <?= TransferHelper::renderStatusBadge($transfer['status']) ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Asset:</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($transfer['asset_name']) ?> (<?= htmlspecialchars($transfer['asset_ref']) ?>)</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Transfer Type:</strong><br>
                        <span class="text-muted"><?= ucfirst($transfer['transfer_type']) ?></span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>From Project:</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($transfer['from_project_name']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>To Project:</strong><br>
                        <span class="text-muted"><?= htmlspecialchars($transfer['to_project_name']) ?></span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Reason for Transfer:</strong><br>
                        <p class="text-muted mt-1"><?= htmlspecialchars($transfer['reason']) ?></p>
                    </div>
                </div>

                <?php if (!empty($transfer['notes'])): ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Notes:</strong><br>
                        <p class="text-muted mt-1"><?= htmlspecialchars($transfer['notes']) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Receipt Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-check-circle me-2" aria-hidden="true"></i>Confirm Receipt
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (canReceiveTransfer($transfer, $user)): ?>
                <form method="POST" action="?route=transfers/receive&id=<?= $transfer['id'] ?> ?>">
                    <?= CSRFProtection::getTokenField() ?>

                    <div class="mb-3">
                        <label for="receipt_notes" class="form-label">Completion Notes</label>
                        <textarea class="form-control"
                                  id="receipt_notes"
                                  name="receipt_notes"
                                  rows="4"
                                  placeholder="Confirm asset condition and completion details..."
                                  aria-label="Receipt notes confirming asset condition and completion"><?= htmlspecialchars($_POST['receipt_notes'] ?? '') ?></textarea>
                        <div class="form-text">
                            Document the condition of the asset and confirm it has been received at the destination project.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success" aria-label="Complete transfer and confirm receipt">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Complete Transfer
                        </button>
                        <a href="?route=transfers/view&id=<?= $transfer['id'] ?> ?>"
                           class="btn btn-outline-secondary"
                           aria-label="Cancel receipt">
                            Cancel
                        </a>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>You do not have permission to receive this transfer or it is not in a receivable status.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Receipt Guidelines -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2" aria-hidden="true"></i>Receipt Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <h6 class="alert-heading">As a Site Inventory Clerk, verify:</h6>
                    <ul class="mb-0">
                        <li>The asset matches the transfer description</li>
                        <li>The asset is in the expected condition</li>
                        <li>All accessories/parts are included</li>
                        <li>Document any damage or missing items</li>
                        <li>Confirm proper storage location</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Transfer Timeline -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2" aria-hidden="true"></i>Current Status
                </h6>
            </div>
            <div class="card-body">
                <?php include __DIR__ . '/_timeline.php'; ?>
            </div>
        </div>
    </div>
</div>

<!-- Include Transfers Module JS -->
<script src="<?= ASSETS_URL ?>/js/modules/transfers.js"></script>

<?php
$content = ob_get_clean();
$pageTitle = $branding['app_name'] . ' - Receive Transfer';
include APP_ROOT . '/views/layouts/main.php';
?>
