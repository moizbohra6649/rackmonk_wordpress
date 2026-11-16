<?php
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 4) . '/wp-load.php';
$checks = 0;
function client_check($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
    echo "PASS: $message\n";
}
try {
    $schema = Rackmonk_WHMCS_Client_API::schema();
    client_check(!is_wp_error($schema), 'Native WHMCS registration schema loads');
    echo 'Registration enabled: ' . (!empty($schema['registration_enabled']) ? 'yes' : 'no') . '; custom fields: ' . count($schema['custom_fields']) . '; security questions: ' . count($schema['security_questions']) . '; currencies: ' . count($schema['currencies']) . "\n";
    $valid = ['firstname' => 'Test', 'lastname' => 'Customer', 'email' => 'client-validation@example.test', 'phonenumber' => '+91 9876543210', 'companyname' => '', 'tax_id' => '', 'address1' => '203 Regency Palace', 'address2' => '', 'city' => 'Indore', 'state' => 'MP', 'country' => 'IN', 'postcode' => '452010', 'password' => 'Test-only-Secure!54321', 'password_confirm' => 'Test-only-Secure!54321', 'currency' => (string) $schema['currencies'][0]['id'], 'terms' => '1'];
    client_check(!is_wp_error(Rackmonk_Client_Area::validate_registration($valid, $schema)), 'Complete valid profile passes without creating accounts');
    foreach (['firstname', 'lastname', 'email', 'phonenumber', 'address1', 'city', 'state', 'country', 'postcode', 'password', 'password_confirm', 'terms', 'currency'] as $key) {
        $bad = $valid; $bad[$key] = '';
        client_check(is_wp_error(Rackmonk_Client_Area::validate_registration($bad, $schema)), 'Missing ' . $key . ' rejected');
    }
    foreach (['email' => ['array'], 'country' => 'ZZ', 'state' => 'Invalid', 'currency' => '99999', 'rm_company_url' => 'https://spam.example.test', 'password' => 'short', 'customfield' => 'not-an-array'] as $key => $value) {
        $bad = $valid; $bad[$key] = $value;
        client_check(is_wp_error(Rackmonk_Client_Area::validate_registration($bad, $schema)), 'Invalid ' . $key . ' rejected');
    }
    foreach (["Quote'Password!12345", 'Back\\slash-Password!12345', "Both'and\\slash!12345"] as $password) {
        $special = $valid; $special['password'] = $password; $special['password_confirm'] = $password;
        $data = Rackmonk_Client_Area::validate_registration($special, $schema);
        client_check(!is_wp_error($data) && $data['password'] === $password, 'WHMCS password is unchanged by profile sanitization');
        $hash = wp_hash_password(wp_slash($data['password']));
        client_check(wp_check_password(wp_slash($password), $hash), 'Native Woo POST representation matches signup hash');
    }
    foreach (['/my-account/' => 'login', '/register/' => 'register', '/my-account/lost-password/' => 'forgot', '/my-account/lost-password/?reset-link-sent=true' => 'sent'] as $path => $mode) {
        $response = wp_remote_get(home_url($path), ['timeout' => 30]);
        client_check(!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200, $mode . ' page returns HTTP 200');
        $html = wp_remote_retrieve_body($response);
        client_check(strpos($html, 'data-auth-mode="' . $mode . '"') !== false, $mode . ' uses the designed auth shell');
        client_check(strpos($html, 'client-area.css') !== false && strpos($html, 'client-area.js') !== false, $mode . ' assets are loaded');
        client_check(strpos($html, 'Fatal error') === false && strpos($html, 'Warning:') === false, $mode . ' has no PHP error output');
        if ($mode === 'register') {
            foreach (['firstname', 'lastname', 'email', 'phonenumber', 'companyname', 'address1', 'address2', 'city', 'state', 'country', 'postcode', 'password', 'password_confirm', 'currency', 'terms'] as $name) client_check(strpos($html, 'name="' . $name . '"') !== false, 'Registration renders ' . $name);
            client_check(strpos($html, 'rackmonk-register-nonce') !== false, 'Registration is nonce-protected');
        }
        if ($mode === 'login') client_check(strpos($html, '>Client Area<') !== false && strpos($html, '/register/') !== false && strpos($html, '/lost-password/') !== false, 'Client Area opens login with register and forgot links');
    }
    echo "$checks checks passed. No users created and no emails sent.\nVERDICT: PASS\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'VERDICT: FAIL -- ' . $error->getMessage() . "\n");
    exit(1);
}
