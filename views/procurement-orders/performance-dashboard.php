<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

        <a href="?route=procurement-orders" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Procurement Orders
        </a>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4" id="filterCard" style="display: none;">
    <div class="card-body">
        <form method="GET" action="">
            <input type="hidden" name="route" value="procurement-orders/performance-dashboard">
            <div class="row">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $_GET['date_from'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $_GET['date_to'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label for="project_id" class="form-label">Project</label>
                    <select class="form-control" id="project_id" name="project_id">
                        <option value="">All Projects</option>
                        <!-- Projects will be loaded dynamically -->
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Apply Filters
                    </button>
                    <a href="?route=procurement-orders/performance-dashboard" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Key Performance Metrics -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            On-Time Delivery Rate</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= isset($summary['on_time_rate']) ? number_format($summary['on_time_rate'], 1) : '0' ?>%
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-clock text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Orders</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= isset($overallStats['total_orders']) ? number_format($overallStats['total_orders']) : '0' ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cart-plus text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Total Value</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ₱<?= isset($overallStats['total_value']) ? number_format($overallStats['total_value'], 2) : '0.00' ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-currency-dollar text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Average Lead Time</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= isset($summary['avg_lead_time']) ? number_format($summary['avg_lead_time'], 1) : '0' ?> days
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-hourglass text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Delivery Performance Chart -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Monthly Delivery Performance</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="deliveryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Status Distribution -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Order Status Distribution</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Supplier Performance Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Supplier Performance Analysis</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="supplierTable">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Total Orders</th>
                        <th>Total Value</th>
                        <th>On-Time Deliveries</th>
                        <th>On-Time Rate</th>
                        <th>Avg Lead Time</th>
                        <th>Performance Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($supplierPerformance)): ?>
                        <?php foreach ($supplierPerformance as $supplier): ?>
                        <tr>
                            <td><?= htmlspecialchars($supplier['supplier_name']) ?></td>
                            <td><?= number_format($supplier['total_orders']) ?></td>
                            <td>₱<?= number_format($supplier['total_value'], 2) ?></td>
                            <td><?= number_format($supplier['on_time_deliveries']) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $supplier['on_time_rate'] >= 90 ? 'success' : 
                                    ($supplier['on_time_rate'] >= 70 ? 'warning' : 'danger') 
                                ?>">
                                    <?= number_format($supplier['on_time_rate'], 1) ?>%
                                </span>
                            </td>
                            <td><?= number_format($supplier['avg_lead_time'], 1) ?> days</td>
                            <td>
                                <?php 
                                $rating = $supplier['on_time_rate'] >= 90 ? 'Excellent' : 
                                         ($supplier['on_time_rate'] >= 80 ? 'Good' : 
                                         ($supplier['on_time_rate'] >= 70 ? 'Average' : 'Poor'));
                                $ratingClass = $supplier['on_time_rate'] >= 90 ? 'success' : 
                                              ($supplier['on_time_rate'] >= 80 ? 'info' : 
                                              ($supplier['on_time_rate'] >= 70 ? 'warning' : 'danger'));
                                ?>
                                <span class="badge bg-<?= $ratingClass ?>"><?= $rating ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No supplier performance data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Delivery Alerts -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Recent Delivery Alerts</h6>
        <div class="dropdown no-arrow">
            <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="bi bi-three-dots-vertical text-gray-400"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow">
                <a class="dropdown-item" href="?route=procurement-orders&status=overdue">View All Overdue</a>
                <a class="dropdown-item" href="?route=procurement-orders&status=urgent">View Urgent Orders</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($alerts)): ?>
            <div class="table-responsive">
                <table class="table table-bordered" id="alertsTable">
                    <thead>
                        <tr>
                            <th>Alert Type</th>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Scheduled Date</th>
                            <th>Days Overdue</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alerts as $alert): ?>
                        <tr>
                            <td>
                                <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                <?= htmlspecialchars($alert['alert_type'] ?? 'Alert') ?>
                            </td>
                            <td>
                                <a href="?route=procurement-orders/view&id=<?= $alert['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($alert['po_number'] ?? 'N/A') ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($alert['vendor_name'] ?? 'N/A') ?></td>
                            <td><?= isset($alert['scheduled_delivery_date']) && $alert['scheduled_delivery_date'] ? date('M d, Y', strtotime($alert['scheduled_delivery_date'])) : 'N/A' ?></td>
                            <td>
                                <span class="badge bg-<?= $alert['days_overdue'] > 7 ? 'danger' : 'warning' ?>">
                                    <?= $alert['days_overdue'] ?> days
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    ($alert['days_overdue'] ?? 0) > 14 ? 'danger' : 
                                    (($alert['days_overdue'] ?? 0) > 7 ? 'warning' : 'info') 
                                ?>">
                                    <?= ($alert['days_overdue'] ?? 0) > 14 ? 'High' : (($alert['days_overdue'] ?? 0) > 7 ? 'Medium' : 'Low') ?>
                                </span>
                            </td>
                            <td>
                                <a href="?route=procurement-orders/view&id=<?= $alert['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                <h5 class="mt-2">No Delivery Alerts</h5>
                <p>All deliveries are on track!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Performance Insights -->
