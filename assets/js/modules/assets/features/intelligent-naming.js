/**
 * Intelligent Naming Module
 *
 * Handles automatic name generation for assets based on equipment type, subtype, brand, and model.
 * Provides name preview, manual editing toggle, and name components tracking.
 *
 * @module assets/features/intelligent-naming
 * @version 3.0.0
 * @since Phase 3 Refactoring - Inline JavaScript Extraction
 */

import { debounce } from '../core/asset-form-base.js';

/**
 * Current generated name
 * @type {string}
 */
let currentGeneratedName = '';

/**
 * Current name components (for backend processing)
 * @type {Object}
 */
let currentNameComponents = {};

/**
 * Manual edit mode flag
 * @type {boolean}
 */
let isManualEdit = false;

/**
 * Initialize intelligent naming system
 *
 * @param {Object} options - Configuration options
 * @param {string} options.nameInputId - ID of name input field (default: 'name' or 'asset_name')
 * @param {string} options.equipmentTypeId - ID of equipment type select
 * @param {string} options.subtypeId - ID of subtype select
 * @param {string} options.brandId - ID of brand input/select
 * @param {string} options.modelId - ID of model input
 */
export function initializeIntelligentNaming(options = {}) {
    const {
        nameInputId = 'name',
        equipmentTypeId = 'equipment_type_id',
        subtypeId = 'subtype_id',
        brandId = 'brand',
        modelId = 'model'
    } = options;

    const nameInput = document.getElementById(nameInputId) || document.getElementById('asset_name');
    const equipmentTypeSelect = document.getElementById(equipmentTypeId);
    const subtypeSelect = document.getElementById(subtypeId);
    const brandInput = document.getElementById(brandId);
    const modelInput = document.getElementById(modelId);
    const namePreview = document.getElementById('name-preview');
    const previewNameDiv = document.getElementById('preview-name');
    const applyNameBtn = document.getElementById('apply-generated-name') || document.getElementById('use-generated-name');
    const editNameBtn = document.getElementById('edit-name-btn') || document.getElementById('manual-edit-toggle');

    if (!nameInput || !equipmentTypeSelect || !subtypeSelect) {
        console.warn('Required elements for intelligent naming not found');
        return;
    }

    // Initialize name field as read-only if it has auto-generated attribute
    if (nameInput.getAttribute('data-auto-generated') === 'true') {
        nameInput.readOnly = true;
        nameInput.style.backgroundColor = '#f8f9fa';
        nameInput.style.cursor = 'not-allowed';
    }

    // Subtype change - generate name preview
    subtypeSelect.addEventListener('change', function() {
        if (this.value && equipmentTypeSelect.value) {
            generateNamePreview(equipmentTypeSelect.value, this.value, brandInput?.value, modelInput?.value);
        } else {
            clearNamePreview();
        }
    });

    // Brand/Model change - update name preview
    if (brandInput) {
        brandInput.addEventListener('change', function() {
            if (equipmentTypeSelect.value && subtypeSelect.value) {
                generateNamePreview(equipmentTypeSelect.value, subtypeSelect.value, this.value, modelInput?.value);
            }
        });
    }

    if (modelInput) {
        const debouncedGenerate = debounce(() => {
            if (equipmentTypeSelect.value && subtypeSelect.value) {
                generateNamePreview(equipmentTypeSelect.value, subtypeSelect.value, brandInput?.value, modelInput.value);
            }
        }, 500);

        modelInput.addEventListener('input', debouncedGenerate);
    }

    // Apply generated name button
    if (applyNameBtn) {
        applyNameBtn.addEventListener('click', function() {
            applyGeneratedName(nameInput);
        });
    }

    // Manual edit toggle
    if (editNameBtn) {
        editNameBtn.addEventListener('click', function() {
            toggleManualEdit(nameInput, this);
        });
    }

    // Track manual name changes
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            if (isManualEdit) {
                const isCustomNameField = document.getElementById('is_custom_name') || document.getElementById('is_manual_name');
                if (isCustomNameField) {
                    isCustomNameField.value = '1';
                }
            }
        });
    }

}

/**
 * Generate name preview based on equipment type, subtype, brand, and model
 *
 * @param {number|string} equipmentTypeId - Equipment type ID
 * @param {number|string} subtypeId - Subtype ID
 * @param {string} brand - Brand name (optional)
 * @param {string} model - Model name (optional)
 * @returns {Promise<string|null>} - Generated name or null
 */
export async function generateNamePreview(equipmentTypeId, subtypeId, brand = '', model = '') {
    if (!equipmentTypeId || !subtypeId) {
        clearNamePreview();
        return null;
    }

    const params = new URLSearchParams({
        action: 'generate-name',
        equipment_type_id: equipmentTypeId,
        subtype_id: subtypeId,
        brand: brand || '',
        model: model || ''
    });

    try {
        const response = await fetch(`?route=api/intelligent-naming&${params}`);
        const data = await response.json();

        if (data.success && data.data) {
            currentGeneratedName = data.data.generated_name;
            currentNameComponents = data.data.name_components || {};

            const previewNameDiv = document.getElementById('preview-name');
            const namePreview = document.getElementById('name-preview');

            if (previewNameDiv) {
                previewNameDiv.textContent = currentGeneratedName;
            }

            if (namePreview) {
                namePreview.classList.remove('d-none');
                namePreview.style.display = 'block';
            }

            // Auto-populate name field if not in manual edit mode
            if (!isManualEdit) {
                const nameInput = document.getElementById('name') || document.getElementById('asset_name');
                if (nameInput) {
                    nameInput.value = currentGeneratedName;
                    // Remove validation error if present
                    nameInput.classList.remove('is-invalid');
                    nameInput.classList.add('is-valid');
                }
            }

            return currentGeneratedName;
        } else {
            console.error('Failed to generate name:', data.message || 'Unknown error');
            clearNamePreview();
            return null;
        }
    } catch (error) {
        console.error('Error generating name:', error);
        clearNamePreview();
        return null;
    }
}

