<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Please correct the following errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Asset Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Asset Summary
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Asset ID:</dt>
                            <dd class="col-sm-7">#<?= $asset['id'] ?></dd>
                            
                            <dt class="col-sm-5">Reference:</dt>
                            <dd class="col-sm-7">
                                <strong><?= htmlspecialchars($asset['ref']) ?></strong>
                            </dd>
                            
                            <dt class="col-sm-5">Asset Name:</dt>
                            <dd class="col-sm-7">
                                <strong><?= htmlspecialchars($asset['name']) ?></strong>
                            </dd>
                            
                            <dt class="col-sm-5">Category:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['category_name']) ?></dd>
                            
                            <dt class="col-sm-5">Project:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['project_name']) ?></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Vendor:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['vendor_name'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Manufacturer:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['maker_name'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Verified By:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['verified_by_name'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Current Status:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-info">Pending Authorization</span>
                            </dd>
                            
                            <dt class="col-sm-5">Acquisition Cost:</dt>
                            <dd class="col-sm-7">
                                <strong><?= $asset['acquisition_cost'] ? formatCurrency($asset['acquisition_cost']) : 'N/A' ?></strong>
                            </dd>
                        </dl>
                    </div>
                </div>
                
                <hr>
                
                <!-- Asset Description -->
                <?php if ($asset['description']): ?>
                    <div class="mb-3">
                        <h6>Description:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($asset['description'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Verification Status -->
                <?php if ($asset['verified_by_name']): ?>
                    <div class="alert alert-success">
                        <h6 class="alert-heading">
                            <i class="bi bi-check-circle me-2"></i>Verification Complete
                        </h6>
                        <p class="mb-0">
                            This asset has been verified by <?= htmlspecialchars($asset['verified_by_name']) ?> on 
                            <?= date('M j, Y g:i A', strtotime($asset['verification_date'])) ?>.
                        </p>
                        <?php if ($asset['verification_notes']): ?>
                            <hr class="my-2">
                            <p class="mb-0">
                                <strong>Verification Notes:</strong><br>
                                <?= nl2br(htmlspecialchars($asset['verification_notes'])) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Asset Specifications -->
                <?php if ($asset['serial_number'] || $asset['model']): ?>
                    <div class="mb-3">
                        <h6>Specifications:</h6>
                        <div class="row">
                            <?php if ($asset['serial_number']): ?>
                                <div class="col-md-6">
                                    <strong>Serial Number:</strong> <?= htmlspecialchars($asset['serial_number']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($asset['model']): ?>
                                <div class="col-md-6">
                                    <strong>Model:</strong> <?= htmlspecialchars($asset['model']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Authorization Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-check-circle me-2"></i>Asset Authorization
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="bi bi-info-circle me-2"></i>Authorization Checklist
                    </h6>
                    <p>Please review the following before making your authorization decision:</p>
                    <ul class="mb-0">
                        <li>Asset has been verified by authorized personnel</li>
                        <li>Asset is required for legitimate business activities</li>
                        <li>Acquisition cost is within budget and reasonable</li>
                        <li>Asset category and project assignment are appropriate</li>
                        <li>Financial impact is acceptable</li>
                        <li>Asset complies with procurement policies</li>
                    </ul>
                </div>
                
                <form method="POST" action="?route=assets/authorize&id=<?= $asset['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    
                    <!-- Financial Impact Summary -->
                    <div class="card border-warning mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">
                                <i class="bi bi-currency-dollar me-2"></i>Financial Impact Summary
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-warning mb-1">
                                            <?= $asset['acquisition_cost'] ? formatCurrency($asset['acquisition_cost']) : 'N/A' ?>
                                        </h4>
                                        <small class="text-muted">Asset Cost</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-info mb-1">
                                            <?= htmlspecialchars($asset['project_name']) ?>
                                        </h4>
                                        <small class="text-muted">Project Assignment</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 class="text-success mb-1">
                                            <?= $asset['is_client_supplied'] ? 'Client' : 'Company' ?>
                                        </h4>
                                        <small class="text-muted">Funded By</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Authorization Notes -->
                    <div class="mb-3">
                        <label for="authorization_notes" class="form-label">Authorization Notes</label>
                        <textarea class="form-control" 
                                  id="authorization_notes" 
                                  name="authorization_notes" 
                                  rows="4" 
                                  placeholder="Add any authorization notes, conditions, or instructions..."></textarea>
                        <div class="form-text">
                            Optional: Add any conditions, instructions, or comments regarding this authorization.
                        </div>
                    </div>
                    
                    <!-- Authorization Actions -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="bi bi-check-circle me-2"></i>Authorize Asset
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        Approve this asset registration and make it available for use.
                                    </p>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="confirmAuthorization" required>
                                        <label class="form-check-label" for="confirmAuthorization">
                                            I confirm that I have reviewed this asset registration and authorize its deployment
                                        </label>
                                    </div>
                                    <button type="submit" name="action" value="authorize" class="btn btn-success">
                                        <i class="bi bi-check-circle me-1"></i>Authorize Asset
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0">
                                        <i class="bi bi-x-circle me-2"></i>Reject Authorization
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        Reject this asset authorization with a reason.
                                    </p>
                                    <div class="mb-3">
                                        <label for="rejection_reason" class="form-label">Rejection Reason</label>
                                        <textarea class="form-control" 
                                                  id="rejection_reason" 
                                                  name="rejection_reason" 
                                                  rows="3" 
                                                  placeholder="Explain why authorization is being rejected..."></textarea>
                                    </div>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger">
                                        <i class="bi bi-x-circle me-1"></i>Reject Authorization
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Asset Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box me-2"></i>Asset Details
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Type:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-<?= $asset['is_consumable'] ? 'info' : 'primary' ?>">
                            <?= $asset['is_consumable'] ? 'Consumable' : 'Non-Consumable' ?>
                        </span>
                    </dd>
                    
                    <?php if ($asset['is_consumable']): ?>
                        <dt class="col-sm-5">Quantity:</dt>
                        <dd class="col-sm-7"><?= number_format($asset['quantity']) ?> <?= htmlspecialchars($asset['unit'] ?? 'pcs') ?></dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-5">Client Supplied:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-<?= $asset['is_client_supplied'] ? 'warning' : 'light text-dark' ?>">
                            <?= $asset['is_client_supplied'] ? 'Yes' : 'No' ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-5">Acquired Date:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($asset['acquired_date'])) ?></dd>
                </dl>
                
                <hr>
                
                <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-eye me-1"></i>View Full Asset Details
                </a>
            </div>
        </div>
        
        <!-- Budget Impact -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-graph-up me-2"></i>Budget Impact
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-12 mb-3">
                        <h5 class="text-primary">
                            <?= $asset['acquisition_cost'] ? formatCurrency($asset['acquisition_cost']) : 'N/A' ?>
                        </h5>
                        <small class="text-muted">One-time Cost</small>
                    </div>
                </div>
                
                <div class="alert alert-<?= $asset['is_client_supplied'] ? 'success' : 'warning' ?> py-2">
                    <small>
                        <i class="bi bi-info-circle me-1"></i>
                        <?= $asset['is_client_supplied'] ? 'Client-funded asset (no budget impact)' : 'Company-funded asset (impacts budget)' ?>
                    </small>
                </div>
                
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span>Category:</span>
                        <strong><?= htmlspecialchars($asset['category_name']) ?></strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span>Project:</span>
                        <strong><?= htmlspecialchars($asset['project_name']) ?></strong>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- MVA Workflow Progress -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-diagram-3 me-2"></i>MVA Workflow Progress
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item completed">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">1. Asset Created</h6>
                            <p class="timeline-text">Asset registered by <?= htmlspecialchars($asset['made_by_name'] ?? 'System') ?></p>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($asset['created_at'])) ?></small>
                        </div>
                    </div>
                    
                    <div class="timeline-item completed">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">2. Verification</h6>
                            <p class="timeline-text">Verified by <?= htmlspecialchars($asset['verified_by_name']) ?></p>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($asset['verification_date'])) ?></small>
                        </div>
                    </div>
                    
                    <div class="timeline-item active">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">3. Authorization</h6>
                            <p class="timeline-text">Awaiting authorization by <?= htmlspecialchars($user['full_name']) ?></p>
                            <small class="text-muted">Current step</small>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker bg-light"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">4. Deployment</h6>
                            <p class="timeline-text">Asset ready for use</p>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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

.timeline-item.completed .timeline-marker {
    background-color: #28a745 !important;
}

.timeline-item.active .timeline-marker {
    background-color: #17a2b8 !important;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #dee2e6;
}

.timeline-title {
    margin-bottom: 5px;
    font-size: 14px;
    font-weight: 600;
}

.timeline-text {
    margin-bottom: 5px;
    font-size: 13px;
    color: #6c757d;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Authorize Asset - ConstructLink™';
$pageHeader = 'Authorize Asset Registration';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Assets', 'url' => '?route=assets'],
    ['title' => 'Asset #' . $asset['id'], 'url' => '?route=assets/view&id=' . $asset['id']],
    ['title' => 'Authorize', 'url' => '?route=assets/authorize&id=' . $asset['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>