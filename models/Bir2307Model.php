<?php
/**
 * ConstructLink™ BIR Form 2307 Model
 * Manages Certificate of Creditable Tax Withheld at Source
 */

class Bir2307Model extends BaseModel {
    protected $table = 'bir_2307_forms';
    protected $fillable = [
        'form_number', 'procurement_order_id', 'vendor_id', 'period_from', 'period_to',
        'quarter', 'year', 'payee_tin', 'payee_name', 'payee_registered_name',
        'payee_first_name', 'payee_middle_name', 'payee_last_name', 'payee_address',
        'payee_zip_code', 'payee_foreign_address', 'payor_tin', 'payor_name',
        'payor_address', 'payor_zip_code', 'income_payments', 'total_amount',
        'total_tax_withheld', 'money_payments', 'status', 'generated_by',
        'generated_at', 'submitted_at', 'notes'
    ];
    
    /**
     * Generate BIR 2307 form for procurement order
     */
    public function generateForm($procurementOrderId, $userId = null) {
        try {
            $this->beginTransaction();
            
            // Get procurement order details
            $poModel = new ProcurementOrderModel();
            $procurementOrder = $poModel->find($procurementOrderId);
            
            if (!$procurementOrder) {
                $this->rollback();
                return ['success' => false, 'message' => 'Procurement order not found'];
            }
            
            // Get vendor details
            $vendorModel = new VendorModel();
            $vendor = $vendorModel->find($procurementOrder['vendor_id']);
            
            if (!$vendor) {
                $this->rollback();
                return ['success' => false, 'message' => 'Vendor not found'];
            }
            
            // Check if form already exists for this PO
            $sql = "SELECT * FROM bir_2307_forms WHERE procurement_order_id = ? AND status IN ('Draft', 'Generated') LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            $existingForm = $stmt->fetch();
            
            if ($existingForm) {
                $this->rollback();
                return ['success' => false, 'message' => 'BIR 2307 form already exists for this procurement order'];
            }
            
            // Generate form number
            $formNumber = $this->generateFormNumber();
            
            // Get period details
            $periodDetails = $this->determinePeriod($procurementOrder['created_at']);
            
            // Prepare payee information based on vendor type
            $payeeData = $this->preparePayeeData($vendor);
            
            // Get procurement items with ATC codes
            $itemModel = new ProcurementItemModel();
            $items = $itemModel->getItemsByOrderId($procurementOrderId);
            
            // Calculate income payments by ATC code
            $incomePayments = $this->calculateIncomePayments($items, $procurementOrder);
            
            // Get company information
            require_once APP_ROOT . '/config/company.php';
            $companyInfo = getCompanyInfo();
            
            // Create form data
            $formData = [
                'form_number' => $formNumber,
                'procurement_order_id' => $procurementOrderId,
                'vendor_id' => $vendor['id'],
                'period_from' => $periodDetails['period_from'],
                'period_to' => $periodDetails['period_to'],
                'quarter' => $periodDetails['quarter'],
                'year' => $periodDetails['year'],
                
                // Payee information
                'payee_tin' => $vendor['tin'] ?? '',
                'payee_name' => $payeeData['payee_name'],
                'payee_registered_name' => $payeeData['registered_name'],
                'payee_first_name' => $payeeData['first_name'],
                'payee_middle_name' => $payeeData['middle_name'],
                'payee_last_name' => $payeeData['last_name'],
                'payee_address' => $vendor['address'] ?? '',
                'payee_zip_code' => $vendor['zip_code'] ?? '',
                'payee_foreign_address' => null,
                
                // Payor information (V Cutamora)
                'payor_tin' => $companyInfo['tin'],
                'payor_name' => $companyInfo['name'],
                'payor_address' => $companyInfo['main_office']['address'] . ', ' . $companyInfo['main_office']['city'],
                'payor_zip_code' => '1605',
                
                // Income and tax details
                'income_payments' => json_encode($incomePayments['details']),
                'total_amount' => $incomePayments['total_amount'],
                'total_tax_withheld' => $incomePayments['total_tax'],
                'money_payments' => json_encode([]),
                
                // Status
                'status' => 'Generated',
                'generated_by' => $userId,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            // Create the form
            $form = $this->create($formData);
            
            if (!$form) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to generate BIR 2307 form'];
            }
            
            // Update procurement order
            $poModel->update($procurementOrderId, [
                'bir_2307_generated' => 1,
                'bir_2307_form_id' => $form['id']
            ]);
            
            // Log the generation
            $this->logFormAction($form['id'], 'created', $userId, ['procurement_order_id' => $procurementOrderId]);
            
            $this->commit();
            return ['success' => true, 'form' => $form];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Generate BIR 2307 form error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate BIR 2307 form'];
        }
    }
    
