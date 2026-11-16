<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Xhosting_Contact_Info extends Widget_Base {

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
		return 'ex-ct-info';
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
		return esc_html__( 'Contact Info', 'xhosting-plugin' );
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
			'--Contact-option',
			[
				'label' => esc_html__( 'Contact Option', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

        $this->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'gtbus-plugin' ),
				'default' => esc_html__( 'Get In Touch', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        $this->add_control(
			'description', [
				'label' => esc_html__( 'Description', 'gtbus-plugin' ),
				'default' => esc_html__( 'We provide reliable, secure, and high-performance hosting solutions tailored to your needs, ensuring fast, scalable, and hassle-free online experiences.', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
		
        $repeater = new \Elementor\Repeater();

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
				'label' => esc_html__( 'Title', 'gtbus-plugin' ),
				'default' => esc_html__( 'Rackmonk Hosting', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        
        $repeater->add_control(
			'link', [
				'label' => esc_html__( 'Info Link', 'gtbus-plugin' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
			]
		);
       
        $this->add_control(
			'contacts',
			[
				'label' => esc_html__( 'Add Contact Item', 'goyto-plugin' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
                'title_field' => '{{{ title }}}',
			]
		);
		$this->add_control(
			's_title', [
				'label' => esc_html__( 'Social Title', 'gtbus-plugin' ),
				'default' => esc_html__( 'Get Connected', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        $repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'icon', [
				'label' => esc_html__( 'Icon', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::ICONS,
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'link', [
				'label' => esc_html__( 'Info Link', 'gtbus-plugin' ),
				'type' => \Elementor\Controls_Manager::URL,
				'label_block' => true,
			]
		);

		$this->add_control(
			'socials',
			[
				'label' => esc_html__( 'Add Social Icon Item', 'goyto-plugin' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'title_field' => '{{{ icon.value }}}',
				'default' => [
					[
						'icon' => [ 'value' => 'fab fa-facebook-f', 'library' => 'fa-brands' ],
						'link' => [ 'url' => 'https://facebook.com' ],
					],
					[
						'icon' => [ 'value' => 'fab fa-pinterest-p', 'library' => 'fa-brands' ],
						'link' => [ 'url' => 'https://pinterest.com' ],
					],
					[
						'icon' => [ 'value' => 'fab fa-github', 'library' => 'fa-brands' ],
						'link' => [ 'url' => 'https://github.com' ],
					],
					[
						'icon' => [ 'value' => 'fab fa-youtube', 'library' => 'fa-brands' ],
						'link' => [ 'url' => 'https://youtube.com' ],
					],
				],
			]
		);


		$this->end_controls_section();
		
		$this->start_controls_section(
			'slider_sub_title_style',
			[
				'label' => esc_html__( 'Number Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'nm_margin',
			[
				'label' => esc_html__( 'Number Margin', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .number' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .agt-ab-c-item-2 h3' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
			]
		);
        $this->add_control(
			'number_color',
			[
				'label' => esc_html__( 'Number Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .number' => 'color: {{VALUE}}',
					'{{WRAPPER}} .agt-ab-c-item-2 h3' => 'color: {{VALUE}}',
					'{{WRAPPER}} .agt-count-item-3 h3' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'number-br_color',
			[
				'label' => esc_html__( 'Border Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .agt-ab-c-item-2 h3' => 'border-color: {{VALUE}}',
					'{{WRAPPER}} .agt-count-item-3 h3' => 'border-color: {{VALUE}}'
				],
			]
		);
      
		// typography
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[

				'name' => 'm_s_typography',
				'selector' => '
				{{WRAPPER}} .number,
				{{WRAPPER}} .agt-ab-c-item-2 h3,
				{{WRAPPER}} .agt-count-item-3 h3
				',
			]
		);


		$this->end_controls_section();

		$this->start_controls_section(
			'Contact_style_style',
			[
				'label' => esc_html__( 'Title Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'title_margin',
			[
				'label' => esc_html__( 'Title Margin', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .agt-ab-c-item-2 p' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .agt-count-item-3 p' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
        $this->add_control(
			'title_color',
			[
				'label' => esc_html__( 'Title Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .agt-ab-c-item-2 p' => 'color: {{VALUE}}',
					'{{WRAPPER}} .agt-count-item-3 p' => 'color: {{VALUE}}',
					'{{WRAPPER}} .agt-count-item-3 h3 sub' => 'color: {{VALUE}}'
				],
			]
		);
      
		// typography
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[

				'name' => 'title_typography',
				'selector' => '
					{{WRAPPER}} .agt-ab-c-item-2 p,
					{{WRAPPER}} .agt-count-item-3 p
				',
			]
		);


		$this->end_controls_section();

		// title style
		$this->start_controls_section(
			'aboutbox-style',
			[
				'label' => esc_html__( 'Box Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'style' => ['1'],
				],
			]
		);
		
		
		$this->add_control(
			'box_bg',
			[
				'label' => esc_html__( 'Box BG Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .agt-v-count-item' => 'background: {{VALUE}}'
				],
			]
		);
		$this->add_control(
			'box_round_bg',
			[
				'label' => esc_html__( 'Box BG Round Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .agt-v-Contact-wrap:before' => 'background: {{VALUE}}'
				],
			]
		);
		
		
		$this->end_controls_section();


	}


	protected function render() {
		$settings = $this->get_settings_for_display();
		require __DIR__ . '/contact-template/contact-info.php';
    }


}


Plugin::instance()->widgets_manager->register( new Xhosting_Contact_Info() );
