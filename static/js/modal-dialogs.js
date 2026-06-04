/**
 * Modern Modal Dialogs - Replaces alert(), confirm(), prompt()
 */

// ============================================================
// Confirm Dialog
// ============================================================
function showConfirm(message, title = 'Confirm') {
    return new Promise((resolve) => {
        const modalHtml = `
            <div class="modal-overlay active" id="confirmModal" style="z-index: 10000;">
                <div class="modal-content" style="max-width: 500px; animation: modalSlideIn 0.3s ease;">
                    <div class="modal-header">
                        <h2>${escapeHtml(title)}</h2>
                        <button class="modal-close" onclick="closeConfirmModal(false)">&times;</button>
                    </div>
                    <div class="modal-body" style="padding: 2rem 1.5rem;">
                        <p style="color: var(--text-primary); font-size: 1rem; line-height: 1.6; margin: 0;">
                            ${escapeHtml(message)}
                        </p>
                    </div>
                    <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button class="btn-secondary" onclick="closeConfirmModal(false)">Cancel</button>
                        <button class="btn-primary" onclick="closeConfirmModal(true)">OK</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        window.closeConfirmModal = (result) => {
            const modal = document.getElementById('confirmModal');
            if (modal) {
                modal.remove();
            }
            delete window.closeConfirmModal;
            resolve(result);
        };
    });
}

// ============================================================
// Prompt Dialog
// ============================================================
function showPrompt(message, title = 'Input', defaultValue = '') {
    return new Promise((resolve) => {
        const modalHtml = `
            <div class="modal-overlay active" id="promptModal" style="z-index: 10000;">
                <div class="modal-content" style="max-width: 500px; animation: modalSlideIn 0.3s ease;">
                    <div class="modal-header">
                        <h2>${escapeHtml(title)}</h2>
                        <button class="modal-close" onclick="closePromptModal(null)">&times;</button>
                    </div>
                    <div class="modal-body" style="padding: 2rem 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-primary); font-weight: 500;">
                            ${escapeHtml(message)}
                        </label>
                        <input type="text" id="promptInput" value="${escapeHtml(defaultValue)}" 
                               style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: var(--text-primary); font-size: 1rem;"
                               onkeypress="if(event.key==='Enter') closePromptModal(document.getElementById('promptInput').value)">
                    </div>
                    <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button class="btn-secondary" onclick="closePromptModal(null)">Cancel</button>
                        <button class="btn-primary" onclick="closePromptModal(document.getElementById('promptInput').value)">OK</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Focus input
        setTimeout(() => {
            const input = document.getElementById('promptInput');
            if (input) {
                input.focus();
                input.select();
            }
        }, 100);
        
        window.closePromptModal = (result) => {
            const modal = document.getElementById('promptModal');
            if (modal) {
                modal.remove();
            }
            delete window.closePromptModal;
            resolve(result);
        };
    });
}

// ============================================================
// Alert Dialog
// ============================================================
function showAlert(message, title = 'Notice', type = 'info') {
    return new Promise((resolve) => {
        const icons = {
            info: 'ℹ️',
            success: '✅',
            warning: '⚠️',
            error: '❌'
        };
        
        const colors = {
            info: 'var(--blue-500)',
            success: 'var(--green-500)',
            warning: 'var(--orange-500)',
            error: 'var(--red-500)'
        };
        
        const modalHtml = `
            <div class="modal-overlay active" id="alertModal" style="z-index: 10000;">
                <div class="modal-content" style="max-width: 500px; animation: modalSlideIn 0.3s ease;">
                    <div class="modal-header">
                        <h2 style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1.5rem;">${icons[type] || icons.info}</span>
                            ${escapeHtml(title)}
                        </h2>
                        <button class="modal-close" onclick="closeAlertModal()">&times;</button>
                    </div>
                    <div class="modal-body" style="padding: 2rem 1.5rem;">
                        <p style="color: var(--text-primary); font-size: 1rem; line-height: 1.6; margin: 0;">
                            ${escapeHtml(message)}
                        </p>
                    </div>
                    <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button class="btn-primary" onclick="closeAlertModal()" style="background: ${colors[type] || colors.info};">OK</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        window.closeAlertModal = () => {
            const modal = document.getElementById('alertModal');
            if (modal) {
                modal.remove();
            }
            delete window.closeAlertModal;
            resolve();
        };
    });
}

// ============================================================
// Helper Function (check if not already defined)
// ============================================================
if (typeof escapeHtml === 'undefined') {
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// ============================================================
// Modal Animation CSS
// ============================================================
const style = document.createElement('style');
style.textContent = `
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
`;
document.head.appendChild(style);
