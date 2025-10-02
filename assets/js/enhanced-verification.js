/**
 * Enhanced Asset Verification System
 * Provides comprehensive data quality assessment and verification workflow
 */

class EnhancedAssetVerification {
    constructor() {
        this.currentAssetId = null;
        this.currentAssetData = null;
        this.validationResults = null;
        this.uploadedPhotos = [];
        this.corrections = [];
        
        this.initializeEventListeners();
    }
    
    initializeEventListeners() {
        // Modal cleanup only
        document.getElementById('enhancedVerificationModal')?.addEventListener('hidden.bs.modal', this.cleanup.bind(this));
    }
    
    /**
     * Open verification modal for specific asset
     */
    async openVerificationModal(assetId) {
        this.currentAssetId = assetId;
        
        try {
            // Show modal and loading state with error handling
            const modalElement = document.getElementById('enhancedVerificationModal');
            if (!modalElement) {
                throw new Error('Enhanced verification modal element not found');
            }
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
            const loadingElement = document.getElementById('verification-loading');
            const contentElement = document.getElementById('verification-content');
            
            if (loadingElement) loadingElement.style.display = 'block';
            if (contentElement) contentElement.style.display = 'none';
            
            // Load asset data and validation results
            await this.loadAssetData(assetId);
            
            // Perform quality assessment
            try {
                await this.performQualityAssessment();
            } catch (assessmentError) {
                console.error('Quality assessment failed, using fallback:', assessmentError);
                // Set fallback validation results if API fails
                this.validationResults = {
                    overall_score: 0,
                    completeness_score: 0,
                    accuracy_score: 0,
                    validation_results: [],
                    errors: [],
                    warnings: [],
                    info: ['Quality assessment unavailable']
                };
            }
            
            // Update UI with data - wrapped in try-catch for individual components
            try {
                this.updateAssetOverview();
            } catch (err) {
                console.error('Error updating asset overview:', err);
            }
            
            try {
                this.updateQualityScorecard();
            } catch (err) {
                console.error('Error updating quality scorecard:', err);
            }
            
            try {
                this.updateFieldReview();
            } catch (err) {
                console.error('Error updating field review:', err);
            }
            
            try {
                this.updateSuggestedCorrections();
            } catch (err) {
                console.error('Error updating suggested corrections:', err);
            }
            
            // Show content
            if (loadingElement) loadingElement.style.display = 'none';
            if (contentElement) contentElement.style.display = 'block';
            
        } catch (error) {
            console.error('Error loading asset verification data:', error);
            this.showAlert('error', 'Failed to load asset data: ' + error.message);
            
            // Try to hide loading even if there was an error
            const loadingElement = document.getElementById('verification-loading');
            if (loadingElement) loadingElement.style.display = 'none';
        }
    }
    
