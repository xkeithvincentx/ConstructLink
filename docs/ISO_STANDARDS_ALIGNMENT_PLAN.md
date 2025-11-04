# ISO Standards Alignment Plan for ConstructLink™
## Complete Implementation Roadmap

**Document Version:** 1.0
**Created:** November 4, 2025
**Status:** Implementation Plan
**Priority:** High - System-Wide Standards Compliance

---

## 📋 EXECUTIVE SUMMARY

### Current State Discovery

After code analysis, the **actual implementation** is:

```
┌────────────────────────────────────────────────────────────┐
│ REFERENCE GENERATION FLOW (ACTUAL)                         │
├────────────────────────────────────────────────────────────┤
│                                                             │
│ 1. Controller calls: AssetModel->createAsset($data)        │
│ 2. Model calls: generateAssetReference($cat, $disc, false) │
│ 3. Helper calls: ISO55000ReferenceGenerator->generate()    │
│ 4. Result: CON-2025-TO-CV-0001                            │
│                                                             │
│ For Legacy: generateAssetReference($cat, $disc, true)      │
│ Result: CON-LEG-TO-CV-0011                                 │
└────────────────────────────────────────────────────────────┘
```

**Key Finding**: System IS using ISO55000ReferenceGenerator for assets and transfers!

### Operations Coverage

| Operation | Has Reference? | Format | Standard Used | Compliance |
|-----------|---------------|--------|---------------|------------|
| **Assets** | ✅ YES | CON-2025-TO-CV-0001 | ISO 55000 format | ⚠️ Format only |
| **Transfers** | ✅ YES | CON-2025-EQ-ME-0001 | ISO 55000 format | ⚠️ Format only |
| **Borrowing** | ❌ NO | None | None | 0% |
| **Maintenance** | ❌ NO | None | None | 0% |
| **Incidents** | ❌ NO | None | None | 0% |
| **Withdrawals** | ❌ NO | None | None | 0% |

**Coverage**: 33% (2 of 6 operations)

---

## 🎯 PROBLEM STATEMENT

### Issue 1: Misunderstanding of ISO 55000

**What ISO 55000:2014 Actually Is:**
> "ISO 55000 is an asset management SYSTEM standard that defines requirements for establishing, implementing, maintaining, and improving an asset management system."

**What ConstructLink Uses It For:**
- ✅ Reference number format (CON-2025-TO-CV-0001)
- ❌ NOT for asset management processes
- ❌ NOT for lifecycle management
- ❌ NOT for risk management
- ❌ NOT for performance evaluation

**Severity**: The standard name is being misused. Reference format ≠ ISO 55000 compliance.

### Issue 2: ISO 9001:2015 Should Be Used for References

**ISO 9001:2015 Section 7.5 - Documented Information:**
> "The organization shall control documented information... Documentation shall be identified and described."

**This is the CORRECT standard for:**
- ✅ Document/record identification
- ✅ Traceability systems
- ✅ Reference numbering
- ✅ Version control
- ✅ Record management

### Issue 3: ISO 55000 Should Govern Operations

**ISO 55000:2014 Section 8.3 - Operational Planning and Control:**

Operations that SHOULD use ISO 55000 principles:
1. **Borrowing** (Asset Utilization - Section 8.3.1)
2. **Maintenance** (Asset Care - Section 8.3.3)
3. **Transfers** (Change Management - Section 8.3.2)
4. **Incidents** (Risk Management - Section 6.1)
5. **Withdrawals** (Asset Disposal - Section 8.3.4)

---

## 🔍 CURRENT IMPLEMENTATION ANALYSIS

### What Actually Works

**File: `helpers.php:266-282`**
```php
function generateAssetReference($categoryId = null, $disciplineId = null, $isLegacy = false) {
    try {
        // ✅ CORRECT: Uses ISO55000ReferenceGenerator
        require_once APP_ROOT . '/core/ISO55000ReferenceGenerator.php';
        $generator = new ISO55000ReferenceGenerator();

        return $generator->generateReference($categoryId, $disciplineId, $isLegacy);

    } catch (Exception $e) {
        error_log("ISO reference generation error: " . $e->getMessage());

        // Fallback to legacy format
        $prefix = "CL";
        $year = date('Y');
        return $prefix . $year . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
    }
}
```

**File: `AssetModel.php:114-118`**
```php
// Generate asset reference if not provided
if (empty($data['ref'])) {
    $data['ref'] = generateAssetReference(
        $data['category_id'] ?? null,
        $data['primary_discipline'] ?? null,
        false  // Regular asset
    );
}
```

**File: `AssetModel.php:2364-2368` (Legacy Assets)**
```php
// Generate asset reference if not provided
if (empty($data['ref'])) {
    $data['ref'] = generateAssetReference(
        $data['category_id'] ?? null,
        $data['primary_discipline'] ?? null,
        true  // ✅ Legacy asset - generates CON-LEG-TO-CV-0011
    );
}
```

**File: `TransferModel.php` (via generateTransferReference)**
```php
// Generates: CON-2025-EQ-ME-0001
$transferReference = generateTransferReference(
    $asset['category_id'] ?? null,
    $asset['primary_discipline'] ?? null
);
```

### What's Missing

**BorrowedToolModel.php:79-150**
```php
public function createBorrowedTool($data) {
    // ❌ NO REFERENCE GENERATION

    // Only has MVA workflow
    $data['maker_id'] = $userId;
    $data['status'] = 'draft';

    // Insert without reference
    $sql = "INSERT INTO borrowed_tools
            (asset_id, borrower_id, project_id, borrowed_date,
             return_date, status, maker_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
}
```

**MaintenanceModel.php, IncidentModel.php, WithdrawalModel.php**
- Same issue: No reference generation
- No ISO process implementation

---

## 🎯 SOLUTION ARCHITECTURE

### Part 1: Correct Standard Usage

```
┌─────────────────────────────────────────────────────────┐
│ ISO 9001:2015 - Document Control & Traceability        │
├─────────────────────────────────────────────────────────┤
│ USE FOR: Reference numbering across ALL operations     │
│                                                         │
│ Format: CON-[MODULE]-[YEAR]-[SEQ]                      │
│                                                         │
│ Examples:                                               │
│ - CON-AST-2025-0001  (Asset)                          │
│ - CON-BOR-2025-0042  (Borrow)                         │
│ - CON-TRF-2025-0123  (Transfer)                       │
│ - CON-MNT-2025-0045  (Maintenance)                    │
│ - CON-INC-2025-0012  (Incident)                       │
│ - CON-WTH-2025-0008  (Withdrawal)                     │
│                                                         │
│ WHY: ISO 9001 Section 7.5 explicitly covers           │
│      documented information control                     │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ ISO 55000:2014 - Asset Management Processes            │
├─────────────────────────────────────────────────────────┤
│ USE FOR: Operational procedures and lifecycle mgmt     │
│                                                         │
│ Section 8.3.1 - Asset Utilization (Borrowing):        │
│ - Authorization workflow based on risk                 │
│ - Usage tracking and limits                            │
│ - Performance monitoring                               │
│ - Condition verification on return                     │
│                                                         │
│ Section 8.3.3 - Maintenance:                           │
│ - Preventive maintenance schedules                     │
│ - Condition-based monitoring                           │
│ - Performance indicators (MTBF, MTTR)                  │
│ - Risk-based maintenance planning                      │
│                                                         │
│ Section 8.3.2 - Change Management (Transfers):         │
│ - Risk assessment on transfer                          │
│ - Performance impact analysis                          │
│ - Asset condition verification                         │
│ - Custody management procedures                        │
│                                                         │
│ Section 6.1 - Risk Management (Incidents):             │
│ - Risk identification and assessment                   │
│ - Impact analysis                                      │
│ - Corrective actions (CAPA)                           │
│ - Preventive measures                                  │
│ - Trend analysis                                       │
│                                                         │
│ Section 8.3.4 - Disposal (Withdrawals):               │
│ - End-of-life decision criteria                        │
│ - Value recovery assessment                            │
│ - Environmental compliance                             │
│ - Decommissioning procedures                           │
│                                                         │
│ SPECIAL: Withdrawals for consumables                   │
│ - Quantity tracking (non-returnable)                   │
│ - Usage monitoring                                     │
│ - Reorder point alerts                                 │
│ - Project allocation tracking                          │
└─────────────────────────────────────────────────────────┘
```

---

## 📊 IMPLEMENTATION PLAN

### Phase 1: Standardize Reference System (ISO 9001)
**Timeline:** Week 1-2
**Priority:** CRITICAL

#### Step 1.1: Rename ISO55000ReferenceGenerator

**Current Problem**: Misleading name - not about ISO 55000 compliance, just reference format.

**Action**: Rename and simplify

