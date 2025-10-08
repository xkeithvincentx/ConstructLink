<?php
/**
 * Action Buttons Partial
 * Displays primary and secondary action buttons for asset management
 */
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4 gap-2">
    <!-- Primary Actions (Left) -->
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Primary actions">
        <?php if (in_array($userRole, $roleConfig['assets/create'] ?? [])): ?>
            <a href="?route=assets/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">Add Item</span>
                <span class="d-sm-none">Add</span>
            </a>
        <?php endif; ?>
        <?php if (in_array($userRole, $roleConfig['assets/legacy-create'] ?? [])): ?>
            <a href="?route=assets/legacy-create" class="btn btn-success btn-sm">
                <i class="bi bi-clock-history me-1"></i>
                <span class="d-none d-sm-inline">Add Legacy</span>
                <span class="d-sm-none">Legacy</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Secondary Actions (Right) -->
    <div class="btn-toolbar flex-wrap gap-2" role="toolbar" aria-label="Secondary actions">
        <!-- Workflow Dashboards -->
        <div class="btn-group btn-group-sm" role="group" aria-label="Workflow dashboards">
            <?php if (in_array($userRole, $roleConfig['assets/legacy-verify'] ?? [])): ?>
                <a href="?route=assets/verification-dashboard" class="btn btn-outline-warning">
                    <i class="bi bi-check-circle me-1"></i>
                    <span class="d-none d-lg-inline">Verification</span>
                    <span class="d-lg-none">Verify</span>
                </a>
            <?php endif; ?>
            <?php if (in_array($userRole, $roleConfig['assets/legacy-authorize'] ?? [])): ?>
                <a href="?route=assets/authorization-dashboard" class="btn btn-outline-info">
                    <i class="bi bi-shield-check me-1"></i>
                    <span class="d-none d-lg-inline">Authorization</span>
                    <span class="d-lg-none">Auth</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Tools -->
        <div class="btn-group btn-group-sm" role="group" aria-label="Tools">
            <?php if (in_array($userRole, $roleConfig['assets/scanner'] ?? [])): ?>
                <a href="?route=assets/scanner" class="btn btn-outline-secondary">
                    <i class="bi bi-qr-code-scan"></i>
                    <span class="d-none d-md-inline ms-1">Scanner</span>
                </a>
            <?php endif; ?>
            <?php if (in_array($userRole, ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Asset Director'])): ?>
                <a href="?route=assets/tag-management" class="btn btn-outline-secondary">
                    <i class="bi bi-tags"></i>
                    <span class="d-none d-md-inline ms-1">Tags</span>
                </a>
            <?php endif; ?>
            <button type="button" class="btn btn-outline-secondary" onclick="refreshAssets()" title="Refresh">
                <i class="bi bi-arrow-clockwise"></i>
                <span class="d-none d-md-inline ms-1">Refresh</span>
            </button>
        </div>
    </div>
</div>
