<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Xhosting_Contact_Form extends Widget_Base {

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
		return 'ex-ct-form';
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
		return esc_html__( 'Contact Form', 'xhosting-plugin' );
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
				'default' => esc_html__( 'Contact', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        $this->add_control(
			'shortcode', [
				'label' => esc_html__( 'Shortcode', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
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
		require __DIR__ . '/contact-template/contact.php';
    }


}


Plugin::instance()->widgets_manager->register( new Xhosting_Contact_Form() );