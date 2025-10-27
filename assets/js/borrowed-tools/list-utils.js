/**
 * Borrowed Tools List Utilities
 * God-tier production code with full descriptive naming
 * @version 3.1.0
 */

// Configuration
const BATCH_CONFIG = {
  mobile: {
    prefix: 'mobile',
    selector: '.d-md-none',
    itemsSelector: '.card',
    classes: ['border-primary', 'shadow-sm'],
    buttonText: 'Select Multiple',
    activeText: 'Cancel'
  },
  desktop: {
    prefix: 'desktop',
    selector: '.table-responsive.d-none.d-md-block',
    itemsSelector: 'tbody tr',
    classes: ['table-active'],
    buttonText: 'Batch Select',
    activeText: 'Cancel'
  }
};

// Security: CSV sanitization
const sanitizeCSV = text => !text ? '""' : `"${(/^[=+\-@]/.test(text) ? "'" + text : text).replace(/"/g, '""')}"`;

// Security: ID sanitization
const sanitizeId = id => id ? String(id).replace(/[^a-zA-Z0-9_-]/g, '') : null;

// Security: Safe ID extraction
const getItemId = element => {
  try {
    const link = element.querySelector('a[href*="view&id="]');
    return link ? sanitizeId(new URL(link.href, location.origin).searchParams.get('id')) : null;
  } catch { return null; }
};

// Accessibility: Screen reader announcements
let screenReaderElement = null;
const announceToScreenReader = message => {
  if (!screenReaderElement) {
    screenReaderElement = Object.assign(document.createElement('div'), {
      className: 'visually-hidden',
      role: 'status',
      ariaLive: 'polite'
    });
    document.body.appendChild(screenReaderElement);
  }
  screenReaderElement.textContent = message;
};

// DOM: Create element helper
const createElement = tag => document.createElement(tag);

// DOM: Create checkbox element
const createCheckbox = (itemId, prefix) => {
  const container = createElement('div');
  const input = createElement('input');
  const label = createElement('label');

  container.className = 'form-check';
  Object.assign(input, {
    type: 'checkbox',
    className: 'form-check-input batch-select-checkbox',
    id: `batch-${prefix}-${itemId}`,
    value: itemId
  });
  input.dataset.itemId = itemId;
  input.setAttribute('aria-label', `Select item ${itemId}`);

  label.className = 'form-check-label visually-hidden';
  label.htmlFor = input.id;
  label.textContent = `Select item ${itemId}`;

  container.append(input, label);
  return container;
};

// DOM: Create action button
const createActionButton = (id, className, icon, text) => {
  const button = createElement('button');
  const iconElement = createElement('i');

  Object.assign(button, { type: 'button', id, className, disabled: true });
  button.setAttribute('aria-label', text);
  iconElement.className = `bi ${icon} me-1`;
  iconElement.setAttribute('aria-hidden', 'true');
  button.append(iconElement, document.createTextNode(text));

  return button;
};

// State: Create batch state manager
const createBatchState = () => ({
  active: false,
  selectedItems: new Set(),
  abortController: new AbortController()
});

// UI: Create toggle button
const createToggleButton = config => {
  const button = createElement('button');
  const icon = createElement('i');

  Object.assign(button, {
    type: 'button',
    className: 'btn btn-outline-primary btn-sm',
    id: `${config.prefix}BatchToggle`
  });
  button.setAttribute('aria-pressed', 'false');
  icon.className = 'bi bi-check-square me-1';
  icon.setAttribute('aria-hidden', 'true');
  button.append(icon, document.createTextNode(config.buttonText));

  return button;
};

// UI: Update toggle button state
const updateToggleButtonState = (button, active, config) => {
  button.className = active ? 'btn btn-primary btn-sm' : 'btn btn-outline-primary btn-sm';
  button.setAttribute('aria-pressed', String(active));
  button.querySelector('i').className = active ? 'bi bi-x-circle me-1' : 'bi bi-check-square me-1';
  button.childNodes[1].textContent = active ? config.activeText : config.buttonText;
};

