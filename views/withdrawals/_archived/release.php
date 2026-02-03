<?php
// Prevent direct access
if (!defined('APP_ROOT')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Start output buffering
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$roleConfig = require APP_ROOT . '/config/roles.php';
function canReleaseWithdrawal($withdrawal, $userRole, $roleConfig) {
    if ($userRole === 'System Admin') return true;
    return $withdrawal['status'] === 'Approved' && in_array($userRole, $roleConfig['withdrawals/release'] ?? []);
}
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Error:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Release Confirmation -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-check-circle me-2"></i>Confirm Consumable Release
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Consumable Release Process:</strong> By releasing this asset, you confirm that:
                    <ul class="mb-0 mt-2">
                        <li>The withdrawal request has been reviewed and approved</li>
                        <li>The consumable is available and ready for use</li>
                        <li>The receiver has been properly identified</li>
                        <li>The consumable status will be changed to "In Use"</li>
                    </ul>
                </div>
                
           <?php if (canReleaseWithdrawal($withdrawal, $userRole, $roleConfig)): ?>
           <form method="POST" action="?route=withdrawals/release&id=<?= htmlspecialchars($withdrawal['id']) ?>" id="releaseForm">
                    <?= CSRFProtection::getTokenField() ?>
                    <input type="hidden" name="withdrawal_id" value="<?= htmlspecialchars($withdrawal['id']) ?>">
                    
                    <!-- Release Authorization -->
                    <div class="mb-3">
                        <label class="form-label">Release Authorization <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="authorization_level" id="standard_release" value="standard" required>
                                    <label class="form-check-label" for="standard_release">
                                        <i class="bi bi-check-circle text-success me-1"></i>Standard Release
                                    </label>
                                    <div class="form-text">Normal consumable release for approved requests</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="authorization_level" id="emergency_release" value="emergency" required>
                                    <label class="form-check-label" for="emergency_release">
                                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>Emergency Release
                                    </label>
                                    <div class="form-text">Urgent release for critical operations</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Consumable Condition Check -->
                    <div class="mb-3">
                        <label class="form-label">Consumable Condition Verification <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="consumable_condition" id="condition_excellent" value="excellent" required>
                                    <label class="form-check-label" for="condition_excellent">
                                        <i class="bi bi-star-fill text-success me-1"></i>Excellent
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="consumable_condition" id="condition_good" value="good" required>
                                    <label class="form-check-label" for="condition_good">
                                        <i class="bi bi-check-circle text-success me-1"></i>Good
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="consumable_condition" id="condition_fair" value="fair" required>
                                    <label class="form-check-label" for="condition_fair">
                                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>Fair
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Receiver Verification -->
                    <div class="mb-3">
                        <label for="receiver_verification" class="form-label">Receiver Verification</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-check"></i></span>
                            <input type="text" 
                                   class="form-control" 
                                   id="receiver_verification" 
                                   name="receiver_verification" 
                                   placeholder="Confirm receiver identity (ID, badge number, etc.)"
                                   value="<?= htmlspecialchars($withdrawal['receiver_name']) ?>" readonly>
                        </div>
                        <div class="form-text">
                            Verify the identity of the person receiving the consumable.
                        </div>
                    </div>
                    
                    <!-- Release Notes -->
                    <div class="mb-3">
                        <label for="release_notes" class="form-label">Release Notes</label>
                        <textarea class="form-control" 
                                  id="release_notes" 
                                  name="release_notes" 
                                  rows="4" 
                                  placeholder="Enter any notes about the consumable release, special instructions, or conditions..."></textarea>
                        <div class="form-text">
                            Document any special conditions, instructions, or observations.
                        </div>
                    </div>
                    
                    <!-- Emergency Instructions -->
                    <div class="mb-3" id="emergencyInstructions" style="display: none;">
                        <label for="emergency_reason" class="form-label">Emergency Release Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="emergency_reason" 
                                  name="emergency_reason" 
                                  rows="3" 
                                  placeholder="Explain the reason for emergency release..."></textarea>
                        <div class="form-text text-warning">
                            Emergency releases require additional documentation and approval.
                        </div>
                    </div>
                    
                    <!-- Confirmation Checkboxes -->
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="confirmAssetCondition" name="confirmAssetCondition" required>
                            <label class="form-check-label" for="confirmAssetCondition">
                                I have inspected the consumable and confirmed its condition is suitable for use.
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="confirmReceiverIdentity" name="confirmReceiverIdentity" required>
                            <label class="form-check-label" for="confirmReceiverIdentity">
                                I have verified the identity of the person receiving the consumable.
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="confirmAuthorization" name="confirmAuthorization" required>
                            <label class="form-check-label" for="confirmAuthorization">
                                I have the authority to release this consumable for the specified purpose.
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmResponsibility" name="confirmResponsibility" required>
                            <label class="form-check-label" for="confirmResponsibility">
                                I understand that I am responsible for this consumable release decision.
                            </label>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=withdrawals/view&id=<?= htmlspecialchars($withdrawal['id']) ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success" id="releaseButton" disabled>
                            <i class="bi bi-check-circle me-1"></i>Release Consumable
                        </button>
                    </div>
                </form>
           <?php else: ?>
<div class="alert alert-danger mt-4">
    <i class="bi bi-exclamation-triangle me-2"></i>You do not have permission to release this consumable or it is not in a releasable status.
</div>
<?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Withdrawal Summary -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Withdrawal Summary
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Request ID:</dt>
                    <dd class="col-sm-7">#<?= htmlspecialchars($withdrawal['id']) ?></dd>
                    
                    <dt class="col-sm-5">Asset:</dt>
                    <dd class="col-sm-7">
                        <div class="fw-medium"><?= htmlspecialchars($withdrawal['item_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($withdrawal['item_ref']) ?></small>
                    </dd>
                    
                    <dt class="col-sm-5">Receiver:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['receiver_name']) ?></dd>
                    
                    <dt class="col-sm-5">Requested By:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['withdrawn_by_name']) ?></dd>
                    
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-light text-dark">
                            <?= htmlspecialchars($withdrawal['project_name']) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-5">Request Date:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($withdrawal['created_at'])) ?></dd>
                    
                    <?php if ($withdrawal['expected_return']): ?>
                        <dt class="col-sm-5">Expected Return:</dt>
                        <dd class="col-sm-7"><?= date('M j, Y', strtotime($withdrawal['expected_return'])) ?></dd>
                    <?php endif; ?>
                </dl>
                
                <div class="mt-3">
                    <h6>Purpose:</h6>
                    <p class="text-muted small"><?= nl2br(htmlspecialchars($withdrawal['purpose'])) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Release Checklist -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-list-check me-2"></i>Release Checklist
                </h6>
            </div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check1">
                    <label class="form-check-label small" for="check1">
                        Withdrawal request reviewed
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check2">
                    <label class="form-check-label small" for="check2">
                        Asset condition verified
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check3">
                    <label class="form-check-label small" for="check3">
                        Receiver identity confirmed
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check4">
                    <label class="form-check-label small" for="check4">
                        Purpose validated
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check5">
                    <label class="form-check-label small" for="check5">
                        Release notes documented
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Asset Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box me-2"></i>Asset Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Category:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['category_name']) ?></dd>
                    
                    <dt class="col-sm-5">Current Status:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-success">Available</span>
                    </dd>
                    
                    <dt class="col-sm-5">Location:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['project_location'] ?? 'N/A') ?></dd>
                </dl>
                
                <div class="mt-3">
                    <a href="?route=assets/view&id=<?= htmlspecialchars($withdrawal['asset_id']) ?>" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-eye me-1"></i>View Asset Details
                    </a>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Who Can Release (MVA Workflow)
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled small">
                    <li><i class="bi bi-person-check text-primary me-1"></i> System Admin: Any withdrawal</li>
                    <li><i class="bi bi-person-check text-primary me-1"></i> Asset Director/Warehouseman: If allowed by workflow</li>
                    <li><i class="bi bi-person-check text-primary me-1"></i> Only allowed in <strong>Approved</strong> status</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide emergency instructions based on authorization level
