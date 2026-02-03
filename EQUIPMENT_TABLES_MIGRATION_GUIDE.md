# Equipment Tables Migration Guide

## Executive Summary

**Issue:** ConstructLink has **incomplete table refactoring** causing foreign key mismatches between database tables and application code.

**Impact:** The ISO55000ReferenceGenerator and other core modules cannot function properly because they expect different tables than what the database references.

**Solution:** Safe data migration from old tables to new tables with automatic backup and rollback capability.

---

## The Problem

### Three Sets of Tables Exist:

#### 1. OLD Tables (Complete Data - 114/245 records)
- `equipment_types` (114 records)
- `equipment_subtypes` (245 records)
- **Currently referenced by `inventory_items` foreign keys**

#### 2. NEW Tables (Barely Populated - 9/9 records)
- `inventory_equipment_types` (9 records)
- `inventory_subtypes` (9 records)
- **Expected by application code**

#### 3. Additional NEW Table
- `inventory_types` (6 records)
- Purpose unclear in current context

### Database State
```
inventory_items table foreign keys:
  ✗ equipment_type_id → equipment_types (OLD TABLE)
  ✗ subtype_id → equipment_subtypes (OLD TABLE)
  ✓ inventory_type_id → inventory_types (NEW TABLE - not used yet)
```

### Code Expectations
```php
// IntelligentAssetNamer.php:38-39
FROM inventory_subtypes es                    ← EXPECTS NEW TABLE
JOIN inventory_equipment_types et              ← EXPECTS NEW TABLE

// AssetSubtypeManager.php:28, 45, 94
FROM inventory_equipment_types                 ← EXPECTS NEW TABLE
FROM inventory_subtypes                        ← EXPECTS NEW TABLE
```

### Current Data Usage
```
52 items using equipment_type_id (OLD)
30 items using subtype_id (OLD)
0 items using inventory_type_id (NEW)
```

---

## The Solution

### Safe Migration Strategy

1. **Create Backups** - Automatic backup tables with timestamps
2. **Migrate Data** - Copy from old → new tables with ID mapping
3. **Update References** - Update `inventory_items` foreign key values
4. **Update Constraints** - Point foreign keys to new tables
5. **Verify Integrity** - Check for orphaned references

### Migration Features

✓ **Dry Run Mode** - Preview changes without executing
✓ **Automatic Backups** - Timestamped backup tables
✓ **Transaction Safety** - All-or-nothing execution
✓ **Rollback Script** - Restore original state if needed
✓ **Duplicate Detection** - Skips existing records
✓ **Orphan Prevention** - Verifies all references
✓ **Detailed Logging** - Shows every change

---

## How to Run

### Step 1: Preview Migration (Dry Run)

```bash
cd /Users/keithvincentranoa/Developer/ConstructLink
php migrations/migrate_equipment_to_inventory_tables.php --dry-run
```

This will show you:
- Current database state
- What will be migrated
- ID mappings (OLD → NEW)
- Foreign key changes
- **WITHOUT making any changes**

### Step 2: Run Migration (Live)

```bash
php migrations/migrate_equipment_to_inventory_tables.php
```

This will:
1. Create backup tables:
   - `equipment_types_backup_YYYYMMDD_HHMMSS`
   - `equipment_subtypes_backup_YYYYMMDD_HHMMSS`
2. Migrate 114 equipment types
3. Migrate 245 equipment subtypes
4. Update ~52 equipment_type_id references
5. Update ~30 subtype_id references
6. Update foreign key constraints
7. Verify migration integrity

### Step 3: Verify Results

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root constructlink_db -e "
SELECT
  (SELECT COUNT(*) FROM inventory_equipment_types) as equipment_types,
  (SELECT COUNT(*) FROM inventory_subtypes) as subtypes,
  (SELECT COUNT(*) FROM inventory_items WHERE equipment_type_id IS NOT NULL) as items_with_type,
  (SELECT COUNT(*) FROM inventory_items WHERE subtype_id IS NOT NULL) as items_with_subtype;
