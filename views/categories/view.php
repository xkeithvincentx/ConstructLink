<?php
// Start output buffering to capture content
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-tags me-2"></i>
        <?= htmlspecialchars($category['name']) ?>
        <?php 
        $assetType = $category['asset_type'] ?? 'capital';
        switch($assetType) {
            case 'capital': 
                echo '<span class="badge bg-primary ms-2">🔧 Capital Asset</span>';
                break;
            case 'inventory': 
                echo '<span class="badge bg-info ms-2">📦 Inventory</span>';
                break;
            case 'expense': 
                echo '<span class="badge bg-warning text-dark ms-2">💰 Direct Expense</span>';
                break;
            default:
                echo '<span class="badge bg-secondary ms-2">Unknown</span>';
        }
        ?>
        <?php if ($category['is_consumable']): ?>
            <span class="badge bg-light text-dark ms-1">Consumable</span>
        <?php endif; ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <?php if ($auth->hasRole(['System Admin', 'Asset Director'])): ?>
                <a href="?route=categories/edit&id=<?= $category['id'] ?>" class="btn btn-outline-warning">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            <?php endif; ?>
            <a href="?route=categories" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Categories
            </a>
        </div>
    </div>
</div>

<!-- Category Overview -->
<div class="row">
    <!-- Category Information -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Category Information
                </h5>
            </div>
            <div class="card-body">
                <!-- Business Classification Overview -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="bi bi-gear me-1"></i>Business Classification
                        </h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-6">Asset Type:</dt>
                            <dd class="col-sm-6">
                                <?php 
                                $assetType = $category['asset_type'] ?? 'capital';
                                switch($assetType) {
                                    case 'capital': 
                                        echo '<span class="badge bg-primary">🔧 Capital Asset</span>';
                                        break;
                                    case 'inventory': 
                                        echo '<span class="badge bg-info">📦 Inventory</span>';
                                        break;
                                    case 'expense': 
                                        echo '<span class="badge bg-warning text-dark">💰 Direct Expense</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-secondary">Unknown</span>';
                                }
                                ?>
                            </dd>
                            
                            <dt class="col-sm-6">Asset Generation:</dt>
                            <dd class="col-sm-6">
                                <?php if ($category['generates_assets'] ?? true): ?>
                                    <span class="badge bg-success">✓ Creates Assets</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">✗ Direct Expense Only</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-6">Consumable:</dt>
                            <dd class="col-sm-6">
                                <?php if ($category['is_consumable']): ?>
                                    <span class="badge bg-info">✓ Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark">✗ No</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-6">Depreciation:</dt>
                            <dd class="col-sm-6">
                                <?php if ($category['depreciation_applicable'] ?? false): ?>
                                    <span class="badge bg-warning text-dark">✓ Applicable</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark">✗ Not Applicable</span>
                                <?php endif; ?>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success mb-3">
                            <i class="bi bi-cash-coin me-1"></i>Financial Rules
                        </h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-6">Cap. Threshold:</dt>
                            <dd class="col-sm-6">
                                <?php if (($category['capitalization_threshold'] ?? 0) > 0): ?>
                                    <span class="text-success fw-bold">$<?= number_format($category['capitalization_threshold'], 2) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">No threshold</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-6">Auto-Expense:</dt>
                            <dd class="col-sm-6">
                                <?php if ($category['auto_expense_below_threshold'] ?? false): ?>
                                    <span class="badge bg-warning text-dark">✓ Below Threshold</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark">✗ Manual Review</span>
                                <?php endif; ?>
                            </dd>
                            
                            <?php if (!($category['generates_assets'] ?? true)): ?>
                            <dt class="col-sm-6">Expense Type:</dt>
                            <dd class="col-sm-6">
                                <?php if (!empty($category['expense_category'])): ?>
                                    <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $category['expense_category'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Not specified</span>
                                <?php endif; ?>
                            </dd>
                            <?php endif; ?>
                            
                            <dt class="col-sm-6">Category ID:</dt>
                            <dd class="col-sm-6">
                                <span class="badge bg-light text-dark">#<?= $category['id'] ?></span>
                            </dd>
                        </dl>
                    </div>
                </div>
                
                <!-- Basic Information -->
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-info mb-3">
                            <i class="bi bi-info-circle me-1"></i>Basic Information
                        </h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Name:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($category['name']) ?></dd>
                            
                            <dt class="col-sm-5">Parent Category:</dt>
                            <dd class="col-sm-7">
                                <?= !empty($category['parent_name']) ? htmlspecialchars($category['parent_name']) : '<span class="text-muted">None (Top Level)</span>' ?>
                            </dd>
                            
                            <?php if (!empty($category['description'])): ?>
                            <dt class="col-sm-5">Description:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($category['description']) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-secondary mb-3">
                            <i class="bi bi-clock me-1"></i>System Information
                        </h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Created:</dt>
                            <dd class="col-sm-7"><?= date('M j, Y g:i A', strtotime($category['created_at'])) ?></dd>
                            
                            <?php if (!empty($category['updated_at'])): ?>
                            <dt class="col-sm-5">Last Updated:</dt>
                            <dd class="col-sm-7"><?= date('M j, Y g:i A', strtotime($category['updated_at'])) ?></dd>
                            <?php endif; ?>
                            
                            <dt class="col-sm-5">Total Assets:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-primary"><?= $category['assets_count'] ?? 0 ?> assets</span>
                            </dd>
                            
                            <dt class="col-sm-5">Total Value:</dt>
                            <dd class="col-sm-7">
                                <strong>
                                    <?php if (!empty($category['total_value'])): ?>
                                        ₱<?= number_format($category['total_value'], 2) ?>
                                    <?php else: ?>
                                        ₱0.00
                                    <?php endif; ?>
                                </strong>
                            </dd>
                        </dl>
                    </div>
                </div>
                
                <!-- Business Usage Guidelines -->
                <?php if (!empty($category['business_description'])): ?>
                <hr class="my-4">
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="bi bi-lightbulb me-1"></i>Business Usage Guidelines
                    </h6>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($category['business_description'])) ?></p>
                </div>
                <?php endif; ?>
                
                <!-- General Description -->
                <?php if (!empty($category['description']) && $category['description'] !== ($category['business_description'] ?? '')): ?>
                <hr class="my-3">
                <div>
                    <h6 class="text-secondary mb-2">
                        <i class="bi bi-file-text me-1"></i>General Description
                    </h6>
                    <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($category['description'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Category Assets -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-box me-2"></i>Category Assets
                </h5>
                <div class="btn-group btn-group-sm">
                    <a href="?route=assets&category_id=<?= $category['id'] ?>" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>View All
                    </a>
                    <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Procurement Officer'])): ?>
                        <a href="?route=assets/create&category_id=<?= $category['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-plus me-1"></i>Add Asset
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($categoryAssets)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Asset Name</th>
                                    <th>Project</th>
                                    <th>Status</th>
                                    <th class="text-end">Cost</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($categoryAssets, 0, 10) as $asset): ?>
                                    <tr>
                                        <td>
                                            <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="text-decoration-none fw-medium">
                                                <?= htmlspecialchars($asset['ref']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($asset['name']) ?></div>
                                            <?php if (!empty($asset['model'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars($asset['model']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'available' => 'success',
                                                'in_use' => 'primary',
                                                'borrowed' => 'warning',
                                                'under_maintenance' => 'info',
                                                'retired' => 'secondary'
                                            ];
                                            $class = $statusClass[$asset['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $class ?>"><?= ucfirst(str_replace('_', ' ', $asset['status'])) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <?php if (!empty($asset['acquisition_cost'])): ?>
                                                <span class="fw-medium">₱<?= number_format($asset['acquisition_cost'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Asset">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($categoryAssets) > 10): ?>
                        <div class="text-center mt-3 pt-3 border-top">
                            <a href="?route=assets&category_id=<?= $category['id'] ?>" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-right me-1"></i>View All <?= count($categoryAssets) ?> Assets
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-box display-1 text-muted"></i>
                        <h5 class="mt-3 text-muted">No Assets in Category</h5>
                        <p class="text-muted mb-3">This category doesn't have any assets assigned yet.</p>
                        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Procurement Officer'])): ?>
                            <a href="?route=assets/create&category_id=<?= $category['id'] ?>" class="btn btn-primary">
                                <i class="bi bi-plus me-1"></i>Add First Asset
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Category Statistics Sidebar -->
    <div class="col-lg-4">
        <!-- Statistics Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-graph-up me-2"></i>Category Statistics
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center mb-3">
                    <div class="col-6">
                        <div class="border-end pe-3">
                            <h4 class="mb-1 text-primary"><?= $category['assets_count'] ?? 0 ?></h4>
                            <small class="text-muted">Total Assets</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="ps-3">
                            <h4 class="mb-1 text-success"><?= $category['available_assets'] ?? 0 ?></h4>
                            <small class="text-muted">Available</small>
                        </div>
                    </div>
                </div>
                
                <div class="row text-center mb-3">
                    <div class="col-6">
                        <div class="border-end pe-3">
                            <h4 class="mb-1 text-warning"><?= $category['in_use_assets'] ?? 0 ?></h4>
                            <small class="text-muted">In Use</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="ps-3">
                            <h4 class="mb-1 text-info"><?= $category['maintenance_assets'] ?? 0 ?></h4>
                            <small class="text-muted">Maintenance</small>
                        </div>
                    </div>
                </div>
                
                <div class="text-center pt-3 border-top">
                    <h5 class="mb-1 text-dark">
                        <?php if (!empty($category['total_value'])): ?>
                            ₱<?= number_format($category['total_value'], 2) ?>
                        <?php else: ?>
                            ₱0.00
                        <?php endif; ?>
                    </h5>
                    <small class="text-muted">Total Asset Value</small>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=assets&category_id=<?= $category['id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-box me-2"></i>View Category Assets
                    </a>
                    
                    <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Procurement Officer'])): ?>
                        <a href="?route=assets/create&category_id=<?= $category['id'] ?>" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-plus me-2"></i>Add New Asset
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($auth->hasRole(['System Admin', 'Asset Director'])): ?>
                        <a href="?route=categories/edit&id=<?= $category['id'] ?>" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-pencil me-2"></i>Edit Category
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director'])): ?>
                        <a href="?route=reports/utilization&category_id=<?= $category['id'] ?>" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-graph-up me-2"></i>Category Report
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Category Details Card -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-square me-2"></i>Category Details
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Category Type:</span>
                        <span class="fw-medium">
                            <?= $category['is_consumable'] ? 'Consumable' : 'Equipment' ?>
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Hierarchy Level:</span>
                        <span class="fw-medium">
                            <?= !empty($category['parent_name']) ? 'Subcategory' : 'Top Level' ?>
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Asset Count:</span>
                        <span class="fw-medium"><?= $category['assets_count'] ?? 0 ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Total Value:</span>
                        <span class="fw-medium">
                            <?php if (!empty($category['total_value'])): ?>
                                ₱<?= number_format($category['total_value'], 2) ?>
                            <?php else: ?>
                                ₱0.00
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                
                <?php if (($category['assets_count'] ?? 0) == 0 && $auth->hasRole(['System Admin', 'Asset Director'])): ?>
                <hr class="my-3">
                <div class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteCategory(<?= $category['id'] ?>)">
                        <i class="bi bi-trash me-1"></i>Delete Category
                    </button>
                    <div class="form-text">Only empty categories can be deleted</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Delete category function
function deleteCategory(categoryId) {
    if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
        fetch(`?route=api/categories/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ category_id: categoryId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '?route=categories&message=category_deleted';
            } else {
                alert('Failed to delete category: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the category');
        });
    }
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Category Details - ConstructLink™';
$pageHeader = 'Category: ' . htmlspecialchars($category['name']);
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Categories', 'url' => '?route=categories'],
    ['title' => 'View Details', 'url' => '?route=categories/view&id=' . $category['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
