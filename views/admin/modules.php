<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

        <a href="?route=admin" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Admin
        </a>
    </div>
</div>

<!-- Module Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Available Modules</h6>
                        <h3 class="mb-0"><?= isset($availableModules) ? count($availableModules) : 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-boxes display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Installed Modules</h6>
                        <h3 class="mb-0">
                            <?php 
                            $installedCount = 0;
                            if (!empty($availableModules)) {
                                foreach ($availableModules as $module) {
                                    if ($module['installed']) $installedCount++;
                                }
                            }
                            echo $installedCount;
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle display-6"></i>
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
                        <h6 class="card-title">Enabled Modules</h6>
                        <h3 class="mb-0">
                            <?php 
                            $enabledCount = 0;
                            if (!empty($availableModules)) {
                                foreach ($availableModules as $module) {
                                    if ($module['enabled']) $enabledCount++;
                                }
                            }
                            echo $enabledCount;
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-power display-6"></i>
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
                        <h6 class="card-title">System Status</h6>
                        <h5 class="mb-0">Active</h5>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-gear-fill display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Available Modules -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-grid-3x3-gap me-2"></i>Available Modules
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($availableModules)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        No modules found. Place module directories in the <code>/modules/</code> folder.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($availableModules as $moduleName => $module): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 <?= $module['enabled'] ? 'border-success' : ($module['installed'] ? 'border-warning' : 'border-secondary') ?>">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="card-title mb-0">
                                            <i class="bi bi-puzzle-fill me-2"></i>
                                            <?= htmlspecialchars($module['display_name'] ?? $module['name']) ?>
                                        </h6>
                                        <div class="module-status">
                                            <?php if ($module['enabled']): ?>
                                                <span class="badge bg-success">Enabled</span>
                                            <?php elseif ($module['installed']): ?>
                                                <span class="badge bg-warning">Installed</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Available</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text text-muted small mb-2">
                                            <?= htmlspecialchars($module['description'] ?? 'No description available') ?>
                                        </p>
                                        
                                        <div class="module-info">
                                            <div class="row text-center mb-2">
                                                <div class="col-4">
                                                    <small class="text-muted d-block">Version</small>
                                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($module['version'] ?? 'Unknown') ?></span>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted d-block">Author</small>
                                                    <span class="badge bg-light text-dark" title="<?= htmlspecialchars($module['author'] ?? 'Unknown') ?>">
                                                        <?= htmlspecialchars(strlen($module['author'] ?? '') > 10 ? substr($module['author'], 0, 10) . '...' : ($module['author'] ?? 'Unknown')) ?>
                                                    </span>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted d-block">Size</small>
                                                    <span class="badge bg-light text-dark">
                                                        <?php
                                                        $moduleDir = $module['path'] ?? '';
                                                        if (is_dir($moduleDir)) {
                                                            $size = 0;
                                                            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($moduleDir));
                                                            foreach ($iterator as $file) {
                                                                if ($file->isFile()) {
                                                                    $size += $file->getSize();
                                                                }
                                                            }
                                                            echo $size > 1024 * 1024 ? round($size / (1024 * 1024), 1) . ' MB' : round($size / 1024, 1) . ' KB';
                                                        } else {
                                                            echo 'Unknown';
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Module Features -->
                                        <div class="module-features mb-3">
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php if ($module['has_config']): ?>
                                                    <span class="badge bg-info" title="Has configuration">
                                                        <i class="bi bi-gear-fill"></i> Config
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($module['has_routes']): ?>
                                                    <span class="badge bg-info" title="Has custom routes">
                                                        <i class="bi bi-signpost-2-fill"></i> Routes
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($module['has_views']): ?>
                                                    <span class="badge bg-info" title="Has views">
                                                        <i class="bi bi-eye-fill"></i> Views
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($module['dependencies'])): ?>
                                                    <span class="badge bg-warning" title="Has dependencies">
                                                        <i class="bi bi-link-45deg"></i> Dependencies
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Module Actions -->
                                        <div class="module-actions">
                                            <?php if (!$module['installed']): ?>
                                                <button class="btn btn-success btn-sm w-100" onclick="installModule('<?= $moduleName ?>')">
                                                    <i class="bi bi-download me-1"></i>Install Module
                                                </button>
                                            <?php else: ?>
                                                <div class="btn-group w-100" role="group">
                                                    <?php if ($module['enabled']): ?>
                                                        <button class="btn btn-warning btn-sm" onclick="toggleModule('<?= $moduleName ?>', false)">
                                                            <i class="bi bi-pause-circle me-1"></i>Disable
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-success btn-sm" onclick="toggleModule('<?= $moduleName ?>', true)">
                                                            <i class="bi bi-play-circle me-1"></i>Enable
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-danger btn-sm" onclick="uninstallModule('<?= $moduleName ?>')">
                                                        <i class="bi bi-trash me-1"></i>Uninstall
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Module Development Guide -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-code-slash me-2"></i>Module Development Guide
                </h6>
            </div>
            <div class="card-body">
                <p class="mb-3">To create a custom module for ConstructLink™, follow these steps:</p>
                
                <div class="accordion" id="moduleGuideAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingStructure">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStructure">
                                1. Module Structure
                            </button>
                        </h2>
                        <div id="collapseStructure" class="accordion-collapse collapse" data-bs-parent="#moduleGuideAccordion">
                            <div class="accordion-body">
                                <p>Create the following directory structure in <code>/modules/your-module-name/</code>:</p>
                                <pre class="bg-light p-3 rounded"><code>modules/
