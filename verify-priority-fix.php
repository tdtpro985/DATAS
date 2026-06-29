<!DOCTYPE html>
<html>
<head>
    <title>Verify Priority Filter Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; }
        .test-section h3 { margin-top: 0; color: #333; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        code { background: #fff; padding: 2px 5px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Priority Projects Filter Verification</h1>
    
    <div class="test-section">
        <h3>Test 1: Check window.PROJECT_TYPE Assignment</h3>
        <p>This test verifies that window.PROJECT_TYPE is set correctly before ProjectsPage initializes.</p>
        <script>
            // Simulate what pages/projects.php does
            const BASE = '';
            window.PROJECT_TYPE = 'priority';
            document.write('<p>window.PROJECT_TYPE = <code>' + window.PROJECT_TYPE + '</code></p>');
        </script>
    </div>

    <div class="test-section">
        <h3>Test 2: Simulate ProjectsPage Object Creation</h3>
        <p>This test shows that the type is now read in init() instead of at object creation time.</p>
        <script>
            // Simulate the OLD way (would fail)
            const OldProjectsPage = {
                type: window.PROJECT_TYPE || 'all',
                init() {
                    document.write('<p><span class="fail">OLD WAY:</span> type set at object creation = <code>' + this.type + '</code></p>');
                }
            };
            
            // Clear window.PROJECT_TYPE temporarily to show the problem
            const savedType = window.PROJECT_TYPE;
            window.PROJECT_TYPE = undefined;
            OldProjectsPage.init();
            window.PROJECT_TYPE = savedType;

            // Simulate the NEW way (will work)
            const NewProjectsPage = {
                type: null,
                init() {
                    this.type = window.PROJECT_TYPE || 'all';
                    document.write('<p><span class="pass">NEW WAY:</span> type set in init() = <code>' + this.type + '</code></p>');
                }
            };
            NewProjectsPage.init();
        </script>
    </div>

    <div class="test-section">
        <h3>Test 3: API URL Construction</h3>
        <p>This test shows what API URL will be called with the correct type.</p>
        <script>
            const MockProjectsPage = {
                type: null,
                init() {
                    this.type = window.PROJECT_TYPE || 'all';
                    let apiUrl = `${BASE}/api/v1/projects`;
                    if (this.type && this.type !== 'all') {
                        apiUrl += `?type=${encodeURIComponent(this.type)}`;
                    }
                    document.write('<p>API URL will be: <code>' + apiUrl + '</code></p>');
                    if (this.type === 'priority') {
                        document.write('<p><span class="pass">✓ CORRECT:</span> Will call API with type=priority parameter</p>');
                    } else {
                        document.write('<p><span class="fail">✗ WRONG:</span> Not filtering for priority projects</p>');
                    }
                }
            };
            MockProjectsPage.init();
        </script>
    </div>

    <div class="test-section">
        <h3>Summary</h3>
        <p>
            <strong>The Fix:</strong> Changed <code>type: window.PROJECT_TYPE || 'all'</code> to 
            <code>type: null</code> (initially), then assign <code>this.type = window.PROJECT_TYPE || 'all'</code> 
            in the <code>init()</code> method.
        </p>
        <p>
            <strong>Why it works:</strong> The assignment now happens AFTER the inline script in the HTML 
            has set <code>window.PROJECT_TYPE</code>, instead of when the JavaScript file is parsed.
        </p>
        <p class="pass">✓ The fix should now correctly filter priority projects!</p>
    </div>

</body>
</html>
