<?php
/**
 * Transfer Form Fields Partial (for create.php)
 *
 * Displays transfer form fields with validation
 *
 * Required Parameters:
 * @param array $projects List of projects
 * @param array $formData Form data array (for pre-filling)
 */

$projects = $projects ?? [];
$formData = $formData ?? [];
?>

<!-- Project Selection -->
<div class="row mb-4">
    <div class="col-md-6">
        <label for="from_project" class="form-label">From Project <span class="text-danger">*</span></label>

        <!-- Hidden input for form submission -->
        <input type="hidden" name="from_project" x-model="formData.from_project">

        <!-- Display field (readonly) -->
        <select class="form-select"
                id="from_project_display"
                disabled
                aria-label="Source project (auto-filled from selected asset)">
            <option value="">Auto-filled from selected asset</option>
            <template x-for="project in projects" :key="project.id">
                <option :value="project.id" :selected="project.id == formData.from_project" x-text="project.name"></option>
            </template>
        </select>
        <div class="form-text text-success" x-show="formData.from_project">
            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Auto-filled from selected asset
        </div>
        <div class="invalid-feedback">Please select an asset to auto-fill the source project.</div>
    </div>

    <div class="col-md-6">
        <label for="to_project" class="form-label">To Project <span class="text-danger">*</span></label>
        <select class="form-select"
                id="to_project"
                name="to_project"
                x-model="formData.to_project"
                required
                aria-label="Destination project for transfer">
            <option value="">Select To Project</option>
            <template x-for="project in filteredToProjects" :key="project.id">
                <option :value="project.id" x-text="project.name"></option>
            </template>
        </select>
        <div class="form-text" x-show="formData.from_project && !autoFilledToProject">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Source project excluded from list
        </div>
        <div class="form-text text-success" x-show="autoFilledToProject">
            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Auto-filled with your assigned project
        </div>
        <div class="invalid-feedback">Please select the destination project.</div>
    </div>
</div>

<!-- Transfer Details -->
<div class="row mb-4">
    <div class="col-md-6">
        <label for="transfer_type" class="form-label">Transfer Type <span class="text-danger">*</span></label>
        <select class="form-select"
                id="transfer_type"
                name="transfer_type"
                x-model="formData.transfer_type"
                required
                aria-label="Select transfer type">
            <option value="">Select Type</option>
            <option value="temporary" <?= (($formData['transfer_type'] ?? '') == 'temporary') ? 'selected' : '' ?>>Temporary</option>
            <option value="permanent" <?= (($formData['transfer_type'] ?? '') == 'permanent') ? 'selected' : '' ?>>Permanent</option>
        </select>
        <div class="invalid-feedback">Please select the transfer type.</div>
    </div>

    <div class="col-md-6">
        <label for="transfer_date" class="form-label">Transfer Date <span class="text-danger">*</span></label>
        <input type="date"
               class="form-control"
               id="transfer_date"
               name="transfer_date"
               x-model="formData.transfer_date"
               required
               aria-label="Select transfer date">
        <div class="form-text">When should this transfer take place?</div>
        <div class="invalid-feedback">Please provide the transfer date.</div>
    </div>
</div>

<!-- Expected Return Date (for temporary transfers) -->
<div class="row mb-4" id="expected_return_row" x-show="formData.transfer_type === 'temporary'" style="display: none;">
    <div class="col-md-6">
        <label for="expected_return" class="form-label">Expected Return Date <span class="text-danger">*</span></label>
        <input type="date"
               class="form-control"
               id="expected_return"
               name="expected_return"
               x-model="formData.expected_return"
               :required="formData.transfer_type === 'temporary'"
               aria-label="Select expected return date for temporary transfer">
        <div class="form-text">When should the asset be returned?</div>
        <div class="invalid-feedback">Please provide the expected return date for temporary transfers.</div>
    </div>
    <div class="col-md-6">
        <div class="alert alert-info p-2 mt-4" role="alert">
            <small>
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                <strong>Temporary Transfer:</strong> Asset will be returned to the original project after use.
            </small>
        </div>
    </div>
</div>

<!-- Reason for Transfer -->
<div class="mb-4">
    <label for="reason" class="form-label">Reason for Transfer <span class="text-danger">*</span></label>
    <textarea class="form-control"
              id="reason"
              name="reason"
              rows="3"
              required
              placeholder="Explain why this transfer is needed"
              x-model="formData.reason"
              aria-label="Enter reason for transfer"></textarea>
    <div class="form-text">Provide a clear justification for the transfer</div>
    <div class="invalid-feedback">Please provide a reason for the transfer.</div>
</div>

<!-- Additional Notes -->
<div class="mb-4">
    <label for="notes" class="form-label">Additional Notes</label>
    <textarea class="form-control"
              id="notes"
              name="notes"
              rows="2"
              placeholder="Any additional information about this transfer"
              x-model="formData.notes"
              aria-label="Enter additional notes (optional)"></textarea>
    <div class="form-text">Optional additional information</div>
</div>

<!-- Form Actions -->
<div class="d-flex justify-content-between">
    <button type="button"
            class="btn btn-outline-secondary"
            onclick="history.back()"
            aria-label="Cancel and go back">
        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancel
    </button>
    <button type="submit"
            class="btn btn-primary"
            aria-label="Create transfer request">
        <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Create Transfer Request
    </button>
</div>
