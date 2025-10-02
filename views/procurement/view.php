<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-clipboard-data me-2"></i>
        Procurement Order Details
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=procurement" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i>Back to Procurement
        </a>
        
        <?php if (in_array($procurement['status'], ['Approved', 'Delivered']) && $auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])): ?>
            <a href="?route=procurement/generatePO&id=<?= $procurement['id'] ?>" 
               class="btn btn-outline-primary me-2" target="_blank">
                <i class="bi bi-file-earmark-pdf me-1"></i>Generate PDF
            </a>
        <?php endif; ?>
        
        <?php if ($procurement['status'] === 'Pending' && $auth->hasRole(['System Admin', 'Finance Director'])): ?>
            <a href="?route=procurement/approve&id=<?= $procurement['id'] ?>" class="btn btn-success">
                <i class="bi bi-check-circle me-1"></i>Review & Approve
            </a>
        <?php endif; ?>
        
        <?php if ($procurement['status'] === 'Approved' && $auth->hasRole(['System Admin', 'Warehouseman', 'Procurement Officer'])): ?>
            <a href="?route=procurement/receive&id=<?= $procurement['id'] ?>" class="btn btn-info">
                <i class="bi bi-box-arrow-in-down me-1"></i>Mark as Received
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php if ($_GET['message'] === 'procurement_created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Procurement order created successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'procurement_approved'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Procurement order approved successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($_GET['message'] === 'procurement_received'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Procurement marked as received and <?= $_GET['assets'] ?? 0 ?> assets created!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Procurement Details -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clipboard-data me-2"></i>Procurement Order Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">PO Number:</dt>
                            <dd class="col-sm-7">
                                <span class="fw-bold text-primary"><?= htmlspecialchars($procurement['po_number']) ?></span>
                            </dd>
                            
                            <dt class="col-sm-5">Status:</dt>
                            <dd class="col-sm-7">
                                <?php
                                $statusClasses = [
                                    'Pending' => 'bg-warning text-dark',
                                    'Approved' => 'bg-success',
                                    'For Revision' => 'bg-info',
                                    'Rejected' => 'bg-danger',
                                    'Delivered' => 'bg-primary',
                                    'Partial' => 'bg-secondary'
                                ];
                                $statusClass = $statusClasses[$procurement['status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($procurement['status']) ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Requested By:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($procurement['requested_by_name']) ?></dd>
                            
                            <dt class="col-sm-5">Request Date:</dt>
                            <dd class="col-sm-7"><?= date('M j, Y g:i A', strtotime($procurement['created_at'])) ?></dd>
                            
                            <?php if ($procurement['approved_by_name']): ?>
                                <dt class="col-sm-5">Approved By:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($procurement['approved_by_name']) ?></dd>
                            <?php endif; ?>
                            
                            <?php if ($procurement['date_needed']): ?>
                                <dt class="col-sm-5">Date Needed:</dt>
                                <dd class="col-sm-7">
                                    <?= date('M j, Y', strtotime($procurement['date_needed'])) ?>
                                    <?php if (strtotime($procurement['date_needed']) < time() && $procurement['status'] !== 'Delivered'): ?>
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    <?php endif; ?>
                                </dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                    
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Vendor:</dt>
                            <dd class="col-sm-7">
                                <div class="fw-medium"><?= htmlspecialchars($procurement['vendor_name']) ?></div>
                                <?php if ($procurement['vendor_contact']): ?>
                                    <small class="text-muted"><?= htmlspecialchars($procurement['vendor_contact']) ?></small>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-5">Project:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($procurement['project_name']) ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Delivery Status:</dt>
                            <dd class="col-sm-7">
                                <?php
                                $deliveryClasses = [
                                    'Pending' => 'bg-warning text-dark',
                                    'Partial' => 'bg-info',
                                    'Complete' => 'bg-success'
                                ];
                                $deliveryClass = $deliveryClasses[$procurement['delivery_status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $deliveryClass ?>">
                                    <?= htmlspecialchars($procurement['delivery_status']) ?>
                                </span>
                            </dd>
                            
                            <?php if ($procurement['delivery_date']): ?>
                                <dt class="col-sm-5">Delivery Date:</dt>
                                <dd class="col-sm-7"><?= date('M j, Y', strtotime($procurement['delivery_date'])) ?></dd>
                            <?php endif; ?>
                            
                            <?php if ($procurement['payment_terms']): ?>
                                <dt class="col-sm-5">Payment Terms:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($procurement['payment_terms']) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
                
                <!-- Item Details -->
                <div class="mt-4">
                    <h6>Item Details</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Name</th>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])): ?>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars($procurement['item_name']) ?></td>
                                    <td><?= htmlspecialchars($procurement['description'] ?? 'N/A') ?></td>
                                    <td><?= number_format($procurement['quantity']) ?></td>
                                    <td><?= htmlspecialchars($procurement['unit'] ?? 'pcs') ?></td>
                                    <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])): ?>
                                        <td>₱<?= number_format($procurement['unit_price'], 2) ?></td>
                                        <td>₱<?= number_format($procurement['subtotal'], 2) ?></td>
                                    <?php endif; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Package Scope -->
                <?php if ($procurement['package_scope']): ?>
                    <div class="mt-3">
                        <h6>Package Scope</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($procurement['package_scope'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Notes -->
                <?php if ($procurement['notes']): ?>
                    <div class="mt-3">
                        <h6>Notes</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($procurement['notes'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Quote File -->
                <?php if ($procurement['quote_file']): ?>
                    <div class="mt-3">
                        <h6>Vendor Quote</h6>
                        <a href="/uploads/quotes/<?= htmlspecialchars($procurement['quote_file']) ?>" 
                           class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="bi bi-file-earmark-text me-1"></i>View Quote File
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Linked Request (if applicable) -->
        <?php if ($procurement['request_description']): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-link-45deg me-2"></i>Linked Request
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p class="mb-1"><strong>Description:</strong> <?= htmlspecialchars($procurement['request_description']) ?></p>
                            <?php if ($procurement['request_urgency']): ?>
                                <p class="mb-0">
                                    <strong>Urgency:</strong> 
                                    <span class="badge <?= $procurement['request_urgency'] === 'Critical' ? 'bg-danger' : ($procurement['request_urgency'] === 'Urgent' ? 'bg-warning text-dark' : 'bg-info') ?>">
                                        <?= htmlspecialchars($procurement['request_urgency']) ?>
                                    </span>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="?route=requests/view&id=<?= $procurement['request_id'] ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye me-1"></i>View Request
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Financial Summary -->
        <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])): ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-calculator me-2"></i>Financial Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-6">Subtotal:</div>
                        <div class="col-6 text-end">₱<?= number_format($procurement['subtotal'], 2) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">VAT (12%):</div>
                        <div class="col-6 text-end">₱<?= number_format($procurement['vat_amount'], 2) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">Handling Fee:</div>
                        <div class="col-6 text-end">₱<?= number_format($procurement['handling_fee'], 2) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">EWT (2%):</div>
                        <div class="col-6 text-end">-₱<?= number_format($procurement['ewt_amount'], 2) ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6"><strong>Net Total:</strong></div>
                        <div class="col-6 text-end"><strong>₱<?= number_format($procurement['net_total'], 2) ?></strong></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Vendor Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-building me-2"></i>Vendor Information
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong><?= htmlspecialchars($procurement['vendor_name']) ?></strong>
                </div>
                <?php if ($procurement['vendor_contact']): ?>
                    <div class="mb-1">
                        <small class="text-muted">Contact:</small> <?= htmlspecialchars($procurement['vendor_contact']) ?>
                    </div>
                <?php endif; ?>
                <?php if ($procurement['vendor_email']): ?>
                    <div class="mb-1">
                        <small class="text-muted">Email:</small> 
                        <a href="mailto:<?= htmlspecialchars($procurement['vendor_email']) ?>">
                            <?= htmlspecialchars($procurement['vendor_email']) ?>
                        </a>
                    </div>
                <?php endif; ?>
                <?php if ($procurement['vendor_phone']): ?>
                    <div class="mb-1">
                        <small class="text-muted">Phone:</small> 
                        <a href="tel:<?= htmlspecialchars($procurement['vendor_phone']) ?>">
                            <?= htmlspecialchars($procurement['vendor_phone']) ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Vendor Banks -->
                <?php if (!empty($vendorBanks) && $auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])): ?>
                    <div class="mt-3">
                        <small class="text-muted fw-medium">Bank Accounts:</small>
                        <?php foreach ($vendorBanks as $bank): ?>
                            <div class="mt-1">
                                <small>
                                    <?= htmlspecialchars($bank['bank_name']) ?><br>
                                    <?= htmlspecialchars($bank['account_number']) ?> (<?= htmlspecialchars($bank['account_type']) ?>)
                                    <?php if ($bank['bank_category'] === 'Primary'): ?>
                                        <span class="badge bg-primary ms-1" style="font-size: 0.6em;">Primary</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="?route=vendors/view&id=<?= $procurement['vendor_id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-eye me-1"></i>View Vendor Details
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Project Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-folder me-2"></i>Project Information
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong><?= htmlspecialchars($procurement['project_name']) ?></strong>
                </div>
                <div class="mb-1">
                    <small class="text-muted">Code:</small> <?= htmlspecialchars($procurement['project_code']) ?>
                </div>
                <?php if ($procurement['project_location']): ?>
                    <div class="mb-1">
                        <small class="text-muted">Location:</small> <?= htmlspecialchars($procurement['project_location']) ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="?route=projects/view&id=<?= $procurement['project_id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-eye me-1"></i>View Project Details
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <?php if ($procurement['status'] === 'Pending' && $auth->hasRole(['System Admin', 'Finance Director'])): ?>
                    <a href="?route=procurement/approve&id=<?= $procurement['id'] ?>" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-check-circle me-1"></i>Approve Order
                    </a>
                <?php endif; ?>
                
                <?php if ($procurement['status'] === 'Approved' && $auth->hasRole(['System Admin', 'Warehouseman', 'Procurement Officer'])): ?>
                    <a href="?route=procurement/receive&id=<?= $procurement['id'] ?>" class="btn btn-info w-100 mb-2">
                        <i class="bi bi-box-arrow-in-down me-1"></i>Mark as Received
                    </a>
                <?php endif; ?>
                
                <?php if (in_array($procurement['status'], ['Approved', 'Delivered']) && $auth->hasRole(['System Admin', 'Finance Director', 'Procurement Officer'])): ?>
                    <a href="?route=procurement/generatePO&id=<?= $procurement['id'] ?>" 
                       class="btn btn-outline-primary w-100 mb-2" target="_blank">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Generate PDF
                    </a>
                <?php endif; ?>
                
                <a href="?route=procurement" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-list me-1"></i>Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Procurement Order Details - ConstructLink™';
$pageHeader = 'Procurement Order #' . ($procurement['po_number'] ?? 'N/A');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement', 'url' => '?route=procurement'],
    ['title' => 'Order Details', 'url' => '?route=procurement/view&id=' . ($procurement['id'] ?? '')]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