document.querySelectorAll('input[name="authorization_level"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const emergencyInstructions = document.getElementById('emergencyInstructions');
        const emergencyReason = document.getElementById('emergency_reason');
        
        if (this.value === 'emergency') {
            emergencyInstructions.style.display = 'block';
            emergencyReason.required = true;
        } else {
            emergencyInstructions.style.display = 'none';
            emergencyReason.required = false;
            emergencyReason.value = '';
        }
        
        updateReleaseButton();
    });
});

// Update release button state based on form completion
function updateReleaseButton() {
    const releaseButton = document.getElementById('releaseButton');
    const requiredCheckboxes = document.querySelectorAll('#releaseForm input[type="checkbox"][required]');
    const authorizationSelected = document.querySelector('input[name="authorization_level"]:checked');
    const conditionSelected = document.querySelector('input[name="consumable_condition"]:checked');
    
    let allRequiredChecked = true;
    requiredCheckboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            allRequiredChecked = false;
        }
    });
    
    // Check if emergency reason is required and provided
    let emergencyReasonValid = true;
    if (authorizationSelected && authorizationSelected.value === 'emergency') {
        const emergencyReason = document.getElementById('emergency_reason').value.trim();
        emergencyReasonValid = emergencyReason.length > 0;
    }
    
    releaseButton.disabled = !(allRequiredChecked && authorizationSelected && conditionSelected && emergencyReasonValid);
}

