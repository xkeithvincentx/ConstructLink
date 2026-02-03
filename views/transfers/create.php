<?php
/**
 * Transfer Create View
 * Create new asset transfer request
 */

// Load transfer-specific helpers
require_once APP_ROOT . '/core/TransferHelper.php';
require_once APP_ROOT . '/core/ReturnStatusHelper.php';
require_once APP_ROOT . '/core/InputValidator.php';
require_once APP_ROOT . '/helpers/BrandingHelper.php';

// Load module CSS
$moduleCSS = ['/assets/css/modules/transfers.css'];

// Start output buffering
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$userCurrentProjectId = $user['current_project_id'] ?? null;

// Debug logging
error_log("Transfer Create - User Role: {$userRole}, Current Project ID: " . ($userCurrentProjectId ?? 'null'));
?>

<!-- Keep Select2 for project dropdowns only -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-end align-items-center mb-4">
    <a href="?route=transfers"
       class="btn btn-outline-secondary btn-sm"
       aria-label="Return to transfers list">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
        <span class="d-none d-sm-inline">Back to Transfers</span>
    </a>
</div>

<!-- Transfer Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2" aria-hidden="true"></i>Transfer Information
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <h6><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (hasPermission('transfers/create')): ?>
                <form method="POST" action="?route=transfers/create" class="needs-validation" novalidate x-data="transferForm()">
                    <?= CSRFProtection::getTokenField() ?>

                    <!-- Asset Selection Partial -->
                    <?php include __DIR__ . '/_asset_selection.php'; ?>

                    <!-- Transfer Form Fields Partial -->
                    <?php include __DIR__ . '/_transfer_form.php'; ?>
                </form>
                <?php else: ?>
                    <div class="alert alert-warning" role="alert">
                        <h6><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Permission Denied</h6>
                        You do not have permission to create new transfer requests.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar with Guidelines -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Transfer Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Required Information</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check-circle text-success me-1" aria-hidden="true"></i> Asset to transfer</li>
                        <li><i class="bi bi-check-circle text-success me-1" aria-hidden="true"></i> Transfer type</li>
                        <li><i class="bi bi-check-circle text-success me-1" aria-hidden="true"></i> From and to projects</li>
                        <li><i class="bi bi-check-circle text-success me-1" aria-hidden="true"></i> Transfer date</li>
                        <li><i class="bi bi-check-circle text-success me-1" aria-hidden="true"></i> Reason for transfer</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6>Smart Transfer Workflow</h6>
                    <div class="alert alert-info p-2 mb-3" x-show="currentUserRole === 'Finance Director' || currentUserRole === 'Asset Director'" role="alert">
                        <small>
                            <i class="bi bi-lightning-fill me-1" aria-hidden="true"></i>
                            <strong>Streamlined Process:</strong> As a <?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?>, your transfers will be automatically processed through all approval steps!
                        </small>
                    </div>
                    <div class="alert alert-info p-2 mb-3" x-show="currentUserRole === 'Project Manager'" role="alert">
                        <small>
                            <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                            <strong>Auto-Fill:</strong> As a Project Manager, the destination project will automatically be set to your assigned project when you select an asset.
                        </small>
                    </div>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-person-plus text-primary me-1" aria-hidden="true"></i> <strong>Maker:</strong> Finance Director, Asset Director, Project Manager</li>
                        <li><i class="bi bi-person-check text-info me-1" aria-hidden="true"></i> <strong>Verifier:</strong> Auto-verified for Finance Director, Asset Director, Project Manager</li>
                        <li><i class="bi bi-person-check-fill text-success me-1" aria-hidden="true"></i> <strong>Authorizer:</strong> Finance Director, Asset Director (auto-approved for themselves)</li>
                        <li><i class="bi bi-box-arrow-in-down text-warning me-1" aria-hidden="true"></i> <strong>Receiver:</strong> Auto-received for Finance Director, Asset Director</li>
                        <li><i class="bi bi-check2-all text-success me-1" aria-hidden="true"></i> <strong>Completer:</strong> Final step to move asset to destination</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6>Approval Process</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-1-circle text-info me-1" aria-hidden="true"></i> Request submitted</li>
                        <li><i class="bi bi-2-circle text-info me-1" aria-hidden="true"></i> Management approval</li>
                        <li><i class="bi bi-3-circle text-info me-1" aria-hidden="true"></i> Transfer execution</li>
                        <li><i class="bi bi-4-circle text-info me-1" aria-hidden="true"></i> Asset location updated</li>
                    </ul>
                </div>

                <div class="alert alert-info" role="alert">
                    <small>
                        <i class="bi bi-lightbulb me-1" aria-hidden="true"></i>
                        <strong>Tip:</strong> High-value assets may require management approval before transfer.
                    </small>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2" aria-hidden="true"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=assets"
                       class="btn btn-outline-primary btn-sm"
                       aria-label="View all assets">
                        <i class="bi bi-box-seam me-1" aria-hidden="true"></i>View All Assets
                    </a>
                    <a href="?route=projects"
                       class="btn btn-outline-secondary btn-sm"
                       aria-label="View all projects">
                        <i class="bi bi-building me-1" aria-hidden="true"></i>View Projects
                    </a>
                    <a href="?route=transfers"
                       class="btn btn-outline-info btn-sm"
                       aria-label="View all transfers">
                        <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>View Transfers
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Include external JavaScript -->
<script src="<?= ASSETS_URL ?>/js/modules/transfers.js"></script>