    /**
     * Generate unique form number
     */
    private function generateFormNumber() {
        try {
            // Get configuration
            $sql = "SELECT config_value FROM bir_2307_config WHERE config_key = ?";
            $stmt = $this->db->prepare($sql);
            
            // Get prefix
            $stmt->execute(['form_series_prefix']);
            $prefix = $stmt->fetch()['config_value'] ?? '2307-';
            
            // Get and increment counter
            $stmt->execute(['form_series_counter']);
            $counter = (int)($stmt->fetch()['config_value'] ?? 1);
            
            // Format: 2307-YYYY-NNNNN
            $formNumber = $prefix . date('Y') . '-' . str_pad($counter, 5, '0', STR_PAD_LEFT);
            
            // Update counter
            $updateSql = "UPDATE bir_2307_config SET config_value = ? WHERE config_key = 'form_series_counter'";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([$counter + 1]);
            
            return $formNumber;
            
        } catch (Exception $e) {
            // Fallback to timestamp-based number
            return '2307-' . date('Y') . '-' . date('His');
        }
    }
    
    /**
     * Determine period and quarter based on date
     */
    private function determinePeriod($date) {
        $timestamp = strtotime($date);
        $month = (int)date('n', $timestamp);
        $year = (int)date('Y', $timestamp);
        
        // Determine quarter
        if ($month <= 3) {
            $quarter = '1st';
            $periodFrom = "$year-01-01";
            $periodTo = "$year-03-31";
        } elseif ($month <= 6) {
            $quarter = '2nd';
            $periodFrom = "$year-04-01";
            $periodTo = "$year-06-30";
        } elseif ($month <= 9) {
            $quarter = '3rd';
            $periodFrom = "$year-07-01";
            $periodTo = "$year-09-30";
        } else {
            $quarter = '4th';
            $periodFrom = "$year-10-01";
            $periodTo = "$year-12-31";
        }
        
        return [
            'quarter' => $quarter,
            'year' => $year,
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'month' => $month
        ];
    }
    
    /**
     * Prepare payee data based on vendor type
     */
    private function preparePayeeData($vendor) {
        $data = [
            'payee_name' => '',
            'registered_name' => '',
            'first_name' => '',
            'middle_name' => '',
            'last_name' => ''
        ];
        
        switch ($vendor['vendor_type']) {
            case 'Sole Proprietor':
                // For sole proprietors, use individual name fields
                $data['first_name'] = $vendor['first_name'] ?? '';
                $data['middle_name'] = $vendor['middle_name'] ?? '';
                $data['last_name'] = $vendor['last_name'] ?? '';
                
                // Construct full name for display
                $nameParts = array_filter([
                    $vendor['first_name'],
                    $vendor['middle_name'],
                    $vendor['last_name']
                ]);
                $data['payee_name'] = implode(' ', $nameParts);
                
                // Business name goes to registered name if available
                $data['registered_name'] = $vendor['registered_name'] ?? $vendor['name'];
                break;
                
            case 'Company':
            case 'Partnership':
            case 'Cooperative':
            case 'Government':
            default:
                // For companies, use the company name
                $data['payee_name'] = $vendor['name'];
                $data['registered_name'] = $vendor['registered_name'] ?? $vendor['name'];
                break;
        }
        
        return $data;
    }
    
