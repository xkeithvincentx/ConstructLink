<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-eye me-2"></i>
        Procurement Order #<?= htmlspecialchars($procurementOrder['po_number'] ?? 'DRAFT-' . $procurementOrder['id']) ?>
        <?php if (!empty($procurementOrder['is_retroactive']) && $procurementOrder['is_retroactive'] == 1): ?>
            <span class="badge bg-warning ms-2" title="This PO was created for post-purchase documentation">
                <i class="bi bi-clock-history me-1"></i>RETROACTIVE
            </span>
        <?php endif; ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="?route=procurement-orders" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Procurement Orders
            </a>
        </div>
        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/edit'] ?? []) && in_array($procurementOrder['status'], ['Draft', 'Pending'])): ?>
            <div class="btn-group me-2">
                <a href="?route=procurement-orders/edit&id=<?= $procurementOrder['id'] ?>" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i>Edit Order
                </a>
            </div>
        <?php endif; ?>
        <?php if ($procurementOrder['status'] === 'Draft' && in_array($user['role_name'], $roleConfig['procurement-orders/create'] ?? [])): ?>
            <div class="btn-group me-2">
                <form method="POST" action="?route=procurement-orders/submit-for-approval&id=<?= $procurementOrder['id'] ?>" style="display: inline;">
                    <?= CSRFProtection::getTokenField() ?>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Submit this procurement order for approval?')">
                        <i class="bi bi-send me-1"></i>Submit for Approval
                    </button>
                </form>
            </div>
        <?php endif; ?>
        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/approve'] ?? []) && $procurementOrder['status'] === 'Pending'): ?>
            <div class="btn-group me-2">
                <a href="?route=procurement-orders/approve&id=<?= $procurementOrder['id'] ?>" class="btn btn-success">
                    <i class="bi bi-check-circle me-1"></i>Approve Order
                </a>
            </div>
        <?php endif; ?>
        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/schedule-delivery'] ?? []) && $procurementOrder['status'] === 'Approved'): ?>
            <div class="btn-group me-2">
                <a href="?route=procurement-orders/schedule-delivery&id=<?= $procurementOrder['id'] ?>" class="btn btn-warning">
                    <i class="bi bi-calendar-plus me-1"></i>Schedule Delivery
                </a>
            </div>
        <?php endif; ?>
        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/update-delivery'] ?? []) && in_array($procurementOrder['status'], ['Scheduled for Delivery', 'In Transit'])): ?>
            <div class="btn-group me-2">
                <a href="?route=procurement-orders/update-delivery&id=<?= $procurementOrder['id'] ?>" class="btn btn-primary">
                    <i class="bi bi-truck me-1"></i>Update Delivery
                </a>
            </div>
        <?php endif; ?>
        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/receive'] ?? []) && $procurementOrder['status'] === 'Delivered'): ?>
            <div class="btn-group me-2">
                <a href="?route=procurement-orders/receive&id=<?= $procurementOrder['id'] ?>" class="btn btn-success">
                    <i class="bi bi-check-square me-1"></i>Confirm Receipt
                </a>
            </div>
        <?php endif; ?>
        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/generateAssets'] ?? []) && $procurementOrder['status'] === 'Received') : ?>
            <div class="btn-group me-2">
                <a href="?route=procurement-orders/generateAssets&id=<?= $procurementOrder['id'] ?>" class="btn btn-secondary">
                    <i class="bi bi-plus-square me-1"></i>Generate Assets
                </a>
            </div>
        <?php endif; ?>
        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/resolve-discrepancy'] ?? []) && 
                  $procurementOrder['status'] === 'Received' && 
                  $procurementOrder['delivery_status'] === 'Partial'): ?>
            <div class="btn-group me-2">
                <a href="?route=procurement-orders/resolve-discrepancy&id=<?= $procurementOrder['id'] ?>" class="btn btn-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i>Resolve Discrepancy
                </a>
            </div>
        <?php endif; ?>
        <?php 
        // Define allowed statuses for print/PDF generation - include Pending for print preview
        $allowedPrintStatuses = ['Pending', 'Approved', 'Scheduled for Delivery', 'In Transit', 'Delivered', 'Received'];
        ?>
        <?php if (in_array($procurementOrder['status'], $allowedPrintStatuses)): ?>
            <div class="me-2">
                <a href="?route=procurement-orders/print-preview&id=<?= $procurementOrder['id'] ?>" class="btn btn-outline-primary">
                    <i class="bi bi-printer me-1"></i>Print Preview
                </a>
            </div>
        <?php endif; ?>
        
        <div class="me-2">
            <form method="GET" action="" style="display: inline;">
                <input type="hidden" name="route" value="bir2307/generate">
                <input type="hidden" name="po_id" value="<?= $procurementOrder['id'] ?>">
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-receipt me-1"></i>Generate BIR 2307
                </button>
            </form>
        </div>
        <?php if (in_array($procurementOrder['status'], ['Draft', 'Pending', 'Reviewed']) && in_array($user['role_name'], $roleConfig['procurement-orders/cancel'] ?? [])): ?>
            <div class="btn-group">
                <a href="?route=procurement-orders/cancel&id=<?= $procurementOrder['id'] ?>" class="btn btn-outline-danger">
                    <i class="bi bi-x-circle me-1"></i>Cancel Order
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

    <!-- Partial Delivery Alert -->
    <?php if ($procurementOrder['status'] === 'Received' && $procurementOrder['delivery_status'] === 'Partial'): ?>
        <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">Partial Delivery - Discrepancy Detected</h5>
                    <p class="mb-0">This order has been partially received. Some items were delivered in quantities less than ordered.</p>
                    <?php if (!empty($procurementOrder['delivery_discrepancy_notes'])): ?>
                        <p class="mb-0 mt-2"><strong>Details:</strong> <?= htmlspecialchars($procurementOrder['delivery_discrepancy_notes']) ?></p>
                    <?php endif; ?>
                    <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/resolve-discrepancy'] ?? [])): ?>
                        <p class="mb-0 mt-2"><strong>Action Required:</strong> Please use the "Resolve Discrepancy" button above to address this issue.</p>
                    <?php else: ?>
                        <p class="mb-0 mt-2"><strong>Note:</strong> Only Asset Director or Procurement Officer can resolve discrepancies.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Discrepancy Resolution Workflow -->
        <div class="card mt-3 border-warning">
            <div class="card-header bg-warning bg-opacity-10">
                <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Discrepancy Resolution Workflow</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Current Status</h6>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle text-success me-2"></i>Order Delivered by Vendor</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Receipt Confirmed by <?= htmlspecialchars($procurementOrder['received_by_name'] ?? 'Warehouseman') ?></li>
                            <li><i class="bi bi-exclamation-circle text-warning me-2"></i>Discrepancy Reported (Partial Delivery)</li>
                            <li><i class="bi bi-clock text-muted me-2"></i>Awaiting Resolution</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Resolution Process</h6>
                        <ol class="small">
                            <li><strong>Asset Director/Procurement Officer</strong> reviews the discrepancy</li>
                            <li>Contacts vendor for clarification/remedy</li>
                            <li>Documents resolution (return, credit note, re-delivery, etc.)</li>
                            <li>Updates order status accordingly</li>
                        </ol>
                        <div class="mt-3">
                            <strong>Authorized Personnel:</strong>
                            <ul class="list-unstyled ms-3 mb-0">
                                <?php foreach ($roleConfig['procurement-orders/resolve-discrepancy'] ?? [] as $role): ?>
                                    <li><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($role) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Display Messages -->
    <?php if (isset($_GET['message'])): ?>
        <?php
        $messages = [
            'procurement_order_created' => ['type' => 'success', 'text' => 'Procurement order has been created successfully.'],
            'procurement_order_updated' => ['type' => 'success', 'text' => 'Procurement order has been updated successfully.'],
            'procurement_order_approved' => ['type' => 'success', 'text' => 'Procurement order has been approved successfully.'],
            'procurement_order_rejected' => ['type' => 'danger', 'text' => 'Procurement order has been rejected.'],
            'procurement_order_received' => ['type' => 'success', 'text' => 'Procurement order has been received successfully.'],
            'assets_generated' => ['type' => 'success', 'text' => 'Assets have been generated successfully. Count: ' . ($_GET['count'] ?? 0)],
            'feature_not_available' => ['type' => 'warning', 'text' => 'Asset generation feature is currently not available. Please contact your system administrator.']
        ];
        $message = $messages[$_GET['message']] ?? null;
        if ($message): ?>
            <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= $message['text'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Order Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Order Information
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($procurementOrder['is_retroactive']) && $procurementOrder['is_retroactive'] == 1): ?>
                        <div class="alert alert-warning mb-3" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Retroactive Documentation:</strong> This procurement order was created for post-purchase documentation.
                            <?php if (!empty($procurementOrder['retroactive_reason'])): ?>
                                <br><small><strong>Reason:</strong> <?= htmlspecialchars($procurementOrder['retroactive_reason']) ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">PO Number:</dt>
                                <dd class="col-sm-7">
                                    <span class="fw-medium"><?= htmlspecialchars($procurementOrder['po_number'] ?? 'DRAFT-' . $procurementOrder['id']) ?></span>
                                </dd>
                                
                                <dt class="col-sm-5">Status:</dt>
                                <dd class="col-sm-7">
                                    <?php
                                    $statusClasses = [
                                        'Draft' => 'bg-secondary',
                                        'Pending' => 'bg-warning',
                                        'Reviewed' => 'bg-info',
                                        'Approved' => 'bg-success',
                                        'Rejected' => 'bg-danger',
                                        'Received' => 'bg-primary',
                                        'For Revision' => 'bg-warning'
                                    ];
                                    $statusClass = $statusClasses[$procurementOrder['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($procurementOrder['status']) ?>
                                    </span>
                                </dd>
                                
                                <dt class="col-sm-5">Title:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['title']) ?></dd>
                                
                                <dt class="col-sm-5">Vendor:</dt>
                                <dd class="col-sm-7">
                                    <div class="fw-medium"><?= htmlspecialchars($procurementOrder['vendor_name']) ?></div>
                                    <?php if (!empty($procurementOrder['vendor_contact'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($procurementOrder['vendor_contact']) ?></small>
                                    <?php endif; ?>
                                </dd>
                                
                                <dt class="col-sm-5">Project:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($procurementOrder['project_name']) ?>
                                    </span>
                                </dd>
                            </dl>
                        </div>
                        
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Requested By:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['requested_by_name']) ?></dd>
                                
                                <dt class="col-sm-5">Request Date:</dt>
                                <dd class="col-sm-7"><?= date('M j, Y g:i A', strtotime($procurementOrder['created_at'])) ?></dd>
                                
                                <?php if (!empty($procurementOrder['approved_by_name'])): ?>
                                    <dt class="col-sm-5">Approved By:</dt>
                                    <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['approved_by_name']) ?></dd>
                                <?php endif; ?>
                                
                                <?php if (!empty($procurementOrder['received_by_name'])): ?>
                                    <dt class="col-sm-5">Received By:</dt>
                                    <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['received_by_name']) ?></dd>
                                    
                                    <dt class="col-sm-5">Received Date:</dt>
                                    <dd class="col-sm-7"><?= date('M j, Y g:i A', strtotime($procurementOrder['received_at'])) ?></dd>
                                <?php endif; ?>
                                
                                <?php if (!empty($procurementOrder['date_needed'])): ?>
                                    <dt class="col-sm-5">Date Needed:</dt>
                                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($procurementOrder['date_needed'])) ?></dd>
                                <?php endif; ?>
                                
                                <dt class="col-sm-5">Net Total:</dt>
                                <dd class="col-sm-7">
                                    <span class="fw-bold text-primary fs-5">₱<?= number_format($procurementOrder['net_total'], 2) ?></span>
                                </dd>
                                
                                <?php if (!empty($procurementOrder['vat_rate'])): ?>
                                    <dt class="col-sm-5">VAT Rate:</dt>
                                    <dd class="col-sm-7"><?= number_format($procurementOrder['vat_rate'], 2) ?>%</dd>
                                <?php endif; ?>
                                
                                <?php if (!empty($procurementOrder['ewt_rate'])): ?>
                                    <dt class="col-sm-5">EWT Rate:</dt>
                                    <dd class="col-sm-7"><?= number_format($procurementOrder['ewt_rate'], 2) ?>%</dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                    </div>
                    
                    <?php if (!empty($procurementOrder['package_scope'])): ?>
                        <div class="mt-3">
                            <h6>Package Scope:</h6>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($procurementOrder['package_scope'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($procurementOrder['work_breakdown'])): ?>
                        <div class="mt-3">
                            <h6>Work Breakdown:</h6>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($procurementOrder['work_breakdown'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($procurementOrder['justification'])): ?>
                        <div class="mt-3">
                            <h6>Justification:</h6>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($procurementOrder['justification'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($procurementOrder['notes'])): ?>
                        <div class="mt-3">
                            <h6>Notes:</h6>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($procurementOrder['notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($procurementOrder['quality_check_notes'])): ?>
                        <div class="mt-3">
                            <h6>Quality Check Notes:</h6>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($procurementOrder['quality_check_notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- File Attachments -->
            <?php 
            // Get allowed file types based on PO type and status
            require_once APP_ROOT . '/models/ProcurementOrderModel.php';
            $isRetroactive = !empty($procurementOrder['is_retroactive']) && $procurementOrder['is_retroactive'] == 1;
            $currentStatus = $procurementOrder['status'] ?? 'Draft';
            $allowedFileTypes = ProcurementOrderModel::getAllowedFileTypes($isRetroactive, $currentStatus);
            
            // Check if there are any files to display or if any file types are allowed
            $hasFiles = !empty($procurementOrder['quote_file']) || 
                       !empty($procurementOrder['purchase_receipt_file']) || 
                       !empty($procurementOrder['supporting_evidence_file']);
            
            $hasAllowedTypes = false;
            foreach ($allowedFileTypes as $config) {
                if ($config['allowed']) {
                    $hasAllowedTypes = true;
                    break;
                }
            }
            ?>
            <?php if ($hasFiles || $hasAllowedTypes): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-paperclip me-2"></i>File Attachments
                        <?php if ($isRetroactive): ?>
                            <span class="badge bg-warning text-dark ms-2">Retroactive PO</span>
                        <?php endif; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($isRetroactive): ?>
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-clock-history me-2"></i>
                            <strong>Retroactive Documentation:</strong> This PO was created for post-purchase documentation.
                        </div>
                    <?php elseif (!$hasAllowedTypes): ?>
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Status: <?= $currentStatus ?></strong> - File attachments are not yet applicable for this procurement order status.
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <?php 
                        require_once APP_ROOT . '/core/ProcurementFileUploader.php';
                        
                        // Define color themes for different file types
                        $fileTypeStyles = [
                            'quote_file' => ['color' => 'primary', 'fallback_icon' => 'bi-file-earmark-text'],
                            'purchase_receipt_file' => ['color' => 'success', 'fallback_icon' => 'bi-receipt'], 
                            'supporting_evidence_file' => ['color' => 'info', 'fallback_icon' => 'bi-file-earmark-plus']
                        ];
                        
                        foreach ($allowedFileTypes as $fileType => $config):
                            $hasFile = !empty($procurementOrder[$fileType]);
                            $style = $fileTypeStyles[$fileType] ?? ['color' => 'secondary', 'fallback_icon' => 'bi-file-earmark'];
                            
                            // Show file if it exists, or show placeholder if file type is currently allowed
                            if ($hasFile || ($config['allowed'] && $hasAllowedTypes)):
                        ?>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 h-100 <?= !$hasFile && !$config['allowed'] ? 'bg-light' : '' ?>">
                                <?php if ($hasFile): ?>
                                    <?php $fileMetadata = ProcurementFileUploader::getFileMetadata($procurementOrder[$fileType]); ?>
                                    <h6 class="text-<?= $style['color'] ?> mb-2">
                                        <i class="<?= $fileMetadata['icon'] ?? $style['fallback_icon'] ?> me-1"></i><?= $config['label'] ?>
                                    </h6>
                                    <p class="small text-muted mb-2"><?= htmlspecialchars($procurementOrder[$fileType]) ?></p>
                                    <?php if ($fileMetadata): ?>
                                    <div class="small text-muted mb-2">
                                        <div><i class="bi bi-hdd me-1"></i>Size: <?= $fileMetadata['formatted_size'] ?></div>
                                        <?php if ($fileMetadata['upload_date']): ?>
                                        <div><i class="bi bi-calendar me-1"></i>Uploaded: <?= date('M d, Y H:i', strtotime($fileMetadata['upload_date'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <button onclick="previewFile(<?= $procurementOrder['id'] ?>, '<?= $fileType ?>')" 
                                            class="btn btn-sm btn-outline-<?= $style['color'] ?>">
                                        <i class="bi bi-eye me-1"></i>Preview
                                    </button>
                                    <a href="?route=procurement-orders/file&id=<?= $procurementOrder['id'] ?>&type=<?= $fileType ?>&action=view" 
                                       target="_blank" class="btn btn-sm btn-outline-<?= $style['color'] ?> ms-1">
                                        <i class="bi bi-box-arrow-up-right"></i>Open
                                    </a>
                                    <a href="?route=procurement-orders/file&id=<?= $procurementOrder['id'] ?>&type=<?= $fileType ?>&action=download" 
                                       class="btn btn-sm btn-outline-secondary ms-1">
                                        <i class="bi bi-download"></i>
                                    </a>
                                <?php elseif ($config['allowed']): ?>
                                    <h6 class="text-muted mb-2">
                                        <i class="<?= $style['fallback_icon'] ?> me-1"></i><?= $config['label'] ?>
                                    </h6>
                                    <p class="small text-muted mb-2">No file uploaded yet</p>
                                    <div class="text-muted small">
                                        <i class="bi bi-info-circle me-1"></i><?= $config['help'] ?>
                                    </div>
                                <?php else: ?>
                                    <h6 class="text-muted mb-2">
                                        <i class="<?= $style['fallback_icon'] ?> me-1"></i><?= $config['label'] ?>
                                    </h6>
                                    <div class="text-muted small">
                                        <i class="bi bi-x-circle me-1"></i><?= $config['help'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                    
                    <?php if (!empty($procurementOrder['file_upload_notes'])): ?>
                    <div class="mt-3">
                        <h6>Document Notes:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($procurementOrder['file_upload_notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Delivery Tracking Section -->
            <?php if (in_array($procurementOrder['status'], ['Approved', 'Scheduled for Delivery', 'In Transit', 'Delivered', 'Received'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-truck me-2"></i>Delivery Tracking
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Delivery Status:</dt>
                                <dd class="col-sm-7">
                                    <?php
                                    $deliveryStatusClasses = [
                                        'Pending' => 'bg-secondary',
                                        'Scheduled' => 'bg-info',
                                        'In Transit' => 'bg-primary',
                                        'Delivered' => 'bg-success',
                                        'Received' => 'bg-dark',
                                        'Partial' => 'bg-warning'
                                    ];
                                    $deliveryStatus = $procurementOrder['delivery_status'] ?? 'Pending';
                                    $deliveryStatusClass = $deliveryStatusClasses[$deliveryStatus] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $deliveryStatusClass ?>">
                                        <?= htmlspecialchars($deliveryStatus) ?>
                                    </span>
                                </dd>
                                
                                <?php if (!empty($procurementOrder['delivery_method'])): ?>
                                <?php
                                // Determine if this is a service-oriented delivery method
                                $serviceDeliveryMethods = ['On-site Service', 'Remote Service', 'Digital Delivery', 'Email Delivery', 'Postal Mail', 'Office Pickup', 'Service Completion', 'N/A'];
                                $isServiceDelivery = in_array($procurementOrder['delivery_method'], $serviceDeliveryMethods);
                                $deliveryIcon = $isServiceDelivery ? 'bi-gear' : 'bi-geo-alt';
                                $deliveryLabel = $isServiceDelivery ? 'Service Method:' : 'Delivery Method:';
                                ?>
                                <dt class="col-sm-5"><?= $deliveryLabel ?></dt>
                                <dd class="col-sm-7">
                                    <i class="bi <?= $deliveryIcon ?> me-1"></i><?= htmlspecialchars($procurementOrder['delivery_method']) ?>
                                    <?php if ($isServiceDelivery): ?>
                                        <small class="badge bg-info ms-2">Service</small>
                                    <?php endif; ?>
                                </dd>
                                <?php endif; ?>
                                
                                <?php if (!empty($procurementOrder['delivery_location'])): ?>
                                <?php
                                // Determine if this is a service location
                                $serviceLocations = ['Client Office', 'Service Provider Office', 'Digital/Email', 'Multiple Locations', 'N/A'];
                                $isServiceLocation = in_array($procurementOrder['delivery_location'], $serviceLocations);
                                $locationIcon = $isServiceLocation ? 'bi-building' : 'bi-pin-map';
                                $locationLabel = $isServiceLocation ? 'Service Location:' : 'Delivery Location:';
                                ?>
                                <dt class="col-sm-5"><?= $locationLabel ?></dt>
                                <dd class="col-sm-7">
                                    <i class="bi <?= $locationIcon ?> me-1"></i><?= htmlspecialchars($procurementOrder['delivery_location']) ?>
                                    <?php if ($isServiceLocation): ?>
                                        <small class="badge bg-info ms-2">Service</small>
                                    <?php endif; ?>
                                </dd>
                                <?php endif; ?>
                                
                                <?php if (!empty($procurementOrder['tracking_number'])): ?>
                                <dt class="col-sm-5">Tracking Number:</dt>
                                <dd class="col-sm-7">
                                    <code><?= htmlspecialchars($procurementOrder['tracking_number']) ?></code>
                                </dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                        
                        <div class="col-md-6">
                            <dl class="row">
                                <?php if (!empty($procurementOrder['scheduled_delivery_date'])): ?>
                                <dt class="col-sm-5">Scheduled Date:</dt>
                                <dd class="col-sm-7">
                                    <?php
                                    $scheduledDate = strtotime($procurementOrder['scheduled_delivery_date']);
                                    $isOverdue = $scheduledDate < time() && !in_array($procurementOrder['delivery_status'], ['Delivered', 'Received']);
                                    ?>
                                    <span class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                        <?= date('M j, Y', $scheduledDate) ?>
                                    </span>
                                    <?php if ($isOverdue): ?>
                                        <i class="bi bi-exclamation-triangle text-danger ms-1" title="Overdue"></i>
                                    <?php endif; ?>
                                </dd>
                                <?php endif; ?>
                                
                                <?php if (!empty($procurementOrder['actual_delivery_date'])): ?>
                                <dt class="col-sm-5">Actual Date:</dt>
                                <dd class="col-sm-7">
                                    <?= date('M j, Y', strtotime($procurementOrder['actual_delivery_date'])) ?>
                                </dd>
                                <?php endif; ?>
                                
                                <?php if (!empty($procurementOrder['scheduled_by_name'])): ?>
                                <dt class="col-sm-5">Scheduled By:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['scheduled_by_name']) ?></dd>
                                <?php endif; ?>
                                
                                <?php if (!empty($procurementOrder['delivered_by_name'])): ?>
                                <dt class="col-sm-5">Delivered By:</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['delivered_by_name']) ?></dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                    </div>
                    
                    <?php if (!empty($procurementOrder['delivery_notes'])): ?>
                        <div class="mt-3">
                            <h6>Delivery Notes:</h6>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($procurementOrder['delivery_notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($procurementOrder['delivery_discrepancy_notes'])): ?>
                        <div class="mt-3">
                            <div class="alert alert-warning">
                                <h6><i class="bi bi-exclamation-triangle me-2"></i>Delivery Discrepancy</h6>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($procurementOrder['delivery_discrepancy_notes'])) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Delivery Timeline -->
                    <?php if (!empty($procurementOrder['delivery_tracking'])): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-truck me-2"></i>Delivery Tracking
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php 
                                $timeline = getDeliveryTimeline($procurementOrder['delivery_tracking']);
                                foreach ($timeline as $entry): 
                                ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker <?= $entry['class'] ?>">
                                        <i class="bi <?= $entry['icon'] ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($entry['status']) ?></h6>
                                                <p class="mb-1 text-muted small">
                                                    Updated by <?= htmlspecialchars($entry['updated_by']) ?>
                                                </p>
                                                <?php if (!empty($entry['notes'])): ?>
                                                <p class="mb-0"><?= htmlspecialchars($entry['notes']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <?= formatDateTime($entry['date']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Items -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-list-ul me-2"></i>Items (<?= count($procurementOrder['items']) ?>)
                    </h6>
                    <div class="btn-group btn-group-sm">
                        <span class="badge bg-info"><?= $itemsSummary['total_items'] ?> items</span>
                        <span class="badge bg-secondary"><?= $itemsSummary['total_quantity'] ?> total qty</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($procurementOrder['items'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Category</th>
                                        <th>Brand/Model</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-center">Unit</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Subtotal</th>
                                        <th class="text-center">Status</th>
                                        <?php if ($procurementOrder['status'] === 'Received'): ?>
                                            <th class="text-center">Received</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($procurementOrder['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-medium"><?= htmlspecialchars($item['item_name']) ?></div>
                                                <?php if (!empty($item['description'])): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($item['description']) ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($item['specifications'])): ?>
                                                    <br><small class="text-info">Specs: <?= htmlspecialchars($item['specifications']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['category_name'])): ?>
                                                    <?php 
                                                    // Determine business classification display
                                                    $businessIcon = '';
                                                    $businessBadge = '';
                                                    $businessClass = 'bg-light text-dark';
                                                    
                                                    if (isset($item['generates_assets'])) {
                                                        if ($item['generates_assets']) {
                                                            $businessIcon = $item['asset_type'] === 'capital' ? '🔧' : 
                                                                          ($item['asset_type'] === 'inventory' ? '📦' : '💼');
                                                            $businessBadge = '<small class="badge bg-success ms-1">Asset</small>';
                                                        } else {
                                                            $businessIcon = '💰';
                                                            $businessBadge = '<small class="badge bg-warning text-dark ms-1">Expense</small>';
                                                        }
                                                    }
                                                    ?>
                                                    <div>
                                                        <span class="badge <?= $businessClass ?>">
                                                            <?= $businessIcon ?> <?= htmlspecialchars($item['category_name']) ?>
                                                        </span>
                                                        <?= $businessBadge ?>
                                                    </div>
                                                    <?php if (!empty($item['business_description'])): ?>
                                                        <small class="text-muted d-block mt-1" title="<?= htmlspecialchars($item['business_description']) ?>">
                                                            <i class="bi bi-info-circle me-1"></i>
                                                            <?= htmlspecialchars(substr($item['business_description'], 0, 50)) ?>
                                                            <?= strlen($item['business_description']) > 50 ? '...' : '' ?>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['brand']) || !empty($item['model'])): ?>
                                                    <div class="small">
                                                        <?php if (!empty($item['brand'])): ?>
                                                            <div><strong><?= htmlspecialchars($item['brand']) ?></strong></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($item['model'])): ?>
                                                            <div class="text-muted"><?= htmlspecialchars($item['model']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?= number_format($item['quantity']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?= htmlspecialchars($item['unit']) ?>
                                            </td>
                                            <td class="text-end">
                                                ₱<?= number_format($item['unit_price'], 2) ?>
                                            </td>
                                            <td class="text-end">
                                                <strong>₱<?= number_format($item['subtotal'], 2) ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <?php
                                                $deliveryStatusClasses = [
                                                    'Pending' => 'bg-warning',
                                                    'Partial' => 'bg-info',
                                                    'Complete' => 'bg-success'
                                                ];
                                                $deliveryStatusClass = $deliveryStatusClasses[$item['delivery_status']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?= $deliveryStatusClass ?>">
                                                    <?= htmlspecialchars($item['delivery_status']) ?>
                                                </span>
                                            </td>
                                            <?php if ($procurementOrder['status'] === 'Received'): ?>
                                                <td class="text-center">
                                                    <?php if ($item['quantity_received'] > 0): ?>
                                                        <?php if ($item['quantity_received'] < $item['quantity']): ?>
                                                            <div>
                                                                <span class="badge bg-warning"><?= number_format($item['quantity_received']) ?></span>
                                                                <span class="text-muted">/</span>
                                                                <span class="badge bg-secondary"><?= number_format($item['quantity']) ?></span>
                                                            </div>
                                                            <small class="text-danger fw-bold">
                                                                <i class="bi bi-exclamation-triangle"></i> Partial
                                                            </small>
                                                        <?php else: ?>
                                                            <span class="badge bg-success"><?= number_format($item['quantity_received']) ?></span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">0</span>
                                                        <br><small class="text-danger fw-bold">Not Received</small>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php if (!empty($item['quality_notes'])): ?>
                                            <tr>
                                                <td colspan="<?= $procurementOrder['status'] === 'Received' ? '9' : '8' ?>" class="border-0 pt-0">
                                                    <div class="alert alert-info alert-sm mb-0">
                                                        <small><strong>Quality Notes:</strong> <?= htmlspecialchars($item['quality_notes']) ?></small>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($item['item_notes'])): ?>
                                            <tr>
                                                <td colspan="<?= $procurementOrder['status'] === 'Received' ? '9' : '8' ?>" class="border-0 pt-0">
                                                    <div class="alert alert-secondary alert-sm mb-0">
                                                        <small><strong>Notes:</strong> <?= htmlspecialchars($item['item_notes']) ?></small>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <h6 class="mt-3 text-muted">No items found</h6>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Financial Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-receipt me-2"></i>Financial Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>₱<?= number_format($procurementOrder['subtotal'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>VAT (<?= number_format($procurementOrder['vat_rate'], 2) ?>%):</span>
                        <span>₱<?= number_format($procurementOrder['vat_amount'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>EWT (<?= number_format($procurementOrder['ewt_rate'], 2) ?>%):</span>
                        <span class="text-danger">-₱<?= number_format($procurementOrder['ewt_amount'], 2) ?></span>
                    </div>
                    <?php if ($procurementOrder['handling_fee'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Handling Fee:</span>
                            <span>₱<?= number_format($procurementOrder['handling_fee'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($procurementOrder['discount_amount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Discount:</span>
                            <span class="text-success">-₱<?= number_format($procurementOrder['discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Net Total:</span>
                        <span>₱<?= number_format($procurementOrder['net_total'], 2) ?></span>
                    </div>
                    
                    <?php if (!empty($procurementOrder['budget_allocation'])): ?>
                        <div class="mt-3 pt-3 border-top">
                            <div class="d-flex justify-content-between text-muted small">
                                <span>Budget Allocation:</span>
                                <span>₱<?= number_format($procurementOrder['budget_allocation'], 2) ?></span>
                            </div>
                            <?php
                            $budgetVariance = $procurementOrder['budget_allocation'] - $procurementOrder['net_total'];
                            $varianceClass = $budgetVariance >= 0 ? 'text-success' : 'text-danger';
                            ?>
                            <div class="d-flex justify-content-between small <?= $varianceClass ?>">
                                <span>Budget Variance:</span>
                                <span><?= $budgetVariance >= 0 ? '+' : '' ?>₱<?= number_format($budgetVariance, 2) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Items Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-bar-chart me-2"></i>Items Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Items:</span>
                        <span class="badge bg-primary"><?= $itemsSummary['total_items'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Quantity:</span>
                        <span class="badge bg-info"><?= $itemsSummary['total_quantity'] ?></span>
                    </div>
                    <?php if ($procurementOrder['status'] === 'Received'): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Received Quantity:</span>
                            <span class="badge bg-success"><?= $itemsSummary['total_received'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Completed Items:</span>
                            <span class="badge bg-success"><?= $itemsSummary['completed_items'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Partial Items:</span>
                            <span class="badge bg-warning"><?= $itemsSummary['partial_items'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Pending Items:</span>
                            <span class="badge bg-secondary"><?= $itemsSummary['pending_items'] ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vendor Information -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-building me-2"></i>Vendor Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="fw-medium mb-2"><?= htmlspecialchars($procurementOrder['vendor_name']) ?></div>
                    
                    <?php if (!empty($procurementOrder['vendor_contact'])): ?>
                        <div class="small text-muted mb-1">
                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($procurementOrder['vendor_contact']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($procurementOrder['vendor_phone'])): ?>
                        <div class="small text-muted mb-1">
                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($procurementOrder['vendor_phone']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($procurementOrder['vendor_email'])): ?>
                        <div class="small text-muted">
                            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($procurementOrder['vendor_email']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- File Preview Modal -->
    <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filePreviewModalLabel">
                        <i class="bi bi-file-earmark me-2"></i>File Preview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="filePreviewContent">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading file preview...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <div id="filePreviewActions"></div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function previewFile(orderId, fileType) {
        const modal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
        const modalTitle = document.getElementById('filePreviewModalLabel');
        const modalContent = document.getElementById('filePreviewContent');
        const modalActions = document.getElementById('filePreviewActions');
        
        // Show loading state
        modalContent.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading file preview...</p>
            </div>
        `;
        
        modal.show();
        
        // Get file info
        fetch(`?route=procurement-orders/preview&id=${orderId}&type=${fileType}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Update modal title
                modalTitle.innerHTML = `
                    <i class="bi bi-file-earmark me-2"></i>
                    ${data.filename}
                    <small class="text-muted">(${formatFileSize(data.size)})</small>
                `;
                
                // Update modal actions
                modalActions.innerHTML = `
                    <a href="?route=procurement-orders/file&id=${orderId}&type=${fileType}&action=view" 
                       target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right"></i> Open in New Tab
                    </a>
                    <a href="?route=procurement-orders/file&id=${orderId}&type=${fileType}&action=download" 
                       class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-download"></i> Download
                    </a>
                `;
                
                // Display preview based on file type
                let previewContent = '';
                
                if (['jpg', 'jpeg', 'png', 'gif'].includes(data.type.toLowerCase())) {
                    // Image preview
                    previewContent = `
                        <div class="text-center">
                            <img src="${data.url}" class="img-fluid" style="max-height: 70vh;" alt="${data.filename}">
                            ${data.width && data.height ? `<p class="text-muted mt-2">Dimensions: ${data.width} × ${data.height} pixels</p>` : ''}
                        </div>
                    `;
                } else if (data.type.toLowerCase() === 'pdf') {
                    // PDF preview
                    previewContent = `
                        <div class="embed-responsive embed-responsive-1by1" style="height: 70vh;">
                            <iframe src="${data.url}" class="embed-responsive-item w-100 h-100" 
                                    style="border: 1px solid #dee2e6; border-radius: 0.375rem;">
                                <p>Your browser does not support PDFs. 
                                   <a href="${data.url}" target="_blank">Click here to download the PDF</a>
                                </p>
                            </iframe>
                        </div>
                    `;
                } else {
                    // Generic file preview
                    previewContent = `
                        <div class="text-center p-5">
                            <i class="bi bi-file-earmark-text display-1 text-muted mb-3"></i>
                            <h5>${data.filename}</h5>
                            <p class="text-muted">File type: ${data.type.toUpperCase()}</p>
                            <p class="text-muted">Size: ${formatFileSize(data.size)}</p>
                            <p class="text-muted mt-4">Preview not available for this file type.<br>
                               Use the "Open in New Tab" or "Download" buttons to view the file.</p>
                        </div>
                    `;
                }
                
                modalContent.innerHTML = previewContent;
            })
            .catch(error => {
                console.error('File preview error:', error);
                modalContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Error:</strong> Unable to load file preview.
                        <br><small>${error.message}</small>
                    </div>
                `;
                modalActions.innerHTML = '';
            });
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    </script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Procurement Order Details - ConstructLink™';
$pageHeader = 'Procurement Order #' . htmlspecialchars($procurementOrder['po_number'] ?? 'DRAFT-' . $procurementOrder['id']);
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
    ['title' => 'Order Details', 'url' => '?route=procurement-orders/view&id=' . $procurementOrder['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
