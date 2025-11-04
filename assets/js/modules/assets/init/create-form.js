/**
 * Create Form Initializer
 *
 * Initializes the standard asset creation form with all features.
 * Orchestrates all modules and sets up event listeners.
 *
 * @module assets/init/create-form
 * @version 3.0.0
 * @since Phase 3 Refactoring - Inline JavaScript Extraction
 */

import { initializeFormValidation, loadProcurementItems, autopopulateFromProcurementItem, updateQuantityHandling, initializeTextareaAutoResize, showSubmitLoading } from '../core/asset-form-base.js';
import { initializeIntelligentNaming, getCurrentGeneratedName } from '../features/intelligent-naming.js';
import { initializeEquipmentClassification } from '../features/equipment-classification.js';
import { initializeDisciplineHandling } from '../features/discipline-handler.js';
import { initializeBrandValidation } from '../features/brand-validation.js';
import { initializeQuickEntry } from '../features/quick-entry.js';
import { initializeClearButtons } from '../ui/clear-buttons.js';
import { initializeDropdownSyncAlpine } from '../features/dropdown-sync-alpine.js';

/**
 * Initialize create form
 */
export function initCreateForm() {

    const form = document.getElementById('assetForm');
    if (!form) {
        console.warn('Create form not found');
        return;
    }

    // Initialize core form validation
    initializeFormValidation(form);

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

            console.log('Brand select2:select event fired');
            console.log('Brand selected:', brandName, 'ID:', brandId);
            console.log('Selected option:', selectedOption);
            console.log('data-brand-id attribute:', selectedOption.attr('data-brand-id'));

            // Populate hidden fields
            jQuery('#brand_id').val(brandId);
            jQuery('#standardized_brand').val(brandName);

            console.log('Hidden fields populated - brand_id:', jQuery('#brand_id').val(), 'standardized_brand:', jQuery('#standardized_brand').val());
        });

        // Also listen to regular change event as backup
        jQuery('#brand').on('change', function() {
            const selectedOption = jQuery(this).find('option:selected');
            const brandId = selectedOption.attr('data-brand-id') || '';
            const brandName = selectedOption.val() || '';

            console.log('Brand change event fired (backup)');

            // Only populate if select2:select didn't already handle it
            if (!jQuery('#brand_id').val()) {
                jQuery('#brand_id').val(brandId);
                jQuery('#standardized_brand').val(brandName);
                console.log('Backup handler populated fields - brand_id:', brandId);
            }
        });

        // Handle clear event
        jQuery('#brand').on('select2:clear', function() {
            console.log('Brand cleared');
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
            console.log('Brand initialized:', currentBrandValue, 'ID:', brandId);
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

        jQuery('#procurement_order_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Procurement Order (Optional)',
            allowClear: true,
            width: '100%'
        });

        jQuery('#procurement_item_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Item',
            allowClear: true,
            width: '100%'
        });

        jQuery('#vendor_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Vendor (Optional)',
            allowClear: true,
            width: '100%'
        });
    }

    // Initialize equipment classification (with category auto-selection)
    initializeEquipmentClassification({
        categorySelectId: 'category_id',
        equipmentTypeSelectId: 'equipment_type_id',
        enableCategoryAutoSelection: true
    });

    // Initialize intelligent naming
    initializeIntelligentNaming({
        nameInputId: 'name',
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

    // Initialize quick entry
    initializeQuickEntry({
        containerId: 'quick-entry-container',
        isLegacyForm: false
    });

    // Initialize clear buttons
    initializeClearButtons({
        clearCategoryBtnId: 'clear-category-btn',
        clearEquipmentBtnId: 'clear-equipment-btn'
    });

    // Initialize procurement order handling
    initializeProcurementHandling();

    // Initialize category quantity handling
    initializeCategoryQuantityHandling();

    // Initialize form submission enhancements
    initializeFormSubmission(form);

    // Initialize textarea auto-resize
    initializeTextareaAutoResize();

}

/**
 * Initialize procurement order handling
 */
function initializeProcurementHandling() {
    const procurementOrderSelect = document.getElementById('procurement_order_id');
    const procurementItemContainer = document.getElementById('procurement_item_container');
    const procurementItemSelect = document.getElementById('procurement_item_id');
    const vendorSelect = document.getElementById('vendor_id');
    const acquisitionCostInput = document.getElementById('acquisition_cost');
    const unitCostInput = document.getElementById('unit_cost');

    if (!procurementOrderSelect || !procurementItemSelect) {
        return;
    }

    // Handle procurement order selection
    procurementOrderSelect.addEventListener('change', async function() {
        const selectedOption = this.options[this.selectedIndex];
        const vendorName = selectedOption.getAttribute('data-vendor');
        const vendorId = selectedOption.getAttribute('data-vendor-id');

        if (this.value) {
            // Auto-populate vendor if available
            if (vendorId && vendorSelect) {
                vendorSelect.value = vendorId;
            }

            // Show procurement item selection
            if (procurementItemContainer) {
                procurementItemContainer.style.display = 'block';
                procurementItemSelect.required = true;
            }

            await loadProcurementItems(this.value);
        } else {
            if (procurementItemContainer) {
                procurementItemContainer.style.display = 'none';
                procurementItemSelect.required = false;
            }
            procurementItemSelect.innerHTML = '<option value="">Select Item</option>';
        }
    });

    // Handle procurement item selection
    procurementItemSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            autopopulateFromProcurementItem(selectedOption);
        }
    });

    // Auto-populate unit cost from acquisition cost
    if (acquisitionCostInput && unitCostInput) {
        acquisitionCostInput.addEventListener('input', function() {
            if (this.value && !unitCostInput.value) {
                unitCostInput.value = this.value;
            }
        });
    }

    // Initialize procurement item container visibility
    if (procurementOrderSelect.value && procurementItemContainer) {
        procurementItemContainer.style.display = 'block';
        procurementItemSelect.required = true;
        loadProcurementItems(procurementOrderSelect.value);
    }

}

