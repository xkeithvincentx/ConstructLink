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
        <i class="bi bi-list-task me-2"></i>Equipment Type Management
    </h6>
    <p class="mb-0">
        Manage equipment types within categories. Equipment types define the specific kinds of items that can be tracked in the system.
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

<?php if (!in_array($user['role_name'], $roleConfig['equipment/types'] ?? [])): ?>
    <div class="alert alert-danger mt-4">You do not have permission to manage equipment types.</div>
<?php else: ?>

<!-- Action Buttons -->
<div class="mb-3">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEquipmentTypeModal">
        <i class="bi bi-plus-circle me-2"></i>Add Equipment Type
    </button>
    <a href="/equipment/management" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <label for="categoryFilter" class="form-label">Filter by Category</label>
                <select class="form-select" id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= $cat['iso_code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Equipment Types Table -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="bi bi-list-task me-2"></i>Equipment Types
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="equipmentTypesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Equipment Type Name</th>
                        <th>Description</th>
                        <th class="text-center">Subtypes</th>
                        <th class="text-center">Items in Use</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($equipmentTypes)): ?>
                        <?php foreach ($equipmentTypes as $type): ?>
                            <tr data-category-id="<?= $type['category_id'] ?>">
                                <td><?= htmlspecialchars($type['id']) ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($type['category_iso_code']) ?></span>
                                    <br>
                                    <small><?= htmlspecialchars($type['category_name']) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($type['name']) ?></strong>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($type['description']) ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= $type['subtypes_count'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark"><?= $type['assets_count'] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($type['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-warning" onclick="editEquipmentType(<?= htmlspecialchars(json_encode($type)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($isSystemAdmin): ?>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteEquipmentType(<?= $type['id'] ?>, '<?= htmlspecialchars($type['name']) ?>', <?= $type['assets_count'] ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No equipment types found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Equipment Type Modal -->
<div class="modal fade" id="addEquipmentTypeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addEquipmentTypeForm">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Equipment Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">

                    <div class="mb-3">
                        <label for="add_category_id" class="form-label">Category *</label>
                        <select class="form-select" id="add_category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= $cat['iso_code'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="add_name" class="form-label">Equipment Type Name *</label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="add_description" class="form-label">Description</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="add_is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="add_is_active">
                            Active
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Equipment Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Equipment Type Modal -->
<div class="modal fade" id="editEquipmentTypeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editEquipmentTypeForm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Equipment Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="mb-3">
                        <label for="edit_category_id" class="form-label">Category *</label>
                        <select class="form-select" id="edit_category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= $cat['iso_code'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Equipment Type Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                        <label class="form-check-label" for="edit_is_active">
                            Active
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Equipment Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Category Filter
document.getElementById('categoryFilter').addEventListener('change', function() {
    const categoryId = this.value;
    const rows = document.querySelectorAll('#equipmentTypesTable tbody tr');

    rows.forEach(row => {
        if (!categoryId || row.dataset.categoryId === categoryId) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Add Equipment Type
document.getElementById('addEquipmentTypeForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        const response = await fetch('/equipment/types/create', {
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
        alert('Error adding equipment type: ' + error.message);
    }
});

// Edit Equipment Type
function editEquipmentType(type) {
    document.getElementById('edit_id').value = type.id;
    document.getElementById('edit_category_id').value = type.category_id;
    document.getElementById('edit_name').value = type.name;
    document.getElementById('edit_description').value = type.description || '';
    document.getElementById('edit_is_active').checked = type.is_active == 1;

    new bootstrap.Modal(document.getElementById('editEquipmentTypeModal')).show();
}

document.getElementById('editEquipmentTypeForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        const response = await fetch('/equipment/types/update', {
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
        alert('Error updating equipment type: ' + error.message);
    }
});

// Delete Equipment Type
function deleteEquipmentType(id, name, usageCount) {
    if (usageCount > 0) {
        alert('Cannot delete equipment type "' + name + '" because it has ' + usageCount + ' items in use.');
        return;
    }

    if (confirm('Are you sure you want to delete equipment type "' + name + '"? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('csrf_token', '<?= CSRFProtection::generateToken() ?>');
        formData.append('id', id);

        fetch('/equipment/types/delete', {
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
            alert('Error deleting equipment type: ' + error.message);
        });
    }
}
</script>

<?php endif; ?>

<?php
// Capture the buffered content
$content = ob_get_clean();

// Include the layout with the captured content
$pageTitle = 'Equipment Type Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '/'],
    ['title' => 'Equipment Management', 'url' => '/equipment/management'],
    ['title' => 'Equipment Types', 'url' => null]
];

include APP_ROOT . '/views/layouts/main.php';
?>
