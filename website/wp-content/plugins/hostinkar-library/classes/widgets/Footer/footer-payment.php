<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Xhosting_Payment extends Widget_Base {

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
		return 'xhostint-payment';
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
		return esc_html__( 'Payment', 'xhosting-plugin' );
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
					'1'  => esc_html__( 'Style 1', 'xhosting-plugin' )
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
		
        $this->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'gtbus-plugin' ),
				'default' => esc_html__( 'Civil Engineering Contractors', 'gtbus-plugin' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
		
        $this->add_control(
			'payment', [
				'label' => esc_html__( 'Payment Card', 'gtbus-plugin' ),
				'type' => Controls_Manager::MEDIA,
                'label_block' => true,
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
				'label' => esc_html__( 'Button Label', 'xhosting-plugin' ),
				'default' => esc_html__( 'Learn More', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
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
			]
		);
        $repeater = new \Elementor\Repeater();
        
        $repeater->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'xhosting-plugin' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
       
        $this->add_control(
			'languages',
			[
				'label' => esc_html__( 'Add Languages Item', 'goyto-plugin' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
                'title_field' => '{{{ title }}}',
			]
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'payment__title_style',
			[
				'label' => esc_html__( 'Payment Title Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'ttl_margin',
			[
				'label' => esc_html__( 'Payment Title Margin', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
			]
		);
        $this->add_control(
			'title_color',
			[
				'label' => esc_html__( 'Title Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .title' => 'color: {{VALUE}}'
				],
			]
		);
        
		// typography
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'm_s_typography',
				'selector' => '
					{{WRAPPER}} .title
				',
			]
		);
		$this->end_controls_section();
		$this->start_controls_section(
			'payment__btn_style',
			[
				'label' => esc_html__( 'Payment Button Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		
        $this->add_control(
			'btn_color',
			[
				'label' => esc_html__( 'Button Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .footer__cart-content-right > a' => 'color: {{VALUE}}',
					'{{WRAPPER}} .footer__cart-content-right > a svg *' => 'stroke: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'btn_bg_color',
			[
				'label' => esc_html__( 'Button Background Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .footer__cart-content-right > a' => 'background: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'btn_hover_color',
			[
				'label' => esc_html__( 'Button Hover Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .footer__cart-content-right > a:hover' => 'color: {{VALUE}}',
					'{{WRAPPER}} .footer__cart-content-right > a:hover svg *' => 'stroke: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'btn_bg_hover_color',
			[
				'label' => esc_html__( 'Button Hover Background Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .footer__cart-content-right > a:hover' => 'background: {{VALUE}}'
				],
			]
		);
        
		// typography
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'btn_font_typography',
				'selector' => '
					{{WRAPPER}} .footer__cart-content-right > a
				',
			]
		);
		$this->end_controls_section();
		$this->start_controls_section(
			'lang__btn_style',
			[
				'label' => esc_html__( 'Languages Button Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		
        $this->add_control(
			'lan_btn_color',
			[
				'label' => esc_html__( 'Button Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .language__select select' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'lang_btn_bg_color',
			[
				'label' => esc_html__( 'Languages Background Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .language__select select' => 'background: {{VALUE}}'
				],
			]
		);
        
		// typography
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'btn_langg_font_typography',
				'selector' => '
					{{WRAPPER}} .language__select select
				',
			]
		);
		$this->end_controls_section();

	}


	protected function render() {
		$settings = $this->get_settings_for_display();
		require __DIR__ . '/footer-template/payment-' . $settings['style'] . '.php';
    }


}


Plugin::instance()->widgets_manager->register( new Xhosting_Payment() );
