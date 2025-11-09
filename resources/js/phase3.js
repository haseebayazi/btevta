/**
 * Phase 3 - JavaScript Utilities
 * Common JavaScript functions for Phase 3 views
 */

// Toast notifications
function showToast(message, type = 'success') {
    // Implementation depends on your toast library (e.g., Toastr, SweetAlert2)
    console.log(`[${type}] ${message}`);
}

// Confirm delete action
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Export table to Excel
function exportTableToExcel(tableId, filename = 'export.xlsx') {
    // Requires a library like SheetJS or TableExport
    console.log(`Exporting table ${tableId} to ${filename}`);
}

// Print page
function printPage() {
    window.print();
}

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    return form.checkValidity();
}

// Date formatter
function formatDate(date, format = 'M d, Y') {
    // Simple date formatting
    const d = new Date(date);
    return d.toLocaleDateString('en-US');
}

// Number formatter
function formatNumber(number, decimals = 0) {
    return number.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Loading state handler
function setLoading(buttonId, loading = true) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = loading;
        button.innerHTML = loading 
            ? '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...'
            : button.dataset.originalText;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Store original button text
    document.querySelectorAll('button').forEach(btn => {
        btn.dataset.originalText = btn.innerHTML;
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert-dismissible').forEach(alert => {
            alert.remove();
        });
    }, 5000);
});