    /**
     * Load asset data from server
     */
    async loadAssetData(assetId) {
        const response = await fetch(`?route=api/assets/verification-data&id=${assetId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRFTokenValue
            }
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('API Response Error:', errorText);
            throw new Error(`Failed to load asset data: ${response.status} - ${errorText.substring(0, 200)}`);
        }
        
        const responseText = await response.text();
        console.log('API Response:', responseText.substring(0, 500)); // Debug log
        
        try {
            this.currentAssetData = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response Text:', responseText.substring(0, 1000));
            throw new Error('Invalid JSON response from server. Check console for details.');
        }
    }
    
    /**
     * Perform quality assessment on asset data
     */
    async performQualityAssessment() {
        console.log('Performing quality assessment with data:', this.currentAssetData);
        
        const response = await fetch('?route=api/assets/validate-quality', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRFTokenValue
            },
            body: JSON.stringify({
                asset_data: this.currentAssetData,
                user_role: typeof userRole !== 'undefined' ? userRole : 'Site Inventory Clerk' // Fallback if not defined
            })
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Quality assessment error:', errorText);
            throw new Error(`Quality assessment failed: ${response.status}`);
        }
        
        this.validationResults = await response.json();
        console.log('Validation results:', this.validationResults);
    }
    
    /**
     * Update asset overview section - with null checks
     */
    updateAssetOverview() {
        const data = this.currentAssetData;
        
        // Debug log to check what data we received
        console.log('Asset Data Received:', {
            ref: data.ref,
            name: data.name,
            subtype_name: data.subtype_name,
            project_name: data.project_name,
            brand_name: data.brand_name,
            discipline_names: data.discipline_names
        });
        
        // Update all elements that exist in the new tabbed layout
        const elements = [
            { id: 'asset-ref', value: data.ref || '-' },
            { id: 'asset-name', value: data.name || '-' },
            { id: 'asset-category', value: data.category_name || '-' },
            { id: 'asset-equipment-type', value: data.equipment_type_name || '-' },
            { id: 'asset-subtype', value: data.subtype_name || '-' },
            { id: 'asset-project', value: data.project_name || '-' },
            { id: 'asset-quantity', value: data.quantity || '-' },
            { id: 'asset-brand', value: data.brand_name || '-' },
            { id: 'asset-discipline', value: data.discipline_names || '-' },
            { id: 'asset-sub-discipline', value: data.sub_discipline_names || '-' }
        ];
        
        elements.forEach(item => {
            const element = document.getElementById(item.id);
            if (element) {
                element.textContent = item.value;
            } else {
                console.warn(`Element with ID '${item.id}' not found`);
            }
        });
        
        // Update workflow status with null check
        const statusElement = document.getElementById('workflow-status');
        if (statusElement) {
            statusElement.textContent = data.workflow_status || 'pending_verification';
            statusElement.className = `badge ${this.getStatusBadgeClass(data.workflow_status)}`;
        } else {
            console.warn('Element with ID "workflow-status" not found');
        }
    }
    
    /**
     * Update quality scorecard section - simplified
     */
    updateQualityScorecard() {
        const results = this.validationResults;
        
        // Update scores for the new tabbed layout
        const overallScore = Math.round(results.overall_score || 0);
        const overallElement = document.getElementById('overall-score');
        if (overallElement) {
            overallElement.textContent = overallScore;
            // Update circular progress
            const circularProgress = overallElement.closest('.circular-progress');
            if (circularProgress) {
                circularProgress.style.setProperty('--percentage', overallScore);
            }
        }
        
        const completenessScore = Math.round(results.completeness_score || 0);
        const completenessElement = document.getElementById('completeness-score');
        if (completenessElement) completenessElement.textContent = completenessScore;
        
        const accuracyScore = Math.round(results.accuracy_score || 0);
        const accuracyElement = document.getElementById('accuracy-score');
        if (accuracyElement) accuracyElement.textContent = accuracyScore;
        
        // Rules passed - with null checks
        const passedRules = results.validation_results?.filter(r => r.passed).length || 0;
        const totalRules = results.validation_results?.length || 0;
        const passedElement = document.getElementById('validation-rules-passed');
        const totalElement = document.getElementById('validation-rules-total');
        if (passedElement) {
            passedElement.textContent = passedRules;
        } else {
            console.warn('Element validation-rules-passed not found');
        }
        if (totalElement) {
            totalElement.textContent = totalRules;
        } else {
            console.warn('Element validation-rules-total not found');
        }
        
        // Error/Warning/Info counts - with null checks
        const errorCount = results.errors?.length || 0;
        const warningCount = results.warnings?.length || 0;
        const infoCount = results.info?.length || 0;
        
        const errorElement = document.getElementById('error-count');
        const warningElement = document.getElementById('warning-count');
        const infoElement = document.getElementById('info-count');
        
        if (errorElement) {
            errorElement.textContent = errorCount;
        } else {
            console.warn('Element error-count not found');
        }
        if (warningElement) {
            warningElement.textContent = warningCount;
        } else {
            console.warn('Element warning-count not found');
        }
        if (infoElement) {
            infoElement.textContent = infoCount;
        } else {
            console.warn('Element info-count not found');
        }
        
        // Populate error/warning/info lists
        this.populateValidationMessages('error-list', results.errors || []);
        this.populateValidationMessages('warning-list', results.warnings || []);
        this.populateValidationMessages('info-list', results.info || []);
        
        // Field quality breakdown (simplified)
        this.updateFieldQualityList();
    }
    
    /**
     * Update field quality list - simplified version
     */
    updateFieldQualityList() {
        // Since we removed the separate field quality panel, 
        // we can skip this or integrate it into the main quality display
        console.log('Field quality list update skipped - using simplified layout');
    }
    
    /**
     * Update simplified field review
     */
    updateFieldReview() {
        const data = this.currentAssetData;
        const validationResults = this.validationResults.validation_results || [];
        
        // All important fields including discipline and subtype fields
        const importantFields = [
            'name', 'description', 'quantity', 'unit',
            'category_name', 'equipment_type_name', 'subtype_name', 'project_name',
            'brand_name', 'model', 'serial_number', 'acquisition_cost',
            'location', 'condition_notes', 'specification',
            'warranty_period', 'maintenance_requirements'
        ];
        
        const container = document.getElementById('all-fields-review');
        if (!container) {
            console.warn('Field review container not found');
            return;
        }
        
        container.innerHTML = '';
        
        importantFields.forEach(field => {
            const fieldValidation = validationResults.filter(r => r.field_name === field);
            const fieldElement = this.createFieldReviewElement(field, data[field], fieldValidation);
            container.appendChild(fieldElement);
        });
    }
    
    /**
     * Create field review element for tabbed layout
     */
    createFieldReviewElement(fieldName, value, validations) {
        const div = document.createElement('div');
        div.className = 'col-md-4 col-sm-6';
        
        // Field display names including actual asset fields
        const displayNames = {
            'name': 'Asset Name',
            'description': 'Description', 
            'quantity': 'Quantity',
            'unit': 'Unit',
            'category_name': 'Category',
            'equipment_type_name': 'Equipment Type',
            'subtype_name': 'Equipment Subtype',
            'project_name': 'Project',
            'brand_name': 'Brand',
            'model': 'Model',
            'serial_number': 'Serial Number',
            'acquisition_cost': 'Cost',
            'location': 'Location',
            'condition_notes': 'Condition',
            'specification': 'Specification',
            'warranty_period': 'Warranty Period',
            'maintenance_requirements': 'Maintenance Requirements'
        };
        
        const displayName = displayNames[fieldName] || fieldName.replace('_', ' ');
        const displayValue = value || 'Not specified';
        
        // Format cost display
        const formattedValue = fieldName === 'acquisition_cost' && value ? 
            `₱${parseFloat(value).toLocaleString()}` : displayValue;
        
        // Check for validation issues
        const hasIssues = validations && validations.some(v => !v.passed);
        const statusClass = hasIssues ? 'border-warning' : 'border-success';
        
        div.innerHTML = `
            <div class="border rounded p-3 bg-light ${statusClass}">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <strong class="text-muted">${displayName}</strong>
                    ${hasIssues ? '<button class="btn btn-sm btn-outline-warning ms-2" style="padding: 2px 8px; font-size: 11px;" onclick="editCurrentAsset()" title="Edit this field"><i class="bi bi-pencil"></i></button>' : ''}
                </div>
                <div class="fw-bold mb-2">${formattedValue}</div>
                ${this.createValidationFeedback(validations || [])}
            </div>
        `;
        
        return div;
    }
    
    /**
     * Create validation feedback for field
     */
    createValidationFeedback(validations) {
        if (!validations.length) return '';
        
        let feedback = '<div class="mt-2">';
        validations.forEach(validation => {
            if (!validation.passed) {
                const alertClass = validation.severity === 'error' ? 'alert-danger' : 
                                validation.severity === 'warning' ? 'alert-warning' : 'alert-info';
                
                feedback += `
                    <div class="alert ${alertClass} alert-sm mb-1 py-1">
                        <small>${validation.message}</small>
                        ${validation.suggestions && validation.suggestions.length > 0 ? 
                            '<ul class="mb-0 mt-1">' + validation.suggestions.map(s => `<li><small>${s}</small></li>`).join('') + '</ul>' : ''
                        }
                    </div>
                `;
            }
        });
        feedback += '</div>';
        
        return feedback;
    }
    
    /**
     * Update suggested corrections section
     */
    updateSuggestedCorrections() {
        const suggestions = this.validationResults.validation_results
            ?.filter(r => !r.passed && r.suggestions && r.suggestions.length > 0) || [];
        
        const correctionsSection = document.getElementById('suggested-corrections');
        if (!correctionsSection) {
            console.warn('Suggested corrections section not found');
            return;
        }
        
        if (suggestions.length === 0) {
            correctionsSection.style.display = 'none';
            return;
        }
        
        correctionsSection.style.display = 'block';
        const container = document.getElementById('corrections-list');
        if (!container) {
            console.warn('Corrections list container not found');
            return;
        }
        
        container.innerHTML = '';
        
        suggestions.forEach(suggestion => {
            suggestion.suggestions.forEach(text => {
                const item = document.createElement('div');
                item.className = 'correction-item';
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <strong>${suggestion.field_name}:</strong> ${text}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyCorrection('${suggestion.field_name}', '${text}')">
                            Apply
                        </button>
                    </div>
                `;
                container.appendChild(item);
            });
        });
    }
    
    
    /**
     * Utility methods
     */
    getStatusBadgeClass(status) {
        switch (status) {
            case 'pending_verification': return 'bg-warning';
            case 'pending_authorization': return 'bg-info';
            case 'approved': return 'bg-success';
            default: return 'bg-secondary';
        }
    }
    
    getScoreColorClass(score) {
        if (score >= 90) return 'bg-success';
        if (score >= 75) return 'bg-info';
        if (score >= 60) return 'bg-warning';
        return 'bg-danger';
    }
    
    populateValidationMessages(containerId, messages) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.warn(`Validation messages container '${containerId}' not found`);
            return;
        }
        
        container.innerHTML = messages.length > 0 ? 
            '<ul class="mb-0">' + messages.map(msg => `<li><small>${msg}</small></li>`).join('') + '</ul>' :
            '<small class="text-muted">None</small>';
    }
    
    showAlert(type, message) {
        // Reuse existing showAlert function or implement alert display
        if (typeof showAlert === 'function') {
            showAlert(type, message);
        } else {
            alert(message);
        }
    }
    
    cleanup() {
        this.currentAssetId = null;
        this.currentAssetData = null;
        this.validationResults = null;
    }
}

