/**
 * Borrowed Tools Module Initialization
 *
 * Auto-initializes the borrowed tools module by reading configuration
 * from data attributes and setting up event handlers.
 */

import { init } from '/assets/js/borrowed-tools/index.js';
import { refreshBorrowedTools } from '/assets/js/borrowed-tools/list-utils.js';

document.addEventListener('DOMContentLoaded', function() {
    // Get borrowed tools app container
    const appContainer = document.getElementById('borrowed-tools-app');

    if (!appContainer) {
        console.error('Borrowed tools app container not found');
        return;
    }

    // Get CSRF token from data attribute
    const csrfToken = appContainer.dataset.csrfToken;

    if (!csrfToken) {
        console.error('CSRF token not found in data attributes');
        return;
    }

    // Set CSRF token globally for the module
    window.borrowedToolsCsrfToken = csrfToken;

    // Set auto-refresh interval on body element from container data attribute
    const autoRefreshInterval = appContainer.dataset.autoRefreshInterval;
    if (autoRefreshInterval) {
        document.body.dataset.autoRefreshInterval = autoRefreshInterval;
    }

    // Initialize the borrowed tools module
    init(csrfToken);

    // Attach refresh button handler
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshBorrowedTools);
    }
});
