<?php
/** Isolate storefront customer authentication from wp-admin authentication. */
if (!defined('ABSPATH')) exit;

class Rackmonk_Auth_Context {
    public static function administrative(array $server, array $get, array $post, $site_url) {
        $base = rtrim((string) parse_url($site_url, PHP_URL_PATH), '/');
        $path = rawurldecode((string) parse_url($server['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        $relative = $base !== '' && strpos($path, $base . '/') === 0 ? substr($path, strlen($base)) : $path;
        $script = str_replace('\\', '/', $server['SCRIPT_NAME'] ?? '');
        $admin = $relative === '/wp-admin' || strpos($relative, '/wp-admin/') === 0 || strpos($script, '/wp-admin/') !== false || basename($script) === 'wp-login.php' || $relative === '/wp-login.php' || $relative === '/wp-cron.php';
        if (basename($script) === 'admin-ajax.php' || $relative === '/wp-admin/admin-ajax.php') {
            $action = $post['action'] ?? $get['action'] ?? '';
            $customer_actions = ['rackmonk_get_plans', 'woocommerce_get_refreshed_fragments', 'woocommerce_apply_coupon', 'woocommerce_remove_coupon', 'woocommerce_update_shipping_method', 'woocommerce_get_cart_totals', 'woocommerce_update_order_review', 'woocommerce_add_to_cart', 'woocommerce_remove_from_cart', 'woocommerce_checkout', 'woocommerce_get_variation'];
            return !is_string($action) || !in_array($action, $customer_actions, true);
        }
        if ($admin) return true;
        $route = isset($get['rest_route']) && is_string($get['rest_route']) ? $get['rest_route'] : '';
        if (strpos($relative, '/wp-json/') === 0) $route = substr($relative, strlen('/wp-json'));
        if ($route !== '') {
            foreach (['/wp/v2', '/elementor/', '/elementor-pro/', '/wc/v1', '/wc/v2', '/wc/v3', '/wc-admin/', '/wc-analytics/'] as $prefix) if (strpos($route, $prefix) === 0) return true;
            return false;
        }
        // Elementor's authenticated preview/editor must retain the native admin session.
        if (isset($get['elementor-preview']) && is_scalar($get['elementor-preview']) && ctype_digit((string) $get['elementor-preview'])) return true;
        if (isset($get['preview']) && $get['preview'] === 'true') return true;
        if ((isset($get['wp_customize']) && $get['wp_customize'] === 'on') || (isset($get['customize_changeset_uuid']) && is_string($get['customize_changeset_uuid']) && preg_match('/\A[0-9a-f-]{36}\z/iD', $get['customize_changeset_uuid']))) return true;
        return $admin;
    }
}

$rackmonk_admin_context = PHP_SAPI === 'cli' || Rackmonk_Auth_Context::administrative($_SERVER, $_GET, $_POST, (string) get_option('siteurl'));
if (!defined('RACKMONK_AUTH_CONTEXT')) define('RACKMONK_AUTH_CONTEXT', $rackmonk_admin_context ? 'admin' : 'client');
if (!$rackmonk_admin_context) {
    // MU plugins run before wp_cookie_constants(); native wp-admin names stay unchanged.
    if (!defined('COOKIEHASH')) define('COOKIEHASH', md5((string) get_site_option('siteurl')));
    foreach (['AUTH_COOKIE' => 'rackmonk_client_auth_', 'SECURE_AUTH_COOKIE' => 'rackmonk_client_secure_', 'LOGGED_IN_COOKIE' => 'rackmonk_client_logged_in_', 'USER_COOKIE' => 'rackmonk_client_user_', 'PASS_COOKIE' => 'rackmonk_client_pass_'] as $constant => $prefix) {
        if (!defined($constant)) define($constant, $prefix . COOKIEHASH);
    }
}
unset($rackmonk_admin_context, $constant, $prefix);
