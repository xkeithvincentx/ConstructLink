<?php
/**
 * ConstructLink™ Request Approve View - Final Approval/Decline
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

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
                            <label for="action" class="form-label">Approval Decision <span class="text-danger">*</span></label>
                            <select name="action" id="action" class="form-select" required onchange="toggleDecisionFields()">
                                <option value="">Select Decision</option>
                                <option value="approve">Approve Request</option>
                                <option value="decline">Decline Request</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select an approval decision.
                            </div>
                        </div>
                        
                        <!-- Approval Fields -->
                        <div id="approvalFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="approved_budget" class="form-label">Approved Budget (PHP)</label>
                                    <input type="number" name="approved_budget" id="approved_budget" class="form-control" 
                                           step="0.01" min="0" value="<?= $request['estimated_cost'] ?? '' ?>" 
                                           placeholder="Enter approved budget amount">
                                    <div class="form-text">
                                        Budget allocated for this request.
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="budget_code" class="form-label">Budget Code</label>
                                    <input type="text" name="budget_code" id="budget_code" class="form-control" 
                                           placeholder="e.g., PROJ-2024-001-MAT">
                                    <div class="form-text">
                                        Budget allocation code for tracking.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="procurement_deadline" class="form-label">Procurement Deadline</label>
                                <input type="date" name="procurement_deadline" id="procurement_deadline" class="form-control" 
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>" 
                                       value="<?= $request['date_needed'] ?? '' ?>">
                                <div class="form-text">
                                    Target date for procurement completion.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="special_instructions" class="form-label">Special Instructions</label>
                                <textarea name="special_instructions" id="special_instructions" class="form-control" rows="3" 
                                          placeholder="Any special instructions for procurement team..."></textarea>
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
                                            <select name="delivery_priority" id="delivery_priority" class="form-select">
                                                <option value="Normal">Normal</option>
                                                <option value="Urgent" <?= $request['urgency'] === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                                                <option value="Critical" <?= $request['urgency'] === 'Critical' ? 'selected' : '' ?>>Critical</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="delivery_location" class="form-label">Delivery Location</label>
                                            <select name="delivery_location" id="delivery_location" class="form-select">
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
                                                  placeholder="Special delivery requirements, contact person, access instructions..."></textarea>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="requires_inspection" id="requires_inspection" value="1">
                                        <label class="form-check-label" for="requires_inspection">
                                            Requires quality inspection upon delivery
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Decline Fields -->
                        <div id="declineFields" style="display: none;">
                            <div class="mb-3">
                                <label for="decline_reason" class="form-label">Decline Reason <span class="text-danger">*</span></label>
                                <select name="decline_reason" id="decline_reason" class="form-select">
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
                                          placeholder="Suggest alternatives or modifications to the request..."></textarea>
                            </div>
                        </div>
                        
                        <!-- More Info Fields -->
                        <div id="moreInfoFields" style="display: none;">
                            <div class="mb-3">
                                <label for="info_required" class="form-label">Information Required <span class="text-danger">*</span></label>
                                <textarea name="info_required" id="info_required" class="form-control" rows="3" 
                                          placeholder="Specify what additional information is needed..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="response_deadline" class="form-label">Response Deadline</label>
                                <input type="date" name="response_deadline" id="response_deadline" class="form-control" 
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Comments <span class="text-danger">*</span></label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="4" required 
                                      placeholder="Provide detailed comments explaining your decision..."></textarea>
                            <div class="invalid-feedback">
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
                        
                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            <?php
                            $statusClass = [
                                'Generated' => 'bg-secondary',
                                'Reviewed' => 'bg-info',
                                'Forwarded' => 'bg-primary',
                                'Approved' => 'bg-success',
                                'Declined' => 'bg-danger'
                            ];
                            $class = $statusClass[$request['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $class ?>">
                                <?= htmlspecialchars($request['status']) ?>
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
                        
                        <?php if ($request['reviewed_by_name']): ?>
                        <dt class="col-sm-5">Reviewed By:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($request['reviewed_by_name']) ?></dd>
                        <?php endif; ?>
                        
                        <?php if ($request['estimated_cost']): ?>
                        <dt class="col-sm-5">Est. Cost:</dt>
                        <dd class="col-sm-7">₱<?= number_format($request['estimated_cost'], 2) ?></dd>
                        <?php endif; ?>
                        
                        <?php if ($request['date_needed']): ?>
                        <dt class="col-sm-5">Date Needed:</dt>
                        <dd class="col-sm-7">
                            <span class="<?= strtotime($request['date_needed']) < time() ? 'text-danger fw-bold' : '' ?>">
                                <?= date('M j, Y', strtotime($request['date_needed'])) ?>
                            </span>
                        </dd>
                        <?php endif; ?>
                    </dl>
                    
                    <div class="mt-3">
                        <h6>Description:</h6>
                        <p class="text-muted small"><?= nl2br(htmlspecialchars(substr($request['description'], 0, 200))) ?><?= strlen($request['description']) > 200 ? '...' : '' ?></p>
                    </div>
                    
                    <?php if ($request['remarks']): ?>
                    <div class="mt-3">
                        <h6>Remarks:</h6>
                        <p class="text-muted small"><?= nl2br(htmlspecialchars($request['remarks'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Approval Guidelines -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Approval Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <h6>Approve when:</h6>
                    <ul class="small">
                        <li>Budget is available and justified</li>
                        <li>Request aligns with project objectives</li>
                        <li>Proper authorization exists</li>
                        <li>Timeline is reasonable</li>
                    </ul>
                    
                    <h6>Decline when:</h6>
                    <ul class="small">
                        <li>Budget constraints exist</li>
                        <li>Request violates policies</li>
                        <li>Insufficient justification</li>
                        <li>Better alternatives available</li>
                    </ul>
                    
                    <h6>Request more info when:</h6>
                    <ul class="small">
                        <li>Details are unclear or missing</li>
                        <li>Additional documentation needed</li>
                        <li>Clarification required</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger mt-4">You do not have permission to approve this request.</div>
<?php endif; ?>

<script>
function toggleDecisionFields() {
    const decision = document.getElementById('action').value;
    const approvalFields = document.getElementById('approvalFields');
    const declineFields = document.getElementById('declineFields');
    const declineReason = document.getElementById('decline_reason');
    
    // Reset visibility and requirements
    approvalFields.style.display = 'none';
    declineFields.style.display = 'none';
    declineReason.required = false;
    
    if (decision === 'approve') {
        approvalFields.style.display = 'block';
    } else if (decision === 'decline') {
        declineFields.style.display = 'block';
        declineReason.required = true;
    }
}

// Form validation
document.getElementById('approvalForm').addEventListener('submit', function(e) {
    const action = document.getElementById('action').value;
    const remarks = document.getElementById('remarks').value.trim();
    const declineReason = document.getElementById('decline_reason').value;
    
    if (!action || !remarks) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
    
    if (action === 'decline' && !declineReason) {
        e.preventDefault();
        alert('Please select a decline reason.');
        return false;
    }
    
    if (remarks.length < 10) {
        e.preventDefault();
        alert('Please provide more detailed comments (at least 10 characters).');
        return false;
    }
    
    // Confirmation
    const actionText = action === 'approve' ? 'approve' : 'decline';
    if (!confirm(`Are you sure you want to ${actionText} this request?`)) {
        e.preventDefault();
        return false;
    }
});

// Auto-fill approved budget with estimated cost
document.getElementById('action').addEventListener('change', function() {
    if (this.value === 'approve') {
        const estimatedCost = <?= $request['estimated_cost'] ?? 0 ?>;
        if (estimatedCost > 0) {
            document.getElementById('approved_budget').value = estimatedCost;
        }
    }
});
</script>

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
