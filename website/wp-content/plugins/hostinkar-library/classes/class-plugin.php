<?php

namespace XHosting_Library;

if ( ! defined( 'ABSPATH' ) ) {
    exit(); // Exit if accessed directly
}

class Plugin {
    public static function init() {
        self::load_dependencies();
        self::init_hooks();
    }

    private static function load_dependencies() {
        // Load Codestar Framework
        if ( file_exists( XHOSTING_DIR . '/lib/codestar-framework/codestar-framework.php' ) ) {
            require_once XHOSTING_DIR . '/lib/codestar-framework/codestar-framework.php';
        }

        // Core Includes
        require_once XHOSTING_DIR . 'inc/xhosting-plugin-helper.php';
        require_once XHOSTING_DIR . 'classes/class-assets.php';
        require_once XHOSTING_DIR . 'inc/api.php';
        require_once XHOSTING_DIR . 'classes/class-elementor.php';
        require_once XHOSTING_DIR . 'classes/class-megamenu.php';
        require_once XHOSTING_DIR . 'inc/helper-functions.php';

        // Post Types
        require_once XHOSTING_DIR . 'inc/post-type/template.php';

        // Options and Metaboxes loaded after theme setup
        add_action( 'after_setup_theme', [ __CLASS__, 'after_theme_setup' ] );
    }

    public static function after_theme_setup() {
        require_once XHOSTING_DIR . 'inc/options/theme-option.php';
        require_once XHOSTING_DIR . 'inc/options/theme-metabox.php';
    }

    private static function init_hooks() {
        Assets::init();
        Xhosting_Elementor_Widget_Init::getInstance();
        Rs_MegaMenu_Register::get_instance(); 
    }
}
