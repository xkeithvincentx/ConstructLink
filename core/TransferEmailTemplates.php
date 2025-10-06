<?php
/**
 * ConstructLink™ Transfer Email Templates
 * Pre-defined email templates for transfer workflow notifications
 */

class TransferEmailTemplates {
    private $emailService;
    private $tokenManager;

    public function __construct() {
        $this->emailService = EmailService::getInstance();
        $this->tokenManager = new EmailActionToken();
    }

    /**
     * Send verification request email to Project Manager
     *
     * @param array $transfer Transfer data with details
     * @param array $user Project Manager user data
     * @return array Result
     */
    public function sendVerificationRequest($transfer, $user) {
        if (empty($user['email'])) {
            return ['success' => false, 'message' => 'User has no email address'];
        }

        // Generate one-click verification token
        $tokenResult = $this->tokenManager->generateToken(
            'transfer_verify',
            $transfer['id'],
            $user['id'],
            48 // 48 hours expiration
        );

        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $title = "Transfer Verification Required";
        $content = "
            <p>Dear {$user['full_name']},</p>

            <p>A new asset transfer request requires your verification:</p>

            <div style=\"background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;\">
                <p style=\"margin: 5px 0;\"><strong>Transfer ID:</strong> #{$transfer['id']}</p>
                <p style=\"margin: 5px 0;\"><strong>Asset:</strong> {$transfer['asset_ref']} - {$transfer['asset_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>From:</strong> {$transfer['from_project_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>To:</strong> {$transfer['to_project_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>Type:</strong> " . ucfirst($transfer['transfer_type']) . "</p>
                <p style=\"margin: 5px 0;\"><strong>Requested By:</strong> {$transfer['initiated_by_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>Reason:</strong> {$transfer['reason']}</p>
            </div>

            <p>Click the button below to verify this transfer request. No login required - this is a secure one-time link.</p>

            <p style=\"color: #6c757d; font-size: 14px; margin-top: 20px;\">
                <strong>Note:</strong> This link will expire in 48 hours.
            </p>
        ";

        $actions = [
            [
                'text' => 'Verify Transfer',
                'url' => $tokenResult['url'],
                'color' => '#28a745'
            ]
        ];

        $htmlBody = $this->emailService->renderTemplate($title, $content, $actions);

        return $this->emailService->send(
            $user['email'],
            "ConstructLink™: {$title} - Transfer #{$transfer['id']}",
            $htmlBody,
            $user['full_name']
        );
    }

    /**
     * Send approval request email to Finance/Asset Director
     *
     * @param array $transfer Transfer data with details
     * @param array $user Director user data
     * @return array Result
     */
    public function sendApprovalRequest($transfer, $user) {
        if (empty($user['email'])) {
            return ['success' => false, 'message' => 'User has no email address'];
        }

        // Generate one-click approval token
        $tokenResult = $this->tokenManager->generateToken(
            'transfer_approve',
            $transfer['id'],
            $user['id'],
            48 // 48 hours expiration
        );

        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $title = "Transfer Approval Required";
        $content = "
            <p>Dear {$user['full_name']},</p>

            <p>A verified asset transfer request requires your approval:</p>

            <div style=\"background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;\">
                <p style=\"margin: 5px 0;\"><strong>Transfer ID:</strong> #{$transfer['id']}</p>
                <p style=\"margin: 5px 0;\"><strong>Asset:</strong> {$transfer['asset_ref']} - {$transfer['asset_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>From:</strong> {$transfer['from_project_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>To:</strong> {$transfer['to_project_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>Type:</strong> " . ucfirst($transfer['transfer_type']) . "</p>
                <p style=\"margin: 5px 0;\"><strong>Requested By:</strong> {$transfer['initiated_by_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>Verified By:</strong> {$transfer['verified_by_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>Reason:</strong> {$transfer['reason']}</p>
            </div>

            <p>Click the button below to approve this transfer request. No login required - this is a secure one-time link.</p>

            <p style=\"color: #6c757d; font-size: 14px; margin-top: 20px;\">
                <strong>Note:</strong> This link will expire in 48 hours.
            </p>
        ";

        $actions = [
            [
                'text' => 'Approve Transfer',
                'url' => $tokenResult['url'],
                'color' => '#007bff'
            ]
        ];

        $htmlBody = $this->emailService->renderTemplate($title, $content, $actions);

        return $this->emailService->send(
            $user['email'],
            "ConstructLink™: {$title} - Transfer #{$transfer['id']}",
            $htmlBody,
            $user['full_name']
        );
    }

