/**
 * Quick Entry Module
 *
 * Provides quick entry shortcuts for common equipment types.
 * Allows rapid asset creation with pre-filled equipment types.
 *
 * @module assets/features/quick-entry
 * @version 3.0.0
 * @since Phase 3 Refactoring - Inline JavaScript Extraction
 */

import { showAutoSelectionMessage } from '../ui/notifications.js';

/**
 * Initialize quick entry system
 *
 * @param {Object} options - Configuration options
 * @param {string} options.containerId - Container element ID for quick entry buttons
 * @param {Array} options.quickEntryItems - Array of quick entry items
 * @param {boolean} options.isLegacyForm - Is legacy form (default: false)
 */
export function initializeQuickEntry(options = {}) {
    const {
        containerId = 'quick-entry-container',
        quickEntryItems = getDefaultQuickEntryItems(),
        isLegacyForm = false
    } = options;

    const container = document.getElementById(containerId);

    // Fallback: If no container with ID found, try to insert after alert
    let targetContainer = container;
    if (!targetContainer && isLegacyForm) {
        const alertDiv = document.querySelector('.alert.alert-info');
        if (alertDiv) {
            const quickEntryDiv = document.createElement('div');
            quickEntryDiv.id = containerId;
            alertDiv.insertAdjacentElement('afterend', quickEntryDiv);
            targetContainer = quickEntryDiv;
        }
    }

    if (!targetContainer) {
        console.warn('Quick entry container not found');
        return;
    }

    const quickEntryHtml = buildQuickEntryHTML(quickEntryItems, isLegacyForm);
    targetContainer.innerHTML = quickEntryHtml;

    // Add event listeners via event delegation (handled in clear-buttons.js)
}

/**
 * Get default quick entry items
 *
 * @returns {Array} - Array of quick entry item objects
 */
function getDefaultQuickEntryItems() {
    return [
        { equipment: 'Hand Tool', icon: '🔨', label: 'Hand Tool' },
        { equipment: 'Power Tool', icon: '🪚', label: 'Power Tool' },
        { equipment: 'Measurement', icon: '📏', label: 'Measurement' },
        { equipment: 'Safety', icon: '🦺', label: 'Safety' },
        { equipment: 'Material', icon: '🧱', label: 'Material' },
        { equipment: 'Equipment', icon: '⚙️', label: 'Equipment' }
    ];
}

/**
 * Build quick entry HTML
 *
 * @param {Array} items - Array of quick entry items
 * @param {boolean} isLegacyForm - Is legacy form
 * @returns {string} - HTML string
 */
function buildQuickEntryHTML(items, isLegacyForm) {
    const title = isLegacyForm ? 'Warehouse Quick Entry' : 'Item Quick Entry';
    const subtitle = 'Click to auto-fill common items';

    const buttons = items.map(item => `
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-primary btn-sm w-100 quick-fill" data-equipment="${item.equipment}">
                ${item.icon}<br><small>${item.label}</small>
            </button>
        </div>
    `).join('');

    return `
    <div class="card bg-light border-primary mb-4">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0">
                <i class="bi bi-lightning me-2"></i>${title}
                <small class="ms-2 opacity-75">${subtitle}</small>
            </h6>
        </div>
        <div class="card-body py-3">
            <div class="row g-2">
                ${buttons}
            </div>
        </div>
    </div>
    `;
}

/**
 * Quick fill equipment type
 *
 * @param {string} equipmentName - Equipment type name to search for
 * @returns {boolean} - True if equipment found and filled
 */
export function quickFillEquipment(equipmentName) {

    const equipmentSelect = document.getElementById('equipment_type_id');

    if (!equipmentSelect) {
        console.error('Equipment select not found');
        return false;
    }


    // Find the equipment type option
    let found = false;
    for (let option of equipmentSelect.options) {
        if (option.textContent.toLowerCase().includes(equipmentName.toLowerCase())) {

            // Clear the Select2 first if it exists
            if (window.jQuery && window.jQuery('#equipment_type_id').hasClass('select2-hidden-accessible')) {
                window.jQuery('#equipment_type_id').val(option.value).trigger('change');
            } else {
                equipmentSelect.value = option.value;
                equipmentSelect.dispatchEvent(new Event('change'));
            }

            // Wait for category auto-selection and subtype loading, then try to generate name
            setTimeout(() => {
                const subtypeSelect = document.getElementById('subtype_id');
                if (subtypeSelect && subtypeSelect.options.length > 1) {
                    // Auto-select first available subtype for name generation
                    const firstSubtype = subtypeSelect.options[1]; // Skip empty option
                    if (firstSubtype) {

                        // Set value using Select2 if available, otherwise native
                        if (window.jQuery && window.jQuery('#subtype_id').hasClass('select2-hidden-accessible')) {
                            window.jQuery('#subtype_id').val(firstSubtype.value).trigger('change');
                        } else {
                            subtypeSelect.value = firstSubtype.value;
                            subtypeSelect.dispatchEvent(new Event('change'));
                        }
                    }
                }
            }, 1000); // Wait 1 second for category auto-selection and subtype loading

            // Show success message
            showAutoSelectionMessage(`Quick filled: ${equipmentName} - Name will be generated automatically!`);
            found = true;
            break;
        }
    }

    if (!found) {
        console.warn(`No equipment type found matching: ${equipmentName}`);
        for (let option of equipmentSelect.options) {
        }
    }

    return found;
}

// Expose functions to window for global access
if (typeof window !== 'undefined') {
    window.quickFillEquipment = quickFillEquipment;
    window.testQuickEntry = (equipmentName) => {
        quickFillEquipment(equipmentName || 'Hand Tool');
    };
}
