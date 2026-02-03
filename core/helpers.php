<?php
/**
 * ConstructLink™ Helper Functions
 * Global utility functions used throughout the application
 */

/**
 * Get navigation menu based on routes.php and roles.php configuration
 * This function dynamically generates navigation based on user permissions
 */
function getNavigationMenu($userRole) {
    // Load routes configuration to check permissions
    global $routes;
    if (empty($routes)) {
        $routes = require APP_ROOT . '/routes.php';
    }
    
    // Define navigation structure with route mappings
    $navigationStructure = [
        'Assets' => [
            'View Assets' => 'assets',
            'Add Asset' => 'assets/create',
            'Asset Scanner' => 'assets/scanner'
        ],
        'Operations' => [
            'Requests' => 'requests',
            'Withdrawals' => 'withdrawals', 
            'Transfers' => 'transfers',
            'Maintenance' => 'maintenance',
            'Incidents' => 'incidents',
            'Borrowed Tools' => 'borrowed-tools'
        ],
        'Procurement' => [
            'Orders Dashboard' => 'procurement-orders',
            'Create Order' => 'procurement-orders/create',
            'Delivery Management' => 'procurement-orders/delivery-management',
            'Performance Dashboard' => 'procurement-orders/performance-dashboard'
        ],
        'Reports' => [
            'Reports' => 'reports'
        ],
        'Master Data' => [
            'Users' => 'users',
            'Projects' => 'projects',
            'Categories' => 'categories',
            'Equipment Management' => 'equipment/management',
            'Vendors' => 'vendors',
            'Makers' => 'makers',
            'Clients' => 'clients',
            'Brands' => 'brands',
            'Disciplines' => 'disciplines'
        ],
        'Administration' => [
            'System Admin' => 'admin'
        ]
    ];
    
    // Build menu based on user permissions
    $menu = [];
    
    foreach ($navigationStructure as $section => $items) {
        $sectionItems = [];
        
        foreach ($items as $label => $route) {
            // Check if user has permission for this route
            if (hasRoutePermission($userRole, $route)) {
                if ($section === 'Reports' || $section === 'Administration') {
                    // For single-item sections, set directly
                    $menu[$section] = '?route=' . $route;
                    break; // Only one item per section
                } else {
                    // For multi-item sections, add to array
                    $sectionItems[$label] = '?route=' . $route;
                }
            }
        }
        
        // Add section if it has items
        if (!empty($sectionItems)) {
            if (count($sectionItems) === 1) {
                // If only one item, make it a direct link
                $menu[$section] = reset($sectionItems);
            } else {
                // Multiple items, keep as array
                $menu[$section] = $sectionItems;
            }
        }
    }
    
    return $menu;
}

/**
 * Check if user role has permission for a specific route
 */
function hasRoutePermission($userRole, $route) {
    // Load roles configuration
    static $roleConfig = null;
    if ($roleConfig === null) {
        $roleConfig = require APP_ROOT . '/config/roles.php';
    }
    
    // System Admin always has access
    if ($userRole === 'System Admin') {
        return true;
    }
    
    // Get allowed roles for the route using the roles configuration
    $allowedRoles = [];
    
    // Check direct route mapping first
    if (isset($roleConfig[$route]) && is_array($roleConfig[$route]) && !isset($roleConfig[$route]['maker'])) {
        $allowedRoles = $roleConfig[$route];
    }
    // Check MVA structure mapping
    elseif (isset($roleConfig[$route]) && is_array($roleConfig[$route])) {
        foreach (['maker', 'verifier', 'authorizer', 'viewer'] as $mvaRole) {
            if (isset($roleConfig[$route][$mvaRole])) {
                $allowedRoles = array_merge($allowedRoles, $roleConfig[$route][$mvaRole]);
            }
        }
        $allowedRoles = array_unique($allowedRoles);
    }
    // Fallback: only System Admin has access to unknown routes
    else {
        $allowedRoles = ['System Admin'];
    }
    
    // Check if user role is in allowed roles
    return in_array($userRole, $allowedRoles);
}

/**
 * Format currency amount
 */
function formatCurrency($amount, $currency = 'PHP') {
    if ($amount === null || $amount === '') {
        return 'N/A';
    }
    
    return '₱' . number_format((float)$amount, 2);
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M j, Y') {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'M j, Y g:i A') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    
    return date($format, strtotime($datetime));
}

/**
 * Get time ago string
 */