// UI: Create mobile action bar
const createMobileActionBar = prefix => {
  const bar = createElement('div');
  const header = createElement('div');
  const countWrapper = createElement('span');
  const countElement = createElement('span');
  const selectAllButton = createElement('button');
  const buttonContainer = createElement('div');

  bar.id = `${prefix}BatchActionBar`;
  bar.className = 'position-fixed bottom-0 start-0 w-100 bg-white border-top shadow-lg p-3 d-md-none';
  bar.style.zIndex = '1050';
  bar.setAttribute('role', 'toolbar');

  header.className = 'd-flex justify-content-between align-items-center mb-2';
  countWrapper.className = 'fw-bold';
  countElement.id = `${prefix}SelectedCount`;
  countElement.textContent = '0';
  countWrapper.append(countElement, document.createTextNode(' Selected'));

  Object.assign(selectAllButton, {
    type: 'button',
    className: 'btn btn-link btn-sm p-0',
    id: `${prefix}SelectAll`
  });
  selectAllButton.textContent = 'Select All';
  header.append(countWrapper, selectAllButton);

  buttonContainer.className = 'd-grid gap-2';
  buttonContainer.append(
    createActionButton(`${prefix}BatchReturn`, 'btn btn-success', 'bi-box-arrow-down', 'Return Selected'),
    createActionButton(`${prefix}BatchExtend`, 'btn btn-info', 'bi-calendar-plus', 'Extend Selected'),
    createActionButton(`${prefix}BatchExport`, 'btn btn-outline-primary', 'bi-file-earmark-excel', 'Export Selected')
  );

  bar.append(header, buttonContainer);
  return bar;
};

// UI: Create desktop action bar
const createDesktopActionBar = prefix => {
  const bar = createElement('div');
  const countWrapper = createElement('div');
  const strong = createElement('strong');
  const countElement = createElement('span');
  const buttonGroup = createElement('div');

  bar.id = `${prefix}BatchActionBar`;
  bar.className = 'alert alert-info d-none d-md-flex justify-content-between align-items-center mt-3 mb-0';
  bar.setAttribute('role', 'toolbar');

  countElement.id = `${prefix}SelectedCount`;
  countElement.textContent = '0';
  strong.append(countElement, document.createTextNode(' items selected'));
  countWrapper.appendChild(strong);

  buttonGroup.className = 'd-flex gap-2';
  buttonGroup.append(
    createActionButton(`${prefix}BatchReturn`, 'btn btn-success btn-sm', 'bi-box-arrow-down', 'Return Selected'),
    createActionButton(`${prefix}BatchExtend`, 'btn btn-info btn-sm', 'bi-calendar-plus', 'Extend Selected'),
    createActionButton(`${prefix}BatchExport`, 'btn btn-outline-primary btn-sm', 'bi-file-earmark-excel', 'Export Selected')
  );

  bar.append(countWrapper, buttonGroup);
  return bar;
};

// UI: Update action bar state
const updateActionBarState = (prefix, selectedItems) => {
  const countElement = document.getElementById(`${prefix}SelectedCount`);
  if (countElement) countElement.textContent = String(selectedItems.size);

  const hasSelection = selectedItems.size > 0;
  ['Return', 'Extend', 'Export'].forEach(action => {
    const button = document.getElementById(`${prefix}Batch${action}`);
    if (button) button.disabled = !hasSelection;
  });

  announceToScreenReader(`${selectedItems.size} items selected`);
};

// UI: Update select all button text
const updateSelectAllButton = (prefix, allSelected) => {
  const button = document.getElementById(`${prefix}SelectAll`);
  if (button) button.textContent = allSelected ? 'Deselect All' : 'Select All';
};

// Batch: Attach mobile checkbox
const attachMobileCheckbox = (card, itemId, config, state) => {
  const checkboxContainer = createCheckbox(itemId, config.prefix);
  checkboxContainer.className += ' position-absolute top-0 end-0 m-3 batch-checkbox-container';

  card.style.position = 'relative';
  card.insertBefore(checkboxContainer, card.firstChild);

  const checkbox = checkboxContainer.querySelector('input');
  checkbox.addEventListener('change', function() {
    handleCheckboxChange(card, itemId, this.checked, config, state);
  }, { signal: state.abortController.signal });

  card.style.cursor = 'pointer';
  card.addEventListener('click', event => {
    if (!event.target.closest('a,button,input')) {
      checkbox.checked = !checkbox.checked;
      checkbox.dispatchEvent(new Event('change'));
    }
  }, { signal: state.abortController.signal });
};

// Batch: Attach desktop checkbox
const attachDesktopCheckbox = (row, itemId, config, state) => {
  const checkboxContainer = createCheckbox(itemId, config.prefix);
  const cell = createElement('td');

  cell.className = 'batch-select-cell text-center align-middle';
  cell.appendChild(checkboxContainer);
  row.insertBefore(cell, row.firstChild);

  const checkbox = checkboxContainer.querySelector('input');
  checkbox.addEventListener('change', function(event) {
    event.stopPropagation();
    handleCheckboxChange(row, itemId, this.checked, config, state);
  }, { signal: state.abortController.signal });

  row.style.cursor = 'pointer';
  row.addEventListener('click', event => {
    if (!event.target.closest('a,button,input,select,textarea')) {
      checkbox.checked = !checkbox.checked;
      checkbox.dispatchEvent(new Event('change'));
    }
  }, { signal: state.abortController.signal });
};

