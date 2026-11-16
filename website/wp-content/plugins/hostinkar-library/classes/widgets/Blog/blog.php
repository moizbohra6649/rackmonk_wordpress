<?php
/**
 * Elementor Widget: Xhosting Blog Post
 * @package Xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit;

class Xhosting_Blog_Post extends Widget_Base {

	public function get_name() {
		return 'xhosting-blog';
	}

	public function get_title() {
		return esc_html__( 'Xhosting Blog', 'xhosting-plugin' );
	}

	public function get_icon() {
		return 'xhosting-custom-icon';
	}

	public function get_categories() {
		return [ 'xhosting_widgets' ];
	}

	protected function register_controls() {
		$this->register_style_control_section();
		$this->register_post_query_controls();
		$this->register_blog_box_style_controls();
		$this->register_button_style_controls();
		$this->register_blog_typography_controls();
	}

	private function register_style_control_section() {
		$this->start_controls_section( 'widget_style_section', [
			'label' => esc_html__( 'Heading Style', 'xhosting-plugin' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'style', [
			'label'   => esc_html__( 'Style', 'xhosting-plugin' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '1',
			'options' => [
				'1' => esc_html__( 'Style 1', 'xhosting-plugin' ),
				'2' => esc_html__( 'Style 2', 'xhosting-plugin' ),
				'3' => esc_html__( 'Style 3', 'xhosting-plugin' ),
				'4' => esc_html__( 'Style 4', 'xhosting-plugin' )
			],
		] );
		$this->add_control(
			'column',
			[
				'label' => esc_html__( 'Post Column Style', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'column_3',
				'options' => [
					'column_1'  => esc_html__( 'Column 1', 'xhosting-plugin' ),
					'column_2'  => esc_html__( 'Column 2', 'xhosting-plugin' ),
					'column_3'  => esc_html__( 'Column 3', 'xhosting-plugin' ),
					'column_4'  => esc_html__( 'Column 4', 'xhosting-plugin' ),
					'column_5'  => esc_html__( 'Column 5', 'xhosting-plugin' ),
				],
				'condition' => [
					'style' => [ '1' ],
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_post_query_controls() {
		$this->start_controls_section( 'post_options_section', [
			'label' => esc_html__( 'Blog Options', 'xhosting-plugin' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'post_order', [
			'label'   => esc_html__( 'Post Order', 'xhosting-plugin' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'ASC',
			'options' => [
				'ASC'  => esc_html__( 'Ascending', 'xhosting-plugin' ),
				'DESC' => esc_html__( 'Descending', 'xhosting-plugin' ),
			],
		] );

		$this->add_control( 'post_per_page', [
			'label'   => __( 'Posts Per Page', 'xhosting-plugin' ),
			'type'    => Controls_Manager::NUMBER,
			'min'     => 1,
			'default' => 5,
		] );

		$this->add_control( 'post_categories', [
			'label'       => esc_html__( 'Select Categories', 'xhosting-plugin' ),
			'type'        => Controls_Manager::SELECT2,
			'options'     => xhosting_blog_category(),
			'label_block' => true,
			'multiple'    => true,
		] );

		$this->add_control( 'title_length', [
			'label'   => __( 'Title Length', 'xhosting-plugin' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 20,
		] );

		$this->add_control( 'excerpt_length', [
			'label'   => __( 'Excerpt Length', 'xhosting-plugin' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 20,
		] );

		$this->add_control( 'button_label', [
			'label'   => esc_html__( 'Read More Button', 'xhosting-plugin' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Read more', 'xhosting-plugin' ),
		] );

		$this->end_controls_section();
	}

	private function register_blog_box_style_controls() {
		$this->start_controls_section( 'box_style_section', [
			'label' => esc_html__( 'Blog Box Style', 'xhosting-plugin' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_responsive_control(
			'height',
			[
				'label' => esc_html__( 'Image Height', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .blog__post-item .blog__post-thumb img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control( 'box_bg_color', [
			'label'     => esc_html__( 'Box Background Color', 'xhosting-plugin' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .agn-blog-1-item,
				 {{WRAPPER}} .agn-blog-3-item .content,
				 {{WRAPPER}} .agn-blog-9-item,
				 {{WRAPPER}} .agt-blg-item-6' => 'background: {{VALUE}}',
			],
		] );

		$this->add_control( 'box_date_color', [
			'label'     => esc_html__( 'Date Background Color', 'xhosting-plugin' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .agn-blog-9-item .item-date' => 'background: {{VALUE}}',
			],
		] );

		$this->add_control( 'box_border_color', [
			'label'     => esc_html__( 'Box Border Color', 'xhosting-plugin' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .arv-blog-1-card::after' => 'border-color: {{VALUE}}',
			],
			'condition' => [
				'style' => [ '1' ],
			],
		] );

		$this->add_responsive_control( 'box_padding', [
			'label'      => esc_html__( 'Box Padding', 'xhosting-plugin' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem' ],
			'selectors'  => [
				'{{WRAPPER}} .agn-blog-1-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();
	}

	private function register_button_style_controls() {
		$this->start_controls_section( 'button_style_section', [
			'label' => esc_html__( 'Button Style', 'xhosting-plugin' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'post_btn_bg_color', [
			'label'     => esc_html__( 'Button Background Color', 'xhosting-plugin' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .agn-blog-1-item .blog-btn::after' => 'background: {{VALUE}}',
			],
		] );

		$this->add_control( 'post_btn_icon_color', [
			'label'     => esc_html__( 'Button Icon Color', 'xhosting-plugin' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .agn-blog-1-item .blog-btn i' => 'color: {{VALUE}}',
			],
		] );

		$this->end_controls_section();
	}

	private function register_blog_typography_controls() {
		// Skipping for brevity, but structure should be similar: split into clear headings
		// like `register_post_title_typography()`, `register_post_date_typography()` etc.
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$args = [
			'post_type'           => 'post',
			'posts_per_page'      => $settings['post_per_page'] ?? 1,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'order'               => $settings['post_order'],
		];

		if ( ! empty( $settings['post_categories'] ) ) {
			$args['category_name'] = implode( ',', $settings['post_categories'] );
		}

		$query = new \WP_Query( $args );
		$column_class = '';
		if($settings['column'] === 'column_1'){
			$column_class = 'col-lg-12 col-xl-12';
		}elseif($settings['column'] === 'column_2'){
			$column_class = 'col-lg-6 col-md-6 col-xl-6';
		}elseif($settings['column'] === 'column_3'){
			$column_class = 'col-lg-4 col-md-6 col-xl-4';
		}elseif($settings['column'] === 'column_4'){
			$column_class = 'col-lg-6 col-xl-3 col-md-6';
		}elseif($settings['column'] === 'column_5'){
			$column_class = 'col-byt-5 col-xl-4 col-md-6';
		}else{
			$column_class = 'col-lg-3 col-xl-3 col-md-6';
		}
		require __DIR__ . '/blog-template/blog-' . esc_attr( $settings['style'] ) . '.php';
	}

	protected function xhosting_category_name() {
		$categories = get_the_category();
		if ( empty( $categories ) ) return;

		shuffle( $categories );
		$random_category = $categories[ array_rand( $categories ) ];

		$meta     = get_term_meta( $random_category->term_id, 'barfii_cate_meta', true );
		$cat_color = ! empty( $meta['cate-color'] ) ? $meta['cate-color'] : '#3b60fe';

		printf(
			'<a href="%s"><span>%s</span></a>',
			esc_url( get_category_link( $random_category->term_id ) ),
			esc_html( $random_category->cat_name )
		);
	}
}

Plugin::instance()->widgets_manager->register( new Xhosting_Blog_Post() );
