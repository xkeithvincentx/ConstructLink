<?php
/**
 * Procurement & Vendor Information Section Partial
 * Procurement order, vendor, and client-supplied asset (STANDARD MODE ONLY)
 *
 * Required Variables:
 * @var array $formData - Form data for pre-filling (optional)
 * @var array $procurementOrders - Available procurement orders
 * @var array $vendors - Available vendors
 * @var array $clients - Available clients
 *
 * @package ConstructLink
 * @subpackage Views\Assets\Partials
 * @version 1.0.0
 * @since Phase 2 Refactoring
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}
?>

<!-- Procurement & Vendor Information -->
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-primary border-bottom pb-2 mb-3">
            <i class="bi bi-building me-1" aria-hidden="true"></i>Procurement & Vendor Information
        </h6>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <label for="procurement_order_id" class="form-label">Procurement Order</label>
            <select class="form-select" id="procurement_order_id" name="procurement_order_id">
                <option value="">Select Procurement Order (Optional)</option>
                <?php if (!empty($procurementOrders)): ?>
                    <?php foreach ($procurementOrders as $order): ?>
                        <option value="<?= $order['id'] ?>"
                                data-vendor="<?= htmlspecialchars($order['vendor_name'] ?? '') ?>"
                                data-vendor-id="<?= $order['vendor_id'] ?? '' ?>"
                                <?= ($formData['procurement_order_id'] ?? '') == $order['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($order['po_number'] ?: '#' . $order['id']) ?> -
                            <?= htmlspecialchars($order['title']) ?>
                            (<?= $order['item_count'] ?? 0 ?> items)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <div class="form-text">Link to procurement order if asset was purchased</div>
        </div>
    </div>

    <div class="col-md-6 d-none" id="procurement_item_container">
        <div class="mb-3">
            <label for="procurement_item_id" class="form-label">Procurement Item <span class="text-danger">*</span></label>
            <select class="form-select" id="procurement_item_id" name="procurement_item_id">
                <option value="">Select Item</option>
            </select>
            <div class="form-text">Select specific item from procurement order</div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <label for="vendor_id" class="form-label">Vendor</label>
            <select class="form-select" id="vendor_id" name="vendor_id">
                <option value="">Select Vendor</option>
                <?php if (!empty($vendors)): ?>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?= $vendor['id'] ?>"
                                <?= ($formData['vendor_id'] ?? '') == $vendor['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vendor['name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <label for="client_id" class="form-label">Client (if client-supplied)</label>
            <select class="form-select" id="client_id" name="client_id">
                <option value="">Select Client</option>
                <?php if (!empty($clients)): ?>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>"
                                <?= ($formData['client_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="is_client_supplied" name="is_client_supplied"
                   <?= !empty($formData['is_client_supplied']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_client_supplied">
                Client Supplied Asset
            </label>
        </div>
    </div>
</div>
