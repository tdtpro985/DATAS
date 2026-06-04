<?php
/* ============================================================
   pages/encode-platforms.php — Platform Leads Encoding Form
   ============================================================ */
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;

if (empty($_SESSION['user'])) {
    header('Location: ' . $base . '/login');
    exit;
}

$role     = $_SESSION['user']['role']      ?? '';
$fullName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['email'] ?? '');
$userId   = $_SESSION['user']['id']        ?? 0;

// Only encoders, admins, and superadmins may access the encode page.
if (!in_array($role, ['encoder', 'admin', 'superadmin'], true)) {
    header('Location: ' . $base . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encode Platform Leads — TDT Powersteel</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/static/images/logo_header.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/animations.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modern-theme.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/utility.css?v=2">
    <link rel="stylesheet" href="<?= $base ?>/static/css/layout.css?v=4">
    <link rel="stylesheet" href="<?= $base ?>/static/css/badges.css?v=3">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=5">
    <link rel="stylesheet" href="<?= $base ?>/static/css/toast.css?v=1">
    <link rel="stylesheet" href="<?= $base ?>/static/css/admin.css?v=23">
    <style>
        :root {
            --form-bg: rgba(20, 24, 32, 0.65);
            --form-border: rgba(139, 92, 246, 0.2);
            --input-bg: rgba(30, 36, 48, 0.85);
            --input-border: rgba(139, 92, 246, 0.2);
            --input-focus: rgba(139, 92, 246, 0.35);
        }

        html, body {
            overflow: hidden;
            height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Hide sidebar for full screen experience */
        .sidebar {
            display: none !important;
        }
        
        /* Hide any header if present */
        .header {
            display: none !important;
        }

        /* Full height layout - more compact */
        .dashboard { 
            display: flex; 
            flex-direction: column; 
            height: 100vh; 
            padding: 0.25rem; 
            overflow: hidden;
            margin-left: 0;
        }
        
        .card { 
            flex: 1; 
            display: flex;
            flex-direction: column;
            height: calc(100vh - 0.5rem);
            overflow: hidden;
            padding: 0.5rem;
            margin: 0;
        }

        /* Page header - more compact */
        .page-header {
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(139, 92, 246, 0.15);
            flex-shrink: 0;
        }
        .page-header h1 {
            font-size: 1.25rem;
            font-weight: 800;
            margin: 0;
            color: var(--text-primary);
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.1;
        }
        .page-header p {
            margin: 0.25rem 0 0;
            color: var(--text-secondary);
            font-size: 0.8rem;
            line-height: 1.2;
        }

        /* Form container - more compact */
        .form-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: calc(100% - 4rem);
        }

        form {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: 100%;
        }

        .form-content {
            flex: 1;
            overflow-y: auto;
            padding-right: 0.25rem;
            max-height: calc(100vh - 12rem);
        }

        /* Form sections - more compact */
        .form-section {
            background: var(--form-bg);
            border: 1px solid var(--form-border);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
            transition: all 0.3s ease;
        }
        .form-section:hover {
            border-color: rgba(139, 92, 246, 0.4);
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.1);
        }

        .form-section h2 {
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-section h2::before {
            content: '';
            width: 3px;
            height: 16px;
            background: linear-gradient(180deg, #8b5cf6 0%, #7c3aed 100%);
            border-radius: 2px;
        }

        /* Form grid - more compact */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
            align-items: start;
        }
        
        /* Form groups - more compact */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            opacity: 0.9;
            margin-bottom: 0.2rem;
        }
        .form-group label::after {
            content: attr(data-required);
            color: #8b5cf6;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            background: var(--input-bg);
            border: 2px solid var(--input-border);
            border-radius: 6px;
            padding: 0.5rem;
            color: var(--text-primary);
            font-size: 0.8rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            outline: none;
            min-height: 36px;
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.75rem;
        }
        .form-group input:hover,
        .form-group select:hover,
        .form-group textarea:hover {
            border-color: rgba(139, 92, 246, 0.35);
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--input-focus);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
            background: rgba(30, 36, 48, 0.95);
        }

        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238b5cf6' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px;
            padding-right: 2.5rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        /* Form buttons - more compact */
        .form-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.5rem;
            padding: 0.75rem;
            border-top: 1px solid rgba(139, 92, 246, 0.15);
            flex-wrap: wrap;
            background: var(--form-bg);
            border-radius: 8px;
            flex-shrink: 0;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.8rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            outline: none;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.35);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        /* Scrollbar styling */
        .form-content::-webkit-scrollbar {
            width: 8px;
        }
        .form-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }
        .form-content::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.3);
            border-radius: 4px;
        }
        .form-content::-webkit-scrollbar-thumb:hover {
            background: rgba(139, 92, 246, 0.5);
        }

        /* Responsive design - more compact */
        @media (max-width: 768px) {
            .dashboard {
                padding: 0.125rem;
            }
            
            .card {
                padding: 0.25rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .form-section {
                padding: 0.75rem;
            }
            
            .form-buttons {
                flex-direction: column;
                align-items: stretch;
                padding: 0.5rem;
            }
            
            .btn {
                justify-content: center;
                padding: 0.5rem 0.75rem;
            }
        }

        /* Success message - more compact */
        .success-message {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            color: #6ee7b7;
            display: none;
        }

        .success-message.show {
            display: block;
            animation: slideInDown 0.3s ease;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            max-width: 400px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideInUp 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .modal-small {
            max-width: 400px;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.25rem;
            color: var(--text-secondary);
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-body p {
            margin: 0;
            color: var(--text-primary);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body data-role="<?= htmlspecialchars($role) ?>">

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="dashboard">
    <div class="card animate-fadeInUp">
        <div class="page-header">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem;">
                <div>
                    <h1>Encode Platform Leads</h1>
                    <p>Enter platform lead information. Required fields: Source, Contact Person, Contact Number, and Email Address.</p>
                </div>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='<?= $base ?>/platforms'" style="padding: 0.4rem 0.8rem; font-size: 0.7rem;">
                    ← Back to Platform Leads
                </button>
            </div>
        </div>

        <div class="form-container">
            <div class="success-message" id="successMessage">
                Platform lead has been successfully encoded!
            </div>

            <form id="platformForm">
                <div class="form-content">
                    <div class="form-section">
                        <h2>Platform Lead Information</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="source" data-required=" *">Source</label>
                                <select id="source" name="source" required>
                                    <option value="">Select source</option>
                                    <option value="DPWH">DPWH</option>
                                    <option value="BCI">BCI</option>
                                    <option value="EGOV">EGOV</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="contactPerson" data-required=" *">Contact Person</label>
                                <input type="text" id="contactPerson" name="contact_person" required placeholder="Full name of contact person">
                            </div>
                            
                            <div class="form-group">
                                <label for="contactNumber" data-required=" *">Contact Number</label>
                                <input type="text" id="contactNumber" name="contact_number" required placeholder="Telephone or cellphone number">
                            </div>
                            
                            <div class="form-group">
                                <label for="emailAddress" data-required=" *">Email Address</label>
                                <input type="email" id="emailAddress" name="email_address" required placeholder="email@example.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="companyName">Company Name</label>
                                <input type="text" id="companyName" name="company_name" placeholder="Optional company name">
                            </div>
                            
                            <div class="form-group">
                                <label for="companyLocation">Company Location</label>
                                <input type="text" id="companyLocation" name="company_location" placeholder="Optional location">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="materialsQuantity">List of Materials and Quantity</label>
                                <textarea id="materialsQuantity" name="materials_quantity" placeholder="Optional: List materials and quantities needed..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn btn-secondary" id="clearBtn">Clear Form</button>
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <span>💾</span>
                        Save Platform Lead
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal-overlay" id="errorModal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h2>Error</h2>
            <button class="modal-close" onclick="closeErrorModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="errorMessage">An error occurred.</p>
        </div>
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
            <button type="button" class="btn btn-primary" onclick="closeErrorModal()">
                OK
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('platformForm');
    const submitBtn = document.getElementById('submitBtn');
    const clearBtn = document.getElementById('clearBtn');
    const successMessage = document.getElementById('successMessage');
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validate required fields
        const requiredFields = ['source', 'contactPerson', 'contactNumber', 'emailAddress'];
        let isValid = true;
        
        for (const fieldId of requiredFields) {
            const field = document.getElementById(fieldId);
            if (!field.value.trim()) {
                field.style.borderColor = '#ef4444';
                isValid = false;
                setTimeout(() => {
                    field.style.borderColor = '';
                }, 3000);
            }
        }
        
        if (!isValid) {
            showErrorModal('Please fill in all required fields.');
            return;
        }
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>⏳</span> Saving...';
        
        try {
            const formData = new FormData(form);
            
            const response = await fetch('<?= $base ?>/api/v1/platforms/create', {
                method: 'POST',
                body: formData
            });
            
            let result;
            try {
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error('Invalid response from server');
            }
            
            if (result.success) {
                // Show success message
                successMessage.classList.add('show');
                
                // Reset form
                form.reset();
                
                // Hide success message after 3 seconds
                setTimeout(() => {
                    successMessage.classList.remove('show');
                }, 3000);
            } else {
                throw new Error(result.message || 'Failed to save platform lead');
            }
        } catch (error) {
            console.error('Error:', error);
            showErrorModal('Error saving platform lead: ' + error.message);
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span>💾</span> Save Platform Lead';
        }
    });
    
    // Clear form
    clearBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to clear the form?')) {
            form.reset();
            successMessage.classList.remove('show');
        }
    });
    
    // Email validation
    const emailField = document.getElementById('emailAddress');
    emailField.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email && !isValidEmail(email)) {
            this.style.borderColor = '#ef4444';
            setTimeout(() => {
                this.style.borderColor = '';
            }, 3000);
        }
    });
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Modal functions
    function showErrorModal(message) {
        const modal = document.getElementById('errorModal');
        const messageEl = document.getElementById('errorMessage');
        messageEl.textContent = message;
        modal.classList.add('active');
    }
    
    function closeErrorModal() {
        const modal = document.getElementById('errorModal');
        modal.classList.remove('active');
    }
    
    // Auto-resize textarea
    const textarea = document.getElementById('materialsQuantity');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Modal event listeners
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeErrorModal();
        }
    });
    
    // Close modal on overlay click
    document.getElementById('errorModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeErrorModal();
        }
    });
});
</script>

</body>
</html>