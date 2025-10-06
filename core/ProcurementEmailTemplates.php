<?php
/**
 * ConstructLink™ Procurement Order Email Templates
 * Example implementation using generic workflow templates
 */

require_once APP_ROOT . '/core/WorkflowEmailTemplates.php';

class ProcurementEmailTemplates extends WorkflowEmailTemplates {

    /**
     * Send procurement order approval request
     *
     * @param array $order Procurement order data
     * @param array $user Approver user data
     * @return array Result
     */
    public function sendApprovalRequest($order, $user) {
        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'procurement_approve',
            'related_id' => $order['id'],
            'title' => 'Procurement Order Approval Required',
            'message' => 'A new procurement order requires your approval.',
            'details' => [
                'PO Number' => $order['po_number'],
                'Supplier' => $order['supplier_name'],
                'Total Amount' => '₱' . number_format($order['total_amount'], 2),
                'Items' => $order['item_count'] . ' item(s)',
                'Requested By' => $order['requested_by_name'],
                'Department' => $order['department'] ?? 'N/A',
                'Priority' => ucfirst($order['priority'] ?? 'Normal')
            ],
            'button_text' => 'Approve Order',
            'button_color' => '#28a745',
            'subject_suffix' => "PO {$order['po_number']}"
        ]);
    }

    /**
     * Send procurement order verification request
     *
     * @param array $order Procurement order data
     * @param array $user Verifier user data
     * @return array Result
     */
    public function sendVerificationRequest($order, $user) {
        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'procurement_verify',
            'related_id' => $order['id'],
            'title' => 'Procurement Order Verification Required',
            'message' => 'A procurement order requires your verification before approval.',
            'details' => [
                'PO Number' => $order['po_number'],
                'Supplier' => $order['supplier_name'],
                'Total Amount' => '₱' . number_format($order['total_amount'], 2),
                'Items' => $order['item_count'] . ' item(s)',
                'Requested By' => $order['requested_by_name']
            ],
            'button_text' => 'Verify Order',
            'button_color' => '#007bff',
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
            'message' => 'The following procurement order has been successfully completed and delivered:',
            'details' => [
                'PO Number' => $order['po_number'],
                'Supplier' => $order['supplier_name'],
                'Total Amount' => '₱' . number_format($order['total_amount'], 2),
                'Items Received' => $order['item_count'] . ' item(s)',
                'Completed Date' => date('M j, Y')
            ],
            'alert_type' => 'success',
            'view_link' => "{$baseUrl}/?route=procurement-orders/view&id={$order['id']}",
            'view_link_text' => 'View Order Details',
            'subject_suffix' => "PO {$order['po_number']}"
        ]);
    }

    /**
     * Send procurement order status update
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
            'Pending Verification' => 'Awaiting verification by Procurement Officer',
            'Pending Approval' => 'Awaiting approval by Finance Director',
            'Approved' => 'Order has been sent to supplier',
            'In Transit' => 'Items are being delivered',
            'Received' => 'Items received and pending inspection'
        ];

        return parent::sendStatusUpdate([
            'user' => $user,
            'title' => 'Procurement Order Status Update',
            'message' => $message,
            'details' => [
                'PO Number' => $order['po_number'],
                'Supplier' => $order['supplier_name'],
                'Total Amount' => '₱' . number_format($order['total_amount'], 2),
                'Items' => $order['item_count'] . ' item(s)'
            ],
            'status' => $order['status'],
            'next_step' => $nextSteps[$order['status']] ?? null,
            'view_link' => "{$baseUrl}/?route=procurement-orders/view&id={$order['id']}"
        ]);
    }

    /**
     * Send delivery notification
     *
     * @param array $order Procurement order data
     * @param array $user Warehouse personnel
     * @return array Result
     */
    public function sendDeliveryNotification($order, $user) {
        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'procurement_receive',
            'related_id' => $order['id'],
            'title' => 'Procurement Order Delivery - Receipt Confirmation',
            'message' => 'Items from a procurement order have arrived and require receipt confirmation.',
            'details' => [
                'PO Number' => $order['po_number'],
                'Supplier' => $order['supplier_name'],
                'Total Amount' => '₱' . number_format($order['total_amount'], 2),
                'Items to Receive' => $order['item_count'] . ' item(s)',
                'Delivery Date' => date('M j, Y')
            ],
            'button_text' => 'Confirm Receipt',
            'button_color' => '#28a745',
            'additional_info' => 'Please inspect items upon receipt and confirm delivery.',
            'subject_suffix' => "PO {$order['po_number']}"
        ]);
    }
}
?>
