/**
 * Brand Validation Module
 *
 * Validates brand names against known brands database,
 * provides suggestions, and allows brand submission for review.
 *
 * @module assets/features/brand-validation
 * @version 3.0.0
 * @since Phase 3 Refactoring - Inline JavaScript Extraction
 */

import { csrfToken } from '../core/asset-form-base.js';
import { debounce } from '../core/asset-form-base.js';

/**
 * Initialize brand validation system
 *
 * @param {Object} options - Configuration options
 * @param {string} options.brandInputId - ID of brand input element
 */
export function initializeBrandValidation(options = {}) {
    const {
        brandInputId = 'brand'
    } = options;

    const brandInput = document.getElementById(brandInputId);
    const brandIcon = document.getElementById('brand-icon');
    const brandFeedback = document.getElementById('brand-feedback');

    if (!brandInput) {
        console.warn('Brand input element not found');
        return;
    }

    let brandValidationTimeout;

    const debouncedValidation = debounce((brand) => {
        validateBrand(brand);
    }, 500);

    brandInput.addEventListener('input', function() {
        const brand = this.value.trim();

        // Clear previous timeout
        clearTimeout(brandValidationTimeout);

        if (brand.length === 0) {
            resetBrandStatus();
            return;
        }

        // Update icon to loading state
        if (brandIcon) {
            brandIcon.className = 'bi bi-arrow-clockwise text-primary';
        }
        if (brandFeedback) {
            brandFeedback.textContent = 'Validating brand...';
            brandFeedback.className = 'form-text text-muted';
        }

        // Validate after debounce
        debouncedValidation(brand);
    });

}

/**
 * Validate brand name against database
 *
 * @param {string} brand - Brand name to validate
 * @returns {Promise<Object>} - Validation result
 */
export async function validateBrand(brand) {
    const brandIcon = document.getElementById('brand-icon');
    const brandFeedback = document.getElementById('brand-feedback');

    try {
        const response = await fetch('?route=api/assets/validate-brand', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ brand })
        });

        const data = await response.json();

        if (data.success) {
            if (data.status === 'verified') {
                // Brand is verified
                if (brandIcon) brandIcon.className = 'bi bi-check-circle text-success';
                if (brandFeedback) {
                    brandFeedback.innerHTML = `<i class="bi bi-check-circle text-success me-1"></i>Verified brand: ${data.standardized_name}`;
                    brandFeedback.className = 'form-text text-success';
                }

                // Update hidden fields
                updateBrandHiddenFields(data.standardized_name, data.brand_id);

            } else if (data.status === 'unknown') {
                // Brand is unknown - offer suggestion option
                if (brandIcon) brandIcon.className = 'bi bi-question-circle text-warning';
                if (brandFeedback) {
                    brandFeedback.innerHTML = `
                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                        Unknown brand. Would you like to
                        <button type="button" class="btn btn-link p-0 align-baseline" onclick="window.suggestBrand('${brand.replace(/'/g, "\\'")}')">
                            suggest it for review?
                        </button>
                    `;
                    brandFeedback.className = 'form-text text-warning';
                }

                // Clear hidden fields
                updateBrandHiddenFields('', '');
            }
        } else {
            // Validation error
            if (brandIcon) brandIcon.className = 'bi bi-exclamation-triangle text-warning';
            if (brandFeedback) {
                brandFeedback.innerHTML = `<i class="bi bi-exclamation-triangle text-warning me-1"></i>${data.message || 'Brand validation failed'}`;
                brandFeedback.className = 'form-text text-warning';
            }
        }

        return data;
    } catch (error) {
        console.warn('Brand validation error:', error);
        if (brandIcon) brandIcon.className = 'bi bi-exclamation-triangle text-warning';
        if (brandFeedback) {
            brandFeedback.innerHTML = '<i class="bi bi-exclamation-triangle text-warning me-1"></i>Validation temporarily unavailable';
            brandFeedback.className = 'form-text text-warning';
        }
        return { success: false, error: error.message };
    }
}

/**
 * Suggest new brand for review
 *
 * @param {string} brandName - Brand name to suggest
 * @returns {Promise<Object>} - Suggestion result
 */
export async function suggestBrand(brandName) {
    const userConfirmed = confirm(
        `Would you like to suggest "${brandName}" as a new brand for review?\n\n` +
        'This will notify the Asset Director for approval. You can continue creating the asset while the brand is under review.'
    );

    if (!userConfirmed) {
        return { success: false, cancelled: true };
    }

    try {
        const response = await fetch('?route=api/assets/suggest-brand', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                brand_name: brandName,
                context: 'Asset Creation Form'
            })
        });

        const data = await response.json();

        if (data.success) {
            // Update feedback to show suggestion was submitted
            const brandFeedback = document.getElementById('brand-feedback');
            const brandIcon = document.getElementById('brand-icon');

            if (brandIcon) brandIcon.className = 'bi bi-clock text-info';
            if (brandFeedback) {
                brandFeedback.innerHTML = `
                    <i class="bi bi-check-circle text-success me-1"></i>
                    Brand suggestion submitted for review. You can continue creating the asset.
                `;
                brandFeedback.className = 'form-text text-success';
            }

            // Set temporary values for form submission
            updateBrandHiddenFields(brandName, 'pending');
        } else {
            alert('Failed to submit brand suggestion: ' + (data.message || 'Unknown error'));
        }

        return data;
    } catch (error) {
        console.error('Error suggesting brand:', error);
        alert('Failed to submit brand suggestion. Please try again.');
        return { success: false, error: error.message };
    }
}

/**
 * Reset brand validation status
 */
export function resetBrandStatus() {
    const brandIcon = document.getElementById('brand-icon');
    const brandFeedback = document.getElementById('brand-feedback');

    if (brandIcon) brandIcon.className = 'bi bi-question-circle text-muted';
    if (brandFeedback) {
        brandFeedback.textContent = 'Start typing for brand suggestions and validation';
        brandFeedback.className = 'form-text';
    }

    // Clear hidden fields
    updateBrandHiddenFields('', '');
}

/**
 * Update brand hidden fields
 *
 * @param {string} standardizedName - Standardized brand name
 * @param {string} brandId - Brand ID
 */
function updateBrandHiddenFields(standardizedName, brandId) {
    const standardizedBrandField = document.getElementById('standardized_brand');
    const brandIdField = document.getElementById('brand_id');

    if (standardizedBrandField) standardizedBrandField.value = standardizedName;
    if (brandIdField) brandIdField.value = brandId;
}

// Expose suggestBrand to window for onclick handler
if (typeof window !== 'undefined') {
    window.suggestBrand = suggestBrand;
}
