<?php
// Start output buffering to capture content
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-x-circle me-2"></i>
        Cancel Incident #<?= $incident['id'] ?>
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

<!-- Cancel Incident Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Cancel Incident
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Warning</h6>
                    <p class="mb-0">You are about to cancel this incident. This action cannot be undone. Please provide a reason for cancellation.</p>
                </div>

                <form method="POST" action="?route=incidents/cancel&id=<?= $incident['id'] ?>">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Incident Details (Read-only) -->
                    <div class="mb-4">
                        <h6>Incident Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Asset</label>
                                <div class="form-control-plaintext bg-light p-2 rounded">
                                    <strong><?= htmlspecialchars($incident['asset_ref']) ?></strong> - 
                                    <?= htmlspecialchars($incident['asset_name']) ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Current Status</label>
                                <div class="form-control-plaintext bg-light p-2 rounded">
                                    <span class="badge bg-secondary"><?= htmlspecialchars($incident['status']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <div class="form-control-plaintext bg-light p-2 rounded">
                                    <?= ucfirst(htmlspecialchars($incident['type'])) ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Severity</label>
                                <div class="form-control-plaintext bg-light p-2 rounded">
                                    <?= ucfirst(htmlspecialchars($incident['severity'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cancellation Reason -->
                    <div class="mb-3">
                        <label for="reason" class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="reason" 
                                  name="reason" 
                                  rows="4" 
                                  required 
                                  placeholder="Please provide a detailed reason for canceling this incident..."><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                        <div class="form-text">This reason will be recorded in the incident history.</div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=incidents/view&id=<?= $incident['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel Action
                        </a>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this incident? This action cannot be undone.')">
                            <i class="bi bi-x-circle me-1"></i>Cancel Incident
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
                            'Pending Verification' => 'bg-primary',
                            'Pending Authorization' => 'bg-warning text-dark',
                            'Authorized' => 'bg-info',
                            'Resolved' => 'bg-success',
                            'Closed' => 'bg-dark',
                            'Canceled' => 'bg-secondary'
                        ];
                        $statusClass = $statusClasses[$incident['status']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $statusClass ?>">
                            <?= htmlspecialchars($incident['status']) ?>
                        </span>
                    </dd>

                    <dt class="col-sm-5">Reported By:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($incident['reported_by_name']) ?></dd>

                    <dt class="col-sm-5">Date Reported:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($incident['date_reported'])) ?></dd>

                    <?php if ($incident['verified_by_name']): ?>
                        <dt class="col-sm-5">Verified By:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($incident['verified_by_name']) ?></dd>
                        <dt class="col-sm-5">Verification Date:</dt>
                        <dd class="col-sm-7"><?= date('M j, Y', strtotime($incident['verification_date'])) ?></dd>
                    <?php endif; ?>

                    <?php if ($incident['authorized_by_name']): ?>
                        <dt class="col-sm-5">Authorized By:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($incident['authorized_by_name']) ?></dd>
                        <dt class="col-sm-5">Authorization Date:</dt>
                        <dd class="col-sm-7"><?= date('M j, Y', strtotime($incident['authorization_date'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Workflow Guidelines -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Cancellation Guidelines
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled small">
                    <li><i class="bi bi-check-circle text-success me-2"></i>Can only cancel incidents in early stages</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Provide clear reason for cancellation</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Action will be logged in system</li>
                    <li><i class="bi bi-exclamation-triangle text-warning me-2"></i>Cannot cancel resolved/closed incidents</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Cancel Incident - ConstructLink™';
$pageHeader = 'Cancel Incident #' . $incident['id'];
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Incidents', 'url' => '?route=incidents'],
    ['title' => 'Cancel Incident', 'url' => '?route=incidents/cancel&id=' . $incident['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?> 