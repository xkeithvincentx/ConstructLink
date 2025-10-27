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
    <?php AssetHelper::loadModuleCSS('borrowed-tools-print-blank'); ?>
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
            <h1><?= strtoupper($systemName) ?> BORROWING FORM</h1>
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
                    <span class="info-label">Contact:</span>
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
                    <span class="info-label">Reference No.:</span>
                    <span class="info-value"></span>
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
                <!-- POWER TOOLS -->
                <tr class="category-row">
                    <td colspan="4">POWER TOOLS</td>
                </tr>
                <?php
                if (!empty($powerTools)) {
                    foreach ($powerTools as $tool) {
                        echo '<tr>';
                        echo '<td class="text-center"><span class="checkbox"></span></td>';
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
                        echo '<td class="text-center"><span class="checkbox"></span></td>';
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
                        echo '<td class="text-center"><span class="checkbox"></span></td>';
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
                        echo '<td class="text-center"><span class="checkbox"></span></td>';
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
                    <td class="text-center"><span class="checkbox"></span></td>
                    <td class="border-bottom-dashed"></td>
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
                <div class="sig-text-small">Signature</div>
            </div>
            <div class="sig-box">
                <div class="sig-label">OIC</div>
                <div class="sig-space"></div>
                <div class="sig-text-small">Signature (Optional)</div>
            </div>
            <div class="sig-box">
                <div class="sig-label">RELEASED BY</div>
                <div class="sig-space"></div>
                <div class="sig-text-small">Signature</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="form-footer">
            <?= $systemName ?> by Ranoa Digital Solutions
        </div>
    </div>
<?php endfor; ?>
</div>

<?php AssetHelper::loadModuleJS('print-controls'); ?>
</body>
</html>
