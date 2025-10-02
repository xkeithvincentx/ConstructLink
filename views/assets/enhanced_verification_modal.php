<!-- Enhanced Asset Verification Modal -->
<div class="modal fade" id="enhancedVerificationModal" tabindex="-1" aria-labelledby="enhancedVerificationModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="enhancedVerificationModalLabel">
                    <i class="bi bi-shield-check me-2"></i>Enhanced Asset Verification
                </h5>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-outline-dark btn-sm me-2" id="edit-asset-btn" onclick="editCurrentAsset()" title="Edit Asset">
                        <i class="bi bi-pencil-square me-1"></i>Edit Asset
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <!-- Loading Spinner -->
                <div id="verification-loading" class="text-center py-5">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Loading asset data...</span>
                    </div>
                    <p class="mt-3">Loading asset verification data...</p>
                </div>

                <!-- Main Verification Content -->
                <div id="verification-content" style="display: none;">
                    
                    <!-- Tabbed Interface for Clean Organization -->
                    <ul class="nav nav-tabs mb-3" id="verificationTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                                <i class="bi bi-info-circle me-1"></i>Asset Overview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="quality-tab" data-bs-toggle="tab" data-bs-target="#quality" type="button" role="tab">
                                <i class="bi bi-clipboard-check me-1"></i>Data Quality
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="verificationTabContent">
                        
                        <!-- Asset Overview Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 bg-light">
                                                        <strong class="text-muted d-block mb-1">Reference</strong>
                                                        <span id="asset-ref" class="h6">-</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 bg-light">
                                                        <strong class="text-muted d-block mb-1">Status</strong>
                                                        <span class="badge bg-secondary" id="workflow-status">pending_verification</span>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="border rounded p-3 bg-light">
                                                        <strong class="text-muted d-block mb-1">Asset Name</strong>
                                                        <span id="asset-name" class="h6">-</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 bg-light">
                                                        <strong class="text-muted d-block mb-1">Category</strong>
                                                        <span id="asset-category">-</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 bg-light">
                                                        <strong class="text-muted d-block mb-1">Equipment Type</strong>
                                                        <span id="asset-equipment-type">-</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 bg-light">
                                                        <strong class="text-muted d-block mb-1">Equipment Subtype</strong>
                                                        <span id="asset-subtype">-</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="border rounded p-3 bg-light">
                                                        <strong class="text-muted d-block mb-1">Project</strong>
                                                        <span id="asset-project">-</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="border rounded p-3 bg-light">
                                                        <strong class="text-muted d-block mb-1">Quantity</strong>
                                                        <span id="asset-quantity">-</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="border rounded p-3 bg-light">
                                                        <strong class="text-muted d-block mb-1">Brand</strong>
                                                        <span id="asset-brand">-</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 bg-light">
                                                        <strong class="text-muted d-block mb-1">Discipline</strong>
                                                        <span id="asset-discipline">-</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 bg-light">
                                                        <strong class="text-muted d-block mb-1">Sub-Discipline</strong>
                                                        <span id="asset-sub-discipline">-</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center p-4">
                                                <h5 class="text-muted mb-3">Data Quality Score</h5>
                                                <div class="position-relative d-inline-block">
                                                    <div class="circular-progress" style="--percentage: 0;">
                                                        <div class="circular-progress-inner">
                                                            <span id="overall-score" class="h2 mb-0">0</span>%
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="text-muted mt-3 mb-0">Overall Quality Rating</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Quality Tab -->
                        <div class="tab-pane fade" id="quality" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row text-center mb-4">
                                        <div class="col-md-4">
                                            <div class="border rounded p-3">
                                                <h3 class="text-primary mb-1"><span id="completeness-score">0</span>%</h3>
                                                <p class="text-muted mb-0">Completeness</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3">
                                                <h3 class="text-info mb-1"><span id="accuracy-score">0</span>%</h3>
                                                <p class="text-muted mb-0">Accuracy</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3">
                                                <h3 class="text-success mb-1"><span id="validation-rules-passed">0</span>/<span id="validation-rules-total">0</span></h3>
                                                <p class="text-muted mb-0">Rules Passed</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="alert alert-danger">
                                                <h6 class="alert-heading">Errors (<span id="error-count">0</span>)</h6>
                                                <div id="error-list" class="small"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="alert alert-warning">
                                                <h6 class="alert-heading">Warnings (<span id="warning-count">0</span>)</h6>
                                                <div id="warning-list" class="small"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="alert alert-info">
                                                <h6 class="alert-heading">Info (<span id="info-count">0</span>)</h6>
                                                <div id="info-list" class="small"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex justify-content-between w-100">
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x me-1"></i>Close Review
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary me-2" onclick="editCurrentAsset()">
                            <i class="bi bi-pencil-square me-1"></i>Edit Asset
                        </button>
                        <button type="button" class="btn btn-success" onclick="verifyCurrentAsset()">
                            <i class="bi bi-check-circle me-1"></i>Verify Asset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles for the enhanced verification modal - Responsive & Centered */