// Batch: Handle checkbox state change
const handleCheckboxChange = (element, itemId, checked, config, state) => {
  checked ? state.selectedItems.add(itemId) : state.selectedItems.delete(itemId);
  checked ? element.classList.add(...config.classes) : element.classList.remove(...config.classes);

  updateActionBarState(config.prefix, state.selectedItems);

  const container = document.querySelector(config.selector);
  const checkboxes = container?.querySelectorAll('.batch-select-checkbox') || [];
  updateSelectAllButton(config.prefix, state.selectedItems.size === checkboxes.length);
};

// Mode: Add desktop header checkbox
const addDesktopHeaderCheckbox = config => {
  const table = document.getElementById('borrowedToolsTable');
  const headerRow = table?.querySelector('thead tr');

  if (headerRow && !headerRow.querySelector('.batch-select-header')) {
    const headerCell = createElement('th');
    const checkbox = createElement('input');

    headerCell.className = 'batch-select-header text-center';
    headerCell.style.width = '40px';
    Object.assign(checkbox, {
      type: 'checkbox',
      className: 'form-check-input',
      id: `${config.prefix}SelectAll`
    });
    checkbox.setAttribute('aria-label', 'Select all items');

    headerCell.appendChild(checkbox);
    headerRow.insertBefore(headerCell, headerRow.firstChild);
  }
};

// Mode: Attach action bar event listeners
const attachActionBarListeners = (config, state) => {
  const selectAllButton = document.getElementById(`${config.prefix}SelectAll`);

  if (selectAllButton) {
    selectAllButton.addEventListener('click', () => {
      const container = document.querySelector(config.selector);
      if (!container) return;

      const checkboxes = container.querySelectorAll('.batch-select-checkbox');
      const allSelected = state.selectedItems.size === checkboxes.length;

      checkboxes.forEach(checkbox => {
        checkbox.checked = !allSelected;
        checkbox.dispatchEvent(new Event('change'));
      });
    }, { signal: state.abortController.signal });
  }

  const actionMap = { Return: 'return', Extend: 'extend', Export: 'export' };

  Object.entries(actionMap).forEach(([action, type]) => {
    const button = document.getElementById(`${config.prefix}Batch${action}`);
    if (button) {
      button.addEventListener('click', () => {
        handleBatchAction(type, Array.from(state.selectedItems));
      }, { signal: state.abortController.signal });
    }
  });
};

// Mode: Enable batch selection mode
const enableBatchMode = (config, state, toggleButton) => {
  try {
    state.active = true;
    updateToggleButtonState(toggleButton, true, config);

    const container = document.querySelector(config.selector);
    if (!container) throw new Error('Container not found');

    container.querySelectorAll(config.itemsSelector).forEach(item => {
      const itemId = getItemId(item);
      if (itemId) {
        config.prefix === 'mobile'
          ? attachMobileCheckbox(item, itemId, config, state)
          : attachDesktopCheckbox(item, itemId, config, state);
      }
    });

    if (config.prefix === 'desktop') addDesktopHeaderCheckbox(config);

    const actionBar = config.prefix === 'mobile'
      ? createMobileActionBar(config.prefix)
      : createDesktopActionBar(config.prefix);

    config.prefix === 'mobile'
      ? document.body.appendChild(actionBar)
      : container.parentNode.insertBefore(actionBar, container.nextSibling);

    attachActionBarListeners(config, state);
    announceToScreenReader('Batch selection mode enabled');
  } catch (error) {
    state.active = false;
    updateToggleButtonState(toggleButton, false, config);
  }
};

// Mode: Disable batch selection mode
const disableBatchMode = (config, state, toggleButton) => {
  try {
    state.active = false;
    updateToggleButtonState(toggleButton, false, config);

    state.abortController.abort();
    state.abortController = new AbortController();

    document.querySelectorAll('.batch-checkbox-container,.batch-select-cell').forEach(el => el.remove());

    if (config.prefix === 'desktop') {
      const table = document.getElementById('borrowedToolsTable');
      table?.querySelector('.batch-select-header')?.remove();
    }

    const container = document.querySelector(config.selector);
    container?.querySelectorAll(config.itemsSelector).forEach(item => {
      item.classList.remove(...config.classes);
      item.style.position = '';
      item.style.cursor = '';
    });

    document.getElementById(`${config.prefix}BatchActionBar`)?.remove();
    state.selectedItems.clear();

    announceToScreenReader('Batch selection mode disabled');
  } catch (error) {
    // Silent fail - batch mode remains disabled
  }
};

