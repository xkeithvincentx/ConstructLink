/**
 * Alpine.js Dropdown Synchronization Component
 *
 * Provides bidirectional synchronization between Category and Equipment Type dropdowns
 * with item type auto-population functionality using Alpine.js reactive data.
 *
 * @module assets/features/dropdown-sync-alpine
 * @version 1.0.0
 * @since Phase 4 - Alpine.js Integration
 * @requires Alpine.js 3.x
 * @requires jQuery (for Select2 compatibility)
 */

/**
 * Alpine.js Dropdown Sync Component
 *
 * Features:
 * - Bidirectional category <-> equipment type synchronization
 * - Auto-population of form fields from item type data
 * - Loading states and error handling
 * - Select2 integration
 * - Debounced API calls
 *
 * @returns {Object} Alpine.js component
 */
export function dropdownSync() {
    return {
        // State
        categoryId: '',
        equipmentTypeId: '',
        subtypeId: '',

        // Loading states
        loadingEquipmentTypes: false,
        loadingSubtypes: false,
        loadingItemTypeData: false,

        // Error states
        error: null,

        // Data caches
        allEquipmentTypes: [],
        filteredEquipmentTypes: [],
        subtypes: [],
        itemTypeData: null,

        // Flags to prevent infinite loops
        preventCategorySync: false,
        preventEquipmentSync: false,
        isInitializing: false,

        /**
         * Initialize component
         */
        async init() {
            console.log('Alpine.js Dropdown Sync: Initializing');
            this.isInitializing = true;

            // Load all equipment types on initialization for intelligent search
            await this.loadAllEquipmentTypes();

            // Get initial values from DOM (for edit forms or pre-filled data)
            this.categoryId = this.$refs.categorySelect?.value || '';
            this.equipmentTypeId = this.$refs.equipmentTypeSelect?.value || '';
            this.subtypeId = this.$refs.subtypeSelect?.value || '';

            // If equipment type is pre-selected, load its details
            if (this.equipmentTypeId) {
                await this.loadSubtypes(this.equipmentTypeId);
                await this.loadItemTypeData(this.equipmentTypeId);
            }

            // Set up watchers after initial load
            this.setupWatchers();

            // Set up Select2 event listeners (critical for bidirectional sync)
            this.setupSelect2Listeners();

            // If category is pre-selected, filter equipment types
            if (this.categoryId) {
                this.filterEquipmentTypesByCategory();
            }

            this.isInitializing = false;
            console.log('Alpine.js Dropdown Sync: Initialized');
        },

        /**
         * Set up Alpine.js watchers for reactive synchronization
         */
        setupWatchers() {
            // Watch category changes
            this.$watch('categoryId', async (newValue, oldValue) => {
                if (this.isInitializing || this.preventCategorySync) return;

                console.log('Alpine: Category changed to', newValue);

                if (newValue) {
                    // Show equipment classification section
                    const section = document.getElementById('equipment-classification-section');
                    if (section) {
                        section.style.display = 'block';
                    }

                    // Filter equipment types by category
                    this.filterEquipmentTypesByCategory();

                    // Clear subtypes when category changes
                    this.clearSubtypes();
                } else {
                    // Category cleared - hide section and clear dependent fields
                    const section = document.getElementById('equipment-classification-section');
                    if (section) {
                        section.style.display = 'none';
                    }

                    this.equipmentTypeId = '';
                    this.clearSubtypes();
                }

                // Update Select2 if present
                this.syncSelect2('category_id', newValue);
            });

            // Watch equipment type changes
            this.$watch('equipmentTypeId', async (newValue, oldValue) => {
                if (this.isInitializing || this.preventEquipmentSync) return;

                console.log('Alpine: Equipment type changed to', newValue);

                if (newValue) {
                    // Load subtypes
                    await this.loadSubtypes(newValue);

                    // Auto-select category (if not already set or different)
                    await this.autoSelectCategory(newValue);

                    // Load item type data for auto-population
                    await this.loadItemTypeData(newValue);
                } else {
                    // Equipment type cleared
                    this.clearSubtypes();
                    this.clearItemTypeData();
                }

                // Update Select2 if present
                this.syncSelect2('equipment_type_id', newValue);
            });

            // Watch subtype changes
            this.$watch('subtypeId', (newValue) => {
                if (this.isInitializing) return;

                console.log('Alpine: Subtype changed to', newValue);

                // Update Select2 if present
                this.syncSelect2('subtype_id', newValue);

                // Trigger name generation or other form updates
                this.triggerFormUpdates();
            });
        },

        /**
         * Set up Select2 event listeners for bidirectional sync
         * CRITICAL: This ensures Select2 changes update Alpine data
         */
        setupSelect2Listeners() {
            if (!window.jQuery || !window.jQuery.fn.select2) {
                console.warn('Alpine: Select2 not available, skipping Select2 listeners');
                return;
            }

            console.log('Alpine: Setting up Select2 event listeners');

            // Category Select2 listener
            const $categorySelect = window.jQuery('#category_id');
            if ($categorySelect.length && $categorySelect.hasClass('select2-hidden-accessible')) {
                $categorySelect.on('change', (e) => {
                    if (!this.isInitializing && !this.preventCategorySync) {
                        console.log('Select2: Category changed via Select2 to', e.target.value);
                        this.categoryId = e.target.value;
                    }
                });
                console.log('Alpine: Category Select2 listener attached');
            }

            // Equipment Type Select2 listener
            const $equipmentTypeSelect = window.jQuery('#equipment_type_id');
            if ($equipmentTypeSelect.length && $equipmentTypeSelect.hasClass('select2-hidden-accessible')) {
                $equipmentTypeSelect.on('change', (e) => {
                    if (!this.isInitializing && !this.preventEquipmentSync) {
                        console.log('Select2: Equipment type changed via Select2 to', e.target.value);
                        this.equipmentTypeId = e.target.value;
                    }
                });
                console.log('Alpine: Equipment Type Select2 listener attached');
            }

            // Subtype Select2 listener
            const $subtypeSelect = window.jQuery('#subtype_id');
            if ($subtypeSelect.length && $subtypeSelect.hasClass('select2-hidden-accessible')) {
                $subtypeSelect.on('change', (e) => {
                    if (!this.isInitializing) {
                        console.log('Select2: Subtype changed via Select2 to', e.target.value);
                        this.subtypeId = e.target.value;
                    }
                });
                console.log('Alpine: Subtype Select2 listener attached');
            }
        },

        /**
         * Load all equipment types for intelligent search
         */
        async loadAllEquipmentTypes() {
            try {
                const response = await fetch('?route=api/intelligent-naming&action=all-equipment-types');
                const data = await response.json();

                if (data.success && data.data) {
                    this.allEquipmentTypes = data.data;
                    this.filteredEquipmentTypes = data.data;
                    console.log('Alpine: Loaded', this.allEquipmentTypes.length, 'equipment types');
                }
            } catch (error) {
                console.error('Alpine: Error loading equipment types:', error);
                this.error = 'Failed to load equipment types';
            }
        },

        /**
         * Filter equipment types by selected category
         */
        filterEquipmentTypesByCategory() {
            if (!this.categoryId) {
                this.filteredEquipmentTypes = this.allEquipmentTypes;
                return;
            }

            this.filteredEquipmentTypes = this.allEquipmentTypes.filter(
                type => type.category_id == this.categoryId
            );

            console.log('Alpine: Filtered to', this.filteredEquipmentTypes.length, 'equipment types for category', this.categoryId);

            // Check if current equipment type is still valid for new category
            if (this.equipmentTypeId) {
                const isValid = this.filteredEquipmentTypes.some(
                    type => type.id == this.equipmentTypeId
                );

                if (!isValid) {
                    console.log('Alpine: Current equipment type invalid for category, clearing');
                    this.equipmentTypeId = '';
                }
            }
        },

        /**
         * Auto-select category based on equipment type
         */
        async autoSelectCategory(equipmentTypeId) {
            if (!equipmentTypeId) return;

            try {
                const response = await fetch(
                    `?route=api/equipment-type-details&equipment_type_id=${equipmentTypeId}`
                );
                const data = await response.json();

                if (data.success && data.data && data.data.category_id) {
                    const targetCategoryId = data.data.category_id;

                    // Only auto-select if category is empty or different
                    if (!this.categoryId || this.categoryId != targetCategoryId) {
                        console.log('Alpine: Auto-selecting category', targetCategoryId, data.data.category_name);

                        // Set flag to prevent infinite loop
                        this.preventCategorySync = true;

                        this.categoryId = targetCategoryId;

                        // Update DOM
                        if (this.$refs.categorySelect) {
                            this.$refs.categorySelect.value = targetCategoryId;
                        }

                        // Update Select2
                        this.syncSelect2('category_id', targetCategoryId);

                        // Show notification
                        this.showNotification(`Category automatically selected: ${data.data.category_name}`, 'info');

                        // Reset flag after delay
                        setTimeout(() => {
                            this.preventCategorySync = false;
                        }, 200);
                    }
                }
            } catch (error) {
                console.error('Alpine: Error auto-selecting category:', error);
            }
        },

        /**
         * Load subtypes for equipment type
         */
        async loadSubtypes(equipmentTypeId) {
            if (!equipmentTypeId) {
                this.subtypes = [];
                return;
            }

            this.loadingSubtypes = true;

            try {
                const response = await fetch(
                    `?route=api/intelligent-naming&action=subtypes&equipment_type_id=${equipmentTypeId}`
                );
                const data = await response.json();

                if (data.success && data.data) {
                    this.subtypes = data.data;
                    console.log('Alpine: Loaded', this.subtypes.length, 'subtypes');

                    // Update subtype select required attribute
                    if (this.$refs.subtypeSelect) {
                        if (this.subtypes.length > 0) {
                            this.$refs.subtypeSelect.setAttribute('required', 'required');
                            const asterisk = document.getElementById('subtype-required-asterisk');
                            if (asterisk) asterisk.style.display = 'inline';
                        } else {
                            this.$refs.subtypeSelect.removeAttribute('required');
                            const asterisk = document.getElementById('subtype-required-asterisk');
                            if (asterisk) asterisk.style.display = 'none';
                        }
                    }
                } else {
                    this.subtypes = [];
                }
            } catch (error) {
                console.error('Alpine: Error loading subtypes:', error);
                this.error = 'Failed to load subtypes';
                this.subtypes = [];
            } finally {
                this.loadingSubtypes = false;
            }
        },

        /**
         * Load item type data for auto-population
         */
        async loadItemTypeData(equipmentTypeId) {
            if (!equipmentTypeId) {
                this.itemTypeData = null;
                return;
            }

            this.loadingItemTypeData = true;

            try {
                const response = await fetch(
                    `?route=api/equipment-type-details&equipment_type_id=${equipmentTypeId}`
                );
                const data = await response.json();

                if (data.success && data.data) {
                    this.itemTypeData = data.data;
                    console.log('Alpine: Loaded item type data:', this.itemTypeData);

                    // Auto-populate form fields
                    this.autoPopulateFormFields();
                }
            } catch (error) {
                console.error('Alpine: Error loading item type data:', error);
                this.error = 'Failed to load item type data';
            } finally {
                this.loadingItemTypeData = false;
            }
        },

        /**
         * Auto-populate form fields from item type data
         */
        autoPopulateFormFields() {
            if (!this.itemTypeData) return;

            const data = this.itemTypeData;

            // Auto-populate specifications if available and field is empty
            const specsField = document.getElementById('specifications');
            if (specsField && !specsField.value && data.typical_specifications) {
                specsField.value = data.typical_specifications;
                console.log('Alpine: Auto-populated specifications');
            }

            // Auto-populate unit if available
            const unitField = document.getElementById('unit');
            if (unitField && !unitField.value && data.default_unit) {
                unitField.value = data.default_unit;
                console.log('Alpine: Auto-populated unit');
            }

            // Trigger custom event for other components to listen
            this.$dispatch('itemTypeDataLoaded', { data });
        },

        /**
         * Clear subtypes
         */
        clearSubtypes() {
            this.subtypes = [];
            this.subtypeId = '';

            if (this.$refs.subtypeSelect) {
                this.$refs.subtypeSelect.removeAttribute('required');
                const asterisk = document.getElementById('subtype-required-asterisk');
                if (asterisk) asterisk.style.display = 'none';
            }
        },

        /**
         * Clear item type data
         */
        clearItemTypeData() {
            this.itemTypeData = null;
        },

        /**
         * Sync with Select2 dropdown
         */
        syncSelect2(elementId, value) {
            if (!window.jQuery || !window.jQuery.fn.select2) return;

            const $element = window.jQuery(`#${elementId}`);
            if ($element.hasClass('select2-hidden-accessible')) {
                $element.val(value).trigger('change.select2');
            }
        },

        /**
         * Trigger form updates (name generation, etc.)
         */
        triggerFormUpdates() {
            // Dispatch custom event for other components
            document.dispatchEvent(new CustomEvent('dropdownsChanged', {
                detail: {
                    categoryId: this.categoryId,
                    equipmentTypeId: this.equipmentTypeId,
                    subtypeId: this.subtypeId
                }
            }));

            // Trigger intelligent naming if available
            if (window.generateNamePreview && typeof window.generateNamePreview === 'function') {
                window.generateNamePreview();
            }
        },

        /**
         * Show notification
         */
        showNotification(message, type = 'info') {
            // Check if notification container exists
            let container = document.getElementById('alpine-notifications');

            if (!container) {
                container = document.createElement('div');
                container.id = 'alpine-notifications';
                container.className = 'position-fixed top-0 end-0 p-3';
                container.style.zIndex = '9999';
                document.body.appendChild(container);
            }

            const typeClasses = {
                info: 'alert-info',
                success: 'alert-success',
                warning: 'alert-warning',
                error: 'alert-danger'
            };

            const alertClass = typeClasses[type] || typeClasses.info;

            const notification = document.createElement('div');
            notification.className = `alert ${alertClass} alert-dismissible fade show`;
            notification.setAttribute('role', 'alert');
            notification.innerHTML = `
                <i class="bi bi-info-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            container.appendChild(notification);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        },

        /**
         * Clear all selections
         */
        clearAll() {
            this.preventCategorySync = true;
            this.preventEquipmentSync = true;

            this.categoryId = '';
            this.equipmentTypeId = '';
            this.subtypeId = '';

            this.clearSubtypes();
            this.clearItemTypeData();

            // Update DOM
            if (this.$refs.categorySelect) this.$refs.categorySelect.value = '';
            if (this.$refs.equipmentTypeSelect) this.$refs.equipmentTypeSelect.value = '';
            if (this.$refs.subtypeSelect) this.$refs.subtypeSelect.value = '';

            // Update Select2
            this.syncSelect2('category_id', '');
            this.syncSelect2('equipment_type_id', '');
            this.syncSelect2('subtype_id', '');

            setTimeout(() => {
                this.preventCategorySync = false;
                this.preventEquipmentSync = false;
            }, 200);
        },

        /**
         * Get equipment type name by ID
         */
        getEquipmentTypeName(id) {
            const type = this.allEquipmentTypes.find(t => t.id == id);
            return type ? type.name : '';
        },

        /**
         * Get category name from equipment type
         */
        getCategoryNameFromEquipmentType(equipmentTypeId) {
            const type = this.allEquipmentTypes.find(t => t.id == equipmentTypeId);
            return type ? type.category_name : '';
        }
    };
}

/**
 * Initialize dropdown sync with Alpine.js
 * This function can be called from init scripts
 */
export function initializeDropdownSyncAlpine() {
    console.log('Alpine.js Dropdown Sync: Waiting for Alpine.js initialization...');

    // Register component during Alpine's initialization phase
    document.addEventListener('alpine:init', () => {
        console.log('Alpine.js Dropdown Sync: Registering component');
        window.Alpine.data('dropdownSync', dropdownSync);
    });
}

// Auto-initialize - use alpine:init event to ensure proper timing
document.addEventListener('alpine:init', () => {
    console.log('Alpine.js Dropdown Sync: Auto-registering component');
    window.Alpine.data('dropdownSync', dropdownSync);
});