**File: Create `/core/ISO9001DocumentControl.php`**
```php
<?php
/**
 * ISO 9001:2015 Document Control System
 * Section 7.5 - Documented Information Control
 *
 * Provides standardized reference numbering for all ConstructLink operations.
 * This is the CORRECT standard for document/record identification.
 *
 * ISO 55000 is for asset management PROCESSES, not reference numbers.
 */
class ISO9001DocumentControl {

    private $db;
    private $orgCode;

    /**
     * Module codes for reference generation
     * Based on ISO 9001:2015 Section 7.5 requirements
     */
    const MODULE_CODES = [
        'assets' => 'AST',
        'transfers' => 'TRF',
        'borrowed_tools' => 'BOR',
        'maintenance' => 'MNT',
        'incidents' => 'INC',
        'withdrawals' => 'WTH'
    ];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->orgCode = defined('ASSET_ORG_CODE') ? ASSET_ORG_CODE : 'CON';
    }

    /**
     * Generate ISO 9001:2015 compliant document reference
     *
     * Format: [ORG]-[MODULE]-[YEAR]-[SEQ]
     * Example: CON-AST-2025-0001
     *
     * @param string $module Module name (assets, transfers, etc.)
     * @param bool $isLegacy Whether this is a legacy record
     * @return string Generated reference
     */
    public function generateReference($module, $isLegacy = false) {
        if (!isset(self::MODULE_CODES[$module])) {
            throw new Exception("Invalid module: {$module}");
        }

        $moduleCode = self::MODULE_CODES[$module];
        $yearComponent = $isLegacy ? 'LEG' : date('Y');

        // Get next sequence number
        $sequentialNumber = $this->getNextSequentialNumber($module, $yearComponent);

        // Format: CON-AST-2025-0001
        $reference = sprintf(
            '%s-%s-%s-%04d',
            $this->orgCode,
            $moduleCode,
            $yearComponent,
            $sequentialNumber
        );

        // Validate uniqueness
        if (!$this->isReferenceUnique($module, $reference)) {
            // Retry with incremented sequence
            return $this->generateReference($module, $isLegacy);
        }

        return $reference;
    }

    /**
     * Get next sequential number for module/year combination
     */
    private function getNextSequentialNumber($module, $yearComponent) {
        $moduleCode = self::MODULE_CODES[$module];
        $prefix = "{$this->orgCode}-{$moduleCode}-{$yearComponent}-";

        // Get highest sequence number for this module/year
        $sql = "SELECT MAX(CAST(SUBSTRING(ref, LENGTH(?) + 1) AS UNSIGNED)) as max_seq
                FROM {$module} WHERE ref LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$prefix, "{$prefix}%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $maxSeq = (int)($result['max_seq'] ?? 0);
        return $maxSeq + 1;
    }

    /**
     * Validate reference uniqueness
     */
    private function isReferenceUnique($module, $reference) {
        $sql = "SELECT COUNT(*) as count FROM {$module} WHERE ref = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reference]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($result['count'] ?? 0) === 0;
    }

    /**
     * Parse reference to extract components
     *
     * @param string $reference Reference to parse
     * @return array|null Components or null if invalid
     */
    public function parseReference($reference) {
        $pattern = '/^([A-Z]{3})-([A-Z]{3})-(\d{4}|LEG)-(\d{4})$/';

        if (preg_match($pattern, $reference, $matches)) {
            return [
                'full' => $matches[0],
                'org' => $matches[1],
                'module' => $matches[2],
                'year' => $matches[3],
                'sequence' => (int)$matches[4],
                'is_legacy' => $matches[3] === 'LEG'
            ];
        }

        return null;
    }
}
?>
```

#### Step 1.2: Update Helper Functions

**File: `core/helpers.php` - Add new functions**
```php
/**
 * Generate ISO 9001:2015 compliant reference
 * This is the CORRECT standard for document/record identification
 *
 * @param string $module Module name (assets, transfers, borrowed_tools, etc.)
 * @param bool $isLegacy Whether this is a legacy record
 * @return string Generated reference
 */
function generateDocumentReference($module, $isLegacy = false) {
    try {
        require_once APP_ROOT . '/core/ISO9001DocumentControl.php';
        $control = new ISO9001DocumentControl();
        return $control->generateReference($module, $isLegacy);
    } catch (Exception $e) {
        error_log("Document reference generation error: " . $e->getMessage());

        // Fallback format
        $prefix = "CON";
        $year = $isLegacy ? 'LEG' : date('Y');
        return "{$prefix}-ERR-{$year}-" . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
    }
}

/**
 * Backward compatibility wrapper
 * Maintains existing function signature
 */
function generateAssetReference($categoryId = null, $disciplineId = null, $isLegacy = false) {
    return generateDocumentReference('assets', $isLegacy);
}

function generateTransferReference($categoryId = null, $disciplineId = null) {
    return generateDocumentReference('transfers', false);
}

function generateBorrowReference() {
    return generateDocumentReference('borrowed_tools', false);
}

function generateMaintenanceReference() {
    return generateDocumentReference('maintenance', false);
}

function generateIncidentReference() {
    return generateDocumentReference('incidents', false);
}

function generateWithdrawalReference() {
    return generateDocumentReference('withdrawals', false);
}
```

#### Step 1.3: Database Schema Updates

**File: `database/migrations/add_references_to_operations.sql`**
```sql
-- Add reference columns to all operations
-- Using VARCHAR(20) to accommodate: CON-XXX-2025-0001

-- Borrowed Tools
ALTER TABLE borrowed_tools
ADD COLUMN ref VARCHAR(20) UNIQUE AFTER id,
ADD INDEX idx_borrowed_tools_ref (ref);

-- Maintenance Records
ALTER TABLE maintenance_records
ADD COLUMN ref VARCHAR(20) UNIQUE AFTER id,
ADD INDEX idx_maintenance_ref (ref);

-- Incidents
ALTER TABLE incidents
ADD COLUMN ref VARCHAR(20) UNIQUE AFTER id,
ADD INDEX idx_incidents_ref (ref);

-- Withdrawals
ALTER TABLE withdrawals
ADD COLUMN ref VARCHAR(20) UNIQUE AFTER id,
ADD INDEX idx_withdrawals_ref (ref);

-- Add comments for documentation
ALTER TABLE borrowed_tools MODIFY COLUMN ref VARCHAR(20) COMMENT 'ISO 9001:2015 document reference (CON-BOR-YYYY-NNNN)';
ALTER TABLE maintenance_records MODIFY COLUMN ref VARCHAR(20) COMMENT 'ISO 9001:2015 document reference (CON-MNT-YYYY-NNNN)';
ALTER TABLE incidents MODIFY COLUMN ref VARCHAR(20) COMMENT 'ISO 9001:2015 document reference (CON-INC-YYYY-NNNN)';
ALTER TABLE withdrawals MODIFY COLUMN ref VARCHAR(20) COMMENT 'ISO 9001:2015 document reference (CON-WTH-YYYY-NNNN)';
```

#### Step 1.4: Update Models

**File: `models/BorrowedToolModel.php:79` - Update createBorrowedTool()**
```php
public function createBorrowedTool($data) {
    try {
        // Validate required fields
        $validation = $this->validate($data, [
            'asset_id' => 'required|integer',
            'borrower_id' => 'required|integer',
            'project_id' => 'required|integer',
            'borrowed_date' => 'required|date'
        ]);

        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        $this->beginTransaction();

        // ✅ NEW: Generate ISO 9001:2015 reference
        if (empty($data['ref'])) {
            $data['ref'] = generateBorrowReference();
        }

        // Get current user
        $currentUser = Auth::getInstance()->getCurrentUser();

        // Set MVA workflow fields
        $data['maker_id'] = $currentUser['id'];
        $data['status'] = 'draft';
        $data['workflow_status'] = 'draft';
        $data['created_at'] = date('Y-m-d H:i:s');

        // Insert borrowed tool record
        $sql = "INSERT INTO borrowed_tools
                (ref, asset_id, borrower_id, project_id, borrowed_date,
                 return_date, status, workflow_status, maker_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['ref'],
            $data['asset_id'],
            $data['borrower_id'],
            $data['project_id'],
            $data['borrowed_date'],
            $data['return_date'] ?? null,
            $data['status'],
            $data['workflow_status'],
            $data['maker_id'],
            $data['created_at']
        ]);

        if (!$result) {
            $this->rollback();
            return ['success' => false, 'message' => 'Failed to create borrowed tool record'];
        }

        $borrowId = $this->db->lastInsertId();

        // Update asset status to 'borrowed'
        $assetModel = new AssetModel();
        $assetModel->update($data['asset_id'], ['status' => 'borrowed']);

        $this->commit();

        return [
            'success' => true,
            'borrow_id' => $borrowId,
            'ref' => $data['ref'],
            'message' => 'Borrowed tool record created successfully'
        ];

    } catch (Exception $e) {
        $this->rollback();
        error_log("Error creating borrowed tool: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while creating borrowed tool record'];
    }
}
```

**Similar updates for:**
- `MaintenanceModel.php` - Add `generateMaintenanceReference()`
- `IncidentModel.php` - Add `generateIncidentReference()`
- `WithdrawalModel.php` - Add `generateWithdrawalReference()`

#### Step 1.5: Backfill Existing Records

