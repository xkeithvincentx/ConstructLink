<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-building me-2"></i>
        <?= htmlspecialchars($project['name']) ?>
        <?php if ($project['is_active']): ?>
            <span class="badge bg-success ms-2">Active</span>
        <?php else: ?>
            <span class="badge bg-secondary ms-2">Inactive</span>
        <?php endif; ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="?route=projects" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Projects
            </a>
        </div>
        
        <?php if ($auth->hasRole(['System Admin'])): ?>
            <div class="btn-group me-2">
                <a href="?route=projects/edit&id=<?= $project['id'] ?>" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i>Edit Project
                </a>
            </div>
        <?php endif; ?>
        
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-gear me-1"></i>Actions
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" href="?route=assets&project_id=<?= $project['id'] ?>">
                        <i class="bi bi-box me-2"></i>View Assets
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="?route=withdrawals&project_id=<?= $project['id'] ?>">
                        <i class="bi bi-arrow-down-circle me-2"></i>View Withdrawals
                    </a>
                </li>
                <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                    <li>
                        <a class="dropdown-item" href="?route=procurement-orders&project_id=<?= $project['id'] ?>">
                            <i class="bi bi-cart me-2"></i>View Procurement
                        </a>
                    </li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director'])): ?>
                    <li>
                        <a class="dropdown-item" href="?route=projects/export&project_id=<?= $project['id'] ?>">
                            <i class="bi bi-download me-2"></i>Export Data
                        </a>
                    </li>
                <?php endif; ?>
                <li>
                    <a class="dropdown-item" href="#" onclick="printProjectDetails()">
                        <i class="bi bi-printer me-2"></i>Print Details
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php if ($_GET['message'] === 'project_created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Project created successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'project_updated'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Project updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Enhanced Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Assets</h6>
                        <h3 class="mb-0"><?= $project['assets_count'] ?? 0 ?></h3>
                        <small class="opacity-75">Assigned to project</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-box display-6"></i>
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
                        <h6 class="card-title">Available Assets</h6>
                        <h3 class="mb-0"><?= $project['available_assets'] ?? 0 ?></h3>
                        <small class="opacity-75">Ready for use</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle display-6"></i>
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
                        <h6 class="card-title">In Use Assets</h6>
                        <h3 class="mb-0"><?= $project['in_use_assets'] ?? 0 ?></h3>
                        <small class="opacity-75">Currently deployed</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-arrow-down-circle display-6"></i>
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
                        <h6 class="card-title">
                            <?php if ($showFinancialData): ?>
                                Total Value
                            <?php else: ?>
                                Team Members
                            <?php endif; ?>
                        </h6>
                        <h3 class="mb-0">
                            <?php if ($showFinancialData): ?>
                                ₱<?= number_format($project['total_asset_value'] ?? 0, 2) ?>
                            <?php else: ?>
                                <?= $project['assigned_users_count'] ?? 0 ?>
                            <?php endif; ?>
                        </h3>
                        <small class="opacity-75">
                            <?php if ($showFinancialData): ?>
                                Asset investments
                            <?php else: ?>
                                Assigned users
                            <?php endif; ?>
                        </small>
                    </div>
                    <div class="align-self-center">
                        <?php if ($showFinancialData): ?>
                            <i class="bi bi-currency-dollar display-6"></i>
                        <?php else: ?>
                            <i class="bi bi-people display-6"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="row">
    <!-- Project Details -->
    <div class="col-lg-8">
        <!-- Basic Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Project Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Project Code:</dt>
                            <dd class="col-sm-8">
                                <code><?= htmlspecialchars($project['code']) ?></code>
                            </dd>
                            
                            <dt class="col-sm-4">Location:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($project['location']) ?></dd>
                            
                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8">
                                <?php if ($project['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-4">Created:</dt>
                            <dd class="col-sm-8">
                                <?= date('M j, Y g:i A', strtotime($project['created_at'])) ?>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Manager:</dt>
                            <dd class="col-sm-8">
                                <?php if ($project['project_manager_name']): ?>
                                    <div>
                                        <i class="bi bi-person-badge me-1"></i>
                                        <?= htmlspecialchars($project['project_manager_name']) ?>
                                    </div>
                                    <?php if ($project['project_manager_email']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-envelope me-1"></i>
                                            <?= htmlspecialchars($project['project_manager_email']) ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </dd>
                            
                            <?php if ($showFinancialData && $project['budget']): ?>
                                <dt class="col-sm-4">Budget:</dt>
                                <dd class="col-sm-8">
                                    <strong>₱<?= number_format($project['budget'], 2) ?></strong>
                                </dd>
                            <?php endif; ?>
                            
                            <?php if ($project['start_date']): ?>
                                <dt class="col-sm-4">Start Date:</dt>
                                <dd class="col-sm-8"><?= date('M j, Y', strtotime($project['start_date'])) ?></dd>
                            <?php endif; ?>
                            
                            <?php if ($project['end_date']): ?>
                                <dt class="col-sm-4">End Date:</dt>
                                <dd class="col-sm-8">
                                    <?= date('M j, Y', strtotime($project['end_date'])) ?>
                                    <?php if (strtotime($project['end_date']) < time() && $project['is_active']): ?>
                                        <span class="badge bg-warning ms-1">Overdue</span>
                                    <?php endif; ?>
                                </dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
                
                <?php if ($project['description']): ?>
                    <div class="mt-3">
                        <h6>Description:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Project Activity -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-activity me-2"></i>Project Activity
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-primary"><?= $projectActivity['total_withdrawals'] ?? 0 ?></div>
                            <small class="text-muted">Total Withdrawals</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-warning"><?= $projectActivity['pending_withdrawals'] ?? 0 ?></div>
                            <small class="text-muted">Pending Withdrawals</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-info"><?= $projectActivity['procurement_orders'] ?? 0 ?></div>
                            <small class="text-muted">Procurement Orders</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h4 text-success"><?= $projectActivity['requests_count'] ?? 0 ?></div>
                            <small class="text-muted">Requests</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Assets -->
        <?php if (!empty($projectAssets)): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-box me-2"></i>Recent Assets
                    </h6>
                    <a href="?route=assets&project_id=<?= $project['id'] ?>" class="btn btn-sm btn-outline-primary">
                        View All Assets
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Asset</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <?php if ($showFinancialData): ?>
                                        <th>Value</th>
                                    <?php endif; ?>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($projectAssets, 0, 5) as $asset): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <div class="fw-medium"><?= htmlspecialchars($asset['name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($asset['ref']) ?></small>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php
                                            $statusClasses = [
                                                'available' => 'bg-success',
                                                'in_use' => 'bg-warning',
                                                'under_maintenance' => 'bg-info',
                                                'retired' => 'bg-secondary'
                                            ];
                                            $statusClass = $statusClasses[$asset['status']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?= $statusClass ?>">
                                                <?= ucfirst(str_replace('_', ' ', $asset['status'])) ?>
                                            </span>
                                        </td>
                                        <?php if ($showFinancialData): ?>
                                            <td>
                                                <?php if ($asset['acquisition_cost']): ?>
                                                    ₱<?= number_format($asset['acquisition_cost'], 2) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <a href="?route=assets/view&id=<?= $asset['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Withdrawals -->
        <?php if (!empty($recentWithdrawals['data'])): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-arrow-down-circle me-2"></i>Recent Withdrawals
                    </h6>
                    <a href="?route=withdrawals&project_id=<?= $project['id'] ?>" class="btn btn-sm btn-outline-primary">
                        View All Withdrawals
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Asset</th>
                                    <th>Receiver</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentWithdrawals['data'] as $withdrawal): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <div class="fw-medium"><?= htmlspecialchars($withdrawal['asset_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($withdrawal['asset_ref']) ?></small>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($withdrawal['receiver_name']) ?></td>
                                        <td>
                                            <?php
                                            $statusClasses = [
                                                'pending' => 'bg-warning',
                                                'released' => 'bg-info',
                                                'returned' => 'bg-success',
                                                'canceled' => 'bg-secondary'
                                            ];
                                            $statusClass = $statusClasses[$withdrawal['status']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?= $statusClass ?>">
                                                <?= ucfirst($withdrawal['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= date('M j, Y', strtotime($withdrawal['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <a href="?route=withdrawals/view&id=<?= $withdrawal['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Assigned Team Members -->
        <?php if (!empty($projectUsers)): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>Team Members
                    </h6>
                    <?php if ($auth->hasRole(['System Admin', 'Project Manager'])): ?>
                        <button class="btn btn-sm btn-outline-primary" onclick="showAssignUserModal()">
                            <i class="bi bi-plus"></i>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php foreach ($projectUsers as $projectUser): ?>
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-2">
                                <i class="bi bi-person-circle text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-medium"><?= htmlspecialchars($projectUser['full_name']) ?></div>
                                <small class="text-muted">
                                    <?= htmlspecialchars($projectUser['role_name']) ?>
                                    <?php if ($projectUser['department']): ?>
                                        • <?= htmlspecialchars($projectUser['department']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Site Inventory Clerk'])): ?>
                        <a href="?route=withdrawals/create&project_id=<?= $project['id'] ?>" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-down-circle me-2"></i>Create Withdrawal
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                        <a href="?route=procurement-orders/create&project_id=<?= $project['id'] ?>" class="btn btn-outline-success">
                            <i class="bi bi-cart-plus me-2"></i>Create Procurement
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Site Inventory Clerk'])): ?>
                        <a href="?route=requests/create&project_id=<?= $project['id'] ?>" class="btn btn-outline-info">
                            <i class="bi bi-plus-circle me-2"></i>Create Request
                        </a>
                    <?php endif; ?>
                    
                    <a href="?route=assets/create&project_id=<?= $project['id'] ?>" class="btn btn-outline-warning">
                        <i class="bi bi-box me-2"></i>Add Asset
                    </a>
                </div>
            </div>
        </div>

        <!-- Project Timeline -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Project Timeline
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Project Created</h6>
                            <p class="timeline-text">Project was created and initialized</p>
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($project['created_at'])) ?></small>
                        </div>
                    </div>
                    
                    <?php if ($project['start_date'] && strtotime($project['start_date']) <= time()): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Project Started</h6>
                                <p class="timeline-text">Project work commenced</p>
                                <small class="text-muted"><?= date('M j, Y', strtotime($project['start_date'])) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($project['end_date']): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker <?= strtotime($project['end_date']) < time() ? 'bg-danger' : 'bg-info' ?>"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">
                                    <?= strtotime($project['end_date']) < time() ? 'Project Overdue' : 'Expected Completion' ?>
                                </h6>
                                <p class="timeline-text">
                                    <?= strtotime($project['end_date']) < time() ? 'Project has passed its end date' : 'Project scheduled to complete' ?>
                                </p>
                                <small class="text-muted"><?= date('M j, Y', strtotime($project['end_date'])) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Assignment Modal -->
<?php if ($auth->hasRole(['System Admin', 'Project Manager'])): ?>
<div class="modal fade" id="assignUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign User to Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="assignUserForm">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Select User</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Choose a user...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Assignment Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Optional notes about this assignment..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="assignUser()">Assign User</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Print project details
function printProjectDetails() {
    window.print();
}

// Show assign user modal
function showAssignUserModal() {
    // Load available users
    fetch('?route=projects/getAvailableUsers')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('user_id');
                select.innerHTML = '<option value="">Choose a user...</option>';
                data.users.forEach(user => {
                    select.innerHTML += `<option value="${user.id}">${user.full_name} (${user.role_name})</option>`;
                });
                
                const modal = new bootstrap.Modal(document.getElementById('assignUserModal'));
                modal.show();
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            alert('Failed to load users');
        });
}

// Assign user to project
function assignUser() {
    const formData = new FormData(document.getElementById('assignUserForm'));
    formData.append('project_id', <?= $project['id'] ?>);
    
    fetch('?route=projects/assignUser', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to assign user: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while assigning the user');
    });
}

// Auto-refresh project stats every 2 minutes
setInterval(function() {
    if (document.visibilityState === 'visible') {
        fetch(`?route=projects/getStats&project_id=<?= $project['id'] ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update stats cards if needed
                    console.log('Stats updated:', data.data);
                }
            })
            .catch(error => console.error('Stats update error:', error));
    }
}, 120000);
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}

.timeline-title {
    margin-bottom: 5px;
    font-size: 0.9rem;
}

.timeline-text {
    margin-bottom: 5px;
    font-size: 0.85rem;
    color: #6c757d;
}
</style>

<?php
// Capture content an
