<?php
/** Website authentication uses WordPress; hosting identities use the supported WHMCS API. */
if (!defined('ABSPATH')) exit;

class Rackmonk_Client_Area {
    public function __construct() {
        add_shortcode('rackmonk_register', [$this, 'registration_form']);
        add_action('wp_loaded', [$this, 'handle_post'], 15);
        add_action('wp_loaded', [$this, 'whmcs_logout'], 14);
        add_action('template_redirect', [$this, 'prepare_page']);
        add_filter('template_include', [$this, 'page_template'], 30);
        add_filter('woocommerce_locate_template', [$this, 'account_template'], 20, 3);
        add_action('wp_enqueue_scripts', [$this, 'assets'], 30);
        add_filter('authenticate', [$this, 'whmcs_login'], 30, 3);
        add_filter('authenticate', [$this, 'pending_login'], 99, 3);
        add_action('wp_loaded', [$this, 'prepare_reset_account'], 19); // before WooCommerce's lost-password handler (20)
        add_action('lostpassword_post', [$this, 'client_reset_only'], 10, 2);
        add_filter('woocommerce_email_recipient_customer_reset_password', [$this, 'real_recipient'], 10, 2);
        add_filter('wp_mail', [$this, 'mail_to_real_email'], 1);
        add_action('template_redirect', [$this, 'show_real_email'], 20);
        add_action('validate_password_reset', [$this, 'save_whmcs_password'], 100, 2);
        add_filter('woocommerce_process_login_errors', [$this, 'login_limit']);
        add_action('wp_login_failed', [$this, 'failed_login']);
        add_action('woocommerce_account_dashboard', [$this, 'dashboard']);
        add_action('woocommerce_before_account_navigation', [$this, 'account_header']);
        add_filter('woocommerce_account_menu_items', [$this, 'account_menu'], 20);
        // Hosting has nothing to ship: the account keeps a billing address only.
        add_filter('woocommerce_my_account_get_addresses', static function ($addresses) { return array_intersect_key($addresses, ['billing' => 1]); }, 20);
        add_filter('woocommerce_return_to_shop_redirect', static function () { return home_url('/pricing/'); });
        add_filter('woocommerce_return_to_shop_text', static function () { return 'Browse hosting plans'; });
        // The empty Orders page hard-codes "Browse products"; this shop sells hosting plans.
        add_filter('gettext', static function ($translation, $text, $domain) { return $domain === 'woocommerce' && $text === 'Browse products' ? 'Browse hosting plans' : $translation; }, 10, 3);
        add_action('validate_password_reset', [$this, 'reset_policy'], 10, 2);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_post_rackmonk_verify_account', [$this, 'verify_account']);
        add_filter('body_class', static function ($classes) { if (self::is_access_page()) $classes[] = 'rm-client-access'; return $classes; });
    }

    public static function content_defaults() {
        return [
            'account_story_title' => 'Big ideas deserve a reliable home.',
            'account_story_intro' => 'Your next project starts here. Access your account, review your orders and connect with your hosting workspace.',
            'account_story_note' => 'One place to take your next step.',
            'account_login_title' => 'Welcome back.', 'account_login_intro' => 'Sign in with the email and password of your Rackmonk client account.',
            'account_register_title' => 'Make yourself at home.', 'account_register_intro' => 'Create your Rackmonk account. Add your details to get started.',
            'account_forgot_title' => 'A fresh start.', 'account_forgot_intro' => 'Enter the email address of your Rackmonk client account. We will email you a secure link to set a new password.',
            'account_reset_title' => 'Choose a new password.', 'account_reset_intro' => 'Use a strong, unique password. You will use it to sign in to your Rackmonk client account.',
            'account_sent_title' => 'Check your inbox.', 'account_sent_intro' => 'If a reset was requested successfully, follow the link in your email. Check your spam folder too.',
            'account_login_button' => 'Sign in', 'account_register_button' => 'Create my account',
            'account_forgot_button' => 'Send reset link', 'account_reset_button' => 'Save new password',
            'account_hosting_button' => 'Open hosting workspace',
        ];
    }

    public static function copy($key) {
        $defaults = self::content_defaults();
        return Rackmonk_Site_Content::text($key, $defaults[$key] ?? '');
    }

    public static function register_url() {
        $page = get_post((int) get_option('rackmonk_register_page_id'));
        return $page && $page->post_status === 'publish' ? get_permalink($page) : home_url('/register/');
    }

    private static function is_register() { $id = (int) get_option('rackmonk_register_page_id'); return $id > 0 && is_page($id); }
    private static function is_access_page() { return function_exists('is_account_page') && (is_account_page() || self::is_register()); }

    public function assets() {
        if (!self::is_access_page()) return;
        wp_enqueue_style('rackmonk-client-area', RACKMONK_WHMCS_URL . 'assets/css/client-area.css', [], filemtime(RACKMONK_WHMCS_DIR . 'assets/css/client-area.css'));
        wp_enqueue_script('rackmonk-client-area', RACKMONK_WHMCS_URL . 'assets/js/client-area.js', [], filemtime(RACKMONK_WHMCS_DIR . 'assets/js/client-area.js'), true);
    }

    public function prepare_page() {
        if (!self::is_access_page()) return;
        nocache_headers();
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
        if (self::is_register() && is_user_logged_in()) { wp_safe_redirect(wc_get_page_permalink('myaccount')); exit; }
    }

    public function page_template($template) {
        return self::is_access_page() ? RACKMONK_WHMCS_DIR . 'templates/client-area/page.php' : $template;
    }

    public function account_template($template, $name, $path) {
        $views = ['myaccount/form-login.php' => 'login', 'myaccount/form-lost-password.php' => 'forgot', 'myaccount/form-reset-password.php' => 'reset', 'myaccount/lost-password-confirmation.php' => 'sent', 'myaccount/dashboard.php' => 'dashboard', 'myaccount/form-edit-account.php' => 'edit-account'];
        return self::is_access_page() && isset($views[$name]) ? RACKMONK_WHMCS_DIR . 'templates/client-area/' . $views[$name] . '.php' : $template;
    }

    public static function open($mode) {
        ?>
        <section class="rm-auth" data-auth-mode="<?php echo esc_attr($mode); ?>">
            <aside class="rm-auth-story"><h1><?php echo esc_html(self::copy('account_story_title')); ?></h1><p><?php echo esc_html(self::copy('account_story_intro')); ?></p>
                <div class="rm-auth-art" aria-hidden="true"><div class="rm-auth-server"><span></span><span></span><span></span></div><div class="rm-auth-server"><span></span><span></span><span></span></div><div class="rm-auth-server"><span></span><span></span><span></span></div></div>
                <span class="rm-auth-story-note"><?php echo esc_html(self::copy('account_story_note')); ?></span>
            </aside>
            <div class="rm-auth-form-panel"><div class="rm-auth-topline"><a href="<?php echo esc_url(home_url('/')); ?>">&larr; Back to Rackmonk</a></div>
            <span class="rm-auth-kicker"><?php echo esc_html($mode === 'register' ? 'YOUR NEXT CHAPTER' : 'GOOD TO SEE YOU'); ?></span><h2 class="rm-auth-heading"><?php echo esc_html(self::copy('account_' . $mode . '_title')); ?></h2><p class="rm-auth-intro"><?php echo esc_html(self::copy('account_' . $mode . '_intro')); ?></p>
        <?php
        wc_print_notices();
    }

