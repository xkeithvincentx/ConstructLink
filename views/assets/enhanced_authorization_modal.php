<!-- Enhanced Asset Authorization Modal - Simplified -->
<div class="modal fade" id="enhancedAuthorizationModal" tabindex="-1" aria-labelledby="enhancedAuthorizationModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="enhancedAuthorizationModalLabel">
                    <i class="bi bi-shield-check me-2"></i>Enhanced Asset Authorization
                </h5>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-outline-light btn-sm me-2" id="auth-edit-asset-btn" onclick="editCurrentAssetFromAuth()" title="Edit Asset">
                        <i class="bi bi-pencil-square me-1"></i>Edit Asset
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <!-- Loading Spinner -->
                <div id="authorization-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading asset data...</span>
                    </div>
                    <p class="mt-3">Loading asset authorization data...</p>
                </div>

                <!-- Main Authorization Content -->
                <div id="authorization-content" style="display: none;">
                    
                    <!-- Simple Tabbed Interface for Authorization Review -->
                    <ul class="nav nav-tabs mb-3" id="authorizationTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="auth-overview-tab" data-bs-toggle="tab" data-bs-target="#auth-overview" type="button" role="tab">
                                <i class="bi bi-info-circle me-1"></i>Asset Overview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="verification-summary-tab" data-bs-toggle="tab" data-bs-target="#verification-summary" type="button" role="tab">
                                <i class="bi bi-check-circle me-1"></i>Verification Summary
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="authorizationTabContent">
                        
                        <!-- Asset Overview Tab -->
                        <div class="tab-pane fade show active" id="auth-overview" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-light">
                                                <strong class="text-muted d-block mb-1">Reference</strong>
                                                <span id="auth-asset-ref" class="h6">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-light">
                                                <strong class="text-muted d-block mb-1">Status</strong>
                                                <span class="badge bg-info" id="auth-workflow-status">pending_authorization</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="border rounded p-3 bg-light">
                                                <strong class="text-muted d-block mb-1">Asset Name</strong>
                                                <span id="auth-asset-name" class="h6">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-light">
                                                <strong class="text-muted d-block mb-1">Category</strong>
                                                <span id="auth-asset-category">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-light">
                                                <strong class="text-muted d-block mb-1">Equipment Type</strong>
                                                <span id="auth-asset-equipment-type">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-light">
                                                <strong class="text-muted d-block mb-1">Equipment Subtype</strong>
                                                <span id="auth-asset-subtype">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 bg-light">
                                                <strong class="text-muted d-block mb-1">Project</strong>
                                                <span id="auth-asset-project">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 bg-light">
                                                <strong class="text-muted d-block mb-1">Quantity</strong>
                                                <span id="auth-asset-quantity">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 bg-light">
                                                <strong class="text-muted d-block mb-1">Cost</strong>
                                                <span id="auth-asset-cost">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-light">
                                                <strong class="text-muted d-block mb-1">Verified By</strong>
                                                <span id="auth-asset-verifier">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-light">
                                                <strong class="text-muted d-block mb-1">Discipline</strong>
                                                <span id="auth-asset-discipline">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 bg-light">
                                                <strong class="text-muted d-block mb-1">Sub-Discipline</strong>
                                                <span id="auth-asset-sub-discipline">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Verification Summary Tab -->
                        <div class="tab-pane fade" id="verification-summary" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        <h5 class="text-muted mb-3">Asset Ready for Authorization</h5>
                                        <p class="text-muted">This asset has been verified and is ready for final authorization. Review the asset details and proceed with authorization if everything is correct.</p>
                                    </div>
                                    
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <div class="border rounded p-3">
                                                <i class="bi bi-check-circle text-success display-6"></i>
                                                <h6 class="mt-2">Verified</h6>
                                                <p class="text-muted small">Asset has been verified by inventory clerk</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3">
                                                <i class="bi bi-clipboard-check text-info display-6"></i>
                                                <h6 class="mt-2">Data Quality</h6>
                                                <p class="text-muted small">Asset information is complete and validated</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3">
                                                <i class="bi bi-shield-check text-warning display-6"></i>
                                                <h6 class="mt-2">Ready</h6>
                                                <p class="text-muted small">Ready for final authorization</p>
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
                        <button type="button" class="btn btn-primary me-2" onclick="editCurrentAssetFromAuth()">
                            <i class="bi bi-pencil-square me-1"></i>Edit Asset
                        </button>
                        <button type="button" class="btn btn-success" onclick="authorizeCurrentAssetFromModal()">
                            <i class="bi bi-shield-check me-1"></i>Authorize Asset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles for the enhanced authorization modal - Responsive & Centered */
#enhancedAuthorizationModal .modal-dialog {
    max-width: 90%;
    width: 1200px;
    margin: 1.75rem auto;
    max-height: 85vh;
}

#enhancedAuthorizationModal .modal-content {
    display: flex;
    flex-direction: column;
    height: 85vh;
    max-height: 85vh;
}

#enhancedAuthorizationModal .modal-header {
    flex-shrink: 0;
    padding: 1rem 1.5rem;
}

#enhancedAuthorizationModal .modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1 1 auto;
}

#enhancedAuthorizationModal .modal-footer {
    flex-shrink: 0;
    border-top: 1px solid #dee2e6;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    #enhancedAuthorizationModal .modal-dialog {
        max-width: 95%;
        margin: 1rem auto;
    }
}

@media (max-width: 768px) {
    #enhancedAuthorizationModal .modal-dialog {
        max-width: 100%;
        margin: 0.5rem;
        height: 100vh;
        max-height: 100vh;
    }
    
    #enhancedAuthorizationModal .modal-content {
        height: calc(100vh - 1rem);
        max-height: calc(100vh - 1rem);
        border-radius: 0;
    }
    
    /* Make tabs scrollable on mobile */
    #authorizationTabs {
        overflow-x: auto;
        flex-wrap: nowrap;
    }
    
    /* Adjust grid layouts for mobile */
    .col-md-4, .col-md-6 {
        padding: 0.5rem;
    }
    
    /* Stack buttons vertically on mobile */
    #enhancedAuthorizationModal .modal-footer .d-flex {
        flex-direction: column;
    }
    
    #enhancedAuthorizationModal .modal-footer button {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    #enhancedAuthorizationModal .modal-footer button:last-child {
        margin-bottom: 0;
    }
}

@media (max-width: 576px) {
    #enhancedAuthorizationModal .modal-header h5 {
        font-size: 1.1rem;
    }
    
    #enhancedAuthorizationModal .modal-header .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    /* Single column layout on small screens */
    .row .col-md-4,
    .row .col-md-6 {
        width: 100%;
    }
    
    /* Adjust icon sizes on small screens */
    .display-6 {
        font-size: 2rem;
    }
}

#authorizationTabs .nav-link {
    border-radius: 0.375rem 0.375rem 0 0;
    margin-right: 0.25rem;
}

#authorizationTabs .nav-link.active {
    background-color: #f8f9fa;
    border-color: #dee2e6 #dee2e6 #f8f9fa;
}

.tab-content {
    min-height: 400px;
}
</style>