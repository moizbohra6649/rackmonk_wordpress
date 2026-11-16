<?php
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 4) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/template.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$checks = 0;
function content_check($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
    echo "PASS: $message\n";
}
try {
    $editor = new Rackmonk_Site_Content();
    $defaults = Rackmonk_Site_Content::defaults();
    content_check(count($defaults) >= 80, 'About, banner, pricing, configurator and checkout copy registered');
    $override = static function () { return ['about_title' => '<script>test</script>', 'cart_title' => 'Custom cart', 'commerce_cart_title' => 'Custom checkout cart', 'about_use_editor' => '1', 'keep_footer_design' => '0']; };
    add_filter('pre_option_' . Rackmonk_Site_Content::OPTION, $override);
    content_check(Rackmonk_Site_Content::text('cart_title', 'fallback') === 'Custom cart', 'Saved copy overrides defaults');
    content_check(Rackmonk_Site_Content::text('missing', 'fallback') === 'fallback', 'Unconfigured copy keeps defaults');
    $sanitized = $editor->sanitize(['about_title' => '<b>Updated heading</b>', 'about_cta_url' => 'javascript:alert(1)', 'cart_title' => ['invalid'], 'about_use_editor' => 1]);
    content_check($sanitized['about_title'] === 'Updated heading', 'Plain-text fields strip markup');
    content_check($sanitized['about_cta_url'] === '', 'Unsafe destination schemes rejected');
    content_check($sanitized['cart_title'] === 'Custom cart', 'Malformed fields preserve saved value');
    content_check($sanitized['about_use_editor'] === '1' && $sanitized['show_domain_search'] === '0', 'Editorial toggles sanitized');
    $classes = apply_filters('body_class', []);
    content_check(!in_array('rm-footer-design', $classes, true), 'Footer styling can be released to Elementor');
    $admins = get_users(['role' => 'administrator', 'number' => 1]);
    content_check(!empty($admins), 'Administrator available for editor rendering checks');
    wp_set_current_user($admins[0]->ID);
    $editor->settings();
    ob_start(); $editor->page(); $html = ob_get_clean();
    foreach ($defaults as $key => $value) content_check(strpos($html, 'name="' . Rackmonk_Site_Content::OPTION . '[' . $key . ']"') !== false, 'Admin field for ' . $key);
    content_check(strpos($html, '&lt;script&gt;test&lt;/script&gt;') !== false && strpos($html, '<script>test</script>') === false, 'Editor escapes stored text');
    content_check(strpos($html, 'action="options.php"') !== false && strpos($html, '_wpnonce') !== false, 'Settings form uses WordPress nonce-protected saving');
    ob_start(); $editor->plan_page(); $plans = ob_get_clean();
    content_check(strpos($plans, 'Starter Shared') !== false && strpos($plans, 'name="version"') !== false && strpos($plans, '_wpnonce') !== false, 'Real plan copy editor has nonce and concurrency version');
    remove_filter('pre_option_' . Rackmonk_Site_Content::OPTION, $override);
    $pdo = Rackmonk_Plans_Manager::get_instance()->get_whmcs_db();
    $version_row = $pdo->query("SELECT id, name, tagline, description, is_featured FROM tblproducts WHERE paytype != 'onetime' ORDER BY id LIMIT 1")->fetch();
    content_check((bool) $version_row, 'Plan content source is a real recurring catalog product');
    $cart = new Rackmonk_Website_Cart();
    $html = $cart->render();
    content_check(strpos($html, '"content":') !== false && strpos($html, 'cart_eyebrow') !== false, 'Configurator receives editable copy JSON');
    content_check(strpos($html, 'Instant Provisioning') === false && strpos($html, '30-Day Money-Back Guarantee') === false, 'Unsupported default marketing promises removed');
    ob_start(); require dirname(__DIR__) . '/sync-site-branding.php'; ob_end_clean();
    $fixed = rackmonk_brand_value('<a class="xhosting-link" href="https://marveltheme.com/xhosting/documentation/">MarvelTheme</a>');
    content_check(strpos($fixed, 'href="' . home_url('/') . '"') !== false && strpos($fixed, '/xhosting/documentation/') === false, 'Vendor HTML links replace the complete destination');
    content_check(strpos($fixed, 'class="xhosting-link"') !== false && strpos($fixed, '>Rackmonk</a>') !== false, 'Brand cleanup preserves technical attributes');
    echo "$checks checks passed. No persisted settings or plan prices changed.\nVERDICT: PASS\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'VERDICT: FAIL -- ' . $error->getMessage() . "\n");
    exit(1);
}
