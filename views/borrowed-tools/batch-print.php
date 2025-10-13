<?php
/**
 * ConstructLink™ - Printable Batch Form (4-per-page A4)
 * Developed by: Ranoa Digital Solutions
 *
 * Purpose: Handwritten form with signature collection
 * Format: 4 identical copies per A4 page (saves paper)
 * Usage: Print, borrower signs all 4 copies, distribute accordingly
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Tools Form - <?= htmlspecialchars($batch['batch_reference']) ?></title>
    <style>
        /* A4 Page Setup */
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        /* Each form takes 1/4 of A4 page (portrait) */
        .form-quarter {
            width: 100%;
            height: 69mm; /* A4 height (297mm - 20mm margins) / 4 */
            border: 2px solid #000;
            padding: 3mm;
            margin-bottom: 3mm;
            page-break-inside: avoid;
            position: relative;
        }

        .form-quarter:last-child {
            margin-bottom: 0;
        }

        /* Header */
        .form-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
        }

        .form-header h1 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .form-header .batch-ref {
            font-size: 10pt;
            font-weight: bold;
            background: #000;
            color: #fff;
            padding: 1mm 3mm;
            display: inline-block;
        }

        /* Two-column layout for borrower info and items */
        .form-body {
            display: flex;
            gap: 3mm;
            margin-bottom: 2mm;
        }

        .borrower-section {
            flex: 0 0 45%;
            font-size: 7pt;
        }

        .items-section {
            flex: 1;
            font-size: 7pt;
        }

        .field-group {
            margin-bottom: 1.5mm;
        }

        .field-label {
            font-weight: bold;
            font-size: 6pt;
            text-transform: uppercase;
            color: #333;
        }

        .field-value {
            border-bottom: 1px solid #000;
            min-height: 4mm;
            padding: 0.5mm 1mm;
            font-size: 8pt;
        }

        /* Items Checklist */
        .items-checklist {
            border: 1px solid #000;
            padding: 1mm;
            max-height: 30mm;
            overflow: hidden;
        }

        .items-checklist table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6pt;
        }

        .items-checklist th {
            background: #000;
            color: #fff;
            padding: 0.5mm;
            text-align: left;
            font-size: 6pt;
            font-weight: bold;
        }

        .items-checklist td {
            padding: 0.5mm;
            border-bottom: 1px dotted #999;
        }

        .checkbox {
            width: 3mm;
            height: 3mm;
            border: 1px solid #000;
            display: inline-block;
            margin-right: 1mm;
        }

        /* Signature Section */
        .signature-section {
            display: flex;
            gap: 2mm;
            margin-top: 2mm;
            border-top: 1px solid #000;
            padding-top: 1mm;
        }

        .signature-box {
            flex: 1;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 6mm;
            padding-top: 0.5mm;
            font-size: 6pt;
            font-weight: bold;
        }

        .date-box {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 20mm;
            text-align: center;
        }

        /* Footer */
        .form-footer {
            font-size: 5pt;
            text-align: center;
            color: #666;
            margin-top: 1mm;
            border-top: 1px solid #ccc;
            padding-top: 0.5mm;
        }

        /* Print Styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .form-quarter {
                page-break-inside: avoid;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Print Button (hidden on print) */
        .print-controls {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #fff;
            padding: 10px;
            border: 2px solid #000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        @media print {
            .print-controls {
                display: none;
            }
        }

        .critical-badge {
            background: #ff0000;
            color: #fff;
            padding: 0.5mm 1mm;
            font-size: 6pt;
            font-weight: bold;
            display: inline-block;
            margin-left: 2mm;
        }
    </style>
</head>
<body>

<!-- Print Controls -->
<div class="print-controls no-print">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 14pt; cursor: pointer; background: #007bff; color: #fff; border: none; border-radius: 4px;">
        🖨️ Print Form
    </button>
    <button onclick="window.close()" style="padding: 10px 20px; font-size: 14pt; cursor: pointer; background: #6c757d; color: #fff; border: none; border-radius: 4px; margin-left: 5px;">
        ✕ Close
    </button>
</div>

<?php
// Generate 4 identical forms
for ($copy = 1; $copy <= 4; $copy++):
    $copyLabels = [
        1 => 'BORROWER COPY',
        2 => 'WAREHOUSE COPY',
        3 => 'PROJECT MANAGER COPY',
        4 => 'FINANCE COPY'
    ];
?>

