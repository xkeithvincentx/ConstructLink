# Request Module Architecture

## Visual Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                     REQUEST MODULE ARCHITECTURE                      │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                           PRESENTATION LAYER                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  RequestController (controllers/RequestController.php)               │
│  ├── index()       - List requests                                   │
│  ├── create()      - Create form & handler                           │
│  ├── view()        - View details                                    │
│  ├── submit()      - Submit request                                  │
│  ├── approve()     - Approve/Decline                                 │
│  └── export()      - Export to Excel                                 │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                          SERVICE LAYER                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ RequestFilterService (services/Request/)                     │   │
│  │ ├── applyRoleBasedFilters()                                  │   │
│  │ ├── buildFiltersFromRequest()                                │   │
│  │ ├── validateFilters()                                        │   │
│  │ └── getFilterOptions()                                       │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ RequestValidationService (services/Request/)                 │   │
│  │ ├── validateCreateRequest()                                  │   │
│  │ ├── canCreateRequestType()                                   │   │
│  │ ├── validateStatusTransition()                               │   │
│  │ └── getAllowedRequestTypes()                                 │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ RequestPermissionService (services/Request/)                 │   │
│  │ ├── canViewRequest()                                         │   │
│  │ ├── canEditRequest()                                         │   │
│  │ ├── canApproveRequest()                                      │   │
│  │ └── getAvailableActions()                                    │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                       BUSINESS LOGIC LAYER                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌──────────────────────┐     ┌──────────────────────────────┐     │
│  │ RequestModelFacade   │────▶│ models/RequestModel.php      │     │
│  │ (Backward Compat)    │     │ (Delegates to specialized)   │     │
│  └──────────────────────┘     └──────────────────────────────┘     │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         DATA ACCESS LAYER                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ RequestModel (models/Request/RequestModel.php)               │   │
│  │ Responsibility: CRUD Operations                              │   │
│  │ ├── createRequest()                                          │   │
│  │ ├── updateRequest()                                          │   │
│  │ ├── deleteRequest()                                          │   │
│  │ ├── getRequestWithDetails()                                  │   │
│  │ ├── getRequestsWithFilters()                                 │   │
│  │ └── linkToProcurementOrder()                                 │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ RequestWorkflowModel (models/Request/)                       │   │
│  │ Responsibility: Workflow & Status Management                 │   │
│  │ ├── submitRequest()                                          │   │
│  │ ├── updateRequestStatus()                                    │   │
│  │ ├── getRequestWithWorkflow()                                 │   │
│  │ ├── getPendingRequests()                                     │   │
│  │ └── getApprovedRequestsForProcurement()                      │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ RequestStatisticsModel (models/Request/)                     │   │
│  │ Responsibility: Statistics & Reporting                       │   │
│  │ ├── getRequestStatistics()                                   │   │
│  │ ├── getRequestsByType()                                      │   │
│  │ ├── getRequestsByUrgency()                                   │   │
│  │ └── getApprovalRate()                                        │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ RequestDeliveryModel (models/Request/)                       │   │
│  │ Responsibility: Delivery Tracking                            │   │
│  │ ├── getRequestWithDeliveryStatus()                           │   │
│  │ ├── getRequestsWithDeliveryTracking()                        │   │
│  │ ├── getDeliveryAlerts()                                      │   │
│  │ └── getDeliveryStatistics()                                  │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ RequestRestockModel (models/Request/)                        │   │
│  │ Responsibility: Restock Operations                           │   │
│  │ ├── getRestockDetails()                                      │   │
│  │ ├── validateRestockRequest()                                 │   │
│  │ ├── getInventoryItemsForRestock()                            │   │
│  │ └── getLowStockItems()                                       │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ RequestActivityModel (models/Request/)                       │   │
│  │ Responsibility: Activity Logging                             │   │
│  │ ├── logRequestActivity()                                     │   │
│  │ ├── getRequestLogs()                                         │   │
│  │ ├── getRecentActivities()                                    │   │
│  │ └── getActivityStatistics()                                  │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  All models extend BaseModel                                         │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                           DATABASE LAYER                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐     │
│  │  requests    │  │ request_logs │  │ procurement_orders   │     │
│  │  table       │  │  table       │  │ table                │     │
│  └──────────────┘  └──────────────┘  └──────────────────────┘     │
│                                                                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐     │
│  │  projects    │  │  users       │  │ inventory_items      │     │
│  │  table       │  │  table       │  │ table                │     │
│  └──────────────┘  └──────────────┘  └──────────────────────┘     │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Data Flow Examples

### Example 1: Create Request

```
User Request (HTTP POST)
    ↓
RequestController::create()
    ↓
RequestValidationService::validateCreateRequest() ← Check business rules
    ↓
RequestModel::createRequest() ← Create in database
    ↓
RequestActivityModel::logRequestActivity() ← Log action
    ↓
Response (Success/Error)
```

### Example 2: List Requests with Filters

```
User Request (HTTP GET with filters)
    ↓
RequestController::index()
    ↓
RequestFilterService::buildFiltersFromRequest() ← Parse GET params
    ↓
RequestFilterService::applyRoleBasedFilters() ← Apply role restrictions
    ↓
RequestModel::getRequestsWithFilters() ← Query database
    ↓
Response (Paginated results)
```

### Example 3: Approve Request (MVA Workflow)

```
User Request (HTTP POST)
    ↓
RequestController::approve()
    ↓
RequestPermissionService::canApproveRequest() ← Check permissions
    ↓
RequestWorkflowModel::updateRequestStatus() ← Update status
    ↓
RequestActivityModel::logRequestActivity() ← Log approval
    ↓
Response (Success/Error)
```

