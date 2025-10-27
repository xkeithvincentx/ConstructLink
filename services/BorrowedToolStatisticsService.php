<?php
/**
 * ConstructLink™ Borrowed Tool Statistics Service
 * Handles business logic for borrowed tools statistics and reporting
 * Created during Phase 2.2 refactoring
 */

class BorrowedToolStatisticsService {
    private $batchModel;
    private $assetModel;

    public function __construct() {
        require_once APP_ROOT . '/models/BorrowedToolBatchModel.php';
        require_once APP_ROOT . '/models/AssetModel.php';

        $this->batchModel = new BorrowedToolBatchModel();
        $this->assetModel = new AssetModel();
    }

    /**
     * Get comprehensive borrowed tools statistics
     * Moved from BorrowedToolController::index() (Phase 2.2)
     *
     * @param int|null $projectFilter Optional project ID filter
     * @return array Statistics including batch stats, equipment count, and time-based stats
     */
    public function getBorrowedToolsStats($projectFilter = null) {
        // Get batch statistics
        $batchStats = $this->batchModel->getBatchStats(null, null, $projectFilter);

        // Get available non-consumable equipment count
        $availableEquipmentCount = $this->assetModel->getAvailableEquipmentCount($projectFilter);

        // Get time-based statistics
        $timeStats = $this->batchModel->getTimeBasedStatistics($projectFilter);

        // Transform batch stats to match expected format in views
        $borrowedToolStats = [
            // MVA workflow stats
            'pending_verification' => $batchStats['pending_verification'] ?? 0,
            'pending_approval' => $batchStats['pending_approval'] ?? 0,
            'approved' => $batchStats['approved'] ?? 0,

            // Active borrowing stats
            'borrowed' => $batchStats['borrowed'] ?? 0,
            'overdue' => $batchStats['overdue'] ?? 0,

            // Completion stats
            'returned' => $batchStats['returned'] ?? 0,
            'canceled' => $batchStats['canceled'] ?? 0,

            // Asset availability
            'available_equipment' => $availableEquipmentCount,

            // Time-based statistics
            'today' => $timeStats['today'] ?? 0,
            'this_week' => $timeStats['this_week'] ?? 0,
            'this_month' => $timeStats['this_month'] ?? 0,
            'overdue_items' => $timeStats['overdue'] ?? 0
        ];

        return $borrowedToolStats;
    }

    /**
     * Get dashboard statistics summary
     *
     * @param int|null $projectFilter Optional project ID filter
     * @return array Dashboard statistics
     */
    public function getDashboardStats($projectFilter = null) {
        $stats = $this->getBorrowedToolsStats($projectFilter);

        return [
            'active_borrowings' => $stats['borrowed'] + $stats['overdue'],
            'pending_actions' => $stats['pending_verification'] + $stats['pending_approval'] + $stats['approved'],
            'overdue_items' => $stats['overdue'],
            'available_equipment' => $stats['available_equipment']
        ];
    }

    /**
     * Get overdue statistics with details
     *
     * @param int|null $projectFilter Optional project ID filter
     * @return array Overdue statistics with breakdown
     */
    public function getOverdueStats($projectFilter = null) {
        $timeStats = $this->batchModel->getTimeBasedStatistics($projectFilter);
        $batchStats = $this->batchModel->getBatchStats(null, null, $projectFilter);

        return [
            'total_overdue' => $timeStats['overdue'] ?? 0,
            'overdue_percentage' => $batchStats['borrowed'] > 0
                ? round(($timeStats['overdue'] / $batchStats['borrowed']) * 100, 1)
                : 0
        ];
    }

    /**
     * Get trend statistics comparing periods
     *
     * @param int|null $projectFilter Optional project ID filter
     * @return array Trend statistics
     */
    public function getTrendStats($projectFilter = null) {
        $timeStats = $this->batchModel->getTimeBasedStatistics($projectFilter);

        return [
            'today_vs_week' => [
                'today' => $timeStats['today'] ?? 0,
                'week_avg' => ($timeStats['this_week'] ?? 0) / 7,
                'trend' => 'up' // Placeholder - calculate actual trend
            ],
            'week_vs_month' => [
                'week' => $timeStats['this_week'] ?? 0,
                'month_avg' => ($timeStats['this_month'] ?? 0) / 4,
                'trend' => 'stable' // Placeholder - calculate actual trend
            ]
        ];
    }
}
