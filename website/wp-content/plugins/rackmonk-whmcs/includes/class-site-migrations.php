<?php
/**
 * One-time website updates that live in the database (footer layout and links, legal pages, ...).
 *
 * Uploading plugin/theme files does not change the database, so each step here runs once, automatically,
 * the next time an administrator opens wp-admin. Finished steps are recorded in the "rackmonk_site_migrations"
 * option and never run again. Everything a step replaces is first saved in "rackmonk_site_migrations_backup".
 */
if (!defined('ABSPATH')) exit;

final class Rackmonk_Site_Migrations {
    const DONE = 'rackmonk_site_migrations';
    const BACKUP = 'rackmonk_site_migrations_backup';
    const NOTICE = 'rackmonk_site_migrations_notice';
    const LOCK = 'rackmonk_site_migrations_lock';

    public static function boot() {
        add_action('admin_init', [__CLASS__, 'run'], 20); // After the Footer Options first-visit fill (priority 10).
        add_action('admin_notices', [__CLASS__, 'notice']);
    }

    /** Step key => callback. Order matters: the footer links point at the legal pages. */
    private static function steps() {
        return [
            '2026-11-24-legal-pages' => [__CLASS__, 'legal_pages'],
            '2026-11-24-footer' => [__CLASS__, 'footer'],
            '2026-11-24-hello-dolly' => [__CLASS__, 'hello_dolly'],
            '2026-11-25-remove-data-centers' => [__CLASS__, 'remove_data_centers'],
            '2026-11-25-home-migration-box' => [__CLASS__, 'home_migration_box'],
            '2026-11-25-elementor-cache' => [__CLASS__, 'clear_elementor_cache'],
        ];
    }

    public static function run() {
        if (!current_user_can('manage_options') || wp_doing_ajax() || wp_doing_cron()) return;
        $done = get_option(self::DONE, []);
        $done = is_array($done) ? $done : [];
        $pending = array_diff_key(self::steps(), $done);
        if (!$pending || get_transient(self::LOCK)) return;
        set_transient(self::LOCK, 1, 5 * MINUTE_IN_SECONDS);
        $report = [];
        foreach ($pending as $key => $step) {
            try {
                $result = call_user_func($step);
            } catch (Throwable $error) {
                $result = new WP_Error('rackmonk_migration', $error->getMessage());
            }
            if ($result === null) continue; // Not possible yet (e.g. Elementor not loaded); tried again next time.
            if (is_wp_error($result)) { $report[] = ['error', $result->get_error_message()]; continue; }
            $done[$key] = gmdate('c');
            $report[] = ['ok', (string) $result];
        }
        update_option(self::DONE, $done, false);
        delete_transient(self::LOCK);
        if ($report) update_option(self::NOTICE, $report, false);
    }

    public static function notice() {
        $report = get_option(self::NOTICE);
        if (!$report || !current_user_can('manage_options')) return;
        delete_option(self::NOTICE);
        $ok = array_filter($report, static function ($line) { return $line[0] === 'ok'; });
        echo '<div class="notice ' . (count($ok) === count($report) ? 'notice-success' : 'notice-warning') . ' is-dismissible"><p><strong>Rackmonk website update applied.</strong></p><ul style="list-style:disc;margin-left:20px">';
        foreach ($report as [$type, $text]) echo '<li>' . ($type === 'ok' ? '' : '<strong>Not done yet:</strong> ') . esc_html($text) . '</li>';
        echo '</ul></div>';
    }

    private static function backup($key, $value) {
        $all = get_option(self::BACKUP, []);
        $all = is_array($all) ? $all : [];
        if (!isset($all[$key])) $all[$key] = ['saved' => gmdate('c'), 'value' => $value];
        update_option(self::BACKUP, $all, false);
    }

    private static function relative_url($id) {
        return '/' . trim(get_page_uri($id), '/') . '/';
    }

    /* ----------------------------------------------------------- legal pages */

    private static function find_page(array $slugs, array $statuses = ['publish']) {
        foreach ($slugs as $slug) {
            $page = get_page_by_path($slug, OBJECT, 'page');
            if ($page && in_array($page->post_status, $statuses, true)) return $page;
        }
        return null;
    }

