/**
 * ConstructLink™ Authentication Module JavaScript
 * Purpose: External JavaScript for authentication views
 * Standards: ES6+, Accessibility-focused, No inline event handlers
 * Version: 2.0
 * Last Updated: 2025-11-03
 */

(function() {
    'use strict';

    /**
     * Initialize auth module when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        initPasswordToggle();
        initFormValidation();
        initAlertAutoDismiss();
    });

    /**
     * Password visibility toggle
     * Accessibility: Includes ARIA attributes and keyboard support
     */
    function initPasswordToggle() {
        const toggleButton = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        if (!toggleButton || !passwordField || !toggleIcon) {
            return;
        }

        toggleButton.addEventListener('click', function() {
            const isPassword = passwordField.type === 'password';

            // Toggle password visibility
            passwordField.type = isPassword ? 'text' : 'password';

            // Update icon
            if (isPassword) {
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }

            // Update ARIA label for screen readers
            toggleButton.setAttribute(
                'aria-label',
                isPassword ? 'Hide password' : 'Show password'
            );

            // Return focus to password field
            passwordField.focus();
        });

        // Keyboard support (Enter/Space)
        toggleButton.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleButton.click();
            }
        });
    }

    /**
     * Form validation
     * Client-side validation with accessibility support
     */
    function initFormValidation() {
        const loginForm = document.querySelector('.auth-form');

        if (!loginForm) {
            return;
        }

        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            let isValid = true;
            let errors = [];

            // Reset previous errors
            clearValidationErrors(loginForm);

            // Validate username
            if (!username || !username.value.trim()) {
                isValid = false;
                errors.push('Username is required.');
                markFieldInvalid(username, 'Please enter your username.');
            }

            // Validate password
            if (!password || !password.value) {
                isValid = false;
                errors.push('Password is required.');
                markFieldInvalid(password, 'Please enter your password.');
            }

            // If validation fails, prevent submit and show errors
            if (!isValid) {
                e.preventDefault();

                // Show alert message
                showValidationAlert(errors.join(' '));

                // Focus first invalid field
                const firstInvalid = loginForm.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }

                return false;
            }

            // Add loading state to submit button
            const submitButton = loginForm.querySelector('button[type="submit"]');
            if (submitButton) {
                setLoadingState(submitButton, true);
            }
        });
    }

    /**
     * Mark form field as invalid
     * @param {HTMLElement} field - Form field element
     * @param {string} message - Error message
     */
    function markFieldInvalid(field, message) {
        if (!field) return;

        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');

        // Create error message element
        const errorId = field.id + '-error';
        let errorElement = document.getElementById(errorId);

        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.id = errorId;
            errorElement.className = 'invalid-feedback';
            errorElement.setAttribute('role', 'alert');
            field.parentNode.appendChild(errorElement);
        }

        errorElement.textContent = message;
        field.setAttribute('aria-describedby', errorId);
    }

    /**
     * Clear all validation errors from form
     * @param {HTMLFormElement} form - Form element
     */
    function clearValidationErrors(form) {
        const invalidFields = form.querySelectorAll('.is-invalid');
        invalidFields.forEach(function(field) {
            field.classList.remove('is-invalid');
            field.removeAttribute('aria-invalid');
            field.removeAttribute('aria-describedby');
        });

        const errorMessages = form.querySelectorAll('.invalid-feedback');
        errorMessages.forEach(function(msg) {
            msg.remove();
        });

        // Remove validation alert if exists
        const validationAlert = document.querySelector('.alert-validation');
        if (validationAlert) {
            validationAlert.remove();
        }
    }

    /**
     * Show validation alert
     * @param {string} message - Alert message
     */
    function showValidationAlert(message) {
        const form = document.querySelector('.auth-form');
        if (!form) return;

        // Remove existing validation alert
        const existingAlert = document.querySelector('.alert-validation');
        if (existingAlert) {
            existingAlert.remove();
        }

        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-warning alert-dismissible fade show alert-validation';
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
            ${escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        // Insert before form
        form.parentNode.insertBefore(alertDiv, form);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            if (alertDiv && alertDiv.parentNode) {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }
        }, 5000);
    }

    /**
     * Set loading state on submit button
     * @param {HTMLButtonElement} button - Submit button
     * @param {boolean} isLoading - Loading state
     */
    function setLoadingState(button, isLoading) {
        if (!button) return;

        const btnText = button.querySelector('.btn-text');
        const spinner = button.querySelector('.spinner-border');

        button.disabled = isLoading;

        if (isLoading) {
            button.classList.add('btn-loading');
            if (btnText) btnText.classList.add('d-none');
            if (spinner) spinner.classList.remove('d-none');
        } else {
            button.classList.remove('btn-loading');
            if (btnText) btnText.classList.remove('d-none');
            if (spinner) spinner.classList.add('d-none');
        }
    }

    /**
     * Auto-dismiss alerts after 5 seconds
     */
    function initAlertAutoDismiss() {
        const alerts = document.querySelectorAll('.alert:not(.alert-validation)');

        alerts.forEach(function(alert) {
            setTimeout(function() {
                if (alert && alert.parentNode && typeof bootstrap !== 'undefined') {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        });
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Expose public API (if needed)
     */
    window.ConstructLinkAuth = {
        clearValidationErrors: clearValidationErrors,
        setLoadingState: setLoadingState
    };

})();
