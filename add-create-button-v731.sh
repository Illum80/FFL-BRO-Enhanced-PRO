#!/bin/bash
echo "Adding functional Create Form button..."

# Add AJAX handler and button to form-4473-processing.php
sudo tee -a modules/form-4473-processing.php > /dev/null << 'PHP_ADDITION'

    // Add AJAX handler for creating new forms
    public function add_ajax_handlers() {
        add_action('wp_ajax_fflbro_create_new_4473', array($this, 'ajax_create_new_form'));
    }
    
    public function ajax_create_new_form() {
        check_ajax_referer('fflbro_4473_nonce', 'nonce');
        
        global $wpdb;
        
        // Generate unique form number
        $form_number = 'ATF-4473-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        
        // Insert new form
        $result = $wpdb->insert(
            $wpdb->prefix . 'main_fflbro_form4473',
            array(
                'form_number' => $form_number,
                'status' => 'draft',
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            $form_id = $wpdb->insert_id;
            
            // Create default sections
            $wpdb->insert($wpdb->prefix . 'main_fflbro_form4473_transferee', array('form_id' => $form_id));
            $wpdb->insert($wpdb->prefix . 'main_fflbro_form4473_questions', array('form_id' => $form_id));
            $wpdb->insert($wpdb->prefix . 'main_fflbro_form4473_nics', array('form_id' => $form_id));
            
            // Log audit
            $wpdb->insert(
                $wpdb->prefix . 'main_fflbro_form4473_audit',
                array(
                    'form_id' => $form_id,
                    'action' => 'form_created',
                    'description' => 'New Form 4473 created',
                    'user_id' => get_current_user_id(),
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'created_at' => current_time('mysql')
                )
            );
            
            wp_send_json_success(array(
                'form_id' => $form_id,
                'form_number' => $form_number,
                'message' => 'Form created successfully!'
            ));
        } else {
            wp_send_json_error('Failed to create form');
        }
    }
}

// Initialize AJAX handlers
$form_processor = new FFL_BRO_Form_4473_Processing();
$form_processor->add_ajax_handlers();
PHP_ADDITION

echo "✓ AJAX handler added"

# Now update the render method to include working JavaScript
sudo sed -i '/<button class="button button-primary button-hero" onclick="alert/c\                    <button class="button button-primary button-hero" id="create-form-btn" style="position: relative;">\n                        ➕ Create New Form 4473\n                    </button>' modules/form-4473-processing.php

# Add JavaScript at the end of render_4473_page method
sudo sed -i '/^        <\/style>$/a\        <script>\n        jQuery(document).ready(function($) {\n            $("#create-form-btn").on("click", function() {\n                var btn = $(this);\n                btn.prop("disabled", true).html("⏳ Creating...");\n                \n                $.ajax({\n                    url: ajaxurl,\n                    type: "POST",\n                    data: {\n                        action: "fflbro_create_new_4473",\n                        nonce: "<?php echo wp_create_nonce(\"fflbro_4473_nonce\"); ?>"\n                    },\n                    success: function(response) {\n                        if (response.success) {\n                            btn.html("✅ Created!");\n                            alert("🎉 Form Created Successfully!\\n\\nForm Number: " + response.data.form_number + "\\nForm ID: " + response.data.form_id + "\\n\\n✨ Full editing interface coming in next update!");\n                            setTimeout(function() {\n                                btn.prop("disabled", false).html("➕ Create New Form 4473");\n                            }, 3000);\n                        } else {\n                            btn.prop("disabled", false).html("❌ Failed");\n                            alert("Error: " + response.data);\n                        }\n                    },\n                    error: function() {\n                        btn.prop("disabled", false).html("❌ Error");\n                        alert("Failed to create form. Please try again.");\n                    }\n                });\n            });\n        });\n        </script>' modules/form-4473-processing.php

echo "✓ Create button JavaScript added"
echo ""
echo "================================================================"
echo "✅ Create Form Button Added!"
echo "================================================================"
echo ""
echo "Test it:"
echo "  1. Refresh WordPress admin"
echo "  2. Go to: FFL-BRO → Form 4473"
echo "  3. Click 'Create New Form 4473'"
echo "  4. Form will be created in database"
echo ""
