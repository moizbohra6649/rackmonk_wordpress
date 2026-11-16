<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class xHosting_Service extends Widget_Base {


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
		return 'xhosting-service';
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
		return esc_html__( 'xHosting Service', 'xhosting-library' );
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
			'__service__style__',
			[
				'label' => esc_html__( 'Service Style Select', 'xhosting-library' ),
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
					'2'  => esc_html__( 'Style 2', 'xhosting-library' ),
					'3'  => esc_html__( 'Style 3', 'xhosting-library' ),
					'4'  => esc_html__( 'Style 4', 'xhosting-library' )
				]
			]
		);
        
        $this->end_controls_section();

		$this->start_controls_section(
			'service__general',
			[
				'label' => esc_html__( 'Service Option', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'layout',
			[
				'label' => esc_html__( 'Layout', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'  => esc_html__( 'Style 1', 'xhosting-library' ),
					'2'  => esc_html__( 'Style 2', 'xhosting-library' )
				]
			]
		);
        $repeater->add_control(
			'is_populer',
			[
				'label' => esc_html__( 'Populer Item', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'YES', 'xhosting-library' ),
				'label_off' => esc_html__( 'NO', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'no',
				'condition'      => [
                    'layout' => '1',
                ],
			]
		);
        $repeater->add_control(
			'populer_badge', [
				'label' => esc_html__( 'Populer Text', 'xhosting-library' ),
				'default' => esc_html__( 'Popular', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'condition'      => [
                    'is_populer' => 'yes',
					'layout' => '1',
                ],
			]
		);
        $repeater->add_control(
            'type',
            [
                'label'          => __( 'Icon Type', 'xhosting-library' ),
                'type'           => Controls_Manager::CHOOSE,
                'label_block'    => false,
                'options'        => [
                    'icon'  => [
                        'title' => __( 'Icon', 'xhosting-library' ),
                        'icon'  => 'far fa-smile',
                    ],
                    'image' => [
                        'title' => __( 'Image', 'xhosting-library' ),
                        'icon'  => 'fa fa-image',
                    ],
                ],
                'default'        => 'icon',
                'toggle'         => false,
                'style_transfer' => true,
                
            ]
        );
        
        $repeater->add_control(
			'icon', [
				'label' => esc_html__( 'Icon', 'xhosting-plugin' ),
				'type' => Controls_Manager::ICONS,
                'label_block' => true,
                'condition'      => [
                    'type' => 'icon',
                ],
			]
		);
        $repeater->add_control(
			'icon_img', [
				'label' => esc_html__( 'Icon Image', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
                'condition'      => [
                    'type' => 'image',
                ],
			]
		);
        
        $repeater->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
        
        $repeater->add_control(
			'excerpt', [
				'label' => esc_html__( 'Excerpt', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
        
       
        $repeater->add_control(
			'btn_label', [
				'label' => esc_html__( 'Button Label', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        
       
        $repeater->add_control(
			'link', [
				'label' => esc_html__( 'Link', 'xhosting-library' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
			]
		);
        $repeater->add_control(
			'box---style',
			[
				'label' => esc_html__( 'Box Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition'      => [
                    'layout' => '1',
                ],
			]
		);
        $repeater->add_control(
			'box_color',
			[
				'label' => esc_html__( 'Box BG Color', 'textdomain' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .hosting__item' => 'background: {{VALUE}}',
				],
				'condition'      => [
                    'layout' => '1',
                ],
			]
		);
        
        $repeater->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'border',
				'selector' => '{{WRAPPER}} {{CURRENT_ITEM}} .hosting__item',
				'condition'      => [
                    'layout' => '1',
                ],
			]
		);
        $this->add_control(
			'services',
			[
				'label' => esc_html__( 'Add service Item', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
                'title_field' => '{{{ title }}}',
                'prevent_empty' => false,
				'condition' => [
					'style' => ['1', '3']
				],
			]
		);	

		$this->add_control(
			'is_populer',
			[
				'label' => esc_html__( 'Populer Item', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'YES', 'xhosting-library' ),
				'label_off' => esc_html__( 'NO', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'no',
				'condition' => [
					'style' => ['2']
				],
			]
		);
        $this->add_control(
			'populer_badge', [
				'label' => esc_html__( 'Populer Text', 'xhosting-library' ),
				'default' => esc_html__( 'Most Popular', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'condition'      => [
                    'is_populer' => 'yes',
					'style' => ['2']
                ],
			]
		);
		$this->add_control(
			'is_recommend',
			[
				'label' => esc_html__( 'Recommend Item', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'YES', 'xhosting-library' ),
				'label_off' => esc_html__( 'NO', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'no',
				'condition' => [
					'style' => ['2']
				],
			]
		);
        $this->add_control(
			'recommend_badge', [
				'label' => esc_html__( 'Recommend Text', 'xhosting-library' ),
				'default' => esc_html__( 'Recommended', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'condition'      => [
                    'is_recommend' => 'yes',
					'style' => ['2']
                ],
			]
		);
        $this->add_control(
            'type',
            [
                'label'          => __( 'Icon Type', 'xhosting-library' ),
                'type'           => Controls_Manager::CHOOSE,
                'label_block'    => false,
                'options'        => [
                    'icon'  => [
                        'title' => __( 'Icon', 'xhosting-library' ),
                        'icon'  => 'far fa-smile',
                    ],
                    'image' => [
                        'title' => __( 'Image', 'xhosting-library' ),
                        'icon'  => 'fa fa-image',
                    ],
                ],
                'default'        => 'icon',
                'toggle'         => false,
                'style_transfer' => true,
                'condition' => [
					'style' => ['2', '4']
				],
            ]
        );
        
        $this->add_control(
			'icon', [
				'label' => esc_html__( 'Icon', 'xhosting-plugin' ),
				'type' => Controls_Manager::ICONS,
                'label_block' => true,
                'condition'      => [
                    'type' => 'icon',
					'style' => ['2', '4']
                ],
			]
		);
        $this->add_control(
			'icon_img', [
				'label' => esc_html__( 'Icon Image', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
                'condition'      => [
                    'type' => 'image',
					'style' => ['2', '4']
                ],
			]
		);
        
        $this->add_control(
			'sub_title', [
				'label' => esc_html__( 'Sub Title', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['2']
				],
			]
		);

        $this->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
				'condition' => [
					'style' => ['2', '4']
				],
			]
		);
        
        
        $this->add_control(
			'excerpt', [
				'label' => esc_html__( 'Excerpt', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
				'condition' => [
					'style' => ['2', '4']
				],
			]
		);
        
       
        $this->add_control(
			'btn_label', [
				'label' => esc_html__( 'Button Label', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['2', '4']
				],
			]
		);
        
       
        $this->add_control(
			'link', [
				'label' => esc_html__( 'Link', 'xhosting-library' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
				'condition' => [
					'style' => ['2', '4']
				],
			]
		);

		$this->add_control(
			'rating',
			[
				'label' => esc_html__( 'rating', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 5,
				'step' => .1,
				'default' => 5,
				'condition' => [
					'style' => ['3'],
				],
			]
		);
		
		$this->add_control(
			'rate', [
				'label' => esc_html__( 'Rate Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['3'],
				],
			]
		);
		
		$this->add_control(
			'outoftext', [
				'label' => esc_html__( 'Outof Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['3'],
				],
			]
		);
		$this->add_control(
			'total_rating', [
				'label' => esc_html__( 'Rating Total Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['3'],
				],
			]
		);
		$this->add_control(
			'review_text', [
				'label' => esc_html__( 'Review Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['3'],
				],
			]
		);
        		
		$this->end_controls_section();

        $this->start_controls_section(
			'--service--box--style',
			[
				'label' => esc_html__( 'service Box  Style', 'xhosting-library' ),
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
			'--service--title--style',
			[
				'label' => esc_html__( 'service Content  Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_control(
			'--service-icon-style--',
			[
				'label' => esc_html__( 'Service Icon Style', 'xhosting-library' ),
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
			'--service-title-style--',
			[
				'label' => esc_html__( 'service Title Style', 'xhosting-library' ),
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
		require __DIR__ . '/service-template/service-' . $settings['style'] . '.php';
    }




}


Plugin::instance()->widgets_manager->register( new xHosting_Service() );