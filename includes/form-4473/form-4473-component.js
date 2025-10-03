console.log("Form 4473 component script loaded");
const { useState, useEffect } = React;

const Form4473App = () => {
    const [forms, setForms] = useState([]);
    const [currentForm, setCurrentForm] = useState(null);
    const [loading, setLoading] = useState(false);
    const [view, setView] = useState('list'); // 'list', 'create', 'edit'
    const [currentSection, setCurrentSection] = useState('a'); // 'a', 'b', 'c', 'd'

    useEffect(() => {
        loadForms();
    }, []);

    const loadForms = async () => {
        setLoading(true);
        try {
            const response = await fetch(
                `${fflbroForm4473.ajaxurl}?action=fflbro_4473_list_forms&nonce=${fflbroForm4473.nonce}`
            );
            const data = await response.json();
            console.log("Received data:", JSON.stringify(data, null, 2));
            console.log("Checking data.success:", data.success);
            if (data.success) {
                setForms(data.data.forms);
            }
        } catch (error) {
            console.error('Error loading forms:', error);
        }
        setLoading(false);
    };

    const createNewForm = async () => {
        console.log("createNewForm called");
        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('action', 'fflbro_4473_create');
            formData.append('nonce', fflbroForm4473.nonce);

            const response = await fetch(fflbroForm4473.ajaxurl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            console.log("Received data:", JSON.stringify(data, null, 2));
            console.log("Checking data.success:", data.success);
            if (data.success) {
                setCurrentForm({
                    id: data.data.form_id,
                    form_number: data.data.form_number,
                    status: 'in_progress'
                });
                setView('edit');
                setCurrentSection('a');
            }
        } catch (error) {
            console.error('Error creating form:', error);
        }
        setLoading(false);
    };

    const saveSection = async (sectionData) => {
        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('action', 'fflbro_4473_save_section');
            formData.append('nonce', fflbroForm4473.nonce);
            formData.append('form_id', currentForm.id);
            formData.append('section', `section_${currentSection}`);
            formData.append('data', JSON.stringify(sectionData));

            const response = await fetch(fflbroForm4473.ajaxurl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            console.log("Received data:", JSON.stringify(data, null, 2));
            console.log("Checking data.success:", data.success);
            if (data.success) {
                alert('Section saved successfully!');
                // Move to next section
                const sections = ['a', 'b', 'c', 'd'];
                const currentIndex = sections.indexOf(currentSection);
                if (currentIndex < sections.length - 1) {
                    setCurrentSection(sections[currentIndex + 1]);
                }
            }
        } catch (error) {
            console.error('Error saving section:', error);
        }
        setLoading(false);
    };

    if (loading) {
        return React.createElement('div', { className: 'fflbro-loading' }, 'Loading...');
    }

    if (view === 'list') {
        return React.createElement('div', { className: 'fflbro-form-4473-list' },
            React.createElement('div', { className: 'fflbro-header' },
                React.createElement('h2', null, 'ATF Form 4473 Records'),
                React.createElement('button', {
                    className: 'button button-primary',
                    onClick: createNewForm
                }, '+ New Form 4473')
            ),
            React.createElement('table', { className: 'wp-list-table widefat fixed striped' },
                React.createElement('thead', null,
                    React.createElement('tr', null,
                        React.createElement('th', null, 'Form Number'),
                        React.createElement('th', null, 'Customer'),
                        React.createElement('th', null, 'Status'),
                        React.createElement('th', null, 'Date'),
                        React.createElement('th', null, 'Actions')
                    )
                ),
                React.createElement('tbody', null,
                    forms.length === 0 ?
                        React.createElement('tr', null,
                            React.createElement('td', { colSpan: 5, style: { textAlign: 'center', padding: '40px' } },
                                'No forms found. Click "New Form 4473" to create one.'
                            )
                        ) :
                        forms.map(form =>
                            React.createElement('tr', { key: form.id },
                                React.createElement('td', null, form.form_number),
                                React.createElement('td', null, form.first_name && form.last_name ? 
                                    `${form.first_name} ${form.last_name}` : '-'),
                                React.createElement('td', null,
                                    React.createElement('span', {
                                        className: `status-badge status-${form.status}`
                                    }, form.status)
                                ),
                                React.createElement('td', null, new Date(form.created_at).toLocaleDateString()),
                                React.createElement('td', null,
                                    React.createElement('button', {
                                        className: 'button button-small',
                                        onClick: () => {
                                            setCurrentForm(form);
                                            setView('edit');
                                        }
                                    }, 'Edit'),
                                    ' ',
                                    React.createElement('button', {
                                        className: 'button button-small',
                                        onClick: () => alert('PDF generation coming soon')
                                    }, 'PDF')
                                )
                            )
                        )
                )
            )
        );
    }

    if (view === 'edit') {
        return React.createElement('div', { className: 'fflbro-form-4473-edit' },
            React.createElement('div', { className: 'fflbro-header' },
                React.createElement('button', {
                    className: 'button',
                    onClick: () => {
                        setView('list');
                        loadForms();
                    }
                }, '← Back to List'),
                React.createElement('h2', null, `Form 4473: ${currentForm.form_number}`)
            ),
            React.createElement('div', { className: 'fflbro-section-tabs' },
                ['a', 'b', 'c', 'd'].map(section =>
                    React.createElement('button', {
                        key: section,
                        className: currentSection === section ? 'tab active' : 'tab',
                        onClick: () => setCurrentSection(section)
                    }, `Section ${section.toUpperCase()}`)
                )
            ),
            React.createElement('div', { className: 'fflbro-section-content' },
                currentSection === 'a' && React.createElement(SectionA, { 
                    formId: currentForm.id, 
                    onSave: saveSection 
                }),
                currentSection === 'b' && React.createElement(SectionB, { 
                    formId: currentForm.id, 
                    onSave: saveSection 
                }),
                currentSection === 'c' && React.createElement(SectionC, { 
                    formId: currentForm.id, 
                    onSave: saveSection 
                }),
                currentSection === 'd' && React.createElement(SectionD, { 
                    formId: currentForm.id 
                })
            )
        );
    }
};

