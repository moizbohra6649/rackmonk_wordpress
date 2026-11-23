<?php
/** Replace imported demo banner amounts with editable, currency-aware tokens. CLI only. */
if (PHP_SAPI !== 'cli') exit;
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require dirname(__DIR__, 4) . '/wp-load.php';
$apply = in_array('--apply', $argv, true);
$groups = ['home-2' => '', 'web-hosting' => 'shared', 'wp-hosting' => 'wordpress', 'cloud-hosting' => 'cloud', 'vps-hosting' => 'vps', 'shared-hosting-2' => 'shared'];
$updates = [];
foreach ($groups as $slug => $group) {
    $post = get_page_by_path($slug);
    if (!$post) continue;
    $raw = get_post_meta($post->ID, '_elementor_data', true);
    $data = json_decode($raw, true);
    if (!is_array($data)) throw new RuntimeException('Invalid Elementor data: ' . $slug);
    $count = 0;
    $walk = static function (&$elements) use (&$walk, &$count, $group) {
        foreach ($elements as &$element) {
            if (($element['widgetType'] ?? '') === 'xhosting-banner') {
                foreach (['subtitle', 'regular_price', 'discount_price'] as $field) {
                    $value = $element['settings'][$field] ?? null;
                    if (!is_string($value)) continue;
                    $kind = $field === 'discount_price' ? 'monthly_price' : 'from_price';
                    $token = '{' . $kind . ($group !== '' ? ':' . $group : '') . '}';
                    $new = preg_replace('/\$(?:4\.50|2\.99|7\.99)(?=\s*\/mo\b)/', $token, $value);
                    if ($new !== $value) { $element['settings'][$field] = $new; $count++; }
                }
            }
            if (!empty($element['elements'])) $walk($element['elements']);
        }
    };
    $walk($data);
    if ($count) $updates[$post->ID] = ['before' => $raw, 'after' => wp_json_encode($data), 'count' => $count];
    echo $slug . ': ' . $count . " fields\n";
}
if ($apply && $updates) {
    $key = 'rackmonk_currency_tokens_backup_' . gmdate('Ymd_His');
    if (!add_option($key, array_map(static function ($row) { return $row['before']; }, $updates), '', false)) throw new RuntimeException('Could not back up banner data.');
    foreach ($updates as $id => $row) {
        if (get_post_meta($id, '_elementor_data', true) !== $row['before']) throw new RuntimeException('Page changed concurrently: ' . $id);
        update_post_meta($id, '_elementor_data', wp_slash($row['after']));
    }
    \Elementor\Plugin::$instance->files_manager->clear_cache();
    echo 'Backup: ' . $key . "\n";
}
echo count($updates) . ' pages ' . ($apply ? 'applied' : 'dry run') . "\nVERDICT: PASS\n";
