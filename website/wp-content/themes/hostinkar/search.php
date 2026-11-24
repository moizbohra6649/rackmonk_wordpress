<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package xhosting
 */

get_header();
$xhostingPostClass = '';
if (is_active_sidebar('sidebar-1')) {
	$xhostingPostClass = 'col-xxl-8 col-xl-8 col-lg-8';
} else {
	$xhostingPostClass = 'col-12';
}
?>
<section id="cg-blog-feed" class="blog__post-area section-pb-120">
	<div class="container">
		<div class="row">
			<div class="<?php echo esc_attr($xhostingPostClass); ?>">
				<?php xhosting_loops()->render_search_loop(); ?>		
			</div>
			<?php get_sidebar(); ?>
		</div>
	</div>
</section>

<?php
get_footer();
