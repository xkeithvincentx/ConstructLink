/**
 * ConstructLink™ Asset Standardizer JavaScript
 * Real-time validation and standardization for asset creation
 * Compatible with shared hosting environments (vanilla JS)
 */

class AssetStandardizer {
    constructor() {
        this.cache = new Map();
        this.debounceTimers = {};
        this.validationResults = {};
        this.apiBaseUrl = './api/assets';
        this.init();
    }
    
    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupEventListeners());
        } else {
            this.setupEventListeners();
        }
    }
    
    setupEventListeners() {
        // Asset name validation
        const nameInput = document.getElementById('name');
        if (nameInput) {
            nameInput.addEventListener('input', (e) => this.validateAssetName(e.target.value));
            nameInput.addEventListener('blur', (e) => this.finalizeAssetName(e.target.value));
        }
        
        // Brand validation
        const brandInput = document.getElementById('brand');
        if (brandInput) {
            brandInput.addEventListener('input', (e) => this.validateBrand(e.target.value));
            brandInput.addEventListener('blur', (e) => this.finalizeBrand(e.target.value));
        }
        
        // Category change handler
        const categorySelect = document.getElementById('category_id');
        if (categorySelect) {
            categorySelect.addEventListener('change', (e) => this.onCategoryChange(e.target.value));
        }
        
        // Quantity change handler
        const quantityInput = document.getElementById('quantity');
        const availableQuantityInput = document.getElementById('available_quantity');
        if (quantityInput && availableQuantityInput) {
            quantityInput.addEventListener('change', (e) => {
                if (!availableQuantityInput.value) {
                    availableQuantityInput.value = e.target.value;
                }
            });
        }
        
        // Suggestion acceptance handlers
        this.setupSuggestionHandlers();
        
        // Learning system handlers
        this.setupLearningHandlers();
        
        // Form submission handler
        const form = document.getElementById('assetForm');
        if (form) {
            form.addEventListener('submit', (e) => this.onFormSubmit(e));
        }
        
        // Preview button
        const previewBtn = document.getElementById('preview-btn');
        if (previewBtn) {
            previewBtn.addEventListener('click', () => this.showPreview());
        }
    }
    
    /**
     * Validate asset name with debouncing
     */
    validateAssetName(value) {
        if (value.length < 2) {
            this.updateNameStatus('idle');
            return;
        }
        
        this.updateNameStatus('validating');
        
        // Clear previous timer
        clearTimeout(this.debounceTimers.name);
        
        // Set new timer
        this.debounceTimers.name = setTimeout(async () => {
            try {
                const categoryId = document.getElementById('category_id').value;
                const response = await this.apiCall('validate-name', {
                    name: encodeURIComponent(value),
                    category: categoryId || null
                });
                
                if (response.success) {
                    this.processNameValidation(response.data);
                } else {
                    this.updateNameStatus('error', response.message);
                }
                
            } catch (error) {
                console.error('Name validation error:', error);
                this.updateNameStatus('error', 'Validation failed');
            }
        }, 300);
    }
    
    /**
     * Process name validation results
     */
    processNameValidation(data) {
        this.validationResults.name = data;
        
        // Update hidden fields
        document.getElementById('standardized_name').value = data.standardized || '';
        document.getElementById('original_name').value = data.original || '';
        document.getElementById('asset_type_id').value = data.asset_type_id || '';
        
        // Update status icon
        if (data.confidence >= 0.9) {
            this.updateNameStatus('valid', 'Recognized asset name');
        } else if (data.confidence >= 0.7) {
            this.updateNameStatus('warning', 'Partial match - please verify');
        } else if (data.confidence >= 0.5) {
            this.updateNameStatus('info', 'Low confidence - check suggestions');
        } else {
            this.updateNameStatus('unknown', 'Unknown asset - will be learned');
        }
        
        // Show spelling correction if available
        if (data.has_correction) {
            this.showSpellingAlert(data.original, data.corrected);
        } else {
            this.hideSpellingAlert();
        }
        
        // Update suggestions in datalist
        this.updateSuggestions('asset-suggestions', data.suggestions);
        
        // Update disciplines if available
        if (data.disciplines && data.disciplines.length > 0) {
            this.updateDisciplines(data.disciplines);
        }
        
        // Update feedback text
        const feedback = document.getElementById('name-feedback');
        if (feedback) {
            if (data.confidence >= 0.7) {
                feedback.textContent = 'Asset recognized';
                feedback.className = 'form-text text-success';
            } else if (data.warnings && data.warnings.length > 0) {
                feedback.textContent = data.warnings[0];
                feedback.className = 'form-text text-warning';
            } else {
                feedback.textContent = 'Type to see suggestions';
                feedback.className = 'form-text';
            }
        }
    }
    
    /**
     * Update name validation status
     */
    updateNameStatus(status, message = '') {
        const icon = document.getElementById('name-icon');
        const statusSpan = document.getElementById('name-status');
        
        if (!icon || !statusSpan) return;
        
        // Reset classes
        icon.className = 'bi';
        statusSpan.className = 'input-group-text';
        
        switch (status) {
            case 'idle':
                icon.classList.add('bi-question-circle', 'text-muted');
                break;
            case 'validating':
                icon.classList.add('bi-hourglass-split', 'text-info');
                statusSpan.classList.add('border-info');
                break;
            case 'valid':
                icon.classList.add('bi-check-circle', 'text-success');
                statusSpan.classList.add('border-success');
                break;
            case 'warning':
                icon.classList.add('bi-exclamation-triangle', 'text-warning');
                statusSpan.classList.add('border-warning');
                break;
            case 'info':
                icon.classList.add('bi-info-circle', 'text-info');
                statusSpan.classList.add('border-info');
                break;
            case 'unknown':
                icon.classList.add('bi-question-circle', 'text-secondary');
                break;
            case 'error':
                icon.classList.add('bi-x-circle', 'text-danger');
                statusSpan.classList.add('border-danger');
                break;
        }
        
        icon.title = message || '';
    }
    
    /**
     * Show spelling correction alert
     */
    showSpellingAlert(original, corrected) {
        const alert = document.getElementById('spelling-alert');
        const message = document.getElementById('spelling-message');
        
        if (alert && message) {
            message.textContent = `Did you mean "${corrected}"?`;
            alert.classList.remove('d-none');
            
            // Store values for later use
            alert.dataset.original = original;
            alert.dataset.corrected = corrected;
        }
    }
    
    /**
     * Hide spelling correction alert
     */
    hideSpellingAlert() {
        const alert = document.getElementById('spelling-alert');
        if (alert) {
            alert.classList.add('d-none');
        }
    }
    
    /**
     * Validate brand name
     */
    validateBrand(value) {
        if (value.length < 1) {
            this.updateBrandStatus('idle');
            return;
        }
        
        // Clear previous timer
        clearTimeout(this.debounceTimers.brand);
        
        // Set new timer
        this.debounceTimers.brand = setTimeout(async () => {
            try {
                const response = await this.apiCall('validate-brand', {
                    brand: encodeURIComponent(value)
                });
                
                if (response.success) {
                    this.processBrandValidation(response.data);
                } else {
                    this.updateBrandStatus('error', response.message);
                }
                
            } catch (error) {
                console.error('Brand validation error:', error);
                this.updateBrandStatus('error', 'Validation failed');
            }
        }, 500); // Longer delay for brand validation
    }
    
    /**
     * Process brand validation results
     */
    processBrandValidation(data) {
        this.validationResults.brand = data;
        
        // Update hidden fields
        document.getElementById('standardized_brand').value = data.standardized || '';
        document.getElementById('brand_id').value = data.brand_id || '';
        
        // Update status
        if (data.valid) {
            this.updateBrandStatus('valid', `Recognized brand: ${data.standardized}`);
            
            if (data.has_correction) {
                this.showBrandAlert(`Standardized to: ${data.standardized}`, 'info');
            } else {
                this.hideBrandAlert();
            }
        } else {
            this.updateBrandStatus('unknown', 'Brand not recognized - will be added');
            
            if (data.suggestions && data.suggestions.length > 0) {
                this.updateSuggestions('brand-suggestions', data.suggestions);
                this.showBrandAlert('Similar brands found - check suggestions', 'info');
            } else {
                this.hideBrandAlert();
            }
        }
    }
    
    /**
     * Update brand validation status
     */
    updateBrandStatus(status, message = '') {
        const icon = document.getElementById('brand-icon');
        const statusSpan = document.getElementById('brand-status');
        
        if (!icon || !statusSpan) return;
        
        // Reset classes
        icon.className = 'bi';
        statusSpan.className = 'input-group-text';
        
        switch (status) {
            case 'idle':
                icon.classList.add('bi-question-circle', 'text-muted');
                break;
            case 'valid':
                icon.classList.add('bi-check-circle', 'text-success');
                statusSpan.classList.add('border-success');
                break;
            case 'unknown':
                icon.classList.add('bi-plus-circle', 'text-info');
                statusSpan.classList.add('border-info');
                break;
            case 'error':
                icon.classList.add('bi-x-circle', 'text-danger');
                statusSpan.classList.add('border-danger');
                break;
        }
        
        icon.title = message || '';
    }
    
    /**
     * Show brand alert
     */
    showBrandAlert(message, type = 'info') {
        const alert = document.getElementById('brand-alert');
        const messageSpan = document.getElementById('brand-message');
        
        if (alert && messageSpan) {
            messageSpan.textContent = message;
            alert.className = `alert alert-${type} mt-2 py-2`;
            alert.classList.remove('d-none');
        }
    }
    
    /**
     * Hide brand alert
     */
    hideBrandAlert() {
        const alert = document.getElementById('brand-alert');
        if (alert) {
            alert.classList.add('d-none');
        }
    }
    
    /**
     * Handle category change
     */
    async onCategoryChange(categoryId) {
        if (!categoryId) {
            this.hideDisciplineSection();
            this.hideSpecificationsSection();
            return;
        }
        
        try {
            // Get disciplines for this category
            const response = await this.apiCall('disciplines', {
                action: 'by_category',
                category_id: categoryId
            });
            
            if (response.success && response.data.length > 0) {
                this.showDisciplineSection();
                this.populateDisciplines(response.data);
            } else {
                this.hideDisciplineSection();
            }
            
            // Load specifications for this category
            await this.loadCategorySpecifications(categoryId);
            
            // Re-validate asset name with new category context
            const nameInput = document.getElementById('name');
            if (nameInput && nameInput.value.length >= 2) {
                this.validateAssetName(nameInput.value);
            }
            
        } catch (error) {
            console.error('Category change error:', error);
        }
    }
    
    /**
     * Show discipline section
     */
    showDisciplineSection() {
        const section = document.getElementById('discipline-section');
        if (section) {
            section.style.display = 'block';
        }
    }
    
    /**
     * Hide discipline section
     */
    hideDisciplineSection() {
        const section = document.getElementById('discipline-section');
        if (section) {
            section.style.display = 'none';
        }
    }
    
    /**
     * Populate disciplines
     */
    populateDisciplines(disciplines) {
        const primarySelect = document.getElementById('primary_discipline');
        const checkboxContainer = document.getElementById('discipline-checkboxes');
        
        if (!primarySelect || !checkboxContainer) return;
        
        // Clear existing options
        primarySelect.innerHTML = '<option value="">Select Primary Use</option>';
        checkboxContainer.innerHTML = '';
        
        // Populate primary discipline dropdown
        disciplines.forEach(discipline => {
            const option = document.createElement('option');
            option.value = discipline.id;
            option.textContent = discipline.name;
            if (discipline.description) {
                option.title = discipline.description;
            }
            primarySelect.appendChild(option);
        });
        
        // Create checkboxes for all disciplines
        disciplines.forEach(discipline => {
            const checkboxDiv = document.createElement('div');
            checkboxDiv.className = 'form-check';
            
            checkboxDiv.innerHTML = `
                <input class="form-check-input" type="checkbox" 
                       name="disciplines[]" value="${discipline.id}" 
                       id="disc-${discipline.id}">
                <label class="form-check-label" for="disc-${discipline.id}">
                    ${discipline.name}
                    ${discipline.usage_count ? `<small class="text-muted">(${discipline.usage_count} assets)</small>` : ''}
                </label>
            `;
            
            checkboxContainer.appendChild(checkboxDiv);
        });
    }
    
    /**
     * Load category specifications
     */
    async loadCategorySpecifications(categoryId) {
        try {
            const response = await this.apiCall('category-specs', {
                id: categoryId
            });
            
            if (response.success && response.data && response.data.length > 0) {
                this.showSpecificationsSection();
                this.populateSpecifications(response.data);
            } else {
                this.hideSpecificationsSection();
            }
            
        } catch (error) {
            console.log('No specifications available for this category');
            this.hideSpecificationsSection();
        }
    }
    
    /**
     * Show specifications section
     */
    showSpecificationsSection() {
        const section = document.getElementById('specifications-section');
        if (section) {
            section.style.display = 'block';
        }
    }
    
    /**
     * Hide specifications section
     */
    hideSpecificationsSection() {
        const section = document.getElementById('specifications-section');
        if (section) {
            section.style.display = 'none';
        }
    }
    
    /**
     * Populate dynamic specifications
     */
    populateSpecifications(specs) {
        const container = document.getElementById('dynamic-specs');
        if (!container) return;
        
        container.innerHTML = '';
        
        specs.forEach(spec => {
            const colDiv = document.createElement('div');
            colDiv.className = 'col-md-6 mb-3';
            
            let fieldHtml = '';
            switch (spec.type) {
                case 'select':
                    fieldHtml = `
                        <select name="specs[${spec.name}]" class="form-select" ${spec.required ? 'required' : ''}>
                            <option value="">Select ${spec.label}</option>
                            ${spec.options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                        </select>
                    `;
                    break;
                    
                case 'number':
                    fieldHtml = `
                        <input type="number" name="specs[${spec.name}]" class="form-control"
                               min="${spec.min || ''}" max="${spec.max || ''}" 
                               step="${spec.step || '1'}" ${spec.required ? 'required' : ''}>
                    `;
                    break;
                    
                case 'textarea':
                    fieldHtml = `
                        <textarea name="specs[${spec.name}]" class="form-control" rows="3"
                                  ${spec.required ? 'required' : ''}></textarea>
                    `;
                    break;
                    
                default:
                    fieldHtml = `
                        <input type="text" name="specs[${spec.name}]" class="form-control"
                               ${spec.required ? 'required' : ''}>
                    `;
            }
            
            colDiv.innerHTML = `
                <label class="form-label">
                    ${spec.label}${spec.required ? '<span class="text-danger">*</span>' : ''}
                </label>
                ${fieldHtml}
                ${spec.help ? `<div class="form-text">${spec.help}</div>` : ''}
            `;
            
            container.appendChild(colDiv);
        });
    }
    
    /**
     * Update suggestions in datalist
     */
    updateSuggestions(datalistId, suggestions) {
        const datalist = document.getElementById(datalistId);
        if (!datalist || !suggestions) return;
        
        datalist.innerHTML = '';
        
        suggestions.forEach(suggestion => {
            const option = document.createElement('option');
            option.value = suggestion.value || suggestion.name;
            if (suggestion.label && suggestion.label !== option.value) {
                option.textContent = suggestion.label;
            }
            datalist.appendChild(option);
        });
    }
    
    /**
     * Setup suggestion acceptance handlers
     */
    setupSuggestionHandlers() {
        // Accept spelling suggestion
        const acceptBtn = document.getElementById('accept-suggestion');
        if (acceptBtn) {
            acceptBtn.addEventListener('click', () => {
                const alert = document.getElementById('spelling-alert');
                const nameInput = document.getElementById('name');
                
                if (alert && nameInput && alert.dataset.corrected) {
                    nameInput.value = alert.dataset.corrected;
                    this.hideSpellingAlert();
                    this.validateAssetName(nameInput.value);
                    
                    // Learn this correction
                    this.learnCorrection(alert.dataset.original, alert.dataset.corrected);
                }
            });
        }
        
        // Dismiss suggestion
        const dismissBtn = document.getElementById('dismiss-suggestion');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => this.hideSpellingAlert());
        }
    }
    
    /**
     * Setup learning system handlers
     */
    setupLearningHandlers() {
        const teachBtn = document.getElementById('teach-system');
        if (teachBtn) {
            teachBtn.addEventListener('click', () => {
                const originalInput = document.getElementById('learn-original');
                const correctInput = document.getElementById('learn-correct');
                
                if (originalInput && correctInput) {
                    const original = originalInput.value.trim();
                    const correct = correctInput.value.trim();
                    
                    if (original && correct && original !== correct) {
                        this.learnCorrection(original, correct, 'tool_name');
                        originalInput.value = '';
                        correctInput.value = '';
                        this.showMessage('Thank you! The system will learn from this correction.', 'success');
                    } else {
                        this.showMessage('Please enter both original and correct spellings.', 'warning');
                    }
                }
            });
        }
    }
    
    /**
     * Learn correction from user
     */
    async learnCorrection(original, corrected, context = 'tool_name') {
        try {
            const response = await fetch(`${this.apiBaseUrl}/learn-correction.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    original: original,
                    corrected: corrected,
                    context: context
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('Correction learned successfully');
            } else {
                console.warn('Failed to learn correction:', data.message);
            }
            
        } catch (error) {
            console.error('Learn correction error:', error);
        }
    }
    
    /**
     * Handle form submission
     */
    onFormSubmit(event) {
        // Validate all fields before submission
        const hasErrors = this.validateFormBeforeSubmit();
        
        if (hasErrors) {
            event.preventDefault();
            this.showMessage('Please correct the validation errors before submitting.', 'danger');
            return false;
        }
        
        // Update submit button
        const submitBtn = document.getElementById('submit-btn');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Creating Asset...';
            submitBtn.disabled = true;
        }
        
        return true;
    }
    
    /**
     * Validate form before submission
     */
    validateFormBeforeSubmit() {
        let hasErrors = false;
        
        // Check required fields
        const requiredFields = ['name', 'category_id', 'project_id'];
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && !field.value.trim()) {
                this.highlightError(field);
                hasErrors = true;
            }
        });
        
        // Check validation results
        if (this.validationResults.name && this.validationResults.name.warnings.length > 0) {
            const nameInput = document.getElementById('name');
            if (nameInput && this.validationResults.name.confidence < 0.3) {
                this.highlightError(nameInput);
                hasErrors = true;
            }
        }
        
        return hasErrors;
    }
    
    /**
     * Highlight field error
     */
    highlightError(field) {
        field.classList.add('is-invalid');
        setTimeout(() => {
            field.classList.remove('is-invalid');
        }, 3000);
    }
    
    /**
     * Show preview modal
     */
    showPreview() {
        const formData = new FormData(document.getElementById('assetForm'));
        let previewHtml = '<div class="row">';
        
        // Basic information
        previewHtml += `
            <div class="col-md-6">
                <h6>Basic Information</h6>
                <p><strong>Asset Name:</strong> ${formData.get('name') || 'Not specified'}</p>
                <p><strong>Standardized Name:</strong> ${formData.get('standardized_name') || 'Same as above'}</p>
                <p><strong>Description:</strong> ${formData.get('description') || 'Not provided'}</p>
            </div>
        `;
        
        // Classification
        const categorySelect = document.getElementById('category_id');
        const categoryText = categorySelect.selectedOptions[0]?.textContent || 'Not selected';
        
        previewHtml += `
            <div class="col-md-6">
                <h6>Classification</h6>
                <p><strong>Category:</strong> ${categoryText}</p>
                <p><strong>Brand:</strong> ${formData.get('standardized_brand') || formData.get('brand') || 'Not specified'}</p>
                <p><strong>Model:</strong> ${formData.get('model') || 'Not specified'}</p>
            </div>
        `;
        
        previewHtml += '</div>';
        
        // Create modal
        this.showModal('Asset Preview', previewHtml);
    }
    
    /**
     * Show modal dialog
     */
    showModal(title, content) {
        const modalHtml = `
            <div class="modal fade" id="previewModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">${content}</div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal
        const existingModal = document.getElementById('previewModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('previewModal'));
        modal.show();
    }
    
    /**
     * Show message to user
     */
    showMessage(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const container = document.querySelector('.card-body') || document.body;
        container.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
    
    /**
     * Make API call
     */
    async apiCall(endpoint, params = {}) {
        const url = new URL(`${this.apiBaseUrl}/${endpoint}.php`, window.location.origin);
        
        // Add parameters to URL
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.append(key, params[key]);
            }
        });
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`API call failed: ${response.status}`);
        }
        
        return await response.json();
    }
    
    /**
     * Finalize asset name (called on blur)
     */
    finalizeAssetName(value) {
        if (this.validationResults.name && this.validationResults.name.confidence < 0.5 && value.length > 0) {
            this.showLearningSection();
            document.getElementById('learn-original').value = value;
        }
    }
    
    /**
     * Finalize brand (called on blur)
     */
    finalizeBrand(value) {
        if (this.validationResults.brand && !this.validationResults.brand.valid && value.length > 0) {
            // Brand not recognized - could be a new brand
            this.updateBrandStatus('unknown', 'New brand - will be added to system');
        }
    }
    
    /**
     * Show learning section
     */
    showLearningSection() {
        const section = document.getElementById('learning-section');
        if (section) {
            section.style.display = 'block';
        }
    }
}

// Initialize when page loads
if (typeof window !== 'undefined') {
    window.assetStandardizer = new AssetStandardizer();
}