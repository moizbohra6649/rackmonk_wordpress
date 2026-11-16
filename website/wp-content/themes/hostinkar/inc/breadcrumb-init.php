<?php

function xhosting_breadcrumb() {
    $wpbreadcrumb_class = '';
    $breadcrumb_show = 1;
    $id = get_the_ID();

    if (is_front_page() && is_home()) {
        $title = get_the_title();
        $wpbreadcrumb_class = 'tx-front-page';
    } elseif (is_front_page()) {
        $title = get_the_title();
        $breadcrumb_show = 0;
    } elseif (is_home()) {
        if (get_option('page_for_posts')) {
            $id = get_option('page_for_posts');
            $title = get_the_title($id);
        }
    } elseif (is_single() && 'post' == get_post_type()) {
        $title = get_the_title();
    } elseif (is_search()) {
        $title = esc_html__('Search Results for : ', 'hostinkar') . get_search_query();
    } elseif (is_404()) {
        $breadcrumb_show = 0;
    } elseif (function_exists('is_woocommerce') && is_shop()) {
        $breadcrumb_show = 0;
    } elseif (function_exists('is_woocommerce') && is_product()) {
        $title = __('Product Details', 'hostinkar');
    } elseif (function_exists('is_woocommerce') && is_product_tag()) {
        $title = get_the_archive_title();
    } elseif (function_exists('is_woocommerce') && is_product_category()) {
        $title = get_the_archive_title();
    } elseif (is_archive()) {
        $title = get_the_archive_title();
    } else {
        $title = get_the_title();
    }

    $page_meta = get_post_meta($id, 'xhosting_page_meta', true);
    $page_meta = is_array($page_meta) ? $page_meta : [];

    $enable_page_preadcrumb = isset($page_meta['enable_page_preadcrumb']) ? (bool) $page_meta['enable_page_preadcrumb'] : true;
    if (!$enable_page_preadcrumb || $breadcrumb_show == 0) {
        return;
    }

    $enable_custom_title = isset($page_meta['enable_custom_title']) ? (bool) $page_meta['enable_custom_title'] : false;
    $page_custom_title = isset($page_meta['page_custom_title']) ? $page_meta['page_custom_title'] : '';
    $title = $enable_custom_title ? $page_custom_title : $title;

    $breadcrumb_bg = cs_get_option('breadcrumb_bg');

    // Special layout for single post page
    if (is_single() && 'post' === get_post_type()) {
        ?>
        <!-- breadcrumb-area -->
        <section class="breadcrumb__area breadcrumb__area-two">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-md-10">
                        <div class="breadcrumb__inner-wrap">
                            <div class="breadcrumb__content">
                                <nav class="breadcrumb">
                                    <span property="itemListElement" typeof="ListItem">
                                        <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'hostinkar'); ?></a>
                                    </span>
                                    <span class="breadcrumb-separator">//</span>
                                    <span property="itemListElement" typeof="ListItem">
                                        <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>"><?php esc_html_e('Blog', 'hostinkar'); ?></a>
                                    </span>
                                    <span class="breadcrumb-separator">//</span>
                                    <span property="itemListElement" typeof="ListItem"><?php echo xhosting_wp_kses($title); ?></span>
                                </nav>
                                <h2 class="title"><?php echo xhosting_wp_kses($title); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!empty($breadcrumb_bg['url'])): ?>
                <div class="shape">
                    <img src="<?php echo esc_url($breadcrumb_bg['url']); ?>" alt="<?php echo !empty($breadcrumb_bg['alt']) ? esc_attr($breadcrumb_bg['alt']) : ''; ?>">
                </div>
            <?php endif; ?>
        </section>
        <?php
    } else {
        // Default layout for other pages
        ?>
        <section class="breadcrumb__area">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="breadcrumb__content">
                            <?php echo xhosting_breadcrumb_callback(); ?>
                            <h2 class="title"><?php echo xhosting_wp_kses($title); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (!empty($breadcrumb_bg['url'])): ?>
                <div class="shape">
                    <img src="<?php echo esc_url($breadcrumb_bg['url']); ?>" alt="<?php if (!empty($breadcrumb_bg['alt'])) { echo esc_attr($breadcrumb_bg['alt']); } ?>">
                </div>
            <?php endif; ?>
        </section>
        <?php
    }
}
add_action('xhosting_before_main_content', 'xhosting_breadcrumb');



