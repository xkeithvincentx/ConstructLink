/**
 * Legacy Form Initializer
 *
 * Initializes the legacy asset creation form with all features.
 * Includes additional legacy-specific features like "Add Another" and bulk entry.
 *
 * @module assets/init/legacy-form
 * @version 3.0.0
 * @since Phase 3 Refactoring - Inline JavaScript Extraction
 */

import { initializeFormValidation, updateQuantityHandling, initializeTextareaAutoResize } from '../core/asset-form-base.js';
import { initializeIntelligentNaming, getCurrentGeneratedName } from '../features/intelligent-naming.js';
import { initializeEquipmentClassification } from '../features/equipment-classification.js';
import { initializeDisciplineHandling } from '../features/discipline-handler.js';
import { initializeBrandValidation } from '../features/brand-validation.js';
import { initializeQuickEntry } from '../features/quick-entry.js';
import { initializeClearButtons } from '../ui/clear-buttons.js';
import { showCategoryWarning, hideCategoryWarning } from '../ui/notifications.js';
import { initializeDropdownSyncAlpine } from '../features/dropdown-sync-alpine.js';

/**
 * Initialize legacy form
 */
export function initLegacyForm() {

    const form = document.querySelector('form.needs-validation, form[action*="legacy-create"]');
    if (!form) {
        console.warn('Legacy form not found');
        return;
    }

    // Initialize core form validation
    initializeFormValidation(form);

    // Add form submission handler to ensure name is populated
    form.addEventListener('submit', function(e) {
        const nameField = document.getElementById('asset_name') || document.getElementById('name');

        // CRITICAL: Ensure name field has a value before submission
        if (nameField && !nameField.value.trim()) {
            const currentGeneratedName = getCurrentGeneratedName();
            if (currentGeneratedName) {
                console.log('Legacy form: Using generated name:', currentGeneratedName);
                nameField.value = currentGeneratedName;
            } else {
                // Last resort: Generate simple name from available data
                const equipmentTypeSelect = document.getElementById('equipment_type_id');
                const subtypeSelect = document.getElementById('subtype_id');
                const categorySelect = document.getElementById('category_id');

                if (equipmentTypeSelect && equipmentTypeSelect.value && subtypeSelect && subtypeSelect.value) {
                    const equipmentText = equipmentTypeSelect.options[equipmentTypeSelect.selectedIndex].textContent;
                    const subtypeText = subtypeSelect.options[subtypeSelect.selectedIndex].textContent;
                    const fallbackName = `${equipmentText} - ${subtypeText}`;
                    console.log('Legacy form: Using fallback name from equipment/subtype:', fallbackName);
                    nameField.value = fallbackName;
                } else if (equipmentTypeSelect && equipmentTypeSelect.value) {
                    const equipmentText = equipmentTypeSelect.options[equipmentTypeSelect.selectedIndex].textContent;
                    const fallbackName = equipmentText + ' - Legacy Asset';
                    console.log('Legacy form: Using fallback name from equipment:', fallbackName);
                    nameField.value = fallbackName;
                } else if (categorySelect && categorySelect.value) {
                    const categoryText = categorySelect.options[categorySelect.selectedIndex].textContent;
                    const fallbackName = categoryText + ' - Legacy Asset';
                    console.log('Legacy form: Using fallback name from category:', fallbackName);
                    nameField.value = fallbackName;
                } else {
                    // Absolute fallback
                    console.warn('Legacy form: No name data available, using generic name');
                    nameField.value = 'Legacy Asset - ' + new Date().getTime();
                }
            }
        }

        // Remove readonly attribute before submission to ensure value is sent
        if (nameField && nameField.readOnly) {
            nameField.readOnly = false;
        }
    });

    // Initialize Alpine.js dropdown sync
    // Component will auto-register on alpine:init event
    initializeDropdownSyncAlpine();

    // Initialize Select2 on all dropdowns (required before equipment classification)
    if (window.jQuery && window.jQuery.fn.select2) {
        jQuery('#category_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Category',
            allowClear: true,
            width: '100%'
        });

        jQuery('#equipment_type_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Type to search equipment...',
            allowClear: true,
            width: '100%'
        });

        jQuery('#subtype_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Subtype',
            allowClear: true,
            width: '100%'
        });

        jQuery('#brand').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Brand',
            allowClear: true,
            width: '100%',
            tags: true
        });

        // CRITICAL: Populate hidden brand_id field when brand is selected
        // Using Select2's native event for better compatibility
        jQuery('#brand').on('select2:select', function(e) {
            const data = e.params.data;
            const selectedOption = jQuery(this).find('option:selected');
            const brandId = selectedOption.attr('data-brand-id') || '';
            const brandName = data.text || selectedOption.val() || '';

            console.log('LEGACY - Brand select2:select event fired');
            console.log('LEGACY - Brand selected:', brandName, 'ID:', brandId);
            console.log('LEGACY - Selected option:', selectedOption);
            console.log('LEGACY - data-brand-id attribute:', selectedOption.attr('data-brand-id'));

            // Populate hidden fields
            jQuery('#brand_id').val(brandId);
            jQuery('#standardized_brand').val(brandName);

            console.log('LEGACY - Hidden fields populated - brand_id:', jQuery('#brand_id').val(), 'standardized_brand:', jQuery('#standardized_brand').val());
        });

        // Also listen to regular change event as backup
        jQuery('#brand').on('change', function() {
            const selectedOption = jQuery(this).find('option:selected');
            const brandId = selectedOption.attr('data-brand-id') || '';
            const brandName = selectedOption.val() || '';

            console.log('LEGACY - Brand change event fired (backup)');

            // Only populate if select2:select didn't already handle it
            if (!jQuery('#brand_id').val()) {
                jQuery('#brand_id').val(brandId);
                jQuery('#standardized_brand').val(brandName);
                console.log('LEGACY - Backup handler populated fields - brand_id:', brandId);
            }
        });

        // Handle clear event
        jQuery('#brand').on('select2:clear', function() {
            console.log('LEGACY - Brand cleared');
            jQuery('#brand_id').val('');
            jQuery('#standardized_brand').val('');
        });

        // Initialize brand_id if brand is already selected (for edit forms)
        const currentBrandValue = jQuery('#brand').val();
        if (currentBrandValue) {
            const selectedOption = jQuery('#brand').find('option:selected');
            const brandId = selectedOption.attr('data-brand-id') || '';
            jQuery('#brand_id').val(brandId);
            jQuery('#standardized_brand').val(currentBrandValue);
            console.log('LEGACY - Brand initialized:', currentBrandValue, 'ID:', brandId);
        }

        jQuery('#primary_discipline').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Primary Use (Optional)',
            allowClear: true,
            width: '100%'
        });

        jQuery('#project_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Project',
            allowClear: true,
            width: '100%'
        });

        jQuery('#sub_location').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Location',
            allowClear: true,
            width: '100%'
        });

        // NOTE: condition_notes is a textarea, not a select dropdown
        // Removed Select2 initialization to fix unpopulated dropdown issue
    }

    // Initialize equipment classification (with category auto-selection)
    initializeEquipmentClassification({
        categorySelectId: 'category_id',
        equipmentTypeSelectId: 'equipment_type_id',
        enableCategoryAutoSelection: true
    });

    // Initialize intelligent naming (legacy uses 'asset_name' instead of 'name')
    initializeIntelligentNaming({
        nameInputId: 'asset_name',
        equipmentTypeId: 'equipment_type_id',
        subtypeId: 'subtype_id',
        brandId: 'brand',
        modelId: 'model'
    });

    // Initialize discipline handling
    initializeDisciplineHandling({
        categorySelectId: 'category_id',
        disciplineSectionId: 'discipline-section'
    });

    // Initialize brand validation
    initializeBrandValidation({
        brandInputId: 'brand'
    });

    // Initialize quick entry (legacy specific)
    initializeQuickEntry({
        containerId: 'quick-entry-container',
        isLegacyForm: true
    });

    // Initialize clear buttons
    initializeClearButtons({
        clearCategoryBtnId: 'clear-category-btn',
        clearEquipmentBtnId: 'clear-equipment-btn'
    });

    // Initialize legacy-specific features
    initializeCategoryInfoDisplay();
    initializeBulkEntry();
    initializeAddAnother();

    // Initialize textarea auto-resize
    initializeTextareaAutoResize();

    // Initialize today's count display
    initializeTodayCount();

}

