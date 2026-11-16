<?php
/** Do not report password-reset mail as sent when its transport is unavailable. */
if (!defined('ABSPATH')) exit;

class Rackmonk_Email_Delivery {
    private $reset_mail_failed = false;

    public function __construct() {
        add_action('lostpassword_post', [$this, 'validate_reset_transport'], 20, 2);
        add_action('wp_mail_failed', [$this, 'failed'], 10, 0);
        add_action('wp_mail_smtp_mailcatcher_send_failed', [$this, 'failed'], 10, 0);
        add_filter('wp_redirect', [$this, 'failed_reset_redirect'], 99);
        add_action('admin_notices', [$this, 'admin_notice']);
    }

    /** Empty means no known configuration blocker, not guaranteed inbox delivery. */
    public static function configuration_issue() {
        if (!class_exists('WPMailSMTP\\Options')) return '';
        try {
            $options = \WPMailSMTP\Options::init();
            if ($options->get('general', 'do_not_send')) return 'sending_disabled';
            // Other providers remain responsible for their own validation and errors.
            if ($options->get('mail', 'mailer') !== 'smtp') return '';
            $port = (int) $options->get('smtp', 'port');
            if (trim((string) $options->get('smtp', 'host')) === '' || $port < 1 || $port > 65535) return 'smtp_server';
            if ($options->get('smtp', 'auth') && (trim((string) $options->get('smtp', 'user')) === '' || trim((string) $options->get('smtp', 'pass')) === '')) return 'smtp_credentials';
            return '';
        } catch (Throwable $error) {
            return 'smtp_configuration';
        }
    }

    private static function reset_request() {
        return isset($_POST['wc_reset_password'], $_POST['user_login'], $_POST['woocommerce-lost-password-nonce'])
            && is_string($_POST['user_login']) && is_string($_POST['woocommerce-lost-password-nonce'])
            && wp_verify_nonce(wp_unslash($_POST['woocommerce-lost-password-nonce']), 'lost_password');
    }

    public function validate_reset_transport($errors, $user) {
        if (!self::reset_request() || !($errors instanceof WP_Error) || self::configuration_issue() === '') return;
        // Stop before creating a key, preserving any reset link already issued.
        $errors->add('email_unavailable', 'Password-reset email is temporarily unavailable. Please contact Rackmonk support.');
    }

    public function failed() {
        if (!self::reset_request() || $this->reset_mail_failed) return;
        $this->reset_mail_failed = true;
        // Error objects can contain message bodies and reset keys. Never retain them.
        wc_add_notice('We could not send your password-reset email. Please try again later or contact Rackmonk support.', 'error');
        if (WC()->session && !headers_sent()) WC()->session->set_customer_session_cookie(true);
    }

    public function failed_reset_redirect($location) {
        if ($this->reset_mail_failed && strpos($location, 'reset-link-sent=') !== false) return wc_get_account_endpoint_url('lost-password');
        return $location;
    }

    public function admin_notice() {
        if (!current_user_can('manage_options') || self::configuration_issue() === '') return;
        echo '<div class="notice notice-warning"><p><strong>Rackmonk email delivery needs configuration.</strong> Password-reset emails cannot be sent until the SMTP settings are complete. For Gmail, enter the matching account\'s App Password, not its normal password. <a href="' . esc_url(admin_url('admin.php?page=wp-mail-smtp')) . '">Open SMTP settings</a> | <a href="' . esc_url(admin_url('admin.php?page=wp-mail-smtp-tools&tab=test')) . '">Email Test</a>. Credentials are managed only in the protected admin settings.</p></div>';
    }
}
