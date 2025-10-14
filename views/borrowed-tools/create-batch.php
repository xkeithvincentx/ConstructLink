<?php
/**
 * ConstructLink™ - Multi-Item Batch Borrowing
 * Shopping cart style interface for borrowing multiple tools at once
 * Developed by: Ranoa Digital Solutions
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();

if (!hasPermission('borrowed-tools/create')) {
    echo '<div class="alert alert-danger">You do not have permission to create a borrowed tool request.</div>';
    return;
}

// Get grouped equipment using helper
require_once APP_ROOT . '/core/EquipmentCategoryHelper.php';
$groupedEquipment = EquipmentCategoryHelper::getGroupedEquipment($user['current_project_id']);
$commonBorrowers = EquipmentCategoryHelper::getCommonBorrowers($user['current_project_id'], 10);
?>

<style>
.category-tab {
    cursor: pointer;
    transition: all 0.2s;
}

.category-tab:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
}

.category-tab.active {
    border-left: 4px solid #0d6efd;
    background-color: #e7f1ff;
}

.equipment-card {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.equipment-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.equipment-card.selected {
    border-color: #0d6efd;
    background-color: #e7f1ff;
}

.cart-badge {
    position: fixed;
    top: 100px;
    right: 20px;
    z-index: 1000;
}

.cart-panel {
    position: fixed;
    right: -400px;
    top: 0;
    width: 400px;
    height: 100vh;
    background: white;
    box-shadow: -2px 0 10px rgba(0,0,0,0.1);
    transition: right 0.3s;
    z-index: 1050;
    overflow-y: auto;
}

.cart-panel.open {
    right: 0;
}

.cart-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
    display: none;
}

.cart-overlay.show {
    display: block;
}

.quantity-input {
    width: 70px;
}

.borrower-suggestion {
    cursor: pointer;
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s;
}

.borrower-suggestion:hover {
    background-color: #f8f9fa;
}
</style>

<!-- MVA Workflow Info -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="alert alert-success">
            <h6><i class="bi bi-lightning-charge me-1"></i><strong>Basic Tools Workflow</strong> (≤₱50,000)</h6>
            <p class="mb-2"><span class="badge bg-primary">Streamlined Process</span> - Instant approval</p>
            <small>Warehouseman: Create → Auto-Verify → Auto-Approve → Released</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="alert alert-warning">
            <h6><i class="bi bi-shield-check me-1"></i><strong>Critical Tools Workflow</strong> (>₱50,000)</h6>
            <p class="mb-2"><span class="badge bg-warning text-dark">Full MVA Process</span></p>
            <small>
                <span class="badge bg-primary">Maker</span> (Warehouseman) →
                <span class="badge bg-warning text-dark">Verifier</span> (Project Manager) →
                <span class="badge bg-success">Authorizer</span> (Asset Director/Finance Director)
            </small>
        </div>
    </div>
</div>

<!-- Messages -->
<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row" x-data="batchBorrowingApp()">
    <!-- Left Panel: Equipment Selection -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-tools me-2"></i>Select Tools & Equipment
                </h5>
            </div>
            <div class="card-body">
                <!-- Search Bar -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text"
                               class="form-control"
                               placeholder="Search tools by name or reference..."
                               x-model="searchQuery"
                               @input="filterEquipment()">
                        <button class="btn btn-outline-secondary" @click="clearSearch()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Category Tabs -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-2">
                            <template x-for="(category, key) in categories" :key="key">
                                <button type="button"
                                        class="btn category-tab"
                                        :class="activeCategory === key ? 'btn-primary' : 'btn-outline-primary'"
                                        @click="selectCategory(key)">
                                    <i :class="category.icon" class="me-1"></i>
                                    <span x-text="category.label"></span>
                                    <span class="badge bg-light text-dark ms-2"
                                          x-text="category.items ? category.items.length : 0"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Equipment Grid -->
                <div class="row g-3" style="max-height: 600px; overflow-y: auto;">
                    <template x-for="item in filteredItems" :key="item.id">
                        <div class="col-md-6">
                            <div class="card equipment-card h-100"
                                 :class="isInCart(item.id) ? 'selected' : ''"
                                 @click="toggleItem(item)">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1" x-text="item.name"></h6>
                                            <p class="text-muted small mb-1">
                                                <strong>Ref:</strong> <span x-text="item.ref"></span>
                                            </p>
                                            <p class="text-muted small mb-1">
                                                <strong>Category:</strong> <span x-text="item.category_name"></span>
                                            </p>
                                            <template x-if="item.model">
                                                <p class="text-muted small mb-0">
                                                    <strong>Model:</strong> <span x-text="item.model"></span>
                                                </p>
                                            </template>
                                        </div>
                                        <div class="text-end">
                                            <template x-if="item.acquisition_cost > 50000">
                                                <span class="badge bg-warning text-dark mb-2">
                                                    <i class="bi bi-shield-check"></i> Critical
                                                </span>
                                            </template>
                                            <template x-if="item.acquisition_cost <= 50000">
                                                <span class="badge bg-success mb-2">
                                                    <i class="bi bi-lightning"></i> Basic
                                                </span>
                                            </template>
                                            <template x-if="isInCart(item.id)">
                                                <div>
                                                    <i class="bi bi-check-circle-fill text-primary fs-4"></i>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- No Results -->
                    <template x-if="filteredItems.length === 0">
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                No equipment found. Try a different category or search term.
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel: Shopping Cart -->
    <div class="col-lg-4">
        <div class="card shadow-sm sticky-top" style="top: 20px;">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-cart3 me-2"></i>Selected Items
                    <span class="badge bg-light text-dark ms-2" x-text="cart.length"></span>
                </h5>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <template x-if="cart.length === 0">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-cart-x fs-1"></i>
                        <p class="mt-2">No items selected</p>
                        <p class="small">Click on equipment cards to add them</p>
                    </div>
                </template>

                <template x-if="cart.length > 0">
                    <div>
                        <template x-for="(item, index) in cart" :key="item.id">
                            <div class="card mb-2">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 small" x-text="item.name"></h6>
                                            <p class="text-muted mb-1" style="font-size: 0.75rem;">
                                                <span x-text="item.ref"></span>
                                            </p>
                                        </div>
                                        <button type="button"
                                                class="btn btn-sm btn-danger"
                                                @click="removeFromCart(item.id)">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                    <!-- Only show quantity for non-serialized items -->
                                    <div class="mt-2" x-show="!item.serial_number">
                                        <label class="form-label small mb-1">Quantity:</label>
                                        <input type="number"
                                               class="form-control form-control-sm quantity-input"
                                               min="1"
                                               max="99"
                                               x-model.number="item.quantity"
                                               @input="updateQuantity(item.id, $event.target.value)">
                                    </div>
                                    <!-- Show note for serialized items -->
                                    <div class="mt-2" x-show="item.serial_number">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i>Unique item (Serial: <span x-text="item.serial_number"></span>)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div class="border-top pt-3 mt-3">
                            <p class="mb-2"><strong>Total Items:</strong> <span x-text="cart.length"></span></p>
                            <p class="mb-0"><strong>Total Quantity:</strong> <span x-text="totalQuantity"></span></p>
                        </div>
                    </div>
                </template>
            </div>
            <div class="card-footer">
                <button type="button"
                        class="btn btn-outline-danger btn-sm w-100 mb-2"
                        @click="clearCart()"
                        :disabled="cart.length === 0">
                    <i class="bi bi-trash me-1"></i>Clear All
                </button>
                <button type="button"
                        class="btn btn-primary w-100"
                        data-bs-toggle="modal"
                        data-bs-target="#borrowerModal"
                        :disabled="cart.length === 0">
                    <i class="bi bi-arrow-right me-1"></i>Continue to Borrower Info
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Borrower Information Modal -->
<div class="modal fade" id="borrowerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="/index.php?route=borrowed-tools/batch/create" method="POST" @submit.prevent="submitBatch()">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-fill me-2"></i>Borrower Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Borrower Name with Autocomplete -->
                    <div class="mb-3 position-relative">
                        <label for="borrower_name" class="form-label">
                            Borrower Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               class="form-control"
                               id="borrower_name"
                               x-model="formData.borrower_name"
                               @input="showBorrowerSuggestions = true"
                               @blur="setTimeout(() => showBorrowerSuggestions = false, 200)"
                               required>

                        <!-- Borrower Suggestions Dropdown -->
                        <div class="card position-absolute w-100 mt-1"
                             style="z-index: 1000; max-height: 200px; overflow-y: auto;"
                             x-show="showBorrowerSuggestions && filteredBorrowers.length > 0"
                             x-transition>
                            <template x-for="borrower in filteredBorrowers" :key="borrower.borrower_name">
                                <div class="borrower-suggestion"
                                     @click="selectBorrower(borrower.borrower_name, borrower.borrower_contact || '')">
                                    <strong x-text="borrower.borrower_name"></strong>
                                    <template x-if="borrower.borrower_contact">
                                        <br><small class="text-muted" x-text="borrower.borrower_contact"></small>
                                    </template>
                                    <small class="text-muted float-end" x-text="borrower.borrow_count + ' times'"></small>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Borrower Contact -->
                    <div class="mb-3">
                        <label for="borrower_contact" class="form-label">Contact Information</label>
                        <input type="text"
                               class="form-control"
                               id="borrower_contact"
                               x-model="formData.borrower_contact"
                               placeholder="Phone number or email">
                        <div class="form-text">Optional but recommended</div>
                    </div>

                    <!-- Expected Return Date -->
                    <div class="mb-3">
                        <label for="expected_return" class="form-label">
                            Expected Return Date <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               class="form-control"
                               id="expected_return"
                               x-model="formData.expected_return"
                               :min="minDate"
                               required>
                    </div>

                    <!-- Purpose -->
                    <div class="mb-3">
                        <label for="purpose" class="form-label">Purpose</label>
                        <textarea class="form-control"
                                  id="purpose"
                                  x-model="formData.purpose"
                                  rows="3"
                                  placeholder="What will these tools be used for?"></textarea>
                    </div>

                    <!-- Summary -->
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2"></i>Batch Summary</h6>
                        <p class="mb-1"><strong>Total Items:</strong> <span x-text="cart.length"></span></p>
                        <p class="mb-1"><strong>Total Quantity:</strong> <span x-text="totalQuantity"></span></p>
                        <p class="mb-0">
                            <strong>Workflow:</strong>
                            <span x-text="hasCriticalTools ? 'Full MVA (requires approval)' : 'Streamlined (instant)'"></span>
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </button>
                    <button type="submit" class="btn btn-success" :disabled="submitting">
                        <i class="bi bi-check-circle me-1"></i>
                        <span x-text="submitting ? 'Creating Batch...' : 'Create Batch'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function batchBorrowingApp() {
    return {
        // Equipment data
        categories: <?= json_encode($groupedEquipment) ?>,
        activeCategory: Object.keys(<?= json_encode($groupedEquipment) ?>)[0] || 'power_tools',
        searchQuery: '',
        filteredItems: [],

        // Cart
        cart: [],

        // Form data
        formData: {
            borrower_name: '',
            borrower_contact: '',
            expected_return: '',
            purpose: ''
        },
        showBorrowerSuggestions: false,
        submitting: false,

        // Borrower suggestions data
        allBorrowers: <?= json_encode($commonBorrowers) ?>,

        // Computed
        get totalQuantity() {
            return this.cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
        },

        get hasCriticalTools() {
            return this.cart.some(item => item.acquisition_cost > 50000);
        },

        get minDate() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            return tomorrow.toISOString().split('T')[0];
        },

        get filteredBorrowers() {
            if (!this.formData.borrower_name || this.formData.borrower_name.length < 1) {
                return [];
            }

            const query = this.formData.borrower_name.toLowerCase();
            return this.allBorrowers.filter(borrower =>
                borrower.borrower_name.toLowerCase().includes(query)
            ).slice(0, 5); // Limit to 5 suggestions
        },

        init() {
            this.filterEquipment();

            // Set default return date to 7 days from now
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 7);
            this.formData.expected_return = defaultDate.toISOString().split('T')[0];
        },

        selectCategory(key) {
            this.activeCategory = key;
            this.searchQuery = '';
            this.filterEquipment();
        },

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

        isInCart(itemId) {
            return this.cart.some(item => item.id === itemId);
        },

        toggleItem(item) {
            const index = this.cart.findIndex(i => i.id === item.id);
            if (index >= 0) {
                this.cart.splice(index, 1);
            } else {
                this.cart.push({
                    ...item,
                    quantity: 1
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
                    item.quantity = 1;
                } else {
                    item.quantity = Math.max(1, Math.min(99, parseInt(quantity) || 1));
                }
            }
        },

        clearCart() {
            if (confirm('Are you sure you want to clear all selected items?')) {
                this.cart = [];
            }
        },

        selectBorrower(name, contact) {
            this.formData.borrower_name = name;
            this.formData.borrower_contact = contact;
            this.showBorrowerSuggestions = false;
        },


        async submitBatch() {
            if (this.submitting) return;

            if (!this.formData.borrower_name || !this.formData.expected_return) {
                alert('Please fill in all required fields');
                return;
            }

            if (this.cart.length === 0) {
                alert('Please select at least one item');
                return;
            }

            this.submitting = true;

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= CSRFProtection::generateToken() ?>');
                formData.append('borrower_name', this.formData.borrower_name);
                formData.append('borrower_contact', this.formData.borrower_contact);
                formData.append('expected_return', this.formData.expected_return);
                formData.append('purpose', this.formData.purpose);

                // Add cart items
                formData.append('items', JSON.stringify(this.cart.map(item => ({
                    asset_id: item.id,
                    quantity: item.quantity || 1
                }))));

                const response = await fetch('/index.php?route=borrowed-tools/batch/create', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Redirect to main borrowed tools list with success message
                    const messageParam = result.workflow_type === 'streamlined'
                        ? 'batch_created_released'
                        : 'batch_created_pending';
                    window.location.href = '/index.php?route=borrowed-tools&message=' + messageParam;
                } else {
                    alert('Error: ' + (result.message || 'Failed to create batch'));
                    this.submitting = false;
                }
            } catch (error) {
                console.error('Batch creation error:', error);
                alert('Failed to create batch. Please try again.');
                this.submitting = false;
            }
        }
    }
}
</script>

<?php
// Capture the buffered content
$content = ob_get_clean();

// Include the layout with the captured content
$pageTitle = 'Borrow Multiple Tools - ConstructLink™';
$pageHeader = 'Multi-Item Tool Borrowing';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
    ['title' => 'Borrow Multiple Tools', 'url' => '?route=borrowed-tools/create-batch']
];

include APP_ROOT . '/views/layouts/main.php';
?>