    public static function close() { echo '</div></section>'; }

    public static function posted($key, $default = '') {
        return isset($_POST[$key]) && is_string($_POST[$key]) ? wp_unslash($_POST[$key]) : $default;
    }

    public static function field($name, $label, $type = 'text', $required = false, $autocomplete = '', $full = false) {
        $value = $type === 'password' ? '' : self::posted($name);
        $id = 'rm_' . $name;
        ?>
        <div class="rm-auth-field<?php echo $full ? ' rm-auth-full' : ''; ?>"><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?> <?php if ($required): ?><span aria-hidden="true">*</span><?php else: ?><small>(optional)</small><?php endif; ?></label>
        <?php if ($type === 'password'): ?><div class="rm-auth-password"><?php endif; ?>
        <input id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" type="<?php echo esc_attr($type); ?>" value="<?php echo esc_attr($value); ?>" <?php echo $required ? 'required aria-required="true"' : ''; ?> autocomplete="<?php echo esc_attr($autocomplete); ?>" maxlength="<?php echo $autocomplete === 'current-password' ? '4096' : ($type === 'password' && $name !== 'securityqans' ? '64' : '255'); ?>">
        <?php if ($type === 'password'): ?><button type="button" data-toggle-password="<?php echo esc_attr($id); ?>" aria-controls="<?php echo esc_attr($id); ?>" aria-pressed="false">Show</button></div><?php endif; ?>
        </div>
        <?php
    }

