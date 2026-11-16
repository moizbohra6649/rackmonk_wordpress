<?php

/**
 * [Xhosting_Admin description]
 */
if (!class_exists('Xhosting_Admin')) {
    class Xhosting_Admin{

        private static $instance = null;

        /**
         * register instance
         *
         * @return void
         */
        public static function init(){
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * init Construct
         */
        public function __construct(){
            add_action('init', [$this, 'xhosting_tgm_dashboard'], 1);
            add_action('admin_menu', [$this, 'xhosting_Admin_dashboard'], 1);
            add_action('admin_menu', [$this, 'xhosting_template_dashboard'], 20);
            add_action('ocdi/plugin_page_setup', [$this, 'xhosting_import_dsb'], 20);
            add_action('admin_enqueue_scripts', array($this, 'xhosting_Admin_enqueue_scripts'));
            add_action( 'admin_menu', [$this, 'xhosting_megamenu_dashboard'], 21);
        }

        /**
         * Admin Dashboard
         *
         * @return void
         */
        public function xhosting_Admin_dashboard(){
            add_menu_page(
                esc_html__('Rackmonk', 'hostinkar'),
                esc_html__('Rackmonk', 'hostinkar'),
                'manage_options',
                'hostinkar',
                [$this, 'display_xhosting_Admin_dashboard'],
                get_template_directory_uri() . '/inc/admin/assets/img/favicon.png',
                2
            );


        }

        /**
         * Template Dashboard
         *
         * @return void
         */
        public function xhosting_template_dashboard() {
            add_submenu_page(
                'hostinkar',
                esc_html__('Templates', 'hostinkar'),
                esc_html__('Templates', 'hostinkar'),
                'manage_options',
                'edit.php?post_type=xhosting_template',
                false
            );
        }

        /**
         * admin style Add
         */
        public function xhosting_Admin_enqueue_scripts()
        {
            wp_enqueue_style('xhosting-admin', get_theme_file_uri('inc/admin/assets/css/admin.css'), array(), null, 'all');
        }

        public function display_xhosting_Admin_dashboard()
        {
            require_once XHOSTING_INC_DRI . 'admin/admin-page.php';
        }

        public function xhosting_tgm_dashboard()
        {
            require_once XHOSTING_INC_DRI . 'admin/class-tgm-plugin-activation.php';
            require_once XHOSTING_INC_DRI . 'admin/plugin-activation.php';
        }

        public function xhosting_import_dsb($default)
        {
            $default['parent_slug'] = 'hostinkar';
            $default['page_title'] = esc_html__('One Click Demo Import', 'hostinkar');
            $default['menu_title'] = esc_html__('Import Demo Data', 'hostinkar');
            $default['capability'] = 'import';
            $default['menu_slug'] = 'one-click-demo-import';

            return $default;
        }

         /**
         * Megamenu Elementor Template
         *
         * @return  [type]  [return description]
         */
        public function xhosting_megamenu_dashboard(){
            add_submenu_page(
                'hostinkar',
                esc_html__( 'Mega Menu', 'hostinkar' ),
                esc_html__( 'Mega Menu', 'hostinkar' ),
                'manage_options',
                'edit.php?post_type=th-mega-menu',
                false
            );
        }
    }
    Xhosting_Admin::init();
}
