<?php
namespace XHosting_Library;
if ( ! defined( 'ABSPATH' ) ) {
    exit(); // Exit if accessed directly
}
class Assets {
    public static function init() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'frontend' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin' ] );
    }

    public static function frontend() {
        //wp_enqueue_script( 'xhosting-elements', XHOSTING_URL . 'assets/js/xhosting-elements.js', [ 'jquery' ], '1.0', true );
        wp_enqueue_script( 'xhosting-addon-core', XHOSTING_URL . 'assets/js/core.js', [ 'jquery' ], '1.0', true );
        wp_enqueue_script( 'xhosting-blog-ajax', XHOSTING_URL . 'assets/js/xhosting-blog-ajax.js', [ 'jquery' ], '1.0', true );
        wp_localize_script('xhosting-blog-ajax', 'xhosting_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('xhosting_ajax_nonce')
        ]);

    }

    public static function admin() {
        wp_enqueue_style( 'admin-style', XHOSTING_URL . 'assets/css/admin-style.css' );
    }
}