// Add event listeners to all form elements
document.querySelectorAll('#releaseForm input, #releaseForm textarea').forEach(element => {
    element.addEventListener('change', updateReleaseButton);
    element.addEventListener('input', updateReleaseButton);
});

// Form validation
document.getElementById('releaseForm').addEventListener('submit', function(e) {
    const authorizationLevel = document.querySelector('input[name="authorization_level"]:checked');
    const assetCondition = document.querySelector('input[name="consumable_condition"]:checked');
    
    if (!authorizationLevel) {
        e.preventDefault();
        alert('Please select an authorization level.');
        return false;
    }
    
    if (!assetCondition) {
        e.preventDefault();
        alert('Please verify the asset condition.');
        return false;
    }
    
    // Check emergency reason if emergency release
    if (authorizationLevel.value === 'emergency') {
        const emergencyReason = document.getElementById('emergency_reason').value.trim();
        if (!emergencyReason) {
            e.preventDefault();
            alert('Please provide a reason for emergency release.');
            return false;
        }
    }
    
    // Final confirmation
    let confirmMessage = 'Are you sure you want to release this consumable?';
    if (authorizationLevel.value === 'emergency') {
        confirmMessage += '\n\nThis is an EMERGENCY RELEASE and will be logged for audit purposes.';
    }
    
    if (!confirm(confirmMessage)) {
        e.preventDefault();
        return false;
    }
});

// Auto-fill release notes based on selections
document.querySelectorAll('input[name="authorization_level"], input[name="consumable_condition"]').forEach(input => {
    input.addEventListener('change', function() {
        const releaseNotes = document.getElementById('release_notes');
        const authLevel = document.querySelector('input[name="authorization_level"]:checked');
        const condition = document.querySelector('input[name="consumable_condition"]:checked');
        
        if (authLevel && condition && !releaseNotes.value) {
            let notes = `Asset released under ${authLevel.value} authorization. `;
            notes += `Asset condition verified as ${condition.value}. `;
            notes += `Released to ${document.getElementById('receiver_verification').value} `;
            notes += `on ${new Date().toLocaleDateString()}.`;
            
            releaseNotes.value = notes;
        }
    });
});

// Enable release button checking for checklist completion
const checklistItems = document.querySelectorAll('[id^="check"]');
checklistItems.forEach(item => {
    item.addEventListener('change', function() {
        const allChecked = Array.from(checklistItems).every(item => item.checked);
        if (allChecked) {
            updateReleaseButton();
        }
    });
});

// Initialize
updateReleaseButton();
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>