jQuery(document).ready(function($) {
    
    // Davidsons CSV Upload Handler
    $('#davidsons-upload-csv').on('click', function() {
        const fileInput = $('#davidsons-csv-file')[0];
        const csvType = $('#davidsons-csv-type').val();
        
        if (!fileInput.files[0]) {
            alert('Please select a CSV file first');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'davidsons_upload_csv');
        formData.append('nonce', fflbro_ajax.nonce);  // Using fflbro_ajax not fflbroWorking
        formData.append('csv_file', fileInput.files[0]);
        formData.append('csv_type', csvType || 'inventory');
        
        $(this).prop('disabled', true).text('Uploading...');
        
        $.ajax({
            url: fflbro_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Upload response:', response);
                
                if (response.success) {
                    alert('✅ ' + (response.data.message || response.data || 'Upload successful!'));
                    $('#davidsons-csv-file').val('');
                } else {
                    let errorMsg = 'Upload failed';
                    if (typeof response.data === 'string') {
                        errorMsg = response.data;
                    } else if (response.data && response.data.message) {
                        errorMsg = response.data.message;
                    }
                    alert('❌ ' + errorMsg);
                }
            },
            error: function(xhr) {
                console.error('Upload error:', xhr.responseText);
                let errorMsg = 'Upload failed';
                try {
                    const data = JSON.parse(xhr.responseText);
                    errorMsg = data.data || data.message || errorMsg;
                } catch(e) {}
                alert('❌ ' + errorMsg);
            },
            complete: function() {
                $('#davidsons-upload-csv').prop('disabled', false).text('Upload CSV');
            }
        });
    });

    // RSR Group Integration
    $('#test_rsr_connection').on('click', function() {
        var button = $(this);
        var dealerId = $('#rsr_dealer_id').val();
        var apiKey = $('#rsr_api_key').val();
        
        if (!dealerId || !apiKey) {
            alert('Please enter both Dealer ID and API Key');
            return;
        }
        
        button.prop('disabled', true).text('Testing...');
        $('#rsr_status').html('<p>Testing RSR connection...</p>');
        
        $.post(fflbro_ajax.ajax_url, {
            action: 'fflbro_rsr_authenticate',
            nonce: fflbro_ajax.nonce,
            dealer_id: dealerId,
            api_key: apiKey
        }, function(response) {
            if (response.success) {
                $('#rsr_status').html('<p style="color: green;">✅ ' + response.data + '</p>');
            } else {
                $('#rsr_status').html('<p style="color: red;">❌ ' + response.data + '</p>');
            }
        }).fail(function() {
            $('#rsr_status').html('<p style="color: red;">❌ Connection failed</p>');
        }).always(function() {
            button.prop('disabled', false).text('Test Connection');
        });
    });
});