/**
 * Initialize category info display (business classification)
 */
function initializeCategoryInfoDisplay() {
    const categorySelect = document.getElementById('category_id');

    if (!categorySelect) {
        return;
    }

    categorySelect.addEventListener('change', function() {
        updateCategoryInfo();
        updateQuantityHandling(this.value);
    });

    // Initialize if category is already selected
    if (categorySelect.value) {
        updateCategoryInfo();
        updateQuantityHandling(categorySelect.value);
    }

}

/**
 * Update category info panel
 */
function updateCategoryInfo() {
    const categorySelect = document.getElementById('category_id');
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    const categoryInfo = document.getElementById('category-info');

    if (!categoryInfo) return;

    if (categorySelect.value === '') {
        categoryInfo.style.display = 'none';
        return;
    }

    // Get category business data
    const assetType = selectedOption.getAttribute('data-asset-type') || 'capital';
    const generatesAssets = selectedOption.getAttribute('data-generates-assets') === '1';
    const isConsumable = selectedOption.getAttribute('data-is-consumable') === '1';
    const threshold = selectedOption.getAttribute('data-threshold') || '0';
    const businessDesc = selectedOption.getAttribute('data-business-desc') || '';

    // Update UI elements
    const assetTypeDisplay = document.getElementById('category-asset-type');
    const generatesAssetsDisplay = document.getElementById('category-generates-assets');
    const isConsumableDisplay = document.getElementById('category-is-consumable');
    const thresholdDisplay = document.getElementById('category-threshold');

    if (assetTypeDisplay) assetTypeDisplay.innerHTML = getAssetTypeDisplay(assetType);
    if (generatesAssetsDisplay) {
        generatesAssetsDisplay.innerHTML = generatesAssets ?
            '<span class="text-success">Yes</span>' : '<span class="text-danger">No - Direct Expense</span>';
    }
    if (isConsumableDisplay) {
        isConsumableDisplay.innerHTML = isConsumable ?
            '<span class="text-info">Yes</span>' : '<span class="text-muted">No</span>';
    }
    if (thresholdDisplay) {
        thresholdDisplay.innerHTML = threshold > 0 ?
            '$' + parseFloat(threshold).toFixed(2) : '<span class="text-muted">No threshold</span>';
    }

    // Show business description if available
    const businessDescDiv = document.getElementById('category-business-desc');
    if (businessDescDiv && businessDesc) {
        const descText = businessDescDiv.querySelector('small');
        if (descText) descText.textContent = businessDesc;
        businessDescDiv.style.display = 'block';
    } else if (businessDescDiv) {
        businessDescDiv.style.display = 'none';
    }

    // Show category info panel
    categoryInfo.style.display = 'block';

    // Show warning for expense-only categories
    if (!generatesAssets) {
        showCategoryWarning('This category is configured for direct expenses only. Legacy assets cannot be created for this category type.');
    } else {
        hideCategoryWarning();
    }
}

