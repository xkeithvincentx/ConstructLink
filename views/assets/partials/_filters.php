<?php
/**
 * Filters Partial
 * Displays filter form for assets (mobile offcanvas + desktop card)
 */
?>

<!-- Filters -->
<!-- Mobile: Offcanvas, Desktop: Card -->
<div class="mb-4">
    <!-- Mobile Filter Button (Sticky) -->
    <div class="d-md-none position-sticky top-0 z-3 bg-body py-2 mb-3" style="z-index: 1020;">
        <button class="btn btn-primary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
            <i class="bi bi-funnel me-1"></i>
            Filters
            <?php
            $activeFilters = 0;
            if (!empty($_GET['status'])) $activeFilters++;
            if (!empty($_GET['category_id'])) $activeFilters++;
            if (!empty($_GET['project_id'])) $activeFilters++;
            if (!empty($_GET['maker_id'])) $activeFilters++;
            if (!empty($_GET['asset_type'])) $activeFilters++;
            if (!empty($_GET['workflow_status'])) $activeFilters++;
            if (!empty($_GET['search'])) $activeFilters++;
            if ($activeFilters > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $activeFilters ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Desktop: Card (always visible) -->
    <div class="card d-none d-md-block">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="bi bi-funnel me-2"></i>Filters
            </h6>
        </div>
        <div class="card-body p-3">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="route" value="assets">
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <label for="status" class="form-label">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (AssetStatus::getStatusesForDropdown() as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($_GET['status'] ?? '') === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select form-select-sm" id="category_id" name="category_id">
                    <option value="">All Categories</option>
                    <?php if (isset($categories) && is_array($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                    <?= ($_GET['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name'] ?? 'Unknown') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <label for="project_id" class="form-label">Project</label>
                <select class="form-select form-select-sm" id="project_id" name="project_id">
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
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <label for="maker_id" class="form-label">Manufacturer</label>
                <select class="form-select form-select-sm" id="maker_id" name="maker_id">
                    <option value="">All Manufacturers</option>
                    <?php if (isset($makers) && is_array($makers)): ?>
                        <?php foreach ($makers as $maker): ?>
                            <option value="<?= $maker['id'] ?>" 
                                    <?= ($_GET['maker_id'] ?? '') == $maker['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($maker['name'] ?? 'Unknown') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <label for="asset_type" class="form-label">Asset Type</label>
                <select class="form-select form-select-sm" id="asset_type" name="asset_type">
                    <option value="">All Types</option>
                    <option value="consumable" <?= ($_GET['asset_type'] ?? '') === 'consumable' ? 'selected' : '' ?>>Consumable</option>
                    <option value="non_consumable" <?= ($_GET['asset_type'] ?? '') === 'non_consumable' ? 'selected' : '' ?>>Non-Consumable</option>
                    <option value="low_stock" <?= ($_GET['asset_type'] ?? '') === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                    <option value="out_of_stock" <?= ($_GET['asset_type'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>
            <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                <label for="workflow_status" class="form-label">Workflow Status</label>
                <select class="form-select form-select-sm" id="workflow_status" name="workflow_status">
                    <option value="">All Workflow Status</option>
                    <?php foreach (AssetWorkflowStatus::getStatusesForDropdown() as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($_GET['workflow_status'] ?? '') === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-xl-3 col-lg-6 col-md-8 col-12">
                <label for="search" class="form-label">Enhanced Search</label>
                <div class="input-group">
                    <input type="text" class="form-control form-control-sm" id="search" name="search" 
                           placeholder="Search by asset name, reference, serial number, or disciplines..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           data-enhanced-search="true"
                           autocomplete="off"
                           list="search-suggestions">
                    <span class="input-group-text" id="search-status">
                        <i class="bi bi-search text-muted" id="search-icon"></i>
                    </span>
                </div>
                <datalist id="search-suggestions"></datalist>
                <div id="search-feedback" class="form-text"></div>
            </div>
            <div class="col-xl-1 col-lg-3 col-md-4 col-12 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="bi bi-search me-1 d-none d-sm-inline"></i>
                    <span class="d-none d-sm-inline">Filter</span>
                    <i class="bi bi-search d-sm-none"></i>
                </button>
                <a href="?route=assets" class="btn btn-outline-secondary btn-sm flex-fill">
                    <i class="bi bi-x-circle me-1 d-none d-sm-inline"></i>
                    <span class="d-none d-sm-inline">Clear</span>
                    <i class="bi bi-x-circle d-sm-none"></i>
                </a>
            </div>
        </form>
        </div><!-- End card-body -->
    </div><!-- End card (desktop) -->

    <!-- Mobile: Offcanvas Filters -->
    <div class="offcanvas offcanvas-bottom d-md-none" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel" style="height: 85vh;">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="filterOffcanvasLabel">
                <i class="bi bi-funnel me-2"></i>Filter Assets
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form method="GET" action="" id="mobileFilterForm">
                <input type="hidden" name="route" value="assets">
                <div class="mb-3">
                    <label for="mobile_status" class="form-label">Status</label>
                    <select class="form-select" id="mobile_status" name="status">
                        <option value="">All Statuses</option>
                        <?php foreach (AssetStatus::getStatusesForDropdown() as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($_GET['status'] ?? '') === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="mobile_category_id" class="form-label">Category</label>
                    <select class="form-select" id="mobile_category_id" name="category_id">
                        <option value="">All Categories</option>
                        <?php if (isset($categories) && is_array($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"
                                        <?= ($_GET['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name'] ?? 'Unknown') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                <div class="mb-3">
                    <label for="mobile_project_id" class="form-label">Project</label>
                    <select class="form-select" id="mobile_project_id" name="project_id">
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
                <div class="mb-3">
                    <label for="mobile_maker_id" class="form-label">Manufacturer</label>
                    <select class="form-select" id="mobile_maker_id" name="maker_id">
                        <option value="">All Manufacturers</option>
                        <?php if (isset($makers) && is_array($makers)): ?>
                            <?php foreach ($makers as $maker): ?>
                                <option value="<?= $maker['id'] ?>"
                                        <?= ($_GET['maker_id'] ?? '') == $maker['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($maker['name'] ?? 'Unknown') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="mobile_asset_type" class="form-label">Asset Type</label>
                    <select class="form-select" id="mobile_asset_type" name="asset_type">
                        <option value="">All Types</option>
                        <option value="consumable" <?= ($_GET['asset_type'] ?? '') === 'consumable' ? 'selected' : '' ?>>Consumable</option>
                        <option value="non_consumable" <?= ($_GET['asset_type'] ?? '') === 'non_consumable' ? 'selected' : '' ?>>Non-Consumable</option>
                        <option value="low_stock" <?= ($_GET['asset_type'] ?? '') === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                        <option value="out_of_stock" <?= ($_GET['asset_type'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                    </select>
                </div>
                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                <div class="mb-3">
                    <label for="mobile_workflow_status" class="form-label">Workflow Status</label>
                    <select class="form-select" id="mobile_workflow_status" name="workflow_status">
                        <option value="">All Workflow Status</option>
                        <?php foreach (AssetWorkflowStatus::getStatusesForDropdown() as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($_GET['workflow_status'] ?? '') === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="mobile_search" class="form-label">Enhanced Search</label>
                    <input type="text" class="form-control" id="mobile_search" name="search"
                           placeholder="Search by asset name, reference, serial number, or disciplines..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-search me-1"></i>Apply Filters
                    </button>
                    <a href="?route=assets" class="btn btn-outline-secondary flex-grow-1">
                        <i class="bi bi-x-circle me-1"></i>Clear All
                    </a>
                </div>
            </form>
        </div>
    </div>
</div><!-- End filters section -->