    public function registration_form() {
        if (is_user_logged_in()) return '<p><a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">Open your Client Area</a></p>';
        $schema = Rackmonk_WHMCS_Client_API::schema();
        ob_start();
        self::open('register');
        if (is_wp_error($schema) || empty($schema['registration_enabled'])) {
            echo '<div class="woocommerce-info" role="status">New registrations are temporarily unavailable. Please contact Rackmonk for assistance.</div><p class="rm-auth-switch"><a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">Back to sign in</a></p>';
            self::close();
            return ob_get_clean();
        }
        $optional = $schema['optional_fields'] ?? [];
        $needed = static function ($key) use ($optional) { return !in_array($key, $optional, true); };
        $countries = WC()->countries->get_countries();
        $country = self::posted('country', !empty($schema['default_country']) ? $schema['default_country'] : 'IN');
        $states = WC()->countries->get_states($country);
        ?>
        <form method="post" class="rm-auth-register-form" action="<?php echo esc_url(self::register_url()); ?>">
            <input type="hidden" name="rm_auth_action" value="register"><?php wp_nonce_field('rackmonk-register', 'rackmonk-register-nonce'); ?>
            <div hidden aria-hidden="true"><label>Website URL<input type="text" name="rm_company_url" tabindex="-1" autocomplete="off"></label></div>
            <fieldset><legend><span>Personal information</span></legend><div class="rm-auth-grid2cols">
            <?php self::field('firstname', 'First name', 'text', $needed('firstname'), 'given-name'); self::field('lastname', 'Last name', 'text', $needed('lastname'), 'family-name'); self::field('email', 'Email address', 'email', true, 'email'); self::field('phonenumber', 'Phone number with country code', 'tel', true, 'tel'); ?>
            </div></fieldset>
            <fieldset><legend><span>Billing details</span></legend><div class="rm-auth-grid2cols">
            <?php self::field('companyname', 'Company name', 'text', false, 'organization', true); self::field('address1', 'Street address', 'text', $needed('address1'), 'address-line1', true); self::field('address2', 'Apartment, suite or address line 2', 'text', false, 'address-line2', true); ?>
            <div class="rm-auth-field"><label for="rm_country">Country <span aria-hidden="true">*</span></label><select id="rm_country" name="country" required autocomplete="country"><?php foreach ($countries as $code => $name): ?><option value="<?php echo esc_attr($code); ?>" <?php selected($country, $code); ?>><?php echo esc_html($name); ?></option><?php endforeach; ?></select></div>
            <div class="rm-auth-field" id="rm_state_field"><label for="rm_state">State / region <?php echo $needed('state') ? '<span aria-hidden="true">*</span>' : '<small>(optional)</small>'; ?></label>
            <?php if (is_array($states) && $states): ?><select id="rm_state" name="state" data-required="<?php echo $needed('state') ? '1' : '0'; ?>" autocomplete="address-level1" <?php echo $needed('state') ? 'required' : ''; ?>><option value="">Choose state / region</option><?php foreach ($states as $code => $name): ?><option value="<?php echo esc_attr($code); ?>" <?php selected(self::posted('state'), $code); ?>><?php echo esc_html($name); ?></option><?php endforeach; ?></select><?php else: ?><input type="text" id="rm_state" name="state" data-required="<?php echo $needed('state') ? '1' : '0'; ?>" value="<?php echo esc_attr(self::posted('state')); ?>" autocomplete="address-level1" maxlength="255" <?php echo $needed('state') ? 'required' : ''; ?>><?php endif; ?></div>
            <?php self::field('city', 'City', 'text', $needed('city'), 'address-level2'); self::field('postcode', 'Postcode / ZIP', 'text', $needed('postcode'), 'postal-code'); if (!empty($schema['tax_enabled'])) self::field('tax_id', $schema['tax_label'] ?? 'Tax ID / GSTIN', 'text', false, 'off', true); ?>
            </div></fieldset>
            <?php $fields = $schema['custom_fields'] ?? []; $currencies = $schema['currencies'] ?? []; if ($fields || count($currencies) > 1): ?>
            <fieldset><legend><span>Additional information</span></legend><div class="rm-auth-grid2cols">
            <?php foreach ($fields as $field) self::custom_field($field); ?>
            <?php if ($currencies): ?><div class="rm-auth-field"><label for="rm_currency">Hosting billing currency</label><select id="rm_currency" name="currency"><?php foreach ($currencies as $currency): ?><option value="<?php echo (int) $currency['id']; ?>" <?php selected((int) self::posted('currency', !empty($currency['default']) ? (string) $currency['id'] : ''), (int) $currency['id']); ?>><?php echo esc_html($currency['code']); ?></option><?php endforeach; ?></select></div><?php endif; ?>
            </div></fieldset><?php elseif ($currencies): ?><input type="hidden" name="currency" value="<?php echo (int) $currencies[0]['id']; ?>"><?php endif; ?>
            <fieldset><legend><span>Account security</span></legend><div class="rm-auth-grid2cols">
            <?php self::field('password', 'Password', 'password', true, 'new-password'); self::field('password_confirm', 'Confirm password', 'password', true, 'new-password'); ?>
            <output class="rm-auth-meter rm-auth-full" data-password-strength="rm_password">Use 12-64 characters. Choose a unique password.</output>
            <?php if (!empty($schema['security_questions'])): ?><div class="rm-auth-field rm-auth-full"><label for="rm_securityqid">Security question <span aria-hidden="true">*</span></label><select id="rm_securityqid" name="securityqid" required><option value="">Choose a question</option><?php foreach ($schema['security_questions'] as $question): ?><option value="<?php echo (int) $question['id']; ?>" <?php selected((int) self::posted('securityqid'), (int) $question['id']); ?>><?php echo esc_html($question['question']); ?></option><?php endforeach; ?></select></div><?php self::field('securityqans', 'Security answer', 'password', true, 'off', true); endif; ?>
            </div></fieldset>
            <?php if (!empty($schema['marketing_enabled'])): ?><label class="rm-auth-consent"><input type="checkbox" name="marketingoptin" value="1" <?php checked(self::posted('marketingoptin') === '1'); ?>><span>I would like to receive product news and offers. I can opt out later.</span></label><?php endif; ?>
            <label class="rm-auth-consent"><input type="checkbox" name="terms" value="1" required <?php checked(self::posted('terms') === '1'); ?>><span>I agree to the <a href="<?php echo esc_url(self::terms_url()); ?>" target="_blank" rel="noopener">Terms and Conditions</a> and <a href="<?php echo esc_url(self::privacy_url()); ?>" target="_blank" rel="noopener">Privacy Policy</a>.</span></label>
            <button type="submit" class="rm-auth-submit"><?php echo esc_html(self::copy('account_register_button')); ?> <span aria-hidden="true">&rarr;</span></button>
            <p class="rm-auth-switch">Already have an account? <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Sign in</a></p>
        </form>
        <script type="application/json" id="rm-account-states"><?php echo wp_json_encode(WC()->countries->get_states(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
        <?php
        self::close();
        return ob_get_clean();
    }

    private static function terms_url() { $page = get_page_by_path('terms-conditions'); return $page ? get_permalink($page) : home_url('/'); }
    private static function privacy_url() { $page = get_page_by_path('privacy-policy-2'); return $page ? get_permalink($page) : home_url('/'); }

    private static function custom_field($field) {
        $id = (int) $field['id'];
        $type = $field['fieldtype'];
        $label = explode('|', $field['fieldname'], 2); $label = end($label);
        $required = !empty($field['required']) && $field['required'] !== '0';
        $values = isset($_POST['customfield']) && is_array($_POST['customfield']) ? wp_unslash($_POST['customfield']) : [];
        $value = isset($values[$id]) && is_string($values[$id]) && $type !== 'password' ? $values[$id] : '';
        ?>
        <div class="rm-auth-field<?php echo $type === 'textarea' ? ' rm-auth-full' : ''; ?>"><label for="rm_custom_<?php echo $id; ?>"><?php echo esc_html($label); ?> <?php echo $required ? '<span aria-hidden="true">*</span>' : '<small>(optional)</small>'; ?></label>
        <?php if ($type === 'dropdown'): ?><select name="customfield[<?php echo $id; ?>]" id="rm_custom_<?php echo $id; ?>" <?php echo $required ? 'required' : ''; ?>><option value="">Choose an option</option><?php foreach (explode(',', $field['fieldoptions']) as $option): $option = trim($option); ?><option value="<?php echo esc_attr($option); ?>" <?php selected($value, $option); ?>><?php echo esc_html($option); ?></option><?php endforeach; ?></select>
        <?php elseif ($type === 'textarea'): ?><textarea name="customfield[<?php echo $id; ?>]" id="rm_custom_<?php echo $id; ?>" maxlength="2000" <?php echo $required ? 'required' : ''; ?>><?php echo esc_textarea($value); ?></textarea>
        <?php else: ?><input type="<?php echo $type === 'tickbox' ? 'checkbox' : ($type === 'password' ? 'password' : ($type === 'link' ? 'url' : 'text')); ?>" name="customfield[<?php echo $id; ?>]" id="rm_custom_<?php echo $id; ?>" value="<?php echo esc_attr($type === 'tickbox' ? 'on' : $value); ?>" maxlength="255" <?php echo $required ? 'required' : ''; ?> <?php if ($type === 'tickbox') checked($value, 'on'); ?>><?php endif; ?>
        <?php if (!empty($field['description'])): ?><small><?php echo esc_html($field['description']); ?></small><?php endif; ?></div>
        <?php
    }

    public static function validate_registration($input, $schema) {
        if (!is_array($input) || is_wp_error($schema) || empty($schema['registration_enabled'])) return new WP_Error('registration_unavailable', 'Registration is temporarily unavailable. Please contact Rackmonk.');
        $data = [];
        $keys = ['firstname', 'lastname', 'email', 'phonenumber', 'companyname', 'tax_id', 'address1', 'address2', 'city', 'state', 'country', 'postcode', 'password', 'password_confirm', 'currency', 'securityqid', 'securityqans', 'terms', 'marketingoptin', 'rm_company_url'];
        foreach ($keys as $key) {
            if (isset($input[$key]) && !is_string($input[$key])) return new WP_Error('registration_invalid', 'Invalid form values. Please reload and try again.');
            $value = $input[$key] ?? '';
            if (strlen($value) > 1000) return new WP_Error('registration_invalid', 'A form value is too long.');
            $data[$key] = in_array($key, ['password', 'password_confirm', 'securityqans'], true) ? $value : sanitize_text_field($value);
        }
        if ($data['rm_company_url'] !== '') return new WP_Error('registration_invalid', 'Registration could not be processed.');
        if (!is_email($data['email'])) return new WP_Error('registration_email', 'Enter a valid email address.');
        $data['email'] = strtolower(sanitize_email($data['email']));
        $optional = $schema['optional_fields'] ?? [];
        foreach (['firstname' => 'First name', 'lastname' => 'Last name', 'address1' => 'Street address', 'city' => 'City', 'state' => 'State / region', 'postcode' => 'Postcode'] as $key => $label) {
            if (!in_array($key, $optional, true) && $data[$key] === '') return new WP_Error('registration_required', $label . ' is required.');
        }
        foreach (['firstname', 'lastname', 'companyname', 'tax_id', 'address1', 'address2', 'city', 'state', 'postcode'] as $key) if (strlen($data[$key]) > 255) return new WP_Error('registration_length', 'Keep profile fields within 255 characters.');
        if (!isset(WC()->countries->get_countries()[$data['country']])) return new WP_Error('registration_country', 'Choose a valid country.');
        $states = WC()->countries->get_states($data['country']);
        if ($data['state'] !== '' && is_array($states) && $states && !isset($states[$data['state']])) return new WP_Error('registration_state', 'Choose a valid state for the selected country.');
        if ($data['postcode'] !== '' && !WC_Validation::is_postcode($data['postcode'], $data['country'])) return new WP_Error('registration_postcode', 'Enter a valid postcode for the selected country.');
        if (!preg_match('/^[+0-9(). \-]{6,32}$/', $data['phonenumber']) || strlen(preg_replace('/\D/', '', $data['phonenumber'])) < 6) return new WP_Error('registration_phone', 'Enter your phone number, including country code.');
        if (strlen($data['password']) < 12 || strlen($data['password']) > 64) return new WP_Error('registration_password', 'Use a unique password between 12 and 64 characters.');
        if ($data['password'] !== trim($data['password'])) return new WP_Error('registration_password', 'Your password must not start or end with whitespace.');
        if ($data['password'] !== $data['password_confirm']) return new WP_Error('registration_password_match', 'The passwords do not match.');
        if ($data['terms'] !== '1') return new WP_Error('registration_terms', 'Accept the Terms and Conditions and Privacy Policy to continue.');
        $currency_ids = array_map('intval', array_column($schema['currencies'] ?? [], 'id'));
        if ($currency_ids && !in_array((int) $data['currency'], $currency_ids, true)) return new WP_Error('registration_currency', 'Choose an available billing currency.');
        if (!empty($schema['security_questions'])) {
            if (!in_array((int) $data['securityqid'], array_map('intval', array_column($schema['security_questions'], 'id')), true) || strlen(trim($data['securityqans'])) < 3 || strlen($data['securityqans']) > 255) return new WP_Error('registration_security', 'Choose a security question and enter an answer of at least three characters.');
        }
        $custom = $input['customfield'] ?? [];
        if (!is_array($custom)) return new WP_Error('registration_custom', 'Invalid additional information.');
        $data['customfields'] = [];
        $data['profile_customfields'] = [];
        foreach ($schema['custom_fields'] ?? [] as $field) {
            $id = (int) $field['id'];
            $value = $custom[$id] ?? '';
            if (!is_string($value) || strlen($value) > ($field['fieldtype'] === 'textarea' ? 2000 : 255)) return new WP_Error('registration_custom', 'Check your additional information.');
            $value = $field['fieldtype'] === 'password' ? $value : sanitize_textarea_field($value);
            if (!empty($field['required']) && $field['required'] !== '0' && trim($value) === '') return new WP_Error('registration_custom_required', 'Complete all required additional information.');
            if ($field['fieldtype'] === 'dropdown' && $value !== '' && !in_array($value, array_map('trim', explode(',', $field['fieldoptions'])), true)) return new WP_Error('registration_custom', 'Choose an available option for additional information.');
            if ($field['fieldtype'] === 'tickbox' && !in_array($value, ['', 'on'], true)) return new WP_Error('registration_custom', 'Invalid additional consent value.');
            if ($field['fieldtype'] === 'link' && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) return new WP_Error('registration_custom', 'Enter a valid website URL in additional information.');
            $data['customfields'][$id] = $value;
            if ($field['fieldtype'] !== 'password') $data['profile_customfields'][$id] = $value;
        }
        return $data;
    }

    private static function rate_key($context) {
        return 'rm_auth_' . $context . '_' . hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown', wp_salt('nonce'));
    }

    public function failed_login() {
        if (!isset($_POST['login'])) return;
        $key = self::rate_key('login');
        set_transient($key, (int) get_transient($key) + 1, 15 * MINUTE_IN_SECONDS);
    }

    public function login_limit($errors) {
        if ((int) get_transient(self::rate_key('login')) >= 10) $errors->add('rate_limit', 'Too many sign-in attempts. Please wait 15 minutes before trying again.');
        return $errors;
    }

    /**
     * Storefront sign-in (/my-account/) is checked against WHMCS, not WordPress.
     * WordPress passwords are ignored here, so wp-admin accounts cannot sign in to the
     * Client Area; a valid WHMCS user is linked to (or given) a website customer account.
     */
    public function whmcs_login($user, $username, $password) {
        if (!defined('RACKMONK_AUTH_CONTEXT') || RACKMONK_AUTH_CONTEXT !== 'client') return $user;
        if (!is_string($username) || !is_string($password) || $username === '' || $password === '') return $user;
        $login = trim($username);
        if (!is_email($login)) {
            // Customers sign in with their WHMCS email; allow a website username only to look that email up.
            $byLogin = get_user_by('login', $login);
            if (!$byLogin) return new WP_Error('invalid_email', 'Sign in with the email address of your Rackmonk client account.');
            $login = get_user_meta($byLogin->ID, self::LOGIN_EMAIL_META, true) ?: $byLogin->user_email;
        }
        $email = strtolower($login);
        $check = Rackmonk_WHMCS_Client_API::validate_login($email, $password);
        if (is_wp_error($check)) {
            return new WP_Error($check->get_error_code() === 'whmcs_invalid_login' ? 'incorrect_password' : 'whmcs_unavailable', esc_html($check->get_error_message()));
        }
        if ($check['two_factor']) {
            $url = Rackmonk_Plans_Manager::get_instance()->get_whmcs_system_url();
            return new WP_Error('whmcs_two_factor', 'Your account uses two-factor authentication. Please <a href="' . esc_url(trailingslashit((string) $url) . 'login.php') . '">sign in on the hosting client area</a>.');
        }
        $client = Rackmonk_WHMCS_Client_API::client_for_user((int) $check['user_id']);
        if (!$client) return new WP_Error('whmcs_no_client', 'This login is not linked to a Rackmonk client account. Please contact support.');
        if (in_array($client['status'], ['Closed'], true)) return new WP_Error('whmcs_closed', 'This client account is closed. Please contact Rackmonk support.');
        $wpUser = self::linked_customer((int) $check['user_id'], $client, $email);
        return $wpUser;
    }

    /** Find or create the website customer account that mirrors a WHMCS user. */
    private static function linked_customer(int $whmcsUserId, array $client, string $email) {
        $found = get_users(['meta_key' => '_rackmonk_whmcs_user_id', 'meta_value' => $whmcsUserId, 'number' => 2, 'fields' => 'all']);
        $user = count($found) === 1 ? $found[0] : null;
        if (!$user) {
            // Never attach a staff/admin WordPress account to a customer login: customer_by_email() skips them.
            $byEmail = self::customer_by_email($email);
            if ($byEmail) {
                $linkedTo = (int) get_user_meta($byEmail->ID, '_rackmonk_whmcs_user_id', true);
                if ($linkedTo && $linkedTo !== $whmcsUserId) return new WP_Error('whmcs_conflict', 'This email is linked to a different client account. Please contact Rackmonk support.');
                $user = $byEmail;
            } else {
                $suppress = static function () { return false; };
                add_filter('woocommerce_email_enabled_customer_new_account', $suppress);
                // The website password is random and unused: customers always sign in with WHMCS.
                // If a staff account owns this email, the client's website record uses an internal alias.
                $aliased = self::staff_owns($email);
                $id = wc_create_new_customer($aliased ? self::alias_email($email) : $email, '', wp_generate_password(32, true, true), ['first_name' => $client['firstname'], 'last_name' => $client['lastname']]);
                remove_filter('woocommerce_email_enabled_customer_new_account', $suppress);
                if (is_wp_error($id)) return new WP_Error('whmcs_account', 'Your website account could not be prepared. Please contact Rackmonk support.');
                if ($aliased) { update_user_meta($id, self::LOGIN_EMAIL_META, strtolower($email)); update_user_meta($id, 'billing_email', $email); }
                update_user_meta($id, '_rackmonk_created_from_whmcs_login', gmdate('c'));
                $user = get_user_by('id', $id);
            }
        }
        update_user_meta($user->ID, '_rackmonk_whmcs_user_id', $whmcsUserId);
        update_user_meta($user->ID, '_rackmonk_whmcs_client_id', (int) $client['client_id']);
        update_user_meta($user->ID, '_rackmonk_whmcs_sync_status', 'active');
        update_user_meta($user->ID, '_rackmonk_whmcs_last_login', gmdate('c'));
        wp_cache_delete($user->ID, 'user_meta');
        return get_user_by('id', $user->ID);
    }

    /** Real (WHMCS) email of a client whose website record uses an internal alias email. */
    const LOGIN_EMAIL_META = '_rackmonk_whmcs_login_email';

    private static function is_staff($user) {
        return user_can($user, 'edit_posts') || array_diff((array) $user->roles, ['customer', 'subscriber']);
    }

    private static function staff_owns($email) {
        $user = get_user_by('email', $email);
        return $user && self::is_staff($user);
    }

    private static function alias_email($email) {
        return 'client-' . substr(hash('sha256', strtolower($email)), 0, 20) . '@clients.invalid';
    }

    /** The website customer record for a client email (never a staff/admin account). */
    private static function customer_by_email($email) {
        $aliased = get_users(['meta_key' => self::LOGIN_EMAIL_META, 'meta_value' => strtolower($email), 'number' => 2]);
        if (count($aliased) === 1) return $aliased[0];
        $user = get_user_by('email', $email);
        return $user && !self::is_staff($user) ? $user : null;
    }

    /** Website emails for an aliased client go to the client's real email. */
    /**
     * Safety net for every email the website sends (WooCommerce, WordPress, other plugins): an
     * internal alias address (…@clients.invalid) is never a real mailbox, so swap it for the
     * client's real email before sending, in the recipients and in the message text.
     */
    public function mail_to_real_email($args) {
        $to = $args['to'] ?? '';
        $list = is_array($to) ? $to : explode(',', (string) $to);
        $changed = false;
        foreach ($list as $i => $entry) {
            if (!preg_match('/[A-Z0-9._%+-]+@clients\.invalid/i', (string) $entry, $match)) continue;
            $user = get_user_by('email', $match[0]);
            $real = $user ? get_user_meta($user->ID, self::LOGIN_EMAIL_META, true) : '';
            if (!is_string($real) || !is_email($real)) continue;
            $list[$i] = str_ireplace($match[0], $real, (string) $entry);
            foreach (['subject', 'message'] as $part) if (isset($args[$part]) && is_string($args[$part])) $args[$part] = str_ireplace($match[0], $real, $args[$part]);
            $changed = true;
        }
        if ($changed) $args['to'] = is_array($to) ? $list : implode(',', $list);
        return $args;
    }

    /** Account pages never show the internal alias address: print the client's real email instead. */
    public function show_real_email() {
        if (!is_user_logged_in() || !self::is_access_page()) return;
        $user = wp_get_current_user();
        $real = get_user_meta($user->ID, self::LOGIN_EMAIL_META, true);
        if (!is_string($real) || !is_email($real) || strcasecmp($real, $user->user_email) === 0) return;
        $alias = $user->user_email;
        ob_start(static function ($html) use ($alias, $real) {
            // Keep the hidden form value WooCommerce compares against the stored address.
            $keep = 'name="account_email" value="' . esc_attr($alias) . '"';
            $token = 'name="account_email" value="rm-alias-placeholder"';
            return str_replace($token, $keep, str_ireplace($alias, esc_html($real), str_replace($keep, $token, $html)));
        });
    }

    public function real_recipient($recipient, $object = null) {
        if ($object instanceof WP_User) {
            $real = get_user_meta($object->ID, self::LOGIN_EMAIL_META, true);
            if (is_string($real) && is_email($real)) return $real;
        }
        return $recipient;
    }

    private static function client_context() {
        return defined('RACKMONK_AUTH_CONTEXT') && RACKMONK_AUTH_CONTEXT === 'client';
    }

    /**
     * Website "Forgot password": a WHMCS client who has never signed in to the website has no
     * website account yet, so link/create it first; WooCommerce then emails the reset link.
     */
    public function prepare_reset_account() {
        if (!self::client_context() || !isset($_POST['wc_reset_password'], $_POST['user_login']) || !is_string($_POST['user_login'])) return;
        if (!wp_verify_nonce(self::posted('woocommerce-lost-password-nonce'), 'lost_password')) return;
        $email = strtolower(trim(self::posted('user_login')));
        if (!is_email($email)) return;
        $existing = self::customer_by_email($email);
        if (!$existing || !(int) get_user_meta($existing->ID, '_rackmonk_whmcs_user_id', true)) {
            $whmcsUserId = Rackmonk_WHMCS_Client_API::user_id_by_email($email);
            if (!$whmcsUserId) return;
            $client = Rackmonk_WHMCS_Client_API::client_for_user($whmcsUserId);
            if (!$client || $client['status'] === 'Closed') return;
            $existing = self::linked_customer($whmcsUserId, $client, $email);
            if (!$existing instanceof WP_User) return;
        }
        // The email belongs to a staff account too: point WooCommerce at the client's own record.
        if (strcasecmp($existing->user_email, $email) !== 0) $_POST['user_login'] = wp_slash($existing->user_login);
    }

    /** Only website accounts linked to a WHMCS client can reset from the storefront. */
    public function client_reset_only($errors, $user) {
        if (!self::client_context() || !($errors instanceof WP_Error) || !isset($_POST['wc_reset_password'])) return;
        if (!$user instanceof WP_User) return; // WooCommerce reports unknown accounts itself
        $linked = (int) get_user_meta($user->ID, '_rackmonk_whmcs_user_id', true) > 0;
        if (!$linked || user_can($user, 'edit_posts')) $errors->add('invalid_email', 'Enter the email address of your Rackmonk client account.');
    }

    /** The reset form saves the new password in WHMCS, because WHMCS is where customers sign in. */
    public function save_whmcs_password($errors, $user) {
        if (!self::client_context() || !($errors instanceof WP_Error) || !$user instanceof WP_User) return;
        if (!isset($_POST['wc_reset_password'], $_POST['password_1']) || !is_string($_POST['password_1'])) return;
        if ($errors->has_errors() || wc_notice_count('error') > 0) return; // other checks failed: change nothing
        $whmcsUserId = (int) get_user_meta($user->ID, '_rackmonk_whmcs_user_id', true);
        if (!$whmcsUserId || user_can($user, 'edit_posts')) { $errors->add('not_client', 'This reset link is not for a Rackmonk client account.'); return; }
        $saved = Rackmonk_WHMCS_Client_API::set_user_password($whmcsUserId, wp_unslash($_POST['password_1']));
        if (is_wp_error($saved)) $errors->add('whmcs_password', $saved->get_error_message());
    }

    public function pending_login($user, $username, $password) {
        if ($user instanceof WP_User) {
            if (defined('RACKMONK_AUTH_CONTEXT') && RACKMONK_AUTH_CONTEXT === 'client' && user_can($user, 'manage_options')) return new WP_Error('administrator_login', 'Use the WordPress admin sign-in for administrator accounts. This Client Area is for customers.');
            $status = get_user_meta($user->ID, '_rackmonk_whmcs_sync_status', true);
            $managed = get_user_meta($user->ID, '_rackmonk_registration_managed', true) === '1';
            if (($managed || $status !== '') && ($status !== 'active' || !(int) get_user_meta($user->ID, '_rackmonk_whmcs_client_id', true) || !(int) get_user_meta($user->ID, '_rackmonk_whmcs_user_id', true))) return new WP_Error('account_pending', 'Your account setup is awaiting confirmation. Please contact Rackmonk support.');
        }
        return $user;
    }

    private static function transaction() {
        global $wpdb;
        foreach ([$wpdb->users, $wpdb->usermeta] as $table) {
            $engine = $wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table));
            if (strcasecmp((string) $engine, 'InnoDB') !== 0) throw new RuntimeException('Transactional account storage is required.');
        }
        if ($wpdb->query('START TRANSACTION') === false) throw new RuntimeException('Account storage is unavailable.');
    }

