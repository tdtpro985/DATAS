/* ============================================================
   encode-non-priority.js — Non-Priority Form (3 Steps)
   ============================================================ */

// Searchable Select Widget
class SearchableSelect {
    constructor(wrapperId, selectId) {
        this.wrapper = document.getElementById(wrapperId);
        this.select = document.getElementById(selectId);
        this.trigger = this.wrapper.querySelector('.searchable-select-trigger');
        this.label = this.wrapper.querySelector('.searchable-select-label');
        this.arrow = this.wrapper.querySelector('.searchable-select-arrow');
        this.dropdown = this.wrapper.querySelector('.searchable-select-dropdown');
        this.search = this.wrapper.querySelector('.searchable-select-search');
        this.optionsContainer = this.wrapper.querySelector('.searchable-select-options');
        
        this.init();
    }

    init() {
        // Toggle dropdown
        this.trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });

        // Search filter
        this.search.addEventListener('input', () => this.filterOptions());

        // Select option on click
        this.optionsContainer.addEventListener('click', (e) => {
            const option = e.target.closest('.searchable-option');
            if (option && !option.classList.contains('hidden')) {
                this.selectOption(option.dataset.value);
            }
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!this.wrapper.contains(e.target)) {
                this.close();
            }
        });

        // Keyboard support
        this.trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggle();
            }
        });
    }

    toggle() {
        if (this.wrapper.classList.contains('open')) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.wrapper.classList.add('open');
        this.search.value = '';
        this.filterOptions();
        setTimeout(() => this.search.focus(), 50);
    }

    close() {
        this.wrapper.classList.remove('open');
    }

    filterOptions() {
        const term = this.search.value.toLowerCase().trim();
        const options = this.optionsContainer.querySelectorAll('.searchable-option');
        let hasVisible = false;

        options.forEach(opt => {
            const text = opt.textContent.toLowerCase();
            if (text.includes(term)) {
                opt.classList.remove('hidden');
                hasVisible = true;
            } else {
                opt.classList.add('hidden');
            }
        });

        // Show/hide no results message
        let noResults = this.optionsContainer.querySelector('.searchable-no-results');
        if (!hasVisible && options.length > 0) {
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.className = 'searchable-no-results';
                noResults.textContent = 'No regions found';
                this.optionsContainer.appendChild(noResults);
            }
            noResults.style.display = 'block';
        } else if (noResults) {
            noResults.style.display = 'none';
        }
    }

    selectOption(value) {
        // Update hidden select
        this.select.value = value;
        
        // Update label
        const selectedOption = this.optionsContainer.querySelector(`[data-value="${value}"]`);
        if (selectedOption) {
            this.label.textContent = selectedOption.textContent;
            this.label.classList.add('has-value');
        }

        // Update selected state
        this.optionsContainer.querySelectorAll('.searchable-option').forEach(opt => {
            opt.classList.toggle('selected', opt.dataset.value === value);
        });

        // Trigger change event
        this.select.dispatchEvent(new Event('change', { bubbles: true }));

        this.close();
    }

    populate(regions) {
        // Clear existing options
        this.optionsContainer.innerHTML = '';
        this.select.innerHTML = '<option value="">Select region</option>';

        // Add new options
        regions.forEach(region => {
            // Add to hidden select
            const selectOption = document.createElement('option');
            selectOption.value = region.name;
            selectOption.textContent = region.name;
            this.select.appendChild(selectOption);

            // Add to visible dropdown
            const divOption = document.createElement('div');
            divOption.className = 'searchable-option';
            divOption.dataset.value = region.name;
            divOption.textContent = region.name;
            this.optionsContainer.appendChild(divOption);
        });
    }

    setValue(value) {
        if (value) {
            this.selectOption(value);
        } else {
            this.label.textContent = 'Select region';
            this.label.classList.remove('has-value');
            this.select.value = '';
        }
    }
}

