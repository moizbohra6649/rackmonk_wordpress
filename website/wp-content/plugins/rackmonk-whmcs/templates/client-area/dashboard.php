<?php
/** My Account → Dashboard: hosting workspace plus shortcuts to the other account pages. */
if (!defined('ABSPATH')) exit;
$cards = [
    'orders'       => ['Orders', 'Review your orders and their status.'],
    'edit-address' => ['Billing address', 'The address used on your invoices and at checkout.'],
    'edit-account' => ['Account details', 'Update your name and see how you sign in.'],
];
?>
<div class="rm-acct-dashboard">
    <?php do_action('woocommerce_account_dashboard'); ?>
    <div class="rm-acct-grid">
        <?php foreach ($cards as $endpoint => [$title, $text]): ?>
        <a class="rm-acct-card rm-acct-link rm-acct-link--<?php echo esc_attr($endpoint); ?>" href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>">
            <span class="rm-acct-icon" aria-hidden="true"></span>
            <span class="rm-acct-link-text"><strong><?php echo esc_html($title); ?></strong><span><?php echo esc_html($text); ?></span></span>
            <span class="rm-acct-arrow" aria-hidden="true">&rarr;</span>
        </a>
        <?php endforeach; ?>
    </div>
    <p class="rm-acct-help">Need a new plan? <a href="<?php echo esc_url(home_url('/pricing/')); ?>">Browse hosting plans</a> &middot; Need help? <a href="<?php echo esc_url(home_url('/contact-us/')); ?>">Contact support</a></p>
</div>
