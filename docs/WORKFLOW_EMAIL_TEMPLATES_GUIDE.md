# ConstructLink™ Workflow Email Templates Guide

## Overview

The **WorkflowEmailTemplates** class provides a generic, reusable email notification system for all workflows in ConstructLink. This allows you to easily implement one-click email actions for any module (Transfers, Procurement Orders, Maintenance, etc.) without duplicating code.

---

## Architecture

### Base Class: `WorkflowEmailTemplates`

Located at: `core/WorkflowEmailTemplates.php`

This is the generic base class that provides three main email types:

1. **Action Request Emails** - Emails with one-click action buttons
2. **Completion Notifications** - Success/completion emails (no action required)
3. **Status Update Notifications** - Status change notifications

### Module-Specific Classes

Each module extends `WorkflowEmailTemplates` and implements module-specific email methods:

- `TransferEmailTemplates` - For asset transfers
- `ProcurementEmailTemplates` - For procurement orders
- _(You can create more for other modules)_

---

## Creating Email Templates for Your Module

### Step 1: Create Your Email Template Class

```php
<?php
require_once APP_ROOT . '/core/WorkflowEmailTemplates.php';

class YourModuleEmailTemplates extends WorkflowEmailTemplates {

    public function sendApprovalRequest($data, $user) {
        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'yourmodule_approve',
            'related_id' => $data['id'],
            'title' => 'Approval Required',
            'message' => 'Your custom message here',
            'details' => [
                'Field 1' => $data['field1'],
                'Field 2' => $data['field2']
            ],
            'button_text' => 'Approve',
            'button_color' => '#28a745'
        ]);
    }
}
?>
```

### Step 2: Define Token Action Types

Add your action types to the `email_action_tokens` table enum:

```sql
ALTER TABLE email_action_tokens
MODIFY COLUMN action_type ENUM(
    'transfer_verify',
    'transfer_approve',
    'transfer_dispatch',
    'transfer_receive',
    'procurement_verify',
    'procurement_approve',
    'procurement_receive',
    'yourmodule_action'  -- Add your actions here
) NOT NULL;
```

### Step 3: Implement Action Handler

Create a handler in your controller or create a dedicated email action handler:

```php
// In EmailActionController.php or your module controller
private function handleYourModuleAction($tokenData, $user) {
    $itemId = $tokenData['related_id'];

    // Get item details
    $item = $this->yourModel->find($itemId);

    // Perform action
    $result = $this->yourModel->performAction($itemId, $user['id']);

    if ($result['success']) {
        $this->tokenManager->markTokenAsUsed($tokenData['token'], $this->getClientIP());
        $this->showSuccess('Action Successful', 'Your action completed');
    }
}
```

---

## Email Template Methods

### 1. Send Action Request Email

**Use Case**: When you need a user to take action (approve, verify, etc.)

```php
$this->sendActionRequest([
    // REQUIRED FIELDS
    'user' => $userArray,              // User data with 'id', 'email', 'full_name'
    'action_type' => 'module_action',  // Token action type
    'related_id' => 123,               // Entity ID
    'title' => 'Email Title',          // Email subject title
    'message' => 'Main message',       // Main email message
    'button_text' => 'Take Action',    // Button text

    // OPTIONAL FIELDS
    'greeting' => 'Dear User,',        // Custom greeting (default: "Dear {full_name},")
    'details' => [                     // Details box
        'Label 1' => 'Value 1',
        'Label 2' => 'Value 2'
    ],
    'button_color' => '#007bff',       // Button color (default: #007bff)
    'additional_info' => 'Note...',    // Additional info below button
    'token_expiry_hours' => 48,        // Token expiry (default: 48)
    'subject_suffix' => 'ID #123'      // Suffix for email subject
]);
```

**Result**: Email with one-click action button that doesn't require login

### 2. Send Completion Notification

**Use Case**: Notify users that a workflow has completed

