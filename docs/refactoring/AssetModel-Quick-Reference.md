# AssetModel Refactoring - Quick Reference Guide

## For Developers: How to Use the New Service Architecture

---

## Overview

AssetModel has been refactored from a 3,317-line god object into a clean service-based architecture. This guide helps you understand where to find functionality and how to use it.

---

## Method Location Guide

### "Where did my method go?"

Use this quick lookup table to find where AssetModel methods were moved:

| Old Method (AssetModel) | New Location (Service) | Line Count |
|------------------------|------------------------|------------|
| **CRUD Operations** |
| `createAsset()` | `AssetCrudService` | 80 lines |
| `updateAsset()` | `AssetCrudService` | 70 lines |
| `deleteAsset()` | `AssetCrudService` | 45 lines |
| `findByQRCode()` | `AssetCrudService` | 15 lines |
| `getAssetWithDetails()` | `AssetCrudService` | 30 lines |
| `getAssetProjectId()` | `AssetCrudService` | 10 lines |
| `updateAssetStatus()` | `AssetCrudService` | 40 lines |
| `bulkUpdateStatus()` | `AssetCrudService` | 45 lines |
| **Workflow Methods** |
| `submitForVerification()` | `AssetWorkflowService` | 30 lines |
| `verifyAsset()` | `AssetWorkflowService` | 35 lines |
| `authorizeAsset()` | `AssetWorkflowService` | 35 lines |
| `rejectAsset()` | `AssetWorkflowService` | 35 lines |
| `createLegacyAsset()` | `AssetWorkflowService` | 120 lines (refactored from 268!) |
| `verifyLegacyAsset()` | `AssetWorkflowService` | 40 lines |
| `authorizeLegacyAsset()` | `AssetWorkflowService` | 40 lines |
| `batchVerifyAssets()` | `AssetWorkflowService` | 30 lines |
| `batchAuthorizeAssets()` | `AssetWorkflowService` | 30 lines |
| `getAssetsByWorkflowStatus()` | `AssetWorkflowService` | 40 lines |
| `getWorkflowStatistics()` | `AssetWorkflowService` | 25 lines |
| `getAssetsPendingVerification()` | `AssetWorkflowService` | 35 lines |
| `getAssetsPendingAuthorization()` | `AssetWorkflowService` | 35 lines |
| `getLegacyWorkflowStats()` | `AssetWorkflowService` | 30 lines |
| **Quantity Management** |
| `consumeQuantity()` | `AssetQuantityService` | 50 lines |
| `restoreQuantity()` | `AssetQuantityService` | 50 lines |
| **Procurement Integration** |
| `createAssetFromProcurementItem()` | `AssetProcurementService` | 60 lines |
| `generateAssetsFromProcurementItem()` | `AssetProcurementService` | 70 lines |
| `createAssetFromProcurement()` | `AssetProcurementService` | 50 lines |
| `getAssetsByProcurementOrder()` | `AssetProcurementService` | 30 lines |
| **Statistics & Reporting** |
| `getAssetStatistics()` | `AssetStatisticsService` | 40 lines |
| `getAssetUtilization()` | `AssetStatisticsService` | 45 lines |
| `getAssetValueReport()` | `AssetStatisticsService` | 45 lines |
| `getDepreciationReport()` | `AssetStatisticsService` | 50 lines |
| `getMaintenanceSchedule()` | `AssetStatisticsService` | 40 lines |
| `getAssetStats()` | `AssetStatisticsService` | 60 lines |
| `getRoleSpecificStatistics()` | `AssetStatisticsService` | 30 lines |
| **Query & Search** |
| `getAssetsWithFilters()` | `AssetQueryService` | 95 lines (refactored from 168!) |
| `getAssetsByProject()` | `AssetQueryService` | 30 lines |
| `getAvailableAssets()` | `AssetQueryService` | 40 lines |
| `getAssetsByCategory()` | `AssetQueryService` | 35 lines |
| `getAssetsByVendor()` | `AssetQueryService` | 35 lines |
| `getOverdueAssets()` | `AssetQueryService` | 50 lines |
| `getAssetsByBusinessType()` | `AssetQueryService` | 80 lines |
| `getMaintenableAssets()` | `AssetQueryService` | 30 lines |
| `canBeMaintained()` | `AssetQueryService` | 68 lines |
| `getAvailableEquipmentCount()` | `AssetQueryService` | 25 lines |
| `getAvailableForBorrowing()` | `AssetQueryService` | 70 lines |
| **Activity Logging** |
| `getAssetHistory()` | `AssetActivityService` | 40 lines |
| `getCompleteActivityLogs()` | `AssetActivityService` | 40 lines |
| **Validation** |
| `validateAssetBusinessRules()` | `AssetValidationService` | 50 lines |
| **Export** |
| `exportAssets()` | `AssetExportService` | 40 lines |

