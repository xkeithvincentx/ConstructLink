<?php
/**
 * ConstructLink™ Procurement File Upload Handler
 * Handles file uploads for procurement orders including quotes, receipts, and evidence
 */

class ProcurementFileUploader {
    const UPLOAD_DIR = 'uploads/procurement/';
    const ALLOWED_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    const MAX_SIZE = 10485760; // 10MB
    
    /**
     * Handle multiple procurement file uploads
     */
    public static function handleProcurementFiles($files, $existingFiles = []) {
        $uploadedFiles = [];
        $errors = [];
        
        // Handle all three file types
        $fileFields = ['quote_file', 'purchase_receipt_file', 'supporting_evidence_file'];
        
        foreach ($fileFields as $field) {
            if (isset($files[$field]) && $files[$field]['error'] === UPLOAD_ERR_OK) {
                $result = self::processFile($files[$field], $field);
                if ($result['success']) {
                    // Delete old file if replacing
                    if (!empty($existingFiles[$field])) {
                        self::deleteFile($existingFiles[$field]);
                    }
                    $uploadedFiles[$field] = $result['filename'];
                } else {
                    $errors[] = "Error uploading " . str_replace('_', ' ', $field) . ": " . $result['error'];
                }
            } else {
                // Keep existing file if no new upload
                if (!empty($existingFiles[$field])) {
                    $uploadedFiles[$field] = $existingFiles[$field];
                }
                
                // Handle specific upload errors
                if (isset($files[$field]) && $files[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                    $errorMsg = self::getUploadErrorMessage($files[$field]['error']);
                    $errors[] = "Error with " . str_replace('_', ' ', $field) . ": " . $errorMsg;
                }
            }
        }
        
        return ['files' => $uploadedFiles, 'errors' => $errors];
    }
    
    /**
     * Process a single file upload
     */
    private static function processFile($file, $type) {
        try {
            // Validate file size
            if ($file['size'] > self::MAX_SIZE) {
                return [
                    'success' => false, 
                    'error' => 'File size exceeds ' . (self::MAX_SIZE / 1024 / 1024) . 'MB limit'
                ];
            }
            
            // Get file extension
            $pathInfo = pathinfo($file['name']);
            $extension = strtolower($pathInfo['extension'] ?? '');
            
            // Validate file type
            if (!in_array($extension, self::ALLOWED_TYPES)) {
                return [
                    'success' => false,
                    'error' => 'File type not allowed. Allowed types: ' . implode(', ', self::ALLOWED_TYPES)
                ];
            }
            
            // Generate unique filename
            $filename = self::generateUniqueFilename($type, $extension);
            
            // Create upload directory if it doesn't exist
            $uploadPath = APP_ROOT . '/' . self::UPLOAD_DIR;
            if (!is_dir($uploadPath)) {
                if (!mkdir($uploadPath, 0755, true)) {
                    return [
                        'success' => false,
                        'error' => 'Failed to create upload directory'
                    ];
                }
            }
            
            // Move uploaded file
            $fullPath = $uploadPath . $filename;
            if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                // Verify file was uploaded correctly
                if (!file_exists($fullPath)) {
                    return [
                        'success' => false,
                        'error' => 'File upload verification failed'
                    ];
                }
                
                return [
                    'success' => true,
                    'filename' => $filename,
                    'path' => '/' . self::UPLOAD_DIR . $filename,
                    'size' => $file['size'],
                    'type' => $file['type']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to move uploaded file'
                ];
            }
            
        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'File upload failed due to server error'
            ];
        }
    }
    
    /**
     * Generate unique filename
     */
    private static function generateUniqueFilename($type, $extension) {
        $timestamp = time();
        $random = substr(md5(uniqid(rand(), true)), 0, 8);
        return $type . '_' . $timestamp . '_' . $random . '.' . $extension;
    }
    
    /**
     * Delete a file
     */
    private static function deleteFile($filename) {
        if (empty($filename)) return false;
        
        $filePath = APP_ROOT . '/' . self::UPLOAD_DIR . $filename;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
    
    /**
     * Get human-readable error message for upload errors
     */
    private static function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File size exceeds server limit';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File size exceeds form limit';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Validate file type by content (additional security)
     */
    private static function validateFileContent($filePath, $expectedExtension) {
        if (!file_exists($filePath)) return false;
        
        // Get MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        // Define allowed MIME types
        $allowedMimeTypes = [
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        ];
        
        if (!isset($allowedMimeTypes[$expectedExtension])) {
            return false;
        }
        
        return in_array($mimeType, $allowedMimeTypes[$expectedExtension]);
    }
    
    /**
     * Get upload directory path
     */
    public static function getUploadPath() {
        return APP_ROOT . '/' . self::UPLOAD_DIR;
    }
    
    /**
     * Get public URL for uploaded file
     */
    public static function getFileUrl($filename) {
        if (empty($filename)) return null;
        return '/' . self::UPLOAD_DIR . $filename;
    }
    
    /**
     * Check if file exists
     */
    public static function fileExists($filename) {
        if (empty($filename)) return false;
        return file_exists(APP_ROOT . '/' . self::UPLOAD_DIR . $filename);
    }
    
    /**
     * Get file size in human readable format
     */
    public static function getFormattedFileSize($filename) {
        if (empty($filename)) return null;
        
        $filePath = APP_ROOT . '/' . self::UPLOAD_DIR . $filename;
        if (!file_exists($filePath)) return null;
        
        $size = filesize($filePath);
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size >= 1024 && $i < 3; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
    
    /**
     * Get file upload date from filename timestamp
     */
    public static function getFileUploadDate($filename) {
        if (empty($filename)) return null;
        
        // Extract timestamp from filename format: type_timestamp_random.ext
        $parts = explode('_', pathinfo($filename, PATHINFO_FILENAME));
        if (count($parts) >= 2 && is_numeric($parts[1])) {
            return date('Y-m-d H:i:s', $parts[1]);
        }
        
        // Fallback to file modification time
        $filePath = APP_ROOT . '/' . self::UPLOAD_DIR . $filename;
        if (file_exists($filePath)) {
            return date('Y-m-d H:i:s', filemtime($filePath));
        }
        
        return null;
    }
    
    /**
     * Get file type icon based on extension
     */
    public static function getFileTypeIcon($filename) {
        if (empty($filename)) return 'bi-file-earmark';
        
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $iconMap = [
            'pdf' => 'bi-file-earmark-pdf',
            'doc' => 'bi-file-earmark-word',
            'docx' => 'bi-file-earmark-word',
            'jpg' => 'bi-file-earmark-image',
            'jpeg' => 'bi-file-earmark-image',
            'png' => 'bi-file-earmark-image',
            'gif' => 'bi-file-earmark-image',
        ];
        
        return $iconMap[$extension] ?? 'bi-file-earmark';
    }
    
    /**
     * Get comprehensive file metadata
     */
    public static function getFileMetadata($filename) {
        if (empty($filename)) return null;
        
        $filePath = APP_ROOT . '/' . self::UPLOAD_DIR . $filename;
        if (!file_exists($filePath)) return null;
        
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $size = filesize($filePath);
        
        return [
            'filename' => $filename,
            'extension' => $extension,
            'size' => $size,
            'formatted_size' => self::getFormattedFileSize($filename),
            'upload_date' => self::getFileUploadDate($filename),
            'icon' => self::getFileTypeIcon($filename),
            'url' => self::getFileUrl($filename),
            'exists' => true
        ];
    }
}
?>