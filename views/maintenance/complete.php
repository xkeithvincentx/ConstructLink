<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-check-circle me-2"></i>
        Complete Maintenance #<?= $maintenance['id'] ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group">
            <a href="?route=maintenance/view&id=<?= $maintenance['id'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Details
            </a>
            <a href="?route=maintenance" class="btn btn-outline-secondary">
                <i class="bi bi-list me-1"></i>All Maintenance
            </a>
        </div>
    </div>
</div>

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h6><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Success Messages -->
<?php if (!empty($messages)): ?>
    <div class="alert alert-success">
        <?php foreach ($messages as $message): ?>
            <div><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Complete Maintenance Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Completion Details
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=maintenance/complete&id=<?= $maintenance['id'] ?>">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Completion Notes -->
                    <div class="mb-3">
                        <label for="completion_notes" class="form-label">Work Performed / Completion Notes <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors) && in_array('Completion notes are required', $errors) ? 'is-invalid' : '' ?>" 
                                  id="completion_notes" 
                                  name="completion_notes" 
                                  rows="6" 
                                  required 
                                  placeholder="Describe the work performed, issues resolved, and any observations..."><?= htmlspecialchars($formData['completion_notes'] ?? '') ?></textarea>
                        <div class="invalid-feedback">
                            Please provide details about the work performed.
                        </div>
                        <div class="form-text">
                            Include details about what was done, parts replaced, issues found, and current condition of the asset.
                        </div>
                    </div>

                    <div class="row">
                        <!-- Actual Cost -->
                        <div class="col-md-6 mb-3">
                            <label for="actual_cost" class="form-label">Actual Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" 
                                       class="form-control" 
                                       id="actual_cost" 
                                       name="actual_cost" 
                                       value="<?= htmlspecialchars($formData['actual_cost'] ?? '') ?>" 
                                       step="0.01" 
                                       min="0" 
                                       placeholder="0.00">
                            </div>
                            <div class="form-text">
                                <?php if ($maintenance['estimated_cost']): ?>
                                    Estimated cost was ₱<?= number_format($maintenance['estimated_cost'], 2) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Next Maintenance Date -->
                        <div class="col-md-6 mb-3">
                            <label for="next_maintenance_date" class="form-label">Next Maintenance Date</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="next_maintenance_date" 
                                   name="next_maintenance_date" 
                                   value="<?= htmlspecialchars($formData['next_maintenance_date'] ?? '') ?>" 
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                            <div class="form-text">
                                Recommended date for next maintenance (optional)
                            </div>
                        </div>
                    </div>

                    <!-- Parts Used -->
                    <div class="mb-3">
                        <label for="parts_used" class="form-label">Parts Used</label>
                        <textarea class="form-control" 
                                  id="parts_used" 
                                  name="parts_used" 
                                  rows="4" 
                                  placeholder="List parts, materials, or consumables used during maintenance..."><?= htmlspecialchars($formData['parts_used'] ?? '') ?></textarea>
                        <div class="form-text">
                            Include part numbers, quantities, and suppliers if applicable.
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=maintenance/view&id=<?= $maintenance['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Complete Maintenance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Current Maintenance Info -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Current Maintenance
                </h6>
            </div>
            <div class="card-body">
                <dl class="row small">
                    <dt class="col-sm-5">Maintenance ID:</dt>
                    <dd class="col-sm-7">#<?= $maintenance['id'] ?></dd>

                    <dt class="col-sm-5">Asset:</dt>
                    <dd class="col-sm-7">
                        <?= htmlspecialchars($maintenance['asset_ref']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($maintenance['asset_name']) ?></small>
                    </dd>

                    <dt class="col-sm-5">Type:</dt>
                    <dd class="col-sm-7">
                        <?php
                        $typeClasses = [
                            'preventive' => 'bg-info',
                            'corrective' => 'bg-warning',
                            'emergency' => 'bg-danger'
                        ];
                        $typeClass = $typeClasses[$maintenance['type']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $typeClass ?>">
                            <?= ucfirst($maintenance['type']) ?>
                        </span>
                    </dd>

                    <dt class="col-sm-5">Priority:</dt>
                    <dd class="col-sm-7">
                        <?php
                        $priorityClasses = [
                            'low' => 'bg-success',
                            'medium' => 'bg-warning',
                            'high' => 'bg-danger',
                            'urgent' => 'bg-dark'
                        ];
                        $priorityClass = $priorityClasses[$maintenance['priority']] ?? 'bg-warning';
                        ?>
                        <span class="badge <?= $priorityClass ?>">
                            <?= ucfirst($maintenance['priority']) ?>
                        </span>
                    </dd>

                    <dt class="col-sm-5">Scheduled:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($maintenance['scheduled_date'])) ?></dd>

                    <dt class="col-sm-5">Assigned To:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['assigned_to'] ?: 'Unassigned') ?></dd>

                    <?php if ($maintenance['estimated_cost']): ?>
                        <dt class="col-sm-5">Estimated Cost:</dt>
                        <dd class="col-sm-7">₱<?= number_format($maintenance['estimated_cost'], 2) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Original Description -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-file-text me-2"></i>Original Description
                </h6>
            </div>
            <div class="card-body">
                <p class="small mb-0"><?= nl2br(htmlspecialchars($maintenance['description'])) ?></p>
            </div>
        </div>

        <!-- Completion Guidelines -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Completion Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6 class="text-primary">Work Performed Notes Should Include:</h6>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-check text-success me-1"></i> Tasks completed</li>
                    <li><i class="bi bi-check text-success me-1"></i> Issues found and resolved</li>
                    <li><i class="bi bi-check text-success me-1"></i> Current asset condition</li>
                    <li><i class="bi bi-check text-success me-1"></i> Any recommendations</li>
                </ul>

                <h6 class="text-warning mt-3">Parts Documentation:</h6>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-check text-success me-1"></i> Part numbers and quantities</li>
                    <li><i class="bi bi-check text-success me-1"></i> Supplier information</li>
                    <li><i class="bi bi-check text-success me-1"></i> Cost breakdown</li>
                </ul>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=assets/view&id=<?= $maintenance['asset_id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-box"></i> View Asset Details
                    </a>
                    <a href="?route=maintenance&asset_id=<?= $maintenance['asset_id'] ?>" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-clock-history"></i> Maintenance History
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-suggest next maintenance date based on type
document.addEventListener('DOMContentLoaded', function() {
    const nextMaintenanceInput = document.getElementById('next_maintenance_date');
    const maintenanceType = '<?= $maintenance['type'] ?>';
    
    if (!nextMaintenanceInput.value) {
        let months = 6; // Default for preventive
        
        switch(maintenanceType) {
            case 'preventive':
                months = 6;
                break;
            case 'corrective':
                months = 3;
                break;
            case 'emergency':
                months = 1;
                break;
        }
        
        const nextDate = new Date();
        nextDate.setMonth(nextDate.getMonth() + months);
        nextMaintenanceInput.value = nextDate.toISOString().split('T')[0];
    }
});

// Validate form before submission
document.querySelector('form').addEventListener('submit', function(e) {
    const completionNotes = document.getElementById('completion_notes').value.trim();
    
    if (completionNotes.length < 10) {
        e.preventDefault();
        alert('Please provide more detailed completion notes (at least 10 characters).');
        document.getElementById('completion_notes').focus();
        return false;
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Complete Maintenance #' . $maintenance['id'] . ' - ConstructLink™';
$pageHeader = 'Complete Maintenance #' . $maintenance['id'];
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Maintenance', 'url' => '?route=maintenance'],
    ['title' => 'Complete Maintenance', 'url' => '?route=maintenance/complete&id=' . $maintenance['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
