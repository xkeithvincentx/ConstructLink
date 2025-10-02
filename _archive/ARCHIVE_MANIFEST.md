# ConstructLink Archive Manifest

**Archive Date:** October 2, 2025
**Purpose:** Clean up root directory by archiving temporary, test, and old version files

## Archive Summary

| Category | Count | Location |
|----------|-------|----------|
| Test/Diagnostic Files | 22 | `_archive/test_files/` |
| Temporary Documentation | 16 | `_archive/documentation/` |
| Old View Files | 13 | `_archive/old_versions/views/` |
| Backup Core/Models/Controllers | 3 | `_archive/old_versions/{core,models,controllers}/` |
| SQL Patch Scripts | 4 | `_archive/sql_patches/` |
| Miscellaneous Files | 5 | `_archive/misc/` |
| **TOTAL** | **63** | |

---

## Files Archived

### 1. Test & Diagnostic Files (`test_files/`)

Test scripts and diagnostic tools used during development:

```
analyze_existing_assets.php
check_projects_table.php
debug_asset_edit_form.php
final_asset_edit_diagnostic.php
run_migration.php
simple_api_test.php
simple_asset_diagnostic.php
test_api_endpoints_direct.php
test_api_endpoints.php
test_api_fix.php
test_asset_classification_debug.php
test_asset_creation.php
test_asset_standardization.php
test_asset_system.php
test_categories_table.php
test_category_business_logic.php
test_file_upload.php
test_form_api_calls.php
test_iso_references.php
test_migration.php
test_post_data_debug.php
test_validator.php
```

**Safety:** None of these files are referenced in `index.php`, `routes.php`, or any production code.

---

### 2. Temporary Documentation (`documentation/`)

Reports and implementation summaries generated during development:

```
ASSET_CLASSIFICATION_DEBUG_REPORT.md
ASSET_DISCIPLINE_ISSUE_ANALYSIS.md
ASSET_EDIT_FORM_DIAGNOSTIC_REPORT.md
ASSET_MANAGEMENT_TEST_REPORT.md
ASSET_STANDARDIZATION_README.md
CATEGORY_BUSINESS_LOGIC_IMPLEMENTATION_SUMMARY.md
DEPLOYMENT_CHECKLIST.md
DISCIPLINE_SCALABILITY_SOLUTION.md
ENHANCED_RETURN_PROCESS_SUMMARY.md
FILE_PREVIEW_ENHANCEMENTS_SUMMARY.md
LEGACY_ASSET_TROUBLESHOOTING.md
LOGICAL_FILE_UPLOAD_FIXES_SUMMARY.md
PROCUREMENT_FILE_UPLOAD_SUMMARY.md
SETUP_LEGACY_WORKFLOW.md
test_brands.md
test_disciplines.md
```

**Note:** Main `README.md` remains in root directory.

---

### 3. Old Version Files (`old_versions/`)

#### Controllers (`old_versions/controllers/`)
```
Bir2307Controller_backup.php
```

#### Core (`old_versions/core/`)
```
Auth_FIXED.php
```
**Note:** Application uses `core/Auth.php` (active), not `Auth_FIXED.php`.

#### Models (`old_versions/models/`)
```
TransferModel_backup.php
```

#### Views (`old_versions/views/`)
```
approve_old.php
cancel_old.php
create_old.php
edit_old.php
index_backup.php
index_old.php
manage_banks_old.php
profile_old.php
release_old.php
reports_old.php
return_old.php
review_old.php
view_old.php
```

**Safety:** No references to `*_old.php`, `*_backup.php`, or `Auth_FIXED.php` found in active codebase.

---

### 4. SQL Patch Scripts (`sql_patches/`)

One-time SQL fix scripts (already applied):

```
diagnose_return_workflow_issues.sql
fix_asset_location_issues.sql
fix_inconsistent_return_data.sql
fix_return_status_enum.sql
```

**Note:** Production migrations remain in `database/migrations/`.

---

### 5. Miscellaneous Files (`misc/`)

```
2307 Jan 2018 ENCS v3.xlsx
asset_edit_diagnostic_results.json
getMessage (empty file)
Pages from 2307 Jan 2018 ENCS v3.pdf
test_auto_selection.html
```

---

## Restoration Instructions

To restore any archived file:

```bash
# Example: Restore a test file
cp _archive/test_files/test_validator.php .

# Example: Restore old view
cp _archive/old_versions/views/index_old.php views/some_module/
```

---

## Verification Completed

✅ No production code references archived files
✅ `index.php` loads only active files
✅ `routes.php` integrity maintained
✅ All MVC structure intact
✅ Application tested successfully post-cleanup

---

## Archive Structure

```
_archive/
├── ARCHIVE_MANIFEST.md (this file)
├── test_files/ (22 files)
├── documentation/ (16 files)
├── old_versions/
│   ├── controllers/ (1 file)
│   ├── core/ (1 file)
│   ├── models/ (1 file)
│   └── views/ (13 files)
├── sql_patches/ (4 files)
└── misc/ (5 files)
```

**Total Files Archived:** 63
**Root Directory:** Cleaned and production-ready
