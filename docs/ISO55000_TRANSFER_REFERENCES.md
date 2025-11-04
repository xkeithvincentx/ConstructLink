# ISO 55000:2024 Transfer References Implementation

## Overview

This document details the implementation of ISO 55000:2024 compliant reference generation for the Transfers module in ConstructLink™.

## Implementation Date

November 4, 2025

## Objective

Apply the existing ISO 55000:2024 reference generation system to transfer records, ensuring consistency, traceability, and compliance with asset management standards.

## Reference Format

Transfer references follow the same ISO 55000:2024 format as asset references:

```
[ORG]-[YEAR]-[CAT]-[DIS]-[SEQ]
```

### Components

- **ORG**: Organization code (default: 'CON')
- **YEAR**: Current year (4 digits) or 'LEG' for legacy
- **CAT**: Category code (2 characters, from asset's category)
- **DIS**: Discipline code (2 characters, from asset's discipline)
- **SEQ**: Sequential number (4 digits, zero-padded)

### Example

```
CON-2025-EQ-ME-0001
```

This represents:
- **CON**: ConstructLink/V Cutamora Construction
- **2025**: Year of transfer
- **EQ**: Equipment category
- **ME**: Mechanical discipline
- **0001**: First transfer in this category/discipline/year combination

## Design Principles

### DRY (Don't Repeat Yourself)

The implementation strictly follows DRY principles by:

1. **Reusing Existing Code**: No duplication of ISO 55000 reference generation logic
2. **Extending Base Class**: Modified `ISO55000ReferenceGenerator` to accept table name parameter
3. **Single Helper Function**: Created `generateTransferReference()` that wraps the existing generator
4. **Consistent Pattern**: Follows the exact same pattern as `generateAssetReference()`

### Code Reusability

```php
// Asset reference generation
$generator = new ISO55000ReferenceGenerator('assets');
$ref = $generator->generateReference($categoryId, $disciplineId, false);

// Transfer reference generation (same code, different table)
$generator = new ISO55000ReferenceGenerator('transfers');
$ref = $generator->generateReference($categoryId, $disciplineId, false);
```

## Files Modified

### 1. Core Files

#### `/core/ISO55000ReferenceGenerator.php`

**Changes:**
- Added `$tableName` property to support multiple tables
- Modified constructor to accept optional `$tableName` parameter (default: 'assets')
- Updated `getNextSequentialNumber()` to use `$this->tableName`
- Updated `isReferenceUnique()` to use `$this->tableName`

**Impact:** Backward compatible - existing asset code continues to work without changes

#### `/core/helpers.php`

**Changes:**
- Added `generateTransferReference()` function
- Follows same pattern as `generateAssetReference()`
- Uses ISO55000ReferenceGenerator with 'transfers' table

### 2. Model Files

#### `/models/TransferModel.php`

**Changes:**
- Added 'ref' to `$fillable` array
- Modified `createTransfer()` method to generate reference before creating record
- Reference generation occurs after asset validation, before workflow logic
- Uses asset's category and discipline for traceability

**Code:**
```php
// Generate ISO 55000:2024 compliant transfer reference
$transferReference = generateTransferReference(
    $asset['category_id'] ?? null,
    $asset['primary_discipline'] ?? null
);
$data['ref'] = $transferReference;
```

### 3. View Files

#### `/views/transfers/_table.php` (Desktop View)

**Changes:**
- Changed column header from "ID" to "Reference"
- Updated display to show `$transfer['ref']` instead of ID
- Added `font-monospace` class for consistent reference display
- Handles null references with fallback to 'N/A'

#### `/views/transfers/_mobile_cards.php` (Mobile View)

**Changes:**
- Updated card header to display reference instead of ID
- Added `font-monospace` class for consistency
- Updated aria-labels for accessibility

### 4. Database Schema

#### `transfers` table

**Changes:**
```sql
ALTER TABLE transfers
ADD COLUMN ref VARCHAR(25) NULL UNIQUE AFTER id;
```

**Properties:**
- Type: VARCHAR(25)
- Nullable: YES (allows gradual migration)
- Unique: YES (ensures no duplicate references)
- Position: After `id` column

## Migration Strategy

### Phase 1: Temporary References (Completed)

Backfilled existing 22 transfer records with temporary sequential references:

```sql
UPDATE transfers
SET ref = CONCAT('TR-2025-', LPAD(id, 4, '0'))
WHERE ref IS NULL OR ref = '';
```

**Result:** All 22 transfers now have references (TR-2025-0001 through TR-2025-0022)

### Phase 2: ISO 55000 References (Optional)

Created `/migrations/generate_iso_transfer_references.php` script to upgrade temporary references to ISO 55000 format.

**To run:**
```bash
php migrations/generate_iso_transfer_references.php
```

**Note:** This script can be run via web interface (requires System Admin login) or CLI.

### Phase 3: Future Transfers

All new transfers created after this implementation will automatically receive ISO 55000:2024 compliant references.

## Traceability

Transfer references are directly linked to the transferred asset's properties:

1. **Category**: Inherited from asset's category_id
2. **Discipline**: Inherited from asset's primary_discipline
3. **Sequential Number**: Unique within category/discipline/year combination

This provides:
- **Asset Lineage**: Easy to identify what type of asset was transferred
- **Chronological Tracking**: Sequential numbering shows transfer order
- **Cross-Module Consistency**: Same reference format as assets

## Verification

### Database Verification

```sql
-- Check all transfers have references
SELECT
    COUNT(*) as total_transfers,
    COUNT(ref) as with_reference,
    COUNT(*) - COUNT(ref) as without_reference
FROM transfers;

-- Expected: total_transfers=22, with_reference=22, without_reference=0
```

### Code Verification

All PHP files pass syntax validation:
- ✅ `/core/ISO55000ReferenceGenerator.php` - No syntax errors
- ✅ `/core/helpers.php` - No syntax errors
- ✅ `/models/TransferModel.php` - No syntax errors

## DRY Compliance Verification

### ✅ No Code Duplication

The ISO 55000 reference generation logic exists in exactly ONE place:
- `ISO55000ReferenceGenerator::generateReference()`

### ✅ Consistent Helper Pattern

Both asset and transfer helpers follow the same pattern:
```php
// Assets
function generateAssetReference($categoryId, $disciplineId, $isLegacy) {
    $generator = new ISO55000ReferenceGenerator('assets');
    return $generator->generateReference($categoryId, $disciplineId, $isLegacy);
}

// Transfers (same pattern)
function generateTransferReference($categoryId, $disciplineId) {
    $generator = new ISO55000ReferenceGenerator('transfers');
    return $generator->generateReference($categoryId, $disciplineId, false);
}
```

### ✅ Single Responsibility

Each component has a single, well-defined responsibility:
- `ISO55000ReferenceGenerator`: Generate and validate ISO references
- `generateTransferReference()`: Convenience wrapper for transfers
- `TransferModel::createTransfer()`: Create transfers with references
- Views: Display references consistently

## Testing Checklist

- [x] Database schema updated with ref column
- [x] All existing transfers have references (22/22)
- [x] Helper function created and tested
- [x] Model updated to generate references on creation
- [x] Desktop view displays references instead of IDs
- [x] Mobile view displays references instead of IDs
- [x] No PHP syntax errors in modified files
- [x] DRY principles maintained throughout
- [x] Backward compatibility preserved for assets
- [x] Migration scripts created for existing data

## Future Considerations

### Upgrading to Full ISO References

When ready to upgrade temporary references to full ISO format:

1. Ensure all assets have proper category and discipline assignments
2. Verify all categories have valid iso_code values
3. Run: `php migrations/generate_iso_transfer_references.php`
4. This can be done at any time without breaking functionality

### Reference Display

Current implementation displays references as-is. Consider future enhancements:
- Tooltip showing reference breakdown (ORG-YEAR-CAT-DIS-SEQ)
- Color coding by category or status
- Search/filter by reference pattern

## Support

For questions or issues related to ISO 55000 transfer references:

1. Check this documentation
2. Review `/core/ISO55000ReferenceGenerator.php` code comments
3. Test with migration scripts in `/migrations/`
4. Contact system administrator

## Summary

The ISO 55000:2024 transfer reference implementation:
- ✅ Follows DRY principles strictly
- ✅ Reuses existing, proven code
- ✅ Maintains consistency across modules
- ✅ Provides proper traceability
- ✅ Handles existing data gracefully
- ✅ Sets foundation for future enhancements
- ✅ Complies with ISO 55000:2024 standards

---

**Document Version:** 1.0
**Last Updated:** November 4, 2025
**Author:** Claude Code (ConstructLink Implementation Team)
