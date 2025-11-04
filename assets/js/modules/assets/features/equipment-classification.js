/**
 * Equipment Classification Module
 *
 * Handles equipment type and subtype selection, category filtering,
 * and bidirectional synchronization between category and equipment type.
 *
 * @module assets/features/equipment-classification
 * @version 3.0.0
 * @since Phase 3 Refactoring - Inline JavaScript Extraction
 */

import { loadAllEquipmentTypes, populateEquipmentTypeDropdown, filterEquipmentTypesByCategory, fetchEquipmentTypeDetails, loadSubtypes } from '../core/asset-form-base.js';
import { showAutoSelectionMessage } from '../ui/notifications.js';

/**
 * All equipment types storage
 * @type {Array}
 */
let allEquipmentTypes = [];

/**
 * Equipment type to category mapping
 * @type {Object}
 */
let equipmentTypeToCategory = {};

/**
 * Initialize equipment classification system
 *
 * @param {Object} options - Configuration options
 * @param {string} options.categorySelectId - ID of category select element
 * @param {string} options.equipmentTypeSelectId - ID of equipment type select element
 * @param {boolean} options.enableCategoryAutoSelection - Enable automatic category selection (default: true)
 */
export async function initializeEquipmentClassification(options = {}) {
    const {
        categorySelectId = 'category_id',
        equipmentTypeSelectId = 'equipment_type_id',
        enableCategoryAutoSelection = true
    } = options;

    const categorySelect = document.getElementById(categorySelectId);
    const equipmentTypeSelect = document.getElementById(equipmentTypeSelectId);

    if (!categorySelect || !equipmentTypeSelect) {
        console.warn('Required elements for equipment classification not found');
        return;
    }

    // Load all equipment types on initialization
    allEquipmentTypes = await loadAllEquipmentTypes();
    buildEquipmentTypeToCategoryMapping(allEquipmentTypes);

    // Initially populate equipment type dropdown with all types (for legacy mode intelligent search)
    // or leave empty for standard mode (will be populated when category is selected)
    if (!categorySelect.value) {
        populateEquipmentTypeDropdown(equipmentTypeSelect, allEquipmentTypes, 'Type to search equipment...');
    } else {
        // If category is pre-selected, filter equipment types for that category
        filterAndPopulateEquipmentTypes(categorySelect.value);
    }

    // Category change handler - filter equipment types
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        const currentEquipmentTypeId = equipmentTypeSelect.value;

        // Check if we need to preserve equipment selection due to auto-category-selection
        const preserveSelection = window.preserveEquipmentSelection;

        if (categoryId) {
            const equipmentClassificationSection = document.getElementById('equipment-classification-section');
            if (equipmentClassificationSection) {
                equipmentClassificationSection.style.display = 'block';
            }

            // Filter equipment types by the selected category
            filterAndPopulateEquipmentTypes(categoryId);

            // Restore equipment selection if it was auto-triggered
            if (preserveSelection) {
                setTimeout(() => {
                    // Set flag to skip category auto-selection (avoid infinite loop)
                    window.skipCategoryAutoSelection = true;

                    equipmentTypeSelect.value = preserveSelection;

                    // Also restore Select2 selection if exists
                    if (window.jQuery && window.jQuery('#' + equipmentTypeSelectId).hasClass('select2-hidden-accessible')) {
                        window.jQuery('#' + equipmentTypeSelectId).val(preserveSelection).trigger('change');
                    }

                    // Clear the preserve flag
                    window.preserveEquipmentSelection = null;
                }, 100);
            } else if (currentEquipmentTypeId) {
                // Check if current equipment type is still valid for new category
                const currentEquipmentTypeOption = equipmentTypeSelect.querySelector(`option[value="${currentEquipmentTypeId}"]`);

                if (!currentEquipmentTypeOption) {
                    // Equipment type is no longer valid for this category - clear it
                    equipmentTypeSelect.value = '';

                    // Update Select2 if initialized
                    if (window.jQuery && window.jQuery('#' + equipmentTypeSelectId).hasClass('select2-hidden-accessible')) {
                        window.jQuery('#' + equipmentTypeSelectId).val('').trigger('change');
                    }

                    // Clear subtypes as well
                    clearSubtypes();
                }
            }
        } else {
            const equipmentClassificationSection = document.getElementById('equipment-classification-section');
            if (equipmentClassificationSection) {
                equipmentClassificationSection.style.display = 'none';
            }
            clearEquipmentClassification();
        }
    });

    // Equipment type change handler - auto-select category and load subtypes
    equipmentTypeSelect.addEventListener('change', async function() {
        const equipmentTypeId = this.value;
        const selectedOption = this.options[this.selectedIndex];
        const subtypeSelect = document.getElementById('subtype_id');

        // Clear subtypes if no equipment type selected
        if (!equipmentTypeId) {
            await loadSubtypes(null);
            hideEquipmentDetails();
            return;
        }

        // Prevent infinite loops when category auto-selection triggers category change
        window.preventCategoryAutoSelection = true;

        // Step 1: Load subtypes for the selected equipment type
        await loadSubtypes(equipmentTypeId);

        // Step 2: Auto-select category if enabled and needed
        if (enableCategoryAutoSelection && !window.skipCategoryAutoSelection) {
            const details = await fetchEquipmentTypeDetails(equipmentTypeId);

            if (details && details.category_id) {
                const currentCategoryId = categorySelect.value;
                const targetCategoryId = details.category_id;

                // Only auto-select category if:
                // 1. No category is currently selected, OR
                // 2. The current category doesn't match the equipment type's category
                if (!currentCategoryId || currentCategoryId != targetCategoryId) {

                    // Set flag to preserve equipment type selection during category change
                    window.preserveEquipmentSelection = equipmentTypeId;

                    // Set category value
                    categorySelect.value = targetCategoryId;

                    // Trigger Select2 update if applicable
                    if (window.jQuery && window.jQuery('#' + categorySelectId).hasClass('select2-hidden-accessible')) {
                        window.jQuery('#' + categorySelectId).val(targetCategoryId).trigger('change');
                    } else {
                        // Manually trigger change event if Select2 not initialized
                        const event = new Event('change', { bubbles: true });
                        categorySelect.dispatchEvent(event);
                    }

                    // Show feedback
                    showAutoSelectionMessage('Category automatically selected: ' + details.category_name);
                }
            }
        }

        // Reset the flag after a short delay
        setTimeout(() => {
            window.preventCategoryAutoSelection = false;
            window.skipCategoryAutoSelection = false;
        }, 200);
    });

    // Initialize if category is pre-selected (for edit forms or pre-filled data)
    if (categorySelect.value) {
        const equipmentClassificationSection = document.getElementById('equipment-classification-section');
        if (equipmentClassificationSection) {
            equipmentClassificationSection.style.display = 'block';
        }
        filterAndPopulateEquipmentTypes(categorySelect.value);
    }

    // Initialize if equipment type is pre-selected (for edit forms)
    // Use setTimeout to ensure DOM is ready and Select2 is initialized
    setTimeout(async () => {
        if (equipmentTypeSelect.value) {
            const equipmentTypeId = equipmentTypeSelect.value;

            // Load subtypes for the pre-selected equipment type
            // Note: loadSubtypes() handles Select2 update internally
            await loadSubtypes(equipmentTypeId);
        }
    }, 300);

}

