# Request Module - Quick Reference Guide

## File Locations

```
models/Request/
├── RequestModel.php                 # CRUD operations
├── RequestWorkflowModel.php         # Status & workflow
├── RequestStatisticsModel.php       # Statistics & reports
├── RequestDeliveryModel.php         # Delivery tracking
├── RequestRestockModel.php          # Restock operations
└── RequestActivityModel.php         # Activity logs

services/Request/
├── RequestFilterService.php         # Filtering logic
├── RequestValidationService.php     # Validation logic
└── RequestPermissionService.php     # Permission checks

models/
├── RequestModel.php                 # Facade (backward compatible)
└── RequestModel_Legacy.php          # Original (backup)
```

---

## Quick Usage Examples

### CRUD Operations

```php
require_once APP_ROOT . '/models/Request/RequestModel.php';
$model = new RequestModel();

// Create
$result = $model->createRequest($data);

// Read
$request = $model->getRequestWithDetails($id);
$requests = $model->getRequestsWithFilters($filters, $page, $perPage);

// Update
$result = $model->updateRequest($id, $data);

// Delete
$result = $model->deleteRequest($id);

// Link to procurement
$result = $model->linkToProcurementOrder($requestId, $poId);
```

### Workflow Operations

```php
require_once APP_ROOT . '/models/Request/RequestWorkflowModel.php';
$workflow = new RequestWorkflowModel();

// Submit request
$result = $workflow->submitRequest($requestId, $userId);

// Change status
$result = $workflow->updateRequestStatus($requestId, 'Approved', $userId, $remarks);

// Get pending requests
$pending = $workflow->getPendingRequests($userId, $userRole);

// Get workflow details
$request = $workflow->getRequestWithWorkflow($id);
```

### Statistics

```php
require_once APP_ROOT . '/models/Request/RequestStatisticsModel.php';
$stats = new RequestStatisticsModel();

// Overall statistics
$statistics = $stats->getRequestStatistics($projectId, $dateFrom, $dateTo);

// By type
$byType = $stats->getRequestsByType($dateFrom, $dateTo);

// By urgency
$byUrgency = $stats->getRequestsByUrgency($projectId);

// Approval rate
$approvalRate = $stats->getApprovalRate($projectId, $dateFrom, $dateTo);
```

### Delivery Tracking

```php
require_once APP_ROOT . '/models/Request/RequestDeliveryModel.php';
$delivery = new RequestDeliveryModel();

// Get with delivery status
$request = $delivery->getRequestWithDeliveryStatus($id);

// Get delivery dashboard
$requests = $delivery->getRequestsWithDeliveryTracking($filters, $userRole, $userId);

// Get alerts
$alerts = $delivery->getDeliveryAlerts($userRole, $userId);

// Statistics
$stats = $delivery->getDeliveryStatistics($projectId, $dateFrom, $dateTo);
```

### Restock Operations

```php
require_once APP_ROOT . '/models/Request/RequestRestockModel.php';
$restock = new RequestRestockModel();

// Get restock details
$details = $restock->getRestockDetails($requestId);

// Validate restock request
$validation = $restock->validateRestockRequest($data);

// Get eligible items
$items = $restock->getInventoryItemsForRestock($projectId, $lowStockOnly);

// Get low stock items
$lowStock = $restock->getLowStockItems($projectId, $threshold);
```

### Activity Logging

```php
require_once APP_ROOT . '/models/Request/RequestActivityModel.php';
$activity = new RequestActivityModel();

// Log activity
$activity->logRequestActivity($requestId, 'status_changed', 'Draft', 'Submitted', $remarks, $userId);

// Get logs
$logs = $activity->getRequestLogs($requestId);

// Recent activities
$recent = $activity->getRecentActivities($limit, $userId, $projectId);

// Statistics
$stats = $activity->getActivityStatistics($dateFrom, $dateTo);
```

---

## Service Usage

