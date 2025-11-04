/**
 * Assets Module - Core Functions
 * Extracted from inline script for better maintainability
 *
 * Contains:
 * - Asset deletion
 * - Export/print functions
 * - Refresh functions
 * - Alert message display
 * - Legacy asset verification/authorization
 */

// CSRF Token (will be provided by config in init.js)
let CSRFTokenValue = '';

/**
 * Set CSRF token from global config
 */
export function setCsrfToken(token) {
    CSRFTokenValue = token;
}

/**
 * Delete asset with confirmation
 */
export function deleteAsset(assetId) {
    if (confirm('Are you sure you want to delete this asset? This action cannot be undone.')) {
        fetch('?route=assets/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ asset_id: assetId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete asset: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the asset');
        });
    }
}

/**
 * Refresh assets page
 */
export function refreshAssets() {
    window.location.reload();
}

/**
 * Export to Excel with current filters
 */
export function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=assets/export&' + params.toString();
}

/**
 * Print table
 */
export function printTable() {
    window.print();
}

/**
 * Show alert messages
 * @param {string} type - 'error', 'info', 'success'
 * @param {string} message - Alert message text
 */
export function showAlert(type, message) {
    const alertDiv = document.createElement('div');

    // Remove any existing alerts first
    const existingAlerts = document.querySelectorAll('.alert.fade.show');
    existingAlerts.forEach(alert => alert.remove());

    let alertClass, iconClass;
    switch(type) {
        case 'error':
            alertClass = 'alert-danger';
            iconClass = 'exclamation-triangle';
            break;
        case 'info':
            alertClass = 'alert-info';
            iconClass = 'info-circle';
            break;
        case 'success':
        default:
            alertClass = 'alert-success';
            iconClass = 'check-circle';
            break;
    }

    alertDiv.className = `alert ${alertClass} alert-dismissible fade show`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        <i class="bi bi-${iconClass} me-2" aria-hidden="true"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Insert after page header
    const pageHeader = document.querySelector('.border-bottom');
    if (pageHeader) {
        pageHeader.parentNode.insertBefore(alertDiv, pageHeader.nextSibling);
    } else {
        // Fallback: insert at top of main content
        const mainContent = document.querySelector('main') || document.body;
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
    }

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

/**
 * Verify legacy asset
 */
export function verifyAsset(assetId) {
    console.log('verifyAsset called with ID:', assetId);

    if (!CSRFTokenValue) {
        console.error('CSRF token is missing!');
        showAlert('error', 'Security token missing. Please refresh the page.');
        return;
    }

    if (confirm('Are you sure you want to verify this legacy asset?')) {
        console.log('User confirmed verification');

        const requestBody = `asset_id=${assetId}&_csrf_token=${encodeURIComponent(CSRFTokenValue)}`;
        console.log('Request body:', requestBody);

        showAlert('info', 'Verifying asset...');

        fetch('?route=assets/verify-asset', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: requestBody
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('Parsed response:', data);
            if (data.success) {
                showAlert('success', 'Asset verified successfully!');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('error', data.message || 'Failed to verify asset');
            }
        })
        .catch(error => {
            console.error('Verify error:', error);
            showAlert('error', 'An error occurred: ' + error.message);
        });
    } else {
        console.log('User cancelled verification');
    }
}

/**
 * Authorize legacy asset
 */
export function authorizeAsset(assetId) {
    console.log('authorizeAsset called with ID:', assetId);

    if (!CSRFTokenValue) {
        console.error('CSRF token is missing!');
        showAlert('error', 'Security token missing. Please refresh the page.');
        return;
    }

    if (confirm('Are you sure you want to authorize this legacy asset as project property?')) {
        console.log('User confirmed authorization');

        const requestBody = `asset_id=${assetId}&_csrf_token=${encodeURIComponent(CSRFTokenValue)}`;
        console.log('Request body:', requestBody);

        showAlert('info', 'Authorizing asset...');

        fetch('?route=assets/authorize-asset', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: requestBody
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('Parsed response:', data);
            if (data.success) {
                showAlert('success', 'Asset authorized successfully!');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('error', data.message || 'Failed to authorize asset');
            }
        })
        .catch(error => {
            console.error('Authorize error:', error);
            showAlert('error', 'An error occurred: ' + error.message);
        });
    } else {
        console.log('User cancelled authorization');
    }
}

// Make functions globally accessible (fallback for inline onclick handlers)
if (typeof window !== 'undefined') {
    window.deleteAsset = deleteAsset;
    window.verifyAsset = verifyAsset;
    window.authorizeAsset = authorizeAsset;
    window.showAlert = showAlert;
    window.refreshAssets = refreshAssets;
    window.exportToExcel = exportToExcel;
    window.printTable = printTable;
}
