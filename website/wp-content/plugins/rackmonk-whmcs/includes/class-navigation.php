<?php
/** WordPress-managed navigation, with safe icon choices and hosting descriptions. */
if (!defined('ABSPATH')) exit;

class Rackmonk_Navigation {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'assets'], 30);
        add_action('wp_nav_menu_item_custom_fields', [$this, 'fields'], 20, 2);
        add_action('wp_update_nav_menu_item', [$this, 'save'], 20, 2);
        add_filter('nav_menu_item_title', [$this, 'title'], 20, 4);
        add_filter('nav_menu_link_attributes', [$this, 'attributes'], 20, 4);
    }

    public static function content_defaults() {
        return [
            'menu_caption' => 'HOSTING SOLUTIONS', 'menu_heading' => 'Find your perfect hosting.',
            'menu_view_all' => 'Compare all plans', 'menu_footer' => 'Not sure where to start?',
            'menu_help_text' => 'Talk to our team', 'menu_help_url' => home_url('/contact-us/'),
        ];
    }

    public static function copy($key) {
        return Rackmonk_Site_Content::text($key, self::content_defaults()[$key] ?? '');
    }

    public function assets() {
        $path = get_template_directory() . '/assets/css/rackmonk-navigation.css';
        if (is_file($path)) wp_enqueue_style('rackmonk-navigation', get_template_directory_uri() . '/assets/css/rackmonk-navigation.css', [], filemtime($path));
    }

    public static function icons() {
        return ['home' => 'Home', 'hosting' => 'Hosting', 'layers' => 'Shared hosting', 'cloud' => 'Cloud', 'server' => 'Server / VPS', 'wordpress' => 'WordPress', 'migration' => 'Migration', 'globe' => 'Domain', 'info' => 'About', 'support' => 'Support / Contact', 'user' => 'Account', 'grid' => 'Pages', 'arrow' => 'Arrow'];
    }

    public static function icon($key) {
        $paths = [
            'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>',
            'hosting' => '<rect x="3" y="3" width="18" height="7" rx="2"/><rect x="3" y="14" width="18" height="7" rx="2"/><path d="M7 6.5h.01M7 17.5h.01M12 6.5h5M12 17.5h5"/>',
            'layers' => '<path d="m12 3 10 5-10 5L2 8zM2 12l10 5 10-5M2 16l10 5 10-5"/>',
            'cloud' => '<path d="M7 19a5 5 0 0 1-1-9.9A6 6 0 0 1 17.7 8a5.5 5.5 0 0 1 .8 11z"/>',
            'server' => '<rect x="4" y="2" width="16" height="20" rx="3"/><path d="M4 9h16M4 16h16M8 5.5h.01M8 12.5h.01M8 19.5h.01M12 5.5h4M12 12.5h4M12 19.5h4"/>',
            'wordpress' => '<circle cx="12" cy="12" r="9"/><path d="m5 7 4 11 3-8 3 8 4-11M4 7h5M15 7h5"/>',
            'migration' => '<path d="M4 7h15m-4-4 4 4-4 4M20 17H5m4-4-4 4 4 4"/>',
            'globe' => '<circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18M5 6h14M5 18h14"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
            'support' => '<path d="M4 13v-2a8 8 0 0 1 16 0v6a4 4 0 0 1-4 4h-3"/><rect x="2" y="11" width="5" height="7" rx="2"/><rect x="17" y="11" width="5" height="7" rx="2"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        ];
        if (!isset($paths[$key])) return '';
        return '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$key] . '</svg>';
    }

    public static function details($item) {
        $slug = $item->object === 'page' ? get_post_field('post_name', $item->object_id) : sanitize_title($item->title);
        $defaults = [
            'home2' => ['home', ''], 'home' => ['home', ''], 'pricing' => ['hosting', ''],
            'shared-hosting-2' => ['layers', 'A simple start for websites and small businesses.'],
            'shared-hosting' => ['layers', 'A simple start for websites and small businesses.'],
            'cloud-hosting' => ['cloud', 'Room to grow for your next business website.'],
            'vps-hosting' => ['server', 'More control for applications and ambitious projects.'],
            'wp-hosting' => ['wordpress', 'A home for your blogs and WordPress websites.'],
            'wordpress-hosting' => ['wordpress', 'A home for your blogs and WordPress websites.'],
            'migration' => ['migration', 'Bring your existing website over to Rackmonk.'],
            'domain' => ['globe', ''], 'about-us' => ['info', ''], 'contact-us' => ['support', ''],
            'my-account' => ['user', ''], 'register' => ['user', ''], 'pages' => ['grid', ''],
        ];
        $default = $defaults[$slug] ?? ['arrow', ''];
        $icon = get_post_meta($item->ID, '_rackmonk_menu_icon', true);
        if ($icon === '') $icon = $default[0];
        $description = metadata_exists('post', $item->ID, '_rackmonk_menu_description') ? get_post_meta($item->ID, '_rackmonk_menu_description', true) : ($item->description ?: $default[1]);
        return ['icon' => $icon, 'description' => $description, 'hosting' => $slug === 'pricing' || sanitize_title($item->title) === 'hosting'];
    }

    public function fields($id, $item) {
        $details = self::details($item);
        wp_nonce_field('rackmonk_navigation_' . $id, 'rackmonk_navigation_nonce[' . $id . ']');
        echo '<p class="description description-wide"><label for="rm-menu-icon-' . (int) $id . '">Navigation icon<br><select id="rm-menu-icon-' . (int) $id . '" name="rackmonk_menu_icon[' . (int) $id . ']" class="widefat">';
        $saved = get_post_meta($id, '_rackmonk_menu_icon', true);
        foreach (array_merge(['' => 'Automatic', 'none' => 'No icon'], self::icons()) as $value => $label) echo '<option value="' . esc_attr($value) . '" ' . selected($saved, $value, false) . '>' . esc_html($label) . '</option>';
        echo '</select></label></p><p class="description description-wide"><label for="rm-menu-desc-' . (int) $id . '">Dropdown description<br><textarea id="rm-menu-desc-' . (int) $id . '" class="widefat" rows="2" maxlength="160" name="rackmonk_menu_description[' . (int) $id . ']">' . esc_textarea($details['description']) . '</textarea></label></p>';
    }

    public function save($menu_id, $id) {
        if (!current_user_can('edit_theme_options')) return;
        $nonces = $_POST['rackmonk_navigation_nonce'] ?? [];
        $icons = $_POST['rackmonk_menu_icon'] ?? [];
        $descriptions = $_POST['rackmonk_menu_description'] ?? [];
        if (!is_array($nonces) || !is_array($icons) || !is_array($descriptions) || !is_string($nonces[$id] ?? null) || !wp_verify_nonce(wp_unslash($nonces[$id]), 'rackmonk_navigation_' . $id)) return;
        if (isset($icons[$id]) && is_string($icons[$id])) {
            $key = sanitize_key(wp_unslash($icons[$id]));
            if ($key === '' || $key === 'none' || array_key_exists($key, self::icons())) update_post_meta($id, '_rackmonk_menu_icon', $key);
        }
        if (isset($descriptions[$id]) && is_string($descriptions[$id])) update_post_meta($id, '_rackmonk_menu_description', wp_slash(mb_substr(sanitize_text_field(wp_unslash($descriptions[$id])), 0, 160)));
    }

    public function title($title, $item, $args, $depth) {
        if (empty($args->rackmonk_mega)) return $title;
        $details = self::details($item);
        $icon = self::icon($details['icon']);
        $html = $icon ? '<span class="rm-nav-icon">' . $icon . '</span>' : '';
        $html .= '<span class="rm-nav-copy"><span class="rm-nav-label">' . esc_html(wp_strip_all_tags($title)) . '</span>';
        if ($depth > 0 && $details['description'] !== '') $html .= '<span class="rm-nav-description">' . esc_html($details['description']) . '</span>';
        $html .= '</span>';
        if ($depth > 0) $html .= '<span class="rm-nav-arrow">' . self::icon('arrow') . '</span>';
        return $html;
    }

    public function attributes($attributes, $item, $args, $depth) {
        if (empty($args->rackmonk_mega)) return $attributes;
        $details = self::details($item);
        $attributes['class'] = trim(($attributes['class'] ?? '') . ($depth > 0 ? ' rm-mega-link' : ' rm-nav-link'));
        $attributes['data-menu-icon'] = $details['icon'];
        return $attributes;
    }
}

