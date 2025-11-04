# Asset Forms CSS Modules

This directory contains modular CSS files for asset creation and management forms.

## Module Files

### responsive.css
**Purpose:** Mobile-first responsive styling for all asset forms  
**Usage:** Include in all asset form views (create, edit, legacy)  
**Breakpoints:** 576px, 768px, 992px, 1200px (Bootstrap 5)

```html
<link rel="stylesheet" href="/assets/css/modules/assets/responsive.css">
```

**Handles:**
- Card body padding (desktop → mobile)
- Form label sizing
- Button toolbar layouts
- Touch targets (44px WCAG 2.1)
- Alert responsive sizing
- Form validation feedback

---

### discipline-checkboxes.css
**Purpose:** Multi-select discipline checkbox component  
**Usage:** Include on forms with discipline checkboxes  

```html
<link rel="stylesheet" href="/assets/css/modules/assets/discipline-checkboxes.css">
```

**Handles:**
- Scrollable container (max-height with overflow)
- Multi-column layout (1 → 2 → 3 columns)
- Responsive font sizing
- Touch-friendly spacing

**Columns:**
- Mobile: 1 column
- Large (992px+): 2 columns
- XL (1200px+): 3 columns

---

### select2-custom.css
**Purpose:** Select2 dropdown integration with Bootstrap 5  
**Usage:** Include on all forms using Select2 dropdowns  

```html
<link rel="stylesheet" href="/assets/css/modules/assets/select2-custom.css">
```

**Critical Fix:**
Forces search boxes to be visible in all dropdowns (was hidden by default).

**Handles:**
- Bootstrap 5 height/padding alignment
- Search box forced visibility
- Focus states
- Z-index fixes (modal compatibility)
- Mobile responsive adjustments
- iOS zoom prevention

---

### legacy-specific.css
**Purpose:** Styles unique to legacy_create.php  
**Usage:** Include ONLY on legacy forms  

```html
<link rel="stylesheet" href="/assets/css/modules/assets/legacy-specific.css">
```

**Contains:**
- Enhanced dropdown shadows
- Validation styling
- Text color adjustments

---

## Usage Examples

### Standard Asset Form (create.php, edit.php)
```html
<link rel="stylesheet" href="/assets/css/modules/assets/responsive.css">
<link rel="stylesheet" href="/assets/css/modules/assets/discipline-checkboxes.css">
<link rel="stylesheet" href="/assets/css/modules/assets/select2-custom.css">
```

### Legacy Asset Form (legacy_create.php)
```html
<link rel="stylesheet" href="/assets/css/modules/assets/responsive.css">
<link rel="stylesheet" href="/assets/css/modules/assets/discipline-checkboxes.css">
<link rel="stylesheet" href="/assets/css/modules/assets/select2-custom.css">
<link rel="stylesheet" href="/assets/css/modules/assets/legacy-specific.css">
```

### Asset List/Index (if using Select2)
```html
<link rel="stylesheet" href="/assets/css/modules/assets/responsive.css">
<link rel="stylesheet" href="/assets/css/modules/assets/select2-custom.css">
```

---

## File Organization

```
/assets/css/modules/assets/
├── responsive.css            (134 lines, 2.7 KB)
├── discipline-checkboxes.css (100 lines, 2.5 KB)
├── select2-custom.css        (152 lines, 4.6 KB)
├── legacy-specific.css       (50 lines, 1.6 KB)
└── README.md                 (this file)
```

---

## Maintenance Guidelines

### Adding New Styles
1. Determine which module the style belongs to (by purpose)
2. Add to appropriate section with descriptive comment
3. Follow mobile-first approach (base → media queries)
4. Update module version if significant change

### Modifying Existing Styles
1. Check if style is used across multiple forms
2. If shared, update in common module (responsive, select2-custom)
3. If form-specific, update in legacy-specific.css
4. Test on all breakpoints (576, 768, 992, 1200)

### Creating New Modules
If adding a new complex component:
1. Create new module file: `component-name.css`
2. Add proper file header with @module, @version, @since
3. Organize by specificity (base → responsive)
4. Update this README with usage instructions

---

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- iOS Safari 14+
- Android Chrome 90+

---

## Performance Notes

- All CSS modules are external (cacheable)
- Average file size: 2.8 KB per module
- Total bundle size: 11.4 KB (unminified)
- Recommended: Enable gzip compression on server
- Consider minification for production

---

## Changelog

### Version 3.0.0 (2025-11-03)
- Initial extraction from inline styles
- Created 4 modular CSS files
- Extracted 436 lines of CSS
- Reduced view file sizes by 63%
- Implemented mobile-first responsive design

---

**Maintained by:** coder-agent  
**Phase:** 3 Week 2 - Frontend Refactoring  
**Last Updated:** 2025-11-03