function timeAgo($datetime) {
    if (empty($datetime)) return 'N/A';
    
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'available' => 'bg-success',
        'in_use' => 'bg-primary',
        'borrowed' => 'bg-info',
        'under_maintenance' => 'bg-warning',
        'retired' => 'bg-secondary',
        'in_transit' => 'bg-warning text-dark',
        'disposed' => 'bg-dark',
        'pending' => 'bg-warning',
        'released' => 'bg-primary',
        'returned' => 'bg-success',
        'canceled' => 'bg-secondary',
        'temporary' => 'bg-info',
        'permanent' => 'bg-primary',
        'lost' => 'bg-danger',
        'damaged' => 'bg-warning',
        'other' => 'bg-secondary',
        'preventive' => 'bg-info',
        'corrective' => 'bg-warning',
        'completed' => 'bg-success',
        'under_investigation' => 'bg-warning',
        'verified' => 'bg-info',
        'resolved' => 'bg-success'
    ];
    
    return $classes[$status] ?? 'bg-secondary';
}

/**
 * Get status label
 */
function getStatusLabel($status) {
    $labels = [
        'available' => 'Available',
        'in_use' => 'In Use',
        'borrowed' => 'Borrowed',
        'under_maintenance' => 'Under Maintenance',
        'retired' => 'Retired',
        'in_transit' => 'In Transit',
        'disposed' => 'Disposed',
        'pending' => 'Pending',
        'released' => 'Released',
        'returned' => 'Returned',
        'canceled' => 'Canceled',
        'temporary' => 'Temporary',
        'permanent' => 'Permanent',
        'lost' => 'Lost',
        'damaged' => 'Damaged',
        'other' => 'Other',
        'preventive' => 'Preventive',
        'corrective' => 'Corrective',
        'completed' => 'Completed',
        'under_investigation' => 'Under Investigation',
        'verified' => 'Verified',
        'resolved' => 'Resolved'
    ];
    
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate ISO 55000:2024 compliant asset reference
 * 
 * @param int|null $categoryId Asset category ID
 * @param int|null $disciplineId Primary discipline ID
 * @param bool $isLegacy Whether this is a legacy asset
 * @return string ISO compliant reference
 */
function generateAssetReference($categoryId = null, $disciplineId = null, $isLegacy = false) {
    try {
        // Use new ISO 55000:2024 reference generator
        require_once APP_ROOT . '/core/ISO55000ReferenceGenerator.php';
        $generator = new ISO55000ReferenceGenerator();
        
        return $generator->generateReference($categoryId, $disciplineId, $isLegacy);
        
    } catch (Exception $e) {
        error_log("ISO reference generation error: " . $e->getMessage());
        
        // Fallback to legacy format for compatibility
        $prefix = "CL";
        $year = date('Y');
        return $prefix . $year . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
    }
}

/**
 * Legacy function for backward compatibility
 * @deprecated Use generateAssetReference() instead
 */
function generateLegacyAssetReference() {
    return generateAssetReference(null, null, true);
}

/**
 * Generate simple transfer reference
 *
 * Format: TR-YYYY-NNNN
 * Example: TR-2025-0023
 *
 * Sequential numbering is unique within each year and pulled from database.
 * This format is simpler and more suitable for transfers than the ISO 55000:2024
 * format used for assets.
 *
 * @return string Transfer reference in TR-YYYY-NNNN format
 */
function generateTransferReference() {
    try {
        $db = Database::getInstance()->getConnection();
        $year = date('Y');
        $prefix = "TR-{$year}-";

        // Get the highest sequential number for this year
        $sql = "SELECT MAX(CAST(SUBSTRING(ref, LENGTH(?) + 1) AS UNSIGNED)) as max_seq
                FROM transfers
                WHERE ref LIKE ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$prefix, "{$prefix}%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $maxSeq = (int)($result['max_seq'] ?? 0);
        $nextSeq = $maxSeq + 1;

        // Format as TR-YYYY-NNNN (4-digit zero-padded)
        $reference = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        // Validate uniqueness
        $checkSql = "SELECT COUNT(*) as count FROM transfers WHERE ref = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$reference]);
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ((int)$checkResult['count'] > 0) {
            // Reference already exists, retry with incremented number
            $nextSeq++;
            $reference = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
        }

        return $reference;

    } catch (Exception $e) {
        error_log("Transfer reference generation error: " . $e->getMessage());

        // Fallback to time-based format (should never happen)
        $prefix = "TR";
        $year = date('Y');
        return $prefix . '-' . $year . '-' . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
    }
}

/**
 * Check if user has permission based on roles.php configuration
 */
function hasPermission($permission) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // System Admin has all permissions
    if ($user['role_name'] === 'System Admin') {
        return true;
    }
    
    // Load roles configuration
    $roles = require APP_ROOT . '/config/roles.php';
    
    // Check if permission exists in roles configuration
    if (!isset($roles[$permission])) {
        return false;
    }
    
    // Check if user's role is in the allowed roles for this permission
    return in_array($user['role_name'], $roles[$permission]);
}

/**
 * Check if user has role
 */
function hasRole($roles) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    return in_array($user['role_name'], $roles);
}

/**
 * Get current user
 */
function getCurrentUser() {
    $auth = Auth::getInstance();
    return $auth->getCurrentUser();
}

