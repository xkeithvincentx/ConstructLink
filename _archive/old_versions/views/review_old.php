<?php
/**
 * ConstructLink™ Request Review View - Asset Director Review
 */

// Include main layout
include APP_ROOT . '/views/layouts/main.php';

function renderContent() {
    global $request, $auth, $errors, $messages;
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-eye-fill me-2"></i>
                Review Request #<?= $request['id'] ?>
            </h1>
            <p class="text-muted mb-0">Asset Director Review and Forward</p>
        </div>
        
        <div class="btn-toolbar">
            <a href="?route=requests/view&id=<?= $request['id'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Request
            </a>
        </div>
    </div>

    <!-- Messages -->
    <?php include APP_ROOT . '/views/layouts/messages.php'; ?>

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
                            <label for="review_decision" class="form-label">Review Decision <span class="text-danger">*</span></label>
                            <select name="review_decision" id="review_decision" class="form-select" required onchange="toggleForwardOptions()">
                                <option value="">Select Decision</option>
                                <option value="forward_finance">Forward to Finance Director</option>
                                <option value="forward_procurement">Forward to Procurement Officer</option>
                                <option value="decline">Decline Request</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a review decision.
                            </div>
                        </div>
                        
                        <div class="mb-3" id="forwardReasonField" style="display: none;">
                            <label for="forward_reason" class="form-label">Forward Reason</label>
                            <select name="forward_reason" id="forward_reason" class="form-select">
                                <option value="">Select Reason</option>
                                <option value="budget_approval">Requires Budget Approval</option>
                                <option value="procurement_needed">Procurement Required</option>
                                <option value="cost_evaluation">Cost Evaluation Needed</option>
                                <option value="vendor_selection">Vendor Selection Required</option>
                                <option value="other">Other (specify in comments)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="declineReasonField" style="display: none;">
                            <label for="decline_reason" class="form-label">Decline Reason <span class="text-danger">*</span></label>
                            <select name="decline_reason" id="decline_reason" class="form-select">
                                <option value="">Select Reason</option>
                                <option value="budget_constraints">Budget Constraints</option>
                                <option value="not_project_related">Not Project Related</option>
                                <option value="duplicate_request">Duplicate Request</option>
                                <option value="insufficient_justification">Insufficient Justification</option>
                                <option value="alternative_available">Alternative Available</option>
                                <option value="other">Other (specify in comments)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="review_comments" class="form-label">Review Comments <span class="text-danger">*</span></label>
                            <textarea name="review_comments" id="review_comments" class="form-control" rows="4" required 
                                      placeholder="Provide detailed review comments explaining your decision..."></textarea>
                            <div class="invalid-feedback">
                                Please provide review comments.
                            </div>
                            <div class="form-text">
                                Your comments will be visible to the requester and subsequent approvers.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="priority_adjustment" class="form-label">Priority Adjustment</label>
                            <select name="priority_adjustment" id="priority_adjustment" class="form-select">
                                <option value="">Keep Current Priority (<?= htmlspecialchars($request['urgency']) ?>)</option>
                                <option value="Normal">Normal</option>
                                <option value="Urgent">Urgent</option>
                                <option value="Critical">Critical</option>
                            </select>
                            <div class="form-text">
                                Adjust priority if necessary based on your assessment.
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
</div>

<script>
function toggleForwardOptions() {
    const decision = document.getElementById('review_decision').value;
    const forwardReasonField = document.getElementById('forwardReasonField');
    const declineReasonField = document.getElementById('declineReasonField');
    const declineReason = document.getElementById('decline_reason');
    
    // Reset visibility
    forwardReasonField.style.display = 'none';
    declineReasonField.style.display = 'none';
    declineReason.required = false;
    
    if (decision.startsWith('forward_')) {
        forwardReasonField.style.display = 'block';
    } else if (decision === 'decline') {
        declineReasonField.style.display = 'block';
        declineReason.required = true;
    }
}

// Form validation
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    const decision = document.getElementById('review_decision').value;
    const comments = document.getElementById('review_comments').value.trim();
    const declineReason = document.getElementById('decline_reason').value;
    
    if (!decision || !comments) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
    
    if (decision === 'decline' && !declineReason) {
        e.preventDefault();
        alert('Please select a decline reason.');
        return false;
    }
    
    if (comments.length < 10) {
        e.preventDefault();
        alert('Please provide more detailed review comments (at least 10 characters).');
        return false;
    }
    
    // Confirmation
    const actionText = decision === 'decline' ? 'decline' : 'forward';
    if (!confirm(`Are you sure you want to ${actionText} this request?`)) {
        e.preventDefault();
        return false;
    }
});
</script>

<?php
}
?>
