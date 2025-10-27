<?php
// Start output buffering to capture content
ob_start();


$auth = Auth::getInstance();
$user = $auth->getCurrentUser();

$borrowedTool = $borrowedTool ?? null; // Ensure borrowedTool is defined

// Check permissions and set error flag
$hasError = false;
$errorMessage = '';

if (!$borrowedTool) {
    $hasError = true;
    $errorMessage = 'Borrowed tool not found.';
} elseif (!in_array($borrowedTool['status'], ['Borrowed', 'Overdue'])) {
    $hasError = true;
    $errorMessage = 'This tool cannot be extended in its current status.';
} elseif (!in_array($user['role_name'], ['System Admin', 'Asset Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])) {
    $hasError = true;
    $errorMessage = 'You do not have permission to extend this borrowing period.';
}
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

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

<!-- MVA Workflow Sidebar -->
<div class="alert alert-info mb-4">
    <strong>MVA Workflow:</strong> <br>
    <span class="badge bg-primary">Maker</span> (Warehouseman) →
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-success">Authorizer</span> (Asset Director/Finance Director) →
    <span class="badge bg-secondary">Borrowed</span> →
    <span class="badge bg-success">Returned</span> / <span class="badge bg-danger">Overdue</span>
</div>

<!-- Extend Borrowing Form -->
<?php if (!$hasError): ?>
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-plus me-2"></i>Extend Borrowing Period
                </h5>
            </div>
            <div class="card-body">
                <!-- Current Borrowing Info -->
                <div class="alert alert-warning">
                    <h6><i class="bi bi-clock-history me-1"></i>Current Borrowing Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Asset:</strong> <?= htmlspecialchars($borrowedTool['asset_name']) ?><br>
                            <strong>Reference:</strong> <?= htmlspecialchars($borrowedTool['asset_ref']) ?><br>
                            <strong>Borrower:</strong> <?= htmlspecialchars($borrowedTool['borrower_name']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Borrowed Date:</strong> <?= formatDate($borrowedTool['created_at']) ?><br>
                            <strong>Current Expected Return:</strong> <?= formatDate($borrowedTool['expected_return']) ?><br>
                            <?php 
                            $isOverdue = strtotime($borrowedTool['expected_return']) < time();
                            if ($isOverdue):
                                $daysOverdue = floor((time() - strtotime($borrowedTool['expected_return'])) / 86400);
                            ?>
                                <strong class="text-danger">Status:</strong> 
                                <span class="badge bg-danger"><?= $daysOverdue ?> days overdue</span>
                            <?php else: ?>
                                <?php
                                $daysRemaining = floor((strtotime($borrowedTool['expected_return']) - time()) / 86400);
                                ?>
                                <strong>Status:</strong> 
                                <span class="badge bg-success"><?= $daysRemaining ?> days remaining</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form method="POST" action="?route=borrowed-tools/extend&id=<?= $borrowedTool['id'] ?>" class="needs-validation" novalidate x-data="extendForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- New Expected Return Date -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="new_expected_return" class="form-label">New Expected Return Date <span class="text-danger">*</span></label>
                            <input type="date"
                                   class="form-control"
                                   id="new_expected_return"
                                   name="new_expected_return"
                                   value="<?= htmlspecialchars($formData['new_expected_return'] ?? '') ?>"
                                   data-original-date="<?= $borrowedTool['expected_return'] ?>"
                                   required
                                   x-model="formData.new_expected_return">
                            <div class="form-text">Select the new date when the tool should be returned</div>
                            <div class="invalid-feedback">Please provide the new expected return date.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Extension Period</label>
                            <div class="form-control-plaintext" id="extensionPeriod">
                                <span class="text-muted">Select new return date to see extension</span>
                            </div>
                        </div>
                    </div>

                    <!-- Reason for Extension -->
                    <div class="mb-4">
                        <label for="reason" class="form-label">Reason for Extension <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="reason" 
                                  name="reason" 
                                  rows="4" 
                                  required
                                  placeholder="Please explain why the borrowing period needs to be extended..."
                                  x-model="formData.reason"><?= htmlspecialchars($formData['reason'] ?? '') ?></textarea>
                        <div class="form-text">
                            Provide a clear justification for the extension request
                        </div>
                        <div class="invalid-feedback">
                            Please provide a reason for the extension.
                        </div>
                    </div>

                    <!-- Extension Summary -->
                    <div class="mb-4" x-show="formData.new_expected_return">
                        <h6>Extension Summary</h6>
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Original Return Date:</strong><br>
                                        <span class="text-muted"><?= formatDate($borrowedTool['expected_return']) ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>New Return Date:</strong><br>
                                        <span class="text-primary" x-text="formatDate(formData.new_expected_return)"></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Extension Period:</strong><br>
                                        <span class="text-info" id="extensionDays">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=borrowed-tools/view&id=<?= $borrowedTool['id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-calendar-plus me-1"></i>Extend Borrowing Period
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
                    <i class="bi bi-exclamation-triangle me-2"></i>Extension Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>When to Extend</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check text-success me-1"></i> Project delays</li>
                        <li><i class="bi bi-check text-success me-1"></i> Additional work required</li>
                        <li><i class="bi bi-check text-success me-1"></i> Equipment still needed</li>
                        <li><i class="bi bi-check text-success me-1"></i> Unforeseen circumstances</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6>Extension Limits</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-info-circle text-info me-1"></i> Maximum 30 days per extension</li>
                        <li><i class="bi bi-info-circle text-info me-1"></i> Up to 2 extensions per borrowing</li>
                        <li><i class="bi bi-info-circle text-info me-1"></i> Valid reason required</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6>What Happens Next</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> New return date set</li>
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Extension logged in system</li>
                        <li><i class="bi bi-arrow-right text-primary me-1"></i> Borrower notified (if applicable)</li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <i class="bi bi-lightbulb me-1"></i>
                        <strong>Note:</strong> Extensions should be requested before the original return date when possible.
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Borrowing History -->
        <div class="card mt-3 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Borrowing Timeline
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Tool Borrowed</h6>
                            <p class="timeline-text small text-muted">
                                <?= formatDateTime($borrowedTool['created_at']) ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($isOverdue): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-danger"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Became Overdue</h6>
                            <p class="timeline-text small text-muted">
                                <?= formatDate($borrowedTool['expected_return']) ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Extension Request</h6>
                            <p class="timeline-text small text-muted">
                                Now
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tool Information -->
        <div class="card mt-3 shadow-sm">
            <div class="card-header bg-dark text-white">
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
                    
                    <dt class="col-sm-4">Total Days:</dt>
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

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Load module-specific assets
AssetHelper::loadModuleCSS('borrowed-tools-extend');
AssetHelper::loadModuleJS('extend');

// Set page variables
$pageTitle = 'Extend Borrowing - ConstructLink™';
$pageHeader = 'Extend Borrowing: ' . htmlspecialchars($borrowedTool['asset_name'] ?? 'Unknown');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
    ['title' => 'Extend Borrowing', 'url' => '?route=borrowed-tools/extend&id=' . ($borrowedTool['id'] ?? 0)]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
