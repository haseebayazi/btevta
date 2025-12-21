/**
 * BTEVTA UI Helper Functions
 * Provides reusable UI patterns for forms, modals, and interactions
 */

window.UIHelpers = {
    /**
     * Add loading state to all forms with submit buttons
     */
    initFormLoading: function() {
        document.querySelectorAll('form').forEach(form => {
            if (form.dataset.noLoading) return; // Skip forms with data-no-loading

            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.dataset.originalText = originalText;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                    submitBtn.disabled = true;

                    // Re-enable after 10 seconds (safety fallback)
                    setTimeout(() => {
                        if (submitBtn.disabled) {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }
                    }, 10000);
                }
            });
        });
    },

    /**
     * Initialize delete confirmation dialogs
     * Looks for forms with data-confirm attribute
     */
    initDeleteConfirmations: function() {
        document.querySelectorAll('form[data-confirm]').forEach(form => {
            form.addEventListener('submit', function(e) {
                const message = form.dataset.confirm || 'Are you sure you want to delete this item?';

                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // Also handle buttons with data-confirm-delete
        document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const message = btn.dataset.confirmDelete || 'Are you sure you want to delete this item?';

                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    },

    /**
     * Show a custom confirmation modal
     */
    confirmAction: function(options) {
        return new Promise((resolve) => {
            const {
                title = 'Confirm Action',
                message = 'Are you sure?',
                confirmText = 'Confirm',
                cancelText = 'Cancel',
                type = 'danger' // danger, warning, info
            } = options;

            const colors = {
                danger: { bg: 'bg-red-100', text: 'text-red-600', btn: 'bg-red-600 hover:bg-red-700' },
                warning: { bg: 'bg-yellow-100', text: 'text-yellow-600', btn: 'bg-yellow-600 hover:bg-yellow-700' },
                info: { bg: 'bg-blue-100', text: 'text-blue-600', btn: 'bg-blue-600 hover:bg-blue-700' }
            };
            const color = colors[type] || colors.info;

            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 overflow-y-auto';
            modal.innerHTML = `
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity confirm-backdrop"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full ${color.bg} sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="fas fa-exclamation-triangle ${color.text}"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">${title}</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">${message}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" class="confirm-yes w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 ${color.btn} text-white font-medium focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                ${confirmText}
                            </button>
                            <button type="button" class="confirm-no mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 hover:bg-gray-50 font-medium focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                ${cancelText}
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            modal.querySelector('.confirm-yes').addEventListener('click', () => {
                modal.remove();
                resolve(true);
            });

            modal.querySelector('.confirm-no').addEventListener('click', () => {
                modal.remove();
                resolve(false);
            });

            modal.querySelector('.confirm-backdrop').addEventListener('click', () => {
                modal.remove();
                resolve(false);
            });
        });
    },

    /**
     * Show toast notification
     */
    toast: function(message, type = 'success', duration = 3000) {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center transform transition-all duration-300 translate-y-full opacity-0`;
        toast.innerHTML = `
            <i class="fas ${icons[type]} mr-3"></i>
            <span>${message}</span>
            <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-y-full', 'opacity-0');
        }, 10);

        // Auto-remove
        setTimeout(() => {
            toast.classList.add('translate-y-full', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    /**
     * Initialize tooltips
     */
    initTooltips: function() {
        document.querySelectorAll('[data-tooltip]').forEach(el => {
            el.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'absolute z-50 bg-gray-800 text-white text-xs px-2 py-1 rounded shadow-lg';
                tooltip.textContent = el.dataset.tooltip;
                tooltip.id = 'tooltip-' + Math.random().toString(36).substr(2, 9);

                document.body.appendChild(tooltip);

                const rect = el.getBoundingClientRect();
                tooltip.style.top = (rect.top - tooltip.offsetHeight - 5 + window.scrollY) + 'px';
                tooltip.style.left = (rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)) + 'px';

                el.dataset.tooltipId = tooltip.id;
            });

            el.addEventListener('mouseleave', function() {
                const tooltip = document.getElementById(el.dataset.tooltipId);
                if (tooltip) tooltip.remove();
            });
        });
    },

    /**
     * Initialize all UI helpers
     */
    init: function() {
        this.initFormLoading();
        this.initDeleteConfirmations();
        this.initTooltips();
    }
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    UIHelpers.init();
});
