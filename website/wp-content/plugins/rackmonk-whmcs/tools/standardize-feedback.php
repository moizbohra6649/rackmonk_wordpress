<?php
/** CLI-only, backed-up conversion of page feedback sections to the Migration Elementor layout. */
if (PHP_SAPI !== 'cli') exit;
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require dirname(__DIR__, 4) . '/wp-load.php';
$apply = in_array('--apply', $argv, true);
$widgets = static function ($elements) use (&$widgets) {
    $found = [];
    foreach ($elements as $element) {
        if (isset($element['widgetType'])) $found[] = $element;
        if (!empty($element['elements'])) $found = array_merge($found, $widgets($element['elements']));
    }
    return $found;
};
$source = get_page_by_path('migration');
if (!$source) throw new RuntimeException('Migration reference page missing.');
$source_data = json_decode(get_post_meta($source->ID, '_elementor_data', true), true);
$reference = null;
foreach ($source_data as $root) {
    foreach ($widgets([$root]) as $widget) {
        if ($widget['widgetType'] === 'go-testimonial' && ($widget['settings']['style'] ?? '') === '5') $reference = $root;
    }
}
if (!$reference) throw new RuntimeException('Migration feedback layout missing.');
$reference_widgets = $widgets([$reference]);
$reference_review = null;
foreach ($reference_widgets as $widget) if ($widget['widgetType'] === 'go-testimonial') $reference_review = $widget['settings']['testimonials'][0];
if (!$reference_review) throw new RuntimeException('Reference review settings missing.');
$updates = [];
foreach (get_posts(['post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1]) as $post) {
    if ($post->ID === $source->ID) continue;
    $raw = get_post_meta($post->ID, '_elementor_data', true);
    $data = json_decode($raw, true);
    if (!is_array($data)) continue;
    $indices = [];
    $reviews = [];
    $non_slider = false;
    foreach ($data as $index => $root) {
        $root_widgets = $widgets([$root]);
        $testimonials = array_values(array_filter($root_widgets, static function ($w) { return $w['widgetType'] === 'go-testimonial'; }));
        if (!$testimonials) continue;
        // Never replace a root that also owns an unrelated page component.
        foreach ($root_widgets as $widget) if (!in_array($widget['widgetType'], ['go-testimonial', 'xhosting-heading', 'xhosting-rating', 'xhosting-btn'], true)) throw new RuntimeException('Mixed feedback section on page ' . $post->ID);
        $indices[] = $index;
        foreach ($testimonials as $widget) {
            $settings = $widget['settings'];
            $style = (string) ($settings['style'] ?? '1');
            if ($style !== '5') $non_slider = true;
            $items = in_array($style, ['3', '5'], true) ? ($settings['testimonials'] ?? []) : [$settings];
            foreach ($items as $item) {
                $review = $reference_review;
                // Keep the original testimonial wording, attribution, rating image and portrait metadata.
                foreach (['feedback', 'name', 'designation', 'authore', 'rating_logo', 'quote'] as $field) {
                    if (array_key_exists($field, $item)) $review[$field] = $item[$field];
                }
                if (isset($item['rating_img']['url']) && $item['rating_img']['url'] !== '') {
                    // Static cards store a single-star image, not the full rating strip required by style 5.
                    $review['rating_logo'] = $reference_review['rating_logo'];
                }
                $review['_id'] = substr(md5($post->ID . ':review:' . count($reviews)), 0, 7);
                $reviews[] = $review;
            }
        }
    }
    if (!$indices || !$non_slider) continue;
    if (!$reviews) throw new RuntimeException('No reviews to preserve on page ' . $post->ID);
    $first = min($indices);
    if ($first > 0) {
        $previous = $widgets([$data[$first - 1]]);
        if (count($previous) === 1 && $previous[0]['widgetType'] === 'xhosting-heading') $indices[] = --$first;
    }
    foreach ($indices as $index) {
        $next = $index + 1;
        if (!isset($data[$next]) || in_array($next, $indices, true)) continue;
        $next_widgets = $widgets([$data[$next]]);
        if (count($next_widgets) === 1 && $next_widgets[0]['widgetType'] === 'xhosting-btn' && stripos($next_widgets[0]['settings']['btn_label'] ?? '', 'Read All Reviews') !== false) $indices[] = $next;
    }
    $replacement = $reference;
    $rewrite = static function (&$elements) use (&$rewrite, $post, $reviews) {
        foreach ($elements as &$element) {
            $element['id'] = substr(md5($post->ID . ':feedback:' . $element['id']), 0, 7);
            if (($element['widgetType'] ?? '') === 'go-testimonial') $element['settings']['testimonials'] = $reviews;
            if (!empty($element['elements'])) $rewrite($element['elements']);
        }
    };
    $replacement_list = [$replacement];
    $rewrite($replacement_list);
    $replacement = $replacement_list[0];
    $replacement['settings']['css_classes'] = trim(($replacement['settings']['css_classes'] ?? '') . ' rm-feedback-standardized');
    $after = [];
    foreach ($data as $index => $root) {
        if ($index === $first) $after[] = $replacement;
        if (!in_array($index, $indices, true)) $after[] = $root;
    }
    $after_reviews = [];
    foreach ($widgets($after) as $widget) if ($widget['widgetType'] === 'go-testimonial') {
        if (($widget['settings']['style'] ?? '') !== '5') throw new RuntimeException('A non-slider testimonial remains.');
        $after_reviews = array_merge($after_reviews, $widget['settings']['testimonials']);
    }
    if (count($after_reviews) !== count($reviews)) throw new RuntimeException('Review count changed unexpectedly.');
    foreach ($reviews as $i => $review) foreach (['feedback', 'name', 'designation'] as $field) {
        if (($review[$field] ?? '') !== ($after_reviews[$i][$field] ?? '')) throw new RuntimeException('Review content changed unexpectedly.');
    }
    $updates[$post->ID] = ['before' => $raw, 'after' => wp_json_encode($after), 'slug' => $post->post_name, 'reviews' => count($reviews)];
    echo $post->post_name . ': ' . count($reviews) . " reviews preserved, Migration layout\n";
}
if ($apply && $updates) {
    $key = 'rackmonk_feedback_layout_backup_' . gmdate('Ymd_His');
    if (!add_option($key, array_map(static function ($row) { return $row['before']; }, $updates), '', false)) throw new RuntimeException('Backup failed; nothing saved.');
    foreach ($updates as $id => $row) {
        if (get_post_meta($id, '_elementor_data', true) !== $row['before']) throw new RuntimeException('Concurrent change on page ' . $id);
    }
    foreach ($updates as $id => $row) {
        update_post_meta($id, '_elementor_data', wp_slash($row['after']));
        if (get_post_meta($id, '_elementor_data', true) !== $row['after']) throw new RuntimeException('Could not verify saved page ' . $id);
    }
    \Elementor\Plugin::$instance->files_manager->clear_cache();
    echo 'Backup: ' . $key . "\n";
}
echo count($updates) . ' pages ' . ($apply ? 'applied' : 'dry run') . "\nVERDICT: PASS\n";
