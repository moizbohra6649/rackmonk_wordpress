<?php
if (!defined('ABSPATH')) exit;
Rackmonk_Client_Area::open('reset');
do_action('woocommerce_before_reset_password_form');
?>
<form method="post" class="woocommerce-ResetPassword lost_reset_password">
    <?php Rackmonk_Client_Area::field('password_1', 'New password', 'password', true, 'new-password', true); Rackmonk_Client_Area::field('password_2', 'Confirm new password', 'password', true, 'new-password', true); ?>
    <input type="hidden" name="reset_key" value="<?php echo esc_attr($args['key']); ?>"><input type="hidden" name="reset_login" value="<?php echo esc_attr($args['login']); ?>"><input type="hidden" name="wc_reset_password" value="true">
    <?php do_action('woocommerce_resetpassword_form'); wp_nonce_field('reset_password', 'woocommerce-reset-password-nonce'); ?>
    <button type="submit" class="rm-auth-submit"><?php echo esc_html(Rackmonk_Client_Area::copy('account_reset_button')); ?> <span aria-hidden="true">&rarr;</span></button>
</form>
<?php Rackmonk_Client_Area::close(); do_action('woocommerce_after_reset_password_form'); ?>
