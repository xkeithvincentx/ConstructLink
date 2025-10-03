<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

</div>

<!-- Messages -->
<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Settings Form -->
<form method="POST" action="?route=admin/settings" id="settingsForm">
    <?= CSRFProtection::generateToken() ?>
    
    <div class="row">
        <!-- General Settings -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-gear me-2"></i>General Settings
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $generalSettings = [
                        'system_name' => 'System Name',
                        'company_name' => 'Company Name',
                        'system_version' => 'System Version'
                    ];
                    
                    foreach ($generalSettings as $key => $label):
                        $setting = null;
                        foreach ($settings as $s) {
                            if ($s['setting_key'] === $key) {
                                $setting = $s;
                                break;
                            }
                        }
                    ?>
                        <div class="mb-3">
                            <label for="<?= $key ?>" class="form-label">
                                <?= htmlspecialchars($label) ?>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="<?= $key ?>" 
                                   name="<?= $key ?>" 
                                   value="<?= htmlspecialchars($setting['setting_value'] ?? '') ?>"
                                   placeholder="<?= htmlspecialchars($label) ?>">
                            <div class="form-check mt-2">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="<?= $key ?>_public" 
                                       name="<?= $key ?>_public" 
                                       <?= ($setting['is_public'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="<?= $key ?>_public">
                                    Public setting (visible to frontend)
                                </label>
                            </div>
                            <?php if ($setting && $setting['description']): ?>
                                <div class="form-text"><?= htmlspecialchars($setting['description']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- System Configuration -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-cpu me-2"></i>System Configuration
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $systemSettings = [
                        'maintenance_mode' => ['label' => 'Maintenance Mode', 'type' => 'select', 'options' => ['0' => 'Disabled', '1' => 'Enabled']],
                        'session_timeout' => ['label' => 'Session Timeout (seconds)', 'type' => 'number'],
                        'asset_ref_prefix' => ['label' => 'Asset Reference Prefix', 'type' => 'text']
                    ];
                    
                    foreach ($systemSettings as $key => $config):
                        $setting = null;
                        foreach ($settings as $s) {
                            if ($s['setting_key'] === $key) {
                                $setting = $s;
                                break;
                            }
                        }
                    ?>
                        <div class="mb-3">
                            <label for="<?= $key ?>" class="form-label">
                                <?= htmlspecialchars($config['label']) ?>
                            </label>
                            
                            <?php if ($config['type'] === 'select'): ?>
                                <select class="form-select" id="<?= $key ?>" name="<?= $key ?>">
                                    <?php foreach ($config['options'] as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" 
                                                <?= ($setting['setting_value'] ?? '') === $value ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($config['type'] === 'number'): ?>
                                <input type="number" 
                                       class="form-control" 
                                       id="<?= $key ?>" 
                                       name="<?= $key ?>" 
                                       value="<?= htmlspecialchars($setting['setting_value'] ?? '') ?>"
                                       placeholder="<?= htmlspecialchars($config['label']) ?>">
                            <?php else: ?>
                                <input type="text" 
                                       class="form-control" 
                                       id="<?= $key ?>" 
                                       name="<?= $key ?>" 
                                       value="<?= htmlspecialchars($setting['setting_value'] ?? '') ?>"
                                       placeholder="<?= htmlspecialchars($config['label']) ?>">
                            <?php endif; ?>
                            
                            <div class="form-check mt-2">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="<?= $key ?>_public" 
                                       name="<?= $key ?>_public" 
                                       <?= ($setting['is_public'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="<?= $key ?>_public">
                                    Public setting
                                </label>
                            </div>
                            <?php if ($setting && $setting['description']): ?>
                                <div class="form-text"><?= htmlspecialchars($setting['description']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Feature Settings -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-toggles me-2"></i>Feature Settings
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $featureSettings = [
                        'qr_code_enabled' => ['label' => 'QR Code Generation', 'type' => 'select', 'options' => ['0' => 'Disabled', '1' => 'Enabled']],
                        'email_notifications' => ['label' => 'Email Notifications', 'type' => 'select', 'options' => ['0' => 'Disabled', '1' => 'Enabled']]
                    ];
                    
                    foreach ($featureSettings as $key => $config):
                        $setting = null;
                        foreach ($settings as $s) {
                            if ($s['setting_key'] === $key) {
                                $setting = $s;
                                break;
                            }
                        }
                    ?>
                        <div class="mb-3">
                            <label for="<?= $key ?>" class="form-label">
                                <?= htmlspecialchars($config['label']) ?>
                            </label>
                            
                            <select class="form-select" id="<?= $key ?>" name="<?= $key ?>">
                                <?php foreach ($config['options'] as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" 
                                            <?= ($setting['setting_value'] ?? '') === $value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="form-check mt-2">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="<?= $key ?>_public" 
                                       name="<?= $key ?>_public" 
                                       <?= ($setting['is_public'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="<?= $key ?>_public">
                                    Public setting
                                </label>
                            </div>
                            <?php if ($setting && $setting['description']): ?>
                                <div class="form-text"><?= htmlspecialchars($setting['description']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- All Settings -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-list me-2"></i>All Settings
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Setting</th>
                                    <th>Value</th>
                                    <th>Public</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($settings as $setting): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($setting['setting_key']) ?></div>
                                            <?php if ($setting['description']): ?>
                                                <small class="text-muted"><?= htmlspecialchars($setting['description']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code class="small"><?= htmlspecialchars($setting['setting_value']) ?></code>
                                        </td>
                                        <td>
                                            <?php if ($setting['is_public']): ?>
                                                <span class="badge bg-success">Yes</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($setting['updated_at']): ?>
                                                <small class="text-muted">
                                                    <?= formatDateTime($setting['updated_at']) ?>
                                                    <?php if ($setting['updated_by_name']): ?>
                                                        <br>by <?= htmlspecialchars($setting['updated_by_name']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">Never</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Save Settings</h6>
                            <small class="text-muted">Changes will be applied immediately</small>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Save Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function resetSettings() {
    if (confirm('Are you sure you want to reset all settings to their default values? This action cannot be undone.')) {
        fetch('?route=admin/resetSettings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('input[name="csrf_token"]').value
            },
            body: JSON.stringify({
                csrf_token: document.querySelector('input[name="csrf_token"]').value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while resetting settings');
        });
    }
}

// Form validation
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    const sessionTimeout = document.getElementById('session_timeout').value;
    if (sessionTimeout && (sessionTimeout < 300 || sessionTimeout > 86400)) {
        e.preventDefault();
        alert('Session timeout must be between 300 seconds (5 minutes) and 86400 seconds (24 hours)');
        return false;
    }
});
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

code {
    background-color: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'System Settings - ConstructLink™';
$pageHeader = 'System Settings';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'System Admin', 'url' => '?route=admin'],
    ['title' => 'Settings', 'url' => '?route=admin/settings']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