### Filtering

```php
require_once APP_ROOT . '/services/Request/RequestFilterService.php';
$filterService = new RequestFilterService();

// Build from GET
$filters = $filterService->buildFiltersFromRequest($_GET);

// Apply role-based
$filters = $filterService->applyRoleBasedFilters($userRole, $userId, $filters);

// Validate
$validation = $filterService->validateFilters($filters);

// Get options
$options = $filterService->getFilterOptions();
```

### Validation

```php
require_once APP_ROOT . '/services/Request/RequestValidationService.php';
$validationService = new RequestValidationService();

// Validate creation
$errors = $validationService->validateCreateRequest($data, $userRole);

// Check request type permission
$canCreate = $validationService->canCreateRequestType($userRole, 'Petty Cash');

// Get allowed types
$types = $validationService->getAllowedRequestTypes($userRole);

// Validate status transition
$validation = $validationService->validateStatusTransition('Draft', 'Submitted');
```

### Permissions

```php
require_once APP_ROOT . '/services/Request/RequestPermissionService.php';
$permissionService = new RequestPermissionService();

// Check view permission
$canView = $permissionService->canViewRequest($request, $userId, $userRole);

// Check edit permission
$canEdit = $permissionService->canEditRequest($request, $userId, $userRole);

// Check workflow permissions
$canVerify = $permissionService->canVerifyRequest($request, $userId, $userRole);
$canAuthorize = $permissionService->canAuthorizeRequest($request, $userId, $userRole);
$canApprove = $permissionService->canApproveRequest($request, $userId, $userRole);

// Get all available actions
$actions = $permissionService->getAvailableActions($request, $userId, $userRole);
```

---

## Common Patterns

### Pattern 1: List Requests with Filters

```php
// Load dependencies
require_once APP_ROOT . '/models/Request/RequestModel.php';
require_once APP_ROOT . '/services/Request/RequestFilterService.php';

$currentUser = $auth->getCurrentUser();

// Build and apply filters
$filterService = new RequestFilterService();
$filters = $filterService->buildFiltersFromRequest($_GET);
$filters = $filterService->applyRoleBasedFilters($currentUser['role_name'], $currentUser['id'], $filters);

// Get data
$requestModel = new RequestModel();
$result = $requestModel->getRequestsWithFilters($filters, $page, $perPage);

$requests = $result['data'];
$pagination = $result['pagination'];
```

### Pattern 2: Create Request with Validation

```php
// Load dependencies
require_once APP_ROOT . '/models/Request/RequestModel.php';
require_once APP_ROOT . '/services/Request/RequestValidationService.php';

$currentUser = $auth->getCurrentUser();
$validationService = new RequestValidationService();

// Validate
$errors = $validationService->validateCreateRequest($_POST, $currentUser['role_name']);

if (empty($errors)) {
    $requestModel = new RequestModel();
    $result = $requestModel->createRequest($_POST);

    if ($result['success']) {
        header('Location: ?route=requests/view&id=' . $result['request']['id']);
        exit;
    }
}
```

### Pattern 3: View Request with Permissions

```php
// Load dependencies
require_once APP_ROOT . '/models/Request/RequestModel.php';
require_once APP_ROOT . '/services/Request/RequestPermissionService.php';

$requestId = $_GET['id'] ?? 0;
$currentUser = $auth->getCurrentUser();

// Get request
$requestModel = new RequestModel();
$request = $requestModel->getRequestWithDetails($requestId);

// Check permission
$permissionService = new RequestPermissionService();
if (!$permissionService->canViewRequest($request, $currentUser['id'], $currentUser['role_name'])) {
    http_response_code(403);
    include APP_ROOT . '/views/errors/403.php';
    exit;
}

// Get available actions for UI
$actions = $permissionService->getAvailableActions($request, $currentUser['id'], $currentUser['role_name']);

// Render view
include APP_ROOT . '/views/requests/view.php';
```

