<?php
/** Reversible metadata tests plus read-only public navigation checks. */
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 4) . '/wp-load.php';
$checks = 0;
function navigation_check($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++; echo "PASS: $message\n";
}
$menu = wp_get_nav_menu_object('Main Menu');
$items = wp_get_nav_menu_items($menu->term_id) ?: [];
$item = null;
foreach ($items as $candidate) if ($candidate->title === 'Shared Hosting') $item = $candidate;
if (!$item) throw new RuntimeException('Hosting menu item missing.');
$original = [];
foreach (['_rackmonk_menu_icon', '_rackmonk_menu_description'] as $key) $original[$key] = ['exists' => metadata_exists('post', $item->ID, $key), 'value' => get_post_meta($item->ID, $key, true)];
$user_before = get_current_user_id();
$post_before = $_POST;
try {
    $navigation = (new ReflectionClass('Rackmonk_Navigation'))->newInstanceWithoutConstructor();
    $admins = get_users(['role' => 'administrator', 'number' => 1]);
    wp_set_current_user($admins[0]->ID);
    ob_start(); $navigation->fields($item->ID, $item); $fields = ob_get_clean();
    navigation_check(strpos($fields, 'Navigation icon') !== false && strpos($fields, 'Dropdown description') !== false, 'WordPress menu editor exposes icon and description controls');
    navigation_check(strpos($fields, 'rackmonk_navigation_nonce[' . $item->ID . ']') !== false, 'Item-specific nonce protects editor fields');
    $_POST = ['rackmonk_navigation_nonce' => [$item->ID => 'invalid'], 'rackmonk_menu_icon' => [$item->ID => 'cloud']];
    $navigation->save($menu->term_id, $item->ID);
    navigation_check(get_post_meta($item->ID, '_rackmonk_menu_icon', true) === $original['_rackmonk_menu_icon']['value'], 'Invalid nonce cannot change the icon');
    $_POST = wp_slash(['rackmonk_navigation_nonce' => [$item->ID => wp_create_nonce('rackmonk_navigation_' . $item->ID)], 'rackmonk_menu_icon' => [$item->ID => 'cloud'], 'rackmonk_menu_description' => [$item->ID => 'A <b>better</b> plan & support']]);
    $navigation->save($menu->term_id, $item->ID);
    navigation_check(get_post_meta($item->ID, '_rackmonk_menu_icon', true) === 'cloud' && get_post_meta($item->ID, '_rackmonk_menu_description', true) === 'A better plan & support', 'Valid menu save persists icon and sanitized description');
    $html = wp_nav_menu(['theme_location' => 'menu-1', 'container' => false, 'echo' => false, 'rackmonk_mega' => true, 'walker' => new Rackmonk_Navigation_Walker()]);
    navigation_check(strpos($html, 'A better plan &amp; support') !== false && strpos($html, '<b>better</b>') === false, 'Saved descriptions render as escaped plain text');
    $_POST['rackmonk_menu_icon'][$item->ID] = 'javascript:alert(1)';
    $navigation->save($menu->term_id, $item->ID);
    navigation_check(get_post_meta($item->ID, '_rackmonk_menu_icon', true) === 'cloud', 'Only allowlisted SVG icon choices can be saved');
    wp_set_current_user(0);
    $_POST['rackmonk_menu_icon'][$item->ID] = 'server';
    $navigation->save($menu->term_id, $item->ID);
    navigation_check(get_post_meta($item->ID, '_rackmonk_menu_icon', true) === 'cloud', 'Unauthenticated requests cannot change navigation metadata');
} finally {
    foreach ($original as $key => $saved) {
        if ($saved['exists']) update_post_meta($item->ID, $key, wp_slash($saved['value'])); else delete_post_meta($item->ID, $key);
    }
    $_POST = $post_before;
    wp_set_current_user($user_before);
}
try {
    $response = wp_remote_get(home_url('/'), ['timeout' => 30]);
    navigation_check(!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200, 'Homepage serves the enhanced menu');
    $html = wp_remote_retrieve_body($response);
    $dom = new DOMDocument(); @$dom->loadHTML($html); $xpath = new DOMXPath($dom);
    $root = '//nav[@id="rm-primary-nav"]';
    $titles = [];
    foreach ($xpath->query($root . '/ul/li/a') as $link) $titles[] = trim($link->textContent);
    navigation_check($titles === ['Home', 'Hosting', 'About Us', 'Contact Us', 'Client Area'], 'Top-level items and hidden Domain/Pages preserved');
    navigation_check($xpath->query($root . '/ul/li/a/span[@class="rm-nav-icon"]/svg')->length === 5, 'Every visible top-level item has a decorative icon');
    navigation_check($xpath->query($root . '//div[@class="rm-mega-panel"]')->length === 1, 'Hosting uses one mega-menu panel');
    navigation_check($xpath->query($root . '//a[contains(@class,"rm-mega-link")]')->length === 5, 'All five existing hosting links remain');
    navigation_check($xpath->query($root . '//span[@class="rm-nav-description"]')->length === 5, 'Every hosting option has category-specific copy');
    foreach ($xpath->query($root . '//a') as $link) navigation_check(strpos($link->getAttribute('href'), home_url('/')) === 0, 'Navigation link stays on the website');
    navigation_check(strpos($html, 'rackmonk-navigation.css') !== false && strpos($html, 'rackmonk-menu.js') !== false, 'Mega-menu styles and disclosure behavior loaded');
    navigation_check(count(wp_get_nav_menu_items($menu->term_id)) === count($items), 'No WordPress menu items deleted or created');
    echo "$checks checks passed. Temporary metadata restored; accounts and sessions unchanged.\nVERDICT: PASS\n";
} catch (Throwable $error) { fwrite(STDERR, 'VERDICT: FAIL -- ' . $error->getMessage() . "\n"); exit(1); }
