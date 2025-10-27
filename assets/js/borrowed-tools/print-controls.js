/**
 * ConstructLink - Print Controls for Borrowed Tools Print Views
 *
 * Purpose: Handles print and close button functionality for print-specific views
 * Usage: Shared module for print-blank-form.php and batch-print.php
 */

document.addEventListener('DOMContentLoaded', function() {
    // Print button handler
    const printBtn = document.querySelector('[data-action="print"]');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }

    // Close button handler
    const closeBtn = document.querySelector('[data-action="close"]');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            window.close();
        });
    }
});

// Optional auto-print on load (disabled by default)
// Uncomment to enable automatic printing when the page loads:
// window.addEventListener('load', function() {
//     setTimeout(() => window.print(), 500);
// });
