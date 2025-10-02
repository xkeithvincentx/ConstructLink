<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();

// Check if user has permission to access brand workflow
$allowedRoles = ['System Admin', 'Asset Director'];
if (!in_array($user['role_name'], $allowedRoles)) {
    http_response_code(403);
    echo "<div class='alert alert-danger'>Access denied. You don't have permission to access brand workflow management.</div>";
    exit;
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-diagram-3 me-2"></i>
        Brand Workflow Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-outline-secondary me-2" onclick="refreshWorkflowData()">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
        <button class="btn btn-primary" onclick="showBrandWorkflowStats()">
            <i class="bi bi-graph-up me-1"></i>Statistics
        </button>
    </div>
</div>

<!-- Workflow Statistics Cards -->
<div class="row mb-4" id="workflow-stats">
    <div class="col-md-3">
        <div class="card text-bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Pending Suggestions</h5>
                        <h3 class="mb-0" id="pending-suggestions">-</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-lightbulb fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-bg-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Unknown Brands</h5>
                        <h3 class="mb-0" id="pending-notifications">-</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Recent Activity</h5>
                        <h3 class="mb-0" id="recent-activity">-</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock-history fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-bg-secondary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Assets w/ Unknown Brands</h5>
                        <h3 class="mb-0" id="assets-unknown-brands">-</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-question-circle fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs for different workflow sections -->
<ul class="nav nav-tabs" id="workflow-tabs">
    <li class="nav-item">
        <button class="nav-link active" id="suggestions-tab" data-bs-toggle="tab" data-bs-target="#suggestions-panel" type="button">
            <i class="bi bi-lightbulb me-1"></i>Brand Suggestions
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications-panel" type="button">
            <i class="bi bi-bell me-1"></i>Unknown Brand Notifications
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity-panel" type="button">
            <i class="bi bi-activity me-1"></i>Recent Activity
        </button>
    </li>
</ul>

<div class="tab-content mt-4" id="workflow-tab-content">
    <!-- Brand Suggestions Panel -->
    <div class="tab-pane fade show active" id="suggestions-panel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pending Brand Suggestions</h5>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadBrandSuggestions('pending')">Pending</button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadBrandSuggestions('approved')">Approved</button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadBrandSuggestions('rejected')">Rejected</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Suggested Brand</th>
                                <th>Suggested By</th>
                                <th>Context</th>
                                <th>Asset</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="suggestions-list">
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Unknown Brand Notifications Panel -->
    <div class="tab-pane fade" id="notifications-panel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Unknown Brand Notifications</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Brand Name</th>
                                <th>Asset</th>
                                <th>Created By</th>
                                <th>Context</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="notifications-list">
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Panel -->
    <div class="tab-pane fade" id="activity-panel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Brand Workflow Activity</h5>
            </div>
            <div class="card-body">
                <div id="activity-list">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Suggestion Review Modal -->
<div class="modal fade" id="suggestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Review Brand Suggestion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="suggestionForm">
                    <input type="hidden" id="suggestion-id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Suggested Brand Name</label>
                                <input type="text" class="form-control" id="suggested-name" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Suggested By</label>
                                <input type="text" class="form-control" id="suggested-by" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Decision</label>
                        <select class="form-select" id="suggestion-status" required>
                            <option value="">Choose action...</option>
                            <option value="approved">Approve & Create Brand</option>
                            <option value="merged">Merge with Existing Brand</option>
                            <option value="rejected">Reject Suggestion</option>
                        </select>
                    </div>
                    
                    <div id="brand-creation-section" style="display: none;">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Official Brand Name</label>
                                    <input type="text" class="form-control" id="brand-name" name="brand_name">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Quality Tier</label>
                                    <select class="form-select" id="quality-tier" name="quality_tier">
                                        <option value="premium">Premium</option>
                                        <option value="mid-range" selected>Mid-Range</option>
                                        <option value="budget">Budget</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Review Notes</label>
                        <textarea class="form-control" id="review-notes" name="review_notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processSuggestion()">Submit Decision</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the workflow dashboard
    loadWorkflowStats();
    loadBrandSuggestions('pending');
    
    // Set up tab change handlers
    document.getElementById('notifications-tab').addEventListener('shown.bs.tab', function() {
        loadUnknownBrandNotifications();
    });
    
    document.getElementById('activity-tab').addEventListener('shown.bs.tab', function() {
        loadRecentActivity();
    });
    
    // Set up suggestion status change handler
    document.getElementById('suggestion-status').addEventListener('change', function() {
        const brandSection = document.getElementById('brand-creation-section');
        if (this.value === 'approved') {
            brandSection.style.display = 'block';
            document.getElementById('brand-name').value = document.getElementById('suggested-name').value;
        } else {
            brandSection.style.display = 'none';
        }
    });
});

