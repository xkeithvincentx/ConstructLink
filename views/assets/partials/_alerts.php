<?php
/**
 * Alerts Partial
 * Displays maintenance and stock level alert messages
 */
?>

<!-- Maintenance Alerts -->
<?php if (!empty($assetsDueForMaintenance)): ?>
    <div class="alert alert-warning" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2"></i>Items Due for Maintenance
        </h6>
        <p class="mb-2">There are <?= count($assetsDueForMaintenance) ?> item(s) that require maintenance attention:</p>
        <ul class="mb-0">
            <?php foreach (array_slice($assetsDueForMaintenance, 0, 3) as $asset): ?>
                <li>
                    <strong><?= htmlspecialchars($asset['name']) ?></strong> 
                    (<?= htmlspecialchars($asset['ref']) ?>) - 
                    <?= $asset['days_until_due'] > 0 ? $asset['days_until_due'] . ' days until due' : 'Overdue' ?>
                    <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="ms-2">View Details</a>
                </li>
            <?php endforeach; ?>
            <?php if (count($assetsDueForMaintenance) > 3): ?>
                <li><em>... and <?= count($assetsDueForMaintenance) - 3 ?> more</em></li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Stock Level Alerts -->
<?php if (isset($assetStats['out_of_stock_count']) && $assetStats['out_of_stock_count'] > 0): ?>
    <div class="alert alert-danger" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-circle me-2"></i>Out of Stock Alert
        </h6>
        <p class="mb-2">
            <strong><?= $assetStats['out_of_stock_count'] ?></strong> consumable item(s) are completely out of stock and need immediate replenishment.
        </p>
        <a href="?route=assets&asset_type=out_of_stock" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-eye me-1"></i>View Out of Stock Items
        </a>
    </div>
<?php endif; ?>

<?php if (isset($assetStats['low_stock_count']) && $assetStats['low_stock_count'] > 0): ?>
    <div class="alert alert-warning" role="alert">
        <h6 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2"></i>Low Stock Warning
        </h6>
        <p class="mb-2">
            <strong><?= $assetStats['low_stock_count'] ?></strong> consumable item(s) are running low on stock (below 20% of total quantity).
        </p>
        <div class="d-flex gap-2">
            <a href="?route=assets&asset_type=low_stock" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-eye me-1"></i>View Low Stock Items
            </a>
            <?php if (in_array($userRole, $roleConfig['procurement-orders/create'] ?? [])): ?>
                <a href="?route=procurement-orders/create" class="btn btn-sm btn-warning">
                    <i class="bi bi-plus-circle me-1"></i>Create Procurement Order
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