**File: `migrations/backfill_operation_references.php`**
```php
<?php
/**
 * Backfill Reference Numbers for Existing Records
 * Generates ISO 9001:2015 compliant references for all existing operations
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ISO9001DocumentControl.php';

$db = Database::getInstance()->getConnection();
$control = new ISO9001DocumentControl();

try {
    $db->beginTransaction();

    // 1. Backfill Borrowed Tools
    echo "Backfilling borrowed_tools references...\n";
    $borrowedTools = $db->query("SELECT id, created_at FROM borrowed_tools WHERE ref IS NULL OR ref = ''")->fetchAll();

    foreach ($borrowedTools as $tool) {
        $year = date('Y', strtotime($tool['created_at']));
        $isLegacy = ($year < date('Y'));
        $ref = $control->generateReference('borrowed_tools', $isLegacy);

        $stmt = $db->prepare("UPDATE borrowed_tools SET ref = ? WHERE id = ?");
        $stmt->execute([$ref, $tool['id']]);
        echo "  Generated ref {$ref} for borrow ID {$tool['id']}\n";
    }

    // 2. Backfill Maintenance Records
    echo "\nBackfilling maintenance_records references...\n";
    $maintenanceRecords = $db->query("SELECT id, created_at FROM maintenance_records WHERE ref IS NULL OR ref = ''")->fetchAll();

    foreach ($maintenanceRecords as $record) {
        $year = date('Y', strtotime($record['created_at']));
        $isLegacy = ($year < date('Y'));
        $ref = $control->generateReference('maintenance', $isLegacy);

        $stmt = $db->prepare("UPDATE maintenance_records SET ref = ? WHERE id = ?");
        $stmt->execute([$ref, $record['id']]);
        echo "  Generated ref {$ref} for maintenance ID {$record['id']}\n";
    }

    // 3. Backfill Incidents
    echo "\nBackfilling incidents references...\n";
    $incidents = $db->query("SELECT id, created_at FROM incidents WHERE ref IS NULL OR ref = ''")->fetchAll();

    foreach ($incidents as $incident) {
        $year = date('Y', strtotime($incident['created_at']));
        $isLegacy = ($year < date('Y'));
        $ref = $control->generateReference('incidents', $isLegacy);

        $stmt = $db->prepare("UPDATE incidents SET ref = ? WHERE id = ?");
        $stmt->execute([$ref, $incident['id']]);
        echo "  Generated ref {$ref} for incident ID {$incident['id']}\n";
    }

    // 4. Backfill Withdrawals
    echo "\nBackfilling withdrawals references...\n";
    $withdrawals = $db->query("SELECT id, created_at FROM withdrawals WHERE ref IS NULL OR ref = ''")->fetchAll();

    foreach ($withdrawals as $withdrawal) {
        $year = date('Y', strtotime($withdrawal['created_at']));
        $isLegacy = ($year < date('Y'));
        $ref = $control->generateReference('withdrawals', $isLegacy);

        $stmt = $db->prepare("UPDATE withdrawals SET ref = ? WHERE id = ?");
        $stmt->execute([$ref, $withdrawal['id']]);
        echo "  Generated ref {$ref} for withdrawal ID {$withdrawal['id']}\n";
    }

    $db->commit();

    echo "\n✅ Backfill completed successfully!\n";
    echo "\nSummary:\n";
    echo "  Borrowed Tools: " . count($borrowedTools) . " records\n";
    echo "  Maintenance: " . count($maintenanceRecords) . " records\n";
    echo "  Incidents: " . count($incidents) . " records\n";
    echo "  Withdrawals: " . count($withdrawals) . " records\n";

} catch (Exception $e) {
    $db->rollback();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
```

---

### Phase 2: Implement ISO 55000 Asset Management Processes
**Timeline:** Week 3-8
**Priority:** HIGH

#### Overview

ISO 55000:2014 defines asset management as:
> "Coordinated activity of an organization to realize value from assets"

This phase implements the **actual processes and lifecycle management**, not just reference formats.

#### Step 2.1: Borrowing Operations (ISO 55000 Section 8.3.1)

**Create: `/models/ISO55000/AssetUtilizationManager.php`**
```php
<?php
/**
 * ISO 55000:2014 Section 8.3.1 - Asset Utilization Management
 *
 * Manages the borrowing/lending process for assets with risk-based controls,
 * usage tracking, and performance monitoring.
 */
class AssetUtilizationManager {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Assess borrowing request based on asset criticality and risk
     * ISO 55000 Section 8.3.1 - Risk-based authorization
     *
     * @param int $assetId Asset to be borrowed
     * @param int $borrowerId Person requesting
     * @param int $projectId Destination project
     * @return array Assessment result
     */
    public function assessBorrowingRequest($assetId, $borrowerId, $projectId) {
        // Get asset information
        $assetModel = new AssetModel();
        $asset = $assetModel->find($assetId);

        if (!$asset) {
            return [
                'authorized' => false,
                'reason' => 'Asset not found',
                'risk_level' => 'unknown'
            ];
        }

        // Risk assessment based on asset value and criticality
        $riskLevel = $this->assessAssetRisk($asset);

        // Determine authorization requirements
        $authRequirements = $this->determineAuthorizationLevel($riskLevel, $asset);

        // Check if asset is available for borrowing
        $availability = $this->checkAssetAvailability($asset);

        // Check borrower competence (if applicable)
        $competence = $this->checkBorrowerCompetence($borrowerId, $asset);

        return [
            'authorized' => $availability['available'] && $competence['qualified'],
            'risk_level' => $riskLevel,
            'auth_requirements' => $authRequirements,
            'conditions' => $this->defineUsageConditions($asset),
            'monitoring_required' => $this->requiresMonitoring($riskLevel),
            'return_inspection_required' => $this->requiresInspection($riskLevel),
            'availability' => $availability,
            'competence' => $competence
        ];
    }

    /**
     * Assess asset risk level
     * Based on value, criticality, and business impact
     */
    private function assessAssetRisk($asset) {
        $riskScore = 0;

        // Factor 1: Asset value
        $value = (float)($asset['acquisition_cost'] ?? 0);
        if ($value > 100000) $riskScore += 3;
        elseif ($value > 50000) $riskScore += 2;
        elseif ($value > 10000) $riskScore += 1;

        // Factor 2: Category criticality
        $categoryModel = new CategoryModel();
        $category = $categoryModel->find($asset['category_id']);

        if ($category && $category['asset_type'] === 'capital') {
            $riskScore += 2; // Capital assets are more critical
        }

        // Factor 3: Current condition
        if (!empty($asset['condition_notes'])) {
            if (stripos($asset['condition_notes'], 'repair') !== false ||
                stripos($asset['condition_notes'], 'damaged') !== false) {
                $riskScore += 2;
            }
        }

        // Determine risk level
        if ($riskScore >= 5) return 'high';
        elseif ($riskScore >= 3) return 'medium';
        else return 'low';
    }

    /**
     * Determine authorization level required
     */
    private function determineAuthorizationLevel($riskLevel, $asset) {
        switch ($riskLevel) {
            case 'high':
                return [
                    'maker' => true,
                    'verifier' => true,
                    'authorizer' => true, // System Admin approval required
                    'reason' => 'High-value or critical asset'
                ];
            case 'medium':
                return [
                    'maker' => true,
                    'verifier' => true,
                    'authorizer' => false,
                    'reason' => 'Medium-value asset'
                ];
            case 'low':
            default:
                return [
                    'maker' => true,
                    'verifier' => false,
                    'authorizer' => false,
                    'reason' => 'Low-value asset - expedited approval'
                ];
        }
    }

    /**
     * Check asset availability for borrowing
     */
    private function checkAssetAvailability($asset) {
        $available = ($asset['status'] === 'available');

        // Check if already borrowed
        if ($asset['status'] === 'borrowed') {
            return [
                'available' => false,
                'reason' => 'Asset is currently borrowed',
                'expected_return' => $this->getExpectedReturnDate($asset['id'])
            ];
        }

        // Check if in maintenance
        if ($asset['status'] === 'maintenance') {
            return [
                'available' => false,
                'reason' => 'Asset is undergoing maintenance',
                'expected_completion' => $this->getMaintenanceCompletion($asset['id'])
            ];
        }

        // Check quantity for consumables
        if ($asset['available_quantity'] !== null && $asset['available_quantity'] <= 0) {
            return [
                'available' => false,
                'reason' => 'No quantity available',
                'current_quantity' => 0
            ];
        }

        return [
            'available' => true,
            'reason' => 'Asset is available for borrowing'
        ];
    }

    /**
     * Check borrower competence (for specialized equipment)
     */
    private function checkBorrowerCompetence($borrowerId, $asset) {
        // For specialized or high-value equipment, check if borrower
        // has required training/certification

        // This would integrate with a training/certification system
        // For now, return basic check

        return [
            'qualified' => true,
            'certifications' => [],
            'training_required' => false
        ];
    }

    /**
     * Define usage conditions based on asset type and risk
     */
    private function defineUsageConditions($asset) {
        $conditions = [];

        // Standard conditions
        $conditions[] = 'Return in same condition as received';
        $conditions[] = 'Report any damage immediately';

        // Risk-based conditions
        $riskLevel = $this->assessAssetRisk($asset);

        if ($riskLevel === 'high') {
            $conditions[] = 'Daily usage log required';
            $conditions[] = 'Inspection required on return';
            $conditions[] = 'Replacement cost liability applies';
        }

        return $conditions;
    }

    /**
     * Determine if asset requires usage monitoring
     */
    private function requiresMonitoring($riskLevel) {
        return ($riskLevel === 'high');
    }

    /**
     * Determine if return inspection is required
     */
    private function requiresInspection($riskLevel) {
        return ($riskLevel !== 'low');
    }

    /**
     * Get expected return date for borrowed asset
     */
    private function getExpectedReturnDate($assetId) {
        $sql = "SELECT return_date FROM borrowed_tools
                WHERE asset_id = ? AND status = 'approved'
                ORDER BY borrowed_date DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['return_date'] ?? null;
    }

    /**
     * Get maintenance completion date
     */
    private function getMaintenanceCompletion($assetId) {
        $sql = "SELECT expected_completion FROM maintenance_records
                WHERE asset_id = ? AND status = 'in_progress'
                ORDER BY maintenance_date DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['expected_completion'] ?? null;
    }

    /**
     * Track asset utilization performance
     * ISO 55000 Section 9 - Performance evaluation
     */
    public function trackUtilizationPerformance($assetId) {
        // Calculate utilization metrics
        $sql = "SELECT
                    COUNT(*) as total_borrows,
                    SUM(DATEDIFF(returned_date, borrowed_date)) as total_days_borrowed,
                    AVG(DATEDIFF(returned_date, borrowed_date)) as avg_borrow_duration,
                    COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count
                FROM borrowed_tools
                WHERE asset_id = ? AND status != 'draft'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculate utilization rate (%)
        $totalDaysInService = $this->calculateTotalDaysInService($assetId);
        $utilizationRate = $totalDaysInService > 0
            ? ($metrics['total_days_borrowed'] / $totalDaysInService) * 100
            : 0;

        return [
            'total_borrows' => (int)$metrics['total_borrows'],
            'total_days_borrowed' => (int)$metrics['total_days_borrowed'],
            'avg_borrow_duration' => round($metrics['avg_borrow_duration'], 1),
            'overdue_count' => (int)$metrics['overdue_count'],
            'utilization_rate' => round($utilizationRate, 2),
            'performance_rating' => $this->calculatePerformanceRating($metrics, $utilizationRate)
        ];
    }

    /**
     * Calculate total days asset has been in service
     */
    private function calculateTotalDaysInService($assetId) {
        $sql = "SELECT acquired_date FROM assets WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $acquiredDate = new DateTime($result['acquired_date']);
            $today = new DateTime();
            return $acquiredDate->diff($today)->days;
        }

        return 0;
    }

    /**
     * Calculate performance rating
     */
    private function calculatePerformanceRating($metrics, $utilizationRate) {
        $score = 0;

        // High utilization is good
        if ($utilizationRate > 70) $score += 3;
        elseif ($utilizationRate > 40) $score += 2;
        elseif ($utilizationRate > 10) $score += 1;

        // Low overdue rate is good
        if ($metrics['total_borrows'] > 0) {
            $overdueRate = ($metrics['overdue_count'] / $metrics['total_borrows']) * 100;
            if ($overdueRate < 5) $score += 2;
            elseif ($overdueRate < 15) $score += 1;
        }

        if ($score >= 4) return 'Excellent';
        elseif ($score >= 3) return 'Good';
        elseif ($score >= 2) return 'Fair';
        else return 'Poor';
    }
}
?>
```

