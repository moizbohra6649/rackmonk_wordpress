<?php
/** Temporary, mail-suppressed fixture accounts; cleanup uses supported DeleteClient. */
if (PHP_SAPI !== 'cli') exit;
if (in_array('--inspect-fixtures', $argv, true)) {
    ob_start(); require dirname(__DIR__, 5) . '/whmcs/init.php';
    $db = '\WHMCS\Database\Capsule';
    $clients = [];
    foreach ($db::table('tblclients')->where('email', 'like', 'rm-auth-fixture-%@example.test')->get() as $row) {
        $clients[] = ['id' => $row->id, 'has_reference' => strpos($row->notes, 'Rackmonk registration reference: ') === 0, 'hosting' => $db::table('tblhosting')->where('userid', $row->id)->count(), 'invoices' => $db::table('tblinvoices')->where('userid', $row->id)->count(), 'orders' => $db::table('tblorders')->where('userid', $row->id)->count()];
    }
    $users = [];
    foreach ($db::table('tblusers')->where('email', 'like', 'rm-auth-fixture-%@example.test')->get() as $row) $users[] = ['id' => $row->id, 'links' => $db::table('tblusers_clients')->where('auth_user_id', $row->id)->count(), 'mfa_method_empty' => empty($row->second_factor), 'mfa_config_empty' => in_array($row->second_factor_config, ['', null, '{}', '[]', 'a:0:{}'], true)];
    $methods = [];
    foreach ((new ReflectionClass('WHMCS\User\User'))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) if (stripos($method->name, 'delet') !== false) $methods[] = $method->name;
    if (in_array('--remove-orphans', $argv, true)) {
        foreach ($db::table('tblusers')->where('email', 'like', 'rm-auth-fixture-%@example.test')->get() as $row) {
            if (!preg_match('/\Arm-auth-fixture-[a-f0-9]{12}@example\.test\z/D', $row->email) || $row->first_name !== 'Integration' || $row->last_name !== 'Fixture' || $db::table('tblusers_clients')->where('auth_user_id', $row->id)->count()) continue;
            $model = \WHMCS\User\User::find($row->id);
            $model->delete();
        }
    }
    ob_end_clean(); echo json_encode(['clients' => $clients, 'users' => $users, 'deletion_methods' => $methods]) . "\n"; exit;
}
if (in_array('--probe-registration', $argv, true)) {
    ob_start(); require dirname(__DIR__, 5) . '/whmcs/init.php';
    $db = '\WHMCS\Database\Capsule';
    $email = 'rm-auth-fixture-' . bin2hex(random_bytes(6)) . '@example.test';
    $hex = bin2hex(random_bytes(16)); $ref = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    $params = ['firstname' => 'Integration', 'lastname' => 'Fixture', 'companyname' => 'AUTOMATED TEST - DO NOT BILL', 'email' => $email, 'address1' => 'Integration fixture only', 'address2' => '', 'city' => 'Indore', 'state' => 'MP', 'postcode' => '452010', 'country' => 'IN', 'phonenumber' => '+91 9000000000', 'tax_id' => '', 'password2' => "Test'Back\\slash-Strong!54321", 'securityqid' => '', 'securityqans' => '', 'currency' => (string) $db::table('tblcurrencies')->orderBy('default', 'desc')->value('id'), 'marketingoptin' => false, 'customfields' => base64_encode(serialize([])), 'notes' => 'Rackmonk registration reference: ' . $ref, 'skipvalidation' => false, 'noemail' => true];
    $params['phonenumber'] = '+91.6264069660';
    $result = localAPI('AddClient', $params);
    $client = $db::table('tblclients')->where('email', $email)->where('notes', $params['notes'])->first();
    $summary = ['result' => $result['result'] ?? '', 'message' => $result['message'] ?? '', 'exact_reference' => (bool) $client];
    if ($client) {
        $summary['owners'] = $db::table('tblusers_clients')->where('client_id', $client->id)->where('owner', 1)->count();
        $owner_id = $db::table('tblusers_clients')->where('client_id', $client->id)->where('owner', 1)->value('auth_user_id');
        $owner = $db::table('tblusers')->where('id', $owner_id)->first();
        $summary['allow_sso'] = (bool) $client->allow_sso;
        $summary['mfa_config_class'] = array_search($owner->second_factor_config, ['', null, '{}', '[]', 'a:0:{}'], true);
        $sso = localAPI('CreateSsoToken', ['client_id' => $client->id, 'user_id' => $owner_id]);
        $summary['sso_result'] = $sso['result'] ?? '';
        $summary['sso_message'] = $sso['message'] ?? '';
        $deleted = localAPI('DeleteClient', ['clientid' => $client->id, 'deleteusers' => true, 'deletetransactions' => false]);
        $summary['cleanup'] = $deleted['result'] ?? 'error';
        if (($deleted['result'] ?? '') === 'success' && $owner && $owner->email === $email && !$db::table('tblusers_clients')->where('auth_user_id', $owner_id)->count()) \WHMCS\User\User::find($owner_id)->delete();
    }
    ob_end_clean(); echo json_encode($summary) . "\n"; exit;
}
if (in_array('--probe-validation', $argv, true)) {
    ob_start(); require dirname(__DIR__, 5) . '/whmcs/init.php';
    $result = localAPI('AddClient', ['firstname' => '', 'lastname' => '', 'email' => '', 'address1' => '', 'city' => '', 'state' => '', 'postcode' => '', 'country' => 'INVALID', 'phonenumber' => '', 'password2' => '', 'skipvalidation' => false, 'noemail' => true]);
    ob_end_clean();
    echo json_encode(['result' => $result['result'] ?? '', 'message' => $result['message'] ?? '']) . "\n";
    exit;
}
if (in_array('--cleanup', $argv, true)) {
    $input = json_decode(stream_get_contents(STDIN), true);
    if (!is_array($input) || !preg_match('/\Arm-auth-fixture-[a-f0-9]{12}@example\.test\z/D', $input['email'] ?? '') || !preg_match('/\A[0-9a-f-]{36}\z/D', $input['reference'] ?? '')) exit(3);
    ob_start();
    require dirname(__DIR__, 5) . '/whmcs/init.php';
    $db = '\WHMCS\Database\Capsule';
    $client = $db::table('tblclients')->where('id', (int) $input['client_id'])->first();
    if (!$client || $client->email !== $input['email'] || $client->notes !== 'Rackmonk registration reference: ' . $input['reference']) exit(4);
    foreach (['tblhosting', 'tbldomains', 'tblinvoices', 'tblorders'] as $table) if ($db::table($table)->where('userid', $client->id)->count()) exit(5);
    $owners = $db::table('tblusers_clients')->where('client_id', $client->id)->where('owner', 1)->pluck('auth_user_id');
    $result = localAPI('DeleteClient', ['clientid' => $client->id, 'deleteusers' => true, 'deletetransactions' => false]);
    if (($result['result'] ?? '') === 'success') foreach ($owners as $owner_id) {
        $owner = \WHMCS\User\User::find($owner_id);
        if ($owner && $owner->email === $input['email'] && !$db::table('tblusers_clients')->where('auth_user_id', $owner_id)->count()) $owner->delete();
    }
    ob_end_clean();
    echo json_encode(['result' => $result['result'] ?? 'error']);
    exit(($result['result'] ?? '') === 'success' ? 0 : 6);
}
if (in_array('--reset-fixture', $argv, true)) {
    $input = json_decode(stream_get_contents(STDIN), true);
    require dirname(__DIR__, 4) . '/wp-load.php';
    $user = get_user_by('login', $input['reset_login'] ?? '');
    if (!$user || !preg_match('/\Arm-auth-fixture-[a-f0-9]{12}@example\.test\z/D', $user->user_email)) exit(3);
    add_filter('pre_wp_mail', static function () { return true; });
    $_POST = wp_slash($input); $_REQUEST = $_POST;
    $success = false;
    add_filter('wp_redirect', static function ($url) use (&$success) {
        $success = strpos($url, 'password-reset=true') !== false;
        throw new RuntimeException('fixture_redirect');
    });
    try { WC_Form_Handler::process_reset_password(); } catch (RuntimeException $error) { if ($error->getMessage() !== 'fixture_redirect') $success = false; }
    echo json_encode(['success' => $success]);
    exit($success ? 0 : 4);
}
if (!in_array('--live-accounts', $argv, true) && !in_array('--cleanup-fixtures', $argv, true)) exit("Use --live-accounts to create and remove temporary test accounts. No emails are sent.\n");
require dirname(__DIR__, 4) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
if (in_array('--cleanup-fixtures', $argv, true)) {
    $count = 0;
    foreach (get_users(['meta_key' => '_rackmonk_registration_managed', 'meta_value' => '1']) as $fixture) {
        if (!preg_match('/\Arm-auth-fixture-[a-f0-9]{12}@example\.test\z/D', $fixture->user_email) || $fixture->first_name !== 'Integration' || $fixture->last_name !== 'Fixture') continue;
        $data = ['client_id' => (int) get_user_meta($fixture->ID, '_rackmonk_whmcs_client_id', true), 'reference' => get_user_meta($fixture->ID, '_rackmonk_whmcs_registration_ref', true), 'email' => $fixture->user_email];
        $proc = proc_open([PHP_BINARY, __FILE__, '--cleanup'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__, null, ['bypass_shell' => true]);
        fwrite($pipes[0], wp_json_encode($data)); fclose($pipes[0]); $out = stream_get_contents($pipes[1]); fclose($pipes[1]); stream_get_contents($pipes[2]); fclose($pipes[2]); $exit = proc_close($proc);
        if ($exit !== 0 || (json_decode($out, true)['result'] ?? '') !== 'success') throw new RuntimeException('Guarded fixture cleanup failed.');
        wp_delete_user($fixture->ID); $count++;
    }
    echo "Removed $count guarded synthetic fixture accounts.\n"; exit;
}
$checks = 0;
function integration_check($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
    echo "PASS: $message\n";
}
function input_value($html, $name) {
    $dom = new DOMDocument(); @$dom->loadHTML($html);
    $nodes = (new DOMXPath($dom))->query('//input[@name="' . $name . '"]');
    return $nodes->length ? $nodes->item(0)->getAttribute('value') : '';
}
function fixture_login($email, $password) {
    $url = wc_get_page_permalink('myaccount');
    $form = wp_remote_get($url, ['timeout' => 30]);
    return wp_remote_post($url, ['timeout' => 30, 'redirection' => 0, 'body' => ['username' => $email, 'password' => $password, 'login' => 'Sign in', 'woocommerce-login-nonce' => input_value(wp_remote_retrieve_body($form), 'woocommerce-login-nonce'), 'redirect' => $url]]);
}
$pdo = Rackmonk_Plans_Manager::get_instance()->get_whmcs_db();
$before = ['wp' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"), 'clients' => (int) $pdo->query('SELECT COUNT(*) FROM tblclients')->fetchColumn(), 'users' => (int) $pdo->query('SELECT COUNT(*) FROM tblusers')->fetchColumn()];
$email = 'rm-auth-fixture-' . bin2hex(random_bytes(6)) . '@example.test';
$password = "Test'Back\\slash-Strong!54321";
$schema = Rackmonk_WHMCS_Client_API::schema();
$input = ['firstname' => 'Integration', 'lastname' => 'Fixture', 'email' => $email, 'phonenumber' => '+91.6264069660', 'companyname' => 'AUTOMATED TEST - DO NOT BILL', 'tax_id' => '', 'address1' => 'Integration fixture only', 'address2' => '', 'city' => 'Indore', 'state' => 'MP', 'country' => 'IN', 'postcode' => '452010', 'password' => $password, 'password_confirm' => $password, 'currency' => (string) $schema['currencies'][0]['id'], 'terms' => '1'];
$id = 0; $client_id = 0; $whmcs_user_id = 0; $reference = ''; $failure = null;
$rate = 'rm_auth_register_' . hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown', wp_salt('nonce'));
$original_rate = get_transient($rate);
delete_transient($rate);
$suppress_mail = static function () { return true; };
add_filter('pre_wp_mail', $suppress_mail);
try {
    $api = new Rackmonk_Client_Area();
    $fault_input = $input; $fault_input['email'] = 'rm-auth-fixture-' . bin2hex(random_bytes(6)) . '@example.test';
    $deny_reference = static function ($check, $object_id, $meta_key) { return $meta_key === '_rackmonk_whmcs_registration_ref' ? false : $check; };
    add_filter('update_user_metadata', $deny_reference, 10, 3);
    add_filter('add_user_metadata', $deny_reference, 10, 3);
    try { $fault_result = $api->register($fault_input); } finally { remove_filter('update_user_metadata', $deny_reference, 10); remove_filter('add_user_metadata', $deny_reference, 10); }
    $fault_remaining = get_user_by('email', $fault_input['email']);
    if ($fault_remaining) { $id = $fault_remaining->ID; $email = $fault_input['email']; }
    integration_check(is_wp_error($fault_result) && $fault_result->get_error_code() === 'registration_storage' && !get_user_by('email', $fault_input['email']) && (int) $pdo->query('SELECT COUNT(*) FROM tblclients')->fetchColumn() === $before['clients'], 'Failed pending-reference persistence rolls back before WHMCS creation');
    $registration_page = wp_remote_get(Rackmonk_Client_Area::register_url(), ['timeout' => 30]);
    $body = $input + ['rm_auth_action' => 'register', 'rackmonk-register-nonce' => input_value(wp_remote_retrieve_body($registration_page), 'rackmonk-register-nonce')];
    $http_registration = wp_remote_post(Rackmonk_Client_Area::register_url(), ['timeout' => 60, 'redirection' => 0, 'body' => $body]);
    $registered = get_user_by('email', $email);
    $id = $registered ? $registered->ID : 0;
    integration_check(!is_wp_error($http_registration) && wp_remote_retrieve_response_code($http_registration) === 302 && $id > 0, 'Actual registration form POST creates website and WHMCS identities');
    $client_id = (int) get_user_meta($id, '_rackmonk_whmcs_client_id', true);
    $whmcs_user_id = (int) get_user_meta($id, '_rackmonk_whmcs_user_id', true);
    $reference = get_user_meta($id, '_rackmonk_whmcs_registration_ref', true);
    integration_check($client_id > 0 && $whmcs_user_id > 0 && get_user_meta($id, '_rackmonk_whmcs_sync_status', true) === 'active', 'Only a verified client/user mapping activates website access');
    $owner = $pdo->prepare('SELECT COUNT(*) FROM tblusers_clients WHERE client_id=? AND auth_user_id=? AND owner=1');
    $owner->execute([$client_id, $whmcs_user_id]);
    integration_check((int) $owner->fetchColumn() === 1, 'WHMCS owner relationship exists');
    $hash = $pdo->prepare('SELECT password FROM tblusers WHERE id=?'); $hash->execute([$whmcs_user_id]);
    integration_check(password_verify($password, $hash->fetchColumn()), 'WHMCS receives the unchanged quote/backslash password');
    integration_check(get_user_meta($id, 'billing_city', true) === 'Indore' && get_user_meta($id, 'billing_state', true) === 'MP', 'Website checkout billing profile is populated');
    $snapshot = get_user_meta($id, '_rackmonk_registration_profile', true);
    integration_check(is_array($snapshot) && !isset($snapshot['password'], $snapshot['securityqans']), 'Durable profile contains no password or security answer');
    $login = fixture_login($email, $password);
    integration_check(!is_wp_error($login) && wp_remote_retrieve_response_code($login) === 302, 'Real native Woo login accepts the signup password');
    $cookies = wp_remote_retrieve_cookies($login);
    $dashboard = wp_remote_get(wc_get_page_permalink('myaccount'), ['timeout' => 30, 'cookies' => $cookies]);
    $dashboard_html = wp_remote_retrieve_body($dashboard);
    integration_check(strpos($dashboard_html, 'name="rm_auth_action" value="sso"') !== false, 'Authenticated account offers its hosting workspace');
    $sso = wp_remote_post(wc_get_page_permalink('myaccount'), ['timeout' => 35, 'redirection' => 0, 'cookies' => $cookies, 'body' => ['rm_auth_action' => 'sso', 'rackmonk-sso-nonce' => input_value($dashboard_html, 'rackmonk-sso-nonce')]]);
    $location = is_wp_error($sso) ? '' : wp_remote_retrieve_header($sso, 'location');
    $sso_error = '';
    if (!is_wp_error($sso) && wp_remote_retrieve_response_code($sso) !== 302) {
        $dom = new DOMDocument(); @$dom->loadHTML(wp_remote_retrieve_body($sso));
        foreach ((new DOMXPath($dom))->query('//*[contains(concat(" ",normalize-space(@class)," ")," woocommerce-error ")]') as $error) $sso_error .= trim($error->textContent);
    }
    integration_check(!is_wp_error($sso) && wp_remote_retrieve_response_code($sso) === 302 && wp_parse_url($location, PHP_URL_HOST) === wp_parse_url(Rackmonk_Plans_Manager::get_instance()->get_whmcs_system_url(), PHP_URL_HOST), 'Hosting workspace issues a server-side WHMCS SSO redirect' . ($sso_error ? ': ' . $sso_error : ''));
    unset($location, $sso);
    $duplicate = $api->register($input);
    integration_check(is_wp_error($duplicate) && $duplicate->get_error_code() === 'registration_exists', 'Duplicate website registration rejected without auto-linking');
    $user = get_user_by('id', $id);
    $_POST = ['user_login' => $email];
    $mail_count = 0;
    $capture = static function ($pre, $atts) use (&$mail_count) { $mail_count++; return true; };
    add_filter('pre_wp_mail', $capture, 20, 2);
    $retrieved = WC_Shortcode_My_Account::retrieve_password();
    remove_filter('pre_wp_mail', $capture, 20);
    $_POST = [];
    integration_check($retrieved && $mail_count === 1, 'Forgot-password workflow generates a reset email (delivery intercepted)');
    $key = get_password_reset_key($user);
    integration_check(!is_wp_error($key), 'Native expiring reset key generated');
    $reset_url = add_query_arg('show-reset-form', 'true', wc_get_account_endpoint_url('lost-password'));
    $reset_cookie = new WP_Http_Cookie(['name' => 'wp-resetpass-' . COOKIEHASH, 'value' => $id . ':' . $key]);
    $reset_form = wp_remote_get($reset_url, ['timeout' => 30, 'cookies' => [$reset_cookie]]);
    $reset_html = wp_remote_retrieve_body($reset_form);
    integration_check(strpos($reset_html, 'data-auth-mode="reset"') !== false && input_value($reset_html, 'reset_key') === $key, 'Valid reset key opens the new reset-password design');
    $new_password = "New'Back\\slash-Password!67890";
    $process = proc_open([PHP_BINARY, '-d', 'display_errors=0', __FILE__, '--reset-fixture'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__, null, ['bypass_shell' => true]);
    fwrite($pipes[0], wp_json_encode(['wc_reset_password' => 'true', 'password_1' => $new_password, 'password_2' => $new_password, 'reset_key' => $key, 'reset_login' => $user->user_login, 'woocommerce-reset-password-nonce' => input_value($reset_html, 'woocommerce-reset-password-nonce')])); fclose($pipes[0]);
    $reset = json_decode(stream_get_contents($pipes[1]), true); fclose($pipes[1]); stream_get_contents($pipes[2]); fclose($pipes[2]); $reset_exit = proc_close($process);
    integration_check($reset_exit === 0 && !empty($reset['success']), 'Native password-reset handler completes successfully with mail intercepted');
    $login = fixture_login($email, $new_password);
    integration_check(!is_wp_error($login) && wp_remote_retrieve_response_code($login) === 302, 'Native login accepts reset quote/backslash password');
    clean_user_cache($id);
    $replay = wp_remote_get($reset_url, ['timeout' => 30, 'cookies' => [$reset_cookie]]);
    integration_check(is_wp_error(check_password_reset_key($key, $user->user_login)) && strpos(wp_remote_retrieve_body($replay), 'data-auth-mode="reset"') === false, 'Used reset key cannot be replayed');
    update_user_meta($id, '_rackmonk_whmcs_sync_status', 'pending');
    integration_check(is_wp_error($api->pending_login(get_user_by('id', $id), '', '')), 'Pending registration is blocked from authentication');
    $fault = static function ($check, $object_id, $meta_key, $value) use ($id) { return (int) $object_id === $id && $meta_key === '_rackmonk_whmcs_sync_status' && $value === 'active' ? false : $check; };
    add_filter('update_user_metadata', $fault, 10, 4);
    $activation = Rackmonk_Client_Area::activate($id, ['client_id' => $client_id, 'user_id' => $whmcs_user_id]);
    remove_filter('update_user_metadata', $fault, 10);
    integration_check(is_wp_error($activation) && get_user_meta($id, '_rackmonk_whmcs_sync_status', true) === 'pending', 'Failed activation persistence stays blocked and rolls back');
    $retry = $input; $retry['password'] = $new_password; $retry['password_confirm'] = $new_password; $retry['address1'] = 'CHANGED RETRY MUST NOT OVERWRITE';
    $recovered = $api->register($retry);
    integration_check($recovered === $id && get_user_meta($id, '_rackmonk_registration_profile', true) === $snapshot && get_user_meta($id, 'billing_address_1', true) === $input['address1'], 'Exact-reference recovery retains original billing snapshot');
    wp_delete_user($id);
    $rejected = $api->register($input);
    integration_check(is_wp_error($rejected) && !get_user_by('email', $email), 'Existing WHMCS identity is never claimed by email-only signup');
} catch (Throwable $error) {
    $failure = $error->getMessage();
    $pending = get_user_by('email', $email);
    if ($pending) {
        $ref = get_user_meta($pending->ID, '_rackmonk_whmcs_registration_ref', true);
        $find = $pdo->prepare('SELECT COUNT(*) FROM tblclients WHERE BINARY notes=? AND email=?'); $find->execute(['Rackmonk registration reference: ' . $ref, $email]);
        echo 'Diagnostic: pending website account=' . get_user_meta($pending->ID, '_rackmonk_whmcs_sync_status', true) . '; exact-reference WHMCS matches=' . (int) $find->fetchColumn() . "\n";
    }
}
finally {
    remove_filter('pre_wp_mail', $suppress_mail);
    $_POST = [];
    $remaining = get_user_by('email', $email);
    if ($remaining) {
        $id = $remaining->ID;
        $reference = $reference ?: get_user_meta($id, '_rackmonk_whmcs_registration_ref', true);
        $client_id = $client_id ?: (int) get_user_meta($id, '_rackmonk_whmcs_client_id', true);
        wp_delete_user($id);
    }
    if (!$client_id && $reference) {
        $find = $pdo->prepare('SELECT id FROM tblclients WHERE BINARY notes=? AND email=?');
        $find->execute(['Rackmonk registration reference: ' . $reference, $email]);
        $client_id = (int) $find->fetchColumn();
    }
    if ($client_id) {
        $process = proc_open([PHP_BINARY, '-d', 'display_errors=0', '-d', 'log_errors=0', __FILE__, '--cleanup'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__, null, ['bypass_shell' => true]);
        fwrite($pipes[0], wp_json_encode(['client_id' => $client_id, 'reference' => $reference, 'email' => $email])); fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]); fclose($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0 || ($result = json_decode($output, true)) === null || ($result['result'] ?? '') !== 'success') $failure = 'Temporary WHMCS fixture cleanup requires attention (no real accounts were targeted).';
    }
    if ($original_rate === false) delete_transient($rate); else set_transient($rate, $original_rate, HOUR_IN_SECONDS);
    $after = ['wp' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"), 'clients' => (int) $pdo->query('SELECT COUNT(*) FROM tblclients')->fetchColumn(), 'users' => (int) $pdo->query('SELECT COUNT(*) FROM tblusers')->fetchColumn()];
    if ($before !== $after) $failure = ($failure ? $failure . ' ' : '') . 'Temporary account counts were not restored: ' . wp_json_encode(['before' => $before, 'after' => $after]);
    else echo "PASS: Temporary WP/WHMCS clients and users removed; original account counts restored.\n";
}
if ($failure) { fwrite(STDERR, 'VERDICT: FAIL -- ' . $failure . "\n"); exit(1); }
echo "$checks integration checks passed. SMTP delivery not tested.\nVERDICT: PASS\n";
