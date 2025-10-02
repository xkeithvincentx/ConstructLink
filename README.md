# ConstructLink™ Asset and Inventory Management System

**Version 1.0.0** | **By Ranoa Digital Solutions**

A comprehensive web-based inventory and asset management system designed specifically for **V CUTAMORA CONSTRUCTION INC.** to support project-based construction operations.

## 🏗️ System Overview

ConstructLink™ centralizes procurement, asset issuance, asset lifecycle tracking, and inter-site transfers while providing audit-ready records for accounting and executive decision-making. The system integrates with **SecureLink™ QR code technology** to ensure authenticity, traceability, and tamper-proof validation.

## ✨ Key Features

### 🎯 Core Objectives
- **Digitize** inventory issuance and asset lifecycle logging across multiple project sites
- **Unify** consumable and equipment tracking, including client-supplied items
- **Prevent** redundant purchases by tracking availability of idle or underutilized assets
- **Provide** real-time access to inventory data for field and head office teams
- **Maintain** compatibility with accounting and audit reporting standards
- **Align** terminologies with accounting practices

### 🔧 Functional Modules

#### Master Data Management
- **User Management** with role-based permissions (7 distinct roles)
- **Asset Categories** and subcategories with consumable flags
- **Vendors, Makers, and Client** metadata management
- **Project Site Registry** and location metadata

#### Procurement & Receiving
- **PO Logging Interface** (internal or external POs)
- **Delivery Intake** by warehouse with reference numbers
- **SecureLink™ QR Code Tagging** with HMAC validation
- **Asset condition confirmation** (new, refurbished)

#### Asset Lifecycle Management
- **Comprehensive Asset Listing** with advanced filters
- **Asset Status Management** (Available, In Use, Transferred, Damaged, Retired)
- **Lifecycle Audit Logs** with timestamps and user actions
- **Digital Withdrawal Requests** and release forms
- **Borrowed Items Tracker** with due date management
- **Inter-site Transfer Tracker** with approval workflows
- **Return to HO Inventory** for asset reuse
- **Asset Maintenance Logs** (Preventive and Corrective)
- **Incident Logging** (Lost, Damaged, Reported by, Verified by)
- **Retirement and Disposal Tracker** with finance approval

#### Reporting & Analytics
- **Daily/Weekly Withdrawals** per site
- **Transfer Reports** by asset or project
- **Incident and Maintenance Logs**
- **Asset Utilization** and idle asset reports
- **Asset Retirement** and write-off reports
- **Inventory Movement Logs** by user
- **Performance KPIs** and metrics

## 🛠️ Technology Stack

### Backend
- **PHP 8.x** with PDO (Object-Oriented, prepared statements, error handling)
- **MySQL 8.x** (InnoDB engine, UTF8MB4 charset, enforced foreign keys)
- **Unit Testing** with PHPUnit coverage

### Frontend
- **HTML5** with semantic markup
- **Bootstrap 5** for responsive design
- **Alpine.js** for lightweight reactive UI interactions

### Security & Integration
- **SecureLink™ HMAC + QR** integration for asset authentication
- **CSRF token protection** on all forms
- **Session-based authentication** with role-based access control
- **Rate limiting** for API endpoints
- **Comprehensive audit logging** for all mutations

### PDF Generation
- **DomPDF or TCPDF** for receipts, reports, and certificates

### Hosting Requirements
- **PHP 8.x+** with required extensions
- **MySQL 8+** database server
- **SSL enabled** for secure connections
- **Minimum 1GB PHP memory limit**
- **120 seconds execution time limit**
- **10MB+ file upload support**

## 📁 Application Structure

```
ConstructLink/
├── index.php                 # Application entry point
├── .htaccess                 # Apache configuration
├── routes.php                # Route definitions
├── install.php               # Installation script
├── README.md                 # This documentation
│
├── config/                   # Configuration files
│   ├── config.php           # Main configuration
│   ├── .env.php             # Environment settings
│   └── database.php         # Database setup
│
├── core/                     # Core system classes
│   ├── Autoloader.php       # PSR-4 autoloader
│   ├── Auth.php             # Authentication system
│   ├── SecureLink.php       # QR code & HMAC system
│   └── Validator.php        # Input validation
│
├── models/                   # Database models
│   ├── BaseModel.php        # Base model class
│   ├── AssetModel.php       # Asset management
│   ├── UserModel.php        # User management
│   ├── WithdrawalModel.php  # Asset withdrawals
│   ├── TransferModel.php    # Inter-site transfers
│   ├── MaintenanceModel.php # Maintenance tracking
│   ├── IncidentModel.php    # Incident reporting
│   ├── BorrowedToolModel.php# Tool borrowing
│   └── ProjectModel.php     # Project & master data
│
├── controllers/              # Business logic controllers
├── views/                    # HTML templates
│   ├── layouts/             # Shared layouts
│   └── modules/             # Feature modules
│
├── assets/                   # Static resources
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript files
│   └── qr/                  # Generated QR codes
│
├── api/                      # API endpoints
├── uploads/                  # File uploads
├── logs/                     # Application logs
└── monitoring/               # System monitoring
```

