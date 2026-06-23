/* ============================================================
   CUSTOM SELECT DROPDOWN — JavaScript Implementation
   ============================================================
   Converts native select elements into custom styled dropdowns
   ============================================================ */

class CustomSelect {
    constructor(selectElement, options = {}) {
        this.select = selectElement;
        this.options = {
            searchable: options.searchable || false,
            placeholder: options.placeholder || 'Select an option',
            ...options
        };
        
        this.isOpen = false;
        this.selectedOption = null;
        
        this.init();
    }
    
    init() {
        // Create custom select structure
        this.createCustomSelect();
        
        // Set initial value
        this.setInitialValue();
        
        // Attach event listeners
        this.attachEventListeners();
        
        // Hide native select
        this.select.style.display = 'none';
    }
    
    createCustomSelect() {
        // Create wrapper
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'custom-select-wrapper';
        if (this.select.classList.contains('select-sm')) {
            this.wrapper.classList.add('select-sm');
        }
        if (this.select.classList.contains('select-lg')) {
            this.wrapper.classList.add('select-lg');
        }
        if (this.select.disabled) {
            this.wrapper.classList.add('disabled');
        }
        
        // Create trigger button
        this.trigger = document.createElement('div');
        this.trigger.className = 'custom-select-trigger';
        this.trigger.innerHTML = `
            <span class="custom-select-label">${this.options.placeholder}</span>
            <span class="custom-select-arrow">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </span>
        `;
        
        // Create dropdown container
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'custom-select-dropdown';
        
        // Add search if enabled
        if (this.options.searchable) {
            const searchDiv = document.createElement('div');
            searchDiv.className = 'custom-select-search';
            searchDiv.innerHTML = '<input type="text" placeholder="Search..." />';
            this.dropdown.appendChild(searchDiv);
            this.searchInput = searchDiv.querySelector('input');
        }
        
        // Create options container
        this.optionsContainer = document.createElement('div');
        this.optionsContainer.className = 'custom-select-options';
        
        // Populate options
        this.populateOptions();
        
        this.dropdown.appendChild(this.optionsContainer);
        
        // Append everything
        this.wrapper.appendChild(this.trigger);
        this.wrapper.appendChild(this.dropdown);
        
        // Insert after native select
        this.select.parentNode.insertBefore(this.wrapper, this.select.nextSibling);
    }
    
