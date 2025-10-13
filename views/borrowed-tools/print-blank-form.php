<?php
/**
 * ConstructLink™ - Blank Printable Borrowing Form (4-per-page A4)
 * Developed by: Ranoa Digital Solutions
 *
 * Purpose: Pre-printed blank forms with checklist for handwritten use
 * Format: 4 identical blank copies per A4 page (saves paper)
 * Design: Simple checklist format for low-education workers
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
            margin: 5mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.0;
            color: #000;
            background: #fff;
        }

        /* Each form takes EXACTLY 1/4 of A4 page */
        .form-quarter {
            width: 100%;
            height: 71.75mm; /* (297mm - 10mm margins) / 4 = 71.75mm per form */
            border: 2px solid #000;
            padding: 1.5mm;
            margin-bottom: 0;
            page-break-inside: avoid;
            page-break-after: avoid;
            position: relative;
            overflow: hidden;
        }

        /* Header */
        .form-header {
            text-align: center;
            border-bottom: 1.5px solid #000;
            padding-bottom: 0.5mm;
            margin-bottom: 1mm;
        }

        .form-header h1 {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 0.3mm;
            line-height: 1.0;
        }

        .form-header .copy-label {
            font-size: 5.5pt;
            font-weight: bold;
        }

        /* Info Section - Compact */
        .info-section {
            display: flex;
            gap: 1mm;
            margin-bottom: 0.8mm;
            font-size: 5.5pt;
        }

        .info-left, .info-right {
            flex: 1;
        }

        .info-field {
            margin-bottom: 0.5mm;
            line-height: 1.0;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 28%;
        }

        .info-value {
            border-bottom: 0.8px solid #000;
            display: inline-block;
            width: 70%;
            min-height: 2.5mm;
        }

        /* Checklist Section - Compact */
        .checklist-section {
            border: 1px solid #000;
            margin-bottom: 0.8mm;
        }

        .checklist-table {
            width: 100%;
            border-collapse: collapse;
        }

        .checklist-table th {
            background: #000;
            color: #fff;
            padding: 0.2mm;
            font-size: 5pt;
            font-weight: bold;
            text-align: center;
            border: 1px solid #000;
            line-height: 1.0;
        }

        .checklist-table td {
            padding: 0.2mm 0.4mm;
            font-size: 5pt;
            border: 0.5px solid #ccc;
            vertical-align: middle;
            line-height: 1.0;
        }

        .category-header {
            background: #d0d0d0;
            font-weight: bold;
            padding: 0.3mm 0.5mm !important;
            font-size: 5.5pt;
        }

        .checkbox {
            width: 2mm;
            height: 2mm;
            border: 0.8px solid #000;
            display: inline-block;
        }

        .qty-cell {
            width: 7mm;
            text-align: center;
            background: #fff;
        }

        .return-cell {
            background: #f0f0f0;
        }

        /* Others Section - Minimal */
        .others-section {
            font-size: 5pt;
            border: 1px solid #000;
            padding: 0.3mm 0.5mm;
            margin-bottom: 0.8mm;
            min-height: 4mm;
            line-height: 1.1;
        }

        .others-label {
            font-weight: bold;
            font-size: 5.5pt;
        }

        /* Signature Section - Compact */
        .signature-section {
            display: flex;
            gap: 0.5mm;
            font-size: 4.5pt;
        }

        .sig-box {
            flex: 1;
            text-align: center;
            border: 1px solid #000;
            padding: 0.3mm;
        }

        .sig-label {
            font-weight: bold;
            margin-bottom: 0.3mm;
            line-height: 1.0;
        }

        .sig-line {
            border-top: 0.8px solid #000;
            margin-top: 2.5mm;
            padding-top: 0.2mm;
            font-size: 4pt;
        }

        .date-line {
            margin-top: 0.3mm;
            font-size: 4pt;
        }

        .date-box {
            border-bottom: 0.8px solid #000;
            display: inline-block;
            width: 10mm;
        }

        /* Footer - Minimal */
        .form-footer {
            position: absolute;
            bottom: 0.5mm;
            left: 1.5mm;
            right: 1.5mm;
            font-size: 3.5pt;
            text-align: center;
            color: #666;
            border-top: 0.5px solid #ccc;
            padding-top: 0.2mm;
            line-height: 1.0;
        }

        /* Print Styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .form-quarter {
                page-break-inside: avoid;
                page-break-after: avoid;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Print Button */
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

