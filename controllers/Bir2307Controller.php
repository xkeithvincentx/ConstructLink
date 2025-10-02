<?php
/**
 * ConstructLink™ BIR Form 2307 Controller
 * Handles Certificate of Creditable Tax Withheld at Source operations
 */

class Bir2307Controller {
    
    private $bir2307Model;
    private $procurementModel;
    private $vendorModel;
    private $atcCodeModel;
    private $auth;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        
        // Initialize models
        $this->bir2307Model = new Bir2307Model();
        $this->procurementModel = new ProcurementOrderModel();
        $this->vendorModel = new VendorModel();
        $this->atcCodeModel = new AtcCodeModel();
    }
    
    /**
     * Generate BIR 2307 form for procurement order
     */
    public function generate() {
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['error'] = 'Please log in first';
            header('Location: ?route=login');
            exit;
        }

        $procurementOrderId = $_GET['po_id'] ?? 0;
        
        if (!$procurementOrderId) {
            $_SESSION['error'] = 'Invalid procurement order ID';
            header('Location: ?route=procurement-orders');
            exit;
        }

        try {
            // Get procurement order
            $procurementOrder = $this->procurementModel->find($procurementOrderId);
            
            if (!$procurementOrder) {
                $_SESSION['error'] = 'Procurement order not found';
                header('Location: ?route=procurement-orders');
                exit;
            }

            // Get vendor
            $vendor = $this->vendorModel->find($procurementOrder['vendor_id']);
            
            if (!$vendor) {
                $_SESSION['error'] = 'Vendor not found';
                header('Location: ?route=procurement-orders/view&id=' . $procurementOrderId);
                exit;
            }

            // Check if vendor has TIN
            if (empty($vendor['tin'])) {
                $_SESSION['error'] = 'Vendor TIN is required for BIR 2307 generation. Please edit vendor "' . $vendor['name'] . '" and add a TIN number.';
                header('Location: ?route=procurement-orders/view&id=' . $procurementOrderId);
                exit;
            }

            // Generate BIR 2307 Form
            $bir2307Data = $this->prepareBir2307Data($procurementOrder, $vendor);
            
            // Load the BIR 2307 view
            $pageTitle = 'BIR Form 2307 - Certificate of Creditable Tax Withheld at Source';
            $pageHeader = 'BIR Form 2307';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
                ['title' => 'PO #' . ($procurementOrder['reference'] ?? $procurementOrder['id']), 'url' => '?route=procurement-orders/view&id=' . $procurementOrderId],
                ['title' => 'BIR Form 2307', 'url' => '']
            ];

            include APP_ROOT . '/views/bir-2307/form.php';

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error generating BIR 2307: ' . $e->getMessage();
            header('Location: ?route=procurement-orders/view&id=' . $procurementOrderId);
            exit;
        }
    }

    /**
     * Prepare BIR 2307 data structure
     */
    private function prepareBir2307Data($procurementOrder, $vendor) {
        // Determine current quarter and year
        $currentDate = new DateTime();
        $quarter = ceil($currentDate->format('n') / 3);
        $year = $currentDate->format('Y');

        // Calculate period dates
        $quarterStart = new DateTime($year . '-' . (($quarter - 1) * 3 + 1) . '-01');
        $quarterEnd = new DateTime($quarterStart->format('Y-m-t'));
        $quarterEnd->modify('last day of +2 months');

        return [
            // Form Information
            'form_number' => 'BIR2307-' . date('Y') . '-' . str_pad($procurementOrder['id'], 6, '0', STR_PAD_LEFT),
            'quarter' => $this->getQuarterName($quarter),
            'year' => $year,
            'period_from' => $quarterStart->format('Y-m-d'),
            'period_to' => $quarterEnd->format('Y-m-d'),

            // Payee Information (Part I) - The Vendor
            'payee' => [
                'tin' => $vendor['tin'],
                'name' => $this->getVendorDisplayName($vendor),
                'address' => $vendor['address'] ?? '',
                'zip_code' => $vendor['zip_code'] ?? ''
            ],

            // Payor Information (Part II) - Our Company
            'payor' => [
                'tin' => '007-608-972-000',
                'name' => 'V CUTAMORA CONSTRUCTION INC.',
                'address' => 'Unit 2506, 25th Floor, World Trade Exchange Building, 215 Juan Luna Street, Binondo, Manila',
                'zip_code' => '1006'
            ],

            // Income Payments (Part III)
            'total_amount' => $procurementOrder['net_total'] ?? 0,
            'total_tax_withheld' => $this->calculateEWT($procurementOrder),

            // Source data
            'procurement_order' => $procurementOrder,
            'vendor' => $vendor
        ];
    }

    /**
     * Get vendor display name based on vendor type
     */
    private function getVendorDisplayName($vendor) {
        if ($vendor['vendor_type'] === 'Sole Proprietor') {
            // For sole proprietors, use individual name format
            $parts = array_filter([
                $vendor['first_name'] ?? '',
                $vendor['middle_name'] ?? '',
                $vendor['last_name'] ?? ''
            ]);
            
            if (!empty($parts)) {
                return implode(' ', $parts);
            }
        }
        
        // For companies and other entity types, use registered name or business name
        return $vendor['registered_name'] ?? $vendor['name'];
    }

    /**
     * Calculate Expanded Withholding Tax
     */
    private function calculateEWT($procurementOrder) {
        // For now, return simple 2% calculation
        // This should be enhanced to use ATC codes in the future
        $amount = $procurementOrder['net_total'] ?? 0;
        return $amount * 0.02; // 2% EWT rate
    }

    /**
     * Get quarter name
     */
    private function getQuarterName($quarter) {
        $quarters = [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'];
        return $quarters[$quarter] ?? '1st';
    }
}