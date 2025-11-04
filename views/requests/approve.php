<?php
/**
 * ConstructLink™ Request Approve View - Final Approval/Decline
 *
 * Refactored to use partials and external resources following DRY principles.
 * All inline JavaScript and styles have been extracted.
 *
 * @version 2.0.0
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';

// Add external CSS and JS to page head
$additionalCSS = ['assets/css/modules/requests.css'];
$additionalJS = [
    'assets/js/modules/requests/init/form-validation.js',
    'assets/js/modules/requests/components/field-toggles.js'
];
?>

<!-- Only show the approve form if the user is allowed -->
<?php if (in_array($user['role_name'], $roleConfig['requests/approve'] ?? [])): ?>
    <div class="row">
        <div class="col-lg-8">
            <!-- Approval Form -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-clipboard-check me-2"></i>Approval Decision
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="?route=requests/approve&id=<?= $request['id'] ?>" id="approvalForm">
                        <?= CSRFProtection::getTokenField() ?>

                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Important Notice</h6>
                            <p class="mb-0 small">
                                This is the final approval step. Once approved, the request will be ready for procurement.
                                Please review all details carefully before making your decision.
                            </p>
                        </div>

                        <div class="mb-3">
                            <label for="action" class="form-label">
                                Approval Decision <span class="text-danger">*</span>
                            </label>
                            <select name="action" id="action" class="form-select" required aria-required="true">
                                <option value="">Select Decision</option>
                                <option value="approve">Approve Request</option>
                                <option value="decline">Decline Request</option>
                            </select>
                            <div class="invalid-feedback" role="alert">
                                Please select an approval decision.
                            </div>
                        </div>

                        <!-- Approval Fields -->
                        <div id="approvalFields" class="conditional-field" aria-hidden="true">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="approved_budget" class="form-label">Approved Budget (PHP)</label>
                                    <input type="number" name="approved_budget" id="approved_budget" class="form-control"
                                           step="0.01" min="0" value="<?= $request['estimated_cost'] ?? '' ?>"
                                           placeholder="Enter approved budget amount"
                                           aria-label="Approved budget in Philippine Pesos">
                                    <div class="form-text">
                                        Budget allocated for this request.
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="budget_code" class="form-label">Budget Code</label>
                                    <input type="text" name="budget_code" id="budget_code" class="form-control"
                                           placeholder="e.g., PROJ-2024-001-MAT"
                                           aria-label="Budget allocation code">
                                    <div class="form-text">
                                        Budget allocation code for tracking.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="procurement_deadline" class="form-label">Procurement Deadline</label>
                                <input type="date" name="procurement_deadline" id="procurement_deadline" class="form-control"
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                       value="<?= $request['date_needed'] ?? '' ?>"
                                       aria-label="Target date for procurement completion">
                                <div class="form-text">
                                    Target date for procurement completion.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="special_instructions" class="form-label">Special Instructions</label>
                                <textarea name="special_instructions" id="special_instructions" class="form-control" rows="3"
                                          placeholder="Any special instructions for procurement team..."
                                          aria-label="Special instructions for procurement team"></textarea>
                            </div>

                            <!-- Delivery Considerations -->
                            <div class="card bg-light mt-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="bi bi-truck me-2"></i>Delivery Considerations
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="delivery_priority" class="form-label">Delivery Priority</label>
                                            <select name="delivery_priority" id="delivery_priority" class="form-select"
                                                    aria-label="Select delivery priority level">
                                                <option value="Normal">Normal</option>
                                                <option value="Urgent" <?= ($request['urgency'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                                                <option value="Critical" <?= ($request['urgency'] ?? '') === 'Critical' ? 'selected' : '' ?>>Critical</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="delivery_location" class="form-label">Delivery Location</label>
                                            <select name="delivery_location" id="delivery_location" class="form-select"
                                                    aria-label="Select delivery location">
                                                <option value="Project Site">Project Site</option>
                                                <option value="Warehouse">Warehouse</option>
                                                <option value="Office">Office</option>
                                                <option value="Other">Other (specify in instructions)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="delivery_instructions" class="form-label">Delivery Instructions</label>
                                        <textarea name="delivery_instructions" id="delivery_instructions" class="form-control" rows="2"
                                                  placeholder="Special delivery requirements, contact person, access instructions..."
                                                  aria-label="Special delivery instructions"></textarea>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="requires_inspection"
                                               id="requires_inspection" value="1"
                                               aria-label="Require quality inspection upon delivery">
                                        <label class="form-check-label" for="requires_inspection">
                                            Requires quality inspection upon delivery
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Decline Fields -->
                        <div id="declineFields" class="conditional-field" aria-hidden="true">
                            <div class="mb-3">
                                <label for="decline_reason" class="form-label">
                                    Decline Reason <span class="text-danger">*</span>
                                </label>
                                <select name="decline_reason" id="decline_reason" class="form-select"
                                        aria-label="Select reason for declining request">
                                    <option value="">Select Reason</option>
                                    <option value="budget_exceeded">Budget Exceeded</option>
                                    <option value="not_authorized">Not Authorized</option>
                                    <option value="policy_violation">Policy Violation</option>
                                    <option value="insufficient_funds">Insufficient Funds</option>
                                    <option value="alternative_required">Alternative Required</option>
                                    <option value="timing_issues">Timing Issues</option>
                                    <option value="other">Other (specify in comments)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="alternative_suggestion" class="form-label">Alternative Suggestion</label>
                                <textarea name="alternative_suggestion" id="alternative_suggestion" class="form-control" rows="3"
                                          placeholder="Suggest alternatives or modifications to the request..."
                                          aria-label="Suggest alternative solutions"></textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="remarks" class="form-label">
                                Comments <span class="text-danger">*</span>
                            </label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="4" required
                                      placeholder="Provide detailed comments explaining your decision..."
                                      aria-required="true"
                                      aria-label="Approval or decline comments"></textarea>
                            <div class="invalid-feedback" role="alert">
                                Please provide approval comments.
                            </div>
                            <div class="form-text">
                                Your comments will be visible to all stakeholders.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="?route=requests/view&id=<?= $request['id'] ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Submit Decision
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Request Summary - Using Partial -->
            <?php include APP_ROOT . '/views/requests/_partials/_request-summary.php'; ?>

            <!-- Approval Guidelines - Using Partial -->
            <?php
            $context = 'approve';
            include APP_ROOT . '/views/requests/_partials/_review-guidelines.php';
            ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger mt-4" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>You do not have permission to approve this request.
    </div>
<?php endif; ?>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Approve Request - ConstructLink™';
$pageHeader = 'Approve Request #' . $request['id'];
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Requests', 'url' => '?route=requests'],
    ['title' => 'Request #' . $request['id'], 'url' => '?route=requests/view&id=' . $request['id']],
    ['title' => 'Approve', 'url' => '?route=requests/approve&id=' . $request['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
