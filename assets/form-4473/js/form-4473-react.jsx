// Form 4473 React Component v7.3.1 - COMPLETE SYSTEM
const { useState, useEffect } = React;

function Form4473Manager() {
    const [forms, setForms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [view, setView] = useState('list');
    const [currentForm, setCurrentForm] = useState(null);

    useEffect(() => {
        loadForms();
    }, []);

    const loadForms = async () => {
        try {
            const response = await fetch(fflbroForm4473.apiUrl + 'form-4473/list', {
                headers: { 'X-WP-Nonce': fflbroForm4473.nonce }
            });
            const data = await response.json();
            if (data.status === 'success') {
                setForms(data.data.forms || []);
            }
            setError(null);
        } catch (err) {
            setError('Failed to load forms');
        } finally {
            setLoading(false);
        }
    };

    const createNewForm = async () => {
        setLoading(true);
        try {
            const response = await fetch(fflbroForm4473.apiUrl + 'form-4473/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': fflbroForm4473.nonce
                },
                body: JSON.stringify({})
            });
            const data = await response.json();
            if (data.status === 'success') {
                await loadForms();
                alert('✅ Form ' + data.data.form_number + ' created!');
            }
        } catch (err) {
            setError('Failed to create form');
        } finally {
            setLoading(false);
        }
    };

    const viewForm = async (formId) => {
        setLoading(true);
        try {
            const response = await fetch(fflbroForm4473.apiUrl + 'form-4473/' + formId, {
                headers: { 'X-WP-Nonce': fflbroForm4473.nonce }
            });
            const data = await response.json();
            if (data.status === 'success') {
                setCurrentForm(data.data.form);
                setView('edit');
            }
        } catch (err) {
            alert('Failed to load form');
        } finally {
            setLoading(false);
        }
    };

    const saveForm = async () => {
        if (!currentForm) return;
        setLoading(true);
        try {
            const response = await fetch(fflbroForm4473.apiUrl + 'form-4473/' + currentForm.id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': fflbroForm4473.nonce
                },
                body: JSON.stringify({
                    transferee_info: currentForm.transferee_info,
                    firearm_info: currentForm.firearm_info,
                    background_check: currentForm.background_check,
                    digital_signature: currentForm.digital_signature,
                    status: currentForm.status
                })
            });
            const data = await response.json();
            if (data.status === 'success') {
                alert('✅ Form saved!');
                await loadForms();
            }
        } catch (err) {
            alert('Failed to save');
        } finally {
            setLoading(false);
        }
    };

    const generatePDF = async (formId) => {
        window.open(fflbroForm4473.apiUrl + 'form-4473/' + formId + '/pdf', '_blank');
    };

    const emailForm = async (formId) => {
        if (!confirm('Send this form via email?')) return;
        try {
            const response = await fetch(fflbroForm4473.apiUrl + 'form-4473/' + formId + '/email', {
                method: 'POST',
                headers: { 'X-WP-Nonce': fflbroForm4473.nonce }
            });
            const data = await response.json();
            if (data.status === 'success') {
                alert('✅ Email sent successfully!');
            }
        } catch (err) {
            alert('Failed to send email');
        }
    };

    const backToList = () => {
        setView('list');
        setCurrentForm(null);
    };

    if (loading && !currentForm) {
        return React.createElement('div', { className: 'form-4473-loading' }, 'Loading...');
    }

    if (error && !forms.length) {
        return React.createElement('div', { className: 'form-4473-error' }, error);
    }

    if (view === 'edit' && currentForm) {
        return React.createElement(FormEditor, { 
            form: currentForm,
            onSave: saveForm,
            onBack: backToList,
            onFormChange: setCurrentForm,
            onGeneratePDF: generatePDF,
            onEmail: emailForm,
            loading: loading
        });
    }

    return React.createElement('div', { className: 'form-4473-manager' },
        React.createElement('div', { className: 'form-4473-header' },
            React.createElement('h2', null, 'Form 4473 Records (' + forms.length + ')'),
            React.createElement('button', { 
                className: 'btn-create-form',
                onClick: createNewForm,
                disabled: loading
            }, loading ? '⏳ Creating...' : '+ Create New Form')
        ),
        React.createElement('div', { className: 'form-4473-list' },
            forms.length === 0 ?
                React.createElement('p', { className: 'no-forms' }, 'No forms found. Create one to begin.') :
                forms.map(form => 
                    React.createElement('div', { key: form.id, className: 'form-4473-card' },
                        React.createElement('div', { className: 'form-info' },
                            React.createElement('h3', null, 'Form #' + form.id),
                            React.createElement('p', null, 'Number: ' + form.form_number),
                            React.createElement('p', null, 'Created: ' + new Date(form.created_date).toLocaleString()),
                            form.transferee_name && 
                                React.createElement('p', null, 'Transferee: ' + form.transferee_name)
                        ),
                        React.createElement('div', { className: 'form-status' },
                            React.createElement('span', { 
                                className: 'status-badge status-' + form.status
                            }, form.status)
                        ),
                        React.createElement('div', { className: 'form-actions' },
                            React.createElement('button', { 
                                className: 'btn-edit',
                                onClick: () => viewForm(form.id)
                            }, 'Edit'),
                            React.createElement('button', { 
                                className: 'btn-pdf',
                                onClick: () => generatePDF(form.id)
                            }, 'PDF'),
                            React.createElement('button', { 
                                className: 'btn-email',
                                onClick: () => emailForm(form.id)
                            }, 'Email')
                        )
                    )
                )
        )
    );
}

