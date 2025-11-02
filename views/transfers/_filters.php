<?php
/**
 * Transfer Filters Partial
 *
 * Displays filter form for transfers with responsive design
 *
 * Required Parameters:
 * @param array $projects List of projects for filter dropdowns
 * @param array $transferStatuses List of valid transfer statuses from config
 */

if (!isset($projects) || !is_array($projects)) {
    $projects = [];
}

// Sanitize GET parameters using InputValidator
$statusFilter = InputValidator::validateStatus($_GET['status'] ?? '');
$typeFilter = InputValidator::validateTransferType($_GET['transfer_type'] ?? '');
$fromProject = (int)($_GET['from_project'] ?? 0);
$toProject = (int)($_GET['to_project'] ?? 0);
$dateFrom = InputValidator::sanitizeString($_GET['date_from'] ?? '');
$dateTo = InputValidator::sanitizeString($_GET['date_to'] ?? '');
$search = InputValidator::sanitizeString($_GET['search'] ?? '');

// Count active filters
$activeFilters = 0;
if (!empty($statusFilter)) $activeFilters++;
if (!empty($typeFilter)) $activeFilters++;
if ($fromProject > 0) $activeFilters++;
if ($toProject > 0) $activeFilters++;
if (!empty($dateFrom)) $activeFilters++;
if (!empty($dateTo)) $activeFilters++;
if (!empty($search)) $activeFilters++;
?>

<!-- Filters -->
<!-- Mobile: Modal, Desktop: Card -->
<div class="mb-4">
    <!-- Mobile Filter Button (Sticky) -->
    <div class="d-md-none position-sticky top-0 z-3 bg-body py-2 mb-3" style="z-index: 1020;">
        <button class="btn btn-primary w-100"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#filterOffcanvas"
                aria-label="Open transfer filters">
            <i class="bi bi-funnel me-1" aria-hidden="true"></i>
            Filters
            <?php if ($activeFilters > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $activeFilters ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Desktop: Card (always visible) -->
    <div class="card d-none d-md-block">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="bi bi-funnel me-2" aria-hidden="true"></i>Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="?route=transfers" class="row g-3">
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" aria-label="Filter by transfer status">
                        <option value="">All Statuses</option>
                        <option value="Pending Verification" <?= $statusFilter === 'Pending Verification' ? 'selected' : '' ?>>Pending Verification</option>
                        <option value="Pending Approval" <?= $statusFilter === 'Pending Approval' ? 'selected' : '' ?>>Pending Approval</option>
                        <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="In Transit" <?= $statusFilter === 'In Transit' ? 'selected' : '' ?>>In Transit</option>
                        <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Canceled" <?= $statusFilter === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="transfer_type" class="form-label">Type</label>
                    <select class="form-select" id="transfer_type" name="transfer_type" aria-label="Filter by transfer type">
                        <option value="">All Types</option>
                        <option value="temporary" <?= $typeFilter === 'temporary' ? 'selected' : '' ?>>Temporary</option>
                        <option value="permanent" <?= $typeFilter === 'permanent' ? 'selected' : '' ?>>Permanent</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="from_project" class="form-label">From Project</label>
                    <select class="form-select" id="from_project" name="from_project" aria-label="Filter by source project">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>"
                                    <?= $fromProject == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="to_project" class="form-label">To Project</label>
                    <select class="form-select" id="to_project" name="to_project" aria-label="Filter by destination project">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>"
                                    <?= $toProject == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date"
                           class="form-control"
                           id="date_from"
                           name="date_from"
                           value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>"
                           aria-label="Filter by start date">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date"
                           class="form-control"
                           id="date_to"
                           name="date_to"
                           value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>"
                           aria-label="Filter by end date">
                </div>
                <div class="col-md-8">
                    <label for="search" class="form-label">Search</label>
                    <input type="text"
                           class="form-control"
                           id="search"
                           name="search"
                           placeholder="Search by asset name, reference, or reason..."
                           value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                           aria-label="Search transfers by asset, reference, or reason">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2" aria-label="Apply filters">
                        <i class="bi bi-search me-1" aria-hidden="true"></i>Filter
                    </button>
                    <a href="?route=transfers" class="btn btn-outline-secondary" aria-label="Clear all filters">
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear
                    </a>
                </div>
            </form>
        </div><!-- End card-body -->
    </div><!-- End card (desktop) -->

    <!-- Mobile: Offcanvas Filters -->
    <div class="offcanvas offcanvas-bottom d-md-none"
         tabindex="-1"
         id="filterOffcanvas"
         aria-labelledby="filterOffcanvasLabel"
         style="height: 85vh;">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="filterOffcanvasLabel">
                <i class="bi bi-funnel me-2" aria-hidden="true"></i>Filter Transfers
            </h5>
            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="offcanvas"
                    aria-label="Close filters panel"></button>
        </div>
        <div class="offcanvas-body">
            <form method="GET" action="?route=transfers" id="mobileFilterForm">
                <div class="mb-3">
                    <label for="mobile_status" class="form-label">Status</label>
                    <select class="form-select" id="mobile_status" name="status" aria-label="Filter by transfer status">
                        <option value="">All Statuses</option>
                        <option value="Pending Verification" <?= $statusFilter === 'Pending Verification' ? 'selected' : '' ?>>Pending Verification</option>
                        <option value="Pending Approval" <?= $statusFilter === 'Pending Approval' ? 'selected' : '' ?>>Pending Approval</option>
                        <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="In Transit" <?= $statusFilter === 'In Transit' ? 'selected' : '' ?>>In Transit</option>
                        <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Canceled" <?= $statusFilter === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="mobile_transfer_type" class="form-label">Type</label>
                    <select class="form-select" id="mobile_transfer_type" name="transfer_type" aria-label="Filter by transfer type">
                        <option value="">All Types</option>
                        <option value="temporary" <?= $typeFilter === 'temporary' ? 'selected' : '' ?>>Temporary</option>
                        <option value="permanent" <?= $typeFilter === 'permanent' ? 'selected' : '' ?>>Permanent</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="mobile_from_project" class="form-label">From Project</label>
                    <select class="form-select" id="mobile_from_project" name="from_project" aria-label="Filter by source project">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>"
                                    <?= $fromProject == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="mobile_to_project" class="form-label">To Project</label>
                    <select class="form-select" id="mobile_to_project" name="to_project" aria-label="Filter by destination project">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>"
                                    <?= $toProject == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="mobile_date_from" class="form-label">Date From</label>
                    <input type="date"
                           class="form-control"
                           id="mobile_date_from"
                           name="date_from"
                           value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>"
                           aria-label="Filter by start date">
                </div>
                <div class="mb-3">
                    <label for="mobile_date_to" class="form-label">Date To</label>
                    <input type="date"
                           class="form-control"
                           id="mobile_date_to"
                           name="date_to"
                           value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>"
                           aria-label="Filter by end date">
                </div>
                <div class="mb-3">
                    <label for="mobile_search" class="form-label">Search</label>
                    <input type="text"
                           class="form-control"
                           id="mobile_search"
                           name="search"
                           placeholder="Search by asset name, reference, or reason..."
                           value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                           aria-label="Search transfers by asset, reference, or reason">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1" aria-label="Apply filters">
                        <i class="bi bi-search me-1" aria-hidden="true"></i>Apply Filters
                    </button>
                    <a href="?route=transfers" class="btn btn-outline-secondary flex-grow-1" aria-label="Clear all filters">
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear All
                    </a>
                </div>
            </form>
        </div>
    </div>
</div><!-- End filters section -->
