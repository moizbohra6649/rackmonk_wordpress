<?php
/** One-time header configuration, using the normal WordPress menu/media APIs. */
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 3) . '/wp-load.php';
if (!in_array('--apply', $argv, true)) exit("Use --apply to assign Main Menu, add local page links and set the Rackmonk logo.\n");

$menu = wp_get_nav_menu_object('Main Menu');
if (!$menu) throw new RuntimeException('Existing Main Menu not found.');
$items = wp_get_nav_menu_items($menu->term_id) ?: [];
add_option('rackmonk_header_setup_backup', ['theme_mods' => get_theme_mods(), 'menu_items' => array_map(static function ($item) {
    return ['id' => $item->ID, 'title' => $item->title, 'url' => $item->url, 'parent' => $item->menu_item_parent, 'order' => $item->menu_order];
}, $items)], '', false);

function rackmonk_header_item($menu_id, $items, $title, $page = null, $parent = 0, $classes = []) {
    $id = 0;
    $position = 0;
    foreach ($items as $item) if (strcasecmp($item->title, $title) === 0 || ($title === 'Client Area' && strcasecmp($item->title, 'Login') === 0)) { $id = $item->ID; $position = $item->menu_order; break; }
    $data = ['menu-item-title' => $title, 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent, 'menu-item-classes' => implode(' ', $classes)];
    if ($position) $data['menu-item-position'] = $position;
    if (in_array('rm-menu-login', $classes, true)) {
        $current = wp_get_nav_menu_items($menu_id) ?: [];
        $data['menu-item-position'] = $current ? max(array_column($current, 'menu_order')) + 1 : 1;
    }
    if ($page) {
        $data += ['menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $page->ID];
    } else {
        $data += ['menu-item-type' => 'custom', 'menu-item-url' => home_url('/')];
    }
    $result = wp_update_nav_menu_item($menu_id, $id, $data);
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
    echo "Configured menu item: $title\n";
    return $result;
}

$hosting = rackmonk_header_item($menu->term_id, $items, 'Hosting', get_page_by_path('pricing'));
foreach (['Shared Hosting' => 'shared-hosting-2', 'Cloud Hosting' => 'cloud-hosting', 'VPS Hosting' => 'vps-hosting', 'WordPress Hosting' => 'wp-hosting', 'Migration' => 'migration'] as $label => $slug) {
    $page = get_page_by_path($slug);
    if (!$page) throw new RuntimeException('Missing page: ' . $slug);
    rackmonk_header_item($menu->term_id, $items, $label, $page, $hosting);
}
$domain = rackmonk_header_item($menu->term_id, $items, 'Domain', get_page_by_path('domain'));
foreach (['Domain Search' => 'domain', 'Domain Transfer' => 'domain-transfer'] as $label => $slug) rackmonk_header_item($menu->term_id, $items, $label, get_page_by_path($slug), $domain);
$account = get_post((int) get_option('woocommerce_myaccount_page_id'));
if (!$account || $account->post_status !== 'publish') throw new RuntimeException('Published account page not found.');
rackmonk_header_item($menu->term_id, $items, 'Client Area', $account, 0, ['rm-menu-login']);
// Preserve the existing Register item, but do not send it to WHMCS.
foreach ($items as $item) if (strcasecmp($item->title, 'Register') === 0) {
    $register = get_post((int) get_option('rackmonk_register_page_id'));
    rackmonk_header_item($menu->term_id, $items, 'Register', $register && $register->post_status === 'publish' ? $register : $account, (int) $item->menu_item_parent);
}
$locations = get_theme_mod('nav_menu_locations', []);
$locations['menu-1'] = (int) $menu->term_id;
set_theme_mod('nav_menu_locations', $locations);

$logo_id = (int) get_option('rackmonk_header_logo_id');
if (!$logo_id || !wp_attachment_is_image($logo_id)) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $logo_id = media_sideload_image(get_template_directory_uri() . '/assets/img/rackmonk-logo-light.png', 0, 'Rackmonk header logo', 'id');
    if (is_wp_error($logo_id)) throw new RuntimeException($logo_id->get_error_message());
    update_post_meta($logo_id, '_wp_attachment_image_alt', 'Rackmonk');
    update_option('rackmonk_header_logo_id', $logo_id, false);
}
set_theme_mod('custom_logo', $logo_id);
if (class_exists('\\Elementor\\Plugin')) \Elementor\Plugin::$instance->files_manager->clear_cache();
echo "Assigned Main Menu to Primary and configured editable logo attachment $logo_id.\nVERDICT: PASS\n";
