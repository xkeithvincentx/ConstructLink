<?php
/**
 * ConstructLink™ QR Tag Generator
 * Generates printable QR tag templates for physical asset tagging
 */

// Try to load vendor autoload if it exists
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

// Try to load TCPDF from various possible locations
$tcpdfPaths = [
    APP_ROOT . '/core/TCPDF/tcpdf.php',
    APP_ROOT . '/vendor/tcpdf/tcpdf.php',
    APP_ROOT . '/libraries/tcpdf/tcpdf.php'
];

$tcpdfLoaded = false;
foreach ($tcpdfPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $tcpdfLoaded = true;
        break;
    }
}

// Load FPDF as fallback if TCPDF is not available
$fpdfLoaded = false;
if (file_exists(APP_ROOT . '/core/FPDF.php')) {
    require_once APP_ROOT . '/core/FPDF.php';
    $fpdfLoaded = true;
}

class QRTagGenerator {
    private $pdf;
    private $tagTemplates;
    private $tcpdfAvailable;
    private $fpdfAvailable;
    private $useTCPDF;
    
    public function __construct() {
        global $tcpdfLoaded, $fpdfLoaded;
        $this->tcpdfAvailable = $tcpdfLoaded ?? false;
        $this->fpdfAvailable = $fpdfLoaded ?? false;
        $this->useTCPDF = $this->tcpdfAvailable; // Prefer TCPDF if available
        
        // Only log if there are issues - remove excessive debug logging
        if (!$this->tcpdfAvailable && !$this->fpdfAvailable) {
            error_log("QRTagGenerator: No PDF libraries available");
        }
        
        $this->initializeTemplates();
    }
    
    /**
     * Initialize tag template configurations
     */
    private function initializeTemplates() {
        $this->tagTemplates = [
            'small' => [
                'name' => 'Small Tools',
                'width' => 25.4,  // 1 inch in mm
                'height' => 38.1,  // 1.5 inches in mm
                'font_size' => 6,
                'qr_size' => 15,
                'description' => 'For hand tools, small parts'
            ],
            'medium' => [
                'name' => 'Power Tools',
                'width' => 38.1,  // 1.5 inches in mm
                'height' => 50.8,  // 2 inches in mm
                'font_size' => 8,
                'qr_size' => 20,
                'description' => 'For drills, grinders, power tools'
            ],
            'large' => [
                'name' => 'Equipment',
                'width' => 50.8,  // 2 inches in mm
                'height' => 76.2,  // 3 inches in mm
                'font_size' => 10,
                'qr_size' => 30,
                'description' => 'For generators, compressors, large equipment'
            ],
            'consumable' => [
                'name' => 'Materials/Consumables',
                'width' => 63.5,  // 2.5 inches in mm
                'height' => 38.1,  // 1.5 inches in mm
                'font_size' => 9,
                'qr_size' => 25,
                'description' => 'For material containers, consumable items'
            ]
        ];
    }
    
