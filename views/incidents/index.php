<?php
// Start output buffering to capture content
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$auth = Auth::getInstance();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <!-- Primary Actions (Left) -->
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Primary actions">
        <?php if (hasPermission('incidents/create')): ?>
            <a href="?route=incidents/create" class="btn btn-danger btn-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">Report Incident</span>
                <span class="d-sm-none">Report</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <!-- Pending Verification -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-clock text-warning fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Pending Verification</h6>
                        <h3 class="mb-0"><?= $incidentStats['pending_verification'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-person me-1"></i>Project Manager review needed
                </p>
                <?php if (in_array($userRole, ['Project Manager', 'System Admin']) && ($incidentStats['pending_verification'] ?? 0) > 0): ?>
                    <a href="?route=incidents&status=Pending Verification" class="btn btn-sm btn-outline-warning w-100 mt-2">
                        <i class="bi bi-search me-1"></i>Review Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pending Authorization -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-hourglass-split text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Pending Authorization</h6>
                        <h3 class="mb-0"><?= $incidentStats['pending_authorization'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-shield-check me-1"></i>Asset Director approval needed
                </p>
                <?php if (in_array($userRole, ['Asset Director', 'System Admin']) && ($incidentStats['pending_authorization'] ?? 0) > 0): ?>
                    <a href="?route=incidents&status=Pending Authorization" class="btn btn-sm btn-outline-info w-100 mt-2">
                        <i class="bi bi-shield-check me-1"></i>Authorize Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Authorized -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Authorized</h6>
                        <h3 class="mb-0"><?= $incidentStats['authorized'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-tools me-1"></i>Ready for resolution
                </p>
            </div>
        </div>
    </div>

    <!-- Resolved -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-square text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Resolved</h6>
                        <h3 class="mb-0"><?= $incidentStats['resolved'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-clipboard-check me-1"></i>Ready for closure
                </p>
            </div>
        </div>
    </div>

    <!-- Critical Incidents -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--danger-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Critical</h6>
                        <h3 class="mb-0"><?= $incidentStats['critical'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-lightning me-1"></i><?= $incidentStats['critical_overdue'] ?? 0 ?> overdue
                </p>
                <a href="?route=incidents&severity=critical" class="btn btn-sm btn-outline-danger w-100 mt-2">
                    <i class="bi bi-lightning me-1"></i>View Critical
                </a>
            </div>
        </div>
    </div>

    <!-- Closed Incidents -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-archive text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Closed</h6>
                        <h3 class="mb-0"><?= $incidentStats['closed'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-calendar me-1"></i><?= $incidentStats['closed_this_month'] ?? 0 ?> this month
                </p>
            </div>
        </div>
    </div>

    <!-- Total Incidents -->
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-list-ul text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Total Incidents</h6>
                        <h3 class="mb-0"><?= $incidentStats['total_incidents'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-graph-up me-1"></i>All time
                </p>
            </div>
        </div>
    </div>
</div>

<!-- MVA Workflow Info Banner -->
<div class="alert alert-info mb-4">
    <strong><i class="bi bi-info-circle me-2"></i>MVA Workflow:</strong>
    <span class="badge bg-primary">Maker</span> (Site Inventory Clerk) →
    <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
    <span class="badge bg-info">Authorizer</span> (Asset Director) →
    <span class="badge bg-primary">Resolved</span> →
    <span class="badge bg-secondary">Closed</span>
</div>

<!-- Old role-based action cards removed - using standard pattern above -->

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=incidents" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Pending Verification" <?= ($_GET['status'] ?? '') === 'Pending Verification' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="Pending Authorization" <?= ($_GET['status'] ?? '') === 'Pending Authorization' ? 'selected' : '' ?>>Pending Authorization</option>
                    <option value="Authorized" <?= ($_GET['status'] ?? '') === 'Authorized' ? 'selected' : '' ?>>Authorized</option>
                    <option value="Resolved" <?= ($_GET['status'] ?? '') === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                    <option value="Closed" <?= ($_GET['status'] ?? '') === 'Closed' ? 'selected' : '' ?>>Closed</option>
                    <option value="Canceled" <?= ($_GET['status'] ?? '') === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <option value="lost" <?= ($_GET['type'] ?? '') === 'lost' ? 'selected' : '' ?>>Lost</option>
                    <option value="damaged" <?= ($_GET['type'] ?? '') === 'damaged' ? 'selected' : '' ?>>Damaged</option>
                    <option value="stolen" <?= ($_GET['type'] ?? '') === 'stolen' ? 'selected' : '' ?>>Stolen</option>
                    <option value="other" <?= ($_GET['type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="severity" class="form-label">Severity</label>
                <select class="form-select" id="severity" name="severity">
                    <option value="">All Severities</option>
                    <option value="low" <?= ($_GET['severity'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                    <option value="medium" <?= ($_GET['severity'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="high" <?= ($_GET['severity'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                    <option value="critical" <?= ($_GET['severity'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
                </select>
            </div>
            <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Project Manager'])): ?>
            <div class="col-md-3">
                <label for="project_id" class="form-label">Project</label>
                <select class="form-select" id="project_id" name="project_id">
                    <option value="">All Projects</option>
                    <?php if (isset($projects) && is_array($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" 
                                    <?= ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name'] ?? 'Unknown') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if (in_array($userRole, ['System Admin', 'Asset Director', 'Project Manager'])): ?>
            <div class="col-md-3">
                <label for="reported_by" class="form-label">Reported By</label>
                <select class="form-select" id="reported_by" name="reported_by">
                    <option value="">All Reporters</option>
                    <?php if (isset($reporters) && is_array($reporters)): ?>
                        <?php foreach ($reporters as $reporter): ?>
                            <option value="<?= $reporter['id'] ?>" 
                                    <?= ($_GET['reported_by'] ?? '') == $reporter['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($reporter['full_name'] ?? 'Unknown') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by asset name, reporter, location, or description..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=incidents" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
                <?php if (in_array($userRole, ['System Admin', 'Asset Director', 'Finance Director'])): ?>
                <button class="btn btn-outline-success" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Incidents Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Incident Reports</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                <i class="bi bi-file-earmark-excel me-1"></i>Export
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($incidents)): ?>
            <div class="text-center py-5">
                <i class="bi bi-shield-check display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No incidents reported</h5>
                <p class="text-muted">Great! No incidents have been reported. Try adjusting your filters if you're looking for specific records.</p>
                <?php if (hasPermission('incidents/create')): ?>
                    <a href="?route=incidents/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Report New Incident
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="incidentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asset</th>
                            <th>Type</th>
                            <th>Severity</th>
                            <?php if (in_array($userRole, ['System Admin', 'Asset Director', 'Project Manager'])): ?>
                            <th>Project</th>
                            <?php endif; ?>
                            <th>Reported By</th>
                            <th>Date Reported</th>
                            <th>Status</th>
                            <?php if (in_array($userRole, ['System Admin', 'Asset Director', 'Finance Director'])): ?>
                            <th>MVA Progress</th>
                            <?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incidents as $incident): ?>
                            <tr class="<?= ($incident['severity'] ?? '') === 'critical' ? 'table-danger' : '' ?>">
                                <td>
                                    <a href="?route=incidents/view&id=<?= $incident['id'] ?>" class="text-decoration-none">
                                        <strong>#<?= $incident['id'] ?></strong>
                                    </a>
                                    <?php if (($incident['severity'] ?? '') === 'critical'): ?>
                                        <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Critical Incident"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="bi bi-box text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($incident['asset_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($incident['asset_ref']) ?></small>
                                            <?php if (!empty($incident['location'])): ?>
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($incident['location']) ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $typeClasses = [
                                        'lost' => 'bg-warning text-dark',
                                        'damaged' => 'bg-danger',
                                        'stolen' => 'bg-dark',
                                        'other' => 'bg-secondary'
                                    ];
                                    $typeIcons = [
                                        'lost' => 'bi-question-circle',
                                        'damaged' => 'bi-exclamation-triangle',
                                        'stolen' => 'bi-shield-x',
                                        'other' => 'bi-three-dots'
                                    ];
                                    $typeClass = $typeClasses[$incident['type']] ?? 'bg-secondary';
                                    $typeIcon = $typeIcons[$incident['type']] ?? 'bi-three-dots';
                                    ?>
                                    <span class="badge <?= $typeClass ?>">
                                        <i class="<?= $typeIcon ?> me-1"></i><?= ucfirst($incident['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $severityClasses = [
                                        'low' => 'bg-success',
                                        'medium' => 'bg-warning text-dark',
                                        'high' => 'bg-danger',
                                        'critical' => 'bg-dark'
                                    ];
                                    $severityClass = $severityClasses[$incident['severity'] ?? 'medium'] ?? 'bg-warning';
                                    ?>
                                    <span class="badge <?= $severityClass ?>">
                                        <?= ucfirst($incident['severity'] ?? 'Medium') ?>
                                    </span>
                                    <?php if (($incident['severity'] ?? '') === 'critical'): ?>
                                        <div class="mt-1">
                                            <small class="text-danger">
                                                <i class="bi bi-lightning me-1"></i>Immediate action required
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <?php if (in_array($userRole, ['System Admin', 'Asset Director', 'Project Manager'])): ?>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($incident['project_name'] ?? 'N/A') ?>
                                    </span>
                                    <?php if (!empty($incident['project_code'])): ?>
                                        <small class="text-muted d-block"><?= htmlspecialchars($incident['project_code']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($incident['reported_by_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($incident['reporter_role'] ?? '') ?></small>
                                        <?php if (!empty($incident['witnesses'])): ?>
                                            <div class="mt-1">
                                                <small class="text-info">
                                                    <i class="bi bi-people me-1"></i>Witnesses present
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= date('M j, Y', strtotime($incident['date_reported'])) ?></div>
                                        <small class="text-muted"><?= date('g:i A', strtotime($incident['date_reported'])) ?></small>
                                        <?php
                                        $daysSinceReported = floor((time() - strtotime($incident['date_reported'])) / 86400);
                                        if ($daysSinceReported > 7 && in_array($incident['status'], ['Pending Verification', 'Pending Authorization'])):
                                        ?>
                                            <div class="mt-1">
                                                <small class="text-warning">
                                                    <i class="bi bi-clock me-1"></i><?= $daysSinceReported ?> days old
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
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
                                </td>
                                <?php if (in_array($userRole, ['System Admin', 'Asset Director', 'Finance Director'])): ?>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php
                                        $verificationComplete = !empty($incident['verified_by']);
                                        $authorizationComplete = !empty($incident['authorized_by']);
                                        $resolutionComplete = !empty($incident['resolved_by']);
                                        ?>
                                        <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                            <?php
                                            $progressPercentage = 0;
                                            if ($verificationComplete) $progressPercentage += 33;
                                            if ($authorizationComplete) $progressPercentage += 33;
                                            if ($resolutionComplete) $progressPercentage += 34;
                                            ?>
                                            <div class="progress-bar bg-<?= $progressPercentage >= 100 ? 'success' : ($progressPercentage >= 66 ? 'info' : 'warning') ?>" 
                                                 style="width: <?= $progressPercentage ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $progressPercentage ?>%</small>
                                    </div>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            <?php if ($verificationComplete): ?>
                                                <i class="bi bi-check-circle text-success me-1"></i>V
                                            <?php else: ?>
                                                <i class="bi bi-circle text-muted me-1"></i>V
                                            <?php endif; ?>
                                            
                                            <?php if ($authorizationComplete): ?>
                                                <i class="bi bi-check-circle text-success me-1"></i>A
                                            <?php else: ?>
                                                <i class="bi bi-circle text-muted me-1"></i>A
                                            <?php endif; ?>
                                            
                                            <?php if ($resolutionComplete): ?>
                                                <i class="bi bi-check-circle text-success me-1"></i>R
                                            <?php else: ?>
                                                <i class="bi bi-circle text-muted me-1"></i>R
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=incidents/view&id=<?= $incident['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if (hasPermission('incidents/investigate') && $incident['status'] === 'Pending Verification'): ?>
                                            <a href="?route=incidents/investigate&id=<?= $incident['id'] ?>" 
                                               class="btn btn-outline-warning" title="Verify Incident">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission('incidents/resolve') && $incident['status'] === 'Pending Authorization'): ?>
                                            <a href="?route=incidents/resolve&id=<?= $incident['id'] ?>" 
                                               class="btn btn-outline-success" title="Authorize Incident">
                                                <i class="bi bi-shield-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission('incidents/resolve') && in_array($incident['status'], ['Authorized', 'Pending Authorization'])): ?>
                                            <a href="?route=incidents/resolve&id=<?= $incident['id'] ?>" 
                                               class="btn btn-outline-info" title="Resolve Incident">
                                                <i class="bi bi-tools"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission('incidents/close') && $incident['status'] === 'Resolved'): ?>
                                            <a href="?route=incidents/close&id=<?= $incident['id'] ?>" 
                                               class="btn btn-outline-dark" title="Close Incident">
                                                <i class="bi bi-archive"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission('incidents/cancel') && in_array($incident['status'], ['Pending Verification', 'Pending Authorization', 'Authorized'])): ?>
                                            <a href="?route=incidents/cancel&id=<?= $incident['id'] ?>" 
                                               class="btn btn-outline-danger" title="Cancel Incident">
                                                <i class="bi bi-x-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Incidents pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Critical Incidents Alert -->
<?php if (!empty($criticalIncidents)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Critical Incidents Alert
                </h6>
            </div>
            <div class="card-body">
                <p class="text-danger mb-3">
                    <strong><?= count($criticalIncidents) ?> critical incident(s)</strong> require immediate attention. These incidents may impact operations significantly.
                </p>
                <div class="row">
                    <?php foreach (array_slice($criticalIncidents, 0, 6) as $criticalIncident): ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                <div>
                                    <strong><?= htmlspecialchars($criticalIncident['asset_name']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?= ucfirst($criticalIncident['type']) ?> - Reported by: <?= htmlspecialchars($criticalIncident['reported_by_name']) ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger">Critical</span>
                                    <br>
                                    <small class="text-muted">
                                        <?= floor((time() - strtotime($criticalIncident['date_reported'])) / 86400) ?> days ago
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($criticalIncidents) > 6): ?>
                    <p class="text-muted mt-2">And <?= count($criticalIncidents) - 6 ?> more critical incidents...</p>
                <?php endif; ?>
                <div class="mt-3">
                    <a href="?route=incidents&severity=critical" class="btn btn-danger">
                        <i class="bi bi-lightning me-1"></i>View All Critical Incidents
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Overdue Incidents Alert -->
<?php if (!empty($overdueIncidents)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Overdue Incidents Alert
                </h6>
            </div>
            <div class="card-body">
                <p class="text-warning mb-3">
                    <strong><?= count($overdueIncidents) ?> incident(s)</strong> have been pending for more than 7 days and may require escalation.
                </p>
                <div class="row">
                    <?php foreach (array_slice($overdueIncidents, 0, 4) as $overdueIncident): ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                <div>
                                    <strong><?= htmlspecialchars($overdueIncident['asset_name']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        Status: <?= htmlspecialchars($overdueIncident['status']) ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-warning text-dark">
                                        <?= floor((time() - strtotime($overdueIncident['date_reported'])) / 86400) ?> days old
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($overdueIncidents) > 4): ?>
                    <p class="text-muted mt-2">And <?= count($overdueIncidents) - 4 ?> more overdue incidents...</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Export to Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=incidents&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Auto-refresh for critical incidents
if (document.querySelector('.badge.bg-dark') || document.querySelector('.table-danger')) {
    setTimeout(() => {
        location.reload();
    }, 120000); // Refresh every 2 minutes if there are critical incidents
}

// Enhanced search functionality with debounce
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[action="?route=incidents"]');
    const filterInputs = filterForm.querySelectorAll('select');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Search with debounce
    let searchTimeout;
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    }
});

// Bulk action functionality for selected incidents
function toggleIncidentSelection(incidentId) {
    const checkbox = document.querySelector(`input[data-incident-id="${incidentId}"]`);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        updateBulkActionButtons();
    }
}

function updateBulkActionButtons() {
    const checkedBoxes = document.querySelectorAll('input[data-incident-id]:checked');
    const bulkActionContainer = document.getElementById('bulkActionContainer');
    
    if (bulkActionContainer) {
        if (checkedBoxes.length > 0) {
            bulkActionContainer.style.display = 'block';
            bulkActionContainer.querySelector('.selected-count').textContent = checkedBoxes.length;
        } else {
            bulkActionContainer.style.display = 'none';
        }
    }
}

// Priority escalation for overdue incidents
function escalateIncident(incidentId) {
    if (confirm('Escalate this incident to higher priority? This will notify relevant stakeholders.')) {
        fetch('?route=incidents/escalate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ incident_id: incidentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to escalate incident: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while escalating the incident');
        });
    }
}

// Role-based notification sounds for critical incidents
function playNotificationSound() {
    const userRole = document.body.getAttribute('data-user-role');
    const hasCritical = document.querySelector('.table-danger');
    
    if (hasCritical && ['Asset Director', 'Project Manager', 'System Admin'].includes(userRole)) {
        // Play a subtle notification sound (if browser supports it)
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmUgCiyR2e/FdiMFl6ztqZg3djOqYGNjoJ+Ul1mprpVdQDNfgcbbpX1lIAcvks/w2IMyCCaC0OjaqmYddKrdqJ8zdjaq'); 
        audio.volume = 0.1;
        audio.play().catch(() => {}); // Fail silently if audio not supported
    }
}

// Initialize notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    playNotificationSound();
    
    // Set up periodic checks for new critical incidents
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            fetch('?route=api/incidents/critical-count')
                .then(response => response.json())
                .then(data => {
                    const currentCount = document.querySelector('.badge.bg-danger')?.textContent || '0';
                    if (parseInt(data.count) > parseInt(currentCount)) {
                        playNotificationSound();
                        location.reload();
                    }
                })
                .catch(() => {});
        }
    }, 300000); // Check every 5 minutes
});

// Keyboard shortcuts for power users
document.addEventListener('keydown', function(e) {
    // Ctrl+N for new incident (if user has permission)
    if (e.ctrlKey && e.key === 'n') {
        const newIncidentBtn = document.querySelector('a[href*="incidents/create"]');
        if (newIncidentBtn) {
            e.preventDefault();
            window.location.href = newIncidentBtn.href;
        }
    }
    
    // Ctrl+R for refresh
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        location.reload();
    }
    
    // Esc to clear search/filters
    if (e.key === 'Escape') {
        const clearBtn = document.querySelector('a[href="?route=incidents"]');
        if (clearBtn && document.querySelector('input[name="search"]').value) {
            window.location.href = clearBtn.href;
        }
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Asset Incidents - ConstructLink™';
$pageHeader = 'Asset Incidents';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Incidents', 'url' => '?route=incidents']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
