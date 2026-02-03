/**
 * Requests Index Alpine.js Component
 * Handles filtering, modal interactions, and AJAX operations for requests
 *
 * @author ConstructLink Development Team
 * @version 1.0.0
 */

/**
 * Initialize requests index app
 * @returns {Object} Alpine.js component data
 */
export function requestsIndexApp() {
    return {
        // Filter state
        filters: {
            status: new URLSearchParams(window.location.search).get('status') || '',
            requestType: new URLSearchParams(window.location.search).get('request_type') || '',
            urgency: new URLSearchParams(window.location.search).get('urgency') || '',
            projectId: new URLSearchParams(window.location.search).get('project_id') || '',
            search: new URLSearchParams(window.location.search).get('search') || '',
            dateFrom: new URLSearchParams(window.location.search).get('date_from') || '',
            dateTo: new URLSearchParams(window.location.search).get('date_to') || ''
        },

        /**
         * Apply filters and reload page with filter parameters
         */
        applyFilters() {
            const params = new URLSearchParams();
            params.append('route', 'requests');

            if (this.filters.status) {
                params.append('status', this.filters.status);
            }
            if (this.filters.requestType) {
                params.append('request_type', this.filters.requestType);
            }
            if (this.filters.urgency) {
                params.append('urgency', this.filters.urgency);
            }
            if (this.filters.projectId) {
                params.append('project_id', this.filters.projectId);
            }
            if (this.filters.search) {
                params.append('search', this.filters.search);
            }
            if (this.filters.dateFrom) {
                params.append('date_from', this.filters.dateFrom);
            }
            if (this.filters.dateTo) {
                params.append('date_to', this.filters.dateTo);
            }

            // Reload page with filters
            window.location.href = '?' + params.toString();
        },

        /**
         * Clear all filters and reload page
         */
        clearFilters() {
            this.filters = {
                status: '',
                requestType: '',
                urgency: '',
                projectId: '',
                search: '',
                dateFrom: '',
                dateTo: ''
            };
            window.location.href = '?route=requests';
        },

        /**
         * Setup modal handlers for request operations
         */
        setupModalHandlers() {
            // Handle verify modal
            document.querySelectorAll('[data-action="verify-request"]').forEach(button => {
                button.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-request-id');
                    document.querySelector('#requestVerifyModal input[name="request_id"]').value = requestId;
                });
            });

            // Handle authorize modal
            document.querySelectorAll('[data-action="authorize-request"]').forEach(button => {
                button.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-request-id');
                    document.querySelector('#requestAuthorizeModal input[name="request_id"]').value = requestId;
                });
            });

            // Handle approve modal
            document.querySelectorAll('[data-action="approve-request"]').forEach(button => {
                button.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-request-id');
                    document.querySelector('#requestApproveModal input[name="request_id"]').value = requestId;
                });
            });

            // Handle decline modal
            document.querySelectorAll('[data-action="decline-request"]').forEach(button => {
                button.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-request-id');
                    document.querySelector('#requestDeclineModal input[name="request_id"]').value = requestId;
                });
            });
        },

        /**
         * Initialize component
         */
        init() {
            console.log('Requests Index App initialized');
            this.setupModalHandlers();
        }
    }
}

// Make available globally for Alpine
window.requestsIndexApp = requestsIndexApp;
