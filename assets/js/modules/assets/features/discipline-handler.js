/**
 * Discipline Handler Module
 *
 * Manages discipline selection for assets including primary discipline
 * and multi-discipline checkboxes with hierarchical support.
 *
 * @module assets/features/discipline-handler
 * @version 3.0.0
 * @since Phase 3 Refactoring - Inline JavaScript Extraction
 */

import { csrfToken } from '../core/asset-form-base.js';

/**
 * All disciplines storage (including hierarchy)
 * @type {Array}
 */
let allDisciplines = [];

/**
 * Initialize discipline handling system
 *
 * @param {Object} options - Configuration options
 * @param {string} options.categorySelectId - ID of category select element
 * @param {string} options.disciplineSectionId - ID of discipline section container
 */
export async function initializeDisciplineHandling(options = {}) {
    const {
        categorySelectId = 'category_id',
        disciplineSectionId = 'discipline-section'
    } = options;

    const categorySelect = document.getElementById(categorySelectId);
    const disciplineSection = document.getElementById(disciplineSectionId);

    if (!categorySelect || !disciplineSection) {
        console.warn('Required elements for discipline handling not found');
        return;
    }

    // Load all disciplines on initialization
    await loadAllDisciplines();

    // Load disciplines when category changes
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        if (categoryId) {
            loadDisciplinesForCategory(categoryId);
        } else {
            disciplineSection.style.display = 'none';
            clearDisciplines();
        }
    });

    // Initialize primary discipline change handler
    const primaryDisciplineSelect = document.getElementById('primary_discipline');
    if (primaryDisciplineSelect) {
        primaryDisciplineSelect.addEventListener('change', function() {
            updateDisciplineCheckboxes();
        });
    }

    // If category is already selected, load disciplines
    if (categorySelect.value) {
        await loadDisciplinesForCategory(categorySelect.value);
    } else {
        // Show disciplines section by default
        disciplineSection.style.display = 'block';
        // Retry loading if data not available yet
        setTimeout(async () => {
            if (allDisciplines.length === 0) {
                await loadAllDisciplines();
            }
            populateAllDisciplines();
        }, 500);
    }

}

/**
 * Load all disciplines from API
 *
 * @returns {Promise<Array>} - Array of all disciplines
 */
export async function loadAllDisciplines() {

    try {
        const response = await fetch('?route=api/assets/disciplines&action=list', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            }
        });


        const text = await response.text();

        const data = JSON.parse(text);

        if (data.success) {
            allDisciplines = data.data;
            return allDisciplines;
        } else {
            console.error('Disciplines API error:', data.message);
            return [];
        }
    } catch (error) {
        console.error('Error loading disciplines:', error);
        return [];
    }
}

/**
 * Load disciplines for specific category
 *
 * @param {number|string} categoryId - Category ID
 * @returns {Promise<Array>} - Array of disciplines for category
 */
