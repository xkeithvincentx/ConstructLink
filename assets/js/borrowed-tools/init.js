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

    // Get condition options from data attribute
    let conditionOptions = [];
    try {
        const conditionOptionsJson = appContainer.dataset.conditionOptions;
        if (conditionOptionsJson) {
            conditionOptions = JSON.parse(conditionOptionsJson);
        }
    } catch (e) {
        console.error('Failed to parse condition options:', e);
        // Fallback to default options
        conditionOptions = {
            'Good': 'Good',
            'Fair': 'Fair',
            'Poor': 'Poor',
            'Damaged': 'Damaged',
            'Lost': 'Lost'
        };
    }

    // Set auto-refresh interval on body element from container data attribute
    const autoRefreshInterval = appContainer.dataset.autoRefreshInterval;
    if (autoRefreshInterval) {
        document.body.dataset.autoRefreshInterval = autoRefreshInterval;
    }

    // Initialize the borrowed tools module
    init(csrfToken, conditionOptions);

    // Attach refresh button handler
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshBorrowedTools);
    }

    // Initialize statistics toggle with session persistence
    initializeStatisticsToggle();
});

/**
 * Initialize statistics collapse toggle with session persistence
 * Remembers user's preference to show/hide detailed statistics
 */
function initializeStatisticsToggle() {
    const toggleBtn = document.getElementById('toggleDetailedStats');
    const toggleText = document.getElementById('toggleStatsText');
    const toggleIcon = document.getElementById('toggleStatsIcon');
    const statsCollapse = document.getElementById('detailedStatsCollapse');

    if (!statsCollapse || !toggleBtn) {
        return; // Elements not found, exit gracefully
    }

    // Restore previous state from sessionStorage
    const statsExpanded = sessionStorage.getItem('borrowedTools_statsExpanded') === 'true';
    if (statsExpanded) {
        const bsCollapse = new bootstrap.Collapse(statsCollapse, { toggle: false });
        bsCollapse.show();
    }

    // Listen for collapse state changes
    statsCollapse.addEventListener('show.bs.collapse', function() {
        if (toggleText) toggleText.textContent = 'Hide Detailed Statistics';
        if (toggleIcon) {
            toggleIcon.classList.remove('bi-chevron-down');
            toggleIcon.classList.add('bi-chevron-up');
        }
        sessionStorage.setItem('borrowedTools_statsExpanded', 'true');
    });

    statsCollapse.addEventListener('hide.bs.collapse', function() {
        if (toggleText) toggleText.textContent = 'View Detailed Statistics';
        if (toggleIcon) {
            toggleIcon.classList.remove('bi-chevron-up');
            toggleIcon.classList.add('bi-chevron-down');
        }
        sessionStorage.setItem('borrowedTools_statsExpanded', 'false');
    });
}
