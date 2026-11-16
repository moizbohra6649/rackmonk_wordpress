<?php
require_once __DIR__ . '/wp-load.php';

echo "Blogname: " . get_option('blogname') . "\n";
echo "Blog description: " . get_option('blogdescription') . "\n";

$opt = get_option('xhosting_theme_options');
echo "Theme options keys:\n";
print_r(array_keys($opt ?: []));
if (!empty($opt['whmcs_portal_url'])) {
    echo "WHMCS URL: " . $opt['whmcs_portal_url'] . "\n";
}
if (!empty($opt['copyright_text'])) {
    echo "Copyright text: " . $opt['copyright_text'] . "\n";
}

