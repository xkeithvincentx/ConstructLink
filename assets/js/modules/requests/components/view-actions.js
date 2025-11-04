/**
 * ConstructLink™ Request View Actions Module
 *
 * Handles interactive elements on the request view/details page.
 * Provides submit confirmations and print functionality.
 *
 * @module RequestViewActions
 * @version 2.0.0
 */

(function() {
    'use strict';

    /**
     * Handle request submission confirmation
     * @param {Event} e - Form submit event
     * @returns {boolean} - Whether to proceed with submission
     */
    function confirmRequestSubmission(e) {
        const confirmed = confirm('Are you sure you want to submit this request for review?');
        if (!confirmed) {
            e.preventDefault();
            return false;
        }
        return true;
    }

    /**
     * Handle print request
     */
    function printRequest() {
        window.print();
    }

    /**
     * Initialize view page actions
     */
    function init() {
        // Remove inline onclick handlers and attach proper event listeners
        const submitForms = document.querySelectorAll('form[action*="requests/submit"]');
        submitForms.forEach(form => {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.removeAttribute('onclick');
                form.addEventListener('submit', confirmRequestSubmission);
            }
        });

        // Print button
        const printButtons = document.querySelectorAll('[onclick*="window.print"]');
        printButtons.forEach(btn => {
            btn.removeAttribute('onclick');
            btn.addEventListener('click', printRequest);
        });
    }

    // Expose functions globally for backward compatibility
    window.RequestViewActions = {
        confirmSubmission: confirmRequestSubmission,
        print: printRequest
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