function xhosting_breadcrumb_callback() {
    global $wp_query;
    $queried_object = get_queried_object();

    $breadcrumb = '<nav class="breadcrumb">';
    $delimiter = '<span class="breadcrumb-separator">//</span>';
    $before = '<span property="itemListElement" typeof="ListItem">';
    $after = '</span>';
    $items = [];

    if (!is_front_page()) {
        $items[] = $before . '<a href="' . home_url('/') . '">' . esc_html__('Home', 'hostinkar') . '</a>' . $after;

        if (is_category()) {
            $cat_obj = $wp_query->get_queried_object();
            $this_category = get_category($cat_obj->term_id);
            if ($this_category->parent != 0) {
                $parent_category = get_category($this_category->parent);
                $items[] = $before . get_category_parents($parent_category, false, $after . $delimiter . $before) . $after;
            }
            $items[] = $before . '<a href="' . get_category_link(get_query_var('cat')) . '">' . single_cat_title('', false) . '</a>' . $after;

        } elseif ($wp_query->is_posts_page) {
            $items[] = $before . $queried_object->post_title . $after;

        } elseif (is_tax()) {
            $items[] = $before . '<a href="' . get_term_link($queried_object) . '">' . $queried_object->name . '</a>' . $after;

        } elseif (is_page()) {
            global $post;
            if ($post->post_parent) {
                $anc = array_reverse(get_post_ancestors($post->ID));
                foreach ($anc as $ancestor) {
                    $items[] = $before . '<a href="' . get_permalink($ancestor) . '">' . get_the_title($ancestor) . '</a>' . $after;
                }
            }
            $items[] = $before . get_the_title() . $after;

        } elseif (is_singular()) {
            if ($category = wp_get_object_terms(get_the_ID(), array('category', 'location', 'tax_feature'))) {
                if (!is_wp_error($category)) {
                    $items[] = $before . '<a href="' . get_term_link($category[0]) . '">' . $category[0]->name . '</a>' . $after;
                }
            }
            $items[] = $before . get_the_title() . $after;

        } elseif (is_tag()) {
            $items[] = $before . '<a href="' . get_term_link($queried_object) . '">' . single_tag_title('', false) . '</a>' . $after;

        } elseif (is_day()) {
            $items[] = $before . esc_html__('Archive for ', 'hostinkar') . get_the_time('F jS, Y') . $after;

        } elseif (is_month()) {
            $items[] = $before . '<a href="' . get_month_link(get_the_time('Y'), get_the_time('m')) . '">' . __('Archive for ', 'hostinkar') . get_the_time('F, Y') . '</a>' . $after;

        } elseif (is_year()) {
            $items[] = $before . '<a href="' . get_year_link(get_the_time('Y')) . '">' . __('Archive for ', 'hostinkar') . get_the_time('Y') . '</a>' . $after;

        } elseif (is_author()) {
            $items[] = $before . '<a href="' . esc_url(get_author_posts_url(get_the_author_meta("ID"))) . '">' . __('Archive for ', 'hostinkar') . get_the_author() . '</a>' . $after;

        } elseif (is_search()) {
            $items[] = $before . esc_html__('Search Results for ', 'hostinkar') . get_search_query() . $after;

        } elseif (is_post_type_archive('product')) {
            $shop_page_id = wc_get_page_id('shop');
            if (get_option('page_on_front') !== $shop_page_id) {
                $_name = get_the_title($shop_page_id);
                if (!$_name) {
                    $product_post_type = get_post_type_object('product');
                    $_name = $product_post_type->labels->singular_name;
                }
                $items[] = $before . '<a href="' . get_post_type_archive_link('product') . '">' . $_name . '</a>' . $after;
            }

        } else {
            $items[] = $before . '<a href="' . get_permalink() . '">' . get_the_title() . '</a>' . $after;
        }
    }

    $breadcrumb .= implode($delimiter, $items);
    $breadcrumb .= '</nav>';

    return $breadcrumb;
}
