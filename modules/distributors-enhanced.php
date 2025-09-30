<?php
/**
 * FFL-BRO Enhanced PRO - Distributors Management Interface v7.0.0
 * Complete distributors page with Lipseys and RSR Group integration
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current product counts
global $wpdb;
$lipseys_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_products WHERE distributor = 'lipseys'");
$rsr_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_products WHERE distributor = 'rsr'");
?>

<div class="wrap">
    <h1>🏢 Distributor Management</h1>
    <p>Manage your firearms distributor integrations and catalog synchronization.</p>
    
    <style>
    .distributor-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    
    .distributor-card {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .distributor-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .distributor-logo {
        width: 60px;
        height: 40px;
        margin-right: 15px;
        border-radius: 4px;
    }
    
    .distributor-info h3 {
        margin: 0 0 5px 0;
        color: #333;
    }
    
    .distributor-description {
        margin: 0 0 10px 0;
        color: #666;
        font-size: 14px;
    }
    
    .connection-status {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
    
    .status-indicator.online {
        background-color: #46b450;
    }
    
    .status-indicator.offline {
        background-color: #dc3232;
    }
    
    .status-text {
        font-size: 13px;
        color: #666;
    }
    
    .distributor-stats {
        display: flex;
        gap: 20px;
        margin: 15px 0;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-number {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }
    
    .stat-label {
        display: block;
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
    }
    
    .distributor-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .distributor-actions .button {
        flex: 1;
    }
    </style>
    
    <div class="distributor-grid">
        
        <!-- Lipseys Card -->
        <div class="distributor-card" style="border: 2px solid #0073aa;">
            <div class="distributor-header">
                <img src="https://via.placeholder.com/60x40/0073aa/FFFFFF?text=LIP" alt="Lipseys" class="distributor-logo">
                <div class="distributor-info">
                    <h3>Lipseys <span style="color: #0073aa; font-size: 12px;">(Working)</span></h3>
                    <p class="distributor-description">Premium firearms distributor • API integration • Live authentication</p>
                    <div class="connection-status" id="lipseys-status">
                        <span class="status-indicator online"></span>
                        <span class="status-text">Connected</span>
                    </div>
                </div>
            </div>
            <div class="distributor-stats">
                <div class="stat-item">
                    <span class="stat-number" id="lipseys-products"><?php echo number_format($lipseys_count); ?></span>
                    <span class="stat-label">Products</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="lipseys-categories">25+</span>
                    <span class="stat-label">Categories</span>
                </div>
            </div>
            <div class="distributor-actions">
                <button class="button button-primary" onclick="testLipseysConnection()" style="background: #0073aa;">Test API</button>
                <button class="button" onclick="syncLipseysInventory()">Sync Catalog</button>
            </div>
        </div>
        
        <!-- RSR Group Card -->
        <div class="distributor-card" style="border: 2px solid #FF6600;">
            <div class="distributor-header">
                <img src="https://via.placeholder.com/60x40/FF6600/FFFFFF?text=RSR" alt="RSR Group" class="distributor-logo">
                <div class="distributor-info">
                    <h3>RSR Group <span style="color: #FF6600; font-size: 12px;">(Account #67271)</span></h3>
                    <p class="distributor-description">45+ years • 350+ manufacturers • TLS encrypted FTP</p>
                    <div class="connection-status" id="rsr-status">
                        <span class="status-indicator offline"></span>
                        <span class="status-text">Not Connected</span>
                    </div>
                </div>
            </div>
            <div class="distributor-stats">
                <div class="stat-item">
                    <span class="stat-number" id="rsr-products"><?php echo number_format($rsr_count); ?></span>
                    <span class="stat-label">Products</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="rsr-categories">43</span>
                    <span class="stat-label">Categories</span>
                </div>
            </div>
            <div class="distributor-actions">
                <button class="button button-primary" onclick="testRSRConnection()" style="background: #FF6600;">Test Connection</button>
                <button class="button" onclick="syncRSRInventory()">Sync Catalog</button>
            </div>
        </div>
        
    </div>
    <!-- End distributor cards -->
    
    <div style="margin-top: 30px;">
        <h2>🔧 Synchronization Status</h2>
        <div id="sync-log" style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; max-height: 200px; overflow-y: auto;">
            <p>Ready for distributor operations...</p>
        </div>
    </div>
    
</div>

<script>
// Global variables
var fflbro_nonce = '<?php echo wp_create_nonce('fflbro_nonce'); ?>';

// Lipseys Integration Functions
function testLipseysConnection() {
    console.log('Testing Lipseys connection...');
    
    jQuery.post(ajaxurl, {
        action: 'fflbro_test_lipseys_connection',
        nonce: fflbro_nonce
    }, function(response) {
        if (response.success) {
            jQuery('#lipseys-status .status-indicator').removeClass('offline').addClass('online');
            jQuery('#lipseys-status .status-text').text('Connected');
            alert('✅ Lipseys Connection Successful!\n\n' + 
                  'API Status: Working\n' +
                  'Authentication: Valid\n' +
                  'Products Available: ' + (response.data.product_count || 'N/A'));
        } else {
            jQuery('#lipseys-status .status-indicator').removeClass('online').addClass('offline');
            jQuery('#lipseys-status .status-text').text('Connection Failed');
            alert('❌ Lipseys Connection Failed:\n' + response.data);
        }
    }).fail(function(xhr, status, error) {
        alert('❌ Lipseys Test Error: ' + error);
    });
}

function syncLipseysInventory() {
    if (!confirm('Start Lipseys catalog sync?\n\nThis will process the catalog and may take a few minutes.\n\nContinue?')) {
        return;
    }
    
    var button = jQuery(event.target);
    var originalText = button.text();
    button.text('Syncing...').prop('disabled', true);
    
    jQuery.post(ajaxurl, {
        action: 'sync_lipseys_catalog',
        nonce: fflbro_nonce
    }, function(response) {
        button.text(originalText).prop('disabled', false);
        
        if (response.success) {
            alert('✅ ' + response.data.message);
            location.reload();
        } else {
            alert('❌ Lipseys Sync Failed:\n' + response.data);
        }
    });
}

// RSR Group Integration Functions
function testRSRConnection() {
    console.log('Testing RSR connection...');
    
    jQuery.post(ajaxurl, {
        action: 'fflbro_test_rsr_connection',
        nonce: fflbro_nonce
    }, function(response) {
        console.log('RSR Test Response:', response);
        
        if (response.success) {
            jQuery('#rsr-status .status-indicator').removeClass('offline').addClass('online');
            jQuery('#rsr-status .status-text').text('Connected');
            alert('✅ RSR Connection Successful!\n\n' + 
                  'Account: ' + (response.data.account || 'N/A') + '\n' +
                  'Server: ' + response.data.server + '\n' +
                  'Connection: ' + response.data.connection_type + '\n' +
                  'Files Found: ' + response.data.files_found + '\n' +
                  'Available: ' + response.data.available_files.join(', '));
        } else {
            jQuery('#rsr-status .status-indicator').removeClass('online').addClass('offline');
            jQuery('#rsr-status .status-text').text('Connection Failed');
            alert('❌ RSR Connection Failed:\n' + response.data);
        }
    }).fail(function(xhr, status, error) {
        console.error('RSR Test Error:', error);
        alert('❌ RSR Test Error: ' + error);
    });
}

function syncRSRInventory() {
    if (!confirm('Start RSR catalog sync?\n\nThis will:\n• Download live inventory from RSR Group\n• Process 40,000+ products\n• Take 10-20 minutes\n• Replace existing RSR data\n\nContinue?')) {
        return;
    }
    
    var button = jQuery(event.target);
    var originalText = button.text();
    button.text('Syncing...').prop('disabled', true);
    
    // Show progress
    var progressDiv = jQuery('<div id="rsr-progress" style="margin: 10px 0; padding: 10px; background: #f0f0f0; border-radius: 5px;"><div>Starting RSR sync...</div></div>');
    button.parent().append(progressDiv);
    
    jQuery.post(ajaxurl, {
        action: 'fflbro_sync_rsr_catalog', 
        nonce: fflbro_nonce
    }, function(response) {
        button.text(originalText).prop('disabled', false);
        jQuery('#rsr-progress').remove();
        
        if (response.success) {
            alert('✅ ' + response.data.message + '\n\nStats:\n' + 
                  '• Processed: ' + (response.data.stats ? response.data.stats.processed : 'N/A') + '\n' +
                  '• Created: ' + (response.data.stats ? response.data.stats.created : 'N/A') + '\n' +
                  '• Errors: ' + (response.data.stats ? response.data.stats.errors : 'N/A'));
            
            // Update product count
            if (response.data.stats && response.data.stats.created) {
                jQuery('#rsr-products').text(response.data.stats.created.toLocaleString());
            }
            
            location.reload();
        } else {
            alert('❌ RSR Sync Failed:\n' + response.data);
        }
    }).fail(function(xhr, status, error) {
        button.text(originalText).prop('disabled', false);
        jQuery('#rsr-progress').remove();
        alert('❌ RSR Sync Error: ' + error);
    });
}

// Log function for sync status
function logMessage(message) {
    var logDiv = jQuery('#sync-log');
    var timestamp = new Date().toLocaleTimeString();
    logDiv.append('<p>[' + timestamp + '] ' + message + '</p>');
    logDiv.scrollTop(logDiv[0].scrollHeight);
}
</script>
