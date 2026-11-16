<?php

// File Security Check
if (!defined('ABSPATH')) {
    exit;
}

function xhosting_theme_options_style()
{

    //
    // Enqueueing StyleSheet file
    //
    wp_enqueue_style('xhosting-dynamic-style', get_template_directory_uri() . '/assets/css/dynamic-style.css');
    $css_output = '';
    $thx_color_1 = cs_get_option('theme-color-1');
    $thx_color_2 = cs_get_option('theme-color-2');
    $thx_color_3 = cs_get_option('theme-color-3');
    $thx_color_4 = cs_get_option('theme-color-4');
    $thx_color_5 = cs_get_option('theme-color-5');
    $thx_color_6 = cs_get_option('theme-color-6');

    /**
     * Theme Color 1
     */
   
    if (!empty($thx_color_1)) {
        $css_output .= '       
        :root {
            --tg-theme-primary: ' . esc_attr($thx_color_1) . ';
        }            
        ';
    }
    if (!empty($thx_color_2)) {
        $css_output .= '       
        :root {
            --tg-theme-primary-2: ' . esc_attr($thx_color_2) . ';
        }            
        ';
    }
    if (!empty($thx_color_3)) {
        $css_output .= '       
        :root {
            --tg-theme-primary-3: ' . esc_attr($thx_color_3) . ';
        }            
        ';
    }
    if (!empty($thx_color_4)) {
        $css_output .= '       
        :root {
            --tg-theme-secondary: ' . esc_attr($thx_color_4) . ';
        }            
        ';
    }
    if (!empty($thx_color_5)) {
        $css_output .= '       
        :root {
            --tg-color-blue-default: ' . esc_attr($thx_color_5) . ';
        }            
        ';
    }
    if (!empty($thx_color_6)) {
        $css_output .= '       
        :root {
            --tg-color-yellow-default: ' . esc_attr($thx_color_6) . ';
        }            
        ';
    }
    
    wp_add_inline_style('xhosting-dynamic-style', $css_output);

}
add_action('wp_enqueue_scripts', 'xhosting_theme_options_style');
