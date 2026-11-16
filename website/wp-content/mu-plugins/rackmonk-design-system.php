<?php
/**
 * Plugin Name: Rackmonk Design System
 * Description: Material 3 style design system. One "Theme Color" (Appearance → Customize → Theme Color) drives the whole palette, Elementor Global Colors and the vector illustrations.
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/rackmonk-design/class-palette.php';
require_once __DIR__ . '/rackmonk-design/class-vectors.php';

final class Rackmonk_Design_System {
    const SEED_OPTION = 'rackmonk_seed_color';
    const VERSION = '1.0.0';
    private static $syncing = false;

    public static function boot() {
        add_action('wp_head', [__CLASS__, 'print_tokens'], 1);
        add_action('admin_head', [__CLASS__, 'print_tokens'], 1);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue'], 999);
        add_action('customize_register', [__CLASS__, 'customizer']);
        add_action('customize_save_after', [__CLASS__, 'after_customizer_save']);
        add_action('update_option_' . self::SEED_OPTION, [__CLASS__, 'seed_changed'], 10, 2);
        add_action('add_option_' . self::SEED_OPTION, static function ($option, $value) { self::seed_changed('', $value); }, 10, 2);
        add_action('update_option_' . self::ON_PRIMARY_OPTION, static function () { self::apply(self::seed()); });
        add_action('add_option_' . self::ON_PRIMARY_OPTION, static function () { self::apply(self::seed()); });
        add_action('elementor/document/after_save', [__CLASS__, 'elementor_kit_saved'], 10, 1);
        add_filter('body_class', static function ($classes) { $classes[] = 'rm-m3'; return $classes; });
        add_action('wp_footer', static function () { echo '<div class="rm-fab-spacer" aria-hidden="true"></div>'; }, 1);
    }

    public static function seed() {
        return Rackmonk_Palette::normalize(get_option(self::SEED_OPTION, Rackmonk_Palette::DEFAULT_SEED));
    }

    const ON_PRIMARY_OPTION = 'rackmonk_on_primary';

    public static function roles() {
        static $cache = [];
        $seed = self::seed();
        $mode = self::on_primary_mode();
        if (!isset($cache[$seed . $mode])) $cache[$seed . $mode] = self::with_on_primary(Rackmonk_Palette::roles($seed), $mode);
        return $cache[$seed . $mode];
    }

    public static function on_primary_mode() {
        $mode = get_option(self::ON_PRIMARY_OPTION, 'white');
        return in_array($mode, ['white', 'dark', 'auto'], true) ? $mode : 'white';
    }

    /** Text/icon colour on filled (primary) buttons: white by default, or dark / automatic. */
    private static function with_on_primary(array $r, $mode) {
        if ($mode === 'white') $r['on-primary'] = '#FFFFFF';
        elseif ($mode === 'dark') $r['on-primary'] = $r['on-primary-container'];
        // Hovered filled button: darker tone under white text, lighter tone under dark text.
        $r['primary-hover'] = Rackmonk_Palette::luminance($r['on-primary']) > 0.5 ? $r['primary-strong'] : $r['primary-fixed-dim'];
        return $r;
    }

    /** CSS custom properties: --md-sys-color-<role>. Printed first so every stylesheet can use them. */
    public static function print_tokens() {
        $css = ':root{';
        foreach (self::roles() as $role => $hex) {
            [$r, $g, $b] = Rackmonk_Palette::rgb($hex);
            $css .= "--md-sys-color-{$role}:{$hex};--md-sys-color-{$role}-rgb:{$r},{$g},{$b};";
        }
        $css .= '}';
        echo '<style id="rackmonk-md-tokens">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- values are generated hex colours.
    }

    public static function enqueue() {
        $file = __DIR__ . '/rackmonk-design/m3.css';
        $deps = wp_style_is('xhosting-main', 'registered') ? ['xhosting-main'] : [];
        wp_enqueue_style('rackmonk-m3', content_url('mu-plugins/rackmonk-design/m3.css'), $deps, filemtime($file));
    }

    /* ----------------------------------------------------------------- Customizer */

    public static function customizer($wp_customize) {
        $wp_customize->add_section('rackmonk_theme_color', [
            'title'       => 'Theme Color',
            'priority'    => 21,
            'description' => 'Choose one brand colour. Buttons, links, highlights, cards, backgrounds, Elementor Global Colors and all the website illustrations are generated from it automatically. Click “Publish” to apply.',
        ]);
        $wp_customize->add_setting(self::SEED_OPTION, [
            'type'              => 'option',
            'default'           => Rackmonk_Palette::DEFAULT_SEED,
            'sanitize_callback' => [Rackmonk_Palette::class, 'normalize'],
            'transport'         => 'refresh',
            'capability'        => 'edit_theme_options',
        ]);
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, self::SEED_OPTION, [
            'label'   => 'Theme color',
            'section' => 'rackmonk_theme_color',
        ]));
        $wp_customize->add_setting(self::ON_PRIMARY_OPTION, [
            'type'              => 'option',
            'default'           => 'white',
            'sanitize_callback' => static function ($v) { return in_array($v, ['white', 'dark', 'auto'], true) ? $v : 'white'; },
            'transport'         => 'refresh',
            'capability'        => 'edit_theme_options',
        ]);
        $wp_customize->add_control(self::ON_PRIMARY_OPTION, [
            'label'       => 'Button text colour',
            'section'     => 'rackmonk_theme_color',
            'type'        => 'radio',
            'choices'     => ['white' => 'White (recommended)', 'dark' => 'Dark', 'auto' => 'Automatic (best contrast)'],
            'description' => 'Text and icons on filled buttons. Choose Dark if you pick a very light theme colour such as yellow.',
        ]);
        $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'rackmonk_theme_color_presets', [
            'label'       => 'Suggested colours',
            'section'     => 'rackmonk_theme_color',
            'settings'    => [],
            'type'        => 'hidden',
            'description' => 'Rackmonk green #17BD54 · Blue #2563EB · Teal #0D9488 · Purple #7C3AED · Orange #EA580C. Paste a code into the field above.',
        ]));
    }

    public static function after_customizer_save() {
        // The option hook covers changes; this also repairs a site whose seed was set before the plugin existed.
        self::apply(self::seed());
    }

    public static function seed_changed($old, $new) {
        if (Rackmonk_Palette::normalize($old) === Rackmonk_Palette::normalize($new) && $old !== '') return;
        self::apply(Rackmonk_Palette::normalize($new));
    }

    /** Push the palette to Elementor and recolour the illustrations. */
    public static function apply($seed) {
        if (self::$syncing) return;
        self::$syncing = true;
        $r = Rackmonk_Palette::roles($seed);
        $kit_id = (int) get_option('elementor_active_kit');
        if ($kit_id) {
            $kit = get_post_meta($kit_id, '_elementor_page_settings', true);
            $kit = is_array($kit) ? $kit : [];
            $kit['system_colors'] = [
                ['_id' => 'primary',   'title' => 'Primary (Theme Color)', 'color' => $r['primary']],
                ['_id' => 'secondary', 'title' => 'Headings',              'color' => $r['on-surface']],
                ['_id' => 'text',      'title' => 'Body Text',             'color' => $r['on-surface-variant']],
                ['_id' => 'accent',    'title' => 'Primary Dark (Hover)',  'color' => $r['primary-strong']],
            ];
            $kit['custom_colors'] = [
                ['_id' => 'rmlight',  'title' => 'Primary Container',     'color' => $r['primary-container-low']],
                ['_id' => 'rmsoft',   'title' => 'Primary Container Strong', 'color' => $r['primary-container']],
                ['_id' => 'rmbg',     'title' => 'Surface Container',     'color' => $r['surface-container-low']],
                ['_id' => 'rmborder', 'title' => 'Outline Variant',       'color' => $r['outline-variant']],
                ['_id' => 'rmmuted',  'title' => 'Outline',               'color' => $r['outline']],
                ['_id' => 'rmwhite',  'title' => 'White',                 'color' => '#FFFFFF'],
                ['_id' => 'rmonprimary', 'title' => 'Text on Primary',    'color' => $r['on-primary']],
                ['_id' => 'rmdark',   'title' => 'Inverse Surface (Dark sections)', 'color' => $r['inverse-surface']],
            ];
            update_post_meta($kit_id, '_elementor_page_settings', $kit);
        }
        if (class_exists('Rackmonk_Vectors')) Rackmonk_Vectors::regenerate_all();
        if (class_exists('\\Elementor\\Plugin')) \Elementor\Plugin::$instance->files_manager->clear_cache();
        self::$syncing = false;
    }

    /** If someone changes Primary in Elementor → Site Settings, adopt it as the theme colour. */
    public static function elementor_kit_saved($document) {
        if (self::$syncing || !method_exists($document, 'get_main_id')) return;
        $id = (int) $document->get_main_id();
        if ($id !== (int) get_option('elementor_active_kit')) return;
        $kit = get_post_meta($id, '_elementor_page_settings', true);
        foreach (($kit['system_colors'] ?? []) as $c) {
            if (($c['_id'] ?? '') === 'primary' && !empty($c['color'])) {
                $new = Rackmonk_Palette::normalize(substr($c['color'], 0, 7));
                if ($new !== self::seed()) update_option(self::SEED_OPTION, $new);
            }
        }
    }
}

Rackmonk_Design_System::boot();
