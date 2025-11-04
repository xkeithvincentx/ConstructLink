/**
 * Assets Module - Initialization
 * Extracted from inline script for better maintainability
 *
 * Handles:
 * - Enhanced search initialization
 * - Keyboard shortcuts
 * - Responsive enhancements
 * - Card height equalization
 * - Mobile optimizations
 */

import { EnhancedAssetSearch, setCsrfToken as setSearchCsrfToken } from './enhanced-search.js';
import { setCsrfToken as setCoreCsrfToken } from './core-functions.js';

// Global asset search instance
let assetSearch;

/**
 * Initialize all assets module functionality
 */
function initAssetsModule(config = {}) {
    // Set CSRF tokens
    if (config.csrfToken) {
        setSearchCsrfToken(config.csrfToken);
        setCoreCsrfToken(config.csrfToken);
    }

    // Initialize enhanced search
    assetSearch = new EnhancedAssetSearch(config.csrfToken);

    // Make search instance globally accessible
    if (typeof window !== 'undefined') {
        window.assetSearch = assetSearch;
    }

    // Initialize keyboard shortcuts
    initKeyboardShortcuts();

    // Initialize responsive enhancements
    initResponsiveEnhancements();

    // Initialize card height equalizer
    initCardHeightEqualizer();

    // Initialize mobile optimizations
    if (window.innerWidth <= 768) {
        initMobileOptimizations();
    }
}

/**
 * Initialize keyboard shortcuts
 */
function initKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+K or Cmd+K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }

        // Escape to clear search
        if (e.key === 'Escape') {
            const searchInput = document.getElementById('search');
            if (searchInput && document.activeElement === searchInput) {
                searchInput.blur();
                if (assetSearch) {
                    assetSearch.clearFeedback();
                }
            }
        }
    });

    // Add keyboard shortcut hint to search field (desktop only)
    if (window.innerWidth > 768) {
        const searchGroup = document.querySelector('#search')?.closest('.input-group');
        if (searchGroup) {
            const hint = document.createElement('span');
            hint.className = 'search-hint';
            hint.textContent = '⌘K';
            hint.title = 'Keyboard shortcut: Ctrl+K (Windows) or Cmd+K (Mac)';
            searchGroup.style.position = 'relative';
            searchGroup.appendChild(hint);
        }
    }
}

/**
 * Initialize responsive table enhancements
 */
function initResponsiveEnhancements() {
    function handleTableResponsiveness() {
        const table = document.getElementById('assetsTable');
        if (!table) return;

        const windowWidth = window.innerWidth;
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const referenceCell = cells[0];
            const assetCell = cells[1];

            if (windowWidth < 768 && referenceCell && assetCell) {
                // Add category and other info to asset cell on mobile
                const categoryCell = row.querySelector('.d-none.d-md-table-cell');
                if (categoryCell && !assetCell.querySelector('.mobile-category')) {
                    const categoryBadge = categoryCell.querySelector('.badge');
                    if (categoryBadge) {
                        const mobileCategory = document.createElement('div');
                        mobileCategory.className = 'mobile-category mt-1';
                        mobileCategory.innerHTML = `<small class="text-muted">${categoryBadge.textContent}</small>`;
                        assetCell.appendChild(mobileCategory);
                    }
                }
            }
        });
    }

    // Handle table responsiveness on load and resize
    handleTableResponsiveness();
    window.addEventListener('resize', handleTableResponsiveness);
}

/**
 * Initialize card height equalizer
 */
function initCardHeightEqualizer() {
    function equalizeCardHeights() {
        const cardRows = document.querySelectorAll('.row.mb-4');
        cardRows.forEach(row => {
            const cards = row.querySelectorAll('.card');
            let maxHeight = 0;

            // Reset heights
            cards.forEach(card => {
                card.style.height = 'auto';
            });

            // Find max height
            cards.forEach(card => {
                const height = card.offsetHeight;
                if (height > maxHeight) {
                    maxHeight = height;
                }
            });

            // Apply max height to all cards in the row (only on larger screens)
            if (window.innerWidth >= 768) {
                cards.forEach(card => {
                    card.style.height = maxHeight + 'px';
                });
            }
        });
    }

    // Run on load and resize
    window.addEventListener('load', equalizeCardHeights);
    window.addEventListener('resize', function() {
        setTimeout(equalizeCardHeights, 100);
    });
}

/**
 * Initialize mobile-specific optimizations
 */
function initMobileOptimizations() {
    // Add swipe gestures for table navigation on mobile
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer) {
        let isScrolling = false;
        let startX = 0;

        tableContainer.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            isScrolling = false;
        });

        tableContainer.addEventListener('touchmove', function(e) {
            if (!startX) return;
            isScrolling = true;
        });

        tableContainer.addEventListener('touchend', function() {
            startX = 0;
            isScrolling = false;
        });

        // Add visual indicator for horizontal scrolling
        const scrollIndicator = document.createElement('div');
        scrollIndicator.className = 'text-center text-muted small mt-2 d-md-none';
        scrollIndicator.innerHTML = '<i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Swipe horizontally to see more columns';
        tableContainer.parentNode.appendChild(scrollIndicator);
    }
}

/**
 * Alternative event delegation for workflow buttons (fallback method)
 */
function initWorkflowButtonDelegation() {
    document.addEventListener('click', function(event) {
        const target = event.target;
        const button = target.closest('button[data-action]');

        if (button) {
            const action = button.getAttribute('data-action');
            const assetId = button.getAttribute('data-asset-id');

            console.log('=== Alternative Event Handler ===');
            console.log('Button clicked:', action, 'Asset ID:', assetId);

            if (action === 'verify' && assetId) {
                console.log('Calling verifyAsset via event delegation');
                window.verifyAsset(parseInt(assetId));
            } else if (action === 'authorize' && assetId) {
                console.log('Calling authorizeAsset via event delegation');
                window.authorizeAsset(parseInt(assetId));
            }
        }
    });
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // Config will be provided by inline script tag
        if (typeof window.ConstructLinkConfig !== 'undefined') {
            initAssetsModule(window.ConstructLinkConfig);
        } else {
            console.warn('ConstructLinkConfig not found, initializing with defaults');
            initAssetsModule({});
        }

        // Initialize workflow button delegation
        initWorkflowButtonDelegation();
    });
} else {
    // DOM already loaded
    if (typeof window.ConstructLinkConfig !== 'undefined') {
        initAssetsModule(window.ConstructLinkConfig);
    } else {
        initAssetsModule({});
    }
    initWorkflowButtonDelegation();
}

// Export for manual initialization if needed
export { initAssetsModule, assetSearch };