/**
 * Get asset type display badge
 *
 * @param {string} assetType - Asset type
 * @returns {string} - HTML string for badge
 */
function getAssetTypeDisplay(assetType) {
    const types = {
        'capital': '<span class="badge bg-primary">🔧 Capital Asset</span>',
        'inventory': '<span class="badge bg-info">📦 Inventory/Materials</span>',
        'expense': '<span class="badge bg-warning">💰 Direct Expense</span>'
    };
    return types[assetType] || types['capital'];
}

/**
 * Initialize bulk entry functionality
 */
function initializeBulkEntry() {
    const bulkToggle = document.getElementById('bulk-entry-toggle');
    const bulkPanel = document.getElementById('bulk-entry-panel');
    const bulkClose = document.getElementById('bulk-entry-close');
    const bulkSerialMode = document.getElementById('bulk-serial-mode');
    const bulkPrefixContainer = document.getElementById('bulk-prefix-container');
    const quantityInput = document.getElementById('quantity');
    const bulkQuantityInput = document.getElementById('bulk-quantity');

    if (!bulkToggle || !bulkPanel) {
        return;
    }

    // Show/hide bulk entry panel
    bulkToggle.addEventListener('click', function() {
        if (bulkPanel.style.display === 'none' || !bulkPanel.style.display) {
            bulkPanel.style.display = 'block';
            this.innerHTML = '<i class="bi bi-x"></i> Close Bulk';
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');

            // Sync bulk quantity with main quantity
            if (quantityInput && bulkQuantityInput && quantityInput.value > 1) {
                bulkQuantityInput.value = quantityInput.value;
            }
        } else {
            bulkPanel.style.display = 'none';
            this.innerHTML = '<i class="bi bi-stack"></i> Bulk Entry';
            this.classList.remove('btn-primary');
            this.classList.add('btn-outline-primary');
        }
    });

    // Close bulk entry panel
    if (bulkClose) {
        bulkClose.addEventListener('click', function() {
            bulkPanel.style.display = 'none';
            bulkToggle.innerHTML = '<i class="bi bi-stack"></i> Bulk Entry';
            bulkToggle.classList.remove('btn-primary');
            bulkToggle.classList.add('btn-outline-primary');
        });
    }

    // Handle serial numbering mode change
    if (bulkSerialMode && bulkPrefixContainer) {
        bulkSerialMode.addEventListener('change', function() {
            if (this.value === 'custom') {
                bulkPrefixContainer.style.display = 'block';
            } else {
                bulkPrefixContainer.style.display = 'none';
            }
        });
    }

    // Sync bulk quantity with main quantity input
    if (bulkQuantityInput && quantityInput) {
        bulkQuantityInput.addEventListener('input', function() {
            if (parseInt(this.value) > 1) {
                quantityInput.value = this.value;
            }
        });
    }

}

