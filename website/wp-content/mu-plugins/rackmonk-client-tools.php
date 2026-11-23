<?php
/**
 * Plugin Name: Rackmonk Client Tools
 * Description: Lets the site owner edit everything from WordPress: HD image quality, one-click image replacement, an HD image report, an editing guide on the dashboard, Header & Footer menu access and per-page SEO fields.
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) exit;

final class Rackmonk_Client_Tools {
    const HD_WIDTH = 1200;          // Images narrower than this are flagged as not HD.
    const ICON_MAX = 128;           // Smaller images are icons/avatars and are not flagged.
    const SIZES_OPTION = 'rackmonk_display_sizes';
    const RASTER = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    const EAGER_IMAGES = 5;         // Images at the top of a page (hero) that must not wait for lazy loading.

    public static function boot() {
        // HD output: keep full resolution and save resized copies at maximum quality.
        add_filter('big_image_size_threshold', '__return_false', 99);
        add_filter('wp_editor_set_quality', [__CLASS__, 'max_quality'], 99);
        add_filter('jpeg_quality', [__CLASS__, 'max_quality'], 99);
        add_filter('max_srcset_image_width', [__CLASS__, 'srcset_width']);
        add_action('elementor/frontend/widget/before_render', [__CLASS__, 'full_size_image_widget']);
        add_filter('elementor/widget/render_content', [__CLASS__, 'keep_display_size'], 30);
        // Whole Elementor document (page, then footer) in page order, after cached widgets are filled in.
        add_filter('elementor/frontend/the_content', [__CLASS__, 'lazy_images'], 40);

        // Media library tools.
        add_filter('manage_media_columns', [__CLASS__, 'media_column']);
        add_action('manage_media_custom_column', [__CLASS__, 'media_column_value'], 10, 2);
        add_filter('media_row_actions', [__CLASS__, 'media_row_action'], 10, 2);
        add_filter('attachment_fields_to_edit', [__CLASS__, 'attachment_field'], 10, 2);
        add_action('admin_post_rackmonk_replace_media', [__CLASS__, 'handle_replace']);

        // Admin navigation for the owner.
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('wp_dashboard_setup', [__CLASS__, 'dashboard_widget']);
        add_action('admin_notices', [__CLASS__, 'template_notice']);

        // SEO description and social sharing tags.
        add_action('add_meta_boxes', [__CLASS__, 'seo_box']);
        add_action('save_post', [__CLASS__, 'save_seo'], 10, 2);
        add_filter('rackmonk_meta_description', [__CLASS__, 'meta_description']);
        add_action('wp_head', [__CLASS__, 'social_tags'], 3);
    }

    /* ---------------------------------------------------------------- HD output */

    public static function max_quality() {
        return 100;
    }

    public static function srcset_width() {
        return 4096;
    }

    /** The Elementor Image widget defaults to the 1024px "large" copy; serve the full file instead. */
    public static function full_size_image_widget($widget) {
        if ($widget->get_name() !== 'image') return;
        $size = $widget->get_settings('image_size');
        if ($size === 'custom' || $size === 'full') return;
        $widget->set_settings('image_size', 'full');
        $widget->reset_render_state();
    }

    /**
     * Theme widgets print <img> without width/height. When a small demo image is replaced by an HD file,
     * pin it to the size it was designed for so the layout stays the same and the extra pixels make it sharp.
     */
    public static function keep_display_size($content) {
        $sizes = get_option(self::SIZES_OPTION, []);
        if (!$sizes || !is_array($sizes) || stripos($content, '<img') === false) return $content;
        return preg_replace_callback('/<img\b[^>]*>/i', static function ($match) use ($sizes) {
            $tag = $match[0];
            if (preg_match('/\swidth\s*=/i', $tag) || !preg_match('/\ssrc\s*=\s*["\']([^"\']+)["\']/i', $tag, $src)) return $tag;
            $key = self::upload_key(html_entity_decode($src[1]));
            if ($key === '' || empty($sizes[$key])) return $tag;
            [$w, $h] = $sizes[$key];
            return preg_replace('/^<img\b/i', '<img width="' . (int) $w . '" height="' . (int) $h . '"', $tag);
        }, $content);
    }

    /**
     * Theme widgets print plain <img> tags with no loading hints. The first images on a page (the hero) load
     * straight away, the first one with high priority; every later image waits until it is about to scroll into view.
     */
    public static function lazy_images($content) {
        static $seen = 0;
        // Only the visible page body counts (content rendered earlier, e.g. for the SEO description, is never shown).
        if (is_admin() || !did_action('wp_body_open') || stripos($content, '<img') === false) return $content;
        return preg_replace_callback('/<img\b[^>]*>/i', static function ($match) use (&$seen) {
            $tag = $match[0];
            $seen++;
            // Images with width and height (e.g. the Elementor Image widget) are already optimised by Elementor/WordPress.
            if (preg_match('/\sloading\s*=/i', $tag) || (preg_match('/\swidth\s*=/i', $tag) && preg_match('/\sheight\s*=/i', $tag))) return $tag;
            if ($seen <= self::EAGER_IMAGES) {
                $add = ($seen === 1 && !preg_match('/\sfetchpriority\s*=/i', $tag)) ? ' fetchpriority="high"' : '';
            } else {
                $add = ' loading="lazy"';
            }
            if (!preg_match('/\sdecoding\s*=/i', $tag)) $add .= ' decoding="async"';
            return preg_replace('/^<img\b/i', '<img' . $add, $tag);
        }, $content);
    }

    /** Path of an uploaded file relative to the uploads folder, e.g. "2025/07/hero.webp". */
    private static function upload_key($url) {
        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $base = (string) wp_parse_url(wp_get_upload_dir()['baseurl'], PHP_URL_PATH);
        $base = trailingslashit($base);
        return $base !== '/' && strpos($path, $base) === 0 ? ltrim(substr($path, strlen($base)), '/') : '';
    }

    /* ------------------------------------------------------------ Media library */

    public static function media_column($columns) {
        $columns['rackmonk_resolution'] = 'Resolution';
        return $columns;
    }

    public static function media_column_value($column, $id) {
        if ($column !== 'rackmonk_resolution') return;
        $meta = wp_get_attachment_metadata($id);
        if (empty($meta['width'])) { echo '&mdash;'; return; }
        echo esc_html($meta['width'] . ' × ' . $meta['height']);
        if (self::is_low_res($id, $meta)) echo '<br><span style="color:#b32d2e;font-weight:600">Not HD</span>';
        elseif (in_array(get_post_mime_type($id), self::RASTER, true) && $meta['width'] >= self::HD_WIDTH) echo '<br><span style="color:#008a20;font-weight:600">HD</span>';
    }

    private static function is_low_res($id, $meta = null) {
        $meta = $meta ?: wp_get_attachment_metadata($id);
        $width = (int) ($meta['width'] ?? 0);
        return in_array(get_post_mime_type($id), self::RASTER, true) && $width > self::ICON_MAX && $width < self::HD_WIDTH;
    }

    private static function replace_url($id) {
        return admin_url('admin.php?page=rackmonk-replace-image&attachment_id=' . (int) $id);
    }

    public static function media_row_action($actions, $post) {
        if (in_array($post->post_mime_type, self::RASTER, true) && current_user_can('edit_post', $post->ID)) {
            $actions['rackmonk_replace'] = '<a href="' . esc_url(self::replace_url($post->ID)) . '">Replace with HD file</a>';
        }
        return $actions;
    }

    public static function attachment_field($fields, $post) {
        if (in_array($post->post_mime_type, self::RASTER, true) && current_user_can('edit_post', $post->ID)) {
            $fields['rackmonk_replace'] = [
                'label' => 'Replace file',
                'input' => 'html',
                'html'  => '<a class="button" href="' . esc_url(self::replace_url($post->ID)) . '">Replace with HD file</a>',
                'helps' => 'Upload a sharper version. Every page using this image updates automatically.',
            ];
        }
        return $fields;
    }

    public static function replace_page() {
        $id = isset($_GET['attachment_id']) ? absint($_GET['attachment_id']) : 0;
        echo '<div class="wrap"><h1>Replace Image</h1>';
        if (!$id || get_post_type($id) !== 'attachment' || !in_array(get_post_mime_type($id), self::RASTER, true) || !current_user_can('edit_post', $id)) {
            echo '<p>Choose an image from the <a href="' . esc_url(admin_url('upload.php?mode=list')) . '">Media Library</a> and click <strong>Replace with HD file</strong>.</p></div>';
            return;
        }
        if (isset($_GET['replaced'])) {
            echo '<div class="notice notice-success"><p>Image replaced. Every page using it now shows the new file. If you still see the old image, press <strong>Ctrl + F5</strong> to refresh your browser.</p></div>';
        }
        $meta = wp_get_attachment_metadata($id);
        $w = (int) ($meta['width'] ?? 0);
        $h = (int) ($meta['height'] ?? 0);
        $pinned = isset(get_option(self::SIZES_OPTION, [])[self::upload_key(wp_get_attachment_url($id))]);
        ?>
        <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;margin-top:16px">
            <div style="background:#fff;border:1px solid #dcdcde;padding:12px;max-width:360px">
                <img src="<?php echo esc_url(wp_get_attachment_url($id) . '?v=' . get_post_modified_time('U', true, $id)); ?>" style="max-width:100%;height:auto;display:block" alt="">
                <p><strong><?php echo esc_html(wp_basename(get_attached_file($id))); ?></strong><br>Current size: <?php echo esc_html("$w × $h px"); ?></p>
            </div>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:520px">
                <input type="hidden" name="action" value="rackmonk_replace_media">
                <input type="hidden" name="attachment_id" value="<?php echo (int) $id; ?>">
                <?php wp_nonce_field('rackmonk_replace_' . $id); ?>
                <p><label for="rackmonk_file"><strong>New image file</strong> (JPG, PNG, WebP or GIF)</label><br>
                    <input type="file" id="rackmonk_file" name="rackmonk_file" accept="image/jpeg,image/png,image/webp,image/gif" required></p>
                <?php if ($w): ?>
                <p class="description">For a sharp HD result, upload an image at least <strong><?php echo esc_html((2 * $w) . ' × ' . (2 * $h)); ?> px</strong> with the same shape (aspect ratio).</p>
                <?php endif; ?>
                <p><label><input type="checkbox" name="keep_size" value="1" <?php checked($pinned || !$meta || $w < self::HD_WIDTH); ?>> Keep the same display size on the website (recommended — the layout stays exactly as it is, only sharper)</label></p>
                <p class="description">The file name and link stay the same, so you do not need to edit any page. The file is converted to the original format automatically if needed.</p>
                <?php submit_button('Replace image'); ?>
            </form>
        </div></div>
        <?php
    }

    public static function handle_replace() {
        $id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;
        check_admin_referer('rackmonk_replace_' . $id);
        if (!$id || get_post_type($id) !== 'attachment' || !current_user_can('upload_files') || !current_user_can('edit_post', $id)) {
            wp_die('You are not allowed to replace this file.', '', ['response' => 403]);
        }
        $old_mime = get_post_mime_type($id);
        $target = get_attached_file($id);
        if (!in_array($old_mime, self::RASTER, true) || !$target || !file_exists($target)) wp_die('Only existing JPG, PNG, WebP or GIF images can be replaced here.');

        $file = $_FILES['rackmonk_file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            wp_die('The upload failed. Please choose the file again. Very large files may exceed the server upload limit (' . esc_html(size_format(wp_max_upload_size())) . ').');
        }
        $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        if (empty($check['type']) || !in_array($check['type'], self::RASTER, true)) wp_die('Please upload a JPG, PNG, WebP or GIF image.');

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $old_meta = wp_get_attachment_metadata($id);
        $old_w = (int) ($old_meta['width'] ?? 0);
        $old_h = (int) ($old_meta['height'] ?? 0);
        $dir = dirname($target);
        $ext = pathinfo($target, PATHINFO_EXTENSION);
        $staged = $dir . '/' . wp_unique_filename($dir, 'rackmonk-replace-' . wp_generate_password(8, false) . '.' . $ext);

        // Stage the new file next to the old one first, so a failure never leaves a broken image.
        if ($check['type'] === $old_mime) {
            if (!@move_uploaded_file($file['tmp_name'], $staged)) wp_die('Could not save the uploaded file. Check folder permissions.');
        } else {
            $editor = wp_get_image_editor($file['tmp_name']);
            if (is_wp_error($editor)) wp_die('This image could not be read: ' . esc_html($editor->get_error_message()));
            $editor->set_quality(100);
            $saved = $editor->save($staged, $old_mime);
            if (is_wp_error($saved)) wp_die('This image could not be converted: ' . esc_html($saved->get_error_message()));
            $staged = $saved['path'];
        }
        if (!@getimagesize($staged)) { wp_delete_file($staged); wp_die('The uploaded file is not a valid image.'); }

        // Remove the old resized copies (and an untouched original, if WordPress kept one), then swap the file in.
        foreach (($old_meta['sizes'] ?? []) as $size) {
            if (!empty($size['file']) && $dir . '/' . $size['file'] !== $target) wp_delete_file($dir . '/' . $size['file']);
        }
        $original = wp_get_original_image_path($id);
        if ($original && $original !== $target && file_exists($original)) wp_delete_file($original);
        if (!@rename($staged, $target)) { wp_delete_file($staged); wp_die('Could not replace the old file. Check folder permissions.'); }

        $meta = wp_generate_attachment_metadata($id, $target);
        unset($meta['original_image']);
        wp_update_attachment_metadata($id, $meta);
        wp_update_post(['ID' => $id, 'post_modified' => current_time('mysql'), 'post_modified_gmt' => current_time('mysql', true)]);

        $sizes = get_option(self::SIZES_OPTION, []);
        $sizes = is_array($sizes) ? $sizes : [];
        $key = self::upload_key(wp_get_attachment_url($id));
        if (!empty($_POST['keep_size']) && $key !== '') {
            // Remember the size the design was built for (only the first time, so repeated swaps keep it).
            if (empty($sizes[$key]) && $old_w && $old_h) $sizes[$key] = [$old_w, $old_h];
        } else {
            unset($sizes[$key]);
        }
        update_option(self::SIZES_OPTION, $sizes, true);

        if (class_exists('\\Elementor\\Plugin')) \Elementor\Plugin::$instance->files_manager->clear_cache();
        wp_safe_redirect(add_query_arg('replaced', 1, self::replace_url($id)));
        exit;
    }

    /* ----------------------------------------------------------- HD image report */

    /** Every raster image used by published Elementor content, with the pages using it. */
    private static function used_images() {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT p.ID, m.meta_value FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_elementor_data' WHERE p.post_status = 'publish' AND p.post_type NOT IN ('revision')");
        $found = [];
        foreach ($rows as $row) {
            $data = json_decode($row->meta_value, true);
            if (!is_array($data)) continue;
            array_walk_recursive($data, static function ($value, $key) use (&$found, $row) {
                if (!is_string($value) || !preg_match('/\.(jpe?g|png|webp|gif)(\?.*)?$/i', $value)) return;
                $found[$value]['pages'][$row->ID] = true;
                if (stripos((string) $key, 'background') !== false || stripos((string) $key, 'bg') !== false) $found[$value]['bg'] = true;
            });
        }
        return $found;
    }

    public static function report_page() {
        echo '<div class="wrap"><h1>HD Image Check</h1>';
        echo '<p>Images used on your published pages that are smaller than <strong>' . self::HD_WIDTH . ' px</strong> wide look soft on large and high-resolution (retina) screens. Click <strong>Replace with HD file</strong> to upload a sharper version — the website updates everywhere automatically. Small icons and avatars (under ' . self::ICON_MAX . ' px) are not listed.</p>';
        $low = [];
        $outside = [];
        $hd = 0;
        foreach (self::used_images() as $url => $info) {
            $id = attachment_url_to_postid($url);
            if (!$id) { $outside[$url] = $info; continue; }
            $meta = wp_get_attachment_metadata($id);
            if (self::is_low_res($id, $meta)) $low[$id] = $info + ['meta' => $meta];
            elseif ((int) ($meta['width'] ?? 0) >= self::HD_WIDTH) $hd++;
        }
        uasort($low, static function ($a, $b) { return count($b['pages']) <=> count($a['pages']); });
        echo '<p><strong>' . count($low) . '</strong> images need an HD version &nbsp;·&nbsp; <strong>' . (int) $hd . '</strong> images are already HD.</p>';
        if ($low) {
            echo '<table class="widefat striped"><thead><tr><th style="width:110px">Image</th><th>File</th><th>Current size</th><th>Upload at least</th><th>Used on</th><th></th></tr></thead><tbody>';
            foreach ($low as $id => $info) {
                $w = (int) $info['meta']['width'];
                $h = (int) $info['meta']['height'];
                $pages = [];
                foreach (array_keys($info['pages']) as $pid) {
                    $pages[] = '<a href="' . esc_url(admin_url('post.php?post=' . $pid . '&action=elementor')) . '">' . esc_html(get_the_title($pid) ?: '#' . $pid) . '</a>';
                }
                echo '<tr><td>' . wp_get_attachment_image($id, 'thumbnail', false, ['style' => 'max-width:100px;height:auto']) . '</td>';
                echo '<td>' . esc_html(wp_basename(get_attached_file($id)));
                if (!empty($info['bg'])) echo '<br><small>Background image: after replacing, set <em>Background → Size</em> to <em>Cover</em> in Elementor if it looks too large.</small>';
                echo '</td><td>' . esc_html("$w × $h") . '</td><td>' . esc_html((2 * $w) . ' × ' . (2 * $h)) . '</td>';
                echo '<td>' . implode(', ', $pages) . '</td>';
                echo '<td><a class="button button-primary" href="' . esc_url(self::replace_url($id)) . '">Replace with HD file</a></td></tr>';
            }
            echo '</tbody></table>';
        }
        if ($outside) {
            echo '<h2 style="margin-top:32px">Images not in the Media Library</h2><p>These are linked from the theme folder or are missing. Open the page in Elementor and choose a new image from the Media Library.</p><ul>';
            foreach ($outside as $url => $info) {
                $links = [];
                foreach (array_keys($info['pages']) as $pid) $links[] = '<a href="' . esc_url(admin_url('post.php?post=' . $pid . '&action=elementor')) . '">' . esc_html(get_the_title($pid) ?: '#' . $pid) . '</a>';
                echo '<li><code>' . esc_html(wp_basename(wp_parse_url($url, PHP_URL_PATH))) . '</code> — ' . implode(', ', $links) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
    }

    /* ---------------------------------------------------------- Owner navigation */

    public static function admin_menu() {
        add_media_page('HD Image Check', 'HD Image Check', 'upload_files', 'rackmonk-hd-images', [__CLASS__, 'report_page']);
        add_submenu_page('', 'Replace Image', 'Replace Image', 'upload_files', 'rackmonk-replace-image', [__CLASS__, 'replace_page']);
        if (post_type_exists('xhosting_template')) {
            add_theme_page('Header & Footer', 'Header & Footer', 'edit_pages', 'edit.php?post_type=xhosting_template');
        }
    }

    public static function dashboard_widget() {
        if (!current_user_can('edit_pages')) return;
        wp_add_dashboard_widget('rackmonk_edit_guide', 'Edit Your Website', [__CLASS__, 'dashboard_content']);
        // Show it first.
        global $wp_meta_boxes;
        $normal = $wp_meta_boxes['dashboard']['normal']['core'] ?? [];
        if (isset($normal['rackmonk_edit_guide'])) {
            $wp_meta_boxes['dashboard']['normal']['core'] = ['rackmonk_edit_guide' => $normal['rackmonk_edit_guide']] + $normal;
        }
    }

    private static function elementor_link($id) {
        return admin_url('post.php?post=' . (int) $id . '&action=elementor');
    }

    /**
     * Site-wide Header/Footer template the theme really renders (Rackmonk Options, else the homepage's
     * page setting), or 0 when none is valid and the theme falls back to its built-in header/footer.
     */
    private static function live_template($type) {
        $style = function_exists('cs_get_option') ? cs_get_option($type . '_style') : '';
        if (!$style) {
            $meta = get_post_meta((int) get_option('page_on_front'), 'xhosting_page_meta', true);
            if (is_array($meta) && !empty($meta['meta_' . $type . '_type'])) $style = $meta['meta_' . $type . '_style'] ?? '';
        }
        return ($style && get_post_type((int) $style) === 'xhosting_template') ? (int) $style : 0;
    }

    /** Explains on the Header & Footer screens which template is live, so edits are made in the right place. */
    public static function template_notice() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'xhosting_template' || !in_array($screen->base, ['edit', 'post'], true)) return;
        $header = self::live_template('header');
        $footer = self::live_template('footer');
        $lines = [];
        if ($header) {
            $lines[] = 'Live header: <a href="' . esc_url(self::elementor_link($header)) . '"><strong>' . esc_html(get_the_title($header)) . '</strong></a> (edit with Elementor).';
        } else {
            $lines[] = '<strong>The website header is not made from the Header templates in this list</strong>, so editing them does not change the live site. Change the header logo in <a href="' . esc_url(admin_url('customize.php?autofocus[section]=title_tagline')) . '">Appearance &gt; Customize &gt; Site Identity</a>, its links, icons and drop-down descriptions in <a href="' . esc_url(admin_url('nav-menus.php')) . '">Appearance &gt; Menus</a>, and the Hosting drop-down headings in <a href="' . esc_url(admin_url('admin.php?page=rackmonk-site-content')) . '">Website Content &gt; Menu content</a>.';
        }
        if ($footer) {
            $lines[] = 'Live footer: <a href="' . esc_url(self::elementor_link($footer)) . '"><strong>' . esc_html(get_the_title($footer)) . '</strong></a>. Edit it with Elementor to change footer text, links, logo, address and social icons. Other Footer templates are not shown on the website.';
        }
        echo '<div class="notice notice-info"><p>' . implode('</p><p>', $lines) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above.
    }

    public static function dashboard_content() {
        $home = (int) get_option('page_on_front');
        $links = [];
        if ($home) $links[] = ['Home page', self::elementor_link($home), 'Edit text, images and sections visually with Elementor.'];
        $links[] = ['All pages', admin_url('edit.php?post_type=page'), 'Hover a page and click “Edit with Elementor”.'];
        $links[] = ['Blog posts', admin_url('edit.php'), 'Add or edit news and articles.'];
        $links[] = ['Header menu', admin_url('nav-menus.php'), 'Add, rename, reorder or hide header links, and set drop-down icons and descriptions.'];
        $links[] = ['Logo & site icon', admin_url('customize.php?autofocus[section]=title_tagline'), 'Change the header logo, favicon, site name and tagline.'];
        if (post_type_exists('xhosting_template')) {
            $footer = self::live_template('footer');
            $links[] = $footer
                ? ['Footer', self::elementor_link($footer), 'Footer text, links, logo, address, phone and social icons (opens “' . get_the_title($footer) . '” in Elementor).']
                : ['Header & Footer', admin_url('edit.php?post_type=xhosting_template'), 'Footer links, address, phone and social icons (edit with Elementor).'];
        }
        $kit = (int) get_option('elementor_active_kit');
        $links[] = ['Theme colour', admin_url('customize.php?autofocus[section]=rackmonk_theme_color'), 'Pick one colour — buttons, backgrounds, Elementor colours and all illustrations update automatically.'];
        if ($kit) $links[] = ['Fonts', admin_url('post.php?post=' . $kit . '&action=elementor'), 'In Elementor, open Site Settings → Global Fonts.'];
        if (menu_page_url('rackmonk-site-content', false)) $links[] = ['Website Content', admin_url('admin.php?page=rackmonk-site-content'), 'About Us page text, header drop-down headings, pricing labels and buttons, cart and account wording.'];
        if (menu_page_url('rackmonk-plan-content', false)) $links[] = ['Hosting plans', admin_url('admin.php?page=rackmonk-plan-content'), 'Plan names, taglines and features shown in the pricing tables.'];
        if (menu_page_url('rackmonk-hosting-cards', false)) $links[] = ['Homepage hosting cards', admin_url('admin.php?page=rackmonk-hosting-cards'), 'Title, text, button, icon image and colours of the hosting category cards.'];
        $links[] = ['Images', admin_url('upload.php?page=rackmonk-hd-images'), 'See which images are not HD and replace them in one click.'];
        echo '<ul style="margin:0">';
        foreach ($links as [$label, $url, $help]) {
            echo '<li style="padding:8px 0;border-bottom:1px solid #f0f0f1"><a href="' . esc_url($url) . '" style="font-weight:600">' . esc_html($label) . '</a><br><span style="color:#646970">' . esc_html($help) . '</span></li>';
        }
        echo '</ul><p style="margin-bottom:0;color:#646970">Tip: in Elementor, click any text or image on the page to change it, then press <strong>Publish</strong> (or <strong>Update</strong>).</p>';
    }

    /* ----------------------------------------------------------------------- SEO */

    private static function seo_plugin_active() {
        return defined('WPSEO_VERSION') || class_exists('RankMath') || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION');
    }

    public static function seo_box() {
        if (self::seo_plugin_active()) return;
        foreach (['page', 'post'] as $type) {
            add_meta_box('rackmonk_seo', 'Search & Sharing', [__CLASS__, 'seo_box_content'], $type, 'side', 'default');
        }
    }

    public static function seo_box_content($post) {
        wp_nonce_field('rackmonk_seo_' . $post->ID, 'rackmonk_seo_nonce');
        $value = get_post_meta($post->ID, '_rackmonk_meta_description', true);
        echo '<p><label for="rackmonk_meta_description"><strong>Google description</strong></label></p>';
        echo '<textarea id="rackmonk_meta_description" name="rackmonk_meta_description" rows="4" maxlength="320" style="width:100%">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Shown under the page title in Google and when the link is shared. About 150–160 characters. The Featured Image is used as the share picture.</p>';
    }

    public static function save_seo($post_id, $post) {
        if (!isset($_POST['rackmonk_seo_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rackmonk_seo_nonce'])), 'rackmonk_seo_' . $post_id)) return;
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) return;
        $value = isset($_POST['rackmonk_meta_description']) ? sanitize_textarea_field(wp_unslash($_POST['rackmonk_meta_description'])) : '';
        if ($value === '') delete_post_meta($post_id, '_rackmonk_meta_description');
        else update_post_meta($post_id, '_rackmonk_meta_description', $value);
    }

    public static function meta_description($default) {
        if (is_singular()) {
            $id = get_queried_object_id();
            $custom = (string) get_post_meta($id, '_rackmonk_meta_description', true);
            if ($custom !== '') return $custom;
            if (has_excerpt($id)) return wp_strip_all_tags(get_the_excerpt($id));
        }
        return $default;
    }

    public static function social_tags() {
        if (self::seo_plugin_active()) return;
        $description = apply_filters('rackmonk_meta_description', get_bloginfo('description'));
        $url = is_singular() ? get_permalink() : ((is_front_page() || is_home()) ? home_url('/') : '');
        $image = '';
        if (is_singular() && has_post_thumbnail()) $image = get_the_post_thumbnail_url(null, 'full');
        if (!$image && get_theme_mod('custom_logo')) $image = wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full');
        $tags = [
            'og:site_name' => get_bloginfo('name'),
            'og:type' => is_singular('post') ? 'article' : 'website',
            'og:title' => wp_get_document_title(),
            'og:description' => $description,
            'og:url' => $url,
            'og:image' => $image,
        ];
        foreach ($tags as $property => $content) {
            if ($content) echo '<meta property="' . esc_attr($property) . '" content="' . esc_attr($content) . '">' . "\n";
        }
        echo '<meta name="twitter:card" content="' . ($image ? 'summary_large_image' : 'summary') . '">' . "\n";
    }
}

Rackmonk_Client_Tools::boot();

// [rackmonk_year] → current year, so the footer copyright never goes out of date.
add_shortcode('rackmonk_year', static function () { return esc_html(wp_date('Y')); });
