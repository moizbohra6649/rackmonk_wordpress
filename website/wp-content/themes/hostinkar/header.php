<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package xhosting
 */
if (get_post_meta(get_the_ID(), 'xhosting_page_meta', true)) {
	$header_meta = get_post_meta(get_the_ID(), 'xhosting_page_meta', true);
} else {
	$header_meta = array();
}

if (array_key_exists('page_header_disable', $header_meta)) {
	$page_header_disable = $header_meta['page_header_disable'];
} else {
	$page_header_disable = false;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="<?php echo esc_attr(apply_filters('rackmonk_meta_description', get_bloginfo('description'))); ?>"/>
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class('fd-home-5'); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site site_wrapper">
	<?php
	xhosting_loops()->render_preloader();
	xhosting_loops()->render_scroll_up_btn();
	
	ob_start();
	if ($page_header_disable != true) {
		Xhosting_Helper::xhosting_header_template();
	}
	$site_header = ob_get_clean();
	if ($page_header_disable != true && (trim(wp_strip_all_tags($site_header)) !== '' || preg_match('/<(?:img|svg)\b/i', $site_header))) {
		echo $site_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} elseif ($page_header_disable != true) {
		?>
		<header class="rm-site-header">
			<div class="rm-site-header-inner">
				<div class="rm-site-brand">
					<?php if (has_custom_logo()) :
						the_custom_logo();
					else : ?>
						<a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(get_bloginfo('name')); ?></a>
					<?php endif; ?>
				</div>
				<button class="rm-menu-toggle" type="button" aria-expanded="false" aria-controls="rm-primary-nav"><span><?php esc_html_e('Menu', 'hostinkar'); ?></span><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button>
				<nav id="rm-primary-nav" class="rm-site-nav" aria-label="<?php esc_attr_e('Primary navigation', 'hostinkar'); ?>">
					<?php wp_nav_menu(array('theme_location' => 'menu-1', 'container' => false, 'menu_class' => 'rm-site-menu', 'fallback_cb' => false, 'rackmonk_mega' => class_exists('Rackmonk_Navigation_Walker'), 'walker' => class_exists('Rackmonk_Navigation_Walker') ? new Rackmonk_Navigation_Walker() : '')); ?>
				</nav>
			</div>
		</header>
		<?php
		wp_enqueue_script('rackmonk-header-menu', get_template_directory_uri() . '/assets/js/rackmonk-menu.js', [], filemtime(get_template_directory() . '/assets/js/rackmonk-menu.js'), true);
	}
	do_action('xhosting_before_main_content');
	?>
	<div class="body-bg-1 bg-default">