└── your-module-name/
    ├── module.json       (Required: Module manifest)
    ├── YourModule.php    (Optional: Main module class)
    ├── config.php        (Optional: Module configuration)
    ├── routes.php        (Optional: Custom routes)
    ├── install.php       (Optional: Installation script)
    ├── install.sql       (Optional: Database schema)
    ├── uninstall.php     (Optional: Uninstallation script)
    └── views/            (Optional: Module views)
        └── index.php</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingManifest">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseManifest">
                                2. Module Manifest (module.json)
                            </button>
                        </h2>
                        <div id="collapseManifest" class="accordion-collapse collapse" data-bs-parent="#moduleGuideAccordion">
                            <div class="accordion-body">
                                <p>Create a <code>module.json</code> file with the following structure:</p>
                                <pre class="bg-light p-3 rounded"><code>{
    "name": "your-module-name",
    "display_name": "Your Module Name",
    "version": "1.0.0",
    "description": "Brief description of your module",
    "author": "Your Name",
    "main_class": "YourModule",
    "dependencies": {
        "php": "7.4.0",
        "constructlink": "1.0.0"
    },
    "permissions": [
        {
            "name": "your_module.view",
            "display_name": "View Your Module",
            "description": "Allow viewing module content"
        }
    ],
    "menu_items": [
        {
            "title": "Your Module",
            "url": "?route=your-module",
            "icon": "bi-puzzle",
            "permission": "your_module.view"
        }
    ]
}</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingClass">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClass">
                                3. Main Module Class
                            </button>
                        </h2>
                        <div id="collapseClass" class="accordion-collapse collapse" data-bs-parent="#moduleGuideAccordion">
                            <div class="accordion-body">
                                <p>Create a main class file (optional but recommended):</p>
                                <pre class="bg-light p-3 rounded"><code>&lt;?php
class YourModule {
    public function init() {
        // Module initialization code
        // Called when module is loaded
    }
    
    public function install() {
        // Installation logic
        return true;
    }
    
    public function uninstall() {
        // Cleanup logic
        return true;
    }
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Module Tips
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-lightbulb text-warning me-2 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Naming Convention</h6>
                                <small class="text-muted">Use kebab-case for module directory names and PascalCase for class names.</small>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-shield-check text-success me-2 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Security</h6>
                                <small class="text-muted">Always validate input and use proper authentication checks in your modules.</small>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-database text-info me-2 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Database</h6>
                                <small class="text-muted">Use install.sql for database schema changes and proper migration practices.</small>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-arrow-clockwise text-primary me-2 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Updates</h6>
                                <small class="text-muted">Implement proper uninstall cleanup to avoid orphaned data.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function installModule(moduleName) {
    if (!confirm(`Install module "${moduleName}"?\n\nThis will:\n- Run the module installation scripts\n- Add module to the database\n- Enable the module by default\n\nProceed with installation?`)) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Installing...';
    
    fetch('?route=admin/installModule', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            module_name: moduleName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Module installed successfully!\n\n' + data.message);
            location.reload();
        } else {
            alert('Module installation failed:\n\n' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during module installation');
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function uninstallModule(moduleName) {
    if (!confirm(`Uninstall module "${moduleName}"?\n\nThis will:\n- Run the module uninstallation scripts\n- Remove module data from the database\n- Disable the module\n\nWARNING: This action cannot be undone!\n\nProceed with uninstallation?`)) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Uninstalling...';
    
    fetch('?route=admin/uninstallModule', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            module_name: moduleName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Module uninstalled successfully!\n\n' + data.message);
            location.reload();
        } else {
            alert('Module uninstallation failed:\n\n' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during module uninstallation');
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function toggleModule(moduleName, enabled) {
    const action = enabled ? 'enable' : 'disable';
    
    if (!confirm(`${enabled ? 'Enable' : 'Disable'} module "${moduleName}"?\n\nThe module will be ${enabled ? 'activated' : 'deactivated'} system-wide.`)) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = `<i class="bi bi-hourglass-split me-1"></i>${enabled ? 'Enabling' : 'Disabling'}...`;
    
    fetch('?route=admin/toggleModule', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            module_name: moduleName,
            enabled: enabled
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Module ${action}d successfully!\n\n` + data.message);
            location.reload();
        } else {
            alert(`Module ${action} failed:\n\n` + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(`An error occurred during module ${action}`);
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function refreshModules() {
    location.reload();
}
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

.module-features .badge {
    font-size: 0.7em;
}

.accordion-button {
    font-size: 0.9em;
}

.accordion-body pre {
    font-size: 0.8em;
}

.btn:disabled {
    opacity: 0.6;
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
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Module Management - ConstructLink™';
$pageHeader = 'Module Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'System Admin', 'url' => '?route=admin'],
    ['title' => 'Modules', 'url' => '?route=admin/modules']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>