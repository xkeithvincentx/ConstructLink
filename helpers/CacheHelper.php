<?php
/**
 * ConstructLink™ Cache Helper
 * Simple in-memory caching for reference data and frequently accessed queries
 *
 * Usage:
 * - Cache reference data (roles, positions, categories) that rarely changes
 * - Cache query results for the duration of the HTTP request
 * - Reduce database calls for repeated data access within same request
 *
 * Performance Benefits:
 * - Eliminates redundant database queries within same request
 * - Reduces memory overhead compared to full-page caching
 * - Simple API for get/set/clear operations
 */

class CacheHelper {
    private static $cache = [];
    private static $cacheHits = 0;
    private static $cacheMisses = 0;

    /**
     * Get cached value by key
     *
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found
     */
    public static function get($key) {
        if (isset(self::$cache[$key])) {
            self::$cacheHits++;
            return self::$cache[$key];
        }

        self::$cacheMisses++;
        return null;
    }

    /**
     * Set cache value
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (not implemented for in-memory cache)
     * @return void
     */
    public static function set($key, $value, $ttl = 0) {
        self::$cache[$key] = $value;
    }

    /**
     * Check if key exists in cache
     *
     * @param string $key Cache key
     * @return bool
     */
    public static function has($key) {
        return isset(self::$cache[$key]);
    }

    /**
     * Remove specific cache entry
     *
     * @param string $key Cache key
     * @return void
     */
    public static function forget($key) {
        unset(self::$cache[$key]);
    }

    /**
     * Clear all cache
     *
     * @return void
     */
    public static function clear() {
        self::$cache = [];
        self::$cacheHits = 0;
        self::$cacheMisses = 0;
    }

    /**
     * Get cache statistics
     *
     * @return array Cache stats
     */
    public static function stats() {
        return [
            'size' => count(self::$cache),
            'hits' => self::$cacheHits,
            'misses' => self::$cacheMisses,
            'hit_rate' => self::$cacheHits + self::$cacheMisses > 0
                ? round(self::$cacheHits / (self::$cacheHits + self::$cacheMisses) * 100, 2) . '%'
                : '0%'
        ];
    }

    /**
     * Remember: Get from cache or execute callback and cache result
     * This is the most convenient method for caching queries
     *
     * @param string $key Cache key
     * @param callable $callback Function to execute if cache miss
     * @param int $ttl Time to live (not implemented for in-memory cache)
     * @return mixed
     */
    public static function remember($key, $callback, $ttl = 0) {
        $value = self::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }

    /**
     * Get all roles (cached)
     * Example of domain-specific caching method
     *
     * @return array Roles
     */
    public static function getRoles() {
        return self::remember('roles', function() {
            $db = Database::getInstance();
            return $db->query("SELECT * FROM roles ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Get all positions (cached)
     *
     * @return array Positions
     */
    public static function getPositions() {
        return self::remember('positions', function() {
            $db = Database::getInstance();
            return $db->query("SELECT * FROM positions ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Get all active categories (cached)
     *
     * @return array Categories
     */
    public static function getCategories() {
        return self::remember('categories', function() {
            $db = Database::getInstance();
            return $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Get all active projects (cached)
     *
     * @return array Projects
     */
    public static function getProjects() {
        return self::remember('projects', function() {
            $db = Database::getInstance();
            return $db->query("SELECT * FROM projects WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Get equipment types for a category (cached)
     *
     * @param int $categoryId Category ID
     * @return array Equipment types
     */
    public static function getEquipmentTypes($categoryId) {
        $key = "equipment_types_{$categoryId}";
        return self::remember($key, function() use ($categoryId) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM equipment_types WHERE category_id = ? AND is_active = 1 ORDER BY name ASC");
            $stmt->execute([$categoryId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    /**
     * Cache warmup: Preload frequently accessed data
     * Call this at application bootstrap or start of borrowed tools module
     *
     * @return void
     */
    public static function warmup() {
        self::getRoles();
        self::getPositions();
        self::getCategories();
        self::getProjects();
    }
}
?>