### Example 4: Get Statistics

```
User Request (HTTP GET)
    ↓
RequestController::getStats()
    ↓
RequestPermissionService::canViewStatistics() ← Check permissions
    ↓
RequestStatisticsModel::getRequestStatistics() ← Query aggregated data
    ↓
Response (JSON statistics)
```

---

## Dependency Graph

```
RequestController
    │
    ├──▶ RequestFilterService
    ├──▶ RequestValidationService
    ├──▶ RequestPermissionService
    │
    ├──▶ RequestModel ────────────▶ RequestActivityModel
    ├──▶ RequestWorkflowModel ────▶ RequestActivityModel
    ├──▶ RequestStatisticsModel
    ├──▶ RequestDeliveryModel
    └──▶ RequestRestockModel ─────▶ RequestActivityModel

Legend:
  ───▶ Direct dependency
  ════▶ Optional dependency
```

---

## Responsibilities Matrix

| Component | CRUD | Workflow | Statistics | Delivery | Restock | Activity | Validation | Filter | Permission |
|-----------|------|----------|------------|----------|---------|----------|------------|--------|------------|
| RequestModel | ✓ | | | | | | | | |
| RequestWorkflowModel | | ✓ | | | | | | | |
| RequestStatisticsModel | | | ✓ | | | | | | |
| RequestDeliveryModel | | | | ✓ | | | | | |
| RequestRestockModel | | | | | ✓ | | | | |
| RequestActivityModel | | | | | | ✓ | | | |
| RequestFilterService | | | | | | | | ✓ | |
| RequestValidationService | | | | | | | ✓ | | |
| RequestPermissionService | | | | | | | | | ✓ |

**Single Responsibility**: Each component has exactly ONE primary responsibility.

---

## Communication Patterns

### Pattern 1: Controller → Service → Model

```
RequestController::create()
    ↓ (calls)
RequestValidationService::validateCreateRequest()
    ↓ (calls)
RequestModel::createRequest()
```

**Used for**: Business logic that requires validation before data access

### Pattern 2: Controller → Model → Model

```
RequestController::view()
    ↓ (calls)
RequestModel::getRequestWithDetails()
    ↓ (triggers)
RequestActivityModel::logRequestActivity()
```

**Used for**: CRUD operations with automatic logging

### Pattern 3: Model → Model (Delegation)

```
RequestModelFacade::getRequestStatistics()
    ↓ (delegates to)
RequestStatisticsModel::getRequestStatistics()
```

**Used for**: Backward compatibility

---

## Module Boundaries

```
┌─────────────────────────────────────────────────────┐
│             EXTERNAL DEPENDENCIES                   │
│  - Auth (authentication)                            │
│  - Database (data access)                           │
│  - ProjectModel (project data)                      │
│  - ProcurementOrderModel (procurement data)         │
│  - CategoryModel (category data)                    │
└─────────────────────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────┐
│          REQUEST MODULE BOUNDARY                    │
│  ┌───────────────────────────────────────────────┐ │
│  │  Public Interface (Controller + Services)     │ │
│  └───────────────────────────────────────────────┘ │
│  ┌───────────────────────────────────────────────┐ │
│  │  Business Logic Layer (Models)                │ │
│  └───────────────────────────────────────────────┘ │
│  ┌───────────────────────────────────────────────┐ │
│  │  Data Access Layer (BaseModel)                │ │
│  └───────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────┘
```

**Encapsulation**: All request-related logic contained within module boundary.

---

## File Size Visualization

```
Before Refactoring:
RequestModel.php         ████████████████████████████████ 1,411 lines ❌

After Refactoring:
RequestModel.php         ███████████ 374 lines ✓
RequestWorkflowModel     ███████████ 357 lines ✓
RequestStatisticsModel   █████████   308 lines ✓
RequestDeliveryModel     █████████████ 446 lines ✓
RequestRestockModel      ███████████ 382 lines ✓
RequestActivityModel     █████████   305 lines ✓
RequestFilterService     ████████    278 lines ✓
RequestValidationService █████████   302 lines ✓
RequestPermissionService ███████████ 366 lines ✓
RequestModelFacade       █████       192 lines ✓

Legend: Each █ = ~40 lines
Goal: All files under 500 lines ✓ ACHIEVED
```

---

## Testing Strategy

```
┌─────────────────────────────────────────────────────┐
│                  UNIT TESTS                         │
├─────────────────────────────────────────────────────┤
│  - Test each model method independently             │
│  - Mock database connections                        │
│  - Test service logic with mock models              │
│  - Verify validation rules                          │
│  - Check permission logic                           │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│               INTEGRATION TESTS                     │
├─────────────────────────────────────────────────────┤
│  - Test controller with real services               │
│  - Test model interactions                          │
│  - Verify workflow transitions                      │
│  - Test facade delegation                           │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│              ACCEPTANCE TESTS                       │
├─────────────────────────────────────────────────────┤
│  - Test complete user workflows                     │
│  - Verify role-based access control                 │
│  - Test backward compatibility                      │
│  - End-to-end request lifecycle                     │
└─────────────────────────────────────────────────────┘
```

---

## Scalability Considerations

1. **Horizontal Scaling**: Each model can be moved to separate services
2. **Caching**: Statistics and delivery models can implement caching
3. **Async Processing**: Activity logging can be queued
4. **Read Replicas**: Statistics queries can use read-only replicas
5. **Microservices**: Models can evolve into independent microservices

---

This architecture provides a solid foundation for maintainable, scalable, and testable code.