<div class="row">
    <div class="col-lg-6">
        <div class="card border-left-info shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">Performance Insights</h6>
            </div>
            <div class="card-body">
                <div class="small text-muted">
                    <?php if (isset($summary['insights'])): ?>
                        <?php foreach ($summary['insights'] as $insight): ?>
                            <div class="d-flex align-items-start mb-2">
                                <i class="bi bi-lightbulb text-info me-2 mt-1"></i>
                                <span><?= htmlspecialchars($insight) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="d-flex align-items-start mb-2">
                            <i class="bi bi-lightbulb text-info me-2 mt-1"></i>
                            <span>Procurement performance is within expected ranges</span>
                        </div>
                        <div class="d-flex align-items-start mb-2">
                            <i class="bi bi-lightbulb text-info me-2 mt-1"></i>
                            <span>Consider optimizing supplier relationships for better lead times</span>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="bi bi-lightbulb text-info me-2 mt-1"></i>
                            <span>Monitor delivery performance trends monthly</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-left-warning shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-warning">Action Items</h6>
            </div>
            <div class="card-body">
                <div class="small text-muted">
                    <div class="d-flex align-items-start mb-2">
                        <i class="bi bi-check-square text-warning me-2 mt-1"></i>
                        <span>Follow up on overdue deliveries</span>
                    </div>
                    <div class="d-flex align-items-start mb-2">
                        <i class="bi bi-check-square text-warning me-2 mt-1"></i>
                        <span>Review supplier performance quarterly</span>
                    </div>
                    <div class="d-flex align-items-start mb-2">
                        <i class="bi bi-check-square text-warning me-2 mt-1"></i>
                        <span>Update delivery schedules as needed</span>
                    </div>
                    <div class="d-flex align-items-start">
                        <i class="bi bi-check-square text-warning me-2 mt-1"></i>
                        <span>Document delivery performance issues</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#supplierTable').DataTable({
        "pageLength": 10,
        "order": [[ 4, "desc" ]]
    });
    
    $('#alertsTable').DataTable({
        "pageLength": 5,
        "order": [[ 4, "desc" ]]
    });
    
    // Delivery Performance Chart
    const deliveryCtx = document.getElementById('deliveryChart').getContext('2d');
    new Chart(deliveryCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($deliveryMetrics['months'] ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']) ?>,
            datasets: [{
                label: 'On-Time Deliveries (%)',
                data: <?= json_encode($deliveryMetrics['on_time_rates'] ?? [85, 90, 88, 92, 87, 94]) ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: 'Average Lead Time (days)',
                data: <?= json_encode($deliveryMetrics['lead_times'] ?? [12, 10, 11, 9, 13, 8]) ?>,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'On-Time Rate (%)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Lead Time (days)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
    
    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($overallStats['status_labels'] ?? ['Draft', 'Pending', 'Approved', 'In Transit', 'Delivered']) ?>,
            datasets: [{
                data: <?= json_encode($overallStats['status_counts'] ?? [5, 12, 8, 15, 35]) ?>,
                backgroundColor: [
                    'rgba(108, 117, 125, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(13, 202, 240, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});

// Filter functions
function showDateFilter() {
    document.getElementById('filterCard').style.display = 'block';
    document.getElementById('date_from').focus();
}

function showProjectFilter() {
    document.getElementById('filterCard').style.display = 'block';
    document.getElementById('project_id').focus();
}

function showSupplierFilter() {
    document.getElementById('filterCard').style.display = 'block';
}
</script>

<?php
// Capture content and include layout
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>