<?php
/**
 * ConstructLink™ Transfer Create View
 * Create new asset transfer
 */

// Start output buffering
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$userCurrentProjectId = $user['current_project_id'] ?? null;

// Debug logging
error_log("Transfer Create - User Role: {$userRole}, Current Project ID: " . ($userCurrentProjectId ?? 'null'));
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-end align-items-center mb-4">
    <a href="?route=transfers" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>
        <span class="d-none d-sm-inline">Back to Transfers</span>
    </a>
</div>

<!-- Transfer Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Transfer Information
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-exclamation-triangle me-1"></i>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (hasPermission('transfers/create')): ?>
                <form method="POST" action="?route=transfers/create" class="needs-validation" novalidate x-data="transferForm()">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Asset Selection with Enhanced Search -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label for="asset_id" class="form-label">Asset <span class="text-danger">*</span></label>
                            
                            <!-- Hidden input for actual asset_id -->
                            <input type="hidden" id="asset_id" name="asset_id" x-model="formData.asset_id" required>
                            
                            <!-- Validation div for asset selection -->
                            <div x-show="!formData.asset_id" class="text-danger small mt-1" style="display: none;">
                                Please select an asset to transfer.
                            </div>
                            
                            <!-- Searchable Dropdown -->
                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" 
                                           class="form-control" 
                                           placeholder="Search assets by reference, name, category, or location..."
                                           x-model="searchText"
                                           @input="filterAssets"
                                           @focus="showDropdown = true"
                                           @keydown.down.prevent="navigateDown"
                                           @keydown.up.prevent="navigateUp"
                                           @keydown.enter.prevent="selectHighlighted"
                                           @keydown.escape="showDropdown = false">
                                    <button class="btn btn-outline-secondary" type="button" @click="clearSelection">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </button>
                                </div>
                                
                                <!-- Dropdown Results -->
                                <div class="dropdown-menu w-100" 
                                     :class="{ 'show': showDropdown && (filteredAssets.length > 0 || searchText.length > 0) }"
                                     style="max-height: 300px; overflow-y: auto;">
                                    <template x-if="filteredAssets.length === 0 && searchText.length > 0">
                                        <div class="dropdown-item-text text-muted">No assets found matching your search</div>
                                    </template>
                                    <template x-for="(asset, index) in filteredAssets" :key="asset.id">
                                        <a href="#" 
                                           class="dropdown-item" 
                                           :class="{ 'active': index === highlightedIndex }"
                                           @click.prevent="selectAsset(asset)"
                                           @mouseenter="highlightedIndex = index">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong x-text="asset.ref"></strong> - <span x-text="asset.name"></span><br>
                                                    <small class="text-muted">
                                                        <span x-text="asset.category_name || 'Uncategorized'"></span> | 
                                                        Location: <span x-text="asset.project_name || 'Unknown'"></span>
                                                    </small>
                                                </div>
                                            </div>
                                        </a>
                                    </template>
                                </div>
                            </div>
                            
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>Type to search available assets for transfer
                            </div>
                            <div class="invalid-feedback">Please select an asset to transfer.</div>
                        </div>
                    </div>

                    <!-- Selected Asset Info -->
                    <div class="row mb-4" x-show="selectedAssetInfo" style="display: none;">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle me-2"></i>Selected Asset Information</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Reference:</strong><br>
                                        <span x-text="selectedAssetInfo?.ref"></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Name:</strong><br>
                                        <span x-text="selectedAssetInfo?.name"></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Category:</strong><br>
                                        <span x-text="selectedAssetInfo?.category_name || 'Uncategorized'"></span>
                                    </div>
                                    <div class="col-md-2">
                                        <strong>Current Location:</strong><br>
                                        <span x-text="selectedAssetInfo?.project_name || 'Unknown'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="from_project" class="form-label">From Project <span class="text-danger">*</span></label>
                            
                            <!-- Hidden input for form submission -->
                            <input type="hidden" name="from_project" x-model="formData.from_project">
                            
                            <!-- Display field (readonly) -->
                            <select class="form-select" id="from_project_display" disabled>
                                <option value="">Auto-filled from selected asset</option>
                                <template x-for="project in projects" :key="project.id">
                                    <option :value="project.id" :selected="project.id == formData.from_project" x-text="project.name"></option>
                                </template>
                            </select>
                            <div class="form-text text-success" x-show="formData.from_project">
                                <i class="bi bi-check-circle me-1"></i>Auto-filled from selected asset
                            </div>
                            <div class="invalid-feedback">Please select an asset to auto-fill the source project.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="to_project" class="form-label">To Project <span class="text-danger">*</span></label>
                            <select class="form-select" id="to_project" name="to_project" x-model="formData.to_project" required>
                                <option value="">Select To Project</option>
                                <template x-for="project in filteredToProjects" :key="project.id">
                                    <option :value="project.id" x-text="project.name"></option>
                                </template>
                            </select>
                            <div class="form-text" x-show="formData.from_project && !autoFilledToProject">
                                <i class="bi bi-info-circle me-1"></i>Source project excluded from list
                            </div>
                            <div class="form-text text-success" x-show="autoFilledToProject">
                                <i class="bi bi-check-circle me-1"></i>Auto-filled with your assigned project
                            </div>
                            <div class="invalid-feedback">Please select the destination project.</div>
                        </div>
                    </div>

                    <!-- Transfer Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="transfer_type" class="form-label">Transfer Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="transfer_type" name="transfer_type" x-model="formData.transfer_type" required>
                                <option value="">Select Type</option>
                                <option value="temporary" <?= (($formData['transfer_type'] ?? '') == 'temporary') ? 'selected' : '' ?>>Temporary</option>
                                <option value="permanent" <?= (($formData['transfer_type'] ?? '') == 'permanent') ? 'selected' : '' ?>>Permanent</option>
                            </select>
                            <div class="invalid-feedback">Please select the transfer type.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="transfer_date" class="form-label">Transfer Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="transfer_date" name="transfer_date"
                                   x-model="formData.transfer_date" required>
                            <div class="form-text">When should this transfer take place?</div>
                            <div class="invalid-feedback">Please provide the transfer date.</div>
                        </div>
                    </div>

                    <!-- Expected Return Date (for temporary transfers) -->
                    <div class="row mb-4" id="expected_return_row" x-show="formData.transfer_type === 'temporary'" style="display: none;">
                        <div class="col-md-6">
                            <label for="expected_return" class="form-label">Expected Return Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="expected_return" name="expected_return"
                                   x-model="formData.expected_return"
                                   :required="formData.transfer_type === 'temporary'">
                            <div class="form-text">When should the asset be returned?</div>
                            <div class="invalid-feedback">Please provide the expected return date for temporary transfers.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info p-2 mt-4">
                                <small>
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Temporary Transfer:</strong> Asset will be returned to the original project after use.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Reason for Transfer -->
                    <div class="mb-4">
                        <label for="reason" class="form-label">Reason for Transfer <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required
                                  placeholder="Explain why this transfer is needed"
                                  x-model="formData.reason"></textarea>
                        <div class="form-text">Provide a clear justification for the transfer</div>
                        <div class="invalid-feedback">Please provide a reason for the transfer.</div>
                    </div>

                    <!-- Additional Notes -->
                    <div class="mb-4">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"
                                  placeholder="Any additional information about this transfer"
                                  x-model="formData.notes"></textarea>
                        <div class="form-text">Optional additional information</div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Create Transfer Request
                        </button>
                    </div>
                </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <h6><i class="bi bi-exclamation-triangle me-1"></i>Permission Denied</h6>
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
                    <i class="bi bi-info-circle me-2"></i>Transfer Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Required Information</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check-circle text-success me-1"></i> Asset to transfer</li>
                        <li><i class="bi bi-check-circle text-success me-1"></i> Transfer type</li>
                        <li><i class="bi bi-check-circle text-success me-1"></i> From and to projects</li>
                        <li><i class="bi bi-check-circle text-success me-1"></i> Transfer date</li>
                        <li><i class="bi bi-check-circle text-success me-1"></i> Reason for transfer</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6>Smart Transfer Workflow</h6>
                    <div class="alert alert-info p-2 mb-3" x-show="currentUserRole === 'Finance Director' || currentUserRole === 'Asset Director'">
                        <small>
                            <i class="bi bi-lightning-fill me-1"></i>
                            <strong>Streamlined Process:</strong> As a <?= htmlspecialchars($userRole) ?>, your transfers will be automatically processed through all approval steps!
                        </small>
                    </div>
                    <div class="alert alert-info p-2 mb-3" x-show="currentUserRole === 'Project Manager'">
                        <small>
                            <i class="bi bi-box-arrow-in-right me-1"></i>
                            <strong>Auto-Fill:</strong> As a Project Manager, the destination project will automatically be set to your assigned project when you select an asset.
                        </small>
                    </div>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-person-plus text-primary me-1"></i> <strong>Maker:</strong> Finance Director, Asset Director, Project Manager</li>
                        <li><i class="bi bi-person-check text-info me-1"></i> <strong>Verifier:</strong> Auto-verified for Finance Director, Asset Director, Project Manager</li>
                        <li><i class="bi bi-person-check-fill text-success me-1"></i> <strong>Authorizer:</strong> Finance Director, Asset Director (auto-approved for themselves)</li>
                        <li><i class="bi bi-box-arrow-in-down text-warning me-1"></i> <strong>Receiver:</strong> Auto-received for Finance Director, Asset Director</li>
                        <li><i class="bi bi-check2-all text-success me-1"></i> <strong>Completer:</strong> Final step to move asset to destination</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6>Approval Process</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-1-circle text-info me-1"></i> Request submitted</li>
                        <li><i class="bi bi-2-circle text-info me-1"></i> Management approval</li>
                        <li><i class="bi bi-3-circle text-info me-1"></i> Transfer execution</li>
                        <li><i class="bi bi-4-circle text-info me-1"></i> Asset location updated</li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <i class="bi bi-lightbulb me-1"></i>
                        <strong>Tip:</strong> High-value assets may require management approval before transfer.
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=assets" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-box-seam me-1"></i>View All Assets
                    </a>
                    <a href="?route=projects" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-building me-1"></i>View Projects
                    </a>
                    <a href="?route=transfers" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-arrow-left-right me-1"></i>View Transfers
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Keep Select2 for project dropdowns only -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
function transferForm() {
    return {
        formData: {
            asset_id: '<?= htmlspecialchars($formData['asset_id'] ?? '') ?>',
            from_project: '<?= htmlspecialchars($formData['from_project'] ?? '') ?>',
            to_project: '<?= htmlspecialchars($formData['to_project'] ?? '') ?>',
            transfer_type: '<?= htmlspecialchars($formData['transfer_type'] ?? '') ?>',
            transfer_date: '<?= htmlspecialchars($formData['transfer_date'] ?? date('Y-m-d')) ?>',
            expected_return: '<?= htmlspecialchars($formData['expected_return'] ?? '') ?>',
            reason: '<?= htmlspecialchars($formData['reason'] ?? '') ?>',
            notes: '<?= htmlspecialchars($formData['notes'] ?? '') ?>'
        },
        
        availableAssets: <?= json_encode($availableAssets ?? []) ?>,
        projects: <?= json_encode($projects ?? []) ?>,
        selectedAssetInfo: null,
        filteredAssets: [],
        searchText: '',
        showDropdown: false,
        highlightedIndex: -1,
        currentUserRole: '<?= htmlspecialchars($userRole) ?>',
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

            this.formData.asset_id = asset.id;
            this.selectedAssetInfo = asset;
            this.searchText = `${asset.ref} - ${asset.name}`;
            this.showDropdown = false;

            // Auto-populate from project
            if (asset.project_id) {
                this.formData.from_project = String(asset.project_id);

                // Update to_project dropdown to exclude from_project
                this.updateToProjectDropdown();

                // Auto-fill to_project for Project Managers
                // Finance/Asset Directors can select any project
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
                        // If user's project is the same as from_project, clear to_project
                        this.formData.to_project = '';
                        if (typeof $ !== 'undefined' && $('#to_project').length) {
                            $('#to_project').val('').trigger('change');
                        }
                        this.autoFilledToProject = false;
                    }
                } else {
                    console.log('Not PM or no current project - skipping auto-fill');
                    // Clear to_project if it's the same as from_project
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
            this.formData.asset_id = '';
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

            // Initialize Select2 when jQuery is ready
            if (typeof $ !== 'undefined') {
                this.initializeSelect2();
            } else {
                // Wait for jQuery to load, then initialize
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
            // Build initial to_project dropdown options
            const $toProject = $('#to_project');
            $toProject.empty();
            $toProject.append('<option value="">Select To Project</option>');
            this.filteredToProjects.forEach(project => {
                const option = new Option(project.name, project.id, false, false);
                $toProject.append(option);
            });

            // Initialize Select2 for to_project dropdown
            $toProject.select2({
                theme: 'bootstrap-5',
                placeholder: 'Search for destination project...',
                allowClear: true,
                width: '100%'
            });

            // Sync Select2 with Alpine.js for to_project
            $toProject.on('change', (e) => {
                this.formData.to_project = e.target.value;
                this.validateProjects();
            });
        },
        
        
        updateToProjectDropdown() {
            if (typeof $ === 'undefined') {
                console.log('jQuery not loaded yet, skipping Select2 update');
                return;
            }

            // Save current selection
            const currentToProject = this.formData.to_project;

            // Destroy existing Select2 instance
            if ($('#to_project').hasClass('select2-hidden-accessible')) {
                $('#to_project').select2('destroy');
            }

            // Clear and rebuild dropdown with filtered options
            const $toProject = $('#to_project');
            $toProject.empty();
            $toProject.append('<option value="">Select To Project</option>');

            // Add only filtered projects (excluding from_project)
            this.filteredToProjects.forEach(project => {
                const option = new Option(project.name, project.id, false, false);
                $toProject.append(option);
            });

            // Reinitialize Select2
            $toProject.select2({
                theme: 'bootstrap-5',
                placeholder: 'Search for destination project...',
                allowClear: true,
                width: '100%'
            });

            // Restore selection only if it's still in the filtered list
            if (currentToProject && this.filteredToProjects.find(p => p.id == currentToProject)) {
                $toProject.val(currentToProject).trigger('change');
            } else {
                // Clear selection if from_project was selected as to_project
                this.formData.to_project = '';
                $toProject.val('').trigger('change');
            }

            // Re-attach change handler
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
$pageTitle = 'Create Transfer Request - ConstructLink™';
include APP_ROOT . '/views/layouts/main.php';
?>