```php
$this->sendCompletionNotification([
    // REQUIRED FIELDS
    'users' => $usersArray,            // Array of users to notify
    'title' => 'Process Completed',    // Email title
    'message' => 'Success message',    // Main message

    // OPTIONAL FIELDS
    'details' => [                     // Details box with colored background
        'Label 1' => 'Value 1',
        'Label 2' => 'Value 2'
    ],
    'alert_type' => 'success',         // success|info|warning (affects color)
    'view_link' => 'https://...',      // Link to view details
    'view_link_text' => 'View',        // Link button text
    'subject_suffix' => 'ID #123'      // Email subject suffix
]);
```

**Result**: Sends completion email to multiple users

### 3. Send Status Update Notification

**Use Case**: Notify a user about status changes

```php
$this->sendStatusUpdate([
    // REQUIRED FIELDS
    'user' => $userArray,              // User to notify
    'title' => 'Status Update',        // Email title
    'message' => 'Status changed',     // Main message
    'status' => 'Approved',            // Current status (displayed as badge)

    // OPTIONAL FIELDS
    'details' => [                     // Details box
        'Label 1' => 'Value 1'
    ],
    'next_step' => 'Description...',   // Description of next step
    'view_link' => 'https://...'       // Link to view details
]);
```

**Result**: Status update email with current status badge

---

## Complete Examples

### Example 1: Transfer Module

```php
class TransferEmailTemplates extends WorkflowEmailTemplates {

    public function sendVerificationRequest($transfer, $user) {
        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'transfer_verify',
            'related_id' => $transfer['id'],
            'title' => 'Transfer Verification Required',
            'message' => 'A transfer requires your verification.',
            'details' => [
                'Transfer ID' => "#{$transfer['id']}",
                'Asset' => $transfer['asset_name'],
                'From' => $transfer['from_project'],
                'To' => $transfer['to_project']
            ],
            'button_text' => 'Verify Transfer',
            'button_color' => '#28a745'
        ]);
    }
}
```

### Example 2: Procurement Module

```php
class ProcurementEmailTemplates extends WorkflowEmailTemplates {

    public function sendApprovalRequest($order, $user) {
        return $this->sendActionRequest([
            'user' => $user,
            'action_type' => 'procurement_approve',
            'related_id' => $order['id'],
            'title' => 'PO Approval Required',
            'message' => 'A purchase order needs approval.',
            'details' => [
                'PO Number' => $order['po_number'],
                'Supplier' => $order['supplier'],
                'Amount' => '₱' . number_format($order['amount'], 2)
            ],
            'button_text' => 'Approve Order',
            'button_color' => '#007bff'
        ]);
    }
}
```

### Example 3: Maintenance Module

```php
class MaintenanceEmailTemplates extends WorkflowEmailTemplates {

    public function sendScheduleNotification($maintenance, $user) {
        return $this->sendStatusUpdate([
            'user' => $user,
            'title' => 'Maintenance Scheduled',
            'message' => 'Maintenance has been scheduled for your asset.',
            'details' => [
                'Asset' => $maintenance['asset_name'],
                'Type' => $maintenance['maintenance_type'],
                'Scheduled Date' => $maintenance['scheduled_date']
            ],
            'status' => 'Scheduled',
            'next_step' => 'Technician will be assigned soon',
            'view_link' => "/?route=maintenance/view&id={$maintenance['id']}"
        ]);
    }
}
```

---

## Email Template Customization

### Button Colors

Common button colors:
- `#28a745` - Green (Approve, Verify, Confirm)
- `#007bff` - Blue (View, Info)
- `#ffc107` - Yellow/Warning (Dispatch, Review)
- `#dc3545` - Red (Reject, Cancel)
- `#17a2b8` - Cyan (Info)

### Alert Types for Completion Emails

- `success` - Green background (completed, approved)
- `info` - Blue background (informational)
- `warning` - Yellow background (attention needed)

---

## Integration with Your Module

### In Your Model

