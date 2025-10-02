<?php
/**
 * ConstructLink™ Simple Setup Script
 * Direct installation without routing
 */

// Start session and set error reporting
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define application constants
define('APP_ROOT', __DIR__);
define('APP_VERSION', '1.0.0');
define('APP_NAME', 'ConstructLink™');

echo "<h1>ConstructLink™ Simple Setup</h1>";

// Step 1: Load configuration
echo "<h2>Step 1: Loading Configuration</h2>";
try {
    require_once APP_ROOT . '/config/config.php';
    echo "<p>✅ Configuration loaded successfully</p>";
    echo "<p>Database: " . DB_NAME . " on " . DB_HOST . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Configuration error: " . $e->getMessage() . "</p>";
    exit;
}

// Step 2: Test database connection
echo "<h2>Step 2: Testing Database Connection</h2>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p>✅ Database server connection successful</p>";
    
    // Check if database exists
    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([DB_NAME]);
    
    if ($stmt->fetch()) {
        echo "<p>✅ Database '" . DB_NAME . "' exists</p>";
    } else {
        echo "<p>⚠️ Database '" . DB_NAME . "' does not exist. Creating...</p>";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p>✅ Database '" . DB_NAME . "' created successfully</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Database connection error: " . $e->getMessage() . "</p>";
    exit;
}

// Step 3: Connect to the specific database
echo "<h2>Step 3: Connecting to Application Database</h2>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "<p>✅ Connected to database '" . DB_NAME . "'</p>";
} catch (PDOException $e) {
    echo "<p>❌ Database connection error: " . $e->getMessage() . "</p>";
    exit;
}

// Step 4: Check if tables exist
echo "<h2>Step 4: Checking Database Tables</h2>";
$requiredTables = ['users', 'roles', 'projects', 'categories', 'assets'];
$existingTables = [];

foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        $existingTables[] = $table;
        echo "<p>✅ Table '$table' exists</p>";
    } catch (PDOException $e) {
        echo "<p>❌ Table '$table' does not exist</p>";
    }
}

// Step 5: Install database schema if needed
if (count($existingTables) < count($requiredTables)) {
    echo "<h2>Step 5: Installing Database Schema</h2>";
    
    if ($_POST['install_db'] ?? false) {
        try {
            // Load and execute the database schema
            require_once APP_ROOT . '/config/database.php';
            
            $schema = new DatabaseSchema();
            $schema->createTables();
            
            echo "<p>✅ Database schema installed successfully!</p>";
            echo "<p><strong>Default Admin Account:</strong></p>";
            echo "<ul>";
            echo "<li>Username: admin</li>";
            echo "<li>Password: admin123</li>";
            echo "</ul>";
            echo "<p><a href='/'>Go to Application</a></p>";
            
        } catch (Exception $e) {
            echo "<p>❌ Database installation error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>Database tables are missing. Click the button below to install them.</p>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='install_db' value='1'>";
        echo "<button type='submit' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>Install Database Schema</button>";
        echo "</form>";
    }
} else {
    echo "<h2>Step 5: Database Ready</h2>";
    echo "<p>✅ All required tables exist. The system is ready to use!</p>";
    echo "<p><a href='/'>Go to Application</a></p>";
}

echo "<hr>";
echo "<p><small>ConstructLink™ by Ranoa Digital Solutions - Version " . APP_VERSION . "</small></p>";
?>
