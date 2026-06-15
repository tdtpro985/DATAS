/* ============================================================
   encode-priority.js — Priority Form (4 Steps)
   ============================================================ */

const PriorityForm = {
    currentStep: 1,
    totalSteps: 4,
    form: null,
    uploadedFiles: [],

    init() {
        this.form = document.getElementById('encodeForm');
        if (!this.form) return;

        // Event listeners for step navigation - delegate to form
        this.form.addEventListener('click', (e) => {
            if (e.target.id && e.target.id.startsWith('nextBtn')) {
                e.preventDefault();
                this.nextStep();
            } else if (e.target.id && e.target.id.startsWith('prevBtn')) {
                e.preventDefault();
                this.prevStep();
            }
        });
        
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Initialize file upload
        this.initFileUpload();

        // Handle source dropdown change
        document.getElementById('source').addEventListener('change', (e) => {
            const sourceOtherGroup = document.getElementById('sourceOtherGroup');
            const sourceOtherInput = document.getElementById('sourceOther');
            const philgepsNoticeGroup = document.getElementById('philgepsNoticeGroup');
            const philgepsNoticeInput = document.getElementById('philgepsNotice');
            
            if (e.target.value === 'Other') {
                sourceOtherGroup.style.display = 'block';
                sourceOtherInput.required = true;
                philgepsNoticeGroup.style.display = 'none';
                philgepsNoticeInput.required = false;
                philgepsNoticeInput.value = '';
            } else if (e.target.value === 'PHILGEPS') {
                sourceOtherGroup.style.display = 'none';
                sourceOtherInput.required = false;
                sourceOtherInput.value = '';
                philgepsNoticeGroup.style.display = 'block';
                philgepsNoticeInput.required = true;
            } else {
                sourceOtherGroup.style.display = 'none';
                sourceOtherInput.required = false;
                sourceOtherInput.value = '';
                philgepsNoticeGroup.style.display = 'none';
                philgepsNoticeInput.required = false;
                philgepsNoticeInput.value = '';
            }
        });

        // Load initial countries
        this.loadCountries('contractCountry');
        this.loadCountries('projectCountry');

        // Set up location cascades for contractor
        document.getElementById('contractCountry').addEventListener('change', () => {
            this.loadRegions('contractCountry', 'contractRegion');
            // Clear dependent dropdowns
            this.clearDropdown('contractProvince');
            this.clearDropdown('contractCity');
        });
        document.getElementById('contractRegion').addEventListener('change', () => {
            this.loadProvinces('contractCountry', 'contractRegion', 'contractProvince');
            this.clearDropdown('contractCity');
        });
        document.getElementById('contractProvince').addEventListener('change', () => {
            this.loadCities('contractCountry', 'contractRegion', 'contractProvince', 'contractCity');
        });
        document.getElementById('contractCity').addEventListener('change', () => {
            // Barangay is now a free text field, no need to load options
        });

        // Set up location cascades for project
        document.getElementById('projectCountry').addEventListener('change', () => {
            this.loadRegions('projectCountry', 'projectRegion');
            // Clear dependent dropdowns
            this.clearDropdown('projectProvince');
            this.clearDropdown('projectCity');
        });
        document.getElementById('projectRegion').addEventListener('change', () => {
            this.loadProvinces('projectCountry', 'projectRegion', 'projectProvince');
            this.clearDropdown('projectCity');
        });
        document.getElementById('projectProvince').addEventListener('change', () => {
            this.loadCities('projectCountry', 'projectRegion', 'projectProvince', 'projectCity');
        });
        document.getElementById('projectCity').addEventListener('change', () => {
            // Barangay is now a free text field, no need to load options
        });
    },

    initFileUpload() {
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const fileItems = document.getElementById('fileItems');

        // Click to browse files
        uploadArea.addEventListener('click', () => fileInput.click());
        
        // Browse link click
        const browseLinkSpan = uploadArea.querySelector('.upload-browse');
        if (browseLinkSpan) {
            browseLinkSpan.addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.click();
            });
        }

        // File input change
        fileInput.addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            this.handleFiles(e.dataTransfer.files);
        });
    },

    handleFiles(files) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        
        Array.from(files).forEach(file => {
            // Validate file size
            if (file.size > maxSize) {
                Toast.error(`File "${file.name}" is too large. Maximum size is 10MB.`);
                return;
            }

            // Validate file type
            if (!allowedTypes.includes(file.type)) {
                Toast.error(`File "${file.name}" is not supported. Only JPG, PNG, and PDF files are allowed.`);
                return;
            }

            // Check if file already exists
            if (this.uploadedFiles.some(f => f.name === file.name && f.size === file.size)) {
                Toast.warning(`File "${file.name}" is already selected.`);
                return;
            }

            // Add file to list
            this.uploadedFiles.push(file);
            this.updateFileList();
        });
    },

    updateFileList() {
        const fileList = document.getElementById('fileList');
        const fileItems = document.getElementById('fileItems');

        if (this.uploadedFiles.length === 0) {
            fileList.style.display = 'none';
            return;
        }

        fileList.style.display = 'block';
        fileItems.innerHTML = '';

        this.uploadedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            const fileIcon = this.getFileIcon(file.type);
            const fileSize = this.formatFileSize(file.size);

            fileItem.innerHTML = `
                <div class="file-info">
                    <div class="file-icon">${fileIcon}</div>
                    <div class="file-details">
                        <div class="file-name">${file.name}</div>
                        <div class="file-size">${fileSize}</div>
                    </div>
                </div>
                <button type="button" class="file-remove" onclick="PriorityForm.removeFile(${index})">Remove</button>
            `;

            fileItems.appendChild(fileItem);
        });
    },

    removeFile(index) {
        this.uploadedFiles.splice(index, 1);
        this.updateFileList();
        Toast.info('File removed from upload list.');
    },

    getFileIcon(fileType) {
        if (fileType.startsWith('image/')) return '🖼️';
        if (fileType === 'application/pdf') return '📄';
        return '📁';
    },

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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

        // Update buttons
        document.getElementById('prevBtn').style.display = stepNum > 1 ? 'block' : 'none';
        document.getElementById('nextBtn').style.display = stepNum < this.totalSteps ? 'block' : 'none';
        document.getElementById('submitBtn').style.display = stepNum === this.totalSteps ? 'block' : 'none';

        this.currentStep = stepNum;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    validateStep(stepNum) {
        const requiredFields = {
            1: ['publishedDate', 'source', 'contractId', 'contractorName', 'contactNumber', 'contractCountry', 'contractRegion', 'contractProvince', 'contractCity'],
            2: ['projectName', 'projectCountry', 'projectRegion', 'projectProvince', 'projectCity', 'projectValue', 'completionRate'],
            3: [], // Step 3 is optional
            4: []  // Step 4 is optional
        };

        const fields = requiredFields[stepNum] || [];
        for (const fieldId of fields) {
            const field = document.getElementById(fieldId);
            if (!field || !field.value) {
                return false;
            }
        }

        // Special validation for "Other" source
        if (stepNum === 1) {
            const sourceField = document.getElementById('source');
            const sourceOtherField = document.getElementById('sourceOther');
            const philgepsNoticeField = document.getElementById('philgepsNotice');
            
            if (sourceField.value === 'Other' && (!sourceOtherField.value || sourceOtherField.value.trim() === '')) {
                return false;
            }
            
            if (sourceField.value === 'PHILGEPS' && (!philgepsNoticeField.value || philgepsNoticeField.value.trim() === '')) {
                return false;
            }
        }

        return true;
    },

    async loadCountries(countrySelectId) {
        const countrySelect = document.getElementById(countrySelectId);

        try {
            const response = await fetch(`${BASE}/api/locations.php?action=countries`);
            const data = await response.json();

            countrySelect.innerHTML = '<option value="">Select country</option>';
            if (data.countries) {
                data.countries.forEach(country => {
                    const option = document.createElement('option');
                    option.value = country.code;
                    option.textContent = country.name;
                    // Set Philippines as default
                    if (country.code === 'PH') {
                        option.selected = true;
                    }
                    countrySelect.appendChild(option);
                });
                
                // Auto-load regions for Philippines if it's selected by default
                if (countrySelect.value === 'PH') {
                    const regionSelectId = countrySelectId.replace('Country', 'Region');
                    this.loadRegions(countrySelectId, regionSelectId);
                }
            }
        } catch (error) {
            console.error('Error loading countries:', error);
        }
    },

    async loadRegions(countrySelectId, regionSelectId) {
        const country = document.getElementById(countrySelectId).value === 'Philippines' ? 'PH' : (document.getElementById(countrySelectId).value || 'PH');
        
        // Get the corresponding datalist for the region input
        let datalistId;
        if (regionSelectId === 'contractRegion') {
            datalistId = 'contractRegionList';
        } else if (regionSelectId === 'projectRegion') {
            datalistId = 'projectRegionList';
        }
        
        const regionDatalist = document.getElementById(datalistId);
        if (!regionDatalist) {
            console.error('Datalist not found for:', datalistId);
            return;
        }

        if (!country) {
            regionDatalist.innerHTML = '';
            return;
        }

        try {
            const response = await fetch(`${BASE}/api/locations.php?action=regions&country=${country}`);
            const data = await response.json();

            // Clear existing options
            regionDatalist.innerHTML = '';
            
            if (data.regions) {
                data.regions.forEach(region => {
                    const option = document.createElement('option');
                    option.value = region.name; // Use name for display
                    regionDatalist.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading regions:', error);
        }
    },

    clearDropdown(inputId) {
        const input = document.getElementById(inputId);
        if (input) {
            // Clear the input value for input elements
            input.value = '';
            
            // Also clear the corresponding datalist if it exists
            let datalistId;
            if (inputId === 'contractProvince') datalistId = 'contractProvinceList';
            else if (inputId === 'contractCity') datalistId = 'contractCityList';
            else if (inputId === 'projectProvince') datalistId = 'projectProvinceList';
            else if (inputId === 'projectCity') datalistId = 'projectCityList';
            
            if (datalistId) {
                const datalist = document.getElementById(datalistId);
                if (datalist) {
                    datalist.innerHTML = '';
                }
            }
        }
    },

    async loadProvinces(countryId, regionId, provinceId) {
        const country = document.getElementById(countryId).value === 'Philippines' ? 'PH' : (document.getElementById(countryId).value || 'PH');
        const region = document.getElementById(regionId).value;
        
        // Get the corresponding datalist for the province input
        let datalistId;
        if (provinceId === 'contractProvince') {
            datalistId = 'contractProvinceList';
        } else if (provinceId === 'projectProvince') {
            datalistId = 'projectProvinceList';
        }
        
        const provinceDatalist = document.getElementById(datalistId);
        if (!provinceDatalist) {
            console.error('Datalist not found for:', datalistId);
            return;
        }

        if (!country || !region) {
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
            datalistId = 'contractCityList';
        } else if (cityId === 'projectCity') {
            datalistId = 'projectCityList';
        }
        
        const cityDatalist = document.getElementById(datalistId);
        if (!cityDatalist) {
            console.error('Datalist not found for:', datalistId);
            return;
        }

        if (!country || !region) {
            cityDatalist.innerHTML = '';
            return;
        }

        try {
            const url = province 
                ? `${BASE}/api/locations.php?action=cities&country=${country}&region=${region}&province=${province}`
                : `${BASE}/api/locations.php?action=cities&country=${country}&region=${region}`;
            
            const response = await fetch(url);
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
        // Note: Barangay fields are now text inputs (not dropdowns), so this function is not used
        // Keeping it here for backward compatibility but it won't affect the form
        const country = document.getElementById(countryId).value;
        const region = document.getElementById(regionId).value;
        const city = document.getElementById(cityId).value;
        const barangayInput = document.getElementById(barangayId);

        // Don't hide or manipulate the barangay input field since it's a free text field
        if (!country || !region || !city || !barangayInput) {
            return;
        }

        // You can optionally load barangay suggestions here if needed in the future
        // For now, users can manually type the barangay name
    },

    async handleSubmit(event) {
        event.preventDefault();

        if (!this.validateStep(this.totalSteps)) {
            Toast.error('Please complete all required fields');
            return;
        }

        // Show upload progress if files are selected
        if (this.uploadedFiles.length > 0) {
            this.showUploadProgress();
        }

        const payload = this.buildPayload();

        try {
            // First, submit the project data
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

            const projectId = data.project_id || data.id;

            // Upload files if any
            if (this.uploadedFiles.length > 0 && projectId) {
                await this.uploadFiles(projectId);
            }

            this.hideUploadProgress();
            Toast.success('Priority project encoded successfully!');
            setTimeout(() => {
                window.location.href = `${BASE}/encode`;
            }, 1500);

        } catch (error) {
            console.error('Submit error:', error);
            this.hideUploadProgress();
            Toast.error(error.message || 'Unable to submit project');
        }
    },

    async uploadFiles(projectId) {
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        for (let i = 0; i < this.uploadedFiles.length; i++) {
            const file = this.uploadedFiles[i];
            const formData = new FormData();
            formData.append('file', file);
            formData.append('project_id', projectId);
            formData.append('file_type', file.type.startsWith('image/') ? 'image' : 'document');

            try {
                const response = await fetch(`${BASE}/api/v1/projects/upload`, {
                    method: 'POST',
                    credentials: 'include',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Failed to upload ${file.name}`);
                }

                // Update progress
                const progress = Math.round(((i + 1) / this.uploadedFiles.length) * 100);
                progressFill.style.width = `${progress}%`;
                progressText.textContent = `Uploading... ${progress}%`;

            } catch (error) {
                console.error(`Upload error for ${file.name}:`, error);
                Toast.warning(`Failed to upload ${file.name}: ${error.message}`);
            }
        }
    },

    showUploadProgress() {
        const uploadProgress = document.getElementById('uploadProgress');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        uploadProgress.style.display = 'block';
        progressFill.style.width = '0%';
        progressText.textContent = 'Uploading... 0%';
    },

    hideUploadProgress() {
        const uploadProgress = document.getElementById('uploadProgress');
        uploadProgress.style.display = 'none';
    },

    buildPayload() {
        const getFieldValue = (id) => document.getElementById(id).value || null;
        const getFieldNumber = (id) => {
            const val = document.getElementById(id).value;
            return val === '' ? null : parseFloat(val);
        };

        // Handle source field - use sourceOther if "Other" is selected
        const sourceValue = getFieldValue('source');
        const finalSource = sourceValue === 'Other' ? getFieldValue('sourceOther') : sourceValue;
        const noticeReferenceNumber = sourceValue === 'PHILGEPS' ? getFieldValue('philgepsNotice') : null;

        const payload = {
            contractor_name: getFieldValue('contractorName'),
            contact_person: getFieldValue('contactPerson'),
            contact_number: getFieldValue('contactNumber'),
            contract_id: getFieldValue('contractId'),
            source: finalSource,
            notice_reference_number: noticeReferenceNumber,
            publication_date: getFieldValue('publishedDate'),
            
            // Contractor Location
            contract_country: getFieldValue('contractCountry'),
            contract_region: getFieldValue('contractRegion'),
            contract_province: getFieldValue('contractProvince'),
            contract_city: getFieldValue('contractCity'),
            contract_barangay: getFieldValue('contractBarangay'),
            contract_street: getFieldValue('contractStreet'),
            contract_blk_lot: getFieldValue('contractBlkLot'),
            contract_coordinates: getFieldValue('contractCoords'),
            
            // Legacy fields (kept for compatibility)
            address: getFieldValue('contractStreet'),
            region: getFieldValue('contractCity'),
            city_province: getFieldValue('contractBarangay'),
            
            // Project Details
            project_name: getFieldValue('projectName'),
            project_id: getFieldValue('projectId'),
            project_value: getFieldNumber('projectValue'),
            accomplishment_rate: getFieldNumber('completionRate'),
            
            // Project Location
            project_country: getFieldValue('projectCountry'),
            project_region: getFieldValue('projectRegion'),
            project_province: getFieldValue('projectProvince'),
            project_city: getFieldValue('projectCity'),
            project_barangay: getFieldValue('projectBarangay'),
            project_street: getFieldValue('projectStreet'),
            project_blk_lot: getFieldValue('projectBlkLot'),
            project_coordinates: getFieldValue('projectCoords'),
            
            // Materials
            sheet_pile_type: getFieldValue('sheetPileMaterial'),
            sheet_pile_amount: getFieldNumber('sheetPileValue'),
            drbs: getFieldValue('drbsMaterial'),
            drbs_value: getFieldNumber('drbsValue'),
            
            status: 'Priority',
            form_type: 'priority'
        };
        
        // Debug: Log contractor location fields
        console.log('[PRIORITY ENCODE] Contractor Location:', {
            contract_country: payload.contract_country,
            contract_region: payload.contract_region,
            contract_province: payload.contract_province,
            contract_city: payload.contract_city
        });
        
        return payload;
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => PriorityForm.init());
} else {
    PriorityForm.init();
}

/* ── Edit Mode ─────────────────────────────────────────── */
(function initPriorityEditMode() {
    const editId = new URLSearchParams(window.location.search).get('edit');
    if (!editId) return;

    async function loadAndFill() {
        try {
            document.querySelectorAll('h1').forEach(el => { if (el.textContent.includes('Encode')) el.textContent = 'Edit Priority Project'; });
            document.querySelectorAll('p').forEach(el => { if (el.textContent.includes('Complete all')) el.textContent = 'Update the project details below.'; });
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) submitBtn.textContent = '✓ Save Changes';

            const res = await fetch(`${BASE}/api/v1/projects?db_id=${editId}&size=1`, { credentials: 'include' });
            if (!res.ok) throw new Error('Failed to load project');
            const data = await res.json();
            const p = data.projects?.[0];
            if (!p || !p.id) throw new Error('Project not found');

            const set = (id, val) => { const el = document.getElementById(id); if (el && val !== null && val !== undefined) el.value = val; };

            // Step 1
            set('publishedDate',    p.publication_date);
            set('source',           p.source);
            if (p.source === 'PHILGEPS') { set('philgepsNotice', p.notice_reference_number); const g = document.getElementById('philgepsNoticeGroup'); if (g) g.style.display = 'block'; }
            set('contractId',       p.contractor_id);
            set('contractCountry',  p.contract_country || 'Philippines');
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

            // Step 2
            set('projectId',        p.project_id);
            set('projectName',      p.project_name);
            set('projectValue',     p.project_value);
            set('completionRate',   p.accomplishment_rate);
            set('projectCountry',   p.project_country || 'Philippines');
            set('projectRegion',    p.project_region);
            set('projectProvince',  p.project_province);
            set('projectCity',      p.project_city);
            set('projectBarangay',  p.project_barangay);
            set('projectStreet',    p.project_street);
            set('projectBlkLot',    p.project_blk_lot);
            set('projectCoords',    p.project_coordinates);

            // Step 3 — Materials
            set('sheetPileMaterial', p.sheet_pile_type);
            set('sheetPileValue',    p.sheet_pile_amount);
            set('drbsMaterial',      p.drbs);
            set('drbsValue',         p.drbs_value);

            // Override submit to PUT
            const form = document.getElementById('encodeForm');
            if (form) {
                form.addEventListener('submit', async function overrideSubmit(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    const payload = PriorityForm.buildPayload();
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
                        setTimeout(() => { window.location.href = `${BASE}/projects?type=priority`; }, 1200);
                    } catch (err) {
                        if (typeof Toast !== 'undefined') Toast.error(err.message || 'Update failed');
                    }
                }, true);
            }
        } catch (err) {
            console.error('[PRIORITY EDIT MODE]', err);
            if (typeof Toast !== 'undefined') Toast.error('Failed to load project: ' + err.message);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAndFill);
    } else {
        setTimeout(loadAndFill, 200);
    }
})();
