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
        <!-- Maintenance Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Maintenance Summary
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Request ID:</dt>
                            <dd class="col-sm-7">#<?= $maintenance['id'] ?></dd>
                            
                            <dt class="col-sm-5">Asset:</dt>
                            <dd class="col-sm-7">
                                <strong><?= htmlspecialchars($maintenance['asset_name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($maintenance['asset_ref']) ?></small>
                            </dd>
                            
                            <dt class="col-sm-5">Project:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($maintenance['project_name']) ?></dd>
                            
                            <dt class="col-sm-5">Type:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-<?= $maintenance['type'] === 'emergency' ? 'danger' : ($maintenance['type'] === 'corrective' ? 'warning' : 'info') ?>">
                                    <?= ucfirst($maintenance['type']) ?>
                                </span>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Requested By:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($maintenance['created_by_name'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Verified By:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($maintenance['verified_by_name'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Verification Date:</dt>
                            <dd class="col-sm-7"><?= $maintenance['verification_date'] ? date('M j, Y g:i A', strtotime($maintenance['verification_date'])) : 'N/A' ?></dd>
                            
                            <dt class="col-sm-5">Current Status:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-info">Pending Approval</span>
                            </dd>
                            
                            <dt class="col-sm-5">Scheduled Date:</dt>
                            <dd class="col-sm-7"><?= date('M j, Y', strtotime($maintenance['scheduled_date'])) ?></dd>
                            
                            <dt class="col-sm-5">Priority:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-<?= $maintenance['priority'] === 'urgent' ? 'danger' : ($maintenance['priority'] === 'high' ? 'warning' : ($maintenance['priority'] === 'medium' ? 'info' : 'secondary')) ?>">
                                    <?= ucfirst($maintenance['priority']) ?>
                                </span>
                            </dd>
                        </dl>
                    </div>
                </div>
                
                <hr>
                
                <!-- Description -->
                <div class="mb-3">
                    <h6>Description:</h6>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($maintenance['description'])) ?></p>
                </div>
                
                <!-- Estimated Cost -->
                <?php if ($maintenance['estimated_cost']): ?>
                    <div class="mb-3">
                        <h6>Estimated Cost:</h6>
                        <p class="text-muted">₱<?= number_format($maintenance['estimated_cost'], 2) ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Assigned To -->
                <?php if ($maintenance['assigned_to']): ?>
                    <div class="mb-3">
                        <h6>Assigned To:</h6>
                        <p class="text-muted"><?= htmlspecialchars($maintenance['assigned_to']) ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Verification Notes -->
                <?php if ($maintenance['notes']): ?>
                    <div class="mb-3">
                        <h6>Verification Notes:</h6>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-0"><?= nl2br(htmlspecialchars($maintenance['notes'])) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Authorization Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-shield-check me-2"></i>Authorization Details
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=maintenance/authorize&id=<?= $maintenance['id'] ?>">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle me-2"></i>Authorization Checklist
                        </h6>
                        <p>Please review the following before authorizing this maintenance request:</p>
                        <ul class="mb-0">
                            <li>Maintenance has been properly verified by Project Manager</li>
                            <li>Budget allocation is available for the estimated cost</li>
                            <li>Maintenance aligns with strategic asset management goals</li>
                            <li>Resource allocation does not conflict with other priorities</li>
                            <li>Maintenance timing is appropriate for operational needs</li>
                            <li>Safety and compliance requirements are met</li>
                        </ul>
                    </div>
                    
                    <!-- High Cost Warning -->
                    <?php if ($maintenance['estimated_cost'] && $maintenance['estimated_cost'] > 50000): ?>
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="bi bi-exclamation-triangle me-2"></i>High Cost Maintenance Authorization
                            </h6>
                            <p>This maintenance has an estimated cost of ₱<?= number_format($maintenance['estimated_cost'], 2) ?>, which exceeds ₱50,000. Please provide detailed authorization notes explaining the business justification and budget impact.</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Emergency Priority Alert -->
                    <?php if ($maintenance['type'] === 'emergency'): ?>
                        <div class="alert alert-danger">
                            <h6 class="alert-heading">
                                <i class="bi bi-exclamation-triangle me-2"></i>Emergency Maintenance
                            </h6>
                            <p>This is an <strong>emergency maintenance</strong> request. Please ensure immediate authorization if the maintenance is critical for safety or operations.</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Authorization Notes -->
                    <div class="mb-3">
                        <label for="authorization_notes" class="form-label">
                            Authorization Notes 
                            <?php if ($maintenance['estimated_cost'] && $maintenance['estimated_cost'] > 50000): ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>
                        <textarea class="form-control" 
                                  id="authorization_notes" 
                                  name="authorization_notes" 
                                  rows="4" 
                                  placeholder="Add authorization notes, budget approval, or strategic considerations..."
                                  <?= ($maintenance['estimated_cost'] && $maintenance['estimated_cost'] > 50000) ? 'required' : '' ?>></textarea>
                        <div class="form-text">
                            <?php if ($maintenance['estimated_cost'] && $maintenance['estimated_cost'] > 50000): ?>
                                Required for high-cost maintenance. Please provide detailed business justification.
                            <?php else: ?>
                                Optional: Add any notes about your authorization decision or budget considerations.
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Budget Confirmation -->
                    <?php if ($maintenance['estimated_cost']): ?>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirmBudget" required>
                                <label class="form-check-label" for="confirmBudget">
                                    I confirm that budget allocation of ₱<?= number_format($maintenance['estimated_cost'], 2) ?> is available for this maintenance
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Authorization Confirmation -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmAuthorization" required>
                            <label class="form-check-label" for="confirmAuthorization">
                                I authorize this maintenance request and take responsibility for the business impact and resource allocation
                            </label>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=maintenance/view&id=<?= $maintenance['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-shield-check me-1"></i>Authorize Maintenance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Asset Details -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box me-2"></i>Asset Details
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Reference:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['asset_ref']) ?></dd>
                    
                    <dt class="col-sm-5">Name:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['asset_name']) ?></dd>
                    
                    <dt class="col-sm-5">Category:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['category_name']) ?></dd>
                    
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['project_name']) ?></dd>
                    
                    <dt class="col-sm-5">Current Status:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-<?= $maintenance['asset_status'] === 'available' ? 'success' : ($maintenance['asset_status'] === 'in_use' ? 'primary' : 'warning') ?>">
                            <?= ucfirst(str_replace('_', ' ', $maintenance['asset_status'])) ?>
                        </span>
                    </dd>
                </dl>
                
                <hr>
                
                <a href="?route=assets/view&id=<?= $maintenance['asset_id'] ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-eye me-1"></i>View Asset Details
                </a>
            </div>
        </div>
        
        <!-- MVA Workflow -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-diagram-3 me-2"></i>MVA Workflow
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item completed">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">1. Maker</h6>
                            <p class="timeline-text">Request created by <?= htmlspecialchars($maintenance['created_by_name'] ?? 'N/A') ?></p>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($maintenance['created_at'])) ?></small>
                        </div>
                    </div>
                    
                    <div class="timeline-item completed">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">2. Verifier</h6>
                            <p class="timeline-text">Verified by <?= htmlspecialchars($maintenance['verified_by_name'] ?? 'N/A') ?></p>
                            <small class="text-muted"><?= $maintenance['verification_date'] ? date('M j, Y g:i A', strtotime($maintenance['verification_date'])) : 'N/A' ?></small>
                        </div>
                    </div>
                    
                    <div class="timeline-item active">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">3. Authorizer</h6>
                            <p class="timeline-text">Awaiting authorization by <?= htmlspecialchars($user['full_name']) ?></p>
                            <small class="text-muted">Current step</small>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker bg-light"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">4. Execution</h6>
                            <p class="timeline-text">Maintenance work execution</p>
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
    background-color: #ffc107 !important;
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

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>