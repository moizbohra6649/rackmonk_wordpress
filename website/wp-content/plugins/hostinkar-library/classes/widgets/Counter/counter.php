<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Xhosting_Counter extends Widget_Base {

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
		return 'ex-ct-group';
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
		return esc_html__( 'Counter', 'xhosting-plugin' );
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
		
        $repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'style',
			[
				'label' => esc_html__( 'Counter Layout', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'  => esc_html__( 'Counter 1', 'xhosting-plugin' ),
					'2'  => esc_html__( 'Counter 2', 'xhosting-plugin' )
				]
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
					'style' => '2',
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
					'style' => '2',
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
					'style' => '2',
                ],
			]
		);
        
        $repeater->add_control(
			'count', [
				'label' => esc_html__( 'Count Number', 'gtbus-plugin' ),
				'default' => esc_html__( '18', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        $repeater->add_control(
			'prefix', [
				'label' => esc_html__( 'Count Prefix', 'gtbus-plugin' ),
				'default' => esc_html__( '+', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        
        $repeater->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'gtbus-plugin' ),
				'default' => esc_html__( 'Civil Engineering Contractors', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
       
        $this->add_control(
			'counters',
			[
				'label' => esc_html__( 'Add Counter Item', 'goyto-plugin' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
                'title_field' => '{{{ title }}}',
				'condition'      => [
					'style' => ['2', '1'],
                ],
			]
		);

		$this->add_control(
			'count', [
				'label' => esc_html__( 'Count Number', 'gtbus-plugin' ),
				'default' => esc_html__( '18', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition'      => [
					'style' => ['3'],
                ],
			]
		);
        $this->add_control(
			'prefix', [
				'label' => esc_html__( 'Count Prefix', 'gtbus-plugin' ),
				'default' => esc_html__( '+', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition'      => [
					'style' => ['3'],
                ],
			]
		);
        
        $this->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'gtbus-plugin' ),
				'default' => esc_html__( 'Civil Engineering Contractors', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition'      => [
					'style' => ['3'],
                ],
			]
		);
		
		$this->end_controls_section();

		$this->start_controls_section(
			'slider_cta_style',
			[
				'label' => esc_html__( 'CTA Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
				'condition'      => [
					'style' => '5',
                ],
			]
		);
		$this->add_control(
			'btn_title', [
				'label' => esc_html__( 'Button Title', 'cantia-plugin' ),
				'type' => Controls_Manager::TEXT,
			]
		);
		$this->add_control(
			'btn_2_link', [
				'label' => esc_html__( 'Button Link', 'cantia-plugin' ),
				'type' => Controls_Manager::URL,
			]
		);
		$this->add_control(
			'icon', [
				'label' => esc_html__( 'Icon', 'xhosting-plugin' ),
				'type' => Controls_Manager::ICONS,
                'label_block' => true,
			]
		);
		$this->add_control(
			'phn_title', [
				'label' => esc_html__( 'Phone Title', 'cantia-plugin' ),
				'type' => Controls_Manager::TEXT,
			]
		);
		$this->add_control(
			'phone', [
				'label' => esc_html__( 'Phone No', 'cantia-plugin' ),
				'type' => Controls_Manager::TEXT,
			]
		);
		$this->add_control(
			'link', [
				'label' => esc_html__( 'Button Link', 'cantia-plugin' ),
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
			'counter_style_style',
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
					'{{WRAPPER}} .agt-v-counter-wrap:before' => 'background: {{VALUE}}'
				],
			]
		);
		
		
		$this->end_controls_section();


	}


	protected function render() {
		$settings = $this->get_settings_for_display();
		require __DIR__ . '/counter-template/counter-' . $settings['style'] . '.php';
    }


}


Plugin::instance()->widgets_manager->register( new Xhosting_Counter() );