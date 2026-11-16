<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class xHosting_Rating extends Widget_Base {


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
		return 'xhosting-rating';
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
		return esc_html__( 'xHosting Rating', 'xhosting-library' );
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
				'label' => esc_html__( 'Rating Style Select', 'xhosting-library' ),
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
					'2'  => esc_html__( 'Style 2', 'xhosting-library' )
				]
			]
		);
        
        $this->end_controls_section();

		$this->start_controls_section(
			'Banner__general',
			[
				'label' => esc_html__( 'Rating Content', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
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
					'style' => ['1'],
				],
			]
		);
		
		$this->add_control(
			'rate', [
				'label' => esc_html__( 'Rate Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['1'],
				],
			]
		);
		
		$this->add_control(
			'outoftext', [
				'label' => esc_html__( 'Outof Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['1'],
				],
			]
		);
		$this->add_control(
			'total_rating', [
				'label' => esc_html__( 'Rating Total Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['1'],
				],
			]
		);
		$this->add_control(
			'review_text', [
				'label' => esc_html__( 'Review Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
		$this->add_control(
			'review_logo', [
				'label' => esc_html__( 'Review Logo', 'xhosting-library' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
			]
		);
		
		$this->end_controls_section();

		$this->start_controls_section(
			'--banner--title--style',
			[
				'label' => esc_html__( 'Rating  Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_control(
			'--banner-title-style--',
			[
				'label' => esc_html__( 'Rating Title Style', 'xhosting-library' ),
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
					'{{WRAPPER}} .banner__review p' => 'color: {{VALUE}}'
				],
			]
		);
		
        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'title-fony',
				'label' => esc_html__( 'Rating Title Font', 'xhosting-library' ),
				'selector' => '
					{{WRAPPER}} .banner__review p
				',
			]
		);
		$this->add_control(
			'icon-style--',
			[
				'label' => esc_html__( 'Rating Icon Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'style' => ['1']
				],
			]
		);
		$this->add_control(
			'icon-color',
			[
				'label' => esc_html__( 'Rating Icon Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .banner__review .rating' => 'color: {{VALUE}}'
				]
			]
		);
		$this->add_control(
			'btn-icon-size',
			[
				'label' => esc_html__( 'Icon Font Size', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .banner__review .rating' => 'font-size: {{SIZE}}{{UNIT}};'
				],
				'condition' => [
					'style' => ['1']
				],
			]
		);
		$this->add_control(
			'btn-icon-gap',
			[
				'label' => esc_html__( 'Icon Gap', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .banner__review .rating' => 'gap: {{SIZE}}{{UNIT}};'
				],
				'condition' => [
					'style' => ['1']
				],
			]
		);

        $this->end_controls_tab();
        

	}


	protected function render() {
		$settings = $this->get_settings_for_display();
		extract( $settings );
		if ( ! empty( $settings['btn_link']['url'] ) ) {
			$this->add_link_attributes( 'btn_link', $settings['btn_link'] );
		}
		require __DIR__ . '/rating-template/rating-' . $settings['style'] . '.php';
    }




}


Plugin::instance()->widgets_manager->register( new xHosting_Rating() );