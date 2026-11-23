<?php
/** Read-only WordPress currency/catalog smoke test. No orders, accounts or pricing rows are created. */
if (PHP_SAPI !== 'cli') exit;
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require dirname(__DIR__, 4) . '/wp-load.php';
$manager = Rackmonk_Plans_Manager::get_instance();
$checks = 0;
function verify_currency_live($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
    $GLOBALS['checks']++;
}
foreach (['49.32.0.1' => 'INR', '8.8.8.8' => 'USD'] as $ip => $code) {
    $_SERVER['REMOTE_ADDR'] = $ip;
    Rackmonk_Currency::reset();
    verify_currency_live(Rackmonk_Currency::current()['code'] === $code, 'Guest currency ' . $code);
    $plans = $manager->get_plans(3);
    verify_currency_live(count($plans) > 0, 'VPS catalogue available');
    foreach ($plans as $plan) {
        verify_currency_live($plan['currency_code'] === $code, 'VPS catalogue currency');
        $config = Rackmonk_Website_Cart::runtime_config($plan['id']);
        verify_currency_live($config['currency'] === $code, 'VPS configurator currency');
        foreach ($plan['cycles'] as $term => $cycle) {
            $native = $config['prices'][$term] ?? null;
            verify_currency_live($cycle['available'] === ($native !== null), 'Catalogue/cart availability agrees');
            if ($native) verify_currency_live(abs($cycle['total_price'] - (float) $native['recurring']) < 0.001, 'Catalogue/cart recurring totals agree');
        }
    }
    $items = $manager->get_elementor_pricing_items(3);
    verify_currency_live(count($items) === count($plans), 'Elementor pricing adapter renders without missing methods');
    echo $code . ' VPS price: ' . $items[0]['m_price'] . "\n";
}
// Exercise actual linked accounts without logging in, issuing cookies or changing user metadata.
$linked_users = get_users(['fields' => 'ID', 'number' => 10, 'meta_query' => ['relation' => 'OR', ['key' => '_rackmonk_whmcs_client_id', 'compare' => 'EXISTS'], ['key' => '_rackmonk_whmcs_currency_id', 'compare' => 'EXISTS']]]);
foreach ($linked_users as $user_id) {
    $account_currency = Rackmonk_Currency::account_currency($user_id);
    if (!$account_currency) continue;
    wp_set_current_user($user_id);
    $_SERVER['REMOTE_ADDR'] = $account_currency['code'] === 'INR' ? '8.8.8.8' : '49.32.0.1';
    Rackmonk_Currency::reset();
    verify_currency_live(Rackmonk_Currency::current()['code'] === $account_currency['code'], 'Linked account billing currency overrides opposite-country IP');
    foreach ($manager->get_plans(3) as $plan) verify_currency_live($plan['currency_code'] === $account_currency['code'], 'Linked account catalogue follows billing currency');
    echo 'Linked account currency overrides guest IP: ' . $account_currency['code'] . "\n";
}
wp_set_current_user(0);
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
Rackmonk_Currency::reset();
foreach (['', 'web-hosting/', 'wp-hosting/', 'cloud-hosting/', 'vps-hosting/', 'shared-hosting-2/'] as $path) {
    $response = wp_remote_get(home_url('/' . $path), ['timeout' => 60]);
    verify_currency_live(!is_wp_error($response), 'HTTP request completed');
    verify_currency_live(wp_remote_retrieve_response_code($response) === 200, 'HTTP 200 ' . $path);
    $html = wp_remote_retrieve_body($response);
    verify_currency_live(strpos($html, '$4.50/mo') === false, 'No imported USD demo badge');
    verify_currency_live(!preg_match('/\{(?:from_price|monthly_price)(?::[^}]+)?\}/', $html), 'Price tokens resolved');
    verify_currency_live(strpos($html, 'Fatal error') === false, 'No PHP fatal response');
    verify_currency_live(strpos(wp_remote_retrieve_header($response, 'cache-control'), 'no-store') !== false, 'Personalized prices are not publicly cacheable');
    echo '/' . $path . ': HTTP 200, no unresolved tokens or demo badge' . "\n";
}
echo "$checks live currency assertions passed. Read-only.\nVERDICT: PASS\n";
