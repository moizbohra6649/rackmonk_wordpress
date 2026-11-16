<?php
/** Retain all menu records while hiding Domain/Pages and promoting About Us. */
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 3) . '/wp-load.php';
$menu = wp_get_nav_menu_object('Main Menu');
if (!$menu) throw new RuntimeException('Main Menu not found.');
$items = wp_get_nav_menu_items($menu->term_id) ?: [];
$by_title = [];
foreach ($items as $item) $by_title[strtolower($item->title)] = $item;
foreach (['domain', 'pages', 'about us'] as $title) if (!isset($by_title[$title])) throw new RuntimeException('Missing item: ' . $title);
if (!in_array('--apply', $argv, true)) exit("Use --apply to hide Domain/Pages (without deletion) and promote About Us.\n");
$about = $by_title['about us'];
add_option('rackmonk_menu_visibility_backup', ['items' => array_map(static function ($item) {
    return ['id' => $item->ID, 'parent' => $item->menu_item_parent, 'order' => $item->menu_order, 'hidden' => get_post_meta($item->ID, '_rackmonk_menu_hidden', true)];
}, $items)], '', false);
foreach (['domain', 'pages'] as $title) update_post_meta($by_title[$title]->ID, '_rackmonk_menu_hidden', '1');
$result = wp_update_nav_menu_item($menu->term_id, $about->ID, [
    'menu-item-title' => $about->title, 'menu-item-object' => $about->object,
    'menu-item-object-id' => $about->object_id, 'menu-item-type' => $about->type,
    'menu-item-parent-id' => 0, 'menu-item-position' => $by_title['pages']->menu_order,
    'menu-item-status' => 'publish', 'menu-item-classes' => implode(' ', $about->classes),
]);
if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
update_post_meta($about->ID, '_rackmonk_menu_hidden', '0');
$after = wp_get_nav_menu_items($menu->term_id) ?: [];
if (count($after) !== count($items)) throw new RuntimeException('Unexpected menu record count change.');
$visible = (new Rackmonk_Site_Content())->visible_menu_items($after);
$top = array_values(array_map(static function ($item) { return $item->title; }, array_filter($visible, static function ($item) { return !(int) $item->menu_item_parent; })));
if ($top !== ['Home', 'Hosting', 'About Us', 'Contact Us', 'Login']) throw new RuntimeException('Unexpected visible menu: ' . wp_json_encode($top));
foreach (['domain', 'pages'] as $title) if (!get_post($by_title[$title]->ID)) throw new RuntimeException('Hidden item missing.');
if (class_exists('\\Elementor\\Plugin')) \Elementor\Plugin::$instance->files_manager->clear_cache();
echo 'Visible menu: ' . implode(', ', $top) . "\n";
echo 'Preserved all ' . count($after) . " menu items, including hidden parents and submenus.\nVERDICT: PASS\n";
