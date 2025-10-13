<?php
/**
 * ConstructLink™ - Blank Printable Borrowing Form (4-per-page A4)
 * Developed by: Ranoa Digital Solutions
 *
 * Purpose: Pre-printed blank forms for handwritten use in the field
 * Format: 4 identical blank copies per A4 page (saves paper)
 * Usage: Print in bulk, workers fill by hand, then digitize later
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blank Borrowed Tools Form - ConstructLink™</title>
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
            font-size: 9pt;
            font-weight: bold;
            border: 2px solid #000;
            padding: 1mm 3mm;
            display: inline-block;
            min-width: 50mm;
            min-height: 6mm;
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
            display: block;
            margin-bottom: 0.5mm;
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
            height: 30mm;
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
            border: 1px solid #000;
        }

        .items-checklist td {
            padding: 1.5mm 0.5mm;
            border-bottom: 1px solid #ccc;
            border-right: 1px solid #ccc;
            min-height: 5mm;
        }

        .items-checklist td:first-child {
            border-left: 1px solid #ccc;
        }

        .checkbox {
            width: 3mm;
            height: 3mm;
            border: 1px solid #000;
            display: inline-block;
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
            min-height: 4mm;
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

        .instructions {
            background: #fffacd;
            border: 2px solid #000;
            padding: 10px;
            margin: 10px;
            font-size: 10pt;
        }

        @media print {
            .instructions {
                display: none;
            }
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

<!-- Instructions (hidden when printed) -->
<div class="instructions no-print">
    <h2>📋 Blank Borrowing Form - Instructions</h2>
    <p><strong>Purpose:</strong> Print these blank forms in bulk for handwritten use in the field.</p>
    <p><strong>Usage:</strong></p>
    <ol>
        <li>Print this page (4 copies per A4 sheet)</li>
        <li>Workers fill out by hand when borrowing equipment</li>
        <li>Both borrower and warehouseman sign all copies</li>
        <li>Distribute copies: Borrower keeps 1, Warehouse files 1, PM gets 1, Finance gets 1</li>
        <li>Later: Warehouseman enters the data into the digital system</li>
    </ol>
    <p><strong>Note:</strong> Each page contains 4 identical blank forms to save paper.</p>
</div>

<?php
// Generate 4 identical blank forms
for ($copy = 1; $copy <= 4; $copy++):
    $copyLabels = [
        1 => 'BORROWER COPY',
        2 => 'WAREHOUSE COPY',
        3 => 'PROJECT MANAGER COPY',
        4 => 'FINANCE COPY'
    ];
?>

<!-- Blank Form Copy <?= $copy ?> -->
<div class="form-quarter">
    <!-- Header -->
    <div class="form-header">
        <h1>CONSTRUCTLINK™ - EQUIPMENT BORROWING FORM</h1>
        <div style="font-size: 6pt; margin-bottom: 1mm;">BATCH REFERENCE:</div>
        <div class="form-header .batch-ref"></div>
        <div style="font-size: 7pt; margin-top: 1mm; font-weight: bold;">
            <?= $copyLabels[$copy] ?>
        </div>
    </div>

    <!-- Body -->
    <div class="form-body">
        <!-- Borrower Information -->
        <div class="borrower-section">
            <div class="field-group">
                <div class="field-label">Borrower Name:</div>
                <div class="field-value"></div>
            </div>

            <div class="field-group">
                <div class="field-label">Contact Number:</div>
                <div class="field-value"></div>
            </div>

            <div class="field-group">
                <div class="field-label">Date Borrowed:</div>
                <div class="field-value"></div>
            </div>

            <div class="field-group">
                <div class="field-label">Expected Return Date:</div>
                <div class="field-value"></div>
            </div>

            <div class="field-group">
                <div class="field-label">Purpose:</div>
                <div class="field-value" style="min-height: 8mm;"></div>
            </div>
        </div>

        <!-- Items Checklist -->
        <div class="items-section">
            <div class="field-label" style="margin-bottom: 1mm;">BORROWED ITEMS (Fill in by hand):</div>
            <div class="items-checklist">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5mm;">✓</th>
                            <th>Equipment Name / Description</th>
                            <th style="width: 10mm; text-align: center;">Qty Out</th>
                            <th style="width: 10mm; text-align: center;">Qty In</th>
                            <th style="width: 12mm;">Cond.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <tr>
                                <td><span class="checkbox"></span></td>
                                <td></td>
                                <td></td>
                                <td style="background: #f0f0f0;"></td>
                                <td></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div style="font-size: 5pt; margin-top: 1mm; color: #666;">
                <strong>Condition Codes:</strong> G=Good, F=Fair, P=Poor, D=Damaged, L=Lost
            </div>
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
        Blank Form - Copy <?= $copy ?> of 4 |
        Fill in all fields by hand |
        Enter into digital system later |
        ConstructLink™ by Ranoa Digital Solutions
    </div>
</div>

<?php endfor; ?>

<script>
// Optional: Auto-print on load
// window.addEventListener('load', function() {
//     setTimeout(() => window.print(), 500);
// });
</script>

</body>
</html>
