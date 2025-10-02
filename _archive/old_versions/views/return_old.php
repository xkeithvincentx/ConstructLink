<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-arrow-return-left me-2"></i>
        Return Asset
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Withdrawal
        </a>
    </div>
</div>

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
        <!-- Return Confirmation -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-arrow-return-left me-2"></i>Confirm Asset Return
                </h6>
            </div>
            <div class="card-body">
                <?php if ($withdrawal['expected_return'] && strtotime($withdrawal['expected_return']) < time()): ?>
                    <?php $daysOverdue = floor((time() - strtotime($withdrawal['expected_return'])) / (60 * 60 * 24)); ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Overdue Return:</strong> This asset was expected to be returned on 
                        <?= date('M j, Y', strtotime($withdrawal['expected_return'])) ?> 
                        and is now <strong><?= $daysOverdue ?> days overdue</strong>.
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Return Process:</strong> By processing this return, you confirm that:
                    <ul class="mb-0 mt-2">
                        <li>The asset has been physically returned</li>
                        <li>Asset condition has been inspected</li>
                        <li>Any damage or issues have been documented</li>
                        <li>The asset status will be updated to "Available"</li>
                    </ul>
                </div>
                
                <form method="POST" action="?route=withdrawals/return&id=<?= $withdrawal['id'] ?>" id="returnForm">
                    <?= CSRFProtection::generateToken() ?>
                    
                    <!-- Return Notes -->
                    <div class="mb-3">
                        <label for="return_notes" class="form-label">Return Notes</label>
                        <textarea class="form-control" 
                                  id="return_notes" 
                                  name="return_notes" 
                                  rows="4" 
                                  placeholder="Enter notes about the asset condition upon return, any damage, or other relevant information..."></textarea>
                        <div class="form-text">
                            Document the condition of the asset upon return and any issues found.
                        </div>
                    </div>
                    
                    <!-- Asset Condition Assessment -->
                    <div class="mb-3">
                        <label class="form-label">Asset Condition Assessment</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="condition" id="conditionGood" value="good" checked>
                                    <label class="form-check-label" for="conditionGood">
                                        <i class="bi bi-check-circle text-success me-1"></i>Good Condition
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="condition" id="conditionFair" value="fair">
                                    <label class="form-check-label" for="conditionFair">
                                        <i class="bi bi-exclamation-circle text-warning me-1"></i>Fair Condition
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="condition" id="conditionDamaged" value="damaged">
                                    <label class="form-check-label" for="conditionDamaged">
                                        <i class="bi bi-x-circle text-danger me-1"></i>Damaged
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-text">
                            Select the condition of the asset upon return.
                        </div>
                    </div>
                    
                    <!-- Damage Details (shown when damaged is selected) -->
                    <div class="mb-3" id="damageDetails" style="display: none;">
                        <label for="damage_description" class="form-label">Damage Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="damage_description" 
                                  name="damage_description" 
                                  rows="3" 
                                  placeholder="Describe the damage in detail..."></textarea>
                        <div class="form-text">
                            Provide detailed description of any damage found.
                        </div>
                    </div>
                    
                    <!-- Confirmation Checkbox -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmReturn" required>
                            <label class="form-check-label" for="confirmReturn">
                                I confirm that I have physically received and inspected this asset before marking it as returned.
                            </label>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-arrow-return-left me-1"></i>Process Return
                        </button>
                    </div>
                </form>
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
                    <dd class="col-sm-7">#<?= $withdrawal['id'] ?></dd>
                    
                    <dt class="col-sm-5">Asset:</dt>
                    <dd class="col-sm-7">
                        <div class="fw-medium"><?= htmlspecialchars($withdrawal['asset_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($withdrawal['asset_ref']) ?></small>
                    </dd>
                    
                    <dt class="col-sm-5">Receiver:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['receiver_name']) ?></dd>
                    
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-light text-dark">
                            <?= htmlspecialchars($withdrawal['project_name']) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-5">Released Date:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($withdrawal['released_at'])) ?></dd>
                    
                    <?php if ($withdrawal['expected_return']): ?>
                        <dt class="col-sm-5">Expected Return:</dt>
                        <dd class="col-sm-7">
                            <span class="<?= strtotime($withdrawal['expected_return']) < time() ? 'text-danger fw-bold' : '' ?>">
                                <?= date('M j, Y', strtotime($withdrawal['expected_return'])) ?>
                            </span>
                            <?php if (strtotime($withdrawal['expected_return']) < time()): ?>
                                <?php $daysOverdue = floor((time() - strtotime($withdrawal['expected_return'])) / (60 * 60 * 24)); ?>
                                <br><small class="text-danger"><?= $daysOverdue ?> days overdue</small>
                            <?php endif; ?>
                        </dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-5">Days Out:</dt>
                    <dd class="col-sm-7">
                        <?php $daysOut = floor((time() - strtotime($withdrawal['released_at'])) / (60 * 60 * 24)); ?>
                        <span class="badge bg-info"><?= $daysOut ?> days</span>
                    </dd>
                </dl>
                
                <div class="mt-3">
                    <h6>Purpose:</h6>
                    <p class="text-muted small"><?= nl2br(htmlspecialchars($withdrawal['purpose'])) ?></p>
                </div>
                
                <?php if ($withdrawal['release_notes']): ?>
                    <div class="mt-3">
                        <h6>Release Notes:</h6>
                        <p class="text-muted small"><?= nl2br(htmlspecialchars($withdrawal['release_notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Return Checklist -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-list-check me-2"></i>Return Checklist
                </h6>
            </div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check1">
                    <label class="form-check-label small" for="check1">
                        Asset physically returned
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check2">
                    <label class="form-check-label small" for="check2">
                        Condition inspected
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check3">
                    <label class="form-check-label small" for="check3">
                        Any damage documented
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check4">
                    <label class="form-check-label small" for="check4">
                        Return notes completed
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check5">
                    <label class="form-check-label small" for="check5">
                        Ready to mark as available
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide damage details based on condition selection
document.querySelectorAll('input[name="condition"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const damageDetails = document.getElementById('damageDetails');
        const damageDescription = document.getElementById('damage_description');
        
        if (this.value === 'damaged') {
            damageDetails.style.display = 'block';
            damageDescription.required = true;
        } else {
            damageDetails.style.display = 'none';
            damageDescription.required = false;
            damageDescription.value = '';
        }
    });
});

// Form validation
document.getElementById('returnForm').addEventListener('submit', function(e) {
    const confirmCheckbox = document.getElementById('confirmReturn');
    const conditionDamaged = document.getElementById('conditionDamaged');
    const damageDescription = document.getElementById('damage_description');
    
    if (!confirmCheckbox.checked) {
        e.preventDefault();
        alert('Please confirm that you have physically received and inspected the asset.');
        return false;
    }
    
    // Check if damage description is required but empty
    if (conditionDamaged.checked && !damageDescription.value.trim()) {
        e.preventDefault();
        alert('Please provide a description of the damage.');
        damageDescription.focus();
        return false;
    }
    
    // Final confirmation
    const conditionText = document.querySelector('input[name="condition"]:checked').nextElementSibling.textContent.trim();
    if (!confirm(`Are you sure you want to process this return with condition: ${conditionText}?`)) {
        e.preventDefault();
        return false;
    }
});

// Enable return button only when all checklist items are checked
const checklistItems = document.querySelectorAll('[id^="check"]');
const confirmCheckbox = document.getElementById('confirmReturn');

function updateSubmitButton() {
    const allChecked = Array.from(checklistItems).every(item => item.checked);
    confirmCheckbox.disabled = !allChecked;
    
    if (!allChecked) {
        confirmCheckbox.checked = false;
    }
}

checklistItems.forEach(item => {
    item.addEventListener('change', updateSubmitButton);
});

// Initialize
updateSubmitButton();
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
