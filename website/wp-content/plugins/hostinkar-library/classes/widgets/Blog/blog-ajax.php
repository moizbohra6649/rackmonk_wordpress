<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Xhosting_Ajax_Post extends Widget_Base {

	protected function get_all_posts() {
        $posts = get_posts( array( 'post_type' => 'post', 'numberposts' => -1 ) );
        $post_options = array();
        foreach ( $posts as $post ) {
            $post_options[ $post->ID ] = $post->post_title;
        }
        return $post_options;
    }

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
		return 'xhosting-post-ajax';
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
		return esc_html__( 'Post Ajax', 'xhosting-plugin' );
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
			'print_header_layout',
			[
				'label' => esc_html__( 'Header Layout', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		$this->add_control(
			'style',
			[
				'label' => esc_html__( 'Post Style', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'  => esc_html__( 'Style 1', 'xhosting-plugin' ),
					'2'  => esc_html__( 'Style 2', 'xhosting-plugin' ),
				]
			]
		);
		$this->end_controls_section();


		//hader top
		$this->start_controls_section(
			'__posts_info',
			[
				'label' => esc_html__( 'Posts Option', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		$this->add_control(
			'postcustomorder',
			[
				'label'        => esc_html__( 'Custom Order', 'xhosting-plugin' ),
				'type'         =>Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);
		$this->add_control(
			'postorder',
			[
				'label'     => esc_html__( 'Post Order', 'xhosting-plugin' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'ASC',
				'options'   => [
					'ASC'  => esc_html__( 'Ascending', 'xhosting-plugin' ),
					'DESC' => esc_html__( 'Descending', 'xhosting-plugin' ),
				],
			]
		);
		$this->add_control(
			'postorderby',
			[
				'label'     => esc_html__( 'Post Order By', 'xhosting-plugin' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'none',
				'options'   => [
					'none'          => esc_html__( 'None', 'xhosting-plugin' ),
					'ID'            => esc_html__( 'ID', 'xhosting-plugin' ),
					'date'          => esc_html__( 'Date', 'xhosting-plugin' ),
					'name'          => esc_html__( 'Name', 'xhosting-plugin' ),
					'title'         => esc_html__( 'Title', 'xhosting-plugin' ),
					'comment_count' => esc_html__( 'Comment count', 'xhosting-plugin' ),
					'rand'          => esc_html__( 'Random', 'xhosting-plugin' ),
				],
				'condition' => ['postcustomorder' => 'yes'],
			]
		);
		$this->add_control(
			'pst_per_page',
			[
				'label'   => __( 'Posts Per Page', 'xhosting-plugin' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'default' => 1,
			]
		);
		$this->add_control(
			'skip_post',
			[
				'label'          => esc_html__( 'Skip Post Enable?', 'xhosting-plugin' ),
				'type'           => Controls_Manager::SWITCHER,
				'label_on'       => esc_html__( 'Show', 'xhosting-plugin' ),
				'label_off'      => esc_html__( 'Hide', 'xhosting-plugin' ),
				'return_value'   => 'yes',
				'default'        => 'yes',
				'style_transfer' => true,
			]
		);
		
		$this->add_control(
			'skip_post_count',
			[
				'label'   => __( 'Skip Post Count', 'xhosting-plugin' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
				'condition' => ['skip_post' => 'yes'],
			]
		);
		$this->add_control(
			'category',
			array(
			  'label'       => esc_html__( 'Choose Categories', 'xhosting-plugin' ),
			  'type'        => \Elementor\Controls_Manager::SELECT2,
			  'multiple'    => true,
			  'options'     => array_flip(xhosting_get_category_lists( 'categories', array(
				'sort_order'  => 'ASC',
				'taxonomy'    => 'category',
				'hide_empty'  => true,
			  ) )),
			  'label_block' => true,
			)
		  );
		  $this->add_control(
			'exclude_cate',
			array(
			'label'       => esc_html__( 'Exclude Categories', 'xhosting-plugin' ),
			'description'       => esc_html__( 'Please Select Which Category Post you want to Exclude', 'xhosting-plugin' ),
			'type'        => \Elementor\Controls_Manager::SELECT2,
			'multiple'    => true,
			'options'     => array_flip(xhosting_get_category_lists( 'categories', array(
				'sort_order'  => 'ASC',
				'taxonomy'    => 'category',
				'hide_empty'  => false,
			) )),
			'label_block' => true,
			)
		);
		$this->add_control(
			'selected_posts',
			[
				'label' => __( 'Select Indivisual Post', 'xhosting-plugin' ),
				'description'       => esc_html__( 'You Can Selete your Favorite Post Here', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'options' => $this->get_all_posts(),
				'multiple' => true,
				'label_block' => true,
			]
		);
		$this->add_control(
			'exclude_posts',
			[
				'label' => __( 'Exclude Post', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'options' => $this->get_all_posts(),
				'multiple' => true,
				'label_block' => true,
			]
		);
		$this->add_control(
			'post_tags',
			[
				'type'        => Controls_Manager::SELECT2,
				'label'       => esc_html__( 'Select Tags', 'xhosting-plugin' ),
				'multiple'    => true,
                  'options'     => array_flip(xhosting_item_tag_lists( 'tags', array(
                    'sort_order'  => 'ASC',
                    'taxonomies'    => 'post_tag',
                    'hide_empty'  => false,
                ) )),
			]
		);
		$this->add_control(
			'post_format', [
			   
				'label'   => esc_html__('Select Post Format', 'xhosting-plugin'),
				'type'    => Controls_Manager::SELECT2,
				'options' => [
					
					'post-format-video' => esc_html__( 'Video', 'xhosting-plugin' ),
					'post-format-image' => esc_html__( 'Image', 'xhosting-plugin' ),
					'post-format-audio' => esc_html__( 'Audio', 'xhosting-plugin' ),
					'post-format-gallery' => esc_html__( 'Gallery', 'xhosting-plugin' ),
					'post-format-standard' => esc_html__( 'Standard', 'xhosting-plugin' ),
				],
				'default'     => [],
				'label_block' => true,
				'multiple'    => true,
			]
		);
		$this->add_control(
			'title_length',
			[
				'label'     => __( 'Title Length', 'xhosting-plugin' ),
				'type'      => Controls_Manager::NUMBER,
				'step'      => 1,
				'default'   => 20,
			]
		);
		$this->add_control(
			'excerpt_hide',
			[
				'label'          => esc_html__( 'Excerpt Hide/SHOW', 'xhosting-plugin' ),
				'type'           => Controls_Manager::SWITCHER,
				'label_on'       => esc_html__( 'Show', 'xhosting-plugin' ),
				'label_off'      => esc_html__( 'Hide', 'xhosting-plugin' ),
				'return_value'   => 'yes',
				'default'        => 'no',
				'style_transfer' => true,
			]
		);
		$this->add_control(
			'excerpt_length',
			[
				'label'     => __( 'Excerpt Length', 'xhosting-plugin' ),
				'type'      => Controls_Manager::NUMBER,
				'step'      => 1,
				'default'   => 30,
				'condition' => ['excerpt_hide' => 'yes'],
			]
		);
        $this->add_control(
			'authore_hide',
			[
				'label'          => esc_html__( 'Authore Hide', 'xhosting-plugin' ),
				'type'           => Controls_Manager::SWITCHER,
				'label_on'       => esc_html__( 'Show', 'xhosting-plugin' ),
				'label_off'      => esc_html__( 'Hide', 'xhosting-plugin' ),
				'return_value'   => 'yes',
				'default'        => 'yes',
				'style_transfer' => true,
			]
		);
		$this->add_control(
			'cmnt_hide',
			[
				'label'          => esc_html__( 'Comment Hide', 'xhosting-plugin' ),
				'type'           => Controls_Manager::SWITCHER,
				'label_on'       => esc_html__( 'Show', 'xhosting-plugin' ),
				'label_off'      => esc_html__( 'Hide', 'xhosting-plugin' ),
				'return_value'   => 'yes',
				'default'        => 'no',
				'style_transfer' => true,
			]
		);
		$this->add_control(
			'date_hide',
			[
				'label'          => esc_html__( 'Hide Date', 'xhosting-plugin' ),
				'type'           => Controls_Manager::SWITCHER,
				'label_on'       => esc_html__( 'Show', 'xhosting-plugin' ),
				'label_off'      => esc_html__( 'Hide', 'xhosting-plugin' ),
				'return_value'   => 'yes',
				'default'        => 'yes',
				'style_transfer' => true,
			]
		);
		$this->add_control(
			'cat_hide',
			[
				'label'          => esc_html__( 'Hide Category', 'xhosting-plugin' ),
				'type'           => Controls_Manager::SWITCHER,
				'label_on'       => esc_html__( 'Show', 'xhosting-plugin' ),
				'label_off'      => esc_html__( 'Hide', 'xhosting-plugin' ),
				'return_value'   => 'yes',
				'default'        => 'yes',
				'style_transfer' => true,
			]
		);
		$this->add_control(
			'cate_style',
			[
				'label' => esc_html__( 'Category Style', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1'  => esc_html__( 'Style 1', 'xhosting-plugin' ),
					'2'  => esc_html__( 'Style 2', 'xhosting-plugin' ),
					'3'  => esc_html__( 'Style 3', 'xhosting-plugin' ),
					'4'  => esc_html__( 'Style 4', 'xhosting-plugin' ),
					'5'  => esc_html__( 'Style 5', 'xhosting-plugin' ),
				]
			]
		);
		$this->add_control(
			'show_video',
			[
				'label'          => esc_html__( 'Show Video', 'xhosting-plugin' ),
				'type'           => Controls_Manager::SWITCHER,
				'label_on'       => esc_html__( 'Show', 'xhosting-plugin' ),
				'label_off'      => esc_html__( 'Hide', 'xhosting-plugin' ),
				'return_value'   => 'yes',
				'default'        => 'no',
				'style_transfer' => true,
			]
		);
		$this->add_control(
			'show_gllery',
			[
				'label'          => esc_html__( 'Show Gallery', 'xhosting-plugin' ),
				'type'           => Controls_Manager::SWITCHER,
				'label_on'       => esc_html__( 'Show', 'xhosting-plugin' ),
				'label_off'      => esc_html__( 'Hide', 'xhosting-plugin' ),
				'return_value'   => 'yes',
				'default'        => 'no',
				'style_transfer' => true,
			]
		);
		
		
		
		$this->end_controls_section();

        
		$this->start_controls_section(
			'--image-settings--',
			[
				'label' => esc_html__( 'Image Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'width',
			[
				'label' => esc_html__( 'Image Width', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bytf__feature-image img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);
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
					'{{WRAPPER}} .bytf__feature-image img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'img_box_width',
			[
				'label' => esc_html__( 'Image Box Width', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bytf__feature-image' => 'max-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bytf__feature-image' => 'flex:0 0 {{SIZE}}{{UNIT}};',
				],
			]
		);
		
		$this->add_responsive_control(
			'--img-margin--',
			[
				'label' => esc_html__( 'Image margin', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .bytf__feature-image' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();
		$this->start_controls_section(
			'--content-settings--',
			[
				'label' => esc_html__( 'Content Box Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		
		$this->add_responsive_control(
			'con_padding',
			[
				'label' => esc_html__( 'Padding', 'textdomain' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .bytf__post-overlay-item .bytf__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();


		$this->start_controls_section(
			'--title-settings--',
			[
				'label' => esc_html__( 'Title Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		
        $this->add_control(
			'title--color',
			[
				'label' => esc_html__( 'Title Color', 'xhosting-plugin' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bytf_post_title' => 'color: {{VALUE}}',
				],
			]
		);
		$this->add_control(
			'title-hover-color',
			[
				'label' => esc_html__( 'Title Hover Color', 'xhosting-plugin' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bytf_post_title a' => 'background-image: linear-gradient({{VALUE}}, {{VALUE}})'
				],
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'title__typography',
				'label' => esc_html__( 'Title Font', 'xhosting-plugin' ),
				'selector' => '{{WRAPPER}} .bytf_post_title',
			]
		);
		$this->add_responsive_control(
			'--title-margin--',
			[
				'label' => esc_html__( 'Title margin', 'textdomain' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .bytf_post_title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
		$this->start_controls_section(
			'--excerpt-settings--',
			[
				'label' => esc_html__( 'Excerpt Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		
        $this->add_control(
			'excerpt--color',
			[
				'label' => esc_html__( 'Excerpt Color', 'xhosting-plugin' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bytf__content p' => 'color: {{VALUE}}',
				],
			]
		);


        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'excerpt__typography',
				'label' => esc_html__( 'Excerpt Font', 'xhosting-plugin' ),
				'selector' => '{{WRAPPER}} .bytf__content p',
			]
		);
		$this->add_responsive_control(
			'--excerpt-margin--',
			[
				'label' => esc_html__( 'Excerpt margin', 'textdomain' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .bytf__content p' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'--metta-settings--',
			[
				'label' => esc_html__( 'Meta Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_control(
			'meta--color',
			[
				'label' => esc_html__( 'Meta Color', 'xhosting-plugin' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bytf__postmeta.meta__dark ul li' => 'color: {{VALUE}}',
					'{{WRAPPER}} .bytf__postmeta.meta__dark ul li a' => 'color: {{VALUE}}',
				],
			]
		);
		
		$this->add_responsive_control(
			'--meta-margin--',
			[
				'label' => esc_html__( 'Meta margin', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .bytf__postmeta-info-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
		

		$this->start_controls_section(
			'slider_button_one',
			[
				'label' => esc_html__( 'Button Style', 'xhosting-plugin' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'm_b_typography',
				'selector' => '{{WRAPPER}} .ptx-btn-1 a, .ptx-btn-2 a, .ptx-btn-3 a, .ptx-btn-4 a, .ptx-btn-5 a, .ptx-btn-6 a',
			]
		);
        $this->add_control(
			'padding',
			[
				'label' => esc_html__( 'Padding', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .ptx-btn-1 a, .ptx-btn-2 a, .ptx-btn-3 a, .ptx-btn-4 a, .ptx-btn-5 a, .ptx-btn-6 a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
        $this->add_control(
			'b_round',
			[
				'label' => esc_html__( 'Border Radius', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .ptx-btn-1 a, .ptx-btn-2 a, .ptx-btn-3 a, .ptx-btn-4 a, .ptx-btn-5 a, .ptx-btn-6 a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
        $this->add_control(
			'btn_text',
			[
				'label' => esc_html__( 'Text Color', 'xhosting-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ptx-btn-1 a, .ptx-btn-2 a, .ptx-btn-3 a, .ptx-btn-4 a, .ptx-btn-5 a, .ptx-btn-6 a' => 'color: {{VALUE}}',
				],
			]
		);

        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'button_bg_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .ptx-btn-1 a, .ptx-btn-2 a, .ptx-btn-3 a, .ptx-btn-4 a, .ptx-btn-5 a, .ptx-btn-6 a',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Button BG Color ', 'xhosting-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'xhosting-plugin' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);
        
        $this->end_controls_section();



	}


	/**
	 * Main Contetn
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

        if ( get_query_var('paged') ) {
			$paged = get_query_var('paged');
		} else if ( get_query_var('page') ) {
			$paged = get_query_var('page');
		} else {
			$paged = 1;
		}


		$args = array(
			'post_type'           => 'post',
			'posts_per_page'      => !empty( $settings['pst_per_page'] ) ? $settings['pst_per_page'] : 1,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
            'paged'				  => $paged,
			'order'               => $settings['postorder'],
			'orderby'             => $settings['postorderby'],
			
			'suppress_filters' => false
		);


		if(!empty($settings['category'][0])) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'category',
					'field'    => 'ids',
					'terms'    => $settings['category']
				)
			);
		}

		if(!empty($settings['exclude_cate'][0])) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'category',
					'field'    => 'ids',
					'terms'    => $settings['exclude_cate'],
					'operator' => 'NOT IN'
				)
			);
		}


		if("yes" == $settings['skip_post']){
			$args['offset'] = $settings['skip_post_count'];
		}
		if(!empty($settings['post_tags'][0])) {
			$args['tax_query'] = array(
				array(
				'taxonomy' => 'post_tag',
				'field'    => 'ids',
				'terms'    => $settings['post_tags']
				)
			);
		}
		if( isset($settings['post_format']) && is_array($settings['post_format']) && count($settings['post_format']) > 0  ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'post_format',
					'field'    => 'slug',
					'terms'    => $settings['post_format'],
					'operator' => 'IN'
				) 
			);
		} 
		
		
		$query = new \WP_Query( $args );

        $ajax_json_data = [
			'order' => $settings['postorder'],
			'orderby' => $settings['postorderby'],
			'posts_per_page' => $settings['pst_per_page'],
			'skip_post' => $settings['skip_post'],
			'terms' => $settings['category'],
			'tags' => $settings['post_tags'],
			'postcustomorder' => $settings['postcustomorder'],
			'post_format' => $settings['post_format'],
			'title_length' => $settings['title_length'],
			'excerpt_length' => $settings['excerpt_length'],
			'skip_post_count' => $settings['skip_post_count'],
			'authore_hide' => $settings['authore_hide'],
			'excerpt_hide' => $settings['excerpt_hide'],
			'cmnt_hide' => $settings['cmnt_hide'],
			'date_hide' => $settings['date_hide'],
			'cat_hide' => $settings['cat_hide'],
			'cate_style' => $settings['cate_style'],
		];
		$ajax_json_data = json_encode($ajax_json_data);?>
        <div class="post__wrapper">
            <div class="grid-loadmore-content row">
                <?php
                if ( $query->have_posts() ) {
                $i = 0;
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $post_id    = get_the_ID();
                    $title      = get_the_title();
                    $excerpt    = get_the_excerpt();
                    $author_id  = get_the_author_meta('ID');
                    $author_name = get_the_author();
                    $author_url  = get_author_posts_url($author_id);
                    $author_avatar = get_avatar_url($author_id, ['size' => 32]);
                    $post_date  = get_the_date('d M, Y');
                    $categories = get_the_category();
                    $first_cat  = !empty($categories) ? $categories[0]->name : '';
                    $first_cat_url = !empty($categories) ? get_category_link($categories[0]->term_id) : '#';
                    $thumbnail = get_the_post_thumbnail_url($post_id, 'full') ?: 'https://via.placeholder.com/600x400?text=No+Image';

                $i++;
                    
                ?>
                <div class="col-lg-4 col-md-6 ajax__post-item">
                    <div class="blog__post-item">
                        <div class="blog__post-thumb">
                            <a href="<?php the_permalink(); ?>">
                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($title); ?>">
                            </a>
                            <?php if ( $first_cat ) : ?>
                                <a href="<?php echo esc_url($first_cat_url); ?>" class="blog__post-tag">
                                    <?php echo esc_html($first_cat); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="blog__post-content">
                        <h2 class="blog__post-title">
                            <a href="<?php the_permalink(); ?>">
                                <?php echo esc_html(wp_trim_words($title, $settings['title_length'], '...')); ?>
                            </a>
                        </h2>
                        <p>
                            <?php echo esc_html(wp_trim_words($excerpt, $settings['excerpt_length'], '...')); ?>
                        </p>
                        <div class="blog__post-meta">
                            <ul class="list-wrap">
                                <li class="blog__post-author">
                                    <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author_name); ?>">
                                    <a href="<?php echo esc_url($author_url); ?>">
                                        <?php echo esc_html($author_name); ?>
                                    </a>
                                </li>
                                <li><?php echo esc_html($post_date); ?></li>
                            </ul>
                        </div>
                    </div>
                    </div>
                </div>
                <?php } wp_reset_query(); } ?>
            </div>
            
        </div>
	<?php
    }






}

Plugin::instance()->widgets_manager->register( new Xhosting_Ajax_Post() );