<?php
/**
 * ConstructLink™ PDF to Image Converter
 * Converts PDF pages to A4-sized images for print preview integration
 */

class PdfImageConverter {
    const CACHE_DIR = 'uploads/cache/pdf-images/';
    const DPI = 150; // Print quality DPI
    const A4_WIDTH = 794; // A4 width in pixels at 96 DPI (210mm)
    const A4_HEIGHT = 1123; // A4 height in pixels at 96 DPI (297mm)
    
    private static $conversionMethods = null;
    
    /**
     * Check what PDF conversion methods are available
     * Prioritized for shared hosting compatibility
     */
    public static function getAvailableMethods() {
        if (self::$conversionMethods === null) {
            // Check PHP extensions (best for shared hosting)
            $gmagickAvailable = extension_loaded('gmagick');
            $imagickAvailable = extension_loaded('imagick');
            
            // For imagick, check if it can actually read PDFs
            $imagickPdfSupport = false;
            if ($imagickAvailable) {
                try {
                    $imagick = new Imagick();
                    $formats = $imagick->queryFormats('PDF');
                    $imagick->clear();
                    $imagickPdfSupport = !empty($formats);
                } catch (Exception $e) {
                    error_log("Imagick PDF support check failed: " . $e->getMessage());
                    $imagickPdfSupport = false;
                }
            }
            
            // Check system CLI tools (good for local development)
            $magickCliAvailable = false;
            $imagemagickCliAvailable = false;
            $ghostscriptAvailable = false;
            
            if (function_exists('exec')) {
                // Check for modern ImageMagick (magick command)
                exec('magick -version 2>/dev/null', $output, $returnCode);
                if ($returnCode === 0) {
                    // Verify PDF delegate support
                    exec('magick -list delegate 2>/dev/null | grep -i pdf', $pdfOutput, $pdfReturn);
                    $magickCliAvailable = ($pdfReturn === 0 && !empty($pdfOutput));
                }
                
                // Check for legacy ImageMagick (convert command) - fallback
                if (!$magickCliAvailable) {
                    exec('convert -version 2>/dev/null', $output, $returnCode);
                    if ($returnCode === 0) {
                        exec('convert -list delegate 2>/dev/null | grep -i pdf', $pdfOutput, $pdfReturn);
                        $imagemagickCliAvailable = ($pdfReturn === 0 && !empty($pdfOutput));
                    }
                }
                
                // Check for direct Ghostscript
                exec('gs --version 2>/dev/null', $output, $returnCode);
                $ghostscriptAvailable = ($returnCode === 0);
            }
            
            error_log("PDF Conversion methods availability:");
            error_log("- gmagick extension: " . ($gmagickAvailable ? 'YES' : 'NO'));
            error_log("- imagick extension: " . ($imagickAvailable ? 'YES' : 'NO'));
            error_log("- imagick PDF support: " . ($imagickPdfSupport ? 'YES' : 'NO'));
            error_log("- magick CLI with PDF: " . ($magickCliAvailable ? 'YES' : 'NO'));
            error_log("- convert CLI with PDF: " . ($imagemagickCliAvailable ? 'YES' : 'NO'));
            error_log("- ghostscript CLI: " . ($ghostscriptAvailable ? 'YES' : 'NO'));
            
            self::$conversionMethods = [
                'gmagick' => $gmagickAvailable,                    // Best for shared hosting
                'imagick' => $imagickPdfSupport,                   // Good for shared hosting
                'magick_cli' => $magickCliAvailable,               // Best for local development  
                'imagemagick_cli' => $imagemagickCliAvailable,     // Fallback for local development
                'ghostscript_direct' => $ghostscriptAvailable      // Direct GS conversion
            ];
        }
        return self::$conversionMethods;
    }
    
    /**
     * Check if any conversion method is available
     */
    public static function isConversionAvailable() {
        $methods = self::getAvailableMethods();
        return array_filter($methods) ? true : false;
    }
    
