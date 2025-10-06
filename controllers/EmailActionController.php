<?php
/**
 * ConstructLink™ Email Action Controller
 * Handles one-click actions from email links
 */

class EmailActionController {
    private $tokenManager;
    private $transferModel;

    public function __construct() {
        $this->tokenManager = new EmailActionToken();
        $this->transferModel = new TransferModel();
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
     * Display success page
     */
    private function showSuccess($title, $message, $transferId = null, $additionalInfo = null) {
        $pageTitle = 'Action Successful - ConstructLink™';
        $icon = 'bi-check-circle-fill';
        $iconColor = 'text-success';
        $actionButton = '';

        if ($transferId) {
            $actionButton = "
                <div class=\"mt-4\">
                    <a href=\"?route=transfers/view&id={$transferId}\" class=\"btn btn-primary btn-lg\">
                        <i class=\"bi bi-eye me-2\"></i>View Transfer Details
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