**Update: `BorrowedToolModel.php` - Integrate ISO55000 assessment**
```php
public function createBorrowedTool($data) {
    try {
        $this->beginTransaction();

        // ✅ ISO 55000 Section 8.3.1 - Risk-based assessment
        $utilizationMgr = new AssetUtilizationManager();
        $assessment = $utilizationMgr->assessBorrowingRequest(
            $data['asset_id'],
            $data['borrower_id'],
            $data['project_id']
        );

        if (!$assessment['authorized']) {
            $this->rollback();
            return [
                'success' => false,
                'message' => 'Borrowing not authorized: ' . $assessment['reason']
            ];
        }

        // Generate ISO 9001 reference
        if (empty($data['ref'])) {
            $data['ref'] = generateBorrowReference();
        }

        // Store risk assessment results
        $data['risk_level'] = $assessment['risk_level'];
        $data['usage_conditions'] = json_encode($assessment['conditions']);
        $data['monitoring_required'] = $assessment['monitoring_required'] ? 1 : 0;
        $data['inspection_required'] = $assessment['return_inspection_required'] ? 1 : 0;

        // Set MVA workflow based on risk level
        if ($assessment['risk_level'] === 'low') {
            // Low risk - expedited approval
            $data['status'] = 'approved';
            $data['workflow_status'] = 'approved';
        } else {
            // Medium/High risk - full MVA workflow
            $data['status'] = 'draft';
            $data['workflow_status'] = 'draft';
        }

        // Rest of creation logic...

    } catch (Exception $e) {
        $this->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
```

#### Step 2.2: Maintenance Operations (ISO 55000 Section 8.3.3)

**Create: `/models/ISO55000/MaintenanceStrategyManager.php`**
```php
<?php
/**
 * ISO 55000:2014 Section 8.3.3 - Maintenance Management
 *
 * Implements preventive and corrective maintenance strategies,
 * condition monitoring, and performance tracking.
 */
class MaintenanceStrategyManager {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Determine maintenance strategy for asset
     * Based on criticality, value, and failure consequences
     *
     * @param int $assetId
     * @return array Maintenance strategy
     */
    public function determineMaintenanceStrategy($assetId) {
        $assetModel = new AssetModel();
        $asset = $assetModel->find($assetId);

        if (!$asset) {
            throw new Exception("Asset not found");
        }

        $criticality = $this->assessAssetCriticality($asset);
        $failureMode = $this->identifyFailureModes($asset);

        // Determine strategy type
        if ($criticality['level'] === 'high') {
            $strategy = 'predictive'; // Condition-based monitoring
        } elseif ($criticality['level'] === 'medium') {
            $strategy = 'preventive'; // Scheduled maintenance
        } else {
            $strategy = 'corrective'; // Run-to-failure
        }

        return [
            'strategy' => $strategy,
            'criticality' => $criticality,
            'failure_modes' => $failureMode,
            'schedule' => $this->generateMaintenanceSchedule($asset, $strategy),
            'indicators' => $this->definePerformanceIndicators($asset),
            'procedures' => $this->getMaintenanceProcedures($asset, $strategy)
        ];
    }

    /**
     * Assess asset criticality
     */
    private function assessAssetCriticality($asset) {
        $criticalityScore = 0;

        // Factor 1: Business impact if unavailable
        $utilizationMgr = new AssetUtilizationManager();
        $utilization = $utilizationMgr->trackUtilizationPerformance($asset['id']);

        if ($utilization['utilization_rate'] > 70) {
            $criticalityScore += 3; // Heavily used = critical
        } elseif ($utilization['utilization_rate'] > 40) {
            $criticalityScore += 2;
        }

        // Factor 2: Replacement cost/time
        $value = (float)($asset['acquisition_cost'] ?? 0);
        if ($value > 50000) {
            $criticalityScore += 2;
        }

        // Factor 3: Safety consequences
        if ($this->hasSafetyImplications($asset)) {
            $criticalityScore += 3;
        }

        // Determine level
        if ($criticalityScore >= 6) $level = 'high';
        elseif ($criticalityScore >= 3) $level = 'medium';
        else $level = 'low';

        return [
            'level' => $level,
            'score' => $criticalityScore,
            'factors' => [
                'utilization' => $utilization['utilization_rate'],
                'value' => $value,
                'safety_critical' => $this->hasSafetyImplications($asset)
            ]
        ];
    }

    /**
     * Check if asset has safety implications
     */
    private function hasSafetyImplications($asset) {
        // Check if asset is in safety equipment category
        $sql = "SELECT c.name FROM categories c WHERE c.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$asset['category_id']]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($category) {
            $safetyKeywords = ['safety', 'ppe', 'emergency', 'rescue', 'protective'];
            foreach ($safetyKeywords as $keyword) {
                if (stripos($category['name'], $keyword) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Identify potential failure modes
     */
    private function identifyFailureModes($asset) {
        // Get historical failure data
        $sql = "SELECT
                    incident_type,
                    COUNT(*) as occurrence_count,
                    AVG(downtime_hours) as avg_downtime
                FROM incidents
                WHERE asset_id = ?
                GROUP BY incident_type";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$asset['id']]);
        $failures = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $failures;
    }

    /**
     * Generate maintenance schedule
     */
    private function generateMaintenanceSchedule($asset, $strategy) {
        switch ($strategy) {
            case 'predictive':
                return [
                    'type' => 'Condition-based',
                    'frequency' => 'As needed based on condition monitoring',
                    'triggers' => [
                        'Performance degradation > 10%',
                        'Vibration exceeds threshold',
                        'Temperature anomaly detected'
                    ],
                    'inspection_interval' => '1 week'
                ];

            case 'preventive':
                return [
                    'type' => 'Time-based',
                    'frequency' => $this->calculatePreventiveFrequency($asset),
                    'tasks' => [
                        'Visual inspection',
                        'Lubrication',
                        'Cleaning',
                        'Calibration (if applicable)'
                    ],
                    'inspection_interval' => '1 month'
                ];

            case 'corrective':
            default:
                return [
                    'type' => 'Run-to-failure',
                    'frequency' => 'As needed',
                    'triggers' => [
                        'Functional failure',
                        'User report',
                        'Safety concern'
                    ],
                    'inspection_interval' => '6 months'
                ];
        }
    }

    /**
     * Calculate preventive maintenance frequency
     */
    private function calculatePreventiveFrequency($asset) {
        // Based on manufacturer recommendations, usage intensity, etc.
        // Simplified for now

        $utilizationMgr = new AssetUtilizationManager();
        $utilization = $utilizationMgr->trackUtilizationPerformance($asset['id']);

        if ($utilization['utilization_rate'] > 70) {
            return 'Monthly';
        } elseif ($utilization['utilization_rate'] > 40) {
            return 'Quarterly';
        } else {
            return 'Semi-annually';
        }
    }

    /**
     * Define performance indicators for asset
     */
    private function definePerformanceIndicators($asset) {
        return [
            'mtbf' => $this->calculateMTBF($asset['id']), // Mean Time Between Failures
            'mttr' => $this->calculateMTTR($asset['id']), // Mean Time To Repair
            'availability' => $this->calculateAvailability($asset['id']),
            'maintenance_cost' => $this->calculateMaintenanceCost($asset['id'])
        ];
    }

    /**
     * Calculate Mean Time Between Failures (MTBF)
     */
    private function calculateMTBF($assetId) {
        $sql = "SELECT
                    DATEDIFF(MAX(incident_date), MIN(incident_date)) as total_days,
                    COUNT(*) as failure_count
                FROM incidents
                WHERE asset_id = ? AND incident_type = 'failure'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['failure_count'] > 1) {
            return round($result['total_days'] / $result['failure_count'], 1);
        }

        return null; // Insufficient data
    }

    /**
     * Calculate Mean Time To Repair (MTTR)
     */
    private function calculateMTTR($assetId) {
        $sql = "SELECT AVG(downtime_hours) as avg_repair_time
                FROM incidents
                WHERE asset_id = ? AND downtime_hours IS NOT NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['avg_repair_time'] ? round($result['avg_repair_time'], 1) : null;
    }

    /**
     * Calculate asset availability (%)
     */
    private function calculateAvailability($assetId) {
        $totalDays = $this->calculateTotalDaysInService($assetId);
        $downDays = $this->calculateTotalDowntimeDays($assetId);

        if ($totalDays > 0) {
            return round((($totalDays - $downDays) / $totalDays) * 100, 2);
        }

        return 100; // No downtime data
    }

    /**
     * Calculate total days in service
     */
    private function calculateTotalDaysInService($assetId) {
        $sql = "SELECT acquired_date FROM assets WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $acquiredDate = new DateTime($result['acquired_date']);
            $today = new DateTime();
            return $acquiredDate->diff($today)->days;
        }

        return 0;
    }

    /**
     * Calculate total downtime days
     */
    private function calculateTotalDowntimeDays($assetId) {
        $sql = "SELECT SUM(downtime_hours) / 24 as downtime_days
                FROM incidents
                WHERE asset_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['downtime_days'] ? round($result['downtime_days'], 1) : 0;
    }

    /**
     * Calculate maintenance costs
     */
    private function calculateMaintenanceCost($assetId) {
        $sql = "SELECT
                    SUM(cost) as total_cost,
                    COUNT(*) as maintenance_count
                FROM maintenance_records
                WHERE asset_id = ? AND cost IS NOT NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (float)($result['total_cost'] ?? 0),
            'count' => (int)($result['maintenance_count'] ?? 0),
            'average' => $result['maintenance_count'] > 0
                ? round($result['total_cost'] / $result['maintenance_count'], 2)
                : 0
        ];
    }

    /**
     * Get maintenance procedures
     */
    private function getMaintenanceProcedures($asset, $strategy) {
        // This would link to documented procedures
        // For now, return basic structure

        return [
            'safety_precautions' => $this->getSafetyPrecautions($asset),
            'required_tools' => $this->getRequiredTools($asset),
            'estimated_duration' => $this->estimateMaintenanceDuration($asset, $strategy),
            'skill_level' => $this->determineSkillLevel($asset),
            'documentation' => 'Refer to asset manual and safety guidelines'
        ];
    }

    private function getSafetyPrecautions($asset) {
        return [
            'Lockout/Tagout procedures',
            'Personal protective equipment required',
            'Permit to work (if applicable)'
        ];
    }

    private function getRequiredTools($asset) {
        // Would be asset-specific
        return ['Standard toolkit', 'Specialized tools as required'];
    }

    private function estimateMaintenanceDuration($asset, $strategy) {
        switch ($strategy) {
            case 'predictive':
                return '1-2 hours (inspection)';
            case 'preventive':
                return '2-4 hours';
            case 'corrective':
                return '4-8 hours (depends on failure)';
            default:
                return 'To be determined';
        }
    }

    private function determineSkillLevel($asset) {
        $categoryModel = new CategoryModel();
        $category = $categoryModel->find($asset['category_id']);

        if ($category && $category['asset_type'] === 'capital') {
            return 'Skilled technician';
        }

        return 'General maintenance staff';
    }
}
?>
```

