/**
 * Select2-Alpine.js Adapter
 *
 * Provides seamless integration between Select2 dropdowns and Alpine.js reactive data.
 * Handles bidirectional synchronization between Select2 UI and Alpine state.
 *
 * @module assets/utils/select2-alpine-adapter
 * @version 1.0.0
 * @since Phase 4 - Alpine.js Integration
 * @requires jQuery
 * @requires Select2
 * @requires Alpine.js 3.x
 */

/**
 * Sync Alpine.js data with Select2 dropdown
 *
 * This function ensures Select2 dropdowns stay in sync with Alpine.js reactive data
 * by listening to Alpine mutations and updating Select2 accordingly.
 *
 * @param {string} elementId - ID of the select element
 * @param {Object} alpineData - Reference to Alpine.js component data
 * @param {string} alpineProperty - Name of the Alpine property to sync
 */
export function syncSelect2WithAlpine(elementId, alpineData, alpineProperty) {
    if (!window.jQuery || !window.jQuery.fn.select2) {
        console.warn('Select2 not available');
        return;
    }

    const $element = window.jQuery(`#${elementId}`);

    if (!$element.length) {
        console.warn(`Element #${elementId} not found`);
        return;
    }

    // Watch Alpine property changes and update Select2
    if (window.Alpine && alpineData.$watch) {
        alpineData.$watch(alpineProperty, (newValue) => {
            if ($element.val() !== newValue) {
                $element.val(newValue).trigger('change.select2');
            }
        });
    }

    // Listen to Select2 changes and update Alpine
    $element.on('change.select2', function(e) {
        const newValue = jQuery(this).val();
        if (alpineData[alpineProperty] !== newValue) {
            alpineData[alpineProperty] = newValue;
        }
    });

    console.log(`Select2-Alpine sync established for #${elementId}`);
}

/**
 * Initialize Select2 with Alpine.js compatibility
 *
 * This function initializes Select2 on an element and sets up bidirectional
 * sync with Alpine.js reactive data.
 *
 * @param {string} elementId - ID of the select element
 * @param {Object} options - Select2 options
 * @param {Object} alpineData - Reference to Alpine.js component data (optional)
 * @param {string} alpineProperty - Name of the Alpine property to sync (optional)
 * @returns {jQuery} jQuery Select2 element
 */
export function initSelect2WithAlpine(elementId, options = {}, alpineData = null, alpineProperty = null) {
    if (!window.jQuery || !window.jQuery.fn.select2) {
        console.error('Select2 is required');
        return null;
    }

    const $element = window.jQuery(`#${elementId}`);

    if (!$element.length) {
        console.warn(`Element #${elementId} not found`);
        return null;
    }

    // Default Select2 options
    const defaultOptions = {
        theme: 'bootstrap-5',
        allowClear: true,
        width: '100%'
    };

    // Merge with provided options
    const finalOptions = { ...defaultOptions, ...options };

    // Initialize Select2
    $element.select2(finalOptions);

    // Set up Alpine sync if provided
    if (alpineData && alpineProperty) {
        syncSelect2WithAlpine(elementId, alpineData, alpineProperty);
    }

    return $element;
}

/**
 * Update Select2 options while maintaining Alpine.js sync
 *
 * Useful when dynamically populating dropdown options based on Alpine.js data
 *
 * @param {string} elementId - ID of the select element
 * @param {Array} options - Array of option objects [{value, text}, ...]
 * @param {string} placeholder - Placeholder text
 * @param {string} currentValue - Currently selected value (optional)
 */
export function updateSelect2Options(elementId, options, placeholder = 'Select...', currentValue = null) {
    if (!window.jQuery || !window.jQuery.fn.select2) {
        console.warn('Select2 not available');
        return;
    }

    const $element = window.jQuery(`#${elementId}`);

    if (!$element.length) {
        console.warn(`Element #${elementId} not found`);
        return;
    }

    // Clear existing options
    $element.empty();

    // Add placeholder
    $element.append(new Option(placeholder, '', true, false));

    // Add new options
    options.forEach(option => {
        const newOption = new Option(option.text, option.value, false, false);

        // Add data attributes if provided
        if (option.data) {
            Object.keys(option.data).forEach(key => {
                newOption.setAttribute(`data-${key}`, option.data[key]);
            });
        }

        $element.append(newOption);
    });

    // Restore selected value if provided
    if (currentValue) {
        $element.val(currentValue);
    }

    // Trigger change to update Select2
    $element.trigger('change');
}

/**
 * Destroy Select2 and clean up Alpine.js sync
 *
 * @param {string} elementId - ID of the select element
 */
export function destroySelect2(elementId) {
    if (!window.jQuery || !window.jQuery.fn.select2) {
        return;
    }

    const $element = window.jQuery(`#${elementId}`);

    if ($element.data('select2')) {
        $element.off('change.select2');
        $element.select2('destroy');
    }
}

/**
 * Check if Select2 is initialized on element
 *
 * @param {string} elementId - ID of the select element
 * @returns {boolean} True if Select2 is initialized
 */
export function isSelect2Initialized(elementId) {
    if (!window.jQuery || !window.jQuery.fn.select2) {
        return false;
    }

    const $element = window.jQuery(`#${elementId}`);
    return $element.length > 0 && $element.hasClass('select2-hidden-accessible');
}

/**
 * Get Select2 value
 *
 * @param {string} elementId - ID of the select element
 * @returns {string|null} Selected value or null
 */
export function getSelect2Value(elementId) {
    if (!window.jQuery) {
        return null;
    }

    const $element = window.jQuery(`#${elementId}`);
    return $element.val();
}

/**
 * Set Select2 value programmatically
 *
 * @param {string} elementId - ID of the select element
 * @param {string} value - Value to set
 * @param {boolean} triggerChange - Whether to trigger change event (default: true)
 */
export function setSelect2Value(elementId, value, triggerChange = true) {
    if (!window.jQuery) {
        return;
    }

    const $element = window.jQuery(`#${elementId}`);

    if ($element.length) {
        $element.val(value);

        if (triggerChange) {
            $element.trigger('change');
        }
    }
}
