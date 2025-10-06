<?php
/**
 * ConstructLink™ Email Service
 * Handles email sending with PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailService {
    private static $instance = null;
    private $mailer;
    private $isConfigured = false;

    private function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Configure PHPMailer with application settings
     */
    private function configure() {
        try {
            // Check if email is configured
            if (empty(MAIL_HOST) || empty(MAIL_USERNAME) || empty(MAIL_PASSWORD)) {
                error_log("EmailService: Email not configured. Set MAIL_HOST, MAIL_USERNAME, and MAIL_PASSWORD in .env.php");
                $this->isConfigured = false;
                return;
            }

            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = MAIL_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = MAIL_USERNAME;
            $this->mailer->Password = MAIL_PASSWORD;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = MAIL_PORT;
            $this->mailer->CharSet = 'UTF-8';

            // Default sender
            $this->mailer->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

            // Enable debug in development
            if (APP_DEBUG) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
                $this->mailer->Debugoutput = function($str, $level) {
                    error_log("SMTP Debug: $str");
                };
            }

            $this->isConfigured = true;

        } catch (Exception $e) {
            error_log("EmailService configuration error: {$e->getMessage()}");
            $this->isConfigured = false;
        }
    }

    /**
     * Check if email service is configured
     */
    public function isConfigured() {
        return $this->isConfigured;
    }

    /**
     * Send an email
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body HTML body
     * @param string|null $recipientName Recipient name
     * @return array ['success' => bool, 'message' => string]
     */
    public function send($to, $subject, $body, $recipientName = null) {
        if (!$this->isConfigured) {
            error_log("EmailService: Cannot send email - service not configured");
            return [
                'success' => false,
                'message' => 'Email service not configured'
            ];
        }

        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearReplyTos();

            // Add recipient
            if ($recipientName) {
                $this->mailer->addAddress($to, $recipientName);
            } else {
                $this->mailer->addAddress($to);
            }

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            // Send
            $this->mailer->send();

            error_log("EmailService: Email sent successfully to {$to}");

            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];

        } catch (Exception $e) {
            error_log("EmailService send error: {$this->mailer->ErrorInfo}");
            return [
                'success' => false,
                'message' => "Email send failed: {$this->mailer->ErrorInfo}"
            ];
        }
    }

    /**
     * Send email to multiple recipients
     *
     * @param array $recipients Array of ['email' => string, 'name' => string]
     * @param string $subject Email subject
     * @param string $body HTML body
     * @return array ['success' => bool, 'sent' => int, 'failed' => int]
     */
    public function sendMultiple($recipients, $subject, $body) {
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            $email = $recipient['email'] ?? $recipient;
            $name = $recipient['name'] ?? null;

            $result = $this->send($email, $subject, $body, $name);

            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $sent > 0,
            'sent' => $sent,
            'failed' => $failed
        ];
    }

    /**
     * Generate email template with ConstructLink branding
     *
     * @param string $title Email title
     * @param string $content Email content (HTML)
     * @param array $actions Array of buttons ['text' => string, 'url' => string, 'color' => string]
     * @return string HTML email
     */
    public function renderTemplate($title, $content, $actions = []) {
        $actionButtons = '';

        foreach ($actions as $action) {
            $color = $action['color'] ?? '#007bff';
            $actionButtons .= "
                <tr>
                    <td align=\"center\" style=\"padding: 20px 0;\">
                        <a href=\"{$action['url']}\"
                           style=\"background-color: {$color};
                                  color: #ffffff;
                                  padding: 12px 30px;
                                  text-decoration: none;
                                  border-radius: 5px;
                                  display: inline-block;
                                  font-weight: bold;\">
                            {$action['text']}
                        </a>
                    </td>
                </tr>
            ";
        }

        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        $html = "
        <!DOCTYPE html>
        <html lang=\"en\">
        <head>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <title>{$title}</title>
        </head>
        <body style=\"margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;\">
            <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background-color: #f4f4f4; padding: 20px;\">
                <tr>
                    <td align=\"center\">
                        <table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" style=\"background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);\">
                            <!-- Header -->
                            <tr>
                                <td style=\"background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;\">
                                    <h1 style=\"color: #ffffff; margin: 0; font-size: 28px;\">ConstructLink™</h1>
                                    <p style=\"color: #ffffff; margin: 5px 0 0 0; opacity: 0.9;\">Asset Management System</p>
                                </td>
                            </tr>

                            <!-- Content -->
                            <tr>
                                <td style=\"padding: 40px 30px;\">
                                    <h2 style=\"color: #333333; margin-top: 0;\">{$title}</h2>
                                    <div style=\"color: #666666; line-height: 1.6;\">
                                        {$content}
                                    </div>
                                </td>
                            </tr>

                            <!-- Actions -->
                            {$actionButtons}

                            <!-- Footer -->
                            <tr>
                                <td style=\"background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #dee2e6;\">
                                    <p style=\"color: #6c757d; font-size: 12px; margin: 0;\">
                                        This is an automated message from ConstructLink™<br>
                                        V CUTAMORA CONSTRUCTION INC.<br>
                                        © " . date('Y') . " Ranoa Digital Solutions. All rights reserved.
                                    </p>
                                    <p style=\"color: #6c757d; font-size: 11px; margin: 10px 0 0 0;\">
                                        <a href=\"{$baseUrl}\" style=\"color: #667eea; text-decoration: none;\">Visit ConstructLink™</a>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return $html;
    }
}
?>
