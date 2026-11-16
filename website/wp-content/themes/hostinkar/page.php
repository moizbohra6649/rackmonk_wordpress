<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package xhosting
 */

get_header();
$xhostingPostClass = '';
if(is_active_sidebar('sidebar-1')){
	$xhostingPostClass = 'col-xxl-8 col-xl-12';
}else{
	$xhostingPostClass = 'col-lg-10 offset-lg-1';
}
?>
<section class="egx-blog-7-area fix pb-120 pt-120">
	<div class="container egx-container-2">
		<div class="row">
		<?php xhosting_loops()->render_page_loop(); ?>
		</div>
	</div>
</section>

<?php
get_footer();
