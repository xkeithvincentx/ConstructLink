<?php
/**
 * ConstructLink™ Intelligent Vendor Product Catalog
 * Smart product search and vendor matching based on procurement history
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

</div>

<!-- Success Messages -->
<?php if (isset($_GET['message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>
        <?= htmlspecialchars($_GET['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Catalog Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Products</h6>
                        <h3 class="mb-0"><?= number_format($catalogStats['total_products'] ?? 0) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-box-seam display-6"></i>
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
                        <h6 class="card-title">Active Vendors</h6>
                        <h3 class="mb-0"><?= number_format($catalogStats['total_vendors'] ?? 0) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-building display-6"></i>
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
                        <h6 class="card-title">Categories</h6>
                        <h3 class="mb-0"><?= number_format($catalogStats['total_categories'] ?? 0) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-tags display-6"></i>
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
                        <h6 class="card-title">Avg Price</h6>
                        <h3 class="mb-0">₱<?= number_format($catalogStats['avg_price'] ?? 0, 2) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-currency-peso display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Advanced Search and Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Intelligent Product Search & Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="" id="catalogSearchForm">
            <input type="hidden" name="route" value="vendors/productCatalog">
            <div class="row g-3">
                <!-- Smart Search -->
                <div class="col-md-6">
                    <label for="search" class="form-label">Smart Product Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search by product name, description, model, brand, or vendor..."
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                               autocomplete="off">
                        <button type="button" class="btn btn-outline-info" id="clearSearch">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                    <div id="searchSuggestions" class="list-group mt-1" style="display: none; position: absolute; z-index: 1000;"></div>
                </div>
                
                <!-- Category Filter -->
                <div class="col-md-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">All Categories</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" 
                                        <?= ($_GET['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Vendor Filter -->
                <div class="col-md-3">
                    <label for="vendor_id" class="form-label">Vendor</label>
                    <select class="form-select" id="vendor_id" name="vendor_id">
                        <option value="">All Vendors</option>
                        <?php if (!empty($vendors)): ?>
                            <?php foreach ($vendors as $vendor): ?>
                                <option value="<?= $vendor['id'] ?>" 
                                        <?= ($_GET['vendor_id'] ?? '') == $vendor['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($vendor['name']) ?>
                                    <?= $vendor['is_preferred'] ? ' ⭐' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <!-- Price Range -->
                <div class="col-md-3">
                    <label for="price_min" class="form-label">Min Price</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" class="form-control" id="price_min" name="price_min" 
                               step="0.01" placeholder="0.00"
                               value="<?= htmlspecialchars($_GET['price_min'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label for="price_max" class="form-label">Max Price</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" class="form-control" id="price_max" name="price_max" 
                               step="0.01" placeholder="999999.99"
                               value="<?= htmlspecialchars($_GET['price_max'] ?? '') ?>">
                    </div>
                </div>
                
                <!-- Quality Filters -->
                <div class="col-md-3">
                    <label for="min_rating" class="form-label">Min Vendor Rating</label>
                    <select class="form-select" id="min_rating" name="min_rating">
                        <option value="">Any Rating</option>
                        <option value="4.5" <?= ($_GET['min_rating'] ?? '') === '4.5' ? 'selected' : '' ?>>4.5+ Stars</option>
                        <option value="4" <?= ($_GET['min_rating'] ?? '') === '4' ? 'selected' : '' ?>>4+ Stars</option>
                        <option value="3" <?= ($_GET['min_rating'] ?? '') === '3' ? 'selected' : '' ?>>3+ Stars</option>
                    </select>
                </div>
                
                <!-- Sort Options -->
                <div class="col-md-3">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="relevance" <?= ($_GET['sort_by'] ?? 'relevance') === 'relevance' ? 'selected' : '' ?>>Relevance</option>
                        <option value="price_low" <?= ($_GET['sort_by'] ?? '') === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= ($_GET['sort_by'] ?? '') === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="frequency" <?= ($_GET['sort_by'] ?? '') === 'frequency' ? 'selected' : '' ?>>Order Frequency</option>
                        <option value="vendor_rating" <?= ($_GET['sort_by'] ?? '') === 'vendor_rating' ? 'selected' : '' ?>>Vendor Rating</option>
                        <option value="recent" <?= ($_GET['sort_by'] ?? '') === 'recent' ? 'selected' : '' ?>>Recently Ordered</option>
                    </select>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="preferred_only" name="preferred_only" 
                               <?= isset($_GET['preferred_only']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="preferred_only">
                            <i class="bi bi-star-fill text-warning me-1"></i>Preferred Vendors Only
                        </label>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Search Products
                    </button>
                    <a href="?route=vendors/productCatalog" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear Filters
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Product Catalog Results -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">
            <i class="bi bi-grid me-2"></i>Product Catalog Results
            <?php if (!empty($productCatalog)): ?>
                <span class="badge bg-primary ms-2"><?= count($productCatalog) ?> products</span>
            <?php endif; ?>
        </h6>
        <div>
            <button class="btn btn-sm btn-outline-info" onclick="toggleView('grid')">
                <i class="bi bi-grid-3x3-gap"></i> Grid
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="toggleView('list')">
                <i class="bi bi-list"></i> List
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($productCatalog)): ?>
            <div class="text-center py-5">
                <i class="bi bi-search display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No products found</h5>
                <p class="text-muted">Try adjusting your search criteria or filters.</p>
                <button class="btn btn-primary" onclick="document.getElementById('search').focus()">
                    <i class="bi bi-search me-1"></i>Try Different Search
                </button>
            </div>
        <?php else: ?>
            <div id="productGrid" class="row">
                <?php foreach ($productCatalog as $product): ?>
                    <div class="col-lg-4 col-md-6 mb-4 product-card">
                        <div class="card h-100 product-item">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($product['item_name']) ?></h6>
                                        <?php if ($product['model']): ?>
                                            <small class="text-muted">Model: <?= htmlspecialchars($product['model']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-info dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="showPriceHistory('<?= htmlspecialchars($product['item_name']) ?>', <?= $product['vendor_id'] ?>)">
                                                <i class="bi bi-graph-up me-2"></i>Price History
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="showSimilarProducts('<?= htmlspecialchars($product['item_name']) ?>')">
                                                <i class="bi bi-search me-2"></i>Similar Products
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="getRecommendations('<?= htmlspecialchars($product['item_name']) ?>')">
                                                <i class="bi bi-lightbulb me-2"></i>Get Recommendations
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Vendor Info -->
                                <div class="d-flex align-items-center mb-2">
                                    <div class="me-2">
                                        <?php if ($product['is_preferred']): ?>
                                            <i class="bi bi-star-fill text-warning" title="Preferred Vendor"></i>
                                        <?php else: ?>
                                            <i class="bi bi-building text-primary"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong><?= htmlspecialchars($product['vendor_name']) ?></strong>
                                        <?php if ($product['rating']): ?>
                                            <div class="text-warning small">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $product['rating']): ?>
                                                        <i class="bi bi-star-fill"></i>
                                                    <?php elseif ($i - 0.5 <= $product['rating']): ?>
                                                        <i class="bi bi-star-half"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                                <span class="text-muted ms-1"><?= number_format($product['rating'], 1) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Product Details -->
                                <?php if ($product['description']): ?>
                                    <p class="small text-muted mb-2"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?><?= strlen($product['description']) > 100 ? '...' : '' ?></p>
                                <?php endif; ?>
                                
                                <?php if ($product['brand']): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-light text-dark">Brand: <?= htmlspecialchars($product['brand']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($product['category_name']): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($product['category_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Pricing and Statistics -->
                                <div class="row text-center border-top pt-2">
                                    <div class="col-4">
                                        <div class="small text-muted">Avg Price</div>
                                        <strong>₱<?= number_format($product['avg_price'], 2) ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted">Orders</div>
                                        <strong><?= $product['order_frequency'] ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted">Success Rate</div>
                                        <strong><?= number_format($product['delivery_success_rate'] * 100, 1) ?>%</strong>
                                    </div>
                                </div>
                                
                                <?php if ($product['min_price'] != $product['max_price']): ?>
                                    <div class="mt-2 small text-muted">
                                        Price range: ₱<?= number_format($product['min_price'], 2) ?> - ₱<?= number_format($product['max_price'], 2) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-2 small text-muted">
                                    Last ordered: <?= date('M j, Y', strtotime($product['last_ordered'])) ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary" onclick="contactVendor(<?= $product['vendor_id'] ?>)">
                                            <i class="bi bi-telephone"></i> Contact
                                        </button>
                                        <a href="?route=vendors/view&id=<?= $product['vendor_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i> View Vendor
                                        </a>
                                    </div>
                                    <button class="btn btn-sm btn-success" onclick="requestQuote('<?= htmlspecialchars($product['item_name']) ?>', <?= $product['vendor_id'] ?>)">
                                        <i class="bi bi-cart-plus"></i> Quote
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Product catalog pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center text-muted">
                    Showing results <?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?> - 
                    <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total_results']) ?> 
                    of <?= number_format($pagination['total_results']) ?> products
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modals for detailed views -->
<!-- Price History Modal -->
<div class="modal fade" id="priceHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Price History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="priceHistoryContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status"></div>
                        <p>Loading price history...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Similar Products Modal -->
<div class="modal fade" id="similarProductsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Similar Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="similarProductsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status"></div>
                        <p>Finding similar products...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recommendations Modal -->
<div class="modal fade" id="recommendationsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vendor Recommendations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="recommendationsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status"></div>
                        <p>Getting recommendations...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Smart search with autocomplete
let searchTimeout;
document.getElementById('search').addEventListener('input', function() {
    const query = this.value;
    const suggestions = document.getElementById('searchSuggestions');
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        suggestions.style.display = 'none';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch(`?route=vendors/productSearch&q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.suggestions.length > 0) {
                    let html = '';
                    data.suggestions.forEach(item => {
                        html += `
                            <button type="button" class="list-group-item list-group-item-action" 
                                    onclick="selectSuggestion('${item.value}')">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>${item.label}</strong><br>
                                        <small class="text-muted">${item.vendor} - ${item.category}</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">₱${item.price}</span>
                                    </div>
                                </div>
                            </button>
                        `;
                    });
                    suggestions.innerHTML = html;
                    suggestions.style.display = 'block';
                } else {
                    suggestions.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                suggestions.style.display = 'none';
            });
    }, 300);
});

// Hide suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#search') && !e.target.closest('#searchSuggestions')) {
        document.getElementById('searchSuggestions').style.display = 'none';
    }
});

function selectSuggestion(value) {
    document.getElementById('search').value = value;
    document.getElementById('searchSuggestions').style.display = 'none';
    document.getElementById('catalogSearchForm').submit();
}

// Clear search
document.getElementById('clearSearch').addEventListener('click', function() {
    document.getElementById('search').value = '';
    document.getElementById('searchSuggestions').style.display = 'none';
});

// Auto-submit on filter changes
document.querySelectorAll('#catalogSearchForm select').forEach(select => {
    select.addEventListener('change', function() {
        document.getElementById('catalogSearchForm').submit();
    });
});

// View toggle functions
function toggleView(viewType) {
    // Implementation for grid/list view toggle
    console.log('Toggle view:', viewType);
}

// Modal functions
function showPriceHistory(productName, vendorId) {
    const modal = new bootstrap.Modal(document.getElementById('priceHistoryModal'));
    modal.show();
    
    fetch(`?route=vendors/getProductPriceHistory&product=${encodeURIComponent(productName)}&vendor_id=${vendorId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="table-responsive"><table class="table table-sm">';
                html += '<thead><tr><th>Date</th><th>Price</th><th>Quantity</th><th>PO Number</th></tr></thead><tbody>';
                
                data.data.history.forEach(item => {
                    html += `<tr>
                        <td>${new Date(item.order_date).toLocaleDateString()}</td>
                        <td>₱${parseFloat(item.unit_price).toLocaleString()}</td>
                        <td>${item.quantity}</td>
                        <td>${item.po_number || 'N/A'}</td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
                
                if (data.data.trend_direction) {
                    html += `<div class="alert alert-info">
                        <strong>Price Trend:</strong> ${data.data.trend_direction} 
                        (${data.data.trend_percent > 0 ? '+' : ''}${data.data.trend_percent}%)
                    </div>`;
                }
                
                document.getElementById('priceHistoryContent').innerHTML = html;
            } else {
                document.getElementById('priceHistoryContent').innerHTML = '<div class="alert alert-danger">Failed to load price history</div>';
            }
        })
        .catch(error => {
            document.getElementById('priceHistoryContent').innerHTML = '<div class="alert alert-danger">Error loading price history</div>';
        });
}

function showSimilarProducts(productName) {
    const modal = new bootstrap.Modal(document.getElementById('similarProductsModal'));
    modal.show();
    
    fetch(`?route=vendors/getSimilarProducts&product=${encodeURIComponent(productName)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.products.length > 0) {
                let html = '<div class="row">';
                data.products.forEach(product => {
                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6>${product.item_name}</h6>
                                    <p class="text-muted small">${product.vendor_name}</p>
                                    <div class="d-flex justify-content-between">
                                        <span>₱${parseFloat(product.avg_price).toLocaleString()}</span>
                                        <span class="badge bg-info">${product.order_count} orders</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('similarProductsContent').innerHTML = html;
            } else {
                document.getElementById('similarProductsContent').innerHTML = '<div class="alert alert-info">No similar products found</div>';
            }
        })
        .catch(error => {
            document.getElementById('similarProductsContent').innerHTML = '<div class="alert alert-danger">Error loading similar products</div>';
        });
}

function getRecommendations(productName) {
    const modal = new bootstrap.Modal(document.getElementById('recommendationsModal'));
    modal.show();
    
    fetch(`?route=vendors/getProductRecommendations&product=${encodeURIComponent(productName)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.recommendations.length > 0) {
                let html = '<div class="list-group">';
                data.recommendations.forEach(rec => {
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6>${rec.vendor_name}</h6>
                                    <p class="mb-1">${rec.item_name}</p>
                                    <small class="text-muted">
                                        Success Rate: ${rec.success_rate.toFixed(1)}% | 
                                        Times Ordered: ${rec.times_ordered}
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success">₱${parseFloat(rec.avg_price).toLocaleString()}</span>
                                    <br><small class="text-muted">Score: ${rec.recommendation_score.toFixed(1)}</small>
                                </div>
                            </div>
                            ${rec.recommendation_reasons.length > 0 ? 
                                '<div class="mt-2"><small class="text-success">• ' + rec.recommendation_reasons.join('<br>• ') + '</small></div>' 
                                : ''}
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('recommendationsContent').innerHTML = html;
            } else {
                document.getElementById('recommendationsContent').innerHTML = '<div class="alert alert-info">No recommendations available</div>';
            }
        })
        .catch(error => {
            document.getElementById('recommendationsContent').innerHTML = '<div class="alert alert-danger">Error loading recommendations</div>';
        });
}

// Contact and quote functions
function contactVendor(vendorId) {
    window.location.href = `?route=vendors/view&id=${vendorId}`;
}

function requestQuote(productName, vendorId) {
    // Implementation for quote request
    alert(`Quote request for "${productName}" will be implemented`);
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Vendor Product Catalog - ConstructLink™';
$pageHeader = 'Intelligent Vendor Product Catalog';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Vendors', 'url' => '?route=vendors'],
    ['title' => 'Product Catalog', 'url' => '?route=vendors/productCatalog']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>