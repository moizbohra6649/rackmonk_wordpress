<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package xhosting
 */
add_filter('wpcf7_form_elements', function ($content) {
	$content = preg_replace('/<(span).*?class="\s*(?:.*\s)?wpcf7-form-control-wrap(?:\s[^"]+)?\s*"[^\>]*>(.*)<\/\1>/i', '\2', $content);

	return $content;
});
/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function xhosting_body_classes($classes){
	// Adds a class of hfeed to non-singular pages.
	if (!is_singular()) {
		$classes[] = 'hfeed';
	}

	// Adds a class of no-sidebar when there is no sidebar present.
	if (!is_active_sidebar('sidebar-1')) {
		$classes[] = 'no-sidebar';
	}

	return $classes;
}
add_filter('body_class', 'xhosting_body_classes');

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function xhosting_pingback_header()
{
	if (is_singular() && pings_open()) {
		printf('<link rel="pingback" href="%s">', esc_url(get_bloginfo('pingback_url')));
	}
}
add_action('wp_head', 'xhosting_pingback_header');


function xhosting_archive_html($links) {
    $links = '<div class="category-widget">' . $links; // Add opening div tag
    $links = str_replace('</a>&nbsp;(', '<span class="number">', $links);
    $links = str_replace(')', '</span> <span class="icon-1"></span></a>', $links);
    $links .= '</div>'; // Add closing div tag
    return $links;
}

add_filter('get_archives_link', 'xhosting_archive_html');



/**
 * Comment Link Class
 *
 * @param [type] $class
 * @return void
 */
function wpdocs_comment_reply_link_class($class)
{
	$class = str_replace("class='comment-reply-link", "class='comment-reply-link reply-btn", $class);
	return $class;
}

add_filter('comment_reply_link', 'wpdocs_comment_reply_link_class');

/**
 * Comment List Modification
 */

function xhosting_comments($comment, $args, $depth)
{
	$GLOBALS['comment'] = $comment; ?>

	<div <?php comment_class('comments-box-single wow fadeInUp'); ?> id="comment-<?php comment_ID() ?>">
		<?php if (get_avatar($comment)) { ?>
			<div class="person-img">
				<?php echo get_avatar($comment, 80); ?>
			</div>
		<?php } ?>
		<div class="comments-box-author-content">
			<div class="heading-wrap">
				<div class="title-wrap">
					<span class="name">
						<?php comment_author_link() ?>
					</span>
					<span class="date">
						<?php echo esc_html(get_the_time(get_option('date_format'))); ?>
					</span>
				</div>
				<?php comment_reply_link(array_merge($args, array('depth' => $depth, 'max_depth' => $args['max_depth'], 'reply_text' => wp_kses('Reply', true)))); ?>
			</div>
			<div class="comment-text">
				<?php comment_text(); ?>
			</div>
		</div>
	</div>

	<?php
}


/**
 * Comment Message Box
 */
function xhosting_comment_reform($arg)
{

	$arg['title_reply'] = esc_html__('Leave a comment', 'hostinkar');
	$arg['comment_field'] = '<div class="form-grp"> <textarea id="comment" class="fx-form-1-input" name="comment" cols="77" rows="3" placeholder="' . esc_attr__("Comment", "hostinkar") . '" aria-required="true"></textarea></div>';

	return $arg;

}
add_filter('comment_form_defaults', 'xhosting_comment_reform');


/**
 * Comment Form Field
 */
function xhosting_modify_comment_form_fields($fields)
{
	$commenter = wp_get_current_commenter();
	$req = get_option('require_name_email');

	$fields['author'] = '<div class="form-grp"><input type="text" name="author" id="author" value="' . esc_attr($commenter['comment_author']) . '" placeholder="' . esc_attr__("e.g. Oliver Spiteri", "hostinkar") . '" size="22" tabindex="1"' . ($req ? 'aria-required="true"' : '') . ' class="form_control" /></div>';

	$fields['email'] = '<div class="form-grp"><input  type="email" name="email" id="email" value="' . esc_attr($commenter['comment_author_email']) . '" placeholder="' . esc_attr__("Email address", "hostinkar") . '" size="22" tabindex="2"' . ($req ? 'aria-required="true"' : '') . ' class="form_control"  /></div>';

	$fields['url'] = '<div class="form-grp url"><input type="url" name="url" id="url" value="' . esc_attr($commenter['comment_author_url']) . '" placeholder="' . esc_attr__("byteflows.com", "hostinkar") . '" size="22" tabindex="2"' . ($req ? 'aria-required="false"' : '') . ' class="form_control"  /></div>';

	return $fields;
}
add_filter('comment_form_default_fields', 'xhosting_modify_comment_form_fields');

