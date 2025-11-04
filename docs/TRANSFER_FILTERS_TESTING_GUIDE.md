# Transfer Filters - Testing Guide

## Quick Testing Checklist

### Prerequisites
1. Alpine.js must be loaded globally on the page
2. User must be logged in
3. There should be transfer data to filter

---

## Test 1: Auto-Submit on Dropdown Change

### Test Case 1.1: Status Dropdown
**Steps**:
1. Navigate to `/transfers` page
2. Open browser DevTools Network tab
3. Change status dropdown from "All Statuses" to "Pending Verification"

**Expected Result**:
- ✅ Form submits automatically (no manual click needed)
- ✅ Page reloads with filtered results
- ✅ Status dropdown retains "Pending Verification" selection
- ✅ Network tab shows GET request to `?route=transfers&status=Pending+Verification`

### Test Case 1.2: Type Dropdown
**Steps**:
1. Navigate to `/transfers` page
2. Change transfer type dropdown to "Temporary"

**Expected Result**:
- ✅ Form auto-submits
- ✅ URL includes `&transfer_type=temporary`
- ✅ Selection persists after reload

### Test Case 1.3: Project Dropdowns
**Steps**:
1. Select a project in "From Project" dropdown
2. Select a different project in "To Project" dropdown

**Expected Result**:
- ✅ Each dropdown change triggers auto-submit
- ✅ Both selections persist
- ✅ URL includes both parameters

---

## Test 2: Search Debouncing

### Test Case 2.1: Debounce Delay
**Steps**:
1. Navigate to `/transfers` page
2. Open DevTools Network tab
3. Type "asset" quickly in search box (5 characters in < 1 second)
4. Wait 500ms without typing

**Expected Result**:
- ✅ NO requests sent while typing
- ✅ ONE request sent 500ms after typing stops
- ✅ Network tab shows single GET request with `&search=asset`

### Test Case 2.2: Continued Typing
**Steps**:
1. Type "as" in search box
2. Wait 400ms
3. Type "set" (total: "asset")

