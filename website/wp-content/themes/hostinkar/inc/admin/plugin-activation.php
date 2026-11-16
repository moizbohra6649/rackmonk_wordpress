<?php

add_action('tgmpa_register', 'xhosting_register_required_plugins');

function xhosting_register_required_plugins(){

    $plugins = array(
        array(
            'name' => esc_html__('Hostinkar Library', 'hostinkar'),
            'slug' => 'hostinkar-library',
            'source' => ('https://www.byteflows.net/wp/hostinkar/bytf__installable-plugin/hostinkar-library.zip'),
            'required' => true,
            'force_activation' => false,
            'force_deactivation' => false,
        ),
        array(
            'name' => 'Elementor Website Builder',
            'slug' => 'elementor',
            'required' => true,
        ),
        array(
            'name' => esc_html__('Envato Market', 'hostinkar'),
            'slug' => 'envato-market',
            'source' => esc_url('https://goo.gl/pkJS33'),
            'external_url' => esc_url('https://goo.gl/pkJS33'),
            'required' => false,
        ),

        array(
            'name' => esc_html__('Contact Form 7', 'hostinkar'),
            'slug' => 'contact-form-7',
            'required' => false,
        ),
        array(
            'name' => esc_html__('One Click Demo Import', 'hostinkar'),
            'slug' => 'one-click-demo-import',
            'required' => true,
        ),
        array(
            'name' => esc_html__('WooCommerce', 'hostinkar'),
            'slug' => 'woocommerce',
            'required' => false,
        )

    );

    $config = array(
        'id' => 'hostinkar',
        'parent_slug' => 'hostinkar',
        'menu' => 'tgmpa-install-plugins',
        'dismissable' => true,
        'dismiss_msg' => '',
        'is_automatic' => false,
        'message' => '',
        'default_path' => '',
    );

    tgmpa($plugins, $config);
}