/**
 * Initialize category quantity handling
 */
function initializeCategoryQuantityHandling() {
    const categorySelect = document.getElementById('category_id');
    const quantityInput = document.getElementById('quantity');

    if (!categorySelect || !quantityInput) {
        return;
    }

    // Handle category selection for quantity behavior
    categorySelect.addEventListener('change', function() {
        updateQuantityHandling(this.value);
    });

    // Initialize quantity field based on current category selection
    if (categorySelect.value) {
        updateQuantityHandling(categorySelect.value);
    }

}

/**
 * Initialize form submission enhancements
 *
 * @param {HTMLFormElement} form - Form element
 */
function initializeFormSubmission(form) {
    const submitBtn = document.getElementById('submit-btn');
    const nameField = document.getElementById('name');

    form.addEventListener('submit', function(e) {
        // CRITICAL: Ensure name field has a value before submission
        if (nameField && !nameField.value.trim()) {
            const currentGeneratedName = getCurrentGeneratedName();
            if (currentGeneratedName) {
                console.log('Using generated name:', currentGeneratedName);
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
                    console.log('Using fallback name from equipment/subtype:', fallbackName);
                    nameField.value = fallbackName;
                } else if (equipmentTypeSelect && equipmentTypeSelect.value) {
                    const equipmentText = equipmentTypeSelect.options[equipmentTypeSelect.selectedIndex].textContent;
                    const fallbackName = equipmentText + ' - Asset';
                    console.log('Using fallback name from equipment:', fallbackName);
                    nameField.value = fallbackName;
                } else if (categorySelect && categorySelect.value) {
                    const categoryText = categorySelect.options[categorySelect.selectedIndex].textContent;
                    const fallbackName = categoryText + ' - Asset';
                    console.log('Using fallback name from category:', fallbackName);
                    nameField.value = fallbackName;
                } else {
                    // Absolute fallback
                    console.warn('No name data available, using generic name');
                    nameField.value = 'Asset - ' + new Date().getTime();
                }
            }
        }

        // Remove readonly attribute before submission to ensure value is sent
        if (nameField && nameField.readOnly) {
            nameField.readOnly = false;
        }

        // Show loading state if form is valid
        const isValid = form.checkValidity();
        if (isValid && submitBtn) {
            showSubmitLoading(submitBtn);
        }
    });

}

// Auto-initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCreateForm);
} else {
    initCreateForm();
}
