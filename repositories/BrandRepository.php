<?php
/**
 * ConstructLink™ Brand Repository
 *
 * Handles database operations for brands (inventory_brands table).
 * Provides centralized brand data access to eliminate code duplication
 * across controllers.
 *
 * DESIGN PATTERN: Repository Pattern
 * - Encapsulates data access logic
 * - Provides clean API for brand operations
 * - Separates business logic from data access
 *
 * DATABASE TABLE: inventory_brands
 * - id: Primary key
 * - official_name: Brand display name
 * - quality_tier: Quality classification (Premium, Standard, etc.)
 * - is_active: Active status flag
 *
 * USAGE:
 * ```php
 * $brandRepo = new BrandRepository();
 * $brands = $brandRepo->getActiveBrands();
 * ```
 *
 * @package ConstructLink
 * @version 1.0.0
 */

class BrandRepository
{
    /**
     * Database connection instance
     *
     * @var PDO
     */
    private $db;

    /**
     * Constructor - Initialize database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all active brands ordered by official name
     *
     * Returns brands with is_active = 1, sorted alphabetically by official name.
     * Used for dropdown selects in asset forms.
     *
     * @return array Array of brand records with id, official_name, and quality_tier
     *
     * @example
     * ```php
     * $brands = $brandRepo->getActiveBrands();
     * // Returns:
     * // [
     * //     ['id' => 1, 'official_name' => 'Bosch', 'quality_tier' => 'Premium'],
     * //     ['id' => 2, 'official_name' => 'DeWalt', 'quality_tier' => 'Standard'],
     * // ]
     * ```
     */
    public function getActiveBrands(): array
    {
        try {
            $query = "SELECT id, official_name, quality_tier
                      FROM inventory_brands
                      WHERE is_active = 1
                      ORDER BY official_name ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("BrandRepository::getActiveBrands() error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all brands (including inactive) ordered by official name
     *
     * @return array Array of all brand records
     */
    public function getAllBrands(): array
    {
        try {
            $query = "SELECT id, official_name, quality_tier, is_active
                      FROM inventory_brands
                      ORDER BY official_name ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("BrandRepository::getAllBrands() error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get brand by ID
     *
     * @param int $id Brand ID
     * @return array|false Brand record or false if not found
     */
    public function findById(int $id)
    {
        try {
            $query = "SELECT id, official_name, quality_tier, is_active
                      FROM inventory_brands
                      WHERE id = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("BrandRepository::findById() error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get brands by quality tier
     *
     * @param string $qualityTier Quality tier (e.g., 'Premium', 'Standard', 'Economy')
     * @return array Array of brand records matching the quality tier
     */
    public function getByQualityTier(string $qualityTier): array
    {
        try {
            $query = "SELECT id, official_name, quality_tier
                      FROM inventory_brands
                      WHERE quality_tier = ? AND is_active = 1
                      ORDER BY official_name ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$qualityTier]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("BrandRepository::getByQualityTier() error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if brand exists by ID
     *
     * @param int $id Brand ID
     * @return bool True if brand exists
     */
    public function exists(int $id): bool
    {
        try {
            $query = "SELECT COUNT(*) as count FROM inventory_brands WHERE id = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['count'] ?? 0) > 0;
        } catch (PDOException $e) {
            error_log("BrandRepository::exists() error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get brands as key-value pairs for dropdown selects
     *
     * @return array Associative array with id => official_name
     *
     * @example
     * ```php
     * $options = $brandRepo->getDropdownOptions();
     * // Returns: [1 => 'Bosch', 2 => 'DeWalt', ...]
     * ```
     */
    public function getDropdownOptions(): array
    {
        $brands = $this->getActiveBrands();
        $options = [];

        foreach ($brands as $brand) {
            $options[$brand['id']] = $brand['official_name'];
        }

        return $options;
    }

    /**
     * Get count of active brands
     *
     * @return int Number of active brands
     */
    public function getActiveCount(): int
    {
        try {
            $query = "SELECT COUNT(*) as count
                      FROM inventory_brands
                      WHERE is_active = 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($result['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("BrandRepository::getActiveCount() error: " . $e->getMessage());
            return 0;
        }
    }
}
