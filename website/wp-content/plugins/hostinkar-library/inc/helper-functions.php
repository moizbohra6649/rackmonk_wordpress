<?php
/**
 * Summary of xhosting_body_class
 * @param mixed $classes
 * @return mixed
 */
function xhosting_body_class($classes){

        if (is_page()) {
            global $post;
            $pid = $post->ID;
            $meta = get_post_meta($pid, '_orixy_meta', 'true');
            $style_class = isset($meta['style']) && $meta['style'] ? $meta['style'] : '';
            $classes[] = $style_class;
        } else {
            $classes[] = 'ori-inner-page';
        }
        return $classes;
}
add_filter('body_class', 'xhosting_body_class');

/**
 * Summary of get_wp_image
 * @param mixed $source
 * @return mixed
 */
function get_wp_image($source){
  if (isset($source)){
      $image =  wp_get_attachment_image( $source['id'], 'full' );
  }
  return $image;

}
/**
 * Menu Select
 * @return array
 */
function king_menu_select_choices() {
  $menus = wp_get_nav_menus();
  $items = array();
  $i     = 0;
  foreach ( $menus as $menu ) {
      if ( $i == 0 ) {
          $default = $menu->slug;
          $i ++;
      }
      $items[ $menu->slug ] = $menu->name;
  }

  return $items;
}

/**
 * Rating Loop
 * @param mixed $count
 * @return string
 */
function client_ratings($count){
  $out = '';
  for ($i=0; $i<$count; $i++) {
      $out.= '<li><i class="fas fa-star"></i></li>';
  }
  return $out;
}

/**
 * Link Target
 * @param mixed $link
 * @return string
 */
function get_that_link($link){

  $url = $link['url'] ? 'href='.esc_url($link['url']). '' : '';
  $ext = $link['is_external'] ? 'target= _blank' : '';
  $nofollow = $link['nofollow'] ? 'rel="nofollow"' : '';
  $link = $url.' '.$ext.' '.$nofollow;
  return $link;
}

function get_that_image($source){
  if ($source){
      $image = '<img src="'. esc_url( $source['url'] ).'" alt="'.get_bloginfo( 'name' ).'">';
  }
  return $image;
}

/**
 * All Category List
 */
function xhosting_blog_category (){
  $terms = get_terms( array(
      'taxonomy'    => 'category',
      'hide_empty'  => true
  ) );

  $cat_list = [];
  foreach($terms as $post) {
  $cat_list[$post->slug]  = [$post->name];
  }
  return $cat_list;
}

/**
 * All Category List
 */
function xhosting_service_category (){
  $terms = get_terms( array(
      'taxonomy'    => 'services_cate',
      'hide_empty'  => true
  ) );

  $cat_list = [];
  foreach($terms as $post) {
  $cat_list[$post->slug]  = [$post->name];
  }
  return $cat_list;
}

function xhosting_post_category (){
  $terms = get_terms( array(
      'taxonomy'    => 'projects_cate',
      'hide_empty'  => true
  ) );

  $cat_list = [];
  foreach($terms as $post) {
  $cat_list[$post->slug]  = [$post->name];
  }
  return $cat_list;
}

function xhosting_project_slug (){
  $terms = get_terms( array(
      'taxonomy'    => 'projects_cate',
      'hide_empty'  => true
  ) );

  $cat_list = [];
  foreach($terms as $post) {
  $cat_list[$post->term_id]  = [$post->name];
  }
  return $cat_list;
}

/**
 * Tag List
 */
if ( ! function_exists( 'xhosting_item_tag_lists' ) ) {
  function xhosting_item_tag_lists(  $type = '', $query_args = array() ) {

    $options = array();

    switch( $type ) {

      case 'pages':
      case 'page':
      $pages = get_pages( $query_args );

      if ( !empty($pages) ) {
        foreach ( $pages as $page ) {
          $options[$page->post_title] = $page->ID;
        }
      }
      break;

      case 'posts':
      case 'post':
      $posts = get_posts( $query_args );

      if ( !empty($posts) ) {
        foreach ( $posts as $post ) {
          $options[$post->post_title] = lcfirst($post->ID);
        }
      }
      break;

      case 'tags':
      case 'tag':

      if (isset($query_args['taxonomies']) && taxonomy_exists($query_args['taxonomies'])) {
        $tags = get_terms( $query_args['taxonomies'] );
          if ( !is_wp_error($tags) && !empty($tags) ) {
            foreach ( $tags as $tag ) {
              $options[$tag->name] = $tag->term_id;
          }
        }
      }
      break;
    }

    return $options;

  }
}
function xhosting_reading_time() {	
	global $post;	
	$content = get_post_field( 'post_content', $post->ID );
	$word_count = str_word_count( strip_tags( $content ) );
	$readingtime = ceil($word_count / 200);
	if ($readingtime == 1) {
	$timer = esc_html(" min read");
	} else {
	$timer = esc_html(" min read");
	}
	$totalreadingtime = $readingtime . $timer;
	return $totalreadingtime;
}

