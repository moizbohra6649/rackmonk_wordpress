<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class xHosting_Banner extends Widget_Base {


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
		return 'xhosting-banner';
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
		return esc_html__( 'xHosting Banner', 'xhosting-library' );
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
				'label' => esc_html__( 'Banner Style Select', 'xhosting-library' ),
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
					'2'  => esc_html__( 'Style 2', 'xhosting-library' ),
					'3'  => esc_html__( 'Style 3', 'xhosting-library' ),
					'4'  => esc_html__( 'Style 4', 'xhosting-library' ),
					'5'  => esc_html__( 'Style 5', 'xhosting-library' ),
					'6'  => esc_html__( 'Style 6', 'xhosting-library' ),
					'7'  => esc_html__( 'Style 7', 'xhosting-library' ),
					'8'  => esc_html__( 'Style 8', 'xhosting-library' ),
					'9'  => esc_html__( 'Style 9', 'xhosting-library' ),
					'10'  => esc_html__( 'Style 10', 'xhosting-library' ),
					'11'  => esc_html__( 'Style 11', 'xhosting-library' ),
					'12'  => esc_html__( 'Style 12', 'xhosting-library' ),
					'13'  => esc_html__( 'Style 13', 'xhosting-library' ),
					'14'  => esc_html__( 'Style 14', 'xhosting-library' ),
					'15'  => esc_html__( 'Style 15', 'xhosting-library' ),
					'16'  => esc_html__( 'Style 16', 'xhosting-library' )
				]
			]
		);
        
        $this->end_controls_section();

		$this->start_controls_section(
			'Banner__general',
			[
				'label' => esc_html__( 'Banner Top Option', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		$this->add_control(
			'image',
			[
				'label' => esc_html__( 'Image 1', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
			]
		);
		$this->add_control(
			'image2',
			[
				'label' => esc_html__( 'Image 2', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'style' => ['1', '4', '5', '6', '7', '8', '10', '11', '12', '13', '14']
				],
			]
		);
		$this->add_control(
			'image3',
			[
				'label' => esc_html__( 'Image 3', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'style' => ['1', '5', '6', '7', '8', '14']
				],
			]
		);
		$this->add_control(
			'image4',
			[
				'label' => esc_html__( 'Image 4', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'style' => ['1', '5', '7', '14']
				],
			]
		);
		$this->add_control(
			'image5',
			[
				'label' => esc_html__( 'Image 5', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'style' => ['7']
				],
			]
		);
		$this->add_control(
			'image6',
			[
				'label' => esc_html__( 'Image 6', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'style' => ['7']
				],
			]
		);
		$this->add_control(
			'image7',
			[
				'label' => esc_html__( 'Image 7', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'style' => ['7']
				],
			]
		);
		$this->add_control(
			'image8',
			[
				'label' => esc_html__( 'Image 8', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'style' => ['7']
				],
			]
		);
		$this->add_control(
			'image9',
			[
				'label' => esc_html__( 'Image 9', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'style' => ['7']
				],
			]
		);
		$this->add_control(
			'image10',
			[
				'label' => esc_html__( 'Image 10', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'style' => ['7']
				],
			]
		);
		$this->add_control(
			'image11',
			[
				'label' => esc_html__( 'Image 11', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'style' => ['7']
				],
			]
		);
		$this->add_control(
			'image12',
			[
				'label' => esc_html__( 'Image 12', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'style' => ['7']
				],
			]
		);
		$this->add_control(
			'image13',
			[
				'label' => esc_html__( 'Image 13', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::MEDIA,
				'condition' => [
					'style' => ['7']
				],
			]
		);
		
		$this->add_control(
			'subtitle', [
				'label' => esc_html__( 'Sub Title', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['7', '14']
				],
			]
		);

		$this->add_control(
			'shape_hide',
			[
				'label' => esc_html__( 'Shape Hide', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'xhosting-library' ),
				'label_off' => esc_html__( 'Hide', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition' => [
					'style' => ['10', '11', '15'],
				],
			]
		);
		
		$this->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
		
		$this->add_control(
			'description', [
				'label' => esc_html__( 'Description', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
		$this->add_control(
			'regular_price', [
				'label' => esc_html__( 'Reguler Price', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
				'condition' => [
					'style' => ['1', '6'],
				],
			]
		);
		$this->add_control(
			'discount_price', [
				'label' => esc_html__( 'Discount Price', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
				'condition' => [
					'style' => ['1'],
				],
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
				'condition' => [
					'style' => ['1', '4', '5', '7', '9'],
				],
			]
		);
		$this->add_control(
			'bt_icon', [
				'label' => esc_html__( 'Button Icon', 'xhosting-library' ),
				'type' => Controls_Manager::ICONS,
                'label_block' => true,
				'condition' => [
					'style' => ['1', '4', '5', '7', '9'],
					'btn_icon' => 'yes'
				],
			]
		);
		
		$this->add_control(
			'btn_label', [
				'label' => esc_html__( 'Button Label', 'xhosting-library' ),
				'default' => esc_html__( 'request a quote', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['1', '2', '4', '5', '7', '8', '9', '10', '13', '14', '16']
				],
			]
		);
		$this->add_control(
			'btn_link', [
				'label' => esc_html__( 'Button Link', 'xhosting-library' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
				'condition' => [
					'style' => ['1', '2', '4', '5', '7', '8', '9', '10', '13', '14', '16']
				],
			]
		);
		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'title', [
				'label' => esc_html__( 'List Title', 'xhosting-library' ),
				'default' => esc_html__( 'List Title', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
		$this->add_control(
			'lists',
			[
				'label' => esc_html__( 'Add List Item', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
                'title_field' => '{{{ title }}}',
                'prevent_empty' => false,
				'condition' => [
					'style' => ['5', '6', '8', '9', '11', '14']
				],
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
					'style' => ['6'],
				],
			]
		);
		
		$this->add_control(
			'rate', [
				'label' => esc_html__( 'Rate Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['6'],
				],
			]
		);
		
		$this->add_control(
			'outoftext', [
				'label' => esc_html__( 'Outof Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['6'],
				],
			]
		);
		$this->add_control(
			'total_rating', [
				'label' => esc_html__( 'Rating Total Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['6'],
				],
			]
		);
		$this->add_control(
			'review_text', [
				'label' => esc_html__( 'Review Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition' => [
					'style' => ['6'],
				],
			]
		);
		$this->end_controls_section();


		$this->start_controls_section(
			'--banner--box--style',
			[
				'label' => esc_html__( 'Banner Box Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'style' => ['1', '3', '4', '5', '14', '16']
				],
			]
		);
		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => 'banner-border',
				'selector' => '
				{{WRAPPER}} .banner__inner-wrap,
				{{WRAPPER}} .features__item,
				{{WRAPPER}} .features__item-three,
				{{WRAPPER}} .features__item-two
				',
				'condition' => [
					'style!' => ['14']
				],
			],
			
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'banner-box-bg',
				'types' => [ 'classic', 'gradient' ],
				'selector' => '
				{{WRAPPER}} .banner__inner-wrap,
				{{WRAPPER}} .features__item,
				{{WRAPPER}} .features__item-two,
				{{WRAPPER}} .features__item-three,
				{{WRAPPER}} .features__item-five,
				{{WRAPPER}} .hosting__top-area
				',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Banner Box BG Color ', 'bdoctor-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'bdoctor-plugin' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'--banner--title--style',
			[
				'label' => esc_html__( 'Banner Content  Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_control(
			'--banner-sub-title-style--',
			[
				'label' => esc_html__( 'Banner Sub Title Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'style' => ['14']
				],
			]
		);
		$this->add_control(
			'sub-title-color',
			[
				'label' => esc_html__( 'Sub Title Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sub-title' => 'color: {{VALUE}}'
				],
				'condition' => [
					'style' => ['14']
				],
			]
		);
		
        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'sub-title-fony',
				'label' => esc_html__( 'Sub Title Font', 'xhosting-library' ),
				'selector' => '
					{{WRAPPER}} .sub-title
				',
				'condition' => [
					'style' => ['14']
				],
			]
		);
		$this->add_control(
			'--banner-title-style--',
			[
				'label' => esc_html__( 'Banner Title Style', 'xhosting-library' ),
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
					'{{WRAPPER}} .title' => 'color: {{VALUE}}',
					'{{WRAPPER}} .banner__poster-content .title' => 'color: {{VALUE}}'
				],
			]
		);
		
		$this->add_control(
			'colord-title-color',
			[
				'label' => esc_html__( 'Stylih Title Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .banner__content .title span' => 'color: {{VALUE}}',
					'{{WRAPPER}} .features__content-two .title span' => 'color: {{VALUE}}'
				],
				'condition' => [
					'style' => ['1', '4']
				],
			]
		);
		
        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'title-fony',
				'label' => esc_html__( 'Title Font', 'xhosting-library' ),
				'selector' => '
					{{WRAPPER}} .title,
					{{WRAPPER}} .banner__poster-content .title
				',
			]
		);
		$this->add_control(
			'desc-style--',
			[
				'label' => esc_html__( 'Description Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'style' => ['1', '3', '5', '14']
				],
			]
		);
		$this->add_control(
			'desc-color',
			[
				'label' => esc_html__( 'Descripton Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .banner__content > p' => 'color: {{VALUE}}',
					'{{WRAPPER}} .features__content p' => 'color: {{VALUE}}',
					'{{WRAPPER}} .features__content-three > p' => 'color: {{VALUE}}',
					'{{WRAPPER}} .hosting__top-content > p' => 'color: {{VALUE}}'
				],
				'condition' => [
					'style' => ['1', '3', '5', '14']
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'dsc-fony',
				'label' => esc_html__( 'Descripton Font', 'xhosting-library' ),
				'selector' => '
					{{WRAPPER}} .banner__content > p,
					{{WRAPPER}} .features__content p,
					{{WRAPPER}} .features__content-three > p,
					{{WRAPPER}} .hosting__top-content > p
				',
				'condition' => [
					'style' => ['1', '3', '5', '14']
				],
			]
		);
		$this->add_control(
			'list-style--',
			[
				'label' => esc_html__( 'List Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'style' => ['5', '14']
				],
			]
		);
		$this->add_control(
			'list-icon-color',
			[
				'label' => esc_html__( 'List Icon Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .features__list li svg path' => 'stroke: {{VALUE}}'
				],
				'condition' => [
					'style' => ['5', '14']
				],
			]
		);
		$this->add_control(
			'list-bold-color',
			[
				'label' => esc_html__( 'List Bold Item Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .features__list li p span' => 'color: {{VALUE}}'
				],
				'condition' => [
					'style' => ['5']
				],
			]
		);
		$this->add_control(
			'list-color',
			[
				'label' => esc_html__( 'List Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .features__list li p' => 'color: {{VALUE}}',
					'{{WRAPPER}} .list-wrap li' => 'color: {{VALUE}}'
				],
				'condition' => [
					'style' => ['5', '14']
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'list-fony',
				'label' => esc_html__( 'List Font', 'xhosting-library' ),
				'selector' => '
					{{WRAPPER}} .features__list li p,
					{{WRAPPER}} .list-wrap li
				',
				'condition' => [
					'style' => ['5', '14']
				],
			]
		);
		
		
		$this->add_control(
			'tp--button-style',
			[
				'label' => esc_html__( 'Button Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'style!' => ['3']
				],
			]
		);
		$this->add_control(
			'btn-icon-size',
			[
				'label' => esc_html__( 'Icon Font Size', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-btn svg' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .tg-btn i' => 'font-size: {{SIZE}}{{UNIT}};'
				],
				'condition' => [
					'style' => ['1']
				],
			]
		);
		$this->add_control(
			'btn-cion-color',
			[
				'label' => esc_html__( 'Button Icon Color', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn svg path' => 'stroke: {{VALUE}}',
					'{{WRAPPER}} .tg-btn i' => 'color: {{VALUE}}'
				],
				'condition' => [
					'style' => ['1']
				],
			]
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'tbs_typography',
				'selector' => '
				{{WRAPPER}} .tg-btn,
				{{WRAPPER}} .tg-link-btn
				',
			]
		);
        $this->add_control(
			'tb-padding',
			[
				'label' => esc_html__( 'Padding', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .tg-link-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
			]
		);
        $this->add_control(
			'tbr_round',
			[
				'label' => esc_html__( 'Border Radius', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .tg-link-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
			]
		);


        $this->start_controls_tabs(
			'style_tbs_tabs'
		);

		$this->start_controls_tab(
			'style_tbs_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'bdoctor-plugin' ),
			]
		);
        $this->add_control(
			'tbs_btn_text',
			[
				'label' => esc_html__( 'Text Color', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn' => 'color: {{VALUE}}',
					'{{WRAPPER}} .tg-link-btn' => 'color: {{VALUE}}'
				],
			]
		);
        $this->add_control(
			'tbs_btnbo',
			[
				'label' => esc_html__( 'Btn Border Color', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .features__content-three .tg-link-btn::after, .features__content-three .tg-link-btn::before' => 'background: {{VALUE}}'
				],
				'condition' => [
					'style' => ['5']
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'tbs_button_bg_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '
				{{WRAPPER}} .tg-btn,
				{{WRAPPER}} .tg-link-btn
				',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Button BG Color ', 'bdoctor-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'bdoctor-plugin' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);
        $this->end_controls_tab();
        $this->start_controls_tab(
			'styletbs__hover_tab',
			[
				'label' => esc_html__( 'Hover', 'bdoctor-plugin' ),
			]
		);
        $this->add_control(
			'tbs_btn_h_text',
			[
				'label' => esc_html__( 'Text Hover Color', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-btn:hover' => 'color: {{VALUE}}',
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'tbs_button_bg_hover_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .tg-btn:hover',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Button Hover BG Color ', 'bdoctor-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'bdoctor-plugin' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);
        $this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();

	}


	protected function render() {
		$settings = $this->get_settings_for_display();
		extract( $settings );
		require __DIR__ . '/banner-template/banner-' . $settings['style'] . '.php';
    }




}


Plugin::instance()->widgets_manager->register( new xHosting_Banner() );