<?php
namespace FFLBRO\Fin\REST;

if (!defined('ABSPATH')) exit;

class FinanceRoutes {
    public static function register() {
        register_rest_route('fflbro/v1', '/finance/ping', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'ping'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('fflbro/v1', '/finance/vendors', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_vendors'],
            'permission_callback' => function() {
                return current_user_can('ffl_fin_read');
            }
        ]);
        
        register_rest_route('fflbro/v1', '/finance/checks/prepare', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'prepare_checks'],
            'permission_callback' => function() {
                return current_user_can('ffl_fin_manage');
            }
        ]);
    }
    
    public static function ping() {
        return [
            'ok' => true,
            'ver' => FFLBRO_FIN_VERSION,
            'timestamp' => current_time('mysql')
        ];
    }
    
    public static function get_vendors() {
        return ['vendors' => [], 'count' => 0];
    }
    
    public static function prepare_checks($request) {
        $payment_ids = $request->get_param('payment_ids') ?? [];
        $bank_id = $request->get_param('bank_account_id');
        
        return [
            'batch_id' => time(),
            'count' => count($payment_ids),
            'bank_account_id' => $bank_id
        ];
    }
}
