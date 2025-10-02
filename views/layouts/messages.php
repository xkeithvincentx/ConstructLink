<?php
// Display flash messages from session
$sessionMessages = $_SESSION['flash_messages'] ?? [];
$sessionErrors = $_SESSION['flash_errors'] ?? [];

// Display messages passed directly to the view
$viewMessages = $messages ?? [];
$viewErrors = $errors ?? [];

// Combine all messages
$allMessages = array_merge($sessionMessages, $viewMessages);
$allErrors = array_merge($sessionErrors, $viewErrors);

// Clear session messages after displaying
unset($_SESSION['flash_messages'], $_SESSION['flash_errors']);
?>

<!-- Success Messages -->
<?php if (!empty($allMessages)): ?>
    <?php foreach ($allMessages as $message): ?>
        <?php if (is_string($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (is_array($message) && isset($message['text'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($message['text']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Error Messages -->
<?php if (!empty($allErrors)): ?>
    <?php foreach ($allErrors as $error): ?>
        <?php if (is_string($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (is_array($error) && isset($error['text'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error['text']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<!-- URL Parameter Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php
    $urlMessages = [
        'logged_out' => ['type' => 'info', 'text' => 'You have been logged out successfully.'],
        'session_expired' => ['type' => 'warning', 'text' => 'Your session has expired. Please log in again.'],
        'access_denied' => ['type' => 'danger', 'text' => 'Access denied. You do not have permission to access that resource.'],
        'invalid_token' => ['type' => 'danger', 'text' => 'Invalid or expired security token.'],
        'password_changed' => ['type' => 'success', 'text' => 'Your password has been changed successfully.'],
        'profile_updated' => ['type' => 'success', 'text' => 'Your profile has been updated successfully.'],
        'asset_created' => ['type' => 'success', 'text' => 'Asset created successfully!'],
        'asset_updated' => ['type' => 'success', 'text' => 'Asset updated successfully!'],
        'status_updated' => ['type' => 'success', 'text' => 'Asset status updated successfully!'],
        'asset_deleted' => ['type' => 'success', 'text' => 'Asset has been deleted successfully.'],
        'withdrawal_created' => ['type' => 'success', 'text' => 'Withdrawal request created successfully!'],
        'withdrawal_verified' => ['type' => 'success', 'text' => 'Withdrawal verified successfully!'],
        'withdrawal_approved' => ['type' => 'success', 'text' => 'Withdrawal approved successfully!'],
        'withdrawal_released' => ['type' => 'success', 'text' => 'Asset has been released successfully!'],
        'withdrawal_returned' => ['type' => 'success', 'text' => 'Asset has been returned successfully!'],
        'withdrawal_canceled' => ['type' => 'success', 'text' => 'Withdrawal request canceled successfully!'],
        'request_submitted' => ['type' => 'success', 'text' => 'Request has been submitted for review successfully.'],
        'cannot_submit' => ['type' => 'error', 'text' => 'This request cannot be submitted in its current status.'],
        'submit_failed' => ['type' => 'error', 'text' => 'Failed to submit request. Please try again.'],
        'transfer_created' => ['type' => 'success', 'text' => 'Transfer request created successfully!'],
        'transfer_streamlined' => ['type' => 'success', 'text' => 'Transfer completed with streamlined process! Ready for final completion.'],
        'transfer_simplified' => ['type' => 'success', 'text' => 'Transfer created with simplified process! Awaiting final approval.'],
        'transfer_verified' => ['type' => 'success', 'text' => 'Transfer request verified successfully!'],
        'transfer_approved' => ['type' => 'success', 'text' => 'Transfer approved successfully!'],
        'transfer_received' => ['type' => 'success', 'text' => 'Transfer received successfully!'],
        'transfer_completed' => ['type' => 'success', 'text' => 'Transfer completed successfully!'],
        'transfer_canceled' => ['type' => 'warning', 'text' => 'Transfer canceled successfully!'],
        'asset_returned' => ['type' => 'success', 'text' => 'Asset returned successfully!'],
        'return_initiated' => ['type' => 'warning', 'text' => 'Return process initiated successfully! Asset is now in transit back to origin project.'],
        'return_completed' => ['type' => 'success', 'text' => 'Return completed successfully! Asset is now available at origin project.'],
        'maintenance_scheduled' => ['type' => 'success', 'text' => 'Maintenance has been scheduled successfully.'],
        'maintenance_completed' => ['type' => 'success', 'text' => 'Maintenance has been completed successfully.'],
        'incident_reported' => ['type' => 'success', 'text' => 'Incident has been reported successfully.'],
        'incident_created' => ['type' => 'success', 'text' => 'Incident has been reported successfully.'],
        'incident_updated' => ['type' => 'success', 'text' => 'Incident has been updated successfully.'],
        'incident_investigated' => ['type' => 'success', 'text' => 'Investigation has been completed successfully.'],
        'incident_resolved' => ['type' => 'success', 'text' => 'Incident has been resolved successfully.'],
        'incident_closed' => ['type' => 'success', 'text' => 'Incident has been closed successfully.'],
        'tool_borrowed' => ['type' => 'success', 'text' => 'Tool borrowed successfully!'],
        'tool_returned' => ['type' => 'success', 'text' => 'Tool returned successfully!'],
        'borrowing_extended' => ['type' => 'success', 'text' => 'Borrowing period extended successfully!'],
        'tool_verified' => ['type' => 'success', 'text' => 'Tool request verified successfully!'],
        'tool_approved' => ['type' => 'success', 'text' => 'Tool request approved successfully!'],
        'tool_canceled' => ['type' => 'success', 'text' => 'Tool request canceled successfully!'],
        'tool_processed_streamlined' => ['type' => 'success', 'text' => 'Basic tool processed successfully using streamlined workflow!'],
        'tool_critical_created' => ['type' => 'success', 'text' => 'Critical tool request created and sent for verification!'],
        'procurement_order_created' => ['type' => 'success', 'text' => 'Procurement order has been created successfully.'],
        'procurement_order_updated' => ['type' => 'success', 'text' => 'Procurement order has been updated successfully.'],
        'procurement_order_submitted' => ['type' => 'success', 'text' => 'Procurement order has been submitted for approval successfully.'],
        'submitted_for_approval' => ['type' => 'success', 'text' => 'Procurement order has been submitted for approval successfully.'],
        'procurement_order_approved' => ['type' => 'success', 'text' => 'Procurement order has been approved successfully.'],
        'procurement_order_rejected' => ['type' => 'danger', 'text' => 'Procurement order has been rejected.'],
        'procurement_order_received' => ['type' => 'success', 'text' => 'Procurement order has been received successfully.'],
        'procurement_order_canceled' => ['type' => 'info', 'text' => 'Procurement order has been canceled.'],
        'assets_generated' => ['type' => 'success', 'text' => 'Assets have been generated successfully.'],
        'retroactive_created' => ['type' => 'warning', 'text' => 'Retroactive procurement order has been created successfully and will follow standard workflow.'],
        'feature_not_available' => ['type' => 'warning', 'text' => 'Asset generation feature is currently not available. Please contact your system administrator.'],
        'delivery_scheduled' => ['type' => 'success', 'text' => 'Delivery has been scheduled successfully!'],
        'delivery_status_updated' => ['type' => 'success', 'text' => 'Delivery status has been updated successfully!'],
        'receipt_confirmed' => ['type' => 'success', 'text' => 'Receipt has been confirmed successfully!'],
        'receipt_failed' => ['type' => 'danger', 'text' => 'Failed to confirm receipt. Please try again.'],
        'order_created' => ['type' => 'success', 'text' => 'Procurement order created successfully!'],
        'order_updated' => ['type' => 'success', 'text' => 'Procurement order updated successfully!'],
        'order_approved' => ['type' => 'success', 'text' => 'Procurement order approved successfully!'],
        'order_received' => ['type' => 'success', 'text' => 'Procurement order received successfully!'],
        'order_canceled' => ['type' => 'success', 'text' => 'Procurement order canceled successfully!'],
        'export_failed' => ['type' => 'danger', 'text' => 'Failed to export data. Please try again.'],
        'schedule_failed' => ['type' => 'danger', 'text' => 'Failed to schedule delivery. Please try again.'],
        'discrepancy_flagged' => ['type' => 'warning', 'text' => 'Discrepancy has been flagged and will be reviewed.']
    ];
    
    $messageKey = $_GET['message'];
    if (isset($urlMessages[$messageKey])):
        $msg = $urlMessages[$messageKey];
    ?>
        <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show" role="alert">
            <?php
            $icons = [
                'success' => 'bi bi-check-circle',
                'info' => 'bi bi-info-circle',
                'warning' => 'bi bi-exclamation-triangle',
                'danger' => 'bi bi-x-circle'
            ];
            $icon = $icons[$msg['type']] ?? 'bi bi-info-circle';
            ?>
            <i class="<?= $icon ?> me-2"></i>
            <?= htmlspecialchars($msg['text']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Auto-hide alerts after 5 seconds -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide success and info alerts after 5 seconds
    const autoHideAlerts = document.querySelectorAll('.alert-success, .alert-info');
    autoHideAlerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
