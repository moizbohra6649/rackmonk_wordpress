<?php
/**
 * Rackmonk Elementor Dynamic Pricing Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class Rackmonk_Elementor_Pricing_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'rackmonk-dynamic-pricing';
    }

    public function get_title() {
        return esc_html__('Rackmonk Dynamic Pricing', 'rackmonk-whmcs');
    }

    public function get_icon() {
        return 'eicon-price-table';
    }

    public function get_categories() {
        return ['rackmonk-elements', 'xhosting_widgets', 'general'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Pricing Settings', 'rackmonk-whmcs'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $categories = Rackmonk_Plans_Manager::get_instance()->get_categories();
        $cat_options = ['' => esc_html__('All Categories', 'rackmonk-whmcs')];
        foreach ($categories as $cat) {
            $cat_options[$cat['id']] = $cat['name'];
        }

        $this->add_control(
            'selected_category',
            [
                'label'   => esc_html__('Filter by Category', 'rackmonk-whmcs'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => $cat_options,
            ]
        );

        $this->add_control(
            'default_term',
            [
                'label'   => esc_html__('Default Billing Cycle', 'rackmonk-whmcs'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '48',
                'options' => [
                    '48' => esc_html__('48 Months (4 Years - Recommended)', 'rackmonk-whmcs'),
                    '24' => esc_html__('24 Months (2 Years)', 'rackmonk-whmcs'),
                    '12' => esc_html__('12 Months (1 Year)', 'rackmonk-whmcs'),
                    '1'  => esc_html__('1 Month', 'rackmonk-whmcs'),
                ],
            ]
        );

        $this->add_control(
            'show_category_tabs',
            [
                'label'        => esc_html__('Show Category Tabs', 'rackmonk-whmcs'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'rackmonk-whmcs'),
                'label_off'    => esc_html__('Hide', 'rackmonk-whmcs'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_cycle_tabs',
            [
                'label'        => esc_html__('Show Duration Switcher', 'rackmonk-whmcs'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'rackmonk-whmcs'),
                'label_off'    => esc_html__('Hide', 'rackmonk-whmcs'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $gid = !empty($settings['selected_category']) ? $settings['selected_category'] : '';
        $default_term = !empty($settings['default_term']) ? $settings['default_term'] : '48';
        $show_tabs = !empty($settings['show_category_tabs']) ? $settings['show_category_tabs'] : 'yes';
        $show_cycles = !empty($settings['show_cycle_tabs']) ? $settings['show_cycle_tabs'] : 'yes';

        echo do_shortcode(sprintf(
            '[rackmonk_plans gid="%s" default_term="%s" show_tabs="%s" show_cycles="%s"]',
            esc_attr($gid),
            esc_attr($default_term),
            esc_attr($show_tabs),
            esc_attr($show_cycles)
        ));
    }
}

