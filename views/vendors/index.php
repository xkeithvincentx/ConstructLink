<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4 gap-2">
    <!-- Primary Actions (Left) -->
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Primary actions">
        <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
            <a href="?route=vendors/productCatalog" class="btn btn-success btn-sm">
                <i class="bi bi-search me-1"></i>
                <span class="d-none d-md-inline">Product Catalog</span>
                <span class="d-md-none">Catalog</span>
            </a>
            <a href="?route=vendors/intelligenceDashboard" class="btn btn-info btn-sm">
                <i class="bi bi-graph-up me-1"></i>
                <span class="d-none d-md-inline">Intelligence Dashboard</span>
                <span class="d-md-none">Dashboard</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Secondary Actions (Right) -->
    <div class="btn-toolbar flex-wrap gap-2" role="toolbar" aria-label="Secondary actions">
        <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                Analytics
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="?route=vendors/vendorComparison">
                    <i class="bi bi-bar-chart me-2"></i>Compare Vendors
                </a></li>
                <li><a class="dropdown-item" href="?route=vendors/riskAssessment">
                    <i class="bi bi-shield-exclamation me-2"></i>Risk Assessment
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="?route=vendors/pendingWorkflows">
                    <i class="bi bi-clock me-2"></i>Pending Workflows
                </a></li>
            </ul>
        </div>
        <?php endif; ?>
        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
            <div class="btn-group" role="group">
                <a href="?route=vendors/create" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add New Vendor
                </a>
                <a href="?route=vendors/createWithWorkflow" class="btn btn-outline-primary">
                    <i class="bi bi-diagram-3 me-1"></i>Add with Workflow
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php if ($_GET['message'] === 'vendor_created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Vendor created successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'vendor_updated'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Vendor updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'vendor_deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Vendor deleted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'export_failed'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>Failed to export vendors. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-building text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Total Vendors</h6>
                        <h3 class="mb-0"><?= $vendorStats['total_vendors'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-shop me-1"></i>Registered suppliers
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-star-fill text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Preferred Vendors</h6>
                        <h3 class="mb-0"><?= $vendorStats['preferred_vendors'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-award me-1"></i>Top rated suppliers
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-activity text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Active (30 days)</h6>
                        <h3 class="mb-0"><?= $vendorStats['active_vendors_30d'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-graph-up me-1"></i>Recently active
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-star text-warning fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Avg Rating</h6>
                        <h3 class="mb-0" style="font-size: 1.3rem;">
                            <?= isset($vendorStats['average_rating']) && $vendorStats['average_rating'] > 0 ?
                                number_format($vendorStats['average_rating'], 1) . '/5.0' : 'N/A' ?>
                        </h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-clipboard-data me-1"></i>Overall performance
                </p>
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
        <form method="GET" action="?route=vendors" class="row g-3">
            <div class="col-md-3">
                <label for="payment_terms_id" class="form-label">Payment Terms</label>
                <select class="form-select" id="payment_terms_id" name="payment_terms_id">
                    <option value="">All Terms</option>
                    <?php if (isset($paymentTerms) && is_array($paymentTerms)): ?>
                        <?php foreach ($paymentTerms as $term): ?>
                            <option value="<?= $term['id'] ?>" 
                                    <?= ($_GET['payment_terms_id'] ?? '') == $term['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($term['term_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">All Categories</option>
                    <?php if (isset($vendorCategories) && is_array($vendorCategories)): ?>
                        <?php foreach ($vendorCategories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                    <?= ($_GET['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="is_preferred" class="form-label">Status</label>
                <select class="form-select" id="is_preferred" name="is_preferred">
                    <option value="">All Vendors</option>
                    <option value="1" <?= ($_GET['is_preferred'] ?? '') === '1' ? 'selected' : '' ?>>Preferred Only</option>
                    <option value="0" <?= ($_GET['is_preferred'] ?? '') === '0' ? 'selected' : '' ?>>Regular Only</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="rating_min" class="form-label">Min Rating</label>
                <select class="form-select" id="rating_min" name="rating_min">
                    <option value="">Any Rating</option>
                    <option value="4" <?= ($_GET['rating_min'] ?? '') === '4' ? 'selected' : '' ?>>4+ Stars</option>
                    <option value="3" <?= ($_GET['rating_min'] ?? '') === '3' ? 'selected' : '' ?>>3+ Stars</option>
                    <option value="2" <?= ($_GET['rating_min'] ?? '') === '2' ? 'selected' : '' ?>>2+ Stars</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="intelligent_search" class="form-label">
                    <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                        Intelligent Search
                        <small class="text-muted">(vendors, products, or items)</small>
                    <?php else: ?>
                        Search Vendors
                    <?php endif; ?>
                </label>
                <div class="input-group">
                    <input type="text" class="form-control" id="intelligent_search" name="search"
                           placeholder="<?= $auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']) ? 
                               'Search vendors, products, or items...' : 'Search by vendor name, contact person, email...' ?>"
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           autocomplete="off">
                    <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-search"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item" type="button" onclick="performIntelligentSearch('vendors')">
                            <i class="bi bi-building me-2"></i>Search Vendors
                        </button></li>
                        <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                        <li><button class="dropdown-item" type="button" onclick="performIntelligentSearch('products')">
                            <i class="bi bi-box-seam me-2"></i>Search Products/Items
                        </button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item" type="button" onclick="performIntelligentSearch('auto')">
                            <i class="bi bi-magic me-2"></i>Auto-Detect (Recommended)
                        </button></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div id="intelligentSearchSuggestions" class="list-group mt-1" style="display: none; position: absolute; z-index: 1000; width: 100%;"></div>
            </div>
            <div class="col-md-<?= $auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer']) ? '6' : '6' ?> d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter Vendors
                </button>
                <a href="?route=vendors" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
                <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                <a href="?route=vendors/productCatalog" class="btn btn-success">
                    <i class="bi bi-grid me-1"></i>Product Catalog
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Intelligence Insights -->
<?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-lightbulb me-2"></i>Vendor Intelligence Insights
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white rounded-circle p-2 me-3">
                        <i class="bi bi-search"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Intelligent Product Catalog</div>
                        <small class="text-muted">Smart product search and vendor matching</small>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="?route=vendors/productCatalog" class="btn btn-sm btn-outline-primary">
                        Browse Catalog <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="bg-success text-white rounded-circle p-2 me-3">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Top Performing Vendors</div>
                        <small class="text-muted">View detailed performance metrics and rankings</small>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="?route=vendors/intelligenceDashboard" class="btn btn-sm btn-outline-success">
                        View Dashboard <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="bg-warning text-white rounded-circle p-2 me-3">
                        <i class="bi bi-shield-exclamation"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Risk Assessment</div>
                        <small class="text-muted">Identify and mitigate vendor risks</small>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="?route=vendors/riskAssessment" class="btn btn-sm btn-outline-warning">
                        Assess Risk <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="bg-info text-white rounded-circle p-2 me-3">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Vendor Comparison</div>
                        <small class="text-muted">Compare vendors side-by-side</small>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="?route=vendors/vendorComparison" class="btn btn-sm btn-outline-info">
                        Compare <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Vendors Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Vendors List</h6>
        <div class="d-flex gap-2">
            <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])): ?>
                <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export
                </button>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($vendors)): ?>
            <div class="text-center py-5">
                <i class="bi bi-building display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No vendors found</h5>
                <p class="text-muted">Try adjusting your filters or add a new vendor.</p>
                <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                    <a href="?route=vendors/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Add First Vendor
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="vendorsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Vendor</th>
                            <th>Contact</th>
                            <th>Payment Terms</th>
                            <th>Categories</th>
                            <th>Rating</th>
                            <th>Banks</th>
                            <?php if ($auth->hasRole(['System Admin', 'Finance Director'])): ?>
                                <th>Procurement Value</th>
                            <?php endif; ?>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendors as $vendor): ?>
                            <tr>
                                <td>
                                    <a href="?route=vendors/view&id=<?= $vendor['id'] ?>" class="text-decoration-none">
                                        #<?= $vendor['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <?php if ($vendor['is_preferred']): ?>
                                                <i class="bi bi-star-fill text-warning" title="Preferred Vendor"></i>
                                            <?php else: ?>
                                                <i class="bi bi-building text-primary"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($vendor['name']) ?></div>
                                            <?php if ($vendor['tax_id']): ?>
                                                <small class="text-muted">Tax ID: <?= htmlspecialchars($vendor['tax_id']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php if ($vendor['contact_person']): ?>
                                            <div class="fw-medium"><?= htmlspecialchars($vendor['contact_person']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($vendor['email']): ?>
                                            <small class="text-muted">
                                                <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($vendor['email']) ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($vendor['phone']): ?>
                                            <br><small class="text-muted">
                                                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($vendor['phone']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($vendor['payment_term_name']): ?>
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($vendor['payment_term_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($vendor['assigned_categories'])): ?>
                                        <?php foreach (array_slice($vendor['assigned_categories'], 0, 2) as $category): ?>
                                            <span class="badge bg-secondary me-1">
                                                <?= htmlspecialchars($category['category_name']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (count($vendor['assigned_categories']) > 2): ?>
                                            <span class="badge bg-light text-dark">
                                                +<?= count($vendor['assigned_categories']) - 2 ?> more
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($vendor['rating']): ?>
                                        <div class="d-flex align-items-center">
                                            <span class="me-1"><?= number_format($vendor['rating'], 1) ?></span>
                                            <div class="text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $vendor['rating']): ?>
                                                        <i class="bi bi-star-fill"></i>
                                                    <?php elseif ($i - 0.5 <= $vendor['rating']): ?>
                                                        <i class="bi bi-star-half"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Not rated</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= $vendor['bank_count'] ?? 0 ?> account<?= ($vendor['bank_count'] ?? 0) !== 1 ? 's' : '' ?>
                                    </span>
                                </td>
                                <?php if ($auth->hasRole(['System Admin', 'Finance Director'])): ?>
                                    <td>
                                        <div>
                                            <div class="fw-medium">₱<?= number_format($vendor['total_procurement_value'] ?? 0, 2) ?></div>
                                            <small class="text-muted"><?= $vendor['procurement_count'] ?? 0 ?> orders</small>
                                        </div>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($vendor['is_preferred']): ?>
                                        <span class="badge bg-success">Preferred</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Regular</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=vendors/view&id=<?= $vendor['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-info dropdown-toggle" 
                                                        data-bs-toggle="dropdown" title="Analytics">
                                                    <i class="bi bi-graph-up"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="?route=vendors/performanceAnalysis&id=<?= $vendor['id'] ?>">
                                                        <i class="bi bi-speedometer2 me-2"></i>Performance Analysis
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="?route=vendors/riskAssessment&id=<?= $vendor['id'] ?>">
                                                        <i class="bi bi-shield-exclamation me-2"></i>Risk Assessment
                                                    </a></li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                                            <a href="?route=vendors/edit&id=<?= $vendor['id'] ?>" 
                                               class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="toggleVendorStatus(<?= $vendor['id'] ?>)" 
                                                    title="Toggle Preferred Status">
                                                <i class="bi bi-star"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deleteVendor(<?= $vendor['id'] ?>, '<?= htmlspecialchars($vendor['name']) ?>')" 
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
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
                <nav aria-label="Vendors pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=vendors&page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?route=vendors&page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=vendors&page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
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
// Toggle vendor preferred status
function toggleVendorStatus(vendorId) {
    if (confirm('Are you sure you want to toggle the preferred status for this vendor?')) {
        fetch('?route=vendors/toggleStatus', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'vendor_id=' + vendorId
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
            alert('An error occurred while updating vendor status.');
        });
    }
}

// Delete vendor
function deleteVendor(vendorId, vendorName) {
    if (confirm(`Are you sure you want to delete vendor "${vendorName}"? This action cannot be undone.`)) {
        window.location.href = `?route=vendors/delete&id=${vendorId}`;
    }
}

// Export to Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=vendors/export&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Enhanced search functionality
document.getElementById('intelligent_search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        performIntelligentSearch('auto');
    }
});

// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[action="?route=vendors"]');
    const filterSelects = filterForm.querySelectorAll('select');
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            filterForm.submit();
        });
    });
});

// Intelligent search with autocomplete
<?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
let searchTimeout;
let currentSearchMode = 'auto';

document.getElementById('intelligent_search').addEventListener('input', function() {
    const query = this.value;
    const suggestions = document.getElementById('intelligentSearchSuggestions');
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        suggestions.style.display = 'none';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        // Show mixed results: vendors and products
        Promise.all([
            // Search vendors
            fetch(`?route=vendors/getForDropdown&search=${encodeURIComponent(query)}`).then(r => r.json()).catch(() => ({vendors: []})),
            // Search products
            fetch(`?route=vendors/productSearch&q=${encodeURIComponent(query)}`).then(r => r.json()).catch(() => ({suggestions: []}))
        ]).then(([vendorData, productData]) => {
            let html = '';
            
            // Add vendor results
            if (vendorData.vendors && vendorData.vendors.length > 0) {
                html += '<div class="list-group-item bg-light text-muted small"><strong>VENDORS</strong></div>';
                vendorData.vendors.slice(0, 3).forEach(vendor => {
                    html += `
                        <button type="button" class="list-group-item list-group-item-action" 
                                onclick="selectVendorSuggestion('${vendor.name}')">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-building text-primary me-2"></i>
                                <div>
                                    <strong>${vendor.name}</strong>
                                    ${vendor.contact_person ? `<br><small class="text-muted">${vendor.contact_person}</small>` : ''}
                                </div>
                            </div>
                        </button>
                    `;
                });
            }
            
            // Add product results
            if (productData.success && productData.suggestions && productData.suggestions.length > 0) {
                if (html) html += '<div class="dropdown-divider"></div>';
                html += '<div class="list-group-item bg-light text-muted small"><strong>PRODUCTS/ITEMS</strong></div>';
                productData.suggestions.slice(0, 3).forEach(item => {
                    html += `
                        <button type="button" class="list-group-item list-group-item-action" 
                                onclick="selectProductSuggestion('${item.value}')">
                            <div class="d-flex justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-box-seam text-success me-2"></i>
                                    <div>
                                        <strong>${item.label}</strong><br>
                                        <small class="text-muted">${item.vendor} - ${item.category}</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success">₱${item.price}</span>
                                </div>
                            </div>
                        </button>
                    `;
                });
            }
            
            if (html) {
                suggestions.innerHTML = html;
                suggestions.style.display = 'block';
            } else {
                suggestions.style.display = 'none';
            }
        }).catch(error => {
            console.error('Intelligent search error:', error);
            suggestions.style.display = 'none';
        });
    }, 300);
});

