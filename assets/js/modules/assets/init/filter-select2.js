/**
 * Filter Select2 Initializer
 *
 * Initializes Select2 searchable dropdowns on the asset filter form.
 * This provides a consistent search experience matching the create/legacy forms.
 *
 * @module assets/init/filter-select2
 * @version 1.0.0
 * @since Phase 3 Refactoring - Filter Enhancement
 */

/**
 * Initialize Select2 on filter dropdowns
 */
function initFilterSelect2() {
    // Wait for jQuery and Select2 to be available
    if (!window.jQuery || !window.jQuery.fn.select2) {
        setTimeout(initFilterSelect2, 100);
        return;
    }

    // Wait for Alpine.js to finish rendering (important!)
    // Alpine might still be initializing the DOM
    if (!document.querySelector('#status')) {
        setTimeout(initFilterSelect2, 100);
        return;
    }

    // Small delay to ensure Alpine.js has fully rendered
    setTimeout(() => {
        // Desktop filter dropdowns
        initializeDropdown('#status', 'All Statuses');
        initializeDropdown('#category_id', 'All Categories');
        initializeDropdown('#project_id', 'All Projects');
        initializeDropdown('#brand_id', 'All Brands');
        initializeDropdown('#asset_type', 'All Types');
        initializeDropdown('#workflow_status', 'All Workflow Status');

        // Mobile filter dropdowns (offcanvas)
        initializeDropdown('#status-mobile', 'All Statuses');
        initializeDropdown('#category_id-mobile', 'All Categories');
        initializeDropdown('#project_id-mobile', 'All Projects');
        initializeDropdown('#brand_id-mobile', 'All Brands');
        initializeDropdown('#asset_type-mobile', 'All Types');
        initializeDropdown('#workflow_status-mobile', 'All Workflow Status');
    }, 250);
}

/**
 * Initialize a single dropdown with Select2
 *
 * @param {string} selector - jQuery selector for the dropdown
 * @param {string} placeholder - Placeholder text
 */
function initializeDropdown(selector, placeholder) {
    const $dropdown = jQuery(selector);

    // Check if dropdown exists
    if ($dropdown.length === 0) {
        return;
    }

    // Check if already initialized
    if ($dropdown.hasClass('select2-hidden-accessible')) {
        return;
    }

    // Determine if this is a mobile dropdown
    const isMobile = selector.includes('mobile');

    // Get the current value to preserve it
    const currentValue = $dropdown.val();

    // Initialize Select2 with Bootstrap 5 theme
    $dropdown.select2({
        theme: 'bootstrap-5',
        placeholder: placeholder,
        allowClear: true,
        width: '100%',
        dropdownParent: isMobile ? jQuery('#filterOffcanvas') : jQuery('body'),
        selectionCssClass: isMobile ? 'form-select' : 'form-select form-select-sm',
        dropdownCssClass: 'select2-dropdown-bootstrap-5',
        minimumResultsForSearch: 0
    });

    // Restore the value if it existed
    if (currentValue) {
        $dropdown.val(currentValue).trigger('change.select2');
    }

    // Set up Alpine.js integration
    // When Select2 changes, trigger Alpine's input/change events to update x-model
    $dropdown.on('select2:select select2:clear', function() {
        // Trigger native change event for Alpine.js x-model binding
        const event = new Event('change', { bubbles: true });
        this.dispatchEvent(event);

        // Also trigger input event for Alpine.js reactivity
        const inputEvent = new Event('input', { bubbles: true });
        this.dispatchEvent(inputEvent);
    });
}

// Auto-initialize on page load with multiple strategies
// Strategy 1: DOM Content Loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFilterSelect2);
} else {
    // Strategy 2: Document already loaded
    initFilterSelect2();
}

// Strategy 3: Alpine.js initialized (for reactive elements)
document.addEventListener('alpine:initialized', function() {
    setTimeout(initFilterSelect2, 100);
});

// Strategy 4: Window load (absolute fallback)
window.addEventListener('load', function() {
    setTimeout(initFilterSelect2, 500);
});
