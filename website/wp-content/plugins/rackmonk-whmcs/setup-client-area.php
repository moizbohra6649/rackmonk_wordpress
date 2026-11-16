<?php
/** One-time editable page/menu setup. Does not create customers or alter passwords. */
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 3) . '/wp-load.php';
if (!in_array('--apply', $argv, true)) exit("Use --apply to create the Register page and rename the menu entry to Client Area.\n");
$page = get_page_by_path('register');
if ($page && strpos($page->post_content, '[rackmonk_register]') === false) throw new RuntimeException('An existing Register page has unrelated content; review it before replacing anything.');
if (!$page) {
    $id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Register', 'post_name' => 'register', 'post_content' => '[rackmonk_register]'], true);
    if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
    $page = get_post($id);
}
update_option('rackmonk_register_page_id', $page->ID, false);
foreach ([$page->ID, (int) get_option('woocommerce_myaccount_page_id')] as $account_page_id) {
    if (!$account_page_id) continue;
    $meta = get_post_meta($account_page_id, 'xhosting_page_meta', true);
    add_post_meta($account_page_id, '_rackmonk_account_page_meta_backup', $meta, true);
    $meta = is_array($meta) ? $meta : [];
    $meta['enable_page_preadcrumb'] = false;
    update_post_meta($account_page_id, 'xhosting_page_meta', $meta);
}
$menu = wp_get_nav_menu_object('Main Menu');
if (!$menu) throw new RuntimeException('Main Menu not found.');
foreach (wp_get_nav_menu_items($menu->term_id) ?: [] as $item) {
    if (!in_array(strtolower($item->title), ['login', 'client area', 'register'], true)) continue;
    add_post_meta($item->ID, '_rackmonk_client_area_menu_backup', ['title' => $item->title, 'object' => $item->object, 'object_id' => $item->object_id, 'type' => $item->type], true);
    $register = strtolower($item->title) === 'register';
    $result = wp_update_nav_menu_item($menu->term_id, $item->ID, [
        'menu-item-title' => $register ? 'Register' : 'Client Area', 'menu-item-type' => 'post_type',
        'menu-item-object' => 'page', 'menu-item-object-id' => $register ? $page->ID : (int) get_option('woocommerce_myaccount_page_id'),
        'menu-item-parent-id' => $item->menu_item_parent, 'menu-item-position' => $item->menu_order,
        'menu-item-classes' => implode(' ', $item->classes), 'menu-item-status' => 'publish',
    ]);
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
}
if (class_exists('\\Elementor\\Plugin')) \Elementor\Plugin::$instance->files_manager->clear_cache();
echo 'Register page: ' . get_permalink($page) . "\nMenu entry: Client Area (existing hidden Domain/Pages preserved).\nVERDICT: PASS\n";
