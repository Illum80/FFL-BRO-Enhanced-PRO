jQuery(document).ready(function($) {
    
    // Davidsons CSV Upload Handler
    $('#davidsons-upload-csv').on('click', function() {
        const fileInput = $('#davidsons-csv-file')[0];
        const csvType = $('#davidsons-csv-type').val();
        
        if (!fileInput.files[0]) {
            alert('Please select a CSV file first');
            return;
        }
        
        if (!csvType) {
            alert('Please select CSV type (Inventory or Quantity)');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'davidsons_upload_csv');
        formData.append('nonce', fflbroWorking.nonce);
        formData.append('csv_file', fileInput.files[0]);
        formData.append('csv_type', csvType);
        
        // Show loading state
        $(this).prop('disabled', true).text('Uploading...');
        
        $.ajax({
            url: fflbroWorking.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Upload response:', response);
                
                if (response.success) {
                    alert('✅ ' + (response.data.message || 'Upload successful!'));
                    $('#davidsons-csv-file').val('');
                    loadDavidsonsInventory();
                } else {
                    let errorMsg = 'Upload failed';
                    if (typeof response.data === 'string') {
                        errorMsg = response.data;
                    } else if (response.data && response.data.message) {
                        errorMsg = response.data.message;
                    } else if (response.message) {
                        errorMsg = response.message;
                    }
                    alert('❌ Error: ' + errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('Upload error:', error, xhr.responseText);
                let errorMsg = 'Upload failed: ';
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    errorMsg += errorData.data || errorData.message || error;
                } catch(e) {
                    errorMsg += error || 'Unknown error';
                }
                alert('❌ ' + errorMsg);
            },
            complete: function() {
                $('#davidsons-upload-csv').prop('disabled', false).text('Upload CSV');
            }
        });
    });
    
    function loadDavidsonsInventory() {
        $.post(fflbroWorking.ajax_url, {
            action: 'davidsons_get_inventory',
            nonce: fflbroWorking.nonce
        }, function(response) {
            if (response.success && response.data) {
                $('#davidsons-product-count').text(response.data.count || '0');
                $('#davidsons-last-updated').text(response.data.last_updated || 'Never');
            }
        });
    }
    
    if ($('#davidsons-upload-csv').length) {
        loadDavidsonsInventory();
    }
});
