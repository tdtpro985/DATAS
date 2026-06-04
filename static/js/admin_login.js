document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const loader = document.getElementById('loader');
    const errorBox = document.getElementById('errorBox');

    // UI State: Loading
    btn.disabled = true;
    btnText.style.display = 'none';
    loader.style.display = 'block';
    errorBox.style.display = 'none';

    const formData = new FormData(e.target);

    try {
        const response = await fetch((typeof BASE !== 'undefined' ? BASE : '') + '/api/v1/auth/login', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });

        const data = await response.json();

        if (response.ok) {
            // Check if it's a 202 MFA prompt first
            if (response.status === 202) {
                if (data.require_2fa) {
                    document.getElementById('loginForm').style.display = 'none';
                    document.getElementById('mfaStep').style.display = 'block';
                    document.getElementById('qrCodeContainer').style.display = 'none';
                    document.getElementById('mfaInstructions').textContent = 'Enter the 6-digit code from your authenticator app.';
                    document.getElementById('totpCode').focus();

                    btn.disabled = false;
                    btnText.style.display = 'block';
                    loader.style.display = 'none';
                    return;
                }

                if (data.setup_2fa) {
                    document.getElementById('loginForm').style.display = 'none';
                    document.getElementById('mfaStep').style.display = 'block';
                    document.getElementById('qrCodeContainer').style.display = 'block';
                    document.getElementById('qrCodeImg').src = data.qr_code;
                    document.getElementById('setupSecret').value = data.secret;
                    document.getElementById('mfaInstructions').textContent = 'Scan this QR code with an authenticator app, then enter the 6-digit code below.';
                    document.getElementById('totpCode').focus();

                    btn.disabled = false;
                    btnText.style.display = 'block';
                    loader.style.display = 'none';
                    return;
                }
            }

            // Check if the user is actually an admin or superadmin
            if (data.user.role !== 'admin' && data.user.role !== 'superadmin') {
                // If not an admin, immediately log them out by clearing cookie
                await fetch((typeof BASE !== 'undefined' ? BASE : '') + '/api/v1/auth/logout', { method: 'POST', credentials: 'include' });

                errorBox.textContent = 'Unauthorized. This portal is for Administrators only.';
                errorBox.style.display = 'block';

                // Reset button
                btn.disabled = false;
                btnText.style.display = 'block';
                loader.style.display = 'none';
                return;
            }

            // Success! Store user and redirect to admin panel
            localStorage.setItem('user', JSON.stringify(data.user));
            window.location.href = (typeof BASE !== 'undefined' ? BASE : '') + '/admin';
        } else {
            // Error
            errorBox.textContent = data.detail || 'Login failed. Please try again.';
            errorBox.style.display = 'block';

            // Reset button
            btn.disabled = false;
            btnText.style.display = 'block';
            loader.style.display = 'none';
        }
    } catch (err) {
        console.error('Login error:', err);
        errorBox.textContent = 'Connection error. Please check your network.';
        errorBox.style.display = 'block';

        // Reset button
        btn.disabled = false;
        btnText.style.display = 'block';
        loader.style.display = 'none';
    }
});

// MFA Form Submit
if (document.getElementById('mfaForm')) {
    document.getElementById('mfaForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = document.getElementById('submitMfaBtn');
        const btnText = document.getElementById('mfaBtnText');
        const loader = document.getElementById('mfaLoader');
        const errorBox = document.getElementById('errorBox');

        btn.disabled = true;
        btnText.style.display = 'none';
        loader.style.display = 'block';
        errorBox.style.display = 'none';

        const mfaFormData = new FormData(e.target);
        // Remove spaces from TOTP code for API
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

            if (response.ok) {
                // Safety check: ensure 'user' object exists in response
                if (!data.user) {
                    throw new Error('Invalid response from server: user data missing');
                }

                // Check if the user is actually an admin or superadmin
                if (data.user.role !== 'admin' && data.user.role !== 'superadmin') {
                    await fetch((typeof BASE !== 'undefined' ? BASE : '') + '/api/v1/auth/logout', { method: 'POST', credentials: 'include' });
                    errorBox.textContent = 'Unauthorized. This portal is for Administrators only.';
                    errorBox.style.display = 'block';
                    btn.disabled = false;
                    btnText.style.display = 'block';
                    loader.style.display = 'none';
                    return;
                }

                localStorage.setItem('user', JSON.stringify(data.user));
                window.location.href = (typeof BASE !== 'undefined' ? BASE : '') + '/admin';
            } else {
                errorBox.textContent = data.detail || 'Invalid authentication code.';
                errorBox.style.display = 'block';
                btn.disabled = false;
                btnText.style.display = 'block';
                loader.style.display = 'none';
            }
        } catch (err) {
            console.error('MFA Error:', err);
            errorBox.textContent = 'Connection or Server Error. Please try again.';
            errorBox.style.display = 'block';
            btn.disabled = false;
            btnText.style.display = 'block';
            loader.style.display = 'none';
        }
    });

    document.getElementById('backToLoginLink').addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('mfaStep').style.display = 'none';
        document.getElementById('loginForm').style.display = 'block';
        document.getElementById('errorBox').style.display = 'none';

        // Clear MFA inputs
        document.getElementById('totpCode').value = '';
        document.getElementById('setupSecret').value = '';
    });
}


document.addEventListener('DOMContentLoaded', () => {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle icons
            const eyeIcon = this.querySelector('.eye-icon');
            const eyeOffIcon = this.querySelector('.eye-off-icon');

            if (type === 'text') {
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
                this.setAttribute('aria-label', 'Hide password');
            } else {
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
                this.setAttribute('aria-label', 'Show password');
            }
        });
    }
});

// Page Loader Fade Out
window.addEventListener('load', () => {
    const pageLoader = document.getElementById('pageLoader');
    if (pageLoader) {
        setTimeout(() => {
            pageLoader.classList.add('fade-out');
            setTimeout(() => {
                pageLoader.style.display = 'none';
            }, 500);
        }, 300); // Small initial delay so it feels natural
    }
});