    private static function rollback($id = 0) {
        global $wpdb;
        $wpdb->query('ROLLBACK');
        if ($id) { clean_user_cache($id); wp_cache_delete($id, 'user_meta'); }
    }

    private static function save_profile($id, $data) {
        $customer = new WC_Customer($id);
        $profile = ['first_name' => 'firstname', 'last_name' => 'lastname', 'company' => 'companyname', 'address_1' => 'address1', 'address_2' => 'address2', 'city' => 'city', 'state' => 'state', 'postcode' => 'postcode', 'country' => 'country', 'email' => 'email', 'phone' => 'phonenumber'];
        foreach ($profile as $property => $source) $customer->{'set_billing_' . $property}($data[$source]);
        $customer->set_first_name($data['firstname']); $customer->set_last_name($data['lastname']);
        $customer->save();
        update_user_meta($id, 'billing_tax_id', wp_slash($data['tax_id']));
        update_user_meta($id, '_rackmonk_whmcs_client_fields', wp_slash($data['profile_customfields']));
        update_user_meta($id, '_rackmonk_whmcs_currency_id', (int) $data['currency']);
        update_user_meta($id, '_rackmonk_terms_accepted', ['at' => gmdate('c'), 'terms_url' => self::terms_url(), 'privacy_url' => self::privacy_url()]);
        $snapshot = array_intersect_key($data, array_flip(array_merge(array_values($profile), ['tax_id', 'currency', 'profile_customfields'])));
        update_user_meta($id, '_rackmonk_registration_profile', wp_slash($snapshot));
        wp_cache_delete($id, 'user_meta');
        if (get_user_meta($id, '_rackmonk_registration_profile', true) !== $snapshot) throw new RuntimeException('Account profile could not be saved.');
    }