/**
 * Filter and populate equipment types by category
 *
 * @param {number|string} categoryId - Category ID
 */
export function filterAndPopulateEquipmentTypes(categoryId) {
    const equipmentTypeSelect = document.getElementById('equipment_type_id');

    if (!equipmentTypeSelect) {
        console.warn('Equipment type select not found');
        return;
    }

    if (!categoryId) {
        populateEquipmentTypeDropdown(equipmentTypeSelect, allEquipmentTypes, 'Type to search equipment...');
        return;
    }

    const filteredTypes = filterEquipmentTypesByCategory(allEquipmentTypes, categoryId);
    populateEquipmentTypeDropdown(equipmentTypeSelect, filteredTypes, 'Select Equipment Type');
}

/**
 * Build equipment type to category mapping
 *
 * @param {Array} equipmentTypes - Array of equipment type objects
 */
function buildEquipmentTypeToCategoryMapping(equipmentTypes) {
    equipmentTypeToCategory = {};

    equipmentTypes.forEach(type => {
        equipmentTypeToCategory[type.id] = {
            categoryId: type.category_id,
            categoryName: type.category_name
        };
    });

}

/**
 * Show equipment details for selected subtype
 *
 * @param {HTMLOptionElement} subtypeOption - Selected subtype option element
 */
export function showEquipmentDetails(subtypeOption) {
    if (!subtypeOption) return;

    const materialType = subtypeOption.getAttribute('data-material') || 'N/A';
    const powerSource = subtypeOption.getAttribute('data-power') || 'N/A';
    const application = subtypeOption.getAttribute('data-application') || 'N/A';

    const equipmentDetails = document.getElementById('equipment-details');
    if (!equipmentDetails) return;

    const materialDisplay = document.getElementById('material-type-display');
    const powerDisplay = document.getElementById('power-source-display');
    const applicationDisplay = document.getElementById('application-display');

    if (materialDisplay) materialDisplay.textContent = materialType;
    if (powerDisplay) powerDisplay.textContent = powerSource;
    if (applicationDisplay) applicationDisplay.textContent = application;

    equipmentDetails.style.display = 'block';
}

/**
 * Hide equipment details
 */
export function hideEquipmentDetails() {
    const equipmentDetails = document.getElementById('equipment-details');
    if (equipmentDetails) {
        equipmentDetails.style.display = 'none';
    }
}

/**
 * Clear equipment classification (equipment type and subtypes)
 */
export function clearEquipmentClassification() {
    clearEquipmentTypes();
    clearSubtypes();
    hideEquipmentDetails();

    const nameInput = document.getElementById('name') || document.getElementById('asset_name');
    if (nameInput && nameInput.readOnly) {
        nameInput.value = '';
    }
}

/**
 * Clear equipment types dropdown
 */
function clearEquipmentTypes() {
    const equipmentTypeSelect = document.getElementById('equipment_type_id');
    if (equipmentTypeSelect) {
        equipmentTypeSelect.innerHTML = '<option value="">Select Equipment Type</option>';
    }
}

/**
 * Clear subtypes dropdown
 */
function clearSubtypes() {
    const subtypeSelect = document.getElementById('subtype_id');
    if (subtypeSelect) {
        subtypeSelect.innerHTML = '<option value="">Select Subtype</option>';
        subtypeSelect.removeAttribute('required');
        const subtypeAsterisk = document.getElementById('subtype-required-asterisk');
        if (subtypeAsterisk) subtypeAsterisk.style.display = 'none';
    }
}

/**
 * Get all equipment types
 *
 * @returns {Array} - All equipment types
 */
export function getAllEquipmentTypes() {
    return allEquipmentTypes;
}

/**
 * Get equipment type to category mapping
 *
 * @returns {Object} - Equipment type to category mapping
 */
export function getEquipmentTypeToCategoryMapping() {
    return equipmentTypeToCategory;
}

/**
 * Get category for equipment type
 *
 * @param {number|string} equipmentTypeId - Equipment type ID
 * @returns {Object|null} - Category info or null
 */
export function getCategoryForEquipmentType(equipmentTypeId) {
    return equipmentTypeToCategory[equipmentTypeId] || null;
}
