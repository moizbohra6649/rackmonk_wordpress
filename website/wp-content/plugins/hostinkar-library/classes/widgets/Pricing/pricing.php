<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class xHosting_Pricing extends Widget_Base {


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
		return 'xhosting-pricing';
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
		return esc_html__( 'xHosting Pricing', 'xhosting-library' );
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
			'__pricing__style__',
			[
				'label' => esc_html__( 'Pricing Style Select', 'xhosting-library' ),
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
					'4'  => esc_html__( 'Style 4', 'xhosting-library' )
				]
			]
		);
		$this->add_control(
			'rackmonk_source_note',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => wp_kses_post( 'When WHMCS plans exist, the live site shows them here instead of the items below. Edit plan names, taglines and features in <strong>WP Admin &gt; Website Content &gt; Plan Content</strong>, prices in <strong>WHMCS Plans</strong>, and the tab labels, badge, button and “/mo” text in <strong>Website Content &gt; Pricing content</strong>.' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

        $this->end_controls_section();

		$this->start_controls_section(
			'pricing__general',
			[
				'label' => esc_html__( 'Pricing Option', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);
		$this->add_control(
			'hide_my',
			[
				'label' => esc_html__( 'Monthly/Yearly HIDE/SHOW', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'YES', 'xhosting-library' ),
				'label_off' => esc_html__( 'NO', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);
        $this->add_control(
			'm_label', [
				'label' => esc_html__( 'Month Label', 'xhosting-library' ),
				'label' => esc_html__( 'Monthly', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition'      => [
                    'hide_my' => 'yes',
                ],
			]
		);
        $this->add_control(
			'y_label', [
				'label' => esc_html__( 'Yearly Label', 'xhosting-library' ),
				'label' => __( 'Yearly <strong>(save 30%)</strong>', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition'      => [
                    'hide_my' => 'yes',
                ],
			]
		);
		$this->add_control(
			'more_feature_hide',
			[
				'label' => esc_html__( 'More Feature HIDE/SHOW', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'YES', 'xhosting-library' ),
				'label_off' => esc_html__( 'NO', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);
        $this->add_control(
			'more_feature', [
				'label' => esc_html__( 'More Feature', 'xhosting-library' ),
				'default' => __( 'More Features', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition'      => [
                    'more_feature_hide' => 'yes',
                ],
			]
		);
        $this->add_control(
			'less_feature', [
				'label' => esc_html__( 'Less Feature', 'xhosting-library' ),
				'default' => __( 'Less Features', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'condition'      => [
                    'more_feature_hide' => 'yes',
                ],
			]
		);
		$repeater = new \Elementor\Repeater();
        $repeater->add_control(
			'is_populer',
			[
				'label' => esc_html__( 'Populer Item', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'YES', 'xhosting-library' ),
				'label_off' => esc_html__( 'NO', 'xhosting-library' ),
				'return_value' => 'yes',
				'default' => 'no',
			]
		);
        $repeater->add_control(
			'populer_badge', [
				'label' => esc_html__( 'Populer Text', 'xhosting-library' ),
				'default' => esc_html__( 'Popular', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'condition'      => [
                    'is_populer' => 'yes',
                ],
			]
		);
        
        $repeater->add_control(
			'title', [
				'label' => esc_html__( 'Title', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        
        $repeater->add_control(
			'text', [
				'label' => esc_html__( 'Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
        
        $repeater->add_control(
			'm_price', [
				'label' => esc_html__( 'Monthly Price', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        $repeater->add_control(
			'y_price', [
				'label' => esc_html__( 'Yearly Price', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        $repeater->add_control(
			'm_period', [
				'label' => esc_html__( 'Monthly Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        $repeater->add_control(
			'y_period', [
				'label' => esc_html__( 'Yearly Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        $repeater->add_control(
			'p_text', [
				'label' => esc_html__( 'Price Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
			]
		);
        $repeater->add_control(
			'd_text', [
				'label' => esc_html__( 'Discount Text', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);

       $repeater->add_control(
			'active_btn', [
				'label'        => __( 'Active Button', 'xhosting-library' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'YES', 'xhosting-library' ),
				'label_off'    => __( 'NO', 'xhosting-library' ),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);
        $repeater->add_control(
			'btn_label', [
				'label' => esc_html__( 'Button Label', 'xhosting-library' ),
				'type' => Controls_Manager::TEXT,
                'label_block' => true,
			]
		);
        
        $repeater->add_control(
			'link', [
				'label' => esc_html__( 'Link', 'xhosting-library' ),
				'type' => Controls_Manager::URL,
                'label_block' => true,
			]
		);
        $repeater->add_control(
            'lists',
            [
                'label' => __('Add List Item', 'xhosting-library'),
                'type' => Controls_Manager::REPEATER,
				'prevent_empty' => false,
                'fields' => [
                    [
						'name' => 'marked_hide',
                        'label' => esc_html__( 'Marked HIDE/SHOW', 'xhosting-library' ),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => esc_html__( 'Show', 'xhosting-library' ),
                        'label_off' => esc_html__( 'Hide', 'xhosting-library' ),
                        'return_value' => 'yes',
                        'default' => 'no',
                    ],
					 [
                        'name' => 'info_title',
                        'label' => __('Info Text', 'xhosting-library'),
                        'type' => Controls_Manager::TEXTAREA,
						'label_block' => true,
						'condition' => [
							'marked_hide' => 'yes'
						],
                    ],
                    [
                        'name' => 'icon',
                        'label' => __('List Icon', 'xhosting-library'),
                        'type' => Controls_Manager::ICONS,
                    ],
                    [
                        'name' => 'title',
                        'label' => __('List Title', 'xhosting-library'),
                        'type' => Controls_Manager::TEXT,
						'label_block' => true,
                    ],
                ],
            ],
        );
        $this->add_control(
			'pricings',
			[
				'label' => esc_html__( 'Add pricing Item', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
                'title_field' => '{{{ title }}}',
                'prevent_empty' => false
			]
		);	
		
		$this->end_controls_section();
        $this->start_controls_section(
			'--pricing--switch--style',
			[
				'label' => esc_html__( 'Pricing Switch  Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_control(
			'price-switch-color',
			[
				'label' => esc_html__( 'Pricing Switch Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .pricing-tab .pricing-tab-switcher' => 'background: {{VALUE}}'
				],
			]
		);        
		$this->end_controls_section();

        $this->start_controls_section(
			'--pricing--box--style',
			[
				'label' => esc_html__( 'pricing Content  Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'plist_he',
			[
				'label' => esc_html__( 'Width', 'textdomain' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .pricing__list' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);
        $this->add_responsive_control(
            'padding',
            [
                'label' => esc_html__( 'Box Padding', 'textdomain' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'selectors' => [
                    '{{WRAPPER}} .pricing__box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'box-round',
            [
                'label' => esc_html__( 'Box Round', 'textdomain' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'selectors' => [
                    '{{WRAPPER}} .pricing__box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'box-bg',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .pricing__box',
                'fields_options' => [
                    'background' => [
                        'label' => esc_html__( 'Pricing Box Color ', 'xhosting-plugin' ),
                        'description' => esc_html__( 'Choose background type and style.', 'xhosting-plugin' ),
                        'separator' => 'before',
                    ]
                ]
			]
		);
		$this->end_controls_section();

        $this->start_controls_section(
			'--pricing--title--style',
			[
				'label' => esc_html__( 'Pricing Box Content  Style', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_control(
			'--pricing-icon-style--',
			[
				'label' => esc_html__( 'pricing Icon Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
        $this->add_control(
			'icon-color',
			[
				'label' => esc_html__( 'Icon Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hosting__icon' => 'color: {{VALUE}}'
				],
			]
		);

		$this->add_control(
			'--pricing-title-style--',
			[
				'label' => esc_html__( 'pricing Title Style', 'xhosting-library' ),
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
					'{{WRAPPER}} .hosting__content .title' => 'color: {{VALUE}}'
				],
			]
		);
		
		$this->add_control(
			'colord-title-color',
			[
				'label' => esc_html__( 'Title Hover Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hosting__content .title a:hover' => 'color: {{VALUE}}'
				]
			]
		);
		
        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'title-fony',
				'label' => esc_html__( 'Title Font', 'xhosting-library' ),
				'selector' => '
					{{WRAPPER}} .hosting__content .title
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
					'style' => ['1']
				],
			]
		);
		$this->add_control(
			'desc-color',
			[
				'label' => esc_html__( 'Descripton Color', 'xhosting-library' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hosting__content p' => 'color: {{VALUE}}'
				],
				'condition' => [
					'style' => ['1']
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'dsc-fony',
				'label' => esc_html__( 'Descripton Font', 'xhosting-library' ),
				'selector' => '
					{{WRAPPER}} .hosting__content p
				',
				'condition' => [
					'style' => ['1']
				],
			]
		);
		
		
		$this->add_control(
			'tp--button-style',
			[
				'label' => esc_html__( 'Button Style', 'xhosting-library' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'tbs_typography',
				'selector' => '{{WRAPPER}} .tg-btn',
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
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'tbs_button_bg_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .tg-btn',
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
        $this->add_control(
			'tbs_border_h_text',
			[
				'label' => esc_html__( 'Text Hover Border Color', 'bdoctor-plugin' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tg-border-btn.active, .tg-border-btn:hover' => 'border-color: {{VALUE}}',
				],
			]
		);
        $this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'tbs_button_bg_hover_color',
				'types' => [ 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '
				{{WRAPPER}} .tg-btn:hover,
				{{WRAPPER}} .tg-border-btn.active, .tg-border-btn:hover
				',
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

		// Dynamic WHMCS & Rackmonk Plans Integration
		if ( class_exists( "Rackmonk_Plans_Manager" ) ) {
			$gid = null;
			if ( is_page( "vps-hosting" ) || is_page( "vps" ) ) {
				$gid = 3;
			} elseif ( is_page( "cloud-hosting" ) || is_page( "cloud" ) ) {
				$gid = 2;
			} elseif ( is_page( "wp-hosting" ) || is_page( "wordpress-hosting" ) ) {
				$gid = 4;
			} elseif ( is_page( "shared-hosting" ) || is_page( "web-hosting" ) || is_page( "shared-hosting-2" ) ) {
				$gid = 1;
			}

			$dynamic_items = \Rackmonk_Plans_Manager::get_instance()->get_elementor_pricing_items( $gid );
			if ( ! empty( $dynamic_items ) ) {
				// Labels are editable in WP Admin > Website Content > Pricing content (defaults = previous fixed text).
				$rm_text = static function ( $key, $default ) {
					return class_exists( '\Rackmonk_Site_Content' ) ? \Rackmonk_Site_Content::text( $key, $default ) : $default;
				};
				foreach ( $dynamic_items as &$rm_item ) {
					$rm_item['populer_badge'] = $rm_text( 'pricing_cards_badge', $rm_item['populer_badge'] );
					$rm_item['btn_label']     = $rm_text( 'pricing_cards_button', $rm_item['btn_label'] );
					$rm_item['m_period']      = $rm_text( 'pricing_cards_period_48', $rm_item['m_period'] );
					$rm_item['y_period']      = $rm_text( 'pricing_cards_period_12', $rm_item['y_period'] );
				}
				unset( $rm_item );
				$settings["pricings"] = $dynamic_items;
				$settings["m_label"]  = $rm_text( 'pricing_cards_tab_48', '48 Months (Save up to 70%)' );
				$settings["y_label"]  = $rm_text( 'pricing_cards_tab_12', '12 Months (Save 50%)' );
			}
		}
		extract( $settings );
		if ( ! empty( $settings['btn_link']['url'] ) ) {
			$this->add_link_attributes( 'btn_link', $settings['btn_link'] );
		}
		$cycles = [];
		foreach ( $settings['pricings'] as $item ) {
			$cycles[] = $item['rackmonk_cycles'] ?? null;
		}
		if ( ! empty( $dynamic_items ) ) {
			echo '<div data-rm-hostinkar-cycles="' . esc_attr( wp_json_encode( $cycles ) ) . '">';
		}
		require __DIR__ . '/pricing-template/pricing-' . $settings['style'] . '.php';
		if ( ! empty( $dynamic_items ) ) echo '</div>';
    }




}


Plugin::instance()->widgets_manager->register( new xHosting_Pricing() );
