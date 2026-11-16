<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package xhosting
 */

get_header();
	$error_code_img = cs_get_option('error_code_img');
	$error_shape_img = cs_get_option('error_shape_img');
	$error_title = cs_get_option('error_title');
	$error_button = cs_get_option('error_button');
	$error_text = cs_get_option('error_text');
?>
<section class="error__area">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-6">
				<div class="error__content">
					<img src="<?php if (!empty($error_code_img['url'])) {
						echo esc_url($error_code_img['url']);
					} else {
						echo esc_url(get_template_directory_uri() . '/assets/img/404.svg');
					} ?>"
						alt="<?php esc_attr_e('404', 'hostinkar'); ?>" />
					<h2 class="title">
						<?php if (!empty($error_title)) {
							echo esc_html($error_title);
						} else {
							esc_html_e('Oops! Page can’t be found.', 'hostinkar');
						} ?>
					</h2>
					<p>
						<?php if (!empty($error_text)) {
							echo esc_html($error_text);
						} else {
							esc_html_e("The page you are looking for doesn't exist or has been moved.", 'hostinkar');
						} ?>
					</p>
					<a href="<?php echo esc_url(home_url('/')); ?>" class="tg-btn tg-btn-two">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M6 5.33268V3.99935C6 3.64573 6.14048 3.30659 6.39052 3.05654C6.64057 2.80649 6.97971 2.66602 7.33333 2.66602H12C12.3536 2.66602 12.6928 2.80649 12.9428 3.05654C13.1929 3.30659 13.3333 3.64573 13.3333 3.99935V11.9993C13.3333 12.353 13.1929 12.6921 12.9428 12.9422C12.6928 13.1922 12.3536 13.3327 12 13.3327H7.33333C6.97971 13.3327 6.64057 13.1922 6.39052 12.9422C6.14048 12.6921 6 12.353 6 11.9993V10.666" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
							<path d="M2 8H10.6667L8.66667 6" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
							<path d="M8.66797 10L10.668 8" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						<?php if (!empty($error_button)) {
							echo esc_html($error_button);
						} else {
							esc_html_e('Go Back Home', 'hostinkar');
						} ?>
					</a>
				</div>
			</div>
		</div>
	</div>
	<?php if(!empty($error_shape_img['url'])):?>
		<div class="shape">
			<img src="<?php echo esc_url($error_shape_img['url']);?>" alt="<?php if(!empty($error_shape_img['alt'])){ echo esc_attr($error_shape_img['alt']); }?>">
		</div>
	<?php endif;?>
</section>
<?php
get_footer();
