/**
 * Clear Buttons Module
 *
 * Handles clear/reset button functionality for category and equipment type fields.
 * Implements bidirectional clearing and proper event delegation.
 *
 * @module assets/ui/clear-buttons
 * @version 3.0.0
 * @since Phase 3 Refactoring - Inline JavaScript Extraction
 */

import { clearNamePreview } from '../features/intelligent-naming.js';
import { filterAndPopulateEquipmentTypes, getAllEquipmentTypes } from '../features/equipment-classification.js';
import { showResetNotification } from './notifications.js';
import { clearSubtypes } from '../core/asset-form-base.js';
import { quickFillEquipment } from '../features/quick-entry.js';

/**
 * Initialize clear buttons with event delegation
 *
 * @param {Object} options - Configuration options
 * @param {string} options.clearCategoryBtnId - Clear category button ID
 * @param {string} options.clearEquipmentBtnId - Clear equipment button ID
 */
export function initializeClearButtons(options = {}) {
    const {
        clearCategoryBtnId = 'clear-category-btn',
        clearEquipmentBtnId = 'clear-equipment-btn'
    } = options;

    // Use event delegation for better reliability
    document.addEventListener('click', function(e) {
        // Handle clear category button
        if (e.target && e.target.id === clearCategoryBtnId) {
            e.preventDefault();
            e.stopPropagation();

            handleClearCategory();
            return false;
        }

        // Handle clear equipment button
        if (e.target && e.target.id === clearEquipmentBtnId) {
            e.preventDefault();
            e.stopPropagation();

            handleClearEquipment();
            return false;
        }

        // Handle quick entry buttons
        if (e.target && e.target.classList.contains('quick-fill')) {
            e.preventDefault();
            e.stopPropagation();

            const equipmentName = e.target.dataset.equipment;
            if (equipmentName) {
                quickFillEquipment(equipmentName);
            } else {
                console.error('No equipment name found in dataset');
            }
            return false;
        }
    });

}

/**
 * Handle clear category action
 */
export function handleClearCategory() {
    const categorySelect = document.getElementById('category_id');
    const equipmentTypeSelect = document.getElementById('equipment_type_id');

    // Clear both fields
    if (categorySelect) categorySelect.value = '';
    if (equipmentTypeSelect) equipmentTypeSelect.value = '';

    // Handle Select2
    if (window.jQuery) {
        if (window.jQuery('#category_id').hasClass('select2-hidden-accessible')) {
            window.jQuery('#category_id').val('').trigger('change');
        }
        if (window.jQuery('#equipment_type_id').hasClass('select2-hidden-accessible')) {
            window.jQuery('#equipment_type_id').val('').trigger('change');
        }
    }

    // Clear other related fields
    clearSubtypes();
    clearNamePreview();

    // Reset unit to default
    const unitSelect = document.getElementById('unit');
    if (unitSelect) unitSelect.value = 'pcs';

    // Reload all equipment types (no category filter)
    const allEquipmentTypes = getAllEquipmentTypes();
    if (allEquipmentTypes && allEquipmentTypes.length > 0 && equipmentTypeSelect) {
        filterAndPopulateEquipmentTypes(null);
    }

    // Show notification
    showResetNotification('All selections cleared - showing all equipment types');
}

/**
 * Handle clear equipment action (bidirectional)
 */
export function handleClearEquipment() {
    const categorySelect = document.getElementById('category_id');
    const equipmentTypeSelect = document.getElementById('equipment_type_id');

    // Set flag to prevent auto-category selection
    window.preventCategoryAutoSelection = true;

    // Clear both fields (bidirectional clearing)
    if (categorySelect) categorySelect.value = '';
    if (equipmentTypeSelect) equipmentTypeSelect.value = '';

    // Handle Select2
    if (window.jQuery) {
        if (window.jQuery('#category_id').hasClass('select2-hidden-accessible')) {
            window.jQuery('#category_id').val('').trigger('change');
        }
        if (window.jQuery('#equipment_type_id').hasClass('select2-hidden-accessible')) {
            window.jQuery('#equipment_type_id').val('').trigger('change');
        }
    }

    // Clear other related fields
    clearSubtypes();
    clearNamePreview();

    // Reset unit to default
    const unitSelect = document.getElementById('unit');
    if (unitSelect) unitSelect.value = 'pcs';

    // Show all equipment types (no category filter)
    const allEquipmentTypes = getAllEquipmentTypes();
    if (allEquipmentTypes && allEquipmentTypes.length > 0 && equipmentTypeSelect) {
        filterAndPopulateEquipmentTypes(null);
    }

    // Trigger change events
    if (equipmentTypeSelect) {
        equipmentTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (categorySelect) {
        categorySelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Reset flag after processing
    setTimeout(() => {
        window.preventCategoryAutoSelection = false;
    }, 200);

    // Show notification
    showResetNotification('Equipment and category cleared - showing all equipment types');
}

/**
 * Manual test function for category reset
 */
export function testCategoryReset() {
    handleClearCategory();
    showResetNotification('Manual reset completed - all fields cleared');
}

// Expose test functions to window for debugging
if (typeof window !== 'undefined') {
    window.testCategoryReset = testCategoryReset;
}