#### Step 2.3: Transfer Operations (ISO 55000 Section 8.3.2)

Already has reference numbers, need to add ISO 55000 change management.

**Update: `TransferModel.php` - Add change management**
```php
/**
 * Create transfer with ISO 55000 Section 8.3.2 change management
 */
public function createTransfer($data) {
    try {
        $this->beginTransaction();

        // Get asset information
        $assetModel = new AssetModel();
        $asset = $assetModel->find($data['asset_id']);

        if (!$asset) {
            $this->rollback();
            return ['success' => false, 'message' => 'Asset not found'];
        }

        // ✅ ISO 55000 Section 8.3.2 - Change Management Assessment
        $changeImpact = $this->assessChangeImpact($asset, $data);

        if (!$changeImpact['approved']) {
            $this->rollback();
            return [
                'success' => false,
                'message' => 'Transfer not approved: ' . $changeImpact['reason']
            ];
        }

        // Generate ISO 9001 reference
        if (empty($data['ref'])) {
            $data['ref'] = generateTransferReference();
        }

        // Store change impact results
        $data['risk_assessment'] = json_encode($changeImpact['risks']);
        $data['performance_impact'] = $changeImpact['performance_impact'];
        $data['verification_required'] = $changeImpact['verification_required'] ? 1 : 0;

        // Rest of transfer creation...

    } catch (Exception $e) {
        $this->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Assess change impact (ISO 55000 Section 8.3.2)
 */
private function assessChangeImpact($asset, $transferData) {
    // Risk assessment
    $risks = [];

    // Check if moving to different environmental conditions
    $fromProject = $this->getProject($transferData['from_project_id']);
    $toProject = $this->getProject($transferData['to_project_id']);

    if ($fromProject['location'] != $toProject['location']) {
        $risks[] = [
            'type' => 'environmental',
            'description' => 'Different site conditions',
            'mitigation' => 'Verify asset suitability for new environment'
        ];
    }

    // Check asset condition
    if ($asset['status'] === 'maintenance') {
        return [
            'approved' => false,
            'reason' => 'Asset is currently under maintenance'
        ];
    }

    // Performance impact
    $utilizationMgr = new AssetUtilizationManager();
    $utilization = $utilizationMgr->trackUtilizationPerformance($asset['id']);

    $performanceImpact = 'low';
    if ($utilization['utilization_rate'] > 70) {
        $performanceImpact = 'high';
        $risks[] = [
            'type' => 'operational',
            'description' => 'High utilization asset - may impact project operations',
            'mitigation' => 'Ensure replacement or alternative available'
        ];
    }

    return [
        'approved' => true,
        'risks' => $risks,
        'performance_impact' => $performanceImpact,
        'verification_required' => (count($risks) > 0),
        'reason' => 'Transfer approved with conditions'
    ];
}
```

#### Step 2.4: Incident Management (ISO 55000 Section 6.1)

