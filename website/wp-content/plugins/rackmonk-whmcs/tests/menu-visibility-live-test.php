<?php
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 4) . '/wp-load.php';
function menu_check($ok, $message) {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS: $message\n";
}
try {
    $menu = wp_get_nav_menu_object('Main Menu');
    $stored = wp_get_nav_menu_items($menu->term_id);
    $editor = new Rackmonk_Site_Content();
    $visible = $editor->visible_menu_items($stored);
    $ids = array_column($visible, 'ID');
    $hidden_parents = [];
    foreach ($stored as $item) {
        if (in_array($item->title, ['Domain', 'Pages'], true)) {
            menu_check(get_post_meta($item->ID, '_rackmonk_menu_hidden', true) === '1', $item->title . ' retained and marked hidden');
            menu_check(!in_array($item->ID, $ids, true), $item->title . ' excluded from frontend output');
            $hidden_parents[] = $item->ID;
            ob_start(); $editor->menu_visibility_field($item->ID, $item); $field = ob_get_clean();
            menu_check(strpos($field, 'Hide on website') !== false && strpos($field, 'checked=') !== false, $item->title . ' has a checked admin visibility control');
        }
        if (in_array((int) $item->menu_item_parent, $hidden_parents, true)) menu_check(!in_array($item->ID, $ids, true), 'Hidden parent descendant excluded: ' . $item->title);
    }
    foreach ($visible as $item) menu_check(!(int) $item->menu_item_parent || in_array((int) $item->menu_item_parent, $ids, true), 'No orphaned visible item: ' . $item->title);
    $response = wp_remote_get(home_url('/'), ['timeout' => 30]);
    menu_check(!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200, 'Homepage returns HTTP 200');
    $dom = new DOMDocument();
    @$dom->loadHTML(wp_remote_retrieve_body($response));
    $xpath = new DOMXPath($dom);
    $titles = [];
    foreach ($xpath->query('//nav[@id="rm-primary-nav"]/ul/li/a') as $link) $titles[] = trim($link->textContent);
    menu_check($titles === ['Home', 'Hosting', 'About Us', 'Contact Us', 'Client Area'], 'Live header shows only the requested top-level items');
    menu_check(count($stored) === 24, 'All 24 WordPress menu records preserved');
    echo "VERDICT: PASS\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'VERDICT: FAIL -- ' . $error->getMessage() . "\n");
    exit(1);
}
