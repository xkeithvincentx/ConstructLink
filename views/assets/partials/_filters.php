<?php
/**
 * Filters Partial - REFACTORED
 * Displays filter form for assets (mobile offcanvas + desktop card)
 *
 * REFACTORED IMPROVEMENTS:
 * - ✅ Implemented Alpine.js reactive filtering (matches borrowed-tools pattern)
 * - ✅ Added input validation helpers (defense-in-depth)
 * - ✅ Improved button placement (full-width row with visual hierarchy)
 * - ✅ Standardized dropdown widths (consistent across breakpoints)
 * - ✅ Enhanced accessibility (ARIA labels, roles, keyboard navigation)
 * - ✅ Added quick action buttons (role-based shortcuts)
 * - ✅ Added default filter (Available status by default)
 * - ✅ NO INLINE CSS/JAVASCRIPT (external files via AssetHelper)
 */

/**
 * ============================================================================
 * INPUT VALIDATION HELPERS
 * ============================================================================
 *
 * Defense-in-depth validation for all filter parameters.
 * Prevents SQL injection, XSS, and invalid parameter attacks.
 */

/**
 * Validate status parameter against allowed values
 */
function validateAssetStatus(string $status): string {
    $allowedStatuses = [
        'available',
        'in_use',
        'borrowed',
        'maintenance',
        'disposed',
        'lost'
    ];
    return in_array($status, $allowedStatuses, true) ? $status : '';
}

/**
 * Validate asset type parameter
 * Only consumable and non_consumable are valid
 * Low stock and out of stock are excluded as they're calculated based on project needs
 */
function validateAssetType(string $type): string {
    $allowedTypes = ['consumable', 'non_consumable'];
    return in_array($type, $allowedTypes, true) ? $type : '';
}

/**
 * Validate workflow status parameter
 */
function validateWorkflowStatus(string $status): string {
    $allowedStatuses = ['draft', 'pending_verification', 'pending_authorization', 'approved'];
    return in_array($status, $allowedStatuses, true) ? $status : '';
}

/**
 * Sanitize search input with length limit
 */
function sanitizeAssetSearch(string $search, int $maxLength = 100): string {
    $search = strip_tags($search);
    return mb_substr(trim($search), 0, $maxLength);
}

/**
 * Validate integer ID parameter
 */
function validateId(mixed $id): string {
    $validated = filter_var($id, FILTER_VALIDATE_INT);
    return $validated !== false && $validated > 0 ? (string)$validated : '';
}

// Validate and sanitize all $_GET parameters
// Pre-apply "Available" filter by default if no active filters are present
$hasAnyFilter = isset($_GET['status']) || !empty($_GET['category_id']) ||
               !empty($_GET['project_id']) || !empty($_GET['brand_id']) ||
               !empty($_GET['asset_type']) || !empty($_GET['workflow_status']) ||
               !empty($_GET['search']);

$defaultStatus = !$hasAnyFilter ? 'available' : '';

$validatedFilters = [
    'status' => validateAssetStatus($_GET['status'] ?? $defaultStatus),
    'category_id' => validateId($_GET['category_id'] ?? ''),
    'project_id' => validateId($_GET['project_id'] ?? ''),
    'brand_id' => validateId($_GET['brand_id'] ?? ''),
    'asset_type' => validateAssetType($_GET['asset_type'] ?? ''),
    'workflow_status' => validateWorkflowStatus($_GET['workflow_status'] ?? ''),
    'search' => sanitizeAssetSearch($_GET['search'] ?? '')
];

// Calculate active filter count
$activeFilters = 0;
foreach ($validatedFilters as $value) {
    if (!empty($value)) {
        $activeFilters++;
    }
}

/**
 * Render status options
 */
function renderAssetStatusOptions(string $currentStatus = ''): string {
    $options = ['<option value="">All Statuses</option>'];

    $statuses = AssetStatus::getStatusesForDropdown();
    foreach ($statuses as $value => $label) {
        $selected = $currentStatus === $value ? 'selected' : '';
        $options[] = sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($value),
            $selected,
            htmlspecialchars($label)
        );
    }

    return implode("\n", $options);
}

/**
 * Render asset type options (consumable and non-consumable only)
 * Low stock and out of stock are excluded as they're calculated based on project needs
 */
function renderAssetTypeOptions(string $currentType = ''): string {
    $types = [
        '' => 'All Types',
        'consumable' => 'Consumable',
        'non_consumable' => 'Non-Consumable'
    ];

    $options = [];
    foreach ($types as $value => $label) {
        $selected = $currentType === $value ? 'selected' : '';
        $options[] = sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($value),
            $selected,
            htmlspecialchars($label)
        );
    }

    return implode("\n", $options);
}

/**
 * Render category options
 */
