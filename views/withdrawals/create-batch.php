<?php
/**
 * ConstructLink™ - Multi-Item Batch Withdrawal
 * Shopping cart style interface for withdrawing multiple consumables at once
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();

if (!hasPermission('withdrawals/create')) {
    echo '<div class="alert alert-danger">You do not have permission to create a withdrawal request.</div>';
    return;
}

// Get grouped consumable items using helper
require_once APP_ROOT . '/core/EquipmentCategoryHelper.php';
$groupedConsumables = EquipmentCategoryHelper::getGroupedConsumables($user['current_project_id']);
$commonReceivers = EquipmentCategoryHelper::getCommonReceivers($user['current_project_id'], 10);

// Check if consumable loading failed or no consumables available
if (empty($groupedConsumables)) {
    echo '<div class="alert alert-warning">';
    echo '<i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>';
    echo 'No consumable items available for withdrawal in your project. Please contact your administrator.';
    echo '</div>';
    $content = ob_get_clean();
    $pageTitle = 'Withdraw Multiple Consumables - ConstructLink™';
    $pageHeader = 'Multi-Item Consumable Withdrawal';
    $breadcrumbs = [
        ['title' => 'Dashboard', 'url' => '?route=dashboard'],
        ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
        ['title' => 'Withdraw Multiple Consumables', 'url' => '?route=withdrawals/create-batch']
    ];
    include APP_ROOT . '/views/layouts/main.php';
    return;
}
?>

<!-- Load withdrawal forms CSS -->
<?php
require_once APP_ROOT . '/helpers/AssetHelper.php';
AssetHelper::loadModuleCSS('withdrawal-forms');
?>

<!-- MVA Workflow Info -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-info shadow-sm h-100">
            <div class="card-body bg-info bg-opacity-10 d-flex flex-column">
                <h6 class="text-info mb-2">
                    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                    <strong>Consumable Withdrawal Workflow</strong> - Full MVA Process
                </h6>
                <p class="mb-2"><span class="badge bg-warning text-dark">Maker-Verifier-Authorizer</span></p>
                <small class="text-muted mt-auto">
                    <span class="badge bg-primary">Maker</span> (Warehouseman) →
                    <span class="badge bg-warning text-dark">Verifier</span> (Site Inventory Clerk) →
                    <span class="badge bg-success">Authorizer</span> (Project Manager)
                    <span class="badge bg-primary ms-1">Quantities Reserved</span> →
                    <span class="badge bg-info">Released</span>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Reservation Notice -->
<div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
    <div class="d-flex">
        <div class="flex-shrink-0">
            <i class="bi bi-info-circle-fill fs-5 me-2" aria-hidden="true"></i>
        </div>
        <div class="flex-grow-1">
            <h6 class="alert-heading mb-2">How Inventory Reservation Works</h6>
            <ul class="mb-0 ps-3">
                <li><strong>Available quantities shown are real-time</strong> - they reflect current stock minus approved withdrawals</li>
                <li><strong>Pending withdrawals</strong> (shown with <span class="badge bg-warning text-dark small">⚠ pending</span>) have NOT reserved quantities yet</li>
                <li><strong>Quantities are reserved when Authorizer approves</strong> - first approved withdrawal gets the items</li>
                <li>If multiple users create requests for the same item, <strong>first-approved-wins</strong></li>
            </ul>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<!-- Messages -->
<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="status" aria-live="polite">
            <i class="bi bi-check-circle me-2" aria-hidden="true"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert" aria-live="assertive">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row" x-data="batchWithdrawalApp()">
    <!-- Left Panel: Consumable Selection -->
    <div class="col-lg-8 col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-box me-2" aria-hidden="true"></i>Select Consumable Items
                </h5>
            </div>
            <div class="card-body">
                <!-- Search Bar -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                        <input type="text"
                               class="form-control"
                               placeholder="Search consumables by name or reference..."
                               x-model="searchQuery"
                               @input="filterConsumables()"
                               aria-label="Search consumables by name or reference">
                        <button class="btn btn-outline-secondary" @click="clearSearch()" aria-label="Clear search" title="Clear search">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
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

                <!-- Consumables Grid -->
                <div class="row g-3 consumables-grid-container" role="region" aria-label="Consumable selection grid" aria-live="polite" tabindex="0">
                    <template x-for="item in filteredItems" :key="item.id">
                        <div class="col-md-6 col-12">
                            <div class="card consumable-card h-100"
                                 :class="isInCart(item.id) ? 'selected' : ''"
                                 @click="toggleItem(item)"
                                 @keydown.enter="toggleItem(item)"
                                 @keydown.space.prevent="toggleItem(item)"
                                 tabindex="0"
                                 role="button"
                                 :aria-pressed="isInCart(item.id) ? 'true' : 'false'"
                                 :aria-label="'Add ' + item.name + ' to cart'">
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
                                            <template x-if="item.unit">
                                                <p class="text-muted small mb-1">
                                                    <strong>Unit:</strong> <span x-text="item.unit"></span>
                                                </p>
                                            </template>
                                            <p class="mb-0">
                                                <span class="badge"
                                                      :class="item.available_quantity > 0 ? 'bg-success' : 'bg-danger'">
                                                    <i class="bi bi-box me-1" aria-hidden="true"></i>
                                                    <span x-text="item.available_quantity + ' available'"></span>
                                                </span>
                                            </p>
                                            <template x-if="item.pending_withdrawal_count && item.pending_withdrawal_count > 0">
                                                <p class="mb-0 mt-1">
                                                    <span class="badge bg-warning text-dark"
                                                          :title="item.pending_withdrawal_count + ' pending withdrawal' + (item.pending_withdrawal_count > 1 ? 's' : '') + ' (not yet approved)'">
                                                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                                                        <span x-text="item.pending_withdrawal_count + ' pending'"></span>
                                                    </span>
                                                </p>
                                            </template>
                                        </div>
                                        <div class="text-end">
                                            <template x-if="isInCart(item.id)">
                                                <div>
                                                    <i class="bi bi-check-circle-fill text-primary fs-4" aria-hidden="true"></i>
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
                                <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                                No consumables found. Try a different category or search term.
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile: Floating Cart Button -->
    <button class="btn btn-success btn-lg cart-badge d-lg-none rounded-circle"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#cartOffcanvas"
            :aria-label="cart.length > 0 ? 'View cart. ' + cart.length + ' item' + (cart.length === 1 ? '' : 's') + ' selected' : 'View cart. No items selected'">
        <i class="bi bi-cart3 fs-5" aria-hidden="true"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge-count"
              x-show="cart.length > 0"
              x-text="cart.length"></span>
    </button>

    <!-- Mobile: Cart Offcanvas (Bottom) -->
    <div class="offcanvas offcanvas-bottom cart-offcanvas-mobile d-lg-none" tabindex="-1" id="cartOffcanvas">
        <div class="offcanvas-header bg-success text-white">
            <h5 class="offcanvas-title">
                <i class="bi bi-cart3 me-2" aria-hidden="true"></i>Selected Items
                <span class="badge bg-light text-dark ms-2" x-text="cart.length"></span>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close cart"></button>
        </div>
        <div class="offcanvas-body cart-offcanvas-body">
            <template x-if="cart.length === 0">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-cart-x fs-1" aria-hidden="true"></i>
                    <p class="mt-2">No items selected</p>
                    <p class="small">Click on consumable cards to add them</p>
                </div>
            </template>

            <template x-if="cart.length > 0">
                <div>
                    <template x-for="(item, index) in cart" :key="item.id">
                        <div class="card mb-3 border">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1" x-text="item.name"></h6>
                                        <p class="text-muted small mb-1">
                                            <strong>Ref:</strong> <span x-text="item.ref"></span>
                                        </p>
                                    </div>
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            @click="removeFromCart(item.id)"
                                            :aria-label="'Remove ' + item.name + ' from cart'"
                                            :title="'Remove ' + item.name + ' from cart'">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label small mb-1">
                                        Quantity:
                                        <i class="bi bi-info-circle text-muted"
                                           aria-hidden="true"
                                           title="Select quantity to withdraw"></i>
                                    </label>
                                    <input type="number"
                                           class="form-control form-control-sm quantity-input-mobile"
                                           min="1"
                                           :max="item.available_quantity"
                                           x-model.number="item.quantity"
                                           @input="updateQuantity(item.id, $event.target.value)"
                                           :aria-label="'Quantity for ' + item.name">
                                    <div class="form-text text-xs">
                                        Up to <span x-text="item.available_quantity"></span> units available
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <small class="text-muted d-block">Total Items</small>
                                    <strong class="fs-5" x-text="cart.length"></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Total Quantity</small>
                                    <strong class="fs-5" x-text="totalQuantity"></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div class="offcanvas-footer p-3 border-top bg-light">
            <button type="button"
                    class="btn btn-outline-danger btn-sm w-100 mb-2"
                    @click="clearCart()"
                    :disabled="cart.length === 0"
                    aria-label="Remove all items from cart"
                    title="Remove all items from cart">
                <i class="bi bi-trash me-1" aria-hidden="true"></i>Clear All
            </button>
            <button type="button"
                    class="btn btn-primary w-100"
                    data-bs-toggle="modal"
                    data-bs-target="#receiverModal"
                    data-bs-dismiss="offcanvas"
                    :disabled="cart.length === 0">
                <i class="bi bi-arrow-right me-1" aria-hidden="true"></i>Continue to Receiver Info
            </button>
        </div>
    </div>

    <!-- Desktop: Right Panel Shopping Cart -->
    <div class="col-lg-4 d-none d-lg-block">
        <div class="card shadow-sm sticky-cart">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-cart3 me-2" aria-hidden="true"></i>Selected Items
                    <span class="badge bg-light text-dark ms-2" x-text="cart.length"></span>
                </h5>
            </div>
            <div class="card-body cart-body-scroll" role="region" aria-label="Shopping cart items" tabindex="0">
                <template x-if="cart.length === 0">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-cart-x fs-1" aria-hidden="true"></i>
                        <p class="mt-2">No items selected</p>
                        <p class="small">Click on consumable cards to add them</p>
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
                                            <p class="text-muted mb-1 text-xs">
                                                <span x-text="item.ref"></span>
                                            </p>
                                        </div>
                                        <button type="button"
                                                class="btn btn-sm btn-danger"
                                                @click="removeFromCart(item.id)"
                                                :aria-label="'Remove ' + item.name + ' from cart'"
                                                :title="'Remove ' + item.name + ' from cart'">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label small mb-1">
                                            Quantity:
                                            <i class="bi bi-info-circle text-muted"
                                               aria-hidden="true"
                                               title="Select quantity to withdraw"></i>
                                        </label>
                                        <input type="number"
                                               class="form-control form-control-sm quantity-input"
                                               min="1"
                                               :max="item.available_quantity"
                                               x-model.number="item.quantity"
                                               @input="updateQuantity(item.id, $event.target.value)"
                                               :aria-label="'Quantity for ' + item.name">
                                        <div class="form-text text-xs">
                                            Up to <span x-text="item.available_quantity"></span> units available
                                        </div>
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
                        :disabled="cart.length === 0"
                        aria-label="Remove all items from cart"
                        title="Remove all items from cart">
                    <i class="bi bi-trash me-1" aria-hidden="true"></i>Clear All
                </button>
                <button type="button"
                        class="btn btn-primary w-100"
                        @click="proceedToReceiverInfo()"
                        :disabled="cart.length === 0">
                    <i class="bi bi-arrow-right me-1" aria-hidden="true"></i>Continue to Receiver Info
                </button>

                <!-- Availability Warning -->
                <div class="alert alert-warning alert-sm mt-2 mb-0 py-2 px-2 text-xs">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    <small><strong>Note:</strong> Consumable availability is validated when you create the batch. Quantities may change if another user withdraws items first.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Receiver Information Modal -->
    <div class="modal fade" id="receiverModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form @submit.prevent="submitBatch()">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-fill me-2" aria-hidden="true"></i>Receiver Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Receiver Name Fields with Autocomplete -->
                    <div class="row mb-3">
                        <div class="col-md-6 position-relative">
                            <label for="receiver_last_name" class="form-label">
                                Last Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control text-capitalize"
                                   id="receiver_last_name"
                                   x-model="formData.receiver_last_name"
                                   @input="updateReceiverSearch(); showReceiverSuggestions = true"
                                   @blur="setTimeout(() => showReceiverSuggestions = false, 200)"
                                   placeholder="e.g., Dela Cruz"
                                   required>
                        </div>
                        <div class="col-md-6 position-relative">
                            <label for="receiver_first_name" class="form-label">
                                First Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control text-capitalize"
                                   id="receiver_first_name"
                                   x-model="formData.receiver_first_name"
                                   @input="updateReceiverSearch(); showReceiverSuggestions = true"
                                   @blur="setTimeout(() => showReceiverSuggestions = false, 200)"
                                   placeholder="e.g., Juan"
                                   required>
                        </div>
                    </div>

                    <!-- Receiver Suggestions Dropdown -->
                    <div class="mb-3 position-relative">
                        <div class="card receiver-suggestions-dropdown"
                             x-show="showReceiverSuggestions && filteredReceivers.length > 0"
                             x-transition>
                            <div class="card-header bg-light py-2">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                    Select from previous receivers or continue typing
                                </small>
                            </div>
                            <div class="list-group list-group-flush" role="listbox">
                                <template x-for="(receiver, index) in filteredReceivers" :key="receiver.receiver_name">
                                    <div class="list-group-item list-group-item-action receiver-suggestion"
                                         @click="selectReceiver(receiver)"
                                         @keydown.enter="selectReceiver(receiver)"
                                         role="option"
                                         tabindex="0"
                                         :aria-label="'Select receiver: ' + receiver.receiver_name">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <strong x-text="receiver.receiver_name"></strong>
                                                <template x-if="receiver.receiver_contact">
                                                    <br><small class="text-muted">
                                                        <i class="bi bi-telephone me-1" aria-hidden="true"></i>
                                                        <span x-text="receiver.receiver_contact"></span>
                                                    </small>
                                                </template>
                                            </div>
                                            <div class="text-end">
                                                <small class="badge bg-secondary" x-text="receiver.withdrawal_count + ' times'"></small>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Receiver Contact -->
                    <div class="mb-3">
                        <label for="receiver_contact" class="form-label">Contact Information</label>
                        <input type="text"
                               class="form-control"
                               id="receiver_contact"
                               x-model="formData.receiver_contact"
                               placeholder="Phone number or email">
                        <div class="form-text">Optional but recommended</div>
                    </div>

                    <!-- Purpose -->
                    <div class="mb-3">
                        <label for="purpose" class="form-label">
                            Purpose <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control"
                                  id="purpose"
                                  x-model="formData.purpose"
                                  rows="3"
                                  placeholder="What will these consumables be used for?"
                                  required></textarea>
                    </div>

                    <!-- Summary -->
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2" aria-hidden="true"></i>Batch Summary</h6>
                        <p class="mb-1"><strong>Total Items:</strong> <span x-text="cart.length"></span></p>
                        <p class="mb-1"><strong>Total Quantity:</strong> <span x-text="totalQuantity"></span></p>
                        <p class="mb-0">
                            <strong>Workflow:</strong> Full MVA (requires verification and approval)
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
                    </button>
                    <button type="submit" class="btn btn-success" :disabled="submitting">
                        <span x-show="!submitting">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Create Batch
                        </span>
                        <span x-show="submitting">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Creating Batch...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>
</div>

<!-- Load batch withdrawal JavaScript module -->
<script type="module">
import { initBatchWithdrawalApp } from '/assets/js/withdrawals/batch-withdrawal.js';

// Configuration from PHP
const config = {
    categories: <?= json_encode($groupedConsumables) ?>,
    commonReceivers: <?= json_encode($commonReceivers) ?>,
    csrfToken: '<?= CSRFProtection::generateToken() ?>'
};

// Initialize batch withdrawal app
window.batchWithdrawalApp = function() {
    return initBatchWithdrawalApp(config);
};
</script>


<?php
// Capture the buffered content
$content = ob_get_clean();

// Include the layout with the captured content
require_once APP_ROOT . '/helpers/BrandingHelper.php';
$pageTitle = BrandingHelper::getPageTitle('Withdraw Multiple Consumables');
$pageHeader = 'Multi-Item Consumable Withdrawal';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
    ['title' => 'Withdraw Multiple Consumables', 'url' => '?route=withdrawals/create-batch']
];

include APP_ROOT . '/views/layouts/main.php';
?>