---

## Usage Examples

### Option 1: Through AssetModel Facade (Backward Compatible)

**This is the recommended approach for existing code.**

```php
// All existing code continues to work unchanged!
$assetModel = new AssetModel();

// CRUD operations
$result = $assetModel->createAsset($data);
$asset = $assetModel->getAssetWithDetails($id);
$result = $assetModel->updateAsset($id, $data);
$result = $assetModel->deleteAsset($id);

// Workflow operations
$result = $assetModel->submitForVerification($assetId, $userId);
$result = $assetModel->verifyAsset($assetId, $verifierId, $notes);
$result = $assetModel->authorizeAsset($assetId, $authorizerId, $notes);

// Query operations
$assets = $assetModel->getAssetsWithFilters($filters, $page, $perPage);
$availableAssets = $assetModel->getAvailableForBorrowing($projectId);

// Statistics
$stats = $assetModel->getRoleSpecificStatistics($userRole, $projectId);
$report = $assetModel->getAssetValueReport($projectId);
```

**Pros:**
- ✅ Zero code changes required
- ✅ Works exactly like before
- ✅ Easy migration path
- ✅ No risk of breaking existing code

**Cons:**
- ⚠️ Slightly more memory usage (service initialization)
- ⚠️ Less explicit about which service is being used

---

### Option 2: Direct Service Access (New Code)

**This is the recommended approach for NEW features.**

```php
// Initialize the specific service you need
$crudService = new AssetCrudService(Database::getInstance()->getConnection());

// Use service methods directly
$result = $crudService->createAsset($data);
$asset = $crudService->getAssetWithDetails($id);
```

**Pros:**
- ✅ More explicit and clear
- ✅ Only load the services you need
- ✅ Better for testing (easier to mock)
- ✅ Follows SOLID principles

**Cons:**
- ⚠️ Requires more boilerplate (service initialization)
- ⚠️ Need to manage dependencies manually

---

### Option 3: Service Registry (Future Enhancement)

**This pattern can be implemented in the future for even cleaner code.**

```php
// Get service from registry (singleton pattern)
$crudService = ServiceRegistry::get('AssetCrudService');
$workflowService = ServiceRegistry::get('AssetWorkflowService');

// Use services
$result = $crudService->createAsset($data);
$result = $workflowService->verifyAsset($assetId, $verifierId);
```

---

## Service Responsibility Guide

### When to use which service?

#### AssetCrudService
**Use when you need to:**
- Create, update, or delete assets
- Find assets by ID or QR code
- Get detailed asset information
- Update asset status
- Bulk update asset statuses

**Example use cases:**
- Asset creation form submission
- Asset edit form submission
- Asset deletion with validation
- QR code scanning
- Status change workflows

---

#### AssetWorkflowService
**Use when you need to:**
- Implement MVA (Maker, Verifier, Authorizer) workflow
- Submit assets for verification
- Verify or authorize assets
- Reject assets with reasons
- Handle legacy asset approval
- Batch verify or authorize multiple assets
- Query assets by workflow status

