/* ============================================================
   status-update.js — Project Status Update Module
   ============================================================
   Adds an "Update Status" button to each row in the contractor
   ranking table and to the rotating contractor card.
   Opens a modal to change a project's sales funnel status.

   ACTIVATED BY: app.js → App.init()
                 when RoleManager.can('update_status') is true
                 Roles: sales_rep, superadmin

   DEPENDENCIES:
     - roles.js    : RoleManager.can() for permission checks
     - components.js : #ranking-body table rows (buttons injected there)
     - index.html  : Modal HTML already present — this module wires it up

   API CALLS:
     PATCH /api/v1/projects/:id/status
       Body: { "status": "<new_status>" }
       Success (200): close modal + trigger SyncManager.refreshAllWithRanking()
       Error: show inline error toast inside the modal

   MODAL HTML IDs (defined in index.html — do NOT re-render):
     #status-update-overlay   — backdrop / outer wrapper
     #status-update-close     — X close button
     #status-update-title     — modal heading
     #su-project-name         — read-only project name display
     #su-current-status       — read-only current status display
     #su-new-status           — <select> for new status value
     #su-cancel               — cancel button
     #su-save                 — save / submit button
     #su-project-id           — hidden input storing the project ID

   REQ: REQ-005, REQ-008
   ============================================================ */

