<?php
/**
 * ConstructLink™ Email Action Controller
 * Handles one-click actions from email links
 */

require_once APP_ROOT . '/core/EmailActionToken.php';
require_once APP_ROOT . '/models/TransferModel.php';
require_once APP_ROOT . '/models/ProcurementOrderModel.php';

class EmailActionController {
    private $tokenManager;
    private $transferModel;
    private $procurementModel;

    public function __construct() {
        $this->tokenManager = new EmailActionToken();
        $this->transferModel = new TransferModel();
        $this->procurementModel = new ProcurementOrderModel();
    }

    /**
     * Main entry point for email action links
     * Route: /?route=email-action&token=xxx
     */
    public function index() {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $this->showError('Invalid Request', 'No action token provided.');
            return;
        }

        // Validate token
        $tokenData = $this->tokenManager->validateToken($token);

        if (!$tokenData) {
            $this->showError('Link Expired or Invalid', 'This action link has expired, has already been used, or is invalid. Please log in to the system to perform this action manually.');
            return;
        }

        // Get user from token
        $user = $this->tokenManager->getUserFromToken($token);

        if (!$user) {
            $this->showError('User Not Found', 'The user associated with this action could not be found.');
            return;
        }

        // Route to appropriate action handler
        switch ($tokenData['action_type']) {
            case 'transfer_verify':
                $this->handleVerifyTransfer($tokenData, $user);
                break;

            case 'transfer_approve':
                $this->handleApproveTransfer($tokenData, $user);
                break;

            case 'transfer_dispatch':
                $this->handleDispatchTransfer($tokenData, $user);
                break;

            case 'transfer_receive':
                $this->handleReceiveTransfer($tokenData, $user);
                break;

            case 'transfer_return_receive':
                $this->handleReceiveReturn($tokenData, $user);
                break;

            case 'procurement_approve':
                $this->handleApproveProcurement($tokenData, $user);
                break;

            case 'procurement_schedule':
                $this->handleScheduleProcurement($tokenData, $user);
                break;

            case 'procurement_receive':
                $this->handleReceiveProcurement($tokenData, $user);
                break;

            default:
                $this->showError('Unknown Action', 'The requested action type is not supported.');
                break;
        }
    }

    /**
     * Handle transfer verification via email
     */
    private function handleVerifyTransfer($tokenData, $user) {
        $transferId = $tokenData['related_id'];

        // Get transfer details
        $transfer = $this->transferModel->getTransferWithDetails($transferId);

        if (!$transfer) {
            $this->showError('Transfer Not Found', 'The transfer request could not be found.');
            return;
        }

        // Check if transfer is in correct status
        if ($transfer['status'] !== 'Pending Verification') {
            $this->showSuccess(
                'Already Processed',
                "This transfer has already been verified. Current status: <strong>{$transfer['status']}</strong>",
                $transferId
            );
            return;
        }

        // Perform verification
        $result = $this->transferModel->verifyTransfer(
            $transferId,
            $user['id'],
            'Verified via email action link'
        );

        if ($result['success']) {
            // Mark token as used
            $this->tokenManager->markTokenAsUsed($tokenData['token'], $this->getClientIP());

            // Invalidate other tokens for this action
            $this->tokenManager->invalidateTokensForAction('transfer_verify', $transferId);

            $this->showSuccess(
                'Transfer Verified Successfully',
                "Thank you, <strong>{$user['full_name']}</strong>. The transfer request has been verified and forwarded for approval.",
                $transferId,
                'The Finance Director or Asset Director will be notified to approve this transfer.'
            );
        } else {
            $this->showError('Verification Failed', $result['message']);
        }
    }

    /**
     * Handle transfer approval via email
     */
    private function handleApproveTransfer($tokenData, $user) {
        $transferId = $tokenData['related_id'];

        // Get transfer details
        $transfer = $this->transferModel->getTransferWithDetails($transferId);

        if (!$transfer) {
            $this->showError('Transfer Not Found', 'The transfer request could not be found.');
            return;
        }

        // Check if transfer is in correct status
        if ($transfer['status'] !== 'Pending Approval') {
            $this->showSuccess(
                'Already Processed',
                "This transfer has already been approved. Current status: <strong>{$transfer['status']}</strong>",
                $transferId
            );
            return;
        }

        // Perform approval
        $result = $this->transferModel->approveTransfer(
            $transferId,
            $user['id'],
            'Approved via email action link'
        );

        if ($result['success']) {
            // Mark token as used
            $this->tokenManager->markTokenAsUsed($tokenData['token'], $this->getClientIP());

            // Invalidate other tokens for this action
            $this->tokenManager->invalidateTokensForAction('transfer_approve', $transferId);

            $this->showSuccess(
                'Transfer Approved Successfully',
                "Thank you, <strong>{$user['full_name']}</strong>. The transfer request has been approved.",
                $transferId,
                'The FROM Project Manager will be notified to dispatch the asset.'
            );
        } else {
            $this->showError('Approval Failed', $result['message']);
        }
    }

    /**
     * Handle transfer dispatch confirmation via email
     */
    private function handleDispatchTransfer($tokenData, $user) {
        $transferId = $tokenData['related_id'];

        // Get transfer details
        $transfer = $this->transferModel->getTransferWithDetails($transferId);

        if (!$transfer) {
            $this->showError('Transfer Not Found', 'The transfer request could not be found.');
            return;
        }

        // Check if transfer is in correct status
        if ($transfer['status'] !== 'Approved') {
            $this->showSuccess(
                'Already Processed',
                "This transfer has already been dispatched. Current status: <strong>{$transfer['status']}</strong>",
                $transferId
            );
            return;
        }

        // Perform dispatch
        $result = $this->transferModel->dispatchTransfer(
            $transferId,
            $user['id'],
            'Dispatched via email action link'
        );

        if ($result['success']) {
            // Mark token as used
            $this->tokenManager->markTokenAsUsed($tokenData['token'], $this->getClientIP());

            // Invalidate other tokens for this action
            $this->tokenManager->invalidateTokensForAction('transfer_dispatch', $transferId);

            $this->showSuccess(
                'Dispatch Confirmed Successfully',
                "Thank you, <strong>{$user['full_name']}</strong>. The asset dispatch has been confirmed and is now in transit.",
                $transferId,
                'The TO Project Manager will be notified to confirm receipt upon arrival.'
            );
        } else {
            $this->showError('Dispatch Failed', $result['message']);
        }
    }

    /**
     * Handle transfer receipt/completion via email
     */
    private function handleReceiveTransfer($tokenData, $user) {
        $transferId = $tokenData['related_id'];

        // Get transfer details
        $transfer = $this->transferModel->getTransferWithDetails($transferId);

        if (!$transfer) {
            $this->showError('Transfer Not Found', 'The transfer request could not be found.');
            return;
        }

        // Check if transfer is in correct status
        if ($transfer['status'] !== 'In Transit') {
            $this->showSuccess(
                'Already Processed',
                "This transfer has already been completed. Current status: <strong>{$transfer['status']}</strong>",
                $transferId
            );
            return;
        }

        // Perform receipt/completion
        $result = $this->transferModel->receiveTransfer(
            $transferId,
            $user['id'],
            'Received and completed via email action link'
        );

        if ($result['success']) {
            // Mark token as used
            $this->tokenManager->markTokenAsUsed($tokenData['token'], $this->getClientIP());

            // Invalidate other tokens for this action
            $this->tokenManager->invalidateTokensForAction('transfer_receive', $transferId);

            $this->showSuccess(
                'Transfer Completed Successfully',
                "Thank you, <strong>{$user['full_name']}</strong>. The asset has been received and the transfer is now complete.",
                $transferId,
                'All parties have been notified of the successful transfer completion.'
            );
        } else {
            $this->showError('Receipt Failed', $result['message']);
        }
    }

    /**
     * Handle return receipt confirmation via email
     */
    private function handleReceiveReturn($tokenData, $user) {
        $transferId = $tokenData['related_id'];

        // Get transfer details
        $transfer = $this->transferModel->getTransferWithDetails($transferId);

        if (!$transfer) {
            $this->showError('Transfer Not Found', 'The transfer request could not be found.');
            return;
        }

        // Check if return is in correct status
        if ($transfer['return_status'] !== 'in_return_transit') {
            $this->showSuccess(
                'Already Processed',
                "This return has already been completed. Current return status: <strong>{$transfer['return_status']}</strong>",
                $transferId
            );
            return;
        }

        // Perform return receipt
        $result = $this->transferModel->receiveReturn(
            $transferId,
            $user['id'],
            'Return received via email action link'
        );

        if ($result['success']) {
            // Mark token as used
            $this->tokenManager->markTokenAsUsed($tokenData['token'], $this->getClientIP());

            // Invalidate other tokens for this action
            $this->tokenManager->invalidateTokensForAction('transfer_return_receive', $transferId);

            $this->showSuccess(
                'Return Completed Successfully',
                "Thank you, <strong>{$user['full_name']}</strong>. The asset has been returned and is now available at your project.",
                $transferId,
                'All parties have been notified of the successful return completion.'
            );
        } else {
            $this->showError('Return Receipt Failed', $result['message']);
        }
    }

    /**
     * Handle procurement order approval via email
     */
    private function handleApproveProcurement($tokenData, $user) {
        $orderId = $tokenData['related_id'];

        // Get order details
        $order = $this->procurementModel->getProcurementOrderWithDetails($orderId);

        if (!$order) {
            $this->showError('Order Not Found', 'The procurement order could not be found.');
            return;
        }

        // Check if order is in correct status
        if ($order['status'] !== 'Pending' && $order['status'] !== 'Reviewed') {
            $this->showSuccess(
                'Already Processed',
                "This procurement order has already been processed. Current status: <strong>{$order['status']}</strong>",
                null,
                null,
                'procurement-orders/view',
                $orderId
            );
            return;
        }

        // Perform approval
        $result = $this->procurementModel->updateStatus(
            $orderId,
            'Approved',
            $user['id'],
            'Approved via email action link'
        );

        if ($result['success']) {
            // Mark token as used
            $this->tokenManager->markTokenAsUsed($tokenData['token'], $this->getClientIP());

            // Invalidate other tokens for this action
            $this->tokenManager->invalidateTokensForAction('procurement_approve', $orderId);

            $this->showSuccess(
                'Procurement Order Approved Successfully',
                "Thank you, <strong>{$user['full_name']}</strong>. The procurement order has been approved.",
                null,
                'The Procurement Officer will be notified to schedule delivery.',
                'procurement-orders/view',
                $orderId
            );
        } else {
            $this->showError('Approval Failed', $result['message']);
        }
    }

    /**
     * Handle procurement delivery scheduling via email
     */
    private function handleScheduleProcurement($tokenData, $user) {
        $orderId = $tokenData['related_id'];

        // Get order details
        $order = $this->procurementModel->getProcurementOrderWithDetails($orderId);

        if (!$order) {
            $this->showError('Order Not Found', 'The procurement order could not be found.');
            return;
        }

        // Check if order is in correct status
        if ($order['status'] !== 'Approved') {
            $this->showSuccess(
                'Already Processed',
                "This procurement order has already been scheduled. Current status: <strong>{$order['status']}</strong>",
                null,
                null,
                'procurement-orders/view',
                $orderId
            );
            return;
        }

        // This action just acknowledges - actual scheduling happens in the system
        // Mark token as used
        $this->tokenManager->markTokenAsUsed($tokenData['token'], $this->getClientIP());

        // Invalidate other tokens for this action
        $this->tokenManager->invalidateTokensForAction('procurement_schedule', $orderId);

        $this->showSuccess(
            'Scheduling Acknowledged',
            "Thank you, <strong>{$user['full_name']}</strong>. Please log in to the system to schedule the delivery date and coordinate with the vendor.",
            null,
            'You can set the scheduled delivery date and tracking information in the order details.',
            'procurement-orders/view',
            $orderId
        );
    }

    /**
     * Handle procurement receipt confirmation via email
     */
    private function handleReceiveProcurement($tokenData, $user) {
        $orderId = $tokenData['related_id'];

        // Get order details
        $order = $this->procurementModel->getProcurementOrderWithDetails($orderId);

        if (!$order) {
            $this->showError('Order Not Found', 'The procurement order could not be found.');
            return;
        }

        // Check if order is in correct status
        if ($order['status'] !== 'Delivered') {
            $this->showSuccess(
                'Already Processed',
                "This procurement order has already been received. Current status: <strong>{$order['status']}</strong>",
                null,
                null,
                'procurement-orders/view',
                $orderId
            );
            return;
        }

        // Perform receipt confirmation
        $result = $this->procurementModel->updateStatus(
            $orderId,
            'Received',
            $user['id'],
            'Items received and confirmed via email action link'
        );

        if ($result['success']) {
            // Mark token as used
            $this->tokenManager->markTokenAsUsed($tokenData['token'], $this->getClientIP());

            // Invalidate other tokens for this action
            $this->tokenManager->invalidateTokensForAction('procurement_receive', $orderId);

            $this->showSuccess(
                'Items Received Successfully',
                "Thank you, <strong>{$user['full_name']}</strong>. The procurement order has been marked as received and completed.",
                null,
                'All parties have been notified of the successful delivery completion.',
                'procurement-orders/view',
                $orderId
            );
        } else {
            $this->showError('Receipt Failed', $result['message']);
        }
    }

    /**
     * Display success page
     */
    private function showSuccess($title, $message, $transferId = null, $additionalInfo = null, $route = 'transfers/view', $routeId = null) {
        $pageTitle = 'Action Successful - ConstructLink™';
        $icon = 'bi-check-circle-fill';
        $iconColor = 'text-success';
        $actionButton = '';

        // Only show "View Details" button if user is logged in
        // Email action doesn't require login, but viewing details does
        require_once APP_ROOT . '/core/Auth.php';
        $auth = Auth::getInstance();
        $isLoggedIn = $auth->isLoggedIn();

        $id = $routeId ?? $transferId;
        if ($id && $isLoggedIn) {
            $viewText = strpos($route, 'procurement') !== false ? 'View Order Details' : 'View Transfer Details';
            $actionButton = "
                <div class=\"mt-4\">
                    <a href=\"?route={$route}&id={$id}\" class=\"btn btn-primary btn-lg\">
                        <i class=\"bi bi-eye me-2\"></i>{$viewText}
                    </a>
                </div>
            ";
        } elseif ($id && !$isLoggedIn) {
            // Show login button instead
            $actionButton = "
                <div class=\"mt-4\">
                    <p class=\"text-muted mb-3\">To view full details, please log in to the system.</p>
                    <a href=\"?route=login\" class=\"btn btn-primary btn-lg\">
                        <i class=\"bi bi-box-arrow-in-right me-2\"></i>Login to View Details
                    </a>
                </div>
            ";
        }

        $additionalInfoHtml = '';
        if ($additionalInfo) {
            $additionalInfoHtml = "
                <div class=\"alert alert-info mt-3\">
                    <i class=\"bi bi-info-circle me-2\"></i>{$additionalInfo}
                </div>
            ";
        }

        ob_start();
        ?>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center p-5">
                            <i class="bi <?= $icon ?> <?= $iconColor ?>" style="font-size: 72px;"></i>
                            <h2 class="mt-4 mb-3"><?= htmlspecialchars($title) ?></h2>
                            <p class="lead"><?= $message ?></p>
                            <?= $additionalInfoHtml ?>
                            <?= $actionButton ?>
                            <div class="mt-4">
                                <a href="?route=dashboard" class="btn btn-outline-secondary">
                                    <i class="bi bi-house me-2"></i>Go to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        include APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Display error page
     */
    private function showError($title, $message) {
        $pageTitle = 'Action Failed - ConstructLink™';
        $icon = 'bi-exclamation-triangle-fill';
        $iconColor = 'text-danger';

        ob_start();
        ?>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center p-5">
                            <i class="bi <?= $icon ?> <?= $iconColor ?>" style="font-size: 72px;"></i>
                            <h2 class="mt-4 mb-3"><?= htmlspecialchars($title) ?></h2>
                            <p class="lead"><?= htmlspecialchars($message) ?></p>
                            <div class="mt-4">
                                <a href="?route=login" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login to System
                                </a>
                            </div>
                            <div class="mt-3">
                                <a href="?route=dashboard" class="btn btn-outline-secondary">
                                    <i class="bi bi-house me-2"></i>Go to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        include APP_ROOT . '/views/layouts/main.php';
    }

    /**
     * Get client IP address
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}
?>
