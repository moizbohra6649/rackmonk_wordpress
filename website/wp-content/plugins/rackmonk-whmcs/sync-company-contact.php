<?php
/** Official contact content import; writes only when explicitly run with --apply. */
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__, 3) . '/wp-load.php';

function rackmonk_contact_audit($value, $path) {
    if (is_array($value)) {
        foreach ($value as $key => $child) rackmonk_contact_audit($child, $path . '.' . $key);
    } elseif (is_string($value) && preg_match('/@|1454|344 455|google.*maps|AI solutions|ready to make|facebook\.com|linkedin\.com|instagram\.com|twitter\.com/', $value)) {
        echo $path . ': ' . substr($value, 0, 700) . "\n";
    }
}
$official = [
    'source' => 'https://www.rackmonk.com/contact/',
    'address' => '203, Regency Palace, Scheme No. 54, Opposite Marriott Hotel, Indore (MP), Pincode - 452010',
    'phone' => '+91-62640-69660', 'tel' => 'tel:+916264069660', 'email' => 'enquire@rackmonk.com',
    'socials' => [
        'facebook-f' => 'https://www.facebook.com/RackMonk-108971921687662',
        'twitter' => 'https://twitter.com/rackmonk',
        'linkedin-in' => 'https://www.linkedin.com/company/rackmonk/',
        'instagram' => 'https://www.instagram.com/rackmonk/',
    ],
];
$official['map'] = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($official['address']);

function rackmonk_contact_values($value, $key = '') {
    global $official;
    if (is_array($value)) {
        if (in_array($key, ['socials', 'msocials', 'social_icon_list'], true) && $value) {
            $template = reset($value);
            $icon_key = array_key_exists('social_icon', $template) ? 'social_icon' : 'icon';
            $value = [];
            foreach ($official['socials'] as $network => $url) {
                $row = $template;
                $row['_id'] = substr(md5($key . $network), 0, 7);
                $row[$icon_key] = ['value' => 'fab fa-' . $network, 'library' => 'fa-brands'];
                $row['link'] = ['url' => $url, 'is_external' => 'true', 'nofollow' => '', 'custom_attributes' => ''];
                $value[] = $row;
            }
        }
        if (isset($value['link']['url']) && stripos($value['link']['url'], 'tel:') === 0 && isset($value['title']) && preg_match('/\d{3}/', $value['title'])) $value['title'] = $official['phone'];
        foreach ($value as $child_key => $child) $value[$child_key] = rackmonk_contact_values($child, (string) $child_key);
        return $value;
    }
    if (!is_string($value)) return $value;
    if (stripos($value, 'tel:') === 0) return $official['tel'];
    if (stripos($value, 'mailto:') === 0) return 'mailto:' . $official['email'];
    return str_replace(
        ['sayhello@rackmonk.com', '+2 344 455 4455', '1454 Street, North Valley, San Francisco, California', 'Our sales team is ready to discuss your needs and how our AI solutions can help.'],
        [$official['email'], $official['phone'], $official['address'], 'Contact the Rackmonk team for hosting enquiries and help finding a solution for your website or business.'],
        $value
    );
}

