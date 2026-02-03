<?php
/**
 * ConstructLink™ Withdrawal Export Service
 *
 * Handles export operations for withdrawal data (Excel, CSV)
 * Follows PSR-4 namespacing and 2025 best practices
 */

class WithdrawalExportService {

    /**
     * Export withdrawals to Excel format
     *
     * @param array $withdrawals Withdrawal data to export
     * @param array $filters Applied filters for context
     * @return void Outputs Excel file and exits
     */
    public function exportToExcel($withdrawals, $filters = []) {
        try {
            $filename = 'withdrawals_' . date('Y-m-d_His') . '.xls';

            // Set headers for Excel download
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Build export data
            $this->outputExcelContent($withdrawals, $filters);
            exit;

        } catch (Exception $e) {
            error_log("WithdrawalExportService::exportToExcel error: " . $e->getMessage());
            throw new Exception('Failed to export to Excel');
        }
    }

    /**
     * Export withdrawals to CSV format
     *
     * @param array $withdrawals Withdrawal data to export
     * @param array $filters Applied filters for context
     * @return void Outputs CSV file and exits
     */
    public function exportToCSV($withdrawals, $filters = []) {
        try {
            $filename = 'withdrawals_' . date('Y-m-d_His') . '.csv';

            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Output CSV content
            $output = fopen('php://output', 'w');

            // Write header row
            $headers = $this->getExportHeaders();
            fputcsv($output, $headers);

            // Write data rows
            foreach ($withdrawals as $withdrawal) {
                $row = $this->buildExportRow($withdrawal);
                fputcsv($output, $row);
            }

            fclose($output);
            exit;

        } catch (Exception $e) {
            error_log("WithdrawalExportService::exportToCSV error: " . $e->getMessage());
            throw new Exception('Failed to export to CSV');
        }
    }

    /**
     * Build export data from withdrawals array
     *
     * @param array $withdrawals Withdrawal records
     * @return array Formatted data for export
     */
    public function buildExportData($withdrawals) {
        $exportData = [];

        foreach ($withdrawals as $withdrawal) {
            $exportData[] = $this->buildExportRow($withdrawal);
        }

        return $exportData;
    }

    /**
     * Get export column headers
     *
     * @return array Column headers
     */
    private function getExportHeaders() {
        return [
            'ID',
            'Consumable Reference',
            'Consumable Name',
            'Category',
            'Project',
            'Receiver',
            'Purpose',
            'Quantity',
            'Unit',
            'Withdrawn By',
            'Status',
            'Expected Return',
            'Actual Return',
            'Created Date',
            'Verified Date',
            'Approved Date',
            'Released Date',
            'Notes'
        ];
    }

    /**
     * Build a single export row from withdrawal data
     *
     * @param array $withdrawal Withdrawal record
     * @return array Formatted row data
     */
    private function buildExportRow($withdrawal) {
        return [
            $withdrawal['id'] ?? '',
            $withdrawal['item_ref'] ?? '',
            $withdrawal['item_name'] ?? '',
            $withdrawal['category_name'] ?? '',
            $withdrawal['project_name'] ?? '',
            $withdrawal['receiver_name'] ?? '',
            $withdrawal['purpose'] ?? '',
            $withdrawal['quantity'] ?? 1,
            $withdrawal['unit'] ?? 'pcs',
            $withdrawal['withdrawn_by_name'] ?? '',
            $this->formatStatus($withdrawal['status'] ?? ''),
            $this->formatDate($withdrawal['expected_return'] ?? null),
            $this->formatDate($withdrawal['actual_return'] ?? null),
            $this->formatDateTime($withdrawal['created_at'] ?? null),
            $this->formatDateTime($withdrawal['verification_date'] ?? null),
            $this->formatDateTime($withdrawal['approval_date'] ?? null),
            $this->formatDateTime($withdrawal['released_at'] ?? null),
            $this->sanitizeNotes($withdrawal['notes'] ?? '')
        ];
    }

    /**
     * Output Excel-formatted HTML content
     *
     * @param array $withdrawals Withdrawal data
     * @param array $filters Applied filters
     * @return void
     */
    private function outputExcelContent($withdrawals, $filters) {
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        echo '  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        echo '<Worksheet ss:Name="Withdrawals">' . "\n";
        echo '<Table>' . "\n";

        // Header row
        echo '<Row>' . "\n";
        foreach ($this->getExportHeaders() as $header) {
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
        }
        echo '</Row>' . "\n";

        // Data rows
        foreach ($withdrawals as $withdrawal) {
            echo '<Row>' . "\n";
            $row = $this->buildExportRow($withdrawal);
            foreach ($row as $cell) {
                $type = is_numeric($cell) ? 'Number' : 'String';
                echo '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($cell) . '</Data></Cell>' . "\n";
            }
            echo '</Row>' . "\n";
        }

        echo '</Table>' . "\n";
        echo '</Worksheet>' . "\n";
        echo '</Workbook>' . "\n";
    }

    /**
     * Format status for display
     *
     * @param string $status Status value
     * @return string Formatted status
     */
    private function formatStatus($status) {
        return ucwords(str_replace('_', ' ', $status));
    }

    /**
     * Format date for export (Y-m-d format)
     *
     * @param string|null $date Date string
     * @return string Formatted date or empty string
     */
    private function formatDate($date) {
        if (empty($date)) {
            return '';
        }

        try {
            return date('Y-m-d', strtotime($date));
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Format datetime for export (Y-m-d H:i:s format)
     *
     * @param string|null $datetime DateTime string
     * @return string Formatted datetime or empty string
     */
    private function formatDateTime($datetime) {
        if (empty($datetime)) {
            return '';
        }

        try {
            return date('Y-m-d H:i:s', strtotime($datetime));
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Sanitize notes for export
     *
     * @param string $notes Notes text
     * @return string Sanitized notes
     */
    private function sanitizeNotes($notes) {
        // Remove excessive line breaks and tabs
        $notes = str_replace(["\r\n", "\r", "\n"], ' ', $notes);
        $notes = str_replace("\t", ' ', $notes);

        // Remove multiple spaces
        $notes = preg_replace('/\s+/', ' ', $notes);

        // Trim and return
        return trim($notes);
    }
}