const StatusUpdate = {

    // ── init ──────────────────────────────────────────────────────────
    /**
     * Initialises the Status Update module.
     * Called by App.init() when the user has the 'update_status' permission.
     *
     * - Injects event delegation listener on the ranking table body
     * - Injects an Update Status button into the rotating card section
     * - Wires up all modal open / close / save handlers
     */
    init() {
        this.injectButtonsIntoTable();
        this.injectButtonIntoCard();
        this._wireModalHandlers();
        console.log('[STATUS-UPDATE] Module initialized.');
    },

    // ── openModal ─────────────────────────────────────────────────────
    /**
     * Populates the status update modal with the given project data
     * and makes the overlay visible.
     *
     * @param {string|number} projectId     - The project's unique ID
     * @param {string}        projectName   - Human-readable project name
     * @param {string}        currentStatus - The project's current status value
     */
    openModal(projectId, projectName, currentStatus) {
        // Populate read-only display fields
        const nameEl   = document.getElementById('su-project-name');
        const statusEl = document.getElementById('su-current-status');
        const idInput  = document.getElementById('su-project-id');
        const select   = document.getElementById('su-new-status');

        if (nameEl)   nameEl.textContent   = projectName   || '—';
        if (statusEl) statusEl.textContent = currentStatus || '—';
        if (idInput)  idInput.value        = projectId     || '';

        // Pre-select the current status in the dropdown (if it exists as an option)
        if (select && currentStatus) {
            const matchingOption = select.querySelector('option[value="' + currentStatus + '"]');
            if (matchingOption) {
                select.value = currentStatus;
            } else {
                // Default to first option if current status isn't in the list
                select.selectedIndex = 0;
            }
        }

        // Clear any previous inline error toast
        this._clearErrorToast();

        // Show the overlay
        const overlay = document.getElementById('status-update-overlay');
        if (overlay) {
            overlay.classList.add('active');
        }

        console.log('[STATUS-UPDATE] Modal opened for project:', projectId, projectName);
    },

    // ── closeModal ────────────────────────────────────────────────────
    /**
     * Hides the status update overlay and clears the hidden project ID field.
     * Called by the X button, Cancel button, backdrop click, and on save success.
     */
    closeModal() {
        const overlay = document.getElementById('status-update-overlay');
        if (overlay) {
            overlay.classList.remove('active');
        }

        // Clear the stored project ID so stale data can't be accidentally submitted
        const idInput = document.getElementById('su-project-id');
        if (idInput) {
            idInput.value = '';
        }

        // Clear any lingering error toast
        this._clearErrorToast();

        console.log('[STATUS-UPDATE] Modal closed.');
    },

    // ── handleSave ────────────────────────────────────────────────────
    /**
     * Reads the selected new status and the hidden project ID, then calls
     * PATCH /api/v1/projects/:id/status.
     *
     * On success : closes the modal and triggers a full data refresh.
     * On error   : shows an inline error toast inside the modal.
     */
    async handleSave() {
        const idInput  = document.getElementById('su-project-id');
        const select   = document.getElementById('su-new-status');
        const saveBtn  = document.getElementById('su-save');

        const projectId = idInput ? idInput.value : '';
        const newStatus = select  ? select.value  : '';

        // Guard: both values must be present
        if (!projectId || !newStatus) {
            this._showErrorToast('Missing project ID or status. Please try again.');
            return;
        }

        // Disable save button while the request is in flight
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';
        }

        try {
            const response = await fetch((typeof BASE !== 'undefined' ? BASE : '') + '/api/v1/projects/' + projectId + '/status', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json'
                },
                // Session-based auth — no Authorization header needed (consistent with auth.js)
                body: JSON.stringify({ status: newStatus })
            });

            if (!response.ok) {
                // Try to extract a meaningful error message from the response body
                let errorMsg = 'Failed to update status (HTTP ' + response.status + ').';
                try {
                    const errData = await response.json();
                    if (errData && errData.detail) {
                        errorMsg = Array.isArray(errData.detail)
                            ? errData.detail.map(function (d) { return d.msg; }).join(', ')
                            : String(errData.detail);
                    } else if (errData && errData.message) {
                        errorMsg = errData.message;
                    }
                } catch (_) {
                    // Response body wasn't JSON — keep the generic message
                }
                this._showErrorToast(errorMsg);
                return;
            }

            // ── Success ──────────────────────────────────────────────
            console.log('[STATUS-UPDATE] Status updated successfully for project:', projectId);

            // Close the modal first for a snappy feel
            this.closeModal();

            // Trigger a full data refresh so the table and card reflect the new status
            if (typeof SyncManager !== 'undefined' &&
                typeof SyncManager.refreshAllWithRanking === 'function') {
                SyncManager.refreshAllWithRanking();
            }

        } catch (err) {
            // Network-level error (offline, CORS, etc.)
            console.error('[STATUS-UPDATE] Save error:', err);
            this._showErrorToast('Network error. Please check your connection and try again.');
        } finally {
            // Re-enable the save button regardless of outcome
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save';
            }
        }
    },

    // ── injectButtonsIntoTable ────────────────────────────────────────
    /**
     * Attaches a single delegated click listener to #ranking-body.
     * When a click lands on (or inside) an .update-status-btn element,
     * reads the data attributes and calls openModal().
     *
     * Using event delegation means the listener survives table re-renders
     * triggered by SyncManager — no need to re-attach on every data refresh.
     */
    injectButtonsIntoTable() {
        const tbody = document.getElementById('ranking-body');
        if (!tbody) {
            console.warn('[STATUS-UPDATE] #ranking-body not found — table delegation skipped.');
            return;
        }

        tbody.addEventListener('click', function (e) {
            // Walk up the DOM from the click target to find the button
            // (handles clicks on child elements like the ✏️ emoji span)
            const btn = e.target.closest('.update-status-btn');
            if (!btn) return; // Click was not on an update-status button

            const projectId     = btn.dataset.projectId     || '';
            const projectName   = btn.dataset.projectName   || '';
            const currentStatus = btn.dataset.currentStatus || '';

            StatusUpdate.openModal(projectId, projectName, currentStatus);
        });

        console.log('[STATUS-UPDATE] Event delegation attached to #ranking-body.');
    },

    // ── injectButtonIntoCard ──────────────────────────────────────────
    /**
     * Adds an "Update Status" button to the rotating contractor card section.
     * The button reads the currently displayed card's project data from the
     * global rotatingData array (managed by components.js) and calls openModal().
     *
     * The button is appended to #rotating-content, below the existing details.
     * It is re-evaluated on each click so it always reflects the current card.
     */
    injectButtonIntoCard() {
        const content = document.getElementById('rotating-content');
        if (!content) {
            console.warn('[STATUS-UPDATE] #rotating-content not found — card button skipped.');
            return;
        }

        // Avoid double-injection if init() is somehow called more than once
        if (document.getElementById('rc-update-status-btn')) {
            return;
        }

        // Create the button
        const btn = document.createElement('button');
        btn.id        = 'rc-update-status-btn';
        btn.className = 'update-status-btn rc-update-btn';
        btn.title     = 'Update project status';
        btn.setAttribute('aria-label', 'Update status for current project');
        btn.innerHTML = 'Update Status';

        // On click: read the currently displayed card's data from the global state
        btn.addEventListener('click', function () {
            // rotatingData and rotatingIndex are globals defined in app.js / components.js
            if (typeof rotatingData === 'undefined' || rotatingData.length === 0) {
                console.warn('[STATUS-UPDATE] No rotating card data available.');
                return;
            }

            const idx = (typeof rotatingIndex !== 'undefined') ? rotatingIndex : 0;
            const card = rotatingData[idx];

            if (!card) {
                console.warn('[STATUS-UPDATE] No card data at index:', idx);
                return;
            }

            // components.js uses: card.project_id, card.project_name, card.status
            const projectId     = card.project_id   || card.id   || '';
            const projectName   = card.project_name || card.contractor_name || '—';
            const currentStatus = card.status        || '';

            StatusUpdate.openModal(projectId, projectName, currentStatus);
        });

        // Append after the items container (or at the end of rotating-content)
        const itemsContainer = document.getElementById('rc-items');
        if (itemsContainer && itemsContainer.parentNode === content) {
            // Insert after rc-items
            itemsContainer.insertAdjacentElement('afterend', btn);
        } else {
            content.appendChild(btn);
        }

        console.log('[STATUS-UPDATE] Update Status button injected into rotating card.');
    },

    // ── _wireModalHandlers (private) ──────────────────────────────────
    /**
     * Attaches click handlers to the modal's close (X), Cancel, Save buttons,
     * and the backdrop itself (click-outside-to-close).
     *
     * Called once during init().
     */
    _wireModalHandlers() {
        // X close button
        const closeBtn = document.getElementById('status-update-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                StatusUpdate.closeModal();
            });
        }

        // Cancel button
        const cancelBtn = document.getElementById('su-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                StatusUpdate.closeModal();
            });
        }

        // Save button
        const saveBtn = document.getElementById('su-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                StatusUpdate.handleSave();
            });
        }

        // Click on the backdrop (overlay itself, not the modal box) closes the modal
        const overlay = document.getElementById('status-update-overlay');
        if (overlay) {
            overlay.addEventListener('click', function (e) {
                // Only close if the click was directly on the overlay, not on the modal content
                if (e.target === overlay) {
                    StatusUpdate.closeModal();
                }
            });
        }

        // Keyboard: Escape key closes the modal
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const overlay = document.getElementById('status-update-overlay');
                if (overlay && overlay.classList.contains('active')) {
                    StatusUpdate.closeModal();
                }
            }
        });

        console.log('[STATUS-UPDATE] Modal handlers wired.');
    },

    // ── _showErrorToast (private) ─────────────────────────────────────
    /**
     * Displays an inline error message inside the modal footer area.
     * Creates the toast element if it doesn't already exist.
     *
     * @param {string} message - The error text to display
     */
    _showErrorToast(message) {
        // Reuse existing toast element or create a new one
        let toast = document.getElementById('su-error-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id        = 'su-error-toast';
            toast.className = 'su-error-toast';
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');

            // Insert the toast before the footer buttons
            const footer = document.querySelector('.status-update-footer');
            if (footer) {
                footer.insertAdjacentElement('beforebegin', toast);
            } else {
                // Fallback: append to modal body
                const modal = document.querySelector('.status-update-modal');
                if (modal) modal.appendChild(toast);
            }
        }

        toast.textContent = message;
        toast.style.display = 'block';
    },

    // ── _clearErrorToast (private) ────────────────────────────────────
    /**
     * Hides the inline error toast if it exists.
     * Called on modal open and close to ensure a clean state.
     */
    _clearErrorToast() {
        const toast = document.getElementById('su-error-toast');
        if (toast) {
            toast.style.display = 'none';
            toast.textContent   = '';
        }
    }

};
