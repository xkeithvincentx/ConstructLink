<?php
/**
 * ConstructLink™ Generic Workflow Email Templates
 * Reusable email notification system for all workflows (Transfers, Procurement, Maintenance, etc.)
 */

class WorkflowEmailTemplates {
    protected $emailService;
    protected $tokenManager;

    public function __construct() {
        $this->emailService = EmailService::getInstance();
        $this->tokenManager = new EmailActionToken();
    }

    /**
     * Send workflow action request email with one-click link
     *
     * @param array $config Email configuration
     *   - user: User data (must have 'email', 'full_name')
     *   - action_type: Token action type (e.g., 'transfer_verify', 'procurement_approve')
     *   - related_id: ID of the entity (transfer ID, procurement order ID, etc.)
     *   - title: Email title
     *   - greeting: Custom greeting (optional)
     *   - message: Main message text
     *   - details: Array of detail fields ['label' => 'value']
     *   - button_text: Text for action button
     *   - button_color: Color for action button (default: #007bff)
     *   - additional_info: Additional info text below button (optional)
     *   - token_expiry_hours: Token expiration in hours (default: 48)
     * @return array Result ['success' => bool, 'message' => string]
     */
    public function sendActionRequest($config) {
        // Validate required fields
        $required = ['user', 'action_type', 'related_id', 'title', 'message', 'button_text'];
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                return ['success' => false, 'message' => "Missing required field: {$field}"];
            }
        }

        $user = $config['user'];

        if (empty($user['email'])) {
            return ['success' => false, 'message' => 'User has no email address'];
        }

        // Generate one-click action token
        $tokenResult = $this->tokenManager->generateToken(
            $config['action_type'],
            $config['related_id'],
            $user['id'],
            $config['token_expiry_hours'] ?? 48
        );

        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        // Build greeting
        $greeting = $config['greeting'] ?? "Dear {$user['full_name']},";

        // Build details section
        $detailsHtml = '';
        if (!empty($config['details'])) {
            $detailsHtml = '<div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">';
            foreach ($config['details'] as $label => $value) {
                $detailsHtml .= '<p style="margin: 5px 0;"><strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($value) . '</p>';
            }
            $detailsHtml .= '</div>';
        }

        // Build additional info section
        $additionalInfoHtml = '';
        if (!empty($config['additional_info'])) {
            $additionalInfoHtml = '
                <p style="color: #6c757d; font-size: 14px; margin-top: 20px;">
                    <strong>Note:</strong> ' . htmlspecialchars($config['additional_info']) . '
                </p>
            ';
        }

        // Default additional info about token expiry
        $expiryHours = $config['token_expiry_hours'] ?? 48;
        $defaultExpiryInfo = '
            <p style="color: #6c757d; font-size: 14px; margin-top: 20px;">
                <strong>Note:</strong> This link will expire in ' . $expiryHours . ' hours.
            </p>
        ';

        // Build email content
        $content = "
            <p>{$greeting}</p>
            <p>{$config['message']}</p>
            {$detailsHtml}
            <p>Click the button below to proceed. No login required - this is a secure one-time link.</p>
            {$additionalInfoHtml}
            {$defaultExpiryInfo}
        ";

        // Build action button
        $actions = [
            [
                'text' => $config['button_text'],
                'url' => $tokenResult['url'],
                'color' => $config['button_color'] ?? '#007bff'
            ]
        ];

        // Render and send email
        $htmlBody = $this->emailService->renderTemplate($config['title'], $content, $actions);
        $subject = "ConstructLink™: {$config['title']}";

        if (!empty($config['subject_suffix'])) {
            $subject .= " - {$config['subject_suffix']}";
        }

        return $this->emailService->send(
            $user['email'],
            $subject,
            $htmlBody,
            $user['full_name']
        );
    }

    /**
     * Send workflow completion notification (no action required)
     *
     * @param array $config Email configuration
     *   - users: Array of user data
     *   - title: Email title
     *   - message: Main message text
     *   - details: Array of detail fields ['label' => 'value']
     *   - alert_type: success|info|warning (default: success)
     *   - view_link: Optional link to view details
     *   - view_link_text: Text for view link button (default: "View Details")
     *   - subject_suffix: Optional suffix for email subject
     * @return array Result
     */
    public function sendCompletionNotification($config) {
        // Validate required fields
        $required = ['users', 'title', 'message'];
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                return ['success' => false, 'message' => "Missing required field: {$field}"];
            }
        }

        // Build alert style based on type
        $alertColors = [
            'success' => ['bg' => '#d4edda', 'border' => '#28a745'],
            'info' => ['bg' => '#d1ecf1', 'border' => '#17a2b8'],
            'warning' => ['bg' => '#fff3cd', 'border' => '#ffc107']
        ];
        $alertType = $config['alert_type'] ?? 'success';
        $colors = $alertColors[$alertType] ?? $alertColors['success'];

        // Build details section
        $detailsHtml = '';
        if (!empty($config['details'])) {
            $detailsHtml = '<div style="background-color: ' . $colors['bg'] . '; border-left: 4px solid ' . $colors['border'] . '; padding: 20px; border-radius: 5px; margin: 20px 0;">';
            foreach ($config['details'] as $label => $value) {
                $detailsHtml .= '<p style="margin: 5px 0;"><strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($value) . '</p>';
            }
            $detailsHtml .= '</div>';
        }

        // Build content
        $content = "
            <p>{$config['message']}</p>
            {$detailsHtml}
        ";

        // Build action button if view link provided
        $actions = [];
        if (!empty($config['view_link'])) {
            $actions[] = [
                'text' => $config['view_link_text'] ?? 'View Details',
                'url' => $config['view_link'],
                'color' => '#007bff'
            ];
        }

        // Render email
        $htmlBody = $this->emailService->renderTemplate($config['title'], $content, $actions);
        $subject = "ConstructLink™: {$config['title']}";

        if (!empty($config['subject_suffix'])) {
            $subject .= " - {$config['subject_suffix']}";
        }

        // Send to all users
        $recipients = [];
        foreach ($config['users'] as $user) {
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
                $subject,
                $htmlBody,
                $recipient['name']
            );
        }

        return ['success' => true, 'message' => 'Notifications sent', 'count' => count($recipients)];
    }

    /**
     * Send workflow status update notification (no action required)
     *
     * @param array $config Email configuration
     *   - user: User data
     *   - title: Email title
     *   - message: Main message text
     *   - details: Array of detail fields ['label' => 'value']
     *   - status: Current status
     *   - next_step: Description of next step (optional)
     *   - view_link: Optional link to view details
     * @return array Result
     */
    public function sendStatusUpdate($config) {
        // Validate required fields
        $required = ['user', 'title', 'message', 'status'];
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                return ['success' => false, 'message' => "Missing required field: {$field}"];
            }
        }

        $user = $config['user'];

        if (empty($user['email'])) {
            return ['success' => false, 'message' => 'User has no email address'];
        }

        // Build details section
        $detailsHtml = '';
        if (!empty($config['details'])) {
            $detailsHtml = '<div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">';
            foreach ($config['details'] as $label => $value) {
                $detailsHtml .= '<p style="margin: 5px 0;"><strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($value) . '</p>';
            }
            $detailsHtml .= '</div>';
        }

        // Build next step section
        $nextStepHtml = '';
        if (!empty($config['next_step'])) {
            $nextStepHtml = '
                <div style="background-color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p style="margin: 0;"><strong>Next Step:</strong> ' . htmlspecialchars($config['next_step']) . '</p>
                </div>
            ';
        }

        // Build content
        $content = "
            <p>Dear {$user['full_name']},</p>
            <p>{$config['message']}</p>
            {$detailsHtml}
            <p><strong>Current Status:</strong> <span style=\"padding: 5px 10px; background-color: #007bff; color: white; border-radius: 3px; font-size: 14px;\">{$config['status']}</span></p>
            {$nextStepHtml}
        ";

        // Build action button if view link provided
        $actions = [];
        if (!empty($config['view_link'])) {
            $actions[] = [
                'text' => 'View Details',
                'url' => $config['view_link'],
                'color' => '#007bff'
            ];
        }

        // Render and send email
        $htmlBody = $this->emailService->renderTemplate($config['title'], $content, $actions);
        $subject = "ConstructLink™: {$config['title']}";

        return $this->emailService->send(
            $user['email'],
            $subject,
            $htmlBody,
            $user['full_name']
        );
    }
}
?>
