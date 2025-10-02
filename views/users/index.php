<?php
// Start output buffering to capture content
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
$auth = Auth::getInstance();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-people me-2"></i>
        User Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($auth->hasRole(['System Admin'])): ?>
            <a href="?route=users/create" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i>Add User
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
                        <h6 class="card-title">Total Users</h6>
                        <h3 class="mb-0"><?= count($users ?? []) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people display-6"></i>
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
                        <h6 class="card-title">Active Users</h6>
                        <h3 class="mb-0"><?= count(array_filter($users ?? [], fn($u) => $u['is_active'])) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-person-check display-6"></i>
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
                        <h6 class="card-title">Inactive Users</h6>
                        <h3 class="mb-0"><?= count(array_filter($users ?? [], fn($u) => !$u['is_active'])) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-person-x display-6"></i>
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
                        <h6 class="card-title">System Admins</h6>
                        <h3 class="mb-0"><?= count(array_filter($users ?? [], fn($u) => $u['role_name'] === 'System Admin')) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-shield-check display-6"></i>
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
        <form method="GET" action="?route=users" class="row g-3">
            <div class="col-md-3">
                <label for="role_id" class="form-label">Role</label>
                <select class="form-select" id="role_id" name="role_id">
                    <option value="">All Roles</option>
                    <?php if (isset($roles)): ?>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" <?= ($_GET['role_id'] ?? '') == $role['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="project_id" class="form-label">Project</label>
                <select class="form-select" id="project_id" name="project_id">
                    <option value="">All Projects</option>
                    <?php if (isset($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" <?= ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="is_active" class="form-label">Status</label>
                <select class="form-select" id="is_active" name="is_active">
                    <option value="">All Statuses</option>
                    <option value="1" <?= ($_GET['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($_GET['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by name, username, or email..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=users" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">System Users</h6>
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
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No users found</h5>
                <p class="text-muted">No users match your current filters. Try adjusting your search criteria.</p>
                <?php if ($auth->hasRole(['System Admin'])): ?>
                    <a href="?route=users/create" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i>Add First User
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Current Project</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <div class="avatar-circle bg-primary text-white">
                                                <?= strtoupper(substr($user['full_name'], 0, 2)) ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-medium">
                                                <a href="?route=users/view&id=<?= $user['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($user['full_name']) ?>
                                                </a>
                                            </div>
                                            <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($user['role_name'] ?? 'No Role') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($user['current_project_name'])): ?>
                                        <div class="fw-medium"><?= htmlspecialchars($user['current_project_name']) ?></div>
                                        <?php if (!empty($user['project_location'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($user['project_location']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No Project</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($user['email']) ?></div>
                                        <?php if (!empty($user['phone'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($user['phone']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()): ?>
                                        <span class="badge bg-warning ms-1">Locked</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['failed_login_attempts'] > 0): ?>
                                        <small class="text-warning d-block">
                                            <?= $user['failed_login_attempts'] ?> failed attempts
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['last_login']): ?>
                                        <div>
                                            <div class="fw-medium"><?= date('M j, Y', strtotime($user['last_login'])) ?></div>
                                            <small class="text-muted"><?= date('g:i A', strtotime($user['last_login'])) ?></small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= date('M j, Y', strtotime($user['created_at'])) ?></div>
                                        <small class="text-muted"><?= date('g:i A', strtotime($user['created_at'])) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=users/view&id=<?= $user['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Profile">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($auth->hasRole(['System Admin'])): ?>
                                            <a href="?route=users/edit&id=<?= $user['id'] ?>" 
                                               class="btn btn-outline-secondary" title="Edit User">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <?php if ($user['is_active']): ?>
                                                    <button type="button" class="btn btn-outline-warning" 
                                                            onclick="toggleUserStatus(<?= $user['id'] ?>, false)"
                                                            title="Deactivate User">
                                                        <i class="bi bi-person-x"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-success" 
                                                            onclick="toggleUserStatus(<?= $user['id'] ?>, true)"
                                                            title="Activate User">
                                                        <i class="bi bi-person-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button type="button" class="btn btn-outline-info" 
                                                        onclick="resetUserPassword(<?= $user['id'] ?>)"
                                                        title="Reset Password">
                                                    <i class="bi bi-key"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['full_name']) ?>')"
                                                        title="Delete User">
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
                <nav aria-label="Users pagination" class="mt-4">
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

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}
</style>

<script>
// Export to Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=users&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Toggle user status
function toggleUserStatus(userId, activate) {
    const action = activate ? 'activate' : 'deactivate';
    const message = activate ? 'activate this user' : 'deactivate this user';
    
    if (confirm(`Are you sure you want to ${message}?`)) {
        fetch(`?route=users/toggle-status&id=${userId}&activate=${activate ? 1 : 0}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating user status');
        });
    }
}

// Reset user password
function resetUserPassword(userId) {
    if (confirm('Are you sure you want to reset this user\'s password? A new temporary password will be generated.')) {
        fetch(`?route=users/reset-password&id=${userId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password reset successfully. New password: ' + data.new_password);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while resetting password');
        });
    }
}

// Delete user
function deleteUser(userId, userName) {
    if (confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
        if (confirm('This will permanently delete the user and all associated data. Are you absolutely sure?')) {
            window.location.href = `?route=users/delete&id=${userId}`;
        }
    }
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'User Management - ConstructLink™';
$pageHeader = 'User Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Users', 'url' => '?route=users']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
