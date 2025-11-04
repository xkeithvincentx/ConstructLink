/**
 * Unified Dropdown Synchronization System for ConstructLink Asset Forms
 * Handles bidirectional synchronization between Category, Equipment Type, and Subtype dropdowns
 * Works identically across legacy_create, create, and edit forms
 */

window.UnifiedDropdownSync = (function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        apiEndpoints: {
            equipmentTypes: '?route=api/equipment-types',
            subtypes: '?route=api/intelligent-naming&action=subtypes',
            equipmentTypeDetails: '?route=api/equipment-type-details'
        },
        selectors: {
            category: '#category_id',
            equipmentType: '#equipment_type_id', 
            subtype: '#subtype_id'
        },
        events: {
            change: 'change',
            select2Change: 'change.select2'
        }
    };
    
    // State management
    let state = {
        isInitializing: false,
        preventAutoSelection: false,
        loadingStates: {
            category: false,
            equipmentType: false,
            subtype: false
        }
    };
    
    // Cache for API responses
    const cache = new Map();
    
    /**
     * Initialize the unified dropdown synchronization system
     */
    function init() {
        console.log('Initializing Unified Dropdown Sync System');
        
        // Remove any existing event listeners to prevent conflicts
        removeExistingListeners();
        
        // Set up event listeners
        setupCategoryListener();
        setupEquipmentTypeListener();
        setupSubtypeListener();
        
        // Initialize with existing values if any
        initializeWithExistingValues();
        
        console.log('Unified Dropdown Sync System initialized');
    }
    
    /**
     * Remove existing event listeners to prevent conflicts
     * Note: We'll use a compatibility mode where we don't remove existing handlers
     * but add our own with a specific namespace to avoid conflicts
     */
    function removeExistingListeners() {
        // In compatibility mode, we don't remove existing handlers
        // Instead, we'll use namespaced events and careful coordination
        console.log('Running in compatibility mode - preserving existing handlers');
    }
    
    /**
     * Set up category change listener
     */
    function setupCategoryListener() {
        const categoryEl = document.querySelector(CONFIG.selectors.category);
        if (!categoryEl) return;
        
        categoryEl.addEventListener('change', handleCategoryChange);
        
        // Also handle Select2 events if applicable
        if (window.jQuery && window.jQuery().select2) {
            window.jQuery(CONFIG.selectors.category).off('change.select2.unifiedSync').on('change.select2.unifiedSync', handleCategoryChange);
        }
    }
    
    /**
     * Set up equipment type change listener with compatibility mode
     */
    function setupEquipmentTypeListener() {
        const equipmentTypeEl = document.querySelector(CONFIG.selectors.equipmentType);
        if (!equipmentTypeEl) {
            console.log('UnifiedDropdownSync: Equipment type element not found');
            return;
        }
        
        console.log('UnifiedDropdownSync: Setting up equipment type listener');
        
        // Use multiple strategies to capture equipment type changes
        
        // 1. Direct DOM event listener with higher priority
        equipmentTypeEl.addEventListener('change', handleEquipmentTypeChange, { capture: true });
        
        // 2. Also add a regular listener as backup
        equipmentTypeEl.addEventListener('change', handleEquipmentTypeChange);
        
        // 3. Handle Select2 events with our namespace
        if (window.jQuery && window.jQuery().select2) {
            // Wait for Select2 to be initialized
            setTimeout(() => {
                if (window.jQuery(CONFIG.selectors.equipmentType).hasClass('select2-hidden-accessible')) {
                    console.log('UnifiedDropdownSync: Setting up Select2 listener for equipment type');
                    window.jQuery(CONFIG.selectors.equipmentType).off('change.unifiedSync').on('change.unifiedSync', handleEquipmentTypeChange);
                }
            }, 1000);
        }
        
        // 4. Use MutationObserver as final backup to detect value changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                    const event = { target: equipmentTypeEl };
                    handleEquipmentTypeChange(event);
                }
            });
        });
        
        observer.observe(equipmentTypeEl, {
            attributes: true,
            attributeFilter: ['value']
        });
    }
    
    /**
     * Set up subtype change listener
     */
    function setupSubtypeListener() {
        const subtypeEl = document.querySelector(CONFIG.selectors.subtype);
        if (!subtypeEl) return;
        
        subtypeEl.addEventListener('change', handleSubtypeChange);
        
        // Also handle Select2 events if applicable
        if (window.jQuery && window.jQuery().select2) {
            window.jQuery(CONFIG.selectors.subtype).off('change.select2.unifiedSync').on('change.select2.unifiedSync', handleSubtypeChange);
        }
    }
    
    /**
     * Handle category selection change
     */
    function handleCategoryChange(event) {
        if (state.preventAutoSelection) return;
        
        const categoryId = event.target.value;
        console.log('Category changed to:', categoryId);
        
        if (categoryId) {
            // Load equipment types for selected category
            loadEquipmentTypes(categoryId).then(() => {
                // Clear subtypes since category changed
                clearSubtypes();
                triggerFormUpdates();
            });
        } else {
            // Category cleared - clear dependent dropdowns
            clearEquipmentTypes();
            clearSubtypes();
            triggerFormUpdates();
        }
    }
    
    /**
     * Handle equipment type selection change (compatibility mode)
     */
    function handleEquipmentTypeChange(event) {
        // Check if this change is coming from our own system to avoid loops
        if (state.preventAutoSelection || event.target.dataset.unifiedSyncSource) {
            console.log('UnifiedDropdownSync: Skipping equipment type change - prevent flag set');
            return;
        }
        
        const equipmentTypeId = event.target.value;
        console.log('UnifiedDropdownSync: Equipment type changed to:', equipmentTypeId);
        
        if (equipmentTypeId) {
            // Always try to load equipment type details for category auto-population
            console.log('UnifiedDropdownSync: Loading details for equipment type:', equipmentTypeId);
            loadEquipmentTypeDetails(equipmentTypeId).then((details) => {
                console.log('UnifiedDropdownSync: Equipment type details received:', details);
                
                if (details && details.category_id) {
                    // Auto-populate category if not already set or different
                    const currentCategoryId = document.querySelector(CONFIG.selectors.category).value;
                    console.log('UnifiedDropdownSync: Current category:', currentCategoryId, 'Target category:', details.category_id);
                    
                    if (!currentCategoryId || currentCategoryId != details.category_id) {
                        console.log('UnifiedDropdownSync: Auto-selecting category:', details.category_id, details.category_name);
                        setCategory(details.category_id);
                    }
                }
                
                // Load subtypes for this equipment type
                return loadSubtypes(equipmentTypeId);
            }).then(() => {
                triggerFormUpdates();
            }).catch(error => {
                console.error('UnifiedDropdownSync: Error handling equipment type change:', error);
            });
        } else {
            // Equipment type cleared - clear subtypes
            console.log('UnifiedDropdownSync: Equipment type cleared, clearing subtypes');
            clearSubtypes();
            triggerFormUpdates();
        }
    }
    
    /**
     * Handle subtype selection change
     */
    function handleSubtypeChange(event) {
        const subtypeId = event.target.value;
        console.log('Subtype changed to:', subtypeId);
        
        triggerFormUpdates();
    }
    
    /**
     * Load equipment types for a category
     */
    function loadEquipmentTypes(categoryId) {
        const cacheKey = `equipmentTypes_${categoryId}`;
        
        if (cache.has(cacheKey)) {
            populateEquipmentTypes(cache.get(cacheKey));
            return Promise.resolve();
        }
        
        setLoadingState('equipmentType', true);
        
        return fetch(`${CONFIG.apiEndpoints.equipmentTypes}&category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cache.set(cacheKey, data.data);
                    populateEquipmentTypes(data.data);
                } else {
                    console.error('Failed to load equipment types:', data.message);
                    populateEquipmentTypes([]);
                }
            })
            .catch(error => {
                console.error('Error loading equipment types:', error);
                populateEquipmentTypes([]);
            })
            .finally(() => {
                setLoadingState('equipmentType', false);
            });
    }
    
    /**
     * Load equipment type details including category info
     */
    function loadEquipmentTypeDetails(equipmentTypeId) {
        const cacheKey = `equipmentTypeDetails_${equipmentTypeId}`;
        
        if (cache.has(cacheKey)) {
            return Promise.resolve(cache.get(cacheKey));
        }
        
        return fetch(`${CONFIG.apiEndpoints.equipmentTypeDetails}&equipment_type_id=${equipmentTypeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cache.set(cacheKey, data.data);
                    return data.data;
                } else {
                    console.error('Failed to load equipment type details:', data.message);
                    return null;
                }
            })
            .catch(error => {
                console.error('Error loading equipment type details:', error);
                return null;
            });
    }
    
    /**
     * Load subtypes for an equipment type
     */
    function loadSubtypes(equipmentTypeId) {
        const cacheKey = `subtypes_${equipmentTypeId}`;
        
        if (cache.has(cacheKey)) {
            populateSubtypes(cache.get(cacheKey));
            return Promise.resolve();
        }
        
        setLoadingState('subtype', true);
        
        return fetch(`${CONFIG.apiEndpoints.subtypes}&equipment_type_id=${equipmentTypeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cache.set(cacheKey, data.data);
                    populateSubtypes(data.data);
                } else {
                    console.error('Failed to load subtypes:', data.message);
                    populateSubtypes([]);
                }
            })
            .catch(error => {
                console.error('Error loading subtypes:', error);
                populateSubtypes([]);
            })
            .finally(() => {
                setLoadingState('subtype', false);
            });
    }
    
    /**
     * Populate equipment types dropdown
     */
    function populateEquipmentTypes(equipmentTypes) {
        const equipmentTypeEl = document.querySelector(CONFIG.selectors.equipmentType);
        if (!equipmentTypeEl) return;
        
        // Store current value to preserve selection if possible
        const currentValue = equipmentTypeEl.value;
        
        // Clear existing options
        equipmentTypeEl.innerHTML = '<option value="">Select Equipment Type</option>';
        
        // Add new options
        equipmentTypes.forEach(type => {
            const option = document.createElement('option');
            option.value = type.id;
            option.textContent = type.name;
            option.dataset.categoryId = type.category_id;
            equipmentTypeEl.appendChild(option);
        });
        
        // Restore selection if it still exists
        if (currentValue && equipmentTypeEl.querySelector(`option[value="${currentValue}"]`)) {
            equipmentTypeEl.value = currentValue;
        }
        
        // Reinitialize Select2 if present
        if (window.jQuery && window.jQuery().select2) {
            window.jQuery(equipmentTypeEl).select2('destroy').select2({
                placeholder: 'Search equipment types...',
                allowClear: true
            });
        }
    }
    
    /**
     * Populate subtypes dropdown
     */
    function populateSubtypes(subtypes) {
        const subtypeEl = document.querySelector(CONFIG.selectors.subtype);
        if (!subtypeEl) return;
        
        // Store current value to preserve selection if possible
        const currentValue = subtypeEl.value;
        
        // Clear existing options
        subtypeEl.innerHTML = '<option value="">Select Subtype</option>';
        
        // Add new options
        subtypes.forEach(subtype => {
            const option = document.createElement('option');
            option.value = subtype.id;
            option.textContent = subtype.subtype_name;
            option.dataset.materialType = subtype.material_type;
            option.dataset.powerSource = subtype.power_source;
            subtypeEl.appendChild(option);
        });
        
        // Restore selection if it still exists
        if (currentValue && subtypeEl.querySelector(`option[value="${currentValue}"]`)) {
            subtypeEl.value = currentValue;
        }
        
        // Reinitialize Select2 if present
        if (window.jQuery && window.jQuery().select2) {
            window.jQuery(subtypeEl).select2('destroy').select2({
                placeholder: 'Search subtypes...',
                allowClear: true
            });
        }
    }
    
    /**
     * Set category value programmatically
     */
    function setCategory(categoryId) {
        const categoryEl = document.querySelector(CONFIG.selectors.category);
        if (!categoryEl) return;
        
        state.preventAutoSelection = true;
        
        categoryEl.value = categoryId;
        
        // Trigger Select2 change if present
        if (window.jQuery && window.jQuery().select2) {
            window.jQuery(categoryEl).trigger('change.select2');
        }
        
        setTimeout(() => {
            state.preventAutoSelection = false;
        }, 100);
    }
    
    /**
     * Clear equipment types dropdown
     */
    function clearEquipmentTypes() {
        const equipmentTypeEl = document.querySelector(CONFIG.selectors.equipmentType);
        if (!equipmentTypeEl) return;
        
        equipmentTypeEl.innerHTML = '<option value="">Select Equipment Type</option>';
        
        if (window.jQuery && window.jQuery().select2) {
            window.jQuery(equipmentTypeEl).val('').trigger('change');
        }
    }
    
    /**
     * Clear subtypes dropdown
     */
    function clearSubtypes() {
        const subtypeEl = document.querySelector(CONFIG.selectors.subtype);
        if (!subtypeEl) return;
        
        subtypeEl.innerHTML = '<option value="">Select Subtype</option>';
        
        if (window.jQuery && window.jQuery().select2) {
            window.jQuery(subtypeEl).val('').trigger('change');
        }
    }
    
    /**
     * Clear all dropdowns
     */
    function clearAllFields() {
        console.log('Clearing all dropdown fields');
        
        state.preventAutoSelection = true;
        
        // Clear all dropdowns
        const categoryEl = document.querySelector(CONFIG.selectors.category);
        const equipmentTypeEl = document.querySelector(CONFIG.selectors.equipmentType);
        const subtypeEl = document.querySelector(CONFIG.selectors.subtype);
        
        if (categoryEl) {
            categoryEl.value = '';
            if (window.jQuery && window.jQuery().select2) {
                window.jQuery(categoryEl).val('').trigger('change');
            }
        }
        
        clearEquipmentTypes();
        clearSubtypes();
        
        setTimeout(() => {
            state.preventAutoSelection = false;
            triggerFormUpdates();
        }, 100);
    }
    
    /**
     * Set loading state for visual feedback
     */
    function setLoadingState(field, isLoading) {
        state.loadingStates[field] = isLoading;
        
        // Add visual loading indicators if needed
        const element = document.querySelector(CONFIG.selectors[field]);
        if (element) {
            element.disabled = isLoading;
            if (isLoading) {
                element.classList.add('loading');
            } else {
                element.classList.remove('loading');
            }
        }
    }
    
    /**
     * Initialize with existing values (for edit forms)
     */
    function initializeWithExistingValues() {
        state.isInitializing = true;
        
        const categoryEl = document.querySelector(CONFIG.selectors.category);
        const equipmentTypeEl = document.querySelector(CONFIG.selectors.equipmentType);
        
        if (categoryEl && categoryEl.value) {
            loadEquipmentTypes(categoryEl.value);
        }
        
        if (equipmentTypeEl && equipmentTypeEl.value) {
            loadSubtypes(equipmentTypeEl.value);
        }
        
        setTimeout(() => {
            state.isInitializing = false;
        }, 1000);
    }
    
    /**
     * Trigger form-specific updates (name generation, unit updates, etc.)
     */
    function triggerFormUpdates() {
        // Trigger any external functions that depend on dropdown changes
        if (typeof generateNamePreview === 'function') {
            generateNamePreview();
        }
        if (typeof updateIntelligentUnit === 'function') {
            const equipmentTypeId = document.querySelector(CONFIG.selectors.equipmentType).value;
            const subtypeId = document.querySelector(CONFIG.selectors.subtype).value;
            updateIntelligentUnit(equipmentTypeId, subtypeId);
        }
        if (typeof updateQuantityHandling === 'function') {
            const equipmentTypeId = document.querySelector(CONFIG.selectors.equipmentType).value;
            updateQuantityHandling(equipmentTypeId);
        }
        
        // Dispatch custom events for other components to listen to
        document.dispatchEvent(new CustomEvent('dropdownsChanged', {
            detail: {
                category: document.querySelector(CONFIG.selectors.category)?.value,
                equipmentType: document.querySelector(CONFIG.selectors.equipmentType)?.value,
                subtype: document.querySelector(CONFIG.selectors.subtype)?.value
            }
        }));
    }
    
    // Public API
    return {
        init: init,
        clearAllFields: clearAllFields,
        loadEquipmentTypes: loadEquipmentTypes,
        loadSubtypes: loadSubtypes,
        getState: () => state,
        clearCache: () => cache.clear()
    };
})();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.UnifiedDropdownSync.init);
} else {
    window.UnifiedDropdownSync.init();
}