// Global functions for button handlers
function removePhoto(button) {
    button.closest('.photo-preview-item').remove();
}

function applyCorrection(fieldName, suggestion) {
    // Implementation for applying suggested corrections
    console.log('Applying correction for', fieldName, ':', suggestion);
    // You could implement field editing functionality here
}

// Global functions for asset actions - redirect to existing functions
function editCurrentAsset() {
    if (enhancedVerification.currentAssetId) {
        const editUrl = `?route=assets/edit&id=${enhancedVerification.currentAssetId}`;
        window.open(editUrl, '_blank');
    } else {
        console.error('No current asset ID available for editing');
        if (typeof showAlert === 'function') {
            showAlert('error', 'Unable to edit - asset ID not found');
        }
    }
}

function verifyCurrentAsset() {
    if (enhancedVerification.currentAssetId) {
        // Close the review modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('enhancedVerificationModal'));
        if (modal) modal.hide();
        
        // Call the existing verifyAsset function
        if (typeof verifyAsset === 'function') {
            verifyAsset(enhancedVerification.currentAssetId);
        } else {
            console.error('verifyAsset function not found');
            if (typeof showAlert === 'function') {
                showAlert('error', 'Verify function not available');
            }
        }
    } else {
        console.error('No current asset ID available for verification');
        if (typeof showAlert === 'function') {
            showAlert('error', 'Unable to verify - asset ID not found');
        }
    }
}

