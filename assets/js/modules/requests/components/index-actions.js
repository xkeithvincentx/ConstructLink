/**
 * ConstructLink™ Request Index Actions Module
 *
 * Handles interactive elements on the requests index page.
 * Provides export functionality, alert toggling, and real-time updates.
 *
 * @module RequestIndexActions
 * @version 2.0.0
 */

(function() {
    'use strict';

    /**
     * Export requests in specified format
     * @param {string} format - Export format (csv, excel, pdf)
     */
    function exportRequests(format) {
        const params = new URLSearchParams(window.location.search);
        params.set('format', format);
        window.location.href = '?route=requests/export&' + params.toString();
    }

    /**
     * Toggle visibility of additional delivery alerts
     */
    function toggleAllAlerts() {
        const hiddenAlerts = document.querySelectorAll('.hidden-alert');
        const button = event.target;

        if (hiddenAlerts.length > 0) {
            hiddenAlerts.forEach(alert => {
                alert.style.display = alert.style.display === 'none' ? 'block' : 'none';
            });

            const isShowing = hiddenAlerts[0].style.display !== 'none';
            button.innerHTML = isShowing ?
                '<i class="bi bi-chevron-up me-1"></i>Hide Additional Alerts' :
                '<i class="bi bi-chevron-down me-1"></i>Show All Alerts (' + hiddenAlerts.length + ' more)';

            // Announce to screen readers
            announceChange(isShowing ? 'Additional alerts shown' : 'Additional alerts hidden');
        }
    }

    /**
     * Check if PO can be created from request
     * @param {Object} request - Request data
     * @param {string} userRole - Current user role
     * @returns {boolean} - Whether PO can be created
     */
    function canCreatePOFromRequest(request, userRole) {
        // Authorization roles
        const authorizedRoles = ['Procurement Officer', 'Finance Director', 'System Admin'];

        return request.status === 'Approved' &&
               !request.procurement_id &&
               authorizedRoles.includes(userRole);
    }

    /**
     * Highlight overdue requests on page load
     */
    function highlightOverdueRequests() {
        const overdueRows = document.querySelectorAll('tr[data-overdue="true"]');
        overdueRows.forEach(row => {
            row.classList.add('table-danger');
        });
    }

    /**
     * Initialize Bootstrap tooltips for delivery status badges
     */
    function initializeTooltips() {
        const deliveryBadges = document.querySelectorAll('[title]');
        deliveryBadges.forEach(badge => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                new bootstrap.Tooltip(badge);
            }
        });
    }

    /**
     * Auto-refresh page for real-time updates
     * Only refreshes if no filters are applied to avoid disrupting user workflow
     * @param {number} interval - Refresh interval in milliseconds (default: 300000 = 5 minutes)
     */
    function setupAutoRefresh(interval = 300000) {
        setInterval(function() {
            // Only refresh if no filters are applied
            if (window.location.search === '?route=requests' || window.location.search === '') {
                location.reload();
            }
        }, interval);
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
     * Initialize index page actions
     */
    function init() {
        // Create ARIA live region if not exists
        if (!document.getElementById('aria-announcements')) {
            const announcement = document.createElement('div');
            announcement.id = 'aria-announcements';
            announcement.className = 'sr-only';
            announcement.setAttribute('role', 'status');
            announcement.setAttribute('aria-live', 'polite');
            announcement.setAttribute('aria-atomic', 'true');
            document.body.appendChild(announcement);
        }

        // Remove inline onclick handlers and attach proper event listeners
        const toggleAlertsBtn = document.querySelector('[onclick*="toggleAllAlerts"]');
        if (toggleAlertsBtn) {
            toggleAlertsBtn.removeAttribute('onclick');
            toggleAlertsBtn.addEventListener('click', toggleAllAlerts);
        }

        // Initialize tooltips
        initializeTooltips();

        // Highlight overdue requests
        highlightOverdueRequests();

        // Setup auto-refresh (optional - can be disabled)
        // setupAutoRefresh(300000); // 5 minutes
    }

    // Expose functions globally for backward compatibility
    window.RequestIndexActions = {
        exportRequests: exportRequests,
        toggleAllAlerts: toggleAllAlerts,
        canCreatePOFromRequest: canCreatePOFromRequest
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