function loadWorkflowStats() {
    fetch('?route=api/admin/brand-workflow&action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('pending-suggestions').textContent = data.data.pending_suggestions;
                document.getElementById('pending-notifications').textContent = data.data.pending_notifications;
                document.getElementById('recent-activity').textContent = data.data.recent_activity;
                document.getElementById('assets-unknown-brands').textContent = data.data.assets_unknown_brands;
            }
        })
        .catch(error => console.error('Error loading workflow stats:', error));
}

function loadBrandSuggestions(status = 'pending') {
    const tbody = document.getElementById('suggestions-list');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm"></div></td></tr>';
    
    fetch(`?route=api/admin/brand-suggestions&status=${status}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayBrandSuggestions(data.data);
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to load suggestions</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading suggestions:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading suggestions</td></tr>';
        });
}

function displayBrandSuggestions(suggestions) {
    const tbody = document.getElementById('suggestions-list');
    
    if (suggestions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No suggestions found</td></tr>';
        return;
    }
    
    tbody.innerHTML = suggestions.map(suggestion => `
        <tr>
            <td><strong>${suggestion.suggested_name}</strong></td>
            <td>${suggestion.suggested_by_name}</td>
            <td><small class="text-muted">${suggestion.category_context || 'N/A'}</small></td>
            <td>${suggestion.asset_name ? `<small>${suggestion.asset_name} (${suggestion.asset_ref})</small>` : 'N/A'}</td>
            <td><small>${new Date(suggestion.created_at).toLocaleDateString()}</small></td>
            <td><span class="badge bg-${getStatusBadgeClass(suggestion.status)}">${suggestion.status}</span></td>
            <td>
                ${suggestion.status === 'pending' ? 
                    `<button class="btn btn-sm btn-outline-primary" onclick="reviewSuggestion(${suggestion.id})">Review</button>` :
                    '<span class="text-muted">Processed</span>'
                }
            </td>
        </tr>
    `).join('');
}

function reviewSuggestion(suggestionId) {
    // Load suggestion details and show modal
    fetch(`?route=api/admin/brand-suggestions&status=pending`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const suggestion = data.data.find(s => s.id == suggestionId);
                if (suggestion) {
                    document.getElementById('suggestion-id').value = suggestion.id;
                    document.getElementById('suggested-name').value = suggestion.suggested_name;
                    document.getElementById('suggested-by').value = suggestion.suggested_by_name;
                    
                    const modal = new bootstrap.Modal(document.getElementById('suggestionModal'));
                    modal.show();
                }
            }
        });
}

function processSuggestion() {
    const form = document.getElementById('suggestionForm');
    const formData = new FormData(form);
    
    const data = {
        id: parseInt(formData.get('id')),
        status: document.getElementById('suggestion-status').value,
        review_notes: formData.get('review_notes'),
        create_brand: document.getElementById('suggestion-status').value === 'approved',
        brand_name: formData.get('brand_name'),
        quality_tier: formData.get('quality_tier')
    };
    
    fetch('?route=api/admin/brand-suggestions', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('suggestionModal')).hide();
            showNotification('Brand suggestion processed successfully!', 'success');
            loadBrandSuggestions('pending');
            loadWorkflowStats();
        } else {
            showNotification('Failed to process suggestion: ' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error processing suggestion:', error);
        showNotification('Failed to process suggestion', 'error');
    });
}

function loadUnknownBrandNotifications() {
    const tbody = document.getElementById('notifications-list');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm"></div></td></tr>';
    
    fetch('?route=api/assets/unknown-brand-notifications')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUnknownBrandNotifications(data.data);
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to load notifications</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading notifications</td></tr>';
        });
}

function displayUnknownBrandNotifications(notifications) {
    const tbody = document.getElementById('notifications-list');
    
    if (notifications.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No notifications found</td></tr>';
        return;
    }
    
    tbody.innerHTML = notifications.map(notification => `
        <tr>
            <td><strong>${notification.brand_name}</strong></td>
            <td><small>${notification.asset_name}</small></td>
            <td>${notification.created_by_name}</td>
            <td><small>${notification.category_context || 'N/A'}</small></td>
            <td><span class="badge bg-info">${notification.notification_type}</span></td>
            <td><span class="badge bg-${getStatusBadgeClass(notification.status)}">${notification.status}</span></td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="assignNotification(${notification.id})">Assign</button>
                    <button class="btn btn-outline-success" onclick="resolveNotification(${notification.id})">Resolve</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function loadRecentActivity() {
    const container = document.getElementById('activity-list');
    container.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
    
    fetch('?route=api/admin/brand-workflow&action=recent&limit=20')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRecentActivity(data.data);
            } else {
                container.innerHTML = '<div class="text-center text-muted">No activity found</div>';
            }
        })
        .catch(error => {
            console.error('Error loading activity:', error);
            container.innerHTML = '<div class="text-center text-danger">Error loading activity</div>';
        });
}

