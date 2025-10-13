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
            margin: 8mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.1;
            color: #000;
            background: #fff;
        }

        /* Each form takes 1/4 of A4 page (portrait) */
        .form-quarter {
            width: 100%;
            height: 70mm; /* A4 height (297mm - 16mm margins) / 4 */
            border: 2px solid #000;
            padding: 2mm;
            margin-bottom: 2mm;
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
            padding-bottom: 1mm;
            margin-bottom: 1.5mm;
        }

        .form-header h1 {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 0.5mm;
        }

        .form-header .copy-label {
            font-size: 6pt;
            font-weight: bold;
        }

        /* Info Section */
        .info-section {
            display: flex;
            gap: 2mm;
            margin-bottom: 1.5mm;
            font-size: 6pt;
        }

        .info-left {
            flex: 1;
        }

        .info-right {
            flex: 1;
        }

        .info-field {
            margin-bottom: 1mm;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 35%;
        }

        .info-value {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 63%;
            min-height: 3mm;
        }

        /* Checklist Section */
        .checklist-section {
            border: 1px solid #000;
            margin-bottom: 1.5mm;
        }

        .checklist-table {
            width: 100%;
            border-collapse: collapse;
        }

        .checklist-table th {
            background: #000;
            color: #fff;
            padding: 0.3mm;
            font-size: 5pt;
            font-weight: bold;
            text-align: center;
            border: 1px solid #000;
        }

        .checklist-table td {
            padding: 0.2mm 0.5mm;
            font-size: 5.5pt;
            border: 1px solid #ccc;
            vertical-align: middle;
        }

        .category-header {
            background: #e0e0e0;
            font-weight: bold;
            padding: 0.3mm 1mm !important;
            font-size: 6pt;
        }

        .checkbox {
            width: 2.5mm;
            height: 2.5mm;
            border: 1px solid #000;
            display: inline-block;
        }

        .qty-cell {
            width: 8mm;
            text-align: center;
            background: #fff;
        }

        .return-cell {
            background: #f5f5f5;
        }

        /* Others Section */
        .others-section {
            font-size: 5.5pt;
            border: 1px solid #000;
            padding: 0.5mm;
            margin-bottom: 1.5mm;
            min-height: 6mm;
        }

        .others-label {
            font-weight: bold;
            font-size: 6pt;
        }

        /* Signature Section */
        .signature-section {
            display: flex;
            gap: 1mm;
            font-size: 5pt;
        }

        .sig-box {
            flex: 1;
            text-align: center;
            border: 1px solid #000;
            padding: 0.5mm;
        }

        .sig-label {
            font-weight: bold;
            margin-bottom: 0.5mm;
        }

        .sig-line {
            border-top: 1px solid #000;
            margin-top: 4mm;
            padding-top: 0.3mm;
        }

        .date-line {
            margin-top: 0.5mm;
        }

        .date-box {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 12mm;
        }

        /* Footer */
        .form-footer {
            position: absolute;
            bottom: 1mm;
            left: 2mm;
            right: 2mm;
            font-size: 4pt;
            text-align: center;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 0.3mm;
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
        <li><strong>Print:</strong> Print this page (4 copies per sheet)</li>
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
        <h1>CONSTRUCTLINK™ EQUIPMENT BORROWING FORM</h1>
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

    <!-- Equipment Checklist -->
    <div class="checklist-section">
        <table class="checklist-table">
            <thead>
                <tr>
                    <th style="width: 4mm;">✓</th>
                    <th style="text-align: left;">EQUIPMENT / TOOL</th>
                    <th style="width: 8mm;">Qty<br>Out</th>
                    <th style="width: 8mm;">Qty<br>In</th>
                </tr>
            </thead>
            <tbody>
                <!-- POWER TOOLS -->
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
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Circular Saw / Lagari</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>

                <!-- HAND TOOLS -->
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
                    <td>Screwdriver / Destornilador</td>
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

                <!-- MEASURING -->
                <tr>
                    <td colspan="4" class="category-header">📏 MEASURING</td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Tape Measure / Panukat</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Level / Lebel</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>

                <!-- PAINTING -->
                <tr>
                    <td colspan="4" class="category-header">🎨 PAINTING</td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Paint Brush / Brocha</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Roller / Rodilyo</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>

                <!-- SAFETY -->
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

                <!-- HEAVY EQUIPMENT -->
                <tr>
                    <td colspan="4" class="category-header">🚜 HEAVY EQUIPMENT</td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Generator / Planta</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>
                <tr>
                    <td><span class="checkbox"></span></td>
                    <td>Welding Machine / Welding</td>
                    <td class="qty-cell"></td>
                    <td class="qty-cell return-cell"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Others Section -->
    <div class="others-section">
        <div class="others-label">OTHERS (Not in checklist - write here):</div>
        <div style="line-height: 1.3;">
            _____________________________________________
        </div>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="sig-box">
            <div class="sig-label">BORROWED BY</div>
            <div class="sig-line">Signature</div>
            <div class="date-line">Date: <span class="date-box"></span></div>
        </div>
        <div class="sig-box">
            <div class="sig-label">RELEASED BY</div>
            <div class="sig-line">Signature</div>
            <div class="date-line">Date: <span class="date-box"></span></div>
        </div>
        <div class="sig-box">
            <div class="sig-label">RETURNED TO</div>
            <div class="sig-line">Signature</div>
            <div class="date-line">Date: <span class="date-box"></span></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="form-footer">
        Copy <?= $copy ?>/4 | ✓=Check if borrowed | Qty Out=Number taken | Qty In=Number returned | ConstructLink™ by Ranoa Digital Solutions
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
