/**
 * Batch Borrowing Module
 * Extracted from create-batch.php inline JavaScript
 * Handles shopping cart interface and batch creation
 */

/**
 * Initialize batch borrowing application
 * @param {Object} config - Configuration object
 * @returns {Object} Alpine.js component
 */
export function initBatchBorrowingApp(config) {
    const {
        CRITICAL_TOOL_THRESHOLD,
        categories,
        commonBorrowers,
        csrfToken
    } = config;

    // Define constants
    const MAX_BORROWER_SUGGESTIONS = 10;
    const MIN_QUANTITY = 1;
    const MAX_ITEMS_PER_BATCH = 50;
    const DEFAULT_RETURN_DAYS = 7;

    return {
        // Equipment data
        categories: categories,
        activeCategory: Object.keys(categories)[0] || 'power_tools',
        searchQuery: '',
        filteredItems: [],

        // Cart
        cart: [],

        // Constants exposed to template
        MIN_QUANTITY,

        // Form data
        formData: {
            borrower_last_name: '',
            borrower_first_name: '',
            borrower_name: '',  // Combined standardized name (Last, First)
            borrower_contact: '',
            expected_return: '',
            purpose: ''
        },
        borrowerSearchQuery: '',  // Combined search query for filtering
        showBorrowerSuggestions: false,
        submitting: false,

        // CSRF token
        csrfToken: csrfToken,

        // Borrower suggestions data
        allBorrowers: commonBorrowers,

        // COMPUTED PROPERTIES
        get totalQuantity() {
            return this.cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
        },

        get hasCriticalTools() {
            return this.cart.some(item => item.acquisition_cost > CRITICAL_TOOL_THRESHOLD);
        },

        get minDate() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            return tomorrow.toISOString().split('T')[0];
        },

        get filteredBorrowers() {
            if (this.borrowerSearchQuery.length < 1) {
                return [];
            }

            const query = this.borrowerSearchQuery.toLowerCase().trim();
            const queryParts = query.split(/\s+/);

            return this.allBorrowers.filter(borrower => {
                const borrowerName = borrower.borrower_name.toLowerCase();
                const borrowerParts = borrowerName.split(/[\s,]+/).filter(p => p.length > 0);

                // Match if ANY query part matches ANY borrower name part
                return queryParts.every(queryPart =>
                    borrowerParts.some(borrowerPart =>
                        borrowerPart.includes(queryPart) || queryPart.includes(borrowerPart)
                    )
                );
            }).slice(0, MAX_BORROWER_SUGGESTIONS);
        },

        // INITIALIZATION
        init() {
            this.filterEquipment();

            // Set default return date using constant
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + DEFAULT_RETURN_DAYS);
            this.formData.expected_return = defaultDate.toISOString().split('T')[0];
        },

        // CATEGORY METHODS
        selectCategory(key) {
            this.activeCategory = key;
            this.searchQuery = '';
            this.filterEquipment();
        },

        // EQUIPMENT FILTERING
        filterEquipment() {
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
                    (item.serial_number && item.serial_number.toLowerCase().includes(query))
                );
            } else {
                // No search query - show items from active category only
                const category = this.categories[this.activeCategory];
                this.filteredItems = (category && category.items) ? category.items : [];
            }
        },

        clearSearch() {
            this.searchQuery = '';
            this.filterEquipment();
        },

        // CART MANAGEMENT
        isInCart(itemId) {
            return this.cart.some(item => item.id === itemId);
        },

        toggleItem(item) {
            const index = this.cart.findIndex(i => i.id === item.id);
            if (index >= 0) {
                this.cart.splice(index, 1);
            } else {
                // Set initial quantity to 1, store available_quantity
                this.cart.push({
                    ...item,
                    quantity: 1,
                    available_quantity: item.available_quantity || 1
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
                // Serialized items must always be quantity 1 (unique items)
                if (item.serial_number) {
                    item.quantity = MIN_QUANTITY;
                } else {
                    // Non-serialized items: respect available_quantity from database
                    const maxAllowed = item.available_quantity || MIN_QUANTITY;
                    item.quantity = Math.max(MIN_QUANTITY, Math.min(maxAllowed, parseInt(quantity) || MIN_QUANTITY));
                }
            }
        },

        clearCart() {
            if (confirm('Are you sure you want to clear all selected items?')) {
                this.cart = [];
            }
        },

        // BORROWER METHODS
        updateBorrowerSearch() {
            this.borrowerSearchQuery = `${this.formData.borrower_last_name} ${this.formData.borrower_first_name}`.trim();

            // Update the combined standardized name (Last, First)
            if (this.formData.borrower_last_name && this.formData.borrower_first_name) {
                this.formData.borrower_name = `${this.formData.borrower_last_name}, ${this.formData.borrower_first_name}`;
            } else if (this.formData.borrower_last_name) {
                this.formData.borrower_name = this.formData.borrower_last_name;
            } else if (this.formData.borrower_first_name) {
                this.formData.borrower_name = this.formData.borrower_first_name;
            } else {
                this.formData.borrower_name = '';
            }
        },

        selectBorrower(borrower) {
            // Parse the stored name (could be "Last, First" or just a single name)
            const nameParts = borrower.borrower_name.split(',').map(p => p.trim());

            if (nameParts.length === 2) {
                // Format: "Last, First"
                this.formData.borrower_last_name = nameParts[0];
                this.formData.borrower_first_name = nameParts[1];
            } else {
                // Single name - try to split by space and guess
                const spaceParts = borrower.borrower_name.trim().split(/\s+/);
                if (spaceParts.length >= 2) {
                    // Assume last word is last name, rest is first name
                    this.formData.borrower_last_name = spaceParts[spaceParts.length - 1];
                    this.formData.borrower_first_name = spaceParts.slice(0, -1).join(' ');
                } else {
                    // Just one word - put in last name
                    this.formData.borrower_last_name = borrower.borrower_name;
                    this.formData.borrower_first_name = '';
                }
            }

            this.formData.borrower_name = borrower.borrower_name;
            this.formData.borrower_contact = borrower.borrower_contact || '';
            this.updateBorrowerSearch();
            this.showBorrowerSuggestions = false;
        },

        // MODAL MANAGEMENT
        proceedToBorrowerInfo() {
            if (this.cart.length === 0) {
                alert('Please add items to cart first');
                return;
            }

            // Show availability reminder
            const proceed = confirm(
                'Important: Equipment availability will be verified when you create the batch.\n\n' +
                'If another user borrows an item before you submit, that item will be marked as unavailable.\n\n' +
                'Do you want to continue?'
            );

            if (proceed) {
                // Open the modal programmatically
                const modal = new bootstrap.Modal(document.getElementById('borrowerModal'));
                modal.show();
            }
        },

        // FORM SUBMISSION
        async submitBatch() {
            if (this.submitting) return;

            // Validate required fields
            if (!this.formData.borrower_last_name || !this.formData.borrower_first_name) {
                alert('Please fill in both first name and last name');
                return;
            }

            if (!this.formData.expected_return) {
                alert('Please select an expected return date');
                return;
            }

            if (this.cart.length === 0) {
                alert('Please select at least one item');
                return;
            }

            // Ensure the combined name is set
            this.updateBorrowerSearch();

            this.submitting = true;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                formData.append('borrower_name', this.formData.borrower_name);
                formData.append('borrower_contact', this.formData.borrower_contact);
                formData.append('expected_return', this.formData.expected_return);
                formData.append('purpose', this.formData.purpose);

                // Add cart items
                formData.append('items', JSON.stringify(this.cart.map(item => ({
                    asset_id: item.id,
                    quantity: item.quantity || 1
                }))));

                const response = await fetch('index.php?route=borrowed-tools/batch/create', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Redirect to main borrowed tools list with success message
                    const messageParam = result.workflow_type === 'streamlined'
                        ? 'batch_created_released'
                        : 'batch_created_pending';
                    window.location.href = 'index.php?route=borrowed-tools&message=' + messageParam;
                } else {
                    alert('Error: ' + (result.message || 'Failed to create batch'));
                    this.submitting = false;
                }
            } catch (error) {
                console.error('Batch creation error:', error);
                alert('Failed to create batch. Please try again.');
                this.submitting = false;
            }
        },

        // Expose constant to template
        CRITICAL_TOOL_THRESHOLD
    };
}

// Export for use in view
export default {
    initBatchBorrowingApp
};
