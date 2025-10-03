<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';

$incident = $incident ?? null; // Ensure incident is defined

if (!$incident) {
    echo '<div class="alert alert-danger">Incident not found.</div>';
    return;
}

if (!hasPermission('incidents/resolve') || !in_array($incident['status'], ['Authorized', 'Pending Authorization'])) {
    echo '<div class="alert alert-danger">You do not have permission to resolve this incident.</div>';
    return;
}
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

</div>

<!-- MVA Workflow Sidebar -->
<div class="alert alert-info mb-4">
    <strong>MVA Workflow:</strong> <br>
    <span class="badge bg-primary">Maker</span> (Site Inventory Clerk) →
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-success">Authorizer</span> (Asset Director) →
    <span class="badge bg-secondary">Resolved</span> →
    <span class="badge bg-dark">Closed</span>
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

<!-- Resolution Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Resolution Details
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=incidents/resolve&id=<?= $incident['id'] ?>">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Action Type -->
                    <div class="mb-3">
                        <label for="action" class="form-label">Action Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="action" name="action" required>
                            <?php if ($incident['status'] === 'Pending Authorization'): ?>
                                <option value="authorize">Authorize Incident</option>
                            <?php endif; ?>
                            <?php if (in_array($incident['status'], ['Authorized', 'Pending Authorization'])): ?>
                                <option value="resolve">Resolve Incident</option>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">
                            Choose whether to authorize the incident or resolve it directly.
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors) && in_array('Notes are required', $errors) ? 'is-invalid' : '' ?>" 
                                  id="notes" 
                                  name="notes" 
                                  rows="6" 
                                  required 
                                  placeholder="Provide detailed notes about your authorization or resolution decision..."><?= htmlspecialchars($formData['notes'] ?? '') ?></textarea>
                        <div class="invalid-feedback">
                            Please provide detailed notes.
                        </div>
                        <div class="form-text">
                            Include your reasoning, decisions made, and any relevant details.
                        </div>
                    </div>
                    
                    <!-- Resolution Details (only for resolve action) -->
                    <div class="mb-3" id="resolutionDetails" style="display: none;">
                        <label for="resolution_details" class="form-label">Resolution Details</label>
                        <textarea class="form-control" 
                                  id="resolution_details" 
                                  name="resolution_details" 
                                  rows="4" 
                                  placeholder="Describe how the incident was resolved, actions taken, current status of the asset, and any follow-up required..."><?= htmlspecialchars($formData['resolution_details'] ?? '') ?></textarea>
                        <div class="form-text">
                            Include all actions taken, current asset status, and any recommendations for preventing similar incidents.
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=incidents/view&id=<?= $incident['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Process Incident
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Current Incident Info -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Incident Summary
                </h6>
            </div>
            <div class="card-body">
                <dl class="row small">
                    <dt class="col-sm-5">Incident ID:</dt>
                    <dd class="col-sm-7">#<?= $incident['id'] ?></dd>

                    <dt class="col-sm-5">Asset:</dt>
                    <dd class="col-sm-7">
                        <?= htmlspecialchars($incident['asset_ref']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($incident['asset_name']) ?></small>
                    </dd>

                    <dt class="col-sm-5">Type:</dt>
                    <dd class="col-sm-7">
                        <?php
                        $typeClasses = [
                            'lost' => 'bg-warning',
                            'damaged' => 'bg-info',
                            'stolen' => 'bg-danger',
                            'other' => 'bg-secondary'
                        ];
                        $typeClass = $typeClasses[$incident['type']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $typeClass ?>">
                            <?= ucfirst($incident['type']) ?>
                        </span>
                    </dd>

                    <dt class="col-sm-5">Severity:</dt>
                    <dd class="col-sm-7">
                        <?php
                        $severityClasses = [
                            'low' => 'bg-success',
                            'medium' => 'bg-warning',
                            'high' => 'bg-danger',
                            'critical' => 'bg-dark'
                        ];
                        $severityClass = $severityClasses[$incident['severity']] ?? 'bg-warning';
                        ?>
                        <span class="badge <?= $severityClass ?>">
                            <?= ucfirst($incident['severity']) ?>
                        </span>
                    </dd>

                    <dt class="col-sm-5">Current Status:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-info">
                            <?= ucfirst(str_replace('_', ' ', $incident['status'])) ?>
                        </span>
                    </dd>

                    <dt class="col-sm-5">Date Reported:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($incident['date_reported'])) ?></dd>

                    <dt class="col-sm-5">Reported By:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($incident['reported_by_name']) ?></dd>
                </dl>
            </div>
        </div>

        <!-- Original Description -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-file-text me-2"></i>Original Report
                </h6>
            </div>
            <div class="card-body">
                <h6 class="text-primary">Description:</h6>
                <p class="small mb-3"><?= nl2br(htmlspecialchars($incident['description'])) ?></p>

                <?php if (!empty($incident['location'])): ?>
                    <h6 class="text-primary">Location:</h6>
                    <p class="small mb-3"><?= htmlspecialchars($incident['location']) ?></p>
                <?php endif; ?>

                <?php if (!empty($incident['witnesses'])): ?>
                    <h6 class="text-primary">Witnesses:</h6>
                    <p class="small mb-0"><?= nl2br(htmlspecialchars($incident['witnesses'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Investigation Notes -->
        <?php if (!empty($incident['resolution_notes'])): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-search me-2"></i>Investigation Findings
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small mb-0"><?= nl2br(htmlspecialchars($incident['resolution_notes'])) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Resolution Guidelines -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Resolution Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6 class="text-primary">Resolution Should Include:</h6>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-check text-success me-1"></i> Actions taken to resolve</li>
                    <li><i class="bi bi-check text-success me-1"></i> Current asset status</li>
                    <li><i class="bi bi-check text-success me-1"></i> Recovery details (if applicable)</li>
                    <li><i class="bi bi-check text-success me-1"></i> Repair/replacement information</li>
                    <li><i class="bi bi-check text-success me-1"></i> Preventive measures implemented</li>
                    <li><i class="bi bi-check text-success me-1"></i> Follow-up requirements</li>
                </ul>

                <h6 class="text-warning mt-3">Asset Status Impact:</h6>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-info-circle text-info me-1"></i> <strong>Lost/Stolen (not recovered):</strong> Asset will be marked as retired</li>
                    <li><i class="bi bi-info-circle text-info me-1"></i> <strong>Recovered/Repaired:</strong> Asset will return to available status</li>
                    <li><i class="bi bi-info-circle text-info me-1"></i> <strong>Damaged beyond repair:</strong> Asset may be marked as retired</li>
                </ul>
            </div>
        </div>

        <!-- Resolution Checklist -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-list-check me-2"></i>Resolution Checklist
                </h6>
            </div>
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check1">
                    <label class="form-check-label small" for="check1">
                        Root cause identified
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check2">
                    <label class="form-check-label small" for="check2">
                        Corrective actions taken
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check3">
                    <label class="form-check-label small" for="check3">
                        Asset status determined
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check4">
                    <label class="form-check-label small" for="check4">
                        Preventive measures implemented
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check5">
                    <label class="form-check-label small" for="check5">
                        Documentation completed
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check6">
                    <label class="form-check-label small" for="check6">
                        Stakeholders notified
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const actionSelect = document.getElementById('action');
    const resolutionDetails = document.getElementById('resolutionDetails');
    
    function toggleResolutionDetails() {
        if (actionSelect.value === 'resolve') {
            resolutionDetails.style.display = 'block';
        } else {
            resolutionDetails.style.display = 'none';
        }
    }
    
    actionSelect.addEventListener('change', toggleResolutionDetails);
    toggleResolutionDetails(); // Initial state
    
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const notes = document.getElementById('notes').value.trim();
        const action = actionSelect.value;
        
        if (notes.length < 30) {
            e.preventDefault();
            alert('Please provide more detailed notes (at least 30 characters).');
            document.getElementById('notes').focus();
            return false;
        }
        
        if (action === 'resolve') {
            const resolutionDetails = document.getElementById('resolution_details').value.trim();
            if (resolutionDetails.length < 50) {
                e.preventDefault();
                alert('Please provide more detailed resolution information (at least 50 characters).');
                document.getElementById('resolution_details').focus();
                return false;
            }
        }
        
        // Confirm action
        const actionText = action === 'authorize' ? 'authorize' : 'resolve';
        if (!confirm('Are you sure you want to ' + actionText + ' this incident? This action will advance the incident in the workflow.')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Resolve Incident #' . $incident['id'] . ' - ConstructLink™';
$pageHeader = 'Resolve Incident #' . $incident['id'];
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Incidents', 'url' => '?route=incidents'],
    ['title' => 'Resolve', 'url' => '?route=incidents/resolve&id=' . $incident['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
