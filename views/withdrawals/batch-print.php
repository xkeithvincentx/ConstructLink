<?php
/**
 * ConstructLink™ - Printable Consumable Withdrawal Batch Slip
 *
 * Purpose: Pre-filled form with actual withdrawal batch data
 * Format: Clean A4 page layout for printing
 */

// Get system name
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'system_name' LIMIT 1");
$systemName = $stmt->fetchColumn() ?: 'ConstructLink™';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Batch Slip - <?= htmlspecialchars($batch['batch_reference']) ?></title>
    <?php AssetHelper::loadModuleCSS('withdrawal-batch-print'); ?>
</head>
<body>

<!-- Print Controls -->
<div class="print-controls no-print">
    <button data-action="print" class="btn-print">
        🖨️ Print Slip
    </button>
    <button data-action="close" class="btn-close-window">
        ✕ Close
    </button>
</div>

<!-- Single Form -->
<div class="form-container">
    <!-- Header -->
    <div class="form-header">
        <h1><?= strtoupper($systemName) ?></h1>
        <h2>CONSUMABLE WITHDRAWAL BATCH SLIP</h2>
        <p class="subtitle">Inventory Management System</p>
    </div>

    <!-- Batch Information Section -->
    <div class="info-section">
        <div class="info-row">
            <div class="info-field">
                <span class="info-label">Batch Reference:</span>
                <span class="info-value strong"><?= htmlspecialchars($batch['batch_reference']) ?></span>
            </div>
            <div class="info-field">
                <span class="info-label">Date Issued:</span>
                <span class="info-value"><?= date('M d, Y', strtotime($batch['created_at'])) ?></span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-field">
                <span class="info-label">Receiver Name:</span>
                <span class="info-value"><?= htmlspecialchars($batch['receiver_name']) ?></span>
            </div>
            <div class="info-field">
                <span class="info-label">Contact:</span>
                <span class="info-value"><?= htmlspecialchars($batch['receiver_contact'] ?? '') ?></span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-field full-width">
                <span class="info-label">Purpose:</span>
                <span class="info-value"><?= htmlspecialchars($batch['purpose']) ?></span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-field">
                <span class="info-label">Status:</span>
                <span class="info-value status-<?= strtolower(str_replace(' ', '-', $batch['status'])) ?>">
                    <?= htmlspecialchars($batch['status']) ?>
                </span>
            </div>
            <div class="info-field">
                <span class="info-label">Released Date:</span>
                <span class="info-value">
                    <?= $batch['release_date'] ? date('M d, Y', strtotime($batch['release_date'])) : '_______________' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Consumables Table -->
    <table class="consumables-table">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="text-left">CONSUMABLE NAME</th>
                <th class="text-left col-ref">REFERENCE</th>
                <th class="text-left col-category">CATEGORY</th>
                <th class="col-qty">QTY</th>
                <th class="text-left col-unit">UNIT</th>
            </tr>
        </thead>
        <tbody>
            <?php $counter = 1; ?>
            <?php foreach ($batch['items'] as $item): ?>
            <tr>
                <td class="text-center"><?= $counter++ ?></td>
                <td><?= htmlspecialchars($item['consumable_name']) ?></td>
                <td><?= htmlspecialchars($item['consumable_ref']) ?></td>
                <td><?= htmlspecialchars($item['category_name']) ?></td>
                <td class="qty-col"><?= $item['quantity'] ?></td>
                <td><?= htmlspecialchars($item['unit'] ?? 'N/A') ?></td>
            </tr>
            <?php endforeach; ?>

            <!-- Fill empty rows if less than 10 items -->
            <?php for ($i = count($batch['items']); $i < 10; $i++): ?>
            <tr class="empty-row">
                <td class="text-center"><?= $i + 1 ?></td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">TOTAL:</th>
                <th class="qty-col"><?= array_sum(array_column($batch['items'], 'quantity')) ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <!-- Summary Section -->
    <div class="summary-section">
        <div class="summary-row">
            <div class="summary-item">
                <span class="summary-label">Total Items:</span>
                <span class="summary-value"><?= count($batch['items']) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Quantity:</span>
                <span class="summary-value"><?= array_sum(array_column($batch['items'], 'quantity')) ?></span>
            </div>
        </div>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="sig-box">
            <div class="sig-label">PREPARED BY</div>
            <div class="sig-space">
                <?php if (!empty($batch['created_by_name'])): ?>
                    <div class="sig-name">
                        <?= htmlspecialchars($batch['created_by_name']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sig-role">Maker (Warehouseman)</div>
            <div class="sig-date">
                Date: <?= date('M d, Y', strtotime($batch['created_at'])) ?>
            </div>
        </div>

        <div class="sig-box">
            <div class="sig-label">VERIFIED BY</div>
            <div class="sig-space">
                <?php if (!empty($batch['verified_by_name'])): ?>
                    <div class="sig-name">
                        <?= htmlspecialchars($batch['verified_by_name']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sig-role">Verifier (Project Manager)</div>
            <div class="sig-date">
                <?php if (!empty($batch['verified_at'])): ?>
                    Date: <?= date('M d, Y', strtotime($batch['verified_at'])) ?>
                <?php else: ?>
                    Date: _______________
                <?php endif; ?>
            </div>
        </div>

        <div class="sig-box">
            <div class="sig-label">APPROVED BY</div>
            <div class="sig-space">
                <?php if (!empty($batch['approved_by_name'])): ?>
                    <div class="sig-name">
                        <?= htmlspecialchars($batch['approved_by_name']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sig-role">Authorizer (Director)</div>
            <div class="sig-date">
                <?php if (!empty($batch['approved_at'])): ?>
                    Date: <?= date('M d, Y', strtotime($batch['approved_at'])) ?>
                <?php else: ?>
                    Date: _______________
                <?php endif; ?>
            </div>
        </div>

        <div class="sig-box">
            <div class="sig-label">RELEASED BY</div>
            <div class="sig-space">
                <?php if (!empty($batch['released_by_name'])): ?>
                    <div class="sig-name">
                        <?= htmlspecialchars($batch['released_by_name']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sig-role">Warehouseman</div>
            <div class="sig-date">
                <?php if (!empty($batch['release_date'])): ?>
                    Date: <?= date('M d, Y', strtotime($batch['release_date'])) ?>
                <?php else: ?>
                    Date: _______________
                <?php endif; ?>
            </div>
        </div>

        <div class="sig-box">
            <div class="sig-label">RECEIVED BY</div>
            <div class="sig-space">
                <div class="sig-name">
                    <?= htmlspecialchars($batch['receiver_name']) ?>
                </div>
            </div>
            <div class="sig-role">Receiver</div>
            <div class="sig-date">
                Date: _______________
            </div>
        </div>
    </div>

    <!-- Notes Section -->
    <?php if (!empty($batch['notes'])): ?>
    <div class="notes-section">
        <div class="notes-label">Additional Notes:</div>
        <div class="notes-content">
            <?= nl2br(htmlspecialchars($batch['notes'])) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="form-footer">
        <div class="footer-left">
            <div class="qr-code">
                <!-- QR Code placeholder - implement with library if needed -->
                <div class="qr-placeholder">
                    <?= htmlspecialchars($batch['batch_reference']) ?>
                </div>
            </div>
        </div>
        <div class="footer-center">
            <p class="footer-text">This is a computer-generated document.</p>
            <p class="footer-text">No signature required for system-generated slips.</p>
        </div>
        <div class="footer-right">
            <p class="footer-text">Print Date: <?= date('M d, Y H:i') ?></p>
            <p class="footer-text"><?= $systemName ?></p>
        </div>
    </div>
</div>

<?php AssetHelper::loadModuleJS('print-controls'); ?>

<style>
/* Print-specific styles */
@media print {
    .no-print, .print-controls {
        display: none !important;
    }

    body {
        margin: 0;
        padding: 0;
    }

    .form-container {
        box-shadow: none;
        margin: 0;
        padding: 20px;
    }
}

/* Screen styles */
@media screen {
    body {
        background: #f5f5f5;
        padding: 20px;
    }

    .print-controls {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        background: white;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        gap: 10px;
    }

    .btn-print, .btn-close-window {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-print {
        background: #0d6efd;
        color: white;
    }

    .btn-close-window {
        background: #6c757d;
        color: white;
    }

    .form-container {
        max-width: 210mm;
        min-height: 297mm;
        margin: 0 auto;
        background: white;
        padding: 20mm;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
}

/* General Styles */
.form-header {
    text-align: center;
    border-bottom: 3px solid #333;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.form-header h1 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.form-header h2 {
    margin: 5px 0;
    font-size: 18px;
    font-weight: bold;
    color: #555;
}

.form-header .subtitle {
    margin: 5px 0 0 0;
    font-size: 12px;
    color: #777;
}

/* Info Section */
.info-section {
    margin-bottom: 20px;
    border: 1px solid #ddd;
    padding: 15px;
    background: #f9f9f9;
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.info-row:last-child {
    margin-bottom: 0;
}

.info-field {
    flex: 1;
    display: flex;
    padding: 4px 0;
}

.info-field.full-width {
    flex: 1 0 100%;
}

.info-label {
    font-weight: bold;
    min-width: 120px;
    color: #555;
}

.info-value {
    flex: 1;
    color: #333;
}

.info-value.strong {
    font-weight: bold;
    font-size: 16px;
}

/* Consumables Table */
.consumables-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.consumables-table thead {
    background: #333;
    color: white;
}

.consumables-table th,
.consumables-table td {
    border: 1px solid #ddd;
    padding: 8px;
    font-size: 12px;
}

.consumables-table th {
    font-weight: bold;
    text-align: center;
}

.consumables-table .text-left {
    text-align: left;
}

.consumables-table .text-right {
    text-align: right;
}

.consumables-table .col-num {
    width: 40px;
}

.consumables-table .col-ref {
    width: 120px;
}

.consumables-table .col-category {
    width: 120px;
}

.consumables-table .col-qty {
    width: 60px;
    text-align: center;
}

.consumables-table .col-unit {
    width: 80px;
}

.consumables-table .qty-col {
    text-align: center;
    font-weight: bold;
}

.consumables-table tbody tr.empty-row {
    height: 30px;
}

.consumables-table tfoot {
    background: #f0f0f0;
}

.consumables-table tfoot th {
    font-weight: bold;
    color: #333;
}

/* Summary Section */
.summary-section {
    margin-bottom: 20px;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #ddd;
}

.summary-row {
    display: flex;
    justify-content: space-around;
}

.summary-item {
    text-align: center;
}

.summary-label {
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
    color: #555;
}

.summary-value {
    font-size: 20px;
    font-weight: bold;
    color: #333;
}

/* Signature Section */
.signature-section {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
    margin-top: 30px;
}

.sig-box {
    border: 1px solid #ddd;
    padding: 10px;
    min-height: 100px;
    background: #fafafa;
}

.sig-label {
    font-weight: bold;
    font-size: 11px;
    text-align: center;
    margin-bottom: 5px;
    color: #555;
}

.sig-space {
    min-height: 50px;
    border-bottom: 2px solid #333;
    margin-bottom: 5px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.sig-name {
    font-weight: bold;
    text-align: center;
    padding-bottom: 2px;
}

.sig-role {
    text-align: center;
    font-size: 11px;
    color: #666;
    margin-bottom: 3px;
}

.sig-date {
    text-align: center;
    font-size: 10px;
    color: #666;
}

/* Notes Section */
.notes-section {
    margin-bottom: 20px;
    padding: 10px;
    border: 1px solid #ddd;
    background: #fffbf0;
}

.notes-label {
    font-weight: bold;
    margin-bottom: 5px;
    color: #555;
}

.notes-content {
    font-size: 12px;
    color: #333;
}

/* Footer */
.form-footer {
    border-top: 2px solid #333;
    padding-top: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.footer-left {
    flex: 1;
}

.footer-center {
    flex: 2;
    text-align: center;
}

.footer-right {
    flex: 1;
    text-align: right;
}

.footer-text {
    margin: 2px 0;
    font-size: 10px;
    color: #666;
}

.qr-placeholder {
    width: 60px;
    height: 60px;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    text-align: center;
    padding: 5px;
    word-break: break-all;
}
</style>

</body>
</html>