// comment Move Field
function xhosting_move_comment_field_to_bottom($fields)
{
	$comment_field = $fields['comment'];
	unset($fields['comment']);
	$fields['comment'] = $comment_field;
	return $fields;
}
add_filter('comment_form_fields', 'xhosting_move_comment_field_to_bottom');



/**
 * Product Per Page Count
 *
 * @param [type] $per_page
 * @return void
 */


/**
 * Authore Avater
 */
function xhosting_main_author_avatars($size){
	echo get_avatar(get_the_author_meta('email'), $size);
}

add_action('genesis_entry_header', 'xhosting_post_author_avatars');




/**
 * pagination
 */
if (!function_exists('xhosting_pagination')) {

	function _xhosting_pagi_callback($pagination)
	{
		return $pagination;
	}

	//page navegation
	function xhosting_pagination($prev, $next, $pages, $args)
	{
		global $wp_query, $wp_rewrite;
		$menu = '';
		$wp_query->query_vars['paged'] > 1 ? $current = $wp_query->query_vars['paged'] : $current = 1;

		if ($pages == '') {
			global $wp_query;
			$pages = $wp_query->max_num_pages;

			if (!$pages) {
				$pages = 1;
			}

		}

		$pagination = [
			'base' => add_query_arg('paged', '%#%'),
			'format' => '',
			'total' => $pages,
			'current' => $current,
			'prev_text' => $prev,
			'next_text' => $next,
			'type' => 'array',
		];

		//rewrite permalinks
		if ($wp_rewrite->using_permalinks()) {
			$pagination['base'] = user_trailingslashit(trailingslashit(remove_query_arg('s', get_pagenum_link(1))) . 'page/%#%/', 'paged');
		}

		if (!empty($wp_query->query_vars['s'])) {
			$pagination['add_args'] = ['s' => get_query_var('s')];
		}

		$pagi = '';
		if (paginate_links($pagination) != '') {
			$paginations = paginate_links($pagination);
			$pagi .= '<nav class="Page navigation example"><ul class="pagination">';
			foreach ($paginations as $key => $pg) {
				$pagi .= '<li class="page-item">' . $pg . '</li>';
			}
			$pagi .= '</ul></nav>';
		}

		print _xhosting_pagi_callback($pagi);
	}
}


/**
 * Search Widget
 */
function xhosting_search_widgets($form)
{
	$form = '<div class="search-widget"><form method="get" id="searchform" class="sidebar-search-box" action="' . home_url('/') . '" >
    	<input class="search-input" placeholder="' . esc_attr__('Search...', 'hostinkar') . '" type="text"  value="' . get_search_query() . '" name="s" id="s" />
		<button type="submit" aria-label="search" class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form></div>';
	return $form;
}
add_filter('get_search_form', 'xhosting_search_widgets', 100);


/**
 * Authore
 */
function xhosting_authore_info()
{

	global $post;
	if (is_object($post)):

		$theme_author_markup = '';
		// Get author's display name
		$display_name = get_the_author_meta('display_name', $post->post_author);

		// If display name is not available then use nickname as display name
		if (empty($display_name))
			$display_name = get_the_author_meta('nickname', $post->post_author);

		// Get author's biographical information or description
		$user_description = get_the_author_meta('user_description', $post->post_author);

		$user_facebook = get_the_author_meta('facebook', $post->post_author);
		$user_twitter = get_the_author_meta('twitter', $post->post_author);
		$user_linkedin = get_the_author_meta('linkedin', $post->post_author);
		$user_instagram = get_the_author_meta('instagram', $post->post_author);
		$user_pinterest = get_the_author_meta('pinterest', $post->post_author);
		$user_youtube = get_the_author_meta('youtube', $post->post_author);

		// Get link to the author archive page
		$user_posts = get_author_posts_url(get_the_author_meta('ID', $post->post_author));
		if (!empty($display_name))
			// Author avatar - - the number 90 is the px size of the image.
			$theme_author_markup .= '<div class="inner-img">' . get_avatar(get_the_author_meta('ID'), 130) . '</div>';
		$theme_author_markup .= '<div class="inner-text headline pera-content">';
		$theme_author_markup .= '<div class="admin-title-social"><h4 class="ct-heading-1 name">' . $display_name . '</h4></div>';
		$theme_author_markup .= '<p>' . get_the_author_meta('description') . '</p>';

		$theme_author_markup .= '</div>';

		// Pass all this info to post content
		echo '<div class="art-blog-details-author flex-wrap d-flex flex-wrap align-items-center">' . $theme_author_markup . '</div>';


	endif;
}

