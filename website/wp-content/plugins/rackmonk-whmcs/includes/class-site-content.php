<?php
/** Editorial settings for generated website sections, separate from billing rules. */
if (!defined('ABSPATH')) exit;

class Rackmonk_Site_Content {
    const OPTION = 'rackmonk_site_content';

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'settings']);
        add_action('update_option_' . self::OPTION, [$this, 'clear_cache']);
        add_action('elementor/element/xhosting-banner/Banner__general/before_section_end', [$this, 'banner_controls']);
        add_action('admin_post_rackmonk_plan_content', [$this, 'save_plan']);
        add_action('admin_notices', [$this, 'editor_notice']);
        add_action('wp_nav_menu_item_custom_fields', [$this, 'menu_visibility_field'], 10, 2);
        add_action('wp_update_nav_menu_item', [$this, 'save_menu_visibility'], 10, 2);
        add_filter('wp_nav_menu_objects', [$this, 'visible_menu_items']);
        add_filter('body_class', static function ($classes) {
            if (self::text('keep_footer_design', '1') === '1') $classes[] = 'rm-footer-design';
            return $classes;
        });
    }

    public function menu_visibility_field($id, $item) {
        wp_nonce_field('rackmonk_menu_visibility_' . $id, 'rackmonk_menu_visibility_nonce[' . $id . ']');
        echo '<p class="description description-wide"><label><input type="checkbox" name="rackmonk_menu_hidden[' . (int) $id . ']" value="1" ' . checked(get_post_meta($id, '_rackmonk_menu_hidden', true), '1', false) . '> Hide on website (keeps this item and its submenu in WordPress)</label></p>';
    }

    public function save_menu_visibility($menu_id, $id) {
        if (!current_user_can('edit_theme_options')) return;
        $nonce = $_POST['rackmonk_menu_visibility_nonce'][$id] ?? '';
        if (!is_string($nonce) || !wp_verify_nonce(wp_unslash($nonce), 'rackmonk_menu_visibility_' . $id)) return;
        update_post_meta($id, '_rackmonk_menu_hidden', empty($_POST['rackmonk_menu_hidden'][$id]) ? '0' : '1');
    }

    public function visible_menu_items($items) {
        if (is_admin()) return $items;
        $hidden = [];
        foreach ($items as $item) if (get_post_meta($item->ID, '_rackmonk_menu_hidden', true) === '1') $hidden[$item->ID] = true;
        // Hide descendants as well, without changing their stored parent or visibility.
        do {
            $changed = false;
            foreach ($items as $item) {
                if (!isset($hidden[$item->ID]) && isset($hidden[(int) $item->menu_item_parent])) {
                    $hidden[$item->ID] = true;
                    $changed = true;
                }
            }
        } while ($changed);
        return array_values(array_filter($items, static function ($item) use ($hidden) { return !isset($hidden[$item->ID]); }));
    }

    public function editor_notice() {
        $screen = get_current_screen();
        $id = isset($_GET['post']) && is_scalar($_GET['post']) ? absint($_GET['post']) : 0;
        if (!$screen || $screen->base !== 'post' || !$id || get_post_field('post_name', $id) !== 'about-us' || self::text('about_use_editor', '0') === '1') return;
        echo '<div class="notice notice-info"><p>The designed About Us content is managed in <a href="' . esc_url(admin_url('admin.php?page=rackmonk-site-content')) . '">Website Content</a>. You can also enable its normal WordPress / Elementor editor there.</p></div>';
    }

    public function banner_controls($widget) {
        $widget->add_control('rackmonk_price_source', [
            'label' => 'Price source', 'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'auto',
            'condition' => ['style' => '1'],
            'options' => ['auto' => 'Keep current behavior', 'manual' => 'Manual Elementor price text', 'catalog' => 'Live configured plan pricing'],
            'description' => 'Manual mode uses the Regular Price / Discount Price fields above. Live mode uses configured prices; its labels are editable in Website Content.',
        ]);
    }

    public static function all() {
        $saved = get_option(self::OPTION, []);
        return is_array($saved) ? $saved : [];
    }

    public static function text($key, $default = '') {
        $saved = self::all();
        return isset($saved[$key]) && is_scalar($saved[$key]) ? (string) $saved[$key] : $default;
    }

    public static function defaults() {
        $defaults = [
            'about_eyebrow' => 'ABOUT RACKMONK',
            'about_title' => 'Give your brand the wings to fly.',
            'about_intro' => 'Fast, secure and reliable hosting, backed by a team that helps you build and scale your business.',
            'about_contact_button' => 'Talk to our team',
            'about_contact_url' => home_url('/contact-us/'),
            'about_identity_label' => 'WHO WE ARE',
            'about_identity_title' => 'Hosting built around your ambitions.',
            'about_identity_first' => 'Rackmonk brings together experienced hosting professionals, enterprise-grade hardware and around-the-clock support to help businesses establish and grow their online presence.',
            'about_identity_second' => 'Whether you are just getting started or scaling up, our team is here to offer guidance and help you find a solution that fits your needs.',
            'about_mission_label' => 'OUR MISSION',
            'about_mission_title' => 'Turn your dreams into a reality.',
            'about_mission_text' => 'We believe in transparent advice, dependable infrastructure and support that is there when you need it. Our goal is to make your hosting journey smoother, so you can focus on what matters most: growing your business.',
            'about_pillars_label' => 'WHAT MATTERS TO US',
            'about_pillars_title' => 'The pillars behind Rackmonk.',
            'about_technology_title' => 'Technology',
            'about_technology_text' => 'We work to improve speed, security and efficiency with advanced technologies and enterprise-grade hardware.',
            'about_people_title' => 'People',
            'about_people_text' => 'Our team brings the experience, knowledge and dedication that power the Rackmonk journey.',
            'about_customers_title' => 'Customers',
            'about_customers_text' => 'Our customers come first, with expert guidance and support available 24 hours a day, 365 days a year.',
            'about_cta_label' => 'GROW WITH THE FLOW',
            'about_cta_title' => 'Ready for your next step?',
            'about_cta_text' => 'Explore our hosting plans or connect with our team to find the right fit.',
            'about_cta_button' => 'Explore plans',
            'about_cta_url' => home_url('/pricing/'),
            'banner_start_label' => 'Start with',
            'banner_monthly_label' => 'Monthly billing',
            'banner_term_label' => 'Months Term',
            'banner_tax_note' => 'Taxes extra.',
            'banner_unavailable' => '48-month pricing will be available once configured.',
            'pricing_all_plans' => 'All Plans',
            'pricing_48_title' => '48 Months',
            'pricing_24_title' => '24 Months',
            'pricing_12_title' => '12 Months',
            'pricing_1_title' => '1 Month',
            'pricing_48_badge' => '',
            'pricing_24_badge' => '',
            'pricing_12_badge' => '',
            'pricing_hint_title' => 'Compare billing terms:',
            'pricing_hint' => 'Review the selected term, total price and included features before ordering.',
            'pricing_empty' => 'No active hosting plans found in this category.',
            'pricing_popular' => 'MOST POPULAR',
            'pricing_choose' => 'Choose Plan',
            'pricing_features' => 'Top Features Included:',
            'pricing_unavailable' => 'Unavailable',
        ];
        foreach (['Rackmonk_Website_Cart', 'Rackmonk_Website_Checkout', 'Rackmonk_Client_Area', 'Rackmonk_Navigation'] as $class) {
            if (is_callable([$class, 'content_defaults'])) $defaults = array_merge($defaults, $class::content_defaults());
        }
        return $defaults;
    }

    public function settings() {
        register_setting('rackmonk_site_content', self::OPTION, ['type' => 'array', 'sanitize_callback' => [$this, 'sanitize']]);
    }

    public function sanitize($input) {
        $saved = self::all();
        if (!is_array($input)) return $saved;
        foreach (self::defaults() as $key => $default) {
            if (!array_key_exists($key, $input) || !is_scalar($input[$key])) continue;
            $saved[$key] = substr($key, -4) === '_url' ? esc_url_raw($input[$key], ['http', 'https']) : sanitize_textarea_field($input[$key]);
        }
        foreach (['about_use_editor', 'show_domain_search', 'keep_footer_design'] as $key) $saved[$key] = !empty($input[$key]) ? '1' : '0';
        return $saved;
    }

    public function clear_cache() {
        if (class_exists('\\Elementor\\Plugin')) \Elementor\Plugin::$instance->files_manager->clear_cache();
    }

    public function menu() {
        add_menu_page('Website Content', 'Website Content', 'manage_options', 'rackmonk-site-content', [$this, 'page'], 'dashicons-edit-page', 24);
        add_submenu_page('rackmonk-site-content', 'Plan Content', 'Plan Content', 'manage_options', 'rackmonk-plan-content', [$this, 'plan_page']);
    }

    public function save_plan() {
        if (!current_user_can('manage_options')) wp_die('You cannot edit plan content.', '', ['response' => 403]);
        $pid = isset($_POST['pid']) && is_scalar($_POST['pid']) ? absint($_POST['pid']) : 0;
        check_admin_referer('rackmonk_plan_content_' . $pid);
        $pdo = Rackmonk_Plans_Manager::get_instance()->get_whmcs_db();
        if (!$pdo) wp_die('WHMCS is disconnected. Nothing was saved.');
        $values = [];
        foreach (['name', 'tagline', 'description', 'version'] as $key) {
            if (!isset($_POST[$key]) || !is_string($_POST[$key])) wp_die('Incomplete form. Nothing was saved.');
            $values[$key] = wp_unslash($_POST[$key]);
        }
        $values['name'] = sanitize_text_field($values['name']);
        $values['tagline'] = sanitize_text_field($values['tagline']);
        $values['description'] = wp_kses_post($values['description']);
        if ($values['name'] === '' || strlen($values['name']) > 255 || strlen($values['tagline']) > 255) wp_die('Enter a plan name and keep name/tagline within 255 bytes.');
        try {
            $pdo->beginTransaction();
            $query = $pdo->prepare("SELECT id, name, tagline, description, is_featured FROM tblproducts WHERE id = ? AND paytype != 'onetime' FOR UPDATE");
            $query->execute([$pid]);
            $row = $query->fetch();
            if (!$row || !hash_equals(hash('sha256', wp_json_encode($row)), $values['version'])) throw new RuntimeException('Plan changed or no longer exists. Reload before editing.');
            $query = $pdo->prepare('UPDATE tblproducts SET name = ?, tagline = ?, description = ?, is_featured = ? WHERE id = ?');
            $query->execute([$values['name'], $values['tagline'], $values['description'], empty($_POST['featured']) ? 0 : 1, $pid]);
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            wp_die(esc_html($error instanceof PDOException ? 'Plan content could not be saved. Check the connection.' : $error->getMessage()));
        }
        $this->clear_cache();
        wp_safe_redirect(admin_url('admin.php?page=rackmonk-plan-content&saved=1'));
        exit;
    }

    public function plan_page() {
        if (!current_user_can('manage_options')) return;
        $pdo = Rackmonk_Plans_Manager::get_instance()->get_whmcs_db();
        $rows = [];
        try {
            if ($pdo) $rows = $pdo->query("SELECT id, name, tagline, description, is_featured FROM tblproducts WHERE paytype != 'onetime' AND (retired IS NULL OR retired IN ('', '0')) ORDER BY gid, `order`, id")->fetchAll();
        } catch (Throwable $error) { echo '<div class="notice notice-error"><p>Plan content could not be loaded.</p></div>'; }
        ?>
        <div class="wrap"><h1>Plan Content</h1><p>Edit the real catalog plan name, tagline, features/description and featured badge. Prices and billing rules are not changed. Enter each feature on a separate line or use an HTML list. These changes also update the source WHMCS catalog.</p>
        <?php if (isset($_GET['saved'])): ?><div class="notice notice-success"><p>Plan content saved.</p></div><?php endif; ?>
        <?php if (!$rows): ?><p>No editable plans found. Check your WHMCS connection.</p><?php endif; ?>
        <?php foreach ($rows as $row): ?>
        <details style="padding:16px;margin:16px 0;background:white;border:1px solid #ddd"><summary><strong><?php echo esc_html($row['name']); ?></strong></summary>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="rackmonk_plan_content"><input type="hidden" name="pid" value="<?php echo (int) $row['id']; ?>"><input type="hidden" name="version" value="<?php echo esc_attr(hash('sha256', wp_json_encode($row))); ?>">
            <?php wp_nonce_field('rackmonk_plan_content_' . (int) $row['id']); ?>
            <?php foreach (['name' => 'Plan name', 'tagline' => 'Tagline', 'description' => 'Description / features'] as $key => $label): ?>
            <p><label><?php echo esc_html($label); ?><br><textarea class="large-text" name="<?php echo esc_attr($key); ?>" rows="<?php echo $key === 'description' ? 8 : 2; ?>"><?php echo esc_textarea($row[$key]); ?></textarea></label></p>
            <?php endforeach; ?>
            <p><label><input type="checkbox" name="featured" value="1" <?php checked(!empty($row['is_featured'])); ?>> Featured / popular plan</label></p>
            <?php submit_button('Save this plan'); ?>
        </form></details>
        <?php endforeach; ?></div>
        <?php
    }

    public function page() {
        if (!current_user_can('manage_options')) return;
        $saved = self::all();
        $front = (int) get_option('page_on_front');
        ?>
        <div class="wrap"><h1>Rackmonk Website Content</h1>
        <p>Manage website content without editing code. Use Elementor for page sections and shared templates; use the fields below for generated About, banner and cart content. Billing validation is not changed by these settings.</p>
        <p>Company address, telephone, email and social profiles are editable in the Contact Us page and shared Header / Footer templates. Contact-form delivery recipients are managed in Contact &gt; Contact Forms &gt; Mail.</p>
        <p>Outgoing Gmail delivery: <a href="<?php echo esc_url(admin_url('admin.php?page=wp-mail-smtp')); ?>">SMTP settings</a> | <a href="<?php echo esc_url(admin_url('admin.php?page=wp-mail-smtp-tools&tab=test')); ?>">Send an Email Test</a>. Enter your App Password only in the admin password field; never paste it into a page, source file or chat.</p>
        <h2>Visual page editing</h2>
        <p><a class="button button-primary" href="<?php echo esc_url(admin_url('post.php?post=' . $front . '&action=elementor')); ?>">Edit homepage in Elementor</a> <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=xhosting_template')); ?>">Header / Footer templates</a> <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=xhosting-theme-option')); ?>">Theme settings</a> <a class="button" href="<?php echo esc_url(admin_url('nav-menus.php')); ?>">Menus</a> <a class="button" href="<?php echo esc_url(admin_url('customize.php')); ?>">Logo / Site icon</a> <a class="button" href="<?php echo esc_url(admin_url('options-general.php')); ?>">Site name</a> <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=rackmonk-hosting-cards')); ?>">Hosting cards</a> <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=rackmonk-whmcs-plans')); ?>">Plan prices / resources</a></p>
        <details><summary><strong>All website pages and shared templates</strong></summary>
        <table class="widefat striped" style="max-width:1100px"><thead><tr><th>Page / Template</th><th>Editor</th><th>Preview</th></tr></thead><tbody>
        <?php foreach (get_posts(['post_type' => ['page', 'xhosting_template', 'elementor_library', 'th-mega-menu'], 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'post_type title', 'order' => 'ASC']) as $post): ?>
            <tr><td><?php echo esc_html($post->post_title . ' (' . $post->post_type . ')'); ?></td><td><a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">WordPress editor</a><?php if (get_post_meta($post->ID, '_elementor_data', true)): ?> | <a href="<?php echo esc_url(admin_url('post.php?post=' . $post->ID . '&action=elementor')); ?>">Elementor</a><?php endif; ?></td><td><a href="<?php echo esc_url(get_permalink($post)); ?>" target="_blank" rel="noopener">View</a></td></tr>
        <?php endforeach; ?>
        </tbody></table></details>
        <h2>Generated section content</h2>
        <?php settings_errors(); ?>
        <form action="options.php" method="post">
        <?php settings_fields('rackmonk_site_content'); ?>
        <p><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[about_use_editor]" value="1" <?php checked(self::text('about_use_editor', '0'), '1'); ?>> Use the About Us page's normal WordPress / Elementor content instead of the designed About sections below.</label></p>
        <p><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[show_domain_search]" value="1" <?php checked(self::text('show_domain_search', '0'), '1'); ?>> Display Elementor domain-search widgets publicly (requires a configured search destination).</label></p>
        <p><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[keep_footer_design]" value="1" <?php checked(self::text('keep_footer_design', '1'), '1'); ?>> Keep the current light footer styling. Disable to let the shared footer's Elementor color settings take full control.</label></p>
        <p>All fields are plain text, except destination URLs. Empty fields intentionally hide their text. Prices and totals remain calculated from configured plans.</p>
        <?php $group = ''; foreach (self::defaults() as $key => $default): $next = explode('_', $key)[0]; if ($next !== $group): if ($group !== '') echo '</table></details>'; $group = $next; ?>
            <details style="margin:16px 0;background:#fff;padding:16px;border:1px solid #ddd"><summary><strong><?php echo esc_html(ucfirst($group)); ?> content</strong></summary><table class="form-table" role="presentation">
        <?php endif; ?>
            <tr><th><label for="rm-copy-<?php echo esc_attr($key); ?>"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></label></th><td><textarea class="large-text" rows="2" id="rm-copy-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr(self::OPTION . '[' . $key . ']'); ?>"><?php echo esc_textarea($saved[$key] ?? $default); ?></textarea></td></tr>
        <?php endforeach; if ($group !== '') echo '</table></details>'; ?>
        <?php submit_button('Save website content'); ?>
        </form></div>
        <?php
    }
}