**Create: `/models/ISO55000/RiskIncidentManager.php`**
```php
<?php
/**
 * ISO 55000:2014 Section 6.1 - Risk and Incident Management
 *
 * Manages asset-related incidents with risk assessment,
 * corrective/preventive actions (CAPA), and trend analysis.
 */
class RiskIncidentManager {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Assess incident severity and risk
     *
     * @param array $incidentData
     * @return array Risk assessment
     */
    public function assessIncidentRisk($incidentData) {
        $severityScore = 0;

        // Factor 1: Safety impact
        if (isset($incidentData['has_injury']) && $incidentData['has_injury']) {
            $severityScore += 5;
        }

        // Factor 2: Financial impact
        if (isset($incidentData['estimated_cost'])) {
            $cost = (float)$incidentData['estimated_cost'];
            if ($cost > 50000) $severityScore += 4;
            elseif ($cost > 10000) $severityScore += 3;
            elseif ($cost > 5000) $severityScore += 2;
            else $severityScore += 1;
        }

        // Factor 3: Operational impact (downtime)
        if (isset($incidentData['downtime_hours'])) {
            $hours = (float)$incidentData['downtime_hours'];
            if ($hours > 48) $severityScore += 3;
            elseif ($hours > 24) $severityScore += 2;
            elseif ($hours > 8) $severityScore += 1;
        }

        // Factor 4: Environmental impact
        if (isset($incidentData['environmental_impact']) && $incidentData['environmental_impact']) {
            $severityScore += 3;
        }

        // Determine severity level
        if ($severityScore >= 8) $severity = 'critical';
        elseif ($severityScore >= 5) $severity = 'major';
        elseif ($severityScore >= 3) $severity = 'moderate';
        else $severity = 'minor';

        return [
            'severity' => $severity,
            'score' => $severityScore,
            'immediate_actions' => $this->determineImmediateActions($severity),
            'investigation_required' => ($severity !== 'minor'),
            'root_cause_analysis_required' => ($severity === 'critical' || $severity === 'major')
        ];
    }

    /**
     * Determine immediate actions based on severity
     */
    private function determineImmediateActions($severity) {
        switch ($severity) {
            case 'critical':
                return [
                    'Isolate affected asset immediately',
                    'Notify System Admin and Safety Officer',
                    'Initiate emergency response procedures',
                    'Secure area and prevent access',
                    'Document scene with photos'
                ];

            case 'major':
                return [
                    'Assess asset condition',
                    'Notify Asset Director',
                    'Remove from service if unsafe',
                    'Document incident details'
                ];

            case 'moderate':
                return [
                    'Assess asset condition',
                    'Report to supervisor',
                    'Tag asset if requires attention'
                ];

            case 'minor':
            default:
                return [
                    'Document incident',
                    'Monitor asset performance'
                ];
        }
    }

    /**
     * Perform root cause analysis
     * 5 Whys methodology
     */
    public function performRootCauseAnalysis($incidentId) {
        // This would be interactive with the user
        // For now, provide structure

        return [
            'methodology' => '5 Whys Analysis',
            'why_1' => 'What was the immediate cause?',
            'why_2' => 'Why did that happen?',
            'why_3' => 'Why did that condition exist?',
            'why_4' => 'Why was that not prevented?',
            'why_5' => 'Why was there no system to prevent this?',
            'root_cause' => 'To be determined through investigation',
            'contributing_factors' => []
        ];
    }

    /**
     * Generate corrective and preventive actions (CAPA)
     */
    public function generateCAPA($incidentId, $rootCause) {
        $sql = "SELECT * FROM incidents WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$incidentId]);
        $incident = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$incident) {
            throw new Exception("Incident not found");
        }

        $capa = [
            'corrective_actions' => [],
            'preventive_actions' => [],
            'responsible_parties' => [],
            'target_dates' => [],
            'verification_methods' => []
        ];

        // Corrective actions (fix the immediate problem)
        $capa['corrective_actions'][] = [
            'action' => 'Repair or replace damaged asset',
            'responsible' => 'Maintenance Team',
            'target_date' => date('Y-m-d', strtotime('+7 days')),
            'verification' => 'Functional test after repair'
        ];

        if ($incident['severity'] !== 'minor') {
            $capa['corrective_actions'][] = [
                'action' => 'Review and update asset condition assessment',
                'responsible' => 'Asset Director',
                'target_date' => date('Y-m-d', strtotime('+3 days')),
                'verification' => 'Updated asset record'
            ];
        }

        // Preventive actions (prevent recurrence)
        $capa['preventive_actions'][] = [
            'action' => 'Enhance preventive maintenance schedule',
            'responsible' => 'Maintenance Manager',
            'target_date' => date('Y-m-d', strtotime('+14 days')),
            'verification' => 'Updated maintenance plan'
        ];

        if ($this->isTrainingGap($rootCause)) {
            $capa['preventive_actions'][] = [
                'action' => 'Conduct user training on proper asset usage',
                'responsible' => 'Training Coordinator',
                'target_date' => date('Y-m-d', strtotime('+30 days')),
                'verification' => 'Training attendance records'
            ];
        }

        return $capa;
    }

    /**
     * Check if root cause indicates training gap
     */
    private function isTrainingGap($rootCause) {
        $trainingKeywords = ['training', 'knowledge', 'competence', 'awareness', 'procedure'];
        foreach ($trainingKeywords as $keyword) {
            if (stripos($rootCause, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Analyze incident trends
     * Identify recurring patterns
     */
    public function analyzeIncidentTrends() {
        // Trend by asset
        $sql = "SELECT
                    a.id,
                    a.name,
                    COUNT(i.id) as incident_count,
                    MAX(i.incident_date) as last_incident
                FROM assets a
                INNER JOIN incidents i ON i.asset_id = a.id
                WHERE i.incident_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY a.id
                HAVING incident_count > 2
                ORDER BY incident_count DESC
                LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $assetTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Trend by type
        $sql = "SELECT
                    incident_type,
                    COUNT(*) as count,
                    AVG(downtime_hours) as avg_downtime
                FROM incidents
                WHERE incident_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY incident_type
                ORDER BY count DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $typeTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'high_incident_assets' => $assetTrends,
            'common_incident_types' => $typeTrends,
            'recommendations' => $this->generateTrendRecommendations($assetTrends, $typeTrends)
        ];
    }

    /**
     * Generate recommendations based on trends
     */
    private function generateTrendRecommendations($assetTrends, $typeTrends) {
        $recommendations = [];

        // Assets with multiple incidents
        foreach ($assetTrends as $asset) {
            if ($asset['incident_count'] >= 5) {
                $recommendations[] = [
                    'type' => 'asset_replacement',
                    'asset_id' => $asset['id'],
                    'recommendation' => "Asset '{$asset['name']}' has {$asset['incident_count']} incidents in 6 months. Consider replacement or major overhaul.",
                    'priority' => 'high'
                ];
            } elseif ($asset['incident_count'] >= 3) {
                $recommendations[] = [
                    'type' => 'maintenance_review',
                    'asset_id' => $asset['id'],
                    'recommendation' => "Review maintenance strategy for asset '{$asset['name']}'.",
                    'priority' => 'medium'
                ];
            }
        }

        // Common incident types
        foreach ($typeTrends as $type) {
            if ($type['count'] > 10) {
                $recommendations[] = [
                    'type' => 'process_improvement',
                    'incident_type' => $type['incident_type'],
                    'recommendation' => "Implement preventive measures for '{$type['incident_type']}' incidents (occurred {$type['count']} times).",
                    'priority' => 'high'
                ];
            }
        }

        return $recommendations;
    }
}
?>
```

#### Step 2.5: Withdrawal Management (ISO 55000 Section 8.3.4)

**Special Handling for Consumables**

**Update: `WithdrawalModel.php`**
```php
/**
 * Create withdrawal with ISO 55000 Section 8.3.4 disposal management
 *
 * NOTE: Withdrawals serve two purposes:
 * 1. Asset disposal (end-of-life assets)
 * 2. Consumable consumption (materials used on projects)
 */
public function createWithdrawal($data) {
    try {
        $this->beginTransaction();

        // Get asset information
        $assetModel = new AssetModel();
        $asset = $assetModel->find($data['asset_id']);

        if (!$asset) {
            $this->rollback();
            return ['success' => false, 'message' => 'Asset not found'];
        }

        // Determine withdrawal type
        $categoryModel = new CategoryModel();
        $category = $categoryModel->find($asset['category_id']);

        $isConsumable = ($category && $category['is_consumable'] == 1);

        if ($isConsumable) {
            // ✅ Consumable withdrawal (materials used, non-returnable)
            return $this->processConsumableWithdrawal($asset, $data);
        } else {
            // ✅ Asset disposal (end-of-life)
            return $this->processAssetDisposal($asset, $data);
        }

    } catch (Exception $e) {
        $this->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Process consumable withdrawal
 * For materials consumed on projects (non-returnable)
 */
private function processConsumableWithdrawal($asset, $data) {
    // Generate ISO 9001 reference
    if (empty($data['ref'])) {
        $data['ref'] = generateWithdrawalReference();
    }

    // Validate quantity
    $requestedQty = (int)($data['quantity'] ?? 1);
    $availableQty = (int)($asset['available_quantity'] ?? 0);

    if ($requestedQty > $availableQty) {
        $this->rollback();
        return [
            'success' => false,
            'message' => "Insufficient quantity. Requested: {$requestedQty}, Available: {$availableQty}"
        ];
    }

    // Set withdrawal type
    $data['withdrawal_type'] = 'consumable';
    $data['is_disposal'] = 0;
    $data['quantity_withdrawn'] = $requestedQty;

    // Store consumption details
    $data['project_id'] = $data['project_id'] ?? null;
    $data['withdrawn_by'] = $data['withdrawn_by'] ?? Auth::getInstance()->getCurrentUser()['id'];
    $data['withdrawal_date'] = $data['withdrawal_date'] ?? date('Y-m-d');
    $data['purpose'] = $data['purpose'] ?? 'Project consumption';

    // Insert withdrawal record
    $sql = "INSERT INTO withdrawals
            (ref, asset_id, withdrawal_type, is_disposal, quantity_withdrawn,
             project_id, withdrawn_by, withdrawal_date, purpose, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $this->db->prepare($sql);
    $result = $stmt->execute([
        $data['ref'],
        $data['asset_id'],
        $data['withdrawal_type'],
        $data['is_disposal'],
        $data['quantity_withdrawn'],
        $data['project_id'],
        $data['withdrawn_by'],
        $data['withdrawal_date'],
        $data['purpose']
    ]);

    if (!$result) {
        $this->rollback();
        return ['success' => false, 'message' => 'Failed to create withdrawal record'];
    }

    $withdrawalId = $this->db->lastInsertId();

    // Update asset quantity
    $newAvailableQty = $availableQty - $requestedQty;
    $assetModel = new AssetModel();
    $assetModel->update($data['asset_id'], [
        'available_quantity' => $newAvailableQty
    ]);

    // Check if reorder needed
    if ($newAvailableQty <= ($asset['reorder_point'] ?? 0)) {
        $this->triggerReorderAlert($asset, $newAvailableQty);
    }

    $this->commit();

    return [
        'success' => true,
        'withdrawal_id' => $withdrawalId,
        'ref' => $data['ref'],
        'remaining_quantity' => $newAvailableQty,
        'reorder_needed' => ($newAvailableQty <= ($asset['reorder_point'] ?? 0)),
        'message' => 'Consumable withdrawal recorded successfully'
    ];
}

/**
 * Process asset disposal (ISO 55000 Section 8.3.4)
 * For end-of-life assets
 */
private function processAssetDisposal($asset, $data) {
    // Generate ISO 9001 reference
    if (empty($data['ref'])) {
        $data['ref'] = generateWithdrawalReference();
    }

    // ✅ ISO 55000 Section 8.3.4 - Disposal Assessment
    $disposalAssessment = $this->assessDisposal($asset, $data);

    if (!$disposalAssessment['approved']) {
        $this->rollback();
        return [
            'success' => false,
            'message' => 'Disposal not approved: ' . $disposalAssessment['reason']
        ];
    }

    // Set withdrawal type
    $data['withdrawal_type'] = 'disposal';
    $data['is_disposal'] = 1;
    $data['quantity_withdrawn'] = 1; // Full asset

    // Store disposal assessment
    $data['disposal_reason'] = $data['disposal_reason'] ?? 'End of life';
    $data['disposal_method'] = $disposalAssessment['disposal_method'];
    $data['residual_value'] = $disposalAssessment['residual_value'];
    $data['environmental_compliance'] = json_encode($disposalAssessment['environmental_compliance']);

    // Authorization based on value
    if ($disposalAssessment['requires_authorization']) {
        $data['status'] = 'draft';
        $data['workflow_status'] = 'pending_authorization';
    } else {
        $data['status'] = 'approved';
        $data['workflow_status'] = 'approved';
    }

    // Insert disposal record
    $sql = "INSERT INTO withdrawals
            (ref, asset_id, withdrawal_type, is_disposal, quantity_withdrawn,
             disposal_reason, disposal_method, residual_value,
             environmental_compliance, status, workflow_status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $this->db->prepare($sql);
    $result = $stmt->execute([
        $data['ref'],
        $data['asset_id'],
        $data['withdrawal_type'],
        $data['is_disposal'],
        $data['quantity_withdrawn'],
        $data['disposal_reason'],
        $data['disposal_method'],
        $data['residual_value'],
        $data['environmental_compliance'],
        $data['status'],
        $data['workflow_status']
    ]);

    if (!$result) {
        $this->rollback();
        return ['success' => false, 'message' => 'Failed to create disposal record'];
    }

    $withdrawalId = $this->db->lastInsertId();

    // If approved, update asset status
    if ($data['status'] === 'approved') {
        $assetModel = new AssetModel();
        $assetModel->update($data['asset_id'], [
            'status' => 'disposed',
            'disposal_date' => date('Y-m-d')
        ]);
    }

    $this->commit();

    return [
        'success' => true,
        'withdrawal_id' => $withdrawalId,
        'ref' => $data['ref'],
        'requires_authorization' => $disposalAssessment['requires_authorization'],
        'message' => 'Disposal request created successfully'
    ];
}

/**
 * Assess disposal decision (ISO 55000 Section 8.3.4)
 */
private function assessDisposal($asset, $data) {
    $value = (float)($asset['acquisition_cost'] ?? 0);
    $age = $this->calculateAssetAge($asset);

    // Determine disposal method
    $disposalMethod = $this->determineDisposalMethod($asset, $data);

    // Assess residual value
    $residualValue = $this->assessResidualValue($asset, $age);

    // Check environmental compliance
    $envCompliance = $this->checkEnvironmentalCompliance($asset);

    // Determine if authorization required
    $requiresAuth = ($value > 10000 || $residualValue > 5000);

    return [
        'approved' => true,
        'disposal_method' => $disposalMethod,
        'residual_value' => $residualValue,
        'environmental_compliance' => $envCompliance,
        'requires_authorization' => $requiresAuth,
        'reason' => 'Disposal assessment complete'
    ];
}

/**
 * Calculate asset age in years
 */
private function calculateAssetAge($asset) {
    $acquiredDate = new DateTime($asset['acquired_date']);
    $today = new DateTime();
    $interval = $acquiredDate->diff($today);
    return $interval->y + ($interval->m / 12);
}

/**
 * Determine disposal method
 */
private function determineDisposalMethod($asset, $data) {
    // Check user-specified method
    if (!empty($data['disposal_method'])) {
        return $data['disposal_method'];
    }

    // Determine based on condition and value
    $value = (float)($asset['acquisition_cost'] ?? 0);

    if ($value > 5000 && empty($asset['condition_notes'])) {
        return 'Resale';
    } elseif ($value > 1000) {
        return 'Trade-in';
    } else {
        return 'Scrap/Recycle';
    }
}

/**
 * Assess residual value
 */
private function assessResidualValue($asset, $age) {
    $originalValue = (float)($asset['acquisition_cost'] ?? 0);

    // Simple depreciation calculation
    // 20% per year, minimum 10% of original value
    $depreciationRate = 0.20;
    $minValue = $originalValue * 0.10;

    $residualValue = $originalValue * pow((1 - $depreciationRate), $age);
    $residualValue = max($residualValue, $minValue);

    return round($residualValue, 2);
}

/**
 * Check environmental compliance
 */
private function checkEnvironmentalCompliance($asset) {
    // Check if asset contains hazardous materials
    $hazardousKeywords = ['battery', 'chemical', 'oil', 'refrigerant'];

    $hasHazardous = false;
    foreach ($hazardousKeywords as $keyword) {
        if (stripos($asset['name'], $keyword) !== false ||
            stripos($asset['description'] ?? '', $keyword) !== false) {
            $hasHazardous = true;
            break;
        }
    }

    return [
        'contains_hazardous' => $hasHazardous,
        'disposal_permits_required' => $hasHazardous,
        'special_handling' => $hasHazardous ? 'Follow hazardous waste disposal regulations' : 'Standard disposal',
        'environmental_fee' => $hasHazardous ? 'May apply' : 'None'
    ];
}

/**
 * Trigger reorder alert for consumables
 */
private function triggerReorderAlert($asset, $currentQty) {
    // This would send notification to procurement
    // For now, just log
    error_log("Reorder alert: Asset {$asset['name']} (ID: {$asset['id']}) is at {$currentQty} units (reorder point: {$asset['reorder_point']})");

    // Could also create a task or notification record
}
```

