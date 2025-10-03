<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';


?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Status Messages -->
<?php include APP_ROOT . '/views/layouts/messages.php'; ?>

<!-- MVA Workflow Sidebar -->
<div class="alert alert-info mb-4">
    <strong>MVA Workflow:</strong> <br>
    <span class="badge bg-primary">Maker</span> (Site Inventory Clerk) →
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-success">Authorizer</span> (Asset Director) →
    <span class="badge bg-secondary">Resolved</span> →
    <span class="badge bg-dark">Closed</span>
</div>

<!-- Incident Details -->
<div class="row">
    <div class="col-lg-8">
        <!-- Main Incident Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Incident Details
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Incident ID:</dt>
                            <dd class="col-sm-8">#<?= $incident['id'] ?></dd>

                            <dt class="col-sm-4">Asset:</dt>
                            <dd class="col-sm-8">
                                <a href="?route=assets/view&id=<?= $incident['asset_id'] ?>" class="text-decoration-none">
                                    <strong><?= htmlspecialchars($incident['asset_ref']) ?></strong>
                                </a><br>
                                <small class="text-muted"><?= htmlspecialchars($incident['asset_name']) ?></small>
                            </dd>

                            <dt class="col-sm-4">Type:</dt>
                            <dd class="col-sm-8">
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

                            <dt class="col-sm-4">Severity:</dt>
                            <dd class="col-sm-8">
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

                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8">
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
                        </dl>
                    </div>
                    
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Date Reported:</dt>
                            <dd class="col-sm-8"><?= date('M j, Y', strtotime($incident['date_reported'])) ?></dd>

                            <dt class="col-sm-4">Reported By:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($incident['reported_by_name']) ?></dd>

                            <dt class="col-sm-4">Location:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($incident['location'] ?: 'Not specified') ?></dd>

                            <dt class="col-sm-4">Project:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($incident['project_name'] ?? 'N/A') ?></dd>

                            <dt class="col-sm-4">Category:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($incident['category_name'] ?? 'N/A') ?></dd>
                        </dl>
                    </div>
                </div>

                <!-- Description -->
                <div class="mt-3">
                    <h6>Description:</h6>
                    <div class="bg-light p-3 rounded">
                        <?= nl2br(htmlspecialchars($incident['description'])) ?>
                    </div>
                </div>

                <!-- Witnesses -->
                <?php if (!empty($incident['witnesses'])): ?>
                    <div class="mt-3">
                        <h6>Witnesses:</h6>
                        <div class="bg-light p-3 rounded">
                            <?= nl2br(htmlspecialchars($incident['witnesses'])) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Resolution Notes -->
                <?php if (!empty($incident['resolution_notes'])): ?>
                    <div class="mt-3">
                        <h6>Resolution Notes:</h6>
                        <div class="bg-light p-3 rounded">
                            <?= nl2br(htmlspecialchars($incident['resolution_notes'])) ?>
                        </div>
                        <?php if ($incident['resolved_by_name']): ?>
                            <small class="text-muted">
                                Resolved by <?= htmlspecialchars($incident['resolved_by_name']) ?>
                                <?php if ($incident['resolution_date']): ?>
                                    on <?= date('M j, Y g:i A', strtotime($incident['resolution_date'])) ?>
                                <?php endif; ?>
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Incidents -->
        <?php if (!empty($relatedIncidents) && count($relatedIncidents) > 1): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>Related Incidents for this Asset
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Reported By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatedIncidents as $related): ?>
                                    <?php if ($related['id'] != $incident['id']): ?>
                                        <tr>
                                            <td>
                                                <a href="?route=incidents/view&id=<?= $related['id'] ?>" class="text-decoration-none">
                                                    #<?= $related['id'] ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge <?= $typeClasses[$related['type']] ?? 'bg-secondary' ?>">
                                                    <?= ucfirst($related['type']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($related['date_reported'])) ?></td>
                                            <td>
                                                <span class="badge <?= $statusClasses[$related['status']] ?? 'bg-warning' ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $related['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($related['reported_by_name']) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Actions -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <!-- Investigation Actions -->
                    <?php if ($incident['status'] === 'under_investigation' && $auth->hasRole(['System Admin', 'Asset Director'])): ?>
                        <a href="?route=incidents/investigate&id=<?= $incident['id'] ?>" class="btn btn-info btn-sm">
                            <i class="bi bi-search"></i> Complete Investigation
                        </a>
                    <?php endif; ?>

                    <!-- Resolution Actions -->
                    <?php if (in_array($incident['status'], ['under_investigation', 'verified']) && $auth->hasRole(['System Admin', 'Asset Director'])): ?>
                        <a href="?route=incidents/resolve&id=<?= $incident['id'] ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-check-circle"></i> Resolve Incident
                        </a>
                    <?php endif; ?>

                    <!-- Close Action -->
                    <?php if ($incident['status'] === 'resolved' && $auth->hasRole(['System Admin', 'Asset Director'])): ?>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="closeIncident()">
                            <i class="bi bi-x-circle"></i> Close Incident
                        </button>
                    <?php endif; ?>

                    <!-- Edit Action -->
                    <?php if (!in_array($incident['status'], ['resolved', 'closed']) && $auth->hasRole(['System Admin', 'Asset Director'])): ?>
                        <a href="?route=incidents/edit&id=<?= $incident['id'] ?>" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-pencil"></i> Edit Incident
                        </a>
                    <?php endif; ?>

                    <!-- Delete Action -->
                    <?php if ($auth->hasRole(['System Admin'])): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteIncident()">
                            <i class="bi bi-trash"></i> Delete Incident
                        </button>
                    <?php endif; ?>
                </div>
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

        <!-- Timeline -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock me-2"></i>Timeline
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-danger"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Incident Reported</h6>
                            <p class="timeline-text small">
                                <?= date('M j, Y g:i A', strtotime($incident['created_at'])) ?><br>
                                <span class="text-muted">by <?= htmlspecialchars($incident['reported_by_name']) ?></span>
                            </p>
                        </div>
                    </div>

                    <?php if ($incident['status'] !== 'under_investigation'): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Investigation Started</h6>
                                <p class="timeline-text small">
                                    Status changed to investigation
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (in_array($incident['status'], ['resolved', 'closed'])): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Incident Resolved</h6>
                                <p class="timeline-text small">
                                    <?php if ($incident['resolution_date']): ?>
                                        <?= date('M j, Y g:i A', strtotime($incident['resolution_date'])) ?><br>
                                    <?php endif; ?>
                                    <?php if ($incident['resolved_by_name']): ?>
                                        <span class="text-muted">by <?= htmlspecialchars($incident['resolved_by_name']) ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($incident['status'] === 'closed'): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-secondary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Incident Closed</h6>
                                <p class="timeline-text small">
                                    Case closed and archived
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Close Incident Modal -->
<div class="modal fade" id="closeIncidentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Close Incident</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?route=incidents/close&id=<?= $incident['id'] ?>">
                <?= CSRFProtection::getTokenField() ?>
                <div class="modal-body">
                    <p>Are you sure you want to close this incident? This action will mark the incident as closed and archived.</p>
                    <div class="mb-3">
                        <label for="closure_notes" class="form-label">Closure Notes (Optional)</label>
                        <textarea class="form-control" id="closure_notes" name="closure_notes" rows="3" placeholder="Any final notes or comments..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Close Incident</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Incident Modal -->
<div class="modal fade" id="deleteIncidentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Incident</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?route=incidents/delete">
                <?= CSRFProtection::getTokenField() ?>
                <input type="hidden" name="incident_id" value="<?= $incident['id'] ?>">
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone.
                    </div>
                    <p>Are you sure you want to delete this incident? All related data will be permanently removed.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Incident</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function closeIncident() {
    const modal = new bootstrap.Modal(document.getElementById('closeIncidentModal'));
    modal.show();
}

function deleteIncident() {
    const modal = new bootstrap.Modal(document.getElementById('deleteIncidentModal'));
    modal.show();
}
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-content {
    padding-left: 10px;
}

.timeline-title {
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.timeline-text {
    margin-bottom: 0;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Incident #' . $incident['id'] . ' - ConstructLink™';
$pageHeader = 'Incident #' . $incident['id'];
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Incidents', 'url' => '?route=incidents'],
    ['title' => 'View Details', 'url' => '?route=incidents/view&id=' . $incident['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
