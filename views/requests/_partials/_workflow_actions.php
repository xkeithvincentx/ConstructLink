<?php
/**
 * Request Workflow Actions Partial
 *
 * Displays MVA workflow action buttons based on current status and user role.
 * Shows context-aware buttons for verify, authorize, approve, and decline actions.
 *
 * Required variables:
 * - $request: Request data array
 * - $canVerify: Boolean - whether current user can verify
 * - $canAuthorize: Boolean - whether current user can authorize
 * - $canApprove: Boolean - whether current user can approve
 * - $currentUser: Current user data array
 * - $auth: Auth instance
 *
 * @version 1.0.0
 */
?>

<!-- MVA Workflow Action Buttons -->
<div class="card mt-3">
    <div class="card-header bg-primary text-white">
        <h6 class="card-title mb-0">
            <i class="bi bi-check2-square me-2"></i>Approval Actions
        </h6>
    </div>
    <div class="card-body">
        <?php if ($request['status'] === 'Declined'): ?>
            <!-- Declined Request - Show Resubmit Option -->
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Request Declined</strong>
                <p class="mb-2 mt-2"><?= nl2br(htmlspecialchars($request['decline_reason'])) ?></p>
                <small class="text-muted">
                    Declined by <?= htmlspecialchars($request['decliner_name']) ?>
                    on <?= date('M j, Y g:i A', strtotime($request['declined_at'])) ?>
                </small>
            </div>

            <?php if ($request['requested_by'] == $currentUser['id']): ?>
                <form method="POST" action="?route=requests/resubmit" onsubmit="return confirm('Reset this request to draft status for editing?');">
                    <?= CSRFProtection::getTokenField() ?>
                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-arrow-clockwise me-1"></i>Reset to Draft for Resubmission
                    </button>
                </form>
            <?php endif; ?>

        <?php elseif ($request['status'] === 'Draft'): ?>
            <!-- Draft Status - Submit Button -->
            <?php if ($request['requested_by'] == $currentUser['id']): ?>
                <form method="POST" action="?route=requests/submit&id=<?= $request['id'] ?>">
                    <?= CSRFProtection::getTokenField() ?>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-send me-1"></i>Submit Request for Approval
                    </button>
                </form>
            <?php else: ?>
                <p class="text-muted text-center">This request is in draft status.</p>
            <?php endif; ?>

        <?php elseif (in_array($request['status'], ['Submitted', 'Verified', 'Authorized'])): ?>
            <!-- Active Workflow - Show Approval Buttons -->
            <div class="d-grid gap-2">
                <?php if ($canVerify): ?>
                    <form method="POST" action="?route=requests/verify" id="verifyForm">
                        <?= CSRFProtection::getTokenField() ?>
                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                        <textarea name="notes" class="form-control mb-2" rows="2" placeholder="Verification notes (optional)"></textarea>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle me-1"></i>Verify Request
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($canAuthorize): ?>
                    <form method="POST" action="?route=requests/authorize" id="authorizeForm">
                        <?= CSRFProtection::getTokenField() ?>
                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                        <textarea name="notes" class="form-control mb-2" rows="2" placeholder="Authorization notes (optional)"></textarea>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-shield-check me-1"></i>Authorize Request
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($canApprove): ?>
                    <form method="POST" action="?route=requests/approveWorkflow" id="approveForm">
                        <?= CSRFProtection::getTokenField() ?>
                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                        <textarea name="notes" class="form-control mb-2" rows="2" placeholder="Approval notes (optional)"></textarea>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-all me-1"></i>Approve Request
                        </button>
                    </form>
                <?php endif; ?>

                <!-- Decline Button (available to all approvers) -->
                <?php if (in_array($currentUser['role_name'], ['Site Inventory Clerk', 'Site Admin', 'Project Manager', 'Finance Director', 'Asset Director', 'System Admin'])): ?>
                    <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#declineModal">
                        <i class="bi bi-x-circle me-1"></i>Decline Request
                    </button>
                <?php endif; ?>

                <?php if (!$canVerify && !$canAuthorize && !$canApprove): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Pending Approval</strong>
                        <p class="mb-0 mt-2">
                            <?php if ($nextApprover): ?>
                                Waiting for <?= htmlspecialchars($nextApprover['role']) ?> to <?= htmlspecialchars($nextApprover['action']) ?>.
                            <?php else: ?>
                                This request is in the approval process.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($request['status'] === 'Approved'): ?>
            <!-- Approved - Show Procurement Options -->
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>
                <strong>Request Approved</strong>
                <p class="mb-0 mt-2">
                    Approved by <?= htmlspecialchars($request['approver_name']) ?>
                    on <?= date('M j, Y g:i A', strtotime($request['approved_at'])) ?>
                </p>
            </div>

            <?php if (empty($request['procurement_id']) && in_array($currentUser['role_name'], ['Procurement Officer', 'System Admin'])): ?>
                <a href="?route=procurement-orders/createFromRequest&request_id=<?= $request['id'] ?>" class="btn btn-primary w-100">
                    <i class="bi bi-cart-plus me-1"></i>Create Procurement Order
                </a>
            <?php endif; ?>

        <?php elseif ($request['status'] === 'Procured'): ?>
            <!-- Procured Status -->
            <div class="alert alert-info">
                <i class="bi bi-cart-check me-2"></i>
                <strong>Procurement Order Created</strong>
                <p class="mb-0 mt-2">This request is now linked to a procurement order.</p>
            </div>

        <?php elseif ($request['status'] === 'Fulfilled'): ?>
            <!-- Fulfilled Status -->
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Request Fulfilled</strong>
                <p class="mb-0 mt-2">This request has been completed.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Decline Modal -->
<div class="modal fade" id="declineModal" tabindex="-1" aria-labelledby="declineModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="declineModalLabel">Decline Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="?route=requests/decline">
                <?= CSRFProtection::getTokenField() ?>
                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Declining this request will require the requester to resubmit.
                    </div>
                    <div class="mb-3">
                        <label for="decline_reason" class="form-label">Reason for Declining <span class="text-danger">*</span></label>
                        <textarea name="decline_reason" id="decline_reason" class="form-control" rows="4" required
                                  placeholder="Please provide a detailed reason for declining this request..."></textarea>
                        <div class="form-text">Be specific to help the requester understand what needs to be changed.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>Decline Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
