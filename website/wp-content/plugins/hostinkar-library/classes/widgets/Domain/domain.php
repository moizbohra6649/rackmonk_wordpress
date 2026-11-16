<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Xhosting_Domain extends Widget_Base {


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
		return 'xhs-domain-item';
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
		return esc_html__( 'Domain Box', 'xhosting-plugin' );
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
				'label' => esc_html__( 'Domain Style Select', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
        $this->add_control(
			'style',
			[
				'label' => esc_html__( 'Domain Style', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'  => esc_html__( 'Style 1', 'xhosting-plugin' ),
					'2'  => esc_html__( 'Style 2', 'xhosting-plugin' )
				]
			]
		);
        $this->end_controls_section();

		$this->start_controls_section(
			'--domain-content-option',
			[
				'label' => esc_html__( 'Domain Option', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		
        $this->add_control(
			'extension', [
				'label' => esc_html__( 'Domain Extension', 'xhosting-plugin' ),
				'default' => esc_html__( '.com', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		
        $this->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'xhosting-plugin' ),
				'default' => esc_html__( 'Intro Price', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        
        $this->add_control(
			'price', [
				'label' => esc_html__( 'Dscription', 'xhosting-plugin' ),
				'default' => esc_html__( '$6.99', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        $this->add_control(
			'discount', [
				'label' => esc_html__( 'Discount', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        
        $this->add_control(
			'btn_label', [
				'label' => esc_html__( 'Register Button Label', 'xhosting-plugin' ),
                'default' => esc_html__( 'Register', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        
        $this->add_control(
			'btn_link', [
				'label' => esc_html__( 'Register Button Link', 'xhosting-plugin' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
			]
		);
        
        $this->add_control(
			't_btn_label', [
				'label' => esc_html__( 'Transfer Button Label', 'xhosting-plugin' ),
                'default' => esc_html__( 'Transfer', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        
        $this->add_control(
			't_btn_link', [
				'label' => esc_html__( 'Transfer Button Link', 'xhosting-plugin' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
			]
		);
        
		$this->end_controls_section();

		$this->start_controls_section(
			'Domain--box-stle',
			[
				'label' => esc_html__( 'Box Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_control(
			'box_bg_color',
			[
				'label' => esc_html__( 'Box BG Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .features__item-fourteen' => 'background: {{VALUE}}'
				],
			]
		);
		$this->add_control(
			'box_border_color',
			[
				'label' => esc_html__( 'Box Border Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .features__item-fourteen' => 'border-color: {{VALUE}}'
				],
			]
		);
		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'banner-box-bg',
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'condition' => [
					'style' => ['2']
				],
				'selector' => '				
				{{WRAPPER}} .features__item-five
				',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Banner Box BG Color ', 'bdoctor-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'bdoctor-plugin' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);
		$this->end_controls_section();
		

		$this->start_controls_section(
			'Domain--title-stle',
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
					'{{WRAPPER}} .title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
			]
		);
        $this->add_control(
			'title_color',
			[
				'label' => esc_html__( 'Title Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'colord_color',
			[
				'label' => esc_html__( 'Title Stylish Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title span' => 'color: {{VALUE}}'
				],
				'condition' => [
					'style' => ['2']
				],
			]
		);
        $this->add_control(
			'subtitle_color',
			[
				'label' => esc_html__( 'Sub Title Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .features__item-fourteen .text-overlay' => '-webkit-text-stroke: 1.5px {{VALUE}}'
				],
				'condition' => [
					'style' => ['6']
				],
			]
		);
        
		// typography
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[

				'name' => 'm_s_typography',
				'selector' => '
					{{WRAPPER}} .title
				',
			]
		);


		$this->end_controls_section();
		$this->start_controls_section(
			'Domain--desc-stle',
			[
				'label' => esc_html__( 'Desc Style', 'xhosting-plugin' ),
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
					'{{WRAPPER}} .features__content-four p' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
			]
		);
        $this->add_control(
			'desc_color',
			[
				'label' => esc_html__( 'Desc Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .features__content-four p' => 'color: {{VALUE}}'
				],
			]
		);
        
		// typography
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[

				'name' => 'desc_typography',
				'selector' => '
					{{WRAPPER}} .features__content-four p
				',
			]
		);


		$this->end_controls_section();

		$this->start_controls_section(
			'Domain--icon-stle',
			[
				'label' => esc_html__( 'Icon Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		
        $this->add_control(
			'icon_box_color',
			[
				'label' => esc_html__( 'Icon Box Border Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .features__icon' => 'border-color: {{VALUE}}',
					'{{WRAPPER}} .features__icon-four' => 'border-color: {{VALUE}}'
				],
			]
		);
		
        $this->add_control(
			'icon_color',
			[
				'label' => esc_html__( 'Icon Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .agn-award-3-card .icon i' => 'color: {{VALUE}}',
					'{{WRAPPER}} .features__icon-four' => 'color: {{VALUE}}'
				],
			]
		);
		
        $this->add_control(
			'icon_bg_color',
			[
				'label' => esc_html__( 'Icon BG Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .features__icon-four' => 'background: {{VALUE}}',
				],
			]
		);
        
		$this->end_controls_section();

		$this->start_controls_section(
			'slider_button_one',
			[
				'label' => esc_html__( 'Button Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'style' => [ '2' ],
				],
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
        require __DIR__ . '/domain-template/domain-' . $settings['style'] . '.php';
    }


}


Plugin::instance()->widgets_manager->register( new Xhosting_Domain() );