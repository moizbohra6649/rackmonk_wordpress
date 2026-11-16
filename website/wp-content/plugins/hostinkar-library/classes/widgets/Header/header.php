<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class xHosting_Header extends Widget_Base {

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
		return 'xhosting-header-1';
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
		return esc_html__( 'xHosting Header', 'xhosting-library' );
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
			'__header__style__',
			[
				'label' => esc_html__( 'Header Style Select', 'xhosting-library' ),
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
					'4'  => esc_html__( 'Style 4', 'xhosting-library' ),
					'5'  => esc_html__( 'Style 5', 'xhosting-library' )
				]
			]
		);
        
        $this->end_controls_section();

		$this->start_controls_section(
			'header__general',
			[
				'label' => esc_html__( 'Header Top Option', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		
		$this->add_control(
			'topbar_hide',
			[
				'label' => esc_html__( 'Top Bar HIDE/SHOW', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'xhosting-library' ),
				'label_off' => esc_html__( 'Hide', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition' => [
					'style' => ['1'],
				],
			]
		);
		$this->add_control(
			'badge_icon', [
				'label' => esc_html__( 'Badge Icon', 'xhosting-library' ),
				'type' => Controls_Manager::ICONS,
                'label_block' => true,
				'condition' => [
					'style' => ['1'],
					'topbar_hide' => 'yes'
				],
			]
		);
		$this->add_control(
			'top_text', [
				'label' => esc_html__( 'Top Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
				'condition' => [
					'style' => ['1'],
					'topbar_hide' => 'yes'
				],
			]
		);
		
		$this->add_control(
			'top_btn_label', [
				'label' => esc_html__( 'Header Top Button Label', 'xhosting-library' ),
				'default' => esc_html__( 'request a quote', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['1'],
					'topbar_hide' => 'yes'
				],
			]
		);
		$this->add_control(
			'top_btn_link', [
				'label' => esc_html__( 'Header Top Button Link', 'xhosting-library' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
				'condition' => [
					'style' => ['1'],
					'topbar_hide' => 'yes'
				],
			]
		);
		
		$this->end_controls_section();

		$this->start_controls_section(
			'header__button',
			[
				'label' => esc_html__( 'Header Button Option', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'btn_icon',
			[
				'label' => esc_html__( 'Button Icon HIDE/SHOW', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'xhosting-library' ),
				'label_off' => esc_html__( 'Hide', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);
		$this->add_control(
			'bt_icon', [
				'label' => esc_html__( 'Button Icon', 'xhosting-library' ),
				'type' => Controls_Manager::ICONS,
                'label_block' => true,
				'condition' => [
					'btn_icon' => 'yes'
				],
			]
		);
		
		$this->add_control(
			'btn_label', [
				'label' => esc_html__( 'Button Label', 'xhosting-library' ),
				'default' => esc_html__( 'request a quote', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'btn_link', [
				'label' => esc_html__( 'Button Link', 'xhosting-library' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
			]
		);
		
		$this->end_controls_section();
		
        $this->start_controls_section(
			'logoop',
			[
				'label' => esc_html__( 'Logo Option', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'rzlogo', [
				'label' => esc_html__( 'Logo', 'xhosting-library' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
				'default'     => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
			]
		);
		
		$this->add_responsive_control(
			'logo_max_width',
			[
				'label' => esc_html__( 'Max Width', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 5000,
					]
				],

				'selectors' => [
					'{{WRAPPER}} .logo_site-size' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'rzmlogo', [
				'label' => esc_html__( 'Mobile Logo', 'xhosting-library' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
				'default'     => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
			]
		);
		
		$this->add_responsive_control(
			'm_logo_max_width',
			[
				'label' => esc_html__( 'Max Width', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 5000,
					]
				],

				'selectors' => [
					'{{WRAPPER}} .logo_site-m-size' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);
        
		$this->end_controls_section();

        $this->start_controls_section(
			'menu_select',
			[
				'label' => esc_html__( 'Menu Option', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		
		$this->add_control(
			'choose-menu',
			[
				'label' => esc_html__( 'Choose menu', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'options' => xhosting_menu_selector(),
				'multiple' => false
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'header-mobile-style',
			[
				'label' => esc_html__( 'Mobile Option', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		$this->add_control(
			'mobile_search_enable',
			[
				'label' => esc_html__( 'Mobile Search HIDE/SHOW', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'xhosting-library' ),
				'label_off' => esc_html__( 'Hide', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$repeater = new \Elementor\Repeater();
        $repeater->add_control(
			'icon', [
				'label' => esc_html__( 'Icon', 'xhosting-library' ),
				'type' => Controls_Manager::ICONS,
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

        $this->add_control(
			'msocials',
			[
				'label' => esc_html__( 'Add Mobile Social Item', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'prevent_empty' => false
			]
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'header-help-style',
			[
				'label' => esc_html__( 'Headiner Help Option', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		$this->add_control(
			'help_text', [
				'label' => esc_html__( 'Help Text', 'xhosting-library' ),
				'default' => esc_html__( 'Help', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'condition' => [
					'help_enable' => 'yes',
				],
			]
		);
		$this->add_control(
			'help_enable',
			[
				'label' => esc_html__( 'Help Option HIDE/SHOW', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'xhosting-library' ),
				'label_off' => esc_html__( 'Hide', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'hp_title', [
				'label' => esc_html__( 'Help Title', 'xhosting-library' ),
				'default' => esc_html__( 'Get In Touch', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'condition' => [
					'help_enable' => 'yes',
				],
			]
		);
		$this->add_control(
			'hp_desc', [
				'label' => esc_html__( 'Help Description', 'xhosting-library' ),
				'default' => esc_html__( 'Discover unique domains, secure hosting services all in one place.', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);

		$this->add_control(
			'hp_ct_info_h',
			[
				'label' => esc_html__( 'Contact Info', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		
		$this->add_control(
			'ct_title', [
				'label' => esc_html__( 'Contact Info Title', 'xhosting-library' ),
				'default' => esc_html__( 'Have any questions?', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'phone', [
				'label' => esc_html__( 'Phone', 'xhosting-library' ),
				'default' => esc_html__( '+2 344 455 4455', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'email', [
				'label' => esc_html__( 'Email', 'xhosting-library' ),
				'default' => '',
				'placeholder' => esc_html__( 'Email address', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'hp_btn_icon',
			[
				'label' => esc_html__( 'Button Icon HIDE/SHOW', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'xhosting-library' ),
				'label_off' => esc_html__( 'Hide', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition' => [
					'style' => ['1'],
				],
			]
		);
		$this->add_control(
			'hp_bt_icon', [
				'label' => esc_html__( 'Button Icon', 'xhosting-library' ),
				'type' => Controls_Manager::ICONS,
                'label_block' => true,
				'condition' => [
					'style' => ['1'],
					'btn_icon' => 'yes'
				],
			]
		);
		
		$this->add_control(
			'hp_btn_label', [
				'label' => esc_html__( 'Button Label', 'xhosting-library' ),
				'default' => esc_html__( 'request a quote', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['1']
				],
			]
		);
		$this->add_control(
			'hp_btn_link', [
				'label' => esc_html__( 'Button Link', 'xhosting-library' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
				'condition' => [
					'style' => ['1']
				],
			]
		);
		$this->add_control(
			'hp_contact_title', [
				'label' => esc_html__( 'Contact Title', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'hp_ct_btn', [
				'label' => esc_html__( 'Contact Button', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'hp_ct_link', [
				'label' => esc_html__( 'Contact Link', 'xhosting-library' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
			]
		);
		$this->add_control(
			'social_title', [
				'label' => esc_html__( 'Social Title', 'xhosting-library' ),
				'default' => esc_html__( 'Get Connected', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$repeater = new \Elementor\Repeater();
        $repeater->add_control(
			'icon', [
				'label' => esc_html__( 'Icon', 'xhosting-library' ),
				'type' => Controls_Manager::ICONS,
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

        $this->add_control(
			'socials',
			[
				'label' => esc_html__( 'Add Social Item', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'prevent_empty' => false
			]
		);
		$this->end_controls_section();
		


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
					'{{WRAPPER}} .tg-header__top' => 'background: {{VALUE}}'
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
					'{{WRAPPER}} .tg-header__top-content-left p' => 'color: {{VALUE}}'
				],
			]
		);
		$this->add_control(
			'top-text-b-color',
			[
				'label' => esc_html__( 'Top Bold Text Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-header__top-content-left p span' => 'color: {{VALUE}}'
				],
			]
		);
		
        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'top_info_fb_typography',
				'label' => esc_html__( 'Top Text Font', 'xhosting-library' ),
				'selector' => '
					{{WRAPPER}} .tg-header__top-content-left p
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
				'selector' => '{{WRAPPER}} .tg-header__top-btn',
			]
		);
        $this->add_control(
			'tb-padding',
			[
				'label' => esc_html__( 'Padding', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-header__top-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .tg-header__top-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .tg-header__top-btn' => 'color: {{VALUE}}',
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'tbs_button_bg_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .tg-header__top-btn',
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
					'{{WRAPPER}} .tg-header__top-btn:hover' => 'color: {{VALUE}}',
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'tbs_button_bg_hover_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .tg-header__top-btn:hover',
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
					'{{WRAPPER}} .arv-header-1-action .search-btn .icon' => 'color: {{VALUE}} !important'
				],
			]
		);
        $this->add_control(
			'search-text-hover',
			[
				'label' => esc_html__( 'Search Text Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .arv-header-1-action .search-btn .text' => 'color: {{VALUE}} !important'
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
		
		require __DIR__ . '/header-template/header-' . $settings['style'] . '.php';
    }

	
	/**
	 * Search Body Display
	 *
	 * @return void
	 */
	protected function ___search_body(){
		$settings = $this->get_settings_for_display();
		?>
		<div class="ct-search-box search_box_active ">

		<div class="ct-search-container">
			<div class="ct-search-wrap text-center mb-55">
				<?php if(!empty($settings['search_btn_label'])):?>
					<h5 class="ct-search-title fx-heading-1 fx-font-500"><?php echo xhosting_wp_kses($settings['search_title'])?></h5>
				<?php endif;?>
				<form action="<?php echo esc_url(home_url( '/' ));?>" class="ct-search-form">
					<input class="ct-search-form-input" type="text" name="s" value="<?php echo get_search_query();?>"  placeholder="<?php esc_attr_e( 'What are you looking for?...', 'xhosting-library' );?>">
				</form>

			</div>

			<div class="ct-search-tag-wrap text-center">
				<?php if(!empty($settings['search_link_title'])):?>
					<h6 class="ct-search-tag-title fx-font-400 fx-heading-1"><?php echo xhosting_wp_kses($settings['search_link_title']);?></h6>
				<?php endif;?>
				<div class="ct-search-tag d-inline-flex flex-wrap">
					<?php foreach($settings['search_link'] as $item):?>
						<a target="<?php echo esc_attr( $item['link']['is_external'] ? '_blank' : '_self' ); ?>" rel="<?php echo esc_attr( $item['link']['nofollow'] ? 'nofollow' : '' ); ?>" href="<?php echo $item['link']['url'] ? esc_url($item['link']['url']) : ''; ?>" aria-label="name" class="ct-search-tag-item fx-heading-1 fx-font-500" ><?php echo xhosting_wp_kses($item['title']);?></a>
					<?php endforeach;?>
				</div>
			</div>
		</div>

		<button aria-label="search-close" type="button" class="ct-search-box-close search_box_close">
			<i class="fa-solid fa-xmark"></i>
		</button>

		</div>
		<!-- search-popup -->

		<div class="overlay"></div>

<?php 
}

protected function mobile_menu(){ 
	$settings = $this->get_settings_for_display();	
?>
<div class="mobile_menu lenis lenis-smooth ofcanvas_sidebar position-relative">
	<div class="mobile_menu_wrap">
		<div class="mobile_menu_overlay open_mobile_menu"></div>
		<div class="mobile_menu_content">
			<div class="mobile_menu_close open_mobile_menu">
				<i class="fas fa-times"></i>
			</div>
			<div class="m-brand-logo">
				<a href="<?php echo esc_url(home_url());?>" aria-label="name">
					<img class="logo_site-m-size" src="<?php echo esc_url($settings['rzmlogo']['url']);?>" alt="<?php if(!empty($settings['rzmlogo']['alt'])){ echo esc_attr($settings['rzmlogo']['alt']);}?>">
				</a>
			</div>
			<div class="mobile-search-bar position-relative">
			<form method="get" action="<?php echo home_url( '/' ); ?>">
				<input type="text" name="s" placeholder="Keywords">
				<button type="submit"><i class="fas fa-search"></i></button>
			</form>

			</div>
			<nav class="mobile-main-navigation  clearfix ul-li">
			<?php
				echo str_replace(['menu-item-has-children', 'sub-menu'], ['dropdown', 'dropdown-menu clearfix'], wp_nav_menu( array(
					'echo'           => false,
					'menu' => !empty($settings['choose-menu']) ? $settings['choose-menu'] : 'menu-1',
					'menu_id'        =>'main-nav',
					'menu_class'        =>'nav navbar-nav clearfix list-unstyled',
					'container'=>false,
					'fallback_cb'    => 'Navwalker_Class::fallback',
					'walker'         => class_exists( 'Rs_Mega_Menu_Walker' ) ? new \Rs_Mega_Menu_Walker : '',
				)) );
			?>
			</nav>
			<?php if(!empty($settings['f_socials'])):?>
				<div class="ptx-mobile-header-social text-center">
					<?php foreach($settings['f_socials'] as $item):?>
						<a href="<?php echo esc_url($item['link']['url']);?>">
							<?php \Elementor\Icons_Manager::render_icon( $item['icon'], [ 'aria-hidden' => 'true' ] ); ?>
						</a>
					<?php endforeach;?>
				</div>
			<?php endif;?>
		</div>
	</div>
</div>
<?php 
}

protected function hamburger_menu(){
	$settings = $this->get_settings_for_display();		
?>
	<div class="xs-sidebar-group info-group">
		<div class="xs-overlay xs-bg-black">
			<div class="row loader-area">
				<div class="col-3 preloader-wrap">
					<div class="loader-bg"></div>
				</div>
				<div class="col-3 preloader-wrap">
					<div class="loader-bg"></div>
				</div>
				<div class="col-3 preloader-wrap">
					<div class="loader-bg"></div>
				</div>
				<div class="col-3 preloader-wrap">
					<div class="loader-bg"></div>
				</div>
			</div>
		</div>
		<div class="xs-sidebar-widget">
			<div class="sidebar-widget-container">
				<div class="widget-heading">
					<a href="#" class="close-side-widget close-side-widget">
						<?php esc_html_e('X', 'xhosting-library');?>
					</a>
				</div>
				<div class="sidebar-textwidget">

					<div class="sidebar-info-contents headline pera-content">
						<div class="content-inner headline-2">
							<div class="logo">
								<a href="<?php echo esc_url(home_url());?>" aria-label="name">
									<img class="logo_site-size" src="<?php echo esc_url($settings['oflogo']['url']);?>" alt="<?php if(!empty($settings['oflogo']['alt'])){ echo esc_attr($settings['oflogo']['alt']);}?>">
								</a>
							</div>
							<div class="content-box">
								<?php if(!empty($settings['off_title'])):?>
									<h5>
										<?php echo xhosting_wp_kses($settings['off_title']);?>
									</h5>
								<?php endif;?>
								<?php if(!empty($settings['offdesc'])):?>
									<p class="text"><?php echo xhosting_wp_kses($settings['offdesc']);?></p>
								<?php endif;?>
							</div>
							<div class="gallery-box ul-li">
								<?php if(!empty($settings['gal_title'])):?>
								<h5><?php echo xhosting_wp_kses($settings['gal_title']);?></h5>
								<?php endif;?>
								<?php if(!empty($settings['gallery'])):?>
								<ul class="zoom-gallery">
									<?php foreach($settings['gallery'] as $item):?>
										<li>
											<a href="<?php echo esc_url($item['url']);?>" data-source="<?php echo esc_url($item['url']);?>">
												<img src="<?php echo esc_url($item['url']);?>" alt="<?php if(!empty($item['alt'])){ echo esc_attr($item['alt']);}?>">
											</a>
										</li>
									<?php endforeach;?>
								</ul>
								<?php endif;?>
							</div>
							<div class="content-box">
								<?php if(!empty($settings['social_title'])):?>
									<h5><?php echo xhosting_wp_kses($settings['social_title']);?></h5>
								<?php endif;?>
								<?php if(!empty($settings['header_socials'])):?>
									<ul class="social-box">
										<?php foreach($settings['header_socials'] as $item):?>
											<li>
												<a href="<?php echo esc_url($item['link']['url']);?>">
													<?php \Elementor\Icons_Manager::render_icon( $item['icons'], [ 'aria-hidden' => 'true' ] ); ?>
												</a>
											</li>
										<?php endforeach;?>
									</ul>
								<?php endif;?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}






}


Plugin::instance()->widgets_manager->register( new xHosting_Header() );
