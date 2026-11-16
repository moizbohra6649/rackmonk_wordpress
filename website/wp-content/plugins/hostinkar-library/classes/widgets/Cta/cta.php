<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Xhosting_Cta extends Widget_Base {

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
		return 'xhostint-cta';
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
		return esc_html__( 'CTA', 'xhosting-plugin' );
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
			'int_widget_opt',
			[
				'label' => esc_html__( 'Counter Style Select', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
        $this->add_control(
			'style',
			[
				'label' => esc_html__( 'Style', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'  => esc_html__( 'Style 1', 'xhosting-plugin' ),
					'2'  => esc_html__( 'Style 2', 'xhosting-plugin' ),
					'3'  => esc_html__( 'Style 3', 'xhosting-plugin' )
				]
			]
		);
        $this->end_controls_section();

        $this->start_controls_section(
			'--Counter-option',
			[
				'label' => esc_html__( 'Counter Option', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		
        $this->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'gtbus-plugin' ),
				'default' => esc_html__( 'Civil Engineering Contractors', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
        
        $this->add_control(
			'title_shape',
			[
				'label' => esc_html__( 'Title Shape', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'xhosting-library' ),
				'label_off' => esc_html__( 'Hide', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);
        
		 $this->add_control(
			'icon', [
				'label' => esc_html__( 'Icon', 'gtbus-plugin' ),
				'type' => Controls_Manager::ICONS,
                'label_block' => true,
				'condition'      => [
					'title_shape' => 'yes',
					'style' => '3',
                ],
			]
		);
        
		 $this->add_control(
			'description', [
				'label' => esc_html__( 'Description', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
				'condition'      => [
					'style' => '3',
                ],
			]
		);
		$this->add_control(
			'btn_style',
			[
				'label' => esc_html__( 'Button Style', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'  => esc_html__( 'Style 1', 'xhosting-plugin' ),
					'2'  => esc_html__( 'Style 2', 'xhosting-plugin' )
				],
				'condition'      => [
					'style' => '1',
                ],
			]
		);
        $this->add_control(
			'btn_label', [
				'label' => esc_html__( 'Button Label', 'xhosting-plugin' ),
				'default' => esc_html__( 'Learn More', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'btn_link',
			[
				'label' => esc_html__( 'Link', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'xhosting-plugin' ),
				'options' => [ 'url', 'is_external', 'nofollow' ],
				'default' => [
					'url' => '',
					'is_external' => true,
					'nofollow' => true,
				],
				'label_block' => true,
				'dynamic' => [
					'active' => true,
				],
			]
		);
		$this->end_controls_section();
		
		$this->start_controls_section(
			'cta__style',
			[
				'label' => esc_html__( 'Cta Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		
        $this->add_control(
			'cta_box_bg',
			[
				'label' => esc_html__( 'Cta Box BG Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .footer__offer' => 'background: {{VALUE}}'
				],
			]
		);
		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'box_shadow',
				'selector' => '{{WRAPPER}} .footer__offer',
			]
		);
        $this->add_control(
			'title_oclor',
			[
				'label' => esc_html__( 'Title Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'title_st_oclor',
			[
				'label' => esc_html__( 'Title Stylish Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title span' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'cta_icon',
			[
				'label' => esc_html__( 'Cta Icon Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .icon' => 'color: {{VALUE}}'
				],
				'condition'      => [
					'style' => '3',
                ],
			]
		);
        
		// typography
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'title_font_typography',
				'selector' => '
					{{WRAPPER}} .title
				',
			]
		);
		$this->end_controls_section();
		$this->start_controls_section(
			'slider_button_one',
			[
				'label' => esc_html__( 'Button Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'm_b_typography',
				'selector' => '
					{{WRAPPER}} .tg-btn,
					{{WRAPPER}} .tg-link-btn
				',
			]
		);
        $this->add_control(
			'padding',
			[
				'label' => esc_html__( 'Padding', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .tg-link-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
			]
		);
        $this->add_control(
			'b_round',
			[
				'label' => esc_html__( 'Border Radius', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .tg-link-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
			]
		);


        $this->start_controls_tabs(
			'style_tabs'
		);

		$this->start_controls_tab(
			'style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'xhosting-plugin' ),
			]
		);
        $this->add_control(
			'btn_text',
			[
				'label' => esc_html__( 'Text Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'color: {{VALUE}}',
					'{{WRAPPER}} .tg-link-btn' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'btn_border_text',
			[
				'label' => esc_html__( 'Border Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'border-color: {{VALUE}}',
				],
			]
		);
        $this->add_control(
			'btn_underline_text',
			[
				'label' => esc_html__( 'Underline Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .faq__content .tg-link-btn::after, .faq__content .tg-link-btn::before' => 'background: {{VALUE}}',
				],
				'condition' => [
					'style' => [ '3' ],
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'button_bg_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .tg-btn',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Button BG Color ', 'xhosting-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'xhosting-plugin' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);
        $this->end_controls_tab();
        $this->start_controls_tab(
			'style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'xhosting-plugin' ),
			]
		);
        $this->add_control(
			'btn_h_text',
			[
				'label' => esc_html__( 'Text Hover Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn:hover' => 'color: {{VALUE}}',
				],
			]
		);
		 $this->add_control(
			'btn_border_h_text',
			[
				'label' => esc_html__( 'Border Hover Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn:hover' => 'border-color: {{VALUE}}',
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'button_bg_hover_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .tg-btn:hover',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Button Hover BG Color ', 'xhosting-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'xhosting-plugin' ),
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
		require __DIR__ . '/cta-template/cta-' . $settings['style'] . '.php';
    }


}


Plugin::instance()->widgets_manager->register( new Xhosting_Cta() );
