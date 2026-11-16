<?php 

if(!function_exists('xhosting_register_custom_icon_library')){
    add_filter('elementor/icons_manager/additional_tabs', 'xhosting_register_custom_icon_library');
    function xhosting_register_custom_icon_library($tabs){
        $custom_tabs = [
            'extra_icon2' => [
                'name' => 'xhosting-flat-icon',
                'label' => esc_html__( 'Flaticon', 'xhosting' ),
                'url' => XHOSTING_DIR_URL . '/assets/css/flaticon_multipurpose_agency.css',
                'enqueue' => [ XHOSTING_DIR_URL . '/assets/css/flaticon_multipurpose_agency.css' ],
                'prefix' => 'flaticon-',
                'displayPrefix' => 'flaticon',
                'labelIcon' => 'flaticon-trophy',
                'ver' => XHOSTING_VERSION,
                'fetchJson' => get_template_directory_uri() . '/assets/js/flaticon.js?v='.XHOSTING_VERSION,
                'native' => true,
            ]
           

        ];

        $tabs = array_merge($custom_tabs, $tabs);

        return $tabs;
    }
}