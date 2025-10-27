/**
 * Borrowed Tools Index Page - JavaScript Module
 * Developed by: Ranoa Digital Solutions
 *
 * Handles all JavaScript functionality for the borrowed tools index page:
 * - Batch expansion/collapse
 * - Modal loading and form handling
 * - AJAX operations
 * - Filtering and sorting
 * - Auto-refresh functionality
 */

import { AjaxHandler } from './ajax-handler.js';

// Store CSRF token for AJAX requests
let csrfToken = '';
let ajax = null;

// Track incidents reported during current return session
const reportedIncidents = {};

/**
 * Initialize the module
 */
export function init(token) {
    csrfToken = token;
    ajax = new AjaxHandler(csrfToken);

    initializeEventListeners();
    initializeFilters();
    initializeSorting();
    initializeAutoRefresh();
}

/**
 * Initialize all event listeners
 */
function initializeEventListeners() {
    // Batch action modals - load batch data when modal opens (except return modal)
    document.querySelectorAll('.batch-action-btn').forEach(button => {
        button.addEventListener('click', function() {
            const batchId = this.getAttribute('data-batch-id');
            const modalId = this.getAttribute('data-bs-target').substring(1);

            const modal = document.getElementById(modalId);
            if (modal) {
                modal.setAttribute('data-batch-id', batchId);
                // Don't use loadBatchItemsIntoModal for return and extend modals - they have custom handlers
                if (modalId !== 'batchReturnModal' && modalId !== 'batchExtendModal') {
                    loadBatchItemsIntoModal(batchId, modalId);
                }
            }
        });
    });

    // Batch return modal
    const batchReturnModal = document.getElementById('batchReturnModal');
    if (batchReturnModal) {
        batchReturnModal.addEventListener('shown.bs.modal', handleBatchReturnModalShow);
    }

    // Batch return form
    const batchReturnForm = document.getElementById('batchReturnForm');
    if (batchReturnForm) {
        batchReturnForm.addEventListener('submit', handleBatchReturnSubmit);
    }

    // Batch extend modal
    const batchExtendModal = document.getElementById('batchExtendModal');
    if (batchExtendModal) {
        batchExtendModal.addEventListener('shown.bs.modal', handleBatchExtendModalShow);
    }

    // Batch extend form
    const batchExtendForm = document.getElementById('batchExtendForm');
    if (batchExtendForm) {
        batchExtendForm.addEventListener('submit', handleBatchExtendSubmit);
    }

    // Select all extend checkbox
    const selectAllExtend = document.getElementById('selectAllExtend');
    if (selectAllExtend) {
        selectAllExtend.addEventListener('change', handleSelectAllExtend);
    }

    // Individual extend checkboxes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-extend-checkbox')) {
            updateSelectAllExtendCheckbox();
        }
    });

    // Incident report buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.report-incident-item-btn')) {
            handleIncidentReportClick(e);
        }
    });

    // Incident form
    const quickIncidentForm = document.getElementById('quickIncidentForm');
    if (quickIncidentForm) {
        quickIncidentForm.addEventListener('submit', handleIncidentSubmit);
    }

    // Truncated text hover
    const truncatedElements = document.querySelectorAll('.text-truncate');
    truncatedElements.forEach(element => {
        if (element.scrollWidth > element.clientWidth) {
            element.classList.add('text-truncated-hover');
        }
    });

    // Overdue row hover effects
    const overdueRows = document.querySelectorAll('.table-danger');
    overdueRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 0 10px rgba(220, 53, 69, 0.3)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.boxShadow = 'none';
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });
}

/**
 * Load batch items into modal
 */
function loadBatchItemsIntoModal(batchId, modalId) {
    const batchItemsRow = document.querySelector(`.batch-items-row[data-batch-id="${batchId}"]`);
    if (!batchItemsRow) return;

    const modal = document.getElementById(modalId);
    const itemsContainer = modal.querySelector('.batch-modal-items');

    if (itemsContainer) {
        const batchTable = batchItemsRow.querySelector('table').cloneNode(true);
        itemsContainer.innerHTML = '';
        itemsContainer.appendChild(batchTable);
    }

    const batchIdInput = modal.querySelector('input[name="batch_id"]');
    if (batchIdInput) {
        batchIdInput.value = batchId;
    }
}

/**
 * Handle batch return modal show
 */
