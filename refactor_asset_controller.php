<?php
/**
 * AssetController Refactoring Script
 *
 * This script refactors AssetController.php to use:
 * - PermissionMiddleware for permission checks
 * - BrandRepository for brand queries
 * - FormDataProvider for form options
 * - ControllerErrorHandler for exception handling
 */

$controllerFile = __DIR__ . '/controllers/AssetController.php';
$backupFile = __DIR__ . '/controllers/AssetController.php.backup';

if (!file_exists($controllerFile)) {
    die("Error: AssetController.php not found\n");
}

// Backup original file
if (!file_exists($backupFile)) {
    copy($controllerFile, $backupFile);
    echo "Created backup: AssetController.php.backup\n";
}

// Read the file
$content = file_get_contents($controllerFile);

// Track changes
$changes = [
    'permission_checks' => 0,
    'brand_queries' => 0,
    'form_options' => 0,
    'error_handlers' => 0,
];

// 1. Replace permission checks with PermissionMiddleware
$permissionReplacements = [
    // index method - line 61
    [
        'old' => "        if (!in_array(\$userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.index');"
    ],
    // delete method
    [
        'old' => "        if (!in_array(\$userRole, ['System Admin', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.delete');"
    ],
    // updateStatus method
    [
        'old' => "        if (!in_array(\$userRole, ['System Admin', 'Asset Director', 'Warehouseman', 'Project Manager'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.update_status');"
    ],
    // export method
    [
        'old' => "        if (!in_array(\$userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.export');"
    ],
    // bulkUpdate method
    [
        'old' => "        if (!in_array(\$userRole, ['System Admin', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.bulk_update');"
    ],
    // utilization method
    [
        'old' => "        if (!in_array(\$userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Project Manager'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.utilization');"
    ],
    // depreciation method
    [
        'old' => "        if (!in_array(\$userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.depreciation');"
    ],
    // generateFromProcurement method
    [
        'old' => "        if (!in_array(\$userRole, ['System Admin', 'Procurement Officer', 'Warehouseman'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.generate_from_procurement');"
    ],
    // legacyCreate method
    [
        'old' => "        if (!in_array(\$userRole, ['Warehouseman', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.legacy_create');"
    ],
    // verificationDashboard method
    [
        'old' => "        if (!in_array(\$userRole, ['Site Inventory Clerk', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.verification_dashboard');"
    ],
    // authorizationDashboard method
    [
        'old' => "        if (!in_array(\$userRole, ['Project Manager', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.authorization_dashboard');"
    ],
    // assignLocation method
    [
        'old' => "        if (!in_array(\$userRole, ['Warehouseman', 'Site Inventory Clerk', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.assign_location');"
    ],
    // verify method
    [
        'old' => "        if (!in_array(\$userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.verify');"
    ],
    // authorize method
    [
        'old' => "        if (!in_array(\$userRole, ['Finance Director', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.authorize');"
    ],
    // validateAssetQuality method
    [
        'old' => "        if (!in_array(\$userRole, ['Site Inventory Clerk', 'Project Manager', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }",
        'new' => "        PermissionMiddleware::requirePermission('assets.validate_quality');"
    ],
];

foreach ($permissionReplacements as $replacement) {
    $count = 0;
    $content = str_replace($replacement['old'], $replacement['new'], $content, $count);
    $changes['permission_checks'] += $count;
}

// 2. Replace brand queries with BrandRepository
$brandQuery = "            \$brandQuery = \"SELECT id, official_name, quality_tier FROM inventory_brands WHERE is_active = 1 ORDER BY official_name ASC\";
            \$stmt = \$this->db->prepare(\$brandQuery);
            \$stmt->execute();
            \$brands = \$stmt->fetchAll(PDO::FETCH_ASSOC);";

$brandReplacement = "            \$brandRepository = new BrandRepository();
            \$brands = \$brandRepository->getActiveBrands();";

$count = 0;
$content = str_replace($brandQuery, $brandReplacement, $content, $count);
$changes['brand_queries'] += $count;

// Also replace the alternate format in create method
$brandQuery2 = "            // Get brands from database
            \$db = Database::getInstance()->getConnection();
            \$brandQuery = \"SELECT id, official_name, quality_tier FROM inventory_brands WHERE is_active = 1 ORDER BY official_name ASC\";
            \$brandStmt = \$db->query(\$brandQuery);
            \$brands = \$brandStmt->fetchAll(PDO::FETCH_ASSOC);";