#enhancedVerificationModal .modal-dialog {
    max-width: 90%;
    width: 1200px;
    margin: 1.75rem auto;
    max-height: 85vh;
}

#enhancedVerificationModal .modal-content {
    display: flex;
    flex-direction: column;
    height: 85vh;
    max-height: 85vh;
}

#enhancedVerificationModal .modal-header {
    flex-shrink: 0;
    padding: 1rem 1.5rem;
}

#enhancedVerificationModal .modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1 1 auto;
}

#enhancedVerificationModal .modal-footer {
    flex-shrink: 0;
    border-top: 1px solid #dee2e6;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    #enhancedVerificationModal .modal-dialog {
        max-width: 95%;
        margin: 1rem auto;
    }
}

@media (max-width: 768px) {
    #enhancedVerificationModal .modal-dialog {
        max-width: 100%;
        margin: 0.5rem;
        height: 100vh;
        max-height: 100vh;
    }
    
    #enhancedVerificationModal .modal-content {
        height: calc(100vh - 1rem);
        max-height: calc(100vh - 1rem);
        border-radius: 0;
    }
    
    /* Make tabs scrollable on mobile */
    #verificationTabs {
        overflow-x: auto;
        flex-wrap: nowrap;
    }
    
    /* Adjust grid layouts for mobile */
    .col-md-4, .col-md-6, .col-md-8 {
        padding: 0.5rem;
    }
    
    /* Smaller circular progress on mobile */
    .circular-progress {
        width: 100px;
        height: 100px;
    }
    
    .circular-progress-inner {
        width: 75px;
        height: 75px;
    }
    
    /* Stack buttons vertically on mobile */
    #enhancedVerificationModal .modal-footer .d-flex {
        flex-direction: column;
    }
    
    #enhancedVerificationModal .modal-footer button {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    #enhancedVerificationModal .modal-footer button:last-child {
        margin-bottom: 0;
    }
}

@media (max-width: 576px) {
    #enhancedVerificationModal .modal-header h5 {
        font-size: 1.1rem;
    }
    
    #enhancedVerificationModal .modal-header .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    /* Single column layout on small screens */
    .row .col-md-4,
    .row .col-md-6 {
        width: 100%;
    }
}

.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.circular-progress {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: conic-gradient(
        #28a745 0deg, 
        #28a745 calc(var(--percentage) * 3.6deg), 
        #e9ecef calc(var(--percentage) * 3.6deg)
    );
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.circular-progress-inner {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    box-shadow: 0 0 0 3px rgba(255,255,255,0.8);
}

#verificationTabs .nav-link {
    border-radius: 0.375rem 0.375rem 0 0;
    margin-right: 0.25rem;
}

#verificationTabs .nav-link.active {
    background-color: #f8f9fa;
    border-color: #dee2e6 #dee2e6 #f8f9fa;
}

.tab-content {
    min-height: 400px;
}

.field-review-item {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background: #f8f9fa;
}

.field-status-icon {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    margin-right: 8px;
}

.field-status-good { background-color: #28a745; }
.field-status-warning { background-color: #ffc107; }
.field-status-error { background-color: #dc3545; }

.correction-item {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
}

.photo-preview-item {
    position: relative;
    margin-bottom: 1rem;
}

.photo-preview-item img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 0.375rem;
}

.photo-remove-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(220, 53, 69, 0.8);
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    color: white;
    cursor: pointer;
}
</style>