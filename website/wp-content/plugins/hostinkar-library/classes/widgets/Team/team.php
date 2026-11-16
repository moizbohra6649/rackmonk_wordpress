<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class xHosting_Team extends Widget_Base {


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
		return 'xhosting-team';
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
		return esc_html__( 'xHosting Team', 'xhosting-library' );
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
			'__team__style__',
			[
				'label' => esc_html__( 'Team Style Select', 'xhosting-library' ),
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
					'1'  => esc_html__( 'Style 1', 'xhosting-library' )
				]
			]
		);
        
        $this->end_controls_section();

		$this->start_controls_section(
			'team__general',
			[
				'label' => esc_html__( 'Team Option', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
        
        $this->add_control(
			'image', [
				'label' => esc_html__( 'Image', 'xhosting-library' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
			]
		);
        
        $this->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
        
        $this->add_control(
			'designation', [
				'label' => esc_html__( 'Designation', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        

		$this->end_controls_section();

        $this->start_controls_section(
			'--team--box--style',
			[
				'label' => esc_html__( 'team Box  Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
        $this->add_responsive_control(
            'padding',
            [
                'label' => esc_html__( 'Box Padding', 'textdomain' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'selectors' => [
                    '{{WRAPPER}} .hosting__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .hosting__item-two' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ],
            ]
        );
        $this->add_responsive_control(
            'box-round',
            [
                'label' => esc_html__( 'Box Round', 'textdomain' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'selectors' => [
                    '{{WRAPPER}} .hosting__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .hosting__item-two' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ],
            ]
        );
		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'button_bg_hover_color',
				'types' => [ 'gradient', 'classic'],
				'exclude' => [ 'image' ],
				'selector' => '
					{{WRAPPER}} .hosting__item-two,
					{{WRAPPER}} .hosting__item-five
				',
				'condition' => [
					'style' => ['2', '4']
				],
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Box BG Color ', 'xhosting-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'xhosting-plugin' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);
		$this->end_controls_section();

        $this->start_controls_section(
			'--team--title--style',
			[
				'label' => esc_html__( 'team Content  Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_control(
			'--team-icon-style--',
			[
				'label' => esc_html__( 'team Icon Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
        $this->add_control(
			'icon-box-color',
			[
				'label' => esc_html__( 'Icon Box BG Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hosting__icon-two' => 'background: {{VALUE}}',
				],
				'condition' => [
					'style' => ['2']
				],
			]
		);
        $this->add_control(
			'icon-box-border-color',
			[
				'label' => esc_html__( 'Icon Box Border BG Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hosting__icon-two' => 'border-color: {{VALUE}}',
				],
				'condition' => [
					'style' => ['2']
				],
			]
		);
        $this->add_control(
			'icon-color',
			[
				'label' => esc_html__( 'Icon Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hosting__icon' => 'color: {{VALUE}}',
					'{{WRAPPER}} .hosting__icon-tw' => 'color: {{VALUE}}'
				],
			]
		);

		$this->add_control(
			'--team-title-style--',
			[
				'label' => esc_html__( 'team Title Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		
		$this->add_control(
			'title-color',
			[
				'label' => esc_html__( 'Title Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title' => 'color: {{VALUE}}'
				],
			]
		);
		
		$this->add_control(
			'colord-title-color',
			[
				'label' => esc_html__( 'Title Hover Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title a:hover' => 'color: {{VALUE}}'
				]
			]
		);
		
        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'title-fony',
				'label' => esc_html__( 'Title Font', 'xhosting-library' ),
				'selector' => '
					{{WRAPPER}} .title
				',
			]
		);
		$this->add_control(
			'desc-style--',
			[
				'label' => esc_html__( 'Description Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'style' => ['1']
				],
			]
		);
		$this->add_control(
			'desc-color',
			[
				'label' => esc_html__( 'Descripton Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hosting__content p' => 'color: {{VALUE}}',
					'{{WRAPPER}} .hosting__content-two p' => 'color: {{VALUE}}'
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'dsc-fony',
				'label' => esc_html__( 'Descripton Font', 'xhosting-library' ),
				'selector' => '
					{{WRAPPER}} .hosting__content p,
					{{WRAPPER}} .hosting__content-two p
				',
				'condition' => [
					'style' => ['1']
				],
			]
		);
		
		
		$this->add_control(
			'tp--button-style',
			[
				'label' => esc_html__( 'Button Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'tbs_typography',
				'selector' => '{{WRAPPER}} .tg-btn',
			]
		);
        $this->add_control(
			'tb-padding',
			[
				'label' => esc_html__( 'Padding', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
        $this->add_control(
			'tbr_round',
			[
				'label' => esc_html__( 'Border Radius', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);


        $this->start_controls_tabs(
			'style_tbs_tabs'
		);

		$this->start_controls_tab(
			'style_tbs_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'bdoctor-plugin' ),
			]
		);
        $this->add_control(
			'tbs_btn_text',
			[
				'label' => esc_html__( 'Text Color', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'color: {{VALUE}}',
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'tbs_button_bg_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .tg-btn',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Button BG Color ', 'bdoctor-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'bdoctor-plugin' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);
        $this->end_controls_tab();
        $this->start_controls_tab(
			'styletbs__hover_tab',
			[
				'label' => esc_html__( 'Hover', 'bdoctor-plugin' ),
			]
		);
        $this->add_control(
			'tbs_btn_h_text',
			[
				'label' => esc_html__( 'Text Hover Color', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn:hover' => 'color: {{VALUE}}',
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'tbs_button_bg_hover_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .tg-btn:hover',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Button Hover BG Color ', 'bdoctor-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'bdoctor-plugin' ),
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
		extract( $settings );
		require __DIR__ . '/team-template/team-' . $settings['style'] . '.php';
    }




}


Plugin::instance()->widgets_manager->register( new xHosting_Team() );