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
    <title>Test Button Functionality</title>
    <link rel="stylesheet" href="<?= $base ?>/static/css/base.css?v=6">
    <link rel="stylesheet" href="<?= $base ?>/static/css/modals.css?v=5">
    <style>
        body { padding: 2rem; background: #0f172a; color: white; font-family: Inter, sans-serif; }
        .test-container { max-width: 800px; margin: 0 auto; }
        .test-button { 
            background: #f97316; 
            color: #000; 
            border: none; 
            padding: 1rem 2rem; 
            border-radius: 0.5rem; 
            font-weight: 600; 
            cursor: pointer; 
            font-size: 1rem;
        }
        .console-output {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
            height: 300px;
            overflow-y: scroll;
            font-family: monospace;
            font-size: 0.875rem;
            white-space: pre-wrap;
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
    </style>
</head>
<body data-role="admin">
    <div class="test-container">
        <h1>🧪 Button Functionality Test</h1>
        <p>This page tests the "Bulk Assign Projects" button functionality.</p>
        
        <h2>Step 1: Test Button Click</h2>
        <button class="test-button" onclick="testButtonClick()">Test openSalesRepModal() Function</button>
        
        <h2>Step 2: Test Modal Opening</h2>
        <button class="test-button" onclick="openSalesRepModal()">Bulk Assign Projects</button>
        
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
                    Select a sales representative below.
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
        
        function testButtonClick() {
            console.log('Testing button click...');
            
            // Test if function exists
            if (typeof openSalesRepModal === 'function') {
                console.log('✅ openSalesRepModal function exists');
            } else {
                console.error('❌ openSalesRepModal function not defined');
                return;
            }
            
            // Test modal element
            const modal = document.getElementById('salesRepModal');
            if (modal) {
                console.log('✅ salesRepModal element found');
            } else {
                console.error('❌ salesRepModal element not found');
                return;
            }
            
            // Test grid element
            const grid = document.getElementById('salesRepsGrid');
            if (grid) {
                console.log('✅ salesRepsGrid element found');
            } else {
                console.error('❌ salesRepsGrid element not found');
                return;
            }
            
            console.log('🎉 All elements found! Try clicking "Bulk Assign Projects" button.');
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Page loaded, ready for testing');
        });
    </script>
    
    <!-- Load the actual JavaScript file -->
    <script src="<?= $base ?>/static/js/projects-management-clean.js?v=4"></script>
</body>
</html>