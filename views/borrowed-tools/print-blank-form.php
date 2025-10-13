<?php
/**
 * ConstructLink™ - Blank Printable Borrowing Form (2x2 Grid)
 * Developed by: Ranoa Digital Solutions
 *
 * Purpose: Pre-printed blank forms with checklist for handwritten use
 * Format: 4 copies per A4 page in 2x2 grid (saves paper)
 * Design: Simple checklist - Power Tools, Hand Tools, Others
 */

// Get system name from settings for rebranding support
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'system_name' LIMIT 1");
$systemName = $stmt->fetchColumn() ?: 'ConstructLink™';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blank Borrowed Tools Form - ConstructLink™</title>
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

        /* 2x2 Grid Container */
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 0;
            width: 100%;
            height: 287mm; /* A4 height minus margins */
        }

        /* Each Quarter Form */
        .form-quarter {
            border: 2px solid #000;
            padding: 3mm;
            overflow: hidden;
            page-break-inside: avoid;
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

        .copy-label {
            font-size: 6pt;
            font-weight: bold;
            color: #666;
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
        }

        /* Equipment Table */
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

        .category-row {
            background: #d0d0d0;
            font-weight: bold;
            font-size: 6pt;
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

        /* Signature Section - Simplified */
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

        .date-field {
            border-bottom: 1px solid #000;
            min-height: 3mm;
            margin-top: 0.5mm;
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
    <h2>📋 Equipment Borrowing Form - 4 Copies Per Page</h2>
    <p><strong>Design:</strong> 2x2 grid with 4 identical copies per A4 sheet (saves paper)</p>
    <p><strong>Features:</strong></p>
    <ul>
        <li>Power Tools and Hand Tools from database</li>
        <li>5 blank rows in Others section</li>
        <li>Qty Out and Qty In columns</li>
        <li>Simplified signatures: Borrowed By, Released By, and Return Date field</li>
    </ul>
    <p><strong>Print this page and you'll get 4 copies ready to use!</strong></p>
</div>

<div class="grid-container">
<?php
// Generate 4 identical forms in 2x2 grid
for ($copy = 1; $copy <= 4; $copy++):
?>
    <div class="form-quarter">
        <!-- Header -->
        <div class="form-header">
            <h1><?= strtoupper($systemName) ?> BORROWER FORM</h1>
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-field">
                    <span class="info-label">Name:</span>
                    <span class="info-value"></span>
                </div>
                <div class="info-field">
                    <span class="info-label">Date Filled:</span>
                    <span class="info-value"></span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-field">
                    <span class="info-label">Reference No.:</span>
                    <span class="info-value"></span>
                </div>
                <div class="info-field">
                    <span class="info-label">Released Date:</span>
                    <span class="info-value"></span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-field">
                    <span class="info-label">Returned Date:</span>
                    <span class="info-value"></span>
                </div>
                <div class="info-field">
                    <span class="info-label">Contact:</span>
                    <span class="info-value"></span>
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
                <!-- POWER TOOLS -->
                <tr class="category-row">
                    <td colspan="4">POWER TOOLS</td>
                </tr>
                <?php
                if (!empty($powerTools)) {
                    foreach ($powerTools as $tool) {
                        echo '<tr>';
                        echo '<td style="text-align: center;"><span class="checkbox"></span></td>';
                        echo '<td>' . htmlspecialchars($tool['display_name']) . '</td>';
                        echo '<td class="qty-col"></td>';
                        echo '<td class="qty-col return-col"></td>';
                        echo '</tr>';
                    }
                } else {
                    $fallbackPowerTools = [
                        'Drill [Cordless, Electric, Hammer, Impact]',
                        'Grinder [Angle, Bench]',
                        'Saw [Circular, Jigsaw, Reciprocating]',
                        'Sander [Belt, Orbital, Palm]'
                    ];
                    foreach ($fallbackPowerTools as $tool) {
                        echo '<tr>';
                        echo '<td style="text-align: center;"><span class="checkbox"></span></td>';
                        echo '<td>' . $tool . '</td>';
                        echo '<td class="qty-col"></td>';
                        echo '<td class="qty-col return-col"></td>';
                        echo '</tr>';
                    }
                }
                ?>

                <!-- HAND TOOLS -->
                <tr class="category-row">
                    <td colspan="4">HAND TOOLS</td>
                </tr>
                <?php
                if (!empty($handTools)) {
                    foreach ($handTools as $tool) {
                        echo '<tr>';
                        echo '<td style="text-align: center;"><span class="checkbox"></span></td>';
                        echo '<td>' . htmlspecialchars($tool['display_name']) . '</td>';
                        echo '<td class="qty-col"></td>';
                        echo '<td class="qty-col return-col"></td>';
                        echo '</tr>';
                    }
                } else {
                    $fallbackHandTools = [
                        'Hammer [Claw, Sledge, Ball Peen]',
                        'Screwdriver [Phillips, Flathead]',
                        'Wrench [Adjustable, Socket, Allen]',
                        'Pliers [Needle-nose, Cutting, Locking]',
                        'Measuring [Tape Measure, Level, Square]',
                        'Cutting [Chisel, File, Hand Saw]'
                    ];
                    foreach ($fallbackHandTools as $tool) {
                        echo '<tr>';
                        echo '<td style="text-align: center;"><span class="checkbox"></span></td>';
                        echo '<td>' . $tool . '</td>';
                        echo '<td class="qty-col"></td>';
                        echo '<td class="qty-col return-col"></td>';
                        echo '</tr>';
                    }
                }
                ?>

                <!-- OTHERS -->
                <tr class="category-row">
                    <td colspan="4">OTHERS</td>
                </tr>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <tr>
                    <td style="text-align: center;"><span class="checkbox"></span></td>
                    <td style="border-bottom: 1px solid #999;"></td>
                    <td class="qty-col"></td>
                    <td class="qty-col return-col"></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="sig-box">
                <div class="sig-label">BORROWER</div>
                <div class="sig-space"></div>
                <div style="font-size: 4pt;">Signature</div>
            </div>
            <div class="sig-box">
                <div class="sig-label">RELEASED BY</div>
                <div class="sig-space"></div>
                <div style="font-size: 4pt;">Signature</div>
            </div>
            <div class="sig-box">
                <div class="sig-label">SUPERVISOR</div>
                <div class="sig-space"></div>
                <div style="font-size: 4pt;">Signature (Optional)</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="form-footer">
            <?= $systemName ?> by Ranoa Digital Solutions
        </div>
    </div>
<?php endfor; ?>
</div>

</body>
</html>