---

### Phase 3: Database Schema Updates
**Timeline:** Week 2-3
**Priority:** HIGH

**File: `database/migrations/iso_alignment_schema_updates.sql`**
```sql
-- ============================================================================
-- ISO Standards Alignment - Database Schema Updates
-- ============================================================================

-- 1. Add reference columns to all operations (if not already present)
-- ============================================================================

-- Borrowed Tools
ALTER TABLE borrowed_tools
ADD COLUMN IF NOT EXISTS ref VARCHAR(20) UNIQUE AFTER id,
ADD COLUMN IF NOT EXISTS risk_level ENUM('low', 'medium', 'high') DEFAULT 'low' AFTER ref,
ADD COLUMN IF NOT EXISTS usage_conditions TEXT AFTER risk_level,
ADD COLUMN IF NOT EXISTS monitoring_required TINYINT(1) DEFAULT 0 AFTER usage_conditions,
ADD COLUMN IF NOT EXISTS inspection_required TINYINT(1) DEFAULT 0 AFTER monitoring_required;

CREATE INDEX IF NOT EXISTS idx_borrowed_tools_ref ON borrowed_tools(ref);
CREATE INDEX IF NOT EXISTS idx_borrowed_tools_risk ON borrowed_tools(risk_level);

-- Maintenance Records
ALTER TABLE maintenance_records
ADD COLUMN IF NOT EXISTS ref VARCHAR(20) UNIQUE AFTER id,
ADD COLUMN IF NOT EXISTS strategy ENUM('predictive', 'preventive', 'corrective') DEFAULT 'corrective' AFTER ref,
ADD COLUMN IF NOT EXISTS criticality ENUM('low', 'medium', 'high') DEFAULT 'low' AFTER strategy,
ADD COLUMN IF NOT EXISTS cost DECIMAL(10,2) DEFAULT NULL AFTER criticality,
ADD COLUMN IF NOT EXISTS downtime_hours DECIMAL(5,2) DEFAULT NULL AFTER cost,
ADD COLUMN IF NOT EXISTS expected_completion DATE DEFAULT NULL AFTER downtime_hours;

CREATE INDEX IF NOT EXISTS idx_maintenance_ref ON maintenance_records(ref);
CREATE INDEX IF NOT EXISTS idx_maintenance_strategy ON maintenance_records(strategy);

-- Incidents
ALTER TABLE incidents
ADD COLUMN IF NOT EXISTS ref VARCHAR(20) UNIQUE AFTER id,
ADD COLUMN IF NOT EXISTS severity ENUM('minor', 'moderate', 'major', 'critical') DEFAULT 'minor' AFTER ref,
ADD COLUMN IF NOT EXISTS has_injury TINYINT(1) DEFAULT 0 AFTER severity,
ADD COLUMN IF NOT EXISTS environmental_impact TINYINT(1) DEFAULT 0 AFTER has_injury,
ADD COLUMN IF NOT EXISTS estimated_cost DECIMAL(10,2) DEFAULT NULL AFTER environmental_impact,
ADD COLUMN IF NOT EXISTS downtime_hours DECIMAL(5,2) DEFAULT NULL AFTER estimated_cost,
ADD COLUMN IF NOT EXISTS root_cause TEXT AFTER downtime_hours,
ADD COLUMN IF NOT EXISTS corrective_actions TEXT AFTER root_cause,
ADD COLUMN IF NOT EXISTS preventive_actions TEXT AFTER corrective_actions,
ADD COLUMN IF NOT EXISTS investigation_status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending' AFTER preventive_actions;

CREATE INDEX IF NOT EXISTS idx_incidents_ref ON incidents(ref);
CREATE INDEX IF NOT EXISTS idx_incidents_severity ON incidents(severity);
CREATE INDEX IF NOT EXISTS idx_incidents_status ON incidents(investigation_status);

-- Withdrawals
ALTER TABLE withdrawals
ADD COLUMN IF NOT EXISTS ref VARCHAR(20) UNIQUE AFTER id,
ADD COLUMN IF NOT EXISTS withdrawal_type ENUM('consumable', 'disposal') DEFAULT 'consumable' AFTER ref,
ADD COLUMN IF NOT EXISTS is_disposal TINYINT(1) DEFAULT 0 AFTER withdrawal_type,
ADD COLUMN IF NOT EXISTS quantity_withdrawn INT DEFAULT 1 AFTER is_disposal,
ADD COLUMN IF NOT EXISTS project_id INT DEFAULT NULL AFTER quantity_withdrawn,
ADD COLUMN IF NOT EXISTS withdrawn_by INT DEFAULT NULL AFTER project_id,
ADD COLUMN IF NOT EXISTS purpose VARCHAR(200) DEFAULT NULL AFTER withdrawn_by,
ADD COLUMN IF NOT EXISTS disposal_reason VARCHAR(200) DEFAULT NULL AFTER purpose,
ADD COLUMN IF NOT EXISTS disposal_method ENUM('Resale', 'Trade-in', 'Scrap/Recycle', 'Donation', 'Other') DEFAULT NULL AFTER disposal_reason,
ADD COLUMN IF NOT EXISTS residual_value DECIMAL(10,2) DEFAULT NULL AFTER disposal_method,
ADD COLUMN IF NOT EXISTS environmental_compliance TEXT DEFAULT NULL AFTER residual_value,
ADD COLUMN IF NOT EXISTS status ENUM('draft', 'approved', 'completed', 'cancelled') DEFAULT 'approved' AFTER environmental_compliance,
ADD COLUMN IF NOT EXISTS workflow_status ENUM('draft', 'pending_verification', 'pending_authorization', 'approved', 'rejected') DEFAULT 'approved' AFTER status;

CREATE INDEX IF NOT EXISTS idx_withdrawals_ref ON withdrawals(ref);
CREATE INDEX IF NOT EXISTS idx_withdrawals_type ON withdrawals(withdrawal_type);
CREATE INDEX IF NOT EXISTS idx_withdrawals_project ON withdrawals(project_id);

-- 2. Add ISO 55000 process tracking columns
-- ============================================================================

-- Assets: Add lifecycle tracking
ALTER TABLE assets
ADD COLUMN IF NOT EXISTS mtbf_days DECIMAL(8,2) DEFAULT NULL AFTER available_quantity,
ADD COLUMN IF NOT EXISTS mttr_hours DECIMAL(5,2) DEFAULT NULL AFTER mtbf_days,
ADD COLUMN IF NOT EXISTS availability_percent DECIMAL(5,2) DEFAULT 100.00 AFTER mttr_hours,
ADD COLUMN IF NOT EXISTS total_maintenance_cost DECIMAL(10,2) DEFAULT 0.00 AFTER availability_percent,
ADD COLUMN IF NOT EXISTS last_performance_review DATE DEFAULT NULL AFTER total_maintenance_cost;

CREATE INDEX IF NOT EXISTS idx_assets_availability ON assets(availability_percent);

-- Transfers: Add change management tracking
ALTER TABLE transfers
ADD COLUMN IF NOT EXISTS risk_assessment TEXT DEFAULT NULL AFTER ref,
ADD COLUMN IF NOT EXISTS performance_impact ENUM('low', 'medium', 'high') DEFAULT 'low' AFTER risk_assessment,
ADD COLUMN IF NOT EXISTS verification_required TINYINT(1) DEFAULT 0 AFTER performance_impact;

-- 3. Create ISO 55000 tracking tables
-- ============================================================================

-- Asset Performance Indicators
CREATE TABLE IF NOT EXISTS asset_performance_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    recorded_date DATE NOT NULL,
    mtbf_days DECIMAL(8,2) DEFAULT NULL,
    mttr_hours DECIMAL(5,2) DEFAULT NULL,
    availability_percent DECIMAL(5,2) DEFAULT NULL,
    utilization_rate DECIMAL(5,2) DEFAULT NULL,
    maintenance_cost DECIMAL(10,2) DEFAULT NULL,
    performance_rating ENUM('Poor', 'Fair', 'Good', 'Excellent') DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    INDEX idx_api_asset (asset_id),
    INDEX idx_api_date (recorded_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ISO 55000 Section 9 - Performance evaluation history';

-- Maintenance Schedules
CREATE TABLE IF NOT EXISTS maintenance_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    strategy ENUM('predictive', 'preventive', 'corrective') DEFAULT 'preventive',
    frequency VARCHAR(50) DEFAULT NULL COMMENT 'Monthly, Quarterly, etc.',
    last_maintenance_date DATE DEFAULT NULL,
    next_maintenance_date DATE DEFAULT NULL,
    tasks TEXT DEFAULT NULL,
    estimated_duration_hours DECIMAL(4,1) DEFAULT NULL,
    responsible_party VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    INDEX idx_ms_asset (asset_id),
    INDEX idx_ms_next_date (next_maintenance_date),
    INDEX idx_ms_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ISO 55000 Section 8.3.3 - Maintenance planning';

-- Incident CAPA Tracking
CREATE TABLE IF NOT EXISTS incident_capa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    action_type ENUM('corrective', 'preventive') NOT NULL,
    action_description TEXT NOT NULL,
    responsible_party VARCHAR(100) NOT NULL,
    target_date DATE NOT NULL,
    completion_date DATE DEFAULT NULL,
    verification_method VARCHAR(200) DEFAULT NULL,
    status ENUM('open', 'in_progress', 'completed', 'overdue') DEFAULT 'open',
    effectiveness_verified TINYINT(1) DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
    INDEX idx_capa_incident (incident_id),
    INDEX idx_capa_status (status),
    INDEX idx_capa_target (target_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ISO 55000 Section 10 - Corrective and Preventive Actions';

-- ============================================================================
-- Add comments to document ISO standard compliance
-- ============================================================================

ALTER TABLE borrowed_tools COMMENT='ISO 55000 Section 8.3.1 - Asset utilization management';
ALTER TABLE maintenance_records COMMENT='ISO 55000 Section 8.3.3 - Maintenance management';
ALTER TABLE transfers COMMENT='ISO 55000 Section 8.3.2 - Change management';
ALTER TABLE incidents COMMENT='ISO 55000 Section 6.1 - Risk and incident management';
ALTER TABLE withdrawals COMMENT='ISO 55000 Section 8.3.4 - Asset disposal and consumable tracking';
```

