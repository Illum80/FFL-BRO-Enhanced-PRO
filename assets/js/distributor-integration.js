jQuery(document).ready(function($) {
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
        
        $.post(ajaxurl, {
            action: 'fflbro_rsr_authenticate',
            nonce: fflbro_ajax.nonce,
            dealer_id: dealerId,
            api_key: apiKey
        }, function(response) {
            if (response.success) {
                $('#rsr_status').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
            } else {
                $('#rsr_status').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
            }
        }).fail(function() {
            $('#rsr_status').html('<div class="notice notice-error"><p>Connection failed</p></div>');
        }).always(function() {
            button.prop('disabled', false).text('Test RSR Connection');
        });
    });
});
