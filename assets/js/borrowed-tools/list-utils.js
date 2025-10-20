/**
 * Borrowed Tools List Utilities
 * Extracted from _borrowed_tools_list.php inline JavaScript
 * Handles batch toggle, export, and print functionality
 */

/**
 * Initialize batch toggle functionality for mobile view
 */
export function initMobileBatchToggle() {
    document.querySelectorAll('.batch-toggle-mobile').forEach(button => {
        button.addEventListener('click', function() {
            const batchId = this.getAttribute('data-batch-id');
            const batchItems = document.querySelector(`.batch-items-mobile[data-batch-id="${batchId}"]`);
            const icon = this.querySelector('i');

            if (batchItems.style.display === 'none') {
                batchItems.style.display = 'block';
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-up');
                this.innerHTML = '<i class="bi bi-chevron-up me-1"></i>Hide Items';
            } else {
                batchItems.style.display = 'none';
                icon.classList.remove('bi-chevron-up');
                icon.classList.add('bi-chevron-down');
                this.innerHTML = '<i class="bi bi-chevron-down me-1"></i>View Items';
            }
        });
    });
}

/**
 * Initialize batch toggle functionality for desktop table
 */
export function initDesktopBatchToggle() {
    document.querySelectorAll('.batch-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const batchId = this.getAttribute('data-batch-id');
            const batchItemsRow = document.querySelector(`.batch-items-row[data-batch-id="${batchId}"]`);
            const icon = this.querySelector('i');

            if (batchItemsRow.style.display === 'none') {
                batchItemsRow.style.display = '';
                icon.classList.remove('bi-chevron-right');
                icon.classList.add('bi-chevron-down');
            } else {
                batchItemsRow.style.display = 'none';
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-right');
            }
        });
    });
}

/**
 * Export table to Excel
 */
export function exportToExcel() {
    const table = document.getElementById('borrowedToolsTable');
    if (!table) {
        console.error('Table not found');
        return;
    }

    // Simple CSV export (can be enhanced later)
    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');

        for (let j = 0; j < cols.length; j++) {
            row.push(cols[j].innerText);
        }

        csv.push(row.join(','));
    }

    // Create download link
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', `borrowed-tools-${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Print table
 */
export function printTable() {
    window.print();
}

/**
 * Send overdue reminder
 * @param {number} toolId - Borrowed tool ID
 */
export function sendOverdueReminder(toolId) {
    // Placeholder - implement actual reminder logic
    console.log(`Sending overdue reminder for tool ID: ${toolId}`);
    alert('Overdue reminder functionality will be implemented soon.');
}

/**
 * Refresh borrowed tools list
 */
export function refreshBorrowedTools() {
    location.reload();
}

/**
 * Initialize all list utilities
 */
export function initListUtils() {
    initMobileBatchToggle();
    initDesktopBatchToggle();

    // Attach event listeners to buttons
    const exportBtn = document.querySelector('button[onclick="exportToExcel()"]');
    const printBtn = document.querySelector('button[onclick="printTable()"]');
    const refreshBtn = document.querySelector('button[onclick="refreshBorrowedTools()"]');

    if (exportBtn) {
        exportBtn.removeAttribute('onclick');
        exportBtn.addEventListener('click', exportToExcel);
    }

    if (printBtn) {
        printBtn.removeAttribute('onclick');
        printBtn.addEventListener('click', printTable);
    }

    if (refreshBtn) {
        refreshBtn.removeAttribute('onclick');
        refreshBtn.addEventListener('click', refreshBorrowedTools);
    }

    // Attach event listener for overdue reminder buttons
    document.querySelectorAll('button[onclick^="sendOverdueReminder"]').forEach(btn => {
        const toolId = btn.getAttribute('onclick').match(/\d+/)[0];
        btn.removeAttribute('onclick');
        btn.addEventListener('click', () => sendOverdueReminder(toolId));
    });
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initListUtils);
} else {
    initListUtils();
}

// Export default
export default {
    initListUtils,
    initMobileBatchToggle,
    initDesktopBatchToggle,
    exportToExcel,
    printTable,
    sendOverdueReminder,
    refreshBorrowedTools
};
