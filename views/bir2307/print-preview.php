<?php
/**
 * BIR Form 2307 Print Preview
 * Certificate of Creditable Tax Withheld at Source
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'BIR Form 2307') ?></title>
    
    <style>
        @page {
            size: 8.5in 11in;
            margin: 0.25in;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { 
                margin: 0; 
                padding: 0;
                font-family: Arial, sans-serif;
                font-size: 10pt;
            }
            .form-container {
                page-break-after: avoid;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .form-container {
            max-width: 8in;
            margin: 0 auto;
            background: white;
            padding: 0.25in;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 10px;
            position: relative;
        }
        
        .form-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .form-subtitle {
            font-size: 10pt;
            margin: 3px 0;
        }
        
        .form-number-box {
            position: absolute;
            right: 0;
            top: 0;
            border: 1px solid #000;
            padding: 5px;
            font-size: 9pt;
        }
        
        .barcode-box {
            position: absolute;
            right: 0;
            top: 40px;
            width: 150px;
            height: 30px;
            border: 1px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Courier New', monospace;
            font-size: 8pt;
        }
        
        .section {
            margin-bottom: 15px;
            border: 1px solid #000;
        }
        
        .section-header {
            background: #e0e0e0;
            padding: 3px 5px;
            font-weight: bold;
            font-size: 9pt;
            border-bottom: 1px solid #000;
        }
        
        .section-content {
            padding: 5px;
        }
        
        .field-row {
            display: flex;
            margin-bottom: 5px;
        }
        
        .field-label {
            min-width: 80px;
            font-size: 9pt;
            padding: 2px;
        }
        
        .field-value {
            flex: 1;
            border-bottom: 1px solid #333;
            padding: 2px;
            font-size: 9pt;
            min-height: 16px;
        }
        
        .tin-box {
            display: inline-flex;
            align-items: center;
            margin-right: 10px;
        }
        
        .tin-digit {
            width: 20px;
            height: 20px;
            border: 1px solid #000;
            text-align: center;
            margin: 0 1px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'Courier New', monospace;
        }
        
        .tin-separator {
            margin: 0 3px;
        }
        
        .checkbox-group {
            display: inline-flex;
            align-items: center;
            margin-right: 15px;
        }
        
        .checkbox {
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin-right: 3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10pt;
        }
        
        .table-income {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        
        .table-income th,
        .table-income td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 8pt;
        }
        
        .table-income th {
            background: #e0e0e0;
            font-weight: bold;
        }
        
        .table-income .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .total-row {
            font-weight: bold;
            background: #f0f0f0;
        }
        
        .signature-section {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 48%;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            margin-top: 30px;
            margin-bottom: 3px;
        }
        
        .signature-label {
            font-size: 8pt;
            text-align: center;
        }
        
        .footer-note {
            margin-top: 15px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ccc;
            font-size: 8pt;
            text-align: justify;
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        @media screen {
            .form-container {
                margin-top: 20px;
                margin-bottom: 50px;
            }
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="no-print print-controls">
        <button onclick="window.print()" style="padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">
            Print Form
        </button>
        <button onclick="window.close()" style="padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer; margin-left: 5px;">
            Close
        </button>
    </div>

    <!-- BIR Form 2307 -->
    <div class="form-container">
        <!-- Header -->
        <div class="form-header">
            <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                <div style="text-align: left; margin-right: 20px;">
                    <div style="font-size: 8pt;">Republic of the Philippines</div>
                    <div style="font-size: 8pt;">Department of Finance</div>
                    <div style="font-size: 8pt; font-weight: bold;">Bureau of Internal Revenue</div>
                </div>
                <div style="margin: 0 20px;">
                    <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==" 
                         alt="BIR Logo" style="width: 50px; height: 50px;">
                </div>
            </div>
            
            <div class="form-title">Certificate of Creditable Tax</div>
            <div class="form-title">Withheld at Source</div>
            
            <div class="form-number-box">
                <div style="font-size: 8pt;">BIR Form No.</div>
                <div style="font-size: 14pt; font-weight: bold;">2307</div>
                <div style="font-size: 7pt;">January 2018 (ENCS)</div>
            </div>
            
            <div class="barcode-box">
                <?= str_replace('-', '', $form['form_number']) ?>
            </div>
        </div>
        
        <div style="font-size: 8pt; margin-bottom: 10px;">
            Fill in all applicable spaces. Mark all appropriate boxes with an "X".
        </div>
        
        <!-- Period Covered -->
        <div style="margin-bottom: 10px;">
            <span style="font-weight: bold; font-size: 9pt;">1 For the Period</span>
            <span style="margin-left: 20px;">From: <u><?= date('m/d/Y', strtotime($form['period_from'])) ?></u></span>
            <span style="margin-left: 20px;">To: <u><?= date('m/d/Y', strtotime($form['period_to'])) ?></u></span>
        </div>
        
        <!-- Part I - Payee Information -->
        <div class="section">
            <div class="section-header">Part I - Payee Information</div>
            <div class="section-content">
                <!-- TIN -->
                <div class="field-row">
                    <div class="field-label">2 Taxpayer Identification Number (TIN)</div>
                    <div>
                        <?php 
                        $tin = str_replace('-', '', $form['payee_tin']);
                        $tinParts = str_split(str_pad($tin, 12, '0', STR_PAD_LEFT), 3);
                        ?>
                        <div class="tin-box">
                            <?php foreach($tinParts as $i => $part): ?>
                                <?php foreach(str_split($part) as $digit): ?>
                                    <div class="tin-digit"><?= $digit ?></div>
                                <?php endforeach; ?>
                                <?php if($i < 3): ?><span class="tin-separator">-</span><?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Name -->
                <div class="field-row">
                    <div class="field-label">3 Payee's Name</div>
                    <div class="field-value">
                        <?php if($vendor['vendor_type'] === 'Sole Proprietor'): ?>
                            <?= htmlspecialchars($form['payee_last_name']) ?>, 
                            <?= htmlspecialchars($form['payee_first_name']) ?> 
                            <?= htmlspecialchars($form['payee_middle_name']) ?>
                            <?php if($form['payee_registered_name']): ?>
                                (<?= htmlspecialchars($form['payee_registered_name']) ?>)
                            <?php endif; ?>
                        <?php else: ?>
                            <?= htmlspecialchars($form['payee_name']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Registered Address -->
                <div class="field-row">
                    <div class="field-label">4 Registered Address</div>
                    <div class="field-value"><?= htmlspecialchars($form['payee_address']) ?></div>
                    <div style="margin-left: 10px;">ZIP Code: <span class="field-value" style="width: 60px;"><?= htmlspecialchars($form['payee_zip_code']) ?></span></div>
                </div>
                
                <!-- Foreign Address -->
                <?php if($form['payee_foreign_address']): ?>
                <div class="field-row">
                    <div class="field-label">5 Foreign Address</div>
                    <div class="field-value"><?= htmlspecialchars($form['payee_foreign_address']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Part II - Payor Information -->
        <div class="section">
            <div class="section-header">Part II - Payor Information</div>
            <div class="section-content">
                <!-- TIN -->
                <div class="field-row">
                    <div class="field-label">6 Taxpayer Identification Number (TIN)</div>
                    <div>
                        <?php 
                        $payorTin = str_replace('-', '', $form['payor_tin']);
                        $payorTinParts = str_split(str_pad($payorTin, 12, '0', STR_PAD_LEFT), 3);
                        ?>
                        <div class="tin-box">
                            <?php foreach($payorTinParts as $i => $part): ?>
                                <?php foreach(str_split($part) as $digit): ?>
                                    <div class="tin-digit"><?= $digit ?></div>
                                <?php endforeach; ?>
                                <?php if($i < 3): ?><span class="tin-separator">-</span><?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Name -->
                <div class="field-row">
                    <div class="field-label">7 Payor's Name</div>
                    <div class="field-value"><?= htmlspecialchars($form['payor_name']) ?></div>
                </div>
                
                <!-- Registered Address -->
                <div class="field-row">
                    <div class="field-label">8 Registered Address</div>
                    <div class="field-value"><?= htmlspecialchars($form['payor_address']) ?></div>
                    <div style="margin-left: 10px;">ZIP Code: <span class="field-value" style="width: 60px;"><?= htmlspecialchars($form['payor_zip_code']) ?></span></div>
                </div>
            </div>
        </div>
        
        <!-- Part III - Details of Monthly Income Payments and Taxes Withheld -->
        <div class="section">
            <div class="section-header">Part III - Details of Monthly Income Payments and Taxes Withheld</div>
            <div class="section-content">
                <table class="table-income">
                    <thead>
                        <tr>
                            <th rowspan="2">Income Payments Subject to Expanded<br>Withholding Tax</th>
                            <th rowspan="2">ATC</th>
                            <th colspan="3">AMOUNT OF INCOME PAYMENTS</th>
                            <th rowspan="2">Tax Withheld for the<br>Quarter</th>
                        </tr>
                        <tr>
                            <th>1st Month of the<br>Quarter</th>
                            <th>2nd Month of the<br>Quarter</th>
                            <th>3rd Month of the<br>Quarter</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalByMonth = [0, 0, 0];
                        $totalTax = 0;
                        
                        // Determine which months are in this quarter
                        $quarterMonths = [
                            '1st' => [1, 2, 3],
                            '2nd' => [4, 5, 6],
                            '3rd' => [7, 8, 9],
                            '4th' => [10, 11, 12]
                        ];
                        $months = $quarterMonths[$form['quarter']] ?? [1, 2, 3];
                        
                        foreach($form['income_payments'] as $payment): 
                            $monthIndex = array_search($payment['month'], $months);
                        ?>
                        <tr>
                            <td style="text-align: left; font-size: 7pt;">
                                <?= htmlspecialchars(substr($payment['description'], 0, 50)) ?>
                            </td>
                            <td><?= htmlspecialchars($payment['atc_code']) ?></td>
                            <td class="amount">
                                <?= $monthIndex === 0 ? number_format($payment['amount'], 2) : '' ?>
                            </td>
                            <td class="amount">
                                <?= $monthIndex === 1 ? number_format($payment['amount'], 2) : '' ?>
                            </td>
                            <td class="amount">
                                <?= $monthIndex === 2 ? number_format($payment['amount'], 2) : '' ?>
                            </td>
                            <td class="amount">
                                <?= number_format($payment['tax_withheld'], 2) ?>
                            </td>
                        </tr>
                        <?php 
                            if($monthIndex !== false) {
                                $totalByMonth[$monthIndex] += $payment['amount'];
                            }
                            $totalTax += $payment['tax_withheld'];
                        endforeach; 
                        ?>
                        
                        <!-- Empty rows for form completeness -->
                        <?php for($i = count($form['income_payments']); $i < 5; $i++): ?>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                        <?php endfor; ?>
                        
                        <!-- Total Row -->
                        <tr class="total-row">
                            <td colspan="2">Total</td>
                            <td class="amount"><?= number_format($totalByMonth[0], 2) ?></td>
                            <td class="amount"><?= number_format($totalByMonth[1], 2) ?></td>
                            <td class="amount"><?= number_format($totalByMonth[2], 2) ?></td>
                            <td class="amount"><?= number_format($totalTax, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Money Payments Subject to Withholding -->
                <div style="margin-top: 10px;">
                    <div style="font-weight: bold; font-size: 9pt; margin-bottom: 5px;">
                        Money Payments Subject to Withholding of Business Tax (Government & Private)
                    </div>
                    <table class="table-income">
                        <tbody>
                            <?php for($i = 0; $i < 3; $i++): ?>
                            <tr>
                                <td style="width: 50%;">&nbsp;</td>
                                <td style="width: 15%;">&nbsp;</td>
                                <td style="width: 15%;">&nbsp;</td>
                                <td style="width: 20%;">&nbsp;</td>
                            </tr>
                            <?php endfor; ?>
                            <tr class="total-row">
                                <td>Total</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Declaration -->
        <div style="margin-top: 15px; font-size: 8pt; text-align: justify;">
            We declare under the penalties of perjury that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and
            correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent to
            the processing of our information as contemplated under the "Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful purposes.
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">
                    Signature over Printed Name of Payor/Payor's Authorized Representative/Tax Agent<br>
                    (Indicate Title/Designation and TIN)
                </div>
                <div style="margin-top: 5px;">
                    <div class="field-row">
                        <div class="field-label" style="font-size: 8pt;">Tax Agent Accreditation No./Attorney's Roll No. (if applicable)</div>
                        <div class="field-value" style="width: 150px;"></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label" style="font-size: 8pt;">Date of Issue</div>
                        <div class="field-value" style="width: 100px;"></div>
                        <div class="field-label" style="font-size: 8pt; margin-left: 10px;">Date of Expiry</div>
                        <div class="field-value" style="width: 100px;"></div>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; padding-top: 20px;">
                <div style="font-weight: bold; font-size: 9pt;">CONFORME:</div>
            </div>
        </div>
        
        <!-- Payee Signature -->
        <div style="margin-top: 30px;">
            <div class="signature-line" style="width: 350px; margin: 0 auto;"></div>
            <div class="signature-label">
                Signature over Printed Name of Payee/Payee's Authorized Representative/Tax Agent<br>
                (Indicate Title/Designation and TIN)
            </div>
            <div style="margin-top: 5px; text-align: center;">
                <div class="field-row" style="justify-content: center;">
                    <div class="field-label" style="font-size: 8pt;">Tax Agent Accreditation No./Attorney's Roll No. (if applicable)</div>
                    <div class="field-value" style="width: 150px;"></div>
                </div>
                <div class="field-row" style="justify-content: center;">
                    <div class="field-label" style="font-size: 8pt;">Date of Issue</div>
                    <div class="field-value" style="width: 100px;"></div>
                    <div class="field-label" style="font-size: 8pt; margin-left: 10px;">Date of Expiry</div>
                    <div class="field-value" style="width: 100px;"></div>
                </div>
            </div>
        </div>
        
        <!-- Footer Note -->
        <div class="footer-note">
            <strong>NOTE:</strong> The BIR Data Privacy is in the BIR website (www.bir.gov.ph)
        </div>
    </div>
</body>
</html>