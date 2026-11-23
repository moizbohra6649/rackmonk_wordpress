<?php
/** Dedicated disposable WP identities; never signs out an existing administrator. */
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 4) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
$checks = 0;
function session_check($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++; echo "PASS: $message\n";
}
function session_input($html, $name) {
    $dom = new DOMDocument(); @$dom->loadHTML($html);
    $fields = (new DOMXPath($dom))->query('//input[@name="' . $name . '"]');
    return $fields->length ? $fields->item(0)->getAttribute('value') : '';
}
function session_cookies(array $original, $response) {
    $by_name = [];
    foreach ($original as $cookie) $by_name[$cookie->name] = $cookie;
    foreach (wp_remote_retrieve_cookies($response) as $cookie) {
        if ($cookie->value === '' || ($cookie->expires && $cookie->expires < time())) unset($by_name[$cookie->name]);
        else $by_name[$cookie->name] = $cookie;
    }
    return array_values($by_name);
}
$created = []; $failure = null;
$mail_sink = static function () { return true; };
add_filter('pre_wp_mail', $mail_sink);
try {
    $site = home_url('/');
    foreach ([
        ['/my-account/', '/index.php', [], [], false],
        ['/register/', '/index.php', [], [], false],
        ['/checkout/', '/index.php', [], [], false],
        ['/wp-admin/', '/wp-admin/index.php', [], [], true],
        ['/wp-login.php', '/wp-login.php', [], [], true],
        ['/wp-admin/profile.php', '/wp-admin/profile.php', ['rest_route' => '/rackmonk/v1/cart/quote'], [], true],
        ['/wp-admin/admin-ajax.php', '/wp-admin/admin-ajax.php', [], ['action' => 'elementor_ajax'], true],
        ['/wp-admin/admin-ajax.php', '/wp-admin/admin-ajax.php', [], ['action' => 'rackmonk_get_plans'], false],
        ['/wp-json/wp/v2/pages', '/index.php', [], [], true],
        ['/wp-json/elementor-one/v1/settings', '/index.php', [], [], true],
        ['/wp-json/elementor-mcp-composer/v1.0.22/mcp-settings', '/index.php', [], [], true],
        ['/wp-json/wp-site-health/v1/tests/page-cache', '/index.php', [], [], true],
        ['/wp-json/wc/store/v1/cart', '/index.php', [], [], false],
        ['/wp-json/rackmonk/v1/cart/quote', '/index.php', [], [], false],
        ['/', '/index.php', ['elementor-preview' => '37'], [], true],
        ['/', '/index.php', ['preview' => 'true'], [], true],
        ['/', '/index.php', ['wp_customize' => 'on'], [], true],
    ] as $case) {
        [$path, $script, $get, $post, $expected] = $case;
        $base = rtrim((string) wp_parse_url($site, PHP_URL_PATH), '/');
        session_check(Rackmonk_Auth_Context::administrative(['REQUEST_URI' => $base . $path, 'SCRIPT_NAME' => $base . $script], $get, $post, $site) === $expected, 'Correct session context: ' . $path . ($get ? ' preview/query' : ''));
    }
    $suffix = bin2hex(random_bytes(5));
    $password = 'Session-fixture-Only!93452';
    $admin_name = 'rm-session-admin-' . $suffix;
    $client_name = 'rm-session-client-' . $suffix;
    foreach ([[$admin_name, 'administrator'], [$client_name, 'customer']] as [$name, $role]) {
        $id = wp_insert_user(['user_login' => $name, 'user_email' => $name . '@example.test', 'user_pass' => $password, 'role' => $role]);
        session_check(!is_wp_error($id), 'Temporary ' . $role . ' fixture created');
        $created[$role] = $id;
    }
    $expiry = time() + HOUR_IN_SECONDS;
    $token = WP_Session_Tokens::get_instance($created['administrator'])->create($expiry);
    $admin_auth = wp_generate_auth_cookie($created['administrator'], $expiry, 'auth', $token);
    $admin_logged = wp_generate_auth_cookie($created['administrator'], $expiry, 'logged_in', $token);
    $jar = [new WP_Http_Cookie(['name' => AUTH_COOKIE, 'value' => $admin_auth]), new WP_Http_Cookie(['name' => LOGGED_IN_COOKIE, 'value' => $admin_logged])];
    $profile_url = admin_url('profile.php');
    $profile = wp_remote_get($profile_url, ['timeout' => 30, 'cookies' => $jar]);
    session_check(!is_wp_error($profile) && session_input(wp_remote_retrieve_body($profile), 'user_login') === $admin_name, 'Native administrator session works');
    $account_url = wc_get_page_permalink('myaccount');
    $front = wp_remote_get($account_url, ['timeout' => 30, 'cookies' => $jar]);
    session_check(strpos(wp_remote_retrieve_body($front), 'data-auth-mode="login"') !== false, 'Admin cookie does not sign the storefront in as an administrator');
    $login = wp_remote_post($account_url, ['timeout' => 30, 'redirection' => 0, 'cookies' => $jar, 'body' => ['login' => 'Sign in', 'username' => $client_name, 'password' => $password, 'woocommerce-login-nonce' => session_input(wp_remote_retrieve_body($front), 'woocommerce-login-nonce'), 'redirect' => $account_url]]);
    session_check(!is_wp_error($login) && wp_remote_retrieve_response_code($login) === 302, 'Customer login succeeds with administrator cookies present');
    $set_cookie = implode("\n", (array) wp_remote_retrieve_header($login, 'set-cookie'));
    session_check(strpos($set_cookie, AUTH_COOKIE . '=') === false && strpos($set_cookie, LOGGED_IN_COOKIE . '=') === false && strpos($set_cookie, SECURE_AUTH_COOKIE . '=') === false && strpos($set_cookie, 'WHMCS') === false, 'Customer login does not issue native admin/WHMCS cookies');
    session_check(strpos($set_cookie, 'rackmonk_client_logged_in_') !== false, 'Customer authentication uses its dedicated cookie namespace');
    $jar = session_cookies($jar, $login);
    $account = wp_remote_get($account_url, ['timeout' => 30, 'cookies' => $jar]);
    $account_html = wp_remote_retrieve_body($account);
    session_check(strpos($account_html, $client_name) !== false && strpos($account_html, 'data-auth-mode="login"') === false, 'Customer dashboard uses customer identity');
    $profile = wp_remote_get($profile_url, ['timeout' => 30, 'cookies' => $jar]);
    session_check(session_input(wp_remote_retrieve_body($profile), 'user_login') === $admin_name, 'wp-admin retains administrator identity after customer login');
    $dom = new DOMDocument(); @$dom->loadHTML($account_html);
    $logout = '';
    foreach ((new DOMXPath($dom))->query('//a[contains(@href,"customer-logout")]') as $link) { $logout = $link->getAttribute('href'); break; }
    session_check($logout !== '', 'Customer logout stays on the account endpoint');
    $logout_response = wp_remote_get($logout, ['timeout' => 30, 'redirection' => 0, 'cookies' => $jar]);
    $jar = session_cookies($jar, $logout_response);
    $profile = wp_remote_get($profile_url, ['timeout' => 30, 'cookies' => $jar]);
    session_check(session_input(wp_remote_retrieve_body($profile), 'user_login') === $admin_name, 'Customer logout does not log wp-admin out');
    $front = wp_remote_get($account_url, ['timeout' => 30, 'cookies' => $jar]);
    session_check(strpos(wp_remote_retrieve_body($front), 'data-auth-mode="login"') !== false, 'Customer logout returns to the independent login screen');
    $whmcs = wp_remote_get(Rackmonk_Plans_Manager::get_instance()->get_whmcs_system_url() . 'clientarea.php', ['timeout' => 30, 'cookies' => $jar]);
    session_check(!is_wp_error($whmcs), 'Native WHMCS login page remains reachable');
    $whmcs_set = implode("\n", (array) wp_remote_retrieve_header($whmcs, 'set-cookie'));
    session_check(strpos($whmcs_set, AUTH_COOKIE . '=') === false && strpos($whmcs_set, LOGGED_IN_COOKIE . '=') === false, 'WHMCS does not issue WordPress auth cookies');
    $profile = wp_remote_get($profile_url, ['timeout' => 30, 'cookies' => session_cookies($jar, $whmcs)]);
    session_check(session_input(wp_remote_retrieve_body($profile), 'user_login') === $admin_name, 'Opening WHMCS preserves the independent wp-admin session');
    $front = wp_remote_get($account_url, ['timeout' => 30, 'cookies' => $jar]);
    $login = wp_remote_post($account_url, ['timeout' => 30, 'redirection' => 0, 'cookies' => $jar, 'body' => ['login' => 'Sign in', 'username' => $client_name, 'password' => $password, 'woocommerce-login-nonce' => session_input(wp_remote_retrieve_body($front), 'woocommerce-login-nonce'), 'redirect' => $account_url]]);
    session_check(wp_remote_retrieve_response_code($login) === 302, 'Customer signs back in independently');
    $jar = session_cookies($jar, $login);
    $prior_user = get_current_user_id(); $prior_cookie = $_COOKIE[LOGGED_IN_COOKIE] ?? null;
    $_COOKIE[LOGGED_IN_COOKIE] = $admin_logged; wp_set_current_user($created['administrator']);
    $admin_logout = add_query_arg(['action' => 'logout', '_wpnonce' => wp_create_nonce('log-out')], site_url('wp-login.php'));
    wp_set_current_user($prior_user);
    if ($prior_cookie === null) unset($_COOKIE[LOGGED_IN_COOKIE]); else $_COOKIE[LOGGED_IN_COOKIE] = $prior_cookie;
    $response = wp_remote_get($admin_logout, ['timeout' => 30, 'redirection' => 0, 'cookies' => $jar]);
    session_check(wp_remote_retrieve_response_code($response) === 302, 'Disposable admin fixture can log out normally');
    $jar = session_cookies($jar, $response);
    $front = wp_remote_get($account_url, ['timeout' => 30, 'cookies' => $jar]);
    session_check(strpos(wp_remote_retrieve_body($front), $client_name) !== false && strpos(wp_remote_retrieve_body($front), 'data-auth-mode="login"') === false, 'wp-admin logout does not log the customer out');
} catch (Throwable $error) { $failure = $error->getMessage(); }
finally {
    remove_filter('pre_wp_mail', $mail_sink);
    foreach ($created as $id) wp_delete_user($id);
    echo "Temporary fixture identities removed. No existing administrator session was reset.\n";
}
if ($failure) { fwrite(STDERR, 'VERDICT: FAIL -- ' . $failure . "\n"); exit(1); }
echo "$checks checks passed.\nVERDICT: PASS\n";
