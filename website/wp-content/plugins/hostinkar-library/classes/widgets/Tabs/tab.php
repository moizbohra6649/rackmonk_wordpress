<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Xhosting_Tabs extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve Elementor widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'xhosting-tab';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve Elementor widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Xhosting Tab', 'xhosting-plugin' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve Elementor widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'xhosting-custom-icon';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the Elementor widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'xhosting_widgets' ];
	}


	protected function register_controls() {

        $this->start_controls_section(
			'int_tab_opt',
			[
				'label' => esc_html__( 'Tab Style Select', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
        $this->add_control(
			'style',
			[
				'label' => esc_html__( 'Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'  => esc_html__( 'Style 1', 'xhosting-library' ),
					'2'  => esc_html__( 'Style 2', 'xhosting-library' )
				]
			]
		);
        $this->end_controls_section();
		
        $this->start_controls_section(
			'--tabs-option',
			[
				'label' => esc_html__( 'Tab Option', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
        
        $repeater = new \Elementor\Repeater();
        $repeater->add_control(
			'style',
			[
				'label' => esc_html__( 'Layout Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'  => esc_html__( 'Style 1', 'xhosting-library' ),
					'2'  => esc_html__( 'Style 2', 'xhosting-library' )
				]
			]
		);
        $repeater->add_control(
			'shape', [
				'label' => esc_html__( 'Tab Shape Image', 'gesto-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
			]
		);
        $repeater->add_control(
			'content_img', [
				'label' => esc_html__( 'Content Image Image', 'gesto-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
			]
		);
       
        $repeater->add_control(
			'tb_title', [
				'label' => esc_html__( 'Tab Title', 'xhosting-plugin' ),
				'default' => esc_html__( 'Planning', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['2'],
				],
			]
		);
        $repeater->add_control(
			'tb_desc', [
				'label' => esc_html__( 'Tab Description', 'xhosting-plugin' ),
				'default' => esc_html__( 'Planning', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
				'condition' => [
					'style' => ['2'],
				],
			]
		);
       
        $repeater->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'xhosting-plugin' ),
				'default' => esc_html__( 'Planning', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		
        $repeater->add_control(
			'description', [
				'label' => esc_html__( 'Description', 'xhosting-plugin' ),
				'default' => esc_html__( 'Explore hosting options for your website, from shared hosting to cloud and VPS services.', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
		$repeater->add_control(
			'btn_label', [
				'label' => esc_html__( 'Button Label', 'xhosting-plugin' ),
				'default' => esc_html__( 'Learn More', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        $repeater->add_control(
			'btn_link', [
				'label' => esc_html__( 'Button Link', 'xhosting-plugin' ),
				'type' => Controls_Manager::URL,
                'default' => [
					'url' => '',
					'is_external' => true,
					'nofollow' => true,
					// 'custom_attributes' => '',
				],
				'label_block' => true,
			]
		);
		$repeater->add_control(
            'lists',
            [
                'label' => __('List Item', 'xhosting-plugin'),
                'type' => Controls_Manager::REPEATER,
				'prevent_empty' => false,
				'condition' => [
                    'style' => ['2'],
                ],
                'fields' => [
                    // social icon
                    [
                        'name' => 'icon',
                        'label' => __('List Icon', 'xhosting-plugin'),
                        'type' => Controls_Manager::ICONS,
                        'default' => [
                            'value' => 'fab fa-facebook-f',
                            'library' => 'fa-solid',
                        ],
                    ],
                    // social link
                    [
                        'name' => 'title',
                        'label' => __('List Title', 'xhosting-plugin'),
                        'type' => Controls_Manager::TEXTAREA,
                    ],
                ],
            ],
        );
		$repeater->add_control(
			'box_color',
			[
				'label' => esc_html__( 'Box BG Color', 'textdomain' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .success__item-four' => 'background: {{VALUE}}',
				],
				'condition'      => [
                    'style' => '2',
                ],
			]
		);
        $this->add_control(
			'tabs',
			[
				'label' => esc_html__( 'Add Tab Item', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
                'title_field' => '{{{ title }}}',
				'prevent_empty' => false,
			]
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'tab__active_style',
			[
				'label' => esc_html__( 'Tab Active Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

        $this->add_control(
			'tb_active_color',
			[
				'label' => esc_html__( 'Tab Active Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .success__tab-wrap .nav-link.active' => 'border-color: {{VALUE}}',
				],
			]
		);
        
		$this->end_controls_section();

		$this->start_controls_section(
			'tabs__title_style',
			[
				'label' => esc_html__( 'Title Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'ttl_margin',
			[
				'label' => esc_html__( 'Title Margin', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .elementor-gt-heading' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
        $this->add_control(
			'title_color',
			[
				'label' => esc_html__( 'Title Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-gt-heading' => 'color: {{VALUE}}',
				],
			]
		);
        
		// typography
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[

				'name' => 'm_s_typography',
				'selector' => '{{WRAPPER}} .elementor-gt-heading',
			]
		);


		$this->end_controls_section();
		$this->start_controls_section(
			'desc_style',
			[
				'label' => esc_html__( 'Description Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'desc_margin',
			[
				'label' => esc_html__( 'Desc Margin', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .fx-para-1' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
        $this->add_control(
			'desc_color',
			[
				'label' => esc_html__( 'Desc Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .fx-para-1' => 'color: {{VALUE}}',
				],
			]
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[

				'name' => 'desc_typography',
				'selector' => '{{WRAPPER}} .fx-para-1',
			]
		);
		$this->end_controls_section();

        // feature style
		$this->start_controls_section(
			'--button_one',
			[
				'label' => esc_html__( 'Button Style', 'goyto-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'm_b_typography',
				'selector' => '{{WRAPPER}} .fx-pr-btn-1',
			]
		);
        $this->add_control(
			'padding',
			[
				'label' => esc_html__( 'Padding', 'goyto-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .fx-pr-btn-1' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
        $this->add_control(
			'b_round',
			[
				'label' => esc_html__( 'Border Radius', 'goyto-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .fx-pr-btn-1' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);


        $this->start_controls_tabs(
			'style_tabs'
		);

		$this->start_controls_tab(
			'style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'goyto-plugin' ),
			]
		);
        $this->add_control(
			'btn_text',
			[
				'label' => esc_html__( 'Text Color', 'goyto-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .fx-pr-btn-1' => 'color: {{VALUE}}',
					'{{WRAPPER}} .fx-pr-btn-1:is(.has-hover-white) .text::before' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'button_bg_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .fx-pr-btn-1',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Button BG Color ', 'goyto-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'goyto-plugin' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);
        $this->end_controls_tab();
        $this->start_controls_tab(
			'style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'goyto-plugin' ),
			]
		);
        $this->add_control(
			'btn_h_text',
			[
				'label' => esc_html__( 'Text Hovwe Color', 'goyto-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .fx-pr-btn-1:hover' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'button_bg_hover_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .fx-pr-btn-1:hover',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Button Hover BG Color ', 'goyto-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'goyto-plugin' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);
        $this->end_controls_tab();
		$this->end_controls_tabs();
        $this->end_controls_section();

	}


	protected function render() {
		$settings = $this->get_settings_for_display();
        require __DIR__ . '/tab-template/tab-' . $settings['style'] . '.php';
    }


}


Plugin::instance()->widgets_manager->register( new Xhosting_Tabs() );
