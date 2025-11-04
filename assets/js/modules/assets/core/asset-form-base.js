/**
 * Asset Form Base Module
 *
 * Shared functionality for both create and legacy asset forms.
 * Contains validation, dropdown management, AJAX helpers, and UI utilities.
 *
 * @module assets/core/asset-form-base
 * @version 3.0.0
 * @since Phase 3 Refactoring - Inline JavaScript Extraction
 * @requires Bootstrap 5.x
 * @requires jQuery 3.x (for Select2 compatibility)
 */

/**
 * CSRF Token for API calls
 * @type {string}
 */
export const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';

/**
 * Enhanced form validation with error summary and accessibility
 *
 * @param {HTMLFormElement} form - The form element to validate
 * @returns {boolean} - True if form is valid
 */
export function initializeFormValidation(form) {
    if (!form) {
        console.warn('Form element not provided for validation');
        return false;
    }

    form.addEventListener('submit', function(event) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        const errors = [];

        requiredFields.forEach(field => {
            if (!field.value || !field.value.trim()) {
                field.classList.add('is-invalid');
                field.classList.remove('is-valid');
                isValid = false;

                const label = document.querySelector(`label[for="${field.id}"]`);
                const fieldName = label ? label.textContent.replace('*', '').trim() : field.name || 'Field';
                errors.push({ field, fieldName });
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });

        if (!isValid) {
            event.preventDefault();
            event.stopPropagation();
            showErrorSummary(errors);
        }

        return isValid;
    });

    // Real-time validation feedback
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });

        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid') && this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                updateErrorSummary();
            }
        });
    });

    return true;
}

/**
 * Show error summary with jump links to invalid fields
 *
 * @param {Array<{field: HTMLElement, fieldName: string}>} errors - Array of error objects
 */
