<?php
/**
 * @Template: class-megamenu-walker.php
 * @since: 1.0
 * @author: byteflows Themes
 * @descriptions:
 */

if (!defined('ABSPATH')) {
    die();
}

class Rs_Mega_Menu_Walker extends Walker_Nav_Menu {

    private $is_megamenu = false;

    public function start_lvl(&$output, $depth = 0, $args = array()) {
        // Skip submenu ul if it's a mega menu
        if ($this->is_megamenu) return;

        $indent = str_repeat("\t", $depth);
        $output .= "\n$indent<ul class=\"sub-menu\">\n";
    }

    public function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
        $item_html = '';
        $megamenu_enabled = apply_filters('th_enable_megamenu', false);
        $this->is_megamenu = false;

        if ('[divider]' === $item->title) {
            $output .= '<li class="menu-item-divider"></li>';
            return;
        }

        // Add custom classes
        $extra_menu_custom = apply_filters("ct_menu_edit", array());
        foreach ($extra_menu_custom as $key => $f) {
            $val = get_post_meta($item->ID, '_menu_item_' . $key, true);
            if (!empty($val)) {
                $item->classes[] = $val;
            }
        }

        // Enqueue script for one-page navigation
        if ($item->ct_onepage === 'is-one-page' && !wp_script_is('single-page-nav')) {
            wp_register_script('single-page-nav', CT_URL . 'assets/js/lib/jquery.singlePageNav.js', ['jquery', 'elementor-waypoints'], 'all', true);
            wp_localize_script('single-page-nav', 'one_page_options', ['filter' => '.is-one-page', 'speed' => '1000']);
            wp_enqueue_script('single-page-nav');
        }

        add_filter('nav_menu_link_attributes', function ($atts, $item) {
            if (isset($item->ct_onepage) && $item->ct_onepage === 'is-one-page') {
                $atts['class'] = isset($atts['class']) ? $atts['class'] . ' is-one-page' : 'is-one-page';
                if (!empty($item->ct_custom_class)) {
                    $atts['class'] .= ' ' . $item->ct_custom_class;
                }
                if (isset($item->ct_onepage_offset)) {
                    $atts['data-onepage-offset'] = $item->ct_onepage_offset;
                }
            } elseif (!empty($item->ct_custom_class)) {
                $atts['class'] = isset($atts['class']) ? $atts['class'] . ' ' . $item->ct_custom_class : $item->ct_custom_class;
            }
            return $atts;
        }, 10, 2);

        // Mega menu logic
        if (!empty($item->ct_megaprofile) && $megamenu_enabled) {
            $item->classes[] = 'tg-mega-menu-has-children';
            $item->classes[] = 'megamenu-style-alt';
            $item->classes[] = 'menu-item-has-children';
            $this->is_megamenu = true;
        }

        // Icon management
        $args->old_link_before = $args->link_before;
        $args->old_link_after  = $args->link_after;

        if (!empty($item->ct_icon)) {
            $icon_html = '<span class="link-icon ' . esc_attr($item->ct_icon_position ?? 'left') . '-icon"><i class="' . esc_attr($item->ct_icon) . '"></i></span>';
            if (($item->ct_icon_position ?? 'left') === 'left') {
                $args->link_before = $icon_html . $args->link_before;
            } else {
                $args->link_after .= $icon_html;
            }
        }

        if (!empty($item->ct_menu_marker)) {
            $args->link_after = '<cite class="ct-menu-item-marker">' . esc_attr($item->ct_menu_marker) . '</cite>' . $args->link_after;
        }

        parent::start_el($item_html, $item, $depth, $args, $id);

        // Reset link before/after
        $args->link_before = $args->old_link_before ?? '';
        $args->link_after  = $args->old_link_after ?? '';

        // Append mega menu content directly (no <ul>)
        if ($this->is_megamenu && !empty($item->ct_megaprofile)) {
            $item_html .= $this->get_megamenu($item->ct_megaprofile);
        }

        $output .= $item_html;
    }

    public function end_lvl(&$output, $depth = 0, $args = array()) {
        if ($this->is_megamenu) return;

        $indent = str_repeat("\t", $depth);
        $output .= "$indent</ul>\n";
    }

    public function end_el(&$output, $item, $depth = 0, $args = array()) {
        $output .= "</li>\n";
    }

    public function display_element($element, &$children_elements, $max_depth, $depth, $args, &$output) {
        $element->hasChildren = !empty($children_elements[$element->ID]);
        return parent::display_element($element, $children_elements, $max_depth, $depth, $args, $output);
    }


    public function get_megamenu($id) {
    $megamenu_enabled = apply_filters('th_enable_megamenu', false);
        if (!$megamenu_enabled || !$id) {
            return '';
        }

        $content = \Elementor\Plugin::$instance->frontend->get_builder_content($id);
        $css = $this->get_vc_custom_css($id);

        // Get menu item post object
        $post = get_post($id);
        
        // Dynamic class based on menu post title (you can change this logic)
        $custom_class = sanitize_html_class(sanitize_title($post->post_title));

        return '<div class="tg-mega-menu-wrap ' . esc_attr($custom_class) . '">' . $css . $content . '</div>';
    }

    public function get_vc_custom_css($id) {
        $out = '';
        if (!$id) return '';

        $post_custom_css = get_post_meta($id, '_wpb_post_custom_css', true);
        if (!empty($post_custom_css)) {
            $out .= '<style data-type="vc_custom-css">' . strip_tags($post_custom_css) . '</style>';
        }

        $shortcodes_custom_css = get_post_meta($id, '_wpb_shortcodes_custom_css', true);
        if (!empty($shortcodes_custom_css)) {
            $out .= '<style data-type="vc_shortcodes-custom-css">' . strip_tags($shortcodes_custom_css) . '</style>';
        }

        return $out;
    }
    
}
