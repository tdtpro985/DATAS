/* ============================================================
   modal-system.js — Modern Modal & Notification System
   ============================================================
   Replaces alert(), confirm(), and Toast with modern modals
   ============================================================ */

const ModalSystem = {
    // Confirmation Modal
    confirm: function(options) {
        return new Promise((resolve) => {
            const {
                title = 'Confirm Action',
                message = 'Are you sure?',
                confirmText = 'Confirm',
                cancelText = 'Cancel',
                type = 'warning' // warning, danger, info, success
            } = options;

            // Create modal HTML
            const modalHTML = `
                <div class="modern-modal-overlay" id="confirmModal">
                    <div class="modern-modal-content modern-modal-${type}">
                        <div class="modern-modal-header">
                            <div class="modern-modal-icon">
                                ${this.getIcon(type)}
                            </div>
                            <h3 class="modern-modal-title">${this.escapeHtml(title)}</h3>
                        </div>
                        <div class="modern-modal-body">
                            <p>${this.escapeHtml(message)}</p>
                        </div>
                        <div class="modern-modal-footer">
                            <button class="modern-btn modern-btn-secondary" id="modalCancelBtn">
                                ${this.escapeHtml(cancelText)}
                            </button>
                            <button class="modern-btn modern-btn-primary" id="modalConfirmBtn">
                                ${this.escapeHtml(confirmText)}
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Add to DOM
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            const modal = document.getElementById('confirmModal');

            // Show modal with animation
            setTimeout(() => modal.classList.add('active'), 10);

            // Handle confirm
            document.getElementById('modalConfirmBtn').addEventListener('click', () => {
                this.closeModal(modal);
                resolve(true);
            });

            // Handle cancel
            document.getElementById('modalCancelBtn').addEventListener('click', () => {
                this.closeModal(modal);
                resolve(false);
            });

            // Handle overlay click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal);
                    resolve(false);
                }
            });
        });
    },

    // Success Notification
    success: function(message, duration = 3000) {
        this.showNotification({
            message,
            type: 'success',
            duration
        });
    },

    // Error Notification
    error: function(message, duration = 4000) {
        this.showNotification({
            message,
            type: 'error',
            duration
        });
    },

    // Info Notification
    info: function(message, duration = 3000) {
        this.showNotification({
            message,
            type: 'info',
            duration
        });
    },

    // Warning Notification
    warning: function(message, duration = 3000) {
        this.showNotification({
            message,
            type: 'warning',
            duration
        });
    },

    // Show Notification
    showNotification: function(options) {
        const {
            message,
            type = 'info',
            duration = 3000
        } = options;

        const notifHTML = `
            <div class="modern-notification modern-notification-${type}">
                <div class="modern-notification-icon">
                    ${this.getIcon(type)}
                </div>
                <div class="modern-notification-message">
                    ${this.escapeHtml(message)}
                </div>
                <button class="modern-notification-close">×</button>
            </div>
        `;

        // Create container if it doesn't exist
        let container = document.getElementById('notificationContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notificationContainer';
            container.className = 'modern-notification-container';
            document.body.appendChild(container);
        }

        // Add notification
        container.insertAdjacentHTML('beforeend', notifHTML);
        const notif = container.lastElementChild;

        // Show with animation
        setTimeout(() => notif.classList.add('active'), 10);

        // Handle close button
        notif.querySelector('.modern-notification-close').addEventListener('click', () => {
            this.closeNotification(notif);
        });

        // Auto close
        if (duration > 0) {
            setTimeout(() => {
                this.closeNotification(notif);
            }, duration);
        }
    },

    // Close Modal
    closeModal: function(modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    },

    // Close Notification
    closeNotification: function(notif) {
        notif.classList.remove('active');
        setTimeout(() => notif.remove(), 300);
    },

    // Get Icon
    getIcon: function(type) {
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            danger: '⚠',
            info: 'ℹ'
        };
        return icons[type] || icons.info;
    },

    // Escape HTML
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Make it globally available
window.ModalSystem = ModalSystem;
