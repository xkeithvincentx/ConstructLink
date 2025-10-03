<?php
/**
 * ConstructLink™ Routes Configuration
 * Centralized route definitions with MVA RBAC using roles.php
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Load central MVA roles config
$roleConfig = require APP_ROOT . '/config/roles.php';

/**
 * Helper function to retrieve allowed roles for a specific route
 * Supports both direct role arrays and MVA role structures
 */
if (!function_exists('getRolesFor')) {
    function getRolesFor($routeKey) {
        global $roleConfig;
        
        // Direct route mapping (e.g., 'requests/create' => ['Role1', 'Role2'])
        if (isset($roleConfig[$routeKey]) && is_array($roleConfig[$routeKey]) && !isset($roleConfig[$routeKey]['maker'])) {
            return $roleConfig[$routeKey];
        }
        
        // MVA structure mapping (e.g., 'requests' => ['maker' => [...], 'verifier' => [...]])
        if (isset($roleConfig[$routeKey]) && is_array($roleConfig[$routeKey])) {
            $roles = [];
            foreach (['maker', 'verifier', 'authorizer', 'viewer'] as $mvaRole) {
                if (isset($roleConfig[$routeKey][$mvaRole])) {
                    $roles = array_merge($roles, $roleConfig[$routeKey][$mvaRole]);
                }
            }
            return array_unique($roles);
        }
        
        // Fallback: System Admin always has access
        return ['System Admin'];
    }
}

