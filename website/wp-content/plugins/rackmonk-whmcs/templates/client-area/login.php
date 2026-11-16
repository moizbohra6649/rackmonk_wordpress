<?php
if (!defined('ABSPATH')) exit;
Rackmonk_Client_Area::open('login');
do_action('woocommerce_before_customer_login_form');
?>
<form class="woocommerce-form woocommerce-form-login login rm-auth-login-form" method="post" action="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">
    <?php do_action('woocommerce_login_form_start'); ?>
    <?php Rackmonk_Client_Area::field('username', 'Email address', 'email', true, 'email', true); Rackmonk_Client_Area::field('password', 'Password', 'password', true, 'current-password', true); ?>
    <div class="rm-auth-options"><label><input name="rememberme" type="checkbox" value="forever"> Remember me</label><a href="<?php echo esc_url(wc_get_account_endpoint_url('lost-password')); ?>">Forgot password?</a></div>
    <?php do_action('woocommerce_login_form'); wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
    <input type="hidden" name="redirect" value="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">
    <button type="submit" class="rm-auth-submit" name="login" value="Sign in"><?php echo esc_html(Rackmonk_Client_Area::copy('account_login_button')); ?> <span aria-hidden="true">&rarr;</span></button>
    <?php do_action('woocommerce_login_form_end'); ?>
</form>
<p class="rm-auth-switch">New to Rackmonk? <a href="<?php echo esc_url(Rackmonk_Client_Area::register_url()); ?>">Create an account</a></p>
<?php Rackmonk_Client_Area::close(); do_action('woocommerce_after_customer_login_form'); ?>
