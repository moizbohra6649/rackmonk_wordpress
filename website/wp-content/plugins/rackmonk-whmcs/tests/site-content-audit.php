<?php
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 4) . '/wp-load.php';
global $wpdb;
$posts = get_posts(['post_type' => ['page', 'post', 'xhosting_template', 'elementor_library', 'th-mega-menu'], 'post_status' => 'publish', 'numberposts' => -1]);
function audit_values($value, $path) {
    if (is_array($value)) {
        foreach ($value as $key => $child) audit_values($child, $path . '.' . $key);
    } elseif (is_string($value) && preg_match('/hostinkar|xhosting|hostinger|hostgator|bluehost|godaddy|siteground|themegenix|envato|hostin\b/i', $value)) {
        if (strlen($value) < 1500) echo $path . ': ' . $value . "\n";
    }
}
if (in_array('--public', $argv, true) || in_array('--verify', $argv, true)) {
    $verify = in_array('--verify', $argv, true);
    $failures = [];
    $count = 0;
    foreach (get_posts(['post_type' => ['page', 'post'], 'post_status' => 'publish', 'numberposts' => -1]) as $page) {
        $url = get_permalink($page);
        $response = wp_remote_get($url, ['timeout' => 20]);
        if (is_wp_error($response)) { $failures[] = "HTTP ERROR $url " . $response->get_error_message(); continue; }
        $dom = new DOMDocument();
        @$dom->loadHTML(wp_remote_retrieve_body($response));
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//script|//style') as $node) $node->parentNode->removeChild($node);
        $text = preg_replace('/\s+/', ' ', $dom->textContent);
        $count++;
        echo "PUBLIC {$page->ID} $url STATUS " . wp_remote_retrieve_response_code($response) . "\n";
        if (!$verify) echo $text . "\n";
        if (wp_remote_retrieve_response_code($response) !== 200) $failures[] = 'Non-200 page ' . $url;
        if (preg_match('/\b(?:MarvelTheme|Hostinkar|Xhosting|Hostinger|HostGator|Bluehost|GoDaddy|SiteGround|Trustpilot|Themeforest|XYZ Doohickey)\b|privacy@yourmail\.com|Fatal error|Parse error/i', $text, $match)) $failures[] = $url . ': ' . $match[0];
        foreach ($xpath->query('//img[@src]') as $image) {
            if (preg_match('~/(?:Network-Color-2|luminous-Color-2|Sitemark-Color-2|Penta-Color-2|Vertigo-Color-2|levelup-Color-2)\.svg|/testi_rating\.webp~i', $image->getAttribute('src'))) $failures[] = $url . ': demo brand image ' . $image->getAttribute('src');
        }
    }
    echo "Checked $count published page/post URLs.\n";
    if ($failures) { echo 'VERDICT: FAIL -- ' . implode("\n", $failures) . "\n"; exit(1); }
    echo "VERDICT: PASS\n";
    exit;
}
foreach ($posts as $post) {
    echo "POST {$post->ID} {$post->post_type} {$post->post_title} TEMPLATE " . get_post_meta($post->ID, '_wp_page_template', true) . "\n";
    $data = get_post_meta($post->ID, '_elementor_data', true);
    audit_values(json_decode($data ?: '[]', true), (string) $post->ID);
    audit_values($post->post_content, $post->ID . '.content');
}
foreach (['hostinkar', 'xhosting_theme_options', 'widget_text', 'widget_custom_html', Rackmonk_Hosting_Cards::OPTION] as $option) {
    echo 'OPTION ' . $option . "\n";
    audit_values(get_option($option), $option);
}
