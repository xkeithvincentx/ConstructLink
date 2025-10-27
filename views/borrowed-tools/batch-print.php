<?php
/**
 * ConstructLink™ - Printable Borrowing Form (2x2 Grid - Filled)
 *
 * Purpose: Pre-filled form with actual borrowing data
 * Format: 4 copies per A4 page in 2x2 grid (matching blank form layout)
 */

// Get system name
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'system_name' LIMIT 1");
$systemName = $stmt->fetchColumn() ?: 'ConstructLink™';

// Get critical tool threshold from config
$criticalThreshold = config('business_rules.critical_tool_threshold', 50000);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing Form - <?= htmlspecialchars($batch['batch_reference']) ?></title>
    <?php AssetHelper::loadModuleCSS('borrowed-tools-print-filled'); ?>
</head>
<body>

<!-- Print Controls -->
<div class="print-controls no-print">
    <button data-action="print" class="btn-print">
        🖨️ Print Form
    </button>
    <button data-action="close" class="btn-close-window">
        ✕ Close
    </button>
</div>

<!-- Single Form -->
<div class="form-container">
    <!-- Header -->
    <div class="form-header">
        <h1><?= strtoupper($systemName) ?> BORROWING FORM</h1>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-row">
            <div class="info-field">
                <span class="info-label">Name:</span>
                <span class="info-value"><?= htmlspecialchars($batch['borrower_name']) ?></span>
            </div>
            <div class="info-field">
                <span class="info-label">Date Filled:</span>
                <span class="info-value"><?= date('M d, Y', strtotime($batch['created_at'])) ?></span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-field">
                <span class="info-label">Contact:</span>
                <span class="info-value"><?= htmlspecialchars($batch['borrower_contact'] ?? '') ?></span>
            </div>
            <div class="info-field">
                <span class="info-label">Released Date:</span>
                <span class="info-value">
                    <?= $batch['release_date'] ? date('M d, Y', strtotime($batch['release_date'])) : '' ?>
                </span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-field">
                <span class="info-label">Returned Date:</span>
                <span class="info-value">
                    <?= $batch['return_date'] ? date('M d, Y', strtotime($batch['return_date'])) : '' ?>
                </span>
            </div>
            <div class="info-field">
                <span class="info-label">Reference No.:</span>
                <span class="info-value"><?= htmlspecialchars($batch['batch_reference']) ?></span>
            </div>
        </div>
    </div>

    <!-- Equipment Table -->
    <table class="equipment-table">
            <thead>
                <tr>
                    <th class="col-checkbox"></th>
                    <th class="text-left">EQUIPMENT</th>
                    <th class="col-qty">Qty<br>Out</th>
                    <th class="col-qty">Qty<br>In</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batch['items'] as $item): ?>
                <tr>
                    <td class="text-center"><span class="checkbox"></span></td>
                    <td<?= ($item['acquisition_cost'] > $criticalThreshold) ? ' class="critical-item"' : '' ?>>
                        <?= htmlspecialchars($item['asset_name']) ?>
                        <?php if ($item['acquisition_cost'] > $criticalThreshold): ?>
                            <span class="critical-star">★</span>
                        <?php endif; ?>
                    </td>
                    <td class="qty-col"><?= $item['quantity'] ?></td>
                    <td class="qty-col return-col">
                        <?= $item['quantity_returned'] > 0 ? $item['quantity_returned'] : '' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="sig-box">
            <div class="sig-label">BORROWED BY</div>
            <div class="sig-space">
                <div class="sig-name">
                    <?= htmlspecialchars($batch['borrower_name']) ?>
                </div>
            </div>
            <div class="sig-role">Signature / Printed Name</div>
        </div>
        <div class="sig-box">
            <div class="sig-label">RELEASED BY</div>
            <div class="sig-space">
                <?php if ($batch['released_by_name']): ?>
                    <div class="sig-name">
                        <?= htmlspecialchars($batch['released_by_name']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sig-role">Warehouseman</div>
        </div>
        <div class="sig-box">
            <div class="sig-label">RETURNED TO</div>
            <div class="sig-space">
                <?php if ($batch['returned_by_name']): ?>
                    <div class="sig-name">
                        <?= htmlspecialchars($batch['returned_by_name']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sig-role">Received By</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="form-footer">
        <?= $systemName ?> by Ranoa Digital Solutions
    </div>
</div><!-- End form-container -->

<?php AssetHelper::loadModuleJS('print-controls'); ?>
</body>
</html>
