# Transfers Table - Testing Guide

## 🧪 Quick Testing Checklist

### Visual Testing (5 minutes)

#### 1. **Desktop View (1920px)**
- [ ] Navigate to `?route=transfers`
- [ ] Verify all 11 columns are visible
- [ ] Actions column visible on right side
- [ ] No horizontal scrolling required
- [ ] From → To locations stacked vertically with icons

#### 2. **Laptop View (1366px)**
- [ ] Resize browser to 1366px width
- [ ] Verify 9-10 columns visible
- [ ] Actions column still sticky on right
- [ ] Expected Return and/or Return Status hidden
- [ ] Table fits within viewport

#### 3. **Tablet View (768px)**
- [ ] Resize browser to 768px width
- [ ] Verify only 7 critical columns visible
- [ ] Actions column remains accessible
- [ ] Reason and Transfer Date hidden
- [ ] Minimal horizontal scroll

#### 4. **Mobile View (<768px)**
- [ ] Resize browser to 375px (iPhone SE)
- [ ] Table completely hidden
- [ ] Mobile card view displayed
- [ ] All information visible in cards
- [ ] Touch-friendly buttons

### Sticky Column Testing (2 minutes)

#### Test Sticky Actions
1. Open transfers page on desktop
2. Scroll horizontally to the left
3. **Expected:** Actions column stays visible on right
4. Hover over a row
5. **Expected:** Actions column background changes with row
6. Click a button in Actions column
7. **Expected:** Button click works normally

### Responsive Breakpoint Testing (5 minutes)

#### Use Browser DevTools
1. Open Chrome DevTools (F12)
2. Toggle Device Toolbar (Ctrl+Shift+M)
3. Test each breakpoint:

**Ultra-Wide (1920px)**
```
✓ All 11 columns visible
✓ Actions sticky on right
✓ No scrolling needed
```

**Large Desktop (1400px)**
```
✓ 11 columns visible
✓ Actions sticky on right
✓ Fits viewport
```

**Desktop (1366px)**
```
✓ 10 columns (Expected Return hidden at 1399px)
✓ Actions sticky on right
✓ Fits viewport
```

**Laptop (1200px)**
```
✓ 9 columns (Return Status hidden)
✓ Actions sticky on right
✓ Fits viewport
```

**Tablet Landscape (992px)**
```
✓ 9 columns visible
✓ Actions sticky on right
✓ Minimal scroll
```

**Tablet Portrait (768px)**
```
✓ 7 critical columns only
✓ Actions sticky on right
✓ Some scroll expected
```

**Mobile (375px)**
```
✓ Table hidden
✓ Card view displayed
✓ No scrolling
```

### Accessibility Testing (3 minutes)

#### Keyboard Navigation
1. Open transfers page
2. Press Tab key repeatedly
3. **Expected:** Focus moves through all action buttons
4. **Expected:** Sticky column buttons receive focus
5. Press Enter on a focused button
6. **Expected:** Action triggers

#### Screen Reader (Optional)
1. Enable screen reader (NVDA/JAWS/VoiceOver)
2. Navigate to Actions column
3. **Expected:** "View transfer #123 details" announced
4. **Expected:** All buttons have descriptive labels

#### Contrast Check
1. Use browser DevTools > Accessibility
2. Check button contrast ratios
3. **Expected:** All buttons ≥4.5:1 contrast
4. **Expected:** No violations reported

### Functional Testing (5 minutes)

#### Column Content Verification
1. **ID Column**
   - [ ] Links to detail view
   - [ ] Hover underline appears
   - [ ] Click opens detail page

2. **Asset Column**
   - [ ] Asset name displayed
   - [ ] Asset reference code below name
   - [ ] Both text readable

3. **From → To Column**
   - [ ] Two rows (vertical stack)
   - [ ] Red up-arrow for "From"
   - [ ] Green down-arrow for "To"
   - [ ] Full location names in tooltips
   - [ ] Truncation with ellipsis if too long

4. **Reason Column**
   - [ ] Text truncated at 200px
   - [ ] Hover shows full text in tooltip
   - [ ] `cursor: help` appears on hover

5. **Actions Column**
   - [ ] View button always present
   - [ ] Workflow button displays based on status
   - [ ] More (⋮) dropdown for secondary actions
   - [ ] All buttons clickable

#### Workflow Action Logic
Test with different transfer statuses:

**Pending Verification:**
- [ ] Shows "Verify" button (warning)
- [ ] Only visible to Project Managers