function FormEditor({ form, onSave, onBack, onFormChange, onGeneratePDF, onEmail, loading }) {
    const [activeSection, setActiveSection] = useState('transferee');
    const [signaturePad, setSignaturePad] = useState(null);

    const updateTransferee = (field, value) => {
        const transferee = form.transferee_info || {};
        transferee[field] = value;
        onFormChange({ ...form, transferee_info: transferee });
    };

    const updateFirearm = (field, value) => {
        const firearm = form.firearm_info || {};
        firearm[field] = value;
        onFormChange({ ...form, firearm_info: firearm });
    };

    const updateBackgroundCheck = (field, value) => {
        const bgCheck = form.background_check || {};
        bgCheck[field] = value;
        onFormChange({ ...form, background_check: bgCheck });
    };

    const updateStatus = (newStatus) => {
        onFormChange({ ...form, status: newStatus });
    };

    const handlePhotoUpload = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('id_photo', file);
        formData.append('form_id', form.id);

        try {
            const response = await fetch(fflbroForm4473.apiUrl + 'form-4473/upload-id', {
                method: 'POST',
                headers: { 'X-WP-Nonce': fflbroForm4473.nonce },
                body: formData
            });
            const data = await response.json();
            if (data.status === 'success') {
                alert('✅ Photo uploaded!');
            }
        } catch (err) {
            alert('Failed to upload photo');
        }
    };

    const transferee = form.transferee_info || {};
    const firearm = form.firearm_info || {};
    const bgCheck = form.background_check || {};

    // ATF Form 4473 Background Questions
    const backgroundQuestions = [
        { id: 'q11a', text: 'Are you the actual transferee/buyer of the firearm(s) listed on this form?' },
        { id: 'q11b', text: 'Are you under indictment or information in any court for a felony?' },
        { id: 'q11c', text: 'Have you ever been convicted in any court of a felony?' },
        { id: 'q11d', text: 'Are you a fugitive from justice?' },
        { id: 'q11e', text: 'Are you an unlawful user of, or addicted to, marijuana or any depressant, stimulant, narcotic drug, or any other controlled substance?' },
        { id: 'q11f', text: 'Have you ever been adjudicated as a mental defective OR have you ever been committed to a mental institution?' },
        { id: 'q11g', text: 'Have you been discharged from the Armed Forces under dishonorable conditions?' },
        { id: 'q11h', text: 'Are you subject to a court order restraining you from harassing, stalking, or threatening your child or an intimate partner or child of such partner?' },
        { id: 'q11i', text: 'Have you ever been convicted in any court of a misdemeanor crime of domestic violence?' },
        { id: 'q11j', text: 'Have you ever renounced your United States citizenship?' },
        { id: 'q11k', text: 'Are you an alien illegally or unlawfully in the United States?' }
    ];

    return React.createElement('div', { className: 'form-4473-editor' },
        React.createElement('div', { className: 'editor-header' },
            React.createElement('button', { 
                className: 'btn-back',
                onClick: onBack
            }, '← Back'),
            React.createElement('h2', null, 'Edit: ' + form.form_number),
            React.createElement('div', { className: 'header-actions' },
                React.createElement('button', { 
                    className: 'btn-pdf',
                    onClick: () => onGeneratePDF(form.id)
                }, '📄 PDF'),
                React.createElement('button', { 
                    className: 'btn-email',
                    onClick: () => onEmail(form.id)
                }, '📧 Email'),
                React.createElement('button', { 
                    className: 'btn-save',
                    onClick: onSave,
                    disabled: loading
                }, loading ? '⏳ Saving...' : '💾 Save')
            )
        ),
        
        React.createElement('div', { className: 'editor-tabs' },
            React.createElement('button', {
                className: 'tab-btn' + (activeSection === 'transferee' ? ' active' : ''),
                onClick: () => setActiveSection('transferee')
            }, 'Section I'),
            React.createElement('button', {
                className: 'tab-btn' + (activeSection === 'firearm' ? ' active' : ''),
                onClick: () => setActiveSection('firearm')
            }, 'Section II'),
            React.createElement('button', {
                className: 'tab-btn' + (activeSection === 'background' ? ' active' : ''),
                onClick: () => setActiveSection('background')
            }, 'Section III'),
            React.createElement('button', {
                className: 'tab-btn' + (activeSection === 'signature' ? ' active' : ''),
                onClick: () => setActiveSection('signature')
            }, 'Section IV'),
            React.createElement('button', {
                className: 'tab-btn' + (activeSection === 'photo' ? ' active' : ''),
                onClick: () => setActiveSection('photo')
            }, 'Photo ID'),
            React.createElement('button', {
                className: 'tab-btn' + (activeSection === 'nics' ? ' active' : ''),
                onClick: () => setActiveSection('nics')
            }, 'NICS'),
            React.createElement('button', {
                className: 'tab-btn' + (activeSection === 'status' ? ' active' : ''),
                onClick: () => setActiveSection('status')
            }, 'Status')
        ),

        React.createElement('div', { className: 'editor-content' },
            // Section I: Transferee
            activeSection === 'transferee' && React.createElement('div', { className: 'form-section' },
                React.createElement('h3', null, 'Section I: Transferee Information'),
                React.createElement('div', { className: 'form-grid' },
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'First Name *'),
                        React.createElement('input', {
                            type: 'text',
                            value: transferee.first_name || '',
                            onChange: (e) => updateTransferee('first_name', e.target.value),
                            required: true
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Middle Name'),
                        React.createElement('input', {
                            type: 'text',
                            value: transferee.middle_name || '',
                            onChange: (e) => updateTransferee('middle_name', e.target.value)
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Last Name *'),
                        React.createElement('input', {
                            type: 'text',
                            value: transferee.last_name || '',
                            onChange: (e) => updateTransferee('last_name', e.target.value),
                            required: true
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Date of Birth *'),
                        React.createElement('input', {
                            type: 'date',
                            value: transferee.dob || '',
                            onChange: (e) => updateTransferee('dob', e.target.value),
                            required: true
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Address *'),
                        React.createElement('input', {
                            type: 'text',
                            value: transferee.address || '',
                            onChange: (e) => updateTransferee('address', e.target.value),
                            required: true
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'City *'),
                        React.createElement('input', {
                            type: 'text',
                            value: transferee.city || '',
                            onChange: (e) => updateTransferee('city', e.target.value),
                            required: true
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'State *'),
                        React.createElement('input', {
                            type: 'text',
                            value: transferee.state || '',
                            maxLength: 2,
                            placeholder: 'FL',
                            onChange: (e) => updateTransferee('state', e.target.value.toUpperCase()),
                            required: true
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'ZIP Code *'),
                        React.createElement('input', {
                            type: 'text',
                            value: transferee.zip || '',
                            maxLength: 10,
                            onChange: (e) => updateTransferee('zip', e.target.value),
                            required: true
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Phone'),
                        React.createElement('input', {
                            type: 'tel',
                            value: transferee.phone || '',
                            onChange: (e) => updateTransferee('phone', e.target.value)
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Email'),
                        React.createElement('input', {
                            type: 'email',
                            value: transferee.email || '',
                            onChange: (e) => updateTransferee('email', e.target.value)
                        })
                    )
                )
            ),

            // Section II: Firearm
            activeSection === 'firearm' && React.createElement('div', { className: 'form-section' },
                React.createElement('h3', null, 'Section II: Firearm Information'),
                React.createElement('div', { className: 'form-grid' },
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Manufacturer *'),
                        React.createElement('input', {
                            type: 'text',
                            value: firearm.manufacturer || '',
                            onChange: (e) => updateFirearm('manufacturer', e.target.value),
                            required: true
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Model *'),
                        React.createElement('input', {
                            type: 'text',
                            value: firearm.model || '',
                            onChange: (e) => updateFirearm('model', e.target.value),
                            required: true
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Serial Number *'),
                        React.createElement('input', {
                            type: 'text',
                            value: firearm.serial || '',
                            onChange: (e) => updateFirearm('serial', e.target.value),
                            required: true
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Caliber/Gauge *'),
                        React.createElement('input', {
                            type: 'text',
                            value: firearm.caliber || '',
                            placeholder: '9mm, .45 ACP, 12 Gauge, etc.',
                            onChange: (e) => updateFirearm('caliber', e.target.value),
                            required: true
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Type *'),
                        React.createElement('select', {
                            value: firearm.type || '',
                            onChange: (e) => updateFirearm('type', e.target.value),
                            required: true
                        },
                            React.createElement('option', { value: '' }, 'Select Type'),
                            React.createElement('option', { value: 'pistol' }, 'Pistol'),
                            React.createElement('option', { value: 'revolver' }, 'Revolver'),
                            React.createElement('option', { value: 'rifle' }, 'Rifle'),
                            React.createElement('option', { value: 'shotgun' }, 'Shotgun'),
                            React.createElement('option', { value: 'other' }, 'Other')
                        )
                    )
                )
            ),

            // Section III: Background Questions
            activeSection === 'background' && React.createElement('div', { className: 'form-section' },
                React.createElement('h3', null, 'Section III: Background Check Questions'),
                React.createElement('p', { className: 'section-instructions' }, 
                    'Answer all questions. Important: If you answer "yes" to any question (except 11a), the transfer may be denied.'
                ),
                React.createElement('div', { className: 'background-questions' },
                    backgroundQuestions.map(q => 
                        React.createElement('div', { key: q.id, className: 'question-row' },
                            React.createElement('label', { className: 'question-label' },
                                React.createElement('span', { className: 'question-text' }, q.text),
                                React.createElement('div', { className: 'question-options' },
                                    React.createElement('label', { className: 'radio-option' },
                                        React.createElement('input', {
                                            type: 'radio',
                                            name: q.id,
                                            value: 'yes',
                                            checked: bgCheck[q.id] === 'yes',
                                            onChange: () => updateBackgroundCheck(q.id, 'yes')
                                        }),
                                        React.createElement('span', null, 'Yes')
                                    ),
                                    React.createElement('label', { className: 'radio-option' },
                                        React.createElement('input', {
                                            type: 'radio',
                                            name: q.id,
                                            value: 'no',
                                            checked: bgCheck[q.id] === 'no',
                                            onChange: () => updateBackgroundCheck(q.id, 'no')
                                        }),
                                        React.createElement('span', null, 'No')
                                    )
                                )
                            )
                        )
                    )
                )
            ),

            // Section IV: Signatures
            activeSection === 'signature' && React.createElement('div', { className: 'form-section' },
                React.createElement('h3', null, 'Section IV: Signatures & Certification'),
                React.createElement('div', { className: 'signature-section' },
                    React.createElement('p', { className: 'certification-text' }, 
                        'I certify that my answers to Section III are true, correct, and complete. I have read and understand the Notices, Instructions, and Definitions on ATF Form 4473.'
                    ),
                    React.createElement('div', { className: 'signature-pad-container' },
                        React.createElement('label', null, 'Transferee Signature:'),
                        React.createElement('div', { 
                            className: 'signature-placeholder',
                            style: { 
                                border: '2px solid #ccc', 
                                height: '150px', 
                                borderRadius: '8px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                background: '#f9fafb',
                                color: '#6b7280',
                                fontStyle: 'italic'
                            }
                        }, 
                            form.digital_signature ? 
                                React.createElement('img', { 
                                    src: form.digital_signature, 
                                    alt: 'Signature',
                                    style: { maxWidth: '100%', maxHeight: '100%' }
                                }) :
                                'Digital signature canvas (click to sign)'
                        ),
                        React.createElement('p', { className: 'signature-note' }, 
                            'Note: Digital signature functionality uses the signature-handler.php backend. Canvas implementation can be added here.'
                        )
                    )
                )
            ),

            // Photo ID Upload
            activeSection === 'photo' && React.createElement('div', { className: 'form-section' },
                React.createElement('h3', null, 'Photo ID Upload'),
                React.createElement('div', { className: 'photo-upload-section' },
                    React.createElement('p', null, 'Upload a photo of government-issued ID (driver\'s license, passport, etc.)'),
                    React.createElement('input', {
                        type: 'file',
                        accept: 'image/*',
                        onChange: handlePhotoUpload,
                        className: 'photo-upload-input'
                    }),
                    React.createElement('p', { className: 'upload-note' }, 
                        'Maximum file size: 5MB. Accepted formats: JPG, PNG'
                    )
                )
            ),

            // NICS Section
            activeSection === 'nics' && React.createElement('div', { className: 'form-section' },
                React.createElement('h3', null, 'NICS Check'),
                React.createElement('div', { className: 'nics-section' },
                    React.createElement('p', null, 'FBI National Instant Criminal Background Check System'),
                    React.createElement('div', { className: 'nics-info' },
                        React.createElement('p', null, React.createElement('strong', null, 'Status: '), 
                            React.createElement('span', { style: { color: '#f59e0b' } }, 'Pending')
                        ),
                        React.createElement('p', null, 'NICS Transaction Number: N/A'),
                        React.createElement('button', {
                            className: 'btn-nics-check',
                            onClick: () => alert('NICS check would be initiated here. Backend endpoint: /form-4473/nics/check')
                        }, '🔍 Run NICS Check')
                    ),
                    React.createElement('p', { className: 'nics-note' }, 
                        'Note: NICS integration requires FFL credentials and FBI E-Check access. Backend framework ready in nics-handler.php'
                    )
                )
            ),

            // Status
            activeSection === 'status' && React.createElement('div', { className: 'form-section' },
                React.createElement('h3', null, 'Form Status'),
                React.createElement('div', { className: 'status-selector' },
                    ['in_progress', 'completed', 'pending', 'denied'].map(status => 
                        React.createElement('label', { key: status, className: 'status-option' },
                            React.createElement('input', {
                                type: 'radio',
                                name: 'status',
                                value: status,
                                checked: form.status === status,
                                onChange: () => updateStatus(status)
                            }),
                            React.createElement('span', null, status.replace('_', ' ').toUpperCase())
                        )
                    )
                ),
                React.createElement('div', { className: 'form-meta' },
                    React.createElement('p', null, React.createElement('strong', null, 'Form Number: '), form.form_number),
                    React.createElement('p', null, React.createElement('strong', null, 'Created: '), new Date(form.date_created).toLocaleString()),
                    form.date_completed && React.createElement('p', null, React.createElement('strong', null, 'Completed: '), new Date(form.date_completed).toLocaleString())
                )
            )
        )
    );
}