function xhosting_wp_kses( $val ) {
    return wp_kses( $val, array(

        'p'      => array(
            'class' => array(),
            'style' => array(),
        ),

        'img'    => array(
            'src'   => array(),
            'alt'   => array(),
            'class' => array(),
            'style' => array(),
        ),
        'span'   => array(
            'class' => array(),
            'style' => array(),
        ),
        'svg'   => array(
            'class' => array(),
            'style' => array(),
        ),
        'small'  => array(),
        'div'    => array(
            'style' => array(),
            'br' => array(),
            'class' => array(),
        ),
        'strong' => array(
            'style' => array(),
        ),
        'b'      => array(
            'style' => array(),
        ),
        'br'     => array(),
        'h1'     => array(
            'style' => array(),
        ),
        'i'      => array(
            'class' => array(),
            'style' => array(),
        ),
        'ul'     => array(
            'class' => array(),
            'style' => array(),
        ),
        'ul'     => array(
            'id' => array(),
        ),
        'li'     => array(
            'class' => array(),
            'style' => array(),
        ),
        'li'     => array(
            'id' => array(),
        ),
        'sub'     => array(
            'id' => array(),
        ),
        'h1'     => array(
            'style' => array(),
        ),
        'h2'     => array(),
        'h3'     => array(
            'style' => array(),
        ),
        'h4'     => array(
            'style' => array(),
        ),
        'h5'     => array(
            'style' => array(),
        ),
        'h6'     => array(
            'style' => array(),
        ),
        'a'      => array(
            'href' => array(),
            'target' => array(),
            'style' => array(),
            'class' => array(),
        ),
        'iframe' => array( 'src' => array(), 'height' => array(), 'width' => array() ),

    ), '' );
}


/**
 * Product Per Page Count
 *
 * @param [type] $per_page
 * @return void
 */

 function xhosting_product_count( $cols ) {
	$product_count = cs_get_option('product_count');
  	$cols = !empty($product_count) ? $product_count : '12';
  	return $cols;
}
add_filter( 'loop_shop_per_page', 'xhosting_product_count', 20 );
function xhosting_custom_class( $classes ) {
    $page_meta = get_post_meta( get_the_ID(), 'xhosting_page_meta', true ) ? get_post_meta( get_the_ID(), 'xhosting_page_meta', true ) : [];
    $bodycsl = array_key_exists( 'select-body-cls', $page_meta ) ? $page_meta['select-body-cls'] : false;
    $body_class = $bodycsl == true ? $bodycsl : '';
    $classes[] = '' . esc_attr( $body_class ) . '';
    return $classes;
}
add_filter( 'body_class', 'xhosting_custom_class' );
function xhosting_dark_class( $classes ) {
    $page_meta = get_post_meta( get_the_ID(), 'xhosting_page_meta', true ) ? get_post_meta( get_the_ID(), 'xhosting_page_meta', true ) : [];
    $bodycsl = array_key_exists( 'select-dark-cls', $page_meta ) ? $page_meta['select-dark-cls'] : false;
    $body_class = $bodycsl == true ? $bodycsl : '';
    $classes[] = '' . esc_attr( $body_class ) . '';
    return $classes;
}
add_filter( 'body_class', 'xhosting_dark_class' );


function xhosting_img_alt_text( $img_er_id = null ) {
    if ( empty( $img_er_id ) ) {
        return esc_html__( 'Image Alt Text', 'hostinkar' );
    }

    // Convert URL to attachment ID if necessary
    if ( filter_var( $img_er_id, FILTER_VALIDATE_URL ) ) {
        $img_er_id = attachment_url_to_postid( $img_er_id );
    }

    $image_alt = get_post_meta( $img_er_id, '_wp_attachment_image_alt', true );
    $alt_text = ! empty( $image_alt ) ? $image_alt : get_the_title( $img_er_id );

    return $alt_text;
}