/**
 * Flash message helper
 */
function flash($message, $type = 'success') {
    if (!isset($_SESSION['flash_' . $type])) {
        $_SESSION['flash_' . $type] = [];
    }
    $_SESSION['flash_' . $type][] = $message;
}

/**
 * Redirect helper
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        flash($message, $type);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Generate CSRF token field
 */
function csrfField() {
    return CSRFProtection::getTokenField();
}

/**
 * Escape HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get file size in human readable format
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Format bytes in human readable format (alias for formatFileSize)
 */
function formatBytes($bytes) {
    if ($bytes === null || $bytes === '') {
        return 'N/A';
    }
    return formatFileSize($bytes);
}

/**
 * Check if string is valid JSON
 */
function isJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

/**
 * Log activity
 */
function logActivity($action, $description = null, $tableName = null, $recordId = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, description, table_name, record_id, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        // Handle array to string conversion for description
        if (is_array($description)) {
            $description = json_encode($description);
        }
        
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $description,
            $tableName,
            $recordId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

/**
 * Send notification
 */
function sendNotification($userId, $title, $message, $type = 'info', $url = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, url, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$userId, $title, $message, $type, $url]);
        
        return true;
    } catch (Exception $e) {
        error_log("Notification sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get application version
 */
function getAppVersion() {
    return APP_VERSION ?? '1.0.0';
}

/**
 * Check if maintenance mode is enabled
 */
function isMaintenanceMode() {
    return defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === true;
}

/**
 * Check if IP is allowed during maintenance
 */
function isMaintenanceAllowedIP($ip) {
    $allowedIPs = MAINTENANCE_ALLOWED_IPS ?? ['127.0.0.1'];
    return in_array($ip, $allowedIPs);
}

/**
 * Get procurement order status badge class
 */
function getProcurementStatusBadgeClass($status) {
    $classes = [
        'Draft' => 'bg-secondary',
        'Pending' => 'bg-warning',
        'Reviewed' => 'bg-info',
        'For Revision' => 'bg-warning',
        'Approved' => 'bg-success',
        'Rejected' => 'bg-danger',
        'Scheduled for Delivery' => 'bg-primary',
        'In Transit' => 'bg-info',
        'Delivered' => 'bg-success',
        'Received' => 'bg-dark'
    ];
    
    return $classes[$status] ?? 'bg-secondary';
}

/**
 * Calculate procurement order totals
 */
function calculateProcurementTotals($items) {
    $subtotal = 0;
    $vatAmount = 0;
    $ewtAmount = 0;
    $netTotal = 0;
    
    foreach ($items as $item) {
        $itemSubtotal = $item['quantity'] * $item['unit_price'];
        $subtotal += $itemSubtotal;
    }
    
    return [
        'subtotal' => $subtotal,
        'vat_amount' => $vatAmount,
        'ewt_amount' => $ewtAmount,
        'net_total' => $netTotal
    ];
}

/**
 * Generate PO number
 */
function generatePONumber() {
    $year = date('Y');
    $month = date('m');
    
    // Get the last PO number for this year/month
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT po_number FROM procurement_orders WHERE po_number LIKE ? ORDER BY po_number DESC LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute(["PO{$year}{$month}%"]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Extract sequence number and increment
        $lastNumber = (int)substr($result['po_number'], -4);
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }
    
    return "PO{$year}{$month}" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
}

/**
 * Check if procurement order can be edited
 */
function canEditProcurementOrder($order, $userRole) {
    if ($userRole === 'System Admin') return true;
    if ($userRole === 'Procurement Officer' && in_array($order['status'], ['Draft', 'For Revision'])) return true;
    return false;
}

/**
 * Check if procurement order can be approved
 */
function canApproveProcurementOrder($order, $userRole) {
    if ($userRole === 'System Admin') return true;
    if ($userRole === 'Finance Director' && in_array($order['status'], ['Pending', 'Reviewed'])) return true;
    return false;
}

/**
 * Check if procurement order can be received
 */
function canReceiveProcurementOrder($order, $userRole) {
    if ($userRole === 'System Admin') return true;
    if (in_array($userRole, ['Warehouseman', 'Asset Director', 'Site Inventory Clerk', 'Project Manager']) && 
        $order['delivery_status'] === 'Delivered') return true;
    return false;
}

/**
 * Get delivery status badge class
 */
function getDeliveryStatusBadgeClass($status) {
    $classes = [
        'Pending' => 'bg-secondary',
        'Scheduled' => 'bg-warning',
        'In Transit' => 'bg-info',
        'Delivered' => 'bg-success',
        'Received' => 'bg-dark',
        'Partial' => 'bg-warning'
    ];
    
    return $classes[$status] ?? 'bg-secondary';
}

/**
 * Get delivery method options
 */
function getDeliveryMethodOptions($procurementOrderId = null) {
    $physicalOptions = [
        'Pickup' => 'Pickup',
        'Direct Delivery' => 'Direct Delivery',
        'Batch Delivery' => 'Batch Delivery',
        'Airfreight' => 'Airfreight',
        'Bus Cargo' => 'Bus Cargo',
        'Courier' => 'Courier',
        'Other' => 'Other'
    ];
    
    $serviceOptions = [
        'On-site Service' => 'On-site Service',
        'Remote Service' => 'Remote Service',
        'Digital Delivery' => 'Digital Delivery',
        'Email Delivery' => 'Email Delivery',
        'Postal Mail' => 'Postal Mail',
        'Office Pickup' => 'Office Pickup',
        'Service Completion' => 'Service Completion',
        'N/A' => 'N/A'
    ];
    
    // If no procurement order specified, return physical options (backward compatibility)
    if (!$procurementOrderId) {
        return $physicalOptions;
    }
    
    // Analyze categories in the procurement order
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "
            SELECT DISTINCT c.generates_assets, c.asset_type, c.expense_category
            FROM procurement_items pi
            LEFT JOIN categories c ON pi.category_id = c.id
            WHERE pi.procurement_order_id = ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$procurementOrderId]);
        $categories = $stmt->fetchAll();
        
        $hasPhysicalItems = false;
        $hasServiceItems = false;
        
        foreach ($categories as $category) {
            if ($category['generates_assets']) {
                $hasPhysicalItems = true;
            } else {
                $hasServiceItems = true;
            }
        }
        
        if ($hasPhysicalItems && $hasServiceItems) {
            // Mixed order - return both options
            return array_merge($physicalOptions, $serviceOptions);
        } elseif ($hasServiceItems) {
            return $serviceOptions;
        } else {
            return $physicalOptions;
        }
        
    } catch (Exception $e) {
        error_log("Error analyzing delivery options: " . $e->getMessage());
        return $physicalOptions; // Fallback to physical options
    }
}

