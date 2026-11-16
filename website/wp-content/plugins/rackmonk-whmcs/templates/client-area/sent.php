<?php
if (!defined('ABSPATH')) exit;
Rackmonk_Client_Area::open('sent');
?>
<p class="rm-auth-switch"><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Back to sign in</a></p>
<?php Rackmonk_Client_Area::close(); ?>