// Section A - Transferee Information
const SectionA = ({ formId, onSave }) => {
    const [data, setData] = useState({
        last_name: '',
        first_name: '',
        middle_name: '',
        date_of_birth: '',
        residence_address: '',
        residence_city: '',
        residence_state: '',
        residence_zip: '',
        email: '',
        phone: ''
    });

    return React.createElement('div', { className: 'section-form' },
        React.createElement('h3', null, 'Section A: Transferee (Buyer) Information'),
        React.createElement('div', { className: 'form-grid' },
            React.createElement('div', { className: 'form-field' },
                React.createElement('label', null, 'Last Name *'),
                React.createElement('input', {
                    type: 'text',
                    value: data.last_name,
                    onChange: (e) => setData({...data, last_name: e.target.value}),
                    required: true
                })
            ),
            React.createElement('div', { className: 'form-field' },
                React.createElement('label', null, 'First Name *'),
                React.createElement('input', {
                    type: 'text',
                    value: data.first_name,
                    onChange: (e) => setData({...data, first_name: e.target.value}),
                    required: true
                })
            ),
            React.createElement('div', { className: 'form-field' },
                React.createElement('label', null, 'Middle Name'),
                React.createElement('input', {
                    type: 'text',
                    value: data.middle_name,
                    onChange: (e) => setData({...data, middle_name: e.target.value})
                })
            ),
            React.createElement('div', { className: 'form-field' },
                React.createElement('label', null, 'Date of Birth *'),
                React.createElement('input', {
                    type: 'date',
                    value: data.date_of_birth,
                    onChange: (e) => setData({...data, date_of_birth: e.target.value}),
                    required: true
                })
            ),
            React.createElement('div', { className: 'form-field full-width' },
                React.createElement('label', null, 'Residence Address *'),
                React.createElement('input', {
                    type: 'text',
                    value: data.residence_address,
                    onChange: (e) => setData({...data, residence_address: e.target.value}),
                    required: true
                })
            ),
            React.createElement('div', { className: 'form-field' },
                React.createElement('label', null, 'City *'),
                React.createElement('input', {
                    type: 'text',
                    value: data.residence_city,
                    onChange: (e) => setData({...data, residence_city: e.target.value}),
                    required: true
                })
            ),
            React.createElement('div', { className: 'form-field' },
                React.createElement('label', null, 'State *'),
                React.createElement('input', {
                    type: 'text',
                    maxLength: 2,
                    value: data.residence_state,
                    onChange: (e) => setData({...data, residence_state: e.target.value.toUpperCase()}),
                    required: true
                })
            ),
            React.createElement('div', { className: 'form-field' },
                React.createElement('label', null, 'ZIP Code *'),
                React.createElement('input', {
                    type: 'text',
                    value: data.residence_zip,
                    onChange: (e) => setData({...data, residence_zip: e.target.value}),
                    required: true
                })
            ),
            React.createElement('div', { className: 'form-field' },
                React.createElement('label', null, 'Email'),
                React.createElement('input', {
                    type: 'email',
                    value: data.email,
                    onChange: (e) => setData({...data, email: e.target.value})
                })
            ),
            React.createElement('div', { className: 'form-field' },
                React.createElement('label', null, 'Phone'),
                React.createElement('input', {
                    type: 'tel',
                    value: data.phone,
                    onChange: (e) => setData({...data, phone: e.target.value})
                })
            )
        ),
        React.createElement('div', { className: 'form-actions' },
            React.createElement('button', {
                className: 'button button-primary',
                onClick: () => onSave(data)
            }, 'Save & Continue →')
        )
    );
};

