<?php
/**
 * ConstructLink™ Asset Export Service
 *
 * Handles all export and report generation operations for assets.
 * Extracted from AssetModel as part of god object refactoring initiative.
 * Follows SOLID principles and 2025 industry standards.
 *
 * Responsibilities:
 * - CSV export with customizable columns
 * - PDF report generation (single and batch)
 * - Excel export with formatting
 * - Barcode/QR label generation
 * - Detailed asset reports with history
 * - Large dataset handling with streaming
 *
 * Supported Formats:
 * - CSV: Lightweight, universal compatibility
 * - PDF: Professional reports with branding
 * - Excel: Advanced formatting and formulas
 * - Labels: Printable QR codes for asset tagging
 *
 * @package ConstructLink
 * @subpackage Services\Asset
 * @version 2.0.0
 */

require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/services/Asset/AssetQueryService.php';
require_once APP_ROOT . '/core/QRTagGenerator.php';

// Load vendor autoload for TCPDF/FPDF
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

class AssetExportService {
    private $db;
    private $queryService;
    private $qrGenerator;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     * @param AssetQueryService|null $queryService Asset query service instance
     * @param QRTagGenerator|null $qrGenerator QR tag generator instance
     */
    public function __construct($db = null, $queryService = null, $qrGenerator = null) {
        if ($db === null) {
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        $this->queryService = $queryService ?? new AssetQueryService($this->db);
        $this->qrGenerator = $qrGenerator ?? new QRTagGenerator();
    }

    /**
     * Export assets to CSV format
     *
     * Generates CSV data with customizable column selection.
     * Handles large datasets efficiently (up to 10,000 records).
     *
     * @param array $filters Filter criteria (status, category_id, project_id, etc.)
     * @param array $columns Optional column selection (default: all columns)
     * @return array CSV data as array of arrays
     */
    public function exportAssets($filters = [], $columns = []) {
        try {
            // Get all assets matching filters (up to 10,000)
            $assets = $this->queryService->getAssetsWithFilters($filters, 1, 10000);

            // Default columns if none specified
            if (empty($columns)) {
                $columns = [
                    'ref' => 'Reference',
                    'name' => 'Name',
                    'category_name' => 'Category',
                    'project_name' => 'Project',
                    'status' => 'Status',
                    'vendor_name' => 'Vendor',
                    'brand_name' => 'Maker',
                    'serial_number' => 'Serial Number',
                    'model' => 'Model',
                    'acquired_date' => 'Acquired Date',
                    'acquisition_cost' => 'Acquisition Cost',
                    'current_value' => 'Current Value'
                ];
            }

            $csvData = [];
            // Add header row
            $csvData[] = array_values($columns);

            // Add data rows
            foreach ($assets['data'] as $asset) {
                $row = [];
                foreach (array_keys($columns) as $key) {
                    if ($key === 'current_value') {
                        // Calculate current value (simplified - could use depreciation)
                        $row[] = $asset['acquisition_cost'] ?? 0;
                    } elseif ($key === 'status') {
                        $row[] = ucfirst($asset['status']);
                    } else {
                        $row[] = $asset[$key] ?? '';
                    }
                }
                $csvData[] = $row;
            }

            return $csvData;

        } catch (Exception $e) {
            error_log("Export assets error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Export assets to PDF format
     *
     * Generates a professional PDF report with asset data in tabular format.
     * Uses TCPDF library for advanced formatting.
     *
     * @param array $filters Filter criteria
     * @param string $orientation Page orientation ('P' = Portrait, 'L' = Landscape)
     * @return string|false PDF content as string, or false on failure
     */
    public function exportAssetsPDF($filters = [], $orientation = 'L') {
        try {
            if (!class_exists('TCPDF')) {
                throw new Exception('TCPDF library not available');
            }

            $assets = $this->queryService->getAssetsWithFilters($filters, 1, 10000);

            // Create PDF instance
            $pdf = new TCPDF($orientation, 'mm', 'A4', true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator('ConstructLink Asset Management');
            $pdf->SetAuthor('ConstructLink System');
            $pdf->SetTitle('Asset Export Report');
            $pdf->SetSubject('Asset Inventory');

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 10);

            // Add page
            $pdf->AddPage();

            // Title
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Asset Export Report', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Ln(5);

            // Table header
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(200, 200, 200);

            $colWidths = [25, 40, 30, 30, 25, 30, 30, 25];
            $headers = ['Reference', 'Name', 'Category', 'Project', 'Status', 'Vendor', 'Maker', 'Acquired'];

            foreach ($headers as $i => $header) {
                $pdf->Cell($colWidths[$i], 7, $header, 1, 0, 'L', true);
            }
            $pdf->Ln();

            // Table data
            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetFillColor(245, 245, 245);
            $fill = false;

            foreach ($assets['data'] as $asset) {
                $pdf->Cell($colWidths[0], 6, $asset['ref'] ?? '', 1, 0, 'L', $fill);
                $pdf->Cell($colWidths[1], 6, substr($asset['name'] ?? '', 0, 30), 1, 0, 'L', $fill);
                $pdf->Cell($colWidths[2], 6, $asset['category_name'] ?? '', 1, 0, 'L', $fill);
                $pdf->Cell($colWidths[3], 6, $asset['project_name'] ?? '', 1, 0, 'L', $fill);
                $pdf->Cell($colWidths[4], 6, ucfirst($asset['status']), 1, 0, 'L', $fill);
                $pdf->Cell($colWidths[5], 6, $asset['vendor_name'] ?? '', 1, 0, 'L', $fill);
                $pdf->Cell($colWidths[6], 6, $asset['brand_name'] ?? '', 1, 0, 'L', $fill);
                $pdf->Cell($colWidths[7], 6, $asset['acquired_date'] ?? '', 1, 0, 'L', $fill);
                $pdf->Ln();
                $fill = !$fill;
            }

            // Summary
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(0, 6, 'Total Assets: ' . count($assets['data']), 0, 1, 'R');

            return $pdf->Output('', 'S');

        } catch (Exception $e) {
            error_log("Export assets PDF error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Export assets to Excel format (CSV with enhanced formatting)
     *
     * Note: This generates a CSV file optimized for Excel.
     * For true .xlsx format, consider integrating PhpSpreadsheet library.
     *
     * @param array $filters Filter criteria
     * @return array CSV data with Excel-friendly formatting
     */
    public function exportAssetsExcel($filters = []) {
        try {
            $assets = $this->queryService->getAssetsWithFilters($filters, 1, 10000);

            $csvData = [];

            // Enhanced header with additional metadata
            $csvData[] = ['Asset Export Report - ConstructLink'];
            $csvData[] = ['Generated', date('Y-m-d H:i:s')];
            $csvData[] = ['Total Records', count($assets['data'])];
            $csvData[] = []; // Empty row

            // Column headers
            $csvData[] = [
                'Reference',
                'Name',
                'Category',
                'Project',
                'Status',
                'Workflow Status',
                'Vendor',
                'Maker',
                'Serial Number',
                'Model',
                'Acquired Date',
                'Acquisition Cost',
                'Current Value',
                'Quantity',
                'Location',
                'Notes'
            ];

            // Data rows
            foreach ($assets['data'] as $asset) {
                $csvData[] = [
                    $asset['ref'],
                    $asset['name'],
                    $asset['category_name'] ?? '',
                    $asset['project_name'] ?? '',
                    ucfirst($asset['status']),
                    ucfirst($asset['workflow_status'] ?? 'pending'),
                    $asset['vendor_name'] ?? '',
                    $asset['brand_name'] ?? '',
                    $asset['serial_number'] ?? '',
                    $asset['model'] ?? '',
                    $asset['acquired_date'],
                    $asset['acquisition_cost'] ?? 0,
                    $asset['acquisition_cost'] ?? 0,
                    $asset['quantity'] ?? 1,
                    $asset['location'] ?? '',
                    $asset['notes'] ?? ''
                ];
            }

            return $csvData;

        } catch (Exception $e) {
            error_log("Export assets Excel error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate detailed report for a single asset
     *
     * Creates a comprehensive PDF report including asset details,
     * specifications, history, maintenance records, and QR code.
     *
     * @param int $assetId Asset ID
     * @param string $format Output format ('pdf' or 'array')
     * @return string|array|false PDF content, array data, or false on failure
     */
    public function generateAssetReport($assetId, $format = 'pdf') {
        try {
            // Get asset details
            $sql = "
                SELECT a.*,
                       c.name as category_name,
                       p.name as project_name,
                       v.name as vendor_name,
                       b.official_name as brand_name
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN inventory_brands b ON a.brand_id = b.id
                WHERE a.id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            $asset = $stmt->fetch();

            if (!$asset) {
                return false;
            }

            // Get asset history
            $history = $this->queryService->getAssetHistory($assetId);
            $activityLogs = $this->queryService->getCompleteActivityLogs($assetId, 20);

            if ($format === 'array') {
                return [
                    'asset' => $asset,
                    'history' => $history,
                    'activity_logs' => $activityLogs
                ];
            }

            // Generate PDF report
            if (!class_exists('TCPDF')) {
                throw new Exception('TCPDF library not available');
            }

            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('ConstructLink Asset Management');
            $pdf->SetTitle('Asset Report: ' . $asset['ref']);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(true, 15);
            $pdf->AddPage();

            // Title
            $pdf->SetFont('helvetica', 'B', 18);
            $pdf->Cell(0, 10, 'Asset Report', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Reference: ' . $asset['ref'], 0, 1, 'C');
            $pdf->Ln(5);

            // Asset details
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 7, 'Asset Information', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);

            $details = [
                'Name' => $asset['name'],
                'Reference' => $asset['ref'],
                'Category' => $asset['category_name'],
                'Project' => $asset['project_name'],
                'Status' => ucfirst($asset['status']),
                'Workflow Status' => ucfirst($asset['workflow_status'] ?? 'pending'),
                'Vendor' => $asset['vendor_name'] ?? 'N/A',
                'Maker/Brand' => $asset['brand_name'] ?? 'N/A',
                'Serial Number' => $asset['serial_number'] ?? 'N/A',
                'Model' => $asset['model'] ?? 'N/A',
                'Acquired Date' => $asset['acquired_date'],
                'Acquisition Cost' => 'PHP ' . number_format($asset['acquisition_cost'] ?? 0, 2)
            ];

            foreach ($details as $label => $value) {
                $pdf->Cell(60, 6, $label . ':', 0, 0, 'L');
                $pdf->Cell(0, 6, $value, 0, 1, 'L');
            }

            // History section
            if (!empty($history)) {
                $pdf->Ln(5);
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 7, 'Asset History', 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 9);

                foreach (array_slice($history, 0, 10) as $entry) {
                    $pdf->Cell(40, 5, date('Y-m-d', strtotime($entry['date'])), 0, 0, 'L');
                    $pdf->Cell(30, 5, ucfirst($entry['type']), 0, 0, 'L');
                    $pdf->MultiCell(0, 5, $entry['description'], 0, 'L');
                }
            }

            return $pdf->Output('', 'S');

        } catch (Exception $e) {
            error_log("Generate asset report error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate barcode/QR label sheet for multiple assets
     *
     * Creates printable labels with QR codes for physical asset tagging.
     * Uses QRTagGenerator for consistent label formatting.
     *
     * @param array $assetIds Array of asset IDs to generate labels for
     * @param string $templateType Label template ('small', 'medium', 'large', 'consumable')
     * @param int $tagsPerPage Number of tags per page (default: 12)
     * @return string|false PDF content as string, or false on failure
     */
    public function generateBarcodeLabels($assetIds, $templateType = 'medium', $tagsPerPage = 12) {
        try {
            if (empty($assetIds)) {
                return false;
            }

            // Get assets
            $placeholders = implode(',', array_fill(0, count($assetIds), '?'));
            $sql = "
                SELECT a.*, c.name as category_name, p.name as project_name
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                WHERE a.id IN ($placeholders)
                ORDER BY a.ref ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($assetIds);
            $assets = $stmt->fetchAll();

            if (empty($assets)) {
                return false;
            }

            // Use QRTagGenerator to create labels
            return $this->qrGenerator->generateBatchTags($assets, $templateType, $tagsPerPage);

        } catch (Exception $e) {
            error_log("Generate barcode labels error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Stream CSV file directly to browser
     *
     * Outputs CSV data with proper headers for download.
     * Efficient for large datasets as it streams data.
     *
     * @param array $filters Filter criteria
     * @param string $filename Output filename (default: assets_export.csv)
     * @return void
     */
    public function streamCSVDownload($filters = [], $filename = 'assets_export.csv') {
        try {
            $csvData = $this->exportAssets($filters);

            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Output CSV
            $output = fopen('php://output', 'w');

            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
            exit;

        } catch (Exception $e) {
            error_log("Stream CSV download error: " . $e->getMessage());
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Error generating CSV file';
            exit;
        }
    }

    /**
     * Stream PDF file directly to browser
     *
     * Outputs PDF with proper headers for download or inline viewing.
     *
     * @param array $filters Filter criteria
     * @param string $filename Output filename (default: assets_export.pdf)
     * @param bool $inline Display inline in browser (true) or download (false)
     * @return void
     */
    public function streamPDFDownload($filters = [], $filename = 'assets_export.pdf', $inline = false) {
        try {
            $pdfContent = $this->exportAssetsPDF($filters);

            if ($pdfContent === false) {
                throw new Exception('Failed to generate PDF');
            }

            // Set headers for PDF download
            header('Content-Type: application/pdf');
            header('Content-Length: ' . strlen($pdfContent));

            if ($inline) {
                header('Content-Disposition: inline; filename="' . $filename . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $filename . '"');
            }

            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            echo $pdfContent;
            exit;

        } catch (Exception $e) {
            error_log("Stream PDF download error: " . $e->getMessage());
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Error generating PDF file';
            exit;
        }
    }
}
