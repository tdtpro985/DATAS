/* ============================================================
   activity-logs.js — System Activity Logs
   ============================================================ */

const ActivityLogs = {
    filters: {
        actionType: '',
        userId: '',
        entityType: '',
        startDate: '',
        endDate: '',
        page: 1,
        pageSize: 50
    },

    async init() {
        // Validate session
        const user = await Auth.checkAuth();
        if (!user) return;

        // Load users for filter
        await this.loadUsers();

        // Setup tab listeners
        this.setupTabs();

        // Load initial logs
        await this.loadLogs();

        console.log('[ACTIVITY LOGS] Initialized');
    },

    setupTabs() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                // Add active to clicked tab
                e.target.classList.add('active');
                
                // Set filter
                const type = e.target.getAttribute('data-type');
                this.filters.actionType = type;
                this.filters.page = 1;
                
                // Reload logs
                this.loadLogs();
            });
        });
    },

    async loadUsers() {
        try {
            const res = await fetch(`${BASE}/api/v1/users`, {
                credentials: 'include'
            });
            const users = await res.json();
            const userArray = Array.isArray(users) ? users : (users.users || []);
            
            const select = document.getElementById('userFilter');
            userArray.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = `${user.full_name || user.email} (${user.role})`;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('[ACTIVITY LOGS] Load users error:', error);
        }
    },

    async loadLogs() {
        try {
            const params = new URLSearchParams({
                page: this.filters.page,
                size: this.filters.pageSize
            });

            if (this.filters.actionType) params.append('action_type', this.filters.actionType);
            if (this.filters.userId) params.append('user_id', this.filters.userId);
            if (this.filters.entityType) params.append('entity_type', this.filters.entityType);
            if (this.filters.startDate) params.append('start_date', this.filters.startDate);
            if (this.filters.endDate) params.append('end_date', this.filters.endDate);

            const res = await fetch(`${BASE}/api/v1/activity-logs?${params}`, {
                credentials: 'include'
            });

            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }

            const data = await res.json();
            this.renderLogs(data.logs);
            this.renderPagination(data.pagination);

        } catch (error) {
            console.error('[ACTIVITY LOGS] Load error:', error);
            Toast.error('Failed to load activity logs');
            document.getElementById('logsTableBody').innerHTML = `
                <tr><td colspan="6" style="text-align:center;color:var(--text-secondary);">Failed to load logs</td></tr>
            `;
        }
    },

    renderLogs(logs) {
        const tbody = document.getElementById('logsTableBody');
        
        if (!logs || logs.length === 0) {
            tbody.innerHTML = `
                <tr><td colspan="6" style="text-align:center;color:var(--text-secondary);">No activity logs found</td></tr>
            `;
            return;
        }

        const rows = logs.map(log => {
            const actionClass = this.getActionClass(log.action_type);
            const actionLabel = this.formatActionType(log.action_type);
            const timestamp = new Date(log.created_at).toLocaleString();
            
            return `
                <tr>
                    <td style="white-space:nowrap;">${this.escapeHtml(timestamp)}</td>
                    <td>
                        <div style="font-weight:600;">${this.escapeHtml(log.user_name || 'Unknown')}</div>
                        <div style="font-size:0.75rem;color:var(--text-secondary);">${this.escapeHtml(log.user_role || '')}</div>
                    </td>
                    <td>
                        <span class="action-badge ${actionClass}">${actionLabel}</span>
                    </td>
                    <td>
                        <div style="font-weight:600;text-transform:capitalize;">${this.escapeHtml(log.entity_type || 'N/A')}</div>
                        ${log.entity_id ? `<div style="font-size:0.75rem;color:var(--text-secondary);">ID: ${log.entity_id}</div>` : ''}
                    </td>
                    <td>${this.escapeHtml(log.description)}</td>
                    <td style="font-family:monospace;font-size:0.8rem;">${this.escapeHtml(log.ip_address || 'N/A')}</td>
                </tr>
            `;
        }).join('');

        tbody.innerHTML = rows;
    },

    renderPagination(pagination) {
        const controls = document.getElementById('paginationControls');
        controls.style.display = 'flex';

        const start = ((pagination.page - 1) * pagination.pageSize) + 1;
        const end = Math.min(pagination.page * pagination.pageSize, pagination.total);

        document.getElementById('recordRange').textContent = `${start}-${end}`;
        document.getElementById('totalRecords').textContent = pagination.total;
        document.getElementById('currentPage').textContent = pagination.page;
        document.getElementById('totalPages').textContent = pagination.totalPages;

        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        prevBtn.disabled = pagination.page === 1;
        nextBtn.disabled = pagination.page === pagination.totalPages;
    },

    getActionClass(actionType) {
        if (actionType.includes('CREATE')) return 'create';
        if (actionType.includes('UPDATE')) return 'update';
        if (actionType.includes('DELETE')) return 'delete';
        if (actionType.includes('LOGIN')) return 'login';
        if (actionType.includes('LOGOUT')) return 'logout';
        if (actionType.includes('ASSIGN')) return 'assign';
        if (actionType.includes('ARCHIVE')) return 'archive';
        return 'update';
    },

    formatActionType(actionType) {
        return actionType.replace(/_/g, ' ').toLowerCase()
            .replace(/\b\w/g, l => l.toUpperCase());
    },

    applyFilters() {
        this.filters.userId = document.getElementById('userFilter').value;
        this.filters.entityType = document.getElementById('entityFilter').value;
        this.filters.startDate = document.getElementById('startDate').value;
        this.filters.endDate = document.getElementById('endDate').value;
        this.filters.page = 1;

        // Validate date range
        if (this.filters.startDate && this.filters.endDate) {
            const start = new Date(this.filters.startDate);
            const end = new Date(this.filters.endDate);
            
            if (start > end) {
                Toast.error('Start date cannot be after end date');
                return;
            }
            
            // Check if date range is too large (max 1 year)
            const diffDays = (end - start) / (1000 * 60 * 60 * 24);
            if (diffDays > 365) {
                Toast.warning('Date range cannot exceed 1 year. Please select a smaller range.');
                return;
            }
        }

        // Validate individual dates
        const today = new Date();
        today.setHours(23, 59, 59, 999);
        
        if (this.filters.startDate) {
            const startDate = new Date(this.filters.startDate);
            if (startDate > today) {
                Toast.error('Start date cannot be in the future');
                return;
            }
        }
        
        if (this.filters.endDate) {
            const endDate = new Date(this.filters.endDate);
            if (endDate > today) {
                Toast.error('End date cannot be in the future');
                return;
            }
        }

        this.loadLogs();
    },

    prevPage() {
        if (this.filters.page > 1) {
            this.filters.page--;
            this.loadLogs();
        }
    },

    nextPage() {
        this.filters.page++;
        this.loadLogs();
    },

    escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    ActivityLogs.init();
});