$routes = [
    // =================================================================
    // AUTHENTICATION ROUTES (No Auth Required)
    // =================================================================
    'login' => [
        'controller' => 'AuthController',
        'action' => 'login',
        'auth' => false
    ],
    'logout' => [
        'controller' => 'AuthController',
        'action' => 'logout',
        'auth' => false
    ],
    'forgot-password' => [
        'controller' => 'AuthController',
        'action' => 'forgotPassword',
        'auth' => false
    ],
    'reset-password' => [
        'controller' => 'AuthController',
        'action' => 'resetPassword',
        'auth' => false
    ],
    'check-auth' => [
        'controller' => 'AuthController',
        'action' => 'checkAuth',
        'auth' => false
    ],
    'change-password' => [
        'controller' => 'AuthController',
        'action' => 'changePassword',
        'auth' => true,
        'roles' => getRolesFor('change-password')
    ],
    'notifications' => [
        'controller' => 'NotificationController',
        'action' => 'index',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk', 'Site Admin']
    ],

    // =================================================================
    // DASHBOARD
    // =================================================================
    '' => [
        'controller' => 'DashboardController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('dashboard')
    ],
    'dashboard' => [
        'controller' => 'DashboardController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('dashboard')
    ],
    'dashboard/getStats' => [
        'controller' => 'DashboardController',
        'action' => 'getStats',
        'auth' => true,
        'roles' => getRolesFor('dashboard/getStats')
    ],

    // =================================================================
    // 1. 🔧 REQUESTS (MVA: Site Inventory Clerk/Site Admin → Project Manager → Finance Director)
    // =================================================================
    'requests' => [
        'controller' => 'RequestController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('requests/view')
    ],
    'requests/create' => [
        'controller' => 'RequestController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('requests/create')
    ],
    'requests/view' => [
        'controller' => 'RequestController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('requests/view')
    ],
    'requests/review' => [
        'controller' => 'RequestController',
        'action' => 'review',
        'auth' => true,
        'roles' => getRolesFor('requests/review')
    ],
    'requests/approve' => [
        'controller' => 'RequestController',
        'action' => 'approve',
        'auth' => true,
        'roles' => getRolesFor('requests/approve')
    ],
    'requests/submit' => [
        'controller' => 'RequestController',
        'action' => 'submit',
        'auth' => true,
        'roles' => getRolesFor('requests/create')
    ],
    'requests/create-with-quote' => [
        'controller' => 'RequestController',
        'action' => 'createWithQuote',
        'auth' => true,
        'roles' => getRolesFor('requests/create-with-quote')
    ],
    'requests/verify-quote' => [
        'controller' => 'RequestController',
        'action' => 'verifyQuote',
        'auth' => true,
        'roles' => getRolesFor('requests/verify-quote')
    ],
    'requests/generate-po' => [
        'controller' => 'RequestController',
        'action' => 'generatePO',
        'auth' => true,
        'roles' => getRolesFor('requests/generate-po')
    ],
    'requests/approve-payment' => [
        'controller' => 'RequestController',
        'action' => 'approvePayment',
        'auth' => true,
        'roles' => getRolesFor('requests/approve-payment')
    ],
    'requests/export' => [
        'controller' => 'RequestController',
        'action' => 'export',
        'auth' => true,
        'roles' => ['System Admin', 'Asset Director', 'Finance Director', 'Procurement Officer']
    ],
    'requests/getStats' => [
        'controller' => 'RequestController',
        'action' => 'getStats',
        'auth' => true,
        'roles' => ['System Admin', 'Asset Director', 'Finance Director', 'Procurement Officer', 'Project Manager']
    ],
    'requests/getPendingRequests' => [
        'controller' => 'RequestController',
        'action' => 'getPendingRequests',
        'auth' => true,
        'roles' => ['System Admin', 'Asset Director', 'Finance Director', 'Procurement Officer', 'Project Manager']
    ],

    // =================================================================
    // 2. 🛒 PROCUREMENT ORDERS (MVA: Procurement Officer → Asset Director → Finance Director)
    // =================================================================
    'procurement-orders' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/view')
    ],
    'procurement-orders/create' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/create')
    ],
    'procurement-orders/create-retrospective' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'createRetrospective',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/create')
    ],
    'procurement-orders/edit' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'edit',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/edit')
    ],
    'procurement-orders/view' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/view')
    ],
    'procurement-orders/file' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'serveFile',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/view')
    ],
    'procurement-orders/preview' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'previewFile',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/view')
    ],
    'procurement-orders/verify' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'verify',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/verify')
    ],
    'procurement-orders/approve' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'approve',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/approve')
    ],
    'procurement-orders/submit-for-approval' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'submitForApproval',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/create')
    ],
    'procurement-orders/createFromRequest' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'createFromRequest',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/create')
    ],
    'procurement-orders/approved-requests' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'getApprovedRequests',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/create')
    ],

    // =================================================================
    // 3. 📦 RECEIVING & DELIVERY (MVA: Warehouseman → Site Inventory Clerk → Project Manager)
    // =================================================================
    'procurement-orders/receive' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'receive',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/receive')
    ],
    'procurement-orders/verify-receipt' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'verifyReceipt',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/verify-receipt')
    ],
    'procurement-orders/confirm-receipt' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'confirmReceipt',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/confirm-receipt')
    ],
    'procurement-orders/flag-discrepancy' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'flagDiscrepancy',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/flag-discrepancy')
    ],
    'procurement-orders/resolve-discrepancy' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'resolveDiscrepancy',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/resolve-discrepancy')
    ],
    'procurement-orders/schedule-delivery' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'scheduleDelivery',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/schedule-delivery')
    ],
    'procurement-orders/update-delivery' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'updateDeliveryStatus',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/update-delivery')
    ],
    'procurement-orders/confirm-delivery' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'confirmDelivery',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/confirm-delivery')
    ],
    'procurement-orders/acknowledge-fulfillment' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'acknowledgeFulfillment',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/acknowledge-fulfillment')
    ],
    'procurement-orders/delivery-performance' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'deliveryPerformance',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/delivery-performance')
    ],
    'procurement-orders/delivery-management' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'deliveryManagement',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/delivery-management')
    ],
    'procurement-orders/performance-dashboard' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'performanceDashboard',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/performance-dashboard')
    ],
    'procurement-orders/ready-for-delivery' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'readyForDelivery',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/ready-for-delivery')
    ],
    'procurement-orders/for-receipt' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'forReceipt',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/for-receipt')
    ],
    'procurement-orders/cancel' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'cancel',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/cancel')
    ],

    // =================================================================
    // 4. 🔁 TRANSFERS (MVA: Site Inventory Clerk → Project Manager → Finance Director/Asset Director)
    // =================================================================
    // Transfers module routes with centralized RBAC
    'transfers' => [
        'controller' => 'TransferController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('transfers/view')
    ],
    'transfers/create' => [
        'controller' => 'TransferController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('transfers/create')
    ],
    'transfers/verify' => [
        'controller' => 'TransferController',
        'action' => 'verify',
        'auth' => true,
        'roles' => getRolesFor('transfers/verify')
    ],
    'transfers/approve' => [
        'controller' => 'TransferController',
        'action' => 'approve',
        'auth' => true,
        'roles' => getRolesFor('transfers/approve')
    ],
    'transfers/dispatch' => [
        'controller' => 'TransferController',
        'action' => 'dispatch',
        'auth' => true,
        'roles' => getRolesFor('transfers/dispatch')
    ],
    'transfers/receive' => [
        'controller' => 'TransferController',
        'action' => 'receive',
        'auth' => true,
        'roles' => getRolesFor('transfers/receive')
    ],
    'transfers/complete' => [
        'controller' => 'TransferController',
        'action' => 'complete',
        'auth' => true,
        'roles' => getRolesFor('transfers/complete')
    ],
    'transfers/cancel' => [
        'controller' => 'TransferController',
        'action' => 'cancel',
        'auth' => true,
        'roles' => getRolesFor('transfers/cancel')
    ],
    'transfers/returnAsset' => [
        'controller' => 'TransferController',
        'action' => 'returnAsset',
        'auth' => true,
        'roles' => getRolesFor('transfers/returnAsset')
    ],
    'transfers/receive-return' => [
        'controller' => 'TransferController',
        'action' => 'receiveReturn',
        'auth' => true,
        'roles' => getRolesFor('transfers/receiveReturn')
    ],
    'transfers/view' => [
        'controller' => 'TransferController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('transfers/view')
    ],

    // =================================================================
    // 5. 🛠 BORROWED TOOLS (MVA: Warehouseman → Project Manager → Asset Director/Finance Director)
    // =================================================================
    'borrowed-tools' => [
        'controller' => 'BorrowedToolController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('borrowed-tools/view')
    ],
    'borrowed-tools/create' => [
        'controller' => 'BorrowedToolController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('borrowed-tools/create')
    ],
    'api/borrowed-tools/validate-qr' => [
        'controller' => 'BorrowedToolController',
        'action' => 'validateQRForBorrowing',
        'auth' => true,
        'roles' => getRolesFor('borrowed-tools/create')
    ],
    'borrowed-tools/approve-critical' => [
        'controller' => 'BorrowedToolController',
        'action' => 'approveCritical',
        'auth' => true,
        'roles' => getRolesFor('borrowed-tools/approve-critical')
    ],
    'borrowed-tools/authorize-critical' => [
        'controller' => 'BorrowedToolController',
        'action' => 'authorizeCritical',
        'auth' => true,
        'roles' => getRolesFor('borrowed-tools/authorize-critical')
    ],
    'borrowed-tools/return' => [
        'controller' => 'BorrowedToolController',
        'action' => 'returnTool',
        'auth' => true,
        'roles' => getRolesFor('borrowed-tools/return')
    ],
    'borrowed-tools/view' => [
        'controller' => 'BorrowedToolController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('borrowed-tools/view')
    ],
    'borrowed-tools/verify' => [
        'controller' => 'BorrowedToolController',
        'action' => 'verify',
        'auth' => true,
        'roles' => getRolesFor('borrowed-tools/verify')
    ],
    'borrowed-tools/approve' => [
        'controller' => 'BorrowedToolController',
        'action' => 'approve',
        'auth' => true,
        'roles' => getRolesFor('borrowed-tools/approve')
    ],
    'borrowed-tools/borrow' => [
        'controller' => 'BorrowedToolController',
        'action' => 'borrow',
        'auth' => true,
        'roles' => getRolesFor('borrowed-tools/borrow')
    ],
    'borrowed-tools/extend' => [
        'controller' => 'BorrowedToolController',
        'action' => 'extend',
        'auth' => true,
        'roles' => getRolesFor('borrowed-tools/extend')
    ],
    'borrowed-tools/cancel' => [
        'controller' => 'BorrowedToolController',
        'action' => 'cancel',
        'auth' => true,
        'roles' => getRolesFor('borrowed-tools/cancel')
    ],

    // =================================================================
    // 6. 🔄 WITHDRAWALS (MVA: Warehouseman → Site Inventory Clerk → Project Manager)
    // =================================================================
    'withdrawals' => [
        'controller' => 'WithdrawalController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('withdrawals/view')
    ],
    'withdrawals/create' => [
        'controller' => 'WithdrawalController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('withdrawals/create')
    ],
    'withdrawals/verify' => [
        'controller' => 'WithdrawalController',
        'action' => 'verify',
        'auth' => true,
        'roles' => getRolesFor('withdrawals/verify')
    ],
    'withdrawals/approve' => [
        'controller' => 'WithdrawalController',
        'action' => 'approve',
        'auth' => true,
        'roles' => getRolesFor('withdrawals/approve')
    ],
    'withdrawals/release' => [
        'controller' => 'WithdrawalController',
        'action' => 'release',
        'auth' => true,
        'roles' => getRolesFor('withdrawals/release')
    ],
    'withdrawals/return' => [
        'controller' => 'WithdrawalController',
        'action' => 'return',
        'auth' => true,
        'roles' => getRolesFor('withdrawals/return')
    ],
    'withdrawals/view' => [
        'controller' => 'WithdrawalController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('withdrawals/view')
    ],
    'withdrawals/cancel' => [
        'controller' => 'WithdrawalController',
        'action' => 'cancel',
        'auth' => true,
        'roles' => getRolesFor('withdrawals/cancel')
    ],

    // =================================================================
    // 7. ⚠️ INCIDENTS (MVA: Site Inventory Clerk → Project Manager → Asset Director)
    // =================================================================
    'incidents' => [
        'controller' => 'IncidentController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('incidents/view')
    ],
    'incidents/create' => [
        'controller' => 'IncidentController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('incidents/create')
    ],
    'incidents/investigate' => [
        'controller' => 'IncidentController',
        'action' => 'investigate',
        'auth' => true,
        'roles' => getRolesFor('incidents/investigate')
    ],
    'incidents/resolve' => [
        'controller' => 'IncidentController',
        'action' => 'resolve',
        'auth' => true,
        'roles' => getRolesFor('incidents/resolve')
    ],
    'incidents/close' => [
        'controller' => 'IncidentController',
        'action' => 'close',
        'auth' => true,
        'roles' => getRolesFor('incidents/close')
    ],
    'incidents/cancel' => [
        'controller' => 'IncidentController',
        'action' => 'cancel',
        'auth' => true,
        'roles' => getRolesFor('incidents/cancel')
    ],
    'incidents/view' => [
        'controller' => 'IncidentController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('incidents/view')
    ],
    'incidents/export' => [
        'controller' => 'IncidentController',
        'action' => 'export',
        'auth' => true,
        'roles' => ['System Admin', 'Asset Director', 'Finance Director']
    ],

    // =================================================================
    // 8. 🧰 MAINTENANCE (MVA: Warehouseman/Site Inventory Clerk → Project Manager → Asset Director)
    // =================================================================
    'maintenance' => [
        'controller' => 'MaintenanceController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('maintenance/view')
    ],
    'maintenance/create' => [
        'controller' => 'MaintenanceController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('maintenance/create')
    ],
    'maintenance/verify' => [
        'controller' => 'MaintenanceController',
        'action' => 'verify',
        'auth' => true,
        'roles' => getRolesFor('maintenance/verify')
    ],
    'maintenance/authorize' => [
        'controller' => 'MaintenanceController',
        'action' => 'authorize',
        'auth' => true,
        'roles' => getRolesFor('maintenance/authorize')
    ],
    'maintenance/financial-approve' => [
        'controller' => 'MaintenanceController',
        'action' => 'financialApprove',
        'auth' => true,
        'roles' => getRolesFor('maintenance/financial-approve')
    ],
    'maintenance/complete' => [
        'controller' => 'MaintenanceController',
        'action' => 'complete',
        'auth' => true,
        'roles' => getRolesFor('maintenance/complete')
    ],
    'maintenance/view' => [
        'controller' => 'MaintenanceController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('maintenance/view')
    ],
    'maintenance/start' => [
        'controller' => 'MaintenanceController',
        'action' => 'start',
        'auth' => true,
        'roles' => getRolesFor('maintenance/start')
    ],
    'maintenance/cancel' => [
        'controller' => 'MaintenanceController',
        'action' => 'cancel',
        'auth' => true,
        'roles' => getRolesFor('maintenance/cancel')
    ],

    // =================================================================
    // ASSET MANAGEMENT
    // =================================================================
    'assets' => [
        'controller' => 'AssetController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('assets')
    ],
    'assets/create' => [
        'controller' => 'AssetController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('assets/create')
    ],
    'assets/edit' => [
        'controller' => 'AssetController',
        'action' => 'edit',
        'auth' => true,
        'roles' => getRolesFor('assets/edit')
    ],
    'assets/view' => [
        'controller' => 'AssetController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('assets/view')
    ],
    'assets/delete' => [
        'controller' => 'AssetController',
        'action' => 'delete',
        'auth' => true,
        'roles' => getRolesFor('assets/delete')
    ],
    'assets/verify-generation' => [
        'controller' => 'AssetController',
        'action' => 'verifyGeneration',
        'auth' => true,
        'roles' => getRolesFor('assets/verify-generation')
    ],
    'assets/approve-generation' => [
        'controller' => 'AssetController',
        'action' => 'approveGeneration',
        'auth' => true,
        'roles' => getRolesFor('assets/approve-generation')
    ],
    'assets/scanner' => [
        'controller' => 'AssetController',
        'action' => 'scanner',
        'auth' => true,
        'roles' => getRolesFor('assets/scanner')
    ],
    'procurement-orders/generateAssets' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'generateAssets',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/generateAssets')
    ],
    'assets/export' => [
        'controller' => 'AssetController',
        'action' => 'export',
        'auth' => true,
        'roles' => getRolesFor('assets/export')
    ],

    // =================================================================
    // LEGACY ASSET WORKFLOW ROUTES
    // =================================================================
    'assets/legacy-create' => [
        'controller' => 'AssetController',
        'action' => 'legacyCreate',
        'auth' => true,
        'roles' => getRolesFor('assets/legacy-create')
    ],
    'assets/verification-dashboard' => [
        'controller' => 'AssetController',
        'action' => 'verificationDashboard',
        'auth' => true,
        'roles' => getRolesFor('assets/legacy-verify')
    ],
    'assets/authorization-dashboard' => [
        'controller' => 'AssetController',
        'action' => 'authorizationDashboard',
        'auth' => true,
        'roles' => getRolesFor('assets/legacy-authorize')
    ],
    'assets/verify-asset' => [
        'controller' => 'AssetController',
        'action' => 'verifyAsset',
        'auth' => true,
        'roles' => getRolesFor('assets/legacy-verify')
    ],
    'assets/authorize-asset' => [
        'controller' => 'AssetController',
        'action' => 'authorizeAsset',
        'auth' => true,
        'roles' => getRolesFor('assets/legacy-authorize')
    ],
    'assets/batch-verify' => [
        'controller' => 'AssetController',
        'action' => 'batchVerify',
        'auth' => true,
        'roles' => getRolesFor('assets/legacy-verify')
    ],
    'assets/batch-authorize' => [
        'controller' => 'AssetController',
        'action' => 'batchAuthorize',
        'auth' => true,
        'roles' => getRolesFor('assets/legacy-authorize')
    ],
    
    // Enhanced Verification System API Routes
    'api/assets/verification-data' => [
        'controller' => 'AssetController',
        'action' => 'getVerificationData',
        'auth' => true,
        'roles' => getRolesFor('assets/legacy-verify')
    ],
    'api/assets/authorization-data' => [
        'controller' => 'AssetController',
        'action' => 'getAuthorizationData',
        'auth' => true,
        'roles' => getRolesFor('assets/authorize')
    ],
    'api/assets/validate-quality' => [
        'controller' => 'AssetController',
        'action' => 'validateAssetQuality',
        'auth' => true,
        'roles' => getRolesFor('assets/legacy-verify')
    ],
    'assets/reject-verification' => [
        'controller' => 'AssetController',
        'action' => 'rejectVerification',
        'auth' => true,
        'roles' => getRolesFor('assets/legacy-verify')
    ],
    'assets/approve-with-conditions' => [
        'controller' => 'AssetController',
        'action' => 'approveWithConditions',
        'auth' => true,
        'roles' => getRolesFor('assets/legacy-verify')
    ],

    // =================================================================
    // QR TAG MANAGEMENT ROUTES
    // =================================================================
    'assets/tag-management' => [
        'controller' => 'AssetTagController',
        'action' => 'tagManagement',
        'auth' => true,
        'roles' => ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Asset Director']
    ],
    'assets/print-tag' => [
        'controller' => 'AssetTagController',
        'action' => 'printTag',
        'auth' => true,
        'roles' => ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Asset Director']
    ],
    'assets/print-tags' => [
        'controller' => 'AssetTagController',
        'action' => 'printTags',
        'auth' => true,
        'roles' => ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Asset Director']
    ],
    'assets/tag-preview' => [
        'controller' => 'AssetTagController',
        'action' => 'tagPreview',
        'auth' => true,
        'roles' => ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Asset Director']
    ],
    'assets/test-pdf' => [
        'controller' => 'AssetTagController',
        'action' => 'testPDF',
        'auth' => true,
        'roles' => ['System Admin']
    ],
    'assets/assign-location' => [
        'controller' => 'AssetController',
        'action' => 'assignLocation',
        'auth' => true,
        'roles' => ['System Admin', 'Warehouseman', 'Site Inventory Clerk']
    ],

    // =================================================================
    // MASTER DATA MANAGEMENT
    // =================================================================
    'users' => [
        'controller' => 'UserController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('users')
    ],
    'users/create' => [
        'controller' => 'UserController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('users/create')
    ],
    'users/edit' => [
        'controller' => 'UserController',
        'action' => 'edit',
        'auth' => true,
        'roles' => getRolesFor('users/edit')
    ],
    'users/view' => [
        'controller' => 'UserController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('users/view')
    ],
    'users/delete' => [
        'controller' => 'UserController',
        'action' => 'delete',
        'auth' => true,
        'roles' => getRolesFor('users/delete')
    ],
    'users/profile' => [
        'controller' => 'UserController',
        'action' => 'profile',
        'auth' => true,
        'roles' => getRolesFor('users/profile')
    ],

    // Projects
    'projects' => [
        'controller' => 'ProjectController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('projects')
    ],
    'projects/create' => [
        'controller' => 'ProjectController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('projects/create')
    ],
    'projects/edit' => [
        'controller' => 'ProjectController',
        'action' => 'edit',
        'auth' => true,
        'roles' => getRolesFor('projects/edit')
    ],
    'projects/view' => [
        'controller' => 'ProjectController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('projects/view')
    ],

    // Vendors
    'vendors' => [
        'controller' => 'VendorController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('vendors')
    ],
    'vendors/create' => [
        'controller' => 'VendorController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('vendors/create')
    ],
    'vendors/edit' => [
        'controller' => 'VendorController',
        'action' => 'edit',
        'auth' => true,
        'roles' => getRolesFor('vendors/edit')
    ],
    'vendors/view' => [
        'controller' => 'VendorController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('vendors/view')
    ],
    'vendors/delete' => [
        'controller' => 'VendorController',
        'action' => 'delete',
        'auth' => true,
        'roles' => getRolesFor('vendors/delete')
    ],
    'vendors/toggleStatus' => [
        'controller' => 'VendorController',
        'action' => 'toggleStatus',
        'auth' => true,
        'roles' => getRolesFor('vendors/edit')
    ],
    'vendors/manageBanks' => [
        'controller' => 'VendorController',
        'action' => 'manageBanks',
        'auth' => true,
        'roles' => getRolesFor('vendors/edit')
    ],
    'vendors/export' => [
        'controller' => 'VendorController',
        'action' => 'export',
        'auth' => true,
        'roles' => getRolesFor('vendors/view')
    ],
    
    // =================================================================
    // VENDOR INTELLIGENCE FEATURES
    // =================================================================
    'vendors/intelligenceDashboard' => [
        'controller' => 'VendorController',
        'action' => 'intelligenceDashboard',
        'auth' => true,
        'roles' => getRolesFor('vendors/intelligence')
    ],
    'vendors/performanceAnalysis' => [
        'controller' => 'VendorController',
        'action' => 'performanceAnalysis',
        'auth' => true,
        'roles' => getRolesFor('vendors/intelligence')
    ],
    'vendors/vendorComparison' => [
        'controller' => 'VendorController',
        'action' => 'vendorComparison',
        'auth' => true,
        'roles' => getRolesFor('vendors/intelligence')
    ],
    'vendors/riskAssessment' => [
        'controller' => 'VendorController',
        'action' => 'riskAssessment',
        'auth' => true,
        'roles' => getRolesFor('vendors/intelligence')
    ],
    
    // =================================================================
    // VENDOR MVA WORKFLOW ROUTES
    // =================================================================
    'vendors/createWithWorkflow' => [
        'controller' => 'VendorController',
        'action' => 'createWithWorkflow',
        'auth' => true,
        'roles' => getRolesFor('vendors/create')
    ],
    'vendors/verifyCreation' => [
        'controller' => 'VendorController',
        'action' => 'verifyCreation',
        'auth' => true,
        'roles' => getRolesFor('vendors/verify')
    ],
    'vendors/authorizeCreation' => [
        'controller' => 'VendorController',
        'action' => 'authorizeCreation',
        'auth' => true,
        'roles' => getRolesFor('vendors/authorize')
    ],
    'vendors/workflowStatus' => [
        'controller' => 'VendorController',
        'action' => 'workflowStatus',
        'auth' => true,
        'roles' => getRolesFor('vendors/view')
    ],
    'vendors/pendingWorkflows' => [
        'controller' => 'VendorController',
        'action' => 'pendingWorkflows',
        'auth' => true,
        'roles' => getRolesFor('vendors/view')
    ],
    
    // =================================================================
    // VENDOR API ENDPOINTS
    // =================================================================
    'vendors/getPaymentTerms' => [
        'controller' => 'VendorController',
        'action' => 'getPaymentTerms',
        'auth' => true,
        'roles' => getRolesFor('vendors/view')
    ],
    'vendors/getByCategory' => [
        'controller' => 'VendorController',
        'action' => 'getByCategory',
        'auth' => true,
        'roles' => getRolesFor('vendors/view')
    ],
    'vendors/getForDropdown' => [
        'controller' => 'VendorController',
        'action' => 'getForDropdown',
        'auth' => true,
        'roles' => getRolesFor('vendors/view')
    ],
    'vendors/getStats' => [
        'controller' => 'VendorController',
        'action' => 'getStats',
        'auth' => true,
        'roles' => getRolesFor('vendors/view')
    ],
    'vendors/getVendorRecommendations' => [
        'controller' => 'VendorController',
        'action' => 'getVendorRecommendations',
        'auth' => true,
        'roles' => getRolesFor('vendors/intelligence')
    ],
    'vendors/getPerformanceData' => [
        'controller' => 'VendorController',
        'action' => 'getPerformanceData',
        'auth' => true,
        'roles' => getRolesFor('vendors/intelligence')
    ],
    'vendors/getRiskData' => [
        'controller' => 'VendorController',
        'action' => 'getRiskData',
        'auth' => true,
        'roles' => getRolesFor('vendors/intelligence')
    ],
    'vendors/getTrendData' => [
        'controller' => 'VendorController',
        'action' => 'getTrendData',
        'auth' => true,
        'roles' => getRolesFor('vendors/intelligence')
    ],
    'vendors/getIntelligenceDashboardData' => [
        'controller' => 'VendorController',
        'action' => 'getIntelligenceDashboardData',
        'auth' => true,
        'roles' => getRolesFor('vendors/intelligence')
    ],
    
    // =================================================================
    // INTELLIGENT VENDOR PRODUCT CATALOG
    // =================================================================
    'vendors/productCatalog' => [
        'controller' => 'VendorController',
        'action' => 'productCatalog',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']
    ],
    'vendors/getProductRecommendations' => [
        'controller' => 'VendorController',
        'action' => 'getProductRecommendations',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']
    ],
    'vendors/productSearch' => [
        'controller' => 'VendorController',
        'action' => 'productSearch',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']
    ],
    'vendors/getProductPriceHistory' => [
        'controller' => 'VendorController',
        'action' => 'getProductPriceHistory',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']
    ],
    'vendors/getSimilarProducts' => [
        'controller' => 'VendorController',
        'action' => 'getSimilarProducts',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']
    ],

    // Categories
    'categories' => [
        'controller' => 'CategoryController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('categories')
    ],
    'categories/create' => [
        'controller' => 'CategoryController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('categories/create')
    ],
    'categories/edit' => [
        'controller' => 'CategoryController',
        'action' => 'edit',
        'auth' => true,
        'roles' => getRolesFor('categories/edit')
    ],
    'categories/view' => [
        'controller' => 'CategoryController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('categories/view')
    ],

    // Makers
    'makers' => [
        'controller' => 'MakerController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('makers')
    ],
    'makers/create' => [
        'controller' => 'MakerController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('makers/create')
    ],
    'makers/edit' => [
        'controller' => 'MakerController',
        'action' => 'edit',
        'auth' => true,
        'roles' => getRolesFor('makers/edit')
    ],
    'makers/view' => [
        'controller' => 'MakerController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('makers/view')
    ],

    // Clients
    'clients' => [
        'controller' => 'ClientController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('clients')
    ],
    'clients/create' => [
        'controller' => 'ClientController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('clients/create')
    ],
    'clients/edit' => [
        'controller' => 'ClientController',
        'action' => 'edit',
        'auth' => true,
        'roles' => getRolesFor('clients/edit')
    ],
    'clients/view' => [
        'controller' => 'ClientController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('clients/view')
    ],

    // Brands
    'brands' => [
        'controller' => 'BrandController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('brands')
    ],
    'brands/create' => [
        'controller' => 'BrandController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('brands/create')
    ],
    'brands/edit' => [
        'controller' => 'BrandController',
        'action' => 'edit',
        'auth' => true,
        'roles' => getRolesFor('brands/edit')
    ],
    'brands/view' => [
        'controller' => 'BrandController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('brands/view')
    ],

    // Disciplines
    'disciplines' => [
        'controller' => 'DisciplineController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('disciplines')
    ],
    'disciplines/create' => [
        'controller' => 'DisciplineController',
        'action' => 'create',
        'auth' => true,
        'roles' => getRolesFor('disciplines/create')
    ],
    'disciplines/edit' => [
        'controller' => 'DisciplineController',
        'action' => 'edit',
        'auth' => true,
        'roles' => getRolesFor('disciplines/edit')
    ],
    'disciplines/view' => [
        'controller' => 'DisciplineController',
        'action' => 'view',
        'auth' => true,
        'roles' => getRolesFor('disciplines/view')
    ],

    // =================================================================
    // API ROUTES
    // =================================================================
    'api/procurement-orders/items' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'getItems',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/view')
    ],
    'api/procurement-orders/stats' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'getStats',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/view')
    ],
    'api/operations/stats' => [
        'controller' => 'OperationsController',
        'action' => 'getStats',
        'auth' => true,
        'roles' => ['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk']
    ],
    'api/procurement-orders/delivery-alerts' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'getDeliveryAlerts',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/delivery-performance')
    ],
    'api/validate-qr' => [
        'controller' => 'ApiController',
        'action' => 'validateQR',
        'auth' => false
    ],
    'api/assets/search' => [
        'controller' => 'ApiController',
        'action' => 'searchAssets',
        'auth' => true,
        'roles' => getRolesFor('api/assets/search')
    ],
    'api/assets/generate-qr' => [
        'controller' => 'AssetTagController',
        'action' => 'generateQR',
        'auth' => true,
        'roles' => ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Asset Director']
    ],
    'api/assets/mark-tags-applied' => [
        'controller' => 'AssetTagController',
        'action' => 'markTagsApplied',
        'auth' => true,
        'roles' => ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Asset Director']
    ],
    'api/assets/tag-stats' => [
        'controller' => 'AssetTagController',
        'action' => 'tagStats',
        'auth' => true,
        'roles' => ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Asset Director']
    ],
    'api/assets/verify-tag' => [
        'controller' => 'AssetTagController',
        'action' => 'verifyTag',
        'auth' => true,
        'roles' => ['System Admin', 'Site Inventory Clerk']
    ],
    'api/assets/verify-tags' => [
        'controller' => 'AssetTagController',
        'action' => 'verifyTags',
        'auth' => true,
        'roles' => ['System Admin', 'Site Inventory Clerk']
    ],
    'api/assets/disciplines' => [
        'controller' => 'ApiController',
        'action' => 'assetDisciplines',
        'auth' => true,
        'roles' => getRolesFor('api/assets/disciplines')
    ],
    'api/assets/validate-brand' => [
        'controller' => 'ApiController',
        'action' => 'validateBrand',
        'auth' => true,
        'roles' => getRolesFor('api/assets/validate-brand')
    ],
    'api/assets/suggest-brand' => [
        'controller' => 'ApiController',
        'action' => 'suggestBrand',
        'auth' => true,
        'roles' => getRolesFor('api/assets/suggest-brand')
    ],
    'api/equipment-types' => [
        'controller' => 'ApiController',
        'action' => 'intelligentNaming',
        'auth' => true,
        'roles' => getRolesFor('api/intelligent-naming')
    ],
    'api/subtypes' => [
        'controller' => 'ApiController',
        'action' => 'intelligentNaming',
        'auth' => true,
        'roles' => getRolesFor('api/intelligent-naming')
    ],
    'api/equipment-type-details' => [
        'controller' => 'ApiController',
        'action' => 'intelligentNaming',
        'auth' => true,
        'roles' => getRolesFor('api/intelligent-naming')
    ],
    'api/intelligent-naming' => [
        'controller' => 'ApiController',
        'action' => 'intelligentNaming',
        'auth' => true,
        'roles' => getRolesFor('api/intelligent-naming')
    ],
    'api/assets/unknown-brand-notifications' => [
        'controller' => 'ApiController',
        'action' => 'unknownBrandNotifications',
        'auth' => true,
        'roles' => getRolesFor('api/assets/unknown-brand-notifications')
    ],
    'api/admin/brand-suggestions' => [
        'controller' => 'ApiController',
        'action' => 'brandSuggestions',
        'auth' => true,
        'roles' => getRolesFor('api/admin/brand-suggestions')
    ],
    'api/admin/brand-workflow' => [
        'controller' => 'ApiController',
        'action' => 'brandWorkflow',
        'auth' => true,
        'roles' => getRolesFor('api/admin/brand-workflow')
    ],
    'api/dashboard/stats' => [
        'controller' => 'ApiController',
        'action' => 'dashboardStats',
        'auth' => true,
        'roles' => getRolesFor('api/dashboard/stats')
    ],
    'api/notifications' => [
        'controller' => 'ApiController',
        'action' => 'getNotifications',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk', 'Site Admin']
    ],
    'api/notifications/mark-read' => [
        'controller' => 'ApiController',
        'action' => 'markNotificationAsRead',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk', 'Site Admin']
    ],

    // =================================================================
    // ADMIN API ROUTES
    // =================================================================
    'api/admin/brands' => [
        'controller' => 'ApiController',
        'action' => 'adminBrands',
        'auth' => true,
        'roles' => getRolesFor('brands')
    ],
    'api/admin/disciplines' => [
        'controller' => 'ApiController',
        'action' => 'adminDisciplines',
        'auth' => true,
        'roles' => getRolesFor('disciplines')
    ],

    // =================================================================
    // REPORTS
    // =================================================================
    'reports' => [
        'controller' => 'ReportController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('reports')
    ],
    'reports/withdrawals' => [
        'controller' => 'ReportController',
        'action' => 'withdrawals',
        'auth' => true,
        'roles' => getRolesFor('reports/withdrawals')
    ],
    'reports/transfers' => [
        'controller' => 'ReportController',
        'action' => 'transfers',
        'auth' => true,
        'roles' => getRolesFor('reports/transfers')
    ],
    'reports/maintenance' => [
        'controller' => 'ReportController',
        'action' => 'maintenance',
        'auth' => true,
        'roles' => getRolesFor('reports/maintenance')
    ],
    'reports/incidents' => [
        'controller' => 'ReportController',
        'action' => 'incidents',
        'auth' => true,
        'roles' => getRolesFor('reports/incidents')
    ],

    // =================================================================
    // SYSTEM ADMINISTRATION
    // =================================================================
    'admin' => [
        'controller' => 'AdminController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/settings' => [
        'controller' => 'AdminController',
        'action' => 'settings',
        'auth' => true,
        'roles' => getRolesFor('admin/settings')
    ],
    'admin/maintenance' => [
        'controller' => 'AdminController',
        'action' => 'maintenance',
        'auth' => true,
        'roles' => getRolesFor('admin/maintenance')
    ],
    'admin/logs' => [
        'controller' => 'AdminController',
        'action' => 'logs',
        'auth' => true,
        'roles' => getRolesFor('admin/logs')
    ],
    'admin/upgrades' => [
        'controller' => 'AdminController',
        'action' => 'upgrades',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/executeUpgrade' => [
        'controller' => 'AdminController',
        'action' => 'executeUpgrade',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/executeMigrations' => [
        'controller' => 'AdminController',
        'action' => 'executeMigrations',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/checkIntegrity' => [
        'controller' => 'AdminController',
        'action' => 'checkIntegrity',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/getSystemHealth' => [
        'controller' => 'AdminController',
        'action' => 'getSystemHealthAPI',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/backups' => [
        'controller' => 'AdminController',
        'action' => 'backups',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/createBackup' => [
        'controller' => 'AdminController',
        'action' => 'createBackup',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/security' => [
        'controller' => 'AdminController',
        'action' => 'security',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/modules' => [
        'controller' => 'AdminController',
        'action' => 'modules',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/installModule' => [
        'controller' => 'AdminController',
        'action' => 'installModule',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/uninstallModule' => [
        'controller' => 'AdminController',
        'action' => 'uninstallModule',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/toggleModule' => [
        'controller' => 'AdminController',
        'action' => 'toggleModule',
        'auth' => true,
        'roles' => getRolesFor('admin')
    ],
    'admin/asset-standardization' => [
        'controller' => 'AdminController',
        'action' => 'assetStandardization',
        'auth' => true,
        'roles' => getRolesFor('admin/asset-standardization')
    ],
    'admin/brand-workflow' => [
        'controller' => 'AdminController',
        'action' => 'brandWorkflow',
        'auth' => true,
        'roles' => ['System Admin', 'Asset Director']
    ],

    // =================================================================
    // LEGACY ROUTES (Maintain compatibility)
    // =================================================================
    'procurement-orders/print-preview' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'printPreview',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/print-preview')
    ],
    'procurement-orders/export' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'export',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/export')
    ],

    // =================================================================
    // BIR FORM 2307 ROUTES
    // =================================================================
    'bir2307' => [
        'controller' => 'Bir2307Controller',
        'action' => 'index',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Officer', 'Procurement Officer']
    ],
    'bir2307/generate' => [
        'controller' => 'Bir2307Controller',
        'action' => 'generate',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Officer', 'Procurement Officer']
    ],
    'bir2307/view' => [
        'controller' => 'Bir2307Controller',
        'action' => 'view',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Officer', 'Procurement Officer', 'Asset Director']
    ],
    'bir2307/print-preview' => [
        'controller' => 'Bir2307Controller',
        'action' => 'printPreview',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Officer', 'Procurement Officer']
    ],
    'bir2307/update-status' => [
        'controller' => 'Bir2307Controller',
        'action' => 'updateStatus',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Officer']
    ],
    'bir2307/batch-generate' => [
        'controller' => 'Bir2307Controller',
        'action' => 'batchGenerate',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Officer']
    ],
    'api/atc-codes' => [
        'controller' => 'Bir2307Controller',
        'action' => 'getAtcCodes',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Officer', 'Procurement Officer']
    ],
    'api/calculate-ewt' => [
        'controller' => 'Bir2307Controller',
        'action' => 'calculateEwt',
        'auth' => true,
        'roles' => ['System Admin', 'Finance Officer', 'Procurement Officer']
    ],

    // Legacy procurement redirect
    'procurement' => [
        'controller' => 'ProcurementOrderController',
        'action' => 'index',
        'auth' => true,
        'roles' => getRolesFor('procurement-orders/view')
    ]
];

return $routes;
