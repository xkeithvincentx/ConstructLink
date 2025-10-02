<?php
// Start output buffering to capture content
ob_start();

// Get user data for role-based UI
$currentUser = Auth::getInstance()->getCurrentUser();
$userRole = $currentUser['role_name'] ?? 'Guest';
$auth = Auth::getInstance();
$isOwnProfile = ($currentUser['id'] ?? 0) == ($user['id'] ?? 0);
?>

<div class="row">
    <!-- User Profile Information -->
    <div class="col-lg-8">
        <!-- Basic Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-person me-1"></i>Profile Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 text-center">
                        <div class="avatar-circle bg-primary text-white mb-3" style="width: 80px; height: 80px; line-height: 80px; font-size: 2rem;">
                            <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 2)) ?>
                        </div>
                    </div>
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">Full Name:</dt>
                                    <dd class="col-sm-8"><?= htmlspecialchars($user['full_name'] ?? 'N/A') ?></dd>
                                    
                                    <dt class="col-sm-4">Username:</dt>
                                    <dd class="col-sm-8">
                                        <code><?= htmlspecialchars($user['username'] ?? 'N/A') ?></code>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Email:</dt>
                                    <dd class="col-sm-8">
                                        <?php if (!empty($user['email'])): ?>
                                            <a href="mailto:<?= htmlspecialchars($user['email']) ?>">
                                                <?= htmlspecialchars($user['email']) ?>
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Phone:</dt>
                                    <dd class="col-sm-8">
                                        <?php if (!empty($user['phone'])): ?>
                                            <a href="tel:<?= htmlspecialchars($user['phone']) ?>">
                                                <?= htmlspecialchars($user['phone']) ?>
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">Role:</dt>
                                    <dd class="col-sm-8">
                                        <span class="badge bg-primary"><?= htmlspecialchars($user['role_name'] ?? 'No Role') ?></span>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Department:</dt>
                                    <dd class="col-sm-8"><?= htmlspecialchars($user['department'] ?? 'N/A') ?></dd>
                                    
                                    <dt class="col-sm-4">Project:</dt>
                                    <dd class="col-sm-8">
                                        <?php if (!empty($user['project_name'])): ?>
                                            <span class="badge bg-info">
                                                <?= htmlspecialchars($user['project_name']) ?>
                                                <?php if (!empty($user['project_code'])): ?>
                                                    (<?= htmlspecialchars($user['project_code']) ?>)
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No Project Assignment</span>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Status:</dt>
                                    <dd class="col-sm-8">
                                        <?php if ($user['is_active'] ?? 0): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()): ?>
                                            <span class="badge bg-warning ms-1">Locked</span>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">User ID:</dt>
                                    <dd class="col-sm-8">
                                        <code>#<?= $user['id'] ?? 'N/A' ?></code>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-shield-check me-1"></i>Account Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Created:</dt>
                            <dd class="col-sm-7">
                                <?= isset($user['created_at']) ? date('M j, Y g:i A', strtotime($user['created_at'])) : 'N/A' ?>
                            </dd>
                            
                            <dt class="col-sm-5">Last Updated:</dt>
                            <dd class="col-sm-7">
                                <?= isset($user['updated_at']) && $user['updated_at'] ? date('M j, Y g:i A', strtotime($user['updated_at'])) : 'Never' ?>
                            </dd>
                            
                            <dt class="col-sm-5">Last Login:</dt>
                            <dd class="col-sm-7">
                                <?php if (isset($user['last_login']) && $user['last_login']): ?>
                                    <span class="text-success">
                                        <?= date('M j, Y g:i A', strtotime($user['last_login'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-6">Failed Logins:</dt>
                            <dd class="col-sm-6">
                                <?php if (($user['failed_login_attempts'] ?? 0) > 0): ?>
                                    <span class="text-warning"><?= $user['failed_login_attempts'] ?></span>
                                <?php else: ?>
                                    <span class="text-success">0</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-6">Account Locked:</dt>
                            <dd class="col-sm-6">
                                <?php if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()): ?>
                                    <span class="text-danger">
                                        Until <?= date('M j, Y g:i A', strtotime($user['locked_until'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">No</span>
                                <?php endif; ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Permissions -->
        <?php if (!empty($user['permissions'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-key me-1"></i>Role Permissions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        $permissions = is_array($user['permissions']) ? $user['permissions'] : [];
                        $permissionChunks = array_chunk($permissions, ceil(count($permissions) / 3));
                        ?>
                        <?php foreach ($permissionChunks as $chunk): ?>
                            <div class="col-md-4">
                                <?php foreach ($chunk as $permission): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        <small><?= htmlspecialchars(str_replace('_', ' ', ucwords($permission, '_'))) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <?php if (!empty($userActivities)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-activity me-1"></i>Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach (array_slice($userActivities, 0, 10) as $activity): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title"><?= htmlspecialchars($activity['action'] ?? 'Unknown Action') ?></h6>
                                    <p class="timeline-text text-muted">
                                        <?= htmlspecialchars($activity['description'] ?? 'No description available') ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= isset($activity['created_at']) ? date('M j, Y g:i A', strtotime($activity['created_at'])) : 'Unknown time' ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <?php if ($auth->hasRole(['System Admin'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightning me-1"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="?route=users/edit&id=<?= $user['id'] ?? 0 ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil me-1"></i>Edit User
                        </a>
                        
                        <a href="?route=users/reset-password&id=<?= $user['id'] ?? 0 ?>" 
                           class="btn btn-outline-warning btn-sm"
                           onclick="return confirm('Are you sure you want to reset this user\'s password?')">
                            <i class="bi bi-key me-1"></i>Reset Password
                        </a>
                        
                        <?php if ($user['is_active'] ?? 0): ?>
                            <button type="button" 
                                    class="btn btn-outline-secondary btn-sm"
                                    onclick="toggleUserStatus(<?= $user['id'] ?? 0 ?>, false)">
                                <i class="bi bi-person-x me-1"></i>Deactivate Account
                            </button>
                        <?php else: ?>
                            <button type="button" 
                                    class="btn btn-outline-success btn-sm"
                                    onclick="toggleUserStatus(<?= $user['id'] ?? 0 ?>, true)">
                                <i class="bi bi-person-check me-1"></i>Activate Account
                            </button>
                        <?php endif; ?>
                        
                        <?php if (($user['id'] ?? 0) != $_SESSION['user_id']): ?>
                            <hr>
                            <a href="?route=users/delete&id=<?= $user['id'] ?? 0 ?>" 
                               class="btn btn-outline-danger btn-sm"
                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                <i class="bi bi-trash me-1"></i>Delete User
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- User Statistics -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-graph-up me-1"></i>User Statistics
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h6 class="text-muted">Days Active</h6>
                            <h4 class="mb-0">
                                <?php
                                if (isset($user['created_at'])) {
                                    $daysActive = floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24));
                                    echo $daysActive;
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </h4>
                        </div>
                    </div>
                    <div class="col-6">
                        <h6 class="text-muted">Login Attempts</h6>
                        <h4 class="mb-0 <?= ($user['failed_login_attempts'] ?? 0) > 0 ? 'text-warning' : 'text-success' ?>">
                            <?= $user['failed_login_attempts'] ?? 0 ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Information -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-shield me-1"></i>Role Information
                </h6>
            </div>
            <div class="card-body">
                <h6><?= htmlspecialchars($user['role_name'] ?? 'No Role') ?></h6>
                <?php if (!empty($user['role_description'])): ?>
                    <p class="text-muted small"><?= htmlspecialchars($user['role_description']) ?></p>
                <?php endif; ?>
                
                <div class="mt-3">
                    <small class="text-muted">
                        <strong>Permissions:</strong> 
                        <?= count($user['permissions'] ?? []) ?> granted
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -31px;
    top: 15px;
    width: 2px;
    height: calc(100% + 5px);
    background-color: #dee2e6;
}

.timeline-title {
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.timeline-text {
    font-size: 0.85rem;
    margin-bottom: 5px;
}
</style>

<script>
// Toggle user status
function toggleUserStatus(userId, activate) {
    const action = activate ? 'activate' : 'deactivate';
    const message = activate ? 'activate this user' : 'deactivate this user';
    
    if (confirm(`Are you sure you want to ${message}?`)) {
        fetch(`?route=users/toggle-status&id=${userId}&activate=${activate ? 1 : 0}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
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
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'User Profile - ConstructLink™';
$pageHeader = htmlspecialchars($user['full_name'] ?? 'Unknown User');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Users', 'url' => '?route=users'],
    ['title' => 'View Profile', 'url' => '?route=users/view&id=' . ($user['id'] ?? 0)]
];

// Page actions
$pageActions = '';
if ($auth->hasRole(['System Admin']) || $isOwnProfile) {
    $pageActions .= '<a href="?route=users/edit&id=' . ($user['id'] ?? 0) . '" class="btn btn-outline-primary me-2">
        <i class="bi bi-pencil me-1"></i>Edit Profile
    </a>';
}
$pageActions .= '<a href="?route=users" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Back to Users
</a>';

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