    /**
     * Calculate income payments grouped by ATC code
     */
    private function calculateIncomePayments($items, $procurementOrder) {
        $atcModel = new AtcCodeModel();
        $payments = [];
        $totalAmount = 0;
        $totalTax = 0;
        
        // Group items by ATC code
        $groupedItems = [];
        foreach ($items as $item) {
            $atcCodeId = $item['atc_code_id'] ?? null;
            if (!$atcCodeId) {
                // Default ATC code based on purchase type
                $purchaseType = $item['purchase_type'] ?? 'Goods';
                $atcCodeId = $this->getDefaultAtcCodeId($purchaseType);
            }
            
            if (!isset($groupedItems[$atcCodeId])) {
                $groupedItems[$atcCodeId] = [
                    'items' => [],
                    'total' => 0
                ];
            }
            
            $groupedItems[$atcCodeId]['items'][] = $item;
            $groupedItems[$atcCodeId]['total'] += $item['subtotal'];
        }
        
        // Calculate tax for each ATC code group
        $month = (int)date('n', strtotime($procurementOrder['created_at']));
        
        foreach ($groupedItems as $atcCodeId => $group) {
            $atcCode = $atcModel->find($atcCodeId);
            if (!$atcCode) continue;
            
            // Calculate EWT
            $amount = $group['total'];
            $taxWithheld = $atcModel->calculateEWT($atcCodeId, $amount, true);
            
            $payments[] = [
                'atc_code' => $atcCode['code'],
                'description' => $atcCode['description'],
                'month' => $month,
                'amount' => $amount,
                'tax_withheld' => $taxWithheld
            ];
            
            $totalAmount += $amount;
            $totalTax += $taxWithheld;
        }
        
        return [
            'details' => $payments,
            'total_amount' => $totalAmount,
            'total_tax' => $totalTax
        ];
    }
    
    /**
     * Get default ATC code ID based on purchase type
     */
    private function getDefaultAtcCodeId($purchaseType) {
        $atcModel = new AtcCodeModel();
        
        // Default ATC codes based on purchase type
        $defaultCodes = [
            'Goods' => 'WC156',          // 1% for goods
            'Services' => 'WC157',        // 2% for services
            'Rental' => 'WC030',          // 5% for rental
            'Professional Services' => 'WI011', // 15% for professional services (non-individual)
            'Mixed' => 'WC157',           // Default to services
            'Other' => 'WC157'            // Default to services
        ];
        
        $code = $defaultCodes[$purchaseType] ?? 'WC157';
        $atcCode = $atcModel->getAtcCodeByCode($code);
        
        return $atcCode ? $atcCode['id'] : null;
    }
    
    /**
     * Log form action for audit trail
     */
    private function logFormAction($formId, $action, $userId = null, $details = []) {
        try {
            $sql = "
                INSERT INTO bir_2307_audit_log 
                (form_id, action, user_id, details, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $formId,
                $action,
                $userId,
                json_encode($details),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
        } catch (Exception $e) {
            error_log("Log form action error: " . $e->getMessage());
        }
    }
    
    /**
     * Get form by procurement order ID
     */
    public function getFormByProcurementOrderId($procurementOrderId) {
        try {
            $sql = "
                SELECT f.*, v.name as vendor_name, v.vendor_type,
                       po.po_number, po.title as po_title,
                       u.full_name as generated_by_name
                FROM bir_2307_forms f
                LEFT JOIN vendors v ON f.vendor_id = v.id
                LEFT JOIN procurement_orders po ON f.procurement_order_id = po.id
                LEFT JOIN users u ON f.generated_by = u.id
                WHERE f.procurement_order_id = ?
                    AND f.status != 'Cancelled'
                ORDER BY f.created_at DESC
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Get form by procurement order ID error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get forms for quarter
     */
    public function getFormsForQuarter($quarter, $year, $vendorId = null) {
        try {
            $sql = "
                SELECT f.*, v.name as vendor_name, po.po_number
                FROM bir_2307_forms f
                LEFT JOIN vendors v ON f.vendor_id = v.id
                LEFT JOIN procurement_orders po ON f.procurement_order_id = po.id
                WHERE f.quarter = ? AND f.year = ?
                    AND f.status IN ('Generated', 'Printed', 'Submitted')
            ";
            
            $params = [$quarter, $year];
            
            if ($vendorId) {
                $sql .= " AND f.vendor_id = ?";
                $params[] = $vendorId;
            }
            
            $sql .= " ORDER BY f.vendor_id, f.created_at";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get forms for quarter error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update form status
     */
    public function updateFormStatus($formId, $status, $userId = null) {
        try {
            $validStatuses = ['Draft', 'Generated', 'Printed', 'Submitted', 'Cancelled'];
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }
            
            $updateData = ['status' => $status];
            
            if ($status === 'Submitted') {
                $updateData['submitted_at'] = date('Y-m-d H:i:s');
            }
            
            $result = $this->update($formId, $updateData);
            
            if ($result) {
                $this->logFormAction($formId, strtolower($status), $userId);
                return ['success' => true];
            } else {
                return ['success' => false, 'message' => 'Failed to update status'];
            }
            
        } catch (Exception $e) {
            error_log("Update form status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update status'];
        }
    }
}