/**
 * Post Time Ago
 */
function xhosting_ready_time_ago(){
  return human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) );
}

function xhosting_category_pl(){
  $catgorys = get_the_category();
  foreach( $catgorys as $key => $category):
      ?>
      <a class="cat" href="<?php echo esc_url(get_category_link($category->term_id)); ?>">
        <?php echo esc_html($category->cat_name); ?>
      </a>
  <?php endforeach;
}

/**
 * Post Social Share
 *
 * @return void
 */
function xhosting_post_share() {

  $permalink = get_permalink( get_the_ID() );
  $title     = get_the_title();
?>

<div class="header-dropdown-social blog__details-social">
    <span class="title"><?php esc_html_e( 'Share:', 'xhosting-tools' );?></span>
    <ul class="list-wrap">
        <li>
            <a aria-label="Facebook" class="facebook" onClick="window.open('http://www.facebook.com/sharer.php?u=<?php echo esc_url( $permalink ); ?>','Facebook','width=600,height=300,left='+(screen.availWidth/2-300)+',top='+(screen.availHeight/2-150)+''); return false;" href="http://www.facebook.com/sharer.php?u=<?php echo esc_url( $permalink ); ?>"><i class="fab fa-facebook"></i></a>
        </li>
        <li>
            <a aria-label="pinterest" href="https://www.pinterest.com/" target="_blank"><i class="fab fa-pinterest"></i></a>
        </li>
        <li>
            <a aria-label="github" href="https://github.com/" target="_blank"><i class="fab fa-github"></i></a>
        </li>
        <li>
            <a aria-label="youtube" href="https://www.youtube.com/" target="_blank"><i class="fab fa-youtube"></i></a>
        </li>
    </ul>
</div>

  
<?php 
}

/**
 * Get Menu List
 *
 * @return void
 */
function xhosting_get_nav_menu(){

  $menus = array(
      '' => esc_html__('Default', 'xhosting')
  );

  $obj_menus = wp_get_nav_menus();

  foreach ($obj_menus as $obj_menu){
      $menus[$obj_menu->term_id] = $obj_menu->name;
  }

  return $menus;
}


/**
 * Add Contact Method User
 */
function xhosting_user_social_link( $methode ) {
    $methode['facebook']  = esc_html__( 'Facebook', 'xhosting-tools' );
    $methode['twitter']   = esc_html__( 'Twitter', 'xhosting-tools' );
    $methode['linkedin']  = esc_html__( 'Linkedin', 'xhosting-tools' );
    $methode['instagram'] = esc_html__( 'Instagram', 'xhosting-tools' );
    $methode['pinterest'] = esc_html__( 'Pinterest', 'xhosting-tools' );
    $methode['youtube']   = esc_html__( 'YouTube', 'xhosting-tools' );
  return $methode;
}
add_filter('user_contactmethods', 'xhosting_user_social_link');



/**
 * Authore Avater
 */
function xhosting_post_author_avatars($size) {
  echo get_avatar(get_the_author_meta('email'), $size);
}

add_action('genesis_entry_header', 'xhosting_post_author_avatars');


function xhosting_menu_selector() {
  $menus = wp_get_nav_menus();
  $items = array();
  $i     = 0;
  foreach ( $menus as $menu ) {
      if ( $i == 0 ) {
          $default = $menu->slug;
          $i ++;
      }
      $items[ $menu->slug ] = $menu->name;
  }
  return $items;
}
function xhosting_portfolio_category(){
  $terms = get_terms( array(
    'taxonomy'    => 'portfolio_cat',
    'hide_empty'  => true,
  ) );

  $cat_list = [];
  foreach($terms as $post) {
  $cat_list[$post->slug]  = [$post->name];
  }
  return $cat_list;
}
function xhosting_careero_category(){
  $terms = get_terms( array(
    'taxonomy'    => 'career_cat',
    'hide_empty'  => true,
  ) );

  $cat_list = [];
  foreach($terms as $post) {
  $cat_list[$post->slug]  = [$post->name];
  }
  return $cat_list;
}

/**
 * Image Size Render
 */
function xhosting_img_size_control(){
    $image_sizes = get_intermediate_image_sizes(); 

    $addsizes = [
        'full' => 'Full size'
    ];
    $newsizes = array_merge($image_sizes, $addsizes);

    return array_combine($newsizes, $newsizes);
}

/**
 * 
 */
