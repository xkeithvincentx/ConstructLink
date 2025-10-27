/**
 * Form Validator - Reusable form validation utilities
 * Provides consistent form validation across the application
 */

const FormValidator = {
    /**
     * Initialize Bootstrap form validation
     *
     * @param {string|jQuery} formSelector - Form selector
     * @param {Object} config - Configuration options
     */
    init: function(formSelector, config = {}) {
        const form = $(formSelector)[0];
        if (!form) return;

        const options = {
            preventSubmit: config.preventSubmit || false,
            onSubmit: config.onSubmit || null,
            customValidators: config.customValidators || {}
        };

        // Add custom validation
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else if (options.preventSubmit || options.onSubmit) {
                event.preventDefault();
                event.stopPropagation();

                if (options.onSubmit && typeof options.onSubmit === 'function') {
                    options.onSubmit(form, event);
                }
            }

            form.classList.add('was-validated');
        }, false);

        // Add custom validators
        Object.keys(options.customValidators).forEach(selector => {
            const validator = options.customValidators[selector];
            const elements = form.querySelectorAll(selector);

            elements.forEach(element => {
                element.addEventListener('input', function() {
                    const isValid = validator(element.value, element);
                    element.setCustomValidity(isValid ? '' : 'Invalid input');
                });
            });
        });
    },

    /**
     * Validate all required checkboxes are checked
     *
     * @param {string|jQuery} formSelector - Form selector
     * @param {string} checkboxSelector - Checkbox selector (default: 'input[type="checkbox"][required]')
     * @returns {boolean} True if all required checkboxes are checked
     */
    validateCheckboxes: function(formSelector, checkboxSelector = 'input[type="checkbox"][required]') {
        const checkboxes = $(formSelector).find(checkboxSelector);
        let allChecked = true;

        checkboxes.each(function() {
            if (!$(this).is(':checked')) {
                allChecked = false;
                return false; // Break loop
            }
        });

        return allChecked;
    },

    /**
     * Show validation error message
     *
     * @param {string|jQuery} fieldSelector - Field selector
     * @param {string} message - Error message
     */
    showError: function(fieldSelector, message) {
        const field = $(fieldSelector);
        field.addClass('is-invalid');

        let feedback = field.siblings('.invalid-feedback');
        if (feedback.length === 0) {
            feedback = $('<div class="invalid-feedback"></div>');
            field.after(feedback);
        }
        feedback.text(message);
    },

    /**
     * Clear validation error message
     *
     * @param {string|jQuery} fieldSelector - Field selector
     */
    clearError: function(fieldSelector) {
        const field = $(fieldSelector);
        field.removeClass('is-invalid');
        field.siblings('.invalid-feedback').remove();
    },

    /**
     * Clear all validation errors in a form
     *
     * @param {string|jQuery} formSelector - Form selector
     */
    clearAllErrors: function(formSelector) {
        const form = $(formSelector);
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').remove();
        form.removeClass('was-validated');
    },

    /**
     * Validate email format
     *
     * @param {string} email - Email address
     * @returns {boolean} True if valid email
     */
    isValidEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    /**
     * Validate phone number format
     *
     * @param {string} phone - Phone number
     * @returns {boolean} True if valid phone
     */
    isValidPhone: function(phone) {
        const re = /^[\d\s\-\+\(\)]+$/;
        return re.test(phone) && phone.replace(/\D/g, '').length >= 10;
    },

    /**
     * Validate date is not in the past
     *
     * @param {string} date - Date string
     * @returns {boolean} True if date is today or future
     */
    isNotPastDate: function(date) {
        const inputDate = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return inputDate >= today;
    },

    /**
     * Validate date is within range
     *
     * @param {string} date - Date string
     * @param {string} minDate - Minimum date
     * @param {string} maxDate - Maximum date
     * @returns {boolean} True if date is within range
     */
    isDateInRange: function(date, minDate, maxDate) {
        const inputDate = new Date(date);
        const min = minDate ? new Date(minDate) : null;
        const max = maxDate ? new Date(maxDate) : null;

        if (min && inputDate < min) return false;
        if (max && inputDate > max) return false;
        return true;
    },

    /**
     * Validate required fields
     *
     * @param {string|jQuery} formSelector - Form selector
     * @returns {boolean} True if all required fields are filled
     */
    validateRequired: function(formSelector) {
        const form = $(formSelector);
        let isValid = true;

        form.find('[required]').each(function() {
            const field = $(this);
            const value = field.val();

            if (!value || value.trim() === '') {
                FormValidator.showError(field, 'This field is required');
                isValid = false;
            } else {
                FormValidator.clearError(field);
            }
        });

        return isValid;
    },

    /**
     * Prevent form double submission
     *
     * @param {string|jQuery} formSelector - Form selector
     * @param {string} buttonSelector - Submit button selector
     */
    preventDoubleSubmit: function(formSelector, buttonSelector = 'button[type="submit"]') {
        $(formSelector).on('submit', function() {
            const button = $(this).find(buttonSelector);
            button.prop('disabled', true);

            // Re-enable after 3 seconds as fallback
            setTimeout(() => {
                button.prop('disabled', false);
            }, 3000);
        });
    },

    /**
     * Add real-time validation to a field
     *
     * @param {string|jQuery} fieldSelector - Field selector
     * @param {Function} validator - Validator function
     * @param {string} errorMessage - Error message
     */
    addRealtimeValidation: function(fieldSelector, validator, errorMessage) {
        $(fieldSelector).on('input change', function() {
            const field = $(this);
            const value = field.val();

            if (validator(value)) {
                FormValidator.clearError(field);
                field.addClass('is-valid');
            } else {
                field.removeClass('is-valid');
                if (value) { // Only show error if field has value
                    FormValidator.showError(field, errorMessage);
                }
            }
        });
    },

    /**
     * Set minimum date for date input
     *
     * @param {string|jQuery} fieldSelector - Field selector
     * @param {Date|string} minDate - Minimum date (default: today)
     */
    setMinDate: function(fieldSelector, minDate = new Date()) {
        const date = minDate instanceof Date ? minDate : new Date(minDate);
        const dateString = date.toISOString().split('T')[0];
        $(fieldSelector).attr('min', dateString);
    },

    /**
     * Set maximum date for date input
     *
     * @param {string|jQuery} fieldSelector - Field selector
     * @param {Date|string} maxDate - Maximum date
     */
    setMaxDate: function(fieldSelector, maxDate) {
        const date = maxDate instanceof Date ? maxDate : new Date(maxDate);
        const dateString = date.toISOString().split('T')[0];
        $(fieldSelector).attr('max', dateString);
    }
};

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormValidator;
}
