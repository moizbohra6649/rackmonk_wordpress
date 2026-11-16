<?php
if (!defined('ABSPATH')) exit;
Rackmonk_Client_Area::open('forgot');
do_action('woocommerce_before_lost_password_form');
?>
<form method="post" class="woocommerce-ResetPassword lost_reset_password">
    <?php Rackmonk_Client_Area::field('user_login', 'Email address', 'email', true, 'email', true); do_action('woocommerce_lostpassword_form'); ?>
    <input type="hidden" name="wc_reset_password" value="true">
    <?php wp_nonce_field('lost_password', 'woocommerce-lost-password-nonce'); ?>
    <button type="submit" class="rm-auth-submit"><?php echo esc_html(Rackmonk_Client_Area::copy('account_forgot_button')); ?> <span aria-hidden="true">&rarr;</span></button>
</form>
<p class="rm-auth-switch"><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Back to sign in</a></p>
<?php Rackmonk_Client_Area::close(); do_action('woocommerce_after_lost_password_form'); ?>
