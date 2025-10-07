(function($) {
    'use strict';
    $(document).ready(function() {
        $('.button-link-delete').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
                return false;
            }
        });
        setTimeout(function() {
            $('.notice.is-dismissible').fadeOut();
        }, 5000);
        $('#name').on('blur', function() {
            var vendorName = $(this).val();
            var vendorCode = $('#vendor_code');
            if (vendorCode.val() === '' && vendorName !== '') {
                var suggested = vendorName.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 6);
                if (suggested.length >= 3) {
                    vendorCode.val(suggested + '-001');
                    vendorCode.css('background-color', '#ffffcc');
                    setTimeout(function() { vendorCode.css('background-color', ''); }, 2000);
                }
            }
        });
        console.log('FFL-BRO Finances Admin JS loaded');
    });
})(jQuery);
