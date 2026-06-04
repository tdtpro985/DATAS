/* ============================================================
   TDT Powersteel Dashboard — login.js
   ============================================================
   Handles the user login page flow:
     1. Username + password form submission
     2. MFA (TOTP) verification step
     3. Role-based redirect after successful login
     4. Forgot password modal

   Role → Redirect mapping (mirrors auth.js → getRedirectForRole):
     superadmin → /admin
     admin      → /admin
     encoder    → /       (dashboard, data entry panel shown by roles.js)
     sales_rep  → /       (full dashboard + status update)
     (unknown)  → /       (read-only fallback)

   API endpoints used:
     POST /api/v1/auth/login        — credential login
     POST /api/v1/auth/verify-2fa   — TOTP verification
     POST /api/v1/auth/logout       — clear session (used on role mismatch)
     POST /api/v1/auth/request-password-reset — forgot password
   ============================================================ */

/* ── Helpers ──────────────────────────────────────────────── */

/**
 * Sets the submit button into a loading state.
 * @param {HTMLButtonElement} btn
 * @param {HTMLElement} textEl
 * @param {HTMLElement} loaderEl
 */
function setLoading(btn, textEl, loaderEl) {
    btn.disabled = true;
    textEl.style.display = 'none';
    loaderEl.style.display = 'block';
}

/**
 * Resets the submit button back to its idle state.
 * @param {HTMLButtonElement} btn
 * @param {HTMLElement} textEl
 * @param {HTMLElement} loaderEl
 */
function clearLoading(btn, textEl, loaderEl) {
    btn.disabled = false;
    textEl.style.display = 'block';
    loaderEl.style.display = 'none';
}

/**
 * Shows an error message in the given error box element.
 * @param {HTMLElement} box
 * @param {string} msg
 */
function showError(box, msg) {
    box.textContent = msg;
    box.style.display = 'block';
}

/**
 * Hides the error box.
 * @param {HTMLElement} box
 */
function hideError(box) {
    box.style.display = 'none';
    box.textContent = '';
}

/**
 * Determines the redirect URL based on the user's role.
 * Mirrors auth.js → getRedirectForRole().
 * Uses the BASE variable injected by the PHP page.
 *
 * @param {string} role - Role string from the backend.
 * @returns {string} URL to redirect to.
 */
function getRedirectForRole(role) {
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

/* ── Login form ───────────────────────────────────────────── */

document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const btn       = document.getElementById('submitBtn');
    const btnText   = document.getElementById('btnText');
    const loader    = document.getElementById('loader');
    const errorBox  = document.getElementById('errorBox');
    const adminLink = document.getElementById('adminPortalLink');

    setLoading(btn, btnText, loader);
    hideError(errorBox);
    adminLink.style.display = 'none';

    const formData = new FormData(e.target);

    try {
        const response = await fetch((typeof BASE !== 'undefined' ? BASE : '') + '/api/v1/auth/login', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });

        const data = await response.json();

        if (!response.ok) {
            // Login failed — show error from backend
            showError(errorBox, data.detail || 'Login failed. Please check your credentials.');
            clearLoading(btn, btnText, loader);
            return;
        }

        // ── 202: MFA required ──────────────────────────────
        if (response.status === 202) {
            clearLoading(btn, btnText, loader);

            // Hide the login form
            document.getElementById('loginForm').style.display = 'none';
            const forgotRow = document.getElementById('forgotPasswordLink');
            if (forgotRow && forgotRow.parentElement) {
                forgotRow.parentElement.style.display = 'none';
            }
            adminLink.style.display = 'none';

            const mfaStep       = document.getElementById('mfaStep');
            const qrContainer   = document.getElementById('qrCodeContainer');
            const mfaInstructions = document.getElementById('mfaInstructions');

            mfaStep.style.display = 'block';

            if (data.setup_2fa) {
                // First-time 2FA setup — show QR code
                document.getElementById('qrCodeImg').src = data.qr_code || '';
                document.getElementById('setupSecret').value = data.secret || '';
                qrContainer.style.display = 'block';
                mfaInstructions.textContent = 'Scan this QR code with an authenticator app, then enter the 6-digit code below.';
            } else {
                // Existing 2FA — just ask for the code
                qrContainer.style.display = 'none';
                mfaInstructions.textContent = 'Enter the 6-digit code from your authenticator app.';
            }

            document.getElementById('totpCode').focus();
            return;
        }

        // ── 200: Login success ─────────────────────────────
        const user = data.user;
        if (!user) {
            showError(errorBox, 'Unexpected server response. Please try again.');
            clearLoading(btn, btnText, loader);
            return;
        }

        // Admin users must use the Admin Portal (/admin/login)
        if (user.role === 'admin' || user.role === 'superadmin') {
            // Log them out of this context and redirect to admin portal
            await fetch((typeof BASE !== 'undefined' ? BASE : '') + '/api/v1/auth/logout', { method: 'POST', credentials: 'include' });
            showError(errorBox, 'Admins must sign in through the Admin Portal.');
            adminLink.style.display = 'block';
            clearLoading(btn, btnText, loader);
            return;
        }

        // Cache user and redirect based on role
        localStorage.setItem('user', JSON.stringify(user));
        window.location.href = getRedirectForRole(user.role);

    } catch (err) {
        console.error('[LOGIN] Submit error:', err);
        showError(errorBox, 'Connection error. Please check your network and try again.');
        clearLoading(btn, btnText, loader);
    }
});

