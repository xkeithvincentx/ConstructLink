<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

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

<!-- Edit Incident Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Incident Details
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=incidents/edit&id=<?= $incident['id'] ?>">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Asset (Read-only) -->
                    <div class="mb-3">
                        <label class="form-label">Asset</label>
                        <div class="form-control-plaintext bg-light p-2 rounded">
                            <strong><?= htmlspecialchars($incident['asset_ref']) ?></strong> - 
                            <?= htmlspecialchars($incident['asset_name']) ?>
                        </div>
                        <div class="form-text">Asset cannot be changed after incident is created.</div>
                    </div>

                    <div class="row">
                        <!-- Incident Type -->
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Incident Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="lost" <?= ($formData['type'] ?? $incident['type']) === 'lost' ? 'selected' : '' ?>>Lost</option>
                                <option value="damaged" <?= ($formData['type'] ?? $incident['type']) === 'damaged' ? 'selected' : '' ?>>Damaged</option>
                                <option value="stolen" <?= ($formData['type'] ?? $incident['type']) === 'stolen' ? 'selected' : '' ?>>Stolen</option>
                                <option value="other" <?= ($formData['type'] ?? $incident['type']) === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <!-- Severity -->
                        <div class="col-md-6 mb-3">
                            <label for="severity" class="form-label">Severity</label>
                            <select class="form-select" id="severity" name="severity">
                                <option value="low" <?= ($formData['severity'] ?? $incident['severity']) === 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= ($formData['severity'] ?? $incident['severity']) === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= ($formData['severity'] ?? $incident['severity']) === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="critical" <?= ($formData['severity'] ?? $incident['severity']) === 'critical' ? 'selected' : '' ?>>Critical</option>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors) && in_array('Description is required', $errors) ? 'is-invalid' : '' ?>" 
                                  id="description" 
                                  name="description" 
                                  rows="4" 
                                  required 
                                  placeholder="Describe what happened, when it occurred, and any relevant details..."><?= htmlspecialchars($formData['description'] ?? $incident['description']) ?></textarea>
                        <div class="invalid-feedback">
                            Please provide a description of the incident.
                        </div>
                    </div>

                    <div class="row">
                        <!-- Location -->
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="location" 
                                   name="location" 
                                   value="<?= htmlspecialchars($formData['location'] ?? $incident['location']) ?>" 
                                   placeholder="Where did this incident occur?">
                        </div>

                        <!-- Date Reported -->
                        <div class="col-md-6 mb-3">
                            <label for="date_reported" class="form-label">Date Reported <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control" 
                                   id="date_reported" 
                                   name="date_reported" 
                                   value="<?= htmlspecialchars($formData['date_reported'] ?? $incident['date_reported']) ?>" 
                                   required>
                        </div>
                    </div>

                    <!-- Witnesses -->
                    <div class="mb-3">
                        <label for="witnesses" class="form-label">Witnesses</label>
                        <textarea class="form-control" 
                                  id="witnesses" 
                                  name="witnesses" 
                                  rows="3" 
                                  placeholder="List any witnesses to the incident (names, contact information, etc.)"><?= htmlspecialchars($formData['witnesses'] ?? $incident['witnesses']) ?></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=incidents/view&id=<?= $incident['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Update Incident
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Current Status -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Current Status
                </h6>
            </div>
            <div class="card-body">
                <dl class="row small">
                    <dt class="col-sm-5">Incident ID:</dt>
                    <dd class="col-sm-7">#<?= $incident['id'] ?></dd>

                    <dt class="col-sm-5">Status:</dt>
                    <dd class="col-sm-7">
                        <?php
                        $statusClasses = [
                            'under_investigation' => 'bg-warning',
                            'verified' => 'bg-info',
                            'resolved' => 'bg-success',
                            'closed' => 'bg-secondary'
                        ];
                        $statusClass = $statusClasses[$incident['status']] ?? 'bg-warning';
                        ?>
                        <span class="badge <?= $statusClass ?>">
                            <?= ucfirst(str_replace('_', ' ', $incident['status'])) ?>
                        </span>
                    </dd>

                    <dt class="col-sm-5">Reported By:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($incident['reported_by_name']) ?></dd>

                    <dt class="col-sm-5">Date Created:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($incident['created_at'])) ?></dd>

                    <dt class="col-sm-5">Last Updated:</dt>
                    <dd class="col-sm-7">
                        <?= $incident['updated_at'] ? date('M j, Y', strtotime($incident['updated_at'])) : 'Never' ?>
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Asset Information -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box me-2"></i>Asset Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row small">
                    <dt class="col-sm-5">Reference:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($incident['asset_ref']) ?></dd>

                    <dt class="col-sm-5">Name:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($incident['asset_name']) ?></dd>

                    <dt class="col-sm-5">Category:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($incident['category_name'] ?? 'N/A') ?></dd>

                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($incident['project_name'] ?? 'N/A') ?></dd>

                    <dt class="col-sm-5">Current Status:</dt>
                    <dd class="col-sm-7">
                        <?php
                        $assetStatusClasses = [
                            'available' => 'success',
                            'in_use' => 'primary',
                            'borrowed' => 'warning',
                            'under_maintenance' => 'info',
                            'retired' => 'secondary'
                        ];
                        $assetStatusClass = $assetStatusClasses[$incident['asset_status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $assetStatusClass ?>">
                            <?= ucfirst(str_replace('_', ' ', $incident['asset_status'])) ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Edit Guidelines -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Edit Guidelines
                </h6>
            </div>
            <div class="card-body">
                <?php if (in_array($incident['status'], ['resolved', 'closed'])): ?>
                    <div class="alert alert-warning">
                        <small>
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Note:</strong> This incident is <?= $incident['status'] ?> and cannot be edited.
                        </small>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Editable Status:</strong> You can modify incident details while it's under investigation.
                        </small>
                    </div>
                <?php endif; ?>

                <h6 class="text-primary">Editable Fields:</h6>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-check text-success me-1"></i> Incident Type</li>
                    <li><i class="bi bi-check text-success me-1"></i> Severity Level</li>
                    <li><i class="bi bi-check text-success me-1"></i> Description</li>
                    <li><i class="bi bi-check text-success me-1"></i> Location</li>
                    <li><i class="bi bi-check text-success me-1"></i> Date Reported</li>
                    <li><i class="bi bi-check text-success me-1"></i> Witnesses</li>
                </ul>

                <h6 class="text-warning mt-3">Non-Editable:</h6>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-x text-danger me-1"></i> Asset Assignment</li>
                    <li><i class="bi bi-x text-danger me-1"></i> Reporter</li>
                    <li><i class="bi bi-x text-danger me-1"></i> Status (use action buttons)</li>
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
                    <a href="?route=incidents/view&id=<?= $incident['id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye"></i> View Details
                    </a>
                    <a href="?route=assets/view&id=<?= $incident['asset_id'] ?>" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-box"></i> View Asset
                    </a>
                    <?php if ($incident['status'] === 'under_investigation'): ?>
                        <a href="?route=incidents/investigate&id=<?= $incident['id'] ?>" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-search"></i> Complete Investigation
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-set severity based on incident type
document.getElementById('type').addEventListener('change', function() {
    const type = this.value;
    const severitySelect = document.getElementById('severity');
    
    // Suggest severity based on type
    switch(type) {
        case 'lost':
        case 'stolen':
            if (severitySelect.value === 'low' || severitySelect.value === 'medium') {
                if (confirm('Lost and stolen incidents typically require high severity. Would you like to set severity to high?')) {
                    severitySelect.value = 'high';
                }
            }
            break;
        case 'damaged':
            if (severitySelect.value === 'low') {
                if (confirm('Damaged asset incidents typically require at least medium severity. Would you like to set severity to medium?')) {
                    severitySelect.value = 'medium';
                }
            }
            break;
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const description = document.getElementById('description').value.trim();
    
    if (description.length < 10) {
        e.preventDefault();
        alert('Please provide a more detailed description (at least 10 characters).');
        document.getElementById('description').focus();
        return false;
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Edit Incident #' . $incident['id'] . ' - ConstructLink™';
$pageHeader = 'Edit Incident #' . $incident['id'];
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Incidents', 'url' => '?route=incidents'],
    ['title' => 'Edit Incident', 'url' => '?route=incidents/edit&id=' . $incident['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
