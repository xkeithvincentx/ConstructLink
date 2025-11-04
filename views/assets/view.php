<?php
/**
 * Inventory Item Details View
 *
 * DATABASE MAPPING NOTE:
 * - This view displays "Inventory Item" to users
 * - Backend uses AssetController and `assets` database table
 * - See controllers/AssetController.php header for full mapping documentation
 */

// Start output buffering to capture content
ob_start();

// Load required helpers
require_once APP_ROOT . '/helpers/AssetHelper.php';

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<div class="d-flex justify-content-end align-items-center mb-4">
    <a href="?route=assets" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>
        <span class="d-none d-sm-inline">Back to Inventory</span>
        <span class="d-sm-none">Back</span>
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Item Information -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Item Information
                    <?php if (!empty($asset['asset_source']) && $asset['asset_source'] === 'legacy'): ?>
                        <span class="badge bg-warning ms-2">Legacy Item</span>
                    <?php elseif (!empty($asset['standardized_name']) || !empty($asset['standardized_brand'])): ?>
                        <span class="badge bg-success ms-2">Standardized</span>
                    <?php endif; ?>
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Reference:</dt>
                            <dd class="col-sm-7">
                                <strong><?= htmlspecialchars($asset['ref']) ?></strong>
                                <?php if (!empty($asset['qr_code'])): ?>
                                    <i class="bi bi-qr-code text-primary ms-1" title="QR Code Available"></i>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-5">Asset Name:</dt>
                            <dd class="col-sm-7">
                                <div class="d-flex flex-column">
                                    <strong><?= htmlspecialchars($asset['name']) ?></strong>
                                    <?php if (!empty($asset['standardized_name']) && $asset['standardized_name'] !== $asset['name']): ?>
                                        <div class="mt-1">
                                            <small class="text-success">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Standardized: <strong><?= htmlspecialchars($asset['standardized_name']) ?></strong>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($asset['disciplines'])): ?>
                                        <div class="mt-1">
                                            <small class="text-info">
                                                <i class="bi bi-tags me-1"></i>
                                                Disciplines: <?= htmlspecialchars($asset['disciplines']) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </dd>
                            
                            <dt class="col-sm-5">Status:</dt>
                            <dd class="col-sm-7">
                                <span class="badge <?= AssetHelper::getAssetStatusBadgeClass($asset['status']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $asset['status'])) ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Category:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Project:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Acquired Date:</dt>
                            <dd class="col-sm-7"><?= date('M j, Y', strtotime($asset['acquired_date'])) ?></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Brand:</dt>
                            <dd class="col-sm-7">
                                <div class="d-flex flex-column">
                                    <?php 
                                    // Priority: brand_name (from asset_brands) > procurement_item_brand > maker_name > 'N/A'
                                    $originalBrand = !empty($asset['brand_name']) ? $asset['brand_name'] : 
                                                   (!empty($asset['procurement_item_brand']) ? $asset['procurement_item_brand'] : 
                                                   ($asset['maker_name'] ?? 'N/A'));
                                    ?>
                                    <strong><?= htmlspecialchars($originalBrand) ?></strong>
                                    <?php if (!empty($asset['procurement_item_brand'])): ?>
                                        <small class="text-info mt-1">
                                            <i class="bi bi-cart me-1"></i>From procurement order
                                        </small>
                                    <?php elseif (!empty($asset['maker_name']) && $originalBrand === $asset['maker_name']): ?>
                                        <small class="text-muted mt-1">
                                            <i class="bi bi-building me-1"></i>Same as manufacturer
                                        </small>
                                    <?php endif; ?>
                                    <?php if (!empty($asset['standardized_brand']) && $asset['standardized_brand'] !== $originalBrand): ?>
                                        <small class="text-success mt-1">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Standardized: <strong><?= htmlspecialchars($asset['standardized_brand']) ?></strong>
                                        </small>
                                    <?php endif; ?>
                                    <?php if (!empty($asset['brand_tier'])): ?>
                                        <small class="text-muted mt-1">
                                            <i class="bi bi-award me-1"></i>
                                            Quality Tier: <?= ucfirst($asset['brand_tier']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </dd>
                            
                            <dt class="col-sm-5">Vendor:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['vendor_name'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Model:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['model'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Serial Number:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['serial_number'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Current Location:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['location'] ?? 'N/A') ?></dd>
                        </dl>
                    </div>
                </div>
                
                <hr>
                
                <!-- Description -->
                <?php if (!empty($asset['description'])): ?>
                    <div class="mb-3">
                        <h6>Description:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($asset['description'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Standardization Information -->
                <?php if (!empty($asset['standardized_name']) || !empty($asset['standardized_brand']) || !empty($asset['disciplines']) || !empty($asset['correction_confidence'])): ?>
                    <div class="mb-3">
                        <h6>Standardization Details:</h6>
                        <div class="row">
                            <?php if (!empty($asset['standardized_name']) && $asset['standardized_name'] !== $asset['name']): ?>
                                <div class="col-md-6 mb-2">
                                    <strong>Original Name:</strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($asset['name']) ?></small><br>
                                    <strong class="text-success">Standardized Name:</strong><br>
                                    <small class="text-success"><?= htmlspecialchars($asset['standardized_name']) ?></small>
                                </div>
                            <?php endif; ?>
                            
                            <?php 
                            $originalBrand = !empty($asset['procurement_item_brand']) ? $asset['procurement_item_brand'] : ($asset['maker_name'] ?? 'N/A');
                            if (!empty($asset['standardized_brand']) && $asset['standardized_brand'] !== $originalBrand): 
                            ?>
                                <div class="col-md-6 mb-2">
                                    <strong>Original Brand:</strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($originalBrand) ?></small>
                                    <?php if (!empty($asset['procurement_item_brand']) && $originalBrand === $asset['procurement_item_brand']): ?>
                                        <br><small class="text-info">(from procurement order)</small>
                                    <?php elseif ($originalBrand === $asset['maker_name']): ?>
                                        <br><small class="text-info">(same as manufacturer)</small>
                                    <?php endif; ?>
                                    <br><strong class="text-success">Standardized Brand:</strong><br>
                                    <small class="text-success"><?= htmlspecialchars($asset['standardized_brand']) ?></small>
                                    <?php if (!empty($asset['procurement_item_brand']) && !empty($asset['maker_name']) && $asset['procurement_item_brand'] !== $asset['maker_name']): ?>
                                        <br><small class="text-warning">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Note: Procurement brand "<?= htmlspecialchars($asset['procurement_item_brand']) ?>" differs from manufacturer "<?= htmlspecialchars($asset['maker_name']) ?>"
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($asset['disciplines'])): ?>
                                <div class="col-md-6 mb-2">
                                    <strong>Applicable Disciplines:</strong><br>
                                    <?php 
                                    $disciplines = explode(',', $asset['disciplines']);
                                    foreach ($disciplines as $discipline): 
                                    ?>
                                        <span class="badge bg-light text-dark me-1 mb-1"><?= htmlspecialchars(trim($discipline)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($asset['correction_confidence'])): ?>
                                <div class="col-md-6 mb-2">
                                    <strong>Standardization Confidence:</strong><br>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar <?= $asset['correction_confidence'] >= 0.8 ? 'bg-success' : ($asset['correction_confidence'] >= 0.6 ? 'bg-warning' : 'bg-danger') ?>" 
                                             role="progressbar" 
                                             style="width: <?= ($asset['correction_confidence'] * 100) ?>%">
                                            <?= round($asset['correction_confidence'] * 100) ?>%
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Technical Specifications -->
                <?php if (!empty($asset['specifications'])): ?>
                    <div class="mb-3">
                        <h6>Technical Specifications:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($asset['specifications'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Condition Notes -->
                <?php if (!empty($asset['condition_notes'])): ?>
                    <div class="mb-3">
                        <h6>Condition Notes:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($asset['condition_notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Legacy Asset Workflow -->
        <?php if (!empty($asset['asset_source']) && $asset['asset_source'] === 'legacy'): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Legacy Asset Workflow
                </h6>
            </div>
            <div class="card-body">
                <?php
                $workflowStatus = $asset['workflow_status'] ?? 'approved';
                $statusInfo = [
                    'draft' => ['icon' => 'bi-file-earmark', 'color' => 'secondary', 'text' => 'Draft - Asset pending verification'],
                    'pending_verification' => ['icon' => 'bi-clock-history', 'color' => 'warning', 'text' => 'Pending Verification - Awaiting Asset Director review'],
                    'pending_authorization' => ['icon' => 'bi-shield-check', 'color' => 'info', 'text' => 'Pending Authorization - Awaiting Finance Director approval'],
                    'approved' => ['icon' => 'bi-check-circle-fill', 'color' => 'success', 'text' => 'Approved - Asset ready for deployment'],
                    'rejected' => ['icon' => 'bi-x-circle', 'color' => 'danger', 'text' => 'Rejected - Asset requires attention']
                ];
                $status = $statusInfo[$workflowStatus] ?? $statusInfo['draft'];
                ?>

                <div class="d-flex align-items-center mb-3">
                    <i class="bi <?= $status['icon'] ?> text-<?= $status['color'] ?> me-2"></i>
                    <strong class="text-<?= $status['color'] ?>"><?= $status['text'] ?></strong>
                </div>

                <?php if (!empty($asset['verification_notes']) || !empty($asset['authorization_notes'])): ?>
                    <div class="border-top pt-3">
                        <h6>Workflow Notes:</h6>
                        <?php if (!empty($asset['verification_notes'])): ?>
                            <div class="mb-2">
                                <strong class="text-warning">Verification Notes:</strong><br>
                                <small class="text-muted"><?= nl2br(htmlspecialchars($asset['verification_notes'])) ?></small>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($asset['authorization_notes'])): ?>
                            <div class="mb-2">
                                <strong class="text-info">Authorization Notes:</strong><br>
                                <small class="text-muted"><?= nl2br(htmlspecialchars($asset['authorization_notes'])) ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Quick Actions for Workflow -->
                <?php if ($workflowStatus === 'pending_verification' && in_array($user['role_name'], $roleConfig['assets/legacy-verify'] ?? [])): ?>
                    <div class="border-top pt-3">
                        <a href="?route=assets/verify&id=<?= $asset['id'] ?>" class="btn btn-warning">
                            <i class="bi bi-check-circle me-1"></i>Verify Asset
                        </a>
                    </div>
                <?php elseif ($workflowStatus === 'pending_authorization' && in_array($user['role_name'], $roleConfig['assets/legacy-authorize'] ?? [])): ?>
                    <div class="border-top pt-3">
                        <a href="?route=assets/authorization-dashboard" class="btn btn-info">
                            <i class="bi bi-shield-check me-1"></i>Authorize Asset
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Financial Information -->
        <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director']) && ($asset['acquisition_cost'] || $asset['unit_cost'])): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-currency-dollar me-2"></i>Financial Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h5 class="text-primary"><?= formatCurrency($asset['acquisition_cost'] ?? 0) ?></h5>
                        <small class="text-muted">Acquisition Cost</small>
                    </div>
                    <?php if ($asset['unit_cost'] && $asset['unit_cost'] != $asset['acquisition_cost']): ?>
                    <div class="col-md-4">
                        <h5 class="text-info"><?= formatCurrency($asset['unit_cost']) ?></h5>
                        <small class="text-muted">Unit Cost</small>
                    </div>
                    <?php endif; ?>
                    <?php if ($asset['warranty_expiry']): ?>
                    <div class="col-md-4">
                        <h6 class="<?= strtotime($asset['warranty_expiry']) < time() ? 'text-danger' : 'text-success' ?>">
                            <?= date('M j, Y', strtotime($asset['warranty_expiry'])) ?>
                        </h6>
                        <small class="text-muted">Warranty Expiry</small>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($asset['is_client_supplied']): ?>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle me-2"></i>This asset was supplied by the client.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Procurement Information -->
        <?php if (!empty($asset['procurement_order_id']) || !empty($asset['po_number'])): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-cart me-2"></i>Procurement Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <?php if (!empty($asset['po_number'])): ?>
                        <dt class="col-sm-3">Purchase Order:</dt>
                        <dd class="col-sm-9">
                            <a href="?route=procurement-orders/view&id=<?= $asset['procurement_order_id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($asset['po_number']) ?>
                            </a>
                            <?php if (!empty($asset['procurement_title'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($asset['procurement_title']) ?></small>
                            <?php endif; ?>
                        </dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($asset['procurement_item_name'])): ?>
                        <dt class="col-sm-3">Procurement Item:</dt>
                        <dd class="col-sm-9">
                            <?= htmlspecialchars($asset['procurement_item_name']) ?>
                            <?php if (!empty($asset['procurement_item_brand'])): ?>
                                <br><small class="text-muted">Brand: <?= htmlspecialchars($asset['procurement_item_brand']) ?></small>
                            <?php endif; ?>
                        </dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-gear me-2"></i>Actions
                </h6>
            </div>
            <div class="card-body">
                <?php if ($asset['status'] === 'available' && in_array($user['role_name'], $roleConfig['withdrawals/create'] ?? [])): ?>
                    <a href="?route=withdrawals/create&asset_id=<?= $asset['id'] ?>" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-box-arrow-right me-1"></i>Withdraw Asset
                    </a>
                <?php endif; ?>
                
                <?php if ($asset['status'] === 'available' && in_array($user['role_name'], $roleConfig['transfers/create'] ?? [])): ?>
                    <a href="?route=transfers/create&asset_id=<?= $asset['id'] ?>" class="btn btn-info w-100 mb-2">
                        <i class="bi bi-arrow-left-right me-1"></i>Transfer Asset
                    </a>
                <?php endif; ?>
                
                <?php if ($asset['status'] === 'available' && in_array($user['role_name'], $roleConfig['borrowed-tools/create'] ?? [])): ?>
                    <a href="?route=borrowed-tools/create&asset_id=<?= $asset['id'] ?>" class="btn btn-warning w-100 mb-2">
                        <i class="bi bi-person-check me-1"></i>Lend Asset
                    </a>
                <?php endif; ?>
                
                <?php if (in_array($user['role_name'], $roleConfig['assets/edit'] ?? [])): ?>
                    <a href="?route=assets/edit&id=<?= $asset['id'] ?>" class="btn btn-outline-warning w-100 mb-2">
                        <i class="bi bi-pencil me-1"></i>Edit Asset
                    </a>
                <?php endif; ?>
                
                <?php if (in_array($user['role_name'], $roleConfig['maintenance/create'] ?? [])): ?>
                    <a href="?route=maintenance/create&asset_id=<?= $asset['id'] ?>" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-tools me-1"></i>Schedule Maintenance
                    </a>
                    
                    <a href="?route=incidents/create&asset_id=<?= $asset['id'] ?>" class="btn btn-outline-danger w-100 mb-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>Report Incident
                    </a>
                <?php endif; ?>
                
                <hr>
                
                <!-- Asset Location Section -->
                <div class="mb-3 p-2 border rounded">
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-geo-alt me-1"></i>Asset Location
                    </h6>
                    
                    <div class="d-flex flex-column gap-1">
                        <?php if (!empty($asset['sub_location'])): ?>
                            <small class="text-success">
                                <i class="bi bi-check-circle me-1"></i>
                                <strong><?= htmlspecialchars($asset['sub_location']) ?></strong>
                            </small>
                        <?php elseif (!empty($asset['location'])): ?>
                            <small class="text-warning">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <strong><?= htmlspecialchars($asset['location']) ?></strong>
                                <span class="text-muted">(General)</span>
                            </small>
                        <?php else: ?>
                            <small class="text-danger">
                                <i class="bi bi-x-circle me-1"></i>No location assigned
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- QR Tag Status Section -->
                <div class="mb-3 p-2 border rounded">
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-qr-code me-1"></i>QR Tag Status
                    </h6>
                    
                    <div class="d-flex flex-column gap-1">
                        <!-- QR Generated Status -->
                        <?php if (!empty($asset['qr_code'])): ?>
                            <small class="text-success">
                                <i class="bi bi-check-circle me-1"></i>QR Code Generated
                            </small>
                        <?php else: ?>
                            <small class="text-danger">
                                <i class="bi bi-x-circle me-1"></i>QR Code Missing
                            </small>
                        <?php endif; ?>
                        
                        <!-- Tag Printed Status -->
                        <?php if (!empty($asset['qr_tag_printed'])): ?>
                            <small class="text-success">
                                <i class="bi bi-printer me-1"></i>Tag Printed
                                <span class="text-muted">(<?= date('M j', strtotime($asset['qr_tag_printed'])) ?>)</span>
                            </small>
                        <?php elseif (!empty($asset['qr_code'])): ?>
                            <small class="text-warning">
                                <i class="bi bi-printer me-1"></i>Needs Printing
                            </small>
                        <?php endif; ?>
                        
                        <!-- Tag Applied Status -->
                        <?php if (!empty($asset['qr_tag_applied'])): ?>
                            <small class="text-success">
                                <i class="bi bi-hand-index me-1"></i>Tag Applied
                                <span class="text-muted">(<?= date('M j', strtotime($asset['qr_tag_applied'])) ?>)</span>
                            </small>
                        <?php elseif (!empty($asset['qr_tag_printed'])): ?>
                            <small class="text-warning">
                                <i class="bi bi-hand-index me-1"></i>Needs Application
                            </small>
                        <?php endif; ?>
                        
                        <!-- Tag Verified Status -->
                        <?php if (!empty($asset['qr_tag_verified'])): ?>
                            <small class="text-success">
                                <i class="bi bi-check-circle me-1"></i>Tag Verified
                                <span class="text-muted">(<?= date('M j', strtotime($asset['qr_tag_verified'])) ?>)</span>
                            </small>
                        <?php elseif (!empty($asset['qr_tag_applied'])): ?>
                            <small class="text-warning">
                                <i class="bi bi-check-circle me-1"></i>Needs Verification
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- QR Actions -->
                <?php if (!empty($asset['qr_code'])): ?>
                    <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="showQRCode()">
                        <i class="bi bi-qr-code me-1"></i>Show QR Code
                    </button>
                    
                    <a href="?route=assets/print-tag&id=<?= $asset['id'] ?>" 
                       class="btn btn-outline-success w-100 mb-2" target="_blank">
                        <i class="bi bi-printer me-1"></i>Print QR Tag
                    </a>
                <?php else: ?>
                    <button type="button" class="btn btn-outline-warning w-100 mb-2" onclick="generateQRCode()">
                        <i class="bi bi-qr-code me-1"></i>Generate QR Code
                    </button>
                <?php endif; ?>
                
                <!-- Location Assignment (for appropriate roles) -->
                <?php if (in_array($user['role_name'], ['Warehouseman', 'Site Inventory Clerk', 'System Admin'])): ?>
                    <button type="button" class="btn btn-outline-warning w-100 mb-2" onclick="showLocationAssignment()">
                        <i class="bi bi-geo-alt me-1"></i>
                        <?= empty($asset['sub_location']) ? 'Assign Location' : 'Change Location' ?>
                    </button>
                <?php endif; ?>
                
                <!-- Tag Management Actions (for appropriate roles) -->
                <?php if (in_array($user['role_name'], ['Warehouseman', 'Site Inventory Clerk', 'System Admin'])): ?>
                    <?php if (!empty($asset['qr_tag_printed']) && empty($asset['qr_tag_applied'])): ?>
                        <button type="button" class="btn btn-outline-info w-100 mb-2" onclick="markTagApplied()">
                            <i class="bi bi-hand-index me-1"></i>Mark Tag as Applied
                        </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($asset['qr_tag_applied']) && empty($asset['qr_tag_verified']) && $user['role_name'] === 'Site Inventory Clerk'): ?>
                        <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="verifyTag()">
                            <i class="bi bi-check-circle me-1"></i>Verify Tag Placement
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                
                <button type="button" class="btn btn-outline-secondary w-100" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Print Details
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Complete Audit Trail Section -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-clock-history me-2"></i>Complete Audit Trail
        </h5>
    </div>
    <div class="card-body">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" id="auditTrailTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="activity-logs-tab" data-bs-toggle="tab" data-bs-target="#activity-logs" type="button" role="tab" aria-controls="activity-logs" aria-selected="true">
                    <i class="bi bi-activity me-1"></i>Activity Logs
                    <?php if (!empty($completeLogs)): ?>
                        <span class="badge bg-primary ms-1"><?= count($completeLogs) ?></span>
                    <?php endif; ?>
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="incidents-tab" data-bs-toggle="tab" data-bs-target="#incidents" type="button" role="tab" aria-controls="incidents" aria-selected="false">
                    <i class="bi bi-exclamation-triangle me-1"></i>Incidents
                    <?php if (!empty($incidents)): ?>
                        <span class="badge bg-warning ms-1"><?= count($incidents) ?></span>
                    <?php endif; ?>
                </button>
            </li>

            <?php if ($isNonConsumable): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab" aria-controls="maintenance" aria-selected="false">
                        <i class="bi bi-wrench me-1"></i>Maintenance
                        <?php if (!empty($maintenance)): ?>
                            <span class="badge bg-info ms-1"><?= count($maintenance) ?></span>
                        <?php endif; ?>
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="borrowed-tools-tab" data-bs-toggle="tab" data-bs-target="#borrowed-tools" type="button" role="tab" aria-controls="borrowed-tools" aria-selected="false">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Borrowing History
                        <?php if (!empty($borrowHistory)): ?>
                            <span class="badge bg-success ms-1"><?= count($borrowHistory) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            <?php endif; ?>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="transfers-tab" data-bs-toggle="tab" data-bs-target="#transfers" type="button" role="tab" aria-controls="transfers" aria-selected="false">
                    <i class="bi bi-arrow-left-right me-1"></i>Transfers
                    <?php if (!empty($transfers)): ?>
                        <span class="badge bg-secondary ms-1"><?= count($transfers) ?></span>
                    <?php endif; ?>
                </button>
            </li>

            <?php if ($isConsumable): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="withdrawals-tab" data-bs-toggle="tab" data-bs-target="#withdrawals" type="button" role="tab" aria-controls="withdrawals" aria-selected="false">
                        <i class="bi bi-box-arrow-down me-1"></i>Withdrawals
                        <?php if (!empty($withdrawals)): ?>
                            <span class="badge bg-danger ms-1"><?= count($withdrawals) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content mt-3" id="auditTrailTabContent">
            <!-- Activity Logs Tab -->
            <div class="tab-pane fade show active" id="activity-logs" role="tabpanel" aria-labelledby="activity-logs-tab">
                <?php include APP_ROOT . '/views/assets/partials/_activity_logs.php'; ?>
            </div>

            <!-- Incidents Tab -->
            <div class="tab-pane fade" id="incidents" role="tabpanel" aria-labelledby="incidents-tab">
                <?php include APP_ROOT . '/views/assets/partials/_incidents.php'; ?>
            </div>

            <!-- Maintenance Tab (Non-Consumable Only) -->
            <?php if ($isNonConsumable): ?>
                <div class="tab-pane fade" id="maintenance" role="tabpanel" aria-labelledby="maintenance-tab">
                    <?php include APP_ROOT . '/views/assets/partials/_maintenance.php'; ?>
                </div>

                <!-- Borrowed Tools Tab (Non-Consumable Only) -->
                <div class="tab-pane fade" id="borrowed-tools" role="tabpanel" aria-labelledby="borrowed-tools-tab">
                    <?php include APP_ROOT . '/views/assets/partials/_borrowed_tools.php'; ?>
                </div>
            <?php endif; ?>

            <!-- Transfers Tab -->
            <div class="tab-pane fade" id="transfers" role="tabpanel" aria-labelledby="transfers-tab">
                <?php include APP_ROOT . '/views/assets/partials/_transfers.php'; ?>
            </div>

            <!-- Withdrawals Tab (Consumable Only) -->
            <?php if ($isConsumable): ?>
                <div class="tab-pane fade" id="withdrawals" role="tabpanel" aria-labelledby="withdrawals-tab">
                    <?php include APP_ROOT . '/views/assets/partials/_withdrawals.php'; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<?php if (!empty($asset['qr_code'])): ?>
<div class="modal fade" id="qrCodeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asset QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrCodeContainer">
                    <div class="border p-4 d-inline-block">
                        <div id="qrCodeImage" style="width: 200px; height: 200px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-qr-code display-1 text-muted"></i>
                        </div>
                    </div>
                </div>
                <p class="mt-3 mb-0">
                    <strong><?= htmlspecialchars($asset['ref']) ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($asset['name']) ?></small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Location Assignment Modal -->
<?php if (in_array($user['role_name'], ['Warehouseman', 'Site Inventory Clerk', 'System Admin'])): ?>
<div class="modal fade" id="locationAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asset Location Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="locationAssignmentForm">
                    <div class="mb-3">
                        <label for="projectLocation" class="form-label">Project Location</label>
                        <input type="text" class="form-control" id="projectLocation" 
                               value="<?= htmlspecialchars($asset['location'] ?? 'N/A') ?>" readonly>
                        <small class="text-muted">This is the general project location and cannot be changed here.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subLocation" class="form-label">Specific Sub-Location <span class="text-danger">*</span></label>
                        <select class="form-select" id="subLocation" required>
                            <option value="">Select sub-location...</option>
                            <option value="Warehouse" <?= ($asset['sub_location'] ?? '') === 'Warehouse' ? 'selected' : '' ?>>Warehouse</option>
                            <option value="Tool Room" <?= ($asset['sub_location'] ?? '') === 'Tool Room' ? 'selected' : '' ?>>Tool Room</option>
                            <option value="Storage Area" <?= ($asset['sub_location'] ?? '') === 'Storage Area' ? 'selected' : '' ?>>Storage Area</option>
                            <option value="Office" <?= ($asset['sub_location'] ?? '') === 'Office' ? 'selected' : '' ?>>Office</option>
                            <option value="Field Storage" <?= ($asset['sub_location'] ?? '') === 'Field Storage' ? 'selected' : '' ?>>Field Storage</option>
                            <option value="Maintenance Shop" <?= ($asset['sub_location'] ?? '') === 'Maintenance Shop' ? 'selected' : '' ?>>Maintenance Shop</option>
                            <option value="Vehicle" <?= ($asset['sub_location'] ?? '') === 'Vehicle' ? 'selected' : '' ?>>Vehicle</option>
                            <option value="Other">Other (specify below)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="customLocationDiv" style="display: none;">
                        <label for="customLocation" class="form-label">Custom Sub-Location</label>
                        <input type="text" class="form-control" id="customLocation" 
                               placeholder="Enter custom sub-location..." maxlength="100">
                    </div>
                    
                    <div class="mb-3">
                        <label for="locationNotes" class="form-label">Location Notes (Optional)</label>
                        <textarea class="form-control" id="locationNotes" rows="2" 
                                  placeholder="Additional notes about the asset location..." maxlength="255"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="assignLocation()">
                    <i class="bi bi-geo-alt me-1"></i>Assign Location
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Show QR Code Modal
function showQRCode() {
    const modal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
    
    // Generate QR code image using the asset reference
    const assetRef = '<?= htmlspecialchars($asset['ref']) ?>';
    const qrImageDiv = document.getElementById('qrCodeImage');
    
    // Use QR Server API (free service) to generate QR code image
    const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(assetRef)}`;
    
    qrImageDiv.innerHTML = `<img src="${qrCodeUrl}" alt="QR Code for ${assetRef}" style="max-width: 200px; max-height: 200px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
    <div style="width: 200px; height: 200px; background: #f8f9fa; display: none; align-items: center; justify-content: center; flex-direction: column;">
        <i class="bi bi-qr-code display-1 text-muted"></i>
        <small class="text-muted mt-2">QR Code Preview</small>
    </div>`;
    
    modal.show();
}

// Show Location Assignment Modal
function showLocationAssignment() {
    const modal = new bootstrap.Modal(document.getElementById('locationAssignmentModal'));
    
    // Handle custom location display
    const subLocationSelect = document.getElementById('subLocation');
    const customLocationDiv = document.getElementById('customLocationDiv');
    const customLocationInput = document.getElementById('customLocation');
    
    // Check if current sub_location is a custom one
    const currentSubLocation = '<?= htmlspecialchars($asset['sub_location'] ?? '') ?>';
    const standardLocations = ['Warehouse', 'Tool Room', 'Storage Area', 'Office', 'Field Storage', 'Maintenance Shop', 'Vehicle'];
    
    if (currentSubLocation && !standardLocations.includes(currentSubLocation)) {
        subLocationSelect.value = 'Other';
        customLocationDiv.style.display = 'block';
        customLocationInput.value = currentSubLocation;
    }
    
    // Add event listener for sub-location change
    subLocationSelect.addEventListener('change', function() {
        if (this.value === 'Other') {
            customLocationDiv.style.display = 'block';
            customLocationInput.required = true;
        } else {
            customLocationDiv.style.display = 'none';
            customLocationInput.required = false;
            customLocationInput.value = '';
        }
    });
    
    modal.show();
}

// Assign Location
function assignLocation() {
    const form = document.getElementById('locationAssignmentForm');
    const subLocationSelect = document.getElementById('subLocation');
    const customLocationInput = document.getElementById('customLocation');
    const locationNotes = document.getElementById('locationNotes');
    const submitButton = event.target;
    
    // Validate form
    if (!subLocationSelect.value) {
        alert('Please select a sub-location');
        return;
    }
    
    if (subLocationSelect.value === 'Other' && !customLocationInput.value.trim()) {
        alert('Please specify the custom sub-location');
        return;
    }
    
    // Determine final sub-location value
    const finalSubLocation = subLocationSelect.value === 'Other' ? 
        customLocationInput.value.trim() : subLocationSelect.value;
    
    // Show loading state
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="bi bi-spinner spin-animation me-1"></i>Assigning...';
    submitButton.disabled = true;
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('?route=assets/assign-location', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken
        },
        body: `asset_id=<?= $asset['id'] ?>&sub_location=${encodeURIComponent(finalSubLocation)}&notes=${encodeURIComponent(locationNotes.value.trim())}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and reload page to show updated location
            const modal = bootstrap.Modal.getInstance(document.getElementById('locationAssignmentModal'));
            modal.hide();
            location.reload();
        } else {
            alert('Failed to assign location: ' + (data.message || 'Unknown error'));
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while assigning location');
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
}

// Generate QR Code for asset
function generateQRCode() {
    const assetId = <?= $asset['id'] ?>;
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="bi bi-spinner spin-animation me-1"></i>Generating...';
    button.disabled = true;
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('?route=api/assets/generate-qr', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken
        },
        body: `asset_id=${assetId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to show updated QR status
        } else {
            alert('Failed to generate QR code: ' + (data.message || 'Unknown error'));
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while generating QR code');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Mark tag as applied
function markTagApplied() {
    const assetId = <?= $asset['id'] ?>;
    const button = event.target;
    const originalText = button.innerHTML;
    
    if (!confirm('Mark QR tag as applied to this asset?')) {
        return;
    }
    
    // Show loading state
    button.innerHTML = '<i class="bi bi-spinner spin-animation me-1"></i>Updating...';
    button.disabled = true;
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('?route=api/assets/mark-tags-applied', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken
        },
        body: `asset_ids=${assetId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to show updated status
        } else {
            alert('Failed to update tag status: ' + (data.message || 'Unknown error'));
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating tag status');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Verify tag placement
function verifyTag() {
    const assetId = <?= $asset['id'] ?>;
    const button = event.target;
    const originalText = button.innerHTML;
    
    if (!confirm('Verify that QR tag is properly placed and readable?')) {
        return;
    }
    
    // Show loading state
    button.innerHTML = '<i class="bi bi-spinner spin-animation me-1"></i>Verifying...';
    button.disabled = true;
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('?route=api/assets/verify-tag', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken
        },
        body: `asset_id=${assetId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to show updated status
        } else {
            alert('Failed to verify tag: ' + (data.message || 'Unknown error'));
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while verifying tag');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>

<style>
.stat-item {
    padding: 10px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
}

.stat-label {
    font-size: 0.8rem;
    color: #6c757d;
}

.spin-animation {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media print {
    .btn, .card-header {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
