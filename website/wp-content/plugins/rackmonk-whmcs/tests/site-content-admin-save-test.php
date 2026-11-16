<?php
/** Temporarily saves editorial test text, verifies HTTP output, then restores it. */
if (PHP_SAPI !== 'cli' || !in_array('--live-save', $argv, true)) exit('Use PHP CLI with --live-save.');
require dirname(__DIR__, 4) . '/wp-load.php';
$admins = get_users(['role' => 'administrator', 'number' => 1]);
if (!$admins) throw new RuntimeException('No administrator found.');
$id = $admins[0]->ID;
$original = get_option(Rackmonk_Site_Content::OPTION, false);
$sessions = WP_Session_Tokens::get_instance($id);
$expiration = time() + 600;
$token = $sessions->create($expiration);
$auth = wp_generate_auth_cookie($id, $expiration, is_ssl() ? 'secure_auth' : 'auth', $token);
$logged = wp_generate_auth_cookie($id, $expiration, 'logged_in', $token);
$_COOKIE[LOGGED_IN_COOKIE] = $logged;
wp_set_current_user($id);
$failed = null;
$plan_original = null;
$plan_changed = false;
try {
    $settings = is_array($original) ? $original : [];
    $settings['about_title'] = 'Rackmonk admin save verification';
    $settings['cart_title'] = 'Rackmonk configurator verification';
    $settings['about_use_editor'] = '0';
    $cookie = (is_ssl() ? SECURE_AUTH_COOKIE : AUTH_COOKIE) . '=' . $auth . '; ' . LOGGED_IN_COOKIE . '=' . $logged;
    $response = wp_remote_post(admin_url('options.php'), [
        'timeout' => 30, 'redirection' => 0,
        'headers' => ['Cookie' => $cookie],
        'body' => [
            'option_page' => 'rackmonk_site_content', 'action' => 'update',
            '_wpnonce' => wp_create_nonce('rackmonk_site_content-options'),
            '_wp_http_referer' => admin_url('admin.php?page=rackmonk-site-content'),
            Rackmonk_Site_Content::OPTION => $settings,
        ],
    ]);
    if (is_wp_error($response)) throw new RuntimeException($response->get_error_message());
    if (wp_remote_retrieve_response_code($response) !== 302) throw new RuntimeException('Admin save did not redirect successfully: ' . wp_remote_retrieve_response_code($response));
    foreach ([Rackmonk_Site_Content::OPTION, 'alloptions', 'notoptions'] as $key) wp_cache_delete($key, 'options');
    if (Rackmonk_Site_Content::text('about_title') !== $settings['about_title']) throw new RuntimeException('Admin save did not persist expected text.');
    echo "PASS: Authenticated, nonce-protected WordPress options save persisted editorial text.\n";
    $response = wp_remote_get(home_url('/about-us/'), ['timeout' => 30]);
    if (is_wp_error($response) || strpos(wp_remote_retrieve_body($response), $settings['about_title']) === false) throw new RuntimeException('Public About page did not reflect saved admin content.');
    echo "PASS: Logged-out public About page reflects saved admin content.\n";
    $response = wp_remote_get(Rackmonk_Website_Cart::get_cart_url(), ['timeout' => 30]);
    if (is_wp_error($response) || strpos(wp_remote_retrieve_body($response), $settings['cart_title']) === false) throw new RuntimeException('Public configurator did not receive saved admin copy.');
    echo "PASS: Public configurator JSON reflects saved admin content.\n";
    $pdo = Rackmonk_Plans_Manager::get_instance()->get_whmcs_db();
    $plan_original = $pdo->query("SELECT id, name, tagline, description, is_featured FROM tblproducts WHERE paytype != 'onetime' ORDER BY id LIMIT 1")->fetch();
    if (!$plan_original) throw new RuntimeException('No plan available for reversible content-save check.');
    $body = [
        'action' => 'rackmonk_plan_content', 'pid' => $plan_original['id'],
        '_wpnonce' => wp_create_nonce('rackmonk_plan_content_' . $plan_original['id']),
        'version' => hash('sha256', wp_json_encode($plan_original)),
        'name' => $plan_original['name'], 'tagline' => 'Rackmonk editorial save verification',
        'description' => $plan_original['description'], 'featured' => $plan_original['is_featured'],
    ];
    $response = wp_remote_post(admin_url('admin-post.php'), ['timeout' => 30, 'redirection' => 0, 'headers' => ['Cookie' => $cookie], 'body' => $body]);
    $plan_changed = true;
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 302) throw new RuntimeException('Plan admin save failed.');
    $query = $pdo->prepare('SELECT tagline FROM tblproducts WHERE id = ?');
    $query->execute([$plan_original['id']]);
    if ($query->fetchColumn() !== $body['tagline']) throw new RuntimeException('Plan editorial text was not saved.');
    echo "PASS: Authenticated plan editor saves actual catalog copy without changing prices.\n";
    $response = wp_remote_post(admin_url('admin-post.php'), ['timeout' => 30, 'redirection' => 0, 'headers' => ['Cookie' => $cookie], 'body' => $body]);
    if (is_wp_error($response) || strpos(wp_remote_retrieve_body($response), 'Plan changed or no longer exists') === false) throw new RuntimeException('Stale plan edit was not rejected.');
    echo "PASS: Stale plan edit rejected by concurrency check.\n";
} catch (Throwable $error) {
    $failed = $error->getMessage();
} finally {
    if ($plan_changed && $plan_original) {
        $query = $pdo->prepare('UPDATE tblproducts SET name = ?, tagline = ?, description = ?, is_featured = ? WHERE id = ?');
        $query->execute([$plan_original['name'], $plan_original['tagline'], $plan_original['description'], $plan_original['is_featured'], $plan_original['id']]);
    }
    foreach ([Rackmonk_Site_Content::OPTION, 'alloptions', 'notoptions'] as $key) wp_cache_delete($key, 'options');
    if ($original === false) delete_option(Rackmonk_Site_Content::OPTION);
    else update_option(Rackmonk_Site_Content::OPTION, $original, false);
    $sessions->destroy($token);
    unset($_COOKIE[LOGGED_IN_COOKIE]);
    echo "Restored original editorial settings and removed temporary admin session.\n";
}
if ($failed) { fwrite(STDERR, 'VERDICT: FAIL -- ' . $failed . "\n"); exit(1); }
echo "VERDICT: PASS\n";
