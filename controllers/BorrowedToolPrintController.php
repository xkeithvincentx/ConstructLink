<?php
/**
 * ConstructLink™ Borrowed Tool Print Controller
 * Handles print functionality for borrowed tools forms and batch documents
 * Phase 2.3 Refactoring - Extracted from monolithic BorrowedToolController
 */

require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';
require_once APP_ROOT . '/helpers/BorrowedTools/PermissionGuard.php';
require_once APP_ROOT . '/helpers/BorrowedTools/ResponseHelper.php';

class BorrowedToolPrintController {
    private $permissionGuard;
    private $batchModel;
    private $equipmentTypeModel;

    public function __construct() {
        $this->permissionGuard = new BorrowedToolsPermissionGuard();

        // Ensure user is authenticated
        if (!$this->permissionGuard->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }

        require_once APP_ROOT . '/models/BorrowedToolBatchModel.php';
        require_once APP_ROOT . '/models/EquipmentTypeModel.php';

        $this->batchModel = new BorrowedToolBatchModel();
        $this->equipmentTypeModel = new EquipmentTypeModel();
    }

    /**
     * Print batch borrowing form with filled data
     * Generates printable document for approved/borrowed batches
     *
     * @return void Renders printable batch form
     */
    public function printBatchForm() {
        try {
            // Check view permission (anyone who can view can print)
            $this->permissionGuard->requirePermission('view');

            $batchId = $_GET['batch_id'] ?? 0;
            if (!$batchId) {
                BorrowedToolsResponseHelper::renderError(404);
                return;
            }

            // Get batch with items
            $projectFilter = $this->permissionGuard->getProjectFilter();
            $batch = $this->batchModel->getBatchWithItems($batchId, $projectFilter);

            if (!$batch) {
                BorrowedToolsResponseHelper::renderError(404);
                return;
            }

            // Update printed_at timestamp
            $this->batchModel->update($batchId, [
                'printed_at' => date('Y-m-d H:i:s')
            ]);

            // Render print view
            include APP_ROOT . '/views/borrowed-tools/batch-print.php';

        } catch (Exception $e) {
            error_log("Print batch form error: " . $e->getMessage());
            BorrowedToolsResponseHelper::renderError(500, 'Failed to generate print form');
        }
    }

    /**
     * Print blank borrowing form with equipment types from database
     * Generates blank form for manual borrowing requests with checkboxes
     *
     * Features:
     * - Power tools section with subtypes
     * - Hand tools section with subtypes
     * - Borrower information fields
     * - MVA workflow signature sections
     * - Space-optimized 2x2 grid format (4 forms per A4 page)
     *
     * @return void Renders printable blank form
     */
    public function printBlankForm() {
        try {
            // Check view permission (anyone who can view can print)
            $this->permissionGuard->requirePermission('view');

            // Load AssetHelper for standalone print view
            require_once APP_ROOT . '/helpers/AssetHelper.php';

            // Fetch equipment types from model
            $powerTools = $this->equipmentTypeModel->getPowerTools();
            $handTools = $this->equipmentTypeModel->getHandTools();

            // Render print view
            include APP_ROOT . '/views/borrowed-tools/print-blank-form.php';

        } catch (Exception $e) {
            error_log("Print blank form error: " . $e->getMessage());

            // Fallback to empty arrays if database fails
            $powerTools = [];
            $handTools = [];
            include APP_ROOT . '/views/borrowed-tools/print-blank-form.php';
        }
    }

    /**
     * Print batch summary report (future feature)
     * Generates summary report for multiple batches
     *
     * @return void
     */
    public function printBatchSummary() {
        // Future implementation
        BorrowedToolsResponseHelper::renderError(501, 'Feature not yet implemented');
    }

    /**
     * Print overdue items report (future feature)
     * Generates report of all overdue borrowed tools
     *
     * @return void
     */
    public function printOverdueReport() {
        // Future implementation
        BorrowedToolsResponseHelper::renderError(501, 'Feature not yet implemented');
    }
}