## 🚀 Installation

### Prerequisites
- Web server (Apache/Nginx) with PHP 8.x+
- MySQL 8.x+ database server
- SSL certificate (recommended for production)

### Quick Installation

1. **Download and Extract**
   ```bash
   # Extract ConstructLink files to your web directory
   cd /path/to/your/webroot
   ```

2. **Configure Database**
   - Create a MySQL database for ConstructLink
   - Update database credentials in `config/.env.php`

3. **Run Installation**
   - Navigate to `http://yourdomain.com/install.php`
   - Follow the step-by-step installation wizard
   - Create your admin account
   - Optionally generate sample data

4. **Security Setup**
   ```bash
   # Remove installation file after setup
   rm install.php
   
   # Set proper file permissions
   chmod 644 config/.env.php
   chmod 755 uploads/
   chmod 755 logs/
   ```

### Manual Installation

1. **Database Setup**
   ```php
   // Include database configuration
   require_once 'config/database.php';
   
   // Initialize database
   $result = initializeDatabase();
   ```

2. **Environment Configuration**
   ```php
   // Update config/.env.php with your settings
   define('ENV_DB_HOST', 'your-database-host');
   define('ENV_DB_NAME', 'your-database-name');
   define('ENV_DB_USER', 'your-database-user');
   define('ENV_DB_PASS', 'your-database-password');
   define('ENV_HMAC_SECRET', 'your-unique-secret-key');
   ```

## 👥 User Roles & Permissions

### 1. System Admin
- **Full system access** and configuration rights
- Cannot perform accounting approval unless also granted Finance Director role
- **Permissions**: All system functions, user management, system configuration

### 2. Finance Director
- **View assets**, approve disposal, assign asset cost centers
- Cannot modify warehouse logs or release assets
- **Permissions**: Financial oversight, disposal approval, reporting

### 3. Asset Director
- **Authorize transfers**, edit asset info, flag idle assets
- Cannot directly issue or receive items on behalf of warehouse
- **Permissions**: Asset management, transfer approval, maintenance oversight

### 4. Procurement Officer
- **Enter POs**, receive deliveries, assign vendor info
- Cannot withdraw or retire assets
- **Permissions**: Procurement management, vendor relations, receiving

### 5. Warehouseman
- **Record withdrawals**, releases, basic asset logs
- Cannot approve inter-site transfers or mark assets as retired
- **Permissions**: Inventory operations, asset release, basic logging

### 6. Project Manager
- **Submit requests**, initiate transfers, approve site-level actions
- Cannot alter central inventory or perform procurement intake
- **Permissions**: Project-level asset management, transfer requests

### 7. Site Inventory Clerk
- **Scan tags**, request withdrawals, log borrower info
- Cannot access finance module or approve transfers
- **Permissions**: Site-level operations, QR scanning, basic requests

## 🔄 Asset Lifecycle Workflow

```
1. Procurement
   ↓
2. Delivery Receiving
   ↓
3. Inventory Tagging (SecureLink™ QR Code Generated)
   ↓
4. Project Site Assignment
   ↓
5. Withdrawal (Released for Use)
   ↓ ↘
6. Return → Transfer to Another Project
   ↓         ↓
7. Maintenance → (Back to Assignment or Retirement)
   ↓
8. Retirement (Asset Decommissioned)
```

Every transition is recorded with:
- **Timestamp** of the action
- **Responsible user** performing the action
- **Justification** or reason for the change
- **SecureLink™ QR codes** ensure traceable audit trail

## 🔐 SecureLink™ QR Code System

### Features
- **HMAC-based authentication** for tamper-proof validation
- **Unique QR codes** for each asset with embedded metadata
- **Real-time validation** via API endpoints
- **Audit logging** of all QR code scans and validations
- **Mobile-friendly** scanning interface

### QR Code Data Structure
```json
{
  "data": {
    "asset_id": 123,
    "asset_ref": "CL2024001",
    "timestamp": 1640995200,
    "version": "1.0",
    "system": "ConstructLink"
  },
  "signature": "hmac_sha256_signature"
}
```

## 📊 Key Performance Indicators (KPIs)

### Operational Efficiency
- **Time-to-receive**: From PO approval to item logged
- **Asset turnaround time**: From assignment to return/retirement
- **Percentage of reused vs. newly purchased assets**
- **Audit completion time**: Time to retrieve full asset lifecycle

### Asset Utilization
- **Asset availability rates** by category and project
- **Idle asset identification** and redeployment metrics
- **Maintenance cost tracking** and trend analysis
- **Incident frequency** and resolution times