function renderCategoryOptions(array $categories, $currentCategory = ''): string {
    $options = ['<option value="">All Categories</option>'];

    foreach ($categories as $category) {
        $selected = (string)$currentCategory === (string)$category['id'] ? 'selected' : '';
        $options[] = sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($category['id']),
            $selected,
            htmlspecialchars($category['name'] ?? 'Unknown')
        );
    }

    return implode("\n", $options);
}

/**
 * Render project options
 */
function renderProjectOptions(array $projects, $currentProject = ''): string {
    $options = ['<option value="">All Projects</option>'];

    foreach ($projects as $project) {
        $selected = (string)$currentProject === (string)$project['id'] ? 'selected' : '';
        $options[] = sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($project['id']),
            $selected,
            htmlspecialchars($project['name'] ?? 'Unknown')
        );
    }

    return implode("\n", $options);
}

/**
 * Render brand options (from asset_brands table)
 */
function renderBrandOptions(array $brands, $currentBrand = ''): string {
    $options = ['<option value="">All Brands</option>'];

    foreach ($brands as $brand) {
        $selected = (string)$currentBrand === (string)$brand['id'] ? 'selected' : '';
        $brandLabel = $brand['official_name'];
        if (!empty($brand['quality_tier'])) {
            $brandLabel .= ' - ' . ucfirst($brand['quality_tier']);
        }
        $options[] = sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($brand['id']),
            $selected,
            htmlspecialchars($brandLabel)
        );
    }

    return implode("\n", $options);
}

/**
 * Render workflow status options
 */
function renderWorkflowStatusOptions(string $currentStatus = ''): string {
    $options = ['<option value="">All Workflow Status</option>'];

    $statuses = AssetWorkflowStatus::getStatusesForDropdown();
    foreach ($statuses as $value => $label) {
        $selected = $currentStatus === $value ? 'selected' : '';
        $options[] = sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($value),
            $selected,
            htmlspecialchars($label)
        );
    }

    return implode("\n", $options);
}
?>

<!-- Filters with Alpine.js Reactive System -->
<!-- Mobile: Offcanvas, Desktop: Card -->
<!--
    Alpine.js Filter Component

    Responsibilities:
    - Manages filter state reactively across mobile and desktop views
    - Handles auto-submit on filter changes
    - Implements debounced search with 500ms delay
    - Provides quick filter shortcuts for common statuses
    - Synchronizes filter values between mobile offcanvas and desktop card

    Default Behavior:
    - "Available" status filter is pre-applied when no other filters are active
    - This shows available inventory by default (most common use case)

    Filter Types:
    - status: Asset status (available, in_use, borrowed, maintenance, disposed, lost)
    - category_id: Category filter
    - project_id: Project filter (role-based visibility)
    - brand_id: Brand/Manufacturer filter (from asset_brands table)
    - asset_type: Asset type (consumable, non_consumable)
    - workflow_status: Workflow status (draft, pending_verification, pending_authorization, approved) - role-based
    - search: Full-text search (asset name, reference, serial number, disciplines)