    public static function activate($id, $mapping) {
        global $wpdb;
        if (empty($mapping['client_id']) || empty($mapping['user_id'])) return new WP_Error('registration_pending', 'Your account connection needs verification.');
        try {
            self::transaction();
            update_user_meta($id, '_rackmonk_whmcs_client_id', (int) $mapping['client_id']);
            update_user_meta($id, '_rackmonk_whmcs_user_id', (int) $mapping['user_id']);
            update_user_meta($id, '_rackmonk_whmcs_sync_status', 'active');
            wp_cache_delete($id, 'user_meta');
            if (get_user_meta($id, '_rackmonk_whmcs_sync_status', true) !== 'active' || (int) get_user_meta($id, '_rackmonk_whmcs_client_id', true) !== (int) $mapping['client_id'] || (int) get_user_meta($id, '_rackmonk_whmcs_user_id', true) !== (int) $mapping['user_id']) throw new RuntimeException();
            if ($wpdb->query('COMMIT') === false) throw new RuntimeException();
            delete_user_meta($id, '_rackmonk_whmcs_sync_error');
            return (int) $id;
        } catch (Throwable $error) {
            self::rollback($id);
            return new WP_Error('registration_pending', 'Your account connection needs verification. Please contact Rackmonk support.');
        }
    }

