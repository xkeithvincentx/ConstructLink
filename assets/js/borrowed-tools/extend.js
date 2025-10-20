/**
 * Extend Borrowing Period - Form Handler
 * ConstructLink™ Borrowed Tools Module
 * Developed by: Ranoa Digital Solutions
 */

/**
 * Alpine.js component for extend form
 * Make it globally available for Alpine.js
 */
window.extendForm = function() {
    return {
        formData: {
            new_expected_return: document.getElementById('new_expected_return')?.value || '',
            reason: document.getElementById('reason')?.value || ''
        },

        /**
         * Initialize form
         */
        init() {
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            const newReturnInput = document.getElementById('new_expected_return');
            newReturnInput.min = today;

            // Set default to 7 days from original return date if not set
            if (!this.formData.new_expected_return) {
                const originalReturn = new Date(newReturnInput.dataset.originalDate);
                originalReturn.setDate(originalReturn.getDate() + 7);
                this.formData.new_expected_return = originalReturn.toISOString().split('T')[0];
                this.calculateExtension();
            }

            // Calculate extension when date changes
            this.$watch('formData.new_expected_return', () => {
                this.calculateExtension();
            });
        },

        /**
         * Calculate extension period
         */
        calculateExtension() {
            const extensionDisplay = document.getElementById('extensionPeriod');
            const extensionDays = document.getElementById('extensionDays');
            const newReturnInput = document.getElementById('new_expected_return');

            if (!extensionDisplay || !extensionDays || !newReturnInput) {
                return; // Elements not found, skip
            }

            if (this.formData.new_expected_return) {
                const originalReturn = new Date(newReturnInput.dataset.originalDate);
                const newReturn = new Date(this.formData.new_expected_return);
                const diffTime = newReturn - originalReturn;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (diffDays > 0) {
                    extensionDisplay.innerHTML = `<span class="text-success"><i class="bi bi-calendar-plus me-1"></i>${diffDays} day(s) extension</span>`;
                    extensionDays.textContent = `${diffDays} day(s)`;
                } else if (diffDays === 0) {
                    extensionDisplay.innerHTML = `<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Same date selected</span>`;
                    extensionDays.textContent = 'No extension';
                } else {
                    extensionDisplay.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Earlier date selected</span>`;
                    extensionDays.textContent = 'Invalid';
                }
            } else {
                extensionDisplay.innerHTML = `<span class="text-muted">Select new return date to see extension</span>`;
                extensionDays.textContent = '-';
            }
        },

        /**
         * Format date for display
         */
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    };
};

/**
 * Form validation handler
 */
(function() {
    'use strict';
    window.addEventListener('load', function() {
        const forms = document.getElementsByClassName('needs-validation');
        const validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

/**
 * Auto-dismiss alerts after 5 seconds
 */
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
