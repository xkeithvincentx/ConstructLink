<?php
/**
 * ConstructLink™ Delivery Performance View
 * Display delivery performance metrics and analytics
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="?route=procurement-orders/delivery-performance" class="row g-3">
            <div class="col-md-3">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" name="date_from" id="date_from" class="form-control" 
                       value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" name="date_to" id="date_to" class="form-control" 
                       value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label for="project_id" class="form-label">Project</label>
                <select name="project_id" id="project_id" class="form-select">
                    <option value="">All Projects</option>
                    <!-- Project options would be populated dynamically -->
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="?route=procurement-orders/delivery-performance" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Performance Metrics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="card-title text-primary"><?= number_format($metrics['total_orders']) ?></h3>
                <p class="card-text">Total Orders</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="card-title text-success"><?= number_format($metrics['completed_deliveries']) ?></h3>
                <p class="card-text">Completed Deliveries</p>
                <small class="text-muted"><?= $summary['completion_rate'] ?>% completion rate</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="card-title text-warning"><?= number_format($metrics['overdue_deliveries']) ?></h3>
                <p class="card-text">Overdue Deliveries</p>
                <small class="text-muted"><?= $summary['overdue_rate'] ?>% overdue rate</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="card-title text-info"><?= number_format($metrics['in_progress']) ?></h3>
                <p class="card-text">In Progress</p>
                <small class="text-muted">Currently being processed</small>
            </div>
        </div>
    </div>
</div>

<!-- Performance Charts -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-pie-chart me-2"></i>Delivery Status Distribution
                </h6>
            </div>
            <div class="card-body">
                <canvas id="deliveryStatusChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-bar-chart me-2"></i>Performance Trends
                </h6>
            </div>
            <div class="card-body">
                <canvas id="performanceTrendsChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Delivery Alerts -->
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-exclamation-triangle me-2"></i>Recent Delivery Alerts
        </h6>
    </div>
    <div class="card-body">
        <?php if (!empty($alerts)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Vendor</th>
                        <th>Project</th>
                        <th>Alert Type</th>
                        <th>Status</th>
                        <th>Days Overdue</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td>
                            <a href="?route=procurement-orders/view&id=<?= $alert['id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($alert['po_number']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($alert['vendor_name']) ?></td>
                        <td><?= htmlspecialchars($alert['project_name']) ?></td>
                        <td>
                            <span class="badge bg-<?= getDeliveryAlertSeverity($alert['alert_type']) ?>">
                                <?= htmlspecialchars($alert['alert_type']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= getDeliveryStatusBadgeClass($alert['delivery_status']) ?>">
                                <?= htmlspecialchars($alert['delivery_status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($alert['days_overdue'] > 0): ?>
                                <span class="text-danger"><?= $alert['days_overdue'] ?> days</span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="?route=procurement-orders/view&id=<?= $alert['id'] ?>" 
                                   class="btn btn-outline-primary" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (canUpdateDeliveryStatus($alert, $userRole)): ?>
                                <a href="?route=procurement-orders/update-delivery&id=<?= $alert['id'] ?>" 
                                   class="btn btn-outline-warning" title="Update Status">
                                    <i class="bi bi-arrow-repeat"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
            <h5 class="mt-3">No Delivery Alerts</h5>
            <p class="text-muted">All deliveries are proceeding as expected.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Delivery Status Distribution Chart
const deliveryStatusCtx = document.getElementById('deliveryStatusChart').getContext('2d');
const deliveryStatusChart = new Chart(deliveryStatusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Completed', 'In Progress', 'Overdue', 'With Discrepancies'],
        datasets: [{
            data: [
                <?= $metrics['completed_deliveries'] ?>,
                <?= $metrics['in_progress'] ?>,
                <?= $metrics['overdue_deliveries'] ?>,
                <?= $metrics['deliveries_with_discrepancies'] ?>
            ],
            backgroundColor: [
                '#28a745',
                '#17a2b8',
                '#ffc107',
                '#dc3545'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Performance Trends Chart (placeholder - would need actual trend data)
const performanceTrendsCtx = document.getElementById('performanceTrendsChart').getContext('2d');
const performanceTrendsChart = new Chart(performanceTrendsCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Completion Rate (%)',
            data: [85, 88, 92, 89, 91, 94],
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4
        }, {
            label: 'Overdue Rate (%)',
            data: [15, 12, 8, 11, 9, 6],
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        },
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?> 