/**
 * Get delivery location options
 */
function getDeliveryLocationOptions($procurementOrderId = null) {
    $physicalLocations = [
        'Project Site' => 'Project Site',
        'Main Office' => 'Main Office', 
        'Branch Office' => 'Branch Office',
        'Warehouse' => 'Warehouse',
        'Vendor Location' => 'Vendor Location',
        'Other' => 'Other (specify in notes)'
    ];
    
    $serviceLocations = [
        'Project Site' => 'Project Site',
        'Client Office' => 'Client Office',
        'Service Provider Office' => 'Service Provider Office',
        'Digital/Email' => 'Digital/Email',
        'Multiple Locations' => 'Multiple Locations',
        'N/A' => 'N/A'
    ];
    
    // If no procurement order specified, return physical locations (backward compatibility)
    if (!$procurementOrderId) {
        return $physicalLocations;
    }
    
    // Analyze categories in the procurement order
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "
            SELECT DISTINCT c.generates_assets, c.asset_type, c.expense_category
            FROM procurement_items pi
            LEFT JOIN categories c ON pi.category_id = c.id
            WHERE pi.procurement_order_id = ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$procurementOrderId]);
        $categories = $stmt->fetchAll();
        
        $hasPhysicalItems = false;
        $hasServiceItems = false;
        
        foreach ($categories as $category) {
            if ($category['generates_assets']) {
                $hasPhysicalItems = true;
            } else {
                $hasServiceItems = true;
            }
        }
        
        if ($hasPhysicalItems && $hasServiceItems) {
            // Mixed order - return both options
            return array_merge($physicalLocations, $serviceLocations);
        } elseif ($hasServiceItems) {
            return $serviceLocations;
        } else {
            return $physicalLocations;
        }
        
    } catch (Exception $e) {
        error_log("Error analyzing delivery location options: " . $e->getMessage());
        return $physicalLocations; // Fallback to physical locations
    }
}

/**
 * Get discrepancy type options
 */
function getDiscrepancyTypeOptions() {
    return [
        'Missing Items' => 'Missing Items',
        'Damaged Items' => 'Damaged Items',
        'Wrong Items' => 'Wrong Items',
        'Quantity Mismatch' => 'Quantity Mismatch',
        'Quality Issues' => 'Quality Issues',
        'Other' => 'Other'
    ];
}

/**
 * Check if delivery is overdue
 */
function isDeliveryOverdue($scheduledDate, $deliveryStatus) {
    if (empty($scheduledDate)) return false;
    if (in_array($deliveryStatus, ['Delivered', 'Received'])) return false;
    
    return strtotime($scheduledDate) < time();
}

/**
 * Calculate days overdue
 */
function getDaysOverdue($scheduledDate) {
    if (empty($scheduledDate)) return 0;
    return max(0, floor((time() - strtotime($scheduledDate)) / (24 * 60 * 60)));
}