const NonPriorityForm = {
    currentStep: 1,
    totalSteps: 3,
    form: null,
    locationCache: {},
    searchableSelects: {},

    init() {
        this.form = document.getElementById('encodeForm');
        if (!this.form) return;

        // Initialize searchable select widgets
        this.searchableSelects.contractRegion = new SearchableSelect('contractRegionWrapper', 'contractRegion');
        this.searchableSelects.projectRegion = new SearchableSelect('projectRegionWrapper', 'projectRegion');

        // Add input event listeners to clear error styling
        this.form.addEventListener('input', (e) => {
            if (e.target.matches('input, select')) {
                e.target.style.borderColor = '';
                e.target.style.boxShadow = '';
            }
        });

        // Event listeners for step navigation - all steps
        document.getElementById('nextBtn').addEventListener('click', () => this.nextStep());
        document.getElementById('prevBtn').addEventListener('click', () => this.prevStep());
        
        // Step 2 buttons
        document.getElementById('nextBtn2').addEventListener('click', () => this.nextStep());
        document.getElementById('prevBtn2').addEventListener('click', () => this.prevStep());
        
        // Step 3 buttons
        document.getElementById('prevBtn3').addEventListener('click', () => this.prevStep());
        
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Source dropdown - show/hide "Other" field and PHILGEPS notice field
        const sourceSelect = document.getElementById('source');
        const sourceOtherGroup = document.getElementById('sourceOtherGroup');
        const sourceOtherInput = document.getElementById('sourceOther');
        const philgepsNoticeGroup = document.getElementById('philgepsNoticeGroup');
        const philgepsNoticeInput = document.getElementById('philgepsNotice');
        
        sourceSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                sourceOtherGroup.style.display = 'block';
                sourceOtherInput.required = true;
                if (philgepsNoticeGroup) {
                    philgepsNoticeGroup.style.display = 'none';
                    philgepsNoticeInput.required = false;
                    philgepsNoticeInput.value = '';
                }
            } else if (this.value === 'PHILGEPS') {
                sourceOtherGroup.style.display = 'none';
                sourceOtherInput.required = false;
                sourceOtherInput.value = '';
                if (philgepsNoticeGroup) {
                    philgepsNoticeGroup.style.display = 'block';
                    philgepsNoticeInput.required = true;
                }
            } else {
                sourceOtherGroup.style.display = 'none';
                sourceOtherInput.required = false;
                sourceOtherInput.value = '';
                if (philgepsNoticeGroup) {
                    philgepsNoticeGroup.style.display = 'none';
                    philgepsNoticeInput.required = false;
                    philgepsNoticeInput.value = '';
                }
            }
        });

        // Project ID - allow any characters (letters, numbers, spaces, special chars)
        // No restriction needed - treat as a normal text field

        // Load initial regions for Philippines
        setTimeout(() => {
            this.loadRegions('contractCountry', 'contractRegion');
            this.loadRegions('projectCountry', 'projectRegion');
        }, 100);

        // Reload regions when the country changes
        document.getElementById('contractCountry').addEventListener('change', () => {
            this.loadRegions('contractCountry', 'contractRegion');
            this.resetLocationFields('contractProvince', 'contractCity');
        });
        document.getElementById('projectCountry').addEventListener('change', () => {
            this.loadRegions('projectCountry', 'projectRegion');
            this.resetLocationFields('projectProvince', 'projectCity');
        });

        // Set up location cascades
        document.getElementById('contractRegion').addEventListener('change', () => {
            this.loadProvinces('contractCountry', 'contractRegion', 'contractProvince');
            this.resetLocationFields('contractCity');
        });
        document.getElementById('contractProvince').addEventListener('change', () => {
            this.loadCities('contractCountry', 'contractRegion', 'contractProvince', 'contractCity');
        });

        document.getElementById('projectRegion').addEventListener('change', () => {
            this.loadProvinces('projectCountry', 'projectRegion', 'projectProvince');
            this.resetLocationFields('projectCity');
        });
        document.getElementById('projectProvince').addEventListener('change', () => {
            this.loadCities('projectCountry', 'projectRegion', 'projectProvince', 'projectCity');
        });
    },

    nextStep() {
        if (!this.validateStep(this.currentStep)) {
            Toast.error('Please fill all required fields');
            return;
        }
        if (this.currentStep < this.totalSteps) {
            this.showStep(this.currentStep + 1);
        }
    },

    prevStep() {
        if (this.currentStep > 1) {
            this.showStep(this.currentStep - 1);
        }
    },

    showStep(stepNum) {
        document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
        document.querySelector(`.form-step[data-step="${stepNum}"]`).classList.add('active');

        // Update badges
        for (let i = 1; i <= this.totalSteps; i++) {
            const badge = document.getElementById(`step${i}Badge`);
            badge.classList.remove('active', 'completed');
            if (i < stepNum) {
                badge.classList.add('completed');
            } else if (i === stepNum) {
                badge.classList.add('active');
            }
        }

        // Update button visibility for each step
        // Step 1 buttons
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        if (prevBtn) prevBtn.style.display = stepNum === 1 ? 'none' : 'block';
        if (nextBtn) nextBtn.style.display = stepNum === 1 ? 'block' : 'none';
        
        // Step 2 buttons
        const prevBtn2 = document.getElementById('prevBtn2');
        const nextBtn2 = document.getElementById('nextBtn2');
        if (prevBtn2) prevBtn2.style.display = stepNum === 2 ? 'block' : 'none';
        if (nextBtn2) nextBtn2.style.display = stepNum === 2 ? 'block' : 'none';
        
        // Step 3 buttons
        const prevBtn3 = document.getElementById('prevBtn3');
        const submitBtn = document.getElementById('submitBtn');
        if (prevBtn3) prevBtn3.style.display = stepNum === 3 ? 'block' : 'none';
        if (submitBtn) submitBtn.style.display = stepNum === 3 ? 'block' : 'none';

        this.currentStep = stepNum;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    validateStep(stepNum) {
        const requiredFields = {
            1: ['publicationDate', 'source', 'contractorName', 'contactNumber', 'contractCountry', 'contractRegion', 'contractProvince', 'contractCity'],
            2: ['projectName', 'projectCountry', 'projectRegion', 'projectProvince', 'projectCity', 'projectValue', 'projectStatus'],
            3: [] // Step 3 is all optional
        };

        const fields = requiredFields[stepNum] || [];
        const missingFields = [];
        
        for (const fieldId of fields) {
            const field = document.getElementById(fieldId);
            if (!field || !field.value || field.value.trim() === '') {
                // Add visual feedback for missing field
                if (field) {
                    field.style.borderColor = '#ef4444';
                    field.style.boxShadow = '0 0 0 2px rgba(239, 68, 68, 0.2)';
                }
                
                // Get field label for error message
                const label = document.querySelector(`label[for="${fieldId}"]`);
                const fieldName = label ? label.textContent.replace(' *', '').trim() : fieldId;
                missingFields.push(fieldName);
            } else {
                // Remove error styling if field is filled
                field.style.borderColor = '';
                field.style.boxShadow = '';
            }
        }
        
        // Special validation for Source "Other" and PHILGEPS
        if (stepNum === 1) {
            const sourceSelect = document.getElementById('source');
            const sourceOther = document.getElementById('sourceOther');
            const philgepsNotice = document.getElementById('philgepsNotice');
            
            if (sourceSelect.value === 'Other' && (!sourceOther.value || sourceOther.value.trim() === '')) {
                if (sourceOther) {
                    sourceOther.style.borderColor = '#ef4444';
                    sourceOther.style.boxShadow = '0 0 0 2px rgba(239, 68, 68, 0.2)';
                }
                missingFields.push('Specify Source');
            }
            
            if (sourceSelect.value === 'PHILGEPS' && (!philgepsNotice.value || philgepsNotice.value.trim() === '')) {
                if (philgepsNotice) {
                    philgepsNotice.style.borderColor = '#ef4444';
                    philgepsNotice.style.boxShadow = '0 0 0 2px rgba(239, 68, 68, 0.2)';
                }
                missingFields.push('Notice Reference Number');
            }
        }
        
        if (missingFields.length > 0) {
            console.log('Missing required fields:', missingFields);
            Toast.error(`Please fill in: ${missingFields.join(', ')}`);
            return false;
        }
        
        return true;
    },

    async loadRegions(countrySelectId, regionSelectId) {
        const countryElement = document.getElementById(countrySelectId);
        const country = countryElement ? (countryElement.value === 'Philippines' ? 'PH' : countryElement.value || 'PH') : 'PH';
        
        try {
            const response = await fetch(`${BASE}/api/locations.php?action=regions&country=${country}`);
            
            if (!response.ok) {
                console.error('Failed to load regions. Status:', response.status);
                if (response.status === 500) {
                    Toast.error('Location database not initialized. Please run the migration file. See RUN_THIS_MIGRATION.md');
                }
                return;
            }
            
            const data = await response.json();

            if (data.regions && data.regions.length > 0) {
                // Determine which searchable select to update
                const selectWidget = regionSelectId === 'contractRegion' 
                    ? this.searchableSelects.contractRegion 
                    : this.searchableSelects.projectRegion;
                
                if (selectWidget) {
                    selectWidget.populate(data.regions);
                }
            } else {
                console.warn('No regions found. Database may not be initialized.');
                Toast.warning('No regions available. Please contact administrator.');
            }
        } catch (error) {
            console.error('Error loading regions:', error);
            Toast.error('Failed to load regions. Check console for details.');
        }
    },

    async loadProvinces(countryId, regionId, provinceId) {
        const country = document.getElementById(countryId).value === 'Philippines' ? 'PH' : (document.getElementById(countryId).value || 'PH');
        const region = document.getElementById(regionId).value;
        
        // Get the corresponding datalist for the province input
        let datalistId;
        if (provinceId === 'contractProvince') {
            datalistId = 'provinceList';
        } else if (provinceId === 'projectProvince') {
            datalistId = 'projectProvinceList';
        }
        
        const provinceDatalist = document.getElementById(datalistId);
        if (!provinceDatalist) {
            console.error('Datalist not found for:', datalistId);
            return;
        }

        if (!region) {
            provinceDatalist.innerHTML = '';
            return;
        }

        try {
            const response = await fetch(`${BASE}/api/locations.php?action=provinces&country=${country}&region=${region}`);
            const data = await response.json();

            // Clear existing options
            provinceDatalist.innerHTML = '';
            
            if (data.provinces) {
                data.provinces.forEach(province => {
                    const option = document.createElement('option');
                    option.value = province.name; // Use name for display
                    provinceDatalist.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading provinces:', error);
        }
    },

    async loadCities(countryId, regionId, provinceId, cityId) {
        const country = document.getElementById(countryId).value === 'Philippines' ? 'PH' : (document.getElementById(countryId).value || 'PH');
        const region = document.getElementById(regionId).value;
        const province = document.getElementById(provinceId).value;
        
        // Get the corresponding datalist for the city input
        let datalistId;
        if (cityId === 'contractCity') {
            datalistId = 'cityList';
        } else if (cityId === 'projectCity') {
            datalistId = 'projectCityList';
        }
        
        const cityDatalist = document.getElementById(datalistId);
        if (!cityDatalist) {
            console.error('Datalist not found for:', datalistId);
            return;
        }

        if (!region || !province) {
            cityDatalist.innerHTML = '';
            return;
        }

        try {
            const response = await fetch(`${BASE}/api/locations.php?action=cities&country=${country}&region=${region}&province=${province}`);
            const data = await response.json();

            // Clear existing options
            cityDatalist.innerHTML = '';
            
            if (data.cities) {
                data.cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.name; // Use name for display
                    cityDatalist.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading cities:', error);
        }
    },

    async loadBarangays(countryId, regionId, provinceId, cityId, barangayId) {
        const country = document.getElementById(countryId).value || 'PH';
        const region = document.getElementById(regionId).value;
        const province = document.getElementById(provinceId).value;
        const city = document.getElementById(cityId).value;
        const barangaySelect = document.getElementById(barangayId);

        if (!region || !province || !city) {
            return;
        }

        try {
            const response = await fetch(`${BASE}/api/locations.php?action=barangays&country=${country}&region=${region}&province=${province}&city=${city}`);
            const data = await response.json();

            if (data.barangays && data.barangays.length > 0) {
                barangaySelect.innerHTML = '<option value="">Select barangay</option>';
                data.barangays.forEach(barangay => {
                    const option = document.createElement('option');
                    option.value = barangay.code;
                    option.textContent = barangay.name;
                    barangaySelect.appendChild(option);
                });
                barangaySelect.style.display = 'block';
            } else {
                barangaySelect.style.display = 'none';
            }
        } catch (error) {
            console.error('Error loading barangays:', error);
        }
    },

    resetLocationFields(...fieldIds) {
        fieldIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                // Clear the input value for input elements (now that they're input+datalist instead of select)
                element.value = '';
                
                // Also clear the corresponding datalist if it exists
                let datalistId;
                if (id === 'contractProvince') datalistId = 'provinceList';
                else if (id === 'contractCity') datalistId = 'cityList';
                else if (id === 'projectProvince') datalistId = 'projectProvinceList';
                else if (id === 'projectCity') datalistId = 'projectCityList';
                
                if (datalistId) {
                    const datalist = document.getElementById(datalistId);
                    if (datalist) {
                        datalist.innerHTML = '';
                    }
                }
            }
        });
    },

    async handleSubmit(event) {
        event.preventDefault();

        if (!this.validateStep(3)) {
            Toast.error('Please complete all required fields');
            return;
        }

        const payload = this.buildPayload();

        try {
            const response = await fetch(`${BASE}/api/v1/projects`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.detail || data.message || 'Failed to submit project');
            }

            Toast.success('Project encoded successfully!');
            setTimeout(() => {
                window.location.href = `${BASE}/encode`;
            }, 1500);

        } catch (error) {
            console.error('Submit error:', error);
            Toast.error(error.message || 'Unable to submit project');
        }
    },

    buildPayload() {
        const getFieldValue = (id) => {
            const element = document.getElementById(id);
            return element ? (element.value || null) : null;
        };
        const getFieldNumber = (id) => {
            const element = document.getElementById(id);
            if (!element) return null;
            const val = element.value;
            return val === '' ? null : parseFloat(val);
        };

        // Get source value - if "Other", use the sourceOther field
        const sourceSelect = document.getElementById('source');
        const sourceValue = sourceSelect && sourceSelect.value === 'Other' 
            ? getFieldValue('sourceOther') 
            : (sourceSelect ? sourceSelect.value : null);
        
        // Get notice reference number for PHILGEPS
        const noticeReferenceNumber = sourceSelect && sourceSelect.value === 'PHILGEPS'
            ? getFieldValue('philgepsNotice')
            : null;

        return {
            contractor_name: getFieldValue('contractorName'),
            contact_person: getFieldValue('contactPerson'),
            contact_number: getFieldValue('contactNumber'),
            contractor_id: getFieldValue('contractId'),
            source: sourceValue,
            notice_reference_number: noticeReferenceNumber,
            publication_date: getFieldValue('publicationDate'),
            
            // Contractor location fields
            address: getFieldValue('contractStreet'), // Keep for backward compatibility
            contract_country: getFieldValue('contractCountry'),
            contract_region: getFieldValue('contractRegion'),
            contract_province: getFieldValue('contractProvince'),
            contract_city: getFieldValue('contractCity'),
            contract_barangay: getFieldValue('contractBarangay'),
            contract_street: getFieldValue('contractStreet'),
            contract_blk_lot: getFieldValue('contractBlkLot'),
            contract_coordinates: getFieldValue('contractCoords'),
            
            // Legacy fields for compatibility
            region: getFieldValue('contractRegion'),
            city_province: getFieldValue('contractCity'),
            
            // Project details
            project_name: getFieldValue('projectName'),
            project_id: getFieldValue('projectId'),
            
            // Project location fields
            project_country: getFieldValue('projectCountry'),
            project_region: getFieldValue('projectRegion'),
            project_province: getFieldValue('projectProvince'),
            project_city: getFieldValue('projectCity'),
            project_barangay: getFieldValue('projectBarangay'),
            project_street: getFieldValue('projectStreet'),
            project_blk_lot: getFieldValue('projectBlkLot'),
            project_coordinates: getFieldValue('projectCoords'),
            
            // Project details
            project_value: getFieldNumber('projectValue'),
            status: getFieldValue('projectStatus'),
            
            // Materials
            drbs_value: getFieldNumber('drbs'), // Fixed: was saving to 'drbs' (text field for type) instead of 'drbs_value' (decimal amount)
            sheet_pile_amount: getFieldNumber('sheetPile'),
            ms_plate: getFieldNumber('msPlate'),
            angle_bars: getFieldNumber('angleBars'),
            channel_bars: getFieldNumber('channelBars'),
            wide_flange: getFieldNumber('wideFlange'),
            gi_bi: getFieldNumber('giBi'),
            
            form_type: 'non-priority'
        };
        
        // Debug log to verify drbs_value is being sent
        console.log('[NON-PRIORITY ENCODE] Payload materials:', {
            drbs_value: payload.drbs_value,
            sheet_pile_amount: payload.sheet_pile_amount,
            ms_plate: payload.ms_plate
        });
        
        return payload;
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => NonPriorityForm.init());
} else {
    NonPriorityForm.init();
}

