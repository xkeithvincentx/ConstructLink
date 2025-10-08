<?php
/**
 * Asset List Partial
 * Displays asset table/cards with pagination (mobile + desktop views)
 */
?>

<!-- Inventory Table -->
<div class="card">
    <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
        <h6 class="card-title mb-0">Inventory</h6>
        <div class="d-flex flex-wrap gap-2">
            <?php if (in_array($userRole, $roleConfig['assets/export'] ?? [])): ?>
                <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel me-1"></i>
                    <span class="d-none d-md-inline">Export</span>
                </button>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>
                <span class="d-none d-md-inline">Print</span>
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($assets)): ?>
            <div class="text-center py-5">
                <i class="bi bi-box-seam display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No inventory items found</h5>
                <p class="text-muted">Try adjusting your filters or add your first item to the system.</p>
                <?php if (in_array($userRole, $roleConfig['assets/create'] ?? [])): ?>
                    <a href="?route=assets/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Add First Item
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Mobile Card View (visible on small screens) -->
            <div class="d-md-none">
                <?php foreach ($assets as $asset): ?>
                    <?php
                    // Get asset data for mobile view
                    $status = $asset['status'] ?? 'available';
                    $assetSource = $asset['asset_source'] ?? 'manual';
                    $workflowStatus = $asset['workflow_status'] ?? 'approved';
                    $quantity = (int)($asset['quantity'] ?? 1);
                    $availableQuantity = (int)($asset['available_quantity'] ?? 1);
                    $isConsumable = isset($asset['is_consumable']) && $asset['is_consumable'] == 1;

                    // Override status display for pending legacy assets
                    if ($assetSource === 'legacy' && $workflowStatus !== 'approved'):
                        $displayStatus = $workflowStatus === 'pending_verification' ? 'Pending Verification' : 'Pending Authorization';
                        $statusClass = 'bg-warning text-dark';
                    else:
                        $statusClasses = [
                            'available' => 'bg-success',
                            'in_use' => 'bg-primary',
                            'borrowed' => 'bg-info',
                            'in_transit' => 'bg-warning',
                            'under_maintenance' => 'bg-secondary',
                            'retired' => 'bg-dark',
                            'disposed' => 'bg-danger'
                        ];
                        $statusClass = $statusClasses[$status] ?? 'bg-secondary';
                        $displayStatus = ucfirst(str_replace('_', ' ', $status));
                    endif;
                    ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="text-decoration-none fw-bold">
                                        <?= htmlspecialchars($asset['ref'] ?? 'N/A') ?>
                                    </a>
                                    <?php if (!empty($asset['qr_code'])): ?>
                                        <i class="bi bi-qr-code text-primary ms-1" title="QR Code Available"></i>
                                    <?php endif; ?>
                                </div>
                                <span class="badge <?= $statusClass ?>"><?= $displayStatus ?></span>
                            </div>

                            <!-- Asset Name -->
                            <div class="mb-2">
                                <div class="fw-medium"><?= htmlspecialchars($asset['name'] ?? 'Unknown') ?></div>
                                <?php if (!empty($asset['serial_number'])): ?>
                                    <small class="text-muted">S/N: <?= htmlspecialchars($asset['serial_number']) ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- Category and Location/Project -->
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1">Category</small>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?></span>
                                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                                    <small class="text-muted d-block mt-2 mb-1">Project</small>
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?></span>
                                <?php else: ?>
                                    <small class="text-muted d-block mt-2 mb-1">Location</small>
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($asset['location'] ?? 'Warehouse') ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Quantity -->
                            <div class="mb-2">
                                <small class="text-muted">Quantity: </small>
                                <strong><?= number_format($availableQuantity) ?> / <?= number_format($quantity) ?></strong>
                                <small class="text-muted"><?= htmlspecialchars($asset['unit'] ?? 'pcs') ?></small>
                                <?php if ($isConsumable && $availableQuantity == 0): ?>
                                    <span class="badge bg-danger ms-1">Out of stock</span>
                                <?php elseif ($isConsumable && $availableQuantity <= ($quantity * 0.2)): ?>
                                    <span class="badge bg-warning text-dark ms-1">Low stock</span>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 mt-3">
                                <!-- Primary View Button -->
                                <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="btn btn-sm btn-primary flex-grow-1">
                                    <i class="bi bi-eye me-1"></i>View Details
                                </a>

                                <!-- Actions Dropdown -->
                                <div class="btn-group">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php
                                        // Workflow actions for legacy assets
                                        if ($assetSource === 'legacy'):
                                            if ($workflowStatus === 'pending_verification' && in_array($userRole, $roleConfig['assets/legacy-verify'] ?? [])):
                                        ?>
                                            <li>
                                                <a class="dropdown-item text-warning"
                                                   href="#"
                                                   onclick="event.preventDefault(); openEnhancedVerification(<?= $asset['id'] ?>);">
                                                    <i class="bi bi-shield-check me-2"></i>Verify Item
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                        <?php
                                            elseif ($workflowStatus === 'pending_authorization' && in_array($userRole, $roleConfig['assets/legacy-authorize'] ?? [])):
                                        ?>
                                            <li>
                                                <a class="dropdown-item text-info"
                                                   href="#"
                                                   onclick="event.preventDefault(); openEnhancedAuthorization(<?= $asset['id'] ?>);">
                                                    <i class="bi bi-shield-check me-2"></i>Authorize Item
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                        <?php
                                            endif;
                                        endif;
                                        ?>

                                        <?php if (in_array($userRole, $roleConfig['assets/edit'] ?? [])): ?>
                                            <li>
                                                <a class="dropdown-item" href="?route=assets/edit&id=<?= $asset['id'] ?>">
                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php
                                        // Borrow/Withdraw actions - only for approved assets
                                        // Legacy assets must be approved before they can be borrowed/withdrawn
                                        $isApproved = ($assetSource === 'manual') || ($assetSource === 'legacy' && $workflowStatus === 'approved');

                                        if ($status === 'available' && $isApproved):
                                            if ($isConsumable && in_array($userRole, $roleConfig['withdrawals/create'] ?? [])):
                                        ?>
                                            <li>
                                                <a class="dropdown-item text-success" href="?route=withdrawals/create&asset_id=<?= $asset['id'] ?>">
                                                    <i class="bi bi-box-arrow-right me-2"></i>Withdraw
                                                </a>
                                            </li>
                                        <?php
                                            elseif (!$isConsumable && in_array($userRole, $roleConfig['borrowed-tools/create'] ?? [])):
                                        ?>
                                            <li>
                                                <a class="dropdown-item text-info" href="?route=borrowed-tools/create&asset_id=<?= $asset['id'] ?>">
                                                    <i class="bi bi-clock-history me-2"></i>Borrow
                                                </a>
                                            </li>
                                        <?php
                                            endif;
                                        endif;
                                        ?>

                                        <?php if (in_array($userRole, $roleConfig['assets/delete'] ?? [])): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger"
                                                   href="#"
                                                   onclick="event.preventDefault(); deleteAsset(<?= $asset['id'] ?>);">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Desktop Table View (hidden on small screens) -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-sm" id="assetsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">Reference</th>
                            <th>Item</th>
                            <th class="d-none d-md-table-cell">Category</th>
                            <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                            <th class="d-none d-lg-table-cell">Project</th>
                            <?php else: ?>
                            <th class="d-none d-lg-table-cell">Location</th>
                            <?php endif; ?>
                            <th class="text-center">Quantity</th>
                            <th class="text-center">Status</th>
                            <th class="d-none d-xl-table-cell text-center">QR Tag</th>
                            <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                            <th class="d-none d-xxl-table-cell text-center">Workflow</th>
                            <?php endif; ?>
                            <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                            <th class="d-none d-lg-table-cell text-end">Value</th>
                            <?php endif; ?>
                            <th class="text-center" style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td>
                                    <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="text-decoration-none">
                                        <strong><?= htmlspecialchars($asset['ref'] ?? 'N/A') ?></strong>
                                    </a>
                                    <?php if (!empty($asset['qr_code'])): ?>
                                        <i class="bi bi-qr-code text-primary ms-1" title="QR Code Available"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($asset['name'] ?? 'Unknown') ?></div>
                                        <?php if (!empty($asset['serial_number'])): ?>
                                            <small class="text-muted">S/N: <?= htmlspecialchars($asset['serial_number']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <?php else: ?>
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($asset['location'] ?? 'Warehouse') ?>
                                    </span>
                                </td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <?php
                                    // Get asset quantity information
                                    $quantity = (int)($asset['quantity'] ?? 1);
                                    $availableQuantity = (int)($asset['available_quantity'] ?? 1);
                                    $unit = $asset['unit'] ?? 'pcs';
                                    $isConsumable = isset($asset['is_consumable']) && $asset['is_consumable'] == 1;
                                    ?>
                                    
                                    <?php if ($isConsumable): ?>
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="mb-1">
                                                <span class="badge bg-primary"><?= number_format($availableQuantity) ?></span>
                                                <span class="text-muted">/ <?= number_format($quantity) ?></span>
                                            </div>
                                            <div class="text-center">
                                                <small class="text-muted d-block d-sm-none">
                                                    <?= htmlspecialchars($unit) ?>
                                                </small>
                                                <small class="text-muted d-none d-sm-block">
                                                    Available / Total <?= htmlspecialchars($unit) ?>
                                                </small>
                                                <?php if ($availableQuantity == 0): ?>
                                                    <small class="text-danger">
                                                        <i class="bi bi-exclamation-circle me-1"></i>Out of stock
                                                    </small>
                                                <?php elseif ($availableQuantity < $quantity): ?>
                                                    <small class="text-warning">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                                        <?= number_format($quantity - $availableQuantity) ?> in use
                                                    </small>
                                                <?php elseif ($availableQuantity <= ($quantity * 0.2)): ?>
                                                    <small class="text-warning">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>Low stock
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center">
                                            <span class="badge bg-light text-dark">1 <?= htmlspecialchars($unit) ?></span>
                                            <small class="text-muted d-block d-none d-sm-block">Individual item</small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $status = $asset['status'] ?? 'unknown';
                                    $assetSource = $asset['asset_source'] ?? 'manual';
                                    $workflowStatus = $asset['workflow_status'] ?? 'approved';

                                    // Override status display for pending legacy assets
                                    if ($assetSource === 'legacy' && $workflowStatus !== 'approved'):
                                        $displayStatus = $workflowStatus === 'pending_verification' ? 'Pending Verification' : 'Pending Authorization';
                                        $statusClass = 'bg-warning text-dark';
                                    else:
                                        $statusClasses = [
                                            'available' => 'bg-success',
                                            'in_use' => 'bg-primary',
                                            'borrowed' => 'bg-info',
                                            'under_maintenance' => 'bg-warning',
                                            'retired' => 'bg-secondary',
                                            'disposed' => 'bg-dark'
                                        ];
                                        $statusClass = $statusClasses[$status] ?? 'bg-secondary';
                                        $displayStatus = ucfirst(str_replace('_', ' ', $status));
                                    endif;
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= $displayStatus ?>
                                    </span>

                                    <?php if ($isConsumable && $availableQuantity == 0): ?>
                                        <small class="text-danger d-block">
                                            <i class="bi bi-exclamation-circle me-1"></i>Out of stock
                                        </small>
                                    <?php elseif ($isConsumable && $availableQuantity <= ($quantity * 0.2)): ?>
                                        <small class="text-warning d-block">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Low stock
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-xl-table-cell text-center">
                                    <?php 
                                    // QR Tag Status Indicators
                                    $hasQR = !empty($asset['qr_code']);
                                    $isPrinted = !empty($asset['qr_tag_printed']);
                                    $isApplied = !empty($asset['qr_tag_applied']);
                                    $isVerified = !empty($asset['qr_tag_verified']);
                                    ?>
                                    
                                    <div class="d-flex flex-column gap-1">
                                        <?php if ($hasQR): ?>
                                            <small class="text-success">
                                                <i class="bi bi-qr-code me-1"></i>QR Generated
                                            </small>
                                            
                                            <?php if ($isPrinted): ?>
                                                <small class="text-info">
                                                    <i class="bi bi-printer me-1"></i>Printed
                                                </small>
                                            <?php else: ?>
                                                <small class="text-warning">
                                                    <i class="bi bi-printer me-1"></i>Need Print
                                                </small>
                                            <?php endif; ?>
                                            
                                            <?php if ($isApplied): ?>
                                                <small class="text-primary">
                                                    <i class="bi bi-hand-index me-1"></i>Applied
                                                </small>
                                            <?php elseif ($isPrinted): ?>
                                                <small class="text-warning">
                                                    <i class="bi bi-hand-index me-1"></i>Need Apply
                                                </small>
                                            <?php endif; ?>
                                            
                                            <?php if ($isVerified): ?>
                                                <small class="text-success">
                                                    <i class="bi bi-check-circle me-1"></i>Verified
                                                </small>
                                            <?php elseif ($isApplied): ?>
                                                <small class="text-warning">
                                                    <i class="bi bi-check-circle me-1"></i>Need Verify
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <small class="text-danger">
                                                <i class="bi bi-x-circle me-1"></i>No QR Code
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                                <td class="d-none d-xxl-table-cell text-center">
                                    <?php
                                    $workflowStatus = $asset['workflow_status'] ?? 'approved';
                                    $assetSource = $asset['asset_source'] ?? 'manual';
                                    
                                    // Only show workflow status for legacy assets
                                    if ($assetSource === 'legacy'):
                                        $workflowClasses = [
                                            'draft' => 'bg-secondary',
                                            'pending_verification' => 'bg-warning',
                                            'pending_authorization' => 'bg-info',
                                            'approved' => 'bg-success'
                                        ];
                                        $workflowClass = $workflowClasses[$workflowStatus] ?? 'bg-secondary';
                                        $workflowText = [
                                            'draft' => 'Draft',
                                            'pending_verification' => 'Pending Verification',
                                            'pending_authorization' => 'Pending Authorization', 
                                            'approved' => 'Approved'
                                        ];
                                        $statusText = $workflowText[$workflowStatus] ?? 'Unknown';
                                    ?>
                                        <span class="badge <?= $workflowClass ?>" title="Legacy asset workflow status">
                                            <i class="bi bi-gear me-1"></i><?= $statusText ?>
                                        </span>
                                        <?php if ($workflowStatus === 'pending_verification'): ?>
                                            <small class="text-warning d-block">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Needs verification
                                            </small>
                                        <?php elseif ($workflowStatus === 'pending_authorization'): ?>
                                            <small class="text-info d-block">
                                                <i class="bi bi-shield-exclamation me-1"></i>Needs authorization
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark" title="Standard asset">
                                            <i class="bi bi-check-circle me-1"></i>Standard
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                                <td class="d-none d-lg-table-cell text-end">
                                    <?php if ($asset['acquisition_cost']): ?>
                                        <strong><?= formatCurrency($asset['acquisition_cost']) ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <?php
                                    // Get asset data for actions
                                    $assetSource = $asset['asset_source'] ?? 'manual';
                                    $workflowStatus = $asset['workflow_status'] ?? 'approved';
                                    ?>

                                    <div class="btn-group">
                                        <!-- Primary View Button -->
                                        <a href="?route=assets/view&id=<?= $asset['id'] ?>"
                                           class="btn btn-primary btn-sm">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <!-- Actions Dropdown -->
                                        <button type="button"
                                                class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <!-- View Details -->
                                            <li>
                                                <a class="dropdown-item" href="?route=assets/view&id=<?= $asset['id'] ?>">
                                                    <i class="bi bi-eye me-2"></i>View Details
                                                </a>
                                            </li>

                                            <?php
                                            // Workflow actions for legacy assets
                                            if ($assetSource === 'legacy'):
                                                if ($workflowStatus === 'pending_verification' && in_array($userRole, $roleConfig['assets/legacy-verify'] ?? [])):
                                            ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-warning"
                                                       href="#"
                                                       onclick="event.preventDefault(); openEnhancedVerification(<?= $asset['id'] ?>);">
                                                        <i class="bi bi-shield-check me-2"></i>Verify Item
                                                    </a>
                                                </li>
                                            <?php
                                                elseif ($workflowStatus === 'pending_authorization' && in_array($userRole, $roleConfig['assets/legacy-authorize'] ?? [])):
                                            ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-info"
                                                       href="#"
                                                       onclick="event.preventDefault(); openEnhancedAuthorization(<?= $asset['id'] ?>);">
                                                        <i class="bi bi-shield-check me-2"></i>Authorize Item
                                                    </a>
                                                </li>
                                            <?php
                                                endif;
                                            endif;
                                            ?>

                                            <?php if (in_array($userRole, $roleConfig['assets/edit'] ?? [])): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="?route=assets/edit&id=<?= $asset['id'] ?>">
                                                        <i class="bi bi-pencil me-2"></i>Edit
                                                    </a>
                                                </li>
                                            <?php endif; ?>

                                            <?php
                                            // Borrow/Withdraw actions - only for approved assets
                                            // Legacy assets must be approved before they can be borrowed/withdrawn
                                            $isApproved = ($assetSource === 'manual') || ($assetSource === 'legacy' && $workflowStatus === 'approved');

                                            if ($status === 'available' && $isApproved):
                                                if ($isConsumable && in_array($userRole, $roleConfig['withdrawals/create'] ?? [])):
                                            ?>
                                                <li>
                                                    <a class="dropdown-item text-success" href="?route=withdrawals/create&asset_id=<?= $asset['id'] ?>">
                                                        <i class="bi bi-box-arrow-right me-2"></i>Withdraw
                                                    </a>
                                                </li>
                                            <?php
                                                elseif (!$isConsumable && in_array($userRole, $roleConfig['borrowed-tools/create'] ?? [])):
                                            ?>
                                                <li>
                                                    <a class="dropdown-item text-info" href="?route=borrowed-tools/create&asset_id=<?= $asset['id'] ?>">
                                                        <i class="bi bi-clock-history me-2"></i>Borrow
                                                    </a>
                                                </li>
                                            <?php
                                                endif;
                                            endif;
                                            ?>

                                            <?php if (in_array($userRole, $roleConfig['assets/delete'] ?? [])): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-danger"
                                                       href="#"
                                                       onclick="event.preventDefault(); deleteAsset(<?= $asset['id'] ?>);">
                                                        <i class="bi bi-trash me-2"></i>Delete
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Assets pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <?php 
                                $prevParams = array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route');
                                $prevParams['page'] = $pagination['current_page'] - 1;
                                ?>
                                <a class="page-link" href="?route=assets&<?= http_build_query($prevParams) ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <?php 
                                $pageParams = array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route');
                                $pageParams['page'] = $i;
                                ?>
                                <a class="page-link" href="?route=assets&<?= http_build_query($pageParams) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <?php 
                                $nextParams = array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route');
                                $nextParams['page'] = $pagination['current_page'] + 1;
                                ?>
                                <a class="page-link" href="?route=assets&<?= http_build_query($nextParams) ?>">
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