**Example use cases:**
- Approval workflows
- Multi-step verification processes
- Batch approval operations
- Legacy asset migration workflows

---

#### AssetQuantityService
**Use when you need to:**
- Consume quantity from consumables
- Restore quantity (returns)
- Adjust inventory quantities
- Validate quantity changes

**Example use cases:**
- Material consumption from projects
- Tool returns
- Inventory adjustments
- Stock management

---

#### AssetProcurementService
**Use when you need to:**
- Create assets from procurement orders
- Generate assets from procurement items
- Link assets to procurement orders
- Query assets by procurement order

**Example use cases:**
- Asset receipt from vendors
- Procurement-to-asset conversion
- Procurement integration workflows

---

#### AssetStatisticsService
**Use when you need to:**
- Generate statistics and reports
- Get role-specific metrics
- Calculate asset utilization
- Generate depreciation reports
- Get maintenance schedules
- Calculate asset values

**Example use cases:**
- Dashboard widgets
- Management reports
- Financial reports
- Maintenance planning
- Role-based analytics

---

#### AssetQueryService
**Use when you need to:**
- Search and filter assets
- Get paginated asset lists
- Query available assets
- Find assets by various criteria
- Check asset eligibility for operations
- Get assets for specific purposes (borrowing, maintenance, etc.)

**Example use cases:**
- Asset search pages
- Dropdown lists (available assets)
- Eligibility checks
- Filtered reports

---

#### AssetActivityService
**Use when you need to:**
- Log asset activities
- Get asset history
- Retrieve audit trails
- Track changes

**Example use cases:**
- Audit logging
- Change history display
- Activity timelines
- Compliance reporting

---

#### AssetValidationService
**Use when you need to:**
- Validate asset business rules
- Check category rules
- Validate capitalization thresholds
- Check project access
- Validate quantity updates

**Example use cases:**
- Form validation
- Pre-save checks
- Business rule enforcement
- Access control validation

---

#### AssetExportService
**Use when you need to:**
- Export asset data to CSV
- Export asset data to Excel
- Generate asset reports for download

**Example use cases:**
- Data export features
- Report generation
- Bulk data downloads

---

## Common Code Patterns

### Pattern 1: Create Asset with Validation

```php
// OLD WAY (still works via facade)
$assetModel = new AssetModel();
$result = $assetModel->createAsset($data);

if ($result['success']) {
    // Success handling
} else {
    // Error handling
}

// NEW WAY (direct service access)
$validationService = new AssetValidationService(Database::getInstance()->getConnection());
$crudService = new AssetCrudService(
    Database::getInstance()->getConnection(),
    null, null, null, null,
    $validationService
);

$result = $crudService->createAsset($data);
```

---

### Pattern 2: Complete MVA Workflow

```php
// Using facade (recommended for existing code)
$assetModel = new AssetModel();

// Step 1: Submit
$submitResult = $assetModel->submitForVerification($assetId, $makerId);

// Step 2: Verify
$verifyResult = $assetModel->verifyAsset($assetId, $verifierId, 'Looks good');

// Step 3: Authorize
$authorizeResult = $assetModel->authorizeAsset($assetId, $authorizerId, 'Approved');

// Check final status
$asset = $assetModel->find($assetId);
if ($asset['workflow_status'] === 'approved') {
    // Asset is now available for use!
}
```

---

### Pattern 3: Search with Filters and Pagination

```php
$assetModel = new AssetModel();

$filters = [
    'status' => 'available',
    'project_id' => $projectId,
    'category_id' => $categoryId,
    'search' => 'excavator',
    'workflow_status' => 'approved',
];

$result = $assetModel->getAssetsWithFilters($filters, $page = 1, $perPage = 20);

$assets = $result['data'];
$pagination = $result['pagination'];

// Display results with pagination
foreach ($assets as $asset) {
    // Render asset
}
```

---

### Pattern 4: Role-Based Statistics