    populateOptions(filterText = '') {
        this.optionsContainer.innerHTML = '';
        
        const options = Array.from(this.select.options);
        let hasVisibleOptions = false;
        
        options.forEach((option, index) => {
            // Skip placeholder options
            if (option.value === '' && index === 0) return;
            
            // Filter by search text
            if (filterText && !option.text.toLowerCase().includes(filterText.toLowerCase())) {
                return;
            }
            
            hasVisibleOptions = true;
            
            const optionDiv = document.createElement('div');
            optionDiv.className = 'custom-select-option';
            optionDiv.textContent = option.text;
            optionDiv.dataset.value = option.value;
            optionDiv.dataset.index = index;
            
            if (option.selected) {
                optionDiv.classList.add('selected');
            }
            
            this.optionsContainer.appendChild(optionDiv);
        });
        
        // Show empty state if no options
        if (!hasVisibleOptions) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'custom-select-empty';
            emptyDiv.textContent = filterText ? 'No results found' : 'No options available';
            this.optionsContainer.appendChild(emptyDiv);
        }
    }
    
    setInitialValue() {
        const selectedOption = this.select.options[this.select.selectedIndex];
        if (selectedOption && selectedOption.value !== '') {
            this.updateLabel(selectedOption.text);
        }
    }
    
    attachEventListeners() {
        // Toggle dropdown
        this.trigger.addEventListener('click', (e) => {
            if (!this.select.disabled) {
                this.toggle();
            }
        });
        
        // Select option
        this.optionsContainer.addEventListener('click', (e) => {
            const optionDiv = e.target.closest('.custom-select-option');
            if (optionDiv && !optionDiv.classList.contains('custom-select-empty')) {
                this.selectOption(optionDiv);
            }
        });
        
        // Search functionality
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                this.populateOptions(e.target.value);
            });
            
            // Prevent dropdown from closing when clicking search
            this.searchInput.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
        
        // Close on outside click (consider dropdown moved to body)
        document.addEventListener('click', (e) => {
            if (!this.wrapper.contains(e.target) && !this.dropdown.contains(e.target)) {
                this.close();
            }
        });
        
        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
        
        // Watch for changes to native select (from external code)
        this.select.addEventListener('change', () => {
            this.syncFromNativeSelect();
        });
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        this.isOpen = true;
        this.trigger.classList.add('open');
        this.dropdown.classList.add('open');

        // Move dropdown to body to avoid being clipped by overflow:hidden ancestors
        try {
            // compute trigger position
            const rect = this.trigger.getBoundingClientRect();
            // set fixed positioning and width to match trigger
            this.dropdown.style.position = 'fixed';
            this.dropdown.style.left = rect.left + 'px';
            this.dropdown.style.top = (rect.bottom + 8) + 'px';
            this.dropdown.style.width = rect.width + 'px';

            // append to body if not already
            if (!document.body.contains(this.dropdown)) {
                document.body.appendChild(this.dropdown);
            }

            // reposition on scroll/resize while open
            this._boundReposition = () => {
                const r = this.trigger.getBoundingClientRect();
                this.dropdown.style.left = r.left + 'px';
                this.dropdown.style.top = (r.bottom + 8) + 'px';
                this.dropdown.style.width = r.width + 'px';
            };
            window.addEventListener('scroll', this._boundReposition, true);
            window.addEventListener('resize', this._boundReposition);
        } catch (e) {
            // fallback: leave dropdown in place
            console.warn('CustomSelect: positioning fallback', e);
        }

        // Focus search if available
        if (this.searchInput) {
            setTimeout(() => this.searchInput.focus(), 100);
        }
    }
    
    close() {
        this.isOpen = false;
        this.trigger.classList.remove('open');
        this.dropdown.classList.remove('open');
        
        // Clear search
        if (this.searchInput) {
            this.searchInput.value = '';
            this.populateOptions();
        }

        // remove event listeners related to repositioning
        if (this._boundReposition) {
            window.removeEventListener('scroll', this._boundReposition, true);
            window.removeEventListener('resize', this._boundReposition);
            this._boundReposition = null;
        }

        // move dropdown back into wrapper if it was appended to body
        if (!this.wrapper.contains(this.dropdown) && this.dropdown.parentNode === document.body) {
            this.wrapper.appendChild(this.dropdown);
            this.dropdown.style.position = '';
            this.dropdown.style.left = '';
            this.dropdown.style.top = '';
            this.dropdown.style.width = '';
        }
    }
    
    selectOption(optionDiv) {
        const value = optionDiv.dataset.value;
        const index = optionDiv.dataset.index;
        
        // Update native select
        this.select.selectedIndex = index;
        
        // Trigger change event on native select
        const event = new Event('change', { bubbles: true });
        this.select.dispatchEvent(event);
        
        // Update UI
        this.updateLabel(optionDiv.textContent);
        
        // Update selected class
        this.optionsContainer.querySelectorAll('.custom-select-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        optionDiv.classList.add('selected');
        
        // Close dropdown
        this.close();
    }
    
    updateLabel(text) {
        const label = this.trigger.querySelector('.custom-select-label');
        label.textContent = text;
    }
    
    syncFromNativeSelect() {
        const selectedOption = this.select.options[this.select.selectedIndex];
        if (selectedOption) {
            this.updateLabel(selectedOption.text);
            
            // Update selected class in dropdown
            this.optionsContainer.querySelectorAll('.custom-select-option').forEach(opt => {
                opt.classList.remove('selected');
                if (opt.dataset.value === selectedOption.value) {
                    opt.classList.add('selected');
                }
            });
        }
    }
    
    destroy() {
        this.wrapper.remove();
        this.select.style.display = '';
    }
    
    refresh() {
        this.populateOptions();
        this.syncFromNativeSelect();
    }
}

// Auto-initialize custom selects with data attribute
document.addEventListener('DOMContentLoaded', () => {
    // Initialize all selects with data-custom-select attribute
    document.querySelectorAll('select[data-custom-select]').forEach(select => {
        const searchable = select.dataset.searchable === 'true';
        const placeholder = select.dataset.placeholder || 'Select an option';
        
        new CustomSelect(select, { searchable, placeholder });
    });
});

// Export for manual initialization
window.CustomSelect = CustomSelect;

// Helper function to initialize specific selects
window.initCustomSelect = function(selector, options = {}) {
    const selects = typeof selector === 'string' 
        ? document.querySelectorAll(selector)
        : [selector];
    
    const instances = [];
    selects.forEach(select => {
        if (select && select.tagName === 'SELECT') {
            instances.push(new CustomSelect(select, options));
        }
    });
    
    return instances.length === 1 ? instances[0] : instances;
};
