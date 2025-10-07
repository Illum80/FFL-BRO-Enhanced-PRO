// Form 4473 React Component v7.3.1 - Complete Interface
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
                                className: 'btn-view',
                                onClick: () => viewForm(form.id)
                            }, 'View')
                        )
                    )
                )
        )
    );
}

function FormEditor({ form, onSave, onBack, onFormChange, loading }) {
    const [activeSection, setActiveSection] = useState('transferee');

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

    const updateStatus = (newStatus) => {
        onFormChange({ ...form, status: newStatus });
    };

    const transferee = form.transferee_info || {};
    const firearm = form.firearm_info || {};

    return React.createElement('div', { className: 'form-4473-editor' },
        React.createElement('div', { className: 'editor-header' },
            React.createElement('button', { 
                className: 'btn-back',
                onClick: onBack
            }, '← Back'),
            React.createElement('h2', null, 'Edit: ' + form.form_number),
            React.createElement('button', { 
                className: 'btn-save',
                onClick: onSave,
                disabled: loading
            }, loading ? '⏳ Saving...' : '💾 Save')
        ),
        
        React.createElement('div', { className: 'editor-tabs' },
            React.createElement('button', {
                className: 'tab-btn' + (activeSection === 'transferee' ? ' active' : ''),
                onClick: () => setActiveSection('transferee')
            }, 'Section I: Transferee'),
            React.createElement('button', {
                className: 'tab-btn' + (activeSection === 'firearm' ? ' active' : ''),
                onClick: () => setActiveSection('firearm')
            }, 'Section II: Firearm'),
            React.createElement('button', {
                className: 'tab-btn' + (activeSection === 'status' ? ' active' : ''),
                onClick: () => setActiveSection('status')
            }, 'Status')
        ),

        React.createElement('div', { className: 'editor-content' },
            activeSection === 'transferee' && React.createElement('div', { className: 'form-section' },
                React.createElement('h3', null, 'Transferee Information'),
                React.createElement('div', { className: 'form-grid' },
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'First Name'),
                        React.createElement('input', {
                            type: 'text',
                            value: transferee.first_name || '',
                            onChange: (e) => updateTransferee('first_name', e.target.value)
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Last Name'),
                        React.createElement('input', {
                            type: 'text',
                            value: transferee.last_name || '',
                            onChange: (e) => updateTransferee('last_name', e.target.value)
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Date of Birth'),
                        React.createElement('input', {
                            type: 'date',
                            value: transferee.dob || '',
                            onChange: (e) => updateTransferee('dob', e.target.value)
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Address'),
                        React.createElement('input', {
                            type: 'text',
                            value: transferee.address || '',
                            onChange: (e) => updateTransferee('address', e.target.value)
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'City'),
                        React.createElement('input', {
                            type: 'text',
                            value: transferee.city || '',
                            onChange: (e) => updateTransferee('city', e.target.value)
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'State'),
                        React.createElement('input', {
                            type: 'text',
                            value: transferee.state || '',
                            maxLength: 2,
                            onChange: (e) => updateTransferee('state', e.target.value)
                        })
                    )
                )
            ),

            activeSection === 'firearm' && React.createElement('div', { className: 'form-section' },
                React.createElement('h3', null, 'Firearm Information'),
                React.createElement('div', { className: 'form-grid' },
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Manufacturer'),
                        React.createElement('input', {
                            type: 'text',
                            value: firearm.manufacturer || '',
                            onChange: (e) => updateFirearm('manufacturer', e.target.value)
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Model'),
                        React.createElement('input', {
                            type: 'text',
                            value: firearm.model || '',
                            onChange: (e) => updateFirearm('model', e.target.value)
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Serial Number'),
                        React.createElement('input', {
                            type: 'text',
                            value: firearm.serial || '',
                            onChange: (e) => updateFirearm('serial', e.target.value)
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Caliber'),
                        React.createElement('input', {
                            type: 'text',
                            value: firearm.caliber || '',
                            onChange: (e) => updateFirearm('caliber', e.target.value)
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Type'),
                        React.createElement('select', {
                            value: firearm.type || '',
                            onChange: (e) => updateFirearm('type', e.target.value)
                        },
                            React.createElement('option', { value: '' }, 'Select'),
                            React.createElement('option', { value: 'pistol' }, 'Pistol'),
                            React.createElement('option', { value: 'rifle' }, 'Rifle'),
                            React.createElement('option', { value: 'shotgun' }, 'Shotgun'),
                            React.createElement('option', { value: 'other' }, 'Other')
                        )
                    )
                )
            ),

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
                    React.createElement('p', null, React.createElement('strong', null, 'Created: '), new Date(form.date_created).toLocaleString())
                )
            )
        )
    );
}