```php
$assetModel = new AssetModel();
$currentUser = Auth::getInstance()->getCurrentUser();

// Get statistics based on user role
$stats = $assetModel->getRoleSpecificStatistics(
    $currentUser['role_name'],
    $currentUser['current_project_id']
);

// Stats will vary by role:
// - Project Manager: utilization, low stock, maintenance pending
// - Site Inventory Clerk: consumables, stock levels, activities
// - Warehouseman: capital vs inventory, receipts, in transit
// - System Admin: system-wide metrics
// - Finance Director: asset values, acquisitions
// - Asset Director: all asset metrics
```

---

### Pattern 5: Consumable Quantity Management

```php
$assetModel = new AssetModel();

// Consume quantity (e.g., materials used on project)
$result = $assetModel->consumeQuantity(
    $assetId,
    $quantityToConsume = 50,
    $reason = 'Used for Project XYZ foundation work'
);

if ($result['success']) {
    echo "Consumed: {$result['consumed']} units";
    echo "Remaining: {$result['remaining']} units";
}

// Restore quantity (e.g., materials returned)
$result = $assetModel->restoreQuantity(
    $assetId,
    $quantityToRestore = 10,
    $reason = 'Returned unused materials'
);
```

---

### Pattern 6: Batch Operations

```php
$assetModel = new AssetModel();

// Batch verify multiple assets
$assetIds = [101, 102, 103, 104];
$result = $assetModel->batchVerifyAssets($assetIds, 'Bulk verification');

echo "Verified: {$result['verified_count']} / {$result['total_count']}";

if (!empty($result['errors'])) {
    foreach ($result['errors'] as $error) {
        echo "Error: {$error}";
    }
}

// Batch authorize multiple assets
$result = $assetModel->batchAuthorizeAssets($assetIds, 'Bulk authorization');
```

---

## Testing Guide

### Testing with Services

#### Unit Test Example: AssetCrudService

```php
class AssetCrudServiceTest extends TestCase {
    private $db;
    private $crudService;

    public function setUp() {
        $this->db = $this->createMock(PDO::class);
        $this->crudService = new AssetCrudService($this->db);
    }

    public function testCreateAsset_Success() {
        $data = [
            'name' => 'Test Asset',
            'category_id' => 1,
            'project_id' => 1,
            'acquired_date' => '2025-11-05'
        ];

        $result = $this->crudService->createAsset($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('asset', $result);
    }
}
```

---

#### Integration Test Example: AssetModel Facade

```php
class AssetModelIntegrationTest extends TestCase {
    private $assetModel;

    public function setUp() {
        $this->assetModel = new AssetModel();
    }

    public function testCreateAndRetrieveAsset() {
        // Create asset via facade
        $data = [
            'name' => 'Integration Test Asset',
            'category_id' => 1,
            'project_id' => 1,
            'acquired_date' => date('Y-m-d')
        ];

        $createResult = $this->assetModel->createAsset($data);
        $this->assertTrue($createResult['success']);

        // Retrieve asset via facade
        $assetId = $createResult['asset']['id'];
        $asset = $this->assetModel->getAssetWithDetails($assetId);

        $this->assertEquals('Integration Test Asset', $asset['name']);
    }
}
```

---

## Migration Path for Existing Code

### Step-by-Step Guide

#### Step 1: No Changes Required (Use Facade)
Your existing code works unchanged. All calls to `$assetModel->method()` are automatically delegated to the appropriate service.

```php
// This code requires ZERO changes
$assetModel = new AssetModel();
$result = $assetModel->createAsset($data);
```

#### Step 2: Gradually Adopt Direct Service Access (Optional)
For new features or when refactoring existing code, you can start using services directly:

```php
// Before (still works)
$assetModel = new AssetModel();
$stats = $assetModel->getAssetStatistics($projectId);

// After (more explicit)
$statsService = new AssetStatisticsService(Database::getInstance()->getConnection());
$stats = $statsService->getAssetStatistics($projectId);
```

#### Step 3: Update Controllers (Future)
Eventually, controllers can be refactored to use services directly:

