<?php
/** CLI-only backup and standardization of generated Rackmonk feature-badge sections. */
if (PHP_SAPI !== 'cli') exit;
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require dirname(__DIR__, 4) . '/wp-load.php';
$apply = in_array('--apply', $argv, true);
$names = ['rm-badge-nvme-ssd.svg', 'rm-badge-free-ssl.svg', 'rm-badge-99-9-uptime.svg', 'rm-badge-24-7-support.svg', 'rm-badge-daily-backups.svg', 'rm-badge-ddos-protection.svg', 'rm-badge-litespeed.svg', 'rm-badge-free-migration.svg'];
$registry = Rackmonk_Vectors::registry();
$directory = Rackmonk_Vectors::dir();
$brands = [];
$files = [];
foreach ($names as $name) {
    $spec = $registry[$name] ?? null;
    if (!$spec || ($spec['type'] ?? '') !== 'badge' || empty($spec['attachment'])) throw new RuntimeException('Badge missing: ' . $name);
    $url = wp_get_attachment_url($spec['attachment']);
    if (!$url) throw new RuntimeException('Badge attachment missing: ' . $name);
    $brands[] = ['_id' => substr(md5('rackmonk-feature:' . $name), 0, 7), 'brand_img' => ['url' => $url, 'id' => (int) $spec['attachment'], 'alt' => $spec['opts']['alt'] ?? $spec['opts']['text'] ?? '']];
    $path = $directory['path'] . '/' . $name;
    $before = is_file($path) ? file_get_contents($path) : null;
    $after = Rackmonk_Vectors::render('badge', 280, 80, $spec['opts'], Rackmonk_Design_System::roles());
    if ($before !== $after || $spec['w'] !== 280 || $spec['h'] !== 80) $files[$name] = ['before' => $before, 'spec' => $spec, 'after' => $after];
}
$updates = [];
foreach (get_posts(['post_type' => ['page', 'elementor_library', 'xhosting_template'], 'post_status' => 'publish', 'numberposts' => -1]) as $post) {
    $raw = get_post_meta($post->ID, '_elementor_data', true);
    $data = json_decode($raw, true);
    if (!is_array($data)) continue;
    $count = 0;
    $walk = static function (&$elements) use (&$walk, &$count, $brands) {
        foreach ($elements as &$element) {
            if (($element['widgetType'] ?? '') === 'xhosting-brand-c') {
                $items = $element['settings']['brands'] ?? [];
                $generated = array_filter($items, static function ($item) { return strpos($item['brand_img']['url'] ?? '', '/rackmonk-vectors/rm-badge-') !== false; });
                // Do not turn an unrelated customer-logo list into a feature list.
                if ($items && count($generated) === count($items)) {
                    $settings = &$element['settings'];
                    if (($settings['title'] ?? '') !== 'Build Your Next AI Project with Rackmonk' || $settings['brands'] !== $brands) {
                        $settings['title'] = 'Build Your Next AI Project with Rackmonk';
                        $settings['brands'] = $brands;
                        $count++;
                    }
                    unset($settings);
                }
            }
            if (!empty($element['elements'])) $walk($element['elements']);
        }
    };
    $walk($data);
    if ($count) {
        $updates[$post->ID] = ['before' => $raw, 'after' => wp_json_encode($data)];
        echo $post->post_name . ': ' . $count . " badge section(s), eight badges in canonical order\n";
    }
}
if ($apply && ($updates || $files)) {
    $key = 'rackmonk_feature_badges_backup_' . gmdate('Ymd_His');
    if (!add_option($key, ['pages' => array_map(static function ($row) { return $row['before']; }, $updates), 'files' => $files], '', false)) throw new RuntimeException('Backup failed; nothing changed.');
    foreach ($updates as $id => $row) if (get_post_meta($id, '_elementor_data', true) !== $row['before']) throw new RuntimeException('Concurrent page update: ' . $id);
    foreach ($files as $name => $file) {
        Rackmonk_Vectors::ensure($name, 'badge', 280, 80, $file['spec']['opts']);
        if (file_get_contents($directory['path'] . '/' . $name) !== $file['after']) throw new RuntimeException('Badge verification failed: ' . $name);
    }
    foreach ($updates as $id => $row) {
        update_post_meta($id, '_elementor_data', wp_slash($row['after']));
        if (get_post_meta($id, '_elementor_data', true) !== $row['after']) throw new RuntimeException('Page verification failed: ' . $id);
    }
    \Elementor\Plugin::$instance->files_manager->clear_cache();
    echo 'Backup: ' . $key . "\n";
}
echo count($updates) . ' documents, ' . count($files) . ' SVG files ' . ($apply ? 'applied' : 'dry run') . "\nVERDICT: PASS\n";
