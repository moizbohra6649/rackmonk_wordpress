<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Xhosting_Testimonial extends Widget_Base {

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
		return 'go-testimonial';
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
		return esc_html__( 'Testimonial', 'xhosting-plugin' );
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
				'label' => esc_html__( 'Testimonial Select', 'xhosting-plugin' ),
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
					'1'  => esc_html__( 'Style 1 (Spcaial Video)', 'xhosting-plugin' ),
					'2'  => esc_html__( 'Style 2', 'xhosting-plugin' ),
					'3'  => esc_html__( 'Style 3 (Slider)', 'xhosting-plugin' ),
					'4'  => esc_html__( 'Style 4 (Spcaial)', 'xhosting-plugin' ),
					'5'  => esc_html__( 'Style 5 (Slider Grid)', 'xhosting-plugin' )
				]
			]
		);
		
        $this->end_controls_section();
		
        $this->start_controls_section(
			'--testimonial-option',
			[
				'label' => esc_html__( 'Testimonial Option', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		
        
        $this->add_control(
			'video_link', [
				'label' => esc_html__( 'Video Link', 'xhosting-plugin' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
				'condition' => [
					'style' => '1',
				],
			]
		);
		
        
        $this->add_control(
			'quote', [
				'label' => esc_html__( 'Authore Icon', 'xhosting-plugin' ),
				'type' => Controls_Manager::ICONS,
                'label_block' => true,
				'condition' => [
					'style' => ['1', '4'],
				],
			]
		);
        
        $this->add_control(
			'authore', [
				'label' => esc_html__( 'Authore Image', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
				'condition' => [
					'style!' => ['3', '5'],
				],
			]
		);
       
        $this->add_control(
			'feedback', [
				'label' => esc_html__( 'Feedback', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
				'condition' => [
					'style!' => ['3', '5'],
				],
			]
		);
       
        $this->add_control(
			'name', [
				'label' => esc_html__( 'Name', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style!' => ['3', '5'],
				],
			]
		);
       
        $this->add_control(
			'designation', [
				'label' => esc_html__( 'Designation', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style!' => ['3', '5'],
				],
			]
		);
        $this->add_control(
			'rating_img', [
				'label' => esc_html__( 'Rating Img', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
				'condition' => [
					'style!' => ['3', '5'],
				],
			]
		);
		$this->add_control(
			'rating',
			[
				'label' => esc_html__( 'Rating', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 5,
				'step' => 1,
				'default' => 5,
				'condition' => [
					'style' => ['2', '4'],
				],
			]
		);

		$repeater = new \Elementor\Repeater();		
        $repeater->add_control(
			'style',
			[
				'label' => esc_html__( 'Layout Style', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'  => esc_html__( 'Style 1', 'xhosting-plugin' ),
					'2'  => esc_html__( 'Style 2', 'xhosting-plugin' ),
				]
			]
		);
        $repeater->add_control(
			'quote', [
				'label' => esc_html__( 'Quote Icon', 'xhosting-plugin' ),
				'type' => Controls_Manager::ICONS,
                'label_block' => true,
				'condition' => [
					'style' => ['1'],
				],
			]
		);
        
        $repeater->add_control(
			'authore', [
				'label' => esc_html__( 'Authore Image', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
				'condition' => [
					'style' => ['1'],
				],
			]
		);
        
        $repeater->add_control(
			'rating_logo', [
				'label' => esc_html__( 'Rating Logo Image', 'xhosting-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
				'condition' => [
					'style' => ['2'],
				],
			]
		);
       
        $repeater->add_control(
			'feedback', [
				'label' => esc_html__( 'Feedback', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
       
        $repeater->add_control(
			'name', [
				'label' => esc_html__( 'Name', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
       
        $repeater->add_control(
			'designation', [
				'label' => esc_html__( 'Designation', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
		$this->add_control(
			'testimonials',
			[
				'label' => esc_html__( 'Add Testimonial Item', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
                'title_field' => '{{{ name }}}',
				'condition' => [
					'style' => ['3', '5'],
				],
			]
		);
       
		$this->end_controls_section();

		// title style
		$this->start_controls_section(
			'abouttxt-style',
			[
				'label' => esc_html__( 'Testtimonial Play Icon Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'style' => ['1'],
				],
			]
		);
		
		$this->add_control(
			'play_icon-color',
			[
				'label' => esc_html__( 'Play Icon Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .play-btn a' => 'color: {{VALUE}}'
				]
			]
		);
		$this->add_control(
			'play_icon-hov-color',
			[
				'label' => esc_html__( 'Play Icon Hover Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .play-btn a:hover' => 'color: {{VALUE}}'
				]
			]
		);
		
		$this->add_control(
			'play_icon-bg-color',
			[
				'label' => esc_html__( 'Play Icon BG Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .play-btn a:hover' => 'background: {{VALUE}}'
				]
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
					'style' => ['2', '1', '3', '4', '5', '6', '8', '7'],
				],
			]
		);
		
		
		$this->add_control(
			'box_bg',
			[
				'label' => esc_html__( 'Box BG Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .agt-tst-item .item-inner:before' => 'background: {{VALUE}}',
					'{{WRAPPER}} .arv-testimonial-1-bg' => 'background: {{VALUE}}',
					'{{WRAPPER}} .agt-tst-item-3' => 'background: {{VALUE}}',
					'{{WRAPPER}} .agn-testimonial-5-area' => 'background: {{VALUE}}',
					'{{WRAPPER}} .agt-ft-testi-item' => 'background: {{VALUE}}',
					'{{WRAPPER}} .agt-quote-section' => 'background: {{VALUE}}',
					'{{WRAPPER}} .agt-testi-item-6 .item-wrap' => 'background: {{VALUE}}'
				],
			]
		);
		$this->add_control(
			'box_border_bg',
			[
				'label' => esc_html__( 'Box Border Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .agt-ft-testi-item' => 'border-color: {{VALUE}}'
				],
			]
		);
		
		
		$this->end_controls_section();
		// Sub title style
		$this->start_controls_section(
			'slider_sub_title_style',
			[
				'label' => esc_html__( 'Sub Title Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'style' => ['1'],
				],
			]
		);
		$this->add_responsive_control(
			'margin',
			[
				'label' => esc_html__( 'Sub Title Margin', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .elementor-xhosting-sub' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
        $this->add_control(
			'sub_title_color',
			[
				'label' => esc_html__( 'Sub Title Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-xhosting-sub' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'sub_line_color',
			[
				'label' => esc_html__( 'Sub Line BG Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .arv-subtitle-1 span' => 'background: {{VALUE}}',
					'{{WRAPPER}} .agn-brand-2-sec-title .line' => 'background: {{VALUE}}'
				],
			]
		);
        
		// typography
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[

				'name' => 'm_s_typography',
				'selector' => '{{WRAPPER}} .elementor-xhosting-sub',
			]
		);


		$this->end_controls_section();
		// title style
		$this->start_controls_section(
			'slider_title_style',
			[
				'label' => esc_html__( 'Title Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'style' => ['1'],
				],
			]
		);
        $this->add_responsive_control(
			'title_margin',
			[
				'label' => esc_html__( 'Sub Title Margin', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .elementor-gt-heading' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[

				'name' => 'm_t_typography',
				'selector' => '{{WRAPPER}} .elementor-gt-heading',
			]
		);
		$this->add_control(
			'title_color',
			[
				'label' => esc_html__( 'Title Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-gt-heading' => 'color: {{VALUE}}',
				],
			]
		);
		
		$this->end_controls_section();
		


	}


	protected function render() {
		$settings = $this->get_settings_for_display();
		require __DIR__ . '/testimonial-template/testimonial-' . $settings['style'] . '.php';
    }


}


Plugin::instance()->widgets_manager->register( new Xhosting_Testimonial() );