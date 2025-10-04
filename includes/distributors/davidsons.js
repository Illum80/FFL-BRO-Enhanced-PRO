/**
 * Davidsons CSV Upload Handler
 * Supports: Inventory CSV and Quantity CSV
 */

function uploadDavidsonsCSV() {
    // Create file input
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.csv,.xml';
    
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Ask user what type of CSV this is
        const csvType = prompt(
            'What type of file is this?\n\n' +
            '1 = Inventory CSV (full catalog with prices)\n' +
            '2 = Quantity CSV (stock levels only)\n\n' +
            'Enter 1 or 2:'
        );
        
        if (csvType !== '1' && csvType !== '2') {
            alert('Invalid selection. Please choose 1 or 2.');
            return;
        }
        
        const typeLabel = csvType === '1' ? 'Inventory' : 'Quantity';
        
        if (!confirm(`Upload ${file.name} as Davidsons ${typeLabel} file?`)) {
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('csv_file', file);
        formData.append('csv_type', csvType);
        formData.append('action', 'upload_davidsons_csv');
        formData.append('nonce', fflbro_ajax.nonce);
        
        // Show loading
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Uploading...';
        button.disabled = true;
        
        // Upload
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Success! ' + data.data.message);
                location.reload();
            } else {
                alert('Upload failed: ' + (data.data?.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Upload error: ' + error.message);
        })
        .finally(() => {
            button.textContent = originalText;
            button.disabled = false;
        });
    };
    
    input.click();
}

function viewDavidsonsInventory() {
    fetch(ajaxurl + '?action=get_davidsons_inventory&nonce=' + fflbro_ajax.nonce)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Davidsons Inventory:\n\n' + 
                      'Total Products: ' + data.data.count + '\n' +
                      'Last Updated: ' + (data.data.last_updated || 'Never'));
            } else {
                alert('Failed to load inventory: ' + (data.data?.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
}