    /**
     * Convert PDF to A4-sized images
     */
    public static function convertPdfToImages($pdfPath, $cacheKey) {
        if (!file_exists($pdfPath)) {
            return ['success' => false, 'error' => 'PDF file not found'];
        }
        
        // Check cache first
        $cachedImages = self::getCachedImages($cacheKey);
        if ($cachedImages['success']) {
            return $cachedImages;
        }
        
        // Create cache directory
        $cacheDir = APP_ROOT . '/' . self::CACHE_DIR . $cacheKey . '/';
        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0755, true)) {
                return ['success' => false, 'error' => 'Failed to create cache directory'];
            }
        }
        
        $methods = self::getAvailableMethods();
        error_log("PDF conversion methods available: " . json_encode($methods));
        
        // Try methods in order of preference: PHP extensions first, then CLI tools
        
        // 1. Try GMagick extension first (often better PDF support on shared hosting)
        if ($methods['gmagick']) {
            error_log("PDF conversion attempting GMagick extension");
            $result = self::convertWithGMagick($pdfPath, $cacheDir);
            if ($result['success']) return $result;
            error_log("GMagick failed, trying next method");
        }
        
        // 2. Try PHP Imagick extension (if PDF support is available)
        if ($methods['imagick']) {
            error_log("PDF conversion attempting Imagick extension");
            $result = self::convertWithImagick($pdfPath, $cacheDir);
            if ($result['success']) return $result;
            error_log("Imagick failed, trying next method");
        }
        
        // 3. Try modern ImageMagick CLI (magick command)
        if ($methods['magick_cli']) {
            error_log("PDF conversion attempting modern ImageMagick CLI");
            $result = self::convertWithMagickCli($pdfPath, $cacheDir);
            if ($result['success']) return $result;
            error_log("Magick CLI failed, trying next method");
        }
        
        // 4. Try legacy ImageMagick CLI (convert command)
        if ($methods['imagemagick_cli']) {
            error_log("PDF conversion attempting legacy ImageMagick CLI");
            $result = self::convertWithImageMagickCli($pdfPath, $cacheDir);
            if ($result['success']) return $result;
            error_log("ImageMagick CLI failed, trying next method");
        }
        
        // 5. Try direct Ghostscript conversion
        if ($methods['ghostscript_direct']) {
            error_log("PDF conversion attempting direct Ghostscript");
            $result = self::convertWithGhostscript($pdfPath, $cacheDir);
            if ($result['success']) return $result;
            error_log("Ghostscript failed");
        }
        
        // All methods failed
        error_log("PDF conversion failed: All available methods failed");
        return ['success' => false, 'error' => 'PDF conversion failed: No compatible method succeeded'];
    }
    
    /**
     * Convert using PHP GMagick extension
     */
    private static function convertWithGMagick($pdfPath, $cacheDir) {
        error_log("GMagick: Starting conversion for " . $pdfPath);
        try {
            // Check if file exists and is readable
            if (!file_exists($pdfPath)) {
                error_log("GMagick: PDF file does not exist: " . $pdfPath);
                return ['success' => false, 'error' => 'PDF file not found'];
            }
            
            if (!is_readable($pdfPath)) {
                error_log("GMagick: PDF file is not readable: " . $pdfPath);
                return ['success' => false, 'error' => 'PDF file not readable'];
            }
            
            error_log("GMagick: File exists and is readable, attempting to read PDF");
            
            $gmagick = new Gmagick();
            $gmagick->setImageResolution(self::DPI, self::DPI);
            $gmagick->readImage($pdfPath . '[0]'); // Read first page to test
            
            error_log("GMagick: Successfully read first page, processing all pages");
            
            // If we get here, PDF reading worked, now process all pages  
            $gmagick->clear();
            
            $images = [];
            
            // GMagick approach - process each page separately
            $pageIndex = 0;
            $hasMorePages = true;
            
            while ($hasMorePages) {
                try {
                    error_log("GMagick: Processing page " . ($pageIndex + 1));
                    
                    // Create new instance for each page
                    $pageGmagick = new Gmagick();
                    $pageGmagick->setImageResolution(self::DPI, self::DPI);
                    $pageGmagick->readImage($pdfPath . '[' . $pageIndex . ']');
                    
                    $pageGmagick->setImageFormat('png');
                    $pageGmagick->setImageCompressionQuality(90);
                    
                    // Scale to A4 size
                    $pageGmagick->scaleImage(self::A4_WIDTH, self::A4_HEIGHT, true);
                    
                    $filename = 'page_' . ($pageIndex + 1) . '.png';
                    $filepath = $cacheDir . $filename;
                    
                    if ($pageGmagick->writeImage($filepath)) {
                        $cacheKey = basename($cacheDir);
                        $images[] = [
                            'path' => $filepath,
                            'url' => '/' . self::CACHE_DIR . $cacheKey . '/' . $filename,
                            'page' => $pageIndex + 1
                        ];
                        error_log("GMagick: Successfully created " . $filename . " at " . $filepath);
                    } else {
                        error_log("GMagick: Failed to write " . $filename);
                    }
                    
                    $pageGmagick->clear();
                    $pageIndex++;
                    
                } catch (Exception $e) {
                    error_log("GMagick: No more pages or error at page " . ($pageIndex + 1) . ": " . $e->getMessage());
                    $hasMorePages = false;
                }
            }
            
            // Ensure we have at least one page
            if (empty($images)) {
                error_log("GMagick: No pages were successfully converted");
                return ['success' => false, 'error' => 'GMagick conversion failed: No pages converted'];
            }
            
            return [
                'success' => true,
                'images' => $images,
                'pages' => count($images),
                'method' => 'gmagick'
            ];
            
        } catch (Exception $e) {
            error_log("PDF conversion error (GMagick): " . $e->getMessage());
            
            // If GMagick fails, we'll let the main method try other conversion methods
            // Don't return false immediately, let it continue to try other methods
            return ['success' => false, 'error' => 'GMagick conversion failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Convert using PHP Imagick extension
     */
    private static function convertWithImagick($pdfPath, $cacheDir) {
        try {
            $imagick = new Imagick();
            $imagick->setResolution(self::DPI, self::DPI);
            $imagick->readImage($pdfPath);
            
            $pageCount = $imagick->getNumberImages();
            $images = [];
            
            $imagick = $imagick->coalesceImages();
            
            foreach ($imagick as $pageIndex => $page) {
                $page->setImageFormat('png');
                $page->setImageCompressionQuality(90);
                
                // Scale to A4 size
                $page->scaleImage(self::A4_WIDTH, self::A4_HEIGHT, true);
                
                $filename = 'page_' . ($pageIndex + 1) . '.png';
                $filepath = $cacheDir . $filename;
                
                if ($page->writeImage($filepath)) {
                    $images[] = [
                        'path' => $filepath,
                        'url' => '/' . self::CACHE_DIR . basename($cacheDir) . '/' . $filename,
                        'page' => $pageIndex + 1
                    ];
                }
            }
            
            $imagick->clear();
            
            return [
                'success' => true,
                'images' => $images,
                'pages' => count($images),
                'method' => 'imagick'
            ];
            
        } catch (Exception $e) {
            error_log("PDF conversion error (Imagick): " . $e->getMessage());
            return ['success' => false, 'error' => 'Imagick conversion failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Convert using modern ImageMagick CLI (magick command)
     */
    private static function convertWithMagickCli($pdfPath, $cacheDir) {
        try {
            error_log("MagickCLI: Starting conversion for " . $pdfPath);
            
            // Convert all pages at once using modern magick command
            $outputPattern = $cacheDir . 'page_%d.png';
            $command = sprintf(
                'magick -density %d "%s" -quality 90 -resize %dx%d "%s" 2>&1',
                self::DPI,
                escapeshellarg($pdfPath),
                self::A4_WIDTH,
                self::A4_HEIGHT,
                escapeshellarg($outputPattern)
            );
            
            error_log("MagickCLI: Executing command: " . $command);
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                error_log("MagickCLI: Command failed with return code $returnCode: " . implode("\n", $output));
                return ['success' => false, 'error' => 'Magick CLI conversion failed: ' . implode(' ', $output)];
            }
            
            // Find generated images (ImageMagick uses 0-based naming: page_0.png, page_1.png, etc)
            $images = [];
            $pageIndex = 0;
            while (true) {
                $filename = 'page_' . $pageIndex . '.png';
                $filepath = $cacheDir . $filename;
                
                if (!file_exists($filepath)) {
                    break; // No more pages
                }
                
                // Rename to 1-based naming for consistency
                $newFilename = 'page_' . ($pageIndex + 1) . '.png';
                $newFilepath = $cacheDir . $newFilename;
                rename($filepath, $newFilepath);
                
                $cacheKey = basename($cacheDir);
                $images[] = [
                    'path' => $newFilepath,
                    'url' => '/' . self::CACHE_DIR . $cacheKey . '/' . $newFilename,
                    'page' => $pageIndex + 1
                ];
                
                $pageIndex++;
            }
            
            if (empty($images)) {
                error_log("MagickCLI: No images were generated");
                return ['success' => false, 'error' => 'Magick CLI conversion failed: No images generated'];
            }
            
            error_log("MagickCLI: Successfully converted " . count($images) . " pages");
            return [
                'success' => true,
                'images' => $images,
                'pages' => count($images),
                'method' => 'magick_cli'
            ];
            
        } catch (Exception $e) {
            error_log("MagickCLI: Exception occurred: " . $e->getMessage());
            return ['success' => false, 'error' => 'Magick CLI conversion failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Convert using legacy ImageMagick CLI (convert command)
     */
    private static function convertWithImageMagickCli($pdfPath, $cacheDir) {
        try {
            error_log("ImageMagickCLI: Starting conversion for " . $pdfPath);
            
            // Convert all pages at once using legacy convert command
            $outputPattern = $cacheDir . 'page_%d.png';
            $command = sprintf(
                'convert -density %d "%s" -quality 90 -resize %dx%d "%s" 2>&1',
                self::DPI,
                escapeshellarg($pdfPath),
                self::A4_WIDTH,
                self::A4_HEIGHT,
                escapeshellarg($outputPattern)
            );
            
            error_log("ImageMagickCLI: Executing command: " . $command);
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                error_log("ImageMagickCLI: Command failed with return code $returnCode: " . implode("\n", $output));
                return ['success' => false, 'error' => 'ImageMagick CLI conversion failed: ' . implode(' ', $output)];
            }
            
            // Find generated images (ImageMagick uses 0-based naming: page_0.png, page_1.png, etc)
            $images = [];
            $pageIndex = 0;
            while (true) {
                $filename = 'page_' . $pageIndex . '.png';
                $filepath = $cacheDir . $filename;
                
                if (!file_exists($filepath)) {
                    break; // No more pages
                }
                
                // Rename to 1-based naming for consistency
                $newFilename = 'page_' . ($pageIndex + 1) . '.png';
                $newFilepath = $cacheDir . $newFilename;
                rename($filepath, $newFilepath);
                
                $cacheKey = basename($cacheDir);
                $images[] = [
                    'path' => $newFilepath,
                    'url' => '/' . self::CACHE_DIR . $cacheKey . '/' . $newFilename,
                    'page' => $pageIndex + 1
                ];
                
                $pageIndex++;
            }
            
            if (empty($images)) {
                error_log("ImageMagickCLI: No images were generated");
                return ['success' => false, 'error' => 'ImageMagick CLI conversion failed: No images generated'];
            }
            
            error_log("ImageMagickCLI: Successfully converted " . count($images) . " pages");
            return [
                'success' => true,
                'images' => $images,
                'pages' => count($images),
                'method' => 'imagemagick_cli'
            ];
            
        } catch (Exception $e) {
            error_log("PDF conversion error (ImageMagick): " . $e->getMessage());
            return ['success' => false, 'error' => 'ImageMagick conversion failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Convert using GhostScript
     */
    private static function convertWithGhostScript($pdfPath, $cacheDir) {
        try {
            $outputPattern = $cacheDir . 'page_%d.png';
            $command = sprintf(
                'gs -dNOPAUSE -dBATCH -sDEVICE=png16m -r%d -sOutputFile=%s %s 2>&1',
                self::DPI,
                escapeshellarg($outputPattern),
                escapeshellarg($pdfPath)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                error_log("GhostScript conversion failed: " . implode("\n", $output));
                return ['success' => false, 'error' => 'GhostScript conversion failed: ' . implode(' ', $output)];
            }
            
            // Find and resize generated images
            $images = [];
            $pageNum = 1;
            while (true) {
                $filename = 'page_' . $pageNum . '.png';
                $filepath = $cacheDir . $filename;
                
                if (file_exists($filepath)) {
                    // Resize to A4 using GD
                    self::resizeImageToA4($filepath);
                    
                    $images[] = [
                        'path' => $filepath,
                        'url' => '/' . self::CACHE_DIR . basename($cacheDir) . '/' . $filename,
                        'page' => $pageNum
                    ];
                    $pageNum++;
                } else {
                    break;
                }
            }
            
            if (empty($images)) {
                return ['success' => false, 'error' => 'No images were generated by GhostScript'];
            }
            
            return [
                'success' => true,
                'images' => $images,
                'pages' => count($images),
                'method' => 'ghostscript_direct'
            ];
            
        } catch (Exception $e) {
            error_log("PDF conversion error (GhostScript): " . $e->getMessage());
            return ['success' => false, 'error' => 'GhostScript conversion failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get cached images if they exist
     */
    public static function getCachedImages($cacheKey) {
        $cacheDir = APP_ROOT . '/' . self::CACHE_DIR . $cacheKey . '/';
        
        if (!is_dir($cacheDir)) {
            return ['success' => false, 'error' => 'Cache not found'];
        }
        
        $images = [];
        $files = glob($cacheDir . 'page_*.png');
        
        if (empty($files)) {
            return ['success' => false, 'error' => 'No cached images found'];
        }
        
        // Sort by page number
        usort($files, function($a, $b) {
            $pageA = (int) preg_replace('/.*page_(\d+)\.png/', '$1', $a);
            $pageB = (int) preg_replace('/.*page_(\d+)\.png/', '$1', $b);
            return $pageA - $pageB;
        });
        
        foreach ($files as $filepath) {
            $filename = basename($filepath);
            $pageNum = (int) preg_replace('/page_(\d+)\.png/', '$1', $filename);
            
            $images[] = [
                'path' => $filepath,
                'url' => '/' . self::CACHE_DIR . $cacheKey . '/' . $filename,
                'page' => $pageNum
            ];
        }
        
        return [
            'success' => true,
            'images' => $images,
            'pages' => count($images),
            'method' => 'cached'
        ];
    }
    
    /**
     * Generate cache key for a PDF file
     */
    public static function generateCacheKey($filename, $fileModTime = null) {
        $fileModTime = $fileModTime ?: filemtime(ProcurementFileUploader::getUploadPath() . $filename);
        return md5($filename . '_' . $fileModTime);
    }
    
    /**
     * Clean old cache files
     */
    public static function cleanCache($maxAge = 2592000) { // 30 days default
        $cacheDir = APP_ROOT . '/' . self::CACHE_DIR;
        
        if (!is_dir($cacheDir)) {
            return;
        }
        
        $directories = glob($cacheDir . '*', GLOB_ONLYDIR);
        $cutoffTime = time() - $maxAge;
        
        foreach ($directories as $dir) {
            if (filemtime($dir) < $cutoffTime) {
                self::deleteCacheDir($dir);
            }
        }
    }
    
    /**
     * Check if GhostScript is available
     */
    private static function isGhostscriptAvailable() {
        exec('gs --version 2>/dev/null', $output, $returnCode);
        return $returnCode === 0;
    }
    
    /**
     * Check if ImageMagick is available
     */
    private static function isImageMagickAvailable() {
        exec('convert -version 2>/dev/null', $output, $returnCode);
        return $returnCode === 0;
    }
    
    
    /**
     * Resize image to A4 dimensions using GD
     */
    private static function resizeImageToA4($filepath) {
        if (!function_exists('imagecreatefrompng')) {
            return false;
        }
        
        $source = imagecreatefrompng($filepath);
        if (!$source) {
            return false;
        }
        
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        
        // Create A4 canvas
        $a4Canvas = imagecreatetruecolor(self::A4_WIDTH, self::A4_HEIGHT);
        $white = imagecolorallocate($a4Canvas, 255, 255, 255);
        imagefill($a4Canvas, 0, 0, $white);
        
        // Calculate scaling to fit A4
        $scaleX = self::A4_WIDTH / $sourceWidth;
        $scaleY = self::A4_HEIGHT / $sourceHeight;
        $scale = min($scaleX, $scaleY);
        
        $newWidth = (int)($sourceWidth * $scale);
        $newHeight = (int)($sourceHeight * $scale);
        
        // Center the image
        $offsetX = (int)((self::A4_WIDTH - $newWidth) / 2);
        $offsetY = (int)((self::A4_HEIGHT - $newHeight) / 2);
        
        // Resample and copy
        imagecopyresampled(
            $a4Canvas, $source,
            $offsetX, $offsetY, 0, 0,
            $newWidth, $newHeight, $sourceWidth, $sourceHeight
        );
        
        // Save
        $result = imagepng($a4Canvas, $filepath);
        
        imagedestroy($source);
        imagedestroy($a4Canvas);
        
        return $result;
    }
    
    /**
     * Delete cache directory recursively
     */
    private static function deleteCacheDir($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::deleteCacheDir($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
}
?>