<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Set session for testing
$_SESSION['user'] = [
    'role' => 'admin',
    'full_name' => 'Test Admin',
    'email' => 'admin@test.com'
];

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base = $scriptDir;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Notification Test</title>
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <style>
        body { padding: 2rem; background: #0f172a; color: white; font-family: Inter, sans-serif; }
        .test-container { max-width: 800px; margin: 0 auto; }
        .demo-section { 
            background: #1e293b; 
            border: 1px solid #334155; 
            border-radius: 0.75rem; 
            padding: 1.5rem; 
            margin: 1rem 0; 
        }
        .test-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            margin: 0.5rem;
            transition: all 0.2s ease;
        }
        .test-btn:hover { background: #2563eb; }
        .test-btn.success { background: #10b981; }
        .test-btn.success:hover { background: #059669; }
        .test-btn.error { background: #ef4444; }
        .test-btn.error:hover { background: #dc2626; }
        .test-btn.warning { background: #f59e0b; }
        .test-btn.warning:hover { background: #d97706; }
    </style>
</head>
<body data-role="admin">
    <div class="test-container">
        <h1>🔔 Modal Notification System Test</h1>
        <p>This page tests the new modal-based notification system that replaces browser alerts.</p>
        
        <div class="demo-section">
            <h2>Notification Modals (Replaces alert())</h2>
            <p>These replace the browser's ugly alert boxes with beautiful custom modals:</p>
            
            <button class="test-btn success" onclick="showNotificationModal('Success', 'Successfully assigned 3 project(s) to John Doe!', 'success')">
                Show Success Message
            </button>
            
            <button class="test-btn error" onclick="showNotificationModal('Error', 'Assignment failed: Network connection error', 'error')">
                Show Error Message
            </button>
            
            <button class="test-btn warning" onclick="showNotificationModal('Warning', 'Please select at least one project to assign.', 'warning')">
                Show Warning Message
            </button>
            
            <button class="test-btn" onclick="showNotificationModal('Information', 'Loading sales representatives...', 'info')">
                Show Info Message
            </button>
        </div>

        <div class="demo-section">
            <h2>Confirmation Modals (Replaces confirm())</h2>
            <p>These replace the browser's confirm dialogs with custom modals:</p>
            
            <button class="test-btn" onclick="testAssignmentConfirmation()">
                Test Assignment Confirmation
            </button>
            
            <button class="test-btn error" onclick="testUnassignConfirmation()">
                Test Unassign Confirmation
            </button>
            
            <button class="test-btn" onclick="testCustomConfirmation()">
                Test Custom Confirmation
            </button>
        </div>

        <div class="demo-section">
            <h2>Features</h2>
            <ul>
                <li>✅ <strong>No more browser alerts</strong> - All replaced with custom modals</li>
                <li>✅ <strong>Color-coded by type</strong> - Success (green), Error (red), Warning (orange), Info (blue)</li>
                <li>✅ <strong>Animated</strong> - Smooth fade-in and slide-up animations</li>
                <li>✅ <strong>Keyboard support</strong> - Escape to cancel, Enter to confirm</li>
                <li>✅ <strong>Auto-focus</strong> - Buttons are automatically focused for accessibility</li>
                <li>✅ <strong>Consistent design</strong> - Matches the system's dark theme</li>
                <li>✅ <strong>Responsive</strong> - Works on mobile and desktop</li>
            </ul>
        </div>

        <div class="demo-section">
            <h2>In the Real System</h2>
            <p>These modals are now used for:</p>
            <ul>
                <li><strong>Assignment Success:</strong> "Successfully assigned 5 project(s) to John Doe!"</li>
                <li><strong>Assignment Errors:</strong> "Assignment failed: Network connection error"</li>
                <li><strong>Validation Warnings:</strong> "Please select at least one project to assign."</li>
                <li><strong>Confirmation Dialogs:</strong> "Assign 3 project(s) to Sample Sales Rep?"</li>
            </ul>
            
            <p><strong>👉 Test the real system:</strong> <a href="pages/projects-management.php?view=unassigned" style="color: #3b82f6;">Project Management Page</a></p>
        </div>
    </div>

    <script>
        const BASE = '<?= $base ?>';
        
        function testAssignmentConfirmation() {
            showConfirmationModal(
                'Confirm Assignment',
                'Assign 3 project(s) to Sample Sales Rep?',
                () => {
                    showNotificationModal('Success', 'Assignment confirmed! Projects have been assigned.', 'success');
                },
                () => {
                    showNotificationModal('Cancelled', 'Assignment was cancelled.', 'info');
                }
            );
        }
        
        function testUnassignConfirmation() {
            showConfirmationModal(
                'Confirm Unassignment',
                'Unassign 2 project(s)? This will remove the sales representative assignment.',
                () => {
                    showNotificationModal('Success', 'Projects have been unassigned successfully.', 'success');
                },
                () => {
                    showNotificationModal('Cancelled', 'Unassignment was cancelled.', 'info');
                }
            );
        }
        
        function testCustomConfirmation() {
            showConfirmationModal(
                'Delete Project',
                'Are you sure you want to delete this project? This action cannot be undone.',
                () => {
                    showNotificationModal('Deleted', 'Project has been permanently deleted.', 'error');
                },
                () => {
                    showNotificationModal('Safe', 'Project deletion was cancelled. Your data is safe.', 'info');
                }
            );
        }
    </script>
    
    <!-- Load the modal functions -->
    <script src="<?= $base ?>/static/js/projects-management-clean.js?v=6"></script>
</body>
</html>