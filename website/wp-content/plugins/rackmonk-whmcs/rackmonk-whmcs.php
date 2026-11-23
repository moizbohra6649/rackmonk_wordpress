<?php
/**
 * Plugin Name: Rackmonk WHMCS Dynamic Plans
 * Plugin URI: https://rackmonk.com
 * Description: Dynamic WHMCS & Hosting plans integration for Rackmonk with 48-month default billing and per-month price display.
 * Version: 1.0.0
 * Author: Rackmonk
 * Author URI: https://rackmonk.com
 * Text Domain: rackmonk-whmcs
 */

if (!defined('ABSPATH')) {
    exit;
}

define('RACKMONK_WHMCS_DIR', plugin_dir_path(__FILE__));
define('RACKMONK_WHMCS_URL', plugin_dir_url(__FILE__));
define('RACKMONK_WHMCS_VERSION', '1.0.0');

require_once RACKMONK_WHMCS_DIR . 'includes/class-plans-manager.php';
require_once RACKMONK_WHMCS_DIR . 'includes/class-currency.php';
Rackmonk_Currency::boot();
require_once RACKMONK_WHMCS_DIR . 'includes/class-site-content.php';
new Rackmonk_Site_Content();
require_once RACKMONK_WHMCS_DIR . 'includes/class-website-cart.php';
require_once RACKMONK_WHMCS_DIR . 'includes/class-website-checkout.php';
require_once RACKMONK_WHMCS_DIR . 'includes/class-rest-api.php';
require_once RACKMONK_WHMCS_DIR . 'includes/class-shortcode.php';
require_once RACKMONK_WHMCS_DIR . 'includes/class-configurable-groups.php';
require_once RACKMONK_WHMCS_DIR . 'includes/class-plan-prices.php';
require_once RACKMONK_WHMCS_DIR . 'includes/class-admin-page.php';
require_once RACKMONK_WHMCS_DIR . 'includes/class-hosting-cards.php';
new Rackmonk_Hosting_Cards();
require_once RACKMONK_WHMCS_DIR . 'includes/class-whmcs-client-api.php';
require_once RACKMONK_WHMCS_DIR . 'includes/class-client-area.php';
new Rackmonk_Client_Area();
require_once RACKMONK_WHMCS_DIR . 'includes/class-email-delivery.php';
new Rackmonk_Email_Delivery();
require_once RACKMONK_WHMCS_DIR . 'includes/class-navigation.php';
new Rackmonk_Navigation();

// Register Elementor Widget
add_action('elementor/widgets/register', function($widgets_manager) {
    if (file_exists(RACKMONK_WHMCS_DIR . 'widgets/class-elementor-widget.php')) {
        require_once RACKMONK_WHMCS_DIR . 'widgets/class-elementor-widget.php';
        $widgets_manager->register(new \Rackmonk_Elementor_Pricing_Widget());
    }
});

// Register Elementor Category
add_action('elementor/elements/categories_registered', function($elements_manager) {
    $elements_manager->add_category(
        'rackmonk-elements',
        [
            'title' => esc_html__('Rackmonk Hosting', 'rackmonk-whmcs'),
            'icon'  => 'fa fa-server',
        ]
    );
});

// Enqueue styles & scripts
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'rackmonk-pricing-style',
        RACKMONK_WHMCS_URL . 'assets/css/pricing.css',
        [],
        filemtime(RACKMONK_WHMCS_DIR . 'assets/css/pricing.css')
    );

    wp_enqueue_script(
        'rackmonk-pricing-script',
        RACKMONK_WHMCS_URL . 'assets/js/pricing.js',
        ['jquery'],
        filemtime(RACKMONK_WHMCS_DIR . 'assets/js/pricing.js'),
        true
    );

    wp_localize_script('rackmonk-pricing-script', 'rackmonkData', [
        'ajaxurl'   => admin_url('admin-ajax.php'),
        'apiUrl'    => rest_url('rackmonk/v1/plans'),
        'currency'  => Rackmonk_Currency::current()['prefix'],
        'currencyCode' => Rackmonk_Currency::current()['code'],
        'cartUrl'   => Rackmonk_Website_Cart::get_cart_url(),
    ]);
});
