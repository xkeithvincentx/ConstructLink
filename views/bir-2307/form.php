<?php
/**
 * BIR Form 2307 - Certificate of Creditable Tax Withheld at Source
 * Exact replica of official BIR Form 2307
 */

// Start output buffering for content
ob_start();
?>

<style>
.bir-form {
    background: white;
    font-family: Arial, sans-serif;
    font-size: 9px;
    line-height: 1.2;
    width: 8.5in;
    margin: 0 auto;
    padding: 0;
    border: 2px solid #000;
}

.bir-header {
    display: flex;
    align-items: center;
    padding: 10px;
    border-bottom: 2px solid #000;
}

.bir-header-left {
    width: 100px;
    font-size: 8px;
    text-align: left;
}

.bir-header-center {
    flex: 1;
    text-align: center;
}

.bir-header-right {
    width: 150px;
    text-align: center;
    border: 1px solid #000;
    padding: 5px;
}

.bir-logo {
    width: 50px;
    height: 50px;
    background: #ccc;
    border: 1px solid #000;
    margin: 0 auto 5px;
}

.form-title {
    font-size: 16px;
    font-weight: bold;
    margin: 5px 0;
}

.form-subtitle {
    font-size: 14px;
    font-weight: bold;
}

.form-number {
    font-size: 24px;
    font-weight: bold;
    margin: 5px 0;
}

.form-version {
    font-size: 7px;
}

.barcode-area {
    height: 30px;
    background: repeating-linear-gradient(
        90deg,
        #000,
        #000 1px,
        #fff 1px,
        #fff 3px
    );
    margin-bottom: 5px;
}

.bir-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8px;
}

.bir-table td, .bir-table th {
    border: 1px solid #000;
    padding: 2px 4px;
    vertical-align: top;
}

.form-row {
    width: 100%;
    border-collapse: collapse;
}

.form-row td {
    border: 1px solid #000;
    padding: 3px;
    vertical-align: top;
}

.input-box {
    display: inline-block;
    width: 12px;
    height: 12px;
    border: 1px solid #000;
    text-align: center;
    margin-right: 1px;
    font-size: 8px;
    line-height: 12px;
}

.section-header {
    background-color: #d3d3d3;
    font-weight: bold;
    text-align: center;
    padding: 4px;
}

.small-text {
    font-size: 7px;
    font-style: italic;
}

.signature-section {
    border: 1px solid #000;
    padding: 8px;
    margin-top: 2px;
}

@media print {
    body { margin: 0; }
    .no-print { display: none; }
    .bir-form { 
        border: 2px solid #000;
        width: 100%;
        max-width: none;
    }
}
</style>

<div class="no-print mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <a href="?route=procurement-orders/view&id=<?= $bir2307Data['procurement_order']['id'] ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to PO
            </a>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print Form
            </button>
            <button onclick="generatePDF()" class="btn btn-success">
                <i class="bi bi-file-pdf"></i> Save as PDF
            </button>
        </div>
    </div>
</div>