// Hide suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#intelligent_search') && !e.target.closest('#intelligentSearchSuggestions')) {
        document.getElementById('intelligentSearchSuggestions').style.display = 'none';
    }
});

function selectVendorSuggestion(vendorName) {
    document.getElementById('intelligent_search').value = vendorName;
    document.getElementById('intelligentSearchSuggestions').style.display = 'none';
    document.querySelector('form[action="?route=vendors"]').submit();
}

function selectProductSuggestion(productName) {
    document.getElementById('intelligent_search').value = productName;
    document.getElementById('intelligentSearchSuggestions').style.display = 'none';
    window.location.href = `?route=vendors/productCatalog&search=${encodeURIComponent(productName)}`;
}

function performIntelligentSearch(mode) {
    const query = document.getElementById('intelligent_search').value.trim();
    currentSearchMode = mode;
    
    if (query.length === 0) {
        if (mode === 'products') {
            window.location.href = '?route=vendors/productCatalog';
        }
        return;
    }
    
    if (mode === 'vendors') {
        // Search vendors only
        document.querySelector('form[action="?route=vendors"]').submit();
    } else if (mode === 'products') {
        // Search products only
        window.location.href = `?route=vendors/productCatalog&search=${encodeURIComponent(query)}`;
    } else {
        // Auto-detect: check if query looks like a product/item or vendor
        const productKeywords = ['pipe', 'steel', 'cement', 'rod', 'wire', 'tool', 'equipment', 'material', 'supply'];
        const isLikelyProduct = productKeywords.some(keyword => 
            query.toLowerCase().includes(keyword)
        ) || /\d/.test(query); // Contains numbers (like "2 inch pipe")
        
        if (isLikelyProduct) {
            // Redirect to product catalog
            window.location.href = `?route=vendors/productCatalog&search=${encodeURIComponent(query)}`;
        } else {
            // Search vendors
            document.querySelector('form[action="?route=vendors"]').submit();
        }
    }
}
<?php else: ?>
// For non-privileged users, just search vendors
function performIntelligentSearch(mode) {
    document.querySelector('form[action="?route=vendors"]').submit();
}
<?php endif; ?>
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Vendor Management - ConstructLink™';
$pageHeader = 'Vendor Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Vendors', 'url' => '?route=vendors']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
