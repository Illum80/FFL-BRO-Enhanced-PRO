// Form 4473 React Component v7.3.1
const { useState, useEffect } = React;

function Form4473Manager() {
    const [forms, setForms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        loadForms();
    }, []);

    const loadForms = async () => {
        try {
            const response = await fetch(fflbroForm4473.apiUrl + 'form-4473/list', {
                headers: {
                    'X-WP-Nonce': fflbroForm4473.nonce
                }
            });
            const data = await response.json();
            if (data.status === 'success') {
                setForms(data.data.forms || []);
            }
        } catch (err) {
            setError('Failed to load forms');
        } finally {
            setLoading(false);
        }
    };

    const createNewForm = async () => {
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
                loadForms();
            }
        } catch (err) {
            setError('Failed to create form');
        }
    };

    if (loading) {
        return React.createElement('div', { className: 'form-4473-loading' }, 
            'Loading Form 4473 system...'
        );
    }

    if (error) {
        return React.createElement('div', { className: 'form-4473-error' }, error);
    }

    return React.createElement('div', { className: 'form-4473-manager' },
        React.createElement('div', { className: 'form-4473-header' },
            React.createElement('h2', null, 'ATF Form 4473 - Digital Processing'),
            React.createElement('button', { 
                className: 'btn-create-form',
                onClick: createNewForm 
            }, '+ Create New Form 4473')
        ),
        React.createElement('div', { className: 'form-4473-list' },
            forms.length === 0 ?
                React.createElement('p', { className: 'no-forms' }, 
                    'No Form 4473 records found. Click "Create New Form 4473" to begin.'
                ) :
                forms.map(form => 
                    React.createElement('div', { key: form.id, className: 'form-4473-card' },
                        React.createElement('div', { className: 'form-info' },
                            React.createElement('h3', null, 'Form #' + form.id),
                            React.createElement('p', null, 'Created: ' + form.created_date),
                            form.transferee_name && 
                                React.createElement('p', null, 'Transferee: ' + form.transferee_name)
                        ),
                        React.createElement('div', { className: 'form-status' },
                            React.createElement('span', { 
                                className: 'status-badge status-' + (form.status || 'draft')
                            }, form.status || 'draft')
                        ),
                        React.createElement('div', { className: 'form-actions' },
                            React.createElement('button', { className: 'btn-edit' }, 'Edit'),
                            React.createElement('button', { className: 'btn-view' }, 'View')
                        )
                    )
                )
        )
    );
}
