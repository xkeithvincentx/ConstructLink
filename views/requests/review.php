<?php
/**
 * ConstructLink™ Request Review View - Asset Director Review
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

<?php if (in_array($user['role_name'], $roleConfig['requests/review'] ?? [])): ?>
    <div class="row">
        <div class="col-lg-8">
            <!-- Review Form -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-clipboard-check me-2"></i>Review Decision
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="?route=requests/review&id=<?= $request['id'] ?>" id="reviewForm">
                        <?= CSRFProtection::getTokenField() ?>

                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>Review Instructions</h6>
                            <ul class="mb-0 small">
                                <li>Verify the request details and requirements</li>
                                <li>Check if the request aligns with project needs</li>
                                <li>Forward to appropriate approver or decline if necessary</li>
                                <li>Add review comments for transparency</li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label for="action" class="form-label">
                                Review Decision <span class="text-danger">*</span>
                            </label>
                            <select name="action" id="action" class="form-select" required aria-required="true">
                                <option value="">Select Decision</option>
                                <option value="forward">Forward for Approval</option>
                                <option value="reviewed">Mark as Reviewed</option>
                            </select>
                            <div class="invalid-feedback" role="alert">
                                Please select a review decision.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="remarks" class="form-label">
                                Review Comments <span class="text-danger">*</span>
                            </label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="4" required
                                      placeholder="Provide detailed review comments explaining your decision..."
                                      aria-required="true"
                                      aria-label="Review comments"></textarea>
                            <div class="invalid-feedback" role="alert">
                                Please provide review comments.
                            </div>
                            <div class="form-text">
                                Your comments will be visible to the requester and subsequent approvers.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="?route=requests/view&id=<?= $request['id'] ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Submit Review
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Request Summary - Using Partial -->
            <?php
            $descriptionLimit = 200;
            include APP_ROOT . '/views/requests/_partials/_request-summary.php';
            ?>

            <!-- Review Guidelines - Using Partial -->
            <?php
            $context = 'review';
            include APP_ROOT . '/views/requests/_partials/_review-guidelines.php';
            ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger mt-4" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>You do not have permission to review this request.
    </div>
<?php endif; ?>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Review Request - ConstructLink™';
$pageHeader = 'Review Request #' . $request['id'];
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Requests', 'url' => '?route=requests'],
    ['title' => 'Request #' . $request['id'], 'url' => '?route=requests/view&id=' . $request['id']],
    ['title' => 'Review', 'url' => '?route=requests/review&id=' . $request['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