/**
 * Format delivery date with status indicator
 */
function formatDeliveryDate($date, $status = null) {
    if (empty($date)) return 'Not scheduled';
    
    $formatted = date('M j, Y', strtotime($date));
    
    if ($status === 'Delivered' || $status === 'Received') {
        return '<span class="text-success">' . $formatted . '</span>';
    } elseif (isDeliveryOverdue($date, $status)) {
        $daysOverdue = getDaysOverdue($date);
        return '<span class="text-danger">' . $formatted . ' <small>(' . $daysOverdue . ' days overdue)</small></span>';
    }
    
    return $formatted;
}

/**
 * Get delivery alert severity
 */
function getDeliveryAlertSeverity($alertType) {
    $severities = [
        'Overdue' => 'danger',
        'Discrepancy' => 'warning',
        'Alert' => 'info'
    ];
    
    return $severities[$alertType] ?? 'secondary';
}

/**
 * Check if user can schedule delivery
 */
function canScheduleDelivery($order, $userRole) {
    if ($userRole === 'System Admin') return true;
    if (in_array($userRole, ['Procurement Officer', 'Asset Director']) && $order['status'] === 'Approved') return true;
    return false;
}

/**
 * Check if user can update delivery status
 */
function canUpdateDeliveryStatus($order, $userRole) {
    if ($userRole === 'System Admin') return true;
    if (in_array($userRole, ['Procurement Officer', 'Warehouseman', 'Asset Director']) && 
        in_array($order['delivery_status'], ['Scheduled', 'In Transit', 'Delivered'])) return true;
    return false;
}

/**
 * Get delivery tracking timeline
 */
function getDeliveryTimeline($trackingHistory) {
    $timeline = [];
    
    foreach ($trackingHistory as $entry) {
        $timeline[] = [
            'date' => $entry['created_at'],
            'status' => $entry['status'],
            'updated_by' => $entry['updated_by_name'],
            'notes' => $entry['notes'],
            'icon' => getDeliveryStatusIcon($entry['status']),
            'class' => getDeliveryStatusBadgeClass($entry['status'])
        ];
    }
    
    return $timeline;
}

/**
 * Get delivery status icon
 */
function getDeliveryStatusIcon($status) {
    $icons = [
        'Scheduled' => 'bi-calendar-check',
        'In Transit' => 'bi-truck',
        'Delivered' => 'bi-box-seam',
        'Received' => 'bi-check-circle',
        'Discrepancy Reported' => 'bi-exclamation-triangle',
        'Resolved' => 'bi-check2-all'
    ];
    
    return $icons[$status] ?? 'bi-circle';
}

/**
 * Validate delivery data
 */
function validateDeliveryData($data, $procurementOrderId = null) {
    $errors = [];
    
    // Always validate scheduled date
    if (empty($data['scheduled_date'])) {
        $errors[] = 'Scheduled delivery/completion date is required';
    } elseif (strtotime($data['scheduled_date']) <= time()) {
        $errors[] = 'Scheduled delivery/completion date must be in the future';
    }
    
    // Category-aware validation for delivery method and location
    $requiresPhysicalDelivery = true;
    
    if ($procurementOrderId) {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "
                SELECT DISTINCT c.generates_assets, c.asset_type, c.expense_category
                FROM procurement_items pi
                LEFT JOIN categories c ON pi.category_id = c.id
                WHERE pi.procurement_order_id = ?
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            $categories = $stmt->fetchAll();
            
            $hasPhysicalItems = false;
            $hasServiceItems = false;
            
            foreach ($categories as $category) {
                if ($category['generates_assets']) {
                    $hasPhysicalItems = true;
                } else {
                    $hasServiceItems = true;
                }
            }
            
            // If order contains only services, physical delivery may not be required
            if ($hasServiceItems && !$hasPhysicalItems) {
                $requiresPhysicalDelivery = false;
            }
            
        } catch (Exception $e) {
            error_log("Error validating delivery data: " . $e->getMessage());
            // Default to requiring physical delivery on error
        }
    }
    
    // Validate delivery method
    if (empty($data['delivery_method'])) {
        if ($requiresPhysicalDelivery) {
            $errors[] = 'Delivery method is required';
        } else {
            $errors[] = 'Service delivery method is required';
        }
    } else {
        // Validate that selected method is appropriate for the category mix
        $serviceDeliveryMethods = ['On-site Service', 'Remote Service', 'Digital Delivery', 'Email Delivery', 'Postal Mail', 'Office Pickup', 'Service Completion', 'N/A'];
        $physicalDeliveryMethods = ['Pickup', 'Direct Delivery', 'Batch Delivery', 'Airfreight', 'Bus Cargo', 'Courier', 'Other'];
        
        if (!$requiresPhysicalDelivery && in_array($data['delivery_method'], $physicalDeliveryMethods)) {
            // Service-only order but physical delivery method selected
            if ($data['delivery_method'] !== 'Pickup' && $data['delivery_method'] !== 'Other') {
                $errors[] = 'Selected delivery method is not appropriate for service-only orders';
            }
        }
    }
    
    // Validate delivery location
    if (empty($data['delivery_location'])) {
        if ($requiresPhysicalDelivery) {
            $errors[] = 'Delivery location is required';
        } else {
            $errors[] = 'Service location is required';
        }
    } else {
        // Validate that selected location is appropriate
        if (!$requiresPhysicalDelivery && $data['delivery_location'] === 'Warehouse') {
            $errors[] = 'Warehouse location is not appropriate for service-only orders';
        }
    }
    
    return $errors;
}

