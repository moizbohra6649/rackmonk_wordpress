<?php
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 4) . '/wp-load.php';
if (in_array('--apply', $argv, true)) {
    $saved = Rackmonk_Site_Content::all();
    if (isset($saved['account_register_intro']) && stripos($saved['account_register_intro'], 'WHMCS') !== false) {
        add_option('rackmonk_auth_copy_backup', ['account_register_intro' => $saved['account_register_intro']], '', false);
        $saved['account_register_intro'] = Rackmonk_Client_Area::content_defaults()['account_register_intro'];
        update_option(Rackmonk_Site_Content::OPTION, $saved, false);
    }
}
function auth_copy_check($html, $name) {
    $dom = new DOMDocument(); @$dom->loadHTML($html);
    $nodes = (new DOMXPath($dom))->query('//section[contains(concat(" ", normalize-space(@class), " "), " rm-auth ")]');
    if ($nodes->length !== 1) throw new RuntimeException($name . ': auth section missing');
    $text = preg_replace('/\s+/', ' ', $nodes->item(0)->textContent);
    if (stripos($text, 'WHMCS') !== false || preg_match('/client\s+area/i', $text)) throw new RuntimeException($name . ': technical description or Client Area label remains');
    echo "PASS: $name has no WHMCS description or Client Area label.\n";
}
try {
    foreach (['/my-account/' => 'Login', '/register/' => 'Register', '/my-account/lost-password/' => 'Lost password', '/my-account/lost-password/?reset-link-sent=true' => 'Reset confirmation'] as $path => $name) {
        $response = wp_remote_get(home_url($path), ['timeout' => 30]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) throw new RuntimeException($name . ': HTTP request failed');
        $html = wp_remote_retrieve_body($response);
        auth_copy_check($html, $name);
        if ($name === 'Login') {
            $dom = new DOMDocument(); @$dom->loadHTML($html);
            $links = (new DOMXPath($dom))->query('//nav[@id="rm-primary-nav"]//li[contains(concat(" ", normalize-space(@class), " "), " rm-menu-login ")]/a');
            if ($links->length !== 1 || trim($links->item(0)->textContent) !== 'Client Area') throw new RuntimeException('Header Client Area menu was changed');
        }
    }
    $args = ['key' => 'nonfunctional-test-placeholder', 'login' => 'nonfunctional-test-placeholder'];
    ob_start(); require dirname(__DIR__) . '/templates/client-area/reset.php'; $html = ob_get_clean();
    auth_copy_check($html, 'Reset password template');
    echo "PASS: Header Client Area link retained. No accounts or sessions changed.\nVERDICT: PASS\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'VERDICT: FAIL -- ' . $error->getMessage() . "\n"); exit(1);
}