function displayRecentActivity(activities) {
    const container = document.getElementById('activity-list');
    
    if (activities.length === 0) {
        container.innerHTML = '<div class="text-center text-muted">No recent activity</div>';
        return;
    }
    
    container.innerHTML = activities.map(activity => `
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
            <div>
                <strong>${activity.performed_by_name}</strong> ${activity.action} 
                <span class="text-muted">${activity.entity_type}</span> #${activity.entity_id}
                ${activity.notes ? `<br><small class="text-muted">${activity.notes}</small>` : ''}
            </div>
            <small class="text-muted">${new Date(activity.created_at).toLocaleString()}</small>
        </div>
    `).join('');
}

function getStatusBadgeClass(status) {
    const classes = {
        'pending': 'warning',
        'approved': 'success',
        'rejected': 'danger',
        'in_review': 'info',
        'resolved': 'success',
        'dismissed': 'secondary'
    };
    return classes[status] || 'secondary';
}

function assignNotification(notificationId) {
    fetch('?route=api/assets/unknown-brand-notifications', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: notificationId,
            status: 'in_review'
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Notification assigned to you', 'success');
            loadUnknownBrandNotifications();
            loadWorkflowStats();
        } else {
            showNotification('Failed to assign notification', 'error');
        }
    });
}

function resolveNotification(notificationId) {
    const notes = prompt('Resolution notes (optional):');
    
    fetch('?route=api/assets/unknown-brand-notifications', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: notificationId,
            status: 'resolved',
            resolution_notes: notes || ''
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Notification resolved', 'success');
            loadUnknownBrandNotifications();
            loadWorkflowStats();
        } else {
            showNotification('Failed to resolve notification', 'error');
        }
    });
}

function refreshWorkflowData() {
    loadWorkflowStats();
    loadBrandSuggestions('pending');
    loadUnknownBrandNotifications();
    loadRecentActivity();
    showNotification('Workflow data refreshed', 'success');
}

function showNotification(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 1060;" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHtml);
    
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Brand Workflow Management - ConstructLink™';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Admin', 'url' => '#'],
    ['title' => 'Brand Workflow', 'url' => '?route=admin/brand-workflow']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>