"
```

Expected results:
- `equipment_types`: **114+** (original 9 + 114 migrated - duplicates)
- `subtypes`: **245+** (original 9 + 245 migrated - duplicates)
- `items_with_type`: **52** (unchanged)
- `items_with_subtype`: **30** (unchanged)

### Step 4: Test Application

Test these key areas:
1. Asset creation with equipment type selection
2. Intelligent asset naming
3. Subtype suggestions
4. ISO reference generation
5. Asset editing with type/subtype changes

---

## Rollback (If Needed)

If something goes wrong, rollback to the original state:

```bash
php migrations/rollback_equipment_migration.php
```

This will:
1. Find the most recent backup tables
2. Restore old foreign key constraints
3. Map new IDs back to old IDs
4. Update inventory_items references
5. Restore to pre-migration state

**Note:** You will be prompted for confirmation before rollback executes.

---

## What Gets Migrated

### equipment_types → inventory_equipment_types

**Fields Mapped:**
- `id` → NEW auto-increment ID (mapping tracked)
- `category_id` → `category_id` (preserved)
- `name` → `name` (preserved)
- `description` → `description` (preserved)
- `is_active` → `is_active` (preserved)
- **NEW:** `code` → Generated from name (first 3 letters uppercase)
- `created_at` → `created_at` (preserved)
- `updated_at` → `updated_at` (preserved)

### equipment_subtypes → inventory_subtypes

**Fields Mapped:**
- `id` → NEW auto-increment ID (mapping tracked)
- `equipment_type_id` → `equipment_type_id` (mapped to NEW ID)
- `subtype_name` → `name` (renamed field)
- `subtype_name` → `technical_name` (duplicated)
- `application_area` → `description` (mapped)
- `discipline_tags` → `discipline_tags` (preserved JSON)
- `technical_specs` → `specifications_template` (renamed)
- `is_active` → `is_active` (preserved)
- **NEW:** `code` → Generated (first 3 letters + ID padded)
- `created_at` → `created_at` (preserved)
- `updated_at` → `updated_at` (preserved)

**Fields NOT Migrated (old schema only):**
- `material_type` - Not in new schema
- `power_source` - Not in new schema
- `size_category` - Not in new schema

---

## After Migration

### Old Tables
The old `equipment_types` and `equipment_subtypes` tables will remain in the database but **will no longer be referenced** by `inventory_items` foreign keys.

**Recommendation:** Keep them for 30 days, then drop them if everything works correctly.

```sql
-- After 30 days of successful operation:
DROP TABLE equipment_types;
DROP TABLE equipment_subtypes;
```

### Backup Tables
Backup tables will be created with timestamps:
- `equipment_types_backup_20251111_143022`
- `equipment_subtypes_backup_20251111_143022`

**Recommendation:** Keep backups for 60 days before dropping.

---

## Technical Details

### Foreign Key Changes

**BEFORE Migration:**
```sql
CONSTRAINT fk_inventory_equipment_type
  FOREIGN KEY (equipment_type_id)
  REFERENCES equipment_types(id)

CONSTRAINT fk_inventory_subtype
  FOREIGN KEY (subtype_id)
  REFERENCES equipment_subtypes(id)
```

**AFTER Migration:**
```sql
CONSTRAINT fk_inventory_equipment_type
  FOREIGN KEY (equipment_type_id)
  REFERENCES inventory_equipment_types(id)

CONSTRAINT fk_inventory_subtype
  FOREIGN KEY (subtype_id)
  REFERENCES inventory_subtypes(id)
```

### ID Mapping Example

```
OLD equipment_types.id → NEW inventory_equipment_types.id
1 → 10
2 → 11
3 → 12
...

OLD equipment_subtypes.id → NEW inventory_subtypes.id
1 → 20
2 → 21
3 → 22
...
```

All `inventory_items` records are updated with the new IDs automatically.

---

## Troubleshooting

### Error: "No backup tables found"
**Cause:** Migration hasn't been run yet, or backup tables were deleted.
**Solution:** Run the migration first.

### Error: "Found X orphaned references"
**Cause:** Some inventory_items reference IDs that don't exist in new tables.
**Solution:** Check the migration logs for missing mappings. This shouldn't happen with the automated migration.

### Error: "Cannot add foreign key constraint"
**Cause:** Data integrity issue - some IDs don't match.
**Solution:** Migration will rollback automatically. Check logs for details.

### Application Error: "Table doesn't exist"
**Cause:** Code expecting new tables but migration hasn't run yet.
**Solution:** Run the migration.

---

## Migration Checklist

- [ ] **Backup database** (recommended: full mysqldump)
- [ ] **Run dry-run** to preview changes
- [ ] **Review dry-run output** for any warnings
- [ ] **Run migration** in off-peak hours
- [ ] **Verify migration** completed successfully
- [ ] **Test application** functionality
- [ ] **Monitor for errors** for 24 hours
- [ ] **Keep backups** for 30-60 days
- [ ] **Document completion** date

---

## Support

If you encounter issues:
1. Check migration logs for detailed error messages
2. Verify database connection and permissions
3. Ensure XAMPP MySQL is running
4. Use rollback script if needed
5. Contact database administrator for assistance

---

## Database Schema Diagram

```
BEFORE MIGRATION:

inventory_items
├── equipment_type_id ──→ equipment_types (114 records) ← OLD
├── subtype_id ──→ equipment_subtypes (245 records)     ← OLD
└── inventory_type_id ──→ inventory_types (6 records)   ← NEW (unused)

Application Code Expects:
├── inventory_equipment_types (9 records)                ← NEW (incomplete)
└── inventory_subtypes (9 records)                       ← NEW (incomplete)


AFTER MIGRATION:

inventory_items
├── equipment_type_id ──→ inventory_equipment_types (114+ records) ← MIGRATED
├── subtype_id ──→ inventory_subtypes (245+ records)               ← MIGRATED
└── inventory_type_id ──→ inventory_types (6 records)              ← NEW (still unused)

Application Code:
├── inventory_equipment_types (COMPLETE)                ← FIXED
└── inventory_subtypes (COMPLETE)                       ← FIXED

Old Tables (retained for safety):
├── equipment_types_backup_20251111_143022
├── equipment_subtypes_backup_20251111_143022
├── equipment_types (no longer referenced)
└── equipment_subtypes (no longer referenced)
```

---

## Migration Script Locations

- **Migration:** `/Users/keithvincentranoa/Developer/ConstructLink/migrations/migrate_equipment_to_inventory_tables.php`
- **Rollback:** `/Users/keithvincentranoa/Developer/ConstructLink/migrations/rollback_equipment_migration.php`
- **This Guide:** `/Users/keithvincentranoa/Developer/ConstructLink/EQUIPMENT_TABLES_MIGRATION_GUIDE.md`

---

**Generated:** 2025-11-11
**ConstructLink™ Database Migration**