<script>
function transferForm() {
    return {
        formData: {
            inventory_item_id: '<?= htmlspecialchars($formData['inventory_item_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>',
            from_project: '<?= htmlspecialchars($formData['from_project'] ?? '', ENT_QUOTES, 'UTF-8') ?>',
            to_project: '<?= htmlspecialchars($formData['to_project'] ?? '', ENT_QUOTES, 'UTF-8') ?>',
            transfer_type: '<?= htmlspecialchars($formData['transfer_type'] ?? '', ENT_QUOTES, 'UTF-8') ?>',
            transfer_date: '<?= htmlspecialchars($formData['transfer_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>',
            expected_return: '<?= htmlspecialchars($formData['expected_return'] ?? '', ENT_QUOTES, 'UTF-8') ?>',
            reason: '<?= htmlspecialchars($formData['reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>',
            notes: '<?= htmlspecialchars($formData['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>'
        },

        availableAssets: <?= json_encode($availableAssets ?? []) ?>,
        projects: <?= json_encode($projects ?? []) ?>,
        selectedAssetInfo: null,
        filteredAssets: [],
        searchText: '',
        showDropdown: false,
        highlightedIndex: -1,
        currentUserRole: '<?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?>',
        currentUserProjectId: <?= $userCurrentProjectId ? json_encode($userCurrentProjectId) : 'null' ?>,
        autoFilledToProject: false,

        // Computed property for filtered to projects
        get filteredToProjects() {
            if (!this.formData.from_project) {
                return this.projects;
            }
            return this.projects.filter(p => p.id != this.formData.from_project);
        },

        filterAssets() {
            const searchTerm = this.searchText.toLowerCase().trim();

            if (!searchTerm) {
                this.filteredAssets = this.availableAssets;
            } else {
                this.filteredAssets = this.availableAssets.filter(asset => {
                    const searchableText = `${asset.ref || ''} ${asset.name || ''} ${asset.category_name || ''} ${asset.project_name || ''}`.toLowerCase();
                    return searchableText.includes(searchTerm);
                });
            }

            this.highlightedIndex = -1;
        },

        selectAsset(asset) {
            console.log('=== selectAsset Debug ===');
            console.log('Current User Role:', this.currentUserRole);
            console.log('Current User Project ID:', this.currentUserProjectId);
            console.log('Selected Asset Project ID:', asset.project_id);

            this.formData.inventory_item_id = asset.id;
            this.selectedAssetInfo = asset;
            this.searchText = `${asset.ref} - ${asset.name}`;
            this.showDropdown = false;

            // Auto-populate from project
            if (asset.project_id) {
                this.formData.from_project = String(asset.project_id);

                // Update to_project dropdown to exclude from_project
                this.updateToProjectDropdown();

                // Auto-fill to_project for Project Managers
                if (this.currentUserRole === 'Project Manager' && this.currentUserProjectId) {
                    console.log('PM Auto-fill Logic: Checking if should auto-fill');
                    // Only auto-fill if user's project is different from from_project
                    if (this.currentUserProjectId != asset.project_id) {
                        console.log('Auto-filling TO project with:', this.currentUserProjectId);
                        this.formData.to_project = String(this.currentUserProjectId);
                        if (typeof $ !== 'undefined' && $('#to_project').length) {
                            $('#to_project').val(this.currentUserProjectId).trigger('change');
                        }
                        this.autoFilledToProject = true;
                    } else {
                        console.log('Cannot auto-fill: User project same as FROM project');
                        this.formData.to_project = '';
                        if (typeof $ !== 'undefined' && $('#to_project').length) {
                            $('#to_project').val('').trigger('change');
                        }
                        this.autoFilledToProject = false;
                    }
                } else {
                    console.log('Not PM or no current project - skipping auto-fill');
                    if (this.formData.to_project == asset.project_id) {
                        this.formData.to_project = '';
                        if (typeof $ !== 'undefined' && $('#to_project').length) {
                            $('#to_project').val('').trigger('change');
                        }
                    }
                    this.autoFilledToProject = false;
                }
            }
        },

        clearSelection() {
            this.formData.inventory_item_id = '';
            this.selectedAssetInfo = null;
            this.searchText = '';
            this.formData.from_project = '';
            this.formData.to_project = '';
            this.showDropdown = false;
            this.autoFilledToProject = false;
            this.filterAssets();

            // Reset to_project dropdown
            if (typeof $ !== 'undefined' && $('#to_project').length) {
                $('#to_project').val('').trigger('change');
            }
        },

        navigateDown() {
            if (this.filteredAssets.length > 0) {
                this.highlightedIndex = (this.highlightedIndex + 1) % this.filteredAssets.length;
                this.showDropdown = true;
            }
        },

        navigateUp() {
            if (this.filteredAssets.length > 0) {
                this.highlightedIndex = this.highlightedIndex <= 0 ? this.filteredAssets.length - 1 : this.highlightedIndex - 1;
                this.showDropdown = true;
            }
        },

        selectHighlighted() {
            if (this.highlightedIndex >= 0 && this.highlightedIndex < this.filteredAssets.length) {
                this.selectAsset(this.filteredAssets[this.highlightedIndex]);
            }
        },

        init() {
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            const transferDateInput = document.getElementById('transfer_date');
            transferDateInput.min = today;

            // Initialize filtered assets
            this.filterAssets();

            // Initialize Select2
            if (typeof $ !== 'undefined') {
                this.initializeSelect2();
            } else {
                const checkJQuery = setInterval(() => {
                    if (typeof $ !== 'undefined') {
                        clearInterval(checkJQuery);
                        this.initializeSelect2();
                    }
                }, 50);
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.position-relative')) {
                    this.showDropdown = false;
                }
            });

            // Watch from_project to update to_project dropdown
            this.$watch('formData.from_project', (newValue, oldValue) => {
                if (newValue && newValue !== oldValue) {
                    this.updateToProjectDropdown();
                }
            });

            // Watch transfer type for temporary return date
            this.$watch('formData.transfer_type', (value) => {
                if (value !== 'temporary') {
                    this.formData.expected_return = '';
                }
            });

            // Watch transfer date to update expected return minimum
            this.$watch('formData.transfer_date', (value) => {
                const expectedReturnInput = document.getElementById('expected_return');
                if (value) {
                    const nextDay = new Date(value);
                    nextDay.setDate(nextDay.getDate() + 1);
                    expectedReturnInput.min = nextDay.toISOString().split('T')[0];

                    if (this.formData.expected_return && this.formData.expected_return <= value) {
                        this.formData.expected_return = '';
                    }
                }
            });
        },

        initializeSelect2() {
            const $toProject = $('#to_project');
            $toProject.empty();
            $toProject.append('<option value="">Select To Project</option>');
            this.filteredToProjects.forEach(project => {
                const option = new Option(project.name, project.id, false, false);
                $toProject.append(option);
            });

            $toProject.select2({
                theme: 'bootstrap-5',
                placeholder: 'Search for destination project...',
                allowClear: true,
                width: '100%'
            });

            $toProject.on('change', (e) => {
                this.formData.to_project = e.target.value;
                this.validateProjects();
            });
        },

        updateToProjectDropdown() {
            if (typeof $ === 'undefined') return;

            const currentToProject = this.formData.to_project;

            if ($('#to_project').hasClass('select2-hidden-accessible')) {
                $('#to_project').select2('destroy');
            }

            const $toProject = $('#to_project');
            $toProject.empty();
            $toProject.append('<option value="">Select To Project</option>');

            this.filteredToProjects.forEach(project => {
                const option = new Option(project.name, project.id, false, false);
                $toProject.append(option);
            });

            $toProject.select2({
                theme: 'bootstrap-5',
                placeholder: 'Search for destination project...',
                allowClear: true,
                width: '100%'
            });

            if (currentToProject && this.filteredToProjects.find(p => p.id == currentToProject)) {
                $toProject.val(currentToProject).trigger('change');
            } else {
                this.formData.to_project = '';
                $toProject.val('').trigger('change');
            }

            $toProject.off('change').on('change', (e) => {
                this.formData.to_project = e.target.value;
                this.validateProjects();
            });
        },

        validateProjects() {
            if (this.formData.from_project && this.formData.to_project && this.formData.from_project === this.formData.to_project) {
                alert('Source and destination projects must be different');
                this.formData.to_project = '';
                if (typeof $ !== 'undefined' && $('#to_project').length) {
                    $('#to_project').val('').trigger('change');
                }
            }
        }
    }
}

// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php
$content = ob_get_clean();
$branding = BrandingHelper::loadBranding();
$pageTitle = $branding['app_name'] . ' - Create Transfer Request';
include APP_ROOT . '/views/layouts/main.php';
?>