function handleBatchReturnModalShow(event) {
    // Get batch ID - it's set by the click handler on the modal element
    const batchId = this.getAttribute('data-batch-id');

    if (!batchId) {
        console.error('ERROR: Batch ID not found on modal');
        return;
    }

    console.log('Loading return items for batch ID:', batchId);

    // Find the hidden batch items row
    const batchItemsRow = document.querySelector(`.batch-items-row[data-batch-id="${batchId}"]`);

    if (!batchItemsRow) {
        // Debug: show what batch IDs are available
        const allBatchRows = document.querySelectorAll('.batch-items-row');
        const availableIds = Array.from(allBatchRows).map(row => row.getAttribute('data-batch-id'));
        console.error('ERROR: Batch items row not found for ID:', batchId, '| Available IDs:', availableIds);
        return;
    }

    // Set form values
    document.getElementById('returnBatchId').value = batchId;
    document.getElementById('returnCsrfToken').value = csrfToken;

    // Get items from the hidden batch items table
    const items = batchItemsRow.querySelectorAll('.batch-items-table tbody tr');
    const returnTableBody = document.getElementById('batchReturnItems');

    if (!returnTableBody) {
        console.error('Return table body not found');
        return;
    }

    returnTableBody.innerHTML = '';

    if (items.length === 0) {
        console.error('No items found in batch. batchId:', batchId);
        returnTableBody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">No items found in batch</td></tr>';
        return;
    }

    items.forEach((item, index) => {
        const cells = item.querySelectorAll('td');
        if (cells.length < 10) {
            console.warn('Invalid row structure, skipping item', index);
            return;
        }

        const borrowedToolId = item.getAttribute('data-item-id') || item.dataset.id || '';
        const assetId = item.getAttribute('data-asset-id') || item.dataset.assetId || '';

        const itemNumber = cells[0].textContent.trim();
        const equipmentCell = cells[1];
        const equipmentName = equipmentCell.querySelector('strong')?.textContent || equipmentCell.textContent;
        const equipmentCategory = equipmentCell.querySelector('small')?.textContent || '';
        const reference = cells[2].textContent.trim();
        const borrowed = parseInt(cells[3].textContent.trim()) || 1;
        const returned = parseInt(cells[4].textContent.trim()) || 0;
        const remaining = borrowed - returned;
        const returnedCondition = cells[6].textContent.trim();
        const returnedNotes = cells[9].textContent.trim();

        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="text-center">${index + 1}</td>
            <td>
                <strong>${equipmentName}</strong>
                ${equipmentCategory ? `<br><small class="text-muted">${equipmentCategory}</small>` : ''}
                ${remaining === 0 ? '<br><small class="text-success"><i class="bi bi-check-circle-fill"></i> Fully Returned</small>' : ''}
            </td>
            <td><code>${reference}</code></td>
            <td class="text-center"><span class="badge bg-primary">${borrowed}</span></td>
            <td class="text-center"><span class="badge bg-success">${returned}</span></td>
            <td class="text-center"><span class="badge bg-${remaining > 0 ? 'warning' : 'secondary'}">${remaining}</span></td>
            <td class="text-center">
                ${remaining > 0 ? `
                <input type="number"
                       class="form-control form-control-sm qty-in-input"
                       name="qty_in[]"
                       min="0"
                       max="${remaining}"
                       value="${remaining}"
                       style="width: 70px; display: inline-block;"
                       aria-label="Return quantity for ${equipmentName}">
                <input type="hidden" name="item_id[]" value="${borrowedToolId}">
                ` : '<span class="text-muted">-</span>'}
            </td>
            <td>
                ${remaining > 0 ? `
                <select class="form-select form-select-sm condition-select" name="condition[]" aria-label="Condition for ${equipmentName}">
                    <option value="Good" selected>Good</option>
                    <option value="Fair">Fair</option>
                    <option value="Poor">Poor</option>
                    <option value="Damaged">Damaged</option>
                    <option value="Lost">Lost</option>
                </select>
                ` : `<span class="badge bg-info">${returnedCondition || 'Good'}</span>`}
            </td>
            <td>
                ${remaining > 0 ? `<input type="text" class="form-control form-control-sm" name="item_notes[]" placeholder="Optional" aria-label="Notes for ${equipmentName}">` : `<small class="text-muted">${returnedNotes !== '-' ? returnedNotes : ''}</small>`}
            </td>
            <td class="text-center">
                ${remaining > 0 ? `
                <button type="button" class="btn btn-sm btn-outline-danger report-incident-item-btn incident-btn-${borrowedToolId}"
                        data-item-id="${borrowedToolId}"
                        data-asset-id="${assetId}"
                        data-asset-ref="${reference}"
                        data-asset-name="${equipmentName}"
                        aria-label="Report incident for ${equipmentName}"
                        title="Report incident for this item">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                </button>
                <small class="d-block mt-1 text-success incident-reported-badge-${borrowedToolId}" style="display:none !important;">
                    <i class="bi bi-check-circle-fill"></i> Incident Reported
                </small>
                ` : '-'}
            </td>
        `;
        returnTableBody.appendChild(row);
    });

    console.log(`Loaded ${items.length} items into return modal for batch ${batchId}`);
}

/**
 * Handle batch return form submission
 */
async function handleBatchReturnSubmit(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('processReturnBtn');
    const originalBtnText = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Processing...';

    try {
        const formData = new FormData(this);

        const response = await fetch('index.php?route=borrowed-tools/batch/return', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('batchReturnModal'));
            modal.hide();

            let successMessage = result.message || 'Batch returned successfully!';

            const manualIncidentCount = Object.keys(reportedIncidents).length;
            if (manualIncidentCount > 0) {
                successMessage += '\n\nManually reported incidents: ' + manualIncidentCount;
                Object.entries(reportedIncidents).forEach(([itemId, incident]) => {
                    successMessage += '\n- Incident #' + incident.incident_id + ' (' + incident.type + ', ' + incident.severity + ' severity)';
                });
            }

            alert(successMessage);

            Object.keys(reportedIncidents).forEach(key => delete reportedIncidents[key]);

            window.location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to process return'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    } catch (error) {
        console.error('Batch return error:', error);
        alert('Error: Failed to process return. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

/**
 * Handle batch extend modal show
 */
function handleBatchExtendModalShow() {
    const batchId = this.getAttribute('data-batch-id');
    const batchItemsRow = document.querySelector(`.batch-items-row[data-batch-id="${batchId}"]`);

    if (!batchItemsRow) {
        console.error('Batch items row not found for batch ID:', batchId);
        return;
    }

    document.getElementById('extendBatchId').value = batchId;
    document.getElementById('extendCsrfToken').value = csrfToken;

    const itemsTable = batchItemsRow.querySelector('.batch-items-table tbody');
    const items = Array.from(itemsTable.querySelectorAll('tr[data-item-id]'));

    const extendTableBody = document.getElementById('batchExtendItems');
    extendTableBody.innerHTML = '';

    const today = new Date().toISOString().split('T')[0];
    document.getElementById('new_expected_return').setAttribute('min', today);

    items.forEach((item, index) => {
        const cells = item.querySelectorAll('td');
        if (cells.length < 8) {
            console.warn('Invalid row structure, skipping item', index);
            return;
        }

        const borrowedToolId = item.getAttribute('data-item-id');
        const equipmentName = cells[1].querySelector('strong')?.textContent || cells[1].textContent;
        const equipmentCategory = cells[1].querySelector('small')?.textContent || '';
        const reference = cells[2].textContent.trim();
        const borrowed = parseInt(cells[3].textContent.trim()) || 1;
        const returned = parseInt(cells[4].textContent.trim()) || 0;
        const remaining = borrowed - returned;
        const statusBadge = cells[8].querySelector('.badge');
        const status = statusBadge?.textContent.trim() || '';

        const mainTableRow = document.querySelector(`tr[data-batch-id="${batchId}"]`);
        const expectedReturnCell = mainTableRow?.querySelector('td:nth-child(6)');
        const expectedReturn = expectedReturnCell?.textContent.trim() || 'N/A';

        if (remaining <= 0) {
            return;
        }

        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="text-center">
                <input type="checkbox" class="form-check-input item-extend-checkbox"
                       name="item_ids[]" value="${borrowedToolId}"
                       data-remaining="${remaining}"
                       aria-label="Select ${equipmentName} for extension"
                       ${remaining > 0 ? 'checked' : 'disabled'}>
            </td>
            <td>${index + 1}</td>
            <td>
                <strong>${equipmentName}</strong>
                ${equipmentCategory ? `<br><small class="text-muted">${equipmentCategory}</small>` : ''}
            </td>
            <td>${reference}</td>
            <td class="text-center"><span class="badge bg-primary">${borrowed}</span></td>
            <td class="text-center"><span class="badge bg-warning">${remaining}</span></td>
            <td>${expectedReturn}</td>
            <td><span class="badge bg-secondary">${status}</span></td>
        `;

        extendTableBody.appendChild(row);
    });

    updateSelectAllExtendCheckbox();
}

