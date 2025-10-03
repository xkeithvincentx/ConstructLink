<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <!-- Primary Actions (Left) -->
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Primary actions">
        <?php if ($auth->hasRole(['System Admin', 'Asset Director'])): ?>
            <a href="?route=categories/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">Add Category</span>
                <span class="d-sm-none">Add</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Categories</h6>
                        <h3 class="mb-0"><?= count($categories ?? []) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-tags display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">🔧 Capital Assets</h6>
                        <h3 class="mb-0"><?= count(array_filter($categories ?? [], fn($c) => ($c['asset_type'] ?? 'capital') === 'capital')) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-gear display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">📦 Inventory</h6>
                        <h3 class="mb-0"><?= count(array_filter($categories ?? [], fn($c) => ($c['asset_type'] ?? 'capital') === 'inventory')) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-box-seam display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">💰 Direct Expenses</h6>
                        <h3 class="mb-0"><?= count(array_filter($categories ?? [], fn($c) => ($c['asset_type'] ?? 'capital') === 'expense')) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-receipt display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=categories" class="row g-3">
            <div class="col-md-4">
                <label for="type" class="form-label">Asset Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Asset Types</option>
                    <option value="capital" <?= ($_GET['type'] ?? '') === 'capital' ? 'selected' : '' ?>>🔧 Capital Assets</option>
                    <option value="inventory" <?= ($_GET['type'] ?? '') === 'inventory' ? 'selected' : '' ?>>📦 Inventory/Materials</option>
                    <option value="expense" <?= ($_GET['type'] ?? '') === 'expense' ? 'selected' : '' ?>>💰 Direct Expenses</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by category name..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=categories" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Categories Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Asset Categories</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                <i class="bi bi-file-earmark-excel me-1"></i>Export
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($categories)): ?>
            <div class="text-center py-5">
                <i class="bi bi-tags display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No categories found</h5>
                <p class="text-muted">Try adjusting your filters or create a new category.</p>
                <?php if ($auth->hasRole(['System Admin', 'Asset Director'])): ?>
                    <a href="?route=categories/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create Category
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="categoriesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Business Type</th>
                            <th>Asset Generation</th>
                            <th>Assets Count</th>
                            <th>Threshold</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <a href="?route=categories/view&id=<?= $category['id'] ?>" class="text-decoration-none">
                                        #<?= $category['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <?php 
                                            $assetType = $category['asset_type'] ?? 'capital';
                                            switch($assetType) {
                                                case 'capital': echo '<i class="bi bi-gear text-success" title="Capital Asset"></i>'; break;
                                                case 'inventory': echo '<i class="bi bi-box-seam text-info" title="Inventory"></i>'; break;
                                                case 'expense': echo '<i class="bi bi-receipt text-warning" title="Direct Expense"></i>'; break;
                                                default: echo '<i class="bi bi-gear text-success"></i>';
                                            }
                                            ?>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($category['name']) ?></div>
                                            <?php if (!empty($category['business_description'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars(substr($category['business_description'], 0, 50)) ?><?= strlen($category['business_description']) > 50 ? '...' : '' ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $assetType = $category['asset_type'] ?? 'capital';
                                    switch($assetType) {
                                        case 'capital': 
                                            echo '<span class="badge bg-primary">🔧 Capital</span>';
                                            break;
                                        case 'inventory': 
                                            echo '<span class="badge bg-info">📦 Inventory</span>';
                                            break;
                                        case 'expense': 
                                            echo '<span class="badge bg-warning text-dark">💰 Expense</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Unknown</span>';
                                    }
                                    ?>
                                    <?php if ($category['is_consumable']): ?>
                                        <br><small class="badge bg-light text-dark mt-1">Consumable</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($category['generates_assets'] ?? true): ?>
                                        <span class="badge bg-success">✓ Creates Assets</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">✗ Direct Expense</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark">
                                        <?= $category['assets_count'] ?? 0 ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if (($category['capitalization_threshold'] ?? 0) > 0): ?>
                                        <small class="text-success">$<?= number_format($category['capitalization_threshold'], 2) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">No limit</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=categories/view&id=<?= $category['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($auth->hasRole(['System Admin', 'Asset Director'])): ?>
                                            <a href="?route=categories/edit&id=<?= $category['id'] ?>" 
                                               class="btn btn-outline-warning" title="Edit Category">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <?php if (($category['assets_count'] ?? 0) == 0): ?>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteCategory(<?= $category['id'] ?>)" title="Delete Category">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-danger disabled" 
                                                        title="Cannot delete category with assets">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Categories pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Delete category
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
                location.reload();
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

// Export to Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=categories&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Category Management - ConstructLink™';
$pageHeader = 'Category Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Categories', 'url' => '?route=categories']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
