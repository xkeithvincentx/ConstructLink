/**
 * Assets Pagination Handler
 * Handles records per page selector and pagination controls
 *
 * @version 1.0.0
 * @follows ConstructLink coding standards
 * @pattern Vanilla JavaScript (NO Alpine.js - matches Borrowed Tools pattern)
 */

/**
 * Initialize records per page selector
 *
 * Attaches change event listener to the records per page dropdown.
 * Updates URL with new per_page parameter and reloads page.
 * Resets to page 1 when changing records per page.
 *
 * @returns {void}
 */
export const initRecordsPerPageSelector = () => {
  const recordsPerPageSelect = document.getElementById('recordsPerPage');

  if (!recordsPerPageSelect) {
    // Element not found - feature is desktop-only, may not exist on mobile
    console.debug('[Assets/Pagination] Records per page selector not found - may be hidden on mobile');
    return;
  }

  recordsPerPageSelect.addEventListener('change', function() {
    const perPage = this.value;

    // Validate input (security: whitelist approach)
    if (!/^(5|10|25|50|100)$/.test(perPage)) {
      console.error('[Assets/Pagination] Invalid per_page value:', perPage);
      return;
    }

    // Build new URL with updated per_page parameter
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', perPage);
    url.searchParams.delete('page'); // Reset to page 1 when changing per page

    console.debug('[Assets/Pagination] Changing records per page to:', perPage);

    // Reload page with new parameters
    window.location.href = url.toString();
  });

  console.debug('[Assets/Pagination] Records per page selector initialized');
};

/**
 * Initialize pagination utilities
 * Entry point for all pagination-related functionality
 *
 * @returns {void}
 */
export const initPagination = () => {
  initRecordsPerPageSelector();

  console.log('[Assets/Pagination] Pagination module initialized');
};

/**
 * Auto-initialize on DOM ready
 * Ensures pagination is initialized when DOM is fully loaded
 */
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPagination);
} else {
  // DOM already loaded
  initPagination();
}

/**
 * Default export
 * Provides access to all pagination functions
 */
export default {
  initPagination,
  initRecordsPerPageSelector
};