<!-- Instructions -->
<div class="instructions no-print">
    <h2>📋 Equipment Borrowing Form - Simple Checklist</h2>
    <p><strong>Design:</strong> Pre-printed checklist with common equipment. Just tick boxes and write quantities!</p>
    <p><strong>How to Use:</strong></p>
    <ol>
        <li><strong>Print:</strong> Print this page (exactly 4 copies per A4 sheet)</li>
        <li><strong>Fill Name & Date:</strong> Write borrower name and dates at top</li>
        <li><strong>Tick Items:</strong> Check boxes for items being borrowed</li>
        <li><strong>Write Quantities:</strong> Write number in "Qty Out" column</li>
        <li><strong>Sign:</strong> Borrower and warehouseman sign</li>
        <li><strong>Return:</strong> Write returned quantity in "Qty In" column</li>
        <li><strong>Keep Copies:</strong> Distribute 4 copies (Borrower/Warehouse/PM/Finance)</li>
    </ol>
</div>

<?php
// Generate 4 identical blank forms
$copyLabels = [
    1 => 'BORROWER COPY',
    2 => 'WAREHOUSE COPY',
    3 => 'PROJECT MANAGER COPY',
    4 => 'FINANCE COPY'
];

for ($copy = 1; $copy <= 4; $copy++):
?>

<!-- Blank Form Copy <?= $copy ?> -->
<div class="form-quarter">
    <!-- Header -->
    <div class="form-header">
        <h1>CONSTRUCTLINK™ EQUIPMENT FORM</h1>
        <div class="copy-label"><?= $copyLabels[$copy] ?></div>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-left">
            <div class="info-field">
                <span class="info-label">Name:</span>
                <span class="info-value"></span>
            </div>
            <div class="info-field">
                <span class="info-label">Contact:</span>
                <span class="info-value"></span>
            </div>
        </div>
        <div class="info-right">
            <div class="info-field">
                <span class="info-label">Date Out:</span>
                <span class="info-value"></span>
            </div>
            <div class="info-field">
                <span class="info-label">Return By:</span>
                <span class="info-value"></span>
            </div>
        </div>
    </div>

    <!-- Equipment Checklist - LIMITED ITEMS -->
    <div class="checklist-section">
        <table class="checklist-table">
            <thead>
                <tr>
                    <th style="width: 3mm;">✓</th>
                    <th style="text-align: left;">EQUIPMENT / TOOL</th>
                    <th style="width: 7mm;">Qty<br>Out</th>
                    <th style="width: 7mm;">Qty<br>In</th>
                </tr>
            </thead>
            <tbody>
                <!-- POWER TOOLS - Limited to 2 items -->
                <tr>
                    <td colspan="4" class="category-header">⚡ POWER TOOLS</td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Drill / Bor</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Grinder / Pang-giling</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>

                <!-- HAND TOOLS - Limited to 3 items -->
                <tr>
                    <td colspan="4" class="category-header">🔧 HAND TOOLS</td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Hammer / Martilyo</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Wrench / Liyabe</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Pliers / Plais</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>

                <!-- MEASURING - 1 item -->
                <tr>
                    <td colspan="4" class="category-header">📏 MEASURING</td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Tape Measure / Panukat</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>

                <!-- SAFETY - 2 items -->
                <tr>
                    <td colspan="4" class="category-header">🛡️ SAFETY / PPE</td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Hard Hat / Helmet</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Safety Gloves / Guwantes</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>

                <!-- HEAVY EQUIPMENT - 1 item -->
                <tr>
                    <td colspan="4" class="category-header">🚜 HEAVY EQUIPMENT</td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Generator / Planta</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Others Section - With proper spacing -->
    <div class="others-section">
        <div class="others-label">OTHERS (Specify equipment not listed above):</div>
        <div style="margin-top: 0.3mm;">___________________________________________________________</div>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="sig-box">
            <div class="sig-label">BORROWED BY</div>
            <div class="sig-line">Sign</div>
            <div class="date-line">Date: <span class="date-box"></span></div>
        </div>
        <div class="sig-box">
            <div class="sig-label">RELEASED BY</div>
            <div class="sig-line">Sign</div>
            <div class="date-line">Date: <span class="date-box"></span></div>
        </div>
        <div class="sig-box">
            <div class="sig-label">RETURNED TO</div>
            <div class="sig-line">Sign</div>
            <div class="date-line">Date: <span class="date-box"></span></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="form-footer">
        Copy <?= $copy ?>/4 | ConstructLink™ by Ranoa Digital Solutions
    </div>
</div>

<?php endfor; ?>

</body>
</html>