function authorizeCurrentAsset() {
    if (enhancedVerification.currentAssetId) {
        // Close the review modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('enhancedVerificationModal'));
        if (modal) modal.hide();
        
        // Call the existing authorizeAsset function
        if (typeof authorizeAsset === 'function') {
            authorizeAsset(enhancedVerification.currentAssetId);
        } else {
            console.error('authorizeAsset function not found');
            if (typeof showAlert === 'function') {
                showAlert('error', 'Authorize function not available');
            }
        }
    } else {
        console.error('No current asset ID available for authorization');
        if (typeof showAlert === 'function') {
            showAlert('error', 'Unable to authorize - asset ID not found');
        }
    }
}

// Initialize enhanced verification system
const enhancedVerification = new EnhancedAssetVerification();

// Make globally available for button onclick handlers
window.openEnhancedVerification = (assetId) => {
    enhancedVerification.openVerificationModal(assetId);
};

/**
 * Simplified Enhanced Asset Authorization System
 * Just displays asset info for review and redirects to existing functions
 */
class EnhancedAssetAuthorization {
    constructor() {
        this.currentAssetId = null;
        this.currentAssetData = null;
        
        this.initializeEventListeners();
    }
    
    initializeEventListeners() {
        // Modal cleanup only
        document.getElementById('enhancedAuthorizationModal')?.addEventListener('hidden.bs.modal', this.cleanup.bind(this));
    }
    
