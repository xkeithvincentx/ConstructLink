/**
 * Transfers List Utilities
 * God-tier production code with full descriptive naming
 * @version 1.0.0
 *
 * ARCHITECTURE:
 * - Vanilla JavaScript (no frameworks, no ES6 modules)
 * - Auto-initialization on DOM ready
 * - Follows borrowed-tools pattern for consistency
 *
 * FEATURES:
 * - Records per page selector (5, 10, 25, 50, 100)
 * - Export to Excel functionality
 * - Print table functionality
 *
 * SECURITY:
 * - CSV sanitization prevents formula injection
 * - URL API ensures safe parameter manipulation
 * - No XSS vulnerabilities in DOM manipulation
 */

(function() {
    'use strict';

    // ============================================================================
    // CONFIGURATION
    // ============================================================================

    /**
     * Configuration for records per page selector
     * Allowed values: 5, 10, 25, 50, 100
     */
    const RECORDS_PER_PAGE_OPTIONS = [5, 10, 25, 50, 100];

    // ============================================================================
    // UTILITY FUNCTIONS
    // ============================================================================

    /**
     * Sanitize text for CSV export
     * Prevents CSV formula injection attacks
     *
     * @param {string} text - Text to sanitize
     * @returns {string} Sanitized CSV-safe text
     */
    function sanitizeCSV(text) {
        if (!text) return '""';

        // Prevent CSV formula injection by prefixing with single quote
        const sanitized = /^[=+\-@]/.test(text) ? "'" + text : text;

        // Escape double quotes and wrap in quotes
        return '"' + sanitized.replace(/"/g, '""') + '"';
    }

    /**
     * Show notification (placeholder for toast system)
     *
     * @param {string} message - Notification message
     * @param {string} type - Notification type (info, success, warning, error)
     */
    function showNotification(message, type) {
        type = type || 'info';
        console[type === 'error' ? 'error' : 'info']('[' + type + '] ' + message);
    }

    // ============================================================================
    // RECORDS PER PAGE HANDLER
    // ============================================================================

    /**
     * Initialize records per page selector
     * Auto-submits form when user changes selection
     * Resets to page 1 when changing per_page value
     */
    function initRecordsPerPageSelector() {
        const recordsPerPageSelect = document.getElementById('recordsPerPage');

        if (!recordsPerPageSelect) {
            return; // Element not found
        }

        recordsPerPageSelect.addEventListener('change', function() {
            const perPage = this.value;

            // Validate selected value against whitelist
            if (RECORDS_PER_PAGE_OPTIONS.indexOf(parseInt(perPage)) === -1) {
                showNotification('Invalid records per page value', 'error');
                return;
            }

            try {
                // Use URL API for safe parameter manipulation
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', perPage);
                url.searchParams.delete('page'); // Reset to page 1

                // Reload page with new parameters
                window.location.href = url.toString();
            } catch (error) {
                showNotification('Failed to update records per page', 'error');
                console.error('Records per page error:', error);
            }
        });
    }

    // ============================================================================
    // EXPORT TO EXCEL
    // ============================================================================

    /**
     * Export transfers table to CSV file
     * Excludes action columns and batch selection columns
     * Sanitizes all cell content to prevent CSV formula injection
     */
    function exportToExcel() {
        try {
            const table = document.querySelector('#transfersTable, table');

            if (!table) {
                showNotification('Table not found for export', 'error');
                return;
            }

            // Extract table data and sanitize
            const rows = table.querySelectorAll('tr');
            const csvData = [];

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.querySelectorAll('td, th');
                const rowData = [];

                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    // Exclude action columns and batch select columns
                    if (!cell.classList.contains('batch-select-cell') &&
                        !cell.classList.contains('actions-column')) {
                        rowData.push(sanitizeCSV(cell.innerText.trim()));
                    }
                }

                if (rowData.length > 0) {
                    csvData.push(rowData.join(','));
                }
            }

            if (csvData.length === 0) {
                showNotification('No data to export', 'warning');
                return;
            }

            // Create blob and download link
            const csvContent = csvData.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);

            link.href = url;
            link.download = 'asset-transfers-' + new Date().toISOString().split('T')[0] + '.csv';
            link.style.display = 'none';

            // Trigger download
            document.body.appendChild(link);
            link.click();

            // Cleanup
            setTimeout(function() {
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }, 100);

            showNotification('Export successful', 'success');
        } catch (error) {
            showNotification('Export failed. Please try again.', 'error');
            console.error('Export error:', error);
        }
    }

    // ============================================================================
    // PRINT TABLE
    // ============================================================================

    /**
     * Print transfers table
     * Uses browser's native print functionality
     */
    function printTable() {
        try {
            window.print();
        } catch (error) {
            showNotification('Print failed. Please try again.', 'error');
            console.error('Print error:', error);
        }
    }

    // ============================================================================
    // REFRESH LIST
    // ============================================================================

    /**
     * Refresh transfers list
     * Reloads current page preserving URL parameters
     */
    function refreshTransfers() {
        try {
            location.reload();
        } catch (error) {
            showNotification('Refresh failed. Please try again.', 'error');
            console.error('Refresh error:', error);
        }
    }

    // ============================================================================
    // INITIALIZATION
    // ============================================================================

    /**
     * Initialize all transfers list utilities
     * Attaches event listeners to interactive elements
     */
    function initListUtils() {
        try {
            // Initialize records per page selector
            initRecordsPerPageSelector();

            // Attach export button listener
            const exportButton = document.getElementById('exportBtn');
            if (exportButton) {
                exportButton.addEventListener('click', exportToExcel);
            }

            // Attach print button listener
            const printButton = document.getElementById('printBtn');
            if (printButton) {
                printButton.addEventListener('click', printTable);
            }

            // Attach refresh button listener (if exists)
            const refreshButton = document.getElementById('refreshBtn');
            if (refreshButton) {
                refreshButton.addEventListener('click', refreshTransfers);
            }
        } catch (error) {
            console.error('List utilities initialization error:', error);
        }
    }

    // ============================================================================
    // AUTO-INITIALIZATION
    // ============================================================================

    /**
     * Auto-initialize on DOM ready
     * Supports both loading and interactive/complete states
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initListUtils);
    } else {
        // DOM already loaded
        initListUtils();
    }

})();
