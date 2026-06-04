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
    <title>New Inline Design Test</title>
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=5">
    <style>
        body { padding: 2rem; background: #0f172a; color: white; font-family: Inter, sans-serif; }
        .test-container { max-width: 1200px; margin: 0 auto; }
        .demo-section { 
            background: #1e293b; 
            border: 1px solid #334155; 
            border-radius: 0.75rem; 
            padding: 1.5rem; 
            margin: 1rem 0; 
        }
        .btn-primary {
            background: #f97316;
            color: #000;
            border: 1px solid #f97316;
            padding: 0.875rem 1.75rem;
            border-radius: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            background: #ea580c;
        }
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Simulate the bulk assign button bar */
        #bulkAssignButtonBar {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: space-between;
            background: #1e293b;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #334155;
        }
        
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
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 1rem;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            border-bottom: 1px solid #334155;
        }
        .modal-close {
            background: #374151;
            border: 1px solid #4b5563;
            border-radius: 0.5rem;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
        }
        .modal-body { padding: 1.5rem; }
        #salesRepsGrid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .console-output {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
            height: 200px;
            overflow-y: scroll;
            font-family: monospace;
            font-size: 0.875rem;
            white-space: pre-wrap;
        }
    </style>
</head>
<body data-role="admin">
    <div class="test-container">
        <h1>🎨 New Inline Design Test</h1>
        <p>This demonstrates the new inline design where the assignment status appears next to the bulk assign button.</p>
        
        <div class="demo-section">
            <h2>Before Selection (Normal State)</h2>
            <p>This is how the button bar appears initially:</p>
            
            <!-- Simulate the original button bar -->
            <div id="bulkAssignButtonBar">
                <button class="btn-primary" onclick="openSalesRepModal()">
                    <span style="font-size: 1.25rem;">👥</span>
                    <span>Bulk Assign Projects</span>
                </button>
            </div>
        </div>

        <div class="demo-section">
            <h2>Testing Steps</h2>
            <ol>
                <li><strong>Click "Bulk Assign Projects"</strong> - Modal should open</li>
                <li><strong>Select a sales rep</strong> - Modal should close and show inline status</li>
                <li><strong>The button bar should transform to:</strong>
                    <ul style="margin-top: 0.5rem;">
                        <li>Green status bar showing selected sales rep and project count</li>
                        <li>Orange "Assign Projects" button on the right</li>
                        <li>No floating buttons or top banners</li>
                    </ul>
                </li>
            </ol>
        </div>

        <div class="demo-section">
            <h2>Expected Result Layout</h2>
            <p>After selecting a sales rep, the layout should look like this:</p>
            
            <!-- Show what the transformed layout should look like -->
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: space-between; background: #1e293b; padding: 1rem; border-radius: 0.5rem; border: 1px solid #334155;">
                <div style="display: flex; align-items: center; gap: 1rem; background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 0.875rem 1.5rem; border-radius: 0.75rem; font-weight: 500; font-size: 0.95rem;">
                    <span>📋 Assignment Mode: Selected Sales Rep <strong>Sample Sales Rep</strong></span>
                    <span>| Selected Projects: <strong>0</strong></span>
                    <button style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem;">Cancel</button>
                </div>
                <button class="btn-primary" disabled style="opacity: 0.5;">
                    <span>✓</span>
                    <span>Assign Projects (0)</span>
                </button>
            </div>
        </div>

        <h2>Console Output:</h2>
        <div class="console-output" id="consoleOutput"></div>
        
        <button onclick="clearConsole()" style="margin-top: 0.5rem; padding: 0.5rem 1rem; background: #6b7280; color: white; border: none; border-radius: 0.25rem; cursor: pointer;">Clear Console</button>
    </div>

    <!-- Sales Rep Selection Modal -->
    <div class="modal-overlay" id="salesRepModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>👥 Select Sales Representative</h2>
                <button class="modal-close" onclick="closeSalesRepModal()">×</button>
            </div>
            <div class="modal-body">
                <p style="margin: 0 0 1.5rem; color: #9ca3af;">
                    Select a sales representative below to start project assignment.
                </p>
                
                <input type="text" id="srSearchInput" placeholder="Search by name or branch..." 
                       style="width: 100%; padding: 0.75rem; background: #0f172a; border: 1px solid #334155; border-radius: 0.5rem; color: white; margin-bottom: 1rem;">
                
                <div id="salesRepsGrid">
                    <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #9ca3af;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏳</div>
                        <p>Loading sales representatives...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const BASE = '<?= $base ?>';
        const _B = BASE;
        
        let consoleLog = [];
        
        // Override console.log to capture output
        const originalLog = console.log;
        const originalError = console.error;
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            consoleLog.push('[LOG] ' + args.join(' '));
            updateConsoleDisplay();
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            consoleLog.push('[ERROR] ' + args.join(' '));
            updateConsoleDisplay();
        };
        
        function updateConsoleDisplay() {
            const output = document.getElementById('consoleOutput');
            if (output) {
                output.textContent = consoleLog.join('\n');
                output.scrollTop = output.scrollHeight;
            }
        }
        
        function clearConsole() {
            consoleLog = [];
            updateConsoleDisplay();
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Inline design test page loaded');
        });
    </script>
    
    <!-- Load the actual JavaScript file -->
    <script src="<?= $base ?>/static/js/projects-management-clean.js?v=5"></script>
</body>
</html>