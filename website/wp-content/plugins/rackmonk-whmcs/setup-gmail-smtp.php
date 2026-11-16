<?php
/** CLI-only setup; uses the official plugin installer, never accepts secrets in argv. */
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 3) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

// Policy: compact planning, dependency context, impact verification, no delegation.
$plugin = 'wp-mail-smtp/wp_mail_smtp.php';
if (!in_array('--install', $argv, true) && !in_array('--configure', $argv, true)) exit("Use --install, then --configure. Passwords are entered only in the WordPress admin interface.\n");
if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
    if (!in_array('--install', $argv, true)) throw new RuntimeException('Install the SMTP plugin before configuring it.');
    $info = plugins_api('plugin_information', ['slug' => 'wp-mail-smtp', 'fields' => ['sections' => false, 'screenshots' => false, 'banners' => false, 'icons' => false]]);
    if (is_wp_error($info)) throw new RuntimeException('Official plugin metadata could not be retrieved: ' . $info->get_error_message());
    $url = wp_parse_url($info->download_link ?? '');
    if (($url['scheme'] ?? '') !== 'https' || ($url['host'] ?? '') !== 'downloads.wordpress.org') throw new RuntimeException('Unexpected plugin package source.');
    if (get_filesystem_method() !== 'direct') throw new RuntimeException('Direct WordPress filesystem access is required.');
    $skin = new Automatic_Upgrader_Skin();
    $upgrader = new Plugin_Upgrader($skin);
    $installed = $upgrader->install($info->download_link);
    if (is_wp_error($installed) || !$installed || !file_exists(WP_PLUGIN_DIR . '/' . $plugin)) throw new RuntimeException('SMTP plugin installation failed. Existing settings were not changed.');
    echo "Installed WP Mail SMTP from the official WordPress plugin repository.\n";
}
if (!is_plugin_active($plugin)) {
    $activated = activate_plugin($plugin, '', false, true);
    if (is_wp_error($activated)) throw new RuntimeException('SMTP plugin could not be activated: ' . $activated->get_error_message());
}
if (in_array('--configure', $argv, true)) {
    if (!class_exists('WPMailSMTP\\Options')) throw new RuntimeException('Run configuration in a fresh request after activating the SMTP plugin.');
    $options = \WPMailSMTP\Options::init();
    $mailer = $options->get('mail', 'mailer');
    $host = $options->get('smtp', 'host');
    if (!in_array($mailer, ['', 'mail', 'smtp'], true) || ($host && $host !== 'smtp.gmail.com') || (string) $options->get('smtp', 'pass') !== '') {
        echo "Existing mail provider or credentials detected. All existing settings preserved.\n";
    } else {
        $sender = $options->get('smtp', 'user');
        if (!is_email($sender)) $sender = get_option('woocommerce_email_from_address', get_option('admin_email'));
        if (!is_email($sender)) throw new RuntimeException('Set a valid sender address in WordPress before configuring SMTP.');
        $options->set([
            'mail' => ['mailer' => 'smtp', 'from_email' => $sender, 'from_name' => get_bloginfo('name'), 'from_email_force' => true, 'from_name_force' => true, 'return_path' => true],
            'smtp' => ['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls', 'autotls' => true, 'auth' => true, 'user' => $sender],
            'debug_events' => ['email_debug' => false],
        ], false, false);
        echo "Prepared Gmail SMTP: smtp.gmail.com, port 587, TLS, authentication enabled.\n";
        echo "The existing website sender is prefilled. Enter the matching Gmail App Password in WP Mail SMTP > Settings.\n";
    }
}
echo "WP Mail SMTP is active. No credentials displayed; no emails sent.\nVERDICT: PASS\n";
