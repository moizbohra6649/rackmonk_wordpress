<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package xhosting
 */

get_header();
$xhostingPostClass = '';
if (is_active_sidebar('sidebar-1')) {
	$xhostingPostClass = 'col-lg-8';
} else {
	$xhostingPostClass = 'col-12';
}
?>
<section id="cg-blog-feed" class="blog__post-area section-pb-120">
	<div class="container">
		<div class="row">
			<div class="<?php echo esc_attr($xhostingPostClass); ?>">
				<?php xhosting_loops()->render_post_loop(); ?>			
			</div>
			<?php get_sidebar(); ?>
		</div>
	</div>
</section>

<?php
get_footer();
