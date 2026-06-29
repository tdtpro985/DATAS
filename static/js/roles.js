/* ============================================================
   TDT Powersteel Dashboard — Role Manager (roles.js)
   ============================================================
   Reads the authenticated user's role from the backend,
   applies it to the document body as a data attribute, and
   exposes helpers for conditional feature initialization.

   USAGE:
     await RoleManager.init();
     if (RoleManager.can('update_status')) { StatusUpdate.init(); }

   ROLE CODES (from backend /api/v1/auth/me):
     superadmin  — full access (dashboard + status + data entry + user mgmt)
     admin       — read-only monitoring
     encoder     — data entry only
     sales_rep   — full dashboard + status updates

   LOAD ORDER:
     Must load AFTER auth.js and utils.js, BEFORE app.js.
   ============================================================ */

const RoleManager = {

    // ---------- State ----------
    currentRole: null,   // Active role string (set by init())
    currentUser: null,   // Full user object returned by Auth.checkAuth()

    // ---------- Permission Map ----------
    // Defines what each role is allowed to do.
    // Used by can() to gate feature initialization in App.init().
    PERMISSIONS: {
        superadmin: ['view_dashboard', 'update_status', 'data_entry', 'user_management'],
        admin:      ['view_dashboard'],
        encoder:    ['data_entry'],
        sales_rep:  ['view_dashboard', 'update_status'],
    },

    // ---------- Role Metadata ----------
    // Human-readable display names and badge colors for each role.
    ROLE_META: {
        superadmin: { label: 'Superadmin',   bg: '#f59e0b', color: '#000' },
        admin:      { label: 'System Admin', bg: '#3b82f6', color: '#fff' },
        encoder:    { label: 'Encoder',      bg: '#10b981', color: '#fff' },
        sales_rep:  { label: 'Sales Rep',    bg: '#8b5cf6', color: '#fff' },
        // Fallback for unknown roles
        _unknown:   { label: 'Read Only',    bg: '#374151', color: '#fff' },
    },

    // ---------- init() ----------
    /**
     * Initializes the role manager.
     * Calls Auth.checkAuth() to get the current user, resolves the role,
     * applies it to the DOM, and renders the role badge in the header.
     *
     * @returns {Promise<string>} The resolved role string.
     */
    async init() {
        console.log('[ROLES] Initializing role manager...');

        // Fetch authenticated user from backend via auth.js
        const user = await Auth.checkAuth();

        if (!user) {
            // Auth failed or returned null — apply fallback role
            console.warn('[ROLES] Auth.checkAuth() returned null. Applying fallback role.');
            const fallback = this.getFallbackRole();
            this.currentRole = fallback;
            this.currentUser = null;
            this.applyRoleToDOM(fallback);
            this.renderRoleBadge(null);
            return fallback;
        }

        // Store the full user object for badge rendering
        this.currentUser = user;

        // Resolve role — use fallback if role is unknown
        const rawRole = user.role;
        if (rawRole && this.PERMISSIONS.hasOwnProperty(rawRole)) {
            this.currentRole = rawRole;
        } else {
            // Unknown role — log warning and fall back to read-only
            this.currentRole = this.getFallbackRole(rawRole);
        }

        // Apply role attribute to <body> — CSS selectors activate immediately
        this.applyRoleToDOM(this.currentRole);

        // Render the user's role badge in the header
        this.renderRoleBadge(user);

        console.log('[ROLES] Role resolved:', this.currentRole, '| User:', user.username);
        return this.currentRole;
    },

    // ---------- can(permission) ----------
    /**
     * Checks whether the current role has a given permission.
     *
     * @param {string} permission - Permission key to check (e.g. 'update_status').
     * @returns {boolean} True if the current role includes the permission.
     */
    can(permission) {
        if (!this.currentRole) return false;

        // Look up the permission list for the current role
        const allowed = this.PERMISSIONS[this.currentRole] || [];
        return allowed.includes(permission);
    },

    // ---------- applyRoleToDOM(role) ----------
    /**
     * Sets the data-role attribute on <body>.
     * CSS attribute selectors ([data-role="..."]) use this to show/hide
     * role-specific sections without any JS DOM manipulation.
     *
     * @param {string} role - The role string to apply.
     */
    applyRoleToDOM(role) {
        document.body.dataset.role = role;
        console.log('[ROLES] Applied data-role="' + role + '" to <body>.');
    },

    // ---------- renderRoleBadge(user) ----------
    /**
     * Injects the role badge HTML into #role-badge-container in the header.
     * Displays the user's full name (or username) and their human-readable role.
     *
     * Badge HTML structure:
     *   <div class="role-badge role-badge--<role>">
     *       <span class="role-badge-icon">👤</span>
     *       <span class="role-badge-name"><full_name or username></span>
     *       <span class="role-badge-label"><human readable role></span>
     *   </div>
     *
     * @param {Object|null} user - User object from Auth.checkAuth(), or null for fallback.
     */
    renderRoleBadge(user) {
        // Find the badge container in the header
        const container = document.getElementById('role-badge-container');
        if (!container) {
            console.warn('[ROLES] #role-badge-container not found in DOM. Badge not rendered.');
            return;
        }

        // Determine display name — prefer full_name, fall back to username, then 'Unknown'
        const displayName = (user && (user.full_name || user.username)) || 'Unknown';

        // Determine role for badge class and label
        const role = this.currentRole || 'admin';
        const meta = this.ROLE_META[role] || this.ROLE_META['_unknown'];

        // Build badge HTML
        const badgeHTML = `<div class="role-badge role-badge--${role}" title="${displayName} (${meta.label})">` +
            `<span class="role-badge-name">${displayName}</span>` +
            `<span class="role-badge-label">${meta.label}</span>` +
            `</div>`;

        // Inject into container (replaces any previous badge)
        container.innerHTML = badgeHTML;
        console.log('[ROLES] Role badge rendered for:', displayName, '| Role:', role);
    },

    // ---------- getFallbackRole(unknownValue) ----------
    /**
     * Returns the fallback role for any unrecognized role value.
     * Logs a console warning identifying the unknown role.
     *
     * @param {string} [unknownValue] - The unrecognized role string (optional).
     * @returns {string} Always returns 'admin' (read-only fallback).
     */
    getFallbackRole(unknownValue) {
        if (unknownValue !== undefined) {
            // Log warning with the exact unknown value
            console.warn('[AUTH] Unknown role: ' + unknownValue + '. Defaulting to read-only.');
        }
        return 'admin';
    }

};
