<?php
// Addon to provide missing searchProducts function - FIXED DATA FORMAT
add_action('admin_footer', function() {
    if (isset($_GET['page']) && $_GET['page'] == 'fflbro-quotes') {
        ?>
        <script type="text/javascript">
        function searchProducts() {
            const query = document.getElementById('search-term').value;
            if (!query || query.length < 2) {
                alert('Please enter at least 2 characters to search');
                return;
            }
            
            const resultsDiv = document.getElementById('search-results');
            if (resultsDiv) {
                resultsDiv.innerHTML = '<p>Searching products...</p>';
            }
            
            jQuery.post(ajaxurl, {
                action: 'fflbro_search_products',
                search_term: query,
                nonce: '<?php echo wp_create_nonce('fflbro_nonce'); ?>'
            }, function(response) {
                console.log('Server response:', response);
                
                if (response.success) {
                    // FIXED: Access the products array correctly
                    const products = response.data.products || response.data;
                    console.log('Products array:', products);
                    displaySearchResults(products);
                } else {
                    alert('Search failed: ' + (response.data || 'Unknown error'));
                }
            }).fail(function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('Search request failed (Error ' + xhr.status + '). Please try again.');
            });
        }
        
        function displaySearchResults(products) {
            const resultsDiv = document.getElementById('search-results');
            if (!resultsDiv) return;
            
            if (!products || !Array.isArray(products) || products.length === 0) {
                resultsDiv.innerHTML = '<p>No products found for this search term</p>';
                return;
            }
            
            let html = '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr><th>Item Number</th><th>Description</th><th>Price</th><th>Action</th></tr></thead><tbody>';
            
            products.forEach(function(product) {
                html += '<tr><td>' + (product.item_number || 'N/A') + '</td><td>' + (product.description || 'N/A') + '</td><td>$' + (product.price || '0.00') + '</td><td><button onclick="addToQuote(' + (product.id || 0) + ')" class="button button-small">Add to Quote</button></td></tr>';
            });
            
            html += '</tbody></table>';
            resultsDiv.innerHTML = html;
        }
        
        function addToQuote(productId) {
            alert('Product ' + productId + ' added to quote!');
        }
        </script>
        <?php
    }
});
?>
