/**
 * Withdrawals Index Alpine.js Component
 * Handles filtering, modal interactions, and AJAX operations for withdrawals
 *
 * @author ConstructLink Development Team
 * @version 1.0.0
 */

import { WithdrawalAjaxHandler } from './ajax-handler.js';

/**
 * Initialize withdrawals index app
 * @returns {Object} Alpine.js component data
 */
export function withdrawalsIndexApp() {
    return {
        // Filter state
        filters: {
            status: new URLSearchParams(window.location.search).get('status') || '',
            receiver: new URLSearchParams(window.location.search).get('receiver') || '',
            dateFrom: new URLSearchParams(window.location.search).get('date_from') || '',
            dateTo: new URLSearchParams(window.location.search).get('date_to') || ''
        },

        // AJAX handler instance
        ajaxHandler: null,

        /**
         * Apply filters and reload page with filter parameters
         */
        applyFilters() {
            const params = new URLSearchParams();
            params.append('route', 'withdrawals');

            if (this.filters.status) {
                params.append('status', this.filters.status);
            }
            if (this.filters.receiver) {
                params.append('receiver', this.filters.receiver);
            }
            if (this.filters.dateFrom) {
                params.append('date_from', this.filters.dateFrom);
            }
            if (this.filters.dateTo) {
                params.append('date_to', this.filters.dateTo);
            }

            // Reload page with filters
            window.location.href = '?' + params.toString();
        },

        /**
         * Clear all filters and reload page
         */
        clearFilters() {
            this.filters = {
                status: '',
                receiver: '',
                dateFrom: '',
                dateTo: ''
            };
            window.location.href = '?route=withdrawals';
        },

        /**
         * Setup modal handlers for withdrawal operations
         */
        setupModalHandlers() {
            const csrfToken = document.querySelector('#withdrawals-app').dataset.csrfToken;
            this.ajaxHandler = new WithdrawalAjaxHandler(csrfToken);

            // Setup modal trigger buttons
            this.setupModalTriggers();

            // Setup verify modal form submission
            const verifyForm = document.querySelector('#withdrawalVerifyModal form');
            if (verifyForm) {
                verifyForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    try {
                        const formData = new FormData(e.target);
                        await this.handleVerify(formData);
                    } catch (error) {
                        console.error('Verify form submission error:', error);
                        this.ajaxHandler.showError('An unexpected error occurred');
                    }
                });
            }

            // Setup approve modal form submission
            const approveForm = document.querySelector('#withdrawalApproveModal form');
            if (approveForm) {
                approveForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    try {
                        const formData = new FormData(e.target);
                        await this.handleApprove(formData);
                    } catch (error) {
                        console.error('Approve form submission error:', error);
                        this.ajaxHandler.showError('An unexpected error occurred');
                    }
                });
            }

            // Setup release modal form submission
            const releaseForm = document.querySelector('#withdrawalReleaseModal form');
            if (releaseForm) {
                releaseForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    try {
                        const formData = new FormData(e.target);
                        await this.handleRelease(formData);
                    } catch (error) {
                        console.error('Release form submission error:', error);
                        this.ajaxHandler.showError('An unexpected error occurred');
                    }
                });
            }

            // Setup return modal form submission
            const returnForm = document.querySelector('#withdrawalReturnForm');
            if (returnForm) {
                returnForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    try {
                        const formData = new FormData(e.target);
                        await this.handleReturn(formData);
                    } catch (error) {
                        console.error('Return form submission error:', error);
                        this.ajaxHandler.showError('An unexpected error occurred');
                    }
                });
            }
        },

        /**
         * Setup modal trigger buttons to populate batch_id
         */
        setupModalTriggers() {
            document.querySelectorAll('[data-bs-toggle="modal"][data-batch-id]').forEach(button => {
                button.addEventListener('click', (e) => {
                    const batchId = e.currentTarget.dataset.batchId;
                    const isSingleItem = e.currentTarget.dataset.isSingleItem === 'true';
                    const targetModal = e.currentTarget.dataset.bsTarget;

                    // Set batch_id in the modal's hidden input
                    const modal = document.querySelector(targetModal);
                    if (modal) {
                        const batchInput = modal.querySelector('input[name="batch_id"]');
                        if (batchInput) {
                            batchInput.value = batchId;
                        }

                        // Set is_single_item flag for verify modal
                        const verifyIsSingleInput = modal.querySelector('#verifyIsSingleItem');
                        if (verifyIsSingleInput) {
                            verifyIsSingleInput.value = isSingleItem ? '1' : '0';
                        }

                        // Set is_single_item flag for approve modal
                        const approveIsSingleInput = modal.querySelector('#approveIsSingleItem');
                        if (approveIsSingleInput) {
                            approveIsSingleInput.value = isSingleItem ? '1' : '0';
                        }

                        // Set is_single_item flag for release modal
                        const releaseIsSingleInput = modal.querySelector('#releaseIsSingleItem');
                        if (releaseIsSingleInput) {
                            releaseIsSingleInput.value = isSingleItem ? '1' : '0';
                        }

                        // Set is_single_item flag for return modal
                        const returnIsSingleInput = modal.querySelector('#returnIsSingleItem');
                        if (returnIsSingleInput) {
                            returnIsSingleInput.value = isSingleItem ? '1' : '0';
                        }

                        // Load batch items into modal (placeholder for future implementation)
                        this.loadBatchItemsIntoModal(modal, batchId, isSingleItem);
                    }
                });
            });
        },

        /**
         * Load batch items into modal
         * Fetches and displays items for verify, approve, release, and return modals
         */
        async loadBatchItemsIntoModal(modal, batchId, isSingleItem) {
            // Special handling for return modal (uses table format)
            if (modal.id === 'withdrawalReturnModal') {
                await this.loadReturnModalItems(batchId, isSingleItem);
                return;
            }

            // For verify, approve, and release modals (uses list format)
            if (modal.id === 'withdrawalVerifyModal' || modal.id === 'withdrawalApproveModal' || modal.id === 'withdrawalReleaseModal') {
                await this.loadBatchItemsForDisplay(modal, batchId, isSingleItem);
                return;
            }

            // For other modals, show placeholder
            const itemsContainer = modal.querySelector('.batch-modal-items');
            if (itemsContainer) {
                if (isSingleItem) {
                    itemsContainer.innerHTML = `<p class="text-muted">Single withdrawal item #${batchId}</p>`;
                } else {
                    itemsContainer.innerHTML = `<p class="text-muted">Batch #${batchId} - Items will be displayed here</p>`;
                }
            }
        },

        /**
         * Load batch items for verify/approve/release modals
         */
        async loadBatchItemsForDisplay(modal, batchId, isSingleItem) {
            const itemsContainer = modal.querySelector('.batch-modal-items');
            if (!itemsContainer) return;

            try {
                itemsContainer.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading items...</div>';

                const response = await fetch('/api/withdrawals/batch-details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        batch_id: batchId,
                        is_single_item: isSingleItem,
                        _csrf_token: this.ajaxHandler.csrfToken
                    })
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch batch details');
                }

                const data = await response.json();

                if (data.success) {
                    this.displayBatchItemsList(itemsContainer, data.items);
                } else {
                    throw new Error(data.message || 'Failed to load items');
                }
            } catch (error) {
                console.error('Error loading batch items:', error);
                itemsContainer.innerHTML = '<div class="alert alert-danger">Failed to load items. Please try again.</div>';
            }
        },

        /**
         * Display batch items in a list format
         */
        displayBatchItemsList(container, items) {
            if (items.length === 0) {
                container.innerHTML = '<p class="text-muted">No items found.</p>';
                return;
            }

            // Check if any items have insufficient quantity
            const hasInsufficientQuantity = items.some(item =>
                item.current_available_quantity !== undefined &&
                item.current_available_quantity < item.quantity
            );

            const itemsHtml = `
                ${hasInsufficientQuantity ? `
                    <div class="alert alert-warning mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Some items have insufficient available quantity.
                        These may have been withdrawn by another user since this request was created.
                    </div>
                ` : ''}

                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Consumable</th>
                                <th width="100">Reference</th>
                                <th width="120" class="text-center">Requested</th>
                                <th width="120" class="text-center">Available Now</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map((item, index) => {
                                const hasQuantity = item.current_available_quantity !== undefined;
                                const isInsufficient = hasQuantity && item.current_available_quantity < item.quantity;
                                const rowClass = isInsufficient ? 'table-warning' : '';

                                return `
                                    <tr class="${rowClass}">
                                        <td class="text-center">${index + 1}</td>
                                        <td>
                                            <div class="fw-medium">
                                                ${isInsufficient ? '<i class="bi bi-exclamation-triangle text-warning me-1" aria-label="Insufficient quantity"></i>' : ''}
                                                ${this.escapeHtml(item.item_name)}
                                            </div>
                                            ${item.item_ref ? `<div class="small text-muted">${this.escapeHtml(item.item_ref)}</div>` : ''}
                                        </td>
                                        <td>
                                            <code class="small">WDR-${String(item.id).padStart(5, '0')}</code>
                                        </td>
                                        <td class="text-center">
                                            <strong>${item.quantity}</strong>
                                            ${item.unit ? `<span class="small text-muted ms-1">${this.escapeHtml(item.unit)}</span>` : ''}
                                        </td>
                                        <td class="text-center">
                                            ${hasQuantity ? `
                                                <span class="badge ${isInsufficient ? 'bg-danger' : 'bg-success'}">
                                                    ${item.current_available_quantity}
                                                </span>
                                                ${isInsufficient ? `
                                                    <div class="small text-danger mt-1">
                                                        <strong>Short by ${item.quantity - item.current_available_quantity}</strong>
                                                    </div>
                                                ` : ''}
                                            ` : '<span class="text-muted">N/A</span>'}
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-sm alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Total Items:</strong> ${items.length} |
                    <strong>Total Quantity:</strong> ${items.reduce((sum, item) => sum + parseInt(item.quantity), 0)}
                    ${hasInsufficientQuantity ? '<br><strong class="text-warning">Approval will fail for items with insufficient quantity.</strong>' : ''}
                </div>
            `;

            container.innerHTML = itemsHtml;
        },

        /**
         * Load items for return modal
         */
        async loadReturnModalItems(batchId, isSingleItem) {
            try {
                const response = await fetch('/api/withdrawals/batch-details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        batch_id: batchId,
                        is_single_item: isSingleItem,
                        _csrf_token: this.ajaxHandler.csrfToken
                    })
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch batch details');
                }

                const data = await response.json();

                if (data.success) {
                    this.populateReturnTable(data.items);
                } else {
                    throw new Error(data.message || 'Failed to load items');
                }
            } catch (error) {
                console.error('Error loading return modal items:', error);
                this.ajaxHandler.showError('Failed to load withdrawal items');
            }
        },

        /**
         * Populate return modal table with items
         */
        populateReturnTable(items) {
            const tbody = document.getElementById('withdrawalReturnItems');
            if (!tbody) return;

            tbody.innerHTML = items.map((item, index) => `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td>
                        <div class="fw-medium">${this.escapeHtml(item.item_name)}</div>
                        ${item.item_ref ? `<div class="small text-muted">${this.escapeHtml(item.item_ref)}</div>` : ''}
                    </td>
                    <td>
                        <code class="small">WDR-${String(item.id).padStart(5, '0')}</code>
                    </td>
                    <td class="text-center">
                        <strong>${item.quantity}</strong>
                        ${item.unit ? `<span class="small text-muted ms-1">${this.escapeHtml(item.unit)}</span>` : ''}
                    </td>
                    <td>
                        <input type="number"
                               class="form-control form-control-sm"
                               name="return_quantities[${item.id}]"
                               min="0"
                               max="${item.quantity}"
                               value="${item.quantity}"
                               required
                               aria-label="Return quantity for ${this.escapeHtml(item.item_name)}">
                    </td>
                    <td>
                        <select class="form-select form-select-sm"
                                name="return_conditions[${item.id}]"
                                required
                                aria-label="Condition for ${this.escapeHtml(item.item_name)}">
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Damaged">Damaged</option>
                            <option value="Consumed">Consumed</option>
                        </select>
                    </td>
                    <td>
                        <input type="text"
                               class="form-control form-control-sm"
                               name="return_item_notes[${item.id}]"
                               placeholder="Optional notes"
                               aria-label="Notes for ${this.escapeHtml(item.item_name)}">
                    </td>
                </tr>
            `).join('');
        },

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Handle verify form submission
         */
        async handleVerify(formData) {
            const batchId = formData.get('batch_id');
            const notes = formData.get('verification_notes');
            const isSingleItem = formData.get('is_single_item') === '1';

            await this.ajaxHandler.submitBatchAction('verify', batchId, {
                _csrf_token: formData.get('_csrf_token'),
                verification_notes: notes,
                is_single_item: isSingleItem
            });
        },

        /**
         * Handle approve form submission
         */
        async handleApprove(formData) {
            const batchId = formData.get('batch_id');
            const notes = formData.get('approval_notes');
            const isSingleItem = formData.get('is_single_item') === '1';

            await this.ajaxHandler.submitBatchAction('approve', batchId, {
                _csrf_token: formData.get('_csrf_token'),
                approval_notes: notes,
                is_single_item: isSingleItem
            });
        },

        /**
         * Handle release form submission
         */
        async handleRelease(formData) {
            const batchId = formData.get('batch_id');
            const notes = formData.get('release_notes');
            const isSingleItem = formData.get('is_single_item') === '1';

            // Collect checklist values
            const checklistData = {
                check_receiver_verified: formData.get('check_receiver_verified') === 'on',
                check_quantity_verified: formData.get('check_quantity_verified') === 'on',
                check_condition_documented: formData.get('check_condition_documented') === 'on',
                check_receiver_acknowledged: formData.get('check_receiver_acknowledged') === 'on'
            };

            await this.ajaxHandler.submitBatchAction('release', batchId, {
                _csrf_token: formData.get('_csrf_token'),
                release_notes: notes,
                is_single_item: isSingleItem,
                ...checklistData
            });
        },

        /**
         * Handle return form submission
         */
        async handleReturn(formData) {
            const batchId = formData.get('batch_id');
            const notes = formData.get('return_notes');
            const isSingleItem = formData.get('is_single_item') === '1';

            // Collect per-item return data
            const returnQuantities = {};
            const returnConditions = {};
            const returnItemNotes = {};

            // Extract all return_quantities, return_conditions, and return_item_notes
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('return_quantities[')) {
                    const itemId = key.match(/\[(\d+)\]/)[1];
                    returnQuantities[itemId] = value;
                } else if (key.startsWith('return_conditions[')) {
                    const itemId = key.match(/\[(\d+)\]/)[1];
                    returnConditions[itemId] = value;
                } else if (key.startsWith('return_item_notes[')) {
                    const itemId = key.match(/\[(\d+)\]/)[1];
                    returnItemNotes[itemId] = value;
                }
            }

            const returnData = {
                _csrf_token: formData.get('_csrf_token'),
                return_notes: notes,
                is_single_item: isSingleItem,
                return_quantities: returnQuantities,
                return_conditions: returnConditions,
                return_item_notes: returnItemNotes
            };

            await this.ajaxHandler.submitBatchAction('return', batchId, returnData);
        },

        /**
         * Initialize component
         */
        init() {
            console.log('Withdrawals Index App initialized');
            this.setupModalHandlers();
        }
    }
}

// Make available globally for Alpine
window.withdrawalsIndexApp = withdrawalsIndexApp;
