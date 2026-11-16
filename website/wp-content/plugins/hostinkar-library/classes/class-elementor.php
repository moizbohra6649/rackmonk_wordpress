<?php
/**
 * All Elementor widget init
 * @package xhosting
 * @since 1.0.0
 */

namespace XHosting_Library; 

if ( !defined('ABSPATH') ){
	exit(); // exit if access directly
}


if ( !class_exists('Xhosting_Elementor_Widget_Init') ){

	class Xhosting_Elementor_Widget_Init{
		/*
		* $instance
		* @since 1.0.0
		* */
		private static $instance;
		/*
		* construct()
		* @since 1.0.0
		* */
		public function __construct() {
			add_action( 'elementor/elements/categories_registered', array($this,'_widget_categories') );
			//elementor widget registered
			add_action('elementor/widgets/register',array($this,'_widget_registered'));
			add_action('elementor/editor/after_enqueue_styles',array($this,'editor_style'));
			add_action('elementor/documents/register_controls',array($this,'register_xhosting_page_controls'));
			add_action( 'elementor/frontend/before_enqueue_scripts', [ $this, 'before_enqueue_scripts' ] );
		}
		/*
	   * getInstance()
	   * @since 1.0.0
	   * */
		public static function getInstance(){
			if ( null == self::$instance ){
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
	 * Enqueue Scripts
	 *
	 * @return void
	 */

	public function before_enqueue_scripts() {
		wp_enqueue_script( 'xhosting-elementor', plugins_url() . '/hostinkar-library/assets/js/xhosting-elements.js', array(
			'jquery',
			'elementor-frontend'
		), '1.0', true );

		wp_localize_script('xhosting-elementor', 'translate', array(
			'searching' 	=> __('Searching...', 'xhosting-core'),
			'enterDomain' 	=> __('Please enter a domain name', 'xhosting-core'),
		));
	}

		/**
		 * _widget_categories()
		 * @since 1.0.0
		 * */
		public function _widget_categories($elements_manager){
			$categories = [];
			$categories['xhosting_widgets'] =
				[
					'title' => __( 'xHosting Addons', 'xhosting-library' ),
					'icon'  => 'fa fa-plug'
				];

			$old_categories = $elements_manager->get_categories();
			$categories = array_merge($categories, $old_categories);

			$set_categories = function ( $categories ) {
				$this->categories = $categories;
			};
			$set_categories->call( $elements_manager, $categories );
		}


		/**
		 * _widget_registered()
		 * @since 1.0.0
		 * */
		public function _widget_registered(){
			$widgests_lists = glob(plugin_dir_path( __FILE__ ) .'widgets/*/*.php');
			foreach($widgests_lists as $custom_widget){
				require_once $custom_widget;
			}
		}


		/**
		 * Summary of editor_style
		 * @return void
		 */
		public function editor_style(){
			$cs_icon = plugins_url( 'icons.svg', __FILE__ );
			wp_add_inline_style( 'elementor-editor', '.elementor-element .icon .xhosting-custom-icon{content: url( '.$cs_icon.');width: 28px;}' );
		}

		/**
		 * Elemenotr Page Settings
		 *
		 * @param [type] $document
		 * @return void Elementor Page Settings
		 */
		public function register_xhosting_page_controls( $document ) {

			if ( ! $document instanceof \Elementor\Core\DocumentTypes\PageBase || ! $document::get_property( 'has_elements' ) ) {
				return;
			}

			$document->start_controls_section(
				'body_xhosting_style',
				[
					'label' => esc_html__( 'xHosting Custom Body Style', 'xhosting-library' ),
					'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				]
			);
			$document->add_control(
				'body_color',
				[
					'label' => esc_html__( 'Body Color', 'textdomain' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}}' => 'color: {{VALUE}}',
					],
				]
			);

			$document->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'label' => esc_html__( 'Page Body Font', 'textdomain' ),
					'name' => 'page_body_font',
					'selector' => '{{WRAPPER}}',
				]
			);

			$document->add_group_control(
				\Elementor\Group_Control_Typography::get_type(),
				[
					'label' => esc_html__( 'Page Heading', 'textdomain' ),
					'name' => 'page_heading_font',
					'selector' => '{{WRAPPER}} h1, h2, h3, h4, h5, h6',
				]
			);
			$document->add_control(
				'h_color',
				[
					'label' => esc_html__( 'Heading Color', 'textdomain' ),
					'type' => \Elementor\Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} h1, h2, h3, h4, h5, h6' => 'color: {{VALUE}} !important',
					],
				]
			);
			$document->add_group_control(
				\Elementor\Group_Control_Background::get_type(),
				[
					'name' => 'body_cs_bg_color',
					'types' => [ 'classic' ],
					'selector' => '{{WRAPPER}} .agt-home-8:before',
					'fields_options' => [
						'background' => [
							'label' => esc_html__( 'Body Custom BG Image ', 'xhosting-library' ),
							'description' => esc_html__( 'Choose background type and style.', 'xhosting-library' ),
							'separator' => 'before',
						]
					]
				]
			);
			$document->end_controls_section();
		}




	}

	if ( class_exists('Xhosting_Elementor_Widget_Init') ){
		Xhosting_Elementor_Widget_Init::getInstance();
	}

}//end if
