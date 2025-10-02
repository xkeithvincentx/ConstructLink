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

if (!hasPermission('incidents/investigate') || $incident['status'] !== 'Pending Verification') {
    echo '<div class="alert alert-danger">You do not have permission to investigate this incident.</div>';
    return;
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-search me-2"></i>
        Verify Incident #<?= $incident['id'] ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group">
            <a href="?route=incidents/view&id=<?= $incident['id'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Details
            </a>
            <a href="?route=incidents" class="btn btn-outline-secondary">
                <i class="bi bi-list me-1"></i>All Incidents
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

<!-- MVA Workflow Sidebar -->
<div class="alert alert-info mb-4">
    <strong>MVA Workflow:</strong> <br>
    <span class="badge bg-primary">Maker</span> (Site Inventory Clerk) →
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-success">Authorizer</span> (Asset Director) →
    <span class="badge bg-secondary">Resolved</span> →
    <span class="badge bg-dark">Closed</span>
</div>

<!-- Investigation Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Verification Report
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=incidents/investigate&id=<?= $incident['id'] ?>">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Verification Notes -->
                    <div class="mb-3">
                        <label for="verification_notes" class="form-label">Verification Findings <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors) && in_array('Verification notes are required', $errors) ? 'is-invalid' : '' ?>" 
                                  id="verification_notes" 
                                  name="verification_notes" 
                                  rows="8" 
                                  required 
                                  placeholder="Document your verification findings, evidence reviewed, interviews conducted, and conclusions..."><?= htmlspecialchars($formData['verification_notes'] ?? '') ?></textarea>
                        <div class="invalid-feedback">
                            Please provide detailed verification findings.
                        </div>
                        <div class="form-text">
                            Include all relevant details about your verification process and findings.
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=incidents/view&id=<?= $incident['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Verify Incident
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

                    <dt class="col-sm-5">Date Reported:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($incident['date_reported'])) ?></dd>

                    <dt class="col-sm-5">Reported By:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($incident['reported_by_name']) ?></dd>

                    <dt class="col-sm-5">Location:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($incident['location'] ?: 'Not specified') ?></dd>
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

                <?php if (!empty($incident['witnesses'])): ?>
                    <h6 class="text-primary">Witnesses:</h6>
                    <p class="small mb-0"><?= nl2br(htmlspecialchars($incident['witnesses'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Investigation Guidelines -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Investigation Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6 class="text-primary">Investigation Should Include:</h6>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-check text-success me-1"></i> Physical inspection of area</li>
                    <li><i class="bi bi-check text-success me-1"></i> Interview with reporter</li>
                    <li><i class="bi bi-check text-success me-1"></i> Interview with witnesses</li>
                    <li><i class="bi bi-check text-success me-1"></i> Review of security footage (if available)</li>
                    <li><i class="bi bi-check text-success me-1"></i> Check of asset logs and records</li>
                    <li><i class="bi bi-check text-success me-1"></i> Documentation of evidence</li>
                </ul>

                <h6 class="text-warning mt-3">Document in Report:</h6>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-check text-success me-1"></i> Investigation methods used</li>
                    <li><i class="bi bi-check text-success me-1"></i> Evidence found or not found</li>
                    <li><i class="bi bi-check text-success me-1"></i> Witness statements</li>
                    <li><i class="bi bi-check text-success me-1"></i> Timeline of events</li>
                    <li><i class="bi bi-check text-success me-1"></i> Probable cause determination</li>
                    <li><i class="bi bi-check text-success me-1"></i> Recommendations for resolution</li>
                </ul>
            </div>
        </div>

        <!-- Investigation Checklist -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-list-check me-2"></i>Investigation Checklist
                </h6>
            </div>
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check1">
                    <label class="form-check-label small" for="check1">
                        Site inspection completed
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check2">
                    <label class="form-check-label small" for="check2">
                        Reporter interviewed
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check3">
                    <label class="form-check-label small" for="check3">
                        Witnesses interviewed
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check4">
                    <label class="form-check-label small" for="check4">
                        Records reviewed
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check5">
                    <label class="form-check-label small" for="check5">
                        Evidence documented
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check6">
                    <label class="form-check-label small" for="check6">
                        Cause determined
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const investigationNotes = document.getElementById('investigation_notes').value.trim();
    
    if (investigationNotes.length < 50) {
        e.preventDefault();
        alert('Please provide more detailed investigation findings (at least 50 characters).');
        document.getElementById('investigation_notes').focus();
        return false;
    }
    
    // Check if at least some checklist items are completed
    const checkboxes = document.querySelectorAll('.form-check-input:checked');
    if (checkboxes.length < 3) {
        if (!confirm('It appears you may not have completed all investigation steps. Are you sure you want to proceed?')) {
            e.preventDefault();
            return false;
        }
    }
});

// Auto-save draft functionality
let autoSaveTimer;
document.getElementById('investigation_notes').addEventListener('input', function() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(function() {
        // Save draft to localStorage
        localStorage.setItem('incident_investigation_<?= $incident['id'] ?>', document.getElementById('investigation_notes').value);
    }, 2000);
});

// Load draft on page load
document.addEventListener('DOMContentLoaded', function() {
    const draft = localStorage.getItem('incident_investigation_<?= $incident['id'] ?>');
    if (draft && !document.getElementById('investigation_notes').value) {
        if (confirm('A draft investigation report was found. Would you like to restore it?')) {
            document.getElementById('investigation_notes').value = draft;
        }
    }
});

// Clear draft on successful submission
document.querySelector('form').addEventListener('submit', function() {
    localStorage.removeItem('incident_investigation_<?= $incident['id'] ?>');
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Investigate Incident #' . $incident['id'] . ' - ConstructLink™';
$pageHeader = 'Investigate Incident #' . $incident['id'];
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Incidents', 'url' => '?route=incidents'],
    ['title' => 'Investigate', 'url' => '?route=incidents/investigate&id=' . $incident['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
