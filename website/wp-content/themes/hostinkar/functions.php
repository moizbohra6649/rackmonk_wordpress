<?php
/**
 * Xhosting Theme Functions and Definitions
 *
 * @package Xhosting
 */

defined('ABSPATH') || exit;

final class Xhosting_Theme {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}
	

	/**
	 * Define theme constants
	 */
	private function define_constants() {
		define('XHOSTING_THEME_DRI', get_template_directory());
		define('XHOSTING_INC_DRI', XHOSTING_THEME_DRI . '/inc/');
		define('XHOSTING_THEME_URI', get_template_directory_uri());
		define('XHOSTING_CSS_PATH', XHOSTING_THEME_URI . '/assets/css');
		define('XHOSTING_JS_PATH', XHOSTING_THEME_URI . '/assets/js');
		define('XHOSTING_IMG_PATH', XHOSTING_THEME_URI . '/assets/images');
		define('Xhosting_Admin_DRI', XHOSTING_THEME_DRI . '/admin');
	}

	/**
	 * Load required files
	 */
	private function includes() {
		require_once XHOSTING_INC_DRI . 'enqueue-scripts.php';
		require_once XHOSTING_INC_DRI . 'custom-header.php';
		require_once XHOSTING_INC_DRI . 'template-tags.php';
		require_once XHOSTING_INC_DRI . 'class-wp-xhosting-navwalker.php';
		require_once XHOSTING_INC_DRI . 'template-functions.php';
		require_once XHOSTING_INC_DRI . 'xhosting-functions.php';
		require_once XHOSTING_INC_DRI . 'cs-framework-functions.php';
		require_once XHOSTING_INC_DRI . 'dynamic-style.php';
		require_once XHOSTING_INC_DRI . 'xhosting-helper-class.php';
		require_once XHOSTING_THEME_DRI . '/inc/admin/class-admin-dashboard.php';
		require_once XHOSTING_THEME_DRI . '/inc/admin/demo-import/functions.php';
		require_once XHOSTING_INC_DRI . 'customizer.php';
		require_once XHOSTING_INC_DRI . 'breadcrumb-init.php';
		//require_once XHOSTING_THEME_DRI . '/inc/admin/Activation/envato-license-verifier.php';
		

		if (defined('JETPACK__VERSION')) {
			require_once XHOSTING_INC_DRI . 'jetpack.php';
		}
		function xhosting_loops() {
	static $instance = null;
	if ( is_null( $instance ) ) {
		$instance = new XHosting_Loop_Handler();
	}
	return $instance;
}
	}

	/**
	 * Hook into WordPress
	 */
	private function init_hooks() {
		add_action('after_setup_theme', [$this, 'setup']);
		add_action('after_setup_theme', [$this, 'set_content_width'], 0);
		add_action('widgets_init', [$this, 'widgets_init']);
		add_filter('th_enable_megamenu', [$this, 'enable_megamenu']);
	}

	/**
	 * Theme setup
	 */
	public function setup() {
		load_theme_textdomain('hostinkar', get_template_directory() . '/languages');

		add_theme_support('automatic-feed-links');
		add_image_size('Xhosting-img-size-1', 790, 368, true);
		add_theme_support('title-tag');
		remove_theme_support('widgets-block-editor');
		add_filter('big_image_size_threshold', '__return_false');
		add_theme_support('post-thumbnails');
		add_theme_support('woocommerce');
		add_theme_support('wc-product-gallery-lightbox');

		register_nav_menus([
			'menu-1' => esc_html__('Primary', 'hostinkar'),
		]);

		add_theme_support('html5', [
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		]);

		add_theme_support('post-formats', ['standard', 'image', 'video', 'gallery']);

		add_theme_support('custom-background', apply_filters('Xhosting_custom_background_args', [
			'default-color' => 'ffffff',
			'default-image' => '',
		]));

		add_theme_support('customize-selective-refresh-widgets');

		add_theme_support('custom-logo', [
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		]);
	}

	/**
	 * Set content width
	 */
	public function set_content_width() {
		$GLOBALS['content_width'] = apply_filters('Xhosting_content_width', 640);
	}

	/**
	 * Register widget areas
	 */
	public function widgets_init() {
		register_sidebar([
			'name'          => esc_html__('Sidebar', 'hostinkar'),
			'id'            => 'sidebar-1',
			'description'   => esc_html__('Add widgets here.', 'hostinkar'),
			'before_widget' => '<div id="%1$s" class="%2$s cg-side-widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		]);

		register_sidebar([
			'name'          => esc_html__('Shop Sidebar', 'hostinkar'),
			'id'            => 'shop-sidebar-1',
			'description'   => esc_html__('Add widgets here.', 'hostinkar'),
			'before_widget' => '<div id="%1$s" class="widget mt-30 %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widget__title">',
			'after_title'   => '</h2>',
		]);
	}

	/**
	 * Enable megamenu
	 */
	public function enable_megamenu() {
		return true;
	}
}

// Initialize the theme
new Xhosting_Theme();

add_filter('elementor/frontend/widget/should_render', function ($should_render, $widget) {
	if ($widget->get_name() === 'xhosting-domain-form' && class_exists('Rackmonk_Site_Content') && \Rackmonk_Site_Content::text('show_domain_search', '0') !== '1' && !\Elementor\Plugin::$instance->editor->is_edit_mode()) {
		return false;
	}
	return $should_render;
}, 10, 2);

add_filter('template_include', function ($template) {
	if (is_page('about-us') && class_exists('Rackmonk_Site_Content') && \Rackmonk_Site_Content::text('about_use_editor', '0') !== '1') {
		return get_template_directory() . '/page-rackmonk-about.php';
	}
	return $template;
}, 99);

// Old page addresses → their new ones (WordPress only redirects renamed posts, not pages).
add_action('template_redirect', function () {
	if (!is_404()) return;
	$moved = ['privacy-policy-2' => get_privacy_policy_url()];
	$path = trim((string) wp_parse_url(add_query_arg([]), PHP_URL_PATH), '/');
	$base = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
	if ($base !== '' && strpos($path, $base . '/') === 0) $path = substr($path, strlen($base) + 1);
	if (!empty($moved[$path])) {
		wp_safe_redirect($moved[$path], 301);
		exit;
	}
});

add_action('wp_head', function () {
	if (has_site_icon()) return;
	$icon = esc_url(get_template_directory_uri() . '/assets/img/rackmonk-icon-dark.png');
	echo '<link rel="icon" type="image/png" href="' . $icon . '">';
	echo '<link rel="apple-touch-icon" href="' . $icon . '">';
}, 99);
