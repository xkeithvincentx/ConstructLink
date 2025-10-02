<?php
/**
 * ConstructLink™ Database Class
 * Optimized for performance with connection pooling
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $queryCache = [];
    private $cacheEnabled = true;
    private $maxCacheSize = 50; // Reduced cache size
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    public function getConnection() {
        // Check if connection is still alive
        if ($this->connection === null) {
            $this->connect();
        }
        
        try {
            $this->connection->query("SELECT 1");
        } catch (PDOException $e) {
            // Reconnect if connection is lost
            $this->connect();
        }
        
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        $cacheKey = md5($sql . serialize($params));
        
        // Check cache for SELECT queries only if cache is small
        if ($this->cacheEnabled && strpos(strtoupper(trim($sql)), "SELECT") === 0 && count($this->queryCache) < $this->maxCacheSize) {
            if (isset($this->queryCache[$cacheKey])) {
                return $this->queryCache[$cacheKey];
            }
        }
        
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cache SELECT results only for small result sets
            if ($this->cacheEnabled && strpos(strtoupper(trim($sql)), "SELECT") === 0 && count($result) < 100) {
                if (count($this->queryCache) >= $this->maxCacheSize) {
                    // Remove oldest cache entry
                    array_shift($this->queryCache);
                }
                $this->queryCache[$cacheKey] = $result;
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " SQL: " . $sql);
            return [];
        }
    }
    
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $result = $stmt->execute($params);
            
            // Clear cache for data modification queries
            if (preg_match("/^(INSERT|UPDATE|DELETE)/i", trim($sql))) {
                $this->clearCache();
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Execute error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    public function rollback() {
        return $this->getConnection()->rollback();
    }
    
    public function clearCache() {
        $this->queryCache = [];
    }
    
    public function setCacheEnabled($enabled) {
        $this->cacheEnabled = $enabled;
    }
    
    /**
     * Check if a table exists in the database
     */
    public function tableExists($tableName) {
        try {
            $sql = "SHOW TABLES LIKE '" . $tableName . "'";
            $stmt = $this->getConnection()->query($sql);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Table exists check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $this->getConnection()->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get database schema information
     */
    public function getTableSchema($tableName) {
        try {
            $stmt = $this->getConnection()->prepare("DESCRIBE `$tableName`");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Schema info error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all tables in the database
     */
    public function getAllTables() {
        try {
            $stmt = $this->getConnection()->query("SHOW TABLES");
            $tables = [];
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            return $tables;
        } catch (PDOException $e) {
            error_log("Get tables error: " . $e->getMessage());
            return [];
        }
    }
}
?>
