/**
 * ConstructLink™ Request Form Field Toggles
 *
 * Handles dynamic field visibility based on form selections.
 * Implements accessible show/hide with proper ARIA announcements.
 *
 * @module RequestFieldToggles
 * @version 2.0.0
 */

(function() {
    'use strict';

    /**
     * Toggle decision-specific fields in approval form
     */
    function toggleDecisionFields() {
        const decision = document.getElementById('action');
        if (!decision) return;

        const approvalFields = document.getElementById('approvalFields');
        const declineFields = document.getElementById('declineFields');
        const moreInfoFields = document.getElementById('moreInfoFields');
        const declineReason = document.getElementById('decline_reason');

        // Reset visibility and requirements
        if (approvalFields) {
            approvalFields.style.display = 'none';
            approvalFields.setAttribute('aria-hidden', 'true');
        }

        if (declineFields) {
            declineFields.style.display = 'none';
            declineFields.setAttribute('aria-hidden', 'true');
            if (declineReason) declineReason.required = false;
        }

        if (moreInfoFields) {
            moreInfoFields.style.display = 'none';
            moreInfoFields.setAttribute('aria-hidden', 'true');
        }

        // Show relevant fields based on decision
        const decisionValue = decision.value;

        if (decisionValue === 'approve' && approvalFields) {
            approvalFields.style.display = 'block';
            approvalFields.setAttribute('aria-hidden', 'false');
            announceChange('Approval fields are now visible');
        } else if (decisionValue === 'decline' && declineFields) {
            declineFields.style.display = 'block';
            declineFields.setAttribute('aria-hidden', 'false');
            if (declineReason) declineReason.required = true;
            announceChange('Decline fields are now visible');
        } else if (decisionValue === 'more_info' && moreInfoFields) {
            moreInfoFields.style.display = 'block';
            moreInfoFields.setAttribute('aria-hidden', 'false');
            announceChange('Additional information fields are now visible');
        }
    }

    /**
     * Toggle category field based on request type
     */
    function toggleCategoryField() {
        const requestType = document.getElementById('request_type');
        if (!requestType) return;

        const categoryField = document.getElementById('categoryField');
        const quantityFields = document.getElementById('quantityFields');
        const estimatedCostField = document.getElementById('estimatedCostField');

        const typeValue = requestType.value;

        // Show category for Material, Tool, Equipment
        if (['Material', 'Tool', 'Equipment'].includes(typeValue)) {
            if (categoryField) {
                categoryField.style.display = 'block';
                categoryField.setAttribute('aria-hidden', 'false');
            }
            if (quantityFields) {
                quantityFields.style.display = 'block';
                quantityFields.setAttribute('aria-hidden', 'false');
            }
            announceChange('Category and quantity fields are now visible');
        } else {
            if (categoryField) {
                categoryField.style.display = 'none';
                categoryField.setAttribute('aria-hidden', 'true');
            }
            if (quantityFields) {
                quantityFields.style.display = 'none';
                quantityFields.setAttribute('aria-hidden', 'true');
            }
        }

        // Show estimated cost for all except Petty Cash
        if (typeValue && typeValue !== 'Petty Cash') {
            if (estimatedCostField) {
                estimatedCostField.style.display = 'block';
                estimatedCostField.setAttribute('aria-hidden', 'false');
            }
        } else {
            if (estimatedCostField) {
                estimatedCostField.style.display = 'none';
                estimatedCostField.setAttribute('aria-hidden', 'true');
            }
        }
    }

    /**
     * Toggle forward options in review form
     */
    function toggleForwardOptions() {
        // Placeholder for future forward option logic
        // Currently simplified based on review.php
        const action = document.getElementById('action');
        if (action) {
            announceChange(`Review action: ${action.value}`);
        }
    }

    /**
     * Announce changes to screen readers
     * @param {string} message - Message to announce
     */
    function announceChange(message) {
        const announcement = document.getElementById('aria-announcements');
        if (announcement) {
            announcement.textContent = message;
        }
    }

    /**
     * Auto-fill approved budget with estimated cost
     */
    function autoFillApprovedBudget() {
        const actionField = document.getElementById('action');
        const approvedBudget = document.getElementById('approved_budget');
        const estimatedCostField = document.querySelector('[name="estimated_cost"]');

        if (actionField && approvedBudget && estimatedCostField) {
            actionField.addEventListener('change', function() {
                if (this.value === 'approve') {
                    const estimatedCost = parseFloat(estimatedCostField.value) || 0;
                    if (estimatedCost > 0) {
                        approvedBudget.value = estimatedCost;
                    }
                }
            });
        }
    }

    /**
     * Initialize field toggles
     */
    function init() {
        // Create ARIA live region for announcements
        const announcement = document.createElement('div');
        announcement.id = 'aria-announcements';
        announcement.className = 'sr-only';
        announcement.setAttribute('role', 'status');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        document.body.appendChild(announcement);

        // Decision field toggle (approve form)
        const actionField = document.getElementById('action');
        if (actionField) {
            actionField.removeAttribute('onchange'); // Remove inline handler
            actionField.addEventListener('change', toggleDecisionFields);
        }

        // Request type toggle (create form)
        const requestTypeField = document.getElementById('request_type');
        if (requestTypeField) {
            requestTypeField.removeAttribute('onchange'); // Remove inline handler
            requestTypeField.addEventListener('change', toggleCategoryField);
            // Initialize on page load
            toggleCategoryField();
        }

        // Forward options toggle (review form)
        const reviewActionField = document.getElementById('action');
        if (reviewActionField && document.getElementById('reviewForm')) {
            reviewActionField.addEventListener('change', toggleForwardOptions);
        }

        // Auto-fill budget
        autoFillApprovedBudget();
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
