/**
 * FFL-BRO Image Loader
 * Handles lazy loading and dynamic image fetching for distributors and products
 */

(function($) {
    'use strict';

    const FFLBROImageLoader = {
        
        init: function() {
            this.setupLazyLoading();
            this.setupImageErrorHandling();
            this.setupDistributorCards();
        },

        /**
         * Setup lazy loading for images
         */
        setupLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                            }
                            observer.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        },

        /**
         * Setup error handling for images
         */
        setupImageErrorHandling: function() {
            $(document).on('error', 'img.product-image, img.distributor-logo', function() {
                const $img = $(this);
                
                // Don't retry if already attempted
                if ($img.data('error-handled')) {
                    return;
                }
                $img.data('error-handled', true);

                // Try to load placeholder
                const isProductImage = $img.hasClass('product-image');
                const placeholder = isProductImage ? 
                    FFLBROImageLoader.createProductPlaceholder($img.attr('alt') || 'Product') :
                    FFLBROImageLoader.createDistributorPlaceholder($img.attr('alt') || 'Logo');
                
                $img.replaceWith(placeholder);
            });
        },

        /**
         * Create product placeholder
         */
        createProductPlaceholder: function(altText) {
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" class="product-image">
                <rect fill="#667eea" width="300" height="300"/>
                <circle cx="150" cy="120" r="40" fill="rgba(255,255,255,0.3)"/>
                <rect x="100" y="170" width="100" height="10" rx="5" fill="rgba(255,255,255,0.3)"/>
                <rect x="80" y="190" width="140" height="8" rx="4" fill="rgba(255,255,255,0.2)"/>
                <text x="50%" y="85%" dominant-baseline="middle" text-anchor="middle" 
                      fill="white" font-family="Arial" font-size="14">${altText}</text>
            </svg>`;
            return $(svg);
        },

        /**
         * Create distributor placeholder
         */
        createDistributorPlaceholder: function(altText) {
            const initials = altText.substring(0, 3).toUpperCase();
            const $div = $('<div class="no-image-placeholder distributor-logo"></div>')
                .css({
                    'width': '100px',
                    'height': '40px',
                    'background': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    'color': 'white',
                    'display': 'flex',
                    'align-items': 'center',
                    'justify-content': 'center',
                    'font-weight': 'bold',
                    'border-radius': '8px'
                })
                .text(initials);
            return $div;
        },

        /**
         * Setup distributor cards with logos
         */
        setupDistributorCards: function() {
            $('.distributor-card[data-distributor-id]').each(function() {
                const $card = $(this);
                const distributorId = $card.data('distributor-id');
                const $logoContainer = $card.find('.distributor-logo-container');
                
                if ($logoContainer.length && distributorId) {
                    FFLBROImageLoader.loadDistributorLogo(distributorId, $logoContainer);
                }
            });
        },

        /**
         * Load distributor logo via AJAX
         */
        loadDistributorLogo: function(distributorId, $container) {
            $.ajax({
                url: fflbroImages.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fflbro_get_distributor_logo',
                    nonce: fflbroImages.nonce,
                    distributor_id: distributorId,
                    class: 'distributor-logo-card'
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $container.html(response.data.html);
                        
                        // Add click handler to go to distributor website
                        if (response.data.data && response.data.data.website) {
                            $container.css('cursor', 'pointer').on('click', function() {
                                window.open(response.data.data.website, '_blank');
                            });
                        }
                    }
                },
                error: function() {
                    $container.html('<div class="no-image-placeholder distributor-logo-card">N/A</div>');
                }
            });
        },

        /**
         * Load product image dynamically
         */
        loadProductImage: function(imageName, distributorId, productData, $container) {
            $.ajax({
                url: fflbroImages.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fflbro_get_product_image',
                    nonce: fflbroImages.nonce,
                    image_name: imageName,
                    distributor_id: distributorId,
                    product_data: productData,
                    class: 'product-image'
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $container.html(response.data.html);
                    }
                },
                error: function() {
                    $container.html(FFLBROImageLoader.createProductPlaceholder('Product'));
                }
            });
        },

        /**
         * Preload images for better UX
         */
        preloadImages: function(imageUrls) {
            imageUrls.forEach(url => {
                const img = new Image();
                img.src = url;
            });
        },

        /**
         * Cache image in browser
         */
        cacheImage: function(url) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => resolve(url);
                img.onerror = () => reject(url);
                img.src = url;
            });
        },

        /**
         * Batch load multiple product images
         */
        batchLoadProductImages: function(products) {
            const promises = products.map(product => {
                return FFLBROImageLoader.cacheImage(product.imageUrl);
            });
            
            return Promise.allSettled(promises);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        FFLBROImageLoader.init();
    });

    // Expose to global scope for use in other scripts
    window.FFLBROImageLoader = FFLBROImageLoader;

})(jQuery);