/**
 * Initialize "Add Another" functionality
 */
function initializeAddAnother() {
    // Add another similar asset function
    window.addAnother = function() {
        const form = document.querySelector('form');
        const categorySelect = document.getElementById('category_id');
        const locationSelect = document.getElementById('sub_location');
        const conditionSelect = document.getElementById('condition_notes');

        if (!form) return;

        // Store current values
        const category = categorySelect?.value;
        const location = locationSelect?.value;
        const condition = conditionSelect?.value;

        // Reset form but keep some values
        form.reset();

        // Restore helpful values
        if (categorySelect && category) categorySelect.value = category;
        if (locationSelect && location) locationSelect.value = location;
        if (conditionSelect && condition) conditionSelect.value = condition;

        const acquiredDateInput = document.getElementById('acquired_date');
        const quantityInput = document.getElementById('quantity');
        const nameInput = document.getElementById('asset_name');

        if (acquiredDateInput) {
            acquiredDateInput.value = new Date().toISOString().split('T')[0];
        }
        if (quantityInput) {
            quantityInput.value = '1';
        }

        // Focus on name field
        if (nameInput) nameInput.focus();
    };

}

/**
 * Initialize today's count display
 */
function initializeTodayCount() {
    const todayCountElement = document.getElementById('todayCount');
    if (todayCountElement) {
        // This would be an AJAX call to get today's count
        todayCountElement.textContent = '0';
    }
}

// Auto-initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLegacyForm);
} else {
    initLegacyForm();
}
