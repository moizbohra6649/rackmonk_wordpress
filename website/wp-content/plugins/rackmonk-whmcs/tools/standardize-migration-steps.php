<?php
/** CLI-only, backed-up layout migration for the existing three-step website migration process. */
if (PHP_SAPI !== 'cli') exit;
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require dirname(__DIR__, 4) . '/wp-load.php';
$apply = in_array('--apply', $argv, true);
$titles = ['Backup & Prepare', 'Transfer & Configure', 'Test & Launch'];
$flatten = static function ($elements) use (&$flatten) {
    $widgets = [];
    foreach ($elements as $element) {
        if (isset($element['widgetType'])) $widgets[] = $element;
        if (!empty($element['elements'])) $widgets = array_merge($widgets, $flatten($element['elements']));
    }
    return $widgets;
};
$updates = [];
foreach (get_posts(['post_type' => ['page', 'elementor_library'], 'post_status' => 'publish', 'numberposts' => -1]) as $post) {
    $raw = get_post_meta($post->ID, '_elementor_data', true);
    $data = json_decode($raw, true);
    if (!is_array($data)) continue;
    $all = $flatten($data);
    $steps = [];
    $heading = null;
    foreach ($all as $widget) {
        $title = trim(wp_strip_all_tags($widget['settings']['title'] ?? ''));
        if ($widget['widgetType'] === 'agnr-infobox-item' && in_array($title, $titles, true)) {
            if (isset($steps[$title])) throw new RuntimeException('Ambiguous process steps on ' . $post->ID);
            $steps[$title] = $widget;
        }
        if ($widget['widgetType'] === 'xhosting-heading' && $title === 'Seamless website migration with ease.') $heading = $widget;
    }
    if (!$steps) continue;
    if (count($steps) !== 3 || !$heading) throw new RuntimeException('Incomplete migration section on ' . $post->ID);
    if (count(array_filter($steps, static function ($w) { return ($w['settings']['style'] ?? '') === '11'; })) === 3 && strpos($raw, 'rm-migration-section') !== false) continue;
    $target_ids = array_merge([$heading['id']], array_column(array_values($steps), 'id'));
    $count_targets = static function ($element) use ($flatten, $target_ids) {
        return count(array_filter($flatten([$element]), static function ($w) use ($target_ids) { return in_array($w['id'], $target_ids, true); }));
    };
    $safe = static function ($elements) use ($flatten, $target_ids) {
        foreach ($flatten($elements) as $w) if (!in_array($w['id'], $target_ids, true) && $w['widgetType'] !== 'xhosting-btn') return false;
        return true;
    };
    $build = static function ($original) use ($post, $flatten, $heading, $steps, $titles) {
        $id = static function ($suffix) use ($post) { return substr(md5($post->ID . ':migration-process:' . $suffix), 0, 7); };
        $container = static function ($suffix, $class, $children) use ($id) {
            return ['id' => $id($suffix), 'elType' => 'container', 'settings' => ['content_width' => 'full', 'flex_direction' => 'column', 'css_classes' => $class], 'elements' => $children, 'isInner' => false];
        };
        $heading['settings'] = ['style' => '2', 'title_tag' => 'h2', 'title' => $heading['settings']['title'], 'description' => $heading['settings']['description'] ?? ''];
        $cards = [];
        foreach ($titles as $index => $title) {
            $card = $steps[$title];
            $card['settings'] = ['style' => '11', 'rackmonk_step_number' => sprintf('%02d', $index + 1), 'title' => $card['settings']['title'], 'description' => $card['settings']['description'] ?? ''];
            $cards[] = $card;
        }
        $children = [$heading, $container('cards', 'rm-migration-steps', $cards)];
        $buttons = array_values(array_filter($flatten($original), static function ($w) { return $w['widgetType'] === 'xhosting-btn'; }));
        if ($buttons) $children[] = $container('actions', 'rm-migration-actions', $buttons);
        return $container('section', 'rm-migration-section', $children);
    };
    $changed = false;
    $rewrite = static function (&$elements) use (&$rewrite, &$changed, $count_targets, $safe, $build) {
        foreach ($elements as &$element) {
            if ($count_targets($element) === 4) {
                if ($safe([$element])) { $element = $build([$element]); $changed = true; return; }
                if (empty($element['elements'])) throw new RuntimeException('Invalid process container.');
                $rewrite($element['elements']);
                return;
            }
        }
        unset($element);
        $indices = [];
        foreach ($elements as $index => $element) if ($count_targets($element) > 0) $indices[] = $index;
        $selected = array_map(static function ($i) use ($elements) { return $elements[$i]; }, $indices);
        if (!$indices || !$safe($selected)) throw new RuntimeException('Cannot isolate process without affecting other content.');
        $replacement = $build($selected);
        $after = [];
        foreach ($elements as $index => $element) {
            if ($index === min($indices)) $after[] = $replacement;
            if (!in_array($index, $indices, true)) $after[] = $element;
        }
        $elements = $after;
        $changed = true;
    };
    $rewrite($data);
    if (!$changed) throw new RuntimeException('Could not update process.');
    $after_widgets = [];
    foreach ($flatten($data) as $widget) $after_widgets[$widget['id']] = $widget;
    foreach ($all as $widget) {
        if (!isset($after_widgets[$widget['id']])) throw new RuntimeException('Widget removed: ' . $widget['id']);
        $after_widget = $after_widgets[$widget['id']];
        if (!in_array($widget['id'], $target_ids, true) && $after_widget !== $widget) throw new RuntimeException('Unrelated widget changed.');
        if (in_array($widget['id'], $target_ids, true)) foreach (['title', 'description'] as $field) if (($widget['settings'][$field] ?? '') !== ($after_widget['settings'][$field] ?? '')) throw new RuntimeException('Migration copy changed.');
    }
    $updates[$post->ID] = ['before' => $raw, 'after' => wp_json_encode($data)];
    echo $post->post_name . ": three editable cards; original wording, buttons and other widgets preserved\n";
}
if ($apply && $updates) {
    $key = 'rackmonk_migration_steps_backup_' . gmdate('Ymd_His');
    if (!add_option($key, array_map(static function ($row) { return $row['before']; }, $updates), '', false)) throw new RuntimeException('Backup failed.');
    foreach ($updates as $id => $row) if (get_post_meta($id, '_elementor_data', true) !== $row['before']) throw new RuntimeException('Concurrent change: ' . $id);
    foreach ($updates as $id => $row) {
        update_post_meta($id, '_elementor_data', wp_slash($row['after']));
        if (get_post_meta($id, '_elementor_data', true) !== $row['after']) throw new RuntimeException('Save verification failed: ' . $id);
    }
    \Elementor\Plugin::$instance->files_manager->clear_cache();
    echo 'Backup: ' . $key . "\n";
}
echo count($updates) . ' documents ' . ($apply ? 'applied' : 'dry run') . "\nVERDICT: PASS\n";
