<?php
/**
 * ConstructLink™ Base Model
 * Optimized base model with caching and performance improvements
 */

class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = "id";
    protected $fillable = [];
    protected $timestamps = true;
    protected $cache = [];
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Find all records with caching
     */
    public function findAll($conditions = [], $orderBy = null, $limit = 0) {
        $cacheKey = md5($this->table . serialize($conditions) . $orderBy . $limit);
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $sql = "SELECT * FROM `{$this->table}`";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                if (is_array($value)) {
                    // Handle IN clause for array values
                    $placeholders = array_fill(0, count($value), '?');
                    $whereClause[] = "`{$key}` IN (" . implode(', ', $placeholders) . ")";
                    $params = array_merge($params, $value);
                } else {
                    $whereClause[] = "`{$key}` = ?";
                    $params[] = $value;
                }
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cache result
            $this->cache[$cacheKey] = $result;
            
            return $result;
        } catch (PDOException $e) {
            error_log("FindAll error for table {$this->table}: " . $e->getMessage() . " | SQL: " . $sql);
            return [];
        }
    }
    
    /**
     * Find single record by ID
     */
    public function find($id) {
        $cacheKey = $this->table . "_" . $id;
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->cache[$cacheKey] = $result;
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Find error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Alias for find() method for consistency
     */
    public function findById($id) {
        return $this->find($id);
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data["created_at"] = date("Y-m-d H:i:s");
            $data["updated_at"] = date("Y-m-d H:i:s");
        }
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), "?");
        
        $sql = "INSERT INTO {$this->table} (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($data));
            
            $id = $this->db->lastInsertId();
            $this->clearCache();
            
            return $this->find($id);
        } catch (PDOException $e) {
            error_log("Create error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data["updated_at"] = date("Y-m-d H:i:s");
        }
        
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = ?";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(", ", $setClause) . " WHERE {$this->primaryKey} = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $params = array_values($data);
            $params[] = $id;
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                error_log("Update execution failed");
                return false;
            }
            
            $this->clearCache();
            
            return $this->find($id);
        } catch (PDOException $e) {
            error_log("Update error: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Data: " . print_r($data, true));
            throw new Exception("Database update failed: " . $e->getMessage());
        }
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
            $result = $stmt->execute([$id]);
            $this->clearCache();
            
            return $result;
        } catch (PDOException $e) {
            error_log("Delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Count records
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                $whereClause[] = "{$key} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)$result["total"];
        } catch (PDOException $e) {
            error_log("Count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get first record
     */
    public function findFirst($conditions = [], $orderBy = null) {
        $results = $this->findAll($conditions, $orderBy, 1);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Filter data based on fillable fields
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Clear model cache
     */
    protected function clearCache() {
        $this->cache = [];
    }
    
    /**
     * Validate data
     */
    public function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $ruleArray = explode("|", $rule);
            
            foreach ($ruleArray as $singleRule) {
                if ($singleRule === "required" && empty($data[$field])) {
                    $errors[] = ucfirst($field) . " is required";
                }
                
                if (strpos($singleRule, "max:") === 0) {
                    $maxLength = (int)substr($singleRule, 4);
                    if (isset($data[$field]) && strlen($data[$field]) > $maxLength) {
                        $errors[] = ucfirst($field) . " must not exceed {$maxLength} characters";
                    }
                }
            }
        }
        
        return [
            "valid" => empty($errors),
            "errors" => $errors
        ];
    }
    
    /**
     * Begin database transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit database transaction
     */
    public function commit() {
        return $this->db->commit();
    }
    
    /**
     * Rollback database transaction
     */
    public function rollback() {
        return $this->db->rollback();
    }
}
?>