<?php
/**
 * Plugin Name: FFL-BRO Batch Sync
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

class FFLBro_Batch_Sync {
    public function __construct() {
        add_action('wp_ajax_fflbro_stream_sync', array($this, 'stream_sync'));
        add_action('admin_footer', array($this, 'override_js'));
    }
    
    public function stream_sync() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        set_time_limit(300);
        
        // Auth
        $auth = wp_remote_post('https://api.lipseys.com/api/Integration/Authentication/Login', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array('Email' => 'jrneefe@gmail.com', 'Password' => 'Rampone1214!')),
            'timeout' => 30
        ));
        
        if (is_wp_error($auth)) {
            wp_send_json_error('Auth failed');
        }
        
        $auth_data = json_decode(wp_remote_retrieve_body($auth), true);
        if (!isset($auth_data['token'])) {
            wp_send_json_error('No token');
        }
        
        // Get catalog
        $catalog = wp_remote_get('https://api.lipseys.com/api/Integration/Items/CatalogFeed', array(
            'headers' => array('Token' => $auth_data['token']),
            'timeout' => 120
        ));
        
        if (is_wp_error($catalog)) {
            wp_send_json_error('Catalog failed');
        }
        
        $catalog_data = json_decode(wp_remote_retrieve_body($catalog), true);
        $products = $catalog_data['data'];
        $total = count($products);
        
        // Clear old products
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}fflbro_products WHERE distributor = 'lipseys'");
        
        // Process in chunks of 100 and send progress updates
        $chunk_size = 100;
        $processed = 0;
        
        for ($i = 0; $i < $total; $i += $chunk_size) {
            $chunk = array_slice($products, $i, $chunk_size);
            
            foreach ($chunk as $p) {
                $wpdb->insert($wpdb->prefix . 'fflbro_products', array(
                    'distributor' => 'lipseys',
                    'item_number' => $p['itemNo'] ?? '',
                    'description' => trim(($p['description1'] ?? '') . ' ' . ($p['description2'] ?? '')),
                    'manufacturer' => $p['manufacturer'] ?? '',
                    'price' => floatval($p['currentPrice'] ?? 0),
                    'quantity' => intval($p['quantity'] ?? 0)
                ));
            }
            
            $processed += count($chunk);
            
            // Send progress update
            echo json_encode(array(
                'processed' => $processed,
                'total' => $total,
                'percent' => round(($processed / $total) * 100)
            )) . "\n";
            
            ob_flush();
            flush();
        }
        
        wp_send_json_success(array('total' => $total, 'processed' => $processed));
    }
    
    public function override_js() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'fflbro') === false) return;
        ?>
        <script>
        jQuery(document).ready(function($) {
            window.syncLipseys = function() {
                if (!confirm("Start streaming sync? This will take 2-3 minutes.")) return;
                
                var prog = $('<div>').css({position:'fixed',top:'50%',left:'50%',transform:'translate(-50%,-50%)',background:'#fff',padding:'30px',borderRadius:'10px',boxShadow:'0 4px 20px rgba(0,0,0,0.3)',zIndex:10000,minWidth:'400px',textAlign:'center'}).html('<h2>Syncing</h2><div id="msg">Starting...</div><div style="background:#eee;height:30px;border-radius:15px;margin:20px 0"><div id="bar" style="background:#0073aa;height:100%;width:0%;transition:width 0.3s"></div></div><div id="pct">0%</div>');
                $('body').append(prog);
                
                var xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxurl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                var lastLength = 0;
                xhr.onprogress = function() {
                    var response = xhr.responseText.substring(lastLength);
                    lastLength = xhr.responseText.length;
                    
                    var lines = response.split('\n');
                    for (var i = 0; i < lines.length; i++) {
                        if (lines[i].trim()) {
                            try {
                                var data = JSON.parse(lines[i]);
                                if (data.processed && data.total) {
                                    $('#msg').text(data.processed + ' of ' + data.total);
                                    $('#bar').css('width', data.percent + '%');
                                    $('#pct').text(data.percent + '%');
                                }
                            } catch(e) {}
                        }
                    }
                };
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        $('#msg').html('<strong style="color:green">Complete!</strong>');
                        setTimeout(function() { location.reload(); }, 2000);
                    } else {
                        alert('Sync failed');
                        prog.remove();
                    }
                };
                
                xhr.send('action=fflbro_stream_sync&nonce=' + fflbro_ajax.nonce);
            };
        });
        </script>
        <?php
    }
}

new FFLBro_Batch_Sync();
