<?php
/**
 * ConstructLink™ Request Review View - Asset Director Review
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-eye-fill me-2"></i>
        Review Request #<?= $request['id'] ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=requests/view&id=<?= $request['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Request
        </a>
    </div>
</div>

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
                            <label for="action" class="form-label">Review Decision <span class="text-danger">*</span></label>
                            <select name="action" id="action" class="form-select" required onchange="toggleForwardOptions()">
                                <option value="">Select Decision</option>
                                <option value="forward">Forward for Approval</option>
                                <option value="reviewed">Mark as Reviewed</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a review decision.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Review Comments <span class="text-danger">*</span></label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="4" required 
                                      placeholder="Provide detailed review comments explaining your decision..."></textarea>
                            <div class="invalid-feedback">
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
            <!-- Request Summary -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Request Summary
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">Request ID:</dt>
                        <dd class="col-sm-7">#<?= $request['id'] ?></dd>
                        
                        <dt class="col-sm-5">Type:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-light text-dark">
                                <?= htmlspecialchars($request['request_type']) ?>
                            </span>
                        </dd>
                        
                        <dt class="col-sm-5">Urgency:</dt>
                        <dd class="col-sm-7">
                            <?php
                            $urgencyClass = [
                                'Normal' => 'bg-secondary',
                                'Urgent' => 'bg-warning',
                                'Critical' => 'bg-danger'
                            ];
                            $class = $urgencyClass[$request['urgency']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $class ?>">
                                <?= htmlspecialchars($request['urgency']) ?>
                            </span>
                        </dd>
                        
                        <dt class="col-sm-5">Project:</dt>
                        <dd class="col-sm-7">
                            <div class="fw-medium"><?= htmlspecialchars($request['project_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($request['project_code']) ?></small>
                        </dd>
                        
                        <dt class="col-sm-5">Requested By:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($request['requested_by_name']) ?></dd>
                        
                        <dt class="col-sm-5">Date Created:</dt>
                        <dd class="col-sm-7"><?= date('M j, Y', strtotime($request['created_at'])) ?></dd>
                        
                        <?php if ($request['estimated_cost']): ?>
                        <dt class="col-sm-5">Est. Cost:</dt>
                        <dd class="col-sm-7">₱<?= number_format($request['estimated_cost'], 2) ?></dd>
                        <?php endif; ?>
                    </dl>
                    
                    <div class="mt-3">
                        <h6>Description:</h6>
                        <p class="text-muted small"><?= nl2br(htmlspecialchars(substr($request['description'], 0, 200))) ?><?= strlen($request['description']) > 200 ? '...' : '' ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Review Guidelines -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Review Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <h6>Forward to Finance Director when:</h6>
                    <ul class="small">
                        <li>Budget approval is required</li>
                        <li>Cost exceeds department limits</li>
                        <li>Financial impact assessment needed</li>
                    </ul>
                    
                    <h6>Forward to Procurement when:</h6>
                    <ul class="small">
                        <li>Vendor selection is required</li>
                        <li>Market research needed</li>
                        <li>Procurement process must be followed</li>
                    </ul>
                    
                    <h6>Decline when:</h6>
                    <ul class="small">
                        <li>Request doesn't align with project goals</li>
                        <li>Budget constraints exist</li>
                        <li>Alternative solutions available</li>
                        <li>Insufficient justification provided</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger mt-4">You do not have permission to review this request.</div>
<?php endif; ?>

<script>
function toggleForwardOptions() {
    // This function can be simplified since we removed the complex options
    // Just keeping it for potential future use
}

// Form validation
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    const action = document.getElementById('action').value;
    const remarks = document.getElementById('remarks').value.trim();
    
    if (!action || !remarks) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
    
    if (remarks.length < 10) {
        e.preventDefault();
        alert('Please provide more detailed review comments (at least 10 characters).');
        return false;
    }
    
    // Confirmation
    const actionText = action === 'forward' ? 'forward' : 'review';
    if (!confirm(`Are you sure you want to ${actionText} this request?`)) {
        e.preventDefault();
        return false;
    }
});
</script>

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