/* ── Edit Mode ─────────────────────────────────────────── */
(function initEditMode() {
    const editId = new URLSearchParams(window.location.search).get('edit');
    if (!editId) return;

    async function loadAndFill() {
        try {
            // Update page titles
            document.querySelectorAll('h1').forEach(el => el.textContent = 'Edit Non-Priority Project');
            document.querySelectorAll('p').forEach(el => {
                if (el.textContent.includes('Complete all 3 steps')) el.textContent = 'Update the project details below.';
            });
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) submitBtn.textContent = '✓ Save Changes';

            const res = await fetch(`${BASE}/api/v1/projects?db_id=${editId}&size=1`, { credentials: 'include' });
            if (!res.ok) throw new Error('Failed to load project');
            const data = await res.json();
            const p = data.projects?.[0] || data.project || data;
            if (!p || !p.id) throw new Error('Project not found');

            const set = (id, val) => { const el = document.getElementById(id); if (el && val !== null && val !== undefined) el.value = val; };

            // Step 1 — Contract Details
            set('publicationDate',  p.publication_date);
            set('contractCountry',  p.contract_country || 'Philippines');
            set('source',           p.source);
            if (p.source === 'PHILGEPS') {
                set('philgepsNotice', p.notice_reference_number);
                const pg = document.getElementById('philgepsNoticeGroup');
                if (pg) pg.style.display = 'block';
            }
            set('contractId',       p.contractor_id);
            set('contractRegion',   p.contract_region);
            set('contractProvince', p.contract_province);
            set('contractCity',     p.contract_city);
            set('contractBarangay', p.contract_barangay);
            set('contractStreet',   p.contract_street);
            set('contractBlkLot',   p.contract_blk_lot);
            set('contractCoords',   p.contract_coordinates);
            set('contractorName',   p.contractor_name);
            set('contactPerson',    p.contact_person);
            set('contactNumber',    p.contact_number);

            // Step 2 — Project Details
            set('projectId',        p.project_id);
            set('projectCountry',   p.project_country || 'Philippines');
            set('projectName',      p.project_name);
            set('projectRegion',    p.project_region);
            set('projectProvince',  p.project_province);
            set('projectCity',      p.project_city);
            set('projectBarangay',  p.project_barangay);
            set('projectStreet',    p.project_street);
            set('projectBlkLot',    p.project_blk_lot);
            set('projectCoords',    p.project_coordinates);
            set('projectValue',     p.project_value);
            set('projectStatus',    p.status);

            // Step 3 — Materials
            set('drbs',             p.drbs_value);
            set('sheetPile',        p.sheet_pile_amount);
            set('msPlate',          p.ms_plate);
            set('angleBars',        p.angle_bars);
            set('channelBars',      p.channel_bars);
            set('wideFlange',       p.wide_flange);
            set('giBi',             p.gi_bi);

            // Override submit to PUT
            const form = document.getElementById('encodeForm');
            if (form) {
                form.addEventListener('submit', async function overrideSubmit(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    const payload = NonPriorityForm.buildPayload();
                    payload.id = parseInt(editId);
                    try {
                        const r = await fetch(`${BASE}/api/v1/projects/${editId}`, {
                            method: 'PUT',
                            credentials: 'include',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                        const d = await r.json();
                        if (!r.ok) throw new Error(d.detail || d.message || 'Update failed');
                        if (typeof Toast !== 'undefined') Toast.success('Project updated successfully!');
                        setTimeout(() => { window.location.href = `${BASE}/projects?type=non-priority`; }, 1200);
                    } catch (err) {
                        if (typeof Toast !== 'undefined') Toast.error(err.message || 'Update failed');
                    }
                }, true);
            }

        } catch (err) {
            console.error('[EDIT MODE]', err);
            if (typeof Toast !== 'undefined') Toast.error('Failed to load project data: ' + err.message);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAndFill);
    } else {
        setTimeout(loadAndFill, 200);
    }
})();
