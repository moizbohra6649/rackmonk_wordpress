<?php
if (!defined('ABSPATH')) exit;
get_header();
?>
<main id="primary" class="rm-client-page<?php echo is_user_logged_in() ? ' rm-auth-logged-in' : ''; ?>">
<?php while (have_posts()): the_post(); the_content(); endwhile; ?>
</main>
<?php get_footer(); ?>
