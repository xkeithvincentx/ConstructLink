/**
 * DataTable Helper - Reusable DataTables initialization utilities
 * Provides consistent DataTables configuration across the application
 */

const DataTableHelper = {
    /**
     * Default configuration for DataTables
     */
    defaultConfig: {
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        language: {
            search: '_INPUT_',
            searchPlaceholder: 'Search...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'Showing 0 to 0 of 0 entries',
            infoFiltered: '(filtered from _TOTAL_ total entries)',
            paginate: {
                first: '<i class="bi bi-chevron-double-left"></i>',
                previous: '<i class="bi bi-chevron-left"></i>',
                next: '<i class="bi bi-chevron-right"></i>',
                last: '<i class="bi bi-chevron-double-right"></i>'
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        autoWidth: false
    },

    /**
     * Initialize a DataTable with default or custom configuration
     *
     * @param {string|jQuery} selector - Table selector
     * @param {Object} customConfig - Custom configuration to merge with defaults
     * @returns {DataTable} DataTable instance
     */
    init: function(selector, customConfig = {}) {
        const config = { ...this.defaultConfig, ...customConfig };
        return $(selector).DataTable(config);
    },

    /**
     * Initialize a simple DataTable (no pagination, no search)
     *
     * @param {string|jQuery} selector - Table selector
     * @param {Object} customConfig - Custom configuration
     * @returns {DataTable} DataTable instance
     */
    initSimple: function(selector, customConfig = {}) {
        const config = {
            ...this.defaultConfig,
            paging: false,
            searching: false,
            info: false,
            ...customConfig
        };
        return $(selector).DataTable(config);
    },

    /**
     * Initialize a DataTable with export buttons
     *
     * @param {string|jQuery} selector - Table selector
     * @param {Array} buttons - Button types (e.g., ['copy', 'excel', 'pdf'])
     * @param {Object} customConfig - Custom configuration
     * @returns {DataTable} DataTable instance
     */
    initWithExport: function(selector, buttons = ['copy', 'excel', 'csv', 'pdf', 'print'], customConfig = {}) {
        const config = {
            ...this.defaultConfig,
            buttons: buttons,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12 col-md-6"B>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            ...customConfig
        };
        return $(selector).DataTable(config);
    },

    /**
     * Initialize a server-side processing DataTable
     *
     * @param {string|jQuery} selector - Table selector
     * @param {string} ajaxUrl - URL for server-side processing
     * @param {Array} columns - Column definitions
     * @param {Object} customConfig - Custom configuration
     * @returns {DataTable} DataTable instance
     */
    initServerSide: function(selector, ajaxUrl, columns, customConfig = {}) {
        const config = {
            ...this.defaultConfig,
            processing: true,
            serverSide: true,
            ajax: ajaxUrl,
            columns: columns,
            ...customConfig
        };
        return $(selector).DataTable(config);
    },

    /**
     * Reload a DataTable
     *
     * @param {DataTable} table - DataTable instance
     * @param {boolean} resetPaging - Whether to reset paging
     */
    reload: function(table, resetPaging = false) {
        if (table && typeof table.ajax !== 'undefined') {
            table.ajax.reload(null, resetPaging);
        } else if (table) {
            table.draw(resetPaging);
        }
    },

    /**
     * Destroy a DataTable
     *
     * @param {string|jQuery} selector - Table selector
     */
    destroy: function(selector) {
        const table = $(selector).DataTable();
        if (table) {
            table.destroy();
        }
    },

    /**
     * Get configuration for status column rendering
     *
     * @param {Object} statusConfig - Status badge configuration
     * @returns {Object} Column definition
     */
    getStatusColumn: function(statusConfig = {}) {
        const defaultStatuses = {
            'Active': 'success',
            'Inactive': 'secondary',
            'Pending': 'warning',
            'Approved': 'success',
            'Rejected': 'danger',
            'Borrowed': 'primary',
            'Returned': 'success',
            'Overdue': 'danger',
            'Available': 'success',
            'In Use': 'warning',
            'Under Maintenance': 'info'
        };

        const statusMap = { ...defaultStatuses, ...statusConfig };

        return {
            render: function(data, type, row) {
                const badgeClass = statusMap[data] || 'secondary';
                return `<span class="badge bg-${badgeClass}">${data}</span>`;
            }
        };
    },

    /**
     * Get configuration for date column rendering
     *
     * @param {string} format - Date format (default: 'MMM DD, YYYY')
     * @returns {Object} Column definition
     */
    getDateColumn: function(format = 'MMM DD, YYYY') {
        return {
            render: function(data, type, row) {
                if (!data) return '-';
                if (type === 'sort' || type === 'type') {
                    return data;
                }
                return moment(data).format(format);
            }
        };
    },

    /**
     * Get configuration for action buttons column
     *
     * @param {Function} buttonRenderer - Function that returns button HTML
     * @returns {Object} Column definition
     */
    getActionColumn: function(buttonRenderer) {
        return {
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                return buttonRenderer(row);
            }
        };
    }
};

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DataTableHelper;
}