function xhosting_select_cat($tax) {

  $categories_obj = get_categories('taxonomy='.$tax.'');
  $categories = array();

  foreach ($categories_obj as $pn_cat) {
      $categories[$pn_cat->cat_ID] = $pn_cat->cat_name;
  }
  return $categories;         
}

/**
 * directory category
 */
function get_data_form_director($tax,$label,$name){
    $terms = get_terms($tax);
    $x = '<select name="'.$name.'">';
    $x .= '<option value="">' . $label . '</option>';
    foreach ($terms as $term) {
        $x .= '<option value="' . $term->term_id . '">' . $term->name . '</option>';
    }
    $x .= '</select>';
    return $x;
}

/**
 * 
 */
function xhosting_load_elementor_template(){
	$page_templates = get_posts( [
		'post_type'         => 'elementor_library',
		'posts_per_page'    => -1
	] );

	$options = [];

	if ( ! empty( $page_templates ) && ! is_wp_error( $page_templates ) ){
		foreach ( $page_templates as $template ) {
			$options[ $template->ID ] = $template->post_title;
		}
	}
	return $options;
}

function xhosting_elemento_display() {
	return \Elementor\Plugin::instance();
}

class Custom_Walker_Nav_Menu extends Walker_Nav_Menu {
  function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
      $classes = empty($item->classes) ? array() : (array) $item->classes;
      $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item));

      !empty($class_names) and $class_names = ' class="' . esc_attr($class_names) . '"';
      
      // Add data-menuanchor attribute
      $data_menuanchor = !empty($item->attr_title) ? ' data-menuanchor="' . esc_attr($item->attr_title) . '"' : '';

      $output .= '<li' . $class_names . $data_menuanchor .'>';

      $attributes  = !empty($item->attr_title) ? ' title="'  . esc_attr($item->attr_title) .'"' : '';
      $attributes .= !empty($item->target)     ? ' target="' . esc_attr($item->target)     .'"' : '';
      $attributes .= !empty($item->xfn)        ? ' rel="'    . esc_attr($item->xfn)        .'"' : '';
      $attributes .= !empty($item->url)        ? ' href="'   . esc_attr($item->url)        .'"' : '';

      $item_output = $args->before;
      $item_output .= '<a'. $attributes .'>';
      $item_output .= $args->link_before .apply_filters('the_title', $item->title, $item->ID) .$args->link_after;
      $item_output .= '</a>';
      $item_output .= $args->after;

      $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
  }
}

// Enable SVG uploads
function themex_mime_types($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
}
add_filter('upload_mimes', 'themex_mime_types');

// Sanitize SVG files
function themex_sanitize_svg($file) {
  if ($file['type'] == 'image/svg+xml') {
    $svg = simplexml_load_file($file['tmp_name']);

    if ($svg === false) {
      $file['error'] = 'Invalid SVG file';
      return $file;
    }

    // Remove any potentially dangerous elements or attributes
    $sanitized_svg = themex_sanitize_svg_content($svg);

    // Save the sanitized SVG back to the file
    file_put_contents($file['tmp_name'], $sanitized_svg->asXML());
  }

  return $file;
}
add_filter('wp_handle_upload_prefilter', 'themex_sanitize_svg');

// Sanitize SVG content
function themex_sanitize_svg_content($svg) {
  $dangerous_elements = ['script', 'iframe', 'object', 'embed'];
  foreach ($dangerous_elements as $element) {
    foreach ($svg->xpath('//' . $element) as $dangerous) {
      unset($dangerous[0]);
    }
  }

  return $svg;
}

if ( ! function_exists( 'xhosting_core_set' ) ) {

	function xhosting_core_set( $var, $key, $def = '' ) {

		/*if (!$var)

		return false;*/

		if ( is_object( $var ) && isset( $var->$key ) ) {

			return $var->$key;

		} elseif ( is_array( $var ) && isset( $var[ $key ] ) ) {

			return $var[ $key ];

		} elseif ( $def ) {

			return $def;

		} else {

			return false;

		}

	}

}


add_action('wp_ajax_xhosting_load_more_posts', 'xhosting_load_more_posts');
add_action('wp_ajax_nopriv_xhosting_load_more_posts', 'xhosting_load_more_posts');

function xhosting_load_more_posts() {
	check_ajax_referer('xhosting_blog_nonce', 'nonce');

	$paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
	$per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 3;
	$categories = isset($_POST['categories']) ? (array) $_POST['categories'] : [];

	$args = [
		'post_type' => 'post',
		'post_status' => 'publish',
		'posts_per_page' => $per_page,
		'paged' => $paged,
	];

	if (!empty($categories)) {
		$args['category__in'] = array_map('intval', $categories);
	}

	$query = new WP_Query($args);

	ob_start();

	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
			get_template_part('template-parts/content', 'ajax'); // Customize this
		}
		wp_reset_postdata();
	}

	$content = ob_get_clean();
	wp_send_json_success($content);
}


