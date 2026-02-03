# AssetModel.php Refactoring Strategy
## Comprehensive Architectural Design Document

---

## Executive Summary

**Current State:** AssetModel.php is a god object with 3,317 lines containing 63 public/private methods managing all asset-related functionality.

**Target State:** Decompose into 8-10 focused service classes, each under 500 lines, following SOLID principles and PSR-4 standards.

**Risk Level:** MEDIUM - High-traffic model requiring careful migration with backward compatibility.

**Estimated Effort:** 3-5 days with thorough testing.

---

## Table of Contents
1. [Current Architecture Analysis](#current-architecture-analysis)
2. [Method Inventory & Categorization](#method-inventory--categorization)
3. [Proposed Service Architecture](#proposed-service-architecture)
4. [Detailed Method-to-Service Mapping](#detailed-method-to-service-mapping)
5. [Directory Structure](#directory-structure)
6. [Service Class Specifications](#service-class-specifications)
7. [Dependency Graph](#dependency-graph)
8. [Migration Strategy](#migration-strategy)
9. [Backward Compatibility Plan](#backward-compatibility-plan)
10. [Testing Strategy](#testing-strategy)
11. [Risk Assessment](#risk-assessment)

---

## Current Architecture Analysis

### File Metrics
- **Total Lines:** 3,317 lines
- **Methods:** 63 methods (31 public, 11 private)
- **Violations:**
  - 563% over the 500-line limit
  - God Object anti-pattern
  - Multiple Responsibility Principle violation
  - Tight coupling with 8+ models

### Current Dependencies
```
AssetModel depends on:
├── BaseModel (inheritance)
├── CategoryModel
├── UserModel
├── Auth
├── Validator
├── Database
├── ProcurementOrderModel
├── ProcurementItemModel
├── MaintenanceModel
├── AssetStandardizer
└── AssetSubtypeManager
```

### Current Responsibilities (Anti-Pattern)
The AssetModel currently handles ALL of the following:

1. **CRUD Operations** (Basic persistence)
2. **Workflow Management** (MVA - Maker, Verifier, Authorizer)
3. **Quantity Management** (Consumable inventory)
4. **Procurement Integration** (Asset generation from POs)
5. **Statistics & Reporting** (Role-based analytics)
6. **Activity Logging** (Audit trail)
7. **QR Code Generation** (Physical tagging)
8. **Validation & Business Rules** (Category rules, thresholds)
9. **Search & Filtering** (Complex queries)
10. **Depreciation Calculations** (Financial reporting)
11. **Export Functionality** (Data export)
12. **Maintenance Scheduling** (Equipment lifecycle)
13. **Project Access Control** (Authorization)
14. **Discipline Management** (ISO code tagging)

---

## Method Inventory & Categorization

### 1. CRUD & Basic Operations (8 methods)
```
create($data)                          - Override with discipline handling
createAsset($data)                     - Full asset creation with validation
updateAsset($id, $data)                - Update with project access
deleteAsset($id)                       - Soft delete with checks
find($id)                              - Inherited from BaseModel
findByQRCode($qrCode)                  - QR-based lookup
getAssetWithDetails($id)               - Detailed retrieval with joins
getAssetProjectId($assetId)            - Simple project ID retrieval
```

### 2. Workflow Management (MVA) (13 methods)
```
submitForVerification($assetId, $submittedBy)
verifyAsset($assetId, $verifiedBy, $notes)
authorizeAsset($assetId, $authorizedBy, $notes)
rejectAsset($assetId, $rejectedBy, $rejectionReason)
getAssetsByWorkflowStatus($workflowStatus, $projectId)
getWorkflowStatistics($projectId)
getAssetsPendingVerification($projectId)
getAssetsPendingAuthorization($projectId)
getLegacyWorkflowStats($projectId)
createLegacyAsset($data)               - 268 lines!
verifyLegacyAsset($assetId, $notes)
authorizeLegacyAsset($assetId, $notes)
batchVerifyAssets($assetIds, $notes)
batchAuthorizeAssets($assetIds, $notes)
```

### 3. Quantity Management (Consumables) (2 methods)
```
consumeQuantity($assetId, $quantityToConsume, $reason)
restoreQuantity($assetId, $quantityToRestore, $reason)
```

### 4. Procurement Integration (4 methods)
```
createAssetFromProcurementItem($procurementOrderId, $procurementItemId, $assetData)
generateAssetsFromProcurementItem($procurementOrderId, $procurementItemId, $assetData)
linkAssetToProcurement($assetId, $procurementOrderId, $procurementItemId, $legacyProcurementId) - private
getAssetsByProcurementOrder($procurementOrderId)
createAssetFromProcurement($procurementItem, $assetData)
```

### 5. Statistics & Reporting (12 methods)
```
getAssetStatistics($projectId)
getAssetUtilization($projectId)
getAssetValueReport($projectId)
getDepreciationReport($projectId)
getMaintenanceSchedule($projectId)
getAssetStats()
getRoleSpecificStatistics($userRole, $projectId)
getProjectManagerStats($projectId)            - private
getSiteInventoryClerkStats($projectId)        - private
getWarehousemanStats($projectId)              - private
getSystemAdminStats()                         - private
getFinanceDirectorStats()                     - private
getAssetDirectorStats()                       - private
```

### 6. Search & Query Operations (10 methods)
```
getAssetsWithFilters($filters, $page, $perPage)  - 168 lines!
getAssetsByProject($projectId, $status)
getAvailableAssets($projectId)
getAssetsByCategory($categoryId, $projectId)
getAssetsByVendor($vendorId, $projectId)
getOverdueAssets($type)
getAssetsByBusinessType($assetType, $projectId, $filters)
getMaintenableAssets()
getAvailableEquipmentCount($projectFilter)
getAvailableForBorrowing($projectId)          - 82 lines with complex joins
```

### 7. Activity Logging (3 methods)
```
logAssetActivity($assetId, $action, $description, $oldValues, $newValues) - private
getAssetHistory($assetId)
getCompleteActivityLogs($assetId, $limit)
logActivity($action, $description, $table, $recordId) - private
```

### 8. Status & Lifecycle Management (4 methods)
```
updateAssetStatus($id, $status, $notes)
bulkUpdateStatus($assetIds, $status, $notes)
canBeMaintained($assetId)                     - 68 lines
checkActiveAssetRecords($assetId)             - private
```

### 9. Validation & Business Rules (2 methods)
```
validateAssetBusinessRules($data)             - 48 lines
```

### 10. Export & Utility (4 methods)
```
exportAssets($filters)                        - 38 lines
generateAssetReference()                      - private
generateQRCode($assetRef)                     - private
isQRCodeEnabled()                             - private
```

---

## Proposed Service Architecture

### Design Principles
1. **Single Responsibility:** Each service handles ONE domain concern
2. **Dependency Injection:** All services receive dependencies via constructor
3. **Interface Segregation:** Define contracts for each service
4. **PSR-4 Autoloading:** NOT using namespaces (ConstructLink doesn't use them currently)
5. **Backward Compatibility:** AssetModel becomes a facade/proxy
6. **Maximum 500 lines per service**

### Service Classes (8 Services)

```
services/Asset/
├── AssetCrudService.php                    (~350 lines)
├── AssetWorkflowService.php                (~480 lines)
├── AssetQuantityService.php                (~200 lines)
├── AssetProcurementService.php             (~400 lines)
├── AssetStatisticsService.php              (~450 lines)
├── AssetQueryService.php                   (~400 lines)
├── AssetActivityService.php                (~250 lines)
├── AssetValidationService.php              (~300 lines)
└── AssetExportService.php                  (~200 lines)
```

---

## Detailed Method-to-Service Mapping

### Service 1: AssetCrudService.php
**Responsibility:** Basic CRUD operations, asset retrieval, status updates

**Line Estimate:** ~350 lines

**Methods:**
```php
// Core CRUD
public function createAsset($data)                           // From: createAsset()
public function updateAsset($id, $data)                      // From: updateAsset()
public function deleteAsset($id)                             // From: deleteAsset()
public function findById($id)                                // Wrapper for find()
public function findByQRCode($qrCode)                        // From: findByQRCode()
public function getAssetWithDetails($id)                     // From: getAssetWithDetails()
public function getAssetProjectId($assetId)                  // From: getAssetProjectId()

// Status management
public function updateAssetStatus($id, $status, $notes)      // From: updateAssetStatus()
public function bulkUpdateStatus($assetIds, $status, $notes) // From: bulkUpdateStatus()

// Private helpers
private function generateQRCode($assetRef)                   // From: generateQRCode()
private function isQRCodeEnabled()                           // From: isQRCodeEnabled()
private function checkActiveAssetRecords($assetId)           // From: checkActiveAssetRecords()
```

**Dependencies:**
- Database
- Auth
- Validator
- CategoryModel
- UserModel
- AssetValidationService
- AssetActivityService

---

### Service 2: AssetWorkflowService.php
**Responsibility:** MVA workflow (Maker, Verifier, Authorizer), legacy asset approval

**Line Estimate:** ~480 lines

**Methods:**
```php
// MVA Workflow
public function submitForVerification($assetId, $submittedBy)
public function verifyAsset($assetId, $verifiedBy, $notes)
public function authorizeAsset($assetId, $authorizedBy, $notes)
public function rejectAsset($assetId, $rejectedBy, $rejectionReason)

// Legacy Asset Workflow
public function createLegacyAsset($data)                     // 268 lines - needs refactoring
public function verifyLegacyAsset($assetId, $notes)
public function authorizeLegacyAsset($assetId, $notes)

// Batch Operations
public function batchVerifyAssets($assetIds, $notes)
public function batchAuthorizeAssets($assetIds, $notes)

// Workflow Queries
public function getAssetsByWorkflowStatus($workflowStatus, $projectId)
public function getWorkflowStatistics($projectId)
public function getAssetsPendingVerification($projectId)
public function getAssetsPendingAuthorization($projectId)
public function getLegacyWorkflowStats($projectId)
```

**Dependencies:**
- Database
- Auth
- AssetCrudService
- AssetActivityService
- AssetValidationService

**Note:** createLegacyAsset() is 268 lines and should be further decomposed into smaller methods.

---

### Service 3: AssetQuantityService.php
**Responsibility:** Consumable inventory quantity management

**Line Estimate:** ~200 lines

**Methods:**
```php
public function consumeQuantity($assetId, $quantityToConsume, $reason)
public function restoreQuantity($assetId, $quantityToRestore, $reason)
public function getAvailableQuantity($assetId)
public function adjustQuantity($assetId, $adjustment, $reason)

// Helper methods
private function validateConsumable($asset)
private function calculateNewQuantity($currentQuantity, $adjustment)
```

**Dependencies:**
- Database
- AssetCrudService
- AssetActivityService
- CategoryModel

---

### Service 4: AssetProcurementService.php
**Responsibility:** Asset creation from procurement orders, PO linking

**Line Estimate:** ~400 lines

**Methods:**
```php
public function createAssetFromProcurementItem($procurementOrderId, $procurementItemId, $assetData)
public function generateAssetsFromProcurementItem($procurementOrderId, $procurementItemId, $assetData)
public function createAssetFromProcurement($procurementItem, $assetData)
public function getAssetsByProcurementOrder($procurementOrderId)

// Private helpers
private function linkAssetToProcurement($assetId, $procurementOrderId, $procurementItemId, $legacyProcurementId)
private function prepareProcurementAssetData($procurementItem, $assetData)
```

**Dependencies:**
- Database
- AssetCrudService
- AssetValidationService
- AssetActivityService
- ProcurementOrderModel
- ProcurementItemModel
- CategoryModel

---

### Service 5: AssetStatisticsService.php
**Responsibility:** Statistics, reporting, role-based analytics

**Line Estimate:** ~450 lines

**Methods:**
```php
// General statistics
public function getAssetStatistics($projectId)
public function getAssetUtilization($projectId)
public function getAssetValueReport($projectId)
public function getDepreciationReport($projectId)
public function getMaintenanceSchedule($projectId)
public function getAssetStats()

// Role-specific statistics
public function getRoleSpecificStatistics($userRole, $projectId)

// Private role-specific methods
private function getProjectManagerStats($projectId)
private function getSiteInventoryClerkStats($projectId)
private function getWarehousemanStats($projectId)
private function getSystemAdminStats()
private function getFinanceDirectorStats()
private function getAssetDirectorStats()
```

**Dependencies:**
- Database
- Auth
- UserModel

---

### Service 6: AssetQueryService.php
**Responsibility:** Complex queries, search, filtering, pagination

**Line Estimate:** ~400 lines

**Methods:**
```php
// Complex queries
public function getAssetsWithFilters($filters, $page, $perPage)        // 168 lines
public function getAvailableForBorrowing($projectId)                   // 82 lines

// Filtered queries
public function getAssetsByProject($projectId, $status)
public function getAvailableAssets($projectId)
public function getAssetsByCategory($categoryId, $projectId)
public function getAssetsByVendor($vendorId, $projectId)
public function getAssetsByBusinessType($assetType, $projectId, $filters)
public function getOverdueAssets($type)

// Specialized queries
public function getMaintenableAssets()
public function canBeMaintained($assetId)                              // 68 lines
public function getAvailableEquipmentCount($projectFilter)

// Private query builders
private function buildFilterConditions($filters)
private function buildOrderByClause($filters)
private function applyProjectScoping($currentUser)
```

**Dependencies:**
- Database
- Auth
- UserModel
- CategoryModel

---

### Service 7: AssetActivityService.php
**Responsibility:** Activity logging, audit trail, history tracking

**Line Estimate:** ~250 lines

**Methods:**
```php
public function logAssetActivity($assetId, $action, $description, $oldValues, $newValues)
public function getAssetHistory($assetId)
public function getCompleteActivityLogs($assetId, $limit)
public function logActivity($action, $description, $table, $recordId)

// Private helpers
private function formatActivityLog($log)
private function sanitizeLogData($data)
private function captureUserContext()
```

**Dependencies:**
- Database
- Auth

---

### Service 8: AssetValidationService.php
**Responsibility:** Business rules validation, eligibility checks

**Line Estimate:** ~300 lines

**Methods:**
```php
public function validateAssetBusinessRules($data)
public function validateAssetCreation($data)
public function validateAssetUpdate($id, $data)
public function validateCategoryRules($categoryId, $unitCost)
public function validateQuantityUpdate($assetId, $newQuantity)
public function checkProjectAccess($userId, $projectId)

// Private validation helpers
private function validateCapitalizationThreshold($category, $unitCost)
private function validateAssetGeneration($category)
private function validateConsumableQuantity($category, $quantity)
```

**Dependencies:**
- Database
- CategoryModel
- UserModel
- Validator

---

### Service 9: AssetExportService.php
**Responsibility:** Data export, report generation

**Line Estimate:** ~200 lines

**Methods:**
```php
public function exportAssets($filters)
public function exportToCsv($assets)
public function exportToExcel($assets)
public function generateAssetReport($filters)

// Private helpers
private function formatExportData($assets)
private function buildExportHeaders()
```

**Dependencies:**
- Database
- AssetQueryService

---

## Directory Structure

```
/Users/keithvincentranoa/Developer/ConstructLink/
├── services/
│   └── Asset/                                   # New directory
│       ├── AssetCrudService.php                 # ~350 lines
│       ├── AssetWorkflowService.php             # ~480 lines
│       ├── AssetQuantityService.php             # ~200 lines
│       ├── AssetProcurementService.php          # ~400 lines
│       ├── AssetStatisticsService.php           # ~450 lines
│       ├── AssetQueryService.php                # ~400 lines
│       ├── AssetActivityService.php             # ~250 lines
│       ├── AssetValidationService.php           # ~300 lines
│       └── AssetExportService.php               # ~200 lines
│
├── models/
│   ├── AssetModel.php                           # Refactored to ~300 lines (facade)
│   └── BaseModel.php                            # Unchanged
│
├── config/
│   └── services.php                             # New service registry (optional)
│
└── docs/
    └── refactoring/
        ├── AssetModel-Refactoring-Strategy.md   # This document
        └── AssetModel-Migration-Log.md          # Track migration progress
```

---

## Service Class Specifications

### Template Structure

Each service follows this pattern:

```php
<?php
/**
 * ConstructLink™ [Service Name]
 *
 * [Service Description]
 *
 * Responsibilities:
 * - [Responsibility 1]
 * - [Responsibility 2]
 *
 * Dependencies:
 * - [Dependency 1]
 * - [Dependency 2]
 *
 * @package ConstructLink
 * @version 1.0.0
 */

class [ServiceName] {
    private $db;
    private $dependency1;
    private $dependency2;

    /**
     * Constructor with dependency injection
     */
    public function __construct($db, $dependency1 = null, $dependency2 = null) {
        $this->db = $db;
        $this->dependency1 = $dependency1 ?? new Dependency1();
        $this->dependency2 = $dependency2 ?? new Dependency2();
    }

    // Public methods...

    // Private helper methods...
}
```

### Example: AssetCrudService.php

```php
<?php
/**
 * ConstructLink™ Asset CRUD Service
 *
 * Handles basic asset CRUD operations, retrieval, and status updates.
 *
 * Responsibilities:
 * - Asset creation with validation
 * - Asset updates with project access control
 * - Asset deletion with dependency checks
 * - Asset retrieval with details
 * - Status management (single and bulk)
 * - QR code generation
 *
 * Dependencies:
 * - Database: Database connection
 * - Auth: User authentication and authorization
 * - Validator: Input validation
 * - CategoryModel: Category information
 * - UserModel: User and project access
 * - AssetValidationService: Business rule validation
 * - AssetActivityService: Activity logging
 *
 * @package ConstructLink
 * @version 1.0.0
 */

class AssetCrudService {
    private $db;
    private $auth;
    private $validator;
    private $categoryModel;
    private $userModel;
    private $validationService;
    private $activityService;

    public function __construct(
        $db,
        $auth = null,
        $validator = null,
        $categoryModel = null,
        $userModel = null,
        $validationService = null,
        $activityService = null
    ) {
        $this->db = $db;
        $this->auth = $auth ?? Auth::getInstance();
        $this->validator = $validator ?? new Validator();
        $this->categoryModel = $categoryModel ?? new CategoryModel();
        $this->userModel = $userModel ?? new UserModel();
        $this->validationService = $validationService ?? new AssetValidationService($db);
        $this->activityService = $activityService ?? new AssetActivityService($db);
    }

    /**
     * Create a new asset with validation and project scoping
     */
    public function createAsset($data) {
        try {
            // Validate business rules
            $validation = $this->validationService->validateAssetCreation($data);
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }

            // Check project access
            $currentUser = $this->auth->getCurrentUser();
            if (!$this->validationService->checkProjectAccess($currentUser['id'], $data['project_id'])) {
                return ['success' => false, 'message' => 'Access denied: You do not have access to this project'];
            }

            $this->db->beginTransaction();

            // Generate asset reference if not provided
            if (empty($data['ref'])) {
                $data['ref'] = $this->generateAssetReference($data);
            }

            // Prepare asset data
            $assetData = $this->prepareAssetData($data);

            // Generate QR code if enabled
            if ($this->isQRCodeEnabled()) {
                $assetData['qr_code'] = $this->generateQRCode($assetData['ref']);
            }

            // Insert asset
            $assetId = $this->insertAsset($assetData);
            if (!$assetId) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Failed to create asset'];
            }

            // Log activity
            $this->activityService->logAssetActivity(
                $assetId,
                'asset_created',
                'Asset created',
                null,
                $assetData
            );

            $this->db->commit();

            $asset = $this->findById($assetId);
            return ['success' => true, 'asset' => $asset];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Asset creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create asset'];
        }
    }

    // Additional methods...
    // Private helpers...
}
```

---

## Dependency Graph

### Visual Representation

```
┌─────────────────────────────────────────────────────────────┐
│                      AssetModel (Facade)                     │
│                      ~300 lines                              │
│  - Delegates all operations to services                      │
│  - Maintains backward compatibility                          │
└───────────────────────────┬─────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
        ▼                   ▼                   ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│ AssetCrud    │    │ AssetQuery   │    │ AssetWork    │
│ Service      │    │ Service      │    │ flowService  │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                   │
       │            ┌──────┴──────┐           │
       │            │              │           │
       ▼            ▼              ▼           ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ AssetValid   │ │ AssetActiv   │ │ AssetStats   │
│ ationService │ │ ityService   │ │ Service      │
└──────────────┘ └──────────────┘ └──────────────┘

       │                   │
       ▼                   ▼
┌──────────────┐    ┌──────────────┐
│ AssetQuant   │    │ AssetProcure │
│ ityService   │    │ mentService  │
└──────────────┘    └──────────────┘

       │
       ▼
┌──────────────┐
│ AssetExport  │
│ Service      │
└──────────────┘
```

### Service Dependencies Matrix

| Service | Depends On |
|---------|-----------|
| **AssetCrudService** | Database, Auth, Validator, CategoryModel, UserModel, AssetValidationService, AssetActivityService |
| **AssetWorkflowService** | Database, Auth, AssetCrudService, AssetActivityService, AssetValidationService |
| **AssetQuantityService** | Database, AssetCrudService, AssetActivityService, CategoryModel |
| **AssetProcurementService** | Database, AssetCrudService, AssetValidationService, AssetActivityService, ProcurementOrderModel, ProcurementItemModel, CategoryModel |
| **AssetStatisticsService** | Database, Auth, UserModel |
| **AssetQueryService** | Database, Auth, UserModel, CategoryModel |
| **AssetActivityService** | Database, Auth |
| **AssetValidationService** | Database, CategoryModel, UserModel, Validator |
| **AssetExportService** | Database, AssetQueryService |

### External Model Dependencies

```
Services depend on:
├── Auth (singleton)
├── Validator (utility class)
├── Database (singleton)
├── CategoryModel
├── UserModel
├── ProcurementOrderModel
├── ProcurementItemModel
├── MaintenanceModel
├── AssetStandardizer
└── AssetSubtypeManager
```

---

## Migration Strategy

### Phase 1: Preparation (Day 1)

#### 1.1 Create Service Directory Structure
```bash
mkdir -p services/Asset
```

#### 1.2 Create Base Service Files
- Create stub files for all 9 services
- Add PHPDoc headers
- Define method signatures (no implementation yet)

#### 1.3 Backup Current AssetModel
```bash
cp models/AssetModel.php models/AssetModel.php.backup
cp models/AssetModel.php _archive/AssetModel-pre-refactor-$(date +%Y%m%d).php
```

#### 1.4 Set Up Testing Environment
- Prepare test database
- Document current API endpoints using AssetModel
- Create test suite for regression testing

---

### Phase 2: Service Implementation (Days 2-3)

#### Order of Implementation (by dependency hierarchy)

**Day 2 Morning: Foundation Services**

1. **AssetActivityService** (no service dependencies)
   - Extract logging methods
   - Test independently
   - Estimated: 2 hours

2. **AssetValidationService** (no service dependencies)
   - Extract validation methods
   - Test business rules
   - Estimated: 2 hours

**Day 2 Afternoon: Core Services**

3. **AssetCrudService** (depends on Activity + Validation)
   - Extract basic CRUD
   - Integrate with foundation services
   - Test create/update/delete operations
   - Estimated: 3 hours

4. **AssetQuantityService** (depends on CRUD + Activity)
   - Extract quantity management
   - Test consumable workflows
   - Estimated: 1 hour

**Day 3 Morning: Query & Statistics**

5. **AssetQueryService** (minimal dependencies)
   - Extract complex queries
   - Optimize SQL
   - Test pagination and filtering
   - Estimated: 3 hours

6. **AssetStatisticsService** (minimal dependencies)
   - Extract statistics methods
   - Test role-based stats
   - Estimated: 2 hours

**Day 3 Afternoon: Specialized Services**

7. **AssetWorkflowService** (depends on CRUD + Activity + Validation)
   - Extract MVA workflow
   - Test approval chains
   - Estimated: 3 hours

8. **AssetProcurementService** (depends on CRUD + Validation + Activity)
   - Extract procurement integration
   - Test asset generation from POs
   - Estimated: 2 hours

9. **AssetExportService** (depends on Query)
   - Extract export functionality
   - Test CSV/Excel generation
   - Estimated: 1 hour

---

### Phase 3: Facade Implementation (Day 4 Morning)

#### 3.1 Refactor AssetModel to Facade

Transform AssetModel from god object to thin facade:

```php
<?php
/**
 * ConstructLink™ Asset Model (Facade)
 *
 * This model now acts as a facade to the underlying asset services.
 * All business logic has been moved to specialized services.
 *
 * Backward Compatibility: All existing method signatures preserved.
 */

class AssetModel extends BaseModel {
    protected $table = 'assets';
    protected $fillable = [/* ... */];

    // Service instances
    private $crudService;
    private $workflowService;
    private $quantityService;
    private $procurementService;
    private $statisticsService;
    private $queryService;
    private $activityService;
    private $validationService;
    private $exportService;

    public function __construct() {
        parent::__construct();
        $this->initializeServices();
    }

    /**
     * Initialize all asset services
     */
    private function initializeServices() {
        // Foundation services
        $this->activityService = new AssetActivityService($this->db);
        $this->validationService = new AssetValidationService($this->db);

        // Core services
        $this->crudService = new AssetCrudService(
            $this->db,
            null, // Auth
            null, // Validator
            null, // CategoryModel
            null, // UserModel
            $this->validationService,
            $this->activityService
        );

        $this->quantityService = new AssetQuantityService(
            $this->db,
            $this->crudService,
            $this->activityService
        );

        // Query & statistics
        $this->queryService = new AssetQueryService($this->db);
        $this->statisticsService = new AssetStatisticsService($this->db);

        // Specialized services
        $this->workflowService = new AssetWorkflowService(
            $this->db,
            $this->crudService,
            $this->activityService,
            $this->validationService
        );

        $this->procurementService = new AssetProcurementService(
            $this->db,
            $this->crudService,
            $this->validationService,
            $this->activityService
        );

        $this->exportService = new AssetExportService(
            $this->db,
            $this->queryService
        );
    }

    /**
     * Override base create to preserve discipline handling
     */
    public function create($data) {
        // Handle discipline processing (existing logic)
        // ...
        return parent::create($data);
    }

    // ============================================================
    // FACADE METHODS - Delegate to Services
    // ============================================================

    /**
     * Create asset - delegates to AssetCrudService
     */
    public function createAsset($data) {
        return $this->crudService->createAsset($data);
    }

    /**
     * Update asset - delegates to AssetCrudService
     */
    public function updateAsset($id, $data) {
        return $this->crudService->updateAsset($id, $data);
    }

    /**
     * Delete asset - delegates to AssetCrudService
     */
    public function deleteAsset($id) {
        return $this->crudService->deleteAsset($id);
    }

    /**
     * Get asset with details - delegates to AssetCrudService
     */
    public function getAssetWithDetails($id) {
        return $this->crudService->getAssetWithDetails($id);
    }

    /**
     * Find by QR code - delegates to AssetCrudService
     */
    public function findByQRCode($qrCode) {
        return $this->crudService->findByQRCode($qrCode);
    }

    /**
     * Update asset status - delegates to AssetCrudService
     */
    public function updateAssetStatus($id, $status, $notes = null) {
        return $this->crudService->updateAssetStatus($id, $status, $notes);
    }

    /**
     * Bulk update status - delegates to AssetCrudService
     */
    public function bulkUpdateStatus($assetIds, $status, $notes = null) {
        return $this->crudService->bulkUpdateStatus($assetIds, $status, $notes);
    }

    // Workflow methods - delegate to AssetWorkflowService
    public function submitForVerification($assetId, $submittedBy) {
        return $this->workflowService->submitForVerification($assetId, $submittedBy);
    }

    public function verifyAsset($assetId, $verifiedBy, $notes = null) {
        return $this->workflowService->verifyAsset($assetId, $verifiedBy, $notes);
    }

    public function authorizeAsset($assetId, $authorizedBy, $notes = null) {
        return $this->workflowService->authorizeAsset($assetId, $authorizedBy, $notes);
    }

    public function rejectAsset($assetId, $rejectedBy, $rejectionReason) {
        return $this->workflowService->rejectAsset($assetId, $rejectedBy, $rejectionReason);
    }

    public function createLegacyAsset($data) {
        return $this->workflowService->createLegacyAsset($data);
    }

    public function verifyLegacyAsset($assetId, $notes = '') {
        return $this->workflowService->verifyLegacyAsset($assetId, $notes);
    }

    public function authorizeLegacyAsset($assetId, $notes = '') {
        return $this->workflowService->authorizeLegacyAsset($assetId, $notes);
    }

    public function batchVerifyAssets($assetIds, $notes = '') {
        return $this->workflowService->batchVerifyAssets($assetIds, $notes);
    }

    public function batchAuthorizeAssets($assetIds, $notes = '') {
        return $this->workflowService->batchAuthorizeAssets($assetIds, $notes);
    }

    public function getAssetsByWorkflowStatus($workflowStatus, $projectId = null) {
        return $this->workflowService->getAssetsByWorkflowStatus($workflowStatus, $projectId);
    }

    public function getWorkflowStatistics($projectId = null) {
        return $this->workflowService->getWorkflowStatistics($projectId);
    }

    public function getAssetsPendingVerification($projectId = null) {
        return $this->workflowService->getAssetsPendingVerification($projectId);
    }

    public function getAssetsPendingAuthorization($projectId = null) {
        return $this->workflowService->getAssetsPendingAuthorization($projectId);
    }

    public function getLegacyWorkflowStats($projectId = null) {
        return $this->workflowService->getLegacyWorkflowStats($projectId);
    }

    // Quantity methods - delegate to AssetQuantityService
    public function consumeQuantity($assetId, $quantityToConsume, $reason = null) {
        return $this->quantityService->consumeQuantity($assetId, $quantityToConsume, $reason);
    }

    public function restoreQuantity($assetId, $quantityToRestore, $reason = null) {
        return $this->quantityService->restoreQuantity($assetId, $quantityToRestore, $reason);
    }

    // Procurement methods - delegate to AssetProcurementService
    public function createAssetFromProcurementItem($procurementOrderId, $procurementItemId, $assetData = []) {
        return $this->procurementService->createAssetFromProcurementItem($procurementOrderId, $procurementItemId, $assetData);
    }

    public function generateAssetsFromProcurementItem($procurementOrderId, $procurementItemId, $assetData = []) {
        return $this->procurementService->generateAssetsFromProcurementItem($procurementOrderId, $procurementItemId, $assetData);
    }

    public function createAssetFromProcurement($procurementItem, $assetData = []) {
        return $this->procurementService->createAssetFromProcurement($procurementItem, $assetData);
    }

    public function getAssetsByProcurementOrder($procurementOrderId) {
        return $this->procurementService->getAssetsByProcurementOrder($procurementOrderId);
    }

    // Statistics methods - delegate to AssetStatisticsService
    public function getAssetStatistics($projectId = null) {
        return $this->statisticsService->getAssetStatistics($projectId);
    }

    public function getAssetUtilization($projectId = null) {
        return $this->statisticsService->getAssetUtilization($projectId);
    }

    public function getAssetValueReport($projectId = null) {
        return $this->statisticsService->getAssetValueReport($projectId);
    }

    public function getDepreciationReport($projectId = null) {
        return $this->statisticsService->getDepreciationReport($projectId);
    }

    public function getMaintenanceSchedule($projectId = null) {
        return $this->statisticsService->getMaintenanceSchedule($projectId);
    }

    public function getAssetStats() {
        return $this->statisticsService->getAssetStats();
    }

    public function getRoleSpecificStatistics($userRole, $projectId = null) {
        return $this->statisticsService->getRoleSpecificStatistics($userRole, $projectId);
    }

    // Query methods - delegate to AssetQueryService
    public function getAssetsWithFilters($filters = [], $page = 1, $perPage = 20) {
        return $this->queryService->getAssetsWithFilters($filters, $page, $perPage);
    }

    public function getAssetsByProject($projectId, $status = null) {
        return $this->queryService->getAssetsByProject($projectId, $status);
    }

    public function getAvailableAssets($projectId = null) {
        return $this->queryService->getAvailableAssets($projectId);
    }

    public function getAssetsByCategory($categoryId, $projectId = null) {
        return $this->queryService->getAssetsByCategory($categoryId, $projectId);
    }

    public function getAssetsByVendor($vendorId, $projectId = null) {
        return $this->queryService->getAssetsByVendor($vendorId, $projectId);
    }

    public function getOverdueAssets($type = 'maintenance') {
        return $this->queryService->getOverdueAssets($type);
    }

    public function getAssetsByBusinessType($assetType = null, $projectId = null, $filters = []) {
        return $this->queryService->getAssetsByBusinessType($assetType, $projectId, $filters);
    }

    public function getMaintenableAssets() {
        return $this->queryService->getMaintenableAssets();
    }

    public function canBeMaintained($assetId) {
        return $this->queryService->canBeMaintained($assetId);
    }

    public function getAvailableEquipmentCount($projectFilter = null) {
        return $this->queryService->getAvailableEquipmentCount($projectFilter);
    }

    public function getAvailableForBorrowing($projectId = null) {
        return $this->queryService->getAvailableForBorrowing($projectId);
    }

    public function getAssetProjectId($assetId) {
        return $this->queryService->getAssetProjectId($assetId);
    }

    // Activity methods - delegate to AssetActivityService
    public function getAssetHistory($assetId) {
        return $this->activityService->getAssetHistory($assetId);
    }

    public function getCompleteActivityLogs($assetId, $limit = null) {
        return $this->activityService->getCompleteActivityLogs($assetId, $limit);
    }

    // Validation methods - delegate to AssetValidationService
    public function validateAssetBusinessRules($data) {
        return $this->validationService->validateAssetBusinessRules($data);
    }

    // Export methods - delegate to AssetExportService
    public function exportAssets($filters = []) {
        return $this->exportService->exportAssets($filters);
    }
}
```

**Result:** AssetModel.php reduced from 3,317 lines to ~300 lines

---

### Phase 4: Integration Testing (Day 4 Afternoon)

#### 4.1 Test All Controllers
- Test every controller method that uses AssetModel
- Verify same responses
- Check for regressions

#### 4.2 Affected Controllers (from codebase analysis)
```
controllers/AssetController.php
controllers/BorrowedToolController.php
controllers/TransferController.php
controllers/WithdrawalController.php
controllers/MaintenanceController.php
controllers/ProcurementController.php
controllers/DashboardController.php
```

#### 4.3 Integration Test Checklist
- [ ] Asset creation (regular, legacy, from procurement)
- [ ] Asset updates (status, details, bulk)
- [ ] Asset deletion (with dependency checks)
- [ ] MVA workflow (submit, verify, authorize, reject)
- [ ] Quantity management (consume, restore)
- [ ] Queries (filters, pagination, search)
- [ ] Statistics (all role-based variants)
- [ ] Activity logs (history, audit trail)
- [ ] Export functionality
- [ ] Project access control
- [ ] Borrowing/Transfer/Withdrawal flows

---

### Phase 5: Deployment (Day 5)

#### 5.1 Code Review
- Peer review of all services
- Security audit (SQL injection, XSS, authorization)
- Performance review (N+1 queries, caching)

#### 5.2 Documentation Update
- Update API documentation
- Document new service architecture
- Create service usage examples

#### 5.3 Deployment Steps
1. Backup production database
2. Deploy services directory
3. Deploy refactored AssetModel
4. Monitor logs for errors
5. Rollback plan ready

#### 5.4 Post-Deployment Monitoring
- Monitor error logs for 24 hours
- Check performance metrics
- User acceptance testing

---

## Backward Compatibility Plan

### Strategy: Facade Pattern + Delegation

**Goal:** Zero breaking changes for existing code

### Implementation Details

1. **All Public Methods Preserved**
   - Every public method in original AssetModel remains accessible
   - Same method signatures
   - Same return formats
   - Same error handling

2. **Controllers Require No Changes**
   - Existing controller code works unchanged:
   ```php
   // This continues to work exactly as before
   $assetModel = new AssetModel();
   $result = $assetModel->createAsset($data);
   ```

3. **Service Access (Optional)**
   - New code CAN access services directly:
   ```php
   // Option 1: Through facade (backward compatible)
   $assetModel = new AssetModel();
   $result = $assetModel->createAsset($data);

   // Option 2: Direct service access (new code)
   $crudService = new AssetCrudService($db);
   $result = $crudService->createAsset($data);
   ```

4. **Gradual Migration Path**
   - Phase 1: Services created, AssetModel delegates (ZERO changes needed)
   - Phase 2: New features use services directly
   - Phase 3: Gradually refactor controllers to use services
   - Phase 4: Eventually deprecate AssetModel facade (future)

### Breaking Change Prevention

**Potential Issues & Solutions:**

| Potential Issue | Solution |
|----------------|----------|
| Method signature changes | NONE - All signatures preserved in facade |
| Return format changes | Services return same format as original |
| Transaction handling | Each service manages its own transactions, facade coordinates |
| Error messages | Preserve exact same error messages and codes |
| Dependency injection | Services use optional parameters with defaults |

---

## Testing Strategy

### Unit Testing (Per Service)

Each service gets its own test file:

```
tests/services/Asset/
├── AssetCrudServiceTest.php
├── AssetWorkflowServiceTest.php
├── AssetQuantityServiceTest.php
├── AssetProcurementServiceTest.php
├── AssetStatisticsServiceTest.php
├── AssetQueryServiceTest.php
├── AssetActivityServiceTest.php
├── AssetValidationServiceTest.php
└── AssetExportServiceTest.php
```

### Integration Testing

```php
// Example integration test
class AssetModelRefactorTest extends TestCase {
    private $assetModel;
    private $testProjectId;
    private $testCategoryId;

    public function setUp() {
        $this->assetModel = new AssetModel();
        $this->testProjectId = 1;
        $this->testCategoryId = 1;
    }

    public function testCreateAsset_BackwardCompatibility() {
        $data = [
            'name' => 'Test Asset',
            'category_id' => $this->testCategoryId,
            'project_id' => $this->testProjectId,
            'acquired_date' => date('Y-m-d')
        ];

        $result = $this->assetModel->createAsset($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('asset', $result);
        $this->assertEquals('Test Asset', $result['asset']['name']);
    }

    public function testWorkflowChain_MVA() {
        // Create asset
        $asset = $this->createTestAsset();

        // Submit for verification
        $submitResult = $this->assetModel->submitForVerification($asset['id'], 1);
        $this->assertTrue($submitResult['success']);

        // Verify
        $verifyResult = $this->assetModel->verifyAsset($asset['id'], 2, 'Verified');
        $this->assertTrue($verifyResult['success']);

        // Authorize
        $authorizeResult = $this->assetModel->authorizeAsset($asset['id'], 3, 'Authorized');
        $this->assertTrue($authorizeResult['success']);

        // Check final status
        $updatedAsset = $this->assetModel->find($asset['id']);
        $this->assertEquals('approved', $updatedAsset['workflow_status']);
        $this->assertEquals('available', $updatedAsset['status']);
    }

    // More test cases...
}
```

### Regression Testing

**Critical User Flows to Test:**

1. **Asset Creation Flow**
   - Manual asset creation
   - Asset creation from procurement
   - Legacy asset creation
   - Batch asset generation

2. **Asset Lifecycle Flow**
   - Status updates
   - Quantity adjustments
   - Borrowing/returning
   - Transfers
   - Maintenance scheduling

3. **MVA Workflow Flow**
   - Submit → Verify → Authorize
   - Submit → Reject
   - Batch verify
   - Batch authorize

4. **Reporting Flow**
   - Dashboard statistics
   - Role-specific stats
   - Asset utilization
   - Depreciation reports
   - Export functionality

5. **Search & Filter Flow**
   - Complex filters
   - Pagination
   - Project scoping
   - Category filtering

---

## Risk Assessment

### Risk Matrix

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| **Breaking changes in existing controllers** | HIGH | LOW | Facade pattern ensures backward compatibility |
| **Performance degradation** | MEDIUM | LOW | Service initialization only happens once, minimal overhead |
| **Transaction handling issues** | HIGH | MEDIUM | Each service manages transactions, careful coordination needed |
| **Missing method delegation** | MEDIUM | MEDIUM | Comprehensive checklist, automated testing |
| **Service initialization failures** | HIGH | LOW | Graceful fallbacks, error logging |
| **Circular dependencies** | MEDIUM | LOW | Dependency injection with optional params |
| **Database migration issues** | MEDIUM | LOW | No database changes required |
| **Testing coverage gaps** | MEDIUM | MEDIUM | Comprehensive test suite, regression tests |

### Critical Success Factors

✅ **Must Have:**
1. Zero breaking changes for existing code
2. All 63 methods accessible and functional
3. Same return formats and error messages
4. Transaction integrity maintained
5. Performance equivalent or better
6. Complete test coverage

✅ **Should Have:**
1. Improved code maintainability
2. Easier to test individual services
3. Clear separation of concerns
4. Better documentation
5. Performance monitoring

✅ **Nice to Have:**
1. Service registry for dependency management
2. Caching layer for frequently accessed data
3. Event system for cross-service communication
4. Metrics and observability

---

## Database Considerations

### Current Table: `assets`

**Note:** The assets table was NOT renamed to `inventory_assets` like other tables.

**Supporting tables already migrated:**
- `inventory_disciplines`
- `inventory_brands`
- `inventory_stock`

**No database migration required** for this refactoring.

### Future Database Optimization (Optional)

Consider these optimizations after service refactoring:

1. **Indexes for Performance**
   ```sql
   -- Add indexes for frequently filtered columns
   CREATE INDEX idx_assets_workflow_status ON assets(workflow_status);
   CREATE INDEX idx_assets_status_project ON assets(status, project_id);
   CREATE INDEX idx_assets_category_project ON assets(category_id, project_id);
   ```

2. **Materialized Views for Statistics**
   ```sql
   -- Create view for asset statistics (refresh periodically)
   CREATE VIEW v_asset_statistics AS
   SELECT
       project_id,
       COUNT(*) as total_assets,
       SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_count,
       SUM(acquisition_cost) as total_value
   FROM assets
   GROUP BY project_id;
   ```

3. **Partitioning for Large Datasets**
   - Partition assets by project_id or year
   - Consider if asset table grows beyond 100K records

---

## Performance Optimization Opportunities

### 1. Service Initialization Caching

**Problem:** Each request creates new service instances

**Solution:** Lazy initialization + singleton pattern

```php
class AssetModel extends BaseModel {
    private static $serviceInstances = [];

    private function getService($serviceName) {
        if (!isset(self::$serviceInstances[$serviceName])) {
            self::$serviceInstances[$serviceName] = $this->createService($serviceName);
        }
        return self::$serviceInstances[$serviceName];
    }
}
```

### 2. Query Optimization

**AssetQueryService opportunities:**
- Use LEFT JOIN aggregation to eliminate N+1 queries (already done in `getAvailableForBorrowing`)
- Add query result caching for frequently accessed data
- Implement query builder pattern for complex filters

### 3. Batch Operations

**AssetWorkflowService optimization:**
- Batch database updates in `batchVerifyAssets()` and `batchAuthorizeAssets()`
- Single transaction for multiple assets
- Bulk activity logging

---

## Post-Refactoring Improvements

### Phase 2 Enhancements (After Successful Migration)

1. **Add Interfaces**
   ```php
   interface AssetCrudInterface {
       public function createAsset($data);
       public function updateAsset($id, $data);
       public function deleteAsset($id);
   }

   class AssetCrudService implements AssetCrudInterface {
       // Implementation...
   }
   ```

2. **Event System**
   ```php
   // Dispatch events for cross-cutting concerns
   EventDispatcher::dispatch('asset.created', $asset);
   EventDispatcher::dispatch('asset.workflow.approved', $asset);
   ```

3. **Caching Layer**
   ```php
   class AssetQueryService {
       private $cache;

       public function getAssetWithDetails($id) {
           $cacheKey = "asset_details_{$id}";
           return $this->cache->remember($cacheKey, 3600, function() use ($id) {
               return $this->fetchAssetDetails($id);
           });
       }
   }
   ```

4. **Metrics & Observability**
   ```php
   class AssetCrudService {
       public function createAsset($data) {
           $startTime = microtime(true);

           $result = $this->performCreate($data);

           $duration = microtime(true) - $startTime;
           Metrics::timing('asset.create', $duration);

           return $result;
       }
   }
   ```

---

## Success Metrics

### Quantitative Metrics

| Metric | Before | Target After | Measure |
|--------|--------|--------------|---------|
| **Lines per file** | 3,317 | < 500 | Line count |
| **Cyclomatic complexity** | High (250+) | < 10 per method | Code analysis |
| **Test coverage** | Unknown | > 80% | PHPUnit |
| **Response time (avg)** | Baseline | ≤ Baseline | APM |
| **Memory usage** | Baseline | ≤ Baseline + 5% | PHP profiler |
| **Number of files** | 1 model | 1 model + 9 services | File count |

### Qualitative Metrics

✅ **Code Quality**
- Single Responsibility Principle enforced
- Dependency injection throughout
- Clear separation of concerns
- Improved testability

✅ **Developer Experience**
- Easier to locate specific functionality
- Faster onboarding for new developers
- Clearer code documentation
- Reduced cognitive load

✅ **Maintainability**
- Isolated changes (modify one service)
- Easier debugging (smaller scope)
- Better error handling
- Improved logging

---

## Rollback Plan

### If Issues Arise Post-Deployment

**Rollback Steps:**

1. **Immediate Rollback (< 5 minutes)**
   ```bash
   # Restore backup AssetModel
   cp models/AssetModel.php.backup models/AssetModel.php

   # Remove services directory
   rm -rf services/Asset/

   # Clear application cache
   php cli/clear-cache.php

   # Restart web server
   sudo systemctl restart apache2
   ```

2. **Verify Rollback**
   - Test asset creation
   - Test workflow operations
   - Check error logs

3. **Post-Mortem**
   - Analyze failure cause
   - Document issues
   - Plan remediation

### Rollback Decision Criteria

**Rollback if:**
- Critical functionality broken (asset creation fails)
- Data integrity issues (transactions not committed)
- Performance degradation > 50%
- Multiple error reports from users
- Security vulnerabilities detected

**Do NOT rollback if:**
- Minor UI issues
- Non-critical features affected
- Performance degradation < 20%
- Isolated incidents

---

## Timeline Summary

| Phase | Duration | Activities |
|-------|----------|-----------|
| **Phase 1: Preparation** | 4 hours | Directory setup, backups, test environment |
| **Phase 2: Service Implementation** | 16 hours | Implement all 9 services with tests |
| **Phase 3: Facade Implementation** | 4 hours | Refactor AssetModel to facade |
| **Phase 4: Integration Testing** | 8 hours | Test all controllers, regression tests |
| **Phase 5: Deployment** | 4 hours | Code review, deploy, monitor |
| **Total** | **36 hours** | **3-5 days with proper testing** |

---

## Conclusion

This refactoring strategy transforms AssetModel.php from a 3,317-line god object into a clean, maintainable architecture with:

✅ **9 focused service classes** (each < 500 lines)
✅ **Clear separation of concerns** (SOLID principles)
✅ **100% backward compatibility** (facade pattern)
✅ **Improved testability** (isolated services)
✅ **Better maintainability** (single responsibility)
✅ **Future-proof architecture** (easy to extend)

**Risk Level:** MEDIUM (mitigated by facade pattern and comprehensive testing)

**Recommended Approach:** Proceed with Phase 1 preparation and service implementation in development environment first, then deploy to staging for thorough testing before production.

---

## Next Steps

1. **Review this document** with the development team
2. **Approve the refactoring strategy**
3. **Set up development environment** for refactoring
4. **Begin Phase 1: Preparation**
5. **Track progress** in `docs/refactoring/AssetModel-Migration-Log.md`

---

**Document Version:** 1.0.0
**Last Updated:** 2025-11-05
**Author:** Code Review Agent
**Status:** PROPOSED - AWAITING APPROVAL

---
