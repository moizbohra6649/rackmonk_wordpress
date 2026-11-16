<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class xHosting_Domain_Search extends Widget_Base {


	/**
	 * Get widget name.
	 *WHMCS URL here
	 * Retrieve Elementor widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'xhosting-domain-form';
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
		return esc_html__( 'xHosting Domain Search', 'xhosting-library' );
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
			'__Banner__style__',
			[
				'label' => esc_html__( 'Domain Style Select', 'xhosting-library' ),
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
			'section_content', [
				'label' => __( 'Content', 'xhosting-library' ),
			]
		);

		$this->add_control(
			'bg_img', [
				'label'       => __( 'BG Image', 'xhosting-library' ),
				'type'        => Controls_Manager::MEDIA,
				'label_block' => true,
				'condition' => [
					'style' => [ '2' , '3', '4']
				]
			]
		);
		$this->add_control(
			'shape-1', [
				'label'       => __( 'Shape 1', 'xhosting-library' ),
				'type'        => Controls_Manager::MEDIA,
				'label_block' => true,
				'condition' => [
					'style' => [ '2' , '4']
				]
			]
		);
		$this->add_control(
			'shape-2', [
				'label'       => __( 'Shape 2', 'xhosting-library' ),
				'type'        => Controls_Manager::MEDIA,
				'label_block' => true,
				'condition' => [
					'style' => [ '2' , '4']
				]
			]
		);
		$this->add_control(
			'sub_title', [
				'label'       => __( 'Sub Title', 'xhosting-library' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Sub Title', 'xhosting-library' ),
				'label_block' => true,
				'condition' => [
					'style' => [ '2' , '3']
				]
			]
		);
		$this->add_control(
			'title', [
				'label'       => __( 'Title', 'xhosting-library' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Search your Domain Name', 'xhosting-library' ),
				'label_block' => true,
				'condition' => [
					'style' => [ '2' , '3', '4']
				]
			]
		);
		
		$this->add_control(
            'title_tag',
            [
                'label'   => __( 'Title HTML Tag', 'xhosting-library' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'h1' => [
                        'title' => __( 'H1', 'xhosting-library' ),
                        'icon'  => 'eicon-editor-h1',
                    ],
                    'h2' => [
                        'title' => __( 'H2', 'xhosting-library' ),
                        'icon'  => 'eicon-editor-h2',
                    ],
                    'h3' => [
                        'title' => __( 'H3', 'xhosting-library' ),
                        'icon'  => 'eicon-editor-h3',
                    ],
                    'h4' => [
                        'title' => __( 'H4', 'xhosting-library' ),
                        'icon'  => 'eicon-editor-h4',
                    ],
                    'h5' => [
                        'title' => __( 'H5', 'xhosting-library' ),
                        'icon'  => 'eicon-editor-h5',
                    ],
                    'h6' => [
                        'title' => __( 'H6', 'xhosting-library' ),
                        'icon'  => 'eicon-editor-h6',
                    ],
                ],
                'default' => 'h2',
                'toggle'  => false,
				'condition' => [
					'style' => [ '2' , '3', '4']
				]
            ]
        );
		$this->add_control(
			'description', [
				'label'       => __( 'Description', 'xhosting-library' ),
				'type'        => Controls_Manager::TEXTAREA,
				'label_block' => true,
				'condition' => [
					'style' => [ '2' ]
				]
			]
		);
		$this->add_control(
			'btn_label', [
				'label' => esc_html__( 'Button Label', 'xhosting-plugin' ),
				'default' => esc_html__( 'Learn More', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => [ '2' , '3' ]
				]
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
				'condition' => [
					'style' => [ '2' , '3' ]
				]
			]
		);
		$this->add_control(
			'placeholder_input', [
				'label'       => __( 'Placeholder Input Text', 'xhosting-library' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Domain.com', 'xhosting-library' ),
				'label_block' => true
			]
		);

		$this->add_control(
			'search_btn_label', [
				'label'       => __('Search Button label', 'xhosting-library'),
				'type'        => Controls_Manager::TEXT,
				'default'     => __('Search', 'xhosting-library'),
				'label_block' => true
			]
		);

		$this->add_control(
			'whmcs_url', [
				'label'         => __( 'Link', 'xhosting-library' ),
				'type'          => \Elementor\Controls_Manager::URL,
				'placeholder'   => __( 'https://your-link.com', 'xhosting-library' ),
				'description' => __('We added a WHMCS Integration option in the Theme Option, just add your <a href="admin.php?page=xhosting-framework#tab=whmcs-integration">WHMCS URL here</a>', 'xhosting-library'),
				'show_external' => true,
				'default'       => [
					'url'         => '#',
					'is_external' => true,
					'nofollow'    => true,
				],
			]
		);
		$this->add_control(
			'disable_ajax_search', [
				'label' => esc_html__('Enable Ajax Search', 'xhosting-library'),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Enable', 'xhosting-library'),
				'label_off' => esc_html__('Disable', 'xhosting-library'),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);
		$this->add_control(
			'domain_seggestion_on_off', [
				'label' => esc_html__('Domain Suggestion On/Off', 'xhosting-library'),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__('On', 'xhosting-library'),
				'label_off' => esc_html__('Off', 'xhosting-library'),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->end_controls_section(); //End Content

		//============================= Domain Extension and Price ==========================//
		$this->start_controls_section(
			'extension__section', [
				'label' => __( 'Domain Extension', 'xhosting-library' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'extension_dropdown_list_show', [
				'label'        => __( 'Extension Dropdown List Show/Hide', 'xhosting-library' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'xhosting-library' ),
				'label_off'    => __( 'Hide', 'xhosting-library' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'extension_list_show', [
				'label'        => __( 'Extension List Show/Hide', 'xhosting-library' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'xhosting-library' ),
				'label_off'    => __( 'Hide', 'xhosting-library' ),
				'return_value' => 'yes',
				'default'      => 'yes',
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

		$this->end_controls_section(); //End Domain Extension and Price


		$this->start_controls_section(
			'top_menu__style',
			[
				'label' => esc_html__( 'Top Bar  Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'style' => ['1'],
					'topbar_hide' => 'yes'
				],
			]
		);
		$this->add_control(
			'tp_icon_st',
			[
				'label' => esc_html__( 'Top Bar Badge Icon', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->add_responsive_control(
			'icon_size',
			[
				'label' => esc_html__( 'Size', 'textdomain' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .icon i' => 'font-size: {{SIZE}}{{UNIT}};'
				],
			]
		);
		$this->add_control(
			'tb_bdg_icon_color',
			[
				'label' => esc_html__( 'Badge Icon Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .icon svg path' => 'stroke: {{VALUE}}',
					'{{WRAPPER}} .icon i' => 'color: {{VALUE}}'
				],
			]
		);
		$this->add_control(
			'tp_bar_bg',
			[
				'label' => esc_html__( 'Top Bar BG Option', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->add_control(
			'top_bar_bg',
			[
				'label' => esc_html__( 'Top Bar Menu BG Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-Banner__top' => 'background: {{VALUE}}'
				],
			]
		);
		$this->add_control(
			'tp_bar_text',
			[
				'label' => esc_html__( 'Top Info Option', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		
		$this->add_control(
			'top-text-color',
			[
				'label' => esc_html__( 'Top Text Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-Banner__top-content-left p' => 'color: {{VALUE}}'
				],
			]
		);
		$this->add_control(
			'top-text-b-color',
			[
				'label' => esc_html__( 'Top Bold Text Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-Banner__top-content-left p span' => 'color: {{VALUE}}'
				],
			]
		);
		
        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'top_info_fb_typography',
				'label' => esc_html__( 'Top Text Font', 'xhosting-library' ),
				'selector' => '
					{{WRAPPER}} .tg-Banner__top-content-left p
				',
			]
		);
		$this->add_control(
			'tp--button-style',
			[
				'label' => esc_html__( 'Top Button Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'tbs_typography',
				'selector' => '{{WRAPPER}} .tg-Banner__top-btn',
			]
		);
        $this->add_control(
			'tb-padding',
			[
				'label' => esc_html__( 'Padding', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-Banner__top-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .tg-Banner__top-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .tg-Banner__top-btn' => 'color: {{VALUE}}',
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'tbs_button_bg_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .tg-Banner__top-btn',
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
					'{{WRAPPER}} .tg-Banner__top-btn:hover' => 'color: {{VALUE}}',
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'tbs_button_bg_hover_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .tg-Banner__top-btn:hover',
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


		$this->start_controls_section(
			'menu_bar_style',
			[
				'label' => esc_html__( 'Menu  Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'--menu--style-info',
			[
				'label' => esc_html__( 'Menu Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		
        $this->add_control(
			'menu_color',
			[
				'label' => esc_html__( 'Menu Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .navbar-nav>li>a' => 'color: {{VALUE}} !important',
					'{{WRAPPER}} .navbar-nav>li>a:after' => 'color: {{VALUE}} !important'
				],
			]
		);
        $this->add_control(
			'menu_arrow_color',
			[
				'label' => esc_html__( 'Menu Arrow Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .navbar-nav li:is(.dropdown) > a::before' => 'color: {{VALUE}} !important'
				],
			]
		);
        $this->add_control(
			'menu_color-hover',
			[
				'label' => esc_html__( 'Menu Hover Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .navbar-nav>li>a:hover' => 'color: {{VALUE}} !important'
				],
			]
		);
        $this->add_control(
			'menu_sticky-hover',
			[
				'label' => esc_html__( 'Menu Sticky Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .txa_sticky .navbar-nav>li>a' => 'color: {{VALUE}} !important',
					'{{WRAPPER}} .txa_sticky .navbar-nav>li>a:after' => 'color: {{VALUE}} !important'
				],
			]
		);
		
        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'menu_fb_typography',
				'selector' => '
					{{WRAPPER}} .navbar-nav li a
				',
			]
		);
		$this->add_control(
			'menu_dropdown_style',
			[
				'label' => esc_html__( 'Menu Dropdown Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		
        $this->add_control(
			'mdb_color',
			[
				'label' => esc_html__( 'Dropdown Box BG Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .main-navigation .navbar-nav li .dropdown-menu' => 'background: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'mdb_menu_color',
			[
				'label' => esc_html__( 'Dropdown Box Border Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .main-navigation .navbar-nav li .dropdown-menu' => 'border-color: {{VALUE}}'
				],
			]
		);

        $this->end_controls_section();
		$this->start_controls_section(
			'help_style',
			[
				'label' => esc_html__( 'Help Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

        $this->add_control(
			's-icon-color',
			[
				'label' => esc_html__( 'Search Icon Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .arv-Banner-1-action .search-btn .icon' => 'color: {{VALUE}} !important'
				],
			]
		);
        $this->add_control(
			'search-text-hover',
			[
				'label' => esc_html__( 'Search Text Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .arv-Banner-1-action .search-btn .text' => 'color: {{VALUE}} !important'
				],
			]
		);
		
        $this->end_controls_section();
		 // feature style
		 $this->start_controls_section(
			'--button_one',
			[
				'label' => esc_html__( 'Button Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		 $this->add_control(
			'btn_icon_st',
			[
				'label' => esc_html__( 'Button Icon Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->add_responsive_control(
			'btn_icon_size',
			[
				'label' => esc_html__( 'Icon Size', 'textdomain' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-btn svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .tg-btn i' => 'font-size: {{SIZE}}{{UNIT}};'
				],
			]
		);
		$this->add_control(
			'btn_icon_color',
			[
				'label' => esc_html__( 'Button Icon Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn svg path' => 'stroke: {{VALUE}}',
					'{{WRAPPER}} .tg-btn i' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'm_b_typography',
				'selector' => '{{WRAPPER}} .tg-btn',
			]
		);
        $this->add_control(
			'padding',
			[
				'label' => esc_html__( 'Padding', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
        $this->add_control(
			'b_round',
			[
				'label' => esc_html__( 'Border Radius', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .tg-btn::after, .tg-btn::before' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);


        $this->start_controls_tabs(
			'style_tabs'
		);

		$this->start_controls_tab(
			'style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'xhosting-library' ),
			]
		);
        $this->add_control(
			'btn_text',
			[
				'label' => esc_html__( 'Text Color', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'btn_br_text',
			[
				'label' => esc_html__( 'Border Color', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'border-color: {{VALUE}}'
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
                        'label' => esc_html__( 'Button BG Color ', 'xhosting-library' ),
                        'description' => esc_html__( 'Choose background type and style.', 'xhosting-library' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);
        $this->end_controls_tab();
        $this->start_controls_tab(
			'style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'xhosting-library' ),
			]
		);
        $this->add_control(
			'btn_h_text',
			[
				'label' => esc_html__( 'Text Hovwe Color', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn:hover' => 'color: {{VALUE}}',
					'{{WRAPPER}} .tg-btn:hover .btn-text' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'button_border_hover_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '
				{{WRAPPER}} .tg-btn::before
				',
				'condition' => [
					'style' => ['1'],
				],
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Button Hover Border Color ', 'xhosting-library' ),
                        'description' => esc_html__( 'Choose background type and style.', 'xhosting-library' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'button_bg_hover_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '
				{{WRAPPER}} .tg-btn:hover,
				{{WRAPPER}} .tg-btn:hover::after
				',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Button Hover BG Color ', 'xhosting-library' ),
                        'description' => esc_html__( 'Choose background type and style.', 'xhosting-library' ),
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
		if ( ! empty( $settings['btn_link']['url'] ) ) {
			$this->add_link_attributes( 'btn_link', $settings['btn_link'] );
		}
		require __DIR__ . '/domain-template/domain-' . $settings['style'] . '.php';
    }


/**
	 * The bridge Page Id.
	 */
	public function get_bridge_page_id( $type = 'string' ) {
		$bridge_page_id = get_option( 'cc_whmcs_bridge_pages' );

		if ( isset( $bridge_page_id ) ) {
			if ( $type === 'int' ) {
				return intval( $bridge_page_id );
			}

			return $bridge_page_id;

		} else {
			return false;
		}
	}

	/**
	 * Bridge page exists.
	 */
	public function wdes_bridge_page_exists() {
		if ( $this->get_bridge_page_id() ) {
			return true;
		}

		return false;
	}

	/**
	 * Bridge Checker.
	 */
	public function wdes_bridge_checker() {
		if ( $this->wdes_bridge_page_exists() ) {
			$output = get_permalink( $this->get_bridge_page_id() ) . '?ccce=domainchecker';

			return $output;
		}

		return false;
	}

	/**
	 * Domain Action Url.
	 */
	public function wdes_domain_action_url( $default ) {

		if ( $this->wdes_bridge_checker() ) {
			$output = $this->wdes_bridge_checker();
		} else {
			if ( ! empty( $default ) ) {
				$output = $default;
			} else {
				$output = '#';
			}
		}

		return $output;
	}

}


Plugin::instance()->widgets_manager->register( new xHosting_Domain_Search() );