function showErrorSummary(errors) {
    const errorSummary = document.getElementById('error-summary');
    const errorList = document.getElementById('error-list');

    if (!errorSummary || !errorList || errors.length === 0) {
        return;
    }

    // Clear previous errors
    errorList.innerHTML = '';

    // Build error list with jump links
    errors.forEach(({ field, fieldName }) => {
        const li = document.createElement('li');
        const link = document.createElement('a');
        link.href = `#${field.id}`;
        link.className = 'alert-link text-decoration-underline';
        link.textContent = `${fieldName} is required`;
        link.addEventListener('click', function(e) {
            e.preventDefault();
            field.focus();
            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        li.appendChild(link);
        errorList.appendChild(li);
    });

    // Show error summary and scroll to it
    errorSummary.classList.remove('d-none');
    errorSummary.scrollIntoView({ behavior: 'smooth', block: 'start' });
    errorSummary.focus();
}

/**
 * Update error summary when user corrects errors
 */
function updateErrorSummary() {
    const errorSummary = document.getElementById('error-summary');
    if (!errorSummary || errorSummary.classList.contains('d-none')) {
        return;
    }

    const invalidFields = document.querySelectorAll('.is-invalid');
    if (invalidFields.length === 0) {
        errorSummary.classList.add('d-none');
    }
}

/**
 * Load subtypes for selected equipment type
 *
 * @param {number|string} equipmentTypeId - Equipment type ID
 * @returns {Promise<Array>} - Promise resolving to array of subtypes
 */
export async function loadSubtypes(equipmentTypeId) {
    const subtypeSelect = document.getElementById('subtype_id');

    if (!equipmentTypeId) {
        subtypeSelect.innerHTML = '<option value="">Select Subtype</option>';
        subtypeSelect.removeAttribute('required');
        const subtypeAsterisk = document.getElementById('subtype-required-asterisk');
        if (subtypeAsterisk) subtypeAsterisk.style.display = 'none';

        // Update Select2 if initialized
        if (window.jQuery && window.jQuery('#subtype_id').hasClass('select2-hidden-accessible')) {
            window.jQuery('#subtype_id').empty();
            window.jQuery('#subtype_id').append(new Option('Select Subtype', '', true, false));
            window.jQuery('#subtype_id').trigger('change');
        }

        return [];
    }

    subtypeSelect.innerHTML = '<option value="">Loading...</option>';

    try {
        const response = await fetch(`?route=api/intelligent-naming&action=subtypes&equipment_type_id=${equipmentTypeId}`);

        if (!response.ok) {
            throw new Error(`API returned ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        subtypeSelect.innerHTML = '<option value="">Select Subtype</option>';

        if (data.success && data.data) {
            const subtypeAsterisk = document.getElementById('subtype-required-asterisk');

            if (data.data.length === 0) {
                subtypeSelect.innerHTML = '<option value="">No subtypes available</option>';
                subtypeSelect.removeAttribute('required');
                if (subtypeAsterisk) subtypeAsterisk.style.display = 'none';

                // Update Select2 if initialized
                if (window.jQuery && window.jQuery('#subtype_id').hasClass('select2-hidden-accessible')) {
                    window.jQuery('#subtype_id').empty();
                    window.jQuery('#subtype_id').append(new Option('No subtypes available', '', true, false));
                    window.jQuery('#subtype_id').trigger('change');
                }

                return [];
            } else {
                subtypeSelect.setAttribute('required', 'required');
                if (subtypeAsterisk) subtypeAsterisk.style.display = 'inline';
            }

            data.data.forEach(subtype => {
                const option = document.createElement('option');
                option.value = subtype.id;
                option.textContent = subtype.subtype_name;
                option.setAttribute('data-material', subtype.material_type || '');
                option.setAttribute('data-power', subtype.power_source || '');
                option.setAttribute('data-application', subtype.application_area || '');
                option.setAttribute('data-size', subtype.size_category || '');
                subtypeSelect.appendChild(option);
            });

            // Update Select2 if initialized - CRITICAL for dropdown to show new options
            if (window.jQuery && window.jQuery('#subtype_id').hasClass('select2-hidden-accessible')) {
                // Clear the Select2 data and repopulate
                window.jQuery('#subtype_id').empty();

                // Rebuild options
                window.jQuery('#subtype_id').append(new Option('Select Subtype', '', true, false));
                data.data.forEach(subtype => {
                    window.jQuery('#subtype_id').append(new Option(subtype.subtype_name, subtype.id, false, false));
                });

                // Trigger change to update Select2
                window.jQuery('#subtype_id').trigger('change');
            }

            return data.data;
        }

        return [];
    } catch (error) {
        console.error('Error loading subtypes:', error);
        subtypeSelect.innerHTML = '<option value="">Error loading subtypes</option>';

        // Update Select2 with error state
        if (window.jQuery && window.jQuery('#subtype_id').hasClass('select2-hidden-accessible')) {
            window.jQuery('#subtype_id').empty();
            window.jQuery('#subtype_id').append(new Option('Error loading subtypes', '', true, false));
            window.jQuery('#subtype_id').trigger('change');
        }

        return [];
    }
}

/**
 * Load all equipment types for intelligent search
 *
 * @returns {Promise<Array>} - Promise resolving to array of all equipment types
 */
export async function loadAllEquipmentTypes() {
    try {
        const response = await fetch(`?route=api/intelligent-naming&action=all-equipment-types`);
        const data = await response.json();

        if (data.success && data.data) {
            return data.data;
        }

        return [];
    } catch (error) {
        console.error('Error loading all equipment types:', error);
        return [];
    }
}

/**
 * Populate equipment type dropdown with filtered types
 *
 * @param {HTMLSelectElement} selectElement - The select element to populate
 * @param {Array} equipmentTypes - Array of equipment type objects
 * @param {string} placeholder - Placeholder text for empty option
 */
export function populateEquipmentTypeDropdown(selectElement, equipmentTypes, placeholder = 'Select Equipment Type') {
    if (!selectElement) {
        console.warn('Select element not provided');
        return;
    }

    selectElement.innerHTML = `<option value="">${placeholder}</option>`;

    equipmentTypes.forEach(type => {
        const option = document.createElement('option');
        option.value = type.id;
        option.textContent = type.name;
        if (placeholder.includes('search')) {
            option.textContent += ` (${type.category_name})`;
        }
        option.dataset.categoryId = type.category_id;
        option.dataset.categoryName = type.category_name;
        if (type.description) {
            option.title = type.description;
        }
        selectElement.appendChild(option);
    });

}

/**
 * Filter equipment types by category
 *
 * @param {Array} allEquipmentTypes - All available equipment types
 * @param {number|string} categoryId - Category ID to filter by
 * @returns {Array} - Filtered equipment types
 */
export function filterEquipmentTypesByCategory(allEquipmentTypes, categoryId) {
    if (!categoryId) {
        return allEquipmentTypes;
    }

    const filtered = allEquipmentTypes.filter(type => type.category_id == categoryId);
    return filtered;
}

/**
 * Clear subtypes dropdown
 */
export function clearSubtypes() {
    const subtypeSelect = document.getElementById('subtype_id');
    if (subtypeSelect) {
        subtypeSelect.innerHTML = '<option value="">Select Subtype</option>';
        subtypeSelect.removeAttribute('required');
        const subtypeAsterisk = document.getElementById('subtype-required-asterisk');
        if (subtypeAsterisk) subtypeAsterisk.style.display = 'none';
    }
}

/**
 * Update quantity field behavior based on category (consumable vs non-consumable)
 *
 * @param {string|number} categoryId - Category ID (optional, will fetch from category select if not provided)
 */
export async function updateQuantityHandling(categoryId = null) {
    const categorySelect = document.getElementById('category_id');
    const quantityInput = document.getElementById('quantity');
    const quantityConsumableText = document.getElementById('quantity-consumable-text');
    const quantityNonConsumableText = document.getElementById('quantity-non-consumable-text');

    if (!categorySelect || !quantityInput) return;

    const selectedCategoryId = categoryId || categorySelect.value;

    if (!selectedCategoryId) {
        quantityInput.disabled = false;
        if (quantityConsumableText) quantityConsumableText.style.display = 'none';
        if (quantityNonConsumableText) quantityNonConsumableText.style.display = 'inline';
        return;
    }

    // TODO: API endpoint 'api/categories/details' not yet implemented
    // For now, default to allowing quantity input (user can modify as needed)
    try {
        // Temporary: Enable quantity input by default until API is available
        quantityInput.disabled = false;
        quantityInput.min = 1;
        if (quantityConsumableText) quantityConsumableText.style.display = 'none';
        if (quantityNonConsumableText) quantityNonConsumableText.style.display = 'inline';

        // When API is ready, uncomment and use:
        // const response = await fetch(`?route=api/categories/details&id=${selectedCategoryId}`);
        // const data = await response.json();
        // ... handle consumable logic
    } catch (error) {
        console.error('Error in category-based quantity handling:', error);
        quantityInput.value = 1;
        quantityInput.disabled = true;
    }
}

/**
 * Update intelligent unit based on equipment type
 *
 * @param {number|string} equipmentTypeId - Equipment type ID
 * @param {number|string|null} subtypeId - Subtype ID (optional)
 * @returns {Promise<string|null>} - Selected unit or null
 */
export async function updateIntelligentUnit(equipmentTypeId, subtypeId = null) {

    if (!equipmentTypeId) return null;

    const unitSelect = document.getElementById('unit');
    if (!unitSelect) return null;

    const params = new URLSearchParams({
        action: 'intelligent-unit',
        equipment_type_id: equipmentTypeId
    });

    if (subtypeId) {
        params.append('subtype_id', subtypeId);
    }

    try {
        const response = await fetch(`?route=api/intelligent-naming&${params}`);
        const data = await response.json();

        if (data.success && data.data && data.data.unit) {
            const suggestedUnit = data.data.unit;

            const optionExists = Array.from(unitSelect.options).some(option => option.value === suggestedUnit);

            if (optionExists) {
                unitSelect.value = suggestedUnit;
                return suggestedUnit;
            } else {
                console.warn('Suggested unit not found in dropdown:', suggestedUnit);
            }
        }

        return null;
    } catch (error) {
        console.error('Error getting intelligent unit:', error);
        return null;
    }
}

/**
 * Handle procurement order selection and load items
 *
 * @param {string|number} procurementOrderId - Procurement order ID
 * @returns {Promise<Array>} - Array of procurement items
 */
export async function loadProcurementItems(procurementOrderId) {
    const procurementItemSelect = document.getElementById('procurement_item_id');

    if (!procurementItemSelect) {
        console.warn('Procurement item select not found');
        return [];
    }

    procurementItemSelect.innerHTML = '<option value="">Loading items...</option>';

    try {
        const response = await fetch(`?route=api/procurement-orders/items&id=${procurementOrderId}`);
        const data = await response.json();

        procurementItemSelect.innerHTML = '<option value="">Select Item</option>';

        if (data.success && data.items) {
            data.items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = `${item.item_name} - ₱${parseFloat(item.unit_price).toFixed(2)} (Qty: ${item.quantity})`;
                option.setAttribute('data-cost', item.unit_price);
                option.setAttribute('data-name', item.item_name);
                option.setAttribute('data-brand', item.brand || '');
                option.setAttribute('data-model', item.model || '');
                option.setAttribute('data-specifications', item.specifications || '');
                option.setAttribute('data-category-id', item.category_id || '');
                procurementItemSelect.appendChild(option);
            });

            return data.items;
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No items available';
            procurementItemSelect.appendChild(option);
            return [];
        }
    } catch (error) {
        console.error('Error loading procurement items:', error);
        procurementItemSelect.innerHTML = '<option value="">Error loading items</option>';
        return [];
    }
}

/**
 * Auto-populate form fields from procurement item selection
 *
 * @param {HTMLOptionElement} selectedOption - Selected option element
 */
export function autopopulateFromProcurementItem(selectedOption) {
    if (!selectedOption || !selectedOption.value) return;

    const itemCost = selectedOption.getAttribute('data-cost');
    const itemName = selectedOption.getAttribute('data-name');
    const itemBrand = selectedOption.getAttribute('data-brand');
    const itemModel = selectedOption.getAttribute('data-model');
    const itemSpecs = selectedOption.getAttribute('data-specifications');
    const categoryId = selectedOption.getAttribute('data-category-id');

    const nameInput = document.getElementById('name');
    const modelInput = document.getElementById('model');
    const specsInput = document.getElementById('specifications');
    const categorySelect = document.getElementById('category_id');
    const acquisitionCostInput = document.getElementById('acquisition_cost');
    const unitCostInput = document.getElementById('unit_cost');

    if (itemName && nameInput && !nameInput.value) {
        let assetName = itemName;
        if (itemBrand) {
            assetName = `${itemBrand} ${itemName}`;
        }
        nameInput.value = assetName;
    }

    if (itemModel && modelInput && !modelInput.value) {
        modelInput.value = itemModel;
    }

    if (itemSpecs && specsInput && !specsInput.value) {
        specsInput.value = itemSpecs;
    }

    if (categoryId && categorySelect && !categorySelect.value) {
        categorySelect.value = categoryId;
        if (window.jQuery && window.jQuery('#category_id').hasClass('select2-hidden-accessible')) {
            window.jQuery('#category_id').val(categoryId).trigger('change');
        }
    }

    if (itemCost) {
        if (acquisitionCostInput && !acquisitionCostInput.value) {
            acquisitionCostInput.value = itemCost;
        }
        if (unitCostInput && !unitCostInput.value) {
            unitCostInput.value = itemCost;
        }
    }
}

/**
 * Debounce utility function
 *
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} - Debounced function
 */
export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Initialize auto-resize for textareas
 *
 * @param {string|null} selector - CSS selector for textareas (null = all textareas)
 */
export function initializeTextareaAutoResize(selector = null) {
    const textareas = selector ? document.querySelectorAll(selector) : document.querySelectorAll('textarea');

    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
}

/**
 * Show loading state on submit button
 *
 * @param {HTMLButtonElement} submitButton - Submit button element
 */
export function showSubmitLoading(submitButton) {
    if (!submitButton) return;

    submitButton.disabled = true;
    const btnContent = submitButton.querySelector('.btn-content');
    const spinner = submitButton.querySelector('.spinner-border');

    if (btnContent) btnContent.classList.add('d-none');
    if (spinner) spinner.classList.remove('d-none');
}

/**
 * Hide loading state on submit button
 *
 * @param {HTMLButtonElement} submitButton - Submit button element
 */
export function hideSubmitLoading(submitButton) {
    if (!submitButton) return;

    submitButton.disabled = false;
    const btnContent = submitButton.querySelector('.btn-content');
    const spinner = submitButton.querySelector('.spinner-border');

    if (btnContent) btnContent.classList.remove('d-none');
    if (spinner) spinner.classList.add('d-none');
}

/**
 * Fetch equipment type details with category information
 *
 * @param {number|string} equipmentTypeId - Equipment type ID
 * @returns {Promise<Object|null>} - Equipment type details or null
 */
export async function fetchEquipmentTypeDetails(equipmentTypeId) {
    if (!equipmentTypeId) return null;

    try {
        const response = await fetch(`?route=api/equipment-type-details&equipment_type_id=${equipmentTypeId}`);
        const data = await response.json();

        if (data.success && data.data) {
            return data.data;
        }

        return null;
    } catch (error) {
        console.error('Error fetching equipment type details:', error);
        return null;
    }
}
