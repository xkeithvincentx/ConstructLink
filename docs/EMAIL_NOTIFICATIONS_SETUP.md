# ConstructLink™ Email Notifications Setup Guide

## Overview

ConstructLink now supports **one-click email notifications** for the transfer workflow. Users receive emails with secure action links that allow them to verify, approve, dispatch, and receive transfers **without logging in**.

---

## Features

### 🚀 One-Click Email Actions
- **Verify Transfer**: Project Managers can verify transfers directly from email
- **Approve Transfer**: Finance/Asset Directors can approve transfers with one click
- **Dispatch Transfer**: FROM Project Manager confirms asset dispatch
- **Receive Transfer**: TO Project Manager confirms receipt and completes transfer

### 🔒 Security Features
- Cryptographically secure tokens (64-character hex)
- Token expiration (48 hours default)
- One-time use tokens (auto-invalidated after use)
- IP address logging for audit trail
- No login credentials exposed in emails

### 📧 Email Templates
- Professional branded email design
- Mobile-responsive layout
- Clear call-to-action buttons
- Transfer details included in every email

---

## Email Configuration

### Step 1: Configure SMTP Settings

1. Copy the example environment file:
   ```bash
   cp config/.env.example.php config/.env.php
   ```

2. Edit `config/.env.php` with your SMTP settings:
   ```php
   // SMTP Server
   define('ENV_MAIL_HOST', 'smtp.gmail.com');
   define('ENV_MAIL_PORT', 587);

   // Authentication
   define('ENV_MAIL_USERNAME', 'your-email@gmail.com');
   define('ENV_MAIL_PASSWORD', 'your-app-password');

   // Sender Info
   define('ENV_MAIL_FROM_EMAIL', 'noreply@constructlink.com');
   define('ENV_MAIL_FROM_NAME', 'ConstructLink™ System');
   ```

### Step 2: Gmail Setup (Recommended)

1. **Enable 2-Factor Authentication**:
   - Go to Google Account > Security
   - Turn on 2-Step Verification

2. **Generate App Password**:
   - Go to Security > 2-Step Verification > App passwords
   - Select "Mail" and "Other (Custom name)"
   - Name it "ConstructLink"
   - Copy the 16-character password
   - Use this as `ENV_MAIL_PASSWORD`

3. **Configure .env.php**:
   ```php
   define('ENV_MAIL_HOST', 'smtp.gmail.com');
   define('ENV_MAIL_PORT', 587);
   define('ENV_MAIL_USERNAME', 'your-email@gmail.com');
   define('ENV_MAIL_PASSWORD', 'xxxx xxxx xxxx xxxx'); // App password
   ```

### Step 3: Other Email Providers

#### Microsoft Office 365 / Outlook
```php
define('ENV_MAIL_HOST', 'smtp.office365.com');
define('ENV_MAIL_PORT', 587);
define('ENV_MAIL_USERNAME', 'your-email@yourcompany.com');
define('ENV_MAIL_PASSWORD', 'your-password');
```

#### SendGrid (Recommended for Production)
```php
define('ENV_MAIL_HOST', 'smtp.sendgrid.net');
define('ENV_MAIL_PORT', 587);
define('ENV_MAIL_USERNAME', 'apikey');
define('ENV_MAIL_PASSWORD', 'your-sendgrid-api-key');
```

#### Yahoo Mail
```php
define('ENV_MAIL_HOST', 'smtp.mail.yahoo.com');
define('ENV_MAIL_PORT', 587);
define('ENV_MAIL_USERNAME', 'your-email@yahoo.com');
define('ENV_MAIL_PASSWORD', 'your-app-password');
```

---

## Database Setup

Run the email action tokens migration:

```bash
mysql -u root -p constructlink_db < database/migrations/create_email_action_tokens.sql
```

This creates the `email_action_tokens` table for secure one-click links.

---

## How It Works

### Transfer Workflow with Email Notifications

1. **Create Transfer**
   - User creates transfer request
   - System sends email to Project Manager for verification
   - Email includes "Verify Transfer" button

2. **Verify Transfer** (via email)
   - Project Manager clicks "Verify Transfer" in email
   - No login required - secure token validates action
   - System marks transfer as verified
   - Email sent to Finance/Asset Director for approval

3. **Approve Transfer** (via email)
   - Director clicks "Approve Transfer" in email
   - Transfer marked as approved
   - Email sent to FROM Project Manager for dispatch

4. **Dispatch Transfer** (via email)
   - FROM PM clicks "Confirm Dispatch" in email
   - Transfer status changes to "In Transit"
   - Email sent to TO Project Manager for receipt

5. **Receive Transfer** (via email)
   - TO PM clicks "Confirm Receipt" in email
   - Transfer completed
   - Asset transferred to destination project
   - Completion emails sent to all parties

### Token Security

