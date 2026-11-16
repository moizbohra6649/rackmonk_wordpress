<?php
if(!function_exists('xhosting_page_template_type')  ){
    function xhosting_page_template_type(){
		register_post_type( 'xhosting_template',
		array(
			  'labels' => array(
				'name' => __( 'Template','xhosting-library' ),
				'singular_name' => __( 'Template','xhosting-library' )
			  ),
			'public' => true,
			'publicly_queryable' => true,
			'show_in_menu'      => false,
			'show_in_nav_menus'   => false,
			'supports' => ['title', 'elementor']
		)
		);
	}
	add_action( 'init','xhosting_page_template_type',2 );
}
