<?php
/**
 * ConstructLink™ - Blank Printable Borrowing Form
 * Developed by: Ranoa Digital Solutions
 *
 * Purpose: Pre-printed blank form with checklist for handwritten use
 * Format: One full-page form per A4 sheet
 * Design: Simple checklist - Power Tools, Hand Tools, Others
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
            margin: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }

        /* Full Page Form */
        .form-page {
            width: 100%;
            min-height: 260mm;
            border: 3px solid #000;
            padding: 8mm;
            page-break-after: always;
        }

        /* Header */
        .form-header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 4mm;
            margin-bottom: 6mm;
        }

        .form-header h1 {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .copy-label {
            font-size: 12pt;
            font-weight: bold;
            color: #666;
        }

        /* Info Section */
        .info-section {
            margin-bottom: 8mm;
        }

        .info-row {
            display: flex;
            gap: 8mm;
            margin-bottom: 4mm;
        }

        .info-field {
            flex: 1;
            display: flex;
            align-items: center;
        }

        .info-label {
            font-weight: bold;
            margin-right: 3mm;
            white-space: nowrap;
            font-size: 11pt;
        }

        .info-value {
            border-bottom: 1.5px solid #000;
            flex: 1;
            min-height: 8mm;
        }

        /* Equipment Table */
        .equipment-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 8mm;
        }

        .equipment-table th {
            background: #000;
            color: #fff;
            padding: 3mm;
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
            border: 2px solid #000;
        }

        .equipment-table td {
            padding: 3mm 4mm;
            font-size: 11pt;
            border: 1px solid #666;
            vertical-align: middle;
        }

        .category-row {
            background: #d0d0d0;
            font-weight: bold;
            font-size: 12pt;
        }

        .checkbox {
            width: 6mm;
            height: 6mm;
            border: 2px solid #000;
            display: inline-block;
        }

        .qty-col {
            width: 25mm;
            text-align: center;
        }

        .return-col {
            background: #f0f0f0;
        }

        .item-name-col {
            text-align: left;
        }

        .others-row td {
            background: #fff;
            min-height: 10mm;
        }

        .others-row .item-name-col {
            border-bottom: 1.5px solid #999;
        }

        /* Signature Section */
        .signature-section {
            display: flex;
            gap: 4mm;
            margin-top: 10mm;
        }

        .sig-box {
            flex: 1;
            text-align: center;
            border: 2px solid #000;
            padding: 5mm;
            min-height: 35mm;
        }

        .sig-label {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 3mm;
        }

        .sig-space {
            min-height: 20mm;
            border-bottom: 2px solid #000;
            margin-bottom: 2mm;
        }

        .sig-line {
            font-size: 9pt;
            color: #666;
        }

        .date-line {
            margin-top: 3mm;
            font-size: 10pt;
        }

        .date-box {
            border-bottom: 1.5px solid #000;
            display: inline-block;
            width: 35mm;
            min-height: 6mm;
        }

        /* Footer */
        .form-footer {
            text-align: center;
            margin-top: 10mm;
            padding-top: 3mm;
            border-top: 1px solid #ccc;
            font-size: 9pt;
            color: #666;
        }

        /* Print Styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .form-page {
                page-break-after: always;
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

        .instructions {
            background: #fffacd;
            border: 3px solid #000;
            padding: 15px;
            margin: 15px;
            font-size: 11pt;
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
    <button onclick="window.print()" style="padding: 12px 24px; font-size: 14pt; cursor: pointer; background: #007bff; color: #fff; border: none; border-radius: 4px; font-weight: bold;">
        🖨️ Print Form
    </button>
    <button onclick="window.close()" style="padding: 12px 24px; font-size: 14pt; cursor: pointer; background: #6c757d; color: #fff; border: none; border-radius: 4px; margin-left: 5px;">
        ✕ Close
    </button>
</div>

<!-- Instructions -->
<div class="instructions no-print">
    <h2>📋 Equipment Borrowing Form - Blank Template</h2>
    <p><strong>Design:</strong> Full-page form with Power Tools and Hand Tools checklist + itemizable Others section</p>
    <p><strong>How to Use:</strong></p>
    <ol>
        <li><strong>Print:</strong> Print this page (one form per A4 sheet)</li>
        <li><strong>Fill Info:</strong> Write borrower name, contact, date out, and return by date</li>
        <li><strong>Check Items:</strong> Tick checkboxes for equipment being borrowed</li>
        <li><strong>Write Quantities:</strong> Write numbers in "Qty Out" column</li>
        <li><strong>Others:</strong> For equipment not in checklist, write item name in Others section</li>
        <li><strong>Sign:</strong> Borrower and staff sign in signature boxes</li>
        <li><strong>Return:</strong> Write returned quantities in "Qty In" column (shaded)</li>
    </ol>
</div>

<div class="form-page">
    <!-- Header -->
    <div class="form-header">
        <h1>CONSTRUCTLINK™ EQUIPMENT BORROWING FORM</h1>
        <div class="copy-label">BORROWER COPY</div>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-row">
            <div class="info-field">
                <span class="info-label">Borrower Name:</span>
                <span class="info-value"></span>
            </div>
            <div class="info-field">
                <span class="info-label">Contact Number:</span>
                <span class="info-value"></span>
            </div>
        </div>
        <div class="info-row">
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

    <!-- Equipment Table -->
    <table class="equipment-table">
        <thead>
            <tr>
                <th style="width: 12mm;">✓</th>
                <th class="item-name-col">EQUIPMENT / TOOL</th>
                <th style="width: 30mm;">Qty Out</th>
                <th style="width: 30mm;">Qty In</th>
            </tr>
        </thead>
        <tbody>
            <!-- POWER TOOLS -->
            <tr class="category-row">
                <td colspan="4">⚡ POWER TOOLS</td>
            </tr>
            <?php
            // Display power tools from database
            if (!empty($powerTools)) {
                foreach ($powerTools as $tool) {
                    echo '<tr>';
                    echo '<td style="text-align: center;"><span class="checkbox"></span></td>';
                    echo '<td class="item-name-col">' . htmlspecialchars($tool['subtype_name']) . '</td>';
                    echo '<td class="qty-col"></td>';
                    echo '<td class="qty-col return-col"></td>';
                    echo '</tr>';
                }
            } else {
                // Fallback to hardcoded items if database is empty
                $fallbackPowerTools = ['Drill', 'Grinder', 'Circular Saw', 'Jigsaw', 'Impact Driver'];
                foreach ($fallbackPowerTools as $tool) {
                    echo '<tr>';
                    echo '<td style="text-align: center;"><span class="checkbox"></span></td>';
                    echo '<td class="item-name-col">' . $tool . '</td>';
                    echo '<td class="qty-col"></td>';
                    echo '<td class="qty-col return-col"></td>';
                    echo '</tr>';
                }
            }
            ?>

            <!-- HAND TOOLS -->
            <tr class="category-row">
                <td colspan="4">🔧 HAND TOOLS</td>
            </tr>
            <?php
            // Display hand tools from database
            if (!empty($handTools)) {
                foreach ($handTools as $tool) {
                    echo '<tr>';
                    echo '<td style="text-align: center;"><span class="checkbox"></span></td>';
                    echo '<td class="item-name-col">' . htmlspecialchars($tool['subtype_name']) . '</td>';
                    echo '<td class="qty-col"></td>';
                    echo '<td class="qty-col return-col"></td>';
                    echo '</tr>';
                }
            } else {
                // Fallback to hardcoded items if database is empty
                $fallbackHandTools = ['Hammer', 'Screwdriver', 'Wrench', 'Pliers', 'Tape Measure', 'Level'];
                foreach ($fallbackHandTools as $tool) {
                    echo '<tr>';
                    echo '<td style="text-align: center;"><span class="checkbox"></span></td>';
                    echo '<td class="item-name-col">' . $tool . '</td>';
                    echo '<td class="qty-col"></td>';
                    echo '<td class="qty-col return-col"></td>';
                    echo '</tr>';
                }
            }
            ?>

            <!-- OTHERS -->
            <tr class="category-row">
                <td colspan="4">📝 OTHERS (Write equipment name below)</td>
            </tr>
            <tr class="others-row">
                <td style="text-align: center;"><span class="checkbox"></span></td>
                <td class="item-name-col"></td>
                <td class="qty-col"></td>
                <td class="qty-col return-col"></td>
            </tr>
            <tr class="others-row">
                <td style="text-align: center;"><span class="checkbox"></span></td>
                <td class="item-name-col"></td>
                <td class="qty-col"></td>
                <td class="qty-col return-col"></td>
            </tr>
            <tr class="others-row">
                <td style="text-align: center;"><span class="checkbox"></span></td>
                <td class="item-name-col"></td>
                <td class="qty-col"></td>
                <td class="qty-col return-col"></td>
            </tr>
            <tr class="others-row">
                <td style="text-align: center;"><span class="checkbox"></span></td>
                <td class="item-name-col"></td>
                <td class="qty-col"></td>
                <td class="qty-col return-col"></td>
            </tr>
            <tr class="others-row">
                <td style="text-align: center;"><span class="checkbox"></span></td>
                <td class="item-name-col"></td>
                <td class="qty-col"></td>
                <td class="qty-col return-col"></td>
            </tr>
        </tbody>
    </table>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="sig-box">
            <div class="sig-label">BORROWED BY</div>
            <div class="sig-space"></div>
            <div class="sig-line">Signature over Printed Name</div>
            <div class="date-line">Date: <span class="date-box"></span></div>
        </div>
        <div class="sig-box">
            <div class="sig-label">RELEASED BY</div>
            <div class="sig-space"></div>
            <div class="sig-line">Signature over Printed Name</div>
            <div class="date-line">Date: <span class="date-box"></span></div>
        </div>
        <div class="sig-box">
            <div class="sig-label">RETURNED TO</div>
            <div class="sig-space"></div>
            <div class="sig-line">Signature over Printed Name</div>
            <div class="date-line">Date: <span class="date-box"></span></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="form-footer">
        ConstructLink™ Equipment Management System | Developed by Ranoa Digital Solutions
    </div>
</div>

</body>
</html>