<div class="bir-form">
    <!-- Header Section -->
    <div class="bir-header">
        <div class="bir-header-left">
            <div style="font-weight: bold;">For BIR</div>
            <div>Use Only</div>
            <div>Item:</div>
        </div>
        
        <div class="bir-header-center">
            <div class="bir-logo"></div>
            <div style="font-weight: bold; font-size: 11px;">Republic of the Philippines</div>
            <div style="font-weight: bold; font-size: 10px;">Department of Finance</div>
            <div style="font-weight: bold; font-size: 10px;">Bureau of Internal Revenue</div>
            <div class="form-title">Certificate of Creditable Tax</div>
            <div class="form-subtitle">Withheld at Source</div>
        </div>
        
        <div class="bir-header-right">
            <div class="barcode-area"></div>
            <div style="font-size: 7px;">2307 0114ENCS</div>
        </div>
    </div>

    <!-- BIR Form Number and Date -->
    <table class="form-row">
        <tr>
            <td style="width: 80px; font-weight: bold; background-color: #d3d3d3;">BIR Form No.</td>
            <td style="width: 100px; text-align: center; font-size: 18px; font-weight: bold;">2307</td>
            <td style="font-size: 7px; vertical-align: bottom;">January 2018 (ENCS)</td>
        </tr>
    </table>

    <!-- Instructions -->
    <table class="form-row">
        <tr>
            <td style="font-size: 7px; font-style: italic;">
                Fill in all applicable spaces. Mark all appropriate boxes with an "X".
            </td>
        </tr>
    </table>

    <!-- Row 1: Period -->
    <table class="form-row">
        <tr>
            <td style="width: 20px; font-weight: bold; background-color: #d3d3d3;">1</td>
            <td style="width: 100px;">For the Period</td>
            <td style="width: 50px;">From</td>
            <td style="width: 120px;">
                <?php
                $fromDate = $bir2307Data['period_from'];
                $fromFormatted = str_pad(date('m', strtotime($fromDate)), 2, '0', STR_PAD_LEFT) . 
                                str_pad(date('d', strtotime($fromDate)), 2, '0', STR_PAD_LEFT) . 
                                date('Y', strtotime($fromDate));
                for ($i = 0; $i < strlen($fromFormatted); $i++) {
                    echo '<span class="input-box">' . $fromFormatted[$i] . '</span>';
                }
                ?>
                <span style="font-size: 6px; margin-left: 5px;">(MM/DD/YYYY)</span>
            </td>
            <td style="width: 30px;">To</td>
            <td style="width: 120px;">
                <?php
                $toDate = $bir2307Data['period_to'];
                $toFormatted = str_pad(date('m', strtotime($toDate)), 2, '0', STR_PAD_LEFT) . 
                              str_pad(date('d', strtotime($toDate)), 2, '0', STR_PAD_LEFT) . 
                              date('Y', strtotime($toDate));
                for ($i = 0; $i < strlen($toFormatted); $i++) {
                    echo '<span class="input-box">' . $toFormatted[$i] . '</span>';
                }
                ?>
                <span style="font-size: 6px; margin-left: 5px;">(MM/DD/YYYY)</span>
            </td>
        </tr>
    </table>

    <!-- Part I - Payee Information Header -->
    <table class="form-row">
        <tr>
            <td class="section-header">Part I – Payee Information</td>
        </tr>
    </table>

    <!-- Row 2: Payee TIN -->
    <table class="form-row">
        <tr>
            <td style="width: 20px; font-weight: bold; background-color: #d3d3d3;">2</td>
            <td style="width: 200px;">Taxpayer Identification Number (TIN)</td>
            <td>
                <?php
                $tin = str_replace('-', '', $bir2307Data['payee']['tin']);
                $tinPadded = str_pad($tin, 12, ' ', STR_PAD_RIGHT);
                // Format as XXX-XXX-XXX-XXX
                for ($i = 0; $i < 3; $i++) {
                    echo '<span class="input-box">' . (isset($tinPadded[$i]) ? $tinPadded[$i] : '') . '</span>';
                }
                echo '<span style="margin: 0 5px;">-</span>';
                for ($i = 3; $i < 6; $i++) {
                    echo '<span class="input-box">' . (isset($tinPadded[$i]) ? $tinPadded[$i] : '') . '</span>';
                }
                echo '<span style="margin: 0 5px;">-</span>';
                for ($i = 6; $i < 9; $i++) {
                    echo '<span class="input-box">' . (isset($tinPadded[$i]) ? $tinPadded[$i] : '') . '</span>';
                }
                echo '<span style="margin: 0 5px;">-</span>';
                for ($i = 9; $i < 12; $i++) {
                    echo '<span class="input-box">' . (isset($tinPadded[$i]) ? $tinPadded[$i] : '') . '</span>';
                }
                ?>
            </td>
        </tr>
    </table>

    <!-- Row 3: Payee Name -->
    <table class="form-row">
        <tr>
            <td style="width: 20px; font-weight: bold; background-color: #d3d3d3;">3</td>
            <td style="width: 200px;">Payee's Name <span class="small-text">(Last Name, First Name, Middle Name for Individual OR Registered Name for Non-Individual)</span></td>
            <td style="padding: 8px;">
                <strong><?= htmlspecialchars($bir2307Data['payee']['name']) ?></strong>
            </td>
        </tr>
    </table>

    <!-- Row 4: Registered Address -->
    <table class="form-row">
        <tr>
            <td style="width: 20px; font-weight: bold; background-color: #d3d3d3;">4</td>
            <td style="width: 150px;">Registered Address</td>
            <td style="padding: 8px;">
                <?= htmlspecialchars($bir2307Data['payee']['address']) ?>
            </td>
            <td style="width: 80px; font-weight: bold; background-color: #d3d3d3;">4A ZIP Code</td>
            <td style="width: 80px;">
                <?php
                $zipCode = str_pad($bir2307Data['payee']['zip_code'], 4, ' ', STR_PAD_RIGHT);
                for ($i = 0; $i < 4; $i++) {
                    echo '<span class="input-box">' . (isset($zipCode[$i]) ? $zipCode[$i] : '') . '</span>';
                }
                ?>
            </td>
        </tr>
    </table>

    <!-- Row 5: Foreign Address -->
    <table class="form-row">
        <tr>
            <td style="width: 20px; font-weight: bold; background-color: #d3d3d3;">5</td>
            <td style="width: 200px;">Foreign Address, <span class="small-text">if applicable</span></td>
            <td style="padding: 8px; height: 30px;">
                <!-- Empty for foreign address -->
            </td>
        </tr>
    </table>

    <!-- Part II - Payor Information Header -->
    <table class="form-row">
        <tr>
            <td class="section-header">Part II – Payor Information</td>
        </tr>
    </table>

    <!-- Row 6: Payor TIN -->
    <table class="form-row">
        <tr>
            <td style="width: 20px; font-weight: bold; background-color: #d3d3d3;">6</td>
            <td style="width: 200px;">Taxpayer Identification Number (TIN)</td>
            <td>
                <?php
                $payorTin = str_replace('-', '', $bir2307Data['payor']['tin']);
                $payorTinPadded = str_pad($payorTin, 12, ' ', STR_PAD_RIGHT);
                // Format as XXX-XXX-XXX-XXX
                for ($i = 0; $i < 3; $i++) {
                    echo '<span class="input-box">' . (isset($payorTinPadded[$i]) ? $payorTinPadded[$i] : '') . '</span>';
                }
                echo '<span style="margin: 0 5px;">-</span>';
                for ($i = 3; $i < 6; $i++) {
                    echo '<span class="input-box">' . (isset($payorTinPadded[$i]) ? $payorTinPadded[$i] : '') . '</span>';
                }
                echo '<span style="margin: 0 5px;">-</span>';
                for ($i = 6; $i < 9; $i++) {
                    echo '<span class="input-box">' . (isset($payorTinPadded[$i]) ? $payorTinPadded[$i] : '') . '</span>';
                }
                echo '<span style="margin: 0 5px;">-</span>';
                for ($i = 9; $i < 12; $i++) {
                    echo '<span class="input-box">' . (isset($payorTinPadded[$i]) ? $payorTinPadded[$i] : '') . '</span>';
                }
                ?>
            </td>
        </tr>
    </table>

    <!-- Row 7: Payor Name -->
    <table class="form-row">
        <tr>
            <td style="width: 20px; font-weight: bold; background-color: #d3d3d3;">7</td>
            <td style="width: 200px;">Payor's Name <span class="small-text">(Last Name, First Name, Middle Name for Individual OR Registered Name for Non-Individual)</span></td>
            <td style="padding: 8px;">
                <strong><?= htmlspecialchars($bir2307Data['payor']['name']) ?></strong>
            </td>
        </tr>
    </table>

    <!-- Row 8: Payor Address -->
    <table class="form-row">
        <tr>
            <td style="width: 20px; font-weight: bold; background-color: #d3d3d3;">8</td>
            <td style="width: 150px;">Registered Address</td>
            <td style="padding: 8px;">
                <?= htmlspecialchars($bir2307Data['payor']['address']) ?>
            </td>
            <td style="width: 80px; font-weight: bold; background-color: #d3d3d3;">8A ZIP Code</td>
            <td style="width: 80px;">
                <?php
                $payorZipCode = str_pad($bir2307Data['payor']['zip_code'], 4, ' ', STR_PAD_RIGHT);
                for ($i = 0; $i < 4; $i++) {
                    echo '<span class="input-box">' . (isset($payorZipCode[$i]) ? $payorZipCode[$i] : '') . '</span>';
                }
                ?>
            </td>
        </tr>
    </table>

    <!-- Part III Header -->
    <table class="form-row">
        <tr>
            <td class="section-header">Part III – Details of Monthly Income Payments and Taxes Withheld</td>
        </tr>
    </table>

    <!-- Income Payment Table -->
    <table class="bir-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 150px; text-align: center; vertical-align: middle;">Income Payments Subject to Expanded Withholding Tax</th>
                <th rowspan="2" style="width: 40px; text-align: center; vertical-align: middle;">ATC</th>
                <th colspan="3" style="text-align: center; background-color: #d3d3d3;">AMOUNT OF INCOME PAYMENTS</th>
                <th rowspan="2" style="width: 80px; text-align: center; vertical-align: middle;">Total</th>
                <th rowspan="2" style="width: 80px; text-align: center; vertical-align: middle;">Tax Withheld for the Quarter</th>
            </tr>
            <tr>
                <th style="width: 60px; text-align: center;">1st Month of the Quarter</th>
                <th style="width: 60px; text-align: center;">2nd Month of the Quarter</th>
                <th style="width: 60px; text-align: center;">3rd Month of the Quarter</th>
            </tr>
        </thead>
        <tbody>
            <!-- Sample data row -->
            <tr>
                <td style="padding: 4px;">Payment to suppliers of services</td>
                <td style="text-align: center;">WC157</td>
                <td style="text-align: right; padding: 4px;">
                    <?php
                    $currentMonth = date('n');
                    $quarterMonth = ($currentMonth - 1) % 3 + 1;
                    echo $quarterMonth == 1 ? number_format($bir2307Data['total_amount'], 2) : '';
                    ?>
                </td>
                <td style="text-align: right; padding: 4px;">
                    <?= $quarterMonth == 2 ? number_format($bir2307Data['total_amount'], 2) : '' ?>
                </td>
                <td style="text-align: right; padding: 4px;">
                    <?= $quarterMonth == 3 ? number_format($bir2307Data['total_amount'], 2) : '' ?>
                </td>
                <td style="text-align: right; padding: 4px; font-weight: bold;">
                    <?= number_format($bir2307Data['total_amount'], 2) ?>
                </td>
                <td style="text-align: right; padding: 4px; font-weight: bold;">
                    <?= number_format($bir2307Data['total_tax_withheld'], 2) ?>
                </td>
            </tr>
            <!-- Empty rows -->
            <?php for ($i = 0; $i < 6; $i++): ?>
            <tr>
                <td style="height: 20px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            <?php endfor; ?>
            <!-- Total row -->
            <tr style="background-color: #f0f0f0;">
                <td style="font-weight: bold;">Total</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td style="text-align: right; font-weight: bold; padding: 4px;">
                    <?= number_format($bir2307Data['total_amount'], 2) ?>
                </td>
                <td style="text-align: right; font-weight: bold; padding: 4px;">
                    <?= number_format($bir2307Data['total_tax_withheld'], 2) ?>
                </td>
            </tr>
            <!-- Money Payments Subject to Withholding -->
            <tr>
                <td style="font-weight: bold; background-color: #f0f0f0;">Money Payments Subject to Withholding of Business Tax (Government & Private)</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            <!-- Empty rows for business tax -->
            <?php for ($i = 0; $i < 6; $i++): ?>
            <tr>
                <td style="height: 20px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            <?php endfor; ?>
            <!-- Final total -->
            <tr style="background-color: #f0f0f0;">
                <td style="font-weight: bold;">Total</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        </tbody>
    </table>

    <!-- Declaration -->
    <table class="form-row">
        <tr>
            <td style="font-size: 7px; text-align: justify; padding: 8px;">
                We declare under the penalties of perjury that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and 
                correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent to 
                the processing of our information as contemplated under the "Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful purposes.
            </td>
        </tr>
    </table>

    <!-- Signature Sections -->
    <table class="bir-table">
        <tr>
            <td colspan="3" style="text-align: center; font-weight: bold; padding: 8px;">
                Signature over Printed Name of Payor/Payor's Authorized Representative/Tax Agent<br>
                <span class="small-text">(Indicate Title/Designation and TIN)</span>
            </td>
        </tr>
        <tr>
            <td style="width: 150px;">Tax Agent Accreditation No./<br>Attorney's Roll No. <span class="small-text">(if applicable)</span></td>
            <td style="width: 150px;">Date of Issue<br><span class="small-text">(MM/DD/YYYY)</span>
                <?php for ($i = 0; $i < 8; $i++): ?>
                    <span class="input-box">&nbsp;</span>
                <?php endfor; ?>
            </td>
            <td>Date of Expiry<br><span class="small-text">(MM/DD/YYYY)</span>
                <?php for ($i = 0; $i < 8; $i++): ?>
                    <span class="input-box">&nbsp;</span>
                <?php endfor; ?>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: center; font-weight: bold; padding: 40px 8px 8px;">
                GERALD S. CUTAMORA<br>
                Treasurer / Director<br>
                <?= $bir2307Data['payor']['tin'] ?>
            </td>
        </tr>
    </table>

    <!-- CONFORME Section -->
    <table class="form-row">
        <tr>
            <td style="text-align: center; font-weight: bold; background-color: #f0f0f0; padding: 4px;">
                CONFORME:
            </td>
        </tr>
    </table>

    <!-- Payee Signature Section -->
    <table class="bir-table">
        <tr>
            <td colspan="3" style="text-align: center; font-weight: bold; padding: 8px;">
                Signature over Printed Name of Payee/Payee's Authorized Representative/Tax Agent<br>
                <span class="small-text">(Indicate Title/Designation and TIN)</span>
            </td>
        </tr>
        <tr>
            <td style="width: 150px;">Tax Agent Accreditation No./<br>Attorney's Roll No. <span class="small-text">(if applicable)</span></td>
            <td style="width: 150px;">Date of Issue<br><span class="small-text">(MM/DD/YYYY)</span>
                <?php for ($i = 0; $i < 8; $i++): ?>
                    <span class="input-box">&nbsp;</span>
                <?php endfor; ?>
            </td>
            <td>Date of Expiry<br><span class="small-text">(MM/DD/YYYY)</span>
                <?php for ($i = 0; $i < 8; $i++): ?>
                    <span class="input-box">&nbsp;</span>
                <?php endfor; ?>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="padding: 40px 8px 8px;">
                <!-- Space for payee signature -->
            </td>
        </tr>
    </table>

    <!-- Footer Note -->
    <table class="form-row">
        <tr>
            <td style="font-size: 6px; text-align: left; padding: 4px;">
                *NOTE: The BIR Data Privacy is in the BIR website (www.bir.gov.ph)
            </td>
        </tr>
    </table>
</div>

<script>
function generatePDF() {
    // Simple implementation - opens print dialog
    // In production, you might want to use a PDF library
    window.print();
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>