---

### Phase 4: Views and UI Updates
**Timeline:** Week 4-5
**Priority:** MEDIUM

Update all views to display reference numbers instead of IDs:

**Files to Update:**
- `views/borrowed-tools/_table.php` - Show ref column
- `views/maintenance/_table.php` - Show ref column
- `views/incidents/_table.php` - Show ref column
- `views/withdrawals/_table.php` - Show ref column

**Pattern (example for borrowed-tools):**
```php
<!-- Before -->
<td><?= htmlspecialchars($borrow['id']) ?></td>

<!-- After -->
<td>
    <span class="badge bg-secondary font-monospace">
        <?= htmlspecialchars($borrow['ref'] ?? 'N/A') ?>
    </span>
</td>
```

---

### Phase 5: Testing and Validation
**Timeline:** Week 6
**Priority:** CRITICAL

**Testing Checklist:**

1. **Reference Generation Tests**
   - [ ] Assets generate CON-AST-YYYY-NNNN
   - [ ] Transfers generate CON-TRF-YYYY-NNNN
   - [ ] Borrowing generates CON-BOR-YYYY-NNNN
   - [ ] Maintenance generates CON-MNT-YYYY-NNNN
   - [ ] Incidents generate CON-INC-YYYY-NNNN
   - [ ] Withdrawals generate CON-WTH-YYYY-NNNN
   - [ ] Legacy format generates CON-XXX-LEG-NNNN
   - [ ] References are unique across all operations
   - [ ] References display in views correctly

2. **ISO 55000 Process Tests**
   - [ ] Borrowing risk assessment works
   - [ ] High-risk borrows require authorization
   - [ ] Low-risk borrows auto-approve
   - [ ] Maintenance strategy determination works
   - [ ] MTBF/MTTR calculation works
   - [ ] Transfer change management works
   - [ ] Incident severity assessment works
   - [ ] CAPA generation works
   - [ ] Consumable withdrawal updates quantity
   - [ ] Asset disposal requires authorization
   - [ ] Reorder alerts trigger correctly

3. **Backward Compatibility Tests**
   - [ ] Existing assets still work
   - [ ] Existing transfers still work
   - [ ] No data loss from schema changes
   - [ ] Existing workflows not broken

---

## 📈 SUCCESS METRICS

### Quantitative Metrics

| Metric | Current | Target | Timeline |
|--------|---------|--------|----------|
| Reference Coverage | 33% (2/6) | 100% (6/6) | Week 2 |
| ISO 55000 Process Implementation | 1% | 80% | Week 8 |
| Traceability Score | 33% | 100% | Week 2 |
| Risk-Based Authorizations | 0% | 100% | Week 4 |
| CAPA Implementation | 0% | 100% | Week 6 |
| Performance Monitoring | 0% | 100% | Week 8 |

### Qualitative Metrics

- ✅ All operations have unique, standardized references
- ✅ Risk-based decision making implemented
- ✅ Traceability across entire asset lifecycle
- ✅ Performance indicators tracked
- ✅ Preventive maintenance strategy implemented
- ✅ Incident management with CAPA
- ✅ Consumable tracking and reorder alerts

---

## 🎯 DELIVERABLES

### Week 2 Deliverables
- [ ] ISO9001DocumentControl class
- [ ] Reference generation for all 6 operations
- [ ] Database schema updated
- [ ] Existing records backfilled
- [ ] Views updated to display references

### Week 4 Deliverables
- [ ] AssetUtilizationManager (borrowing)
- [ ] Risk-based authorization implemented
- [ ] Usage tracking functional

### Week 6 Deliverables
- [ ] MaintenanceStrategyManager
- [ ] RiskIncidentManager with CAPA
- [ ] Performance indicators calculated

### Week 8 Deliverables
- [ ] Complete ISO 55000 process implementation
- [ ] All tests passing
- [ ] Documentation updated
- [ ] Training materials created

---

## 📚 REFERENCES

### ISO Standards
- **ISO 9001:2015** - Quality Management Systems (for document control)
- **ISO 55000:2014** - Asset Management Systems (for operational processes)
- **ISO 55001:2014** - Asset Management - Requirements
- **ISO 55002:2018** - Asset Management - Guidelines

### ConstructLink Documentation
- `ISO_ASSET_REFERENCE_STANDARD.md`
- `ISO55000_TRANSFER_REFERENCES.md`
- `ISO_IMPLEMENTATION_COMPLETE.md`

---

## ✅ APPROVAL SIGN-OFF

**Plan Prepared By:** ConstructLink Development Team
**Date:** November 4, 2025
**Status:** Ready for Implementation

**Approved By:**
- [ ] System Administrator
- [ ] Technical Lead
- [ ] Project Manager

---

**END OF DOCUMENT**
