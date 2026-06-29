document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('platformForm');
    const submitBtn = document.getElementById('submitBtn');
    const clearBtn = document.getElementById('clearBtn');
    const successMessage = document.getElementById('successMessage');
    const sourceInput = document.getElementById('source');
    const sourceSuggestions = document.getElementById('sourceSuggestions');
    
    // Load and populate source suggestions from localStorage
    function loadSourceSuggestions() {
        const savedSources = JSON.parse(localStorage.getItem('platformSources') || '[]');
        sourceSuggestions.innerHTML = savedSources.map(src => `<option value="${src}">`).join('');
    }
    
    // Save new source to localStorage
    function saveSourceSuggestion(source) {
        if (!source || source.trim().length < 2) return;
        
        const savedSources = JSON.parse(localStorage.getItem('platformSources') || '[]');
        const trimmedSource = source.trim();
        
        if (!savedSources.includes(trimmedSource)) {
            savedSources.push(trimmedSource);
            localStorage.setItem('platformSources', JSON.stringify(savedSources));
            loadSourceSuggestions();
        }
    }
    
    // Load suggestions on page load
    loadSourceSuggestions();
    
    // Save source on blur
    sourceInput.addEventListener('blur', function() {
        saveSourceSuggestion(this.value);
    });
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Save source before submitting
        saveSourceSuggestion(sourceInput.value);
        
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
            
            const response = await fetch(BASE + '/api/v1/platforms/create', {
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
