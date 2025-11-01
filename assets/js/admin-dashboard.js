/**
 * Admin Dashboard JavaScript
 * Interactions and dynamic features for admin portal
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    initSidebarToggle();

    // Dropdown Menus
    initDropdowns();

    // Form Validations
    initFormValidations();

    // Auto-dismiss alerts
    initAlerts();
});

/**
 * Sidebar Toggle for Mobile
 */
function initSidebarToggle() {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 992) {
                const isClickInside = sidebar.contains(event.target) || toggle.contains(event.target);
                if (!isClickInside && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            }
        });
    }
}

/**
 * Dropdown Menus
 */
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');

    dropdowns.forEach(dropdown => {
        const button = dropdown.querySelector('button, .user-button, .icon-button');
        const menu = dropdown.querySelector('.dropdown-menu');

        if (button && menu) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();

                // Close other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) {
                        m.style.display = 'none';
                    }
                });

                // Toggle current dropdown
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            });
        }
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        const isDropdown = event.target.closest('.dropdown');
        if (!isDropdown) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
}

/**
 * Form Validations
 */
function initFormValidations() {
    // Rejection form validation
    const rejectButtons = document.querySelectorAll('button[name="action"][value="reject"]');

    rejectButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const form = this.closest('form');
            const reasonField = form.querySelector('textarea[name="reason"]');

            if (reasonField && !reasonField.value.trim()) {
                e.preventDefault();
                alert('Please provide a reason for rejection.');
                reasonField.focus();
                return false;
            }
        });
    });

    // Confirm dangerous actions
    const dangerActions = document.querySelectorAll('.btn-danger');

    dangerActions.forEach(button => {
        if (button.textContent.includes('Delete') || button.textContent.includes('Remove')) {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to perform this action? This cannot be undone.')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
}

/**
 * Auto-dismiss Alerts
 */
function initAlerts() {
    const alerts = document.querySelectorAll('.alert');

    alerts.forEach(alert => {
        // Add close button if not present
        if (!alert.querySelector('.alert-close')) {
            const closeBtn = document.createElement('button');
            closeBtn.className = 'alert-close';
            closeBtn.innerHTML = '&times;';
            closeBtn.style.cssText = 'background: none; border: none; font-size: 24px; cursor: pointer; margin-left: auto; color: inherit; opacity: 0.6;';

            closeBtn.addEventListener('click', function() {
                alert.style.animation = 'slideUp 0.3s ease-out';
                setTimeout(() => alert.remove(), 300);
            });

            alert.appendChild(closeBtn);
        }

        // Auto-dismiss success alerts after 5 seconds
        if (alert.classList.contains('alert-success')) {
            setTimeout(() => {
                alert.style.animation = 'slideUp 0.3s ease-out';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }
    });
}

// Slide up animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
`;
document.head.appendChild(style);

/**
 * Confirmation Dialog
 */
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to perform this action?');
}

/**
 * Loading State for Buttons
 */
function setButtonLoading(button, loading = true) {
    if (loading) {
        button.disabled = true;
        button.classList.add('loading');
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<span>Processing...</span>';
    } else {
        button.disabled = false;
        button.classList.remove('loading');
        button.innerHTML = button.dataset.originalText || button.innerHTML;
    }
}

/**
 * Toast Notifications (Simple)
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 16px 24px;
        background: ${type === 'success' ? '#48bb78' : type === 'error' ? '#f56565' : '#4299e1'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideUp 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Data Table Search (if needed)
 */
function initTableSearch(tableId, searchInputId) {
    const table = document.getElementById(tableId);
    const searchInput = document.getElementById(searchInputId);

    if (table && searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
}

/**
 * Refresh Page Data
 */
function refreshPage() {
    window.location.reload();
}

/**
 * Export utility functions
 */
window.adminDashboard = {
    confirmAction,
    setButtonLoading,
    showToast,
    initTableSearch,
    refreshPage
};
