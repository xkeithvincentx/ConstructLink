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
        <i class="bi bi-tag me-2"></i>Equipment Subtype Management
    </h6>
    <p class="mb-0">
        Manage equipment subtypes with detailed technical specifications. Subtypes define specific variants of equipment types with detailed attributes.
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

<?php if (!in_array($user['role_name'], $roleConfig['equipment/subtypes'] ?? [])): ?>
    <div class="alert alert-danger mt-4">You do not have permission to manage equipment subtypes.</div>
<?php else: ?>

<!-- Action Buttons -->
<div class="mb-3">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubtypeModal">
        <i class="bi bi-plus-circle me-2"></i>Add Subtype
    </button>
    <a href="/equipment/management" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<!-- Filters -->
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
            <div class="col-md-6">
                <label for="equipmentTypeFilter" class="form-label">Filter by Equipment Type</label>
                <select class="form-select" id="equipmentTypeFilter">
                    <option value="">All Equipment Types</option>
                    <?php foreach ($equipmentTypes as $type): ?>
                        <option value="<?= $type['id'] ?>" data-category-id="<?= $type['category_id'] ?>">
                            <?= htmlspecialchars($type['category_name']) ?> - <?= htmlspecialchars($type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Subtypes Table -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="bi bi-tag me-2"></i>Equipment Subtypes
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="subtypesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Equipment Type</th>
                        <th>Subtype Name</th>
                        <th>Material/Size</th>
                        <th>Application</th>
                        <th>Discipline Tags</th>
                        <th class="text-center">Items in Use</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($subtypes)): ?>
                        <?php foreach ($subtypes as $subtype): ?>
                            <tr data-category-id="<?= $subtype['category_id'] ?>" data-equipment-type-id="<?= $subtype['equipment_type_id'] ?>">
                                <td><?= htmlspecialchars($subtype['id']) ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($subtype['category_iso_code']) ?></span>
                                    <br>
                                    <small><?= htmlspecialchars($subtype['category_name']) ?></small>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($subtype['equipment_type_name']) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($subtype['subtype_name']) ?></strong>
                                </td>
                                <td>
                                    <small>
                                        <?php if ($subtype['material_type']): ?>
                                            <strong>Material:</strong> <?= htmlspecialchars($subtype['material_type']) ?><br>
                                        <?php endif; ?>
                                        <?php if ($subtype['size_category']): ?>
                                            <strong>Size:</strong> <?= htmlspecialchars($subtype['size_category']) ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($subtype['application_area'] ?? '') ?></small>
                                </td>
                                <td>
                                    <?php
                                    $tags = json_decode($subtype['discipline_tags'], true);
                                    if (is_array($tags) && !empty($tags)):
                                        foreach (array_slice($tags, 0, 3) as $tag):
                                    ?>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($tag) ?></span>
                                    <?php
                                        endforeach;
                                        if (count($tags) > 3):
                                    ?>
                                        <span class="badge bg-secondary">+<?= count($tags) - 3 ?></span>
                                    <?php endif; endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark"><?= $subtype['assets_count'] ?></span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-info" onclick="viewSubtype(<?= htmlspecialchars(json_encode($subtype)) ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="editSubtype(<?= htmlspecialchars(json_encode($subtype)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($isSystemAdmin): ?>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteSubtype(<?= $subtype['id'] ?>, '<?= htmlspecialchars($subtype['subtype_name']) ?>', <?= $subtype['assets_count'] ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No subtypes found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Subtype Modal -->
<div class="modal fade" id="addSubtypeModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="addSubtypeForm">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Subtype</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_category_select" class="form-label">Category *</label>
                            <select class="form-select" id="add_category_select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= $cat['iso_code'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_equipment_type_id" class="form-label">Equipment Type *</label>
                            <select class="form-select" id="add_equipment_type_id" name="equipment_type_id" required>
                                <option value="">Select Equipment Type</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="add_subtype_name" class="form-label">Subtype Name *</label>
                        <input type="text" class="form-control" id="add_subtype_name" name="subtype_name" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_material_type" class="form-label">Material Type</label>
                            <input type="text" class="form-control" id="add_material_type" name="material_type">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_size_category" class="form-label">Size Category</label>
                            <input type="text" class="form-control" id="add_size_category" name="size_category">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="add_application_area" class="form-label">Application Area</label>
                        <textarea class="form-control" id="add_application_area" name="application_area" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="add_technical_specs" class="form-label">Technical Specifications (JSON)</label>
                        <textarea class="form-control font-monospace" id="add_technical_specs" name="technical_specs" rows="4" placeholder='{"key": "value"}'></textarea>
                        <small class="text-muted">Enter JSON format, e.g., {"material": "Steel", "capacity": "100kg"}</small>
                    </div>

                    <div class="mb-3">
                        <label for="add_discipline_tags" class="form-label">Discipline Tags (JSON Array)</label>
                        <textarea class="form-control font-monospace" id="add_discipline_tags" name="discipline_tags" rows="3" placeholder='["Tag1", "Tag2"]'></textarea>
                        <small class="text-muted">Enter JSON array, e.g., ["Electrical", "Construction"]</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subtype</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subtype Modal -->
<div class="modal fade" id="editSubtypeModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="editSubtypeForm">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Subtype</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_category_select" class="form-label">Category *</label>
                            <select class="form-select" id="edit_category_select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= $cat['iso_code'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_equipment_type_id" class="form-label">Equipment Type *</label>
                            <select class="form-select" id="edit_equipment_type_id" name="equipment_type_id" required>
                                <option value="">Select Equipment Type</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_subtype_name" class="form-label">Subtype Name *</label>
                        <input type="text" class="form-control" id="edit_subtype_name" name="subtype_name" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_material_type" class="form-label">Material Type</label>
                            <input type="text" class="form-control" id="edit_material_type" name="material_type">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_size_category" class="form-label">Size Category</label>
                            <input type="text" class="form-control" id="edit_size_category" name="size_category">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_application_area" class="form-label">Application Area</label>
                        <textarea class="form-control" id="edit_application_area" name="application_area" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit_technical_specs" class="form-label">Technical Specifications (JSON)</label>
                        <textarea class="form-control font-monospace" id="edit_technical_specs" name="technical_specs" rows="4"></textarea>
                        <small class="text-muted">Enter JSON format, e.g., {"material": "Steel", "capacity": "100kg"}</small>
                    </div>

                    <div class="mb-3">
                        <label for="edit_discipline_tags" class="form-label">Discipline Tags (JSON Array)</label>
                        <textarea class="form-control font-monospace" id="edit_discipline_tags" name="discipline_tags" rows="3"></textarea>
                        <small class="text-muted">Enter JSON array, e.g., ["Electrical", "Construction"]</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Subtype</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Subtype Modal -->
<div class="modal fade" id="viewSubtypeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subtype Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewSubtypeBody">
                <!-- Content populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const equipmentTypesByCategoryData = <?= json_encode($equipmentTypesByCategory) ?>;

// Category Filter - Update Equipment Type Filter
document.getElementById('categoryFilter').addEventListener('change', function() {
    const categoryId = this.value;
    updateTableFilter();
});

document.getElementById('equipmentTypeFilter').addEventListener('change', function() {
    updateTableFilter();
});

function updateTableFilter() {
    const categoryId = document.getElementById('categoryFilter').value;
    const equipmentTypeId = document.getElementById('equipmentTypeFilter').value;
    const rows = document.querySelectorAll('#subtypesTable tbody tr');

    // Update equipment type filter options
    const equipmentTypeFilter = document.getElementById('equipmentTypeFilter');
    const allOptions = Array.from(equipmentTypeFilter.options);

    allOptions.forEach(option => {
        if (option.value === '') {
            option.style.display = '';
        } else if (!categoryId || option.dataset.categoryId === categoryId) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });

    // Filter table rows
    rows.forEach(row => {
        let show = true;

        if (categoryId && row.dataset.categoryId !== categoryId) {
            show = false;
        }

        if (equipmentTypeId && row.dataset.equipmentTypeId !== equipmentTypeId) {
            show = false;
        }

        row.style.display = show ? '' : 'none';
    });
}

// Populate Equipment Type Dropdown on Category Change (Add Modal)
document.getElementById('add_category_select').addEventListener('change', function() {
    const categoryId = this.value;
    const equipmentTypeSelect = document.getElementById('add_equipment_type_id');

    equipmentTypeSelect.innerHTML = '<option value="">Select Equipment Type</option>';

    if (categoryId && equipmentTypesByCategoryData[categoryId]) {
        equipmentTypesByCategoryData[categoryId].forEach(type => {
            const option = document.createElement('option');
            option.value = type.id;
            option.textContent = type.name;
            equipmentTypeSelect.appendChild(option);
        });
    }
});

// Populate Equipment Type Dropdown on Category Change (Edit Modal)
document.getElementById('edit_category_select').addEventListener('change', function() {
    const categoryId = this.value;
    const equipmentTypeSelect = document.getElementById('edit_equipment_type_id');

    equipmentTypeSelect.innerHTML = '<option value="">Select Equipment Type</option>';

    if (categoryId && equipmentTypesByCategoryData[categoryId]) {
        equipmentTypesByCategoryData[categoryId].forEach(type => {
            const option = document.createElement('option');
            option.value = type.id;
            option.textContent = type.name;
            equipmentTypeSelect.appendChild(option);
        });
    }
});

// Add Subtype
document.getElementById('addSubtypeForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    // Validate JSON fields
    const technicalSpecs = document.getElementById('add_technical_specs').value;
    const disciplineTags = document.getElementById('add_discipline_tags').value;

    if (technicalSpecs) {
        try {
            JSON.parse(technicalSpecs);
        } catch (e) {
            alert('Technical Specifications must be valid JSON');
            return;
        }
    }

    if (disciplineTags) {
        try {
            JSON.parse(disciplineTags);
        } catch (e) {
            alert('Discipline Tags must be valid JSON array');
            return;
        }
    }

    try {
        const response = await fetch('/equipment/subtypes/create', {
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
        alert('Error adding subtype: ' + error.message);
    }
});

// Edit Subtype
function editSubtype(subtype) {
    document.getElementById('edit_id').value = subtype.id;
    document.getElementById('edit_category_select').value = subtype.category_id;

    // Trigger category change to populate equipment types
    const event = new Event('change');
    document.getElementById('edit_category_select').dispatchEvent(event);

    setTimeout(() => {
        document.getElementById('edit_equipment_type_id').value = subtype.equipment_type_id;
    }, 100);

    document.getElementById('edit_subtype_name').value = subtype.subtype_name;
    document.getElementById('edit_material_type').value = subtype.material_type || '';
    document.getElementById('edit_size_category').value = subtype.size_category || '';
    document.getElementById('edit_application_area').value = subtype.application_area || '';
    document.getElementById('edit_technical_specs').value = subtype.technical_specs || '';
    document.getElementById('edit_discipline_tags').value = subtype.discipline_tags || '';

    new bootstrap.Modal(document.getElementById('editSubtypeModal')).show();
}

document.getElementById('editSubtypeForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    // Validate JSON fields
    const technicalSpecs = document.getElementById('edit_technical_specs').value;
    const disciplineTags = document.getElementById('edit_discipline_tags').value;

    if (technicalSpecs) {
        try {
            JSON.parse(technicalSpecs);
        } catch (e) {
            alert('Technical Specifications must be valid JSON');
            return;
        }
    }

    if (disciplineTags) {
        try {
            JSON.parse(disciplineTags);
        } catch (e) {
            alert('Discipline Tags must be valid JSON array');
            return;
        }
    }

    try {
        const response = await fetch('/equipment/subtypes/update', {
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
        alert('Error updating subtype: ' + error.message);
    }
});

// View Subtype Details
function viewSubtype(subtype) {
    let technicalSpecs = '';
    try {
        const specs = JSON.parse(subtype.technical_specs || '{}');
        technicalSpecs = Object.keys(specs).map(key =>
            `<li><strong>${key}:</strong> ${specs[key]}</li>`
        ).join('');
    } catch (e) {
        technicalSpecs = '<li>Invalid JSON</li>';
    }

    let disciplineTags = '';
    try {
        const tags = JSON.parse(subtype.discipline_tags || '[]');
        disciplineTags = tags.map(tag =>
            `<span class="badge bg-info text-dark me-1">${tag}</span>`
        ).join('');
    } catch (e) {
        disciplineTags = '<span class="badge bg-danger">Invalid JSON</span>';
    }

    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6>Category</h6>
                <p><span class="badge bg-secondary">${subtype.category_iso_code}</span> ${subtype.category_name}</p>
            </div>
            <div class="col-md-6">
                <h6>Equipment Type</h6>
                <p>${subtype.equipment_type_name}</p>
            </div>
        </div>
        <hr>
        <h6>Subtype Name</h6>
        <p><strong>${subtype.subtype_name}</strong></p>

        <div class="row">
            <div class="col-md-6">
                <h6>Material Type</h6>
                <p>${subtype.material_type || '<em>N/A</em>'}</p>
            </div>
            <div class="col-md-6">
                <h6>Size Category</h6>
                <p>${subtype.size_category || '<em>N/A</em>'}</p>
            </div>
        </div>

        <h6>Application Area</h6>
        <p>${subtype.application_area || '<em>N/A</em>'}</p>

        <h6>Technical Specifications</h6>
        <ul>${technicalSpecs || '<li><em>None</em></li>'}</ul>

        <h6>Discipline Tags</h6>
        <p>${disciplineTags || '<em>None</em>'}</p>

        <h6>Usage</h6>
        <p><span class="badge bg-warning text-dark">${subtype.assets_count}</span> items using this subtype</p>
    `;

    document.getElementById('viewSubtypeBody').innerHTML = content;
    new bootstrap.Modal(document.getElementById('viewSubtypeModal')).show();
}

// Delete Subtype
function deleteSubtype(id, name, usageCount) {
    if (usageCount > 0) {
        alert('Cannot delete subtype "' + name + '" because it has ' + usageCount + ' items in use.');
        return;
    }

    if (confirm('Are you sure you want to delete subtype "' + name + '"? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('csrf_token', '<?= CSRFProtection::generateToken() ?>');
        formData.append('id', id);

        fetch('/equipment/subtypes/delete', {
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
            alert('Error deleting subtype: ' + error.message);
        });
    }
}
</script>

<?php endif; ?>

<?php
// Capture the buffered content
$content = ob_get_clean();

// Include the layout with the captured content
$pageTitle = 'Equipment Subtype Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '/'],
    ['title' => 'Equipment Management', 'url' => '/equipment/management'],
    ['title' => 'Subtypes', 'url' => null]
];

include APP_ROOT . '/views/layouts/main.php';
?>
