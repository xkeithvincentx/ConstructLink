/**
 * AJAX Handler for Borrowed Tools
 *
 * Provides centralized AJAX functionality for borrowed tools operations
 * with proper error handling and accessibility features.
 */

export class AjaxHandler {
    constructor(csrfToken) {
        this.csrfToken = csrfToken;
        this.baseUrl = '?route=borrowed-tools';
    }

    /**
     * Submit batch action (verify, approve, release, return, etc.)
     *
     * @param {string} action Action name (verify, approve, release, return, extend, cancel)
     * @param {string|number} batchId Batch ID
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
        this.showToast('success', message, 'check-circle');
    }

    /**
     * Show error toast with accessibility
     *
     * @param {string} message Error message
     */
    showError(message) {
        this.showToast('danger', message, 'x-circle');
    }

    /**
     * Show info toast
     *
     * @param {string} message Info message
     */
    showInfo(message) {
        this.showToast('info', message, 'info-circle');
    }

    /**
     * Show warning toast
     *
     * @param {string} message Warning message
     */
    showWarning(message) {
        this.showToast('warning', message, 'exclamation-triangle');
    }

    /**
     * Show accessible toast notification
     *
     * @param {string} type Toast type (success, danger, warning, info)
     * @param {string} message Toast message
     * @param {string} icon Bootstrap icon name
     */
    showToast(type, message, icon) {
        const toast = document.createElement('div');
        toast.className = 'toast show position-fixed bottom-0 end-0 m-3';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.style.zIndex = '9999';

        const typeLabels = {
            success: 'Success',
            danger: 'Error',
            warning: 'Warning',
            info: 'Information'
        };

        const typeLabel = typeLabels[type] || 'Notification';
        const textColorClass = type === 'warning' ? 'text-dark' : 'text-white';

        toast.innerHTML = `
            <div class="toast-header bg-${type} ${textColorClass}">
                <i class="bi bi-${icon} me-2" aria-hidden="true"></i>
                <strong class="me-auto">${typeLabel}</strong>
                <button type="button" class="btn-close ${type === 'warning' ? '' : 'btn-close-white'}" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">${this.escapeHtml(message)}</div>
        `;

        document.body.appendChild(toast);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    /**
     * Escape HTML for security
     *
     * @param {string} text Text to escape
     * @returns {string} Escaped HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show loading spinner on button
     *
     * @param {HTMLElement} button Button element
     * @param {string} loadingText Loading text (default: "Processing...")
     * @returns {Function} Function to restore button state
     */
    setButtonLoading(button, loadingText = 'Processing...') {
        const originalHtml = button.innerHTML;
        const wasDisabled = button.disabled;

        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${loadingText}`;

        // Return function to restore original state
        return () => {
            button.disabled = wasDisabled;
            button.innerHTML = originalHtml;
        };
    }

    /**
     * Confirm action with modal dialog
     *
     * @param {string} message Confirmation message
     * @param {string} title Dialog title (default: "Confirm Action")
     * @returns {Promise<boolean>} True if confirmed, false otherwise
     */
    async confirm(message, title = 'Confirm Action') {
        return new Promise((resolve) => {
            // Use browser confirm for now - can be enhanced with Bootstrap modal
            const result = window.confirm(`${title}\n\n${message}`);
            resolve(result);
        });
    }
}

// Export default instance creator
export function createAjaxHandler(csrfToken) {
    return new AjaxHandler(csrfToken);
}
