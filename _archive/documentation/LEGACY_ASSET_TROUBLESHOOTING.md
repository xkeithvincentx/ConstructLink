# 🔍 ConstructLink™ Legacy Asset Workflow - Troubleshooting Guide

## ❌ Issue: 403 Access Denied Error

### 🎯 Root Cause Analysis
The 403 error occurs when accessing `?route=assets/legacy-create` due to one of these common issues:

### 🔧 **Solution 1: User Role Assignment**
**Problem**: User doesn't have the required "Warehouseman" role

**Fix**: 
1. Check current user role in the 403 error page (shows current role)
2. Update user role in database:
```sql
-- Check current user role
SELECT u.username, r.name as role_name 
FROM users u 
LEFT JOIN roles r ON u.role_id = r.id 
WHERE u.username = 'your_username';

-- Update user to Warehouseman (role_id = 5)
UPDATE users SET role_id = 5 WHERE username = 'your_username';
```

### 🔧 **Solution 2: Database Migration Not Applied**
**Problem**: Required workflow columns don't exist in assets table

**Fix**: Apply the database migration
```sql
-- Run this SQL file:
SOURCE database/migrations/add_legacy_asset_workflow.sql;

-- Or copy and paste the SQL commands directly
-- This adds: asset_source, sub_location, workflow_status, made_by, verified_by, authorized_by, etc.
```

### 🔧 **Solution 3: Missing Project Assignment**
**Problem**: User doesn't have `current_project_id` set

**Fix**: Assign user to a project
```sql
-- Check user's project assignment
SELECT u.username, u.current_project_id, p.name as project_name
FROM users u 
LEFT JOIN projects p ON u.current_project_id = p.id
WHERE u.username = 'your_username';

-- Assign user to Head Office project (ID = 1)
UPDATE users SET current_project_id = 1 WHERE username = 'your_username';
```

### 🔧 **Solution 4: Session/Authentication Issues**
**Problem**: Session corruption or authentication problems

**Fix**: Clear session and re-login
```php
// Clear session
session_start();
session_destroy();
// Then login again
```

---

## 🚨 Common Error Messages & Solutions

### Error: "No project assigned to user"
```php
// Fixed in AssetModel.php - System Admins now use default project
// Non-admin users need current_project_id assigned
```

**Solution**: Assign user to project or ensure they have System Admin role

### Error: "Asset creation failed"
**Possible causes**:
1. Missing required fields (name, category_id, acquired_date)
2. Invalid category_id
3. Database constraint violations

**Debug**: Check browser console and server error logs

### Error: "CSRF token validation failed"
**Cause**: CSRF protection failure

**Solution**: Ensure form includes `<?= CSRFProtection::generateToken() ?>`

---

## 🔍 Step-by-Step Debugging Process

### Step 1: Verify System Installation
```bash
# Check if all files exist
ls -la routes.php
ls -la config/roles.php
ls -la controllers/AssetController.php
ls -la models/AssetModel.php
ls -la views/assets/legacy_create.php
```

### Step 2: Test Database Connection
```sql
-- Test database connection
SELECT 1;

-- Check if roles exist
SELECT * FROM roles WHERE name = 'Warehouseman';

-- Check if workflow columns exist
DESCRIBE assets;
-- Should show: asset_source, workflow_status, made_by, verified_by, authorized_by columns
```

### Step 3: Verify User Setup
```sql
-- Complete user verification query
SELECT 
    u.id,
    u.username,
    u.full_name,
    r.name as role_name,
    u.current_project_id,
    p.name as project_name,
    u.is_active
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
LEFT JOIN projects p ON u.current_project_id = p.id
WHERE u.username = 'test_user';
```

### Step 4: Test Route Access
```bash
# Check if route is properly configured
grep -n "legacy-create" routes.php
```

---

## 🛠 Quick Setup for Testing

### Create Test User
```sql
-- Create Warehouseman test user
INSERT INTO users (username, password_hash, role_id, full_name, email, current_project_id, is_active) 
VALUES ('warehouseman_test', '$2y$10$example_hash', 5, 'Test Warehouseman', 'test@example.com', 1, 1);
```

### Ensure Default Project Exists
```sql
-- Verify Head Office project exists
SELECT * FROM projects WHERE id = 1;

-- Create if missing
INSERT INTO projects (id, name, code, location, is_active) 
VALUES (1, 'Head Office', 'HO', 'Main Office - Inventory Storage', 1);
```

### Verify Categories Exist
```sql
-- Check if categories exist for dropdown
SELECT * FROM categories WHERE is_active = 1 ORDER BY name;

-- Create sample category if none exist
INSERT INTO categories (name, code, is_consumable, is_active) 
VALUES ('Tools', 'TOOLS', 0, 1);
```

---

## 🎯 Testing Workflow

### Test User Roles
1. **System Admin**: Can access all functions, uses default project
2. **Warehouseman**: Can create legacy assets, needs project assignment
3. **Site Inventory Clerk**: Can verify assets, needs project assignment
4. **Project Manager**: Can authorize assets, needs project assignment

### Test Complete Workflow
1. Login as Warehouseman → Create legacy asset
2. Login as Site Inventory Clerk → Verify asset  
3. Login as Project Manager → Authorize asset
4. Check asset status changes: `pending_verification` → `pending_authorization` → `approved`

---

## 📊 Health Check Queries

### System Health Check
```sql
-- 1. Check roles configuration
SELECT name, description FROM roles WHERE name IN ('System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager');

-- 2. Check users with proper assignments
SELECT u.username, r.name as role, p.name as project
FROM users u
LEFT JOIN roles r ON u.role_id = r.id  
LEFT JOIN projects p ON u.current_project_id = p.id
WHERE u.is_active = 1;

-- 3. Check assets table structure
SHOW COLUMNS FROM assets LIKE '%workflow%';
SHOW COLUMNS FROM assets LIKE '%asset_source%';

-- 4. Check legacy assets
SELECT 
    name, 
    asset_source, 
    workflow_status, 
    made_by, 
    verified_by, 
    authorized_by 
FROM assets 
WHERE asset_source = 'legacy';
```

---

## 🔧 Advanced Troubleshooting

### Enable Debug Mode
Add to your config:
```php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all queries
$debug = true;
```

### Check Error Logs
```bash
# Check PHP error logs
tail -f /var/log/php_errors.log

# Check web server logs  
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/nginx/error.log
```

### Network/Permission Issues
```bash
# Check file permissions
ls -la views/assets/legacy_create.php
ls -la controllers/AssetController.php

# Ensure web server can read files
chmod 644 views/assets/legacy_create.php
chmod 644 controllers/AssetController.php
```

---

## ✅ Success Indicators

When everything is working correctly, you should see:

1. **Legacy Create Form**: Accessible at `?route=assets/legacy-create`
2. **Role-based Buttons**: Different buttons for different user roles
3. **Form Submission**: Creates asset with `workflow_status = 'pending_verification'`
4. **Workflow Progression**: Status changes through verification → authorization → approved
5. **Asset Listings**: Shows workflow badges and action buttons

---

## 🆘 Get Help

If issues persist after following this guide:

1. **Check Error Logs**: Look for specific error messages
2. **Verify Database**: Ensure migration was applied completely  
3. **Test User Setup**: Confirm role and project assignments
4. **Browser Console**: Check for JavaScript errors
5. **Network Tab**: Look for failed HTTP requests

**💡 Most Common Fix**: Apply database migration + assign user to project with correct role

---

*Generated by ConstructLink™ Legacy Asset Workflow Implementation*
*Last updated: July 24, 2025*