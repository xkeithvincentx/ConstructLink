/**
 * ConstructLink™ Request Form Validation Module
 *
 * Provides accessible, unobtrusive form validation for request forms.
 * Replaces inline validation with proper ARIA announcements.
 *
 * @module RequestFormValidation
 * @version 2.0.0
 */

(function() {
    'use strict';

    /**
     * Display validation error with accessibility support
     * @param {HTMLElement} field - Form field element
     * @param {string} message - Error message
     */
    function showFieldError(field, message) {
        // Remove existing error if present
        hideFieldError(field);

        // Add invalid class
        field.classList.add('is-invalid');

        // Create error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.setAttribute('role', 'alert');
        errorDiv.setAttribute('aria-live', 'polite');
        errorDiv.textContent = message;
        errorDiv.id = `${field.id}-error`;

        // Insert error after field
        field.parentNode.insertBefore(errorDiv, field.nextSibling);

        // Set ARIA attributes
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', errorDiv.id);

        // Focus on field for accessibility
        field.focus();
    }

    /**
     * Hide validation error
     * @param {HTMLElement} field - Form field element
     */
    function hideFieldError(field) {
        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');

        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    /**
     * Validate approval/review form
     * @param {Event} e - Submit event
     * @returns {boolean} - Whether form is valid
     */
    function validateApprovalForm(e) {
        const form = e.target;
        const action = form.querySelector('#action');
        const remarks = form.querySelector('#remarks');
        const declineReason = form.querySelector('#decline_reason');

        let isValid = true;

        // Clear previous errors
        form.querySelectorAll('.is-invalid').forEach(field => hideFieldError(field));

        // Validate action selection
        if (!action.value) {
            showFieldError(action, 'Please select a decision.');
            isValid = false;
        }

        // Validate remarks
        if (!remarks.value.trim()) {
            showFieldError(remarks, 'Please provide comments.');
            isValid = false;
        } else if (remarks.value.trim().length < 10) {
            showFieldError(remarks, 'Please provide more detailed comments (at least 10 characters).');
            isValid = false;
        }

        // Validate decline reason if declining
        if (action.value === 'decline' && declineReason && !declineReason.value) {
            showFieldError(declineReason, 'Please select a decline reason.');
            isValid = false;
        }

        // Prevent submission if invalid
        if (!isValid) {
            e.preventDefault();
            return false;
        }

        // Confirmation dialog
        const actionText = action.value === 'approve' ? 'approve' :
                          action.value === 'decline' ? 'decline' :
                          action.value === 'forward' ? 'forward' : 'review';

        if (!confirm(`Are you sure you want to ${actionText} this request?`)) {
            e.preventDefault();
            return false;
        }

        return true;
    }

    /**
     * Validate create request form
     * @param {Event} e - Submit event
     * @returns {boolean} - Whether form is valid
     */
    function validateCreateForm(e) {
        const form = e.target;
        const projectId = form.querySelector('#project_id');
        const requestType = form.querySelector('#request_type');
        const description = form.querySelector('#description');

        let isValid = true;

        // Clear previous errors
        form.querySelectorAll('.is-invalid').forEach(field => hideFieldError(field));

        // Validate project selection
        if (!projectId.value) {
            showFieldError(projectId, 'Please select a project.');
            isValid = false;
        }

        // Validate request type
        if (!requestType.value) {
            showFieldError(requestType, 'Please select a request type.');
            isValid = false;
        }

        // Validate description
        if (!description.value.trim()) {
            showFieldError(description, 'Please provide a detailed description.');
            isValid = false;
        } else if (description.value.trim().length < 10) {
            showFieldError(description, 'Please provide a more detailed description (at least 10 characters).');
            isValid = false;
        }

        // Prevent submission if invalid
        if (!isValid) {
            e.preventDefault();
            return false;
        }

        return true;
    }

    /**
     * Initialize form validation
     */
    function init() {
        // Approval form validation
        const approvalForm = document.getElementById('approvalForm');
        if (approvalForm) {
            approvalForm.addEventListener('submit', validateApprovalForm);
        }

        // Review form validation
        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', validateApprovalForm); // Uses same validation
        }

        // Create request form validation
        const requestForm = document.getElementById('requestForm');
        if (requestForm) {
            requestForm.addEventListener('submit', validateCreateForm);
        }

        // Real-time validation on blur
        const requiredFields = document.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                if (!this.value.trim() && this.hasAttribute('required')) {
                    showFieldError(this, 'This field is required.');
                } else {
                    hideFieldError(this);
                }
            });
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
