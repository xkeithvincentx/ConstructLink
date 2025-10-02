<?php
/**
 * ConstructLink™ Company Configuration
 * Company information for documents and branding
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Company Information - with redefinition protection
if (!defined('COMPANY_NAME')) {
    define('COMPANY_NAME', 'V CUTAMORA CONSTRUCTION INC.');
}
if (!defined('COMPANY_ADDRESS')) {
    define('COMPANY_ADDRESS', 'Construction Industry Building\nQuezon City, Metro Manila\nPhilippines');
}
if (!defined('COMPANY_PHONE')) {
    define('COMPANY_PHONE', '+63 XXX XXX XXXX');
}
if (!defined('COMPANY_EMAIL')) {
    define('COMPANY_EMAIL', 'info@vcutamora.com');
}
if (!defined('COMPANY_WEBSITE')) {
    define('COMPANY_WEBSITE', 'www.vcutamora.com');
}
if (!defined('COMPANY_TIN')) {
    define('COMPANY_TIN', 'XXX-XXX-XXX-XXX');
}
if (!defined('COMPANY_LOGO_PATH')) {
    define('COMPANY_LOGO_PATH', APP_ROOT . '/assets/images/company-logo.png');
}

// Document Settings - with redefinition protection
if (!defined('DOCUMENT_HEADER_COLOR')) {
    define('DOCUMENT_HEADER_COLOR', '#1a365d'); // Dark blue
}
if (!defined('DOCUMENT_ACCENT_COLOR')) {
    define('DOCUMENT_ACCENT_COLOR', '#3182ce'); // Light blue
}
if (!defined('DOCUMENT_FOOTER_COLOR')) {
    define('DOCUMENT_FOOTER_COLOR', '#718096'); // Gray
}
if (!defined('DOCUMENT_MARGIN_TOP')) {
    define('DOCUMENT_MARGIN_TOP', 30);
}
if (!defined('DOCUMENT_MARGIN_BOTTOM')) {
    define('DOCUMENT_MARGIN_BOTTOM', 25);
}
if (!defined('DOCUMENT_MARGIN_LEFT')) {
    define('DOCUMENT_MARGIN_LEFT', 15);
}
if (!defined('DOCUMENT_MARGIN_RIGHT')) {
    define('DOCUMENT_MARGIN_RIGHT', 15);
}

// PDF Settings - with redefinition protection
if (!defined('PDF_CREATOR')) {
    define('PDF_CREATOR', 'ConstructLink™ System');
}
if (!defined('PDF_AUTHOR')) {
    define('PDF_AUTHOR', COMPANY_NAME);
}
if (!defined('PDF_TITLE_PREFIX')) {
    define('PDF_TITLE_PREFIX', 'Purchase Order');
}
if (!defined('PDF_SUBJECT')) {
    define('PDF_SUBJECT', 'Purchase Order Document');
}
if (!defined('PDF_KEYWORDS')) {
    define('PDF_KEYWORDS', 'purchase order, procurement, construction, supply');
}

// Procurement Order Settings - with redefinition protection
if (!defined('PO_TERMS_CONDITIONS')) {
    define('PO_TERMS_CONDITIONS', 'Terms and Conditions:
1. All items must be delivered according to specifications.
2. Delivery must be made within the agreed timeframe.
3. All items are subject to quality inspection upon delivery.
4. Payment will be processed according to agreed terms.
5. Any discrepancies must be reported within 48 hours of delivery.');
}

if (!defined('PO_FOOTER_NOTE')) {
    define('PO_FOOTER_NOTE', 'This Purchase Order is generated electronically by ConstructLink™ System.');
}

// Authorized Signatures - with redefinition protection
if (!defined('PO_PREPARED_BY_TITLE')) {
    define('PO_PREPARED_BY_TITLE', 'Prepared by:');
}
if (!defined('PO_APPROVED_BY_TITLE')) {
    define('PO_APPROVED_BY_TITLE', 'Approved by:');
}
if (!defined('PO_RECEIVED_BY_TITLE')) {
    define('PO_RECEIVED_BY_TITLE', 'Received by:');
}

// Company Bank Details (for vendor reference) - with redefinition protection
if (!defined('COMPANY_BANK_NAME')) {
    define('COMPANY_BANK_NAME', 'Philippine National Bank');
}
if (!defined('COMPANY_BANK_ACCOUNT')) {
    define('COMPANY_BANK_ACCOUNT', 'XXXX-XXXX-XXXX');
}
if (!defined('COMPANY_BANK_ACCOUNT_NAME')) {
    define('COMPANY_BANK_ACCOUNT_NAME', COMPANY_NAME);
}

/**
 * Get company information array for PDF generation
 */
function getCompanyInfo() {
    return [
        'name' => 'V CUTAMORA CONSTRUCTION INC.',
        'tagline' => 'QUALITY WORKS AND CLIENT SATISFACTION IS OUR GAME',
        'main_office' => [
            'address' => 'Unit 806, 8th Floor, Taipan Place,',
            'city' => 'F. Ortigas Jr. Road, Ortigas Center, Pasig City, Philippines 1605',
            'phone' => '+632.576.4214',
            'fax' => '+632.625.2885'
        ],
        'branch_office' => [
            'address' => '9072 Talon Street',
            'city' => 'Brgy. Alagao, San Ildefonso, Bulacan, Philippines 3010',
            'phone' => '+632.576.3854',
            'fax' => 'vcutamora.com'
        ],
        'website' => 'vcutamora.com',
        'tin' => '007-608-972-000',
        'email' => 'info@vcutamora.com'
    ];
}