/**
 * Check if user can generate assets
 */
function canGenerateAssets($order, $userRole) {
    if ($userRole === 'System Admin') return true;
    if (in_array($userRole, ['Asset Director', 'Procurement Officer', 'Warehouseman', 'Site Inventory Clerk']) && 
        in_array($order['status'], ['Received', 'Delivered'])) return true;
    return false;
}

/**
 * Check if user can resolve discrepancies
 */
function canResolveDiscrepancy($order, $userRole) {
    if ($userRole === 'System Admin') return true;
    if (in_array($userRole, ['Procurement Officer', 'Warehouseman']) && 
        !empty($order['delivery_discrepancy_notes'])) return true;
    return false;
}

/**
 * Check if user can create PO from request
 */
function canCreatePOFromRequest($request, $userRole) {
    $roleConfig = require APP_ROOT . '/config/roles.php';
    $allowedRoles = $roleConfig['procurement-orders/createFromRequest'] ?? [];
    return in_array($userRole, $allowedRoles) && $request['status'] === 'Approved' && empty($request['procurement_id']);
}

/**
 * Check if user can review requests
 */
function canReviewRequest($request, $userRole) {
    if ($userRole === 'System Admin') return true;
    if (in_array($userRole, ['Asset Director', 'Finance Director']) && 
        in_array($request['status'], ['Submitted', 'Reviewed'])) return true;
    return false;
}

/**
 * Check if user can approve requests
 */
function canApproveRequest($request, $userRole) {
    if ($userRole === 'System Admin') return true;
    if (in_array($userRole, ['Finance Director', 'Procurement Officer']) && 
        in_array($request['status'], ['Reviewed', 'Forwarded'])) return true;
    return false;
}

/**
 * Get allowed request types for user role
 */
function getAllowedRequestTypes($userRole) {
    $allTypes = ['Material', 'Tool', 'Equipment', 'Service', 'Petty Cash', 'Restock', 'Other'];

    switch ($userRole) {
        case 'Site Inventory Clerk':
            return ['Material', 'Tool', 'Restock'];
        case 'Project Manager':
            return array_diff($allTypes, ['Petty Cash']);
        case 'System Admin':
        case 'Asset Director':
        case 'Finance Director':
        case 'Procurement Officer':
        case 'Warehouseman':
            return $allTypes;
        default:
            return [];
    }
}

/**
 * Validate request creation permissions
 */
function validateRequestCreation($data, $userRole) {
    $errors = [];
    
    $allowedTypes = getAllowedRequestTypes($userRole);
    if (!in_array($data['request_type'], $allowedTypes)) {
        $errors[] = 'You are not authorized to create requests of type: ' . $data['request_type'];
    }
    
    return $errors;
}

/**
 * Get delivery performance summary
 */
function getDeliveryPerformanceSummary($metrics) {
    $summary = [];
    
    if ($metrics['total_orders'] > 0) {
        $summary['completion_rate'] = round(($metrics['completed_deliveries'] / $metrics['total_orders']) * 100, 1);
        $summary['overdue_rate'] = round(($metrics['overdue_deliveries'] / $metrics['total_orders']) * 100, 1);
        $summary['discrepancy_rate'] = round(($metrics['deliveries_with_discrepancies'] / $metrics['total_orders']) * 100, 1);
    } else {
        $summary['completion_rate'] = 0;
        $summary['overdue_rate'] = 0;
        $summary['discrepancy_rate'] = 0;
    }
    
    return $summary;
}

/**
 * Can Project Manager review or forward a request?
 */
function canProjectManagerReviewRequest($request, $user) {
    if ($user['role_name'] !== 'Project Manager') return false;
    // Can review/forward if assigned to the project and status is Submitted
    return (
        $request['status'] === 'Submitted' &&
        ($request['project_manager_id'] ?? null) == $user['id']
    );
}

/**
 * Can Project Manager approve a request?
 */
function canProjectManagerApproveRequest($request, $user) {
    if ($user['role_name'] !== 'Project Manager') return false;
    // Can approve if assigned to the project and status is Reviewed or Forwarded
    return (
        in_array($request['status'], ['Reviewed', 'Forwarded']) &&
        ($request['project_manager_id'] ?? null) == $user['id']
    );
}

