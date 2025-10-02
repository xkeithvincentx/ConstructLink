<?php
/**
 * Procurement Order Print Preview
 * Matches the format from samplepo.png
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Get company info
require_once APP_ROOT . '/config/company.php';
$companyInfo = getCompanyInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Purchase Order') ?></title>
    
    <!-- Bootstrap 5 CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Print-specific styles -->
    <style>
        /* Force portrait orientation with proper A4 sizing */
        @page {
            size: A4 portrait;
            margin: 12mm 10mm; /* Reduced margins for more space */
        }
        
        @media print {
            .no-print { display: none !important; }
            body { 
                margin: 0; 
                padding: 0;
                font-size: 10px;
                line-height: 1.1;
                color: #000;
            }
            .container-fluid { 
                max-width: 190mm; /* 210mm - 20mm margins */
                width: 190mm;
                margin: 0 auto; 
                padding: 0; 
            }
            .page-break { page-break-after: always; }
            
            /* Optimize for A4 print layout */
            .po-header {
                border: 1px solid #000;
                padding: 6px;
                margin-bottom: 6px;
                font-size: 9px;
            }
            
            .company-logo {
                width: 45px;
                height: 45px;
                font-size: 12px;
            }
            
            .po-title {
                font-size: 16px;
                margin: 6px 0;
            }
            
            .po-details-table {
                margin-bottom: 6px;
                font-size: 9px;
            }
            
            .po-details-table td {
                padding: 3px 4px;
            }
            
            .items-table {
                font-size: 8px;
                margin-bottom: 6px;
            }
            
            .items-table th,
            .items-table td {
                padding: 2px 1px;
                font-size: 8px;
                line-height: 1.1;
            }
            
            .total-section {
                width: 180px;
                margin-left: auto;
                margin-bottom: 6px;
            }
            
            .total-table {
                font-size: 9px;
            }
            
            .total-table td {
                padding: 2px 4px;
            }
            
            .terms-section {
                margin-bottom: 8px;
                font-size: 8px;
                line-height: 1.2;
            }
            
            .signature-section {
                margin-bottom: 8px;
            }
            
            .signature-box {
                width: 180px;
            }
            
            .signature-line {
                height: 25px;
            }
            
            .footer-info {
                font-size: 7px;
                margin-top: 6px;
            }
            
            /* Prevent overflow and ensure content fits */
            .items-table .description {
                max-width: none;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            
            /* Compact spacing for print */
            h3, h4, h5, h6 {
                margin-top: 5px;
                margin-bottom: 5px;
            }
            
            p {
                margin-bottom: 3px;
            }
            
            /* Hide Bootstrap margins on print */
            .mb-1, .mb-2, .mb-3 {
                margin-bottom: 0 !important;
            }
            
            /* Handle page breaks for multiple items */
            .items-table {
                page-break-inside: auto;
            }
            
            .items-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            .items-table thead {
                display: table-header-group;
            }
            
            
            /* Ensure footer stays at bottom */
            .footer-info {
                page-break-inside: avoid;
            }
            
            /* Force page break before footer if content is too long */
            .signature-section {
                page-break-inside: avoid;
            }
            
            /* Quotation pages print styles */
            .quotation-page {
                page-break-before: always;
                margin: 0 !important;
                padding: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                overflow: hidden !important;
            }
            
            .quotation-page img {
                max-width: 210mm !important;
                max-height: 297mm !important;
                width: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
                object-fit: contain !important;
            }
            
            .quotation-fallback {
                page-break-before: always;
                width: 210mm !important;
                height: 297mm !important;
                margin: 0 !important;
                padding: 40px !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
            }
        }
        
        .po-header {
            border: 2px solid #000;
            padding: 10px;
            margin-bottom: 12px;
        }
        
        .company-logo {
            width: 50px;
            height: 50px;
            border: 2px solid #2E7D32;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #2E7D32;
            font-size: 14px;
        }
        
        .po-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 12px 0 8px 0;
            text-decoration: underline;
        }
        
        .po-details-table {
            margin-bottom: 20px;
        }
        
        .po-details-table td {
            padding: 8px;
            border: 1px solid #000;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .items-table .description {
            text-align: left;
            max-width: 200px;
        }
        
        .total-section {
            width: 250px;
            margin-left: auto;
            margin-bottom: 15px;
        }
        
        .total-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .total-table td {
            border: 1px solid #000;
            padding: 5px 8px;
        }
        
        .total-table .label {
            text-align: left;
            font-weight: bold;
        }
        
        .total-table .amount {
            text-align: right;
        }
        
        .terms-section {
            margin-bottom: 30px;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            height: 35px;
            margin-bottom: 5px;
        }
        
        .footer-info {
            border-top: 1px solid #000;
            padding-top: 10px;
            font-size: 10px;
            color: #666;
        }
        
        .footer-offices {
            display: flex;
            justify-content: space-between;
        }
        
        .footer-tagline {
            text-align: center;
            font-style: italic;
            color: #2E7D32;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Print Controls (hidden when printing) -->
    <div class="no-print container-fluid py-3 bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Purchase Order Print Preview</h5>
            <div class="d-flex gap-3 align-items-center">
                <?php if (!empty($procurementOrder['quote_file'])): ?>
                <!-- Attachment print control -->
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="includeAttachment" checked>
                    <label class="form-check-label" for="includeAttachment">
                        <small>Include quotation file in print</small>
                    </label>
                </div>
                <?php endif; ?>
                
                <div class="btn-group">
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="?route=procurement-orders/view&id=<?= $procurementOrder['id'] ?>" 
                       class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to View
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Order Document -->
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="po-header">
            <div class="row align-items-center">
                <div class="col-1">
                    <!-- Company Logo -->
                    <div class="company-logo">
                        VC
                    </div>
                </div>
                <div class="col-8">
                    <h4 class="mb-1 text-success fw-bold" style="font-size: 16px; line-height: 1.1;">
                        <?= htmlspecialchars($companyInfo['name']) ?>
                    </h4>
                    <p class="mb-0 text-muted" style="font-size: 11px;"><?= htmlspecialchars($companyInfo['tagline']) ?></p>
                    <small class="text-muted" style="font-size: 10px;">TIN: <?= htmlspecialchars($companyInfo['tin']) ?></small>
                </div>
                <div class="col-3 text-end">
                    <div style="border: 1px solid #000; padding: 6px; font-size: 11px;">
                        <strong>P.O. No:</strong><br>
                        <span style="font-size: 12px;"><?= htmlspecialchars($procurementOrder['po_number']) ?></span><br>
                        <strong>Date:</strong><br>
                        <span style="font-size: 10px;"><?= date('M d, Y', strtotime($procurementOrder['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Order Title -->
        <div class="po-title">PURCHASE ORDER</div>

        <!-- Vendor Details -->
        <table class="po-details-table" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50px; font-weight: bold; font-size: 11px;">To:</td>
                <td style="font-size: 11px;"><?= htmlspecialchars($procurementOrder['vendor_name']) ?></td>
                <td style="width: 60px; font-weight: bold; font-size: 11px;">Attention:</td>
                <td style="font-size: 11px;"><?= htmlspecialchars($procurementOrder['vendor_contact'] ?: 'Sales Department') ?></td>
            </tr>
            <tr>
                <td style="font-weight: bold; font-size: 11px;">Address:</td>
                <td colspan="3" style="font-size: 11px;"><?= htmlspecialchars($procurementOrder['vendor_address'] ?: 'Address not provided') ?></td>
            </tr>
            <?php if (!empty($procurementOrder['vendor_phone'])): ?>
            <tr>
                <td style="font-weight: bold; font-size: 11px;">Tel/Fax #:</td>
                <td style="font-size: 11px;"><?= htmlspecialchars($procurementOrder['vendor_phone']) ?></td>
                <td colspan="2"></td>
            </tr>
            <?php endif; ?>
        </table>

        <!-- Items Section -->
        <?php
        // Calculate intelligent item capacity for A4 layout
        // A4 height: 297mm, with 12mm top/bottom margins = 273mm usable
        // Header (~40mm) + Vendor (~15mm) + Title (~10mm) + Footer (~60mm) = ~125mm
        // Available for items: ~148mm
        // Each item row: ~8mm (print), header: ~6mm
        // Maximum items per page: (148-6)/8 = ~17 items comfortably
        
        $itemsPerPage = 17;
        $totalItems = count($procurementOrder['items']);
        $exceedsCapacity = $totalItems > $itemsPerPage;
        $hasQuotation = !empty($procurementOrder['quote_file']);
        $useQuotationReference = $exceedsCapacity && $hasQuotation;
        
        // Determine how many items to show in table
        if ($useQuotationReference) {
            $itemsToShow = 0; // Show no individual items, use summary row instead
        } else {
            $itemsToShow = $totalItems; // Show all items
        }
        ?>
        
        <?php if ($exceedsCapacity && !$hasQuotation): ?>
        <div class="alert alert-info no-print" style="font-size: 12px; margin-bottom: 10px;">
            <strong>Layout Notice:</strong> This order has <?= $totalItems ?> items. 
            For optimal A4 printing, consider splitting orders with more than <?= $itemsPerPage ?> items. 
            Long orders will automatically continue on additional pages.
        </div>
        <?php endif; ?>
        
        <!-- Items Table (always shown) -->
        <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">No.</th>
                        <th style="width: 44%;">Description</th>
                        <th style="width: 8%;">Qty</th>
                        <th style="width: 8%;">Unit</th>
                        <th style="width: 16%;">Unit Price</th>
                        <th style="width: 16%;">Total Price</th>
                    </tr>
                </thead>
            <tbody>
                <?php if ($useQuotationReference): ?>
                <!-- Quotation Reference Summary Row -->
                <?php 
                // Build intelligent quotation reference
                $quotationRef = '';
                if (!empty($procurementOrder['quotation_number'])) {
                    $quotationRef = $procurementOrder['quotation_number'];
                } elseif (!empty($procurementOrder['quotation_date'])) {
                    $quotationRef = 'dated ' . date('M j, Y', strtotime($procurementOrder['quotation_date']));
                } else {
                    $quotationRef = basename($procurementOrder['quote_file'], '.pdf');
                }
                ?>
                <tr>
                    <td>1</td>
                    <td class="description">
                        <strong>Items (<?= $totalItems ?>) as per attached Qtn. <?= htmlspecialchars($quotationRef) ?></strong>
                        <br><small>Complete specifications and details as referenced in attached quotation</small>
                    </td>
                    <td>1</td>
                    <td>lot</td>
                    <td style="text-align: right;">₱ <?= number_format($procurementOrder['net_total'], 2) ?></td>
                    <td style="text-align: right;">₱ <?= number_format($procurementOrder['net_total'], 2) ?></td>
                </tr>
                <?php else: ?>
                <!-- Standard individual items display -->
                <?php 
                $itemNo = 1;
                $displayedItems = array_slice($procurementOrder['items'], 0, $itemsToShow);
                foreach ($displayedItems as $item): 
                ?>
                <tr>
                    <td><?= $itemNo ?></td>
                    <td class="description">
                        <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                        <?php if (!empty($item['specifications'])): ?>
                            <br><small><?= htmlspecialchars($item['specifications']) ?></small>
                        <?php endif; ?>
                        <?php if (!empty($item['brand']) && !empty($item['model'])): ?>
                            <br><small><?= htmlspecialchars($item['brand']) ?> <?= htmlspecialchars($item['model']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($item['quantity']) ?></td>
                    <td><?= htmlspecialchars($item['unit']) ?></td>
                    <td style="text-align: right;">₱ <?= number_format($item['unit_price'], 2) ?></td>
                    <td style="text-align: right;">₱ <?= number_format($item['subtotal'], 2) ?></td>
                </tr>
                <?php 
                $itemNo++;
                endforeach; 
                ?>
                <?php endif; ?>
                
                <!-- Standard "Nothing Follows" row -->
                <tr>
                    <td colspan="6" style="text-align: center; font-weight: bold; padding: 8px; background-color: #f5f5f5;">
                        *** Nothing Follows ***
                    </td>
                </tr>
                
                <?php if (!$useQuotationReference): ?>
                <?php
                // Intelligent spacing only when showing full items (not using quotation reference)
                $currentRows = $itemsToShow;
                $availableSpace = $itemsPerPage - $currentRows;
                
                // Only add spacing rows if we have very few items and plenty of space
                if ($currentRows <= 5 && $availableSpace > 10) {
                    $spacingRows = min(3, $availableSpace - 5); // Leave space for footer
                    for ($i = 0; $i < $spacingRows; $i++): 
                ?>
                <tr style="height: 20px;">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <?php 
                    endfor; 
                }
                ?>
                <?php endif; ?>
                
                <!-- Project Reference Row -->
                <tr>
                    <td colspan="5" style="text-align: left; font-weight: bold;">
                        Project Reference: <?= htmlspecialchars($procurementOrder['project_name'] ?? '') ?>
                    </td>
                    <td>&nbsp;</td>
                </tr>
                
                <?php 
                // Intelligent Vendor's Reference Display
                $vendorReference = '';
                if (!empty($procurementOrder['quotation_number'])) {
                    $vendorReference = $procurementOrder['quotation_number'];
                } elseif (!empty($procurementOrder['quotation_date'])) {
                    $vendorReference = 'Quotation dated ' . date('M j, Y', strtotime($procurementOrder['quotation_date']));
                } elseif (!empty($procurementOrder['quote_file'])) {
                    $vendorReference = basename($procurementOrder['quote_file'], '.pdf');
                }
                
                if ($vendorReference): ?>
                <!-- Vendor's Reference Row -->
                <tr>
                    <td colspan="5" style="text-align: left; font-weight: bold;">
                        Vendor's Reference: <?= htmlspecialchars($vendorReference) ?>
                    </td>
                    <td>&nbsp;</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Total Section -->
        <div class="total-section">
            <table class="total-table">
                <tr>
                    <td class="label">Sub-Total</td>
                    <td>₱</td>
                    <td class="amount"><?= number_format($procurementOrder['subtotal'], 2) ?></td>
                </tr>
                <tr>
                    <td class="label">VAT (<?= number_format($procurementOrder['vat_rate'], 0) ?>%)</td>
                    <td>&nbsp;</td>
                    <td class="amount"><?= number_format($procurementOrder['vat_amount'], 2) ?></td>
                </tr>
                <tr>
                    <td class="label">Grand Total</td>
                    <td>₱</td>
                    <td class="amount"><?= number_format($procurementOrder['subtotal'] + $procurementOrder['vat_amount'], 2) ?></td>
                </tr>
                <tr>
                    <td class="label">E.W.T. (<?= number_format($procurementOrder['ewt_rate'], 0) ?>%)</td>
                    <td>&nbsp;</td>
                    <td class="amount">(<?= number_format($procurementOrder['ewt_amount'], 2) ?>)</td>
                </tr>
                <tr style="font-weight: bold;">
                    <td class="label">Net Amount</td>
                    <td>₱</td>
                    <td class="amount"><?= number_format($procurementOrder['net_total'], 2) ?></td>
                </tr>
            </table>
        </div>

        <!-- Terms and Conditions -->
        <div class="terms-section">
            <h6 style="font-weight: bold; margin-bottom: 10px;">Terms and Conditions:</h6>
            <div style="font-size: 11px; line-height: 1.4;">
                <?php
                // Dynamic terms based on procurement order data
                $deliveryMethod = $procurementOrder['delivery_method'] ?? 'Direct Delivery';
                $deliveryTerms = [
                    'Pickup' => 'Ex-Works / Pick-up at vendor location',
                    'Direct Delivery' => 'Direct delivery to specified location',
                    'Batch Delivery' => 'Batch delivery as per schedule',
                    'Airfreight' => 'Air freight delivery',
                    'Bus Cargo' => 'Bus cargo delivery',
                    'Courier' => 'Courier delivery service',
                    'Other' => 'As per agreed delivery terms'
                ];
                $selectedDeliveryTerm = $deliveryTerms[$deliveryMethod] ?? 'Direct delivery to specified location';
                ?>
                1. <strong>Terms of Payment:</strong> One Hundred Percent (100%) Pre-payment before pick-up / delivery via bank transfer.<br>
                2. <strong>Delivery:</strong> <?= htmlspecialchars($selectedDeliveryTerm) ?><?php if (!empty($procurementOrder['delivery_location'])): ?> - <?= htmlspecialchars($procurementOrder['delivery_location']) ?><?php endif; ?>.<br>
                3. <strong>Pricing:</strong> Unit prices are VAT inclusive at <?= number_format($procurementOrder['vat_rate'], 0) ?>%.<br>
                4. <strong>Documentation:</strong> Vendor's quotation (if attached) shall form part of this Purchase Order.<br>
                5. <strong>Invoicing Details:</strong> <?= htmlspecialchars($companyInfo['name']) ?> (Pasig City 1605) - TIN: <?= htmlspecialchars($companyInfo['tin']) ?>.<br>
                6. <strong>Quality Assurance:</strong> All items are subject to quality inspection upon delivery.<br>
                7. <strong>Discrepancy Reporting:</strong> Any delivery discrepancies must be reported within 48 hours.<br>
                8. <strong>Withholding Tax:</strong> E.W.T. of <?= number_format($procurementOrder['ewt_rate'], 2) ?>% will be deducted as per BIR regulations.<br>
                9. <strong>General Terms:</strong> Refer to standard terms and conditions for additional provisions.
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <p style="font-weight: bold; margin-bottom: 5px;">Conforme:</p>
                <div class="signature-line"></div>
                <p style="margin: 5px 0;">&nbsp;</p>
                <p style="margin: 0;">&nbsp;</p>
            </div>
            <div class="signature-box">
                <p style="font-weight: bold; margin-bottom: 5px;">Approved by:</p>
                <div class="signature-line"></div>
                <p style="margin: 5px 0; text-align: center; font-weight: bold;">Gerald S. Cutamora</p>
                <p style="margin: 0; text-align: center;">Treasurer / Director</p>
            </div>
        </div>

        <!-- Company Seal Area -->
        <div style="text-align: right; margin-bottom: 8px;">
            <div style="width: 50px; height: 50px; border: 1px dashed #ccc; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 7px; color: #999;">
                COMPANY<br>SEAL
            </div>
        </div>

        <!-- Footer Information -->
        <div class="footer-info">
            <div class="footer-offices">
                <div style="font-size: 8px;">
                    <strong>MAIN OFFICE:</strong> <?= htmlspecialchars($companyInfo['main_office']['address']) ?>, <?= htmlspecialchars($companyInfo['main_office']['city']) ?> | 
                    t. <?= htmlspecialchars($companyInfo['main_office']['phone']) ?>
                </div>
                <div style="font-size: 8px;">
                    <strong>BRANCH:</strong> <?= htmlspecialchars($companyInfo['branch_office']['address']) ?>, <?= htmlspecialchars($companyInfo['branch_office']['city']) ?> | 
                    t. <?= htmlspecialchars($companyInfo['branch_office']['phone']) ?>
                </div>
            </div>
            <div style="text-align: center; margin-top: 4px; font-size: 8px; position: relative;">
                <?= htmlspecialchars($companyInfo['website']) ?> | <?= htmlspecialchars($companyInfo['tagline']) ?>
                <!-- Page numbering - positioned in bottom right -->
                <span id="page-number" style="position: absolute; right: 0; top: 0; font-size: 7px; color: #999;">Page 1</span>
            </div>
        </div>
    </div>

    <!-- General Terms and Conditions (always appears after Purchase Order) -->
    <div class="page-break"></div>
    <div class="container-fluid" style="margin-top: 0; padding-top: 15mm;">
        <!-- Terms Header -->
        <div style="text-align: center; border-bottom: 1px solid #000; padding-bottom: 6mm; margin-bottom: 6mm;">
            <h4 style="font-weight: bold; margin-bottom: 3mm; font-size: 16px;">V Cutamora Construction Inc.</h4>
            <h5 style="font-weight: bold; margin: 0; font-size: 13px;">General Terms and Conditions for the Purchase of Goods and Services</h5>
        </div>

        <!-- Terms Content in Two Columns with Proper Section Grouping -->
        <div style="column-count: 2; column-gap: 12mm; font-size: 10px; line-height: 1.4; text-align: justify;">
            
            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-top: 0; margin-bottom: 2mm; font-size: 11px;">1. DEFINITIONS</h6>
                <p style="margin-bottom: 3mm;">In this document the following words shall have the following meanings:</p>
                <p style="margin-bottom: 2mm;"><strong>"Customer"</strong> means V Cutamora Construction, Inc. (VCCI). <strong>"Services"</strong> means the articles and engineering / construction works specified in the Proposal;</p>
                <p style="margin-bottom: 2mm;"><strong>"Proposal"</strong> means a statement of work, quotation or other similar document describing the articles and engineering works to be provided by the Supplier;</p>
                <p style="margin-bottom: 2mm;"><strong>"Supplier"</strong> means the Vendor whose name appears on the Purchase Order</p>
                <p style="margin-bottom: 3mm;"><strong>"Terms and Conditions"</strong> means the terms and conditions of supply set out in this document and any special terms and conditions agreed in writing by the Supplier.</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">2. GENERAL</h6>
                <p style="margin-bottom: 3mm;">These Terms and Conditions shall apply to all purchase orders for the supply of Goods and/or Services by the Supplier to Customer and shall prevail over any other documentation or communication by and between Customer and the Supplier</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">3. THE ORDER</h6>
                <p style="margin-bottom: 3mm;">The Supplier shall be deemed to have accepted the Purchase Order by signing on the space indicated in our Purchase Order. If none is received within 48 hours from the Supplier, the Purchase Order is deemed accepted together with the Terms and Conditions stated herein.</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">4. PRICE AND PAYMENT</h6>
                <p style="margin-bottom: 3mm;">The price for the Goods and Services is as specified in the Proposal and is inclusive of VAT and any applicable charges outlined in the Proposal unless otherwise specified. If the Supplier fails to meet the requirements as outlined in the Measurement of Payment (MOP) or billing requirements, no payment will be released to the Supplier or its request for payment/billing will not be processed.</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">5. DELIVERY</h6>
                <p style="margin-bottom: 2mm;">Delivery of articles and engineering/construction works will be as per proposal. Any changes to the schedule will be coordinated in advance by the Supplier to the Customer at least 2 weeks before the due date. It is required of the Supplier to exhaust all means necessary to deliver goods and services within the schedule stipulated. Delays to the delivery of goods and services will incur penalties of 1/10% per day of delay versus the Purchase Order price.</p>
                <p style="margin-bottom: 2mm;">The Supplier warrants that the articles and engineering/construction works will at the time of delivery correspond to the description given by the Supplier through its proposal. Any changes to the actual item or services rendered should be approved by the Customer through a written consent. No part of the Purchase Order may be changed by the Supplier</p>
                <p style="margin-bottom: 3mm;">Should the Supplier terminate the contract or Purchase Order, liquidated damages and other costs for the procurement of goods and services will be charged to the Supplier.</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">6. CANCELLATION</h6>
                <p style="margin-bottom: 3mm;">Should the Supplier terminate the contract or Purchase Order, liquidated damages and other costs for the procurement of goods and services will be charged to the Supplier.</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">7. SUPPLIER OBLIGATIONS</h6>
                <p style="margin-bottom: 3mm;">The Supplier shall cooperate with the Customer; provide the Customer with any information reasonably required by the Customer; comply with such other requirements as may be set out in the Proposal or otherwise agreed between the parties, provide temporary facility and security on site including manpower needed during engineering works, Safety officers and safety requirements, provide permits and licenses needed to commence work.</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">8. WARRANTY</h6>
                <p style="margin-bottom: 2mm;">1 year warranty or more as agreed upon for parts and services from date of final acceptance stated in the Purchase order.</p>
                <p style="margin-bottom: 3mm;">The Supplier commits to replace parts that are defective within 48 hours and provide the necessary engineering services to meet Customer Purchase Order requirements.</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">9. CONFIDENTIALITY</h6>
                <p style="margin-bottom: 3mm;">All information provided by the Customer in the proposal requisition and Purchase Order to the Supplier are confidential and will require written consent for the dissemination to outside parties.</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">10. LIMITATION OF LIABILITY</h6>
                <p style="margin-bottom: 3mm;">The Customer shall not be liable for any direct loss or damage suffered by the Supplier howsoever caused, as a result of any negligence or otherwise.</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">11. FORCE MAJEURE</h6>
                <p style="margin-bottom: 2mm;">Neither party shall be liable for any delay or failure to perform any of its obligations if the delay or failure results from events or circumstances outside its reasonable control, including but not limited to acts of God, strikes, lock outs, accidents, war, fire, breakdown of plant or machinery or shortage and the Supplier shall be entitled to a reasonable extension of its obligations.</p>
                <p style="margin-bottom: 3mm;">Supplier assures the Customer of regular updates, submission of progress reports and provision of skilled personnel needed for the completion of the Purchase order.</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">12. SEVERANCE</h6>
                <p style="margin-bottom: 3mm;">If any term or provision of these Terms and Conditions is held invalid, illegal or unenforceable for any reason by any court of competent jurisdiction, such provision shall be severed and the remainder of the provisions hereof shall continue in full force and effect as if these Terms and Conditions had been agreed with the invalid, illegal or unenforceable provision eliminated.</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">13. GOVERNING LAW</h6>
                <p style="margin-bottom: 3mm;">These Terms and Conditions shall be governed by and construed in accordance with the law of the Republic of the Philippines and the parties hereby submit to the exclusive jurisdiction of the Philippine courts.</p>
            </div>

            <div style="break-inside: avoid;">
                <h6 style="font-weight: bold; margin-bottom: 2mm; font-size: 11px;">14. BRIBERY AND CORRUPTION</h6>
                <p style="margin-bottom: 3mm;">Customer practices fair business practice and discourages any form of bribery and corruption.</p>
            </div>
        </div>

        <!-- Terms Footer -->
        <div style="text-align: right; margin-top: 8mm; padding-top: 3mm; border-top: 1px solid #ccc; font-size: 7px; color: #666;">
            VCCI-Procurement-GTC-01 (Jan. 2024)
        </div>
    </div>

    <!-- Quotation File Integration (when available) -->
    <?php if (!empty($procurementOrder['quote_file'])): ?>
        <?php
        // Load PDF converter and file uploader
        require_once APP_ROOT . '/core/ProcurementFileUploader.php';
        require_once APP_ROOT . '/core/PdfImageConverter.php';
        
        $quotationPath = ProcurementFileUploader::getUploadPath() . $procurementOrder['quote_file'];
        
        // Check if file exists and is a PDF
        if (ProcurementFileUploader::fileExists($procurementOrder['quote_file']) && 
            strtolower(pathinfo($procurementOrder['quote_file'], PATHINFO_EXTENSION)) === 'pdf'):
            
            // Try to convert PDF to images
            $cacheKey = PdfImageConverter::generateCacheKey($procurementOrder['quote_file']);
            $conversionResult = PdfImageConverter::convertPdfToImages($quotationPath, $cacheKey);
            
            if ($conversionResult['success']):
        ?>
                <!-- PDF Pages as A4 Images -->
                <?php foreach ($conversionResult['images'] as $image): ?>
                    <!-- Page break before each quotation page -->
                    <div class="page-break"></div>
                    
                    <!-- A4 quotation page -->
                    <div class="quotation-page" style="width: 100%; height: 100vh; margin: 0; padding: 0; display: flex; align-items: center; justify-content: center;">
                        <img src="<?= htmlspecialchars($image['url']) ?>" 
                             alt="Quotation Page <?= $image['page'] ?>"
                             style="max-width: 100%; max-height: 100%; width: auto; height: auto; margin: 0; padding: 0; display: block;" />
                    </div>
                <?php endforeach; ?>
                
        <?php else: ?>
                <!-- Fallback: Conversion failed or not available -->
                <div class="page-break"></div>
                <div class="quotation-fallback" style="width: 100%; padding: 40px; text-align: center; border: 2px solid #007bff; background-color: #f8f9fa;">
                    <h4 style="color: #007bff; margin-bottom: 20px;">📎 Quotation Attached</h4>
                    <p><strong>File:</strong> <?= htmlspecialchars(basename($procurementOrder['quote_file'])) ?></p>
                    <p><strong>Reference:</strong> <?= htmlspecialchars(basename($procurementOrder['quote_file'], '.pdf')) ?></p>
                    <div style="margin-top: 20px; padding: 15px; background-color: #fff; border-radius: 5px;">
                        <p style="margin: 0; color: #666; font-size: 14px;">
                            <strong>Note:</strong> Complete quotation details are available in the attached PDF file.<br>
                            <?php 
                            $errorMessage = $conversionResult['error'] ?? '';
                            if (strpos($errorMessage, 'no decode delegate') !== false || strpos($errorMessage, 'PDF') !== false) {
                                echo 'PDF display not supported on this server - please download the PDF file separately.';
                            } else {
                                echo 'PDF conversion temporarily unavailable - please refer to the original quotation document.';
                            }
                            ?>
                        </p>
                    </div>
                    
                    <?php if (!empty($conversionResult['error']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false): ?>
                    <!-- Show technical details only on localhost/development -->
                    <details style="font-size: 12px; color: #999; margin-top: 10px; cursor: pointer;">
                        <summary>Technical Details (Development)</summary>
                        <em><?= htmlspecialchars($conversionResult['error']) ?></em>
                    </details>
                    <?php endif; ?>
                </div>
        <?php endif; ?>
        
        <?php elseif (ProcurementFileUploader::fileExists($procurementOrder['quote_file'])): ?>
            <!-- Non-PDF files -->
            <div class="page-break"></div>
            <div style="width: 100%; padding: 40px; text-align: center; border: 2px dashed #28a745;">
                <h4 style="color: #28a745;">📎 Quotation Attachment</h4>
                <p><strong>File:</strong> <?= htmlspecialchars(basename($procurementOrder['quote_file'])) ?></p>
                <p><strong>Type:</strong> <?= htmlspecialchars(strtoupper(pathinfo($procurementOrder['quote_file'], PATHINFO_EXTENSION))) ?> file</p>
                <p style="color: #666; font-size: 12px; margin-top: 15px;">
                    Non-PDF quotation files are attached separately and cannot be embedded in the print preview.<br>
                    Please refer to the original quotation file for complete specifications and pricing details.
                </p>
            </div>
        <?php else: ?>
            <!-- File not found -->
            <div class="page-break"></div>
            <div style="width: 100%; padding: 40px; text-align: center; border: 2px dashed #dc3545; color: #dc3545;">
                <h4>⚠️ Quotation File Not Found</h4>
                <p><strong>Expected File:</strong> <?= htmlspecialchars($procurementOrder['quote_file']) ?></p>
                <p>The quotation file may have been moved, deleted, or the path is incorrect.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Bootstrap JS for interactive elements -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Simple page numbering for Purchase Order -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Calculate page count based on items (for Purchase Order pages only)
            const itemsTable = document.querySelector('.items-table');
            let poPageCount = 1;
            
            if (itemsTable) {
                const itemRows = itemsTable.querySelectorAll('tbody tr:not([colspan])').length; // Exclude "Nothing Follows" row
                const itemsPerPage = 17; // As defined in PHP
                
                if (itemRows > itemsPerPage) {
                    poPageCount = Math.ceil(itemRows / itemsPerPage);
                }
            }
            
            // Update page number display (only for Purchase Order pages)
            const pageNumber = document.getElementById('page-number');
            if (pageNumber) {
                if (poPageCount > 1) {
                    pageNumber.textContent = `Page 1 of ${poPageCount}`;
                } else {
                    pageNumber.textContent = 'Page 1 of 1';
                }
            }
            
            // Note: Terms and Conditions and Quotation pages are separate and don't use this numbering
        });
    </script>
    
    <!-- Print control JavaScript -->
    <?php if (!empty($procurementOrder['quote_file'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const attachmentToggle = document.getElementById('includeAttachment');
            const quotationPages = document.querySelectorAll('.quotation-page, .quotation-fallback');
            
            if (attachmentToggle && quotationPages.length > 0) {
                attachmentToggle.addEventListener('change', function() {
                    quotationPages.forEach(function(page) {
                        if (this.checked) {
                            page.style.display = 'flex';
                        } else {
                            page.style.display = 'none';
                        }
                    }.bind(this));
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>