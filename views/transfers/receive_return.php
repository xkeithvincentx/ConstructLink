<?php
/**
 * Transfers Module - Receive Return View
 *
 * Receive returned asset at origin project
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
        <!-- Receipt Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-box-arrow-in-down me-2" aria-hidden="true"></i>Confirm Asset Receipt at Origin Project
                </h5>
            </div>
            <div class="card-body">
                <!-- Return Transit Summary -->
                <div class="alert alert-warning mb-4" role="alert">
                    <h6 class="alert-heading">
                        <i class="bi bi-truck me-2" aria-hidden="true"></i>Return Transit Details
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Asset:</strong> <?= htmlspecialchars($transfer['asset_ref']) ?> - <?= htmlspecialchars($transfer['asset_name']) ?><br>
                            <strong>Returning From:</strong> <?= htmlspecialchars($transfer['to_project_name']) ?><br>
                            <strong>Returning To:</strong> <?= htmlspecialchars($transfer['from_project_name']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Return Initiated:</strong>
                            <?php if (!empty($transfer['return_initiation_date'])): ?>
                                <?= date('M j, Y g:i A', strtotime($transfer['return_initiation_date'])) ?>
                            <?php else: ?>
                                <span class="text-muted">Not available</span>
                            <?php endif; ?><br>

                            <strong>Days in Transit:</strong>
                            <?php if (!empty($transfer['return_initiation_date'])): ?>
                                <?php
                                $daysInTransit = floor((time() - strtotime($transfer['return_initiation_date'])) / (60*60*24));
                                $badgeClass = $daysInTransit > 3 ? 'bg-danger' : ($daysInTransit > 1 ? 'bg-warning text-dark' : 'bg-success');
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $daysInTransit ?> day<?= $daysInTransit != 1 ? 's' : '' ?></span>
                            <?php else: ?>
                                <span class="text-muted">Unknown</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($transfer['return_notes'])): ?>
                        <hr>
                        <strong>Return Notes:</strong><br>
                        <div class="small"><?= nl2br(htmlspecialchars($transfer['return_notes'])) ?></div>
                    <?php endif; ?>
                </div>

                <?php if (canReceiveReturn($transfer, $user)): ?>
                <form method="POST" class="needs-validation" novalidate>
                    <?= CSRFProtection::getTokenField() ?>

                    <!-- Asset Condition Assessment -->
                    <div class="mb-4">
                        <label class="form-label">Asset Condition Assessment <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="asset_condition"
                                           id="condition_good"
                                           value="good"
                                           required
                                           aria-required="true">
                                    <label class="form-check-label" for="condition_good">
                                        <i class="bi bi-check-circle text-success me-1" aria-hidden="true"></i>Good Condition
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="asset_condition"
                                           id="condition_fair"
                                           value="fair"
                                           required
                                           aria-required="true">
                                    <label class="form-check-label" for="condition_fair">
                                        <i class="bi bi-exclamation-circle text-warning me-1" aria-hidden="true"></i>Fair Condition
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="asset_condition"
                                           id="condition_damaged"
                                           value="damaged"
                                           required
                                           aria-required="true">
                                    <label class="form-check-label" for="condition_damaged">
                                        <i class="bi bi-exclamation-triangle text-danger me-1" aria-hidden="true"></i>Damaged
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="invalid-feedback">
                            Please assess the asset condition.
                        </div>
                    </div>

                    <!-- Receipt Notes -->
                    <div class="mb-4">
                        <label for="receipt_notes" class="form-label">Receipt Notes</label>
                        <textarea class="form-control"
                                  id="receipt_notes"
                                  name="receipt_notes"
                                  rows="4"
                                  placeholder="Document asset condition, any issues found, or other relevant information..."
                                  aria-label="Receipt notes documenting asset condition upon return"></textarea>
                        <div class="form-text">Document the asset condition upon receipt and any observations.</div>
                    </div>

                    <!-- Receipt Confirmation -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="confirm_receipt"
                                   required
                                   aria-required="true">
                            <label class="form-check-label" for="confirm_receipt">
                                I confirm that I have physically received this asset at the origin project and verified its condition.
                            </label>
                            <div class="invalid-feedback">
                                Please confirm the asset receipt.
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success" aria-label="Confirm receipt and complete return">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Confirm Receipt & Complete Return
                        </button>
                        <a href="?route=transfers/view&id=<?= $transfer['id'] ?> ?>"
                           class="btn btn-secondary"
                           aria-label="Cancel receipt">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancel
                        </a>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger mt-4" role="alert">
                    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>You do not have permission to receive this return or it is not in the correct status.
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

                <p><strong>Return Status:</strong><br>
                <?= ReturnStatusHelper::renderStatusBadge('in_return_transit') ?></p>

                <p><strong>Return Initiated By:</strong><br>
                <?= htmlspecialchars($transfer['return_initiated_by_name'] ?? 'Unknown') ?></p>

                <?php if (!empty($transfer['approved_by_name'])): ?>
                <p><strong>Originally Approved By:</strong><br>
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
                <span class="badge bg-warning text-dark">In Transit</span></p>
            </div>
        </div>

        <!-- Return Process Steps -->
        <div class="card">
            <div class="card-header bg-info">
                <h6 class="card-title mb-0 text-white">
                    <i class="bi bi-list-check me-2" aria-hidden="true"></i>Return Process
                </h6>
            </div>
            <div class="card-body">
                <div class="return-timeline">
                    <div class="timeline-step completed">
                        <div class="timeline-icon bg-success">
                            <i class="bi bi-check" aria-hidden="true"></i>
                        </div>
                        <div class="timeline-text">
                            <h6>Return Initiated</h6>
                            <small class="text-muted">Asset set to in-transit</small>
                        </div>
                    </div>

                    <div class="timeline-step active">
                        <div class="timeline-icon bg-primary">
                            <i class="bi bi-arrow-down" aria-hidden="true"></i>
                        </div>
                        <div class="timeline-text">
                            <h6>Receive at Origin</h6>
                            <small class="text-muted">Confirm receipt and condition</small>
                        </div>
                    </div>

                    <div class="timeline-step pending">
                        <div class="timeline-icon bg-secondary">
                            <i class="bi bi-check2-all" aria-hidden="true"></i>
                        </div>
                        <div class="timeline-text">
                            <h6>Return Complete</h6>
                            <small class="text-muted">Asset available at origin</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Transfers Module JS -->
<script src="<?= ASSETS_URL ?>/js/modules/transfers.js"></script>

<?php
$content = ob_get_clean();
$pageTitle = $branding['app_name'] . ' - Receive Returned Asset';
include APP_ROOT . '/views/layouts/main.php';
?>