// Section B - Firearms Information
const SectionB = ({ formId, onSave }) => {
    const [firearms, setFirearms] = useState([{
        type: 'handgun',
        manufacturer: '',
        model: '',
        serial_number: '',
        caliber: '',
        price: ''
    }]);

    const addFirearm = () => {
        setFirearms([...firearms, {
            type: 'handgun',
            manufacturer: '',
            model: '',
            serial_number: '',
            caliber: '',
            price: ''
        }]);
    };

    return React.createElement('div', { className: 'section-form' },
        React.createElement('h3', null, 'Section B: Firearm(s) Description'),
        firearms.map((firearm, index) =>
            React.createElement('div', { key: index, className: 'firearm-entry' },
                React.createElement('h4', null, `Firearm ${index + 1}`),
                React.createElement('div', { className: 'form-grid' },
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Type *'),
                        React.createElement('select', {
                            value: firearm.type,
                            onChange: (e) => {
                                const newFirearms = [...firearms];
                                newFirearms[index].type = e.target.value;
                                setFirearms(newFirearms);
                            }
                        },
                            React.createElement('option', { value: 'handgun' }, 'Handgun'),
                            React.createElement('option', { value: 'long_gun' }, 'Long Gun'),
                            React.createElement('option', { value: 'other' }, 'Other'),
                            React.createElement('option', { value: 'receiver' }, 'Receiver'),
                            React.createElement('option', { value: 'frame' }, 'Frame')
                        )
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Manufacturer'),
                        React.createElement('input', {
                            type: 'text',
                            value: firearm.manufacturer,
                            onChange: (e) => {
                                const newFirearms = [...firearms];
                                newFirearms[index].manufacturer = e.target.value;
                                setFirearms(newFirearms);
                            }
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Model'),
                        React.createElement('input', {
                            type: 'text',
                            value: firearm.model,
                            onChange: (e) => {
                                const newFirearms = [...firearms];
                                newFirearms[index].model = e.target.value;
                                setFirearms(newFirearms);
                            }
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Serial Number *'),
                        React.createElement('input', {
                            type: 'text',
                            value: firearm.serial_number,
                            onChange: (e) => {
                                const newFirearms = [...firearms];
                                newFirearms[index].serial_number = e.target.value;
                                setFirearms(newFirearms);
                            },
                            required: true
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Caliber/Gauge'),
                        React.createElement('input', {
                            type: 'text',
                            value: firearm.caliber,
                            onChange: (e) => {
                                const newFirearms = [...firearms];
                                newFirearms[index].caliber = e.target.value;
                                setFirearms(newFirearms);
                            }
                        })
                    ),
                    React.createElement('div', { className: 'form-field' },
                        React.createElement('label', null, 'Price'),
                        React.createElement('input', {
                            type: 'number',
                            step: '0.01',
                            value: firearm.price,
                            onChange: (e) => {
                                const newFirearms = [...firearms];
                                newFirearms[index].price = e.target.value;
                                setFirearms(newFirearms);
                            }
                        })
                    )
                )
            )
        ),
        React.createElement('button', {
            className: 'button',
            onClick: addFirearm
        }, '+ Add Another Firearm'),
        React.createElement('div', { className: 'form-actions' },
            React.createElement('button', {
                className: 'button button-primary',
                onClick: () => onSave({ firearms })
            }, 'Save & Continue →')
        )
    );
};

// Section C - Background Check Questions
const SectionC = ({ formId, onSave }) => {
    const [answers, setAnswers] = useState({
        q11a: 'no', q11b: 'no', q11c: 'no', q11d: 'no',
        q11e: 'no', q11f: 'no', q11g: 'no', q11h: 'no',
        q11i: 'no', q11j: 'no', q11k: 'no', q11l: 'no'
    });

    const questions = [
        { id: 'q11a', text: 'Are you the actual transferee/buyer of the firearm(s) listed on this form?' },
        { id: 'q11b', text: 'Are you under indictment or information in any court for a felony?' },
        { id: 'q11c', text: 'Have you ever been convicted in any court of a felony?' },
        { id: 'q11d', text: 'Are you a fugitive from justice?' },
        { id: 'q11e', text: 'Are you an unlawful user of, or addicted to, marijuana or any depressant, stimulant, narcotic drug, or any other controlled substance?' },
        { id: 'q11f', text: 'Have you ever been adjudicated as a mental defective OR have you ever been committed to a mental institution?' },
        { id: 'q11g', text: 'Have you been discharged from the Armed Forces under dishonorable conditions?' },
        { id: 'q11h', text: 'Are you subject to a court order restraining you from harassing, stalking, or threatening your child or an intimate partner?' },
        { id: 'q11i', text: 'Have you ever been convicted in any court of a misdemeanor crime of domestic violence?' },
        { id: 'q11j', text: 'Have you ever renounced your United States citizenship?' },
        { id: 'q11k', text: 'Are you an alien illegally or unlawfully in the United States?' },
        { id: 'q11l', text: 'Are you a prohibited person under state or local law?' }
    ];

    return React.createElement('div', { className: 'section-form' },
        React.createElement('h3', null, 'Section C: Background Check Questions'),
        React.createElement('p', { style: { marginBottom: '20px', fontStyle: 'italic' } },
            'Answer all questions. For questions b-l, answer "yes" only if the statement applies to you.'
        ),
        questions.map((q) =>
            React.createElement('div', { key: q.id, className: 'question-item' },
                React.createElement('p', { className: 'question-text' }, q.text),
                React.createElement('div', { className: 'radio-group' },
                    React.createElement('label', null,
                        React.createElement('input', {
                            type: 'radio',
                            name: q.id,
                            value: 'yes',
                            checked: answers[q.id] === 'yes',
                            onChange: (e) => setAnswers({...answers, [q.id]: 'yes'})
                        }),
                        ' Yes'
                    ),
                    React.createElement('label', null,
                        React.createElement('input', {
                            type: 'radio',
                            name: q.id,
                            value: 'no',
                            checked: answers[q.id] === 'no',
                            onChange: (e) => setAnswers({...answers, [q.id]: 'no'})
                        }),
                        ' No'
                    )
                )
            )
        ),
        React.createElement('div', { className: 'form-actions' },
            React.createElement('button', {
                className: 'button button-primary',
                onClick: () => onSave(answers)
            }, 'Save & Continue →')
        )
    );
};

// Section D - NICS & Completion
const SectionD = ({ formId }) => {
    return React.createElement('div', { className: 'section-form' },
        React.createElement('h3', null, 'Section D: NICS Check & Completion'),
        React.createElement('p', null, 'NICS integration coming soon. For now, record checks manually.'),
        React.createElement('div', { className: 'form-actions' },
            React.createElement('button', {
                className: 'button button-primary',
                onClick: () => alert('Form completion and NICS integration coming soon')
            }, 'Complete Form')
        )
    );
};

// Mount the app
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('fflbro-form-4473-root');
    if (root) {
        ReactDOM.render(React.createElement(Form4473App), root);
    }
});

// Debug check
console.log("React available:", typeof React !== 'undefined');
console.log("ReactDOM available:", typeof ReactDOM !== 'undefined');
