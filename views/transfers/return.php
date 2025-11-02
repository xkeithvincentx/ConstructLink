<?php
/**
 * Transfers Module - Return Asset View
 *
 * Return asset from temporary transfer
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

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
        <strong>Error:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close notification"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Return Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-arrow-return-left me-2" aria-hidden="true"></i>Return Asset to Original Project
                </h5>
            </div>
            <div class="card-body">
                <!-- Transfer Summary -->
                <div class="alert alert-info mb-4" role="alert">
                    <h6 class="alert-heading">
                        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Transfer Summary
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Asset:</strong> <?= htmlspecialchars($transfer['asset_ref']) ?> - <?= htmlspecialchars($transfer['asset_name']) ?><br>
                            <strong>From:</strong> <?= htmlspecialchars($transfer['from_project_name']) ?><br>
                            <strong>To:</strong> <?= htmlspecialchars($transfer['to_project_name']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Transfer Date:</strong> <?= date('M j, Y', strtotime($transfer['transfer_date'])) ?><br>
                            <?php if (!empty($transfer['expected_return'])): ?>
                                <strong>Expected Return:</strong> <?= date('M j, Y', strtotime($transfer['expected_return'])) ?>
                                <?php
                                $today = date('Y-m-d');
                                $expectedReturn = $transfer['expected_return'];
                                if ($expectedReturn < $today && $transfer['return_status'] === 'not_returned'): ?>
                                    <span class="badge bg-danger ms-1">
                                        <?= abs((strtotime($today) - strtotime($expectedReturn)) / (60*60*24)) ?> days overdue
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <br>
                            <strong>Return Status:</strong> <?= ReturnStatusHelper::renderStatusBadge($transfer['return_status'] ?? 'not_returned') ?>
                        </div>
                    </div>
                </div>

                <?php if (canReturnAsset($transfer, $user)): ?>
                <form method="POST" class="needs-validation" novalidate>
                    <?= CSRFProtection::getTokenField() ?>

                    <!-- Return Notes -->
                    <div class="mb-4">
                        <label for="return_notes" class="form-label">Return Initiation Notes</label>
                        <textarea class="form-control"
                                  id="return_notes"
                                  name="return_notes"
                                  rows="4"
                                  placeholder="Add any notes about the asset condition or return process..."
                                  aria-label="Return notes documenting asset condition and return reason"></textarea>
                        <div class="form-text">Optional: Add notes about the asset condition and reason for return initiation.</div>
                    </div>

                    <!-- Confirmation -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="confirm_return"
                                   required
                                   aria-required="true">
                            <label class="form-check-label" for="confirm_return">
                                I confirm that the asset is ready to be returned and will be set to in-transit status.
                            </label>
                            <div class="invalid-feedback">
                                Please confirm the return initiation.
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning" aria-label="Initiate return process">
                            <i class="bi bi-truck me-1" aria-hidden="true"></i>Initiate Return Process
                        </button>
                        <a href="?route=transfers/view&id=<?= $transfer['id'] ?> ?>"
                           class="btn btn-secondary"
                           aria-label="Cancel return">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancel
                        </a>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger mt-4" role="alert">
                    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>You do not have permission to return this asset or it is not in a returnable status.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Transfer Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Transfer Information
                </h6>
            </div>
            <div class="card-body">
                <p><strong>Transfer ID:</strong><br>
                #<?= htmlspecialchars($transfer['id']) ?></p>

                <p><strong>Status:</strong><br>
                <?= TransferHelper::renderStatusBadge($transfer['status']) ?></p>

                <p><strong>Transfer Type:</strong><br>
                <span class="badge bg-info"><?= ucfirst($transfer['transfer_type']) ?></span></p>

                <p><strong>Initiated By:</strong><br>
                <?= htmlspecialchars($transfer['initiated_by_name']) ?></p>

                <?php if (!empty($transfer['approved_by_name'])): ?>
                <p><strong>Approved By:</strong><br>
                <?= htmlspecialchars($transfer['approved_by_name']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Asset Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box-seam me-2" aria-hidden="true"></i>Asset Information
                </h6>
            </div>
            <div class="card-body">
                <p><strong>Reference:</strong><br>
                <?= htmlspecialchars($transfer['asset_ref']) ?></p>

                <p><strong>Name:</strong><br>
                <?= htmlspecialchars($transfer['asset_name']) ?></p>

                <p><strong>Category:</strong><br>
                <?= htmlspecialchars($transfer['category_name'] ?? 'Unknown') ?></p>

                <p><strong>Current Status:</strong><br>
                <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $transfer['asset_status'])) ?></span></p>
            </div>
        </div>

        <!-- Important Notice -->
        <div class="card">
            <div class="card-header bg-warning">
                <h6 class="card-title mb-0 text-dark">
                    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Important Notice
                </h6>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Enhanced Return Process:</strong></p>
                <ul class="small mb-0">
                    <li>Asset will be set to 'in-transit' status during return</li>
                    <li>Origin project manager must confirm receipt</li>
                    <li>Asset will be available at origin only after receipt confirmation</li>
                    <li>Complete audit trail maintained throughout process</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Return Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Who Can Return (MVA Workflow)</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> System Admin: Any transfer</li>
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> Asset Director/Project Manager: If allowed by workflow</li>
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> Only allowed in <strong>Completed</strong> status for temporary transfers</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Transfers Module JS -->
<script src="<?= ASSETS_URL ?>/js/modules/transfers.js"></script>

<?php
$content = ob_get_clean();
$pageTitle = $branding['app_name'] . ' - Initiate Asset Return';
include APP_ROOT . '/views/layouts/main.php';
?>