**Pending Approval:**
- [ ] Shows "Approve" button (success)
- [ ] Only visible to Asset/Finance Directors

**Approved:**
- [ ] Shows "Dispatch" button (info)
- [ ] Only visible to authorized roles

**In Transit:**
- [ ] Shows "Complete" button (success)
- [ ] Only visible to receiving location

**Completed (Temporary):**
- [ ] Shows "Return Asset" button (secondary)
- [ ] Only if not yet returned

### Performance Testing (2 minutes)

#### Load Time
1. Open browser DevTools > Network
2. Navigate to `?route=transfers`
3. Check `transfers.css` load time
4. **Expected:** <100ms (CSS cached)

#### Scroll Performance
1. Open transfers page with 50+ records
2. Scroll horizontally back and forth
3. **Expected:** Smooth 60fps scrolling
4. **Expected:** No janky animations

#### Browser Compatibility
- [ ] **Chrome/Edge (Chromium)** - Full support
- [ ] **Firefox (Gecko)** - Full support
- [ ] **Safari (WebKit)** - Full support
- [ ] **Mobile Safari (iOS)** - Full support
- [ ] **Chrome Mobile (Android)** - Full support

### Edge Case Testing (3 minutes)

#### Long Content
1. Create transfer with very long location names
2. **Expected:** Ellipsis truncation
3. **Expected:** Full text in tooltip
4. **Expected:** No layout breaking

#### Empty State
1. Navigate to transfers with no records
2. **Expected:** Empty state message displayed
3. **Expected:** "Create Transfer" button visible

#### Many Actions
1. Find transfer with Cancel option available
2. **Expected:** More (⋮) dropdown visible
3. Click dropdown
4. **Expected:** "Cancel Transfer" option appears

### Mobile-Specific Testing (5 minutes)

#### Touch Targets
1. Open on actual mobile device (or DevTools)
2. Verify all buttons ≥44px touch target
3. **Expected:** Easy to tap without mis-clicks

#### Card View
1. Verify all information visible in cards:
   - [ ] ID and status
   - [ ] Asset name and reference
   - [ ] From and To locations
   - [ ] Type badge
   - [ ] Initiated by
   - [ ] Dates
   - [ ] Action buttons

2. Verify card actions:
   - [ ] View Details button
   - [ ] Workflow action button (if applicable)
   - [ ] All buttons full-width (stacked)

## 🐛 Common Issues & Fixes

### Issue: Actions column not sticky
**Cause:** CSS not loaded
**Fix:** Clear browser cache, hard refresh (Ctrl+F5)

### Issue: Columns still hidden on desktop
**Cause:** Browser width detection issue
**Fix:** Verify browser actual width (not just window size)

### Issue: Horizontal scroll still required
**Cause:** Content longer than expected
**Fix:** Verify actual data, check for extremely long strings

### Issue: From → To not stacked vertically
**Cause:** CSS class not applied
**Fix:** Verify `.location-transfer` class in HTML

## ✅ Sign-Off Checklist

Before marking as production-ready:

- [ ] All visual tests pass
- [ ] Sticky column works on all browsers
- [ ] Responsive breakpoints tested
- [ ] Accessibility requirements met
- [ ] Functional testing complete
- [ ] Performance acceptable
- [ ] Mobile testing complete
- [ ] Edge cases handled
- [ ] No console errors
- [ ] No visual bugs

## 📝 Test Results Template

```
Date: _____________
Tester: _____________
Browser: _____________
Device: _____________

Visual Tests: PASS / FAIL
Sticky Column: PASS / FAIL
Responsive: PASS / FAIL
Accessibility: PASS / FAIL
Functional: PASS / FAIL
Performance: PASS / FAIL
Mobile: PASS / FAIL

Issues Found:
1. _____________
2. _____________

Overall Status: PASS / FAIL
```

## 🎉 Expected Outcome

After all tests pass:
- ✅ Actions column always visible
- ✅ Table optimized for all screen sizes
- ✅ No horizontal scroll on most laptops
- ✅ Mobile-friendly card view
- ✅ Accessible to all users
- ✅ Fast and performant
- ✅ Professional appearance

**Estimated Total Testing Time:** 30 minutes

---

**Next Steps:**
1. Complete testing checklist
2. Record test results
3. Fix any issues found
4. Deploy to production
5. Monitor user feedback

**Related Documents:**
- `TRANSFERS_TABLE_OPTIMIZATION_REPORT.md` - Full technical details
- `TRANSFERS_TABLE_SOLUTION_SUMMARY.md` - Quick reference guide