/**
 * Handle select all extend checkbox
 */
function handleSelectAllExtend() {
    const checkboxes = document.querySelectorAll('.item-extend-checkbox:not(:disabled)');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
}

/**
 * Update select all extend checkbox state
 */
function updateSelectAllExtendCheckbox() {
    const checkboxes = document.querySelectorAll('.item-extend-checkbox:not(:disabled)');
    const selectAllCheckbox = document.getElementById('selectAllExtend');

    if (!selectAllCheckbox || checkboxes.length === 0) return;

    const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;

    if (checkedCount === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (checkedCount === checkboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

/**
 * Handle batch extend form submission
 */
async function handleBatchExtendSubmit(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('processExtendBtn');
    const originalBtnText = submitBtn.innerHTML;

    const selectedItems = Array.from(document.querySelectorAll('.item-extend-checkbox:checked')).map(cb => cb.value);

    if (selectedItems.length === 0) {
        alert('Please select at least one item to extend');
        return;
    }

    const newExpectedReturn = document.getElementById('new_expected_return').value;
    const reason = document.getElementById('extend_reason').value.trim();

    if (!newExpectedReturn) {
        alert('Please enter a new expected return date');
        return;
    }

    if (!reason) {
        alert('Please provide a reason for the extension');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Processing...';

    try {
        const formData = new FormData();
        formData.append('_csrf_token', document.getElementById('extendCsrfToken').value);
        formData.append('batch_id', document.getElementById('extendBatchId').value);
        formData.append('new_expected_return', newExpectedReturn);
        formData.append('reason', reason);

        selectedItems.forEach(itemId => {
            formData.append('item_ids[]', itemId);
        });

        const response = await fetch('?route=borrowed-tools/batch/extend', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('batchExtendModal'));
            modal.hide();

            alert('Batch extended successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to extend batch'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    } catch (error) {
        console.error('Batch extend error:', error);
        alert('Error: Failed to extend batch. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

/**
 * Handle incident report button click
 */
function handleIncidentReportClick(e) {
    e.preventDefault();
    const btn = e.target.closest('.report-incident-item-btn');
    const itemId = btn.getAttribute('data-item-id');
    const assetId = btn.getAttribute('data-asset-id');
    const assetRef = btn.getAttribute('data-asset-ref');
    const assetName = btn.getAttribute('data-asset-name');

    if (reportedIncidents[itemId]) {
        alert('An incident has already been reported for this item in this session.\nIncident #' + reportedIncidents[itemId].incident_id);
        return;
    }

    document.getElementById('incidentAssetId').value = assetId || '';
    document.getElementById('incidentBorrowedToolId').value = itemId;
    document.getElementById('incidentEquipmentName').textContent = assetName;
    document.getElementById('incidentAssetRef').textContent = assetRef;

    document.getElementById('incident_type').value = '';
    document.getElementById('incident_severity').value = 'medium';
    document.getElementById('incident_description').value = '';
    document.getElementById('incident_location').value = '';

    const incidentModal = new bootstrap.Modal(document.getElementById('quickIncidentModal'));
    incidentModal.show();
}

/**
 * Handle incident form submission
 */
async function handleIncidentSubmit(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitIncidentBtn');
    const originalBtnText = submitBtn.innerHTML;
    const borrowedToolId = document.getElementById('incidentBorrowedToolId').value;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Creating Incident...';

    try {
        const formData = new FormData(this);

        const response = await fetch('?route=incidents/create', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const contentType = response.headers.get('content-type');

        if (contentType && contentType.includes('application/json')) {
            const result = await response.json();

            if (result.success) {
                const incidentModal = bootstrap.Modal.getInstance(document.getElementById('quickIncidentModal'));
                if (incidentModal) {
                    incidentModal.hide();
                }

                reportedIncidents[borrowedToolId] = {
                    incident_id: result.incident?.id || 'NEW',
                    type: formData.get('type'),
                    severity: formData.get('severity')
                };

                const incidentBtn = document.querySelector(`.incident-btn-${borrowedToolId}`);
                const incidentBadge = document.querySelector(`.incident-reported-badge-${borrowedToolId}`);
                if (incidentBtn) {
                    incidentBtn.style.display = 'none';
                }
                if (incidentBadge) {
                    incidentBadge.style.display = 'block';
                    incidentBadge.style.removeProperty('display');
                }

                ajax.showSuccess('Incident #' + (result.incident?.id || 'NEW') + ' created successfully. Continue with the return process.');
            } else {
                alert('Error: ' + (result.message || 'Failed to create incident'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        } else {
            alert('Error: Server returned an unexpected response. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    } catch (error) {
        alert('Error: Failed to create incident. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

/**
 * Initialize filter functionality
 */
function initializeFilters() {
    const filterForm = document.querySelector('form[action="?route=borrowed-tools"]');
    if (!filterForm) return;

    const filterInputs = filterForm.querySelectorAll('select, input[name="date_from"], input[name="date_to"]');

    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });

    let searchTimeout;
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    }
}

/**
 * Initialize sorting functionality
 */
function initializeSorting() {
    const sortableHeaders = document.querySelectorAll('th.sortable');

    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.style.userSelect = 'none';

        header.addEventListener('click', function() {
            const sortColumn = this.getAttribute('data-sort');
            const params = new URLSearchParams(window.location.search);

            const currentSort = params.get('sort') || '';
            const currentOrder = params.get('order') || 'desc';

            let newOrder = 'asc';
            if (sortColumn === currentSort) {
                newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            }

            params.set('sort', sortColumn);
            params.set('order', newOrder);

            window.location.href = '?' + params.toString();
        });
    });
}

/**
 * Initialize auto-refresh functionality
 */
function initializeAutoRefresh() {
    if (!document.querySelector('.table-danger') && !document.querySelector('.bg-danger')) {
        return;
    }

    // Get refresh interval from data attribute set by PHP
    const refreshInterval = parseInt(document.body.dataset.autoRefreshInterval || '300') * 1000;

    if (refreshInterval <= 0) return;

    let refreshTimer = refreshInterval / 1000;

    const createRefreshIndicator = () => {
        const indicator = document.createElement('div');
        indicator.id = 'refresh-indicator';
        indicator.className = 'position-fixed bottom-0 end-0 m-3 alert alert-info alert-dismissible';
        indicator.setAttribute('role', 'status');
        indicator.setAttribute('aria-live', 'polite');
        indicator.innerHTML = `
            <small>
                <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>
                Auto-refresh in <span id="refresh-countdown">${refreshTimer}</span>s
                <button type="button" class="btn-close btn-close-sm" onclick="window.clearAutoRefresh()" aria-label="Cancel auto-refresh"></button>
            </small>
        `;
        document.body.appendChild(indicator);

        const countdown = setInterval(() => {
            refreshTimer--;
            const countdownEl = document.getElementById('refresh-countdown');
            if (countdownEl) countdownEl.textContent = refreshTimer;

            if (refreshTimer <= 0) {
                clearInterval(countdown);
                location.reload();
            }
        }, 1000);

        window.autoRefreshInterval = countdown;
    };

    setTimeout(createRefreshIndicator, 30000);
}

/**
 * Clear auto-refresh
 */
window.clearAutoRefresh = function() {
    if (window.autoRefreshInterval) {
        clearInterval(window.autoRefreshInterval);
        const indicator = document.getElementById('refresh-indicator');
        if (indicator) indicator.remove();
    }
};

/**
 * Mark tool as overdue
 */
window.markOverdue = function(borrowId) {
    if (confirm('Mark this tool as overdue? This will update the status and may trigger notifications.')) {
        fetch('?route=borrowed-tools/markOverdue', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ borrow_id: borrowId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to mark as overdue: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while marking tool as overdue');
        });
    }
};

/**
 * Send overdue reminder
 */
window.sendOverdueReminder = function(borrowId) {
    if (confirm('Send overdue reminder to borrower?')) {
        fetch('?route=borrowed-tools/sendReminder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ borrow_id: borrowId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reminder sent successfully!');
            } else {
                alert('Failed to send reminder: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending reminder');
        });
    }
};

/**
 * Refresh borrowed tools
 */
window.refreshBorrowedTools = function() {
    window.location.reload();
};

/**
 * Export to Excel
 */
window.exportToExcel = function() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=borrowed-tools/export&' + params.toString();
};

/**
 * Print table
 */
window.printTable = function() {
    window.print();
};

/**
 * Quick filter function
 */
window.quickFilter = function(status) {
    if (status === 'overdue') {
        window.location.href = '?route=borrowed-tools&priority=overdue';
    } else {
        window.location.href = '?route=borrowed-tools&status=' + encodeURIComponent(status);
    }
};

/**
 * Filter by priority
 */
window.filterByPriority = function(priority) {
    const desktopPriority = document.getElementById('priority');
    const mobilePriority = document.getElementById('priority-mobile');

    if (desktopPriority) {
        desktopPriority.value = priority;
        desktopPriority.closest('form').submit();
    } else if (mobilePriority) {
        mobilePriority.value = priority;
        mobilePriority.closest('form').submit();
    }
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Token will be set by inline script
        if (window.borrowedToolsCsrfToken) {
            init(window.borrowedToolsCsrfToken);
        }
    });
} else {
    // DOM already loaded
    if (window.borrowedToolsCsrfToken) {
        init(window.borrowedToolsCsrfToken);
    }
}