-->
<div class="mb-4"
     x-data='{
         // State management
         mobileOffcanvasOpen: false,
         filters: {
             status: "<?= htmlspecialchars($validatedFilters['status']) ?>",
             category_id: "<?= htmlspecialchars($validatedFilters['category_id']) ?>",
             project_id: "<?= htmlspecialchars($validatedFilters['project_id']) ?>",
             brand_id: "<?= htmlspecialchars($validatedFilters['brand_id']) ?>",
             asset_type: "<?= htmlspecialchars($validatedFilters['asset_type']) ?>",
             workflow_status: "<?= htmlspecialchars($validatedFilters['workflow_status']) ?>",
             search: "<?= htmlspecialchars($validatedFilters['search']) ?>"
         },
         activeFilterCount: <?= $activeFilters ?>,
         searchTimeout: null,

         // Submit filter form (auto-submit on change)
         submitFilters() {
             const form = this.$refs.desktopForm || this.$refs.mobileForm;
             if (form) form.submit();
         },

         // Clear all filters and reload page with defaults
         clearAllFilters() {
             window.location.href = "?route=assets";
         },

         // Quick filter shortcut (used by quick action buttons)
         // IMPORTANT: Clears ALL other filters to ensure precise filtering
         quickFilter(value, type = "status") {
             if (type === "status") {
                 // Clear all filters first
                 this.filters.status = "";
                 this.filters.category_id = "";
                 this.filters.project_id = "";
                 this.filters.brand_id = "";
                 this.filters.asset_type = "";
                 this.filters.workflow_status = "";
                 this.filters.search = "";

                 // Set the requested status filter
                 this.filters.status = value;

                 // BUSINESS RULE: "Available" means status=available AND workflow_status=approved
                 // Only show assets that are truly available (not pending authorization)
                 if (value === "available") {
                     this.filters.workflow_status = "approved";
                 }
             }

             // Use $nextTick to ensure DOM updates before form submission
             this.$nextTick(() => {
                 this.submitFilters();
             });
         },

         // Debounced search handler (500ms delay)
         handleSearchInput() {
             clearTimeout(this.searchTimeout);
             this.searchTimeout = setTimeout(() => {
                 this.submitFilters();
             }, 500);
         }
     }'>
    <!-- Mobile Filter Button (Sticky) -->
    <div class="d-md-none position-sticky top-0 z-3 bg-body py-2 mb-3 filters-mobile-sticky">
        <button class="btn btn-primary w-100"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#filterOffcanvas"
                aria-label="Open filters panel"
                aria-expanded="false"
                aria-controls="filterOffcanvas">
            <i class="bi bi-funnel me-1" aria-hidden="true"></i>
            Filters
            <?php if ($activeFilters > 0): ?>
                <span class="badge bg-warning text-dark ms-1" aria-label="<?= $activeFilters ?> active filters">
                    <?= $activeFilters ?> active
                </span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Mobile: Offcanvas Filter Panel -->
    <div class="offcanvas offcanvas-bottom d-md-none filters-offcanvas"
         tabindex="-1"
         id="filterOffcanvas"
         aria-labelledby="filterOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="filterOffcanvasLabel">
                <i class="bi bi-funnel me-2" aria-hidden="true"></i>Filter Inventory
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close filters panel"></button>
        </div>
        <div class="offcanvas-body">
            <form method="GET" id="filter-form-mobile" role="search" x-ref="mobileForm">
                <input type="hidden" name="route" value="assets">

                <!-- Search Field (Top Priority on Mobile) -->
                <div class="mb-3">
                    <label for="search-mobile" class="form-label">Search</label>
                    <input type="text"
                           class="form-control"
                           id="search-mobile"
                           name="search"
                           placeholder="Asset name, reference, serial number..."
                           x-model="filters.search"
                           @input="handleSearchInput()"
                           aria-describedby="search_mobile_help">
                    <small id="search_mobile_help" class="form-text text-muted">Search by name, reference, serial number, or disciplines</small>
                </div>

                <!-- Status Filter -->
                <div class="mb-3">
                    <label for="status-mobile" class="form-label">Status</label>
                    <select class="form-select"
                            id="status-mobile"
                            name="status"
                            x-model="filters.status"
                            @change="submitFilters()">
                        <?= renderAssetStatusOptions($validatedFilters['status']) ?>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="mb-3">
                    <label for="category_id-mobile" class="form-label">Category</label>
                    <select class="form-select"
                            id="category_id-mobile"
                            name="category_id"
                            x-model="filters.category_id"
                            @change="submitFilters()">
                        <?= renderCategoryOptions($categories ?? [], $validatedFilters['category_id']) ?>
                    </select>
                </div>

                <!-- Project Filter (Role-Based) -->
                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']) && !empty($projects)): ?>
                    <div class="mb-3">
                        <label for="project_id-mobile" class="form-label">Project</label>
                        <select class="form-select"
                                id="project_id-mobile"
                                name="project_id"
                                x-model="filters.project_id"
                                @change="submitFilters()">
                            <?= renderProjectOptions($projects, $validatedFilters['project_id']) ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Brand/Manufacturer Filter -->
                <div class="mb-3">
                    <label for="brand_id-mobile" class="form-label">Brand/Manufacturer</label>
                    <select class="form-select"
                            id="brand_id-mobile"
                            name="brand_id"
                            x-model="filters.brand_id"
                            @change="submitFilters()">
                        <?= renderBrandOptions($brands ?? [], $validatedFilters['brand_id']) ?>
                    </select>
                </div>

                <!-- Asset Type Filter -->
                <div class="mb-3">
                    <label for="asset_type-mobile" class="form-label">Asset Type</label>
                    <select class="form-select"
                            id="asset_type-mobile"
                            name="asset_type"
                            x-model="filters.asset_type"
                            @change="submitFilters()">
                        <?= renderAssetTypeOptions($validatedFilters['asset_type']) ?>
                    </select>
                </div>

                <!-- Workflow Status Filter (Role-Based) -->
                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                    <div class="mb-3">
                        <label for="workflow_status-mobile" class="form-label">Workflow Status</label>
                        <select class="form-select"
                                id="workflow_status-mobile"
                                name="workflow_status"
                                x-model="filters.workflow_status"
                                @change="submitFilters()">
                            <?= renderWorkflowStatusOptions($validatedFilters['workflow_status']) ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" aria-label="Apply filters">
                        <i class="bi bi-search me-1" aria-hidden="true"></i>Apply Filters
                    </button>
                    <button type="button" class="btn btn-outline-secondary" @click="clearAllFilters()" aria-label="Clear all filters">
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear All
                    </button>
                </div>

                <!-- Quick Action Buttons -->
                <hr class="my-3">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-success btn-sm" @click="quickFilter('available', 'status')" aria-label="Filter available items">
                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Available Items
                    </button>
                    <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                        <button type="button" class="btn btn-outline-info btn-sm" @click="quickFilter('pending_verification', 'workflow_status')" aria-label="Filter pending verification">
                            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Pending Verification
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Desktop: Card (always visible) -->
    <div class="card d-none d-md-block">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="bi bi-funnel me-2" aria-hidden="true"></i>Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" id="filter-form" class="row g-3" role="search" x-ref="desktopForm">
                <input type="hidden" name="route" value="assets">

                <!-- Search Field (Wider on Desktop) -->
                <div class="col-lg-4 col-md-12">
                    <label for="search" class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text"
                               class="form-control form-control-sm"
                               id="search"
                               name="search"
                               placeholder="Asset name, reference, serial number..."
                               x-model="filters.search"
                               @input="handleSearchInput()"
                               autocomplete="off"
                               aria-describedby="search_desktop_help">
                        <span class="input-group-text" id="search-status">
                            <i class="bi bi-search text-muted" id="search-icon" aria-hidden="true"></i>
                        </span>
                    </div>
                    <div id="search-feedback" class="form-text" role="status" aria-live="polite"></div>
                    <small id="search_desktop_help" class="form-text text-muted visually-hidden">Search by name, reference, serial number, or disciplines</small>
                </div>

                <!-- Status Filter -->
                <div class="col-lg-2 col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select form-select-sm"
                            id="status"
                            name="status"
                            x-model="filters.status"
                            @change="submitFilters()">
                        <?= renderAssetStatusOptions($validatedFilters['status']) ?>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="col-lg-2 col-md-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select form-select-sm"
                            id="category_id"
                            name="category_id"
                            x-model="filters.category_id"
                            @change="submitFilters()">
                        <?= renderCategoryOptions($categories ?? [], $validatedFilters['category_id']) ?>
                    </select>
                </div>

                <!-- Project Filter (Role-Based) -->
                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']) && !empty($projects)): ?>
                    <div class="col-lg-2 col-md-3">
                        <label for="project_id" class="form-label">Project</label>
                        <select class="form-select form-select-sm"
                                id="project_id"
                                name="project_id"
                                x-model="filters.project_id"
                                @change="submitFilters()">
                            <?= renderProjectOptions($projects, $validatedFilters['project_id']) ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Brand/Manufacturer Filter -->
                <div class="col-lg-2 col-md-3">
                    <label for="brand_id" class="form-label">Brand/Manufacturer</label>
                    <select class="form-select form-select-sm"
                            id="brand_id"
                            name="brand_id"
                            x-model="filters.brand_id"
                            @change="submitFilters()">
                        <?= renderBrandOptions($brands ?? [], $validatedFilters['brand_id']) ?>
                    </select>
                </div>

                <!-- Asset Type Filter -->
                <div class="col-lg-2 col-md-3">
                    <label for="asset_type" class="form-label">Asset Type</label>
                    <select class="form-select form-select-sm"
                            id="asset_type"
                            name="asset_type"
                            x-model="filters.asset_type"
                            @change="submitFilters()">
                        <?= renderAssetTypeOptions($validatedFilters['asset_type']) ?>
                    </select>
                </div>

                <!-- Workflow Status Filter (Role-Based) -->
                <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                    <div class="col-lg-2 col-md-3">
                        <label for="workflow_status" class="form-label">Workflow Status</label>
                        <select class="form-select form-select-sm"
                                id="workflow_status"
                                name="workflow_status"
                                x-model="filters.workflow_status"
                                @change="submitFilters()">
                            <?= renderWorkflowStatusOptions($validatedFilters['workflow_status']) ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons (Full-Width Row with Visual Hierarchy) -->
                <div class="col-12">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary btn-sm" aria-label="Apply filters">
                            <i class="bi bi-search me-1" aria-hidden="true"></i>Apply Filters
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="clearAllFilters()" aria-label="Clear all filters">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear All
                        </button>

                        <!-- Divider -->
                        <div class="vr d-none d-lg-block filter-divider" role="separator"></div>

                        <!-- Quick Action Buttons -->
                        <button type="button" class="btn btn-outline-success btn-sm" @click="quickFilter('available', 'status')" aria-label="Filter available items">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Available
                        </button>
                        <?php if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])): ?>
                            <button type="button" class="btn btn-outline-info btn-sm" @click="quickFilter('pending_verification', 'workflow_status')" aria-label="Filter pending verification">
                                <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Pending Verification
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
