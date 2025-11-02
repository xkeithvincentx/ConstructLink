/**
 * Transfer Module JavaScript
 * Centralized JavaScript for all transfer-related views
 *
 * @package ConstructLink
 * @since 1.0.0
 */

const TransferModule = {
    /**
     * Initialize module
     */
    init() {
        this.initializeFormValidation();
        this.initializeDateValidation();
        this.initializeExportFunctions();
        this.initializeAutoRefresh();
        this.initializeSearchEnhancements();
    },

    /**
     * Initialize Bootstrap form validation
     */
    initializeFormValidation() {
        const forms = document.getElementsByClassName('needs-validation');

        Array.prototype.filter.call(forms, (form) => {
            form.addEventListener('submit', (event) => {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    },

    /**
     * Initialize date range validation
     */
    initializeDateValidation() {
        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');

        if (dateFrom) {
            dateFrom.addEventListener('change', () => {
                if (dateFrom.value && dateTo && dateTo.value && dateFrom.value > dateTo.value) {
                    alert('Start date cannot be later than end date');
                    dateFrom.value = '';
                }
            });
        }

        if (dateTo) {
            dateTo.addEventListener('change', () => {
                if (dateTo.value && dateFrom && dateFrom.value && dateTo.value < dateFrom.value) {
                    alert('End date cannot be earlier than start date');
                    dateTo.value = '';
                }
            });
        }
    },

    /**
     * Export table to Excel
     */
    exportToExcel() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'excel');
        window.location.href = '?route=transfers/export&' + params.toString();
    },

    /**
     * Print table
     */
    printTable() {
        window.print();
    },

    /**
     * Initialize export functions
     */
    initializeExportFunctions() {
        // Export to Excel function is available globally
        window.exportToExcel = this.exportToExcel;

        // Print function is available globally
        window.printTable = this.printTable;
    },

    /**
     * Initialize auto-refresh for pending transfers
     */
    initializeAutoRefresh() {
        if (document.querySelector('.badge.bg-warning')) {
            setTimeout(() => {
                location.reload();
            }, 60000); // Refresh every 60 seconds if there are pending transfers
        }
    },

    /**
     * Initialize enhanced search functionality
     */
    initializeSearchEnhancements() {
        const searchInput = document.getElementById('search');

        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.target.form.submit();
                }
            });
        }

        const mobileSearchInput = document.getElementById('mobile_search');

        if (mobileSearchInput) {
            mobileSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.target.form.submit();
                }
            });
        }
    },

    /**
     * Cancel transfer request (API call)
     *
     * @param {number} transferId Transfer ID
     */
    async cancelTransfer(transferId) {
        if (!confirm('Are you sure you want to cancel this transfer request?')) {
            return;
        }

        try {
            const response = await fetch('?route=api/transfers/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ transfer_id: transferId })
            });

            const data = await response.json();

            if (data.success) {
                location.reload();
            } else {
                alert('Failed to cancel transfer: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while canceling the transfer');
        }
    }
};

/**
 * Transfer Form Alpine.js Component
 * Used in: create.php
 */