$brandReplacement2 = "            // Get brands from database
            \$brandRepository = new BrandRepository();
            \$brands = \$brandRepository->getActiveBrands();";

$count = 0;
$content = str_replace($brandQuery2, $brandReplacement2, $content, $count);
$changes['brand_queries'] += $count;

// 3. Replace form options loading with FormDataProvider
$formOptionsOld = "            \$categoryModel = new CategoryModel();
            \$projectModel = new ProjectModel();
            \$makerModel = new MakerModel();
            \$vendorModel = new VendorModel();
            \$clientModel = new ClientModel();
            \$procurementModel = new ProcurementModel();

            \$categories = \$categoryModel->getActiveCategories(); // Includes business fields
            \$projects = \$projectModel->getActiveProjects();
            \$makers = \$makerModel->findAll([], 'name ASC');
            \$vendors = \$vendorModel->findAll([], 'name ASC');
            \$clients = \$clientModel->findAll([], 'name ASC');

            // Get brands from database
            \$brandRepository = new BrandRepository();
            \$brands = \$brandRepository->getActiveBrands();";

$formOptionsNew = "            // Load all form options using FormDataProvider
            \$formProvider = new FormDataProvider();
            \$formOptions = \$formProvider->getAssetFormOptions();
            extract(\$formOptions); // Extracts: categories, projects, makers, vendors, clients, brands

            // Get procurement model separately as it's not in form provider
            \$procurementModel = new ProcurementModel();";

$count = 0;
$content = str_replace($formOptionsOld, $formOptionsNew, $content, $count);
$changes['form_options'] += $count;

// 4. Replace error handlers with ControllerErrorHandler
$errorReplacements = [
    [
        'old' => "        } catch (Exception \$e) {
            error_log(\"Asset listing error: \" . \$e->getMessage());
            \$error = 'Failed to load assets';
            include APP_ROOT . '/views/errors/500.php';
        }",
        'new' => "        } catch (Exception \$e) {
            ControllerErrorHandler::handleException(\$e, 'load assets');
        }"
    ],
    [
        'old' => "        } catch (Exception \$e) {
            error_log(\"Asset view error: \" . \$e->getMessage());
            \$error = 'Failed to load asset details';
            include APP_ROOT . '/views/errors/500.php';
        }",
        'new' => "        } catch (Exception \$e) {
            ControllerErrorHandler::handleException(\$e, 'load asset details');
        }"
    ],
    [
        'old' => "        } catch (Exception \$e) {
            error_log(\"Asset deletion error: \" . \$e->getMessage());
            \$_SESSION['error'] = 'Failed to delete asset';
            header('Location: ?route=assets');
            exit;
        }",
        'new' => "        } catch (Exception \$e) {
            ControllerErrorHandler::handleException(\$e, 'delete asset', false);
        }"
    ],
    [
        'old' => "        } catch (Exception \$e) {
            error_log(\"Status update error: \" . \$e->getMessage());
            \$_SESSION['error'] = 'Failed to update status';
            header('Location: ?route=assets');
            exit;
        }",
        'new' => "        } catch (Exception \$e) {
            ControllerErrorHandler::handleException(\$e, 'update status', false);
        }"
    ],
    [
        'old' => "        } catch (Exception \$e) {
            error_log(\"Export error: \" . \$e->getMessage());
            \$_SESSION['error'] = 'Failed to export assets';
            header('Location: ?route=assets');
            exit;
        }",
        'new' => "        } catch (Exception \$e) {
            ControllerErrorHandler::handleException(\$e, 'export assets', false);
        }"
    ],
];

foreach ($errorReplacements as $replacement) {
    $count = 0;
    $content = str_replace($replacement['old'], $replacement['new'], $content, $count);
    $changes['error_handlers'] += $count;
}

// Save refactored file
file_put_contents($controllerFile, $content);

// Output summary
echo "\n=== Refactoring Complete ===\n";
echo "Changes made:\n";
echo "- Permission checks replaced: {$changes['permission_checks']}\n";
echo "- Brand queries replaced: {$changes['brand_queries']}\n";
echo "- Form options loading replaced: {$changes['form_options']}\n";
echo "- Error handlers replaced: {$changes['error_handlers']}\n";
echo "\nTotal changes: " . array_sum($changes) . "\n";
echo "\nBackup saved to: AssetController.php.backup\n";
echo "Refactored file: controllers/AssetController.php\n";
