<?php
/**
 * ConstructLink™ Procurement Order Email Templates
 * Procurement-specific email notifications using generic workflow templates
 */

require_once APP_ROOT . '/core/WorkflowEmailTemplates.php';

class ProcurementEmailTemplates extends WorkflowEmailTemplates {

    /**
     * Send approval request email to Finance Director
     *
     * @param array $order Procurement order data with items
     * @param array $user Finance Director user data
     * @return array Result
     */
    public function sendApprovalRequest($order, $user) {
        $itemCount = isset($order['items']) ? count($order['items']) : ($order['item_count'] ?? 0);

        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'procurement_approve',
            'related_id' => $order['id'],
            'title' => 'Procurement Order Approval Required',
            'message' => 'A new procurement order requires your approval for budget authorization.',
            'details' => [
                'PO Number' => $order['po_number'],
                'Title' => $order['title'] ?? 'N/A',
                'Vendor' => $order['vendor_name'] ?? 'N/A',
                'Project' => $order['project_name'] ?? 'N/A',
                'Total Amount' => '₱' . number_format($order['net_total'] ?? 0, 2),
                'Items' => $itemCount . ' item(s)',
                'Requested By' => $order['requested_by_name'] ?? 'N/A',
                'Date Needed' => isset($order['date_needed']) ? date('M j, Y', strtotime($order['date_needed'])) : 'N/A'
            ],
            'button_text' => 'Review & Approve',
            'button_color' => '#28a745',
            'additional_info' => 'Please review the order details and budget allocation before approving.',
            'subject_suffix' => "PO {$order['po_number']}"
        ]);
    }

    /**
     * Send schedule delivery request to Procurement Officer
     *
     * @param array $order Procurement order data
     * @param array $user Procurement Officer user data
     * @return array Result
     */
    public function sendScheduleDeliveryRequest($order, $user) {
        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'procurement_schedule',
            'related_id' => $order['id'],
            'title' => 'Schedule Procurement Delivery',
            'message' => 'An approved procurement order is ready for delivery scheduling.',
            'details' => [
                'PO Number' => $order['po_number'],
                'Title' => $order['title'] ?? 'N/A',
                'Vendor' => $order['vendor_name'] ?? 'N/A',
                'Project' => $order['project_name'] ?? 'N/A',
                'Total Amount' => '₱' . number_format($order['net_total'] ?? 0, 2),
                'Approved By' => $order['approved_by_name'] ?? 'N/A',
                'Date Needed' => isset($order['date_needed']) ? date('M j, Y', strtotime($order['date_needed'])) : 'N/A'
            ],
            'button_text' => 'Schedule Delivery',
            'button_color' => '#007bff',
            'additional_info' => 'Please coordinate with the vendor and schedule the delivery date.',
            'subject_suffix' => "PO {$order['po_number']}"
        ]);
    }

    /**
     * Send delivery notification to Warehouseman
     *
     * @param array $order Procurement order data
     * @param array $user Warehouseman user data
     * @return array Result
     */
    public function sendDeliveryNotification($order, $user) {
        $scheduledDate = isset($order['scheduled_delivery_date']) ? date('M j, Y', strtotime($order['scheduled_delivery_date'])) : 'TBD';

        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'procurement_receive',
            'related_id' => $order['id'],
            'title' => 'Procurement Delivery - Receipt Confirmation Required',
            'message' => 'Items from a procurement order have been delivered and require receipt confirmation.',
            'details' => [
                'PO Number' => $order['po_number'],
                'Title' => $order['title'] ?? 'N/A',
                'Vendor' => $order['vendor_name'] ?? 'N/A',
                'Delivery Location' => $order['delivery_location'] ?? 'Warehouse',
                'Total Amount' => '₱' . number_format($order['net_total'] ?? 0, 2),
                'Items to Receive' => ($order['item_count'] ?? 0) . ' item(s)',
                'Scheduled Delivery' => $scheduledDate,
                'Tracking Number' => $order['tracking_number'] ?? 'N/A'
            ],
            'button_text' => 'Confirm Receipt',
            'button_color' => '#28a745',
            'additional_info' => 'Please inspect all items upon receipt and confirm delivery. Report any discrepancies immediately.',
            'subject_suffix' => "PO {$order['po_number']}"
        ]);
    }

    /**
     * Send procurement order completion notification
     *
     * @param array $order Procurement order data
     * @param array $users Array of users to notify
     * @return array Result
     */
    public function sendCompletedNotification($order, $users) {
        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        return $this->sendCompletionNotification([
            'users' => $users,
            'title' => 'Procurement Order Completed',
            'message' => 'The following procurement order has been successfully completed and items received:',
            'details' => [
                'PO Number' => $order['po_number'],
                'Title' => $order['title'] ?? 'N/A',
                'Vendor' => $order['vendor_name'] ?? 'N/A',
                'Project' => $order['project_name'] ?? 'N/A',
                'Total Amount' => '₱' . number_format($order['net_total'] ?? 0, 2),
                'Items Received' => ($order['item_count'] ?? 0) . ' item(s)',
                'Received By' => $order['received_by_name'] ?? 'N/A',
                'Completion Date' => isset($order['received_at']) ? date('M j, Y g:i A', strtotime($order['received_at'])) : date('M j, Y g:i A')
            ],
            'alert_type' => 'success',
            'view_link' => "{$baseUrl}/?route=procurement-orders/view&id={$order['id']}",
            'view_link_text' => 'View Order Details',
            'subject_suffix' => "PO {$order['po_number']}"
        ]);
    }

    /**
     * Send procurement order status update notification
     *
     * @param array $order Procurement order data
     * @param array $user User to notify
     * @param string $statusMessage Custom status message
     * @return array Result
     */
    public function sendStatusUpdate($order, $user, $statusMessage = null) {
        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $message = $statusMessage ?? "The status of procurement order has been updated.";

        // Determine next step based on status
        $nextSteps = [
            'Pending' => 'Awaiting approval by Finance Director',
            'Reviewed' => 'Under review for approval',
            'Approved' => 'Ready for delivery scheduling',
            'Scheduled for Delivery' => 'Awaiting delivery from vendor',
            'In Transit' => 'Items are being delivered',
            'Delivered' => 'Awaiting receipt confirmation by Warehouseman',
            'For Revision' => 'Procurement Officer will revise the order',
            'Rejected' => 'Order has been rejected and closed'
        ];

        return parent::sendStatusUpdateEmail([
            'user' => $user,
            'title' => 'Procurement Order Status Update',
            'message' => $message,
            'details' => [
                'PO Number' => $order['po_number'],
                'Title' => $order['title'] ?? 'N/A',
                'Vendor' => $order['vendor_name'] ?? 'N/A',
                'Total Amount' => '₱' . number_format($order['net_total'] ?? 0, 2),
                'Items' => ($order['item_count'] ?? 0) . ' item(s)'
            ],
            'status' => $order['status'],
            'next_step' => $nextSteps[$order['status']] ?? null,
            'view_link' => "{$baseUrl}/?route=procurement-orders/view&id={$order['id']}"
        ]);
    }

    /**
     * Send rejection notification to requester
     *
     * @param array $order Procurement order data
     * @param array $user Requester user data
     * @param string $rejectionReason Reason for rejection
     * @return array Result
     */
    public function sendRejectionNotification($order, $user, $rejectionReason = null) {
        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        $message = "Your procurement order has been rejected.";
        if ($rejectionReason) {
            $message .= " <br><br><strong>Reason:</strong> " . htmlspecialchars($rejectionReason);
        }

        return $this->sendCompletionNotification([
            'users' => [$user],
            'title' => 'Procurement Order Rejected',
            'message' => $message,
            'details' => [
                'PO Number' => $order['po_number'],
                'Title' => $order['title'] ?? 'N/A',
                'Vendor' => $order['vendor_name'] ?? 'N/A',
                'Total Amount' => '₱' . number_format($order['net_total'] ?? 0, 2),
                'Rejected By' => $order['approved_by_name'] ?? 'N/A',
                'Rejection Date' => date('M j, Y')
            ],
            'alert_type' => 'warning',
            'view_link' => "{$baseUrl}/?route=procurement-orders/view&id={$order['id']}",
            'view_link_text' => 'View Order Details',
            'subject_suffix' => "PO {$order['po_number']}"
        ]);
    }

    /**
     * Send revision request notification to Procurement Officer
     *
     * @param array $order Procurement order data
     * @param array $user Procurement Officer user data
     * @param string $revisionNotes Notes for revision
     * @return array Result
     */
    public function sendRevisionRequest($order, $user, $revisionNotes = null) {
        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        $message = "The procurement order requires revision before approval.";
        if ($revisionNotes) {
            $message .= " <br><br><strong>Revision Notes:</strong> " . htmlspecialchars($revisionNotes);
        }

        return $this->sendCompletionNotification([
            'users' => [$user],
            'title' => 'Procurement Order Requires Revision',
            'message' => $message,
            'details' => [
                'PO Number' => $order['po_number'],
                'Title' => $order['title'] ?? 'N/A',
                'Vendor' => $order['vendor_name'] ?? 'N/A',
                'Total Amount' => '₱' . number_format($order['net_total'] ?? 0, 2),
                'Reviewed By' => $order['approved_by_name'] ?? 'N/A'
            ],
            'alert_type' => 'warning',
            'view_link' => "{$baseUrl}/?route=procurement-orders/view&id={$order['id']}",
            'view_link_text' => 'Review & Revise Order',
            'subject_suffix' => "PO {$order['po_number']}"
        ]);
    }
}
?>
