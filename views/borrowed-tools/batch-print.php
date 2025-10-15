<?php
/**
 * ConstructLink™ - Printable Borrowing Form (2x2 Grid - Filled)
 * Developed by: Ranoa Digital Solutions
 *
 * Purpose: Pre-filled form with actual borrowing data
 * Format: 4 copies per A4 page in 2x2 grid (matching blank form layout)
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
    <title>Borrowing Form - <?= htmlspecialchars($batch['batch_reference']) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            color: #000;
            background: #fff;
        }

        /* Single Form Container - Quarter Size (same as 2x2 grid) */
        .form-container {
            width: 95mm;  /* Half of A4 width */
            height: 135mm; /* Half of A4 height */
            margin: 0 auto;
            border: 2px solid #000;
            padding: 3mm;
        }

        .form-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 1mm;
            margin-bottom: 2mm;
        }

        .form-header h1 {
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.1;
        }

        /* Info Section - Compact */
        .info-section {
            font-size: 6pt;
            margin-bottom: 2mm;
        }

        .info-row {
            display: flex;
            gap: 2mm;
            margin-bottom: 1mm;
        }

        .info-field {
            flex: 1;
            display: flex;
            align-items: center;
        }

        .info-label {
            font-weight: bold;
            margin-right: 1mm;
            white-space: nowrap;
        }

        .info-value {
            border-bottom: 1px solid #000;
            flex: 1;
            min-height: 3mm;
            padding: 0 1mm;
        }

        /* Equipment Table - Compact */
        .equipment-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 2mm;
            font-size: 5.5pt;
        }

        .equipment-table th {
            background: #000;
            color: #fff;
            padding: 0.5mm;
            font-size: 5pt;
            font-weight: bold;
            text-align: center;
            border: 1px solid #000;
        }

        .equipment-table td {
            padding: 0.5mm 1mm;
            border: 0.5px solid #999;
            vertical-align: middle;
        }

        .checkbox {
            width: 2.5mm;
            height: 2.5mm;
            border: 1px solid #000;
            display: inline-block;
        }

        .qty-col {
            width: 8mm;
            text-align: center;
        }

        .return-col {
            background: #f0f0f0;
        }

        /* Signature Section - Compact */
        .signature-section {
            display: flex;
            gap: 1mm;
            font-size: 5pt;
        }

        .sig-box {
            flex: 1;
            text-align: center;
            border: 1px solid #000;
            padding: 1mm;
            min-height: 12mm;
        }

        .sig-label {
            font-weight: bold;
            margin-bottom: 0.5mm;
        }

        .sig-space {
            min-height: 8mm;
            border-bottom: 1px solid #000;
            margin-bottom: 0.5mm;
        }

        /* Footer */
        .form-footer {
            text-align: center;
            margin-top: 1mm;
            padding-top: 0.5mm;
            border-top: 0.5px solid #ccc;
            font-size: 4pt;
            color: #666;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }

        .print-controls {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #fff;
            padding: 15px;
            border: 2px solid #000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1000;
        }

        @media print {
            .print-controls {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- Print Controls -->
<div class="print-controls no-print">
    <button onclick="window.print()" style="padding: 12px 24px; font-size: 14pt; cursor: pointer; background: #007bff; color: #fff; border: none; border-radius: 4px; font-weight: bold;">
        🖨️ Print Form
    </button>
    <button onclick="window.close()" style="padding: 12px 24px; font-size: 14pt; cursor: pointer; background: #6c757d; color: #fff; border: none; border-radius: 4px; margin-left: 5px;">
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
                    <th style="width: 4mm;"></th>
                    <th style="text-align: left;">EQUIPMENT</th>
                    <th style="width: 8mm;">Qty<br>Out</th>
                    <th style="width: 8mm;">Qty<br>In</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batch['items'] as $item): ?>
                <tr>
                    <td style="text-align: center;"><span class="checkbox"></span></td>
                    <td style="font-weight: <?= ($item['acquisition_cost'] > 50000) ? 'bold' : 'normal' ?>;">
                        <?= htmlspecialchars($item['asset_name']) ?>
                        <?php if ($item['acquisition_cost'] > 50000): ?>
                            <span style="color: #f00;">★</span>
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
                <div style="margin-top: 4mm; font-size: 6pt; font-weight: bold;">
                    <?= htmlspecialchars($batch['borrower_name']) ?>
                </div>
            </div>
            <div style="font-size: 5pt;">Signature / Printed Name</div>
        </div>
        <div class="sig-box">
            <div class="sig-label">RELEASED BY</div>
            <div class="sig-space">
                <?php if ($batch['released_by_name']): ?>
                    <div style="margin-top: 4mm; font-size: 6pt; font-weight: bold;">
                        <?= htmlspecialchars($batch['released_by_name']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div style="font-size: 5pt;">Warehouseman</div>
        </div>
        <div class="sig-box">
            <div class="sig-label">RETURNED TO</div>
            <div class="sig-space">
                <?php if ($batch['returned_by_name']): ?>
                    <div style="margin-top: 4mm; font-size: 6pt; font-weight: bold;">
                        <?= htmlspecialchars($batch['returned_by_name']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div style="font-size: 5pt;">Received By</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="form-footer">
        <?= $systemName ?> by Ranoa Digital Solutions
    </div>
</div><!-- End form-container -->

<script>
// Auto-print on load (optional - can be disabled)
window.addEventListener('load', function() {
    // Uncomment to auto-print:
    // setTimeout(() => window.print(), 500);
});
</script>

</body>
</html>
