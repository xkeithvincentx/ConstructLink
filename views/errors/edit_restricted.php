<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Restricted - ConstructLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark text-center">
                        <h4 class="mb-0">
                            <i class="bi bi-shield-exclamation me-2"></i>
                            Edit Access Restricted
                        </h4>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <i class="bi bi-lock-fill text-warning" style="font-size: 4rem;"></i>
                        </div>
                        
                        <h5 class="card-title mb-3">Cannot Edit This Asset</h5>
                        
                        <div class="alert alert-warning" role="alert">
                            <?= htmlspecialchars($errorMessage ?? 'You do not have permission to edit this asset.') ?>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="bi bi-person-check text-success me-1"></i>
                                            Need Changes?
                                        </h6>
                                        <p class="card-text small">
                                            Contact your Asset Director or submit a change request through the proper channels.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="bi bi-info-circle text-info me-1"></i>
                                            Why Restricted?
                                        </h6>
                                        <p class="card-text small">
                                            Edit permissions are based on asset workflow status to maintain data integrity and proper approval process.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="javascript:history.back()" class="btn btn-secondary me-2">
                                <i class="bi bi-arrow-left me-1"></i>Go Back
                            </a>
                            <a href="?route=assets" class="btn btn-primary">
                                <i class="bi bi-list me-1"></i>View All Assets
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Help Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bi bi-question-circle me-1"></i>
                            When Can You Edit Assets?
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center mb-3">
                                    <i class="bi bi-pencil-square text-success fs-2"></i>
                                    <h6 class="mt-2">Before Verification</h6>
                                    <small class="text-muted">Site clerks can edit their own assets</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center mb-3">
                                    <i class="bi bi-eye-fill text-info fs-2"></i>
                                    <h6 class="mt-2">During Review</h6>
                                    <small class="text-muted">Project managers can make corrections</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center mb-3">
                                    <i class="bi bi-shield-check text-warning fs-2"></i>
                                    <h6 class="mt-2">After Approval</h6>
                                    <small class="text-muted">Only Asset Director can edit</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>