/* ── MFA form ─────────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', function () {
    const mfaForm = document.getElementById('mfaForm');
    if (!mfaForm) return;

    mfaForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn       = document.getElementById('submitMfaBtn');
        const btnText   = document.getElementById('mfaBtnText');
        const loader    = document.getElementById('mfaLoader');
        const errorBox  = document.getElementById('errorBox');

        setLoading(btn, btnText, loader);
        hideError(errorBox);

        const mfaFormData = new FormData(e.target);
        // Strip spaces from the TOTP code before sending
        const rawTotp = mfaFormData.get('totp_code');
        if (rawTotp) {
            mfaFormData.set('totp_code', rawTotp.replace(/\s/g, ''));
        }

        try {
            const response = await fetch((typeof BASE !== 'undefined' ? BASE : '') + '/api/v1/auth/verify-2fa', {
                method: 'POST',
                credentials: 'include',
                body: mfaFormData
            });

            const data = await response.json();

            if (!response.ok) {
                showError(errorBox, data.detail || 'Invalid authentication code. Please try again.');
                clearLoading(btn, btnText, loader);
                return;
            }

            const user = data.user;
            if (!user) {
                showError(errorBox, 'Unexpected server response. Please try again.');
                clearLoading(btn, btnText, loader);
                return;
            }

            // Cache user and redirect
            localStorage.setItem('user', JSON.stringify(user));
            window.location.href = getRedirectForRole(user.role);

        } catch (err) {
            console.error('[LOGIN] MFA error:', err);
            showError(errorBox, 'Connection error. Please try again.');
            clearLoading(btn, btnText, loader);
        }
    });

    // Back to login link
    const backLink = document.getElementById('backToLoginLink');
    if (backLink) {
        backLink.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('mfaStep').style.display = 'none';
            document.getElementById('loginForm').style.display = 'block';
            const forgotRow = document.getElementById('forgotPasswordLink');
            if (forgotRow && forgotRow.parentElement) {
                forgotRow.parentElement.style.display = 'block';
            }
            hideError(document.getElementById('errorBox'));
            document.getElementById('totpCode').value = '';
            document.getElementById('setupSecret').value = '';
        });
    }
});

/* ── Password visibility toggle ───────────────────────────── */

document.addEventListener('DOMContentLoaded', function () {
    const toggle   = document.getElementById('togglePassword');
    const pwInput  = document.getElementById('password');
    if (!toggle || !pwInput) return;

    toggle.addEventListener('click', function () {
        const isPassword = pwInput.getAttribute('type') === 'password';
        pwInput.setAttribute('type', isPassword ? 'text' : 'password');

        const eyeOn  = toggle.querySelector('.eye-icon');
        const eyeOff = toggle.querySelector('.eye-off-icon');
        if (eyeOn)  eyeOn.style.display  = isPassword ? 'none'  : 'block';
        if (eyeOff) eyeOff.style.display = isPassword ? 'block' : 'none';
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });

    // Also allow keyboard activation (Enter / Space)
    toggle.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggle.click();
        }
    });
});

/* ── Forgot password modal ─────────────────────────────────── */

document.addEventListener('DOMContentLoaded', function () {
    const forgotLink    = document.getElementById('forgotPasswordLink');
    const resetModal    = document.getElementById('resetModal');
    const cancelBtn     = document.getElementById('cancelResetBtn');
    const resetForm     = document.getElementById('resetForm');
    const resetErrorBox = document.getElementById('resetErrorBox');
    const resetSuccess  = document.getElementById('resetSuccessBox');

    if (!forgotLink || !resetModal) return;

    // Open modal
    forgotLink.addEventListener('click', function (e) {
        e.preventDefault();
        resetModal.style.display = 'flex';
        resetForm.reset();
        hideError(resetErrorBox);
        resetSuccess.style.display = 'none';
    });

    // Close modal
    cancelBtn.addEventListener('click', function () {
        resetModal.style.display = 'none';
    });

    // Close on backdrop click
    resetModal.addEventListener('click', function (e) {
        if (e.target === resetModal) resetModal.style.display = 'none';
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && resetModal.style.display === 'flex') {
            resetModal.style.display = 'none';
        }
    });

    // Submit reset request
    resetForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn     = document.getElementById('submitResetBtn');
        const btnText = document.getElementById('resetBtnText');
        const loader  = document.getElementById('resetLoader');

        setLoading(btn, btnText, loader);
        hideError(resetErrorBox);
        resetSuccess.style.display = 'none';

        const username = document.getElementById('resetEmail').value.trim();

        try {
            const response = await fetch((typeof BASE !== 'undefined' ? BASE : '') + '/api/v1/auth/request-password-reset', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: username })
            });

            const data = await response.json();

            if (response.ok) {
                resetSuccess.textContent = data.message || 'Reset request submitted. Contact your administrator.';
                resetSuccess.style.display = 'block';
                resetForm.reset();
                // Auto-close after 3 seconds
                setTimeout(function () {
                    resetModal.style.display = 'none';
                }, 3000);
            } else {
                showError(resetErrorBox, data.detail || 'Failed to submit reset request.');
            }
        } catch (err) {
            showError(resetErrorBox, 'Connection error. Please try again.');
        } finally {
            clearLoading(btn, btnText, loader);
        }
    });
});

/* ── Page loader fade-out ──────────────────────────────────── */

window.addEventListener('load', function () {
    const pageLoader = document.getElementById('pageLoader');
    if (!pageLoader) return;
    setTimeout(function () {
        pageLoader.classList.add('fade-out');
        setTimeout(function () {
            pageLoader.style.display = 'none';
        }, 400);
    }, 250);
});
