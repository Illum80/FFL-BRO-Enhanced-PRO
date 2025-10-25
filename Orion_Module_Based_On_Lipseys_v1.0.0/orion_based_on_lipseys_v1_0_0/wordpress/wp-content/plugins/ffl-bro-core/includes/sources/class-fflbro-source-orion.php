<?php
/**
 * Orion source adapter — mirrors Lipsey’s class with endpoint/field map swapped.
 */
if (!defined('ABSPATH')) { exit; }

class FFLBRO_Source_Orion implements FFLBRO_Source_Interface {

    public const SOURCE_KEY = 'orion';

    /** @var string */
    protected $api_base;
    /** @var string */
    protected $api_key;
    /** @var string */
    protected $api_secret;
    /** @var int */
    protected $timeout;

    public function __construct() {
        $this->api_base   = getenv('ORION_API_BASE') ?: get_option('orion_api_base', 'https://api.orion.example.com');
        $this->api_key    = getenv('ORION_API_KEY') ?: get_option('orion_api_key', '');
        $this->api_secret = getenv('ORION_API_SECRET') ?: get_option('orion_api_secret', '');
        $this->timeout    = (int) (getenv('ORION_API_TIMEOUT') ?: 20);
    }

    public function key(): string {
        return self::SOURCE_KEY;
    }

    public function fetch_sku(string $sku): array {
        $url = rtrim($this->api_base, '/').'/v1/catalog/sku/'.rawurlencode($sku);
        $args = [
            'headers' => [
                'Authorization' => 'Bearer '.$this->api_key,
                'X-Orion-Secret' => $this->api_secret,
                'Accept' => 'application/json',
            ],
            'timeout' => $this->timeout,
        ];
        $res = wp_remote_get($url, $args);
        if (is_wp_error($res)) {
            throw new RuntimeException('Orion API error: '.$res->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        $body = wp_remote_retrieve_body($res);
        if ($code !== 200) {
            throw new RuntimeException('Orion API HTTP '.$code.' — body: '.$body);
        }
        $data = json_decode($body, true) ?: [];
        return $this->map_record_to_product($data);
    }

    protected function map_record_to_product(array $src): array {
        // Mirror Lipsey’s mapping contract → product DTO
        $images = array_map(function($u){ return ['src'=>$u, 'position'=>0]; }, $src['images'] ?? []);
        $price  = $src['price']['sale'] ?? ($src['price']['map'] ?? ($src['price']['msrp'] ?? null));

        return [
            'sku'         => $src['sku'] ?? '',
            'name'        => $src['title'] ?? '',
            'description' => $src['description'] ?? '',
            'brand'       => $src['brand'] ?? '',
            'upc'         => $src['upc'] ?? '',
            'images'      => $images,
            'stock'       => [
                'manage' => true,
                'qty'    => (int) ($src['quantity'] ?? 0),
                'status' => ((int) ($src['quantity'] ?? 0)) > 0 ? 'instock' : 'outofstock',
            ],
            'pricing'     => [
                'regular' => $src['price']['msrp'] ?? null,
                'map'     => $src['price']['map']  ?? null,
                'sale'    => $src['price']['sale'] ?? null,
                'chosen'  => $price,
            ],
            'categories'  => $src['categories'] ?? [],
            '_meta'       => [
                'source' => self::SOURCE_KEY,
                'raw'    => $src,
            ],
        ];
    }
}

// Register with the same factory used by Lipsey’s
add_filter('fflbro_sources', function(array $sources){
    $sources[FFLBRO_Source_Orion::SOURCE_KEY] = new FFLBRO_Source_Orion();
    return $sources;
});