<!-- Form Copy <?= $copy ?> -->
<div class="form-quarter">
    <!-- Header -->
    <div class="form-header">
        <h1>CONSTRUCTLINK™ - EQUIPMENT BORROWING FORM</h1>
        <div class="batch-ref"><?= htmlspecialchars($batch['batch_reference']) ?></div>
        <div style="font-size: 7pt; margin-top: 1mm; font-weight: bold;">
            <?= $copyLabels[$copy] ?>
            <?php if ($batch['is_critical_batch']): ?>
                <span class="critical-badge">⚠ CRITICAL TOOLS</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Body -->
    <div class="form-body">
        <!-- Borrower Information -->
        <div class="borrower-section">
            <div class="field-group">
                <div class="field-label">Borrower Name:</div>
                <div class="field-value"><?= htmlspecialchars($batch['borrower_name']) ?></div>
            </div>

            <div class="field-group">
                <div class="field-label">Contact:</div>
                <div class="field-value"><?= htmlspecialchars($batch['borrower_contact'] ?? '') ?></div>
            </div>

            <div class="field-group">
                <div class="field-label">Date Borrowed:</div>
                <div class="field-value"><?= date('M d, Y', strtotime($batch['created_at'])) ?></div>
            </div>

            <div class="field-group">
                <div class="field-label">Return By:</div>
                <div class="field-value" style="font-weight: bold;">
                    <?= date('M d, Y', strtotime($batch['expected_return'])) ?>
                </div>
            </div>

            <div class="field-group">
                <div class="field-label">Purpose:</div>
                <div class="field-value" style="font-size: 7pt;">
                    <?= htmlspecialchars(substr($batch['purpose'] ?? '', 0, 50)) ?>
                    <?= strlen($batch['purpose'] ?? '') > 50 ? '...' : '' ?>
                </div>
            </div>
        </div>

        <!-- Items Checklist -->
        <div class="items-section">
            <div class="field-label" style="margin-bottom: 1mm;">BORROWED ITEMS:</div>
            <div class="items-checklist">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5mm;">✓</th>
                            <th>Equipment Name</th>
                            <th style="width: 8mm; text-align: center;">Qty Out</th>
                            <th style="width: 8mm; text-align: center;">Qty In</th>
                            <th style="width: 12mm;">Cond.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Show up to 8 items per form
                        $itemsToShow = array_slice($batch['items'], 0, 8);
                        foreach ($itemsToShow as $item):
                            $isCritical = isset($item['acquisition_cost']) && $item['acquisition_cost'] > 50000;
                        ?>
                            <tr>
                                <td><span class="checkbox"></span></td>
                                <td style="font-weight: <?= $isCritical ? 'bold' : 'normal' ?>;">
                                    <?= htmlspecialchars($item['asset_name']) ?>
                                    <?php if ($isCritical): ?>
                                        <span style="color: #f00;">★</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; font-weight: bold;">
                                    <?= $item['quantity'] ?? 1 ?>
                                </td>
                                <td style="background: #f0f0f0;"></td>
                                <td style="font-size: 5pt;">
                                    <?= htmlspecialchars($item['condition_out'] ?? 'Good') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($batch['items']) > 8): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; font-style: italic; color: #666;">
                                    + <?= count($batch['items']) - 8 ?> more items (see digital record)
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($batch['is_critical_batch']): ?>
                <div style="font-size: 6pt; margin-top: 1mm; color: #f00; font-weight: bold;">
                    ★ = High-Value Equipment (&gt;₱50,000) - Extra care required
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div style="font-size: 6pt; font-weight: bold; margin-bottom: 1mm;">BORROWER SIGNATURE</div>
            <div style="font-size: 5pt; color: #666;">I acknowledge receipt and responsibility</div>
            <div class="signature-line">Signature over Printed Name</div>
            <div style="margin-top: 1mm; font-size: 6pt;">
                Date: <span class="date-box"></span>
            </div>
        </div>

        <div class="signature-box">
            <div style="font-size: 6pt; font-weight: bold; margin-bottom: 1mm;">RELEASED BY</div>
            <div style="font-size: 5pt; color: #666;">Warehouseman</div>
            <div class="signature-line">Signature over Printed Name</div>
            <div style="margin-top: 1mm; font-size: 6pt;">
                Date: <span class="date-box"></span>
            </div>
        </div>

        <div class="signature-box">
            <div style="font-size: 6pt; font-weight: bold; margin-bottom: 1mm;">RETURNED TO</div>
            <div style="font-size: 5pt; color: #666;">Received back by</div>
            <div class="signature-line">Signature over Printed Name</div>
            <div style="margin-top: 1mm; font-size: 6pt;">
                Date: <span class="date-box"></span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="form-footer">
        Printed: <?= date('Y-m-d H:i:s') ?> | Copy <?= $copy ?> of 4 |
        Issued By: <?= htmlspecialchars($batch['issued_by_name'] ?? 'System') ?> |
        TOTAL: <?= $batch['total_quantity'] ?> items in <?= $batch['total_items'] ?> line(s) |
        ConstructLink™ by Ranoa Digital Solutions
    </div>
</div>

<?php endfor; ?>

<script>
// Auto-print on load (optional - can be disabled)
window.addEventListener('load', function() {
    // Uncomment to auto-print:
    // setTimeout(() => window.print(), 500);
});
</script>

</body>
</html>
