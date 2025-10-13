<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
$isSystemAdmin = $user['role_name'] === 'System Admin';
?>

<!-- Info Alert -->
<div class="alert alert-info" role="alert">
    <h6 class="alert-heading">
        <i class="bi bi-folder-fill me-2"></i>Category Management
    </h6>
    <p class="mb-0">
        Manage item categories. Categories are the highest level of equipment classification and define how items are organized throughout the system.
    </p>
</div>

<!-- Messages -->
<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Error:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!in_array($user['role_name'], $roleConfig['equipment/categories'] ?? [])): ?>
    <div class="alert alert-danger mt-4">You do not have permission to manage categories.</div>
<?php else: ?>

<!-- Action Buttons -->
<div class="mb-3">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-circle me-2"></i>Add Category
    </button>
    <a href="/equipment/management" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<!-- Categories Table -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="bi bi-folder-fill me-2"></i>Categories
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="categoriesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>ISO Code</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-center">Equipment Types</th>
                        <th class="text-center">Items in Use</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['id']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($category['name']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($category['iso_code']) ?></span>
                                </td>
                                <td>
                                    <?php if ($category['is_consumable']): ?>
                                        <span class="badge bg-warning text-dark">Consumable</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary"><?= ucfirst($category['asset_type']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($category['description']) ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?= $category['equipment_types_count'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= $category['assets_count'] ?></span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-warning" onclick="editCategory(<?= htmlspecialchars(json_encode($category)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($isSystemAdmin): ?>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>', <?= $category['assets_count'] ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No categories found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addCategoryForm">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="add_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="add_name" name="name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="add_iso_code" class="form-label">ISO Code *</label>
                            <input type="text" class="form-control" id="add_iso_code" name="iso_code" maxlength="10" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="add_description" class="form-label">Description</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_asset_type" class="form-label">Item Type *</label>
                            <select class="form-select" id="add_asset_type" name="asset_type" required>
                                <option value="capital">Capital</option>
                                <option value="semi-expendable">Semi-Expendable</option>
                                <option value="consumable">Consumable</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Configuration</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="add_is_consumable" name="is_consumable" value="1">
                                <label class="form-check-label" for="add_is_consumable">
                                    Is Consumable
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="add_generates_assets" name="generates_assets" value="1" checked>
                                <label class="form-check-label" for="add_generates_assets">
                                    Generates Items
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="add_depreciation_applicable" name="depreciation_applicable" value="1">
                                <label class="form-check-label" for="add_depreciation_applicable">
                                    Depreciation Applicable
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="add_capitalization_threshold" class="form-label">Capitalization Threshold (₱)</label>
                        <input type="number" class="form-control" id="add_capitalization_threshold" name="capitalization_threshold" step="0.01" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editCategoryForm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="edit_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_iso_code" class="form-label">ISO Code *</label>
                            <input type="text" class="form-control" id="edit_iso_code" name="iso_code" maxlength="10" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_asset_type" class="form-label">Item Type *</label>
                            <select class="form-select" id="edit_asset_type" name="asset_type" required>
                                <option value="capital">Capital</option>
                                <option value="semi-expendable">Semi-Expendable</option>
                                <option value="consumable">Consumable</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Configuration</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_consumable" name="is_consumable" value="1">
                                <label class="form-check-label" for="edit_is_consumable">
                                    Is Consumable
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_generates_assets" name="generates_assets" value="1">
                                <label class="form-check-label" for="edit_generates_assets">
                                    Generates Items
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_depreciation_applicable" name="depreciation_applicable" value="1">
                                <label class="form-check-label" for="edit_depreciation_applicable">
                                    Depreciation Applicable
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_capitalization_threshold" class="form-label">Capitalization Threshold (₱)</label>
                        <input type="number" class="form-control" id="edit_capitalization_threshold" name="capitalization_threshold" step="0.01" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Add Category
document.getElementById('addCategoryForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        const response = await fetch('/equipment/categories/create', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error adding category: ' + error.message);
    }
});

// Edit Category
function editCategory(category) {
    document.getElementById('edit_id').value = category.id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_iso_code').value = category.iso_code;
    document.getElementById('edit_description').value = category.description || '';
    document.getElementById('edit_asset_type').value = category.asset_type;
    document.getElementById('edit_is_consumable').checked = category.is_consumable == 1;
    document.getElementById('edit_generates_assets').checked = category.generates_assets == 1;
    document.getElementById('edit_depreciation_applicable').checked = category.depreciation_applicable == 1;
    document.getElementById('edit_capitalization_threshold').value = category.capitalization_threshold || '';

    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

document.getElementById('editCategoryForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        const response = await fetch('/equipment/categories/update', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error updating category: ' + error.message);
    }
});

// Delete Category
function deleteCategory(id, name, usageCount) {
    if (usageCount > 0) {
        alert('Cannot delete category "' + name + '" because it has ' + usageCount + ' items in use.');
        return;
    }

    if (confirm('Are you sure you want to delete category "' + name + '"? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('csrf_token', '<?= CSRFProtection::generateToken() ?>');
        formData.append('id', id);

        fetch('/equipment/categories/delete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        })
        .catch(error => {
            alert('Error deleting category: ' + error.message);
        });
    }
}
</script>

<?php endif; ?>

<?php
// Capture the buffered content
$content = ob_get_clean();

// Include the layout with the captured content
$pageTitle = 'Category Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '/'],
    ['title' => 'Equipment Management', 'url' => '/equipment/management'],
    ['title' => 'Categories', 'url' => null]
];

include APP_ROOT . '/views/layouts/main.php';
?>