- Tokens are generated using `random_bytes(32)` (cryptographically secure)
- Each token is unique and stored in database with:
  - Action type (verify, approve, dispatch, receive)
  - Related transfer ID
  - Assigned user ID
  - Expiration timestamp (48 hours)
  - Usage status and IP logging

---

## Testing Email Notifications

### 1. Enable Debug Mode

In `config/.env.php`:
```php
define('ENV_DEBUG', true);
```

### 2. Check Email Service Status

The system will log email configuration status. Check logs:
```bash
tail -f logs/php_errors.log
```

Look for:
```
EmailService: Email sent successfully to user@example.com
```

Or errors like:
```
EmailService: Email not configured. Set MAIL_HOST, MAIL_USERNAME, and MAIL_PASSWORD
```

### 3. Create Test Transfer

1. Login as any user
2. Navigate to Transfers > Create Transfer Request
3. Fill in transfer details
4. Submit

5. Check that:
   - In-app notification appears
   - Email sent to Project Manager
   - Email contains "Verify Transfer" button

### 4. Test One-Click Action

1. Open email received by Project Manager
2. Click "Verify Transfer" button
3. Should see success page without login
4. Transfer status updates to "Pending Approval"
5. Finance Director receives approval email

---

## User Email Requirements

### Setting User Emails

Ensure all users have email addresses in the system:

```sql
-- Check users without email
SELECT id, username, full_name, email
FROM users
WHERE email IS NULL OR email = '';

-- Update user email
UPDATE users
SET email = 'user@example.com'
WHERE id = 1;
```

### Roles Requiring Email

For transfer workflow, these roles **must** have emails:
- **Project Manager** - Receives verification and dispatch requests
- **Finance Director** - Receives approval requests
- **Asset Director** - Receives approval requests

---

## Troubleshooting

### Emails Not Sending

1. **Check Configuration**:
   ```bash
   tail -f logs/php_errors.log | grep "EmailService"
   ```

2. **Verify SMTP Credentials**:
   - Test with a mail client (Thunderbird, Outlook)
   - Ensure username/password are correct

3. **Check Firewall**:
   - Ensure port 587 (TLS) or 465 (SSL) is open
   - Some servers block outgoing SMTP

4. **Gmail Specific**:
   - Must use App-Specific Password (not regular password)
   - 2FA must be enabled
   - Check "Less secure apps" setting (deprecated)

### Tokens Not Working

1. **Check Token Expiration**:
   ```sql
   SELECT * FROM email_action_tokens
   WHERE expires_at < NOW() AND used_at IS NULL;
   ```

2. **Verify Database Migration**:
   ```sql
   SHOW TABLES LIKE 'email_action_tokens';
   ```

3. **Check Route Configuration**:
   - Ensure `email-action` route is in `routes.php`
   - Route must have `auth => false`

### Email Delivery Issues

1. **Check Spam Folder**: Emails may be marked as spam initially
2. **SPF/DKIM Records**: For production, configure DNS records
3. **Email Service Limits**: Gmail has sending limits (500/day)

---

## Production Recommendations

### Use Professional Email Service

For production, use dedicated email services:

1. **SendGrid** (Recommended)
   - 100 emails/day free
   - Better deliverability
   - Detailed analytics

2. **Amazon SES**
   - Very low cost
   - High reliability
   - Scales automatically

3. **Mailgun**
   - 5,000 emails/month free
   - Good for transactional emails

### Security Best Practices

1. **Never commit `.env.php`** to version control
2. **Use strong SMTP passwords**
3. **Enable SSL/TLS** for SMTP
4. **Monitor token usage** for suspicious activity
5. **Set up email rate limiting**

### Monitoring

Monitor email notifications:

```sql
-- Check token usage
SELECT action_type, COUNT(*) as count,
       SUM(CASE WHEN used_at IS NOT NULL THEN 1 ELSE 0 END) as used,
       SUM(CASE WHEN expires_at < NOW() THEN 1 ELSE 0 END) as expired
FROM email_action_tokens
GROUP BY action_type;

-- Recent email actions
SELECT eat.*, u.username, u.email
FROM email_action_tokens eat
JOIN users u ON eat.user_id = u.id
WHERE eat.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY eat.created_at DESC;
```

---

## Email Customization

### Custom Email Templates

Edit `core/TransferEmailTemplates.php` to customize:
- Email content
- Button colors
- Additional information
- Branding

### Custom Sender Name/Email

In `.env.php`:
```php
define('ENV_MAIL_FROM_EMAIL', 'assets@yourcompany.com');
define('ENV_MAIL_FROM_NAME', 'Your Company Asset System');
```

---

## Support

For issues or questions:
1. Check logs: `logs/php_errors.log`
2. Review this documentation
3. Contact: support@ranoatech.com

---

**ConstructLink™** - Asset Management System
*Powered by Ranoa Digital Solutions*
