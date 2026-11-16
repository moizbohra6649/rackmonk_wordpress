<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Xhosting_Megamenu extends Widget_Base {

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
		return 'xhosting-megamenu';
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
		return esc_html__( 'Xhosting Megamenu', 'xhosting-plugin' );
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
				'label' => esc_html__( 'Brand Style Select', 'xhosting-plugin' ),
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
					'1'  => esc_html__( 'Megamenu - Home', 'xhosting-plugin' ),
					'2'  => esc_html__( 'Megamenu - Hosting', 'xhosting-plugin' ),
					'3'  => esc_html__( 'Megamenu - Domain', 'xhosting-plugin' )
				]
			]
		);
        $this->end_controls_section();
		
        $this->start_controls_section(
			'--brand-option',
			[
				'label' => esc_html__( 'Megamenu Option', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		$this->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'banner_img', [
				'label' => esc_html__( 'Banner Image', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
				'condition'      => [
                    'style!' => '3',
                ],
			]
		);
		$this->add_control(
			'discount', [
				'label' => esc_html__( 'Discount Text', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
				'condition'      => [
                    'style' => '2',
                ],
			]
		);
		$repeater = new \Elementor\Repeater();
        $repeater->add_control(
			'style',
			[
				'label' => esc_html__( 'Style', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'  => esc_html__( 'Megamenu - Home', 'xhosting-plugin' ),
					'2'  => esc_html__( 'Megamenu - Hosting', 'xhosting-plugin' ),
					'3'  => esc_html__( 'Megamenu - Domain', 'xhosting-plugin' )
				]
			]
		);
        $repeater->add_control(
			'image', [
				'label' => esc_html__( 'Image', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
				'condition'      => [
                    'style' => '1',
                ],
			]
		);

		$repeater->add_control(
            'type',
            [
                'label'          => __( 'Icon Type', 'xhosting-core' ),
                'type'           => Controls_Manager::CHOOSE,
                'label_block'    => false,
                'options'        => [
                    'icon'  => [
                        'title' => __( 'Icon', 'xhosting-core' ),
                        'icon'  => 'far fa-smile',
                    ],
                    'image' => [
                        'title' => __( 'Image', 'xhosting-core' ),
                        'icon'  => 'fa fa-image',
                    ],
                ],
                'default'        => 'icon',
                'toggle'         => false,
                'style_transfer' => true,
				
                'condition'      => [
                    'style' => ['2', '3'],
                ],
            ]
        );
        
        $repeater->add_control(
			'icon', [
				'label' => esc_html__( 'Icon', 'xhosting-plugin' ),
				'type' => Controls_Manager::ICONS,
                'label_block' => true,
                'condition'      => [
                    'type' => 'icon',
					'style' => ['2', '3'],
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
					'style' => ['2', '3'],
                ],
			]
		);
		$repeater->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition'      => [
					'style' => ['2', '3'],
                ],
			]
		);
		$repeater->add_control(
			'desc', [
				'label' => esc_html__( 'Description', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
				'condition'      => [
					'style' => ['2', '3'],
                ],
			]
		);
        
		$repeater->add_control(
			'website_link',
			[
				'label' => esc_html__( 'Link', 'textdomain' ),
				'type' => \Elementor\Controls_Manager::URL,
				'options' => [ 'url', 'is_external', 'nofollow' ],
				'default' => [
					'url' => '',
					'is_external' => true,
					'nofollow' => true,
					// 'custom_attributes' => '',
				],
				'label_block' => true,
			]
		);
		$this->add_control(
			'megamenus',
			[
				'label' => esc_html__( 'Add Megamenu Item', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
                'prevent_empty' => false,
			]
		);	
		$this->end_controls_section();

		$this->start_controls_section(
			'int_inof_opt',
			[
				'label' => esc_html__( 'Info Box Option', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
				'condition'      => [
					'style' => ['3'],
                ],
			]
		);
		$this->add_control(
			'info_box_enable',
			[
				'label' => esc_html__( 'Info Option HIDE/SHOW', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'xhosting-library' ),
				'label_off' => esc_html__( 'Hide', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);
		$this->add_control(
			'info_shape', [
				'label' => esc_html__( 'Info Shape', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
			]
		);
		$this->add_control(
			'box_title', [
				'label' => esc_html__( 'Box Title', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'info_title', [
				'label' => esc_html__( 'Info Title', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'info_desc', [
				'label' => esc_html__( 'Info Description', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
		$this->add_control(
			'btn_label', [
				'label' => esc_html__( 'Info Button', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'btn_link',
			[
				'label' => esc_html__( 'Button Link', 'textdomain' ),
				'type' => \Elementor\Controls_Manager::URL,
				'options' => [ 'url', 'is_external', 'nofollow' ],
				'default' => [
					'url' => '',
					'is_external' => true,
					'nofollow' => true,
					// 'custom_attributes' => '',
				],
				'label_block' => true,
			]
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'int_srt_opt',
			[
				'label' => esc_html__( 'Megamenu', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'padding',
			[
				'label' => esc_html__( 'Padding', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-mega-menu-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();




	}


	protected function render() {
		$settings = $this->get_settings_for_display();

		require __DIR__ . '/megamenu-template/megamenu-' . $settings['style'] . '.php';
    }


}


Plugin::instance()->widgets_manager->register( new Xhosting_Megamenu() );