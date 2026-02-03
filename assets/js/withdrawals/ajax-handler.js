/**
 * AJAX Handler for Withdrawals Module
 * Provides centralized AJAX functionality for withdrawal operations
 * with proper error handling and accessibility features.
 *
 * @author ConstructLink Development Team
 * @version 1.0.0
 */

export class WithdrawalAjaxHandler {
    constructor(csrfToken) {
        this.csrfToken = csrfToken;
        this.baseUrl = '?route=withdrawals';
    }

    /**
     * Submit batch action (verify, approve, release, return)
     *
     * @param {string} action Action name (verify, approve, release, return)
     * @param {string|number} batchId Batch ID or withdrawal ID
     * @param {Object} formData Additional form data
     * @returns {Promise<Object>} Response data
     */
    async submitBatchAction(action, batchId, formData = {}) {
        try {
            const response = await this.post(`batch/${action}`, {
                batch_id: batchId,
                ...formData
            });

            this.showSuccess(response.message || 'Action completed successfully');

            // Reload page after short delay
            setTimeout(() => window.location.reload(), 1500);

            return response;
        } catch (error) {
            this.showError(error.message || 'An error occurred');
            throw error;
        }
    }

    /**
     * Generic POST request
     *
     * @param {string} endpoint API endpoint
     * @param {Object} data Request data
     * @returns {Promise<Object>} Response data
     */
    async post(endpoint, data) {
        const response = await fetch(`${this.baseUrl}/${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            const error = await response.json().catch(() => ({
                message: `HTTP ${response.status}: ${response.statusText}`
            }));
            throw new Error(error.message || `HTTP ${response.status}`);
        }

        return await response.json();
    }

    /**
     * Generic GET request
     *
     * @param {string} endpoint API endpoint
     * @param {Object} params Query parameters
     * @returns {Promise<Object>} Response data
     */
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${this.baseUrl}/${endpoint}?${queryString}` : `${this.baseUrl}/${endpoint}`;

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-CSRF-Token': this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const error = await response.json().catch(() => ({
                message: `HTTP ${response.status}: ${response.statusText}`
            }));
            throw new Error(error.message || `HTTP ${response.status}`);
        }

        return await response.json();
    }

    /**
     * Show success toast with accessibility
     *
     * @param {string} message Success message
     */
    showSuccess(message) {
        this.showToast(message, 'success');
    }

    /**
     * Show error toast with accessibility
     *
     * @param {string} message Error message
     */
    showError(message) {
        this.showToast(message, 'danger');
    }

    /**
     * Show Bootstrap toast notification
     *
     * @param {string} message Toast message
     * @param {string} type Toast type (success, danger, warning, info)
     */
    showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.setAttribute('style', 'z-index: 9999');
            document.body.appendChild(toastContainer);
        }

        // Create toast element
        const toastId = `toast-${Date.now()}`;
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${this.escapeHtml(message)}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });

        toast.show();

        // Remove toast element from DOM after it's hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    /**
     * Escape HTML to prevent XSS
     *
     * @param {string} text Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
