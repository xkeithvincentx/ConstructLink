<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();

$tool = $borrowedTool; // Alias for easier access

// Check permissions and set error flag
$hasError = false;
$errorMessage = '';

if (!$borrowedTool) {
    $hasError = true;
    $errorMessage = 'Borrowed tool not found.';
} elseif ($borrowedTool['status'] !== 'Borrowed') {
    $hasError = true;
    $errorMessage = 'This tool cannot be returned in its current status.';
} elseif (!in_array($user['role_name'], ['System Admin', 'Warehouseman', 'Site Inventory Clerk'])) {
    $hasError = true;
    $errorMessage = 'You do not have permission to return this tool.';
}
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- MVA Workflow Sidebar -->
<div class="alert alert-info mb-4">
    <strong>MVA Workflow:</strong> <br>
    <span class="badge bg-primary">Maker</span> (Warehouseman) →
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-success">Authorizer</span> (Asset Director/Finance Director) →
    <span class="badge bg-secondary">Borrowed</span> →
    <span class="badge bg-success">Returned</span> / <span class="badge bg-danger">Overdue</span>
</div>

<!-- Messages -->
<?php if ($hasError): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Error:</strong> <?= htmlspecialchars($errorMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Return Tool Form -->
<?php if (!$hasError): ?>
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-arrow-return-left me-2"></i>Return Tool Information
                </h5>
            </div>
            <div class="card-body">
                <!-- Current Borrowing Info -->
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle me-1"></i>Current Borrowing Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Asset:</strong> <?= htmlspecialchars($borrowedTool['asset_name']) ?><br>
                            <strong>Reference:</strong> <?= htmlspecialchars($borrowedTool['asset_ref']) ?><br>
                            <strong>Borrower:</strong> <?= htmlspecialchars($borrowedTool['borrower_name']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Borrowed Date:</strong> <?= formatDate($borrowedTool['created_at']) ?><br>
                            <strong>Expected Return:</strong> <?= formatDate($borrowedTool['expected_return']) ?><br>
                            <?php 
                            $isOverdue = strtotime($borrowedTool['expected_return']) < time();
                            if ($isOverdue):
                                $daysOverdue = floor((time() - strtotime($borrowedTool['expected_return'])) / 86400);
                            ?>
                                <strong class="text-danger">Status:</strong> 
                                <span class="badge bg-danger"><?= $daysOverdue ?> days overdue</span>
                            <?php else: ?>
                                <strong>Status:</strong> <span class="badge bg-warning">On time</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form method="POST" action="?route=borrowed-tools/return&id=<?= $borrowedTool['id'] ?>" class="needs-validation" novalidate>
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Return Condition -->
                    <div class="mb-4">
                        <label for="condition_in" class="form-label">Tool Condition Upon Return <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="condition_in" 
                                  name="condition_in" 
                                  rows="4" 
                                  required
                                  placeholder="Describe the condition of the tool being returned..."><?= htmlspecialchars($formData['condition_in'] ?? '') ?></textarea>
                        <div class="form-text">
                            Please document any damage, wear, or issues with the tool
                        </div>
                        <div class="invalid-feedback">
                            Please describe the tool's condition upon return.
                        </div>
                    </div>

                    <!-- Return Notes -->
                    <div class="mb-4">
                        <label for="return_notes" class="form-label">Return Notes</label>
                        <textarea class="form-control" 
                                  id="return_notes" 
                                  name="return_notes" 
                                  rows="3" 
                                  placeholder="Any additional notes about the return..."><?= htmlspecialchars($formData['return_notes'] ?? '') ?></textarea>
                        <div class="form-text">
                            Optional: Add any additional information about the return
                        </div>
                    </div>

                    <!-- Condition Comparison -->
                    <?php if (!empty($borrowedTool['condition_out'])): ?>
                    <div class="mb-4">
                        <h6>Condition Comparison</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <small class="fw-bold">Condition When Borrowed</small>
                                    </div>
                                    <div class="card-body">
                                        <small><?= nl2br(htmlspecialchars($borrowedTool['condition_out'])) ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <small class="fw-bold">Condition Now (Return)</small>
                                    </div>
                                    <div class="card-body">
                                        <small class="text-muted">Fill in the form above</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=borrowed-tools/view&id=<?= $borrowedTool['id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Confirm Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar with Guidelines -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Return Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Before Returning</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check text-success me-1"></i> Inspect tool thoroughly</li>
                        <li><i class="bi bi-check text-success me-1"></i> Clean if necessary</li>
                        <li><i class="bi bi-check text-success me-1"></i> Check for damage or wear</li>
                        <li><i class="bi bi-check text-success me-1"></i> Gather all accessories</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6>Documentation Required</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check-circle text-success me-1"></i> Tool condition description</li>
                        <li><i class="bi bi-circle text-muted me-1"></i> Return notes (optional)</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6>What Happens Next</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Tool status updated to "returned"</li>
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Asset becomes available again</li>
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Return logged in system</li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <i class="bi bi-lightbulb me-1"></i>
                        <strong>Tip:</strong> Be honest about the tool's condition. This helps maintain our equipment properly.
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Tool Information -->
        <div class="card mt-3 shadow-sm">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-tools me-2"></i>Tool Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row small">
                    <dt class="col-sm-4">Asset:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($borrowedTool['asset_name']) ?></dd>
                    
                    <dt class="col-sm-4">Reference:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($borrowedTool['asset_ref']) ?></dd>
                    
                    <dt class="col-sm-4">Category:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($borrowedTool['category_name'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-4">Project:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($borrowedTool['project_name'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-4">Borrowed:</dt>
                    <dd class="col-sm-8"><?= formatDate($borrowedTool['created_at']) ?></dd>
                    
                    <dt class="col-sm-4">Duration:</dt>
                    <dd class="col-sm-8">
                        <?php
                        $startDate = new DateTime($borrowedTool['created_at']);
                        $endDate = new DateTime();
                        $duration = $startDate->diff($endDate);
                        echo $duration->days . ' day(s)';
                        ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Auto-suggest condition based on original condition
document.addEventListener('DOMContentLoaded', function() {
    const conditionField = document.getElementById('condition_in');
    const originalCondition = <?= json_encode($borrowedTool['condition_out'] ?? '') ?>;
    
    if (originalCondition && !conditionField.value) {
        // Suggest similar condition
        const suggestions = [
            'Same condition as when borrowed',
            'Good condition, normal wear',
            'Excellent condition, well maintained',
            'Fair condition, some additional wear'
        ];
        
        conditionField.placeholder = suggestions[0] + ' - ' + originalCondition;
    }
    
    // Auto-expand textarea as user types
    conditionField.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Return Tool - ConstructLink™';
$pageHeader = 'Return Tool: ' . htmlspecialchars($borrowedTool['asset_name'] ?? 'Unknown');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
    ['title' => 'Return Tool', 'url' => '?route=borrowed-tools/return&id=' . ($borrowedTool['id'] ?? 0)]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
