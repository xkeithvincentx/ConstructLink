/**
 * Notifications Module
 *
 * Provides toast-style notification functions for user feedback.
 * Supports success, info, warning, and error notifications with auto-dismiss.
 *
 * @module assets/ui/notifications
 * @version 3.0.0
 * @since Phase 3 Refactoring - Inline JavaScript Extraction
 */

/**
 * Show reset/clear notification
 *
 * @param {string} message - Notification message
 * @param {number} duration - Duration in milliseconds (default: 4000)
 */
export function showResetNotification(message, duration = 4000) {
    let notification = document.getElementById('reset-notification');

    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'reset-notification';
        notification.className = 'alert alert-warning alert-dismissible fade show position-fixed';
        notification.style.cssText = `
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            min-width: 350px;
            text-align: center;
        `;
        document.body.appendChild(notification);
    }

    notification.innerHTML = `
        <i class="bi bi-arrow-clockwise me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

/**
 * Show auto-selection success message
 *
 * @param {string} message - Notification message
 * @param {number} duration - Duration in milliseconds (default: 3000)
 */
export function showAutoSelectionMessage(message, duration = 3000) {
    let notification = document.getElementById('auto-selection-notification');

    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'auto-selection-notification';
        notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        `;
        document.body.appendChild(notification);
    }

    notification.innerHTML = `
        <i class="bi bi-check-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

/**
 * Show unit auto-selection notification
 *
 * @param {string} unit - Unit code (e.g., 'pcs', 'unit', 'kg')
 * @param {number} duration - Duration in milliseconds (default: 3000)
 */
export function showUnitAutoSelectionMessage(unit, duration = 3000) {
    const unitNames = {
        'pcs': 'Pieces',
        'unit': 'Unit',
        'set': 'Set',
        'box': 'Box',
        'kg': 'Kilogram',
        'm': 'Meter',
        'm3': 'Cubic Meter',
        'sqm': 'Square Meter',
        'l': 'Liter',
        'lot': 'Lot'
    };

    const unitName = unitNames[unit] || unit;

    let notification = document.getElementById('unit-auto-selection-notification');

    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'unit-auto-selection-notification';
        notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
        notification.style.cssText = `
            top: 70px;
            right: 20px;
            z-index: 9999;
            min-width: 250px;
        `;
        document.body.appendChild(notification);
    }

    notification.innerHTML = `
        <i class="bi bi-gear me-2"></i>Unit auto-selected: ${unitName}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

/**
 * Show category warning message
 *
 * @param {string} message - Warning message
 */
export function showCategoryWarning(message) {
    const categoryInfo = document.getElementById('category-info');
    if (!categoryInfo) return;

    let warningDiv = document.getElementById('category-warning');

    if (!warningDiv) {
        warningDiv = document.createElement('div');
        warningDiv.id = 'category-warning';
        warningDiv.className = 'alert alert-warning alert-dismissible fade show mt-2';
        warningDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle me-2"></i>
            <span class="warning-text"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        categoryInfo.appendChild(warningDiv);
    }

    warningDiv.querySelector('.warning-text').textContent = message;
    warningDiv.style.display = 'block';
}

/**
 * Hide category warning message
 */
export function hideCategoryWarning() {
    const warningDiv = document.getElementById('category-warning');
    if (warningDiv) {
        warningDiv.style.display = 'none';
    }
}

/**
 * Show generic success notification
 *
 * @param {string} message - Success message
 * @param {number} duration - Duration in milliseconds (default: 3000)
 */
export function showSuccessNotification(message, duration = 3000) {
    showNotification(message, 'success', duration);
}

/**
 * Show generic info notification
 *
 * @param {string} message - Info message
 * @param {number} duration - Duration in milliseconds (default: 3000)
 */
export function showInfoNotification(message, duration = 3000) {
    showNotification(message, 'info', duration);
}

/**
 * Show generic warning notification
 *
 * @param {string} message - Warning message
 * @param {number} duration - Duration in milliseconds (default: 4000)
 */
export function showWarningNotification(message, duration = 4000) {
    showNotification(message, 'warning', duration);
}

/**
 * Show generic error notification
 *
 * @param {string} message - Error message
 * @param {number} duration - Duration in milliseconds (default: 5000)
 */
export function showErrorNotification(message, duration = 5000) {
    showNotification(message, 'danger', duration);
}

/**
 * Show generic notification (internal helper)
 *
 * @param {string} message - Notification message
 * @param {string} type - Alert type: success, info, warning, danger
 * @param {number} duration - Duration in milliseconds
 */
function showNotification(message, type = 'info', duration = 3000) {
    const icons = {
        'success': 'bi-check-circle',
        'info': 'bi-info-circle',
        'warning': 'bi-exclamation-triangle',
        'danger': 'bi-x-circle'
    };

    const icon = icons[type] || icons.info;
    const notificationId = `notification-${type}-${Date.now()}`;

    const notification = document.createElement('div');
    notification.id = notificationId;
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
    `;
    notification.innerHTML = `
        <i class="bi ${icon} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(notification);

    // Auto-dismiss
    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 150);
        }
    }, duration);
}

/**
 * Show loading notification (does not auto-dismiss)
 *
 * @param {string} message - Loading message
 * @returns {HTMLElement} - Notification element (for manual removal)
 */
export function showLoadingNotification(message) {
    const notificationId = `loading-notification-${Date.now()}`;

    const notification = document.createElement('div');
    notification.id = notificationId;
    notification.className = 'alert alert-primary fade show position-fixed';
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
    `;
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(notification);

    return notification;
}

/**
 * Hide loading notification
 *
 * @param {HTMLElement} notification - Notification element to hide
 */
export function hideLoadingNotification(notification) {
    if (notification && notification.parentNode) {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 150);
    }
}

/**
 * Show confirmation dialog
 *
 * @param {string} message - Confirmation message
 * @param {Function} onConfirm - Callback for confirm action
 * @param {Function} onCancel - Callback for cancel action (optional)
 * @returns {boolean} - Result from native confirm dialog
 */
export function showConfirmation(message, onConfirm, onCancel = null) {
    const confirmed = confirm(message);

    if (confirmed && onConfirm) {
        onConfirm();
    } else if (!confirmed && onCancel) {
        onCancel();
    }

    return confirmed;
}
