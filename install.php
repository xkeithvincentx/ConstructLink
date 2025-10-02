<?php
/**
 * ConstructLink™ Installation Script
 * Sets up the database and initial configuration
 */

// Start session for installation progress tracking
session_start();

// Define installation constants
define('APP_ROOT', __DIR__);
define('INSTALL_VERSION', '1.0.0');

// Include configuration and core classes
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/config/database.php';

// Installation steps
$steps = [
    1 => 'Database Connection Test',
    2 => 'Create Database Tables',
    3 => 'Insert Default Data',
    4 => 'Create Admin User',
    5 => 'Generate Sample Data (Optional)',
    6 => 'Installation Complete'
];

$currentStep = $_GET['step'] ?? 1;
$errors = [];
$messages = [];

/**
 * Test database connection
 */
function testDatabaseConnection() {
    try {
        $db = Database::getInstance();
        return ['success' => true, 'message' => 'Database connection successful'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
    }
}

/**
 * Create database tables
 */
function createDatabaseTables() {
    try {
        $result = initializeDatabase();
        if ($result) {
            return ['success' => true, 'message' => 'Database tables created successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to create database tables'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database table creation failed: ' . $e->getMessage()];
    }
}

/**
 * Create admin user
 */
function createAdminUser($username, $password, $fullName, $email) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Check if admin user already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role_id = 1");
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Admin user already exists'];
        }
        
        // Create admin user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (username, password_hash, role_id, full_name, email, is_active) 
            VALUES (?, ?, 1, ?, ?, 1)
        ");
        
        $result = $stmt->execute([$username, $passwordHash, $fullName, $email]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Admin user created successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to create admin user'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Admin user creation failed: ' . $e->getMessage()];
    }
}

/**
 * Generate sample data
 */
function generateSampleData() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Sample categories
        $categories = [
            ['Tools', 'TOOLS', 0, 'Hand tools and power tools'],
            ['Equipment', 'EQUIP', 0, 'Heavy equipment and machinery'],
            ['Materials', 'MAT', 1, 'Consumable construction materials'],
            ['Vehicles', 'VEH', 0, 'Construction vehicles'],
            ['Safety Equipment', 'SAFETY', 0, 'Personal protective equipment']
        ];
        
        $stmt = $db->prepare("INSERT IGNORE INTO categories (name, code, is_consumable, description) VALUES (?, ?, ?, ?)");
        foreach ($categories as $category) {
            $stmt->execute($category);
        }
        
        // Sample projects
        $projects = [
            ['Head Office', 'HO', 'Main Office - Inventory Storage', 'Central inventory and administration'],
            ['Project Alpha', 'PA', 'Construction Site A', 'Residential development project'],
            ['Project Beta', 'PB', 'Construction Site B', 'Commercial building project'],
            ['Project Gamma', 'PG', 'Construction Site C', 'Infrastructure project']
        ];
        
        $stmt = $db->prepare("INSERT IGNORE INTO projects (name, code, location, description) VALUES (?, ?, ?, ?)");
        foreach ($projects as $project) {
            $stmt->execute($project);
        }
        
        // Sample vendors
        $vendors = [
            ['ABC Construction Supply', 'ABC', 'Main supplier for construction materials', '123 Main St', 'abc@supply.com', '+63-123-456-7890'],
            ['XYZ Equipment Rental', 'XYZ', 'Heavy equipment rental company', '456 Industrial Ave', 'xyz@rental.com', '+63-987-654-3210'],
            ['Tools & More Inc.', 'TAM', 'Hand tools and power tools supplier', '789 Commerce Blvd', 'info@toolsmore.com', '+63-555-123-4567']
        ];
        
        $stmt = $db->prepare("INSERT IGNORE INTO vendors (name, code, contact_info, address, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($vendors as $vendor) {
            $stmt->execute($vendor);
        }
        
        // Sample makers
        $makers = [
            ['Caterpillar', 'CAT', 'USA', 'https://www.caterpillar.com'],
            ['Makita', 'MAK', 'Japan', 'https://www.makita.com'],
            ['DeWalt', 'DEW', 'USA', 'https://www.dewalt.com'],
            ['Bosch', 'BSH', 'Germany', 'https://www.bosch.com']
        ];
        
        $stmt = $db->prepare("INSERT IGNORE INTO makers (name, code, country, website) VALUES (?, ?, ?, ?)");
        foreach ($makers as $maker) {
            $stmt->execute($maker);
        }
        
        // Sample users with different roles
        $users = [
            ['finance_director', 'Finance Director', 'finance@vcutamora.com', 2],
            ['asset_director', 'Asset Director', 'assets@vcutamora.com', 3],
            ['procurement', 'Procurement Officer', 'procurement@vcutamora.com', 4],
            ['warehouse', 'Warehouse Manager', 'warehouse@vcutamora.com', 5],
            ['project_mgr', 'Project Manager', 'projects@vcutamora.com', 6],
            ['site_clerk', 'Site Inventory Clerk', 'site@vcutamora.com', 7]
        ];
        
        $stmt = $db->prepare("INSERT IGNORE INTO users (username, password_hash, role_id, full_name, email, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        foreach ($users as $user) {
            $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
            $stmt->execute([$user[0], $passwordHash, $user[3], $user[1], $user[2]]);
        }
        
        return ['success' => true, 'message' => 'Sample data generated successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Sample data generation failed: ' . $e->getMessage()];
    }
}

