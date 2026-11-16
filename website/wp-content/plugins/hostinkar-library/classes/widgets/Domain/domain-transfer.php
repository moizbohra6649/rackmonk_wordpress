<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Xhosting_Transfer extends Widget_Base {


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
		return 'xhs-domain-transfer';
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
		return esc_html__( 'Domain Transfer', 'xhosting-plugin' );
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
			'--domain-content-option',
			[
				'label' => esc_html__( 'Domain Option', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		
        $this->add_control(
			'bg_img', [
				'label' => esc_html__( 'BG Image', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
			]
		);
		
        $this->add_control(
			'shape', [
				'label' => esc_html__( 'Shape 1', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
			]
		);
		
        $this->add_control(
			'shape2', [
				'label' => esc_html__( 'Shape 2', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
			]
		);
		
        $this->add_control(
			'shape3', [
				'label' => esc_html__( 'Shape 3', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
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
			'search_btn', [
				'label' => esc_html__( 'Transfer Butotn', 'xhosting-plugin' ),
				'default' => esc_html__( 'Transfer Domain', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'tr_btn_link',
			[
				'label' => esc_html__( 'Transfer Button Link', 'xhosting-plugin' ),
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
        $repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'extension_domain', [
				'label'       => __( 'Extension', 'xhosting-library' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( '.com', 'xhosting-library' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'extension_list', [
				'label'       => __( 'Extension List', 'xhosting-library' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'prevent_empty'=> false,
				'default'     => [
					[
						'extension_domain' => __( '.com', 'xhosting-library' )
					],
					[
						'extension_domain' => __( '.net', 'xhosting-library' )
					],
					[
						'extension_domain' => __( '.org', 'xhosting-library' )
					],
					[
						'extension_domain' => __( '.online', 'xhosting-library' )
					],
					[
						'extension_domain' => __( '.ai', 'xhosting-library' )
					],
					[
						'extension_domain' => __( '.business', 'xhosting-library' )
					],
					[
						'extension_domain' => __( '.edu', 'xhosting-library' )
					],
					[
						'extension_domain' => __( '.asia', 'xhosting-library' )
					],
					[
						'extension_domain' => __( '.app', 'xhosting-library' )
					],
				],
				'title_field' => '{{{ extension_domain }}}',
			]
		);
        $this->add_control(
			'text', [
				'label' => esc_html__( 'Text', 'xhosting-plugin' ),
				'default' => esc_html__( 'Need a new domain name?', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
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
        require __DIR__ . '/domain-template/domain-transfer.php';
    }


}


Plugin::instance()->widgets_manager->register( new Xhosting_Transfer() );