    /**
     * Generate printable QR tags for multiple assets
     */
    public function generateBatchTags($assets, $templateType = 'medium', $tagsPerPage = 12) {
        if (!$this->tcpdfAvailable && !$this->fpdfAvailable) {
            throw new Exception('PDF library not available. Cannot generate PDF tags.');
        }
        
        // Validate input
        if (empty($assets) || !is_array($assets)) {
            throw new Exception('No assets provided for tag generation');
        }
        
        // Validate each asset has required fields
        foreach ($assets as $asset) {
            if (empty($asset['ref']) || empty($asset['name'])) {
                throw new Exception('Asset missing required fields (ref or name)');
            }
        }
        
        $template = $this->tagTemplates[$templateType] ?? $this->tagTemplates['medium'];
        
        // Initialize PDF library
        try {
            if ($this->useTCPDF && $this->tcpdfAvailable) {
                $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
                $this->pdf->SetCreator('ConstructLink™');
                $this->pdf->SetTitle('Asset QR Tags - ' . date('Y-m-d'));
                $this->pdf->SetMargins(10, 10, 10);
                $this->pdf->SetAutoPageBreak(true, 10);
            } else if ($this->fpdfAvailable) {
                $this->pdf = new FPDF('P', 'mm', 'A4');
                $this->pdf->SetTitle('Asset QR Tags - ' . date('Y-m-d'));
                $this->pdf->SetMargins(10, 10, 10);
                $this->pdf->SetAutoPageBreak(true, 10);
            } else {
                throw new Exception('No PDF library available');
            }
        } catch (Exception $e) {
            error_log("PDF initialization error: " . $e->getMessage());
            throw new Exception('Failed to initialize PDF library: ' . $e->getMessage());
        }
        
        // Calculate tags per row based on page width
        $pageWidth = 210 - 20; // A4 width minus margins
        $tagsPerRow = floor($pageWidth / ($template['width'] + 5)); // 5mm spacing
        $rowsPerPage = floor($tagsPerPage / $tagsPerRow);
        
        $currentPage = 0;
        $tagCount = 0;
        
        foreach ($assets as $asset) {
            // Start new page if needed
            if ($tagCount % $tagsPerPage === 0) {
                $this->pdf->AddPage();
                $currentPage++;
                
                // Add page header
                $headerFont = $this->useTCPDF ? 'helvetica' : 'Arial';
                $this->pdf->SetFont($headerFont, 'B', 12);
                $this->pdf->Cell(0, 10, 'ConstructLink Asset QR Tags - Page ' . $currentPage, 0, 1, 'C');
                $this->pdf->SetFont($headerFont, '', 8);
                $this->pdf->Cell(0, 5, 'Template: ' . $template['name'] . ' | Generated: ' . date('Y-m-d H:i'), 0, 1, 'C');
                $this->pdf->Ln(5);
            }
            
            // Calculate position
            $row = floor(($tagCount % $tagsPerPage) / $tagsPerRow);
            $col = ($tagCount % $tagsPerPage) % $tagsPerRow;
            
            $x = 10 + ($col * ($template['width'] + 5));
            $y = 30 + ($row * ($template['height'] + 5));
            
            // Generate individual tag
            $this->generateSingleTag($asset, $template, $x, $y);
            
            $tagCount++;
        }
        
        // Handle different Output method signatures between TCPDF and FPDF
        try {
            $filename = 'asset_qr_tags_' . date('Y-m-d_H-i') . '.pdf';
            
            if ($this->useTCPDF && $this->tcpdfAvailable) {
                // TCPDF: Output(name, destination)
                return $this->pdf->Output($filename, 'S');
            } else {
                // FPDF: Output(destination, name)
                return $this->pdf->Output('S', $filename);
            }
        } catch (Exception $e) {
            error_log("PDF output error: " . $e->getMessage());
            error_log("PDF library type: " . ($this->useTCPDF ? 'TCPDF' : 'FPDF'));
            error_log("PDF object class: " . get_class($this->pdf));
            throw new Exception('Failed to generate PDF output: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate single QR tag
     */
    private function generateSingleTag($asset, $template, $x, $y) {
        // Draw tag border
        $this->pdf->SetDrawColor(0, 0, 0);
        if ($this->useTCPDF) {
            $this->pdf->SetLineWidth(0.2);
        }
        $this->pdf->Rect($x, $y, $template['width'], $template['height']);
        
        // Generate QR code (mock implementation - use real QR library in production)
        $qrData = $this->generateQRCodeData($asset);
        $qrX = $x + ($template['width'] - $template['qr_size']) / 2;
        $qrY = $y + 2;
        
        // Generate visual QR code
        $this->generateVisualQRCode($qrX, $qrY, $template['qr_size'], $qrData);
        
        // Add asset information below QR code
        $textY = $qrY + $template['qr_size'] + 2;
        $textWidth = $template['width'] - 4;
        
        // Asset Reference  
        $font = $this->useTCPDF ? 'helvetica' : 'Arial'; // Use appropriate font for each library
        $fontSize = max(6, $template['font_size']); // Ensure minimum font size for FPDF
        
        $this->pdf->SetFont($font, 'B', $fontSize);
        $this->pdf->SetXY($x + 2, $textY);
        $this->pdf->Cell($textWidth, 3, 'REF: ' . substr($asset['ref'], 0, 12), 0, 0, 'C');
        
        // Asset Name
        $this->pdf->SetFont($font, '', max(5, $fontSize - 1));
        $this->pdf->SetXY($x + 2, $textY + 3);
        $assetName = strlen($asset['name']) > 20 ? substr($asset['name'], 0, 17) . '...' : $asset['name'];
        $this->pdf->Cell($textWidth, 3, $assetName, 0, 0, 'C');
        
        // Additional info based on template size
        if ($template['height'] > 40) {
            // Project info for larger tags
            $this->pdf->SetFont($font, '', max(4, $fontSize - 2));
            $this->pdf->SetXY($x + 2, $textY + 6);
            $projectName = isset($asset['project_name']) ? substr($asset['project_name'], 0, 15) : 'N/A';
            $this->pdf->Cell($textWidth, 3, 'Project: ' . $projectName, 0, 0, 'C');
            
            // Category for largest tags  
            if ($template['height'] > 60) {
                $this->pdf->SetXY($x + 2, $textY + 9);
                $categoryName = isset($asset['category_name']) ? substr($asset['category_name'], 0, 15) : 'N/A';
                $this->pdf->Cell($textWidth, 3, $categoryName, 0, 0, 'C');
            }
        }
        
        // Add cut lines (dashed border outside main border) - TCPDF only
        if ($this->useTCPDF) {
            $this->pdf->SetDrawColor(150, 150, 150);
            $this->pdf->SetLineStyle(['dash' => 2, 'gap' => 1]);
            $this->pdf->Rect($x - 1, $y - 1, $template['width'] + 2, $template['height'] + 2);
            $this->pdf->SetLineStyle(['dash' => 0]); // Reset to solid line
        }
    }
    
    /**
     * Generate QR code data string
     */
    private function generateQRCodeData($asset) {
        // Use existing SecureLink QR generation if available
        if (class_exists('SecureLink')) {
            $secureLink = SecureLink::getInstance();
            $result = $secureLink->generateAssetQR($asset['id'], $asset['ref']);
            return $result['qr_data'] ?? $asset['ref'];
        }
        
        // Fallback: simple asset reference
        return $asset['ref'];
    }
    
    /**
     * Get available tag templates
     */
    public function getAvailableTemplates() {
        return $this->tagTemplates;
    }
    
    /**
     * Generate single asset tag PDF
     */
    public function generateSingleAssetTag($asset, $templateType = 'medium') {
        if (!$this->tcpdfAvailable && !$this->fpdfAvailable) {
            throw new Exception('PDF library not available. Cannot generate PDF tags.');
        }
        return $this->generateBatchTags([$asset], $templateType, 1);
    }
    
    /**
     * Calculate optimal template for asset category
     */
    public function suggestTemplate($asset) {
        if (!isset($asset['category_name'])) {
            return 'medium';
        }
        
        $category = strtolower($asset['category_name']);
        
        if (strpos($category, 'tool') !== false && strpos($category, 'hand') !== false) {
            return 'small';
        } elseif (strpos($category, 'equipment') !== false || strpos($category, 'generator') !== false) {
            return 'large';
        } elseif (strpos($category, 'material') !== false || $asset['is_consumable'] ?? false) {
            return 'consumable';
        }
        
        return 'medium'; // Default for power tools, general items
    }
    
    /**
     * Generate tag preview HTML
     */
    public function generateTagPreview($asset, $templateType = 'medium') {
        $template = $this->tagTemplates[$templateType] ?? $this->tagTemplates['medium'];
        
        $html = '<div class="tag-preview" style="
            border: 1px solid #000; 
            width: ' . ($template['width'] * 2) . 'px; 
            height: ' . ($template['height'] * 2) . 'px; 
            padding: 8px; 
            text-align: center;
            font-family: Arial, sans-serif;
            background: white;
            display: inline-block;
            margin: 10px;
        ">';
        
        // Mock QR code
        $html .= '<div style="
            width: ' . ($template['qr_size'] * 2) . 'px; 
            height: ' . ($template['qr_size'] * 2) . 'px; 
            background: #000; 
            margin: 0 auto 8px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
        ">QR</div>';
        
        // Asset info
        $html .= '<div style="font-size: ' . ($template['font_size'] * 1.5) . 'px; font-weight: bold;">REF: ' . htmlspecialchars($asset['ref']) . '</div>';
        $html .= '<div style="font-size: ' . (($template['font_size'] - 1) * 1.5) . 'px; margin-top: 2px;">' . htmlspecialchars(substr($asset['name'], 0, 20)) . '</div>';
        
        if ($template['height'] > 40) {
            $html .= '<div style="font-size: ' . (($template['font_size'] - 2) * 1.5) . 'px; margin-top: 2px;">Project: ' . htmlspecialchars($asset['project_name'] ?? 'N/A') . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate visual QR code pattern in PDF
     */
    private function generateVisualQRCode($x, $y, $size, $data) {
        // Create a simple pattern that resembles a QR code
        $this->pdf->SetFillColor(255, 255, 255); // White background
        $this->pdf->Rect($x, $y, $size, $size, 'F');
        
        $this->pdf->SetFillColor(0, 0, 0); // Black for QR pattern
        
        // Create a grid pattern based on the data
        $gridSize = 21; // Standard QR code is 21x21 for version 1
        $cellSize = $size / $gridSize;
        
        // Generate pattern based on data hash
        $hash = md5($data);
        $binary = '';
        for ($i = 0; $i < strlen($hash); $i++) {
            $binary .= str_pad(decbin(hexdec($hash[$i])), 4, '0', STR_PAD_LEFT);
        }
        
        // Add finder patterns (corner squares)
        $this->drawFinderPattern($x, $y, $cellSize);
        $this->drawFinderPattern($x + ($gridSize - 7) * $cellSize, $y, $cellSize);
        $this->drawFinderPattern($x, $y + ($gridSize - 7) * $cellSize, $cellSize);
        
        // Fill in data pattern
        $binaryIndex = 0;
        for ($row = 0; $row < $gridSize; $row++) {
            for ($col = 0; $col < $gridSize; $col++) {
                // Skip finder pattern areas
                if ($this->isFinderPattern($row, $col, $gridSize)) {
                    continue;
                }
                
                if ($binaryIndex < strlen($binary) && $binary[$binaryIndex] === '1') {
                    $this->pdf->Rect(
                        $x + $col * $cellSize, 
                        $y + $row * $cellSize, 
                        $cellSize, 
                        $cellSize, 
                        'F'
                    );
                }
                $binaryIndex++;
                if ($binaryIndex >= strlen($binary)) {
                    $binaryIndex = 0; // Repeat pattern if needed
                }
            }
        }
        
        // Add border
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->Rect($x, $y, $size, $size);
    }
    
    /**
     * Draw QR code finder pattern (corner square)
     */
    private function drawFinderPattern($x, $y, $cellSize) {
        // Outer square (7x7)
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                if ($i == 0 || $i == 6 || $j == 0 || $j == 6 || 
                    ($i >= 2 && $i <= 4 && $j >= 2 && $j <= 4)) {
                    $this->pdf->Rect(
                        $x + $j * $cellSize, 
                        $y + $i * $cellSize, 
                        $cellSize, 
                        $cellSize, 
                        'F'
                    );
                }
            }
        }
    }
    
    /**
     * Check if position is within finder pattern area
     */
    private function isFinderPattern($row, $col, $gridSize) {
        // Top-left finder pattern
        if ($row < 9 && $col < 9) return true;
        // Top-right finder pattern
        if ($row < 9 && $col >= $gridSize - 8) return true;
        // Bottom-left finder pattern
        if ($row >= $gridSize - 8 && $col < 9) return true;
        
        return false;
    }
    
    /**
     * Check if QR tag generation is available
     */
    public function isAvailable() {
        return $this->tcpdfAvailable || $this->fpdfAvailable;
    }
    
    /**
     * Get status information about PDF libraries
     */
    public function getStatus() {
        return [
            'tcpdf_available' => $this->tcpdfAvailable,
            'fpdf_available' => $this->fpdfAvailable,
            'preferred_library' => $this->useTCPDF ? 'TCPDF' : 'FPDF',
            'can_generate_tags' => $this->isAvailable()
        ];
    }
    
    /**
     * Generate a simple test PDF to verify library functionality
     */
    public function generateTestPDF() {
        if (!$this->isAvailable()) {
            throw new Exception('No PDF library available');
        }
        
        try {
            if ($this->useTCPDF && $this->tcpdfAvailable) {
                $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
                $pdf->AddPage();
                $pdf->SetFont('helvetica', 'B', 16);
                $pdf->Cell(0, 10, 'TCPDF Test', 0, 1, 'C');
                return $pdf->Output('test.pdf', 'S');
            } else {
                // Create basic FPDF test
                $pdf = new FPDF();
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->Cell(0, 10, 'FPDF Test - QR Tag Generator', 0, 1, 'C');
                $pdf->Ln(10);
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(0, 10, 'Library Status:', 0, 1);
                $pdf->Cell(0, 10, 'FPDF Available: ' . ($this->fpdfAvailable ? 'Yes' : 'No'), 0, 1);
                $pdf->Cell(0, 10, 'TCPDF Available: ' . ($this->tcpdfAvailable ? 'Yes' : 'No'), 0, 1);
                $pdf->Cell(0, 10, 'Date: ' . date('Y-m-d H:i:s'), 0, 1);
                
                return $pdf->Output('S', 'test.pdf');
            }
        } catch (Exception $e) {
            throw new Exception('PDF test generation failed: ' . $e->getMessage());
        }
    }
}
?>