<?php 

if ( !function_exists( 'xhosting_tour_type_tax' ) ) {
    function xhosting_tour_type_tax() {
        $labels = [
            'name'          => esc_html__( 'Tour Type', 'xhosting-library' ),
            'menu_name'     => esc_html__( 'Tour Type', 'xhosting-library' ),
            'singular_name' => esc_html__( 'Tour Type', 'xhosting-library' ),
            'search_items'  => esc_html__( 'Search Type', 'xhosting-library' ),
            'all_items'     => esc_html__( 'All Type', 'xhosting-library' ),
            'new_item_name' => esc_html__( 'New Type', 'xhosting-library' ),
            'add_new_item'  => esc_html__( 'Add New Type', 'xhosting-library' ),
            'edit_item'     => esc_html__( 'Edit New Type', 'xhosting-library' ),
            'update_item'   => esc_html__( 'Update New Type', 'xhosting-library' ),
        ];
        $args = array(
            'labels'                => $labels,
            'hierarchical'          => true,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'update_count_callback' => '_update_post_term_count',
        );
        register_taxonomy('tour_type', 'at_biz_dir', $args);
    }
    add_action( 'init', 'xhosting_tour_type_tax' );
}