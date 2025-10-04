<?php
class DavidsonsDistributor {
    public function __construct() {
        add_action("wp_ajax_davidsons_upload_csv", array($this, "upload_csv"));
        add_action("wp_ajax_get_davidsons_inventory", array($this, "get_inventory"));
    }

    public function upload_csv() {
        check_ajax_referer("fflbro_nonce", "nonce");
        if (!current_user_can("manage_options")) {
            wp_send_json_error("Insufficient permissions");
            return;
        }
        if (empty($_FILES["csv_file"])) {
            wp_send_json_error("No file uploaded");
            return;
        }
        $file = $_FILES["csv_file"];
        if ($file["error"] !== UPLOAD_ERR_OK) {
            wp_send_json_error("File upload error: " . $file["error"]);
            return;
        }
        $content = file_get_contents($file["tmp_name"]);
        $result = $this->process_inventory_csv($content);
        if ($result["success"]) {
            wp_send_json_success(array("message" => $result["message"], "count" => $result["count"]));
        } else {
            wp_send_json_error($result["message"]);
        }
    }

    private function process_inventory_csv($content) {
        global $wpdb;
        $table = $wpdb->prefix . "fflbro_products";
        $lines = explode("\n", $content);
        array_shift($lines);
        $count = 0;
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $data = str_getcsv($line);
            if (count($data) < 10) continue;
            $product_data = array(
                "distributor" => "davidsons",
                "distributor_sku" => "davidsons-" . sanitize_text_field($data[0]),
                "item_number" => sanitize_text_field($data[0]),
                "upc" => sanitize_text_field($data[8]),
                "description" => sanitize_text_field($data[1]),
                "manufacturer" => sanitize_text_field($data[9]),
                "price" => floatval(str_replace(array("$", ","), "", $data[4])),
                "quantity" => intval($data[7]),
                "last_updated" => current_time("mysql")
            );
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE distributor_sku = %s", $product_data["distributor_sku"]));
            if ($exists) {
                $wpdb->update($table, $product_data, array("id" => $exists));
            } else {
                $wpdb->insert($table, $product_data);
            }
            $count++;
        }
        return array("success" => true, "message" => "Successfully processed $count products", "count" => $count);
    }

    public function get_inventory() {
        check_ajax_referer("fflbro_nonce", "nonce");
        global $wpdb;
        $table = $wpdb->prefix . "fflbro_products";
        $products = $wpdb->get_results("SELECT * FROM $table WHERE distributor = 'davidsons' ORDER BY item_number LIMIT 100");
        wp_send_json_success($products);
    }
}
new DavidsonsDistributor();
