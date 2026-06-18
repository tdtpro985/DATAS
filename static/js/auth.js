/* ============================================================
   TDT Powersteel Dashboard — auth.js
   ============================================================
   Shared authentication utilities used across all pages.

   Responsibilities:
     - checkAuth()  : Validate session with backend, return user object
     - logout()     : Clear session + redirect to /login
     - getUser()    : Read cached user from localStorage
     - isAdmin()    : Quick role check helper

   Role-based redirect after login is handled in login.js.
   Role-based UI gating is handled in roles.js → RoleManager.

   API:
     GET  /api/v1/auth/me     — validate session, return user
     POST /api/v1/auth/logout — invalidate session cookie
   ============================================================ */

const Auth = {

    /**
     * Returns the cached user object from localStorage.
     * This is a fast synchronous read — does NOT hit the backend.
     * Use checkAuth() for a validated server-side check.
     *
     * @returns {Object|null} The cached user object, or null if not found.
     */
    getUser() {
        try {
            const raw = localStorage.getItem('user');
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    },

    /**
     * Returns true if the cached user has the 'admin' or 'superadmin' role.
     * Quick check — does not validate with the server.
     *
     * @returns {boolean}
     */
    isAdmin() {
        const user = this.getUser();
        return user && (user.role === 'admin' || user.role === 'superadmin');
    },

    /**
     * Validates the current session with the backend by calling GET /api/v1/auth/me.
     *
     * On success : Updates localStorage cache and returns the user object.
     * On failure : Calls logout() which redirects to /login.
     *
     * @returns {Promise<Object|null>} The authenticated user object, or null on failure.
     */
    async checkAuth() {
        try {
            const response = await fetch((typeof BASE !== 'undefined' ? BASE : '/new-dashboard') + '/api/v1/auth/me', {
                credentials: 'include' // send session cookie
            });

            if (!response.ok) {
                // 401 or 403 — session invalid or expired
                console.warn('[AUTH] Session invalid (HTTP ' + response.status + '). Redirecting to login.');
                this.logout();
                return null;
            }

            const user = await response.json();

            // Update the localStorage cache with fresh data from the server
            localStorage.setItem('user', JSON.stringify(user));

            return user;
        } catch (e) {
            console.error('[AUTH] checkAuth() network error:', e);
            // On network error, return cached user if available (offline tolerance)
            return this.getUser();
        }
    },

    /**
     * Logs out the current user.
     * Calls POST /api/v1/auth/logout to invalidate the session cookie,
     * clears localStorage, then redirects to /login.
     *
     * @returns {Promise<void>}
     */
    async logout() {
        try {
            await fetch((typeof BASE !== 'undefined' ? BASE : '/new-dashboard') + '/api/v1/auth/logout', {
                method: 'POST',
                credentials: 'include'
            });
        } catch (e) {
            console.error('[AUTH] Logout request failed:', e);
        } finally {
            localStorage.removeItem('user');
            window.location.href = (typeof BASE !== 'undefined' ? BASE : '') + '/login';
        }
    },

    /**
     * Determines the correct redirect URL for a given role after login.
     *
     * Role → Redirect mapping:
     *   superadmin → /admin  (full admin panel access)
     *   admin      → /admin  (admin panel, read-only)
     *   encoder    → /       (dashboard, data entry panel shown)
     *   sales_rep  → /       (full dashboard + status update)
     *   (unknown)  → /       (dashboard, read-only fallback)
     *
     * @param {string} role - The user's role string from the backend.
     * @returns {string} The URL to redirect to after successful login.
     */
    getRedirectForRole(role) {
        const b = (typeof BASE !== 'undefined') ? BASE : '';
        switch (role) {
            case 'superadmin':
            case 'admin':
                return b + '/admin';
            case 'encoder':
                return b + '/encode';
            case 'sales_rep':
            default:
                return b + '/';
        }
    }
};

/* ============================================================
   Global fetch interceptor
   ============================================================
   Intercepts all fetch() calls across the app.
   On 401: redirects to /login (unless already on a login page).
   On 403: logs a warning (access denied — no redirect).
   On 503 with maintenance flag: auto-logout non-superadmins.
   ============================================================ */
(function installFetchInterceptor() {
    const originalFetch = window.fetch;

    window.fetch = async function (...args) {
        const response = await originalFetch.apply(this, args);

        if (response.status === 401) {
            const path = window.location.pathname;
            const b = (typeof BASE !== 'undefined') ? BASE : '';

            // Detect login pages — works whether app is at root or /new-dashboard/
            const isLoginPage = path === b + '/login' ||
                path === b + '/admin/login' ||
                path.endsWith('/login') ||
                path.endsWith('/admin/login');

            if (!isLoginPage) {
                console.warn('[AUTH] 401 received — session expired. Redirecting to login.');
                localStorage.removeItem('user');
                window.location.href = b + '/login';
            } else {
                // Suppress redirect on login pages — the form handles the error display
                console.log('[AUTH] 401 on login page — suppressing redirect.');
            }
        } else if (response.status === 403) {
            console.warn('[AUTH] 403 Forbidden — access denied to:', args[0]);
        } else if (response.status === 503) {
            // Check if it's maintenance mode
            try {
                const data = await response.clone().json();
                if (data && data.maintenance) {
                    const user = Auth.getUser();
                    if (user && user.role !== 'superadmin') {
                        console.warn('[AUTH] 503 Maintenance mode detected — logging out non-superadmin.');
                        localStorage.removeItem('user');
                        const b = (typeof BASE !== 'undefined') ? BASE : '';
                        window.location.href = b + '/login';
                        // Don't return the original response — throw to prevent further processing
                        throw new Error('Maintenance mode');
                    }
                }
            } catch (e) {
                if (e.message === 'Maintenance mode') throw e;
                // Not a maintenance response, allow normal flow
            }
        }

        return response;
    };
})();
