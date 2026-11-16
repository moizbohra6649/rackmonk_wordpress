<?php
/*
Plugin Name: Hostinkar Library
Plugin URI: https://themeforest.net/user/byteflows
Description: After installing the Hostinkar WordPress Theme, be sure to install the required 'xhosting-library' plugin first. This plugin is essential for unlocking all the core features and full functionality of the xHosting theme.
Author: byteflows
Author URI: http://byteflows.net/
Version: 1.0.2
Text Domain: hostinkar-library
*/
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'XHOSTING_VERSION', '1.0.0' );
define( 'XHOSTING_DIR', plugin_dir_path( __FILE__ ) );
define( 'XHOSTING_URL', plugin_dir_url( __FILE__ ) );
define( 'XHOSTING_DIR_URL',plugin_dir_url(__FILE__) );
require_once XHOSTING_DIR . 'classes/class-plugin.php';


// Bootstrap the plugin
XHosting_Library\Plugin::init();


include( XHOSTING_DIR . '/lib/templates/import.php');
include( XHOSTING_DIR . '/lib/templates/init.php');
include( XHOSTING_DIR . '/lib/templates/load.php');
include( XHOSTING_DIR . '/lib/templates/api.php');
\ByteflowsTheme\Templates\Import::instance()->load();
\ByteflowsTheme\Templates\Load::instance()->load();
\ByteflowsTheme\Templates\Templates::instance()->init();

if (!defined('TEMPLATE_LOGO_SRC')){
	define('TEMPLATE_LOGO_SRC', XHOSTING_URL . '/lib/templates/assets/img/icon-sv.svg');
}