function transferForm() {
    return {
        formData: {
            asset_id: '',
            from_project: '',
            to_project: '',
            transfer_type: '',
            transfer_date: new Date().toISOString().split('T')[0],
            expected_return: '',
            reason: '',
            notes: ''
        },

        availableAssets: window.transferFormData?.availableAssets || [],
        projects: window.transferFormData?.projects || [],
        selectedAssetInfo: null,
        filteredAssets: [],
        searchText: '',
        showDropdown: false,
        highlightedIndex: -1,
        currentUserRole: window.transferFormData?.userRole || '',
        currentUserProjectId: window.transferFormData?.userProjectId || null,
        autoFilledToProject: false,

        /**
         * Get filtered TO projects (exclude FROM project)
         */
        get filteredToProjects() {
            if (!this.formData.from_project) {
                return this.projects;
            }
            return this.projects.filter(p => p.id != this.formData.from_project);
        },

        /**
         * Filter assets based on search text
         */
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

        /**
         * Select an asset
         *
         * @param {Object} asset Asset object
         */
        selectAsset(asset) {
            this.formData.asset_id = asset.id;
            this.selectedAssetInfo = asset;
            this.searchText = `${asset.ref} - ${asset.name}`;
            this.showDropdown = false;

            // Auto-populate FROM project
            if (asset.project_id) {
                this.formData.from_project = String(asset.project_id);
                this.updateToProjectDropdown();

                // Auto-fill TO project for Project Managers
                if (this.currentUserRole === 'Project Manager' && this.currentUserProjectId) {
                    if (this.currentUserProjectId != asset.project_id) {
                        this.formData.to_project = String(this.currentUserProjectId);
                        if (typeof $ !== 'undefined' && $('#to_project').length) {
                            $('#to_project').val(this.currentUserProjectId).trigger('change');
                        }
                        this.autoFilledToProject = true;
                    } else {
                        this.formData.to_project = '';
                        if (typeof $ !== 'undefined' && $('#to_project').length) {
                            $('#to_project').val('').trigger('change');
                        }
                        this.autoFilledToProject = false;
                    }
                } else {
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

        /**
         * Clear asset selection
         */
        clearSelection() {
            this.formData.asset_id = '';
            this.selectedAssetInfo = null;
            this.searchText = '';
            this.formData.from_project = '';
            this.formData.to_project = '';
            this.showDropdown = false;
            this.autoFilledToProject = false;
            this.filterAssets();

            if (typeof $ !== 'undefined' && $('#to_project').length) {
                $('#to_project').val('').trigger('change');
            }
        },

        /**
         * Navigate dropdown down
         */
        navigateDown() {
            if (this.filteredAssets.length > 0) {
                this.highlightedIndex = (this.highlightedIndex + 1) % this.filteredAssets.length;
                this.showDropdown = true;
            }
        },

        /**
         * Navigate dropdown up
         */
        navigateUp() {
            if (this.filteredAssets.length > 0) {
                this.highlightedIndex = this.highlightedIndex <= 0 ? this.filteredAssets.length - 1 : this.highlightedIndex - 1;
                this.showDropdown = true;
            }
        },

        /**
         * Select highlighted item
         */
        selectHighlighted() {
            if (this.highlightedIndex >= 0 && this.highlightedIndex < this.filteredAssets.length) {
                this.selectAsset(this.filteredAssets[this.highlightedIndex]);
            }
        },

        /**
         * Update TO project dropdown with Select2
         */
        updateToProjectDropdown() {
            if (typeof $ === 'undefined') {
                return;
            }

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

        /**
         * Validate that FROM and TO projects are different
         */
        validateProjects() {
            if (this.formData.from_project && this.formData.to_project && this.formData.from_project === this.formData.to_project) {
                alert('Source and destination projects must be different');
                this.formData.to_project = '';
                if (typeof $ !== 'undefined' && $('#to_project').length) {
                    $('#to_project').val('').trigger('change');
                }
            }
        },

        /**
         * Initialize Select2
         */
        initializeSelect2() {
            if (typeof $ === 'undefined') {
                return;
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

            $toProject.on('change', (e) => {
                this.formData.to_project = e.target.value;
                this.validateProjects();
            });
        },

        /**
         * Component initialization
         */
        init() {
            const today = new Date().toISOString().split('T')[0];
            const transferDateInput = document.getElementById('transfer_date');
            if (transferDateInput) {
                transferDateInput.min = today;
            }

            this.filterAssets();

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

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.position-relative')) {
                    this.showDropdown = false;
                }
            });

            this.$watch('formData.from_project', (newValue, oldValue) => {
                if (newValue && newValue !== oldValue) {
                    this.updateToProjectDropdown();
                }
            });

            this.$watch('formData.transfer_type', (value) => {
                if (value !== 'temporary') {
                    this.formData.expected_return = '';
                }
            });

            this.$watch('formData.transfer_date', (value) => {
                const expectedReturnInput = document.getElementById('expected_return');
                if (value && expectedReturnInput) {
                    const nextDay = new Date(value);
                    nextDay.setDate(nextDay.getDate() + 1);
                    expectedReturnInput.min = nextDay.toISOString().split('T')[0];

                    if (this.formData.expected_return && this.formData.expected_return <= value) {
                        this.formData.expected_return = '';
                    }
                }
            });
        }
    };
}

/**
 * Double confirmation for transfer cancellation
 * Used in: cancel.php
 */
function initializeCancelConfirmation() {
    const cancelForm = document.querySelector('form[action*="cancel"]');

    if (cancelForm) {
        cancelForm.addEventListener('submit', (e) => {
            if (!confirm('Are you absolutely sure you want to cancel this transfer request? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    }
}

/**
 * Auto-fill receipt notes based on condition
 * Used in: receive_return.php
 */
function initializeReceiptNotes() {
    const conditionRadios = document.querySelectorAll('input[name="asset_condition"]');
    const notesTextarea = document.getElementById('receipt_notes');

    if (conditionRadios.length > 0 && notesTextarea) {
        conditionRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                const currentNotes = notesTextarea.value;
                let conditionNote = '';

                switch (this.value) {
                    case 'good':
                        conditionNote = 'Asset received in good condition with no visible damage or issues.';
                        break;
                    case 'fair':
                        conditionNote = 'Asset received in fair condition with minor wear but functional.';
                        break;
                    case 'damaged':
                        conditionNote = 'Asset received with damage - requires inspection and possible maintenance.';
                        break;
                }

                if (!currentNotes.trim()) {
                    notesTextarea.value = conditionNote;
                }
            });
        });
    }
}

/**
 * Initialize on DOM ready
 */
document.addEventListener('DOMContentLoaded', () => {
    TransferModule.init();
    initializeCancelConfirmation();
    initializeReceiptNotes();
});

// Export for global access
window.TransferModule = TransferModule;
window.transferForm = transferForm;
