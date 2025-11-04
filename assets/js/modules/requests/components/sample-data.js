/**
 * ConstructLink™ Request Sample Data Module
 *
 * Provides sample data filling functions for quick testing and demonstration.
 * Used in create request form.
 *
 * @module RequestSampleData
 * @version 2.0.0
 */

(function() {
    'use strict';

    /**
     * Sample data configurations
     */
    const sampleData = {
        material: {
            type: 'Material',
            description: 'Portland cement bags for foundation work. Need high-grade cement suitable for structural applications.',
            quantity: '50',
            unit: 'bags',
            estimatedCost: '15000',
            urgency: 'Urgent'
        },
        tool: {
            type: 'Tool',
            description: 'Heavy-duty angle grinder with cutting discs for metal fabrication work.',
            quantity: '2',
            unit: 'pcs',
            estimatedCost: '8000',
            urgency: 'Normal'
        },
        service: {
            type: 'Service',
            description: 'Professional electrical inspection and certification for completed electrical installations.',
            quantity: '',
            unit: '',
            estimatedCost: '25000',
            urgency: 'Normal'
        }
    };

    /**
     * Fill form with sample data
     * @param {string} type - Sample data type (material, tool, service)
     */
    function fillSampleData(type) {
        const data = sampleData[type];
        if (!data) {
            console.warn(`Unknown sample data type: ${type}`);
            return;
        }

        // Get form fields
        const fields = {
            requestType: document.getElementById('request_type'),
            description: document.getElementById('description'),
            quantity: document.getElementById('quantity'),
            unit: document.getElementById('unit'),
            estimatedCost: document.getElementById('estimated_cost'),
            urgency: document.getElementById('urgency')
        };

        // Fill fields
        if (fields.requestType) {
            fields.requestType.value = data.type;
            // Trigger change event to show/hide dependent fields
            fields.requestType.dispatchEvent(new Event('change'));
        }

        if (fields.description) fields.description.value = data.description;
        if (fields.quantity) fields.quantity.value = data.quantity;
        if (fields.unit) fields.unit.value = data.unit;
        if (fields.estimatedCost) fields.estimatedCost.value = data.estimatedCost;
        if (fields.urgency) fields.urgency.value = data.urgency;

        // Announce to screen readers
        announceChange(`Sample ${type} request data filled`);
    }

    /**
     * Announce changes to screen readers
     * @param {string} message - Message to announce
     */
    function announceChange(message) {
        const announcement = document.getElementById('aria-announcements');
        if (announcement) {
            announcement.textContent = message;
        }
    }

    /**
     * Initialize sample data buttons
     */
    function init() {
        // Find sample data buttons
        const materialBtn = document.querySelector('[onclick*="fillSampleMaterial"]');
        const toolBtn = document.querySelector('[onclick*="fillSampleTool"]');
        const serviceBtn = document.querySelector('[onclick*="fillSampleService"]');

        // Remove inline handlers and attach proper event listeners
        if (materialBtn) {
            materialBtn.removeAttribute('onclick');
            materialBtn.addEventListener('click', () => fillSampleData('material'));
        }

        if (toolBtn) {
            toolBtn.removeAttribute('onclick');
            toolBtn.addEventListener('click', () => fillSampleData('tool'));
        }

        if (serviceBtn) {
            serviceBtn.removeAttribute('onclick');
            serviceBtn.addEventListener('click', () => fillSampleData('service'));
        }
    }

    // Expose globally for backward compatibility
    window.RequestSampleData = {
        fillMaterial: () => fillSampleData('material'),
        fillTool: () => fillSampleData('tool'),
        fillService: () => fillSampleData('service')
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
