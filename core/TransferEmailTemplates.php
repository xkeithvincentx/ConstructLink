<?php
/**
 * ConstructLink™ Transfer Email Templates
 * Transfer-specific email notifications using generic workflow templates
 */

require_once APP_ROOT . '/core/WorkflowEmailTemplates.php';

class TransferEmailTemplates extends WorkflowEmailTemplates {

    /**
     * Send verification request email to FROM Project Manager
     *
     * @param array $transfer Transfer data with details
     * @param array $user FROM Project Manager user data
     * @return array Result
     */
    public function sendVerificationRequest($transfer, $user) {
        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'transfer_verify',
            'related_id' => $transfer['id'],
            'title' => 'Transfer Verification Required',
            'message' => 'A new asset transfer request requires your verification. The requesting project needs this asset transferred from your project.',
            'details' => [
                'Transfer ID' => "#{$transfer['id']}",
                'Asset' => "{$transfer['asset_ref']} - {$transfer['asset_name']}",
                'From (Your Project)' => $transfer['from_project_name'],
                'To (Requesting Project)' => $transfer['to_project_name'],
                'Type' => ucfirst($transfer['transfer_type']),
                'Requested By' => $transfer['initiated_by_name'],
                'Reason' => $transfer['reason']
            ],
            'button_text' => 'Verify Transfer',
            'button_color' => '#28a745',
            'subject_suffix' => "Transfer #{$transfer['id']}"
        ]);
    }

    /**
     * Send approval request email to Finance/Asset Director
     *
     * @param array $transfer Transfer data with details
     * @param array $user Director user data
     * @return array Result
     */
    public function sendApprovalRequest($transfer, $user) {
        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'transfer_approve',
            'related_id' => $transfer['id'],
            'title' => 'Transfer Approval Required',
            'message' => 'A verified asset transfer request requires your approval.',
            'details' => [
                'Transfer ID' => "#{$transfer['id']}",
                'Asset' => "{$transfer['asset_ref']} - {$transfer['asset_name']}",
                'From' => $transfer['from_project_name'],
                'To' => $transfer['to_project_name'],
                'Type' => ucfirst($transfer['transfer_type']),
                'Requested By' => $transfer['initiated_by_name'],
                'Verified By' => $transfer['verified_by_name'] ?? 'N/A',
                'Reason' => $transfer['reason']
            ],
            'button_text' => 'Approve Transfer',
            'button_color' => '#007bff',
            'subject_suffix' => "Transfer #{$transfer['id']}"
        ]);
    }

    /**
     * Send dispatch notification to FROM Project Manager
     *
     * @param array $transfer Transfer data with details
     * @param array $user FROM Project Manager user data
     * @return array Result
     */
    public function sendDispatchRequest($transfer, $user) {
        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'transfer_dispatch',
            'related_id' => $transfer['id'],
            'title' => 'Asset Dispatch Confirmation Required',
            'message' => 'An approved transfer requires you to confirm that the asset has been dispatched from your project.',
            'details' => [
                'Transfer ID' => "#{$transfer['id']}",
                'Asset' => "{$transfer['asset_ref']} - {$transfer['asset_name']}",
                'From (Your Project)' => $transfer['from_project_name'],
                'To (Destination)' => $transfer['to_project_name'],
                'Type' => ucfirst($transfer['transfer_type']),
                'Approved By' => $transfer['approved_by_name'] ?? 'N/A'
            ],
            'button_text' => 'Confirm Dispatch',
            'button_color' => '#ffc107',
            'additional_info' => 'Please confirm that the asset has been packed and sent to the destination project.',
            'subject_suffix' => "Transfer #{$transfer['id']}"
        ]);
    }

    /**
     * Send receive/complete notification to TO Project Manager
     *
     * @param array $transfer Transfer data with details
     * @param array $user TO Project Manager user data
     * @return array Result
     */
    public function sendReceiveRequest($transfer, $user) {
        $dispatchDate = isset($transfer['dispatch_date']) ? date('M j, Y', strtotime($transfer['dispatch_date'])) : 'N/A';

        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'transfer_receive',
            'related_id' => $transfer['id'],
            'title' => 'Asset Arrival - Receipt Confirmation Required',
            'message' => 'An asset is in transit to your project and requires receipt confirmation.',
            'details' => [
                'Transfer ID' => "#{$transfer['id']}",
                'Asset' => "{$transfer['asset_ref']} - {$transfer['asset_name']}",
                'From' => $transfer['from_project_name'],
                'To (Your Project)' => $transfer['to_project_name'],
                'Type' => ucfirst($transfer['transfer_type']),
                'Dispatched By' => $transfer['dispatched_by_name'] ?? 'N/A',
                'Dispatch Date' => $dispatchDate
            ],
            'button_text' => 'Confirm Receipt',
            'button_color' => '#28a745',
            'additional_info' => 'Please confirm receipt once the asset has arrived at your project location.',
            'subject_suffix' => "Transfer #{$transfer['id']}"
        ]);
    }

    /**
     * Send transfer completed notification (no action required)
     *
     * @param array $transfer Transfer data with details
     * @param array $users Array of users to notify
     * @return array Result
     */
    public function sendCompletedNotification($transfer, $users) {
        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        return $this->sendCompletionNotification([
            'users' => $users,
            'title' => 'Transfer Completed Successfully',
            'message' => 'The following asset transfer has been completed successfully:',
            'details' => [
                'Transfer ID' => "#{$transfer['id']}",
                'Asset' => "{$transfer['asset_ref']} - {$transfer['asset_name']}",
                'From' => $transfer['from_project_name'],
                'To' => $transfer['to_project_name'],
                'Type' => ucfirst($transfer['transfer_type']),
                'Completed' => date('M j, Y g:i A')
            ],
            'alert_type' => 'success',
            'view_link' => "{$baseUrl}/?route=transfers/view&id={$transfer['id']}",
            'view_link_text' => 'View Transfer Details',
            'subject_suffix' => "Transfer #{$transfer['id']}"
        ]);
    }

    /**
     * Send transfer status update notification
     *
     * @param array $transfer Transfer data
     * @param array $user User to notify
     * @param string $statusMessage Custom status message
     * @return array Result
     */
    public function sendStatusUpdate($transfer, $user, $statusMessage = null) {
        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $message = $statusMessage ?? "The status of your transfer request has been updated.";

        return parent::sendStatusUpdate([
            'user' => $user,
            'title' => 'Transfer Status Update',
            'message' => $message,
            'details' => [
                'Transfer ID' => "#{$transfer['id']}",
                'Asset' => "{$transfer['asset_ref']} - {$transfer['asset_name']}",
                'From' => $transfer['from_project_name'],
                'To' => $transfer['to_project_name']
            ],
            'status' => $transfer['status'],
            'view_link' => "{$baseUrl}/?route=transfers/view&id={$transfer['id']}"
        ]);
    }
}
?>
