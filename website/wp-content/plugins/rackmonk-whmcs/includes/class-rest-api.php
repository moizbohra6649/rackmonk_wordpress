<?php
/**
 * Rackmonk REST & AJAX API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Rackmonk_REST_API {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('wp_ajax_rackmonk_get_plans', [$this, 'ajax_get_plans']);
        add_action('wp_ajax_nopriv_rackmonk_get_plans', [$this, 'ajax_get_plans']);
    }

    public function register_routes() {
        register_rest_route('rackmonk/v1', '/plans', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_plans_response'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('rackmonk/v1', '/categories', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_categories_response'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function get_plans_response($request) {
        $gid = $request->get_param('gid');
        $plans = Rackmonk_Plans_Manager::get_instance()->get_plans($gid);
        return rest_ensure_response([
            'success'       => true,
            'default_cycle' => '48',
            'plans'         => $plans,
        ]);
    }

    public function get_categories_response($request) {
        $cats = Rackmonk_Plans_Manager::get_instance()->get_categories();
        return rest_ensure_response([
            'success'    => true,
            'categories' => $cats,
        ]);
    }

    public function ajax_get_plans() {
        $gid = isset($_GET['gid']) ? intval($_GET['gid']) : null;
        $plans = Rackmonk_Plans_Manager::get_instance()->get_plans($gid);
        wp_send_json_success([
            'default_cycle' => '48',
            'plans'         => $plans,
        ]);
    }
}

new Rackmonk_REST_API();

