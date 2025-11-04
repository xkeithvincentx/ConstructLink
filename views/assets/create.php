<?php
/**
 * Create Inventory Item View (REFACTORED - Phase 2)
 *
 * DATABASE MAPPING NOTE:
 * - This view displays "Inventory Item" / "Item" to users
 * - Backend uses AssetController and `assets` database table
 * - See controllers/AssetController.php header for full mapping documentation
 *
 * REFACTORING NOTE:
 * - Extracted to partials for DRY compliance (Phase 2 UI/UX Audit)
 * - Shares 70% code with legacy_create.php via reusable partials
 * - All partials located in: views/assets/partials/
 *
 * @version 2.0.0
 * @since Phase 2 Refactoring
 */

// Start output buffering to capture content
ob_start();

// Initialize required variables
$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';

// Set mode for partials
$mode = 'standard';
?>

<!-- Include Form Header Partial (Navigation, Alerts, Permission Check) -->
<?php include APP_ROOT . '/views/assets/partials/_form_header.php'; ?>

<div class="row">
    <div class="col-lg-8 col-xl-9">
        <!-- Item Creation Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-form-check me-2" aria-hidden="true"></i>Item Information
                </h6>
            </div>
            <div class="card-body">
                <!-- Quick Entry Section (will be added dynamically) -->
                <div id="quick-entry-container"></div>

                <form method="POST" action="?route=assets/create" id="assetForm">
                    <?= CSRFProtection::getTokenField() ?>

                    <!-- Include Error Summary Partial -->
                    <?php include APP_ROOT . '/views/assets/partials/_error_summary.php'; ?>

                    <!-- Include Basic Info Section Partial (Reference, Name, Description) -->
                    <?php include APP_ROOT . '/views/assets/partials/_basic_info_section.php'; ?>

                    <!-- Alpine.js Dropdown Sync Wrapper -->
                    <div x-data="dropdownSync()" x-init="init()">
                        <!-- Include Classification Section Partial (Category, Project) -->
                        <?php include APP_ROOT . '/views/assets/partials/_classification_section.php'; ?>

                        <!-- Include Equipment Classification Partial (Standard: separate collapsible section) -->
                        <?php include APP_ROOT . '/views/assets/partials/_equipment_classification.php'; ?>
                    </div>

                    <!-- Include Brand & Discipline Section Partial -->
                    <?php include APP_ROOT . '/views/assets/partials/_brand_discipline_section.php'; ?>

                    <!-- Include Procurement Section Partial (Standard Only) -->
                    <?php include APP_ROOT . '/views/assets/partials/_procurement_section.php'; ?>

                    <!-- Include Technical Specs Section Partial (Quantity, Unit, Specifications) -->
                    <?php include APP_ROOT . '/views/assets/partials/_technical_specs_section.php'; ?>

                    <!-- Include Financial Info Section Partial (Dates, Costs) -->
                    <?php include APP_ROOT . '/views/assets/partials/_financial_info_section.php'; ?>

                    <!-- Include Location & Condition Section Partial -->
                    <?php include APP_ROOT . '/views/assets/partials/_location_condition_section.php'; ?>

                    <!-- Include Form Actions Partial (Submit Buttons) -->
                    <?php include APP_ROOT . '/views/assets/partials/_form_actions.php'; ?>

                </form>
            </div>
        </div>
    </div>

    <!-- Include Sidebar Help Partial -->
    <div class="col-lg-4 col-xl-3">
        <?php
        // Load branding for sidebar
        require_once APP_ROOT . '/helpers/BrandingHelper.php';
        $branding = BrandingHelper::loadBranding();
        include APP_ROOT . '/views/assets/partials/_sidebar_help.php';
        ?>
    </div>
</div>

<!-- Inline JavaScript removed - now loaded as ES6 module below -->

<!-- Asset Standardizer JavaScript -->
<script src="/assets/js/asset-standardizer.js"></script>

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 CSS for searchable dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Asset Create Form Module (ES6) - Handles all dropdown initialization -->
<script type="module" src="/assets/js/modules/assets/init/create-form.js"></script>

<!-- Phase 3 Refactoring Complete: All inline JavaScript and CSS extracted to modules -->

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Create Asset';
$pageHeader = 'Create Asset';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Inventory', 'url' => '?route=assets'],
    ['title' => 'Create Asset', 'url' => '?route=assets/create']
];

// Module-specific CSS files (loaded in <head> via main layout)
$moduleCSS = [
    '/assets/css/modules/assets/responsive.css',
    '/assets/css/modules/assets/discipline-checkboxes.css',
    '/assets/css/modules/assets/select2-custom.css'
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
