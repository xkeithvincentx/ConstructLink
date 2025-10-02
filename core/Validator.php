<?php
/**
 * ConstructLink™ Validation System
 * Input validation and sanitization utilities
 */

class Validator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data = []) {
        $this->data = $data;
        $this->errors = [];
    }
    
    /**
     * Validate required field
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' is required.';
        }
        return $this;
    }
    
    /**
     * Validate minimum length
     */
    public function minLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field][] = $message ?? ucfirst($field) . " must be at least {$length} characters long.";
        }
        return $this;
    }
    
    /**
     * Validate maximum length
     */
    public function maxLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field][] = $message ?? ucfirst($field) . " must not exceed {$length} characters.";
        }
        return $this;
    }
    
    /**
     * Validate email format
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?? 'Please enter a valid email address.';
        }
        return $this;
    }
    
    /**
     * Validate numeric value
     */
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' must be a valid number.';
        }
        return $this;
    }
    
    /**
     * Validate integer value
     */
    public function integer($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' must be a valid integer.';
        }
        return $this;
    }
    
    /**
     * Validate decimal value
     */
    public function decimal($field, $precision = 2, $message = null) {
        if (isset($this->data[$field])) {
            $pattern = '/^\d+(\.\d{1,' . $precision . '})?$/';
            if (!preg_match($pattern, $this->data[$field])) {
                $this->errors[$field][] = $message ?? ucfirst($field) . " must be a valid decimal with up to {$precision} decimal places.";
            }
        }
        return $this;
    }
    
    /**
     * Validate date format
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        if (isset($this->data[$field])) {
            $date = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field][] = $message ?? ucfirst($field) . ' must be a valid date in format ' . $format . '.';
            }
        }
        return $this;
    }
    
    /**
     * Validate date range
     */
    public function dateRange($startField, $endField, $message = null) {
        if (isset($this->data[$startField]) && isset($this->data[$endField])) {
            $startDate = strtotime($this->data[$startField]);
            $endDate = strtotime($this->data[$endField]);
            
            if ($startDate && $endDate && $startDate > $endDate) {
                $this->errors[$endField][] = $message ?? 'End date must be after start date.';
            }
        }
        return $this;
    }
    
    /**
     * Validate value is in array
     */
    public function in($field, $array, $message = null) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $array)) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' must be one of: ' . implode(', ', $array) . '.';
        }
        return $this;
    }
    
    /**
     * Validate unique value in database
     */
    public function unique($field, $table, $column = null, $excludeId = null, $message = null) {
        if (!isset($this->data[$field])) {
            return $this;
        }
        
        $column = $column ?? $field;
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
        $params = [$this->data[$field]];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->fetchColumn() > 0) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' already exists.';
        }
        
        return $this;
    }
    
    /**
     * Validate exists in database
     */
    public function exists($field, $table, $column = null, $message = null) {
        if (!isset($this->data[$field])) {
            return $this;
        }
        
        $column = $column ?? 'id';
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$this->data[$field]]);
        
        if ($stmt->fetchColumn() == 0) {
            $this->errors[$field][] = $message ?? ucfirst($field) . ' does not exist.';
        }
        
        return $this;
    }
    
    /**
     * Validate file upload
     */
    public function file($field, $allowedTypes = [], $maxSize = null, $message = null) {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return $this;
        }
        
        $file = $_FILES[$field];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[$field][] = $this->getUploadErrorMessage($file['error']);
            return $this;
        }
        
        // Check file size
        $maxSize = $maxSize ?? UPLOAD_MAX_SIZE;
        if ($file['size'] > $maxSize) {
            $this->errors[$field][] = 'File size must not exceed ' . $this->formatBytes($maxSize) . '.';
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedTypes)) {
                $this->errors[$field][] = 'File type must be one of: ' . implode(', ', $allowedTypes) . '.';
            }
        }
        
        return $this;
    }
    
    /**
     * Validate phone number
     */
    public function phone($field, $message = null) {
        if (isset($this->data[$field])) {
            $phone = preg_replace('/[^0-9+]/', '', $this->data[$field]);
            if (strlen($phone) < 10 || strlen($phone) > 15) {
                $this->errors[$field][] = $message ?? 'Please enter a valid phone number.';
            }
        }
        return $this;
    }
    
    /**
     * Validate URL
     */
    public function url($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = $message ?? 'Please enter a valid URL.';
        }
        return $this;
    }
    
    /**
     * Custom validation rule
     */
    public function custom($field, $callback, $message = null) {
        if (isset($this->data[$field])) {
            $result = call_user_func($callback, $this->data[$field]);
            if (!$result) {
                $this->errors[$field][] = $message ?? ucfirst($field) . ' is invalid.';
            }
        }
        return $this;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !$this->passes();
    }
    
    /**
     * Get all errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get errors for specific field
     */
    public function getError($field) {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Get first error for field
     */
    public function getFirstError($field) {
        $errors = $this->getError($field);
        return !empty($errors) ? $errors[0] : null;
    }
    
    /**
     * Get all errors as flat array
     */
    public function getAllErrors() {
        $allErrors = [];
        foreach ($this->errors as $fieldErrors) {
            $allErrors = array_merge($allErrors, $fieldErrors);
        }
        return $allErrors;
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize for database
     */
    public static function sanitizeForDB($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeForDB'], $data);
        }
        
        return trim($data);
    }
    
    /**
     * Validate asset reference format
     */
    public function assetRef($field, $message = null) {
        if (isset($this->data[$field])) {
            $pattern = '/^' . ASSET_REF_PREFIX . '\d{4}[A-Z0-9]{' . (ASSET_REF_LENGTH - 6) . '}$/';
            if (!preg_match($pattern, $this->data[$field])) {
                $this->errors[$field][] = $message ?? 'Asset reference must follow the format: ' . ASSET_REF_PREFIX . 'YYYY followed by alphanumeric characters.';
            }
        }
        return $this;
    }
    
    /**
     * Validate password strength
     */
    public function password($field, $message = null) {
        if (isset($this->data[$field])) {
            $password = $this->data[$field];
            $errors = [];
            
            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                $errors[] = "at least " . PASSWORD_MIN_LENGTH . " characters long";
            }
            
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = "contain at least one uppercase letter";
            }
            
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = "contain at least one lowercase letter";
            }
            
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = "contain at least one number";
            }
            
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = "contain at least one special character";
            }
            
            if (!empty($errors)) {
                $this->errors[$field][] = $message ?? 'Password must be ' . implode(', ', $errors) . '.';
            }
        }
        return $this;
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File is too large.';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension.';
            default:
                return 'Unknown upload error.';
        }
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Static validation method for quick validation
     */
    public static function validate($data, $rules) {
        $validator = new self($data);
        
        foreach ($rules as $field => $fieldRules) {
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }
            
            foreach ($fieldRules as $rule) {
                if (strpos($rule, ':') !== false) {
                    [$ruleName, $ruleValue] = explode(':', $rule, 2);
                    $ruleParams = explode(',', $ruleValue);
                } else {
                    $ruleName = $rule;
                    $ruleParams = [];
                }
                
                switch ($ruleName) {
                    case 'required':
                        $validator->required($field);
                        break;
                    case 'email':
                        $validator->email($field);
                        break;
                    case 'numeric':
                        $validator->numeric($field);
                        break;
                    case 'integer':
                        $validator->integer($field);
                        break;
                    case 'min':
                        $validator->minLength($field, (int)$ruleParams[0]);
                        break;
                    case 'max':
                        $validator->maxLength($field, (int)$ruleParams[0]);
                        break;
                    case 'in':
                        $validator->in($field, $ruleParams);
                        break;
                    case 'unique':
                        $table = $ruleParams[0];
                        $column = $ruleParams[1] ?? null;
                        $excludeId = $ruleParams[2] ?? null;
                        $validator->unique($field, $table, $column, $excludeId);
                        break;
                    case 'exists':
                        $table = $ruleParams[0];
                        $column = $ruleParams[1] ?? null;
                        $validator->exists($field, $table, $column);
                        break;
                }
            }
        }
        
        return $validator;
    }
}

?>
