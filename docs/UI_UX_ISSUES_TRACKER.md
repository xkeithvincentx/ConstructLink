# UI/UX Issues Tracker - Borrowed Tools Module
**Last Updated:** October 20, 2025
**Status:** 14 issues identified (1 critical, 4 high, 5 medium, 4 low)

---

## CRITICAL ISSUES (Fix Immediately)

### Issue #1: Color Contrast Failure - Info Badges ❌
- **Severity:** CRITICAL
- **WCAG Violation:** Yes (3.8:1 vs 4.5:1 minimum)
- **Affected Users:** 18% of requests in "Pending Approval" status
- **File:** `/helpers/ViewHelper.php`
- **Line:** 27
- **Current Code:**
  ```php
  'Pending Approval' => ['class' => 'info', 'icon' => 'hourglass-split'],
  ```
- **Fixed Code:**
  ```php
  'Pending Approval' => ['class' => 'info text-dark', 'icon' => 'hourglass-split'],
  ```
- **Effort:** 1 minute
- **Testing:** Use browser DevTools contrast checker
- **Status:** ⏳ Pending

---

## HIGH PRIORITY ISSUES (Fix This Sprint)

### Issue #2: Modal Component Not Implemented ⚠️
- **Severity:** HIGH
- **Impact:** 257 lines of duplicated modal code
- **Files Affected:**
  - `/views/borrowed-tools/index.php` (lines 590-900)
- **Modals to Migrate:**
  1. `batchVerifyModal` (line 590)
  2. `batchAuthorizeModal` (line 632)
  3. `batchReleaseModal` (line 674)
  4. `batchReturnModal` (line 716)
  5. `batchExtendModal` (line 776)
  6. `quickIncidentModal` (line 847)
- **Component Location:** `/views/components/modal.php`
- **Estimated Savings:** 180 lines of code
- **Effort:** 2 hours
- **Status:** ⏳ Pending

**Migration Template:**
```php
<?php
// Old modal (42 lines) becomes (8 lines):
ob_start();
?>
<!-- Modal body content here -->
<?php
$body = ob_get_clean();

ob_start();
?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-primary">Submit</button>
<?php
$actions = ob_get_clean();

$id = 'batchVerifyModal';
$title = 'Verify Batch';
$icon = 'check-circle';
$headerClass = 'bg-warning text-dark';
$size = 'lg';
include APP_ROOT . '/views/components/modal.php';
?>
```

---

### Issue #3: Borrowed Status Color Inconsistency ⚠️
- **Severity:** HIGH
- **Impact:** Active state appears inactive
- **File:** `/helpers/ViewHelper.php`
- **Line:** 30
- **Current Code:**
  ```php
  'Borrowed' => ['class' => 'secondary', 'icon' => 'box-arrow-up'],
  ```
- **Fixed Code:**
  ```php
  'Borrowed' => ['class' => 'primary', 'icon' => 'box-arrow-up'],
  ```
- **Rationale:** "Borrowed" is an active state, should use primary (blue) like "Released"
- **Effort:** 1 minute
- **Testing:** Visual check across all borrowed items
- **Status:** ⏳ Pending

---

### Issue #4: Export/Print Buttons Missing ARIA Labels ⚠️
- **Severity:** HIGH (Accessibility)
- **Impact:** Screen reader users can't identify buttons on mobile
- **File:** `/views/borrowed-tools/partials/_borrowed_tools_list.php`
- **Lines:** 13-20
- **Current Code:**
  ```html
  <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
      <i class="bi bi-file-earmark-excel me-1"></i>
      <span class="d-none d-md-inline">Export</span>
  </button>
  ```
- **Fixed Code:**
  ```html
  <button class="btn btn-sm btn-outline-primary"
          onclick="exportToExcel()"
          aria-label="Export to Excel">
      <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>
      <span class="d-none d-md-inline">Export</span>
  </button>
  ```
- **Also Fix:** Print button (add `aria-label="Print table"`)
- **Effort:** 2 minutes
- **Testing:** Screen reader (NVDA/VoiceOver)
- **Status:** ⏳ Pending

---