    /** Terms & Conditions, Refund Policy and Privacy Policy on the "Legal Page" template; Privacy at /privacy-policy/. */
    public static function legal_pages() {
        $dir = RACKMONK_WHMCS_DIR . 'data/legal/';
        foreach (['terms', 'refund', 'privacy'] as $file) if (!is_readable($dir . $file . '.html')) return new WP_Error('rackmonk_legal', 'Legal page text file is missing: data/legal/' . $file . '.html');
        if (!file_exists(get_template_directory() . '/page-rackmonk-legal.php')) return new WP_Error('rackmonk_legal', 'Upload the theme file page-rackmonk-legal.php first.');

        $privacy_id = (int) get_option('wp_page_for_privacy_policy');
        $privacy = self::find_page(['privacy-policy-2', 'privacy-policy']);
        if (!$privacy && $privacy_id && get_post_status($privacy_id) === 'publish') $privacy = get_post($privacy_id);
        // WordPress's unused default "Privacy Policy" draft holds the /privacy-policy/ address: move it to the Trash.
        $draft = get_page_by_path('privacy-policy', OBJECT, 'page');
        if ($draft && $draft->post_status === 'draft' && (!$privacy || $draft->ID !== $privacy->ID)) wp_trash_post($draft->ID);

        $pages = [
            'terms' => ['page' => self::find_page(['terms-conditions', 'terms-and-conditions', 'terms']), 'title' => 'Terms & Conditions', 'slug' => 'terms-conditions', 'order' => 1],
            'refund' => ['page' => self::find_page(['refund-policy'], ['publish', 'draft', 'pending', 'private']), 'title' => 'Refund Policy', 'slug' => 'refund-policy', 'order' => 2],
            'privacy' => ['page' => $privacy, 'title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'order' => 3],
        ];
        $home = untrailingslashit(home_url());
        $saved = [];
        foreach ($pages as $key => $info) {
            $old = $info['page'];
            if ($old) $saved[$key] = ['post' => get_post($old->ID, ARRAY_A), 'meta' => get_post_meta($old->ID)];
            $content = str_replace('href="/', 'href="' . $home . '/', trim((string) file_get_contents($dir . $key . '.html')));
            $data = ['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $info['title'], 'post_name' => $info['slug'], 'menu_order' => $info['order'], 'post_content' => $content];
            if ($old) $data['ID'] = $old->ID;
            $id = $old ? wp_update_post(wp_slash($data), true) : wp_insert_post(wp_slash($data), true);
            if (is_wp_error($id)) return $id;
            update_post_meta($id, '_wp_page_template', 'page-rackmonk-legal.php');
            foreach (['_elementor_edit_mode', '_elementor_data', '_elementor_page_settings', '_elementor_element_cache', '_elementor_css', '_elementor_page_assets', '_elementor_template_type', '_elementor_version', '_elementor_pro_version'] as $meta) delete_post_meta($id, $meta);
            $pages[$key]['id'] = (int) $id;
        }
        self::backup('legal-pages', $saved);
        update_option('wp_page_for_privacy_policy', $pages['privacy']['id']);
        if (function_exists('wc_get_page_id')) update_option('woocommerce_terms_page_id', $pages['terms']['id']);
        return sprintf('Legal pages ready: %s, %s and %s (the old versions are saved).', get_permalink($pages['terms']['id']), get_permalink($pages['refund']['id']), get_permalink($pages['privacy']['id']));
    }

    /* ---------------------------------------------------------------- footer */

    /** Footer: correct hosting/company categories, Domain column hidden, contact details in their own column, legal links. */
    public static function footer() {
        if (!class_exists('Rackmonk_Footer_Content') || !class_exists('\Elementor\Plugin') || empty(\Elementor\Plugin::$instance->documents)) return null;
        $id = Rackmonk_Footer_Content::live_footer_id();
        if (!$id) return 'Footer: no footer template is selected, nothing to change.';
        $document = \Elementor\Plugin::$instance->documents->get($id, false);
        if (!$document) return null;
        $data = (array) $document->get_elements_data();
        $options = get_option('xhosting_theme_options');
        $options = is_array($options) ? $options : [];
        self::backup('footer', ['template' => $id, 'elementor_data' => $data, 'options' => $options]);

        // Start from what the footer shows today, then apply the changes.
        $o = array_merge($options, Rackmonk_Footer_Content::extract($data));
        $o['footer_col1_show'] = true;
        $o['footer_col1_title'] = 'Hosting';
        $o['footer_col1_links'] = [
            ['label' => 'Shared Hosting', 'url' => '/shared-hosting-2/'],
            ['label' => 'Cloud Hosting', 'url' => '/cloud-hosting/'],
            ['label' => 'VPS Hosting', 'url' => '/vps-hosting/'],
            ['label' => 'WordPress Hosting', 'url' => '/wp-hosting/'],
            ['label' => 'Website Migration', 'url' => '/migration/'],
            ['label' => 'All Plans & Pricing', 'url' => '/pricing/'],
        ];
        foreach ($o['footer_col1_links'] as &$link) {
            $page = get_page_by_path(trim($link['url'], '/'), OBJECT, 'page');
            if ($page && $page->post_status === 'publish') $link['url'] = self::relative_url($page->ID);
        }
        unset($link);
        $o['footer_col2_show'] = false; // Domain column hidden; its links are kept.
        $o['footer_col3_show'] = true;
        $o['footer_col3_title'] = 'Company';
        $o['footer_col3_links'] = [
            ['label' => 'About Rackmonk', 'url' => '/about-us/'],
            ['label' => 'Blog', 'url' => '/blog/'],
            ['label' => 'Contact Us', 'url' => '/contact-us/'],
            ['label' => 'Client Area', 'url' => '/my-account/'],
        ];
        $legal = [];
        foreach ([['Terms & Conditions', 'terms-conditions'], ['Refund Policy', 'refund-policy'], ['Privacy Policy', 'privacy-policy']] as [$label, $slug]) {
            $page = get_page_by_path($slug, OBJECT, 'page');
            $legal[] = ['label' => $label, 'url' => $page && $page->post_status === 'publish' ? self::relative_url($page->ID) : '#'];
        }
        $o['footer_legal_links'] = $legal;

        $data = self::contact_column($data);
        update_option('xhosting_theme_options', $o);
        update_option(Rackmonk_Footer_Content::SEEDED, $id, false);
        $document->save(['elements' => Rackmonk_Footer_Content::apply($data, $o)]);
        return 'Footer updated: Hosting and Company links corrected, Domain column hidden, contact details in their own column, policy links point to the new pages.';
    }

    /** Moves the contact text out of the logo column into its own column (#c07ac71) — only when the layout is the original "Footer 1". */
    private static function contact_column(array $data) {
        if (($data[0]['elements'][0]['id'] ?? '') !== '91c0949') return $data;
        $inner = &$data[0]['elements'][0];
        foreach ($inner['elements'] as $column) if (($column['id'] ?? '') === 'c07ac71') return $data; // Already moved.
        $contact = null;
        $template = null;
        foreach ($inner['elements'] as &$column) {
            if (($column['id'] ?? '') === 'cccd908') $template = $column;
            if (($column['id'] ?? '') !== '7334697') continue;
            foreach ($column['elements'] as $i => $widget) {
                if (($widget['id'] ?? '') === 'c0a7ac7') { $contact = $widget; array_splice($column['elements'], $i, 1); break; }
            }
        }
        unset($column);
        if ($contact && $template) {
            $inner['elements'][] = ['id' => 'c07ac71', 'elType' => 'container', 'isInner' => $template['isInner'] ?? true, 'settings' => $template['settings'] ?? ['content_width' => 'full'], 'elements' => [$contact]];
        }
        unset($inner);
        return $data;
    }

    /* ---------------------------------------------------------- data centers */

    /** Removes the "Our Data Center Locations" section (the Data Center widget, and its container once it is empty) from every page. */
    public static function remove_data_centers() {
        if (!class_exists('\Elementor\Plugin') || empty(\Elementor\Plugin::$instance->documents)) return null;
        global $wpdb;
        $ids = $wpdb->get_col("SELECT DISTINCT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_elementor_data' WHERE p.post_type = 'page' AND p.post_status <> 'trash' AND m.meta_value LIKE '%xhosting-data-center%'");
        $changes = [];
        foreach ($ids as $id) {
            $document = \Elementor\Plugin::$instance->documents->get((int) $id, false);
            if (!$document) return null;
            $data = (array) $document->get_elements_data();
            $removed = 0;
            $clean = self::without_widget($data, 'xhosting-data-center', $removed);
            if ($removed) $changes[(int) $id] = [$document, $data, $clean];
        }
        if (!$changes) return 'Data center section: not on any page, nothing to change.';
        self::backup('data-centers', array_map(static function ($change) { return $change[1]; }, $changes));
        $titles = [];
        foreach ($changes as $id => [$document, , $clean]) {
            $document->save(['elements' => $clean]);
            $titles[] = get_the_title($id);
        }
        return 'Removed the "Our Data Center Locations" section from: ' . implode(', ', $titles) . '.';
    }

    /** Home page: the "Seamless website migration" box sat inside the dark "Bring your AI ideas" section; it becomes its own section right after it, as on the hosting pages. */
    public static function home_migration_box() {
        if (!class_exists('\Elementor\Plugin') || empty(\Elementor\Plugin::$instance->documents)) return null;
        $id = (int) get_option('page_on_front');
        if (!$id) return 'Home page migration box: no front page is set, nothing to change.';
        $document = \Elementor\Plugin::$instance->documents->get($id, false);
        if (!$document) return null;
        $data = (array) $document->get_elements_data();
        $sections = [];
        $moved = false;
        foreach ($data as $section) {
            $box = null;
            $children = $section['elements'] ?? [];
            foreach ($children as $i => $child) {
                $classes = ' ' . (string) ($child['settings']['css_classes'] ?? '') . ' ';
                if (($child['elType'] ?? '') === 'container' && strpos($classes, ' rm-migration-section ') !== false && count($children) > 1) {
                    $box = $child;
                    array_splice($section['elements'], $i, 1);
                    break;
                }
            }
            $sections[] = $section;
            if ($box) {
                $box['isInner'] = false;
                $sections[] = $box;
                $moved = true;
            }
        }
        if (!$moved) return 'Home page migration box: already its own section, nothing to change.';
        self::backup('home-migration-box', ['page' => $id, 'elementor_data' => $data]);
        $document->save(['elements' => $sections]);
        return 'Home page: the "Seamless website migration" box is now its own section, as on the hosting pages.';
    }

    /** Elementor keeps rendered pages for a day; clear that once so the theme's new section markup ("rm-has-bg" in functions.php) shows at once. */
    public static function clear_elementor_cache() {
        if (!class_exists('\Elementor\Plugin') || empty(\Elementor\Plugin::$instance->files_manager)) return null;
        \Elementor\Plugin::$instance->files_manager->clear_cache();
        return 'Elementor page cache cleared, so the even spacing between page sections shows everywhere.';
    }

    /** $elements without widgets of $type; a container that only held such widgets is dropped too. */
    private static function without_widget(array $elements, $type, &$removed) {
        $kept = [];
        foreach ($elements as $element) {
            if (($element['widgetType'] ?? '') === $type) { $removed++; continue; }
            if (!empty($element['elements'])) {
                $element['elements'] = self::without_widget($element['elements'], $type, $removed);
                if (!$element['elements']) continue;
            }
            $kept[] = $element;
        }
        return $kept;
    }

    /* ----------------------------------------------------------------- misc */

    public static function hello_dolly() {
        if (!function_exists('is_plugin_active')) require_once ABSPATH . 'wp-admin/includes/plugin.php';
        if (!is_plugin_active('hello.php')) return 'Hello Dolly demo plugin is already off.';
        deactivate_plugins('hello.php');
        return 'Hello Dolly demo plugin turned off (the "Hello, Dolly" lyric in wp-admin is gone).';
    }
}
