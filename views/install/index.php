<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .install-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            background: #e9ecef;
            color: #6c757d;
            font-weight: bold;
        }
        .step.active {
            background: #007bff;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .requirement-check {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .requirement-check:last-child {
            border-bottom: none;
        }
        .status-icon {
            font-size: 1.2rem;
        }
        .status-pass {
            color: #28a745;
        }
        .status-fail {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-card p-5">
            <div class="text-center mb-4">
                <h1 class="h3 mb-3">
                    <i class="bi bi-gear-fill text-primary"></i>
                    ConstructLink™ Installation
                </h1>
                <p class="text-muted">Asset and Inventory Management System</p>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">1</div>
                <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">2</div>
                <div class="step <?= $step >= 3 ? 'active' : '' ?> <?= $step > 3 ? 'completed' : '' ?>">3</div>
            </div>

            <!-- Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <!-- Manual Installation Instructions -->
                    <?php if (isset($_POST['action']) && $_POST['action'] == 'install_database'): ?>
                        <hr>
                        <h6><i class="bi bi-tools"></i> Manual Installation Required</h6>
                        <p class="mb-2">Since automatic installation failed due to database permissions, please follow these steps:</p>
                        <ol class="small">
                            <li>Access your hosting control panel (cPanel/phpMyAdmin)</li>
                            <li>Navigate to your database: <code><?= defined('DB_NAME') ? DB_NAME : 'your_database' ?></code></li>
                            <li>Import the SQL file: <code>/database/schema.sql</code></li>
                            <li>Or copy and paste the SQL commands from the file</li>
                            <li>Once imported, <a href="?route=install&step=3" class="btn btn-sm btn-outline-primary">Continue to Step 3</a></li>
                        </ol>
                        <div class="mt-2">
                            <a href="view-sql.php" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i> View SQL File
                            </a>
                            <a href="view-sql.php?action=download" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download"></i> Download SQL
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($messages)): ?>
                <div class="alert alert-success">
                    <ul class="mb-0">
                        <?php foreach ($messages as $message): ?>
                            <li><?= htmlspecialchars($message) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Step 1: System Requirements -->
            <?php if ($step == 1): ?>
                <div class="step-content">
                    <h4 class="mb-4">
                        <i class="bi bi-check-circle text-primary"></i>
                        System Requirements Check
                    </h4>
                    
                    <div class="requirements-list mb-4">
                        <?php
                        $controller = new InstallController();
                        $requirements = $controller->checkSystemRequirements();
                        $allPassed = true;
                        
                        foreach ($requirements as $name => $req):
                            if (!$req['status']) $allPassed = false;
                        ?>
                            <div class="requirement-check">
                                <span><?= $name ?></span>
                                <div>
                                    <span class="me-2 text-muted"><?= $req['current'] ?></span>
                                    <i class="bi <?= $req['status'] ? 'bi-check-circle status-pass' : 'bi-x-circle status-fail' ?> status-icon"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($allPassed): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>
                            All system requirements are met! You can proceed with the installation.
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="test_database">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-arrow-right"></i>
                                Test Database Connection
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Some system requirements are not met. Please fix the issues above before proceeding.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Step 2: Database Connection -->
            <?php if ($step == 2): ?>
                <div class="step-content">
                    <h4 class="mb-4">
                        <i class="bi bi-database text-primary"></i>
                        Database Configuration
                    </h4>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Database connection test passed! Now we'll install the database schema.
                    </div>

                    <div class="mb-4">
                        <h6>Database Settings:</h6>
                        <ul class="list-unstyled">
                            <li><strong>Host:</strong> <?= defined('DB_HOST') ? DB_HOST : 'localhost' ?></li>
                            <li><strong>Database:</strong> <?= defined('DB_NAME') ? DB_NAME : 'your_database' ?></li>
                            <li><strong>User:</strong> <?= defined('DB_USER') ? DB_USER : 'your_username' ?></li>
                        </ul>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="install_database">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-download"></i>
                            Install Database Schema
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Step 3: Complete Installation -->
            <?php if ($step == 3): ?>
                <div class="step-content">
                    <h4 class="mb-4">
                        <i class="bi bi-check-circle text-success"></i>
                        Installation Complete
                    </h4>
                    
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i>
                        Database schema has been installed successfully!
                    </div>

                    <div class="mb-4">
                        <h6>Default Admin Account:</h6>
                        <ul class="list-unstyled">
                            <li><strong>Username:</strong> admin</li>
                            <li><strong>Password:</strong> admin123</li>
                        </ul>
                        <small class="text-muted">
                            <i class="bi bi-exclamation-triangle"></i>
                            Please change the default password after your first login.
                        </small>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="complete_installation">
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-check-circle"></i>
                            Complete Installation
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <small class="text-muted">
                    ConstructLink™ by Ranoa Digital Solutions<br>
                    Version <?= APP_VERSION ?>
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