```php
class YourModel extends BaseModel {

    private function sendWorkflowNotification($itemId, $action, $userId) {
        require_once APP_ROOT . '/core/YourModuleEmailTemplates.php';

        $emailTemplates = new YourModuleEmailTemplates();
        $item = $this->getItemWithDetails($itemId);
        $user = $userModel->find($userId);

        switch ($action) {
            case 'created':
                // Send to approver
                $approver = $userModel->getUsersByRole(['Approver'])[0];
                $emailTemplates->sendApprovalRequest($item, $approver);
                break;

            case 'approved':
                // Send to initiator
                $initiator = $userModel->find($item['initiated_by']);
                $emailTemplates->sendStatusUpdate($item, $initiator, 'Your request has been approved!');
                break;

            case 'completed':
                // Send to all involved users
                $users = [$initiator, $approver];
                $emailTemplates->sendCompletionNotification($item, $users);
                break;
        }
    }
}
```

---

## Token-Based Security

### How It Works

1. **Token Generation**: Cryptographically secure 64-character token created
2. **Database Storage**: Token stored with action type, related ID, user ID, expiration
3. **Email Link**: Unique URL with token sent to user
4. **Validation**: User clicks link, system validates token
5. **Action Execution**: If valid, action performed without login
6. **Invalidation**: Token marked as used, cannot be reused

### Security Features

- ✅ One-time use tokens
- ✅ Expiration (default 48 hours, configurable)
- ✅ IP address logging
- ✅ User-specific tokens
- ✅ Action-specific tokens
- ✅ Automatic cleanup of old tokens

---

## Best Practices

### 1. Clear Communication

✅ **Good**: "A transfer requires your verification"
❌ **Bad**: "Action needed"

### 2. Provide Context

Always include relevant details:
```php
'details' => [
    'What' => 'Asset Transfer',
    'Who' => 'John Doe',
    'When' => 'Jan 15, 2025',
    'Why' => 'Project requirements'
]
```

### 3. Set Appropriate Expiry

- **Urgent actions**: 24 hours
- **Normal workflow**: 48 hours (default)
- **Low priority**: 72 hours

### 4. Use Descriptive Button Text

✅ **Good**: "Verify Transfer", "Approve Order"
❌ **Bad**: "Click Here", "Submit"

### 5. Include Next Steps

Help users understand what happens after their action:
```php
'additional_info' => 'After verification, the Finance Director will be notified for approval.'
```

---

## Testing Your Email Templates

### 1. Create Test User with Email

```sql
UPDATE users SET email = 'test@example.com' WHERE id = 1;
```

### 2. Trigger Workflow Action

Create a test record that triggers email notification

### 3. Check Logs

```bash
tail -f logs/php_errors.log | grep "EmailService"
```

### 4. Verify Token Created

```sql
SELECT * FROM email_action_tokens ORDER BY created_at DESC LIMIT 5;
```

### 5. Test Email Link

Click the link in email and verify:
- Action completes successfully
- Token marked as used
- Status page displays correctly

---

## Troubleshooting

### Emails Not Sending

1. Check email configuration in `.env.php`
2. Verify SMTP credentials
3. Check error logs for email service errors
4. Test SMTP connection with external tool

### Token Not Working

1. Check token hasn't expired
2. Verify token hasn't been used
3. Check action type matches handler
4. Verify user ID matches token

### Wrong User Receiving Email

1. Verify correct user data passed to template
2. Check workflow logic determines correct recipient
3. Review role-based user queries

---

## Summary

The `WorkflowEmailTemplates` system provides:

✅ **Reusable** - One base class for all modules
✅ **Secure** - Token-based authentication
✅ **Flexible** - Customizable for any workflow
✅ **Professional** - Branded, responsive emails
✅ **Simple** - Easy to implement in new modules

Create module-specific template classes by extending `WorkflowEmailTemplates` and implementing your workflow-specific email methods!

---

**ConstructLink™** - Asset Management System
*Developed by Ranoa Digital Solutions*