/**
 * Can Asset Director review/approve/decline?
 */
function canAssetDirectorReviewRequest($request, $user) {
    return $user['role_name'] === 'Asset Director' && in_array($request['status'], ['Submitted', 'Reviewed']);
}

/**
 * Can Finance Director approve/decline?
 */
function canFinanceDirectorApproveRequest($request, $user) {
    return $user['role_name'] === 'Finance Director' && in_array($request['status'], ['Reviewed', 'Forwarded']);
}

/**
 * Can Procurement Officer create PO?
 */
function canProcurementOfficerCreatePO($request, $user) {
    return $user['role_name'] === 'Procurement Officer' && $request['status'] === 'Approved' && empty($request['procurement_id']);
}

/**
 * Can Warehouseman confirm receipt?
 */
function canWarehousemanConfirmReceipt($order, $user) {
    return $user['role_name'] === 'Warehouseman' && $order['delivery_status'] === 'Delivered';
}

/**
 * Can Warehouseman resolve discrepancy?
 */
function canWarehousemanResolveDiscrepancy($order, $user) {
    return $user['role_name'] === 'Warehouseman' && !empty($order['delivery_discrepancy_notes']);
}

/**
 * Can Procurement Officer resolve discrepancy?
 */
function canProcurementOfficerResolveDiscrepancy($order, $user) {
    return $user['role_name'] === 'Procurement Officer' && !empty($order['delivery_discrepancy_notes']);
}

/**
 * Can Asset Director/Procurement Officer generate assets?
 */
function canGenerateAssetsButton($order, $user) {
    return in_array($user['role_name'], ['System Admin', 'Asset Director', 'Procurement Officer', 'Warehouseman']) && $order['status'] === 'Received';
}

// --- TRANSFERS ---
function canMakeTransfer($transfer, $user) {
    return hasPermission('transfers/create');
}

function canVerifyTransfer($transfer, $user) {
    if (!hasPermission('transfers/verify') || $transfer['status'] !== 'Pending Verification') {
        return false;
    }
    
    // System Admin can verify all transfers
    if ($user['role_name'] === 'System Admin') {
        return true;
    }
    
    // Project Manager can only verify if assigned to the project owning the asset
    if ($user['role_name'] === 'Project Manager') {
        return ($transfer['from_project_manager_id'] ?? null) == $user['id'];
    }
    
    // Other roles (Asset Director, Finance Director) can verify all
    return true;
}

function canAuthorizeTransfer($transfer, $user) {
    return hasPermission('transfers/approve') && $transfer['status'] === 'Pending Approval';
}

function canDispatchTransfer($transfer, $user) {
    if (!hasPermission('transfers/dispatch') || $transfer['status'] !== 'Approved') {
        return false;
    }

    // System Admin can dispatch all transfers
    if ($user['role_name'] === 'System Admin') {
        return true;
    }

    // FROM Project Manager can dispatch
    if ($user['role_name'] === 'Project Manager') {
        return ($transfer['from_project_manager_id'] ?? null) == $user['id'];
    }

    // Other authorized roles can dispatch
    return true;
}

function canReceiveTransfer($transfer, $user) {
    if (!hasPermission('transfers/receive') || $transfer['status'] !== 'In Transit') {
        return false;
    }

    // System Admin can receive all transfers
    if ($user['role_name'] === 'System Admin') {
        return true;
    }

    // Finance/Asset Directors can receive all transfers
    if (in_array($user['role_name'], ['Finance Director', 'Asset Director'])) {
        return true;
    }

    // TO Project Manager ONLY - receive transfers to their project
    if ($user['role_name'] === 'Project Manager') {
        // If TO project has a PM assigned, only that PM can receive
        if (!empty($transfer['to_project_manager_id'])) {
            return $transfer['to_project_manager_id'] == $user['id'];
        }

        // If TO project has NO PM assigned, any PM can receive it
        // But exclude the FROM project manager to avoid confusion
        if (empty($transfer['to_project_manager_id'])) {
            // Don't allow FROM PM to receive
            if (($transfer['from_project_manager_id'] ?? null) == $user['id']) {
                return false;
            }
            // Allow any other PM to receive
            return true;
        }

        return false;
    }

    // For other roles, deny by default for safety
    return false;
}

function canCompleteTransfer($transfer, $user) {
    return hasPermission('transfers/complete') && $transfer['status'] === 'Received';
}

function canReturnAsset($transfer, $user) {
    return hasPermission('transfers/returnAsset') && 
           $transfer['status'] === 'Completed' && 
           $transfer['transfer_type'] === 'temporary' && 
           ($transfer['return_status'] ?? 'not_returned') === 'not_returned';
}