export async function loadDisciplinesForCategory(categoryId) {
    const disciplineSection = document.getElementById('discipline-section');
    const disciplineCheckboxes = document.getElementById('discipline-checkboxes');

    // Show loading state
    if (disciplineCheckboxes) {
        disciplineCheckboxes.innerHTML = '<div class="text-muted"><i class="bi bi-arrow-clockwise"></i> Loading disciplines...</div>';
    }

    try {
        const response = await fetch(`?route=api/assets/disciplines&action=by_category&category_id=${categoryId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            }
        });

        const data = await response.json();

        if (data.success && data.data.length > 0) {
            if (disciplineSection) {
                disciplineSection.style.display = 'block';
            }
            populateDisciplines(data.data);
            return data.data;
        } else {
            // If no specific disciplines for category, show all main disciplines
            if (disciplineSection) {
                disciplineSection.style.display = 'block';
            }
            populateAllDisciplines();
            return allDisciplines;
        }
    } catch (error) {
        console.warn('Could not load disciplines for category:', error);
        // Fallback to showing all disciplines
        if (disciplineSection) {
            disciplineSection.style.display = 'block';
        }
        populateAllDisciplines();
        return allDisciplines;
    }
}

/**
 * Populate discipline dropdowns and checkboxes
 *
 * @param {Array} disciplines - Array of discipline objects
 */
export function populateDisciplines(disciplines) {
    const primaryDisciplineSelect = document.getElementById('primary_discipline');
    const disciplineCheckboxes = document.getElementById('discipline-checkboxes');

    if (!primaryDisciplineSelect || !disciplineCheckboxes) return;

    // Get currently selected primary discipline
    const selectedPrimaryId = primaryDisciplineSelect.value;

    // Clear existing options
    primaryDisciplineSelect.innerHTML = '<option value="">Select Primary Use</option>';
    disciplineCheckboxes.innerHTML = '';

    // Populate primary discipline dropdown
    disciplines.forEach(discipline => {
        const option = document.createElement('option');
        option.value = discipline.id;
        option.textContent = discipline.name;
        if (discipline.has_primary_use) {
            option.textContent += ' (Recommended)';
        }
        primaryDisciplineSelect.appendChild(option);
    });

    // Restore previously selected primary discipline
    if (selectedPrimaryId) {
        primaryDisciplineSelect.value = selectedPrimaryId;
    }

    // Only populate checkboxes if primary discipline is selected
    if (selectedPrimaryId) {
        disciplines.forEach(discipline => {
            // Skip this discipline if it's selected as primary
            if (discipline.id == selectedPrimaryId) {
                return;
            }

            const checkboxDiv = createDisciplineCheckbox(discipline);
            disciplineCheckboxes.appendChild(checkboxDiv);
        });
    } else {
        // Show message when no primary is selected
        disciplineCheckboxes.innerHTML = '<div class="text-muted"><i class="bi bi-info-circle me-2"></i>Please select a Primary Discipline first</div>';
    }
}

/**
 * Populate all disciplines (no category filter)
 */
export function populateAllDisciplines() {
    const primaryDisciplineSelect = document.getElementById('primary_discipline');
    const disciplineCheckboxes = document.getElementById('discipline-checkboxes');

    if (!primaryDisciplineSelect || !disciplineCheckboxes) return;

    if (!allDisciplines.length) {
        disciplineCheckboxes.innerHTML = '<div class="text-muted">Loading disciplines...</div>';
        return;
    }

    // Get currently selected primary discipline BEFORE clearing
    const selectedPrimaryId = primaryDisciplineSelect.value;

    // Clear and repopulate primary dropdown
    primaryDisciplineSelect.innerHTML = '<option value="">Select Primary Use</option>';

    // Add all disciplines to primary dropdown
    allDisciplines.forEach(discipline => {
        const option = document.createElement('option');
        option.value = discipline.id;
        option.textContent = discipline.name;
        primaryDisciplineSelect.appendChild(option);

        // Add sub-disciplines to dropdown
        if (discipline.children && discipline.children.length > 0) {
            discipline.children.forEach(child => {
                const childOption = document.createElement('option');
                childOption.value = child.id;
                childOption.textContent = `  ${child.name}`;
                primaryDisciplineSelect.appendChild(childOption);
            });
        }
    });

    // Restore the previously selected primary discipline
    if (selectedPrimaryId) {
        primaryDisciplineSelect.value = selectedPrimaryId;
    }

    // Now populate the checkboxes based on the current selection
    populateCheckboxes(selectedPrimaryId);
}

/**
 * Populate discipline checkboxes (excluding primary)
 *
 * @param {string|number|null} excludePrimaryId - Primary discipline ID to exclude
 */
function populateCheckboxes(excludePrimaryId) {
    const disciplineCheckboxes = document.getElementById('discipline-checkboxes');
    if (!disciplineCheckboxes) return;

    // Clear checkboxes
    disciplineCheckboxes.innerHTML = '';

    if (!excludePrimaryId) {
        // Show message when no primary is selected
        disciplineCheckboxes.innerHTML = '<div class="text-muted"><i class="bi bi-info-circle me-2"></i>Please select a Primary Discipline first</div>';
        return;
    }

    // Populate checkboxes excluding the selected primary
    allDisciplines.forEach(discipline => {
        // Add main discipline checkbox (exclude if it's the primary)
        if (discipline.id != excludePrimaryId) {
            const checkboxDiv = createDisciplineCheckbox(discipline, true);
            disciplineCheckboxes.appendChild(checkboxDiv);
        }

        // Add sub-disciplines checkboxes
        if (discipline.children && discipline.children.length > 0) {
            discipline.children.forEach(child => {
                // Add checkbox (exclude if it's the primary)
                if (child.id != excludePrimaryId) {
                    const childDiv = createDisciplineCheckbox(child, false, true);
                    disciplineCheckboxes.appendChild(childDiv);
                }
            });
        }
    });
}

/**
 * Create discipline checkbox element
 *
 * @param {Object} discipline - Discipline object
 * @param {boolean} isMain - Is main discipline (not sub-discipline)
 * @param {boolean} isChild - Is child discipline (indented)
 * @returns {HTMLElement} - Checkbox div element
 */
function createDisciplineCheckbox(discipline, isMain = true, isChild = false) {
    const checkboxDiv = document.createElement('div');
    checkboxDiv.className = isChild ? 'form-check mb-1 ms-3' : 'form-check mb-2';

    const checkbox = document.createElement('input');
    checkbox.className = 'form-check-input';
    checkbox.type = 'checkbox';
    checkbox.id = `discipline_${discipline.id}`;
    checkbox.name = 'disciplines[]';
    checkbox.value = discipline.id;

    const label = document.createElement('label');
    label.className = isMain ? 'form-check-label fw-bold' : 'form-check-label text-muted';
    label.setAttribute('for', checkbox.id);

    if (discipline.usage_count) {
        label.innerHTML = `
            <strong>${discipline.name}</strong>
            <small class="text-muted d-block">(${discipline.usage_count} related tools)</small>
        `;
    } else {
        label.textContent = discipline.name;
    }

    checkboxDiv.appendChild(checkbox);
    checkboxDiv.appendChild(label);

    return checkboxDiv;
}

/**
 * Update discipline checkboxes when primary discipline changes
 */
export function updateDisciplineCheckboxes() {
    const primaryDisciplineSelect = document.getElementById('primary_discipline');
    if (!primaryDisciplineSelect) return;

    // Get the newly selected primary discipline
    const selectedPrimaryId = primaryDisciplineSelect.value;

    // Repopulate checkboxes excluding the selected primary
    if (allDisciplines && allDisciplines.length > 0) {
        populateCheckboxes(selectedPrimaryId);
    }
}

/**
 * Clear disciplines (dropdown and checkboxes)
 */
export function clearDisciplines() {
    const primaryDisciplineSelect = document.getElementById('primary_discipline');
    const disciplineCheckboxes = document.getElementById('discipline-checkboxes');

    if (primaryDisciplineSelect) {
        primaryDisciplineSelect.innerHTML = '<option value="">Select Primary Use</option>';
    }

    if (disciplineCheckboxes) {
        disciplineCheckboxes.innerHTML = '';
    }
}

/**
 * Get all loaded disciplines
 *
 * @returns {Array} - All disciplines
 */
export function getAllDisciplines() {
    return allDisciplines;
}

/**
 * Get selected disciplines (primary + checkboxes)
 *
 * @returns {Object} - Object with primaryId and secondaryIds
 */
export function getSelectedDisciplines() {
    const primaryDisciplineSelect = document.getElementById('primary_discipline');
    const disciplineCheckboxes = document.querySelectorAll('input[name="disciplines[]"]:checked');

    const primaryId = primaryDisciplineSelect ? primaryDisciplineSelect.value : null;
    const secondaryIds = Array.from(disciplineCheckboxes).map(cb => cb.value);

    return {
        primaryId,
        secondaryIds
    };
}
