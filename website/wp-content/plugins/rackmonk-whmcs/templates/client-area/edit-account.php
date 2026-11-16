<?php
/**
 * My Account → Account details. Email and password belong to the WHMCS client account
 * (that is where customers sign in), so they are shown here but changed elsewhere.
 */
if (!defined('ABSPATH')) exit;
$user = wp_get_current_user();
$login_email = Rackmonk_Client_Area::login_email($user);
do_action('woocommerce_before_edit_account_form');
?>
<h2 class="rm-acct-title">Account details</h2>
<form class="woocommerce-EditAccountForm edit-account rm-acct-form" action="" method="post" <?php do_action('woocommerce_edit_account_form_tag'); ?>>
    <?php do_action('woocommerce_edit_account_form_start'); ?>
    <div class="rm-acct-fields">
        <p class="rm-acct-field"><label for="account_first_name">First name <span class="required" aria-hidden="true">*</span></label>
            <input type="text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr($user->first_name); ?>" required aria-required="true"></p>
        <p class="rm-acct-field"><label for="account_last_name">Last name <span class="required" aria-hidden="true">*</span></label>
            <input type="text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr($user->last_name); ?>" required aria-required="true"></p>
        <p class="rm-acct-field rm-acct-wide"><label for="account_display_name">Display name <span class="required" aria-hidden="true">*</span></label>
            <input type="text" name="account_display_name" id="account_display_name" value="<?php echo esc_attr($user->display_name); ?>" required aria-required="true" aria-describedby="account_display_name_description">
            <small id="account_display_name_description">This is how your name appears on the website.</small></p>
    </div>
    <input type="hidden" name="account_email" value="<?php echo esc_attr($user->user_email); ?>">
    <?php do_action('woocommerce_edit_account_form_fields'); do_action('woocommerce_edit_account_form'); ?>
    <p class="rm-acct-actions">
        <?php wp_nonce_field('save_account_details', 'save-account-details-nonce'); ?>
        <button type="submit" class="rm-acct-button" name="save_account_details" value="Save changes">Save changes</button>
        <input type="hidden" name="action" value="save_account_details">
    </p>
    <?php do_action('woocommerce_edit_account_form_end'); ?>
</form>

<h3 class="rm-acct-subtitle">Sign-in</h3>
<dl class="rm-acct-signin">
    <div><dt>Email address</dt><dd><?php echo esc_html($login_email); ?></dd>
        <dd class="rm-acct-signin-action"><form method="post"><input type="hidden" name="rm_auth_action" value="sso"><?php wp_nonce_field('rackmonk-sso', 'rackmonk-sso-nonce'); ?><button type="submit" class="rm-acct-textbutton">Change in hosting workspace</button></form></dd></div>
    <div><dt>Password</dt><dd aria-label="Password hidden">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</dd>
        <dd class="rm-acct-signin-action"><a class="rm-acct-textbutton" href="<?php echo esc_url(wc_get_account_endpoint_url('lost-password')); ?>">Reset password</a></dd></div>
</dl>
<?php do_action('woocommerce_after_edit_account_form'); ?>
