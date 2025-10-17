<?php
/**
 * ConstructLink™ MVA (Maker, Verifier, Authorizer) Role Mapping
 * Comprehensive role-based access control for all system workflows
 */

return [
    // =================================================================
    // 1. 🔧 REQUEST FOR MATERIALS / TOOLS / EQUIPMENT / CONSUMABLES
    // =================================================================
    'requests' => [
        'maker' => ['Site Inventory Clerk', 'Site Admin'], // Initiates request in system
        'verifier' => ['Project Manager'], // Reviews if request is valid for project
        'authorizer' => ['Finance Director'], // Approves based on budget/priority
        'viewer' => ['System Admin', 'Asset Director', 'Procurement Officer'] // Can view all requests
    ],
    'requests/create' => ['Site Inventory Clerk', 'Site Admin', 'System Admin'],
    'requests/review' => ['Project Manager', 'System Admin'],
    'requests/approve' => ['Finance Director', 'System Admin'],
    'requests/view' => ['System Admin', 'Asset Director', 'Finance Director', 'Procurement Officer', 'Project Manager', 'Site Inventory Clerk', 'Site Admin'],
    'requests/export' => ['System Admin', 'Asset Director', 'Finance Director', 'Procurement Officer'],
    'requests/getStats' => ['System Admin', 'Asset Director', 'Finance Director', 'Procurement Officer', 'Project Manager'],
    'requests/getPendingRequests' => ['System Admin', 'Asset Director', 'Finance Director', 'Procurement Officer', 'Project Manager'],

    // =================================================================
    // 2. 🛒 DIRECT PROCUREMENT (HEAD OFFICE INITIATED)
    // =================================================================
    'procurement-orders' => [
        'maker' => ['Procurement Officer'], // Creates Purchase Order manually
        'verifier' => ['Asset Director'], // Validates if item should be categorized as asset
        'authorizer' => ['Finance Director'], // Authorizes based on budget
        'notified' => ['Project Manager', 'Site Inventory Clerk'] // Notified once delivery is inbound
    ],
    'procurement-orders/create' => ['Procurement Officer', 'System Admin'],
    'procurement-orders/edit' => ['Procurement Officer', 'System Admin'],
    'procurement-orders/verify' => ['Asset Director', 'System Admin'],
    'procurement-orders/approve' => ['Finance Director', 'System Admin'],
    'procurement-orders/view' => ['System Admin', 'Finance Director', 'Asset Director', 'Warehouseman', 'Procurement Officer', 'Project Manager', 'Site Inventory Clerk'],
    'procurement-orders/createFromRequest' => ['Procurement Officer', 'System Admin'],

    // =================================================================
    // 3. 📦 RECEIVING OF DELIVERED ITEMS
    // =================================================================
    'receiving' => [
        'maker' => ['Warehouseman', 'Site Inventory Clerk', 'Project Manager'], // Inputs delivery receipt, notes actual received quantity (hierarchical)
        'verifier' => ['Site Inventory Clerk', 'Project Manager'], // Cross-checks delivery vs. PO/request (hierarchical)
        'authorizer' => ['Project Manager'], // Acknowledges item as officially received for project use
        'mismatch_handler' => ['Asset Director', 'Procurement Officer'] // Handles partial/incomplete deliveries
    ],
    'procurement-orders/receive' => ['Warehouseman', 'Site Inventory Clerk', 'Project Manager', 'System Admin'],
    'procurement-orders/verify-receipt' => ['Site Inventory Clerk', 'Project Manager', 'System Admin'],
    'procurement-orders/confirm-receipt' => ['Site Inventory Clerk', 'Project Manager', 'System Admin'],
    'procurement-orders/flag-discrepancy' => ['Warehouseman', 'Site Inventory Clerk', 'Project Manager', 'System Admin'],
    'procurement-orders/resolve-discrepancy' => ['Asset Director', 'Procurement Officer', 'System Admin'],

    // =================================================================
    // 4. 🔁 TRANSFER OF ITEMS BETWEEN PROJECTS
    // =================================================================
    'transfers' => [
        'maker' => ['Finance Director', 'Asset Director', 'Project Manager'], // Initiates transfer request
        'verifier' => ['Finance Director', 'Asset Director', 'Project Manager'], // Approves release
        'authorizer' => ['Finance Director', 'Asset Director'], // Final authorization
        'receiver' => ['Project Manager'], // Acknowledges item receipt at destination
        'completer' => ['Project Manager'] // Same as receiver - completes the transfer
    ],
    'transfers/create' => ['Finance Director', 'Asset Director', 'Project Manager', 'System Admin'],
    'transfers/verify' => ['Finance Director', 'Asset Director', 'Project Manager', 'System Admin'],
    'transfers/approve' => ['Finance Director', 'Asset Director', 'System Admin'],
    'transfers/dispatch' => ['Finance Director', 'Asset Director', 'Project Manager', 'System Admin'],
    'transfers/receive' => ['Project Manager', 'System Admin'],
    'transfers/complete' => ['Project Manager', 'System Admin'],
    'transfers/view' => ['System Admin', 'Asset Director', 'Project Manager', 'Finance Director'],
    'transfers/cancel' => ['System Admin', 'Finance Director', 'Asset Director', 'Project Manager'],
    'transfers/returnAsset' => ['System Admin', 'Asset Director', 'Project Manager'],
    'transfers/receiveReturn' => ['System Admin', 'Asset Director', 'Project Manager'],

    // =================================================================
    // 5. 🛠 TOOL BORROWING
    // =================================================================
    // Scenario A: Basic tools needed immediately
    'borrowing_basic' => [
        'maker' => ['Warehouseman'], // Logs tool issuance
        'verifier' => ['Warehouseman'], // Same person for standard tools
        'authorizer' => ['Warehouseman'] // No further approval needed
    ],
    // Scenario B: Critical equipment (crane, welding machine)
    'borrowing_critical' => [
        'maker' => ['Warehouseman'], // Logs request
        'verifier' => ['Project Manager'], // Confirms need
        'authorizer' => ['Asset Director', 'Finance Director'] // Depending on asset value/impact
    ],
    'borrowed-tools/create' => ['Warehouseman', 'System Admin'],
    'borrowed-tools/approve-critical' => ['Project Manager', 'System Admin'],
    'borrowed-tools/authorize-critical' => ['Asset Director', 'Finance Director', 'System Admin'],
    'borrowed-tools/return' => ['Warehouseman', 'Site Inventory Clerk', 'System Admin'],
    'borrowed-tools/view' => ['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'borrowed-tools/extend' => ['System Admin', 'Asset Director', 'Warehouseman', 'Project Manager'],
    'borrowed-tools' => [
        'maker' => ['Warehouseman'], // Logs tool issuance
        'verifier' => ['Warehouseman'], // Same person for standard tools
        'authorizer' => ['Warehouseman'], // No further approval needed
        'viewer' => ['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'] // Can view all borrowed tools
    ],

    // =================================================================
    // 6. 🔄 WITHDRAWALS (STOCK TO USE ON SITE)
    // =================================================================
    'withdrawals' => [
        'maker' => ['Warehouseman'], // Logs withdrawal
        'verifier' => ['Site Inventory Clerk'], // Validates usage vs. request logs
        'authorizer' => ['Project Manager'], // Ensures usage is project-aligned
        'viewer' => ['System Admin', 'Asset Director', 'Finance Director', 'Project Manager', 'Site Inventory Clerk'] // Can view all withdrawals
    ],
    'withdrawals/create' => ['System Admin', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'withdrawals/verify' => ['Site Inventory Clerk', 'System Admin'],
    'withdrawals/approve' => ['Project Manager', 'System Admin'],
    'withdrawals/release' => ['System Admin', 'Asset Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'withdrawals/return' => ['System Admin', 'Asset Director', 'Warehouseman'],
    'withdrawals/view' => ['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'withdrawals/cancel' => ['System Admin', 'Asset Director', 'Project Manager', 'Site Inventory Clerk'],

    // =================================================================
    // 7. ⚠️ INCIDENTS (LOSS, THEFT, DAMAGE)
    // =================================================================
    'incidents' => [
        'maker' => ['Site Inventory Clerk'], // Logs incident with notes/photos
        'verifier' => ['Project Manager'], // Reviews on-site conditions and verifies
        'authorizer' => ['Asset Director'], // Authorizes resolution and approves actions
        'viewer' => ['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'] // Can view all incidents
    ],
    'incidents/create' => ['Site Inventory Clerk', 'Warehouseman', 'Project Manager', 'System Admin'],
    'incidents/investigate' => ['Project Manager', 'System Admin'], // Verifier step
    'incidents/resolve' => ['Asset Director', 'System Admin'], // Authorizer step (can authorize and resolve)
    'incidents/close' => ['Asset Director', 'System Admin'], // Final closure
    'incidents/cancel' => ['Project Manager', 'Asset Director', 'System Admin'], // Can cancel at appropriate stages
    'incidents/view' => ['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'incidents/export' => ['System Admin', 'Asset Director', 'Finance Director'],

    // =================================================================
    // 8. 🧰 MAINTENANCE OF EQUIPMENT
    // =================================================================
    'maintenance' => [
        'maker' => ['Warehouseman', 'Site Inventory Clerk'], // Creates maintenance log
        'verifier' => ['Project Manager'], // Confirms need and cost estimate
        'authorizer' => ['Asset Director'], // Validates repair necessity
        'financial_authorizer' => ['Finance Director'], // Approves payment if external repair needed
        'viewer' => ['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'] // Can view all maintenance
    ],
    'maintenance/create' => ['Warehouseman', 'Site Inventory Clerk', 'System Admin'],
    'maintenance/verify' => ['Project Manager', 'System Admin'],
    'maintenance/authorize' => ['Asset Director', 'System Admin'],
    'maintenance/financial-approve' => ['Finance Director', 'System Admin'],
    'maintenance/complete' => ['Asset Director', 'System Admin'],
    'maintenance/view' => ['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'maintenance/start' => ['System Admin', 'Asset Director'],
    'maintenance/edit' => ['System Admin', 'Asset Director'],
    'maintenance/cancel' => ['System Admin', 'Asset Director'],

    // =================================================================
    // 9. 📑 PROCUREMENT WITH QUOTATION ATTACHMENT
    // =================================================================
    'procurement_with_quote' => [
        'maker' => ['Site Admin'], // Uploads quotation along with request
        'verifier' => ['Project Manager'], // Reviews supplier selection
        'authorizer' => ['Procurement Officer'], // Generates PO
        'secondary_authorizer' => ['Finance Director'] // Confirms payment approval
    ],
    'requests/create-with-quote' => ['Site Admin', 'System Admin'],
    'requests/verify-quote' => ['Project Manager', 'System Admin'],
    'requests/generate-po' => ['Procurement Officer', 'System Admin'],
    'requests/approve-payment' => ['Finance Director', 'System Admin'],

    // =================================================================
    // 10. 🧾 PO DELIVERY SCHEDULING & TRACKING
    // =================================================================
    'delivery_tracking' => [
        'maker' => ['Procurement Officer'], // Updates delivery schedule/tracking
        'verifier' => ['Warehouseman', 'Site Inventory Clerk', 'Project Manager'], // Confirms receipt (hierarchical)
        'authorizer' => ['Project Manager'], // Acknowledges fulfillment
        'visibility' => ['Warehouseman', 'Site Inventory Clerk', 'Project Manager', 'Finance Director', 'Asset Director', 'Procurement Officer'] // All see status (hierarchical)
    ],
    'procurement-orders/schedule-delivery' => ['Procurement Officer', 'System Admin'],
    'procurement-orders/update-delivery' => ['Procurement Officer', 'System Admin'],
    'procurement-orders/confirm-delivery' => ['Warehouseman', 'Site Inventory Clerk', 'Project Manager', 'System Admin'],
    'procurement-orders/acknowledge-fulfillment' => ['Project Manager', 'System Admin'],
    'procurement-orders/delivery-performance' => ['Procurement Officer', 'Asset Director', 'System Admin'],

    // =================================================================
    // ADDITIONAL SYSTEM ROLES AND PERMISSIONS
    // =================================================================
    
    // Asset Generation (MVA for converting procurement to assets)
    'asset_generation' => [
        'maker' => ['Procurement Officer', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'], // Initiates asset generation (hierarchical)
        'verifier' => ['Asset Director'], // Validates asset categorization
        'authorizer' => ['Finance Director'] // Approves asset registration
    ],
    'procurement-orders/generateAssets' => ['Procurement Officer', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager', 'System Admin'],
    'procurement-orders/approved-requests' => ['System Admin', 'Procurement Officer', 'Asset Director', 'Finance Director'],
    'assets/verify-generation' => ['Asset Director', 'System Admin'],
    'assets/approve-generation' => ['Finance Director', 'System Admin'],

    // Consolidated procurement routes for simplified navigation
    'procurement-orders/delivery-management' => ['System Admin', 'Procurement Officer', 'Asset Director', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'],
    'procurement-orders/performance-dashboard' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'],
    
    // Legacy procurement routes (maintain compatibility)
    'procurement-orders/ready-for-delivery' => ['System Admin', 'Procurement Officer', 'Asset Director'],
    'procurement-orders/for-receipt' => ['System Admin', 'Warehouseman', 'Asset Director', 'Site Inventory Clerk', 'Project Manager'],
    'procurement-orders/cancel' => ['System Admin', 'Procurement Officer'],
    'procurement-orders/print-preview' => ['System Admin', 'Finance Director', 'Procurement Officer'],
    'procurement-orders/export' => ['System Admin', 'Finance Director', 'Procurement Officer'],

    // =================================================================
    // AUTHENTICATION & DASHBOARD
    // =================================================================
    'change-password' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'dashboard' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'dashboard/getStats' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],

    // =================================================================
    // ASSET MANAGEMENT
    // =================================================================
    'assets' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'assets/create' => ['System Admin', 'Asset Director', 'Procurement Officer'],
    'assets/edit' => ['System Admin', 'Asset Director', 'Project Manager', 'Site Inventory Clerk', 'Procurement Officer'],
    'assets/view' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'assets/delete' => ['System Admin', 'Asset Director'],
    'assets/scanner' => ['System Admin', 'Asset Director', 'Warehouseman', 'Site Inventory Clerk'],
    'assets/verify' => ['Asset Director', 'System Admin'],
    'assets/authorize' => ['Project Manager', 'Finance Director', 'System Admin'],
    'assets/export' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'],

    // =================================================================
    // LEGACY ASSET WORKFLOW
    // =================================================================
    'legacy_assets' => [
        'maker' => ['Warehouseman'], // Creates legacy asset entries
        'verifier' => ['Site Inventory Clerk'], // Verifies physical inventory against entries
        'authorizer' => ['Project Manager'], // Authorizes asset as project property
        'viewer' => ['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk']
    ],
    'assets/legacy-create' => ['Warehouseman', 'System Admin'],
    'assets/legacy-verify' => ['Site Inventory Clerk', 'Project Manager', 'System Admin'],
    'assets/legacy-authorize' => ['Project Manager', 'System Admin'],
    'assets/legacy-view' => ['Warehouseman', 'Site Inventory Clerk', 'Project Manager', 'System Admin'],
    'assets/legacy-dashboard' => ['Site Inventory Clerk', 'Project Manager', 'System Admin'],
    'assets/workflow-status' => ['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'api/intelligent-naming' => ['System Admin', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],

    // =================================================================
    // MASTER DATA MANAGEMENT
    // =================================================================
    'users' => ['System Admin'],
    'users/create' => ['System Admin'],
    'users/edit' => ['System Admin'],
    'users/view' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'users/delete' => ['System Admin'],
    'users/profile' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],

    // Projects
    'projects' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Project Manager'],
    'projects/create' => ['System Admin'],
    'projects/edit' => ['System Admin'],
    'projects/view' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Project Manager'],

    // Vendors
    'vendors' => ['System Admin', 'Finance Director', 'Procurement Officer'],
    'vendors/create' => ['System Admin', 'Procurement Officer'],
    'vendors/edit' => ['System Admin', 'Procurement Officer'],
    'vendors/view' => ['System Admin', 'Finance Director', 'Procurement Officer'],
    'vendors/intelligenceDashboard' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'],
    'vendors/performanceAnalysis' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'],
    'vendors/vendorComparison' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'],
    'vendors/riskAssessment' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'],
    'vendors/getPerformanceData' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'],
    'vendors/getRiskData' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'],
    'vendors/getTrendData' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'],
    'vendors/getVendorRecommendations' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'],

    // Categories
    'categories' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'],
    'categories/create' => ['System Admin', 'Asset Director'],
    'categories/edit' => ['System Admin', 'Asset Director'],
    'categories/view' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'],

    // Equipment Classification Management
    'equipment/management' => ['System Admin', 'Asset Director'],
    'equipment/categories' => ['System Admin', 'Asset Director'],
    'equipment/types' => ['System Admin', 'Asset Director'],
    'equipment/subtypes' => ['System Admin', 'Asset Director'],

    // Makers
    'makers' => ['System Admin', 'Finance Director', 'Procurement Officer', 'Asset Director'],
    'makers/create' => ['System Admin', 'Procurement Officer'],
    'makers/edit' => ['System Admin', 'Procurement Officer'],
    'makers/view' => ['System Admin', 'Finance Director', 'Procurement Officer', 'Asset Director'],

    // Clients
    'clients' => ['System Admin', 'Finance Director', 'Procurement Officer', 'Project Manager'],
    'clients/create' => ['System Admin', 'Procurement Officer'],
    'clients/edit' => ['System Admin', 'Procurement Officer'],
    'clients/view' => ['System Admin', 'Finance Director', 'Procurement Officer', 'Project Manager'],

    // Brands
    'brands' => ['System Admin', 'Asset Director'],
    'brands/create' => ['System Admin', 'Asset Director'],
    'brands/edit' => ['System Admin', 'Asset Director'],
    'brands/view' => ['System Admin', 'Asset Director'],

    // =================================================================
    // BRAND WORKFLOW (Enhanced MVA for Unknown Brands)
    // =================================================================
    'brand_suggestions' => [
        'maker' => ['Warehouseman', 'Site Inventory Clerk'], // Can suggest new brands during asset creation
        'verifier' => ['Asset Director'], // Reviews and standardizes suggestions
        'authorizer' => ['System Admin'], // Approves new brand entries
        'viewer' => ['System Admin', 'Asset Director', 'Warehouseman', 'Site Inventory Clerk'] // Can view suggestion status
    ],
    'brand_workflow' => [
        'reviewer' => ['Asset Director'], // Reviews unknown brands flagged by system
        'creator' => ['Asset Director'], // Creates standardized brand entries
        'integrator' => ['System Admin'], // Links existing assets to new brands
        'notification_handler' => ['Asset Director', 'System Admin'] // Receives notifications for unknown brands
    ],

    // Disciplines
    'disciplines' => ['System Admin', 'Asset Director'],
    'disciplines/create' => ['System Admin', 'Asset Director'],
    'disciplines/edit' => ['System Admin', 'Asset Director'],
    'disciplines/view' => ['System Admin', 'Asset Director'],

    // =================================================================
    // API ROUTES
    // =================================================================
    'api/procurement-orders/items' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'api/procurement-orders/stats' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'api/procurement-orders/delivery-alerts' => ['Procurement Officer', 'Asset Director', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager', 'System Admin'],
    'api/assets/search' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'api/assets/validate-brand' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'api/assets/suggest-brand' => ['Warehouseman', 'Site Inventory Clerk', 'System Admin', 'Asset Director'],
    'api/assets/disciplines' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],
    'api/assets/unknown-brand-notifications' => ['Asset Director', 'System Admin'],
    'api/admin/brand-suggestions' => ['Asset Director', 'System Admin'],
    'api/admin/brand-workflow' => ['Asset Director', 'System Admin'],
    'api/dashboard/stats' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'],

    // =================================================================
    // REPORTS
    // =================================================================
    'reports' => ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Project Manager'],
    'reports/withdrawals' => ['System Admin', 'Finance Director', 'Asset Director', 'Project Manager'],
    'reports/transfers' => ['System Admin', 'Finance Director', 'Asset Director', 'Project Manager'],
    'reports/maintenance' => ['System Admin', 'Finance Director', 'Asset Director'],
    'reports/incidents' => ['System Admin', 'Finance Director', 'Asset Director'],

    // =================================================================
    // SYSTEM ADMINISTRATION
    // =================================================================
    'admin' => ['System Admin'],
    'admin/settings' => ['System Admin'],
    'admin/maintenance' => ['System Admin'],
    'admin/logs' => ['System Admin'],
    
    // =================================================================
    // ASSET STANDARDIZATION MANAGEMENT
    // =================================================================
    'admin/asset-standardization' => ['System Admin', 'Asset Director'],
    'admin/brands' => ['System Admin', 'Asset Director'],
    'admin/disciplines' => ['System Admin', 'Asset Director'],
    'admin/asset-types' => ['System Admin', 'Asset Director']
];
