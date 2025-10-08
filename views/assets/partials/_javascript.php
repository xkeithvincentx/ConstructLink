<?php
/**
 * JavaScript Partial
 * Contains all JavaScript functions, event handlers, and styles for the assets page
 */
?>

<script>
// CSRF Token for AJAX requests
const CSRFTokenValue = '<?= htmlspecialchars(CSRFProtection::generateToken() ?? "", ENT_QUOTES, 'UTF-8') ?>';
const CSRFToken = `_csrf_token=${encodeURIComponent(CSRFTokenValue)}`;

// Debug: Log CSRF token info
console.log('CSRF Token Value:', CSRFTokenValue ? CSRFTokenValue.substring(0, 8) + '...' : 'EMPTY');
console.log('CSRF Token Format:', CSRFToken ? CSRFToken.substring(0, 20) + '...' : 'EMPTY');

// Functions will be defined below due to hoisting

// Delete asset
function deleteAsset(assetId) {
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

// Refresh assets
function refreshAssets() {
    window.location.reload();
}

// Export to Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=assets/export&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Verify legacy asset
function verifyAsset(assetId) {
    console.log('verifyAsset called with ID:', assetId);
    
    if (!CSRFTokenValue) {
        console.error('CSRF token is missing!');
        showAlert('error', 'Security token missing. Please refresh the page.');
        return;
    }
    
    if (confirm('Are you sure you want to verify this legacy asset?')) {
        console.log('User confirmed verification');
        
        const requestBody = `asset_id=${assetId}&${CSRFToken}`;
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
            console.log('Response headers:', response.headers);
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

// Authorize legacy asset
function authorizeAsset(assetId) {
    console.log('authorizeAsset called with ID:', assetId);
    
    if (!CSRFTokenValue) {
        console.error('CSRF token is missing!');
        showAlert('error', 'Security token missing. Please refresh the page.');
        return;
    }
    
    if (confirm('Are you sure you want to authorize this legacy asset as project property?')) {
        console.log('User confirmed authorization');
        
        const requestBody = `asset_id=${assetId}&${CSRFToken}`;
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

// Show alert messages
function showAlert(type, message) {
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
    alertDiv.innerHTML = `
        <i class="bi bi-${iconClass} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert after page header
    const pageHeader = document.querySelector('.border-bottom');
    pageHeader.parentNode.insertBefore(alertDiv, pageHeader.nextSibling);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Enhanced search functionality with standardization
class EnhancedAssetSearch {
    constructor() {
        this.searchInput = document.getElementById('search');
        this.searchStatus = document.getElementById('search-status');
        this.searchIcon = document.getElementById('search-icon');
        this.searchFeedback = document.getElementById('search-feedback');
        this.suggestions = document.getElementById('search-suggestions');
        this.debounceTimer = null;
        
        this.init();
    }
    
    init() {
        if (!this.searchInput) return;
        
        this.searchInput.addEventListener('input', (e) => this.handleSearch(e));
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.target.form.submit();
            }
        });
        
        // Initialize on page load if there's a search term
        if (this.searchInput.value.trim()) {
            this.validateSearch(this.searchInput.value.trim());
        }
    }
    
    handleSearch(event) {
        const value = event.target.value.trim();
        
        if (value.length < 2) {
            this.clearFeedback();
            return;
        }
        
        this.updateSearchStatus('searching');
        
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.validateSearch(value);
        }, 300);
    }
    
    async validateSearch(query) {
        try {
            const response = await fetch('/api/assets/enhanced-search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': CSRFTokenValue
                },
                body: JSON.stringify({ query: query })
            });
            
            if (!response.ok) {
                throw new Error('Search validation failed');
            }
            
            const data = await response.json();
            this.handleSearchResult(data, query);
            
        } catch (error) {
            console.warn('Enhanced search unavailable, falling back to basic search:', error);
            this.updateSearchStatus('basic');
            this.searchFeedback.innerHTML = '<i class="bi bi-info-circle me-1"></i>Basic search mode';
        }
    }
    
    handleSearchResult(result, originalQuery) {
        if (result.corrected && result.corrected !== originalQuery) {
            this.updateSearchStatus('corrected');
            this.searchFeedback.innerHTML = `
                <i class="bi bi-lightbulb me-1"></i>
                Did you mean: <strong>${result.corrected}</strong>?
                <button type="button" class="btn btn-sm btn-link p-0 ms-1" 
                        onclick="assetSearch.applySuggestion('${result.corrected}')">
                    Use this
                </button>
            `;
        } else if (result.suggestions && result.suggestions.length > 0) {
            this.updateSuggestions(result.suggestions);
            this.updateSearchStatus('suggestions');
            this.searchFeedback.innerHTML = `<i class="bi bi-lightbulb me-1"></i>Showing ${result.suggestions.length} suggestions`;
        } else {
            this.updateSearchStatus('valid');
            this.searchFeedback.innerHTML = '';
        }
        
        if (result.disciplines && result.disciplines.length > 0) {
            this.searchFeedback.innerHTML += `
                <div class="mt-1">
                    <small class="text-muted">
                        <i class="bi bi-tags me-1"></i>Disciplines: ${result.disciplines.join(', ')}
                    </small>
                </div>
            `;
        }
    }
    
    updateSearchStatus(status) {
        const iconClasses = {
            'searching': 'bi-arrow-clockwise text-primary spin',
            'corrected': 'bi-lightbulb text-warning',
            'suggestions': 'bi-list text-info',
            'valid': 'bi-check-circle text-success',
            'basic': 'bi-search text-muted'
        };
        
        this.searchIcon.className = `bi ${iconClasses[status] || 'bi-search text-muted'}`;
    }
    
    updateSuggestions(suggestions) {
        this.suggestions.innerHTML = '';
        suggestions.slice(0, 8).forEach(suggestion => {
            const option = document.createElement('option');
            option.value = suggestion;
            this.suggestions.appendChild(option);
        });
    }
    
    applySuggestion(suggestion) {
        this.searchInput.value = suggestion;
        this.clearFeedback();
        // Auto-submit the form
        this.searchInput.form.submit();
    }
    
    clearFeedback() {
        this.searchFeedback.innerHTML = '';
        this.suggestions.innerHTML = '';
        this.updateSearchStatus('basic');
    }
}

// Initialize enhanced search
let assetSearch;
document.addEventListener('DOMContentLoaded', function() {
    assetSearch = new EnhancedAssetSearch();
});

// Auto-submit form on filter change (enhanced)
document.addEventListener('DOMContentLoaded', function() {
    // Get the filter form by finding the form that contains the hidden route input
    const filterForm = document.querySelector('form[method="GET"]');
    
    if (filterForm) {
        const filterInputs = filterForm.querySelectorAll('select');
        
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Clear search when filters change for better UX
                const searchInput = filterForm.querySelector('input[name="search"]');
                if (searchInput && searchInput !== this) {
                    // Only clear if changing a different filter
                    const currentSearch = searchInput.value.trim();
                    if (currentSearch && this.value) {
                        // Show confirmation for destructive action
                        if (confirm('Changing filters will clear your current search. Continue?')) {
                            searchInput.value = '';
                            filterForm.submit();
                        }
                        return;
                    }
                }
                filterForm.submit();
            });
        });
        
        // Enhanced search handling is now managed by EnhancedAssetSearch class
    }
    
    // Add keyboard shortcuts
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
    
    // Responsive table enhancements
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
});
</script>

<style>
/* Enhanced search styles */
.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

#search-feedback {
    min-height: 20px;
    transition: all 0.3s ease;
}

.input-group .form-control:focus + .input-group-text {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Keyboard shortcut hint */
.search-hint {
    position: absolute;
    right: 45px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 11px;
    color: #6c757d;
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    border: 1px solid #dee2e6;
    pointer-events: none;
}

/* Responsive table improvements */
@media (max-width: 1200px) {
    .table-responsive {
        font-size: 0.875rem;
    }
}

@media (max-width: 992px) {
    .btn-group-sm .btn {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .badge {
        font-size: 0.65em;
        padding: 0.25em 0.4em;
    }
}

@media (max-width: 768px) {
    .search-hint {
        display: none;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.2rem 0.3rem;
        font-size: 0.7rem;
    }
    
    .table-responsive {
        font-size: 0.75rem;
    }
    
    .badge {
        font-size: 0.6em;
        padding: 0.2em 0.3em;
    }
}

@media (max-width: 576px) {
    .table-responsive {
        font-size: 0.7rem;
    }
    
    .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
    }
    
    .btn-group .btn {
        flex: 1;
        min-width: 36px;
    }
    
    /* Stack buttons vertically on very small screens */
    .btn-group-sm .btn {
        padding: 0.15rem 0.25rem;
        font-size: 0.65rem;
        border-radius: 0.2rem;
    }
}

/* Card uniformity improvements */
.card {
    border: 1px solid rgba(0,0,0,.125);
    border-radius: 0.375rem;
}

.card-body {
    padding: 1.25rem;
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
}

@media (max-width: 576px) {
    .card-body {
        padding: 0.75rem;
    }
}

/* Button group responsiveness */
.btn-toolbar {
    flex-wrap: wrap;
    gap: 0.5rem;
}

.btn-group {
    flex-wrap: wrap;
}

@media (max-width: 576px) {
    .btn-toolbar .btn-group {
        width: 100%;
    }
    
    .btn-toolbar .btn {
        flex: 1;
        min-width: 0;
    }
}

/* Table action buttons responsive stacking */
@media (max-width: 576px) {
    .table td:last-child .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .table td:last-child .btn-group .btn {
        border-radius: 0.25rem !important;
        margin-bottom: 2px;
    }
    
    .table td:last-child .btn-group .btn:last-child {
        margin-bottom: 0;
    }
}

/* Role-based Dashboard Cards - Enhanced Responsive Design */
.row.mb-4 {
    margin-left: -0.375rem;
    margin-right: -0.375rem;
}

.row.mb-4 > [class*="col-"] {
    padding-left: 0.375rem;
    padding-right: 0.375rem;
}

/* Dashboard card enhancements for equal heights and optimal spacing */
.card.h-100 {
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card.h-100:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.12);
}

.card.h-100 .card-body {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.card.h-100 .card-footer {
    flex-shrink: 0;
    margin-top: auto;
    border-top: 1px solid rgba(255,255,255,0.2);
    background-color: rgba(0,0,0,0.1);
}

/* Role-specific card background variations */
.bg-primary-dark {
    background-color: rgba(13, 110, 253, 0.8) !important;
}

.bg-success-dark {
    background-color: rgba(25, 135, 84, 0.8) !important;
}

.bg-info-dark {
    background-color: rgba(13, 202, 240, 0.8) !important;
}

.bg-warning-dark {
    background-color: rgba(255, 193, 7, 0.8) !important;
}

.bg-danger-dark {
    background-color: rgba(220, 53, 69, 0.8) !important;
}

.bg-secondary-dark {
    background-color: rgba(108, 117, 125, 0.8) !important;
}

/* Responsive breakpoints for dashboard cards */
/* Extra Large screens (1400px and up) - 4 cards per row with more spacing */
@media (min-width: 1400px) {
    .row.mb-4 {
        margin-left: -0.75rem;
        margin-right: -0.75rem;
    }
    
    .row.mb-4 > [class*="col-"] {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .card.h-100 .card-body {
        padding: 1.5rem;
    }
}

/* Large screens (1200px to 1399px) - 4 cards per row, standard spacing */
@media (min-width: 1200px) and (max-width: 1399px) {
    .card.h-100 .card-body {
        padding: 1.25rem;
    }
    
    .card.h-100 h3 {
        font-size: 1.75rem;
    }
}

/* Medium screens (992px to 1199px) - 3 cards per row */
@media (min-width: 992px) and (max-width: 1199px) {
    .row.mb-4 .col-lg-3 {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
    }
    
    .card.h-100 .card-body {
        padding: 1rem;
    }
    
    .card.h-100 h6 {
        font-size: 0.9rem;
    }
    
    .card.h-100 h3 {
        font-size: 1.5rem;
    }
    
    .card.h-100 small {
        font-size: 0.75rem;
    }
}

/* Small screens (768px to 991px) - 2 cards per row */
@media (min-width: 768px) and (max-width: 991px) {
    .card.h-100 .card-body {
        padding: 1rem;
    }
    
    .card.h-100 h6 {
        font-size: 0.85rem;
    }
    
    .card.h-100 h3 {
        font-size: 1.4rem;
    }
    
    .card.h-100 .display-6 {
        font-size: 2.5rem;
    }
    
    .card.h-100 small {
        font-size: 0.7rem;
    }
}

/* Extra small screens (576px to 767px) - 1 card per row with optimized spacing */
@media (max-width: 767px) {
    .row.mb-4 {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        margin-bottom: 2rem !important;
    }
    
    .row.mb-4 > [class*="col-"] {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .card.h-100 {
        margin-bottom: 0.75rem;
    }
    
    .card.h-100 .card-body {
        padding: 1rem;
    }
    
    .card.h-100 h6 {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .card.h-100 h3 {
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }
    
    .card.h-100 .display-6 {
        font-size: 2.25rem;
    }
    
    .card.h-100 small {
        font-size: 0.8rem;
        opacity: 0.8;
    }
    
    .card.h-100 .card-footer {
        padding: 0.75rem 1rem;
    }
    
    .card.h-100 .card-footer small {
        font-size: 0.75rem;
    }
}

/* Very small screens (below 576px) - Single column with compact design */
@media (max-width: 575px) {
    .row.mb-4 {
        margin-left: -0.25rem;
        margin-right: -0.25rem;
    }
    
    .row.mb-4 > [class*="col-"] {
        padding-left: 0.25rem;
        padding-right: 0.25rem;
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .card.h-100 {
        margin-bottom: 0.5rem;
        border-radius: 0.5rem;
    }
    
    .card.h-100 .card-body {
        padding: 0.75rem;
    }
    
    .card.h-100 .d-flex {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
    
    .card.h-100 h6 {
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
        order: 2;
    }
    
    .card.h-100 h3 {
        font-size: 2rem;
        margin-bottom: 0.25rem;
        order: 1;
    }
    
    .card.h-100 .display-6 {
        font-size: 2rem;
        order: 1;
        align-self: center;
        margin-bottom: 0.5rem;
    }
    
    .card.h-100 small {
        font-size: 0.75rem;
        order: 3;
        margin-top: 0.25rem;
    }
    
    .card.h-100 .card-footer {
        padding: 0.5rem;
        text-align: center;
    }
    
    .card.h-100 .card-footer small {
        font-size: 0.7rem;
    }
}

/* Mobile-first adjustments */
@media (max-width: 480px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn-toolbar {
        width: 100%;
        justify-content: stretch;
    }
    
    .form-label {
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .form-select-sm, .form-control-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
}

/* Card content optimization for readability */
@media (max-width: 768px) {
    .card.h-100 .opacity-75 {
        opacity: 0.85;
    }
    
    .card.h-100 .bi {
        margin-right: 0.25rem;
    }
}

/* Ensure proper contrast and accessibility */
.card.text-white small {
    color: rgba(255, 255, 255, 0.85) !important;
}

.card.text-white .opacity-75 {
    opacity: 0.85 !important;
}
</style>

<script>
// Add search hint and mobile optimizations
document.addEventListener('DOMContentLoaded', function() {
    const searchGroup = document.querySelector('#search')?.closest('.input-group');
    if (searchGroup && window.innerWidth > 768) {
        const hint = document.createElement('span');
        hint.className = 'search-hint';
        hint.textContent = '⌘K';
        hint.title = 'Keyboard shortcut: Ctrl+K (Windows) or Cmd+K (Mac)';
        searchGroup.style.position = 'relative';
        searchGroup.appendChild(hint);
    }
    
    // Mobile-specific enhancements
    if (window.innerWidth <= 768) {
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
            scrollIndicator.innerHTML = '<i class="bi bi-arrow-left-right me-1"></i>Swipe horizontally to see more columns';
            tableContainer.parentNode.appendChild(scrollIndicator);
        }
    }
    
    // Responsive card height equalizer
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
});
</script>

<script>
// Verify workflow functions are properly defined
console.log('=== Function Verification ===');
console.log('verifyAsset function defined:', typeof verifyAsset);
console.log('authorizeAsset function defined:', typeof authorizeAsset);
console.log('deleteAsset function defined:', typeof deleteAsset);
console.log('showAlert function defined:', typeof showAlert);

// Make functions globally accessible (fallback)
if (typeof window !== 'undefined') {
    window.verifyAsset = verifyAsset;
    window.authorizeAsset = authorizeAsset;
    window.deleteAsset = deleteAsset;
    window.showAlert = showAlert;
}

// Alternative event delegation for workflow buttons (fallback method)
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
            verifyAsset(parseInt(assetId));
        } else if (action === 'authorize' && assetId) {
            console.log('Calling authorizeAsset via event delegation');
            authorizeAsset(parseInt(assetId));
        }
    }
});
</script>

<?php
// Include enhanced verification and authorization modals
include APP_ROOT . '/views/assets/enhanced_verification_modal.php';
include APP_ROOT . '/views/assets/enhanced_authorization_modal.php';
?>

<script src="assets/js/enhanced-verification.js"></script>
<script>
// Make user role available globally for enhanced verification
const userRole = '<?= htmlspecialchars($user['role_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>';
</script>

