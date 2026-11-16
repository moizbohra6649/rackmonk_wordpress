<?php

/**
 * Elementor Single Widget
 * @package xhosting Tools
 * @since 1.0.0
 */

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class xHosting_Pricing_Plan extends Widget_Base {


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
		return 'xhosting-pricing-plan';
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
		return esc_html__( 'xHosting Pricing Plan', 'xhosting-library' );
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
        
        $this->end_controls_section();

		$this->start_controls_section(
			'pricing__general',
			[
				'label' => esc_html__( 'Pricing Option', 'xhosting-library' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

        $this->add_control(
            'column1_header',
            [
                'label' => __('First Column Header', 'xhosting-library'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('LTD', 'xhosting-library'),
            ]
        );

        $this->add_control(
            'column2_header',
            [
                'label' => __('Second Column Header', 'xhosting-library'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Transfer Fees', 'xhosting-library'),
            ]
        );

        $this->add_control(
            'column3_header',
            [
                'label' => __('Third Column Header', 'xhosting-library'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('New Registration Fees', 'xhosting-library'),
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'domain_extension',
            [
                'label' => __('Domain Extension', 'xhosting-library'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '.com',
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'transfer_fee',
            [
                'label' => __('Transfer Fee', 'xhosting-library'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '$6.99',
            ]
        );

        $repeater->add_control(
            'registration_fee',
            [
                'label' => __('Registration Fee', 'xhosting-library'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '$11.99',
            ]
        );

        $this->add_control(
            'domain_pricings',
            [
                'label' => __('Domain Pricing List', 'xhosting-library'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'domain_extension' => '.com',
                        'transfer_fee' => '$6.99',
                        'registration_fee' => '$11.99',
                    ],
                    [
                        'domain_extension' => '.shop',
                        'transfer_fee' => '$12.50',
                        'registration_fee' => '$10.99',
                    ],
                    [
                        'domain_extension' => '.net',
                        'transfer_fee' => '$7.99',
                        'registration_fee' => '$14.89',
                    ],
                    [
                        'domain_extension' => '.org',
                        'transfer_fee' => '$7.99',
                        'registration_fee' => '$32.50',
                    ],
                    [
                        'domain_extension' => '.online',
                        'transfer_fee' => '$6.99',
                        'registration_fee' => '$8.99',
                    ],
                    [
                        'domain_extension' => '.studio',
                        'transfer_fee' => '$6.99',
                        'registration_fee' => '$18.99',
                    ],
                    [
                        'domain_extension' => '.business',
                        'transfer_fee' => '$12.50',
                        'registration_fee' => '$34.99',
                    ],
                    [
                        'domain_extension' => '.agency',
                        'transfer_fee' => '$10.50',
                        'registration_fee' => '$22.00',
                    ],
                    [
                        'domain_extension' => '.me',
                        'transfer_fee' => '$4.99',
                        'registration_fee' => '$9.99',
                    ],
                    [
                        'domain_extension' => '.ai',
                        'transfer_fee' => '$6.99',
                        'registration_fee' => '$15.99',
                    ],
                    [
                        'domain_extension' => '.art',
                        'transfer_fee' => '$7.99',
                        'registration_fee' => '$19.20',
                    ]
                ],
                'title_field' => '{{{ domain_extension }}}',
            ]
        );
        

		
		$this->end_controls_section();
        


	}


	protected function render() {
		$settings = $this->get_settings_for_display();
		extract( $settings );
		if ( ! empty( $settings['btn_link']['url'] ) ) {
			$this->add_link_attributes( 'btn_link', $settings['btn_link'] );
		}
		require __DIR__ . '/pricing-template/pricing-plan.php';
    }




}


Plugin::instance()->widgets_manager->register( new xHosting_Pricing_Plan() );