<?php
/**
 * Transfers Module - Complete Transfer View
 *
 * Form to complete a transfer request
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
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Transfer Details
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Transfer ID:</strong> #<?= htmlspecialchars($transfer['id']) ?></p>
                        <p><strong>Asset:</strong> <?= htmlspecialchars($transfer['asset_ref']) ?> - <?= htmlspecialchars($transfer['asset_name']) ?></p>
                        <p><strong>Transfer Type:</strong> <?= ucfirst($transfer['transfer_type']) ?></p>
                        <p><strong>Status:</strong> <?= TransferHelper::renderStatusBadge($transfer['status']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>From:</strong> <?= htmlspecialchars($transfer['from_project_name']) ?></p>
                        <p><strong>To:</strong> <?= htmlspecialchars($transfer['to_project_name']) ?></p>
                        <p><strong>Approved By:</strong> <?= htmlspecialchars($transfer['approved_by_name']) ?></p>
                        <p><strong>Approval Date:</strong> <?= date('M j, Y', strtotime($transfer['approval_date'])) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completion Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2" aria-hidden="true"></i>Transfer Completion
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

                <?php if (canCompleteTransfer($transfer, $user)): ?>
                <form method="POST"
                      action="?route=transfers/complete&id=<?= $transfer['id'] ?>"
                      class="needs-validation"
                      novalidate>
                    <?= CSRFProtection::getTokenField() ?>

                    <!-- Completion Notes -->
                    <div class="mb-4">
                        <label for="completion_notes" class="form-label">Completion Notes</label>
                        <textarea class="form-control"
                                  id="completion_notes"
                                  name="completion_notes"
                                  rows="4"
                                  placeholder="Add any notes about the transfer completion..."
                                  aria-label="Completion notes documenting transfer issues or observations"><?= htmlspecialchars($formData['completion_notes'] ?? '') ?></textarea>
                        <div class="form-text">Document any issues, special conditions, or observations during the transfer</div>
                    </div>

                    <!-- Pre-completion Checklist -->
                    <div class="mb-4">
                        <h6>Pre-completion Checklist</h6>
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="asset_verified"
                                   name="asset_verified"
                                   value="1"
                                   required
                                   aria-required="true">
                            <label class="form-check-label" for="asset_verified">
                                Asset has been physically verified and is ready for transfer
                            </label>
                            <div class="invalid-feedback">Please confirm asset verification.</div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="destination_ready"
                                   name="destination_ready"
                                   value="1"
                                   required
                                   aria-required="true">
                            <label class="form-check-label" for="destination_ready">
                                Destination project is ready to receive the asset
                            </label>
                            <div class="invalid-feedback">Please confirm destination readiness.</div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="documentation_complete"
                                   name="documentation_complete"
                                   value="1"
                                   required
                                   aria-required="true">
                            <label class="form-check-label" for="documentation_complete">
                                All required documentation has been completed
                            </label>
                            <div class="invalid-feedback">Please confirm documentation completion.</div>
                        </div>
                    </div>

                    <!-- Final Confirmation -->
                    <div class="mb-4">
                        <div class="alert alert-warning" role="alert">
                            <h6><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Important Notice</h6>
                            <p class="mb-2">Completing this transfer will:</p>
                            <ul class="mb-0">
                                <li>Update the asset's project location in the system</li>
                                <li>Change the transfer status to "Completed"</li>
                                <li>Make the asset available at the destination project</li>
                                <?php if ($transfer['transfer_type'] === 'permanent'): ?>
                                    <li><strong>Permanently move the asset</strong> (cannot be easily reversed)</li>
                                <?php else: ?>
                                    <li>Temporarily assign the asset (can be returned later)</li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="confirm_completion"
                                   name="confirm_completion"
                                   value="1"
                                   required
                                   aria-required="true">
                            <label class="form-check-label" for="confirm_completion">
                                <strong>I confirm that the transfer has been physically completed and the asset is now at the destination project.</strong>
                            </label>
                            <div class="invalid-feedback">You must confirm the completion to proceed.</div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="button"
                                class="btn btn-outline-secondary"
                                onclick="history.back()"
                                aria-label="Cancel completion">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" aria-label="Complete transfer">
                            <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Complete Transfer
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger mt-4" role="alert">
                    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>You do not have permission to complete this transfer or it is not in a completable status.
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
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Completion Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Before Completing</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Physical transfer completed</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Asset condition verified</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Receiving party confirmed</li>
                        <li><i class="bi bi-check text-success me-1" aria-hidden="true"></i> Documentation signed</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6>After Completion</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-arrow-right text-primary me-1" aria-hidden="true"></i> Asset location updated</li>
                        <li><i class="bi bi-arrow-right text-primary me-1" aria-hidden="true"></i> Transfer marked complete</li>
                        <li><i class="bi bi-arrow-right text-primary me-1" aria-hidden="true"></i> Notifications sent</li>
                        <li><i class="bi bi-arrow-right text-primary me-1" aria-hidden="true"></i> Records archived</li>
                    </ul>
                </div>

                <div class="alert alert-info" role="alert">
                    <small>
                        <i class="bi bi-lightbulb me-1" aria-hidden="true"></i>
                        <strong>Tip:</strong> Take photos or get signatures as proof of transfer completion.
                    </small>
                </div>

                <div class="mb-3">
                    <h6>Who Can Complete (MVA Workflow)</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> System Admin: Any transfer</li>
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> Site Inventory Clerk/Project Manager: If allowed by workflow</li>
                        <li><i class="bi bi-person-check text-primary me-1" aria-hidden="true"></i> Only allowed in <strong>Received</strong> status</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Transfer Timeline -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2" aria-hidden="true"></i>Transfer Progress
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
$pageTitle = $branding['app_name'] . ' - Complete Transfer';
include APP_ROOT . '/views/layouts/main.php';
?>