```php
class AssetController extends BaseController {
    private $crudService;
    private $queryService;

    public function __construct() {
        parent::__construct();
        $db = Database::getInstance()->getConnection();
        $this->crudService = new AssetCrudService($db);
        $this->queryService = new AssetQueryService($db);
    }

    public function create() {
        // Use service directly
        $result = $this->crudService->createAsset($_POST);
        // ...
    }

    public function index() {
        // Use service directly
        $result = $this->queryService->getAssetsWithFilters($_GET, $page, $perPage);
        // ...
    }
}
```

---

## Performance Considerations

### Service Initialization Cost
- **Facade approach:** All services initialized once per request (~0.5ms overhead)
- **Direct service approach:** Only initialize services you need (~0.05ms per service)

### Recommendation
- Use **facade** for existing code (backward compatibility priority)
- Use **direct services** for new features (performance optimization)
- Consider **lazy initialization** for services (create only when first used)

---

## Troubleshooting

### Common Issues

#### Issue 1: "Service not initialized"
**Symptom:** Error when calling service method

**Solution:**
```php
// Make sure AssetModel constructor is called
$assetModel = new AssetModel(); // This initializes all services

// Or initialize service directly
$crudService = new AssetCrudService(Database::getInstance()->getConnection());
```

---

#### Issue 2: "Method not found"
**Symptom:** Fatal error: Call to undefined method

**Solution:** Check the method location table above. The method may have moved to a different service.

```php
// OLD: AssetModel->getAssetStatistics()
// NEW: AssetStatisticsService->getAssetStatistics()

// But still accessible via facade:
$assetModel = new AssetModel();
$stats = $assetModel->getAssetStatistics($projectId); // Works!
```

---

#### Issue 3: "Transaction already started"
**Symptom:** Error about nested transactions

**Solution:** Services manage their own transactions. If calling multiple services, wrap in a single transaction at the controller level:

```php
try {
    Database::getInstance()->getConnection()->beginTransaction();

    $result1 = $assetModel->createAsset($data1);
    $result2 = $assetModel->createAsset($data2);

    Database::getInstance()->getConnection()->commit();
} catch (Exception $e) {
    Database::getInstance()->getConnection()->rollback();
}
```

---

## Best Practices

### DO ✅
- Use AssetModel facade for existing code (backward compatibility)
- Use direct services for new features (better architecture)
- Write unit tests for each service separately
- Mock dependencies in tests
- Keep services focused (single responsibility)
- Log errors appropriately
- Validate input in services
- Use transactions for data integrity

### DON'T ❌
- Don't modify AssetModel facade (keep it thin)
- Don't add business logic to AssetModel (put it in services)
- Don't bypass services to access database directly
- Don't create god services (keep them focused)
- Don't forget to handle errors
- Don't skip validation
- Don't nest transactions unnecessarily

---

## Getting Help

### Where to find information
1. **This document** - Quick reference for day-to-day development
2. **AssetModel-Refactoring-Strategy.md** - Detailed architectural design
3. **AssetModel-Architecture-Diagram.md** - Visual diagrams and flow charts
4. **Service PHPDoc headers** - Each service has detailed documentation
5. **Code examples in tests** - See `tests/services/Asset/`

### Who to ask
- **Technical Lead:** Questions about architecture decisions
- **Service Author:** Questions about specific service implementation
- **QA Team:** Questions about testing strategy

---

## Quick Command Reference

```bash
# View service files
ls -la services/Asset/

# Count lines in a service
wc -l services/Asset/AssetCrudService.php

# Search for method in services
grep -r "createAsset" services/Asset/

# Run service tests
phpunit tests/services/Asset/AssetCrudServiceTest.php

# Run all asset service tests
phpunit tests/services/Asset/

# Check test coverage
phpunit --coverage-html coverage/ tests/services/Asset/
```

---

**Document Version:** 1.0.0
**Last Updated:** 2025-11-05
**Maintained By:** Development Team