/**
 * Clear name preview and reset state
 */
export function clearNamePreview() {
    const namePreview = document.getElementById('name-preview');
    const previewNameDiv = document.getElementById('preview-name');

    if (namePreview) {
        namePreview.classList.add('d-none');
        namePreview.style.display = 'none';
    }

    if (previewNameDiv) {
        previewNameDiv.textContent = '';
    }

    currentGeneratedName = '';
    currentNameComponents = {};

    // Clear name field if it was auto-populated
    const nameInput = document.getElementById('name') || document.getElementById('asset_name');
    if (nameInput && nameInput.readOnly && nameInput.value) {
        nameInput.value = '';
    }
}

/**
 * Apply generated name to name input field
 *
 * @param {HTMLInputElement} nameInput - Name input element
 */
function applyGeneratedName(nameInput) {
    if (!currentGeneratedName || !nameInput) {
        console.warn('No generated name available to apply');
        return;
    }

    nameInput.value = currentGeneratedName;
    nameInput.classList.add('is-valid');

    // Update hidden fields
    const generatedNameField = document.getElementById('generated_name');
    const nameComponentsField = document.getElementById('name_components');
    const isCustomNameField = document.getElementById('is_custom_name') || document.getElementById('is_manual_name');

    if (generatedNameField) {
        generatedNameField.value = currentGeneratedName;
    }

    if (nameComponentsField) {
        nameComponentsField.value = JSON.stringify(currentNameComponents);
    }

    if (isCustomNameField) {
        isCustomNameField.value = '0';
    }

    // Hide name preview after applying
    clearNamePreview();

    // Show success feedback
    showApplySuccessFeedback(currentGeneratedName, nameInput);

}

/**
 * Show success feedback when name is applied
 *
 * @param {string} name - Applied name
 * @param {HTMLElement} nameInput - Name input element
 */
function showApplySuccessFeedback(name, nameInput) {
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success alert-dismissible fade show mt-2';
    successDiv.innerHTML = `
        <i class="bi bi-check-circle me-2"></i>
        <strong>Name Applied:</strong> ${name}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    nameInput.parentNode.insertAdjacentElement('afterend', successDiv);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (successDiv.parentNode) {
            successDiv.remove();
        }
    }, 5000);
}

/**
 * Toggle manual edit mode
 *
 * @param {HTMLInputElement} nameInput - Name input element
 * @param {HTMLButtonElement} toggleButton - Toggle button element
 */
function toggleManualEdit(nameInput, toggleButton) {
    isManualEdit = !isManualEdit;

    const isCustomNameField = document.getElementById('is_custom_name') || document.getElementById('is_manual_name');

    if (isManualEdit) {
        // Enable manual editing
        nameInput.readOnly = false;
        nameInput.style.backgroundColor = '';
        nameInput.style.cursor = '';
        nameInput.placeholder = 'Enter custom asset name...';
        nameInput.focus();
        nameInput.select();

        toggleButton.innerHTML = '<i class="bi bi-check"></i>' || '<i class="bi bi-robot"></i>';
        toggleButton.title = 'Confirm manual edit' || 'Switch to auto-generated name';

        if (isCustomNameField) {
            isCustomNameField.value = '1';
        }

    } else {
        // Disable manual editing and restore auto-generated mode
        nameInput.readOnly = true;
        nameInput.style.backgroundColor = '#f8f9fa';
        nameInput.style.cursor = 'not-allowed';
        nameInput.placeholder = 'Select equipment type and subtype to auto-generate name...';

        toggleButton.innerHTML = '<i class="bi bi-pencil"></i>';
        toggleButton.title = 'Edit name manually';

        if (currentGeneratedName) {
            nameInput.value = currentGeneratedName;
            const generatedNameField = document.getElementById('generated_name');
            if (generatedNameField) {
                generatedNameField.value = currentGeneratedName;
            }
        }

        if (isCustomNameField) {
            isCustomNameField.value = '0';
        }

    }
}

/**
 * Get current generated name
 *
 * @returns {string} - Current generated name
 */
export function getCurrentGeneratedName() {
    return currentGeneratedName;
}

/**
 * Get current name components
 *
 * @returns {Object} - Current name components
 */
export function getCurrentNameComponents() {
    return currentNameComponents;
}

/**
 * Check if in manual edit mode
 *
 * @returns {boolean} - True if in manual edit mode
 */
export function isInManualEditMode() {
    return isManualEdit;
}

/**
 * Set manual edit mode
 *
 * @param {boolean} enabled - Enable/disable manual edit mode
 */
export function setManualEditMode(enabled) {
    isManualEdit = enabled;
}