    /**
     * Open authorization modal for specific asset - simplified for review only
     */
    async openAuthorizationModal(assetId) {
        this.currentAssetId = assetId;
        
        try {
            // Show modal and loading state with error handling
            const modalElement = document.getElementById('enhancedAuthorizationModal');
            if (!modalElement) {
                throw new Error('Enhanced authorization modal element not found');
            }
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
            const loadingElement = document.getElementById('authorization-loading');
            const contentElement = document.getElementById('authorization-content');
            
            if (loadingElement) loadingElement.style.display = 'block';
            if (contentElement) contentElement.style.display = 'none';
            
            // Load basic asset data for review
            await this.loadAssetData(assetId);
            
            // Update UI with data - wrapped in try-catch
            try {
                this.updateAssetOverview();
            } catch (err) {
                console.error('Error updating asset overview:', err);
            }
            
            // Show content
            if (loadingElement) loadingElement.style.display = 'none';
            if (contentElement) contentElement.style.display = 'block';
            
        } catch (error) {
            console.error('Error loading asset authorization data:', error);
            this.showAlert('error', 'Failed to load asset data: ' + error.message);
            
            // Try to hide loading even if there was an error
            const loadingElement = document.getElementById('authorization-loading');
            if (loadingElement) loadingElement.style.display = 'none';
        }
    }
    
    /**
     * Load asset data from server
     */
    async loadAssetData(assetId) {
        const response = await fetch(`?route=api/assets/authorization-data&id=${assetId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRFTokenValue
            }
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('API Response Error:', errorText);
            throw new Error(`Failed to load asset data: ${response.status} - ${errorText.substring(0, 200)}`);
        }
        
        const responseText = await response.text();
        console.log('API Response:', responseText.substring(0, 500)); // Debug log
        
        try {
            this.currentAssetData = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response Text:', responseText.substring(0, 1000));
            throw new Error('Invalid JSON response from server. Check console for details.');
        }
    }
    
    
    /**
     * Update asset overview section - simplified for review only
     */
    updateAssetOverview() {
        const data = this.currentAssetData;
        
        // Update elements with null checks
        const elements = [
            { id: 'auth-asset-ref', value: data.ref || '-' },
            { id: 'auth-asset-name', value: data.name || '-' },
            { id: 'auth-asset-category', value: data.category_name || '-' },
            { id: 'auth-asset-equipment-type', value: data.equipment_type_name || '-' },
            { id: 'auth-asset-subtype', value: data.subtype_name || '-' },
            { id: 'auth-asset-project', value: data.project_name || '-' },
            { id: 'auth-asset-quantity', value: data.quantity || '-' },
            { id: 'auth-asset-verifier', value: data.verified_by_name || 'Not yet verified' },
            { id: 'auth-asset-discipline', value: data.discipline_names || '-' },
            { id: 'auth-asset-sub-discipline', value: data.sub_discipline_names || '-' }
        ];
        
        elements.forEach(item => {
            const element = document.getElementById(item.id);
            if (element) {
                element.textContent = item.value;
            } else {
                console.warn(`Authorization element with ID '${item.id}' not found`);
            }
        });
        
        // Handle cost display separately
        const costElement = document.getElementById('auth-asset-cost');
        if (costElement) {
            costElement.textContent = data.acquisition_cost ? 
                `₱${parseFloat(data.acquisition_cost).toLocaleString()}` : 'Not specified';
        }
    }
    
    showAlert(type, message) {
        if (typeof showAlert === 'function') {
            showAlert(type, message);
        } else {
            alert(message);
        }
    }
    
    cleanup() {
        this.currentAssetId = null;
        this.currentAssetData = null;
    }
}

// Simplified authorization system - no complex logic, just redirects
// Initialize enhanced authorization system
const enhancedAuthorization = new EnhancedAssetAuthorization();

// Make globally available for button onclick handlers
window.openEnhancedAuthorization = (assetId) => {
    enhancedAuthorization.openAuthorizationModal(assetId);
};

// Global functions for authorization actions - redirect to existing functions
function editCurrentAssetFromAuth() {
    if (enhancedAuthorization.currentAssetId) {
        const editUrl = `?route=assets/edit&id=${enhancedAuthorization.currentAssetId}`;
        window.open(editUrl, '_blank');
    } else {
        console.error('No current asset ID available for editing from authorization');
        if (typeof showAlert === 'function') {
            showAlert('error', 'Unable to edit - asset ID not found');
        }
    }
}

function authorizeCurrentAssetFromModal() {
    if (enhancedAuthorization.currentAssetId) {
        // Close the authorization review modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('enhancedAuthorizationModal'));
        if (modal) modal.hide();
        
        // Call the existing authorizeAsset function
        if (typeof authorizeAsset === 'function') {
            authorizeAsset(enhancedAuthorization.currentAssetId);
        } else {
            console.error('authorizeAsset function not found');
            if (typeof showAlert === 'function') {
                showAlert('error', 'Authorize function not available');
            }
        }
    } else {
        console.error('No current asset ID available for authorization');
        if (typeof showAlert === 'function') {
            showAlert('error', 'Unable to authorize - asset ID not found');
        }
    }
}