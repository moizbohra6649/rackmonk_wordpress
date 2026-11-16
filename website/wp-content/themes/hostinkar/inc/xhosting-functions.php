<?php
/**
 * XHosting Theme Loops and Utility Markup (OOP Version)
 */

defined('ABSPATH') || exit;

class XHosting_Loop_Handler {

	/**
	 * Initialize all public hooks
	 */
	public function __construct() {
		// These can be hooked if needed, or just called directly in templates.
	}

	/**
	 * Render Preloader HTML
	 */
	public function render_preloader() {
		$enabled = cs_get_option('preloader_enable');

		if ($enabled):
			$preloader = cs_get_option('xh_custom_preloader');
			$preloader_url = !empty($preloader['url']) ? $preloader['url'] : get_template_directory_uri() . '/assets/img/rackmonk-icon-dark.png';
			?>
			<div id="preloader">
				<img src="<?php echo esc_url($preloader_url); ?>" alt="">
			</div>
			<?php
		endif;
	}

	/**
	 * Render Scroll To Top Button
	 */
	public function render_scroll_up_btn() {
		$enabled = cs_get_option('scroll_up_btn');
		if ($enabled):
			?>
			<button class="scroll__top scroll-to-target" data-target="html">
				<i class="fas fa-chevron-up"></i>
			</button>
			<?php
		endif;
	}

	/**
	 * Post loop for archive, index, or blog template
	 */
	public function render_post_loop() {
		if (have_posts()):
			while (have_posts()):
				the_post();
				get_template_part('template-parts/content', get_post_type());
			endwhile;

			$this->render_pagination('cg-pagination text-center ul-li');

		else:
			get_template_part('template-parts/content', 'none');
		endif;
	}

	/**
	 * Single post loop
	 */
	public function render_single_post() {
		while (have_posts()):
			the_post();
			get_template_part('template-parts/content', 'single');
		endwhile;
	}

	/**
	 * Archive loop
	 */
	public function render_archive_loop() {
		if (have_posts()):
			while (have_posts()):
				the_post();
				get_template_part('template-parts/content', get_post_type());
			endwhile;

			$this->render_pagination('ct-pagination');

		else:
			get_template_part('template-parts/content', 'none');
		endif;
	}

	/**
	 * Search loop
	 */
	public function render_search_loop() {
		if (have_posts()):
			while (have_posts()):
				the_post();
				get_template_part('template-parts/content', 'search');
			endwhile;

			$this->render_pagination('ct-pagination');

		else:
			get_template_part('template-parts/content', 'none');
		endif;
	}

	/**
	 * Page loop
	 */
	public function render_page_loop() {
		while (have_posts()):
			the_post();

			get_template_part('template-parts/content', 'page');

			if (comments_open() || get_comments_number()) {
				comments_template();
			}

		endwhile;
	}

	/**
	 * Pagination
	 *
	 * @param string $class Wrapper class for pagination.
	 */
	private function render_pagination($class = '') {
		echo '<div class="' . esc_attr($class) . '">';
		xhosting_pagination(
			'<i class="fa fa-angle-left"></i>',
			'<i class="fa fa-angle-right"></i>',
			'',
			['class' => '']
		);
		echo '</div>';
	}
}

// Initialize the class (You can also do this in a theme bootstrap file)
$xhosting_loops = new XHosting_Loop_Handler();
