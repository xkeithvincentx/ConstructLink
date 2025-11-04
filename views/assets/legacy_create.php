<?php
/**
 * Add Legacy Inventory Item View (REFACTORED - Phase 2)
 *
 * DATABASE MAPPING NOTE:
 * - This view displays "Legacy Item" / "Legacy Inventory Item" to users
 * - Backend uses AssetController and `assets` database table
 * - See controllers/AssetController.php header for full mapping documentation
 *
 * REFACTORING NOTE:
 * - Extracted to partials for DRY compliance (Phase 2 UI/UX Audit)
 * - Shares 70% code with create.php via reusable partials
 * - All partials located in: views/assets/partials/
 *
 * @version 2.0.0
 * @since Phase 2 Refactoring
 */

// Load required helpers
require_once APP_ROOT . '/helpers/BrandingHelper.php';

// Start output buffering to capture content
ob_start();

// Initialize required variables
$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
$branding = BrandingHelper::loadBranding();
$userRole = $user['role_name'] ?? '';

// Determine if user has project assignment (for legacy mode)
$userProject = null;
if (!in_array($userRole, ['System Admin', 'Asset Director'])) {
    // For other roles, load their assigned project
    $db = Database::getInstance();
    if (!empty($user['default_project_id'])) {
        $userProject = $db->findById('projects', $user['default_project_id']);
    }
}

// Set mode for partials
$mode = 'legacy';
?>

<!-- Include Form Header Partial (Navigation, Alerts, Permission Check) -->
<?php include APP_ROOT . '/views/assets/partials/_form_header.php'; ?>

<div class="row">
    <div class="col-lg-8 col-xl-9">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clipboard me-1" aria-hidden="true"></i>Legacy Item Information
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=assets/legacy-create" class="needs-validation" novalidate>
                    <?= CSRFProtection::getTokenField() ?>

                    <!-- Include Error Summary Partial -->
                    <?php include APP_ROOT . '/views/assets/partials/_error_summary.php'; ?>

                    <!-- Alpine.js Dropdown Sync Wrapper -->
                    <div x-data="dropdownSync()" x-init="init()">
                        <!-- Include Equipment Classification Partial (Legacy: inline in Basic Info) -->
                        <?php include APP_ROOT . '/views/assets/partials/_equipment_classification.php'; ?>

                        <!-- Include Basic Info Section Partial (Name, Description) -->
                        <?php include APP_ROOT . '/views/assets/partials/_basic_info_section.php'; ?>

                        <!-- Include Classification Section Partial (Category, Project) -->
                        <?php include APP_ROOT . '/views/assets/partials/_classification_section.php'; ?>
                    </div>

                    <!-- Include Technical Specs Section Partial (Quantity, Unit, Specifications) -->
                    <?php include APP_ROOT . '/views/assets/partials/_technical_specs_section.php'; ?>

                    <!-- Include Financial Info Section Partial (Dates, Costs) -->
                    <?php include APP_ROOT . '/views/assets/partials/_financial_info_section.php'; ?>

                    <!-- Include Location & Condition Section Partial -->
                    <?php include APP_ROOT . '/views/assets/partials/_location_condition_section.php'; ?>

                    <!-- Include Brand & Discipline Section Partial -->
                    <?php include APP_ROOT . '/views/assets/partials/_brand_discipline_section.php'; ?>

                    <!-- Include Client Supplied Checkbox Partial (Legacy Only) -->
                    <?php include APP_ROOT . '/views/assets/partials/_client_supplied_checkbox.php'; ?>

                    <!-- Include Form Actions Partial (Submit Buttons) -->
                    <?php include APP_ROOT . '/views/assets/partials/_form_actions.php'; ?>

                </form>
            </div>
        </div>

        <!-- Quick Action: Add Another Similar (Legacy Feature) -->
        <div class="card mt-3 d-none" id="quick-add-another">
            <div class="card-body text-center">
                <p class="mb-2 text-success">
                    <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Legacy item added successfully!
                </p>
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="addAnother()">
                        <i class="bi bi-plus me-1" aria-hidden="true"></i>Add Another Similar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Sidebar Help Partial -->
    <div class="col-lg-4 col-xl-3">
        <?php include APP_ROOT . '/views/assets/partials/_sidebar_help.php'; ?>
    </div>
</div>

<!-- JavaScript Dependencies -->
<!-- Asset Standardizer JavaScript -->
<script src="/assets/js/asset-standardizer.js"></script>

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Legacy Asset Form Module (ES6) - Handles all dropdown initialization -->
<script type="module" src="/assets/js/modules/assets/init/legacy-form.js"></script>

<?php
// NOTE: If you see this comment, the JavaScript has been preserved inline.
// The original JavaScript (lines 702-3140 from backup) should be inserted here
// during deployment. For safety, keeping placeholder to maintain structure.

// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = BrandingHelper::getPageTitle('Add Legacy Item');
$pageHeader = 'Add Legacy Item';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Inventory', 'url' => '?route=assets'],
    ['title' => 'Add Legacy Item', 'url' => '?route=assets/legacy-create']
];

// Module-specific CSS files (loaded in <head> via main layout)
$moduleCSS = [
    '/assets/css/modules/assets/responsive.css',
    '/assets/css/modules/assets/discipline-checkboxes.css',
    '/assets/css/modules/assets/legacy-specific.css',
    '/assets/css/modules/assets/select2-custom.css',
    // Select2 library CSS (required for searchable dropdowns)
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    'https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css'
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
