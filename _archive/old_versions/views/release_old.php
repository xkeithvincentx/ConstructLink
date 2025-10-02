<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-check-circle me-2"></i>
        Release Asset
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
        <!-- Release Confirmation -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirm Asset Release
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Important:</strong> By releasing this asset, you confirm that:
                    <ul class="mb-0 mt-2">
                        <li>The asset is in good working condition</li>
                        <li>The receiver has been properly identified</li>
                        <li>All necessary documentation has been completed</li>
                        <li>The asset status will be changed to "In Use"</li>
                    </ul>
                </div>
                
                <form method="POST" action="?route=withdrawals/release&id=<?= $withdrawal['id'] ?>" id="releaseForm">
                    <?= CSRFProtection::generateToken() ?>
                    
                    <!-- Release Notes -->
                    <div class="mb-3">
                        <label for="release_notes" class="form-label">Release Notes</label>
                        <textarea class="form-control" 
                                  id="release_notes" 
                                  name="release_notes" 
                                  rows="4" 
                                  placeholder="Enter any notes about the asset release, condition, or special instructions..."></textarea>
                        <div class="form-text">
                            Optional: Add any relevant notes about the asset condition or release process.
                        </div>
                    </div>
                    
                    <!-- Confirmation Checkbox -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmRelease" required>
                            <label class="form-check-label" for="confirmRelease">
                                I confirm that I have verified the asset condition and receiver identity before releasing this asset.
                            </label>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Release Asset
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
                    
                    <dt class="col-sm-5">Requested By:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($withdrawal['withdrawn_by_name']) ?></dd>
                    
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
                        Asset is in good working condition
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check2">
                    <label class="form-check-label small" for="check2">
                        Receiver identity verified
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check3">
                    <label class="form-check-label small" for="check3">
                        Purpose is appropriate
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check4">
                    <label class="form-check-label small" for="check4">
                        Expected return date noted
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check5">
                    <label class="form-check-label small" for="check5">
                        Release documentation complete
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
document.getElementById('releaseForm').addEventListener('submit', function(e) {
    const confirmCheckbox = document.getElementById('confirmRelease');
    
    if (!confirmCheckbox.checked) {
        e.preventDefault();
        alert('Please confirm that you have verified the asset condition and receiver identity.');
        return false;
    }
    
    // Final confirmation
    if (!confirm('Are you sure you want to release this asset? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
});

// Enable release button only when all checklist items are checked
const checklistItems = document.querySelectorAll('[id^="check"]');
const confirmCheckbox = document.getElementById('confirmRelease');
const submitButton = document.querySelector('button[type="submit"]');

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