if ( ! function_exists( 'xhosting_get_category_lists' ) ) {
  function xhosting_get_category_lists(  $type = '', $query_args = array() ) {

    $options = array();

    switch( $type ) {

      case 'pages':
      case 'page':
      $pages = get_pages( $query_args );

      if ( !empty($pages) ) {
        foreach ( $pages as $page ) {
          $options[$page->post_title] = $page->ID;
        }
      }
      break;

      case 'posts':
      case 'post':
      $posts = get_posts( $query_args );

      if ( !empty($posts) ) {
        foreach ( $posts as $post ) {
          $options[$post->post_title] = lcfirst($post->ID);
        }
      }
      break;

      case 'tags':
      case 'tag':

      if (isset($query_args['taxonomies']) && taxonomy_exists($query_args['taxonomies'])) {
        $tags = get_terms( $query_args['taxonomies'] );
          if ( !is_wp_error($tags) && !empty($tags) ) {
            foreach ( $tags as $tag ) {
              $options[$tag->name] = $tag->term_id;
          }
        }
      }
      break;

      case 'categories':
      case 'category':

      if (isset($query_args['taxonomy']) && taxonomy_exists($query_args['taxonomy'])) {
        $categories = get_categories( $query_args );
          if ( !empty($categories) && is_array($categories) ) {

            foreach ( $categories as $category ) {
               $options[$category->name] = $category->term_id;
            }
          }
      }
      break;

    }

    return $options;

  }
}


/**
 * Ajax Loadmore Data
 */
function xhosting_post_ajax_loading_cb(){   
    $settings =  $_POST['ajax_json_data'];
    if ( get_query_var('paged') ) {
        $paged = get_query_var('paged');
    } else if ( get_query_var('page') ) {
        $paged = get_query_var('page');
    } else {
        $paged = 1;
    }
   $arg = [
      'post_type'   =>  'post',
      'post_status' => 'publish',
      'order'       => $settings['postorder'],
      'posts_per_page' => $settings['pst_per_page'],
      'paged'             => $_POST['paged'],
      'suppress_filters' => false,
    
  ];


   
  $allpostloding = new WP_Query($arg);
  $index = 0;
  $i =0;
  while($allpostloding->have_posts()){ $allpostloding->the_post(); 
    $post_id    = get_the_ID();
    $title      = get_the_title();
    $excerpt    = get_the_excerpt();
    $author_id  = get_the_author_meta('ID');
    $author_name = get_the_author();
    $author_url  = get_author_posts_url($author_id);
    $author_avatar = get_avatar_url($author_id, ['size' => 32]);
    $post_date  = get_the_date('d M, Y');
    $categories = get_the_category();
    $first_cat  = !empty($categories) ? $categories[0]->name : '';
    $first_cat_url = !empty($categories) ? get_category_link($categories[0]->term_id) : '#';
    $thumbnail = get_the_post_thumbnail_url($post_id, 'full') ?: 'https://via.placeholder.com/600x400?text=No+Image';
    $i++;
 ?>

<div class="col-lg-4 col-md-6 xhs-grid-item ajax__post-item">
    <div class="blog__post-item">
        <div class="blog__post-thumb">
            <a href="<?php the_permalink(); ?>">
                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($title); ?>">
            </a>
            <?php if ( $first_cat ) : ?>
                <a href="<?php echo esc_url($first_cat_url); ?>" class="blog__post-tag">
                    <?php echo esc_html($first_cat); ?>
                </a>
            <?php endif; ?>
        </div>
        <div class="blog__post-content">
        <h2 class="blog__post-title">
            <a href="<?php the_permalink(); ?>">
                <?php echo esc_html(wp_trim_words($title, $settings['title_length'], '...')); ?>
            </a>
        </h2>
        <p>
            <?php echo esc_html(wp_trim_words($excerpt, $settings['excerpt_length'], '...')); ?>
        </p>
        <div class="blog__post-meta">
            <ul class="list-wrap">
                <li class="blog__post-author">
                    <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author_name); ?>">
                    <a href="<?php echo esc_url($author_url); ?>">
                        <?php echo esc_html($author_name); ?>
                    </a>
                </li>
                <li><?php echo esc_html($post_date); ?></li>
            </ul>
        </div>
    </div>
    </div>
</div>
      
<?php
     $index ++;
  }
  wp_reset_postdata();
  wp_die();
  
}

add_action( 'wp_ajax_nopriv_xhosting_post_ajax_loading', 'xhosting_post_ajax_loading_cb' );
add_action( 'wp_ajax_xhosting_post_ajax_loading', 'xhosting_post_ajax_loading_cb' );