### Issue #5: Loading State Screen Reader Announcement ⚠️
- **Severity:** HIGH (Accessibility)
- **Impact:** Screen reader users don't know processing is happening
- **File:** `/assets/js/borrowed-tools/ajax-handler.js`
- **Line:** 202
- **Current Code:**
  ```javascript
  button.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${loadingText}`;
  ```
- **Fixed Code:**
  ```javascript
  button.innerHTML = `
      <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
      <span class="visually-hidden">Processing, please wait</span>
      ${loadingText}
  `;
  ```
- **Effort:** 5 minutes
- **Testing:** Screen reader + monitor ARIA live region
- **Status:** ⏳ Pending

---

## MEDIUM PRIORITY ISSUES (Polish Improvements)

### Issue #6: Critical Tool Badge Config Dependency
- **Severity:** MEDIUM
- **Impact:** Hardcoded fallback creates maintenance burden
- **File:** `/helpers/ViewHelper.php`
- **Lines:** 177-183
- **Current Implementation:**
  - Hardcoded `50000` fallback
  - Weak `function_exists('config')` check
- **Recommended Solution:**
  ```php
  class ViewHelper
  {
      private static $criticalToolThreshold = 50000;

      public static function setCriticalToolThreshold($threshold) {
          self::$criticalToolThreshold = $threshold;
      }

      public static function renderCriticalToolBadge(float $cost, ?float $threshold = null): string
      {
          $threshold = $threshold ?? self::$criticalToolThreshold;
          // ... rest of method
      }
  }
  ```
- **Bootstrap Code:**
  ```php
  // In application bootstrap/init:
  ViewHelper::setCriticalToolThreshold(config('business_rules.critical_tool_threshold'));
  ```
- **Effort:** 15 minutes
- **Status:** ⏳ Pending

---

### Issue #7: Purpose Column Too Narrow on Tablet
- **Severity:** MEDIUM
- **Impact:** Text truncation too aggressive
- **File:** `/views/borrowed-tools/partials/_borrowed_tools_list.php`
- **Line:** 393
- **Current:**
  ```html
  <span class="text-truncate d-inline-block" style="max-width: 200px;">
  ```
- **Options:**
  1. Increase to 300px: `style="max-width: 300px;"`
  2. Hide on tablet: Add `d-none d-lg-table-cell` to `<th>` and `<td>`
- **Recommendation:** Option 2 (hide on < 992px)
- **Effort:** 10 minutes
- **Status:** ⏳ Pending

---

### Issue #8: Page Reload After AJAX Action
- **Severity:** MEDIUM
- **Impact:** Jarring user experience
- **File:** `/assets/js/borrowed-tools/ajax-handler.js`
- **Line:** 33
- **Current:**
  ```javascript
  setTimeout(() => window.location.reload(), 1500);
  ```
- **Recommended:**
  - Update table row dynamically
  - Update status badge via DOM manipulation
  - Update statistics cards
  - No full page reload
- **Effort:** 1 hour (requires refactoring)
- **Status:** ⏳ Pending (Future enhancement)

---

### Issue #9: Condition Badge Inline Spacing
- **Severity:** MEDIUM (Visual Polish)
- **Impact:** Badges appear too close together
- **File:** `/helpers/ViewHelper.php`
- **Lines:** 80, 97
- **Current:**
  ```php
  $separator = $inline ? ' ' : '<br>';
  return implode($separator, $badges);
  ```
- **Option 1 - Separator:**
  ```php
  $separator = $inline ? ' <span class="mx-1"></span> ' : '<br>';
  ```
- **Option 2 - Badge Class:**
  ```php
  // In renderSingleConditionBadge, add 'me-2' to first badge only
  return sprintf(
      '<span class="badge %s %s">...',
      htmlspecialchars($class),
      $label === 'Out' && $inline ? 'me-2' : ''
  );
  ```
- **Recommendation:** Option 2 (cleaner)
- **Effort:** 5 minutes
- **Status:** ⏳ Pending

---

### Issue #10: Unused renderActionButton Method
- **Severity:** MEDIUM (Code Cleanliness)
- **Impact:** Dead code in ViewHelper
- **File:** `/helpers/ViewHelper.php`
- **Lines:** 143-166
- **Current Usage:** 0 instances across borrowed-tools module
- **Options:**
  1. Implement in action column (lines 518-741 in _borrowed_tools_list.php)
  2. Remove method to reduce maintenance burden
- **Recommendation:** Option 2 (remove) - current implementation is adequate
- **Effort:** 2 minutes (remove) or 30 minutes (implement)
- **Status:** ⏳ Pending Decision

---

## LOW PRIORITY ISSUES (Future Enhancements)

### Issue #11: MVA Badge Text Truncation
- **Severity:** LOW
- **Impact:** User names may be cut off
- **File:** `/helpers/ViewHelper.php`
- **Line:** 300
- **Current:** `max-width: 80px;`
- **Recommended:**
  ```php
  // Make responsive
  $maxWidthClass = 'style="max-width: 100px;"'; // Desktop
  // Add media query or use Bootstrap d-* classes
  ```
- **Effort:** 10 minutes
- **Status:** 🔵 Backlog

---

### Issue #12: Mobile Condition Badge Display
- **Severity:** LOW
- **Impact:** May wrap awkwardly on very narrow screens
- **File:** `/views/borrowed-tools/partials/_borrowed_tools_list.php`
- **Lines:** 122, 218
- **Current:** Uses inline mode `renderConditionBadges(..., true)`
- **Recommendation:** Use stacked on mobile `renderConditionBadges(..., false)`
- **Effort:** 5 minutes
- **Status:** 🔵 Backlog

---

### Issue #13: MVA Badge Accessibility
- **Severity:** LOW (Accessibility Enhancement)
- **Impact:** M/V/A abbreviations not explained
- **File:** `/helpers/ViewHelper.php`
- **Line:** 300
- **Current:**
  ```php
  '<span class="badge badge-sm %s me-1">%s</span>'
  ```
- **Recommended:**
  ```php
  $roleLabels = [
      'M' => 'Maker',
      'V' => 'Verifier',
      'A' => 'Authorizer'
  ];
  $label = $roleLabels[strtoupper($role)] ?? $role;

  return sprintf(
      '<span class="badge badge-sm %s me-1" aria-label="%s">%s</span>',
      htmlspecialchars($color),
      htmlspecialchars($label),
      htmlspecialchars(strtoupper($role))
  );
  ```
- **Effort:** 5 minutes
- **Status:** 🔵 Backlog

---

### Issue #14: Toast Positioning in Safari
- **Severity:** LOW (Cross-Browser)
- **Impact:** Possible positioning issues in Safari
- **File:** `/assets/js/borrowed-tools/ajax-handler.js`
- **Line:** 144
- **Testing Required:** Safari macOS and iOS
- **Potential Fix:** Add webkit prefixes if needed
  ```javascript
  toast.style.webkitTransform = 'translateZ(0)'; // Force GPU acceleration
  ```
- **Effort:** 5 minutes (after testing confirms issue)
- **Status:** 🔵 Needs Testing

---

## Issue Status Legend

- ❌ **CRITICAL** - Blocks production, fix immediately
- ⚠️ **HIGH** - Should fix this sprint
- ⚙️ **MEDIUM** - Polish improvements, next sprint
- 🔵 **LOW** - Future enhancements, backlog
- ⏳ **Pending** - Awaiting fix
- ✅ **Fixed** - Implemented and tested
- 🧪 **Testing** - Fix implemented, needs QA
- 📋 **Review** - Needs code review

---

## Quick Action Checklist

**Before Production Deploy:**
- [ ] Fix Issue #1 - Info badge color contrast (CRITICAL)
- [ ] Fix Issue #3 - Borrowed status color (HIGH)
- [ ] Fix Issue #4 - ARIA labels on Export/Print (HIGH)
- [ ] Fix Issue #5 - Loading state screen reader (HIGH)

**Next Sprint:**
- [ ] Fix Issue #2 - Migrate modals to component (HIGH)
- [ ] Fix Issue #6 - Config dependency improvement (MEDIUM)
- [ ] Fix Issue #7 - Purpose column width (MEDIUM)
- [ ] Fix Issue #9 - Condition badge spacing (MEDIUM)
- [ ] Decide Issue #10 - Remove or implement action button

**Future Backlog:**
- [ ] Issue #8 - Dynamic AJAX updates (no reload)
- [ ] Issue #11 - Responsive MVA badge width
- [ ] Issue #12 - Mobile condition badge stacking
- [ ] Issue #13 - MVA badge aria-labels
- [ ] Issue #14 - Safari toast testing

---

## Testing Checklist After Fixes

**Accessibility:**
- [ ] Run WAVE accessibility checker
- [ ] Test with NVDA screen reader (Windows)
- [ ] Test with VoiceOver (macOS/iOS)
- [ ] Check color contrast with DevTools
- [ ] Tab through all elements (keyboard only)

**Visual Regression:**
- [ ] Compare status badge colors before/after
- [ ] Verify all 10 status types render correctly
- [ ] Check mobile card view layout
- [ ] Check desktop table view layout
- [ ] Verify condition badges inline and stacked

**Functional:**
- [ ] Export button works and announces
- [ ] Print button works and announces
- [ ] Loading states show correctly
- [ ] Toast notifications dismiss
- [ ] Modal migrations work correctly

**Cross-Browser:**
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari (macOS)
- [ ] Safari (iOS)
- [ ] Chrome (Android)

---

**Tracking Information:**
- Total Issues: 14
- Critical: 1
- High: 4
- Medium: 5
- Low: 4
- Fixed: 0
- In Progress: 0
- Pending: 14

**Last Reviewed:** October 20, 2025
**Next Review:** After critical/high issues fixed