### Financial Metrics
- **Total asset value** by project and category
- **Depreciation tracking** and current asset values
- **Cost per project** for asset utilization
- **ROI on asset investments**

## 🔧 API Endpoints

### QR Code Validation
```
GET /api/validate-qr?data={qr_data}
```

### Asset Search
```
GET /api/assets/search?q={search_term}
```

### Asset Status Update
```
POST /api/assets/status
{
  "asset_id": 123,
  "status": "in_use",
  "reason": "Assigned to Project Alpha"
}
```

### Dashboard Statistics
```
GET /api/dashboard/stats
```

## 🛡️ Security Features

### Authentication & Authorization
- **Session-based authentication** with secure cookie handling
- **Role-based access control** with granular permissions
- **Account lockout** after failed login attempts
- **Password strength requirements** and hashing

### Data Protection
- **CSRF token protection** on all forms
- **SQL injection prevention** with prepared statements
- **XSS protection** with input sanitization
- **Rate limiting** on API endpoints

### Audit & Compliance
- **Comprehensive audit logging** of all user actions
- **Asset version tracking** for change history
- **Secure file uploads** with type validation
- **Data retention policies** for logs and backups

## 📈 Reporting Capabilities

### Standard Reports
- **Daily/Weekly Withdrawal Reports** by site
- **Asset Transfer Reports** by project or asset
- **Maintenance and Incident Logs**
- **Asset Utilization Reports**
- **Idle Asset Reports** for redeployment
- **Financial Asset Reports** with depreciation

### Custom Analytics
- **Asset lifecycle analysis**
- **Project cost allocation**
- **Vendor performance metrics**
- **User activity reports**
- **Trend analysis** and forecasting

## 🔄 Backup & Recovery

### Automated Backups
- **Weekly database backups** with rotation
- **File system backups** for uploads and QR codes
- **Configuration backups** for system settings

### Recovery Procedures
- **Point-in-time recovery** from database backups
- **Asset data restoration** with audit trail preservation
- **System configuration restoration**

## 📱 Mobile Compatibility

### Responsive Design
- **Mobile-first approach** with Bootstrap 5
- **Touch-friendly interfaces** for field operations
- **QR code scanning** optimized for mobile devices
- **Offline capability** for basic operations

### Mobile Features
- **Asset lookup** and status checking
- **QR code scanning** for asset verification
- **Quick withdrawal requests**
- **Photo uploads** for incident reporting

## 🚀 Deployment Options

### Shared Hosting
- **cPanel compatible** with PHP 8.x support
- **MySQL database** with full privileges
- **SSL certificate** for secure connections
- **File upload capabilities**

### VPS/Dedicated Server
- **Full server control** for advanced configurations
- **Redis caching** for improved performance
- **Load balancing** for high availability
- **Custom backup solutions**

### Cloud Deployment
- **AWS/Azure/GCP** compatible
- **Auto-scaling capabilities**
- **Managed database services**
- **CDN integration** for static assets

## 🔧 Maintenance & Updates

### Regular Maintenance
- **Log rotation** and cleanup
- **Database optimization**
- **Security updates**
- **Performance monitoring**

### System Updates
- **Version control** with Git
- **Staging environment** testing
- **Rollback procedures**
- **User notification** of updates

## 📞 Support & Documentation

### Technical Support
- **Email**: support@ranoadigital.com
- **Phone**: +63 XXX XXX XXXX
- **Documentation**: https://docs.constructlink.com

### Training Resources
- **User manuals** for each role
- **Video tutorials** for common tasks
- **Best practices** documentation
- **Troubleshooting guides**

## 📄 License & Copyright

**ConstructLink™** is a proprietary software solution developed by **Ranoa Digital Solutions** for **V CUTAMORA CONSTRUCTION INC.**

- **License Type**: Commercial
- **License Holder**: V CUTAMORA CONSTRUCTION INC.
- **License Expiry**: 2025-12-31
- **License Key**: CL-VCUTAMORA-2024-PREMIUM

### Copyright Notice
© 2024 Ranoa Digital Solutions. All rights reserved.

This software and its documentation are protected by copyright law and international treaties. Unauthorized reproduction or distribution of this software, or any portion of it, may result in severe civil and criminal penalties.

## 🎯 Future Enhancements

### Planned Features
- **Mobile application** for iOS and Android
- **Advanced analytics** with machine learning
- **Integration** with accounting systems
- **Barcode scanning** support
- **IoT sensor integration** for real-time tracking

### Roadmap
- **Q2 2024**: Mobile app release
- **Q3 2024**: Advanced reporting module
- **Q4 2024**: ERP system integration
- **Q1 2025**: IoT and sensor integration

---

**ConstructLink™** - Revolutionizing Construction Asset Management

*Built with ❤️ by Ranoa Digital Solutions*
