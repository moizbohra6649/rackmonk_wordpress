<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Xhosting_List extends Widget_Base {


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
		return 'xh--list';
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
		return esc_html__( 'List', 'xhosting-plugin' );
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
			'--info-list-option',
			[
				'label' => esc_html__( 'List Option', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		
		$repeater = new \Elementor\Repeater();
		
        $repeater->add_control(
			'badge', [
				'label' => esc_html__( 'List Badge', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
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
			'link', [
				'label' => esc_html__( 'List', 'xhosting-library' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
			]
		);
        
        $this->add_control(
			'lists',
			[
				'label' => esc_html__( 'Add list Item', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
                'title_field' => '{{{ title }}}',
                'prevent_empty' => false,
			]
		);
        
		$this->end_controls_section();


		$this->start_controls_section(
			'service--title-stle',
			[
				'label' => esc_html__( 'list Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		
        $this->add_control(
			'title_color',
			[
				'label' => esc_html__( 'Title Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .list-wrap a' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'list_hover_color',
			[
				'label' => esc_html__( 'list Hover Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .list-wrap a:hover' => 'color: {{VALUE}}'
				]
			]
		);
        
		// typography
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[

				'name' => 'm_s_typography',
				'selector' => '
					{{WRAPPER}} .list-wrap a
				',
			]
		);
		$this->end_controls_section();
	}


	protected function render() {
		$settings = $this->get_settings_for_display();
		require __DIR__ . '/list-template/list-1.php';
    }


}


Plugin::instance()->widgets_manager->register( new Xhosting_List() );