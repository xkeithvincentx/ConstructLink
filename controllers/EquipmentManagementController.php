<?php
/**
 * Equipment Management Controller
 *
 * Manages equipment classification system: Categories, Equipment Types, and Subtypes
 * Accessible to: Asset Director, System Admin
 *
 * Features:
 * - CRUD operations for categories, equipment types, and subtypes
 * - Database export/backup functionality
 * - Bulk import from CSV/Excel
 * - Usage statistics and reports
 * - Equipment catalog management
 *
 * Developed by: Ranoa Digital Solutions
 */

class EquipmentManagementController {
    private $auth;
    private $db;

    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Main equipment management dashboard
     */
    public function index() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        // Check permissions
        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }

        // Get statistics
        $stats = $this->getStatistics();

        include APP_ROOT . '/views/equipment/management/index.php';
    }

    /**
     * Get system statistics
     */
    private function getStatistics() {
        $stats = [];

        // Categories count
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM categories");
        $stats['total_categories'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Equipment types count
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM equipment_types WHERE is_active = 1");
        $stats['total_equipment_types'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Subtypes count
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM equipment_subtypes WHERE is_active = 1");
        $stats['total_subtypes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Items using this classification
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM assets");
        $stats['total_assets'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Categories breakdown
        $stmt = $this->db->query("
            SELECT
                c.id,
                c.name,
                c.iso_code,
                c.description,
                c.asset_type,
                c.is_consumable,
                c.generates_assets,
                COUNT(DISTINCT et.id) as equipment_types_count,
                COUNT(DISTINCT es.id) as subtypes_count,
                COUNT(DISTINCT a.id) as assets_count
            FROM categories c
            LEFT JOIN equipment_types et ON c.id = et.category_id
            LEFT JOIN equipment_subtypes es ON et.id = es.equipment_type_id
            LEFT JOIN assets a ON c.id = a.category_id
            GROUP BY c.id
            ORDER BY c.name
        ");
        $stats['categories_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }

    // ==================================================================================
    // CATEGORIES MANAGEMENT
    // ==================================================================================

    /**
     * List all categories
     */
    public function categories() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }

        $stmt = $this->db->query("
            SELECT
                c.*,
                COUNT(DISTINCT et.id) as equipment_types_count,
                COUNT(DISTINCT a.id) as assets_count
            FROM categories c
            LEFT JOIN equipment_types et ON c.id = et.category_id
            LEFT JOIN assets a ON c.id = a.category_id
            GROUP BY c.id
            ORDER BY c.name
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include APP_ROOT . '/views/equipment/management/categories.php';
    }

    /**
     * Create new category
     */
    public function createCategory() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        $name = Validator::sanitize($_POST['name'] ?? '');
        $iso_code = strtoupper(Validator::sanitize($_POST['iso_code'] ?? ''));
        $description = Validator::sanitize($_POST['description'] ?? '');
        $is_consumable = isset($_POST['is_consumable']) ? 1 : 0;
        $generates_assets = isset($_POST['generates_assets']) ? 1 : 0;
        $asset_type = Validator::sanitize($_POST['asset_type'] ?? 'capital');
        $depreciation_applicable = isset($_POST['depreciation_applicable']) ? 1 : 0;
        $capitalization_threshold = floatval($_POST['capitalization_threshold'] ?? 0);
        $business_description = Validator::sanitize($_POST['business_description'] ?? '');

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Category name is required']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO categories (
                    name, iso_code, description, is_consumable, generates_assets,
                    asset_type, depreciation_applicable, capitalization_threshold, business_description
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name, $iso_code, $description, $is_consumable, $generates_assets,
                $asset_type, $depreciation_applicable, $capitalization_threshold, $business_description
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Category created successfully',
                'id' => $this->db->lastInsertId()
            ]);

        } catch (PDOException $e) {
            error_log("Create category error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to create category: ' . $e->getMessage()]);
        }
    }

    /**
     * Update category
     */
    public function updateCategory() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        $id = intval($_POST['id'] ?? 0);
        $name = Validator::sanitize($_POST['name'] ?? '');
        $iso_code = strtoupper(Validator::sanitize($_POST['iso_code'] ?? ''));
        $description = Validator::sanitize($_POST['description'] ?? '');
        $is_consumable = isset($_POST['is_consumable']) ? 1 : 0;
        $generates_assets = isset($_POST['generates_assets']) ? 1 : 0;
        $asset_type = Validator::sanitize($_POST['asset_type'] ?? 'capital');
        $depreciation_applicable = isset($_POST['depreciation_applicable']) ? 1 : 0;
        $capitalization_threshold = floatval($_POST['capitalization_threshold'] ?? 0);
        $business_description = Validator::sanitize($_POST['business_description'] ?? '');

        if ($id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE categories SET
                    name = ?,
                    iso_code = ?,
                    description = ?,
                    is_consumable = ?,
                    generates_assets = ?,
                    asset_type = ?,
                    depreciation_applicable = ?,
                    capitalization_threshold = ?,
                    business_description = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $name, $iso_code, $description, $is_consumable, $generates_assets,
                $asset_type, $depreciation_applicable, $capitalization_threshold,
                $business_description, $id
            ]);

            echo json_encode(['success' => true, 'message' => 'Category updated successfully']);

        } catch (PDOException $e) {
            error_log("Update category error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update category: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete category (soft delete by checking usage)
     */
    public function deleteCategory() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['System Admin'])) { // Only System Admin can delete
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
            return;
        }

        try {
            // Check if category is in use
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM assets WHERE category_id = ?");
            $stmt->execute([$id]);
            $usage = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usage['count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => "Cannot delete category: {$usage['count']} items are using this category"
                ]);
                return;
            }

            // Check equipment types
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM equipment_types WHERE category_id = ?");
            $stmt->execute([$id]);
            $types = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($types['count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => "Cannot delete category: {$types['count']} equipment types are linked to this category"
                ]);
                return;
            }

            // Safe to delete
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);

        } catch (PDOException $e) {
            error_log("Delete category error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete category: ' . $e->getMessage()]);
        }
    }

    // ==================================================================================
    // EQUIPMENT TYPES MANAGEMENT
    // ==================================================================================

    /**
     * List equipment types
     */
    public function equipmentTypes() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }

        $categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;

        $query = "
            SELECT
                et.*,
                c.name as category_name,
                c.iso_code as category_iso_code,
                COUNT(DISTINCT s.id) as subtypes_count,
                COUNT(DISTINCT a.id) as assets_count
            FROM equipment_types et
            LEFT JOIN categories c ON et.category_id = c.id
            LEFT JOIN equipment_subtypes s ON et.id = s.equipment_type_id
            LEFT JOIN assets a ON et.id = a.equipment_type_id
        ";

        if ($categoryId > 0) {
            $query .= " WHERE et.category_id = ?";
        }

        $query .= " GROUP BY et.id ORDER BY c.name, et.name";

        $stmt = $this->db->prepare($query);
        if ($categoryId > 0) {
            $stmt->execute([$categoryId]);
        } else {
            $stmt->execute();
        }
        $equipmentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get categories for filter
        $stmt = $this->db->query("SELECT id, name, iso_code FROM categories ORDER BY name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include APP_ROOT . '/views/equipment/management/equipment_types.php';
    }

    /**
     * Create equipment type
     */
    public function createEquipmentType() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        $category_id = intval($_POST['category_id'] ?? 0);
        $name = Validator::sanitize($_POST['name'] ?? '');
        $description = Validator::sanitize($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 1;

        if ($category_id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Category and name are required']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO equipment_types (category_id, name, description, is_active)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([$category_id, $name, $description, $is_active]);

            echo json_encode([
                'success' => true,
                'message' => 'Equipment type created successfully',
                'id' => $this->db->lastInsertId()
            ]);

        } catch (PDOException $e) {
            error_log("Create equipment type error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to create equipment type: ' . $e->getMessage()]);
        }
    }

    /**
     * Update equipment type
     */
    public function updateEquipmentType() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        $id = intval($_POST['id'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $name = Validator::sanitize($_POST['name'] ?? '');
        $description = Validator::sanitize($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0 || $category_id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE equipment_types SET
                    category_id = ?,
                    name = ?,
                    description = ?,
                    is_active = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([$category_id, $name, $description, $is_active, $id]);

            echo json_encode(['success' => true, 'message' => 'Equipment type updated successfully']);

        } catch (PDOException $e) {
            error_log("Update equipment type error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update equipment type: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete equipment type
     */
    public function deleteEquipmentType() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid equipment type ID']);
            return;
        }

        try {
            // Check usage
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM assets WHERE equipment_type_id = ?");
            $stmt->execute([$id]);
            $usage = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usage['count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => "Cannot delete: {$usage['count']} items are using this equipment type"
                ]);
                return;
            }

            // Delete subtypes first
            $stmt = $this->db->prepare("DELETE FROM equipment_subtypes WHERE equipment_type_id = ?");
            $stmt->execute([$id]);

            // Delete equipment type
            $stmt = $this->db->prepare("DELETE FROM equipment_types WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Equipment type deleted successfully']);

        } catch (PDOException $e) {
            error_log("Delete equipment type error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete equipment type: ' . $e->getMessage()]);
        }
    }

    // ==================================================================================
    // SUBTYPES MANAGEMENT
    // ==================================================================================

    /**
     * List subtypes
     */
    public function subtypes() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }

        $equipmentTypeId = isset($_GET['equipment_type']) ? intval($_GET['equipment_type']) : 0;

        $query = "
            SELECT
                s.*,
                et.name as equipment_type_name,
                et.category_id,
                c.name as category_name,
                c.iso_code as category_iso_code,
                COUNT(DISTINCT a.id) as assets_count
            FROM equipment_subtypes s
            LEFT JOIN equipment_types et ON s.equipment_type_id = et.id
            LEFT JOIN categories c ON et.category_id = c.id
            LEFT JOIN assets a ON s.id = a.equipment_subtype_id
        ";

        if ($equipmentTypeId > 0) {
            $query .= " WHERE s.equipment_type_id = ?";
        }

        $query .= " GROUP BY s.id ORDER BY c.name, et.name, s.subtype_name";

        $stmt = $this->db->prepare($query);
        if ($equipmentTypeId > 0) {
            $stmt->execute([$equipmentTypeId]);
        } else {
            $stmt->execute();
        }
        $subtypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get equipment types for filter
        $stmt = $this->db->query("
            SELECT et.id, et.name, et.category_id, c.name as category_name
            FROM equipment_types et
            LEFT JOIN categories c ON et.category_id = c.id
            ORDER BY c.name, et.name
        ");
        $equipmentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get categories for filter
        $stmt = $this->db->query("SELECT id, name, iso_code FROM categories ORDER BY name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organize equipment types by category for JavaScript
        $equipmentTypesByCategory = [];
        foreach ($equipmentTypes as $type) {
            $catId = $type['category_id'];
            if (!isset($equipmentTypesByCategory[$catId])) {
                $equipmentTypesByCategory[$catId] = [];
            }
            $equipmentTypesByCategory[$catId][] = [
                'id' => $type['id'],
                'name' => $type['name']
            ];
        }

        include APP_ROOT . '/views/equipment/management/subtypes.php';
    }

    /**
     * Create subtype
     */
    public function createSubtype() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        $equipment_type_id = intval($_POST['equipment_type_id'] ?? 0);
        $subtype_name = Validator::sanitize($_POST['subtype_name'] ?? '');
        $material_type = Validator::sanitize($_POST['material_type'] ?? '');
        $power_source = Validator::sanitize($_POST['power_source'] ?? '');
        $size_category = Validator::sanitize($_POST['size_category'] ?? '');
        $application_area = Validator::sanitize($_POST['application_area'] ?? '');
        $technical_specs = $_POST['technical_specs'] ?? ''; // JSON
        $discipline_tags = $_POST['discipline_tags'] ?? ''; // JSON
        $is_active = 1;

        if ($equipment_type_id <= 0 || empty($subtype_name)) {
            echo json_encode(['success' => false, 'message' => 'Equipment type and subtype name are required']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO equipment_subtypes (
                    equipment_type_id, subtype_name, material_type, power_source,
                    size_category, application_area, technical_specs, discipline_tags, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $equipment_type_id, $subtype_name, $material_type, $power_source,
                $size_category, $application_area, $technical_specs, $discipline_tags, $is_active
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Subtype created successfully',
                'id' => $this->db->lastInsertId()
            ]);

        } catch (PDOException $e) {
            error_log("Create subtype error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to create subtype: ' . $e->getMessage()]);
        }
    }

    /**
     * Update subtype
     */
    public function updateSubtype() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        $id = intval($_POST['id'] ?? 0);
        $equipment_type_id = intval($_POST['equipment_type_id'] ?? 0);
        $subtype_name = Validator::sanitize($_POST['subtype_name'] ?? '');
        $material_type = Validator::sanitize($_POST['material_type'] ?? '');
        $power_source = Validator::sanitize($_POST['power_source'] ?? '');
        $size_category = Validator::sanitize($_POST['size_category'] ?? '');
        $application_area = Validator::sanitize($_POST['application_area'] ?? '');
        $technical_specs = $_POST['technical_specs'] ?? ''; // JSON
        $discipline_tags = $_POST['discipline_tags'] ?? ''; // JSON
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0 || $equipment_type_id <= 0 || empty($subtype_name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE equipment_subtypes SET
                    equipment_type_id = ?,
                    subtype_name = ?,
                    material_type = ?,
                    power_source = ?,
                    size_category = ?,
                    application_area = ?,
                    technical_specs = ?,
                    discipline_tags = ?,
                    is_active = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $equipment_type_id, $subtype_name, $material_type, $power_source,
                $size_category, $application_area, $technical_specs, $discipline_tags,
                $is_active, $id
            ]);

            echo json_encode(['success' => true, 'message' => 'Subtype updated successfully']);

        } catch (PDOException $e) {
            error_log("Update subtype error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update subtype: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete subtype
     */
    public function deleteSubtype() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid subtype ID']);
            return;
        }

        try {
            // Check usage
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM assets WHERE subtype_id = ?");
            $stmt->execute([$id]);
            $usage = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usage['count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => "Cannot delete: {$usage['count']} items are using this subtype"
                ]);
                return;
            }

            $stmt = $this->db->prepare("DELETE FROM equipment_subtypes WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Subtype deleted successfully']);

        } catch (PDOException $e) {
            error_log("Delete subtype error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete subtype: ' . $e->getMessage()]);
        }
    }

    // ==================================================================================
    // DATABASE EXPORT / BACKUP
    // ==================================================================================

    /**
     * Export equipment classification to SQL
     */
    public function exportDatabase() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = "equipment_classification_backup_{$timestamp}.sql";

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo "-- ==================================================================================\n";
        echo "-- ConstructLink Equipment Classification Backup\n";
        echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "-- By: " . ($currentUser['username'] ?? 'Unknown') . "\n";
        echo "-- ==================================================================================\n\n";

        // Export categories
        echo "-- Categories\n";
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY id");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($categories as $cat) {
            $values = array_map(function($v) {
                return is_null($v) ? 'NULL' : $this->db->quote($v);
            }, $cat);

            echo "INSERT INTO categories (";
            echo implode(', ', array_keys($cat));
            echo ") VALUES (";
            echo implode(', ', $values);
            echo ");\n";
        }

        echo "\n-- Equipment Types\n";
        $stmt = $this->db->query("SELECT * FROM equipment_types ORDER BY id");
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($types as $type) {
            $values = array_map(function($v) {
                return is_null($v) ? 'NULL' : $this->db->quote($v);
            }, $type);

            echo "INSERT INTO equipment_types (";
            echo implode(', ', array_keys($type));
            echo ") VALUES (";
            echo implode(', ', $values);
            echo ");\n";
        }

        echo "\n-- Equipment Subtypes\n";
        $stmt = $this->db->query("SELECT * FROM equipment_subtypes ORDER BY id");
        $subtypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($subtypes as $subtype) {
            $values = array_map(function($v) {
                return is_null($v) ? 'NULL' : $this->db->quote($v);
            }, $subtype);

            echo "INSERT INTO equipment_subtypes (";
            echo implode(', ', array_keys($subtype));
            echo ") VALUES (";
            echo implode(', ', $values);
            echo ");\n";
        }

        exit;
    }

    /**
     * Export to CSV
     */
    public function exportCSV() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        $type = $_GET['type'] ?? 'categories';
        $timestamp = date('Y-m-d_H-i-s');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="equipment_' . $type . '_' . $timestamp . '.csv"');

        $output = fopen('php://output', 'w');

        switch ($type) {
            case 'categories':
                fputcsv($output, ['ID', 'Name', 'ISO Code', 'Description', 'Is Consumable', 'Asset Type', 'Depreciation Applicable', 'Capitalization Threshold']);
                $stmt = $this->db->query("SELECT id, name, iso_code, description, is_consumable, asset_type, depreciation_applicable, capitalization_threshold FROM categories ORDER BY name");
                break;

            case 'equipment_types':
                fputcsv($output, ['ID', 'Category', 'Name', 'Description', 'Is Active']);
                $stmt = $this->db->query("
                    SELECT et.id, c.name as category, et.name, et.description, et.is_active
                    FROM equipment_types et
                    LEFT JOIN categories c ON et.category_id = c.id
                    ORDER BY c.name, et.name
                ");
                break;

            case 'subtypes':
                fputcsv($output, ['ID', 'Equipment Type', 'Subtype Name', 'Material Type', 'Power Source', 'Size Category', 'Application Area']);
                $stmt = $this->db->query("
                    SELECT s.id, et.name as equipment_type, s.subtype_name, s.material_type, s.power_source, s.size_category, s.application_area
                    FROM equipment_subtypes s
                    LEFT JOIN equipment_types et ON s.equipment_type_id = et.id
                    ORDER BY et.name, s.subtype_name
                ");
                break;
        }

        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