// Process installation steps
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($currentStep) {
        case 1:
            $result = testDatabaseConnection();
            if ($result['success']) {
                $messages[] = $result['message'];
                $currentStep = 2;
            } else {
                $errors[] = $result['message'];
            }
            break;
            
        case 2:
            $result = createDatabaseTables();
            if ($result['success']) {
                $messages[] = $result['message'];
                $currentStep = 3;
            } else {
                $errors[] = $result['message'];
            }
            break;
            
        case 3:
            $currentStep = 4;
            $messages[] = 'Default data inserted successfully';
            break;
            
        case 4:
            $username = $_POST['admin_username'] ?? '';
            $password = $_POST['admin_password'] ?? '';
            $fullName = $_POST['admin_fullname'] ?? '';
            $email = $_POST['admin_email'] ?? '';
            
            if (empty($username) || empty($password) || empty($fullName) || empty($email)) {
                $errors[] = 'All admin user fields are required';
            } else {
                $result = createAdminUser($username, $password, $fullName, $email);
                if ($result['success']) {
                    $messages[] = $result['message'];
                    $currentStep = 5;
                } else {
                    $errors[] = $result['message'];
                }
            }
            break;
            
        case 5:
            if (isset($_POST['generate_sample'])) {
                $result = generateSampleData();
                if ($result['success']) {
                    $messages[] = $result['message'];
                } else {
                    $errors[] = $result['message'];
                }
            }
            $currentStep = 6;
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConstructLink™ Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 0 0.25rem;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
        }
        .step.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .step.completed {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        .install-card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: none;
        }
    </style>
