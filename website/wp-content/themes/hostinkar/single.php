<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package xhosting
 */

get_header();
$breadcrumb_bg = cs_get_option('breadcrumb_bg');
$breadcrumb_bg = !empty($breadcrumb_bg) ? $breadcrumb_bg['url'] : '';
?>
<section class="blog__details-area section-pb-120">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-8 col-md-10">
				<?php xhosting_loops()->render_single_post(); ?>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
