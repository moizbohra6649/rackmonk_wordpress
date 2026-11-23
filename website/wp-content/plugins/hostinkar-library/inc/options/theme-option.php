<?php
/*
 * Theme Options
 * @package xhosting
 * @since 1.0.0
 * */

if (!defined('ABSPATH')) {
    exit(); // exit if access directly
}

if (class_exists('CSF')) {

    //
    // Set a unique slug-like ID
    $prefix = 'xhosting';

    //
    
    CSF::createOptions( $prefix . '_theme_options', array(
        'menu_title'         => 'Rackmonk Options',
        'menu_slug'          => 'xhosting-theme-option',
        'menu_type'          => 'menu',
        'enqueue_webfont'    => true,
        'show_in_customizer' => true,
        'menu_icon' => 'dashicons-category',
        'menu_position' => 3,
        'theme'                   => 'light',
        'framework_title'    => wp_kses_post( 'Rackmonk Options' ),
        'footer_text'    => wp_kses_post( 'The Theme will Created By byteflows ' ),
    ) );

    // Create a top-tab
    CSF::createSection($prefix . '_theme_options', array(
        'id' => 'header_opts', // Set a unique slug-like ID
        'title' => 'Header',
    ));


    /*-------------------------------------------------------
     ** Logo Settings  Options
    --------------------------------------------------------*/

    /*-------------------------------------------------------
     ** Header  Options
    --------------------------------------------------------*/

    CSF::createSection($prefix . '_theme_options', array(
        'title' => 'General Settings',
        'id' => 'general_settings',
        'icon' => 'fa fa-refresh',
        'fields' => array(

            array(
                'id' => 'preloader_enable',
                'title' => esc_html__('Enable Preloader', 'xhosting-tools'),
                'type' => 'switcher',
                'desc' => esc_html__('Enable or Disable Preloader', 'xhosting-tools'),
                'default' => true,
            ),
            array(
                'id'      => 'xh_custom_preloader',
                'title'   => esc_html__( 'Upload Preloader', 'xhosting-tools' ),
                'type'    => 'media',
                'dependency' => array( 'preloader_enable', '==', 'true' ),
            ),
            
            array(
                'id' => 'scroll_up_btn',
                'title' => esc_html__('Scroll Up SHOW/HIDE', 'xhosting-tools'),
                'type' => 'switcher',
                'desc' => esc_html__('Enable or Disable Scroll UP', 'xhosting-tools'),
                'default' => true,
            ),
            

        ),
    ));

    CSF::createSection($prefix . '_theme_options', array(
        'title' => esc_html__('Header', 'xhosting-tools'),
        'parent' => 'header_opts',
        'icon' => 'fa fa-header',
        'fields' => array(
            array(
                'type' => 'subheading',
                'content' => '<h3>' . esc_html__('Header Layout', 'xhosting-tools') . '</h3>',
            ),

            array(
                'id' => 'header_style',
                'type' => 'select',
                'title' => __('Select Header Style', 'xhosting-tools'),
                'options' => Xhosting_Plugin_Helper::get_header_types(),
                'desc' => sprintf(
                    __('%sClick%s to create New Header', 'xhosting-tools'),
                    '<a href="' . admin_url('edit.php?post_type=xhosting_template') . '" target="_blank">',
                    '</a>'
                ),
            )

        ),
    ));


    /*-------------------------------------
     ** Typography Options
    -------------------------------------*/
    CSF::createSection($prefix . '_theme_options', array(
        'title' => esc_html__('Typography', 'xhosting-tools'),
        'id' => 'typography_options',
        'icon' => 'fa fa-font',
        'fields' => array(

            array(
                'type' => 'subheading',
                'content' => '<h3>' . esc_html__('Body', 'xhosting-tools') . '</h3>',
            ),

            array(
                'id' => 'body-typography',
                'type' => 'typography',
                'output' => 'body, p',
                'output_important' => '!important '

            ),

            array(
                'type' => 'subheading',
                'content' => '<h3>' . esc_html__('Heading', 'xhosting-tools') . '</h3>',
            ),

            array(
                'id' => 'heading-gl-typo',
                'type' => 'typography',
                'output' => 'h1, h2, h3, h4, h5, h6',
                'output_important' => '!important '
            ),

        ),
    ));

    // blog optoins
    CSF::createSection($prefix . '_theme_options', array(
        'title' => esc_html__('Blog', 'xhosting-tools'),
        'id' => 'blog_page',
        'icon' => 'fa fa-rss-square',
        'fields' => array(

            array(
                'type' => 'subheading',
                'content' => '<h3>' . esc_html__('Blog Options', 'xhosting-tools') . '</h3>',
            ),

            array(
                'id' => 'breadcrumb_bg',
                'type' => 'media',
                'title' => esc_html__('Single Breadcrumb BG', 'xhosting-tools'),
            ),

            array(
                'id' => 'blog_btn_text',
                'type' => 'text',
                'title' => esc_html__('Blog Read More Button', 'xhosting-tools'),
                'default' => esc_html__('Explore More', 'xhosting-tools'),
                'desc' => esc_html__('Type Blog Read More Button Text Here', 'xhosting-tools'),
            ),
        ),
    ));
    // blog optoins
    CSF::createSection($prefix . '_theme_options', array(
        'title' => esc_html__('WHMCS URL', 'xhosting-tools'),
        'id' => 'whmcs_urls',
        'icon' => 'fa fa-rss-square',
        'fields' => array(

            array(
                'id' => 'whmcs_portal_url',
                'type' => 'text',
                'title' => esc_html__('WHMCS URL', 'xhosting-tools'),
                'desc' => esc_html__('We added a WHMCS Integration option in the Theme Option, just add your <a href="admin.php?page=xhosting-framework#tab=whmcs-integration">WHMCS URL here</a>', 'xhosting-tools'),
            ),
        ),
    ));
    // blog optoins
    CSF::createSection($prefix . '_theme_options', array(
        'title' => esc_html__('Breadcrumb', 'xhosting-tools'),
        'id' => 'breadcrumb_page',
        'icon' => 'fa fa-rss-square',
        'fields' => array(

            array(
                'id' => 'breadcrumb_bg',
                'type' => 'media',
                'title' => esc_html__('Breadcrumb Shape BG', 'xhosting-tools'),
            ),
        ),
    ));


    // xhosting Color Setting
    CSF::createSection($prefix . '_theme_options', array(
        'title' => 'Color Control',
        'id' => 'apix_color_control',
        'icon' => 'fa fa-paint-brush',
        'fields' => array(


            array(  //nav bar one start
                'type' => 'subheading',
                'content' => '<h3>' . esc_html__('Theme Global Color', 'xhosting-tools') . '</h3>',
            ),
            array(
                'id' => 'theme-color-1',
                'type' => 'color',
                'title' => 'Theme Color One',
                'default' => '#17BD54'
            ),
            array(
                'id' => 'theme-color-2',
                'type' => 'color',
                'title' => 'Theme Color Two',
                'default' => '#3795FC'
            ),
            array(
                'id' => 'theme-color-3',
                'type' => 'color',
                'title' => 'Theme Color Three',
                'default' => '#FF4C4C'
            ),
            array(
                'id' => 'theme-color-4',
                'type' => 'color',
                'title' => 'Theme Color Four',
                'default' => '#6F56DA'
            ),
            array(
                'id' => 'theme-color-5',
                'type' => 'color',
                'title' => 'Theme Color Five',
                'default' => '#644DC5'
            ),
            array(
                'id' => 'theme-color-6',
                'type' => 'color',
                'title' => 'Theme Color Five',
                'default' => '#FFD247'
            ),
            
        ),
    ));

    // Create a section
    CSF::createSection($prefix . '_theme_options', array(
        'title' => 'Error Page',
        'id' => 'error_page',
        'icon' => 'fa fa-exclamation-triangle',
        'fields' => array(

            array(
                'id' => 'error_code_img',
                'type' => 'media',
                'title' => esc_html__('Error Code Image', 'xhosting-tools'),
            ),

            array(
                'id' => 'error_shape_img',
                'type' => 'media',
                'title' => esc_html__('Error Shape Image', 'xhosting-tools'),
            ),
            array(
                'id' => 'error_title',
                'type' => 'text',
                'title' => esc_html__('404 Title', 'xhosting-tools'),
                'default' => esc_html__('Oops! Page can’t be found.', 'xhosting-tools'),
            ),
            array(
                'id' => 'error_text',
                'type' => 'text',
                'title' => esc_html__('404 Title', 'xhosting-tools'),
                'default' => esc_html__('The page you are looking for doesnt exist or has been moved.', 'xhosting-tools'),
            ),
            
            array(
                'id' => 'error_button',
                'type' => 'text',
                'title' => esc_html__('404 Button', 'xhosting-tools'),
                'default' => esc_html__('Back To Home', 'xhosting-tools'),
            )


        ),
    ));


    /*-------------------------------------------------------
     ** Footer  Options
    --------------------------------------------------------*/

    $rm_url_help = __('A page address like <code>/web-hosting/</code>, a full link (<code>https://…</code>), or <code>#</code> for no link.', 'xhosting-tools');
    $rm_link_group = static function ($id, $title, $desc = '') use ($rm_url_help) {
        return array(
            'id' => $id,
            'type' => 'group',
            'title' => $title,
            'subtitle' => $desc,
            'button_title' => esc_html__('Add link', 'xhosting-tools'),
            'fields' => array(
                array('id' => 'label', 'type' => 'text', 'title' => esc_html__('Text', 'xhosting-tools')),
                array('id' => 'url', 'type' => 'text', 'title' => esc_html__('Link', 'xhosting-tools'), 'desc' => $rm_url_help),
            ),
        );
    };

    $rm_footer_fields = array(
        array(
            'type' => 'notice',
            'style' => 'info',
            'content' => __('Everything below fills the live website footer. Saving here updates the footer template, and edits made to that template in Elementor show up here. The layout and design stay the same. Clear a field to hide it.', 'xhosting-tools'),
        ),
        array(
            'id' => 'footer_style',
            'type' => 'select',
            'title' => __('Select Footer Style', 'xhosting-tools'),
            'desc' => sprintf(
                __('%sClick%s to create New Footer', 'xhosting-tools'),
                '<a href="' . admin_url('edit.php?post_type=xhosting_template') . '" target="_blank">',
                '</a>'
            ),
            'options' => Xhosting_Plugin_Helper::get_footer_types(),
        ),

        array('type' => 'subheading', 'content' => esc_html__('Offer banner (top of the footer)', 'xhosting-tools')),
        array(
            'id' => 'footer_cta_title',
            'type' => 'text',
            'title' => esc_html__('Offer text', 'xhosting-tools'),
            'desc' => __('Wrap words in <code>&lt;span&gt;…&lt;/span&gt;</code> to highlight them, e.g. Get up to <code>&lt;span&gt;75%&lt;/span&gt;</code> off.', 'xhosting-tools'),
        ),
        array('id' => 'footer_cta_btn_label', 'type' => 'text', 'title' => esc_html__('Button text', 'xhosting-tools')),
        array('id' => 'footer_cta_btn_link', 'type' => 'text', 'title' => esc_html__('Button link', 'xhosting-tools'), 'desc' => $rm_url_help),

        array('type' => 'subheading', 'content' => esc_html__('Logo & about text', 'xhosting-tools')),
        array('id' => 'footer_logo', 'type' => 'media', 'library' => 'image', 'title' => esc_html__('Footer logo', 'xhosting-tools'), 'desc' => esc_html__('Light logo for the dark footer.', 'xhosting-tools')),
        array('id' => 'footer_about', 'type' => 'textarea', 'title' => esc_html__('About text', 'xhosting-tools')),

        array('type' => 'subheading', 'content' => esc_html__('Social links', 'xhosting-tools')),
        array('id' => 'footer_social_title', 'type' => 'text', 'title' => esc_html__('Heading', 'xhosting-tools')),
    );
    if (class_exists('Rackmonk_Footer_Content')) {
        foreach (Rackmonk_Footer_Content::NETWORKS as $rm_key => $rm_network) {
            $rm_footer_fields[] = array(
                'id' => 'footer_social_' . $rm_key,
                'type' => 'text',
                'title' => $rm_network[0],
                'placeholder' => 'https://',
                'desc' => esc_html__('Profile link. Leave empty to hide this icon.', 'xhosting-tools'),
            );
        }
    }
    $rm_footer_fields = array_merge($rm_footer_fields, array(
        array('type' => 'subheading', 'content' => esc_html__('Contact details', 'xhosting-tools')),
        array('id' => 'footer_contact_title', 'type' => 'text', 'title' => esc_html__('Heading', 'xhosting-tools')),
        array('id' => 'footer_address', 'type' => 'textarea', 'title' => esc_html__('Address', 'xhosting-tools'), 'desc' => esc_html__('Opens Google Maps when clicked.', 'xhosting-tools')),
        array('id' => 'footer_phone', 'type' => 'text', 'title' => esc_html__('Phone', 'xhosting-tools'), 'desc' => esc_html__('Separate more than one number with a comma.', 'xhosting-tools')),
        array('id' => 'footer_email', 'type' => 'text', 'title' => esc_html__('Email', 'xhosting-tools'), 'desc' => esc_html__('Separate more than one address with a comma.', 'xhosting-tools')),

        array('type' => 'subheading', 'content' => esc_html__('Link columns', 'xhosting-tools')),
        array('id' => 'footer_col1_show', 'type' => 'switcher', 'title' => esc_html__('Show column 1', 'xhosting-tools'), 'default' => true),
        array('id' => 'footer_col1_title', 'type' => 'text', 'title' => esc_html__('Column 1 heading', 'xhosting-tools'), 'dependency' => array('footer_col1_show', '==', 'true')),
        $rm_link_group('footer_col1_links', esc_html__('Column 1 links', 'xhosting-tools')) + array('dependency' => array('footer_col1_show', '==', 'true')),
        array('id' => 'footer_col2_show', 'type' => 'switcher', 'title' => esc_html__('Show column 2', 'xhosting-tools'), 'desc' => esc_html__('Turn off to hide this column on the website. Its links are kept, so you can show it again any time.', 'xhosting-tools'), 'default' => true),
        array('id' => 'footer_col2_title', 'type' => 'text', 'title' => esc_html__('Column 2 heading', 'xhosting-tools'), 'dependency' => array('footer_col2_show', '==', 'true')),
        $rm_link_group('footer_col2_links', esc_html__('Column 2 links', 'xhosting-tools')) + array('dependency' => array('footer_col2_show', '==', 'true')),
        array('id' => 'footer_col3_show', 'type' => 'switcher', 'title' => esc_html__('Show column 3', 'xhosting-tools'), 'default' => true),
        array('id' => 'footer_col3_title', 'type' => 'text', 'title' => esc_html__('Column 3 heading', 'xhosting-tools'), 'dependency' => array('footer_col3_show', '==', 'true')),
        $rm_link_group('footer_col3_links', esc_html__('Column 3 links', 'xhosting-tools')) + array('dependency' => array('footer_col3_show', '==', 'true')),

        array('type' => 'subheading', 'content' => esc_html__('Payment & support bar', 'xhosting-tools')),
        array('id' => 'footer_payment_title', 'type' => 'text', 'title' => esc_html__('Payment heading', 'xhosting-tools')),
        array('id' => 'footer_payment_image', 'type' => 'media', 'library' => 'image', 'title' => esc_html__('Payment methods image', 'xhosting-tools')),
        array('id' => 'footer_chat_label', 'type' => 'text', 'title' => esc_html__('Support button text', 'xhosting-tools')),
        array('id' => 'footer_chat_link', 'type' => 'text', 'title' => esc_html__('Support button link', 'xhosting-tools'), 'desc' => $rm_url_help),
        array('id' => 'footer_languages', 'type' => 'textarea', 'title' => esc_html__('Language list', 'xhosting-tools'), 'desc' => esc_html__('One language per line. Leave empty to hide the language drop-down.', 'xhosting-tools')),

        array('type' => 'subheading', 'content' => esc_html__('Bottom bar', 'xhosting-tools')),
        array(
            'id' => 'footer_copyright',
            'type' => 'textarea',
            'title' => esc_html__('Copyright text', 'xhosting-tools'),
            'desc' => __('<code>[rackmonk_year]</code> shows the current year. Simple HTML such as <code>&lt;strong&gt;</code> is allowed. Also used by the built-in footer when no footer template is selected.', 'xhosting-tools'),
            'default' => __('© [rackmonk_year] Rackmonk - High Performance Hosting. All rights reserved.', 'xhosting-tools'),
        ),
        $rm_link_group('footer_legal_links', esc_html__('Bottom links', 'xhosting-tools'), esc_html__('Terms, refund and privacy policy links.', 'xhosting-tools')),
    ));

    CSF::createSection($prefix . '_theme_options', array(
        'title' => esc_html__('Footer Options', 'xhosting-tools'),
        'icon' => 'fa fa-copyright',
        'fields' => $rm_footer_fields,
    ));
    unset($rm_url_help, $rm_link_group, $rm_footer_fields, $rm_key, $rm_network);

    // Backup section
    CSF::createSection($prefix . '_theme_options', array(
        'title' => esc_html__('Backup Export', 'xhosting-tools'),
        'id' => 'backup_options',
        'icon' => 'fa fa-window-restore',
        'fields' => array(
            array(
                'type' => 'backup',
            ),
        ),
    ));




}