</head>
<body class="bg-light">
    <div class="install-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1><i class="bi bi-gear-fill"></i> ConstructLink™ Installation</h1>
                    <p class="lead">Asset and Inventory Management System Setup</p>
                    <p>Version <?= INSTALL_VERSION ?> by Ranoa Digital Solutions</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <?php foreach ($steps as $stepNum => $stepName): ?>
                <div class="step <?= $stepNum < $currentStep ? 'completed' : ($stepNum == $currentStep ? 'active' : '') ?>">
                    <div class="step-number">
                        <?php if ($stepNum < $currentStep): ?>
                            <i class="bi bi-check-circle-fill"></i>
                        <?php else: ?>
                            <?= $stepNum ?>
                        <?php endif; ?>
                    </div>
                    <div class="step-title"><?= $stepName ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Messages -->
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Installation Steps -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card install-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-arrow-right-circle"></i> 
                            Step <?= $currentStep ?>: <?= $steps[$currentStep] ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($currentStep == 1): ?>
                            <p>Let's start by testing the database connection to ensure everything is configured correctly.</p>
                            <div class="alert alert-info">
                                <strong>Database Configuration:</strong><br>
                                Host: <?= DB_HOST ?><br>
                                Database: <?= DB_NAME ?><br>
                                User: <?= DB_USER ?>
                            </div>
                            <form method="post">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-database-check"></i> Test Database Connection
                                </button>
                            </form>

                        <?php elseif ($currentStep == 2): ?>
                            <p>Now we'll create all the necessary database tables for ConstructLink™.</p>
                            <div class="alert alert-warning">
                                <strong>Warning:</strong> This will create new tables in your database. 
                                If tables already exist, they will not be overwritten.
                            </div>
                            <form method="post">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-table"></i> Create Database Tables
                                </button>
                            </form>

                        <?php elseif ($currentStep == 3): ?>
                            <p>Default data including roles and basic configuration has been inserted.</p>
                            <div class="alert alert-success">
                                <strong>Default Roles Created:</strong><br>
                                • System Admin<br>
                                • Finance Director<br>
                                • Asset Director<br>
                                • Procurement Officer<br>
                                • Warehouseman<br>
                                • Project Manager<br>
                                • Site Inventory Clerk
                            </div>
                            <form method="post">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-arrow-right"></i> Continue
                                </button>
                            </form>

                        <?php elseif ($currentStep == 4): ?>
                            <p>Create your administrator account to manage the system.</p>
                            <form method="post">
                                <div class="mb-3">
                                    <label for="admin_username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="admin_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                    <div class="form-text">Minimum 8 characters</div>
                                </div>
                                <div class="mb-3">
                                    <label for="admin_fullname" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="admin_fullname" name="admin_fullname" required>
                                </div>
                                <div class="mb-3">
                                    <label for="admin_email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Create Admin User
                                </button>
                            </form>

                        <?php elseif ($currentStep == 5): ?>
                            <p>Optionally generate sample data to help you get started with the system.</p>
                            <div class="alert alert-info">
                                <strong>Sample Data Includes:</strong><br>
                                • Sample categories (Tools, Equipment, Materials, etc.)<br>
                                • Sample projects and locations<br>
                                • Sample vendors and makers<br>
                                • Test user accounts for each role
                            </div>
                            <form method="post">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="generate_sample" name="generate_sample" value="1">
                                        <label class="form-check-label" for="generate_sample">
                                            Generate sample data for testing
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-arrow-right"></i> Continue
                                </button>
                            </form>

                        <?php elseif ($currentStep == 6): ?>
                            <div class="text-center">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                                <h3 class="mt-3">Installation Complete!</h3>
                                <p class="lead">ConstructLink™ has been successfully installed and configured.</p>
                                
                                <div class="alert alert-success text-start">
                                    <strong>Next Steps:</strong><br>
                                    1. Delete or rename this installation file (install.php) for security<br>
                                    2. Configure your .env.php file with production settings<br>
                                    3. Set up SSL certificate for secure connections<br>
                                    4. Configure backup procedures<br>
                                    5. Review and customize user roles and permissions
                                </div>

                                <div class="d-grid gap-2">
                                    <a href="/login" class="btn btn-primary btn-lg">
                                        <i class="bi bi-box-arrow-in-right"></i> Go to Login Page
                                    </a>
                                    <a href="/dashboard" class="btn btn-outline-primary">
                                        <i class="bi bi-speedometer2"></i> Go to Dashboard
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">System Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>PHP Version:</strong> <?= PHP_VERSION ?><br>
                                <strong>Server Software:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?><br>
                                <strong>Document Root:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Memory Limit:</strong> <?= ini_get('memory_limit') ?><br>
                                <strong>Max Execution Time:</strong> <?= ini_get('max_execution_time') ?>s<br>
                                <strong>Upload Max Size:</strong> <?= ini_get('upload_max_filesize') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2024 ConstructLink™ by Ranoa Digital Solutions. All rights reserved.</p>
            <p>Asset and Inventory Management System for V CUTAMORA CONSTRUCTION INC.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