### Pattern 4: Dashboard Statistics

```php
// Load dependencies
require_once APP_ROOT . '/models/Request/RequestStatisticsModel.php';
require_once APP_ROOT . '/models/Request/RequestDeliveryModel.php';

$statsModel = new RequestStatisticsModel();
$deliveryModel = new RequestDeliveryModel();

// Get statistics
$requestStats = $statsModel->getRequestStatistics($projectId);
$deliveryStats = $deliveryModel->getDeliveryStatistics($projectId);
$approvalRate = $statsModel->getApprovalRate($projectId);
$byType = $statsModel->getRequestsByType();

// Get alerts
$alerts = $deliveryModel->getDeliveryAlerts($userRole, $userId);
```

---

## Method Quick Lookup

### When to Use Which Model

| Need to... | Use This Model |
|------------|----------------|
| Create/Read/Update/Delete requests | RequestModel |
| Change request status | RequestWorkflowModel |
| Submit request for approval | RequestWorkflowModel |
| Get pending approvals | RequestWorkflowModel |
| Get request statistics | RequestStatisticsModel |
| Get approval rates | RequestStatisticsModel |
| Track delivery status | RequestDeliveryModel |
| Get delivery alerts | RequestDeliveryModel |
| Create restock request | RequestRestockModel |
| Get low stock items | RequestRestockModel |
| Log activity | RequestActivityModel |
| Get activity logs | RequestActivityModel |

### When to Use Which Service

| Need to... | Use This Service |
|------------|------------------|
| Build filters from GET params | RequestFilterService |
| Apply role-based filters | RequestFilterService |
| Validate request data | RequestValidationService |
| Check if user can create type | RequestValidationService |
| Check view permission | RequestPermissionService |
| Check edit permission | RequestPermissionService |
| Check workflow permissions | RequestPermissionService |
| Get available actions | RequestPermissionService |

---

## Role-Based Request Types

```php
$validationService = new RequestValidationService();
$allowedTypes = $validationService->getAllowedRequestTypes($userRole);

// Returns:
// System Admin: All types
// Asset Director: Material, Tool, Equipment, Service, Other, Restock
// Finance Director: Petty Cash, Service, Material, Tool, Equipment, Other
// Procurement Officer: Material, Tool, Equipment, Other
// Project Manager: Material, Tool, Equipment, Service, Other, Restock
// Site Inventory Clerk: Material, Tool, Restock
// Warehouseman: Restock
```

---

## Status Workflow

```
Draft → Submitted → Verified → Authorized → Approved → Procured
           ↓           ↓           ↓           ↓
        Declined    Declined    Declined    Declined
           ↓
         Draft
```

### Status Transition Validation

```php
$validationService = new RequestValidationService();
$validation = $validationService->validateStatusTransition($currentStatus, $newStatus);

if (!$validation['valid']) {
    // Show errors: $validation['errors']
}
```

---

## Testing Commands

```bash
# Check syntax
php -l models/Request/*.php
php -l services/Request/*.php

# Count lines
wc -l models/Request/*.php services/Request/*.php

# Test backward compatibility
php -r "require_once 'config/bootstrap.php'; \$m = new RequestModel(); var_dump(get_class_methods(\$m));"
```

---

## Migration Checklist

- [ ] Models loaded correctly
- [ ] Services loaded correctly
- [ ] Backward compatibility working
- [ ] Existing code still functional
- [ ] New code using refactored structure
- [ ] Unit tests written
- [ ] Documentation updated
- [ ] Team trained on new structure

---

## Support

For questions or issues with the refactored Request module:

1. Check this quick reference first
2. Review the complete documentation in `REQUEST_MODULE_REFACTORING_COMPLETE.md`
3. Examine the original code in `models/RequestModel_Legacy.php`
4. Consult the facade implementation in `models/RequestModelFacade.php`

---

**Last Updated**: Request Module Refactoring Complete