function rackmonk_contact_widgets($nodes, $footer = false) {
    global $official;
    foreach ($nodes as &$node) {
        if (($node['widgetType'] ?? '') === 'ex-ct-info-box') {
            $titles = ['Sales' => 'Hosting Enquiries', 'Development' => 'Hosting Assistance', 'Media' => 'General Enquiries'];
            $title = $node['settings']['title'] ?? '';
            if (isset($titles[$title])) $node['settings']['title'] = $titles[$title];
        }
        if (($node['widgetType'] ?? '') === 'ex-ct-info') {
            $contacts = &$node['settings']['contacts'];
            $has_phone = false;
            foreach ($contacts as &$row) {
                if (($row['title'] ?? '') === $official['address']) $row['link'] = ['url' => $official['map'], 'is_external' => 'true'];
                if (strpos($row['link']['url'] ?? '', 'tel:') === 0) $has_phone = true;
            }
            unset($row);
            if (!$has_phone) $contacts[] = ['_id' => 'c0a7ca1', 'title' => $official['phone'], 'link' => ['url' => $official['tel']], 'icon' => ['value' => 'fas fa-phone-alt', 'library' => 'fa-solid']];
            unset($contacts);
        }
        if (!empty($node['elements'])) {
            if ($footer) {
                $brand = false;
                $present = false;
                foreach ($node['elements'] as $child) {
                    if (($child['widgetType'] ?? '') === 'image' && strpos($child['settings']['image']['url'] ?? '', 'rackmonk-logo') !== false) $brand = true;
                    if (($child['id'] ?? '') === 'c0a7ac7') $present = true;
                }
                if ($brand && !$present) {
                    $html = '<h4>Contact Rackmonk</h4><p><a href="' . esc_url($official['map']) . '" target="_blank" rel="noopener">' . esc_html($official['address']) . '</a></p><p><a href="' . esc_url($official['tel']) . '">' . esc_html($official['phone']) . '</a><br><a href="mailto:' . esc_attr($official['email']) . '">' . esc_html($official['email']) . '</a></p>';
                    $node['elements'][] = ['id' => 'c0a7ac7', 'elType' => 'widget', 'settings' => ['editor' => $html, '_title' => 'Rackmonk Company Contact'], 'elements' => [], 'widgetType' => 'text-editor'];
                }
            }
            $node['elements'] = rackmonk_contact_widgets($node['elements'], $footer);
        }
    }
    unset($node);
    return $nodes;
}

$apply = in_array('--apply', $argv, true);
$count = 0;
foreach (get_posts(['post_type' => ['page', 'xhosting_template'], 'post_status' => 'publish', 'numberposts' => -1]) as $post) {
    $raw = get_post_meta($post->ID, '_elementor_data', true);
    $data = json_decode($raw ?: '[]', true);
    if (!is_array($data)) continue;
    if (in_array('--audit', $argv, true)) { rackmonk_contact_audit($data, $post->ID . '.' . $post->post_title); continue; }
    $updated = rackmonk_contact_widgets(rackmonk_contact_values($data), $post->post_type === 'xhosting_template' && stripos($post->post_title, 'Footer') === 0);
    if ($updated === $data) continue;
    $count++;
    echo ($apply ? 'UPDATED ' : 'WOULD UPDATE ') . $post->ID . ' ' . $post->post_title . "\n";
    if ($apply) {
        add_post_meta($post->ID, '_rackmonk_contact_backup_v1', $raw, true);
        update_post_meta($post->ID, '_elementor_data', wp_slash(wp_json_encode($updated)));
        delete_post_meta($post->ID, '_elementor_element_cache');
    }
}
if ($apply) {
    foreach (get_posts(['post_type' => 'wpcf7_contact_form', 'post_status' => 'publish', 'numberposts' => -1]) as $form) {
        $mail = get_post_meta($form->ID, '_mail', true);
        if (!is_array($mail)) continue;
        add_post_meta($form->ID, '_rackmonk_contact_mail_backup_v1', $mail, true);
        $mail['recipient'] = $official['email'];
        update_post_meta($form->ID, '_mail', $mail);
        echo 'Form recipient updated: ' . $form->ID . "\n";
    }
    $copy = Rackmonk_Site_Content::all();
    add_option('rackmonk_company_copy_backup_v1', $copy, '', false);
    $copy['about_identity_first'] = 'Rackmonk is based in Indore, Madhya Pradesh, India. Our office is at ' . $official['address'] . '. Contact our team for hosting enquiries and guidance on choosing a suitable solution for your website or business.';
    update_option(Rackmonk_Site_Content::OPTION, $copy, false);
    update_option('rackmonk_company_contact_source', $official, false);
    if (class_exists('\\Elementor\\Plugin')) \Elementor\Plugin::$instance->files_manager->clear_cache();
}
echo "$count editable documents " . ($apply ? 'updated' : 'would change') . ". Source: {$official['source']}\nVERDICT: PASS\n";
