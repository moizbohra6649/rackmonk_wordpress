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
		<div class="row">
			<?php // Same layout as the blog list: the post beside the blog sidebar, full width when the sidebar is empty. ?>
			<div class="<?php echo is_active_sidebar('sidebar-1') ? 'col-lg-8' : 'col-12'; ?>">
				<?php xhosting_loops()->render_single_post(); ?>
			</div>
			<?php get_sidebar(); ?>
		</div>
	</div>
</section>

<?php
get_footer();