**Expected Result**:
- ✅ NO request after first pause (didn't reach 500ms)
- ✅ Request sent 500ms after final character
- ✅ Only searches for complete term "asset"

### Test Case 2.3: Search Persistence
**Steps**:
1. Type "test" in search box
2. Wait for auto-submit
3. Change status dropdown

**Expected Result**:
- ✅ Search term "test" remains in search box
- ✅ Both filters applied (search + status)

---

## Test 3: Date Range Validation

### Test Case 3.1: Invalid Range (From > To)
**Steps**:
1. Set "Date From" to "2024-01-15"
2. Set "Date To" to "2024-01-10" (earlier date)

**Expected Result**:
- ✅ "Date To" field shows red border (is-invalid class)
- ✅ Error message appears below "Date To" field
- ✅ Error message: "End date cannot be earlier than start date"
- ✅ "Date To" field is cleared automatically
- ✅ Form does NOT auto-submit

**Screenshot**:
```
Date To: [2024-01-10] ← Red border
         ⚠ End date cannot be earlier than start date
```

### Test Case 3.2: Invalid Range (To < From)
**Steps**:
1. Set "Date To" to "2024-01-10"
2. Set "Date From" to "2024-01-15" (later date)

**Expected Result**:
- ✅ "Date From" field shows red border
- ✅ Error message: "Start date cannot be later than end date"
- ✅ "Date From" field is cleared
- ✅ Form does NOT auto-submit

### Test Case 3.3: Valid Range
**Steps**:
1. Set "Date From" to "2024-01-01"
2. Set "Date To" to "2024-01-31"

**Expected Result**:
- ✅ NO error messages
- ✅ NO red borders
- ✅ Form auto-submits
- ✅ Both dates persist in URL

### Test Case 3.4: Error Clearing
**Steps**:
1. Create invalid range (show error)
2. Change invalid field to valid date

**Expected Result**:
- ✅ Error message disappears
- ✅ Red border removed
- ✅ Form auto-submits with valid range

---

## Test 4: Quick Filter Buttons

### Test Case 4.1: My Verifications Button (Project Manager)
**Login As**: Project Manager

**Steps**:
1. Navigate to `/transfers` page
2. Verify "My Verifications" button is visible
3. Click "My Verifications" button

**Expected Result**:
- ✅ Status dropdown changes to "Pending Verification"
- ✅ Form auto-submits
- ✅ Page shows only Pending Verification transfers
- ✅ URL includes `&status=Pending+Verification`

### Test Case 4.2: My Approvals Button (Director)
**Login As**: Asset Director or Finance Director

**Steps**:
1. Navigate to `/transfers` page
2. Verify "My Approvals" button is visible
3. Click "My Approvals" button

**Expected Result**:
- ✅ Status dropdown changes to "Pending Approval"
- ✅ Form auto-submits
- ✅ Shows only Pending Approval transfers

### Test Case 4.3: In Transit Button (All Users)
**Login As**: Any role

**Steps**:
1. Navigate to `/transfers` page
2. Verify "In Transit" button is visible
3. Click "In Transit" button

**Expected Result**:
- ✅ Status dropdown changes to "In Transit"
- ✅ Form auto-submits
- ✅ Shows only In Transit transfers

### Test Case 4.4: Role-Based Visibility
**Test Matrix**:

| Role | My Verifications | My Approvals | In Transit |
|------|-----------------|--------------|------------|
| System Admin | ✅ Visible | ✅ Visible | ✅ Visible |
| Project Manager | ✅ Visible | ❌ Hidden | ✅ Visible |
| Asset Director | ❌ Hidden | ✅ Visible | ✅ Visible |
| Finance Director | ❌ Hidden | ✅ Visible | ✅ Visible |
| Warehouseman | ❌ Hidden | ❌ Hidden | ✅ Visible |
| Generic User | ❌ Hidden | ❌ Hidden | ✅ Visible |

---

## Test 5: Mobile Responsive Design

### Test Case 5.1: Mobile Filter Button
**Steps**:
1. Resize browser to mobile width (< 768px)
2. Navigate to `/transfers` page

**Expected Result**:
- ✅ Desktop filter card is hidden
- ✅ Sticky filter button appears at top
- ✅ Button shows "Filters" text
- ✅ Filter icon (funnel) is visible

### Test Case 5.2: Active Filter Badge
**Steps**:
1. Apply 2 filters (e.g., status + search)
2. Resize to mobile width

**Expected Result**:
- ✅ Badge showing "2" appears on filter button
- ✅ Badge has warning color (yellow/orange)
- ✅ Badge has aria-label "2 active filters"

### Test Case 5.3: Mobile Offcanvas
**Steps**:
1. On mobile width, click filter button
2. Verify offcanvas panel opens from bottom

**Expected Result**:
- ✅ Offcanvas slides up from bottom
- ✅ Shows "Filter Transfers" title
- ✅ Close button (X) is visible
- ✅ All filter fields are present
- ✅ Quick filter buttons appear below main filters

### Test Case 5.4: Mobile Auto-Submit
**Steps**:
1. Open mobile offcanvas
2. Change status dropdown
3. Wait for form to submit

**Expected Result**:
- ✅ Form auto-submits (same as desktop)
- ✅ Offcanvas closes automatically
- ✅ Page reloads with filtered results

### Test Case 5.5: Mobile Date Validation
**Steps**:
1. Open mobile offcanvas
2. Set invalid date range

**Expected Result**:
- ✅ Same validation as desktop
- ✅ Error message appears below input
- ✅ Red border on invalid field
- ✅ Field auto-clears

---

## Test 6: Accessibility Testing

### Test Case 6.1: Keyboard Navigation
**Steps**:
1. Navigate to `/transfers` page
2. Press Tab repeatedly

**Expected Result**:
- ✅ Focus moves through all filter inputs in logical order
- ✅ Focus indicator is visible
- ✅ Tab order: Status → Type → From Project → To Project → Date From → Date To → Search → Filter Button → Clear Button → Quick Filters

### Test Case 6.2: Enter Key Submission
**Steps**:
1. Click into search field
2. Type "test"
3. Press Enter

**Expected Result**:
- ✅ Form submits (same as auto-submit)
- ✅ Page reloads with search results

### Test Case 6.3: Screen Reader Labels
**Tools**: Screen reader (NVDA, JAWS, VoiceOver)

**Steps**:
1. Use screen reader to navigate form
2. Listen to each field announcement

**Expected Result**:
- ✅ Each input announces label + type
- ✅ Quick buttons announce purpose
- ✅ Error messages are announced immediately
- ✅ Help text is associated with inputs

### Test Case 6.4: Error Announcements
**Steps**:
1. With screen reader active
2. Create invalid date range
3. Listen for announcement

**Expected Result**:
- ✅ Error message announced with "alert" role
- ✅ Polite announcement (not interrupting)
- ✅ Clear error description

---

## Test 7: Role-Based Status Options

### Test Case 7.1: System Admin View
**Login As**: System Admin

**Steps**:
1. Open status dropdown

**Expected Result**:
- ✅ All Statuses option
- ✅ Pending Verification option
- ✅ Pending Approval option
- ✅ Approved option
- ✅ In Transit option
- ✅ Completed option
- ✅ Canceled option

### Test Case 7.2: Project Manager View
**Login As**: Project Manager

**Expected Visible Options**:
- ✅ All Statuses
- ✅ Pending Verification
- ✅ Approved
- ✅ In Transit
- ✅ Completed
- ✅ Canceled

**Expected Hidden Options**:
- ❌ Pending Approval (Directors only)

### Test Case 7.3: Asset Director View
**Login As**: Asset Director

**Expected Visible Options**:
- ✅ All Statuses
- ✅ Pending Approval
- ✅ In Transit
- ✅ Completed
- ✅ Canceled

**Expected Hidden Options**:
- ❌ Pending Verification (Project Managers only)
- ❌ Approved (Warehouseman only)

### Test Case 7.4: Warehouseman View
**Login As**: Warehouseman

**Expected Visible Options**:
- ✅ All Statuses
- ✅ Approved
- ✅ In Transit
- ✅ Completed
- ✅ Canceled

---

## Test 8: Multi-Filter Combinations

### Test Case 8.1: Status + Search
**Steps**:
1. Set status to "Pending Verification"
2. Search for "asset"

**Expected Result**:
- ✅ Both filters applied
- ✅ URL: `?route=transfers&status=Pending+Verification&search=asset`
- ✅ Results match both criteria

### Test Case 8.2: All Filters Combined
**Steps**:
1. Set status to "In Transit"
2. Set type to "temporary"
3. Set from_project to project ID 5
4. Set to_project to project ID 3
5. Set date range
6. Enter search term

**Expected Result**:
- ✅ All 6 filters applied
- ✅ URL contains all parameters
- ✅ All selections persist after reload
- ✅ Results match all criteria

### Test Case 8.3: Clear All Filters
**Steps**:
1. Apply multiple filters
2. Click "Clear" button

**Expected Result**:
- ✅ Redirects to `?route=transfers` (no parameters)
- ✅ All dropdowns reset to "All" options
- ✅ Date fields cleared
- ✅ Search box cleared
- ✅ Shows all transfers

---

## Test 9: Browser Compatibility

### Test Matrix

| Browser | Version | Auto-Submit | Debounce | Date Validation | Quick Filters | Mobile |
|---------|---------|-------------|----------|-----------------|---------------|--------|
| Chrome | 120+ | ☐ | ☐ | ☐ | ☐ | ☐ |
| Firefox | 115+ | ☐ | ☐ | ☐ | ☐ | ☐ |
| Safari | 16+ | ☐ | ☐ | ☐ | ☐ | ☐ |
| Edge | 120+ | ☐ | ☐ | ☐ | ☐ | ☐ |
| Mobile Safari | iOS 16+ | ☐ | ☐ | ☐ | ☐ | ☐ |
| Mobile Chrome | Android 12+ | ☐ | ☐ | ☐ | ☐ | ☐ |

**Instructions**:
- Check ✅ if test passes
- Mark ❌ if test fails
- Add notes for any issues

---

## Test 10: Performance Testing

### Test Case 10.1: Debounce Performance
**Steps**:
1. Open DevTools Network tab
2. Type 20 characters rapidly in search box
3. Count network requests

**Expected Result**:
- ✅ Maximum 1 request sent
- ✅ Request sent 500ms after typing stops
- ✅ No requests during typing

### Test Case 10.2: Multiple Filter Changes
**Steps**:
1. Change status dropdown
2. Immediately change type dropdown
3. Immediately change project dropdown

**Expected Result**:
- ✅ Form submits for each change
- ✅ Last submission "wins"
- ✅ No race conditions or errors

### Test Case 10.3: Large Dataset
**Setup**: Database with 1000+ transfers

**Steps**:
1. Apply filters
2. Measure page load time

**Expected Result**:
- ✅ Page loads in < 2 seconds
- ✅ No client-side lag
- ✅ Filters remain responsive

---

## Common Issues & Troubleshooting

### Issue 1: Auto-Submit Not Working
**Symptoms**: Dropdowns don't auto-submit

**Check**:
- [ ] Alpine.js is loaded globally
- [ ] transfers.js is loaded after Alpine.js
- [ ] No JavaScript console errors
- [ ] x-data="transferFilters()" is on wrapper div

**Fix**: Check browser console for Alpine.js errors

### Issue 2: Search Debounce Not Working
**Symptoms**: Search submits immediately on every keystroke

**Check**:
- [ ] x-model="searchQuery" is on search input
- [ ] @input.debounce.500ms="autoSubmit" is on search input
- [ ] Alpine.js version is 3.x (debounce modifier)

**Fix**: Verify Alpine.js version and directive syntax

### Issue 3: Date Validation Not Showing
**Symptoms**: No error messages on invalid date range

**Check**:
- [ ] x-ref="dateFrom" and x-ref="dateTo" are set
- [ ] @change="validateDateRange($event.target)" is on both inputs
- [ ] Bootstrap CSS is loaded (for .is-invalid class)

**Fix**: Check Alpine.js component initialization

### Issue 4: Quick Filters Not Working
**Symptoms**: Clicking quick filter buttons does nothing

**Check**:
- [ ] @click="quickFilter('Status')" syntax is correct
- [ ] Status value exactly matches dropdown options
- [ ] Alpine.js component is initialized

**Fix**: Check browser console for errors

### Issue 5: Mobile Offcanvas Not Opening
**Symptoms**: Filter button doesn't open offcanvas

**Check**:
- [ ] Bootstrap JavaScript is loaded
- [ ] data-bs-toggle="offcanvas" is on button
- [ ] data-bs-target="#filterOffcanvas" matches offcanvas ID

**Fix**: Verify Bootstrap version (5.x required)

---

## Regression Testing

After any code changes, verify:

- [ ] All dropdowns still auto-submit
- [ ] Search still debounces
- [ ] Date validation still works
- [ ] Quick filters still work
- [ ] Mobile offcanvas still opens
- [ ] Accessibility attributes intact
- [ ] Role-based visibility unchanged
- [ ] No JavaScript console errors
- [ ] No PHP errors

---

## Sign-Off Checklist

### Developer Testing
- [ ] All Test Cases 1-10 executed
- [ ] All issues documented
- [ ] All fixes implemented
- [ ] Code reviewed against standards
- [ ] Documentation updated

### QA Testing
- [ ] All Test Cases 1-10 executed
- [ ] Browser compatibility verified
- [ ] Mobile devices tested
- [ ] Accessibility verified
- [ ] Performance acceptable

### Deployment Approval
- [ ] All tests passed
- [ ] No critical issues
- [ ] Documentation complete
- [ ] Ready for production

---

**Tester Name**: _________________
**Date**: _________________
**Signature**: _________________