    /**
     * Send dispatch notification to FROM Project Manager
     *
     * @param array $transfer Transfer data with details
     * @param array $user FROM Project Manager user data
     * @return array Result
     */
    public function sendDispatchRequest($transfer, $user) {
        if (empty($user['email'])) {
            return ['success' => false, 'message' => 'User has no email address'];
        }

        // Generate one-click dispatch token
        $tokenResult = $this->tokenManager->generateToken(
            'transfer_dispatch',
            $transfer['id'],
            $user['id'],
            48 // 48 hours expiration
        );

        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $title = "Asset Dispatch Confirmation Required";
        $content = "
            <p>Dear {$user['full_name']},</p>

            <p>An approved transfer requires you to confirm asset dispatch:</p>

            <div style=\"background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;\">
                <p style=\"margin: 5px 0;\"><strong>Transfer ID:</strong> #{$transfer['id']}</p>
                <p style=\"margin: 5px 0;\"><strong>Asset:</strong> {$transfer['asset_ref']} - {$transfer['asset_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>From:</strong> {$transfer['from_project_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>To:</strong> {$transfer['to_project_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>Type:</strong> " . ucfirst($transfer['transfer_type']) . "</p>
                <p style=\"margin: 5px 0;\"><strong>Approved By:</strong> {$transfer['approved_by_name']}</p>
            </div>

            <p>Please confirm that the asset has been dispatched and is on its way to the destination project.</p>

            <p>Click the button below to confirm dispatch. No login required - this is a secure one-time link.</p>

            <p style=\"color: #6c757d; font-size: 14px; margin-top: 20px;\">
                <strong>Note:</strong> This link will expire in 48 hours.
            </p>
        ";

        $actions = [
            [
                'text' => 'Confirm Dispatch',
                'url' => $tokenResult['url'],
                'color' => '#ffc107'
            ]
        ];

        $htmlBody = $this->emailService->renderTemplate($title, $content, $actions);

        return $this->emailService->send(
            $user['email'],
            "ConstructLink™: {$title} - Transfer #{$transfer['id']}",
            $htmlBody,
            $user['full_name']
        );
    }

    /**
     * Send receive/complete notification to TO Project Manager
     *
     * @param array $transfer Transfer data with details
     * @param array $user TO Project Manager user data
     * @return array Result
     */
    public function sendReceiveRequest($transfer, $user) {
        if (empty($user['email'])) {
            return ['success' => false, 'message' => 'User has no email address'];
        }

        // Generate one-click receive token
        $tokenResult = $this->tokenManager->generateToken(
            'transfer_receive',
            $transfer['id'],
            $user['id'],
            48 // 48 hours expiration
        );

        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $title = "Asset Arrival - Receipt Confirmation Required";
        $content = "
            <p>Dear {$user['full_name']},</p>

            <p>An asset is in transit to your project and requires receipt confirmation:</p>

            <div style=\"background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;\">
                <p style=\"margin: 5px 0;\"><strong>Transfer ID:</strong> #{$transfer['id']}</p>
                <p style=\"margin: 5px 0;\"><strong>Asset:</strong> {$transfer['asset_ref']} - {$transfer['asset_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>From:</strong> {$transfer['from_project_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>To:</strong> {$transfer['to_project_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>Type:</strong> " . ucfirst($transfer['transfer_type']) . "</p>
                <p style=\"margin: 5px 0;\"><strong>Dispatched By:</strong> {$transfer['dispatched_by_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>Dispatch Date:</strong> " . date('M j, Y', strtotime($transfer['dispatch_date'])) . "</p>
            </div>

            <p>Please confirm receipt once the asset has arrived at your project location.</p>

            <p>Click the button below to confirm receipt and complete the transfer. No login required - this is a secure one-time link.</p>

            <p style=\"color: #6c757d; font-size: 14px; margin-top: 20px;\">
                <strong>Note:</strong> This link will expire in 48 hours.
            </p>
        ";

        $actions = [
            [
                'text' => 'Confirm Receipt',
                'url' => $tokenResult['url'],
                'color' => '#28a745'
            ]
        ];

        $htmlBody = $this->emailService->renderTemplate($title, $content, $actions);

        return $this->emailService->send(
            $user['email'],
            "ConstructLink™: {$title} - Transfer #{$transfer['id']}",
            $htmlBody,
            $user['full_name']
        );
    }

    /**
     * Send transfer completed notification (no action required)
     *
     * @param array $transfer Transfer data with details
     * @param array $users Array of users to notify
     * @return array Result
     */
    public function sendCompletedNotification($transfer, $users) {
        $title = "Transfer Completed Successfully";
        $content = "
            <p>The following asset transfer has been completed successfully:</p>

            <div style=\"background-color: #d4edda; border-left: 4px solid #28a745; padding: 20px; border-radius: 5px; margin: 20px 0;\">
                <p style=\"margin: 5px 0;\"><strong>Transfer ID:</strong> #{$transfer['id']}</p>
                <p style=\"margin: 5px 0;\"><strong>Asset:</strong> {$transfer['asset_ref']} - {$transfer['asset_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>From:</strong> {$transfer['from_project_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>To:</strong> {$transfer['to_project_name']}</p>
                <p style=\"margin: 5px 0;\"><strong>Type:</strong> " . ucfirst($transfer['transfer_type']) . "</p>
                <p style=\"margin: 5px 0;\"><strong>Completed:</strong> " . date('M j, Y g:i A') . "</p>
            </div>

            <p>The asset has been successfully transferred and all parties have been notified.</p>
        ";

        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $actions = [
            [
                'text' => 'View Transfer Details',
                'url' => "{$baseUrl}/?route=transfers/view&id={$transfer['id']}",
                'color' => '#007bff'
            ]
        ];

        $htmlBody = $this->emailService->renderTemplate($title, $content, $actions);

        $recipients = [];
        foreach ($users as $user) {
            if (!empty($user['email'])) {
                $recipients[] = [
                    'email' => $user['email'],
                    'name' => $user['full_name']
                ];
            }
        }

        if (empty($recipients)) {
            return ['success' => false, 'message' => 'No recipients with email addresses'];
        }

        // Send to all recipients
        foreach ($recipients as $recipient) {
            $this->emailService->send(
                $recipient['email'],
                "ConstructLink™: {$title} - Transfer #{$transfer['id']}",
                $htmlBody,
                $recipient['name']
            );
        }

        return ['success' => true, 'message' => 'Notifications sent'];
    }
}
?>