class Rackmonk_Navigation_Walker extends Walker_Nav_Menu {
    private $root;

    public function start_el(&$output, $data_object, $depth = 0, $args = null, $current_object_id = 0) {
        if ($depth === 0) $this->root = $data_object;
        parent::start_el($output, $data_object, $depth, $args, $current_object_id);
    }

    public function start_lvl(&$output, $depth = 0, $args = null) {
        if ($depth !== 0) { parent::start_lvl($output, $depth, $args); return; }
        $hosting = Rackmonk_Navigation::details($this->root)['hosting'];
        $caption = $hosting ? Rackmonk_Navigation::copy('menu_caption') : 'EXPLORE RACKMONK';
        $heading = $hosting ? Rackmonk_Navigation::copy('menu_heading') : wp_strip_all_tags($this->root->title);
        $overview = $hosting ? Rackmonk_Navigation::copy('menu_view_all') : 'View all';
        $output .= '<div class="rm-mega-panel">';
        if ($caption !== '' || $heading !== '' || $overview !== '') {
            $output .= '<div class="rm-mega-heading"><div>';
            if ($caption !== '') $output .= '<span class="rm-mega-caption">' . esc_html($caption) . '</span>';
            if ($heading !== '') $output .= '<h3 class="rm-mega-title">' . esc_html($heading) . '</h3>';
            $output .= '</div>';
            if ($overview !== '') $output .= '<a class="rm-mega-overview" href="' . esc_url($this->root->url) . '">' . esc_html($overview) . Rackmonk_Navigation::icon('arrow') . '</a>';
            $output .= '</div>';
        }
        $output .= '<ul class="sub-menu rm-mega-grid">';
    }

    public function end_lvl(&$output, $depth = 0, $args = null) {
        if ($depth !== 0) { parent::end_lvl($output, $depth, $args); return; }
        $text = Rackmonk_Navigation::copy('menu_footer');
        $help = Rackmonk_Navigation::copy('menu_help_text');
        $url = esc_url(Rackmonk_Navigation::copy('menu_help_url'));
        $output .= '</ul>';
        if ($text !== '' || ($help !== '' && $url !== '')) {
            $output .= '<div class="rm-mega-footer">';
            if ($text !== '') $output .= '<span>' . esc_html($text) . '</span>';
            if ($help !== '' && $url !== '') $output .= '<a href="' . $url . '">' . esc_html($help) . Rackmonk_Navigation::icon('arrow') . '</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
    }
}
