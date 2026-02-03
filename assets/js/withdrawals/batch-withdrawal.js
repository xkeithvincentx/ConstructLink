/**
 * Batch Withdrawal Module
 * Handles shopping cart interface and batch creation for consumable withdrawals
 * Adapted from batch-borrowing.js for withdrawal-specific logic
 */

/**
 * Initialize batch withdrawal application
 * @param {Object} config - Configuration object
 * @returns {Object} Alpine.js component
 */
export function initBatchWithdrawalApp(config) {
    const {
        categories,
        commonReceivers,
        csrfToken,
        projectId
    } = config;

    // Define constants
    const MAX_RECEIVER_SUGGESTIONS = 10;
    const MIN_QUANTITY = 1;
    const MAX_ITEMS_PER_BATCH = 50;

    return {
        // Consumable data
        categories: categories,
        activeCategory: Object.keys(categories)[0] || 'office_supplies',
        searchQuery: '',
        filteredItems: [],

        // Cart
        cart: [],

        // Constants exposed to template
        MIN_QUANTITY,

        // Form data
        formData: {
            receiver_last_name: '',
            receiver_first_name: '',
            receiver_name: '',  // Combined standardized name (Last, First)
            receiver_contact: '',
            receiver_position: '',
            purpose: '',
            project_id: projectId || ''
        },
        receiverSearchQuery: '',  // Combined search query for filtering
        showReceiverSuggestions: false,
        submitting: false,

        // CSRF token
        csrfToken: csrfToken,

        // Receiver suggestions data
        allReceivers: commonReceivers,

        // COMPUTED PROPERTIES
        get totalQuantity() {
            return this.cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
        },

        get totalItems() {
            return this.cart.length;
        },

        get hasItems() {
            return this.cart.length > 0;
        },

        get filteredReceivers() {
            if (this.receiverSearchQuery.length < 1) {
                return [];
            }

            const query = this.receiverSearchQuery.toLowerCase().trim();
            const queryParts = query.split(/\s+/);

            return this.allReceivers.filter(receiver => {
                const receiverName = receiver.receiver_name.toLowerCase();
                const receiverParts = receiverName.split(/[\s,]+/).filter(p => p.length > 0);

                // Match if ANY query part matches ANY receiver name part
                return queryParts.every(queryPart =>
                    receiverParts.some(receiverPart =>
                        receiverPart.includes(queryPart) || queryPart.includes(receiverPart)
                    )
                );
            }).slice(0, MAX_RECEIVER_SUGGESTIONS);
        },

        // INITIALIZATION
        init() {
            this.filterConsumables();
        },

        // CATEGORY METHODS
        selectCategory(key) {
            this.activeCategory = key;
            this.searchQuery = '';
            this.filterConsumables();
        },

        // CONSUMABLE FILTERING
        filterConsumables() {
            // If there's a search query, search across ALL categories
            if (this.searchQuery.trim()) {
                const query = this.searchQuery.toLowerCase();
                let allItems = [];

                // Collect all items from all categories
                Object.values(this.categories).forEach(category => {
                    if (category.items) {
                        allItems = allItems.concat(category.items);
                    }
                });

                // Filter by search query
                this.filteredItems = allItems.filter(item =>
                    item.name.toLowerCase().includes(query) ||
                    item.ref.toLowerCase().includes(query) ||
                    (item.model && item.model.toLowerCase().includes(query)) ||
                    (item.item_code && item.item_code.toLowerCase().includes(query))
                );
            } else {
                // No search query - show items from active category only
                const category = this.categories[this.activeCategory];
                this.filteredItems = (category && category.items) ? category.items : [];
            }
        },

        clearSearch() {
            this.searchQuery = '';
            this.filterConsumables();
        },

        // CART MANAGEMENT
        isInCart(itemId) {
            return this.cart.some(item => item.id === itemId);
        },

        /**
         * Validate consumable before adding to cart
         * @param {Object} item - Item to validate
         * @returns {boolean} - True if valid
         */
        validateConsumable(item) {
            // Check if item is marked as consumable
            if (!item.is_consumable) {
                this.showError('Only consumable items can be added to withdrawal batch');
                return false;
            }

            // Check if item has available quantity
            if (!item.available_quantity || item.available_quantity <= 0) {
                this.showError('This item is out of stock');
                return false;
            }

            return true;
        },

        /**
         * Validate quantity against available stock
         * @param {Object} item - Item to check
         * @param {number} quantity - Requested quantity
         * @returns {boolean} - True if valid
         */
        validateQuantity(item, quantity) {
            const requestedQty = parseInt(quantity) || 1;

            if (requestedQty < MIN_QUANTITY) {
                this.showError('Quantity must be at least 1');
                return false;
            }

            if (requestedQty > item.available_quantity) {
                this.showError(`Only ${item.available_quantity} available in stock`);
                return false;
            }

            return true;
        },

        toggleItem(item) {
            const index = this.cart.findIndex(i => i.id === item.id);
            if (index >= 0) {
                this.cart.splice(index, 1);
            } else {
                // Validate before adding
                if (!this.validateConsumable(item)) {
                    return;
                }

                // Set initial quantity to 1, store available_quantity
                this.cart.push({
                    ...item,
                    quantity: 1,
                    available_quantity: item.available_quantity || 0
                });
            }
        },

        removeFromCart(itemId) {
            const index = this.cart.findIndex(i => i.id === itemId);
            if (index >= 0) {
                this.cart.splice(index, 1);
            }
        },

        updateQuantity(itemId, quantity) {
            const item = this.cart.find(i => i.id === itemId);
            if (item) {
                // Validate and clamp quantity
                const requestedQty = parseInt(quantity) || MIN_QUANTITY;
                const maxAllowed = item.available_quantity || MIN_QUANTITY;

                // Validate quantity
                if (!this.validateQuantity(item, requestedQty)) {
                    // Reset to max allowed or current quantity
                    item.quantity = Math.min(item.quantity || MIN_QUANTITY, maxAllowed);
                    return;
                }

                item.quantity = Math.max(MIN_QUANTITY, Math.min(maxAllowed, requestedQty));
            }
        },

        clearCart() {
            if (confirm('Are you sure you want to clear all selected items?')) {
                this.cart = [];
            }
        },

        // RECEIVER METHODS
        updateReceiverSearch() {
            this.receiverSearchQuery = `${this.formData.receiver_last_name} ${this.formData.receiver_first_name}`.trim();

            // Update the combined standardized name (Last, First)
            if (this.formData.receiver_last_name && this.formData.receiver_first_name) {
                this.formData.receiver_name = `${this.formData.receiver_last_name}, ${this.formData.receiver_first_name}`;
            } else if (this.formData.receiver_last_name) {
                this.formData.receiver_name = this.formData.receiver_last_name;
            } else if (this.formData.receiver_first_name) {
                this.formData.receiver_name = this.formData.receiver_first_name;
            } else {
                this.formData.receiver_name = '';
            }
        },

        selectReceiver(receiver) {
            // Parse the stored name (could be "Last, First" or just a single name)
            const nameParts = receiver.receiver_name.split(',').map(p => p.trim());

            if (nameParts.length === 2) {
                // Format: "Last, First"
                this.formData.receiver_last_name = nameParts[0];
                this.formData.receiver_first_name = nameParts[1];
            } else {
                // Single name - try to split by space and guess
                const spaceParts = receiver.receiver_name.trim().split(/\s+/);
                if (spaceParts.length >= 2) {
                    // Assume last word is last name, rest is first name
                    this.formData.receiver_last_name = spaceParts[spaceParts.length - 1];
                    this.formData.receiver_first_name = spaceParts.slice(0, -1).join(' ');
                } else {
                    // Just one word - put in last name
                    this.formData.receiver_last_name = receiver.receiver_name;
                    this.formData.receiver_first_name = '';
                }
            }

            this.formData.receiver_name = receiver.receiver_name;
            this.formData.receiver_contact = receiver.receiver_contact || '';
            this.formData.receiver_position = receiver.receiver_position || '';
            this.updateReceiverSearch();
            this.showReceiverSuggestions = false;
        },

        // ERROR HANDLING
        showError(message) {
            // Use Bootstrap toast or alert
            if (typeof window.showToast === 'function') {
                window.showToast('error', message);
            } else {
                alert(message);
            }
        },

        showSuccess(message) {
            if (typeof window.showToast === 'function') {
                window.showToast('success', message);
            } else {
                alert(message);
            }
        },

        // MODAL MANAGEMENT
        proceedToReceiverInfo() {
            if (this.cart.length === 0) {
                this.showError('Please add items to cart first');
                return;
            }

            // Show availability reminder
            const proceed = confirm(
                'Important: Item availability will be verified when you create the batch.\n\n' +
                'If another user withdraws an item before you submit, that item will be marked as unavailable.\n\n' +
                'Do you want to continue?'
            );

            if (proceed) {
                // Open the modal programmatically
                const modal = new bootstrap.Modal(document.getElementById('receiverModal'));
                modal.show();
            }
        },

        // FORM SUBMISSION
        async submitBatch() {
            if (this.submitting) return;

            // Validate required fields
            if (!this.formData.receiver_last_name || !this.formData.receiver_first_name) {
                this.showError('Please fill in both first name and last name');
                return;
            }

            if (!this.formData.purpose || this.formData.purpose.trim() === '') {
                this.showError('Please specify the purpose of withdrawal');
                return;
            }

            if (this.cart.length === 0) {
                this.showError('Please select at least one item');
                return;
            }

            // Check if any item exceeds available quantity
            const invalidItems = this.cart.filter(item =>
                item.quantity > item.available_quantity
            );

            if (invalidItems.length > 0) {
                this.showError('Some items exceed available quantity. Please adjust quantities.');
                return;
            }

            // Ensure the combined name is set
            this.updateReceiverSearch();

            this.submitting = true;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                formData.append('receiver_name', this.formData.receiver_name);
                formData.append('receiver_contact', this.formData.receiver_contact);
                formData.append('receiver_position', this.formData.receiver_position);
                formData.append('purpose', this.formData.purpose);
                formData.append('project_id', this.formData.project_id);

                // Add cart items
                formData.append('items', JSON.stringify(this.cart.map(item => ({
                    inventory_item_id: item.id,
                    quantity: item.quantity || 1
                }))));

                const response = await fetch('index.php?route=withdrawals/batch/create', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Redirect to withdrawals list with success message
                    const messageParam = result.workflow_type === 'streamlined'
                        ? 'batch_created_released'
                        : 'batch_created_pending';
                    window.location.href = 'index.php?route=withdrawals&message=' + messageParam;
                } else {
                    this.showError(result.message || 'Failed to create batch');
                    this.submitting = false;
                }
            } catch (error) {
                console.error('Batch creation error:', error);
                this.showError('Failed to create batch. Please try again.');
                this.submitting = false;
            }
        }
    };
}

// Export for use in view
export default {
    initBatchWithdrawalApp
};
