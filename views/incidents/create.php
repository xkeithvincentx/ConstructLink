<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';

if (!hasPermission('incidents/create')) {
    echo '<div class="alert alert-danger">You do not have permission to create an incident report.</div>';
    return;
}

// Get pre-fill parameters from URL (from borrowed-tools integration)
$assetRef = $_GET['asset_ref'] ?? '';
$prefillType = $_GET['type'] ?? '';
$prefillSeverity = $_GET['severity'] ?? '';
$prefillDescription = $_GET['description'] ?? '';
$borrowedToolId = $_GET['borrowed_tool_id'] ?? '';
?>

<!-- MVA Workflow Sidebar -->
<div class="alert alert-info mb-4">
    <strong>MVA Workflow:</strong> <br>
    <span class="badge bg-primary">Maker</span> (Site Inventory Clerk) →
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-success">Authorizer</span> (Asset Director) →
    <span class="badge bg-secondary">Resolved</span> →
    <span class="badge bg-dark">Closed</span>
</div>

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

<!-- Create Incident Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Incident Details
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=incidents/create">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Asset Selection -->
                    <div class="mb-3">
                        <label for="asset_id" class="form-label">Asset <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors) && in_array('Asset is required', $errors) ? 'is-invalid' : '' ?>" 
                                id="asset_id" 
                                name="asset_id" 
                                required>
                            <option value="">Select Asset</option>
                            <?php if (isset($assets) && is_array($assets)): ?>
                                <?php foreach ($assets as $asset): ?>
                                    <option value="<?= $asset['id'] ?>" 
                                            <?= ($formData['asset_id'] ?? '') == $asset['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($asset['ref']) ?> - <?= htmlspecialchars($asset['name']) ?>
                                        <?php if (!empty($asset['project_name'])): ?>
                                            (<?= htmlspecialchars($asset['project_name']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback">
                            Please select an asset.
                        </div>
                    </div>

                    <div class="row">
                        <!-- Incident Type -->
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Incident Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="lost" <?= ($formData['type'] ?? $prefillType) === 'lost' ? 'selected' : '' ?>>Lost</option>
                                <option value="damaged" <?= ($formData['type'] ?? $prefillType) === 'damaged' ? 'selected' : '' ?>>Damaged</option>
                                <option value="stolen" <?= ($formData['type'] ?? $prefillType) === 'stolen' ? 'selected' : '' ?>>Stolen</option>
                                <option value="other" <?= ($formData['type'] ?? $prefillType ?: 'other') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <!-- Severity -->
                        <div class="col-md-6 mb-3">
                            <label for="severity" class="form-label">Severity</label>
                            <select class="form-select" id="severity" name="severity">
                                <option value="low" <?= ($formData['severity'] ?? $prefillSeverity) === 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= ($formData['severity'] ?? $prefillSeverity ?: 'medium') === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= ($formData['severity'] ?? $prefillSeverity) === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="critical" <?= ($formData['severity'] ?? $prefillSeverity) === 'critical' ? 'selected' : '' ?>>Critical</option>
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
                                  placeholder="Describe what happened, when it occurred, and any relevant details..."><?= htmlspecialchars($formData['description'] ?? $prefillDescription) ?></textarea>
                        <div class="invalid-feedback">
                            Please provide a description of the incident.
                        </div>
                    </div>

                    <!-- Hidden field for borrowed_tool_id -->
                    <?php if (!empty($borrowedToolId)): ?>
                        <input type="hidden" name="borrowed_tool_id" value="<?= htmlspecialchars($borrowedToolId) ?>">
                    <?php endif; ?>

                    <div class="row">
                        <!-- Location -->
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="location" 
                                   name="location" 
                                   value="<?= htmlspecialchars($formData['location'] ?? '') ?>" 
                                   placeholder="Where did this incident occur?">
                        </div>

                        <!-- Date Reported -->
                        <div class="col-md-6 mb-3">
                            <label for="date_reported" class="form-label">Date Reported <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control" 
                                   id="date_reported" 
                                   name="date_reported" 
                                   value="<?= htmlspecialchars($formData['date_reported'] ?? date('Y-m-d')) ?>" 
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
                                  placeholder="List any witnesses to the incident (names, contact information, etc.)"><?= htmlspecialchars($formData['witnesses'] ?? '') ?></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=incidents" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i>Report Incident
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Incident Types Guide -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Incident Types
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-danger">Lost</h6>
                    <p class="small text-muted">Asset cannot be located despite search efforts. May require replacement.</p>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-warning">Damaged</h6>
                    <p class="small text-muted">Asset is physically damaged but may be repairable. Assess repair costs vs replacement.</p>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-dark">Stolen</h6>
                    <p class="small text-muted">Asset was taken without authorization. May require police report and insurance claim.</p>
                </div>
                
                <div class="mb-0">
                    <h6 class="text-secondary">Other</h6>
                    <p class="small text-muted">Any other incident not covered by the above categories.</p>
                </div>
            </div>
        </div>

        <!-- Severity Levels -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-speedometer2 me-2"></i>Severity Levels
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge bg-success me-2">Low</span>
                    <small>Minor impact, no immediate action required</small>
                </div>
                
                <div class="mb-2">
                    <span class="badge bg-warning me-2">Medium</span>
                    <small>Moderate impact, investigate within 48 hours</small>
                </div>
                
                <div class="mb-2">
                    <span class="badge bg-danger me-2">High</span>
                    <small>Significant impact, investigate within 24 hours</small>
                </div>
                
                <div class="mb-0">
                    <span class="badge bg-dark me-2">Critical</span>
                    <small>Severe impact, immediate investigation required</small>
                </div>
            </div>
        </div>

        <!-- Reporting Guidelines -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Reporting Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6 class="text-primary">When to Report:</h6>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-check text-success me-1"></i> Asset is missing or cannot be located</li>
                    <li><i class="bi bi-check text-success me-1"></i> Asset is damaged beyond normal wear</li>
                    <li><i class="bi bi-check text-success me-1"></i> Asset has been stolen or misappropriated</li>
                    <li><i class="bi bi-check text-success me-1"></i> Any unusual circumstances affecting assets</li>
                </ul>

                <h6 class="text-warning mt-3">Include in Description:</h6>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-check text-success me-1"></i> What happened</li>
                    <li><i class="bi bi-check text-success me-1"></i> When it was discovered</li>
                    <li><i class="bi bi-check text-success me-1"></i> Who was involved</li>
                    <li><i class="bi bi-check text-success me-1"></i> Potential causes</li>
                    <li><i class="bi bi-check text-success me-1"></i> Actions already taken</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-select asset based on asset_ref URL parameter
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const assetRef = urlParams.get('asset_ref');

    if (assetRef) {
        const assetSelect = document.getElementById('asset_id');
        const options = assetSelect.options;

        for (let i = 0; i < options.length; i++) {
            const optionText = options[i].text;
            if (optionText.startsWith(assetRef + ' -')) {
                assetSelect.selectedIndex = i;
                break;
            }
        }
    }
});

// Auto-set severity based on incident type
document.getElementById('type').addEventListener('change', function() {
    const type = this.value;
    const severitySelect = document.getElementById('severity');

    // Suggest severity based on type
    switch(type) {
        case 'lost':
        case 'stolen':
            if (severitySelect.value === 'low' || severitySelect.value === 'medium') {
                severitySelect.value = 'high';
            }
            break;
        case 'damaged':
            if (severitySelect.value === 'low') {
                severitySelect.value = 'medium';
            }
            break;
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const assetId = document.getElementById('asset_id').value;
    const description = document.getElementById('description').value.trim();

    if (!assetId) {
        e.preventDefault();
        alert('Please select an asset.');
        document.getElementById('asset_id').focus();
        return false;
    }

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
$pageTitle = 'Report Incident - ConstructLink™';
$pageHeader = 'Report Incident';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Incidents', 'url' => '?route=incidents'],
    ['title' => 'Report Incident', 'url' => '?route=incidents/create']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
