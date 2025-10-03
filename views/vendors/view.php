<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

            <?php endif; ?>
            <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                <a href="?route=vendors/edit&id=<?= $vendor['id'] ?>" class="btn btn-outline-warning">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Success Messages -->
<?php if (isset($_GET['message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>
        <?php
        switch ($_GET['message']) {
            case 'vendor_created':
                echo 'Vendor created successfully!';
                break;
            case 'vendor_updated':
                echo 'Vendor updated successfully!';
                break;
            default:
                echo htmlspecialchars($_GET['message']);
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Vendor Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Orders</h6>
                        <h3 class="mb-0"><?= $vendorStats['total_orders'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-cart display-6"></i>
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
                        <h6 class="card-title">Completed Orders</h6>
                        <h3 class="mb-0"><?= $vendorStats['completed_orders'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle display-6"></i>
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
                        <h6 class="card-title">On-Time Rate</h6>
                        <h3 class="mb-0"><?= number_format($vendorStats['on_time_rate'] ?? 0, 1) ?>%</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock display-6"></i>
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
                        <h6 class="card-title">Rating</h6>
                        <h3 class="mb-0">
                            <?= $vendor['rating'] ? number_format($vendor['rating'], 1) . '/5.0' : 'N/A' ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-star display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vendor Intelligence Overview -->
<?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']) && isset($vendorIntelligence)): ?>
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-speedometer2 me-2"></i>Performance Score
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="progress flex-grow-1 me-3" style="height: 20px;">
                        <div class="progress-bar bg-<?= getScoreColor($vendorIntelligence['performance_score']) ?>" 
                             style="width: <?= $vendorIntelligence['performance_score'] ?>%">
                            <?= number_format($vendorIntelligence['performance_score'], 1) ?>%
                        </div>
                    </div>
                    <span class="badge bg-<?= getGradeBadgeColor($vendorIntelligence['performance_grade']) ?> fs-6">
                        <?= $vendorIntelligence['performance_grade'] ?>
                    </span>
                </div>
                <small class="text-muted">
                    Based on delivery, quality, cost, reliability, and financial metrics
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-shield-exclamation me-2"></i>Risk Assessment
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="progress flex-grow-1 me-3" style="height: 20px;">
                        <div class="progress-bar bg-<?= getRiskColor($vendorIntelligence['risk_level']) ?>" 
                             style="width: <?= $vendorIntelligence['risk_score'] ?>%">
                            <?= number_format($vendorIntelligence['risk_score'], 1) ?>%
                        </div>
                    </div>
                    <span class="badge bg-<?= getRiskBadgeColor($vendorIntelligence['risk_level']) ?> fs-6">
                        <?= $vendorIntelligence['risk_level'] ?>
                    </span>
                </div>
                <small class="text-muted">
                    Overall risk level based on multiple factors
                </small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Financial Overview -->
<?php if ($auth->hasRole(['System Admin', 'Finance Director']) && isset($vendorStats['total_value'])): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h4 class="mb-1">₱<?= number_format($vendorStats['total_value'] ?? 0, 2) ?></h4>
                <p class="text-muted mb-0">Total Procurement Value (All Time)</p>
                <?php if (isset($vendorStats['avg_order_value'])): ?>
                    <small class="text-muted">Average Order: ₱<?= number_format($vendorStats['avg_order_value'] ?? 0, 2) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Vendor Details -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Vendor Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Vendor ID:</dt>
                            <dd class="col-sm-8">#<?= $vendor['id'] ?></dd>

                            <dt class="col-sm-4">Name:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($vendor['name']) ?></dd>

                            <dt class="col-sm-4">Contact Person:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($vendor['contact_person'] ?? 'N/A') ?></dd>

                            <dt class="col-sm-4">Tax ID:</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($vendor['tax_id'] ?? 'N/A') ?></dd>

                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8">
                                <?php if ($vendor['is_preferred']): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-star-fill me-1"></i>Preferred Vendor
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Regular Vendor</span>
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-4">Phone:</dt>
                            <dd class="col-sm-8">
                                <?php if (!empty($vendor['phone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($vendor['phone']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($vendor['phone']) ?>
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-4">Email:</dt>
                            <dd class="col-sm-8">
                                <?php if (!empty($vendor['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($vendor['email']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($vendor['email']) ?>
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Address:</dt>
                            <dd class="col-sm-8">
                                <?php if (!empty($vendor['address'])): ?>
                                    <?= nl2br(htmlspecialchars($vendor['address'])) ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-4">Created:</dt>
                            <dd class="col-sm-8"><?= date('M j, Y g:i A', strtotime($vendor['created_at'])) ?></dd>

                            <dt class="col-sm-4">Last Updated:</dt>
                            <dd class="col-sm-8">
                                <?= $vendor['updated_at'] ? date('M j, Y g:i A', strtotime($vendor['updated_at'])) : 'Never' ?>
                            </dd>
                        </dl>
                    </div>
                </div>

                <?php if (!empty($vendor['contact_info'])): ?>
                    <div class="mt-3">
                        <h6>Additional Contact Information:</h6>
                        <div class="bg-light p-3 rounded">
                            <?= nl2br(htmlspecialchars($vendor['contact_info'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Vendor Assets -->
        <?php if (!empty($vendorAssets)): ?>
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-box me-2"></i>Assets from this Vendor
                    </h5>
                    <span class="badge bg-primary"><?= count($vendorAssets) ?> assets</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Asset Reference</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Project</th>
                                    <th>Status</th>
                                    <th>Value</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendorAssets as $asset): ?>
                                    <tr>
                                        <td>
                                            <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($asset['ref']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($asset['name']) ?></td>
                                        <td><?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusColor($asset['status']) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $asset['status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= formatCurrency($asset['acquisition_cost'] ?? 0) ?></td>
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
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                        <a href="?route=vendors/edit&id=<?= $vendor['id'] ?>" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-pencil me-1"></i>Edit Vendor
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($auth->hasRole(['System Admin', 'Procurement Officer', 'Finance Director'])): ?>
                        <a href="?route=vendors/manageBanks&vendor_id=<?= $vendor['id'] ?>" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-bank me-1"></i>Manage Bank Accounts
                        </a>
                    <?php endif; ?>
                    
                    <a href="?route=assets&vendor_id=<?= $vendor['id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-box me-1"></i>View All Assets
                    </a>
                    
                    <a href="?route=procurement/create&vendor_id=<?= $vendor['id'] ?>" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-cart-plus me-1"></i>New Procurement
                    </a>
                    
                    <button class="btn btn-outline-info btn-sm" onclick="printVendorInfo()">
                        <i class="bi bi-printer me-1"></i>Print Details
                    </button>
                </div>
            </div>
        </div>

        <!-- Bank Accounts Summary -->
        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer', 'Finance Director']) && isset($vendor['banks']) && !empty($vendor['banks'])): ?>
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-bank me-2"></i>Bank Accounts
                    </h6>
                    <span class="badge bg-primary"><?= count($vendor['banks']) ?></span>
                </div>
                <div class="card-body">
                    <?php foreach ($vendor['banks'] as $bank): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                            <div>
                                <div class="fw-medium"><?= htmlspecialchars($bank['bank_name']) ?></div>
                                <small class="text-muted">
                                    <?= htmlspecialchars($bank['account_type']) ?> - <?= htmlspecialchars($bank['currency']) ?>
                                    <?php if ($bank['bank_category'] === 'Primary'): ?>
                                        <span class="badge bg-primary ms-1">Primary</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <code class="small"><?= htmlspecialchars(substr($bank['account_number'], -4)) ?></code>
                        </div>
                    <?php endforeach; ?>
                    <div class="text-center mt-2">
                        <a href="?route=vendors/manageBanks&vendor_id=<?= $vendor['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-gear me-1"></i>Manage Banks
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Categories & Payment Terms -->
        <?php if (isset($vendor['categories_list']) || isset($vendor['payment_terms_list'])): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-tags me-2"></i>Vendor Details
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($vendor['categories_list'])): ?>
                        <div class="mb-3">
                            <strong>Categories:</strong><br>
                            <?php foreach ($vendor['categories_list'] as $category): ?>
                                <span class="badge bg-light text-dark me-1"><?= htmlspecialchars($category) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($vendor['payment_terms_list'])): ?>
                        <div class="mb-0">
                            <strong>Payment Terms:</strong><br>
                            <?php foreach ($vendor['payment_terms_list'] as $term): ?>
                                <span class="badge bg-info me-1"><?= htmlspecialchars($term) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Asset Categories -->
        <?php if (!empty($vendorAssetCategories)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Asset Categories
                    </h6>
                </div>
                <div class="card-body">
                    <?php foreach ($vendorAssetCategories as $category): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small"><?= htmlspecialchars($category['category_name']) ?></span>
                            <span class="badge bg-light text-dark"><?= $category['asset_count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Contact Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-telephone me-2"></i>Contact Information
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($vendor['contact_person'])): ?>
                    <p class="mb-2">
                        <strong>Contact Person:</strong><br>
                        <?= htmlspecialchars($vendor['contact_person']) ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($vendor['phone'])): ?>
                    <p class="mb-2">
                        <strong>Phone:</strong><br>
                        <a href="tel:<?= htmlspecialchars($vendor['phone']) ?>" class="text-decoration-none">
                            <?= htmlspecialchars($vendor['phone']) ?>
                        </a>
                    </p>
                <?php endif; ?>

                <?php if (!empty($vendor['email'])): ?>
                    <p class="mb-2">
                        <strong>Email:</strong><br>
                        <a href="mailto:<?= htmlspecialchars($vendor['email']) ?>" class="text-decoration-none">
                            <?= htmlspecialchars($vendor['email']) ?>
                        </a>
                    </p>
                <?php endif; ?>

                <?php if (!empty($vendor['address'])): ?>
                    <p class="mb-0">
                        <strong>Address:</strong><br>
                        <?= nl2br(htmlspecialchars($vendor['address'])) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function printVendorInfo() {
    window.print();
}

// Helper functions for vendor intelligence
<?php
function getStatusColor($status) {
    switch ($status) {
        case 'available': return 'success';
        case 'in_use': return 'primary';
        case 'under_maintenance': return 'warning';
        case 'retired': return 'secondary';
        case 'borrowed': return 'info';
        default: return 'secondary';
    }
}

function getScoreColor($score) {
    if ($score >= 85) return 'success';
    if ($score >= 70) return 'info';
    if ($score >= 60) return 'warning';
    return 'danger';
}

function getGradeBadgeColor($grade) {
    if (in_array($grade, ['A+', 'A', 'A-'])) return 'success';
    if (in_array($grade, ['B+', 'B', 'B-'])) return 'info';
    if (in_array($grade, ['C+', 'C', 'C-'])) return 'warning';
    return 'danger';
}

function getRiskColor($level) {
    switch (strtolower($level)) {
        case 'minimal': return 'success';
        case 'low': return 'info';
        case 'medium': return 'warning';
        case 'high': return 'danger';
        case 'critical': return 'danger';
        default: return 'secondary';
    }
}

function getRiskBadgeColor($level) {
    switch (strtolower($level)) {
        case 'minimal': return 'success';
        case 'low': return 'primary';
        case 'medium': return 'warning';
        case 'high': return 'danger';
        case 'critical': return 'danger';
        default: return 'secondary';
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return '₱' . number_format($amount, 2);
    }
}
?>
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Vendor Details - ConstructLink™';
$pageHeader = 'Vendor: ' . htmlspecialchars($vendor['name']);
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Vendors', 'url' => '?route=vendors'],
    ['title' => 'View Details', 'url' => '?route=vendors/view&id=' . $vendor['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
