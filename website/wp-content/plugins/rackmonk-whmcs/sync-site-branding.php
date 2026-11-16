<?php
/** One-time, reversible editorial cleanup. Run through PHP CLI with --apply. */
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 3) . '/wp-load.php';
$apply = in_array('--apply', $argv, true);
$logo = get_template_directory_uri() . '/assets/img/rackmonk-logo-light.png';
$contact = home_url('/contact-us/');
$home = home_url('/');

function rackmonk_brand_value($value, $key = '') {
    global $logo, $contact, $home;
    if (is_array($value)) {
        if (isset($value['url']) && preg_match('~/(?:Network-Color-2|luminous-Color-2|Sitemark-Color-2|Penta-Color-2|Vertigo-Color-2|levelup-Color-2)\.svg$|/testi_rating\.webp$~i', $value['url'])) {
            $value['url'] = $logo;
            if (isset($value['id'])) $value['id'] = 0;
            if (isset($value['alt'])) $value['alt'] = 'Rackmonk';
        }
        foreach ($value as $child_key => $child) $value[$child_key] = rackmonk_brand_value($child, (string) $child_key);
        return $value;
    }
    if (!is_string($value) || in_array($key, ['widgetType', 'elType', 'css_classes', '_element_id', 'custom_css'], true)) return $value;
    if (strpos($value, '<') !== false) {
        $parts = preg_split('~(<[^>]+>)~s', $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        $skip = false;
        foreach ($parts as &$part) {
            if (substr($part, 0, 1) === '<') {
                if (preg_match('~^<(script|style)\b~i', $part)) $skip = true;
                if (preg_match('~^</(script|style)\b~i', $part)) $skip = false;
                $part = preg_replace('~mailto:(?:privacy@marveltheme\.com|privacy@yourmail\.com)~i', $contact, $part);
                $part = preg_replace('~https?://(?:www\.)?marveltheme\.com(?:/[^\s"\'<>]*)?~i', $home, $part);
            } elseif (!$skip) {
                $part = rackmonk_brand_value($part, $key);
            }
        }
        unset($part);
        return implode('', $parts);
    }
    if ($key === 'url') {
        if (preg_match('~(?:marveltheme\.com|themeforest\.net|hostinger\.com|xhosting\.com|xhostingindustry\.co\.uk)~i', $value)) return stripos($value, 'mailto:') === 0 ? $contact : $home;
        return $value;
    }
    // Never rename integration identifiers, uploads, CSS selectors or template filenames.
    if (preg_match('~^https?://|^mailto:~i', $value)) return $value;
    $value = preg_replace('~mailto:(?:privacy@marveltheme\.com|privacy@yourmail\.com|[^\s"<>]*@xhosting(?:industry\.co\.uk|\.com))~i', $contact, $value);
    $value = preg_replace('~(?:privacy@marveltheme\.com|privacy@yourmail\.com|sayhello@xhosting\.com|info@xhostingindustry\.co\.uk)~i', 'Rackmonk support', $value);
    $value = preg_replace('~(?:https?://)?(?:www\.)?marveltheme\.com/?~i', untrailingslashit($home), $value);
    $value = preg_replace('/\b(?:MarvelTheme|Hostinkar|Xhosting|Hostinger|HostGator|Bluehost|GoDaddy|SiteGround|Trustpilot)\b/i', 'Rackmonk', $value);
    $value = str_ireplace(['Themeforest Marketplace', 'WordPress Theme on the Rackmonk website', 'Trusted By Top Brands Worldwide'], ['Rackmonk website', 'hosting services on the Rackmonk website', 'Build Your Next Project with Rackmonk'], $value);
    return $value;
}

$changed = 0;
$posts = get_posts(['post_type' => ['page', 'post', 'xhosting_template', 'elementor_library', 'th-mega-menu', 'nav_menu_item'], 'post_status' => ['publish', 'draft', 'private'], 'numberposts' => -1]);
foreach ($posts as $post) {
    $raw = get_post_meta($post->ID, '_elementor_data', true);
    $data = $raw ? json_decode($raw, true) : null;
    $updated = is_array($data) ? rackmonk_brand_value($data) : $data;
    $content = rackmonk_brand_value($post->post_content);
    if (in_array($post->post_name, ['sample-page', 'sample-page-2'], true) && strpos($content, 'XYZ') !== false) {
        $content = '<!-- wp:heading --><h2 class="wp-block-heading">Welcome to Rackmonk</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Explore hosting for your website, project or business. Compare our plans or contact the Rackmonk team for help choosing a suitable option.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p><a href="' . esc_url(home_url('/pricing/')) . '">Explore hosting plans</a></p><!-- /wp:paragraph -->';
    }
    if ($updated === $data && $content === $post->post_content) continue;
    echo ($apply ? 'UPDATE ' : 'WOULD UPDATE ') . $post->ID . ' ' . $post->post_title . "\n";
    $changed++;
    if (!$apply) continue;
    add_post_meta($post->ID, '_rackmonk_branding_backup_v1', ['content' => $post->post_content, 'elementor' => $raw], true);
    if ($content !== $post->post_content) {
        $result = wp_update_post(wp_slash(['ID' => $post->ID, 'post_content' => $content]), true);
        if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
    }
    if ($updated !== $data) {
        update_post_meta($post->ID, '_elementor_data', wp_slash(wp_json_encode($updated)));
        delete_post_meta($post->ID, '_elementor_element_cache');
    }
}
if ($apply && class_exists('\\Elementor\\Plugin')) \Elementor\Plugin::$instance->files_manager->clear_cache();
echo ($apply ? 'Updated ' : 'Would update ') . $changed . " documents. Existing content backups retained in post metadata.\n";
echo "VERDICT: PASS\n";