function canReceiveReturn($transfer, $user) {
    if (!hasPermission('transfers/receiveReturn') || 
        ($transfer['return_status'] ?? 'not_returned') !== 'in_return_transit') {
        return false;
    }
    
    // System Admin can receive all returns
    if ($user['role_name'] === 'System Admin') {
        return true;
    }
    
    // Project Manager can only receive returns if assigned to the origin project
    if ($user['role_name'] === 'Project Manager') {
        return ($transfer['from_project_manager_id'] ?? null) == $user['id'];
    }
    
    // Other roles (Asset Director, Finance Director) can receive all returns
    return true;
}

function canCancelTransfer($transfer, $user) {
    return hasPermission('transfers/cancel') && 
           in_array($transfer['status'], ['Pending Verification', 'Pending Approval']);
}

// --- BORROWED TOOLS ---
function canMakeBorrowedTool($tool, $user) {
    return $user['role_name'] === 'Warehouseman';
}
function canVerifyBorrowedTool($tool, $user) {
    return $user['role_name'] === 'Project Manager' && $tool['project_manager_id'] == $user['id'] && $tool['status'] === 'Pending';
}
function canAuthorizeBorrowedTool($tool, $user) {
    return in_array($user['role_name'], ['Asset Director', 'Finance Director']) && $tool['status'] === 'For Approval';
}

// --- WITHDRAWALS ---
function canMakeWithdrawal($withdrawal, $user) {
    return $user['role_name'] === 'Warehouseman' && $withdrawal['project_id'] == $user['current_project_id'];
}
function canVerifyWithdrawal($withdrawal, $user) {
    $roleConfig = require APP_ROOT . '/config/roles.php';
    if ($user['role_name'] === 'System Admin') return true;
    return $withdrawal['status'] === 'Pending Verification' && in_array($user['role_name'], $roleConfig['withdrawals/verify'] ?? []);
}
function canApproveWithdrawal($withdrawal, $user) {
    $roleConfig = require APP_ROOT . '/config/roles.php';
    if ($user['role_name'] === 'System Admin') return true;
    return $withdrawal['status'] === 'Pending Approval' && in_array($user['role_name'], $roleConfig['withdrawals/approve'] ?? []);
}
function canReleaseWithdrawal($withdrawal, $user) {
    $roleConfig = require APP_ROOT . '/config/roles.php';
    if ($user['role_name'] === 'System Admin') return true;
    return $withdrawal['status'] === 'Approved' && in_array($user['role_name'], $roleConfig['withdrawals/release'] ?? []);
}
function canReturnWithdrawal($withdrawal, $user) {
    $roleConfig = require APP_ROOT . '/config/roles.php';
    if ($user['role_name'] === 'System Admin') return true;
    return $withdrawal['status'] === 'Released' && in_array($user['role_name'], $roleConfig['withdrawals/return'] ?? []);
}
function canCancelWithdrawal($withdrawal, $user) {
    $roleConfig = require APP_ROOT . '/config/roles.php';
    if ($user['role_name'] === 'System Admin') return true;
    $canCancelByRole = in_array($user['role_name'], $roleConfig['withdrawals/cancel'] ?? []);
    $canCancelByOwnership = $withdrawal['withdrawn_by'] == $user['id'];
    return ($canCancelByRole || $canCancelByOwnership) && in_array($withdrawal['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'Released']);
}

// --- INCIDENTS ---
function canMakeIncident($incident, $user) {
    return $user['role_name'] === 'Site Inventory Clerk';
}
function canVerifyIncident($incident, $user) {
    return $user['role_name'] === 'Project Manager' && $incident['status'] === 'Pending Verification';
}
function canAuthorizeIncident($incident, $user) {
    return $user['role_name'] === 'Asset Director' && in_array($incident['status'], ['Pending Authorization', 'Authorized']);
}

// --- MAINTENANCE ---
function canMakeMaintenance($maintenance, $user) {
    return in_array($user['role_name'], ['Warehouseman', 'Site Inventory Clerk']) && $maintenance['project_id'] == $user['current_project_id'];
}
function canVerifyMaintenance($maintenance, $user) {
    return $user['role_name'] === 'Project Manager' && $maintenance['project_manager_id'] == $user['id'] && $maintenance['status'] === 'Requested';
}
function canAuthorizeMaintenance($maintenance, $user) {
    return $user['role_name'] === 'Asset Director' && $maintenance['status'] === 'For Approval';
}

// --- ASSETS ---
function canMakeAsset($asset, $user) {
    return in_array($user['role_name'], ['Procurement Officer', 'Warehouseman']);
}
function canVerifyAsset($asset, $user) {
    return $user['role_name'] === 'Asset Director' && $asset['status'] === 'Pending Verification';
}
function canAuthorizeAsset($asset, $user) {
    return $user['role_name'] === 'System Admin' && $asset['status'] === 'For Approval';
}
?>
