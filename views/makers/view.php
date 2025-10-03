<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php
    $messages = [
        'maker_created' => 'Manufacturer created successfully!',
        'maker_updated' => 'Manufacturer updated successfully!'
    ];
    $message = $messages[$_GET['message']] ?? '';
    ?>
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Manufacturer Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Manufacturer Information
                </h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Name:</dt>
                    <dd class="col-sm-9">
                        <strong><?= htmlspecialchars($maker['name']) ?></strong>
                    </dd>
                    
                    <dt class="col-sm-3">Country:</dt>
                    <dd class="col-sm-9">
                        <?php if (!empty($maker['country'])): ?>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($maker['country']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">Not specified</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-3">Website:</dt>
                    <dd class="col-sm-9">
                        <?php if (!empty($maker['website'])): ?>
                            <a href="<?= htmlspecialchars($maker['website']) ?>" target="_blank" class="text-decoration-none">
                                <i class="bi bi-link-45deg me-1"></i><?= htmlspecialchars($maker['website']) ?>
                                <i class="bi bi-box-arrow-up-right ms-1"></i>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Not provided</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-3">Description:</dt>
                    <dd class="col-sm-9">
                        <?php if (!empty($maker['description'])): ?>
                            <?= nl2br(htmlspecialchars($maker['description'])) ?>
                        <?php else: ?>
                            <span class="text-muted">No description provided</span>
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-3">Created:</dt>
                    <dd class="col-sm-9"><?= formatDate($maker['created_at']) ?></dd>
                    
                    <?php if ($maker['updated_at']): ?>
                        <dt class="col-sm-3">Last Updated:</dt>
                        <dd class="col-sm-9"><?= formatDate($maker['updated_at']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <!-- Asset Categories -->
        <?php if (!empty($makerCategories)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-tags me-2"></i>Asset Categories
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Asset Count</th>
                                    <th>Total Value</th>
                                    <th>Status Distribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($makerCategories as $category): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($category['category_name']) ?></td>
                                        <td>
                                            <span class="badge bg-info"><?= $category['asset_count'] ?></span>
                                        </td>
                                        <td>
                                            <strong><?= formatCurrency($category['total_value']) ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $statuses = explode(',', $category['statuses']);
                                            foreach ($statuses as $status):
                                                $badgeClass = match(trim($status)) {
                                                    'available' => 'bg-success',
                                                    'in_use' => 'bg-primary',
                                                    'under_maintenance' => 'bg-warning',
                                                    'borrowed' => 'bg-info',
                                                    'retired' => 'bg-secondary',
                                                    default => 'bg-light text-dark'
                                                };
                                            ?>
                                                <span class="badge <?= $badgeClass ?> me-1"><?= ucfirst(trim($status)) ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Assets List -->
        <?php if (!empty($makerAssets)): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-box me-2"></i>Assets (<?= count($makerAssets) ?>)
                    </h5>
                    <a href="?route=assets&maker_id=<?= $maker['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>View All
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Asset Name</th>
                                    <th>Category</th>
                                    <th>Project</th>
                                    <th>Status</th>
                                    <th>Value</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($makerAssets, 0, 10) as $asset): ?>
                                    <tr>
                                        <td>
                                            <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($asset['ref']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($asset['name']) ?></div>
                                            <?php if (!empty($asset['model'])): ?>
                                                <small class="text-muted">Model: <?= htmlspecialchars($asset['model']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php
                                            $statusClass = match($asset['status']) {
                                                'available' => 'bg-success',
                                                'in_use' => 'bg-primary',
                                                'under_maintenance' => 'bg-warning',
                                                'borrowed' => 'bg-info',
                                                'retired' => 'bg-secondary',
                                                default => 'bg-light text-dark'
                                            };
                                            ?>
                                            <span class="badge <?= $statusClass ?>"><?= ucfirst($asset['status']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($asset['acquisition_cost']): ?>
                                                <strong><?= formatCurrency($asset['acquisition_cost']) ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?route=assets/view&id=<?= $asset['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" title="View Asset">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($makerAssets) > 10): ?>
                        <div class="text-center mt-3">
                            <a href="?route=assets&maker_id=<?= $maker['id'] ?>" class="btn btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View All <?= count($makerAssets) ?> Assets
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-box display-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">No Assets Found</h5>
                    <p class="text-muted">This manufacturer doesn't have any assets assigned yet.</p>
                    <a href="?route=assets/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Add First Asset
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Statistics -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-graph-up me-2"></i>Statistics
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h4 class="text-primary mb-1"><?= $maker['assets_count'] ?? 0 ?></h4>
                            <small class="text-muted">Total Assets</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-1"><?= formatCurrency($maker['total_value'] ?? 0) ?></h4>
                        <small class="text-muted">Total Value</small>
                    </div>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border-end">
                            <h5 class="text-success mb-1"><?= $maker['available_assets'] ?? 0 ?></h5>
                            <small class="text-muted">Available</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-end">
                            <h5 class="text-primary mb-1"><?= $maker['in_use_assets'] ?? 0 ?></h5>
                            <small class="text-muted">In Use</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <h5 class="text-warning mb-1"><?= $maker['maintenance_assets'] ?? 0 ?></h5>
                        <small class="text-muted">Maintenance</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                        <a href="?route=makers/edit&id=<?= $maker['id'] ?>" class="btn btn-warning btn-sm">
                            <i class="bi bi-pencil me-1"></i>Edit Manufacturer
                        </a>
                    <?php endif; ?>
                    
                    <a href="?route=assets/create&maker_id=<?= $maker['id'] ?>" class="btn btn-success btn-sm">
                        <i class="bi bi-plus me-1"></i>Add Asset
                    </a>
                    
                    <?php if (($maker['assets_count'] ?? 0) > 0): ?>
                        <a href="?route=assets&maker_id=<?= $maker['id'] ?>" class="btn btn-info btn-sm">
                            <i class="bi bi-box me-1"></i>View All Assets
                        </a>
                    <?php endif; ?>
                    
                    <a href="?route=makers" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-list me-1"></i>All Manufacturers
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Additional Information -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Additional Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">ID:</dt>
                    <dd class="col-sm-8">#<?= $maker['id'] ?></dd>
                    
                    <dt class="col-sm-4">Categories:</dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($maker['categories'])): ?>
                            <?= htmlspecialchars($maker['categories']) ?>
                        <?php else: ?>
                            <span class="text-muted">None</span>
                        <?php endif; ?>
                    </dd>
                </dl>
                
                <?php if ($auth->hasRole(['System Admin', 'Procurement Officer']) && ($maker['assets_count'] ?? 0) == 0): ?>
                    <hr>
                    <div class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteMaker(<?= $maker['id'] ?>)">
                            <i class="bi bi-trash me-1"></i>Delete Manufacturer
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Delete manufacturer
function deleteMaker(makerId) {
    if (confirm('Are you sure you want to delete this manufacturer? This action cannot be undone.')) {
        window.location.href = '?route=makers/delete&id=' + makerId;
    }
}

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Manufacturer Details - ConstructLink™';
$pageHeader = 'Manufacturer: ' . htmlspecialchars($maker['name']);
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Manufacturers', 'url' => '?route=makers'],
    ['title' => 'View Details', 'url' => '?route=makers/view&id=' . $maker['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
