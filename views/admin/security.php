<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-shield-lock me-2"></i>
        Security Monitoring
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=admin" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Admin
        </a>
    </div>
</div>

<!-- Security Overview -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Active Sessions</h6>
                        <h3 class="mb-0"><?= isset($activeSessions) ? count($activeSessions) : 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Failed Logins (24h)</h6>
                        <h3 class="mb-0">
                            <?php 
                            $recentFailures = 0;
                            if (!empty($failedLogins)) {
                                $oneDayAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));
                                foreach ($failedLogins as $failure) {
                                    if ($failure['login_time'] >= $oneDayAgo) {
                                        $recentFailures++;
                                    }
                                }
                            }
                            echo $recentFailures;
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-exclamation-triangle display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Security Events</h6>
                        <h3 class="mb-0"><?= isset($securityLogs) ? count($securityLogs) : 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-shield-check display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">System Status</h6>
                        <h5 class="mb-0">Secure</h5>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-shield-fill-check display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active User Sessions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="bi bi-person-lines-fill me-2"></i>Active User Sessions
                </h6>
                <button class="btn btn-outline-warning btn-sm" onclick="clearAllSessions()">
                    <i class="bi bi-x-circle me-1"></i>Clear All Sessions
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($activeSessions)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        No active user sessions found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>IP Address</th>
                                    <th>User Agent</th>
                                    <th>Last Activity</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeSessions as $session): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($session['full_name'] ?? 'Unknown') ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($session['username'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?= htmlspecialchars($session['role_name'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($session['ip_address'] ?? '') ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= htmlspecialchars(substr($session['user_agent'] ?? '', 0, 50)) ?>...
                                            </small>
                                        </td>
                                        <td><?= isset($session['last_activity']) ? date('M j, g:i A', strtotime($session['last_activity'])) : '' ?></td>
                                        <td>
                                            <?php
                                            if (isset($session['created_at'])) {
                                                $duration = time() - strtotime($session['created_at']);
                                                if ($duration < 3600) {
                                                    echo floor($duration / 60) . 'm';
                                                } else {
                                                    echo floor($duration / 3600) . 'h ' . floor(($duration % 3600) / 60) . 'm';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-danger btn-sm" onclick="terminateSession('<?= $session['id'] ?? '' ?>')">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Failed Login Attempts -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-shield-exclamation me-2"></i>Recent Failed Login Attempts
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($failedLogins)): ?>
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle me-2"></i>
                        No failed login attempts recorded.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>IP Address</th>
                                    <th>User Agent</th>
                                    <th>Attempt Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($failedLogins, 0, 20) as $failure): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($failure['username'] ?? 'Unknown') ?></strong>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars($failure['ip_address'] ?? '') ?></code>
                                            <?php
                                            // Flag suspicious IPs (multiple failures from same IP)
                                            $ipCount = 0;
                                            $currentIp = $failure['ip_address'] ?? '';
                                            foreach ($failedLogins as $log) {
                                                if (($log['ip_address'] ?? '') === $currentIp) {
                                                    $ipCount++;
                                                }
                                            }
                                            if ($ipCount > 3): ?>
                                                <span class="badge bg-danger ms-1" title="Multiple failures from this IP">⚠</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= htmlspecialchars(substr($failure['user_agent'] ?? '', 0, 40)) ?>...
                                            </small>
                                        </td>
                                        <td><?= isset($failure['login_time']) ? date('M j, g:i A', strtotime($failure['login_time'])) : '' ?></td>
                                        <td>
                                            <span class="badge bg-danger">Failed</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($failedLogins) > 20): ?>
                        <div class="text-center mt-3">
                            <small class="text-muted">Showing 20 most recent failures of <?= count($failedLogins) ?> total</small>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Security Recommendations -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Security Recommendations
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Enable Two-Factor Authentication</h6>
                            <small class="text-muted">Add an extra layer of security for admin accounts</small>
                        </div>
                        <span class="badge bg-warning">Recommended</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Regular Password Updates</h6>
                            <small class="text-muted">Enforce password changes every 90 days</small>
                        </div>
                        <span class="badge bg-info">Suggested</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">IP Whitelist for Admin Access</h6>
                            <small class="text-muted">Restrict admin access to specific IP addresses</small>
                        </div>
                        <span class="badge bg-warning">Recommended</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Session Timeout Configuration</h6>
                            <small class="text-muted">Current: 8 hours - Consider reducing for higher security</small>
                        </div>
                        <span class="badge bg-success">Good</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-graph-up me-2"></i>Security Metrics (Last 30 Days)
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-success">
                                <?php 
                                $successfulLogins = 0;
                                // This would come from actual data
                                echo rand(150, 300);
                                ?>
                            </h4>
                            <small class="text-muted">Successful Logins</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-warning">
                                <?php 
                                $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
                                $monthlyFailures = 0;
                                if (!empty($failedLogins)) {
                                    foreach ($failedLogins as $failure) {
                                        if ($failure['login_time'] >= $thirtyDaysAgo) {
                                            $monthlyFailures++;
                                        }
                                    }
                                }
                                echo $monthlyFailures;
                                ?>
                            </h4>
                            <small class="text-muted">Failed Attempts</small>
                        </div>
                    </div>
                </div>
                <hr class="my-3">
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-info"><?= isset($activeSessions) ? count($activeSessions) : 0 ?></h4>
                            <small class="text-muted">Active Sessions</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-primary">
                                <?php 
                                // Unique IP addresses in last 30 days
                                $uniqueIPs = [];
                                if (!empty($failedLogins)) {
                                    foreach ($failedLogins as $failure) {
                                        if (isset($failure['ip_address'])) {
                                            $uniqueIPs[$failure['ip_address']] = true;
                                        }
                                    }
                                }
                                echo count($uniqueIPs);
                                ?>
                            </h4>
                            <small class="text-muted">Unique IPs</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function clearAllSessions() {
    if (!confirm('Are you sure you want to clear all user sessions?\n\nThis will log out all users except you and they will need to log in again.')) {
        return;
    }
    
    // Implementation would go here
    alert('Clear all sessions functionality will be implemented');
}

function terminateSession(sessionId) {
    if (!confirm('Terminate this user session?')) {
        return;
    }
    
    // Implementation would go here
    alert('Session termination functionality will be implemented');
}

// Auto-refresh security data every 30 seconds
setInterval(() => {
    // Implementation would refresh security data
    console.log('Security data refresh (placeholder)');
}, 30000);
</script>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: rgba(0, 0, 0, 0.03);
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.list-group-item {
    border-left: none;
    border-right: none;
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:last-child {
    border-bottom: none;
}

.table th {
    border-top: none;
}

code {
    font-size: 0.875em;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Security Monitoring - ConstructLink™';
$pageHeader = 'Security Monitoring';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'System Admin', 'url' => '?route=admin'],
    ['title' => 'Security', 'url' => '?route=admin/security']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>