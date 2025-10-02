<?php
/**
 * ConstructLink™ Category Controller
 * Handles asset category management operations
 */

class CategoryController {
    private $auth;
    private $categoryModel;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->categoryModel = new CategoryModel();
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Display category listing
     */
    public function index() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        
        // Build filters
        $filters = [];
        if (!empty($_GET['type'])) $filters['type'] = $_GET['type'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        
        try {
            // Get categories with pagination
            $result = $this->categoryModel->getCategoriesWithFilters($filters, $page, $perPage);
            $categories = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];
            
            // Get category statistics
            $categoryStats = $this->categoryModel->getCategoryStatistics();
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            $pageTitle = 'Category Management - ConstructLink™';
            $pageHeader = 'Category Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Categories', 'url' => '?route=categories']
            ];
            
            include APP_ROOT . '/views/categories/index.php';
            
        } catch (Exception $e) {
            error_log("Category listing error: " . $e->getMessage());
            $error = 'Failed to load categories';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create category form
     */
    public function create() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        $formData = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            $formData = [
                'name' => Validator::sanitize($_POST['name'] ?? ''),
                'description' => Validator::sanitize($_POST['description'] ?? ''),
                'is_consumable' => (isset($_POST['is_consumable']) && $_POST['is_consumable'] == '1') ? 1 : 0,
                'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                'generates_assets' => (isset($_POST['generates_assets']) && $_POST['generates_assets'] == '1') ? 1 : 0,
                'asset_type' => Validator::sanitize($_POST['asset_type'] ?? 'capital'),
                'expense_category' => !empty($_POST['expense_category']) ? Validator::sanitize($_POST['expense_category']) : null,
                'depreciation_applicable' => (isset($_POST['depreciation_applicable']) && $_POST['depreciation_applicable'] == '1') ? 1 : 0,
                'capitalization_threshold' => !empty($_POST['capitalization_threshold']) ? (float)$_POST['capitalization_threshold'] : 0.00,
                'business_description' => Validator::sanitize($_POST['business_description'] ?? ''),
                'auto_expense_below_threshold' => (isset($_POST['auto_expense_below_threshold']) && $_POST['auto_expense_below_threshold'] == '1') ? 1 : 0
            ];
            
            // Use the enhanced create method with business validation
            $result = $this->categoryModel->createCategoryWithBusinessRules($formData);
            
            if ($result['success']) {
                header('Location: ?route=categories/view&id=' . $result['category']['id'] . '&message=category_created');
                exit;
            } else {
                if (isset($result['errors'])) {
                    $errors = $result['errors'];
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        // Get parent categories for dropdown
        $parentCategories = $this->categoryModel->getActiveCategories();
        
        // Pass auth instance to view
        $auth = $this->auth;
        
        $pageTitle = 'Create Category - ConstructLink™';
        $pageHeader = 'Create New Category';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Categories', 'url' => '?route=categories'],
            ['title' => 'Create Category', 'url' => '?route=categories/create']
        ];
        
        include APP_ROOT . '/views/categories/create.php';
    }
    
    /**
     * Display edit category form
     */
    public function edit() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $categoryId = $_GET['id'] ?? 0;
        
        if (!$categoryId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $category = $this->categoryModel->getCategoryWithDetails($categoryId);
            
            if (!$category) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $formData = $category;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $formData = [
                    'name' => Validator::sanitize($_POST['name'] ?? ''),
                    'description' => Validator::sanitize($_POST['description'] ?? ''),
                    'is_consumable' => (isset($_POST['is_consumable']) && $_POST['is_consumable'] == '1') ? 1 : 0,
                    'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                    // Business classification fields
                    'generates_assets' => (isset($_POST['generates_assets']) && $_POST['generates_assets'] == '1') ? 1 : 0,
                    'asset_type' => Validator::sanitize($_POST['asset_type'] ?? 'capital'),
                    'expense_category' => !empty($_POST['expense_category']) ? Validator::sanitize($_POST['expense_category']) : null,
                    'depreciation_applicable' => (isset($_POST['depreciation_applicable']) && $_POST['depreciation_applicable'] == '1') ? 1 : 0,
                    'capitalization_threshold' => !empty($_POST['capitalization_threshold']) ? (float)$_POST['capitalization_threshold'] : 0.00,
                    'business_description' => Validator::sanitize($_POST['business_description'] ?? ''),
                    'auto_expense_below_threshold' => (isset($_POST['auto_expense_below_threshold']) && $_POST['auto_expense_below_threshold'] == '1') ? 1 : 0
                ];
                
                // Use business validation for update
                $result = $this->categoryModel->updateCategoryWithBusinessRules($categoryId, $formData);
                
                if ($result['success']) {
                    header('Location: ?route=categories/view&id=' . $categoryId . '&message=category_updated');
                    exit;
                } else {
                    if (isset($result['errors'])) {
                        $errors = $result['errors'];
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            // Get parent categories for dropdown (excluding current category)
            $parentCategories = array_filter(
                $this->categoryModel->getActiveCategories(),
                function($cat) use ($categoryId) {
                    return $cat['id'] != $categoryId;
                }
            );
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            $pageTitle = 'Edit Category - ConstructLink™';
            $pageHeader = 'Edit Category: ' . htmlspecialchars($category['name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Categories', 'url' => '?route=categories'],
                ['title' => 'Edit Category', 'url' => '?route=categories/edit&id=' . $categoryId]
            ];
            
            include APP_ROOT . '/views/categories/edit.php';
            
        } catch (Exception $e) {
            error_log("Category edit error: " . $e->getMessage());
            $error = 'Failed to load category details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * View category details
     */
    public function view() {
        $categoryId = $_GET['id'] ?? 0;
        
        if (!$categoryId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $category = $this->categoryModel->getCategoryWithDetails($categoryId);
            
            if (!$category) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Get category assets
            $assetModel = new AssetModel();
            $categoryAssets = $assetModel->getAssetsByCategory($categoryId);
            
            // Get category statistics
            $categoryStats = $this->categoryModel->getCategoryStatistics($categoryId);
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            $pageTitle = 'Category Details - ConstructLink™';
            $pageHeader = 'Category: ' . htmlspecialchars($category['name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Categories', 'url' => '?route=categories'],
                ['title' => 'View Details', 'url' => '?route=categories/view&id=' . $categoryId]
            ];
            
            include APP_ROOT . '/views/categories/view.php';
            
        } catch (Exception $e) {
            error_log("Category view error: " . $e->getMessage());
            $error = 'Failed to load category details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Delete category
     */
    public function delete() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $categoryId = $_GET['id'] ?? 0;
        
        if (!$categoryId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $result = $this->categoryModel->deleteCategory($categoryId);
            
            if ($result['success']) {
                header('Location: ?route=categories&message=category_deleted');
                exit;
            } else {
                header('Location: ?route=categories&error=' . urlencode($result['message']));
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Category deletion error: " . $e->getMessage());
            header('Location: ?route=categories&error=Failed to delete category');
            exit;
        }
    }
    
    /**
     * Get category assets via AJAX
     */
    public function getAssets() {
        $categoryId = $_GET['category_id'] ?? 0;
        
        if (!$categoryId) {
            echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
            return;
        }
        
        try {
            $assetModel = new AssetModel();
            $assets = $assetModel->getAssetsByCategory($categoryId);
            
            echo json_encode([
                'success' => true,
                'assets' => $assets
            ]);
            
        } catch (Exception $e) {
            error_log("Get category assets error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load category assets']);
        }
    }
    
    /**
     * Get categories for dropdown via AJAX
     */
    public function getForDropdown() {
        try {
            $categories = $this->categoryModel->getActiveCategories();
            
            echo json_encode([
                'success' => true,
                'categories' => $categories
            ]);
            
        } catch (Exception $e) {
            error_log("Get categories dropdown error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load categories']);
        }
    }
    
    /**
     * API endpoint to get category details
     */
    public function getCategoryDetails() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $categoryId = $_GET['id'] ?? 0;
        
        if (!$categoryId) {
            echo json_encode(['success' => false, 'message' => 'Category ID required']);
            return;
        }
        
        try {
            $category = $this->categoryModel->find($categoryId);
            
            if (!$category) {
                echo json_encode(['success' => false, 'message' => 'Category not found']);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'category' => $category
            ]);
            
        } catch (Exception $e) {
            error_log("Get category details API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load category details']);
        }
    }
}
?>