// Actions: Handle batch action execution
const handleBatchAction = (action, itemIds) => {
  if (!itemIds.length) return showNotification('Please select at least one item', 'warning');

  const validIds = itemIds.filter(id => sanitizeId(id));
  if (!validIds.length) return showNotification('No valid items selected', 'error');

  if (action === 'return') showNotification(`Batch return for ${validIds.length} items - coming soon`, 'info');
  else if (action === 'extend') showNotification(`Batch extend for ${validIds.length} items - coming soon`, 'info');
  else if (action === 'export') handleBatchExport(validIds);
};

// Actions: Handle batch export
const handleBatchExport = itemIds => {
  try {
    const form = createElement('form');
    const input = createElement('input');

    Object.assign(form, { method: 'POST', action: '?route=borrowed-tools/export' });
    form.style.display = 'none';
    Object.assign(input, { type: 'hidden', name: 'item_ids', value: itemIds.join(',') });

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();

    setTimeout(() => form.remove(), 100);
  } catch (error) {
    showNotification('Export failed. Please try again.', 'error');
  }
};

// Actions: Show notification (placeholder for toast system)
const showNotification = (message, type) => {
  console[type === 'error' ? 'error' : 'info'](`[${type}] ${message}`);
};

// Export: Export table to CSV
export const exportToExcel = () => {
  try {
    const table = document.getElementById('borrowedToolsTable');
    if (!table) return;

    const csvData = Array.from(table.querySelectorAll('tr')).map(row =>
      Array.from(row.querySelectorAll('td,th'))
        .filter(cell => !cell.classList.contains('batch-select-cell') && !cell.classList.contains('actions-column'))
        .map(cell => sanitizeCSV(cell.innerText.trim()))
        .join(',')
    ).filter(row => row).join('\n');

    const blob = new Blob([csvData], { type: 'text/csv' });
    const link = Object.assign(createElement('a'), {
      href: URL.createObjectURL(blob),
      download: `borrowed-tools-${new Date().toISOString().split('T')[0]}.csv`
    });
    link.style.display = 'none';

    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(link.href);
  } catch (error) {
    showNotification('Export failed. Please try again.', 'error');
  }
};

// Export: Print table
export const printTable = () => window.print();

// Export: Refresh list
export const refreshBorrowedTools = () => location.reload();

// Export: Send overdue reminder
export const sendOverdueReminder = () => showNotification('Overdue reminder functionality coming soon', 'info');

// Export: Quick filter
export const quickFilter = filterValue => {
  try {
    const form = document.getElementById('filter-form');
    if (!form) return;

    const field = filterValue === 'overdue'
      ? form.querySelector('[name="priority"]')
      : form.querySelector('[name="status"]');

    if (field) field.value = filterValue === 'overdue' ? filterValue : filterValue;
    form.submit();
  } catch (error) {
    // Silent fail - filter not applied
  }
};

// Init: Initialize all list utilities
export const initListUtils = () => {
  try {
    [BATCH_CONFIG.mobile, BATCH_CONFIG.desktop].forEach(config => {
      const container = document.querySelector(config.selector);
      if (!container) return;

      const state = createBatchState();
      const toggleButton = createToggleButton(config);

      const header = container.closest('.card')?.querySelector('.card-header .d-flex.flex-wrap.gap-2');
      if (!header) return;

      config.prefix === 'mobile'
        ? header.appendChild(toggleButton)
        : header.insertBefore(toggleButton, header.firstChild);

      toggleButton.addEventListener('click', () => {
        state.active
          ? disableBatchMode(config, state, toggleButton)
          : enableBatchMode(config, state, toggleButton);
      });
    });

    const exportButton = document.getElementById('exportBtn');
    const printButton = document.getElementById('printBtn');
    const refreshButton = document.getElementById('refreshBtn');

    if (exportButton) exportButton.addEventListener('click', exportToExcel);
    if (printButton) printButton.addEventListener('click', printTable);
    if (refreshButton) refreshButton.addEventListener('click', refreshBorrowedTools);

    document.addEventListener('click', event => {
      const quickFilterButton = event.target.closest('.quick-filter-btn');
      if (quickFilterButton) {
        const filterValue = quickFilterButton.getAttribute('data-quick-filter');
        if (filterValue) quickFilter(filterValue);
      }
    });
  } catch (error) {
    // Silent fail - initialization error
  }
};

// Auto-initialize
document.readyState === 'loading'
  ? document.addEventListener('DOMContentLoaded', initListUtils)
  : initListUtils();

// Default export
export default {
  initListUtils,
  exportToExcel,
  printTable,
  sendOverdueReminder,
  refreshBorrowedTools,
  quickFilter
};
