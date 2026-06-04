/* ============================================================
   TDT Powersteel Dashboard — init.js
   ============================================================
   Initialization script for the main dashboard page.

   Responsibilities:
     - Validate session on page load via Auth.checkAuth()
     - Redirect to /login if session is invalid
     - No UI setup needed — RoleManager handles role badge rendering

   This file runs AFTER roles.js and BEFORE app.js in the load order.
   RoleManager.init() is called by App.init() (in app.js), not here.

   Load order (see index.html):
     auth.js → utils.js → roles.js → ... → app.js → init.js
   ============================================================ */

document.addEventListener('DOMContentLoaded', async function () {
    console.log('[INIT] Validating session...');

    try {
        // Validate session with backend — redirects to /login if invalid
        const user = await Auth.checkAuth();

        if (user) {
            console.log('[INIT] Session valid. User:', user.username, '| Role:', user.role);
            // RoleManager.init() will be called by App.init() in app.js
            // No need to render UI here — roles.js handles the badge
        } else {
            // Auth.checkAuth() already redirected to /login if session was invalid
            console.warn('[INIT] Session invalid — user should be redirected.');
        }
    } catch (err) {
        console.error('[INIT] Session check failed:', err);
        // Auth.checkAuth() handles the redirect on failure
    }
});
