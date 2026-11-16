<?php
/**
 * Hosting configurator with the shared site header and footer, without blog chrome.
 *
 * @package xhosting
 */

get_header();
?>
<main id="primary" class="site-main">
	<?php
	while (have_posts()) {
		the_post();
		the_content();
	}
	?>
</main>
<?php
get_footer();