    /**
     * WHMCS (international phone format on) accepts "+CC.NUMBER" or a national number, but rejects
     * "+91 98765 43210" and "+919876543210". Split the country code using the selected country.
     */
    public static function whmcs_phone($phone, $country) {
        $plus = strpos(ltrim($phone), '+') === 0;
        $digits = preg_replace('/\D/', '', $phone);
        $code = function_exists('WC') ? preg_replace('/\D/', '', (string) WC()->countries->get_country_calling_code($country)) : '';
        if ($code === '') return $plus ? '+' . $digits : $digits;
        if ($plus || (strpos($digits, $code) === 0 && strlen($digits) > strlen($code) + 6)) {
            if (strpos($digits, $code) !== 0) return '+' . $digits; // a different country's number: leave it to WHMCS
            $digits = substr($digits, strlen($code));
        }
        return '+' . $code . '.' . ltrim($digits, '0');
    }

    public function register($input) {
        $schema = Rackmonk_WHMCS_Client_API::schema();
        $data = self::validate_registration($input, $schema);
        if (is_wp_error($data)) return $data;
        $key = self::rate_key('register');
        if ((int) get_transient($key) >= 5) return new WP_Error('registration_rate', 'Too many registration attempts. Please try again in one hour.');
        set_transient($key, (int) get_transient($key) + 1, HOUR_IN_SECONDS);
        $lock = 'rm_register_lock_' . hash('sha256', $data['email']);
        $stamp = (string) (time() + 300);
        if (!add_option($lock, $stamp, '', false)) {
            $old = get_option($lock);
            if (is_numeric($old) && (int) $old < time()) {
                global $wpdb;
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $lock, (string) $old));
                wp_cache_delete($lock, 'options');
            }
            if (!add_option($lock, $stamp, '', false)) return new WP_Error('registration_busy', 'This registration is already being processed. Please wait before trying again.');
        }
        try {
            $existing = self::customer_by_email($data['email']);
            // A staff/admin website account may already own this email. The client still registers in
            // WHMCS with the real email; only the website-side customer record uses an internal alias.
            $aliased = !$existing && self::staff_owns($data['email']);
            $wpEmail = $aliased ? self::alias_email($data['email']) : $data['email'];
            $api = new Rackmonk_WHMCS_Client_API();
            if ($existing) {
                if (get_user_meta($existing->ID, '_rackmonk_whmcs_sync_status', true) !== 'pending' || !wp_check_password(wp_slash($data['password']), $existing->user_pass, $existing->ID)) return new WP_Error('registration_exists', 'An account already uses these details. Sign in or use password recovery.');
                $reference = get_user_meta($existing->ID, '_rackmonk_whmcs_registration_ref', true);
                $result = $api->recover_registration($reference, $data['email']);
                if (is_wp_error($result)) return new WP_Error('registration_pending', 'Your account setup needs verification. Contact Rackmonk support before trying again.');
                $id = $existing->ID;
            } else {
                global $wpdb;
                $reference = wp_generate_uuid4();
                $id = 0;
                $suppress = static function () { return false; };
                try {
                    self::transaction();
                    add_filter('woocommerce_email_enabled_customer_new_account', $suppress);
                    // Native WC forms authenticate/reset the slashed POST representation.
                    $meta = ['_rackmonk_registration_managed' => '1', '_rackmonk_whmcs_sync_status' => 'pending', '_rackmonk_whmcs_registration_ref' => $reference];
                    if ($aliased) $meta[self::LOGIN_EMAIL_META] = $data['email'];
                    $id = wc_create_new_customer($wpEmail, '', wp_slash($data['password']), ['first_name' => $data['firstname'], 'last_name' => $data['lastname'], 'meta_input' => $meta]);
                    if (is_wp_error($id)) { self::rollback(); return $id; }
                    wp_cache_delete($id, 'user_meta');
                    if (get_user_meta($id, '_rackmonk_registration_managed', true) !== '1' || get_user_meta($id, '_rackmonk_whmcs_sync_status', true) !== 'pending' || get_user_meta($id, '_rackmonk_whmcs_registration_ref', true) !== $reference) throw new RuntimeException();
                    self::save_profile($id, $data);
                    if ($wpdb->query('COMMIT') === false) throw new RuntimeException();
                } catch (Throwable $error) {
                    self::rollback(is_int($id) ? $id : 0);
                    return new WP_Error('registration_storage', 'Account details could not be saved. Please try again later.');
                } finally { remove_filter('woocommerce_email_enabled_customer_new_account', $suppress); }
                $payload = array_intersect_key($data, array_flip(['firstname', 'lastname', 'email', 'phonenumber', 'companyname', 'tax_id', 'address1', 'address2', 'city', 'state', 'country', 'postcode', 'currency', 'securityqid', 'securityqans', 'customfields']));
                $payload['phonenumber'] = self::whmcs_phone($data['phonenumber'], $data['country']);
                $payload['password2'] = $data['password'];
                $payload['marketingoptin'] = $data['marketingoptin'] === '1';
                $payload['clientip'] = $_SERVER['REMOTE_ADDR'] ?? '';
                $result = $api->register_client($payload, $reference);
                unset($payload);
                if (is_wp_error($result)) {
                    if (in_array($result->get_error_code(), ['whmcs_validation', 'whmcs_unavailable', 'whmcs_schema'], true)) {
                        require_once ABSPATH . 'wp-admin/includes/user.php';
                        wp_delete_user($id);
                        return new WP_Error('registration_rejected', $result->get_error_message());
                    }
                    update_user_meta($id, '_rackmonk_whmcs_sync_error', $result->get_error_code());
                    return new WP_Error('registration_pending', 'Your account setup is being verified. Please contact Rackmonk support before retrying.');
                }
            }
            // Recovery retains the durable original profile; retries cannot replace it.
            return self::activate($id, $result);
        } finally {
            if (get_option($lock) === $stamp) delete_option($lock);
        }
    }

    /**
     * Single logout: WHMCS sends the browser here (signed, short-lived) after a user logs out
     * there, so the matching website session ends too.
     */
    public function whmcs_logout() {
        if (!isset($_GET['rm_whmcs_logout'])) return;
        nocache_headers();
        $uid = isset($_GET['uid']) && is_string($_GET['uid']) ? (int) $_GET['uid'] : 0;
        $time = isset($_GET['ts']) && is_string($_GET['ts']) ? (int) $_GET['ts'] : 0;
        $sig = isset($_GET['sig']) && is_string($_GET['sig']) ? $_GET['sig'] : '';
        if (self::client_context() && $uid > 0 && abs(time() - $time) <= 300 && is_user_logged_in()
            && (int) get_user_meta(get_current_user_id(), '_rackmonk_whmcs_user_id', true) === $uid) {
            $key = Rackmonk_WHMCS_Client_API::logout_bridge_key();
            if ($key !== '' && hash_equals(hash_hmac('sha256', $uid . '|' . $time, $key), $sig)) wp_logout();
        }
        wp_safe_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }

    public function handle_post() {
        if (PHP_SAPI !== 'cli' && defined('RACKMONK_AUTH_CONTEXT') && RACKMONK_AUTH_CONTEXT === 'admin' && (isset($_POST['rm_auth_action']) || isset($_POST['login']) || isset($_POST['wc_reset_password']))) {
            unset($_POST['login'], $_POST['wc_reset_password']);
            wc_add_notice('Open the Client Area without editor or preview parameters before signing in.', 'error');
            return;
        }
        $this->guard_native_posts();
        $action = self::posted('rm_auth_action');
        if ($action === 'register') {
            nocache_headers();
            if (is_user_logged_in()) return;
            if (!wp_verify_nonce(self::posted('rackmonk-register-nonce'), 'rackmonk-register')) { wc_add_notice('Your session expired. Reload the page and try again.', 'error'); return; }
            $result = $this->register(wp_unslash($_POST));
            if (is_wp_error($result)) { wc_add_notice(esc_html($result->get_error_message()), 'error'); return; }
            wp_cache_delete($result, 'user_meta');
            if (is_wp_error($this->pending_login(get_user_by('id', $result), '', ''))) { wc_add_notice('Account confirmation is pending. Contact Rackmonk support.', 'error'); return; }
            wc_set_customer_auth_cookie($result);
            // New clients go straight into their WHMCS client area, already signed in (single sign-on).
            $sso = (new Rackmonk_WHMCS_Client_API())->sso((int) $result);
            if (!is_wp_error($sso) && !empty($sso['redirect_url'])) {
                header('Referrer-Policy: no-referrer');
                wp_redirect($sso['redirect_url']);
                exit;
            }
            wp_safe_redirect(wc_get_page_permalink('myaccount')); // WHMCS unreachable: the website dashboard has the workspace button
            exit;
        }
        if ($action === 'sso') {
            nocache_headers();
            if (!is_user_logged_in() || !wp_verify_nonce(self::posted('rackmonk-sso-nonce'), 'rackmonk-sso')) { wc_add_notice('Sign in again before opening your hosting workspace.', 'error'); return; }
            $result = (new Rackmonk_WHMCS_Client_API())->sso(get_current_user_id());
            if (is_wp_error($result)) { wc_add_notice(esc_html($result->get_error_message()), 'error'); return; }
            header('Referrer-Policy: no-referrer');
            wp_redirect($result['redirect_url']);
            exit;
        }
    }

    private function guard_native_posts() {
        if (isset($_POST['login'])) {
            foreach (['username', 'password', 'woocommerce-login-nonce'] as $field) {
                if (!isset($_POST[$field]) || !is_string($_POST[$field])) { unset($_POST['login']); wc_add_notice('Invalid sign-in request. Reload and try again.', 'error'); return; }
            }
            if (!wp_verify_nonce(self::posted('woocommerce-login-nonce'), 'woocommerce-login')) { unset($_POST['login']); wc_add_notice('Your session expired. Reload and try again.', 'error'); }
        }
        if (isset($_POST['wc_reset_password'])) {
            $forgot = isset($_POST['user_login']);
            foreach ($forgot ? ['user_login', 'woocommerce-lost-password-nonce'] : ['password_1', 'password_2', 'reset_key', 'reset_login', 'woocommerce-reset-password-nonce'] as $field) {
                if (!isset($_POST[$field]) || !is_string($_POST[$field])) { unset($_POST['wc_reset_password']); wc_add_notice('Invalid password-reset request. Reload and try again.', 'error'); return; }
            }
            $valid = wp_verify_nonce(self::posted($forgot ? 'woocommerce-lost-password-nonce' : 'woocommerce-reset-password-nonce'), $forgot ? 'lost_password' : 'reset_password');
            if (!$valid) { unset($_POST['wc_reset_password']); wc_add_notice('Your session expired. Reload and try again.', 'error'); return; }
            if ($forgot) {
                $key = self::rate_key('forgot');
                if ((int) get_transient($key) >= 5) { unset($_POST['wc_reset_password']); wc_add_notice('Too many reset requests. Please wait 15 minutes before trying again.', 'error'); return; }
                set_transient($key, (int) get_transient($key) + 1, 15 * MINUTE_IN_SECONDS);
            }
        }
    }

    public function reset_policy($errors, $user) {
        if (!isset($_POST['wc_reset_password'], $_POST['password_1']) || !is_string($_POST['password_1'])) return;
        $length = strlen(wp_unslash($_POST['password_1']));
        if ($length < 12 || $length > 64) $errors->add('password_length', 'Use a unique password between 12 and 64 characters.');
    }

    /** The email a client signs in with (the WHMCS email, even when the website record uses an alias). */
    public static function login_email($user) {
        $real = get_user_meta($user->ID, self::LOGIN_EMAIL_META, true);
        return is_string($real) && is_email($real) ? $real : $user->user_email;
    }

    /** Greeting banner above every My Account page. */
    public function account_header() {
        $user = wp_get_current_user();
        $name = trim($user->first_name . ' ' . $user->last_name) ?: $user->display_name;
        $initials = strtoupper(mb_substr($user->first_name ?: $name, 0, 1) . mb_substr($user->last_name, 0, 1));
        echo '<header class="rm-acct-head"><span class="rm-acct-avatar" aria-hidden="true">' . esc_html($initials) . '</span>'
            . '<div class="rm-acct-head-text"><span class="rm-acct-kicker">My account</span><h1>Hello, ' . esc_html($user->first_name ?: $name) . '</h1><p>' . esc_html(self::login_email($user)) . '</p></div></header>';
    }

    public function account_menu($items) {
        unset($items['downloads']); // hosting plans have no downloadable files
        if (isset($items['edit-address'])) $items['edit-address'] = 'Billing address';
        return $items;
    }

    public function dashboard() {
        $id = get_current_user_id();
        echo '<section class="rm-acct-card rm-acct-hero rm-auth-workspace"><div class="rm-acct-hero-text"><span class="rm-acct-kicker">Hosting</span><h2>Your hosting workspace</h2>';
        if (get_user_meta($id, '_rackmonk_whmcs_sync_status', true) === 'active') {
            echo '<p>Manage your services, domains, invoices and support tickets. You are signed in automatically.</p></div><form method="post"><input type="hidden" name="rm_auth_action" value="sso">';
            wp_nonce_field('rackmonk-sso', 'rackmonk-sso-nonce');
            echo '<button class="rm-acct-button rm-acct-button--light" type="submit">' . esc_html(self::copy('account_hosting_button')) . ' <span aria-hidden="true">&rarr;</span></button></form>';
        } else echo '<p>This website account has no verified WHMCS connection. Contact Rackmonk to arrange secure account linking.</p></div>';
        echo '</section>';
    }

    public function admin_menu() {
        add_submenu_page('rackmonk-site-content', 'Client Accounts', 'Client Accounts', 'manage_options', 'rackmonk-client-accounts', [$this, 'admin_accounts']);
    }

    public function admin_accounts() {
        if (!current_user_can('manage_options')) return;
        $users = get_users(['meta_key' => '_rackmonk_registration_managed', 'meta_value' => '1', 'number' => 100, 'orderby' => 'registered', 'order' => 'DESC']);
        echo '<div class="wrap"><h1>Client Accounts</h1><p>Registration creates a website customer and a WHMCS client. Only verified owner mappings receive hosting access. A pending account stays blocked until its exact registration reference is confirmed; matching emails alone are never linked.</p><p>Showing the latest 100 connected registrations. Manage website profiles under Users; manage hosting profiles in WHMCS. Website password recovery does not change the direct WHMCS password. If WHMCS two-factor authentication is enabled, use the direct hosting login rather than website SSO.</p>';
        if (isset($_GET['verified'])) echo '<div class="notice notice-success"><p>Registration reference verified and account activated.</p></div>';
        echo '<table class="widefat striped"><thead><tr><th>Website customer</th><th>Status</th><th>WHMCS client / user</th><th>Action</th></tr></thead><tbody>';
        foreach ($users as $user) {
            $status = get_user_meta($user->ID, '_rackmonk_whmcs_sync_status', true);
            echo '<tr><td><a href="' . esc_url(get_edit_user_link($user->ID)) . '">' . esc_html($user->user_email) . '</a></td><td>' . esc_html($status ?: 'incomplete') . '</td><td>' . (int) get_user_meta($user->ID, '_rackmonk_whmcs_client_id', true) . ' / ' . (int) get_user_meta($user->ID, '_rackmonk_whmcs_user_id', true) . '</td><td>';
            if ($status === 'pending') {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="rackmonk_verify_account"><input type="hidden" name="user_id" value="' . (int) $user->ID . '">';
                wp_nonce_field('rackmonk-verify-' . $user->ID);
                echo '<button class="button" type="submit">Verify pending registration</button></form>';
            } else echo 'Verified';
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function verify_account() {
        if (!current_user_can('manage_options')) wp_die('You cannot verify client accounts.', '', ['response' => 403]);
        $id = isset($_POST['user_id']) && is_scalar($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        check_admin_referer('rackmonk-verify-' . $id);
        $user = get_user_by('id', $id);
        if (!$user || get_user_meta($id, '_rackmonk_whmcs_sync_status', true) !== 'pending' || !is_array(get_user_meta($id, '_rackmonk_registration_profile', true))) wp_die('No recoverable pending registration found.');
        $result = Rackmonk_WHMCS_Client_API::recover_registration(get_user_meta($id, '_rackmonk_whmcs_registration_ref', true), $user->user_email);
        if (is_wp_error($result)) wp_die('No unique WHMCS owner was confirmed for this registration reference. The account remains blocked.');
        $result = self::activate($id, $result);
        if (is_wp_error($result)) wp_die(esc_html($result->get_error_message()));
        wp_safe_redirect(admin_url('admin.php?page=rackmonk-client-accounts&verified=1'));
        exit;
    }
}
